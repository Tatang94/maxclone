<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/WalletManager.php';
require_once '../includes/paydisini.php';

header('Content-Type: application/json');

// Cek login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $wallet = new WalletManager($pdo);
    $balance = $wallet->getBalance($user_id);
    
    echo json_encode([
        'success' => true,
        'balance' => $balance,
        'formatted' => 'Rp ' . number_format($balance, 0, ',', '.')
    ]);
    
} catch (Exception $e) {
    error_log("Get Balance Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get balance']);
}
?>