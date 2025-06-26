<?php
/**
 * Login Process Handler
 * Handles user authentication and session management
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Rate limiting check
if (!checkRateLimit('login', 5, 900)) { // 5 attempts per 15 minutes
    jsonResponse(['success' => false, 'message' => 'Too many login attempts. Please try again later.'], 429);
}

try {
    // Get and validate input
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $adminLogin = isset($_POST['admin_login']); // Flag for admin login
    
    // Basic validation
    if (!$email) {
        jsonResponse(['success' => false, 'message' => 'Please enter a valid email address']);
    }
    
    if (empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Please enter your password']);
    }
    
    // Check if user exists and get user data
    $stmt = $pdo->prepare("
        SELECT u.*, d.license_number, d.vehicle_make, d.vehicle_model, 
               d.vehicle_plate, d.is_online, d.rating as driver_rating
        FROM users u 
        LEFT JOIN drivers d ON u.id = d.user_id 
        WHERE u.email = ? AND u.status = 'active'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Log failed attempt
        logActivity('login_failed', "Failed login attempt for email: {$email}");
        jsonResponse(['success' => false, 'message' => 'Invalid email or password']);
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        // Log failed attempt
        logActivity('login_failed', "Invalid password for user: {$user['id']}", $user['id']);
        jsonResponse(['success' => false, 'message' => 'Invalid email or password']);
    }
    
    // If admin login is requested, validate admin permissions
    if ($adminLogin && $user['user_type'] !== 'admin') {
        logActivity('admin_login_denied', "Non-admin user attempted admin login: {$user['id']}", $user['id']);
        jsonResponse(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    }
    
    // Check if account is locked or suspended
    if ($user['status'] === 'suspended') {
        jsonResponse(['success' => false, 'message' => 'Your account has been suspended. Please contact support.']);
    }
    
    // Successful login
    loginUser($user);
    
    // Force session save
    session_write_close();
    session_start();
    
    // Set remember me cookie if requested
    if ($remember) {
        $token = generateToken();
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store remember token in database
        $stmt = $pdo->prepare("
            INSERT INTO remember_tokens (user_id, token, expires_at) 
            VALUES (?, ?, FROM_UNIXTIME(?))
            ON DUPLICATE KEY UPDATE 
            token = VALUES(token), 
            expires_at = VALUES(expires_at)
        ");
        $stmt->execute([$user['id'], hash('sha256', $token), $expiry]);
        
        // Set cookie
        setcookie('remember_token', $token, $expiry, '/', '', true, true);
    }
    
    // Log successful login
    logActivity('login_success', "User logged in successfully", $user['id']);
    
    // Determine redirect URL based on user type and login context
    $redirectUrl = 'index.php';
    if ($user['user_type'] === 'admin') {
        $redirectUrl = $adminLogin ? 'dashboard.php' : 'admin/dashboard.php';
    } elseif ($user['is_driver'] == 1 && isset($_POST['driver_mode'])) {
        $redirectUrl = 'driver.php';
    }
    
    // Return success response
    jsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirectUrl,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'is_driver' => $user['is_driver']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'A database error occurred. Please try again.'], 500);
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
}
?>
