<?php
/**
 * Order Process Handler
 * Handles ride booking and order management
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/WalletManager.php';
require_once '../includes/paydisini.php';

// Set JSON response header
header('Content-Type: application/json');

// Debug session data
error_log("Order process - Session data: " . print_r($_SESSION, true));
error_log("Order process - isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false'));

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
            error_log("Auto-logged in demo user: " . $demoUser['id']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Please log in to continue'], 401);
        }
    } catch (Exception $e) {
        error_log("Error auto-login: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Please log in to continue'], 401);
    }
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Rate limiting check
if (!checkRateLimit('order', 10, 3600)) { // 10 orders per hour
    jsonResponse(['success' => false, 'message' => 'Too many order attempts. Please try again later.'], 429);
}

try {
    $userId = $_SESSION['user_id'];
    
    // Get and validate input
    $action = $_POST['action'] ?? 'create_order';
    $pickupLocation = trim($_POST['pickup_location'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $vehicleType = $_POST['vehicle_type'] ?? 'economy';
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $notes = trim($_POST['notes'] ?? '');
    $estimatedPrice = (int)($_POST['estimated_price'] ?? 0);
    
    // Food order specific data
    $foodId = isset($_POST['food_id']) ? (int)$_POST['food_id'] : null;
    $foodName = trim($_POST['food_name'] ?? '');
    $foodType = trim($_POST['food_type'] ?? '');
    $merchantName = trim($_POST['merchant_name'] ?? '');
    $preparationTime = (int)($_POST['preparation_time'] ?? 15);
    
    // If it's a food order, set vehicle type to delivery and create notes
    if ($action === 'create_food_order') {
        $vehicleType = 'delivery';
        $notes = "Pesanan makanan: $foodName dari $merchantName (Waktu persiapan: {$preparationTime} menit)";
        
        // Additional validation for food orders
        if ($foodId <= 0 || empty($foodName) || empty($merchantName)) {
            jsonResponse(['success' => false, 'message' => 'Data pesanan makanan tidak lengkap']);
        }
    }
    
    // Schedule options
    $scheduleLater = isset($_POST['schedule_later']);
    $scheduleDate = $_POST['schedule_date'] ?? '';
    $scheduleTime = $_POST['schedule_time'] ?? '';
    $roundTrip = isset($_POST['round_trip']);
    
    // Basic validation
    if (empty($pickupLocation)) {
        jsonResponse(['success' => false, 'message' => 'Please enter pickup location']);
    }
    
    if (empty($destination)) {
        jsonResponse(['success' => false, 'message' => 'Please enter destination']);
    }
    
    if ($pickupLocation === $destination) {
        jsonResponse(['success' => false, 'message' => 'Pickup and destination cannot be the same']);
    }
    
    if (!in_array($vehicleType, ['economy', 'comfort', 'premium', 'delivery', 'bike', 'car'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid vehicle type']);
    }
    
    if (!in_array($paymentMethod, ['cash', 'card', 'wallet'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid payment method']);
    }
    
    if ($estimatedPrice <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid price calculation']);
    }
    
    // Validate schedule if scheduling for later
    $scheduledFor = null;
    if ($scheduleLater) {
        if (empty($scheduleDate) || empty($scheduleTime)) {
            jsonResponse(['success' => false, 'message' => 'Please provide schedule date and time']);
        }
        
        $scheduledFor = $scheduleDate . ' ' . $scheduleTime . ':00';
        $scheduledTimestamp = strtotime($scheduledFor);
        
        if ($scheduledTimestamp <= time()) {
            jsonResponse(['success' => false, 'message' => 'Scheduled time must be in the future']);
        }
        
        // Don't allow scheduling more than 7 days in advance
        if ($scheduledTimestamp > time() + (7 * 24 * 60 * 60)) {
            jsonResponse(['success' => false, 'message' => 'Cannot schedule more than 7 days in advance']);
        }
    }
    
    // Check for active orders
    $stmt = $pdo->prepare("
        SELECT id FROM orders 
        WHERE user_id = ? AND status IN ('pending', 'accepted', 'in_progress')
    ");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'You already have an active order. Please complete or cancel it first.']);
    }
    
    // Calculate actual price (double-check)
    $distance = calculateEstimatedDistance($pickupLocation, $destination);
    $calculatedPrice = calculateRidePrice($vehicleType, $distance);
    
    // Allow small variance (±10%) to account for UI calculations
    if (abs($estimatedPrice - $calculatedPrice) > ($calculatedPrice * 0.1)) {
        $estimatedPrice = $calculatedPrice; // Use server-calculated price
    }
    
    // Handle wallet payment
    if ($paymentMethod === 'wallet') {
        $wallet = new WalletManager($pdo);
        $currentBalance = $wallet->getBalance($userId);
        
        if ($currentBalance < $estimatedPrice) {
            jsonResponse(['success' => false, 'message' => 'Saldo tidak mencukupi. Silakan isi saldo terlebih dahulu.']);
        }
    }
    
    // Generate order ID
    $orderId = generateOrderId();
    
    // Start transaction
    beginTransaction();
    
    try {
        // Insert order
        $orderData = [
            'user_id' => $userId,
            'pickup_address' => $pickupLocation,
            'pickup_lat' => 0.0, // Will be updated with actual coordinates
            'pickup_lng' => 0.0,
            'destination_address' => $destination,
            'destination_lat' => 0.0,
            'destination_lng' => 0.0,
            'vehicle_type' => $vehicleType,
            'payment_method' => $paymentMethod,
            'estimated_fare' => $estimatedPrice,
            'distance_km' => $distance,
            'status' => $scheduleLater ? 'scheduled' : 'pending',
            'special_instructions' => $notes,
            'scheduled_at' => $scheduledFor,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add food order specific data if this is a food order
        if ($action === 'create_food_order') {
            $orderData['food_id'] = $foodId;
            $orderData['food_name'] = $foodName;
            $orderData['food_type'] = $foodType;
            $orderData['merchant_name'] = $merchantName;
            $orderData['preparation_time'] = $preparationTime;
            $orderData['order_type'] = 'food_delivery';
        } else {
            $orderData['order_type'] = 'transport';
        }
        
        $orderDbId = insertData('orders', $orderData);
        
        // Process wallet payment if selected
        if ($paymentMethod === 'wallet') {
            $wallet->deductBalance($userId, $estimatedPrice, $orderDbId, "Pembayaran Order #{$orderDbId}");
        }
        
        // If scheduling for later, don't immediately search for drivers
        if (!$scheduleLater) {
            // Find available drivers
            $availableDrivers = findAvailableDrivers($pickupLocation, $vehicleType);
            
            if (empty($availableDrivers)) {
                // No drivers available, keep order as pending
                logActivity('order_created', "Order created but no drivers available: {$orderId}", $userId);
            } else {
                // Notify drivers about new order (would use push notifications in production)
                notifyAvailableDrivers($availableDrivers, $orderDbId);
                logActivity('order_created', "Order created and drivers notified: {$orderId}", $userId);
            }
        } else {
            logActivity('order_scheduled', "Order scheduled for {$scheduledFor}: {$orderId}", $userId);
        }
        
        // Commit transaction
        commitTransaction();
        
        // Send confirmation email/SMS if configured
        sendOrderConfirmation($userId, $orderDbId);
        
        // Return success response
        $message = $scheduleLater ? 'Perjalanan berhasil dijadwalkan!' : 'Perjalanan berhasil dipesan!';
        if ($paymentMethod === 'wallet') {
            $message .= ' Pembayaran telah dikurangi dari saldo dompet.';
        }
        
        jsonResponse([
            'success' => true,
            'message' => $message,
            'order_id' => $orderDbId,
            'estimated_price' => $estimatedPrice,
            'estimated_distance' => $distance,
            'payment_method' => $paymentMethod,
            'drivers_available' => !$scheduleLater ? count($availableDrivers ?? []) : null
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        rollbackTransaction();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Order creation error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'A database error occurred. Please try again.'], 500);
} catch (Exception $e) {
    error_log("Order creation error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
}

/**
 * Calculate estimated distance between two locations
 * In production, this would use a geocoding API
 */
function calculateEstimatedDistance($pickup, $destination) {
    // Simple estimation based on string similarity (for demo)
    // In production, use Google Maps API or similar
    $baseDistance = 5; // Default 5km
    $variation = strlen($pickup) + strlen($destination);
    return max(1, $baseDistance + ($variation % 10));
}

/**
 * Find available drivers near pickup location
 */
function findAvailableDrivers($pickupLocation, $vehicleType) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT d.user_id, d.vehicle_make, d.vehicle_model, d.rating, u.name, u.phone
            FROM drivers d
            JOIN users u ON d.user_id = u.id
            WHERE d.is_online = TRUE 
            AND u.status = 'active'
            AND d.user_id NOT IN (
                SELECT DISTINCT driver_id 
                FROM orders 
                WHERE driver_id IS NOT NULL 
                AND status IN ('accepted', 'in_progress')
            )
            ORDER BY d.rating DESC, d.total_trips DESC
            LIMIT 10
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error finding drivers: " . $e->getMessage());
        return [];
    }
}

/**
 * Notify available drivers about new order
 */
function notifyAvailableDrivers($drivers, $orderId) {
    // In production, this would send push notifications
    // For now, we'll log the notification
    foreach ($drivers as $driver) {
        logActivity('driver_notified', "Driver notified about order {$orderId}", $driver['user_id']);
    }
}

/**
 * Send order confirmation to user
 */
function sendOrderConfirmation($userId, $orderId) {
    global $pdo;
    
    try {
        // Get user and order details
        $stmt = $pdo->prepare("
            SELECT u.email, u.name, u.phone, o.order_id, o.pickup_location, 
                   o.destination, o.total_price, o.vehicle_type
            FROM users u
            JOIN orders o ON u.id = o.user_id
            WHERE u.id = ? AND o.id = ?
        ");
        $stmt->execute([$userId, $orderId]);
        $data = $stmt->fetch();
        
        if ($data) {
            // Send email confirmation
            $subject = 'RideMax Order Confirmation - ' . $data['order_id'];
            $message = "
            <html>
            <body>
                <h2>Order Confirmation</h2>
                <p>Hi {$data['name']},</p>
                <p>Your ride has been booked successfully!</p>
                <ul>
                    <li><strong>Order ID:</strong> {$data['order_id']}</li>
                    <li><strong>From:</strong> {$data['pickup_location']}</li>
                    <li><strong>To:</strong> {$data['destination']}</li>
                    <li><strong>Vehicle:</strong> " . ucfirst($data['vehicle_type']) . "</li>
                    <li><strong>Total:</strong> Rp " . number_format($data['total_price']) . "</li>
                </ul>
                <p>We're finding a driver for you. You'll be notified once a driver accepts your request.</p>
                <p>Thank you for choosing RideMax!</p>
            </body>
            </html>
            ";
            
            sendEmail($data['email'], $subject, $message);
            
            // Send SMS if phone is available
            if ($data['phone']) {
                $smsMessage = "RideMax: Your ride {$data['order_id']} has been booked. From {$data['pickup_location']} to {$data['destination']}. Total: Rp " . number_format($data['total_price']);
                sendSMS($data['phone'], $smsMessage);
            }
        }
    } catch (Exception $e) {
        error_log("Error sending order confirmation: " . $e->getMessage());
    }
}
?>
