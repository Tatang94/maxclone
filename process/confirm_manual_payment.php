<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Cek login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$trx_id = $_POST['trx_id'] ?? '';
$sender_name = $_POST['sender_name'] ?? '';
$note = $_POST['note'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($trx_id) || empty($sender_name)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

try {
    // Cek transaksi exists
    $stmt = $pdo->prepare("
        SELECT * FROM paydisini_transactions 
        WHERE paydisini_trx_id = ? AND user_id = ?
    ");
    $stmt->execute([$trx_id, $user_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }
    
    if ($transaction['status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Transaction already paid']);
        exit;
    }
    
    // Update status ke waiting_confirmation
    $stmt = $pdo->prepare("
        UPDATE paydisini_transactions 
        SET status = 'waiting_confirmation', 
            callback_data = ?,
            paid_at = CURRENT_TIMESTAMP
        WHERE paydisini_trx_id = ?
    ");
    
    $confirmation_data = json_encode([
        'sender_name' => $sender_name,
        'note' => $note,
        'confirmed_at' => date('Y-m-d H:i:s'),
        'type' => 'manual_qris'
    ]);
    
    $stmt->execute([$confirmation_data, $trx_id]);
    
    // Insert notification untuk admin
    $stmt = $pdo->prepare("
        INSERT INTO balance_transactions 
        (user_id, transaction_type, amount, balance_before, balance_after, status, description, reference_id, created_at)
        VALUES (?, 'deposit', ?, 0, 0, 'pending_confirmation', ?, ?, CURRENT_TIMESTAMP)
    ");
    
    $description = "Top Up Manual QRIS - Menunggu Konfirmasi Admin (Pengirim: $sender_name)";
    $stmt->execute([$user_id, $transaction['amount'], $description, $trx_id]);
    
    echo json_encode(['success' => true, 'message' => 'Confirmation sent successfully']);
    
} catch (Exception $e) {
    error_log("Manual Payment Confirmation Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal error']);
}
?>