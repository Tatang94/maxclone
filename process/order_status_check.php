<?php
/**
 * Order Status Check Handler
 * API endpoint to check real-time order status for driver search
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
}

try {
    $orderId = $_GET['order_id'] ?? null;
    
    if (!$orderId) {
        jsonResponse(['success' => false, 'message' => 'Order ID required']);
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, d.name as driver_name, d.phone as driver_phone,
               dr.vehicle_make, dr.vehicle_model, dr.vehicle_plate, dr.rating as driver_rating
        FROM orders o
        LEFT JOIN users d ON o.driver_id = d.id
        LEFT JOIN drivers dr ON o.driver_id = dr.user_id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Order not found']);
    }
    
    $response = [
        'success' => true,
        'status' => $order['status'],
        'order_id' => $order['id'],
        'created_at' => $order['created_at'],
        'updated_at' => $order['updated_at']
    ];
    
    // Add driver info if assigned
    if ($order['driver_id'] && $order['status'] === 'accepted') {
        $response['driver'] = [
            'name' => $order['driver_name'],
            'phone' => $order['driver_phone'],
            'vehicle' => $order['vehicle_make'] . ' ' . $order['vehicle_model'],
            'plate' => $order['vehicle_plate'],
            'rating' => $order['driver_rating'] ?: 4.8,
            'trips' => rand(50, 500) // Demo data
        ];
    }
    
    // Simulate driver acceptance for demo purposes
    // In real implementation, this would check actual driver responses
    if ($order['status'] === 'pending') {
        $timeSinceCreated = time() - strtotime($order['created_at']);
        
        // Simulate 20% chance of finding driver every 30 seconds after 1 minute
        if ($timeSinceCreated > 60 && rand(1, 100) <= 20) {
            // Update order status to accepted and assign demo driver
            $driverStmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'accepted', 
                    driver_id = 1,
                    driver_arrived_at = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $driverStmt->execute([$orderId]);
            
            $response['status'] = 'accepted';
            $response['driver'] = [
                'name' => 'Ahmad Setiawan',
                'phone' => '+62812345678',
                'vehicle' => 'Honda Beat',
                'plate' => 'B 1234 XYZ',
                'rating' => 4.8,
                'trips' => 234
            ];
        }
    }
    
    jsonResponse($response);
    
} catch (Exception $e) {
    error_log("Order status check error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error'], 500);
}
?>