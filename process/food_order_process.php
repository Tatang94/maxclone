<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/WalletManager.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Auto-login demo user
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'demo@ridemax.com' OR id = 1 LIMIT 1");
        $stmt->execute();
        $demoUser = $stmt->fetch();
        
        if ($demoUser) {
            $_SESSION['user_id'] = $demoUser['id'];
            $_SESSION['user_name'] = $demoUser['name'];
            $_SESSION['user_email'] = $demoUser['email'];
            $_SESSION['user_type'] = $demoUser['user_type'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Please log in to continue']);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Authentication error']);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    
    // Get form data
    $foodId = isset($_POST['food_id']) ? (int)$_POST['food_id'] : 0;
    $foodName = trim($_POST['food_name'] ?? '');
    $foodType = trim($_POST['food_type'] ?? '');
    $merchantName = trim($_POST['merchant_name'] ?? '');
    $preparationTime = (int)($_POST['preparation_time'] ?? 15);
    $pickupLocation = trim($_POST['pickup_location'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $estimatedPrice = (int)($_POST['estimated_price'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? 'cash');
    
    // Validation
    if ($foodId <= 0 || empty($foodName) || empty($merchantName) || empty($pickupLocation) || empty($destination) || $estimatedPrice <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data pesanan tidak lengkap']);
        exit();
    }
    
    // Generate order ID
    $orderId = 'FD' . date('YmdHis') . sprintf('%04d', rand(1000, 9999));
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                order_id, user_id, pickup_address, destination_address, 
                vehicle_type, payment_method, estimated_fare, status, 
                special_instructions, food_id, food_name, food_type, 
                merchant_name, preparation_time, order_type, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, 'delivery', ?, ?, 'pending', ?, ?, ?, ?, ?, ?, 'food_delivery', NOW(), NOW()
            )
        ");
        
        $notes = "Pesanan makanan: $foodName dari $merchantName (Waktu persiapan: {$preparationTime} menit)";
        
        $stmt->execute([
            $orderId, $userId, $pickupLocation, $destination, 
            $paymentMethod, $estimatedPrice, $notes, 
            $foodId, $foodName, $foodType, $merchantName, $preparationTime
        ]);
        
        $orderDbId = $pdo->lastInsertId();
        
        // Process wallet payment if selected
        if ($paymentMethod === 'wallet') {
            $wallet = new WalletManager($pdo);
            $deductResult = $wallet->deductBalance($userId, $estimatedPrice, $orderDbId, "Pembayaran Order #{$orderId}");
            
            if (!$deductResult) {
                throw new Exception('Saldo tidak mencukupi');
            }
        }
        
        // Find available drivers (simplified)
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE user_type = 'driver' AND is_driver = true 
            ORDER BY RANDOM() 
            LIMIT 5
        ");
        $stmt->execute();
        $drivers = $stmt->fetchAll();
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, created_at) 
            VALUES (?, 'food_order_created', ?, NOW())
        ");
        $stmt->execute([$userId, "Food order created: $orderId - $foodName from $merchantName"]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Pesanan makanan berhasil dibuat',
            'order_id' => $orderDbId,
            'order_code' => $orderId
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Food order error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>