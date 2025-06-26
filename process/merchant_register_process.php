<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    global $pdo;
    
    // Get form data
    $business_name = trim($_POST['business_name']);
    $business_category = trim($_POST['business_category']);
    $business_phone = trim($_POST['business_phone']);
    $business_email = trim($_POST['business_email']);
    
    // Address data
    $province = trim($_POST['province']);
    $city = trim($_POST['city']);
    $district = trim($_POST['district']);
    $village = trim($_POST['village']);
    $detailed_address = trim($_POST['detailed_address']);
    $postal_code = trim($_POST['postal_code']);
    
    // Owner data
    $owner_name = trim($_POST['owner_name']);
    $owner_phone = trim($_POST['owner_phone']);
    $owner_email = trim($_POST['owner_email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($business_name) || empty($business_category) || empty($business_phone) || 
        empty($province) || empty($city) || empty($district) || empty($village) || 
        empty($detailed_address) || empty($owner_name) || empty($owner_phone) || 
        empty($owner_email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib harus diisi']);
        exit();
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Password dan konfirmasi password tidak sama']);
        exit();
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
        exit();
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM merchants WHERE owner_email = ?");
    $stmt->execute([$owner_email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar']);
        exit();
    }
    
    // Check if business phone already exists
    $stmt = $pdo->prepare("SELECT id FROM merchants WHERE business_phone = ?");
    $stmt->execute([$business_phone]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Nomor telepon bisnis sudah terdaftar']);
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Build full address
    $full_address = $detailed_address . ', ' . $village . ', ' . $district . ', ' . $city . ', ' . $province;
    if (!empty($postal_code)) {
        $full_address .= ' ' . $postal_code;
    }
    
    // Insert merchant data
    $stmt = $pdo->prepare("
        INSERT INTO merchants (
            business_name, business_category, business_phone, business_email,
            business_address, province, city, district, village, postal_code,
            owner_name, owner_phone, owner_email, password_hash,
            is_active, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, true, NOW())
    ");
    
    $stmt->execute([
        $business_name, $business_category, $business_phone, $business_email,
        $full_address, $province, $city, $district, $village, $postal_code,
        $owner_name, $owner_phone, $owner_email, $hashed_password
    ]);
    
    $merchant_id = $pdo->lastInsertId();
    
    // Log registration activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, description, created_at) 
        VALUES (?, 'merchant_register', ?, NOW())
    ");
    $stmt->execute([
        $merchant_id, 
        "Merchant '{$business_name}' berhasil mendaftar"
    ]);
    
    // Create session for merchant
    $_SESSION['merchant_id'] = $merchant_id;
    $_SESSION['merchant_name'] = $business_name;
    $_SESSION['merchant_email'] = $owner_email;
    $_SESSION['merchant_owner'] = $owner_name;
    $_SESSION['merchant_login_time'] = time();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Pendaftaran berhasil! Selamat datang di RideMax',
        'redirect' => 'merchant_dashboard.php'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal mendaftar: ' . $e->getMessage()
    ]);
}
?>