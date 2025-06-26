<?php
/**
 * Admin Index - Redirect to Dashboard or Login
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (isLoggedIn() && isAdmin()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>