<?php
/**
 * Driver Process Handler
 * Handles driver-specific operations like status updates, ride management, and location updates
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

// Check if user is a driver
$user = getCurrentUser();
if ($user['is_driver'] != 1) {
    jsonResponse(['success' => false, 'message' => 'Access denied. Driver account required.'], 403);
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'toggle_status':
            handleToggleStatus();
            break;
        case 'get_stats':
            handleGetStats();
            break;
        case 'get_available_rides':
            handleGetAvailableRides();
            break;
        case 'get_recent_rides':
            handleGetRecentRides();
            break;
        case 'get_current_ride':
            handleGetCurrentRide();
            break;
        case 'get_ride_details':
            handleGetRideDetails();
            break;
        case 'accept_ride':
            handleAcceptRide();
            break;
        case 'update_ride_status':
            handleUpdateRideStatus();
            break;
        case 'update_location':
            handleUpdateLocation();
            break;
        case 'get_earnings':
            handleGetEarnings();
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    error_log("Driver process error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred while processing your request'], 500);
}

/**
 * Toggle driver online/offline status
 */
function handleToggleStatus() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $online = (bool)($input['online'] ?? false);
    $userId = $_SESSION['user_id'];
    
    try {
        // Update driver status
        $updateData = [
            'is_online' => $online ? 1 : 0,
            'last_seen' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $updated = updateData('drivers', $updateData, 'user_id = ?', ['user_id' => $userId]);
        
        if (!$updated) {
            jsonResponse(['success' => false, 'message' => 'Failed to update status']);
        }
        
        // Log activity
        $status = $online ? 'online' : 'offline';
        logActivity('driver_status_changed', "Driver went {$status}", $userId);
        
        jsonResponse([
            'success' => true,
            'message' => "Status updated to " . ($online ? 'online' : 'offline'),
            'online' => $online
        ]);
        
    } catch (PDOException $e) {
        error_log("Driver status update error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
    }
}

/**
 * Get driver statistics
 */
function handleGetStats() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    
    try {
        // Today's earnings
        $todayEarnings = fetchValue("
            SELECT SUM(total_price * 0.8) as earnings
            FROM orders
            WHERE driver_id = ? AND status = 'completed' AND DATE(completed_at) = CURDATE()
        ", [$userId]) ?: 0;
        
        // Today's rides
        $todayRides = fetchValue("
            SELECT COUNT(*) 
            FROM orders
            WHERE driver_id = ? AND status = 'completed' AND DATE(completed_at) = CURDATE()
        ", [$userId]) ?: 0;
        
        // Total earnings this week
        $weekEarnings = fetchValue("
            SELECT SUM(total_price * 0.8) as earnings
            FROM orders
            WHERE driver_id = ? AND status = 'completed' 
            AND WEEK(completed_at) = WEEK(NOW()) AND YEAR(completed_at) = YEAR(NOW())
        ", [$userId]) ?: 0;
        
        // Total earnings this month
        $monthEarnings = fetchValue("
            SELECT SUM(total_price * 0.8) as earnings
            FROM orders
            WHERE driver_id = ? AND status = 'completed' 
            AND MONTH(completed_at) = MONTH(NOW()) AND YEAR(completed_at) = YEAR(NOW())
        ", [$userId]) ?: 0;
        
        // Driver rating and total rides
        $driverStats = fetchSingle("
            SELECT rating, total_rides
            FROM drivers
            WHERE user_id = ?
        ", [$userId]);
        
        // Online time today (simplified - in production track actual online sessions)
        $onlineTime = fetchValue("
            SELECT TIMESTAMPDIFF(HOUR, 
                CASE WHEN DATE(last_seen) = CURDATE() THEN last_seen ELSE CURDATE() END,
                NOW()
            ) as hours
            FROM drivers
            WHERE user_id = ? AND is_online = 1
        ", [$userId]) ?: 0;
        
        jsonResponse([
            'success' => true,
            'stats' => [
                'today_earnings' => (int)$todayEarnings,
                'today_rides' => (int)$todayRides,
                'week_earnings' => (int)$weekEarnings,
                'month_earnings' => (int)$monthEarnings,
                'rating' => $driverStats['rating'] ?? 5.0,
                'total_rides' => $driverStats['total_rides'] ?? 0,
                'online_hours_today' => max(0, $onlineTime)
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Driver stats error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to load statistics'], 500);
    }
}

/**
 * Get available rides for driver
 */
function handleGetAvailableRides() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    
    try {
        // Check if driver is online
        $isOnline = fetchValue("SELECT is_online FROM drivers WHERE user_id = ?", [$userId]);
        
        if (!$isOnline) {
            jsonResponse(['success' => true, 'rides' => []]);
        }
        
        // Check if driver has an active ride
        $activeRide = fetchValue("
            SELECT id FROM orders 
            WHERE driver_id = ? AND status IN ('accepted', 'in_progress')
        ", [$userId]);
        
        if ($activeRide) {
            jsonResponse(['success' => true, 'rides' => []]);
        }
        
        // Get available rides
        $rides = fetchMultiple("
            SELECT o.id, o.order_id, o.pickup_location, o.destination, o.vehicle_type,
                   o.total_price, o.estimated_distance, o.notes, o.created_at,
                   u.name as user_name, u.phone as user_phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.status = 'pending' 
            AND o.driver_id IS NULL
            AND (o.scheduled_for IS NULL OR o.scheduled_for <= NOW())
            ORDER BY o.created_at ASC
            LIMIT 10
        ");
        
        jsonResponse(['success' => true, 'rides' => $rides]);
        
    } catch (PDOException $e) {
        error_log("Available rides error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to load available rides'], 500);
    }
}

/**
 * Get recent completed rides
 */
function handleGetRecentRides() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    
    try {
        $rides = fetchMultiple("
            SELECT o.id, o.order_id, o.pickup_location, o.destination, o.vehicle_type,
                   o.total_price, o.completed_at, u.name as user_name,
                   COALESCE(r.rating, 5) as rating
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN ratings r ON o.id = r.order_id AND r.rated_by = 'user'
            WHERE o.driver_id = ? AND o.status = 'completed'
            ORDER BY o.completed_at DESC
            LIMIT 10
        ", [$userId]);
        
        jsonResponse(['success' => true, 'rides' => $rides]);
        
    } catch (PDOException $e) {
        error_log("Recent rides error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to load recent rides'], 500);
    }
}

/**
 * Get current active ride
 */
function handleGetCurrentRide() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    
    try {
        $ride = fetchSingle("
            SELECT o.*, u.name as user_name, u.phone as user_phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.driver_id = ? AND o.status IN ('accepted', 'in_progress')
            ORDER BY o.accepted_at DESC
            LIMIT 1
        ", [$userId]);
        
        if ($ride) {
            jsonResponse(['success' => true, 'ride' => $ride]);
        } else {
            jsonResponse(['success' => true, 'ride' => null]);
        }
        
    } catch (PDOException $e) {
        error_log("Current ride error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to load current ride'], 500);
    }
}

/**
 * Get ride details
 */
function handleGetRideDetails() {
    global $pdo;
    
    $rideId = (int)($_GET['ride_id'] ?? 0);
    
    if (!$rideId) {
        jsonResponse(['success' => false, 'message' => 'Invalid ride ID']);
    }
    
    try {
        $ride = fetchSingle("
            SELECT o.*, u.name as user_name, u.phone as user_phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ? AND o.status = 'pending' AND o.driver_id IS NULL
        ", [$rideId]);
        
        if (!$ride) {
            jsonResponse(['success' => false, 'message' => 'Ride not found or no longer available']);
        }
        
        // Add estimated distance if not set
        if (!$ride['estimated_distance']) {
            $ride['estimated_distance'] = calculateEstimatedDistance($ride['pickup_location'], $ride['destination']);
        }
        
        jsonResponse(['success' => true, 'ride' => $ride]);
        
    } catch (PDOException $e) {
        error_log("Ride details error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to load ride details'], 500);
    }
}

/**
 * Accept a ride
 */
function handleAcceptRide() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $rideId = (int)($input['ride_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    if (!$rideId) {
        jsonResponse(['success' => false, 'message' => 'Invalid ride ID']);
    }
    
    beginTransaction();
    
    try {
        // Check if driver is online and available
        $driver = fetchSingle("
            SELECT d.is_online, u.name
            FROM drivers d
            JOIN users u ON d.user_id = u.id
            WHERE d.user_id = ? AND d.is_online = 1
        ", [$userId]);
        
        if (!$driver) {
            jsonResponse(['success' => false, 'message' => 'Driver not online or not found']);
        }
        
        // Check if driver has an active ride
        $activeRide = fetchValue("
            SELECT id FROM orders 
            WHERE driver_id = ? AND status IN ('accepted', 'in_progress')
        ", [$userId]);
        
        if ($activeRide) {
            jsonResponse(['success' => false, 'message' => 'You already have an active ride']);
        }
        
        // Check if ride is still available
        $ride = fetchSingle("
            SELECT id, order_id, user_id, status, driver_id
            FROM orders
            WHERE id = ? AND status = 'pending' AND driver_id IS NULL
        ", [$rideId]);
        
        if (!$ride) {
            jsonResponse(['success' => false, 'message' => 'Ride no longer available']);
        }
        
        // Accept the ride
        $updateData = [
            'driver_id' => $userId,
            'status' => 'accepted',
            'accepted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        updateData('orders', $updateData, 'id = ?', ['id' => $rideId]);
        
        commitTransaction();
        
        // Log activity
        logActivity('ride_accepted', "Ride {$ride['order_id']} accepted", $userId);
        
        // Send notification to user (in production, use push notifications)
        sendRideAcceptedNotification($ride['user_id'], $ride['order_id'], $driver['name']);
        
        jsonResponse([
            'success' => true,
            'message' => 'Ride accepted successfully',
            'ride_id' => $rideId,
            'order_id' => $ride['order_id']
        ]);
        
    } catch (Exception $e) {
        rollbackTransaction();
        error_log("Accept ride error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to accept ride'], 500);
    }
}

/**
 * Update ride status (start trip, complete trip)
 */
function handleUpdateRideStatus() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $rideId = (int)($input['ride_id'] ?? 0);
    $status = $input['status'] ?? '';
    $userId = $_SESSION['user_id'];
    
    if (!$rideId || !$status) {
        jsonResponse(['success' => false, 'message' => 'Invalid ride ID or status']);
    }
    
    if (!in_array($status, ['in_progress', 'completed', 'cancelled'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid status']);
    }
    
    beginTransaction();
    
    try {
        // Check if ride belongs to this driver
        $ride = fetchSingle("
            SELECT id, order_id, user_id, status, total_price
            FROM orders
            WHERE id = ? AND driver_id = ?
        ", [$rideId, $userId]);
        
        if (!$ride) {
            jsonResponse(['success' => false, 'message' => 'Ride not found or not assigned to you']);
        }
        
        // Validate status transition
        $validTransitions = [
            'accepted' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled']
        ];
        
        if (!isset($validTransitions[$ride['status']]) || 
            !in_array($status, $validTransitions[$ride['status']])) {
            jsonResponse(['success' => false, 'message' => 'Invalid status transition']);
        }
        
        // Update ride status
        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === 'in_progress') {
            $updateData['started_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'completed') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
            
            // Update driver stats
            updateData('drivers', [
                'total_rides' => 'total_rides + 1',
                'total_earnings' => 'total_earnings + ' . ($ride['total_price'] * 0.8), // 80% to driver
                'updated_at' => date('Y-m-d H:i:s')
            ], 'user_id = ?', ['user_id' => $userId]);
        }
        
        updateData('orders', $updateData, 'id = ?', ['id' => $rideId]);
        
        commitTransaction();
        
        // Log activity
        logActivity('ride_status_updated', "Ride {$ride['order_id']} status changed to {$status}", $userId);
        
        // Send notification to user
        sendRideStatusNotification($ride['user_id'], $ride['order_id'], $status);
        
        jsonResponse([
            'success' => true,
            'message' => 'Ride status updated successfully',
            'status' => $status
        ]);
        
    } catch (Exception $e) {
        rollbackTransaction();
        error_log("Update ride status error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update ride status'], 500);
    }
}

/**
 * Update driver location
 */
function handleUpdateLocation() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $latitude = (float)($input['latitude'] ?? 0);
    $longitude = (float)($input['longitude'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    if (!$latitude || !$longitude) {
        jsonResponse(['success' => false, 'message' => 'Invalid coordinates']);
    }
    
    try {
        // Update driver location
        $updateData = [
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_update' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        updateData('drivers', $updateData, 'user_id = ?', ['user_id' => $userId]);
        
        // If driver has an active ride, notify the user
        $activeRide = fetchSingle("
            SELECT id, user_id, order_id 
            FROM orders 
            WHERE driver_id = ? AND status IN ('accepted', 'in_progress')
        ", [$userId]);
        
        if ($activeRide) {
            // In production, send real-time location update to user
            sendLocationUpdateToUser($activeRide['user_id'], $latitude, $longitude);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Location updated successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Location update error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update location'], 500);
    }
}

/**
 * Get driver earnings breakdown
 */
function handleGetEarnings() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $period = $_GET['period'] ?? 'week'; // week, month, year
    
    try {
        $dateCondition = '';
        switch ($period) {
            case 'week':
                $dateCondition = "AND WEEK(completed_at) = WEEK(NOW()) AND YEAR(completed_at) = YEAR(NOW())";
                break;
            case 'month':
                $dateCondition = "AND MONTH(completed_at) = MONTH(NOW()) AND YEAR(completed_at) = YEAR(NOW())";
                break;
            case 'year':
                $dateCondition = "AND YEAR(completed_at) = YEAR(NOW())";
                break;
        }
        
        // Get earnings breakdown
        $earnings = fetchMultiple("
            SELECT DATE(completed_at) as date, 
                   COUNT(*) as rides,
                   SUM(total_price) as gross_earnings,
                   SUM(total_price * 0.8) as net_earnings
            FROM orders
            WHERE driver_id = ? AND status = 'completed' {$dateCondition}
            GROUP BY DATE(completed_at)
            ORDER BY date DESC
        ", [$userId]);
        
        // Get total earnings
        $totalEarnings = fetchSingle("
            SELECT COUNT(*) as total_rides,
                   SUM(total_price) as total_gross,
                   SUM(total_price * 0.8) as total_net
            FROM orders
            WHERE driver_id = ? AND status = 'completed' {$dateCondition}
        ", [$userId]);
        
        jsonResponse([
            'success' => true,
            'earnings' => $earnings,
            'totals' => $totalEarnings ?: [
                'total_rides' => 0,
                'total_gross' => 0,
                'total_net' => 0
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Earnings error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to load earnings'], 500);
    }
}

/**
 * Calculate estimated distance between two locations
 */
function calculateEstimatedDistance($pickup, $destination) {
    // Simple estimation - in production use geocoding API
    $baseDistance = 5;
    $variation = (strlen($pickup) + strlen($destination)) % 15;
    return max(1, $baseDistance + $variation);
}

/**
 * Send ride accepted notification to user
 */
function sendRideAcceptedNotification($userId, $orderId, $driverName) {
    global $pdo;
    
    try {
        $user = fetchSingle("SELECT name, email, phone FROM users WHERE id = ?", [$userId]);
        
        if ($user) {
            // Send email
            $subject = "Your RideMax ride has been accepted!";
            $message = "
            <html>
            <body>
                <h2>Great news!</h2>
                <p>Hi {$user['name']},</p>
                <p>Your ride request (Order #{$orderId}) has been accepted by {$driverName}.</p>
                <p>Your driver is on the way to pick you up. You'll receive updates as your driver approaches.</p>
                <p>Thank you for choosing RideMax!</p>
            </body>
            </html>
            ";
            
            sendEmail($user['email'], $subject, $message);
            
            // Send SMS
            if ($user['phone']) {
                $smsMessage = "RideMax: Your ride {$orderId} has been accepted by {$driverName}. Your driver is on the way!";
                sendSMS($user['phone'], $smsMessage);
            }
        }
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
    }
}

/**
 * Send ride status notification to user
 */
function sendRideStatusNotification($userId, $orderId, $status) {
    global $pdo;
    
    try {
        $user = fetchSingle("SELECT name, phone FROM users WHERE id = ?", [$userId]);
        
        if ($user && $user['phone']) {
            $messages = [
                'in_progress' => "RideMax: Your ride {$orderId} has started. Enjoy your trip!",
                'completed' => "RideMax: Your ride {$orderId} has been completed. Thank you for choosing RideMax!",
                'cancelled' => "RideMax: Your ride {$orderId} has been cancelled. Please book a new ride if needed."
            ];
            
            if (isset($messages[$status])) {
                sendSMS($user['phone'], $messages[$status]);
            }
        }
    } catch (Exception $e) {
        error_log("Status notification error: " . $e->getMessage());
    }
}

/**
 * Send location update to user (placeholder for real-time updates)
 */
function sendLocationUpdateToUser($userId, $latitude, $longitude) {
    // In production, this would send real-time location updates via WebSocket or push notifications
    logActivity('location_updated', "Driver location updated for user {$userId}", $_SESSION['user_id']);
}
?>
