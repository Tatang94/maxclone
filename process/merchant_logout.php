<?php
session_start();

// Clear merchant session
unset($_SESSION['merchant_id']);
unset($_SESSION['merchant_name']);
unset($_SESSION['merchant_email']);
unset($_SESSION['merchant_owner']);
unset($_SESSION['merchant_login_time']);

// Destroy session if no other user sessions exist
if (!isset($_SESSION['user_id'])) {
    session_destroy();
}

// Redirect to merchant login
header('Location: ../merchant_login.php?message=logout_success');
exit();
?>