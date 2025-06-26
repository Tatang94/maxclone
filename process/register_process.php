<?php
/**
 * Registration Process Handler
 * Handles user registration and driver account setup
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
if (!checkRateLimit('register', 3, 3600)) { // 3 attempts per hour
    jsonResponse(['success' => false, 'message' => 'Too many registration attempts. Please try again later.'], 429);
}

try {
    // Get and validate input
    $name = trim($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $userType = $_POST['user_type'] ?? '';
    $termsAccepted = isset($_POST['terms']);
    
    // Driver-specific fields
    $isDriver = isset($_POST['is_driver']);
    $licenseNumber = trim($_POST['license_number'] ?? '');
    $vehicleMake = trim($_POST['vehicle_make'] ?? '');
    $vehicleModel = trim($_POST['vehicle_model'] ?? '');
    $vehiclePlate = trim($_POST['vehicle_plate'] ?? '');
    
    // Basic validation
    if (empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Please enter your full name']);
    }
    
    if (!$email) {
        jsonResponse(['success' => false, 'message' => 'Please enter a valid email address']);
    }
    
    if (empty($phone)) {
        jsonResponse(['success' => false, 'message' => 'Please enter your phone number']);
    }
    
    if (!isValidPhone($phone)) {
        jsonResponse(['success' => false, 'message' => 'Please enter a valid phone number']);
    }
    
    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    }
    
    if ($password !== $confirmPassword) {
        jsonResponse(['success' => false, 'message' => 'Passwords do not match']);
    }
    
    if (!in_array($userType, ['user', 'driver'])) {
        jsonResponse(['success' => false, 'message' => 'Please select a valid account type']);
    }
    
    if (!$termsAccepted) {
        jsonResponse(['success' => false, 'message' => 'Please accept the terms of service']);
    }
    
    // Driver-specific validation
    if ($userType === 'driver' || $isDriver) {
        if (empty($licenseNumber)) {
            jsonResponse(['success' => false, 'message' => 'Please enter your license number']);
        }
        
        if (empty($vehicleMake) || empty($vehicleModel)) {
            jsonResponse(['success' => false, 'message' => 'Please enter your vehicle information']);
        }
        
        if (empty($vehiclePlate)) {
            jsonResponse(['success' => false, 'message' => 'Please enter your vehicle plate number']);
        }
        
        // Validate license plate format (Indonesian format)
        if (!preg_match('/^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{1,3}$/i', $vehiclePlate)) {
            jsonResponse(['success' => false, 'message' => 'Please enter a valid license plate number']);
        }
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Email address is already registered']);
    }
    
    // Check if phone already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([formatPhone($phone)]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Phone number is already registered']);
    }
    
    // For drivers, check if license number already exists
    if ($userType === 'driver' || $isDriver) {
        $stmt = $pdo->prepare("SELECT user_id FROM drivers WHERE license_number = ?");
        $stmt->execute([$licenseNumber]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'License number is already registered']);
        }
        
        // Check if vehicle plate already exists
        $stmt = $pdo->prepare("SELECT user_id FROM drivers WHERE vehicle_plate = ?");
        $stmt->execute([strtoupper($vehiclePlate)]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Vehicle plate number is already registered']);
        }
    }
    
    // Start transaction
    beginTransaction();
    
    try {
        // Hash password
        $hashedPassword = hashPassword($password);
        
        // Insert user
        $userData = [
            'name' => $name,
            'email' => $email,
            'phone' => formatPhone($phone),
            'password' => $hashedPassword,
            'user_type' => $userType === 'driver' ? 'user' : $userType,
            'is_driver' => ($userType === 'driver' || $isDriver) ? 1 : 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = insertData('users', $userData);
        
        // If driver, insert driver data
        if ($userType === 'driver' || $isDriver) {
            $driverData = [
                'user_id' => $userId,
                'license_number' => $licenseNumber,
                'vehicle_make' => $vehicleMake,
                'vehicle_model' => $vehicleModel,
                'vehicle_plate' => strtoupper($vehiclePlate),
                'is_online' => 0,
                'rating' => 5.0,
                'total_rides' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            insertData('drivers', $driverData);
        }
        
        // Commit transaction
        commitTransaction();
        
        // Log successful registration
        logActivity('user_registered', "New user registered: {$email}", $userId);
        
        // Send welcome email (if email service is configured)
        $emailSent = sendWelcomeEmail($email, $name);
        
        // Return success response
        jsonResponse([
            'success' => true,
            'message' => 'Account created successfully! You can now log in.',
            'user_id' => $userId,
            'email_sent' => $emailSent
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        rollbackTransaction();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    
    // Check for duplicate key error
    if ($e->getCode() === '23000') {
        jsonResponse(['success' => false, 'message' => 'Email or phone number is already registered']);
    }
    
    jsonResponse(['success' => false, 'message' => 'A database error occurred. Please try again.'], 500);
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
}

/**
 * Send welcome email to new user
 */
function sendWelcomeEmail($email, $name) {
    $subject = 'Welcome to RideMax!';
    $message = "
    <html>
    <head>
        <title>Welcome to RideMax</title>
    </head>
    <body>
        <h2>Welcome to RideMax, {$name}!</h2>
        <p>Thank you for joining RideMax. Your account has been created successfully.</p>
        <p>You can now:</p>
        <ul>
            <li>Book rides instantly</li>
            <li>Track your orders in real-time</li>
            <li>Access your ride history</li>
            <li>Enjoy a safe and reliable service</li>
        </ul>
        <p>Download our mobile app for the best experience!</p>
        <p>Best regards,<br>The RideMax Team</p>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}
?>
