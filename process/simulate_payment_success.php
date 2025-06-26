<?php
/**
 * Simulate Payment Success - For Testing Only
 * This simulates successful Midtrans payment for sandbox testing
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$trx_id = $_POST['trx_id'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($trx_id)) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

try {
    // Get transaction from database
    $stmt = $pdo->prepare("
        SELECT * FROM paydisini_transactions 
        WHERE paydisini_trx_id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$trx_id, $user_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found or already processed']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Update transaction status to paid
        $stmt = $pdo->prepare("
            UPDATE paydisini_transactions 
            SET status = 'paid', 
                paid_at = CURRENT_TIMESTAMP,
                callback_data = ?
            WHERE paydisini_trx_id = ?
        ");
        
        $callback_data = json_encode([
            'transaction_status' => 'settlement',
            'order_id' => $trx_id,
            'gross_amount' => $transaction['amount'],
            'payment_type' => 'qris',
            'transaction_time' => date('Y-m-d H:i:s'),
            'simulated' => true
        ]);
        
        $stmt->execute([$callback_data, $trx_id]);
        
        // Get current balance
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $currentBalance = $stmt->fetchColumn() ?: 0;
        $newBalance = $currentBalance + $transaction['amount'];
        
        // Update user balance
        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$newBalance, $user_id]);
        
        // Update balance transaction to completed
        $stmt = $pdo->prepare("
            UPDATE balance_transactions 
            SET status = 'completed', 
                balance_after = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE reference_id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$newBalance, $trx_id, $user_id]);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, details) 
            VALUES (?, 'payment_completed', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            'Top up saldo berhasil - Rp ' . number_format($transaction['amount'], 0, ',', '.'),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            json_encode(['amount' => $transaction['amount'], 'method' => 'midtrans_sandbox'])
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment simulation successful',
            'new_balance' => $newBalance,
            'amount' => $transaction['amount']
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Payment simulation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to simulate payment']);
}
?>