<?php
/**
 * Fetch Order Status Handler
 * API endpoint to fetch order status, history, and real-time updates
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
    $recent = $_GET['recent'] ?? null;
    $filter = $_GET['filter'] ?? 'all';
    $sort = $_GET['sort'] ?? 'newest';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $search = trim($_GET['search'] ?? '');
    
    if ($orderId) {
        // Fetch specific order
        handleSingleOrder($orderId, $userId);
    } elseif ($recent) {
        // Fetch recent orders for dashboard
        handleRecentOrders($userId);
    } else {
        // Fetch paginated order history
        handleOrderHistory($userId, $filter, $sort, $page, $search);
    }
    
} catch (Exception $e) {
    error_log("Fetch order status error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred while fetching orders'], 500);
}

/**
 * Handle single order fetch
 */
function handleSingleOrder($orderId, $userId) {
    global $pdo;
    
    try {
        $order = fetchSingle("
            SELECT o.*, d.name as driver_name, d.phone as driver_phone,
                   dr.vehicle_make, dr.vehicle_model, dr.vehicle_plate, dr.rating as driver_rating,
                   dr.current_latitude as driver_lat, dr.current_longitude as driver_lng
            FROM orders o
            LEFT JOIN users d ON o.driver_id = d.id
            LEFT JOIN drivers dr ON o.driver_id = dr.user_id
            WHERE o.id = ? AND o.user_id = ?
        ", [$orderId, $userId]);
        
        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order not found']);
        }
        
        // Add estimated arrival time if driver is assigned
        if ($order['driver_id'] && $order['status'] === 'accepted') {
            $order['estimated_arrival'] = calculateEstimatedArrival($order);
        }
        
        jsonResponse(['success' => true, 'order' => $order]);
        
    } catch (PDOException $e) {
        error_log("Single order fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch order details'], 500);
    }
}

/**
 * Handle recent orders for dashboard
 */
function handleRecentOrders($userId) {
    global $pdo;
    
    try {
        $orders = fetchMultiple("
            SELECT o.id, o.pickup_address, o.destination_address, o.status,
                   o.actual_fare, o.vehicle_type, o.created_at,
                   d.name as driver_name
            FROM orders o
            LEFT JOIN users d ON o.driver_id = d.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
            LIMIT 5
        ", [$userId]);
        
        jsonResponse(['success' => true, 'orders' => $orders]);
        
    } catch (PDOException $e) {
        error_log("Recent orders fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch recent orders'], 500);
    }
}

/**
 * Handle paginated order history
 */
function handleOrderHistory($userId, $filter, $sort, $page, $search) {
    global $pdo;
    
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    try {
        // Build query conditions
        $conditions = ['o.user_id = ?'];
        $params = [$userId];
        
        // Filter by status
        if ($filter !== 'all') {
            if ($filter === 'pending') {
                $conditions[] = "o.status IN ('pending', 'accepted', 'in_progress')";
            } else {
                $conditions[] = "o.status = ?";
                $params[] = $filter;
            }
        }
        
        // Search functionality
        if (!empty($search)) {
            $conditions[] = "(o.order_id LIKE ? OR o.pickup_location LIKE ? OR o.destination LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        // Sort order
        $orderBy = 'ORDER BY o.created_at DESC';
        switch ($sort) {
            case 'oldest':
                $orderBy = 'ORDER BY o.created_at ASC';
                break;
            case 'price_high':
                $orderBy = 'ORDER BY o.total_price DESC';
                break;
            case 'price_low':
                $orderBy = 'ORDER BY o.total_price ASC';
                break;
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM orders o {$whereClause}";
        $total = fetchValue($countQuery, $params);
        
        // Get orders
        $query = "
            SELECT o.id, o.order_id, o.pickup_location, o.destination, o.status,
                   o.total_price, o.vehicle_type, o.payment_method, o.notes,
                   o.created_at, o.completed_at, o.cancelled_at,
                   d.name as driver_name, d.phone as driver_phone,
                   dr.vehicle_make, dr.vehicle_model, dr.vehicle_plate, dr.rating as driver_rating
            FROM orders o
            LEFT JOIN users d ON o.driver_id = d.id
            LEFT JOIN drivers dr ON o.driver_id = dr.user_id
            {$whereClause}
            {$orderBy}
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $orders = fetchMultiple($query, $params);
        
        // Check if there are more orders
        $hasMore = ($offset + $limit) < $total;
        
        jsonResponse([
            'success' => true,
            'orders' => $orders,
            'has_more' => $hasMore,
            'total' => $total,
            'page' => $page
        ]);
        
    } catch (PDOException $e) {
        error_log("Order history fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch order history'], 500);
    }
}

/**
 * Calculate estimated arrival time for driver
 */
function calculateEstimatedArrival($order) {
    // Simple estimation - in production use real traffic data and routing APIs
    $baseTime = 5; // 5 minutes base time
    $distance = $order['estimated_distance'] ?? 5;
    $estimatedMinutes = $baseTime + ($distance * 1.5); // 1.5 minutes per km
    
    return [
        'minutes' => (int)$estimatedMinutes,
        'text' => (int)$estimatedMinutes . ' minutes away'
    ];
}
?>
