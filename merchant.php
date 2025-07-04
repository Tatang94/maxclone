<?php
session_start();

// Check if merchant is already logged in
if (isset($_SESSION['merchant_id'])) {
    header('Location: merchant_dashboard.php');
    exit();
}

// Check if user wants to login instead
if (isset($_GET['login'])) {
    header('Location: merchant_login.php');
    exit();
}

// Redirect to merchant registration page
header('Location: merchant_register.php');
exit();
?>