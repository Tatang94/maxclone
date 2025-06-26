<?php
/**
 * Get Driver Location Handler
 * API endpoint to fetch real-time driver location and ETA information
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

try {
    $userId = $_SESSION['user_id'];
    $orderId = $_GET['order_id'] ?? null;
    $driverId = $_GET['driver_id'] ?? null;
    
    if ($orderId) {
        // Get driver location for a specific order
        handleOrderDriverLocation($orderId, $userId);
    } elseif ($driverId) {
        // Get specific driver location (for admin use)
        handleSpecificDriverLocation($driverId);
    } else {
        // Get all online drivers (for admin dashboard)
        handleAllOnlineDrivers();
    }
    
} catch (Exception $e) {
    error_log("Get driver location error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred while fetching driver location'], 500);
}

/**
 * Handle driver location for a specific order
 */
function handleOrderDriverLocation($orderId, $userId) {
    global $pdo;
    
    try {
        // Verify order belongs to user and has a driver assigned
        $order = fetchSingle("
            SELECT o.id, o.order_id, o.status, o.driver_id, o.pickup_location, o.destination,
                   o.user_id, u.name as user_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ? AND (o.user_id = ? OR ? IN (SELECT id FROM users WHERE user_type = 'admin'))
        ", [$orderId, $userId, $userId]);
        
        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order not found or access denied']);
        }
        
        if (!$order['driver_id']) {
            jsonResponse(['success' => false, 'message' => 'No driver assigned to this order']);
        }
        
        // Get driver location and details
        $driver = fetchSingle("
            SELECT u.id, u.name, u.phone, 
                   d.current_latitude, d.current_longitude, d.last_location_update,
                   d.vehicle_make, d.vehicle_model, d.vehicle_plate, d.rating
            FROM users u
            JOIN drivers d ON u.id = d.user_id
            WHERE u.id = ?
        ", [$order['driver_id']]);
        
        if (!$driver) {
            jsonResponse(['success' => false, 'message' => 'Driver information not found']);
        }
        
        // Calculate ETA if location is available
        $eta = null;
        if ($driver['current_latitude'] && $driver['current_longitude']) {
            $eta = calculateETA($driver, $order);
        }
        
        // Check if location is fresh (updated within last 5 minutes)
        $locationFresh = false;
        if ($driver['last_location_update']) {
            $lastUpdate = strtotime($driver['last_location_update']);
            $locationFresh = (time() - $lastUpdate) < 300; // 5 minutes
        }
        
        $response = [
            'success' => true,
            'order' => [
                'id' => $order['id'],
                'order_id' => $order['order_id'],
                'status' => $order['status'],
                'pickup_location' => $order['pickup_location'],
                'destination' => $order['destination']
            ],
            'driver' => [
                'id' => $driver['id'],
                'name' => $driver['name'],
                'phone' => $driver['phone'],
                'vehicle' => [
                    'make' => $driver['vehicle_make'],
                    'model' => $driver['vehicle_model'],
                    'plate' => $driver['vehicle_plate']
                ],
                'rating' => (float)$driver['rating'],
                'location' => [
                    'latitude' => (float)$driver['current_latitude'],
                    'longitude' => (float)$driver['current_longitude'],
                    'last_update' => $driver['last_location_update'],
                    'is_fresh' => $locationFresh
                ]
            ],
            'eta' => $eta
        ];
        
        jsonResponse($response);
        
    } catch (PDOException $e) {
        error_log("Order driver location error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch driver location'], 500);
    }
}

/**
 * Handle specific driver location (admin only)
 */
function handleSpecificDriverLocation($driverId) {
    global $pdo;
    
    // Check if user is admin
    if (!isAdmin()) {
        jsonResponse(['success' => false, 'message' => 'Admin access required'], 403);
    }
    
    try {
        $driver = fetchSingle("
            SELECT u.id, u.name, u.phone, u.status,
                   d.current_latitude, d.current_longitude, d.last_location_update,
                   d.is_online, d.vehicle_make, d.vehicle_model, d.vehicle_plate, d.rating,
                   d.total_rides, d.total_earnings
            FROM users u
            JOIN drivers d ON u.id = d.user_id
            WHERE u.id = ?
        ", [$driverId]);
        
        if (!$driver) {
            jsonResponse(['success' => false, 'message' => 'Driver not found']);
        }
        
        // Get current order if any
        $currentOrder = fetchSingle("
            SELECT id, order_id, status, pickup_location, destination, created_at
            FROM orders
            WHERE driver_id = ? AND status IN ('accepted', 'in_progress')
            ORDER BY accepted_at DESC
            LIMIT 1
        ", [$driverId]);
        
        $locationFresh = false;
        if ($driver['last_location_update']) {
            $lastUpdate = strtotime($driver['last_location_update']);
            $locationFresh = (time() - $lastUpdate) < 300; // 5 minutes
        }
        
        $response = [
            'success' => true,
            'driver' => [
                'id' => $driver['id'],
                'name' => $driver['name'],
                'phone' => $driver['phone'],
                'status' => $driver['status'],
                'is_online' => (bool)$driver['is_online'],
                'vehicle' => [
                    'make' => $driver['vehicle_make'],
                    'model' => $driver['vehicle_model'],
                    'plate' => $driver['vehicle_plate']
                ],
                'rating' => (float)$driver['rating'],
                'total_rides' => (int)$driver['total_rides'],
                'total_earnings' => (float)$driver['total_earnings'],
                'location' => [
                    'latitude' => (float)$driver['current_latitude'],
                    'longitude' => (float)$driver['current_longitude'],
                    'last_update' => $driver['last_location_update'],
                    'is_fresh' => $locationFresh
                ],
                'current_order' => $currentOrder
            ]
        ];
        
        jsonResponse($response);
        
    } catch (PDOException $e) {
        error_log("Specific driver location error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch driver location'], 500);
    }
}

/**
 * Handle all online drivers (admin only)
 */
function handleAllOnlineDrivers() {
    global $pdo;
    
    // Check if user is admin
    if (!isAdmin()) {
        jsonResponse(['success' => false, 'message' => 'Admin access required'], 403);
    }
    
    try {
        $drivers = fetchMultiple("
            SELECT u.id, u.name, 
                   d.current_latitude, d.current_longitude, d.last_location_update,
                   d.is_online, d.vehicle_make, d.vehicle_model, d.vehicle_plate, d.rating,
                   (SELECT COUNT(*) FROM orders WHERE driver_id = u.id AND status IN ('accepted', 'in_progress')) as has_active_order
            FROM users u
            JOIN drivers d ON u.id = d.user_id
            WHERE d.is_online = 1 AND u.status = 'active'
            ORDER BY d.last_location_update DESC
        ");
        
        // Process drivers data
        foreach ($drivers as &$driver) {
            $locationFresh = false;
            if ($driver['last_location_update']) {
                $lastUpdate = strtotime($driver['last_location_update']);
                $locationFresh = (time() - $lastUpdate) < 300; // 5 minutes
            }
            
            $driver['location_fresh'] = $locationFresh;
            $driver['has_active_order'] = (bool)$driver['has_active_order'];
            $driver['current_latitude'] = (float)$driver['current_latitude'];
            $driver['current_longitude'] = (float)$driver['current_longitude'];
            $driver['rating'] = (float)$driver['rating'];
        }
        
        jsonResponse([
            'success' => true,
            'drivers' => $drivers,
            'total_online' => count($drivers)
        ]);
        
    } catch (PDOException $e) {
        error_log("All online drivers error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch online drivers'], 500);
    }
}

/**
 * Calculate ETA based on driver location and order details
 */
function calculateETA($driver, $order) {
    // Simple ETA calculation - in production use Google Maps API or similar
    
    if (!$driver['current_latitude'] || !$driver['current_longitude']) {
        return null;
    }
    
    // For demo purposes, calculate a simple ETA
    $baseTime = 5; // 5 minutes base time
    $extraTime = rand(1, 10); // Random variation
    
    $estimatedMinutes = $baseTime + $extraTime;
    $arrivalTime = date('H:i', strtotime("+{$estimatedMinutes} minutes"));
    
    return [
        'minutes' => $estimatedMinutes,
        'arrival_time' => $arrivalTime,
        'text' => "{$estimatedMinutes} minutes away",
        'confidence' => 'medium' // low, medium, high
    ];
}

/**
 * Calculate distance between two coordinates using Haversine formula
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDelta / 2) * sin($lonDelta / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}
?>
