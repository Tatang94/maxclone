<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Log semua input untuk debugging
error_log("PayDisini Callback received: " . json_encode($_POST));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

try {
    $paydisini = new PayDisini();
    
    // Validasi signature
    if (!$paydisini->verifyCallback($_POST)) {
        http_response_code(400);
        echo "Invalid signature";
        exit;
    }
    
    $unique_code = $_POST['unique_code'] ?? '';
    $status = $_POST['status'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);
    
    if (empty($unique_code)) {
        http_response_code(400);
        echo "Missing unique_code";
        exit;
    }
    
    // Ambil data transaksi
    $stmt = $pdo->prepare("
        SELECT * FROM paydisini_transactions 
        WHERE paydisini_trx_id = ?
    ");
    $stmt->execute([$unique_code]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        http_response_code(404);
        echo "Transaction not found";
        exit;
    }
    
    // Jika sudah processed, skip
    if ($transaction['status'] === 'paid') {
        echo "OK - Already processed";
        exit;
    }
    
    if ($status === 'Success') {
        $pdo->beginTransaction();
        
        try {
            // Update status transaksi PayDisini
            $stmt = $pdo->prepare("
                UPDATE paydisini_transactions 
                SET status = 'paid', paid_at = CURRENT_TIMESTAMP, callback_data = ?
                WHERE paydisini_trx_id = ?
            ");
            $stmt->execute([json_encode($_POST), $unique_code]);
            
            // Update saldo pengguna
            $wallet = new WalletManager($pdo);
            $wallet->addBalance($transaction['user_id'], $transaction['amount'], $unique_code, 'Top Up Saldo via QRIS');
            
            // Update status balance transaction
            $stmt = $pdo->prepare("
                UPDATE balance_transactions 
                SET status = 'completed', balance_after = balance_before + amount
                WHERE reference_id = ? AND user_id = ?
            ");
            $stmt->execute([$unique_code, $transaction['user_id']]);
            
            $pdo->commit();
            
            echo "OK";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } else {
        // Update status untuk status lain (failed, cancelled, etc)
        $stmt = $pdo->prepare("
            UPDATE paydisini_transactions 
            SET status = ?, callback_data = ? 
            WHERE paydisini_trx_id = ?
        ");
        $stmt->execute([strtolower($status), json_encode($_POST), $unique_code]);
        
        // Update balance transaction jika gagal
        if (in_array(strtolower($status), ['failed', 'cancelled', 'expired'])) {
            $stmt = $pdo->prepare("
                UPDATE balance_transactions 
                SET status = 'failed'
                WHERE reference_id = ? AND user_id = ?
            ");
            $stmt->execute([$unique_code, $transaction['user_id']]);
        }
        
        echo "OK";
    }
    
} catch (Exception $e) {
    error_log("PayDisini Callback Error: " . $e->getMessage());
    http_response_code(500);
    echo "Internal error";
}
?>