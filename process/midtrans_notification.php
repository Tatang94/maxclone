<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/midtrans.php';

// Read notification body
$json_result = file_get_contents('php://input');
$result = json_decode($json_result, true);

if (!$result) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

error_log('Midtrans Notification: ' . $json_result);

try {
    $midtrans = new Midtrans();
    
    $order_id = $result['order_id'];
    $status_code = $result['status_code'];
    $gross_amount = $result['gross_amount'];
    $signature_key = $result['signature_key'];
    $transaction_status = $result['transaction_status'];
    $fraud_status = $result['fraud_status'] ?? '';
    
    // Verify signature
    if (!$midtrans->verifySignature($order_id, $status_code, $gross_amount, $signature_key)) {
        error_log('Invalid signature for order: ' . $order_id);
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }
    
    // Get transaction from database
    $stmt = $pdo->prepare("SELECT * FROM paydisini_transactions WHERE paydisini_trx_id = ?");
    $stmt->execute([$order_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        error_log('Transaction not found: ' . $order_id);
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
        exit;
    }
    
    $new_status = 'pending';
    
    if ($transaction_status == 'capture') {
        if ($fraud_status == 'accept') {
            $new_status = 'paid';
        }
    } elseif ($transaction_status == 'settlement') {
        $new_status = 'paid';
    } elseif ($transaction_status == 'pending') {
        $new_status = 'pending';
    } elseif ($transaction_status == 'deny' || $transaction_status == 'expire' || $transaction_status == 'cancel') {
        $new_status = 'failed';
    }
    
    // Update transaction status
    $stmt = $pdo->prepare("
        UPDATE paydisini_transactions 
        SET status = ?, 
            callback_data = ?,
            paid_at = CASE WHEN ? = 'paid' THEN CURRENT_TIMESTAMP ELSE paid_at END
        WHERE paydisini_trx_id = ?
    ");
    
    $callback_data = json_encode($result);
    $stmt->execute([$new_status, $callback_data, $new_status, $order_id]);
    
    // If payment successful, update user balance
    if ($new_status === 'paid' && $transaction['status'] !== 'paid') {
        $pdo->beginTransaction();
        
        try {
            // Get current balance
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$transaction['user_id']]);
            $currentBalance = $stmt->fetchColumn() ?: 0;
            $newBalance = $currentBalance + $transaction['amount'];
            
            // Update user balance
            $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $transaction['user_id']]);
            
            // Create balance transaction record
            require_once '../includes/functions.php';
            $wallet = new WalletManager($pdo);
            $wallet->addBalance(
                $transaction['user_id'], 
                $transaction['amount'], 
                $order_id, 
                'Top Up Saldo via Midtrans'
            );
            
            $pdo->commit();
            error_log('Balance updated for user: ' . $transaction['user_id']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Failed to update balance: ' . $e->getMessage());
        }
    }
    
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log('Midtrans notification error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>