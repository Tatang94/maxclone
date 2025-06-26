<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/paydisini.php';

header('Content-Type: application/json');

// Cek login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$trx_id = $_POST['trx_id'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($trx_id)) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

try {
    // Ambil data transaksi dari database
    $stmt = $pdo->prepare("
        SELECT * FROM paydisini_transactions 
        WHERE paydisini_trx_id = ? AND user_id = ?
    ");
    $stmt->execute([$trx_id, $user_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }
    
    // Jika sudah paid, return success
    if ($transaction['status'] === 'paid') {
        echo json_encode(['success' => true, 'paid' => true]);
        exit;
    }
    
    // Cek status dari Midtrans atau PayDisini berdasarkan payment method
    if ($transaction['payment_method'] === 'midtrans') {
        require_once '../includes/midtrans.php';
        $midtrans = new Midtrans();
        $response = $midtrans->getTransactionStatus($trx_id);
        
        if (isset($response['transaction_status'])) {
            $transaction_status = $response['transaction_status'];
            $fraud_status = $response['fraud_status'] ?? '';
            
            $is_paid = false;
            if ($transaction_status == 'capture' && $fraud_status == 'accept') {
                $is_paid = true;
            } elseif ($transaction_status == 'settlement') {
                $is_paid = true;
            }
            
            if ($is_paid) {
                // Update status transaksi
                $stmt = $pdo->prepare("
                    UPDATE paydisini_transactions 
                    SET status = 'paid', paid_at = CURRENT_TIMESTAMP, callback_data = ?
                    WHERE paydisini_trx_id = ?
                ");
                $stmt->execute([json_encode($response), $trx_id]);
                
                // Update saldo pengguna
                $wallet = new WalletManager($pdo);
                $wallet->addBalance($user_id, $transaction['amount'], $trx_id, 'Top Up Saldo via Midtrans');
                
                // Update status balance transaction
                $stmt = $pdo->prepare("
                    UPDATE balance_transactions 
                    SET status = 'completed', balance_after = balance_before + amount
                    WHERE reference_id = ? AND user_id = ?
                ");
                $stmt->execute([$trx_id, $user_id]);
                
                echo json_encode(['success' => true, 'status' => 'paid']);
                exit;
            } else {
                echo json_encode(['success' => true, 'status' => 'pending']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Midtrans response']);
            exit;
        }
    } else {
        // Cek status dari PayDisini untuk metode lain
        $paydisini = new PayDisini();
        $response = $paydisini->checkTransactionStatus($trx_id);
    }
    
    if ($response['success']) {
        $status = $response['data']['status'] ?? 'pending';
        
        if ($status === 'Success') {
            // Update status transaksi PayDisini
            $stmt = $pdo->prepare("
                UPDATE paydisini_transactions 
                SET status = 'paid', paid_at = CURRENT_TIMESTAMP, callback_data = ?
                WHERE paydisini_trx_id = ?
            ");
            $stmt->execute([json_encode($response['data']), $trx_id]);
            
            // Update saldo pengguna
            $wallet = new WalletManager($pdo);
            $wallet->addBalance($user_id, $transaction['amount'], $trx_id, 'Top Up Saldo via QRIS');
            
            // Update status balance transaction
            $stmt = $pdo->prepare("
                UPDATE balance_transactions 
                SET status = 'completed', balance_after = balance_before + amount
                WHERE reference_id = ? AND user_id = ?
            ");
            $stmt->execute([$trx_id, $user_id]);
            
            echo json_encode(['success' => true, 'paid' => true]);
        } else {
            echo json_encode(['success' => true, 'paid' => false, 'status' => $status]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to check status']);
    }
    
} catch (Exception $e) {
    error_log("Check Payment Status Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal error']);
}
?>