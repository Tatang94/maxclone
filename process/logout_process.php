<?php
/**
 * Logout Process Handler
 * Handles user logout and session cleanup
 */

session_start();
require_once '../includes/functions.php';

// Log logout activity if user was logged in
if (isLoggedIn()) {
    require_once '../includes/db.php';
    
    $userId = $_SESSION['user_id'];
    logActivity('logout', 'User logged out', $userId);
    
    // If user is a driver, set them offline
    if (isDriver()) {
        try {
            $stmt = $pdo->prepare("UPDATE drivers SET is_online = 0, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error updating driver status on logout: " . $e->getMessage());
        }
    }
    
    // Clear remember token if exists
    if (isset($_COOKIE['remember_token'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Clear cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        } catch (PDOException $e) {
            error_log("Error clearing remember token: " . $e->getMessage());
        }
    }
}

// Perform logout
logout();

// Check if it's an AJAX request
if (isAjax()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit();
}

// Redirect to login page with logout confirmation
header('Location: ../login.php?logged_out=1');
exit();
?>
