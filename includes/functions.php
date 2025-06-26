<?php
/**
 * Global Functions for RideMax Super App
 * Common functions used throughout the application
 */

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user is admin
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if current user is driver
 * @return bool
 */
function isDriver() {
    return isset($_SESSION['is_driver']) && $_SESSION['is_driver'] == 1;
}

/**
 * Get current user information
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, d.license_number, d.vehicle_make, d.vehicle_model, 
                   d.vehicle_plate, d.is_online, d.rating as driver_rating
            FROM users u 
            LEFT JOIN drivers d ON u.id = d.user_id 
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Redirect user to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Redirect user if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

/**
 * Log user in
 * @param array $user User data
 */
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['is_driver'] = $user['is_driver'] ?? 0;
    $_SESSION['login_time'] = time();
    
    // Update last login
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (PDOException $e) {
        error_log("Error updating last login: " . $e->getMessage());
    }
}

/**
 * Log user out
 */
function logout() {
    // Clear all session variables
    $_SESSION = [];
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Hash password securely
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate secure random token
 * @param int $length Token length
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Sanitize input data
 * @param mixed $data Input data
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email Email address
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indonesian format)
 * @param string $phone Phone number
 * @return bool
 */
function isValidPhone($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Indonesian phone number
    return preg_match('/^(08|62|0)[0-9]{8,12}$/', $phone);
}

/**
 * Format phone number
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (substr($phone, 0, 1) === '0') {
        return '+62' . substr($phone, 1);
    } elseif (substr($phone, 0, 2) === '62') {
        return '+' . $phone;
    }
    
    return $phone;
}

/**
 * Calculate distance between two points (Haversine formula)
 * @param float $lat1 Latitude 1
 * @param float $lon1 Longitude 1
 * @param float $lat2 Latitude 2
 * @param float $lon2 Longitude 2
 * @return float Distance in kilometers
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

/**
 * Calculate ride price
 * @param string $vehicleType Vehicle type (economy, comfort, premium)
 * @param float $distance Distance in kilometers
 * @param array $options Additional pricing options
 * @return int Price in IDR
 */
function calculateRidePrice($vehicleType, $distance = 5, $options = []) {
    // Validate and sanitize inputs
    $distance = max(0.1, min(100, (float)$distance)); // Min 0.1km, max 100km
    
    // Map vehicle types to standard pricing
    $vehicleTypeMap = [
        'economy' => 'bike',
        'comfort' => 'car', 
        'premium' => 'delivery',
        'bike' => 'bike',
        'car' => 'car',
        'delivery' => 'delivery'
    ];
    
    $mappedType = $vehicleTypeMap[$vehicleType] ?? 'bike';
    
    // Consistent pricing with frontend
    $basePrices = [
        'bike' => 8000,
        'car' => 15000,
        'delivery' => 12000
    ];
    
    $pricePerKm = [
        'bike' => 2000,
        'car' => 3500,
        'delivery' => 2500
    ];
    
    $basePrice = $basePrices[$mappedType];
    $distancePrice = round($distance * $pricePerKm[$mappedType]);
    
    $total = $basePrice + $distancePrice;
    
    // Apply surge pricing if enabled
    if (isset($options['surge']) && $options['surge']) {
        $total *= 1.5; // 50% surge
    }
    
    // Ensure reasonable price range
    $total = max(5000, min(500000, round($total)));
    
    return (int)round($total);
}

/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param array $headers Additional headers
 * @return bool
 */
function sendEmail($to, $subject, $message, $headers = []) {
    // Basic email headers
    $defaultHeaders = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: RideMax <noreply@ridemax.com>',
        'Reply-To: support@ridemax.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    try {
        return mail($to, $subject, $message, implode("\r\n", $allHeaders));
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send SMS notification (placeholder - integrate with SMS service)
 * @param string $phone Phone number
 * @param string $message SMS message
 * @return bool
 */
function sendSMS($phone, $message) {
    // This is a placeholder function
    // In production, integrate with SMS service like Twilio, Nexmo, or local SMS gateway
    
    error_log("SMS to {$phone}: {$message}");
    return true;
}

/**
 * Log system activity
 * @param string $action Action performed
 * @param string $details Action details
 * @param int $userId User ID (optional)
 */
function logActivity($action, $details = '', $userId = null) {
    global $pdo;
    
    if ($userId === null && isLoggedIn()) {
        $userId = $_SESSION['user_id'];
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Get setting value from database
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed Setting value
 */
function getSetting($key, $default = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        
        return $result !== false ? $result : $default;
    } catch (PDOException $e) {
        error_log("Error getting setting {$key}: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set setting value in database
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool
 */
function setSetting($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value), 
            updated_at = NOW()
        ");
        
        return $stmt->execute([$key, $value]);
    } catch (PDOException $e) {
        error_log("Error setting {$key}: " . $e->getMessage());
        return false;
    }
}

/**
 * Format currency amount (Indonesian Rupiah)
 * @param int $amount Amount in cents/minor units
 * @param bool $showSymbol Show currency symbol
 * @return string Formatted currency
 */
function formatCurrency($amount, $showSymbol = true) {
    $formatted = number_format($amount, 0, ',', '.');
    return $showSymbol ? 'Rp ' . $formatted : $formatted;
}

/**
 * Format date for display
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Get time ago string
 * @param string $datetime Date time string
 * @return string Time ago string
 */
function timeAgo($datetime) {
    try {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';
        
        return floor($time/31536000) . ' years ago';
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Generate order ID
 * @return string Order ID
 */
function generateOrderId() {
    return 'RM' . date('Ymd') . substr(time(), -4) . mt_rand(10, 99);
}

/**
 * Check if request is AJAX
 * @return bool
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Return JSON response
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Validate CSRF token
 * @param string $token Token to validate
 * @return bool
 */
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Rate limiting check
 * @param string $key Rate limit key
 * @param int $limit Request limit
 * @param int $window Time window in seconds
 * @return bool
 */
function checkRateLimit($key, $limit = 60, $window = 3600) {
    $identifier = $key . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION['rate_limits'][$identifier])) {
        $_SESSION['rate_limits'][$identifier] = [
            'count' => 0,
            'reset_time' => time() + $window
        ];
    }
    
    $rateLimit = &$_SESSION['rate_limits'][$identifier];
    
    // Reset if window expired
    if (time() > $rateLimit['reset_time']) {
        $rateLimit['count'] = 0;
        $rateLimit['reset_time'] = time() + $window;
    }
    
    $rateLimit['count']++;
    
    return $rateLimit['count'] <= $limit;
}

/**
 * Clean old sessions and temporary data
 */
function cleanupSessions() {
    // Clean rate limits
    if (isset($_SESSION['rate_limits'])) {
        foreach ($_SESSION['rate_limits'] as $key => $data) {
            if (time() > $data['reset_time']) {
                unset($_SESSION['rate_limits'][$key]);
            }
        }
    }
}

// Run cleanup on session start
if (session_status() === PHP_SESSION_ACTIVE) {
    cleanupSessions();
}
?>
