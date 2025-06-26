<?php
/**
 * Update Order Status Handler
 * API endpoint to update order status (for users, drivers, and admins)
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please log in to continue'], 401);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = (int)($input['order_id'] ?? 0);
    $newStatus = $input['status'] ?? '';
    $reason = trim($input['reason'] ?? '');
    $cancelReason = trim($input['cancel_reason'] ?? '');
    $rating = (int)($input['rating'] ?? 0);
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    $isDriver = $_SESSION['is_driver'] ?? 0;
    
    // Validate input
    if (!$orderId) {
        jsonResponse(['success' => false, 'message' => 'Invalid order ID']);
    }
    
    if (!in_array($newStatus, ['pending', 'accepted', 'in_progress', 'completed', 'cancelled'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid status']);
    }
    
    // Get current order
    $order = fetchSingle("
        SELECT o.*, u.name as user_name, d.name as driver_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN users d ON o.driver_id = d.id
        WHERE o.id = ?
    ", [$orderId]);
    
    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Order not found']);
    }
    
    // Check permissions
    $canUpdate = false;
    
    if ($userType === 'admin') {
        // Admins can update any order
        $canUpdate = true;
    } elseif ($isDriver && $order['driver_id'] == $userId) {
        // Drivers can update their assigned orders
        $canUpdate = true;
    } elseif ($order['user_id'] == $userId) {
        // Users can only cancel their own pending orders
        $canUpdate = ($newStatus === 'cancelled' && $order['status'] === 'pending');
    }
    
    if (!$canUpdate) {
        jsonResponse(['success' => false, 'message' => 'You do not have permission to update this order'], 403);
    }
    
    // Validate status transitions
    $validTransitions = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [], // Completed orders cannot be changed
        'cancelled' => []  // Cancelled orders cannot be changed
    ];
    
    if (!isset($validTransitions[$order['status']]) || 
        !in_array($newStatus, $validTransitions[$order['status']])) {
        jsonResponse(['success' => false, 'message' => 'Invalid status transition']);
    }
    
    beginTransaction();
    
    try {
        // Update order status
        $updateData = [
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add timestamp fields based on status
        switch ($newStatus) {
            case 'accepted':
                $updateData['accepted_at'] = date('Y-m-d H:i:s');
                break;
            case 'in_progress':
                $updateData['started_at'] = date('Y-m-d H:i:s');
                break;
            case 'completed':
                $updateData['completed_at'] = date('Y-m-d H:i:s');
                break;
            case 'cancelled':
                $updateData['cancelled_at'] = date('Y-m-d H:i:s');
                if ($cancelReason) {
                    $updateData['cancel_reason'] = $cancelReason;
                }
                break;
        }
        
        updateData('orders', $updateData, 'id = ?', ['id' => $orderId]);
        
        // Handle specific status changes
        if ($newStatus === 'completed') {
            handleOrderCompletion($order, $rating);
        } elseif ($newStatus === 'cancelled') {
            handleOrderCancellation($order, $cancelReason);
        }
        
        commitTransaction();
        
        // Log activity
        $logMessage = "Order {$order['order_id']} status changed from {$order['status']} to {$newStatus}";
        if ($reason) {
            $logMessage .= " - Reason: {$reason}";
        }
        logActivity('order_status_updated', $logMessage, $userId);
        
        // Send notifications
        sendStatusUpdateNotifications($order, $newStatus, $userId);
        
        jsonResponse([
            'success' => true,
            'message' => 'Order status updated successfully',
            'new_status' => $newStatus,
            'order_id' => $order['order_id']
        ]);
        
    } catch (Exception $e) {
        rollbackTransaction();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Update order status error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'A database error occurred'], 500);
} catch (Exception $e) {
    error_log("Update order status error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}

/**
 * Handle order completion logic
 */
function handleOrderCompletion($order, $rating = 0) {
    global $pdo;
    
    try {
        // Update driver statistics if driver is assigned
        if ($order['driver_id']) {
            // Calculate driver earnings (80% of total price)
            $driverEarning = $order['total_price'] * 0.8;
            
            updateData('drivers', [
                'total_rides' => 'total_rides + 1',
                'total_earnings' => "total_earnings + {$driverEarning}",
                'updated_at' => date('Y-m-d H:i:s')
            ], 'user_id = ?', ['user_id' => $order['driver_id']]);
            
            // Handle rating if provided
            if ($rating > 0 && $rating <= 5) {
                // Insert rating
                insertData('ratings', [
                    'order_id' => $order['id'],
                    'user_id' => $order['user_id'],
                    'driver_id' => $order['driver_id'],
                    'rating' => $rating,
                    'rated_by' => 'user',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Update driver's average rating
                updateDriverRating($order['driver_id']);
            }
        }
        
        // Update user statistics
        updateData('users', [
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', ['id' => $order['user_id']]);
        
    } catch (Exception $e) {
        error_log("Order completion handling error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle order cancellation logic
 */
function handleOrderCancellation($order, $cancelReason) {
    global $pdo;
    
    try {
        // If order was accepted by a driver, make them available again
        if ($order['driver_id'] && $order['status'] !== 'pending') {
            updateData('drivers', [
                'updated_at' => date('Y-m-d H:i:s')
            ], 'user_id = ?', ['user_id' => $order['driver_id']]);
        }
        
        // Check if cancellation fee should be applied
        $cancellationFee = 0;
        if ($order['status'] === 'accepted' || $order['status'] === 'in_progress') {
            $cancellationFee = getSetting('cancellation_fee', 5000);
            
            if ($cancellationFee > 0) {
                // In production, charge the cancellation fee
                logActivity('cancellation_fee_charged', "Cancellation fee of Rp {$cancellationFee} applied to order {$order['order_id']}", $order['user_id']);
            }
        }
        
    } catch (Exception $e) {
        error_log("Order cancellation handling error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Update driver's average rating
 */
function updateDriverRating($driverId) {
    global $pdo;
    
    try {
        // Calculate new average rating
        $avgRating = fetchValue("
            SELECT AVG(rating) 
            FROM ratings 
            WHERE driver_id = ? AND rated_by = 'user'
        ", [$driverId]);
        
        if ($avgRating) {
            updateData('drivers', [
                'rating' => round($avgRating, 2),
                'updated_at' => date('Y-m-d H:i:s')
            ], 'user_id = ?', ['user_id' => $driverId]);
        }
        
    } catch (Exception $e) {
        error_log("Driver rating update error: " . $e->getMessage());
    }
}

/**
 * Send status update notifications
 */
function sendStatusUpdateNotifications($order, $newStatus, $updatedBy) {
    global $pdo;
    
    try {
        // Determine who to notify
        $notifyUser = ($updatedBy !== $order['user_id']);
        $notifyDriver = ($order['driver_id'] && $updatedBy !== $order['driver_id']);
        
        // Get notification messages
        $messages = getStatusNotificationMessages($order['order_id'], $newStatus);
        
        // Notify user
        if ($notifyUser && isset($messages['user'])) {
            $user = fetchSingle("SELECT name, email, phone FROM users WHERE id = ?", [$order['user_id']]);
            if ($user) {
                // Send email
                sendEmail($user['email'], 'RideMax Order Update', $messages['user']['email']);
                
                // Send SMS
                if ($user['phone']) {
                    sendSMS($user['phone'], $messages['user']['sms']);
                }
            }
        }
        
        // Notify driver
        if ($notifyDriver && isset($messages['driver'])) {
            $driver = fetchSingle("SELECT name, email, phone FROM users WHERE id = ?", [$order['driver_id']]);
            if ($driver) {
                // Send SMS to driver
                if ($driver['phone']) {
                    sendSMS($driver['phone'], $messages['driver']['sms']);
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Notification sending error: " . $e->getMessage());
    }
}

/**
 * Get notification messages for different statuses
 */
function getStatusNotificationMessages($orderId, $status) {
    $messages = [];
    
    switch ($status) {
        case 'accepted':
            $messages['user'] = [
                'email' => "Your RideMax order #{$orderId} has been accepted by a driver. Your driver is on the way!",
                'sms' => "RideMax: Your ride #{$orderId} has been accepted. Driver is on the way!"
            ];
            break;
            
        case 'in_progress':
            $messages['user'] = [
                'email' => "Your RideMax ride #{$orderId} has started. Enjoy your trip!",
                'sms' => "RideMax: Your ride #{$orderId} has started. Enjoy your trip!"
            ];
            break;
            
        case 'completed':
            $messages['user'] = [
                'email' => "Your RideMax ride #{$orderId} has been completed. Thank you for choosing RideMax!",
                'sms' => "RideMax: Your ride #{$orderId} is complete. Thank you for choosing RideMax!"
            ];
            $messages['driver'] = [
                'sms' => "RideMax: Trip #{$orderId} completed successfully. Great job!"
            ];
            break;
            
        case 'cancelled':
            $messages['user'] = [
                'email' => "Your RideMax order #{$orderId} has been cancelled. You can book a new ride anytime.",
                'sms' => "RideMax: Your ride #{$orderId} has been cancelled."
            ];
            $messages['driver'] = [
                'sms' => "RideMax: Trip #{$orderId} has been cancelled."
            ];
            break;
    }
    
    return $messages;
}
?>
