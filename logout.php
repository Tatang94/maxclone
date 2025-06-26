<?php
session_start();
require_once 'includes/functions.php';

// Process logout
logout();

// Redirect to login page
header('Location: login.php?logged_out=1');
exit();
?>
