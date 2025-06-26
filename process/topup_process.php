<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/paydisini.php';

// Cek login
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../wallet.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$amount = (int)($_POST['amount'] ?? 0);

// Validasi amount
if ($amount < 10000 || $amount > 5000000) {
    $_SESSION['notification'] = [
        'type' => 'danger',
        'message' => 'Nominal tidak valid. Minimal Rp 10.000, maksimal Rp 5.000.000'
    ];
    header('Location: ../wallet.php');
    exit;
}

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Create Midtrans transaction
    require_once '../includes/midtrans.php';
    $midtrans = new Midtrans();
    $response = $midtrans->createSnapToken($amount, $user_id, $user['name'], $user['email']);
    
    if ($response['success'] === true) {
        // Simpan data transaksi Midtrans
        $stmt = $pdo->prepare("
            INSERT INTO paydisini_transactions 
            (user_id, paydisini_trx_id, amount, fee, status, payment_method, qr_code, checkout_url, expired_at) 
            VALUES (?, ?, ?, ?, 'pending', 'midtrans', ?, ?, ?)
        ");
        
        $expired_at = date('Y-m-d H:i:s', strtotime('+24 hours')); // Midtrans has longer expiry
        $stmt->execute([
            $user_id,
            $response['data']['order_id'],
            $amount,
            0, // No fee
            $response['data']['snap_token'],
            $response['data']['redirect_url'],
            $expired_at
        ]);
        
        // Buat transaksi saldo dalam status pending
        $wallet = new WalletManager($pdo);
        $currentBalance = $wallet->getBalance($user_id);
        
        $stmt = $pdo->prepare("
            INSERT INTO balance_transactions 
            (user_id, transaction_type, amount, balance_before, balance_after, reference_id, reference_type, status, description) 
            VALUES (?, 'deposit', ?, ?, ?, ?, 'paydisini', 'pending', 'Top Up Saldo via QRIS')
        ");
        $stmt->execute([
            $user_id,
            $amount,
            $currentBalance,
            $currentBalance,
            $response['data']['order_id']
        ]);
        
        // Cek response dari Midtrans
        if (isset($response['data']['order_id'])) {
            // Redirect ke halaman pembayaran dengan Snap token
            header('Location: ../payment.php?trx_id=' . urlencode($response['data']['order_id']));
            exit;
        } else {
            throw new Exception('Response tidak valid dari Midtrans');
        }
        
    } else {
        throw new Exception($response['msg'] ?? 'Gagal membuat transaksi');
    }
    
} catch (Exception $e) {
    error_log("TopUp Error: " . $e->getMessage());
    
    // Handle specific Midtrans errors
    if (strpos($e->getMessage(), 'Midtrans API Error:') !== false) {
        $_SESSION['notification'] = [
            'type' => 'warning',
            'message' => 'Layanan pembayaran sedang mengalami gangguan: ' . str_replace('Midtrans API Error: ', '', $e->getMessage())
        ];
    } else {
        $_SESSION['notification'] = [
            'type' => 'danger',
            'message' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti.'
        ];
    }
    
    header('Location: ../wallet.php');
    exit;
}
?>