<?php
/**
 * Cancel Order Process
 * Handles order cancellation
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/WalletManager.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    // Auto-login demo user if session is empty
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'demo@ridemax.com' OR id = 1 LIMIT 1");
        $stmt->execute();
        $demoUser = $stmt->fetch();
        
        if ($demoUser) {
            $_SESSION['user_id'] = $demoUser['id'];
            $_SESSION['user_name'] = $demoUser['name'];
            $_SESSION['user_email'] = $demoUser['email'];
            $_SESSION['user_type'] = $demoUser['user_type'];
            $_SESSION['is_driver'] = $demoUser['is_driver'] ?? false;
            $_SESSION['login_time'] = time();
        } else {
            jsonResponse(['success' => false, 'message' => 'Please log in to continue'], 401);
        }
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Please log in to continue'], 401);
    }
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $userId = $_SESSION['user_id'];
    $orderId = $_POST['order_id'] ?? '';
    
    if (empty($orderId)) {
        jsonResponse(['success' => false, 'message' => 'Order ID is required']);
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Order not found']);
    }
    
    // Check if order can be cancelled
    if (!in_array($order['status'], ['pending', 'scheduled'])) {
        jsonResponse(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'cancelled', 
                updated_at = CURRENT_TIMESTAMP,
                cancelled_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        // Refund to wallet if paid with wallet
        if ($order['payment_method'] === 'wallet') {
            $wallet = new WalletManager($pdo);
            $wallet->addBalance($userId, $order['estimated_fare'], "Refund for cancelled order #{$orderId}");
        }
        
        // Log activity
        logActivity('order_cancelled', "Order #{$orderId} cancelled by user", $userId);
        
        $pdo->commit();
        
        jsonResponse([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'refund_amount' => $order['payment_method'] === 'wallet' ? $order['estimated_fare'] : 0
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error cancelling order: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to cancel order']);
    }
    
} catch (Exception $e) {
    error_log("Cancel order error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred while processing cancellation']);
}
?>