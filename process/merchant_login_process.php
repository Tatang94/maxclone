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
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email dan password harus diisi']);
        exit();
    }
    
    // Check merchant credentials
    $stmt = $pdo->prepare("SELECT * FROM merchants WHERE owner_email = ? AND is_active = true");
    $stmt->execute([$email]);
    $merchant = $stmt->fetch();
    
    if (!$merchant || !password_verify($password, $merchant['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
        exit();
    }
    
    // Create session
    $_SESSION['merchant_id'] = $merchant['id'];
    $_SESSION['merchant_name'] = $merchant['business_name'];
    $_SESSION['merchant_email'] = $merchant['owner_email'];
    $_SESSION['merchant_owner'] = $merchant['owner_name'];
    $_SESSION['merchant_login_time'] = time();
    
    // Update last login
    $stmt = $pdo->prepare("UPDATE merchants SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$merchant['id']]);
    
    // Log login activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, description, created_at) 
        VALUES (?, 'merchant_login', ?, NOW())
    ");
    $stmt->execute([
        $merchant['id'], 
        "Merchant '{$merchant['business_name']}' login"
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login berhasil',
        'redirect' => 'merchant_dashboard.php'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal login: ' . $e->getMessage()
    ]);
}
?>