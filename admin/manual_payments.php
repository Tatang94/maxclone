<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Cek admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $trx_id = $_POST['trx_id'] ?? '';
    $action = $_POST['action'];
    
    if ($action === 'approve' && !empty($trx_id)) {
        try {
            $pdo->beginTransaction();
            
            // Get transaction details
            $stmt = $pdo->prepare("
                SELECT pt.*, u.name as user_name, u.email 
                FROM paydisini_transactions pt 
                JOIN users u ON pt.user_id = u.id 
                WHERE pt.paydisini_trx_id = ?
            ");
            $stmt->execute([$trx_id]);
            $transaction = $stmt->fetch();
            
            if ($transaction && $transaction['status'] === 'waiting_confirmation') {
                // Update PayDisini transaction
                $stmt = $pdo->prepare("
                    UPDATE paydisini_transactions 
                    SET status = 'paid' 
                    WHERE paydisini_trx_id = ?
                ");
                $stmt->execute([$trx_id]);
                
                // Get current balance
                $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
                $stmt->execute([$transaction['user_id']]);
                $currentBalance = $stmt->fetchColumn() ?: 0;
                $newBalance = $currentBalance + $transaction['amount'];
                
                // Update user balance
                $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $stmt->execute([$newBalance, $transaction['user_id']]);
                
                // Update balance transaction
                $stmt = $pdo->prepare("
                    UPDATE balance_transactions 
                    SET status = 'completed', 
                        balance_before = ?, 
                        balance_after = ?,
                        description = CONCAT(description, ' - Disetujui Admin')
                    WHERE reference_id = ? AND user_id = ?
                ");
                $stmt->execute([$currentBalance, $newBalance, $trx_id, $transaction['user_id']]);
                
                $pdo->commit();
                $success_msg = "Pembayaran berhasil disetujui untuk " . $transaction['user_name'];
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Gagal menyetujui pembayaran: " . $e->getMessage();
        }
    } elseif ($action === 'reject' && !empty($trx_id)) {
        try {
            // Update status to rejected
            $stmt = $pdo->prepare("
                UPDATE paydisini_transactions 
                SET status = 'rejected',
                    callback_data = JSON_SET(COALESCE(callback_data, '{}'), '$.rejected_at', NOW())
                WHERE paydisini_trx_id = ?
            ");
            $stmt->execute([$trx_id]);
            
            // Update balance transaction
            $stmt = $pdo->prepare("
                UPDATE balance_transactions 
                SET status = 'rejected',
                    description = CONCAT(description, ' - Ditolak Admin')
                WHERE reference_id = ?
            ");
            $stmt->execute([$trx_id]);
            
            $success_msg = "Pembayaran berhasil ditolak";
        } catch (Exception $e) {
            $error_msg = "Gagal menolak pembayaran: " . $e->getMessage();
        }
    }
}

// Get pending manual payments
$stmt = $pdo->prepare("
    SELECT pt.*, u.name as user_name, u.email, u.phone,
           bt.created_at as confirmation_date
    FROM paydisini_transactions pt 
    JOIN users u ON pt.user_id = u.id 
    LEFT JOIN balance_transactions bt ON bt.reference_id = pt.paydisini_trx_id
    WHERE pt.status = 'waiting_confirmation' 
    ORDER BY pt.created_at DESC
");
$stmt->execute();
$pending_payments = $stmt->fetchAll();

include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <?php if (isset($success_msg)): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger"><?= $error_msg ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Konfirmasi Pembayaran Manual QRIS</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_payments)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h6>Tidak ada pembayaran yang menunggu konfirmasi</h6>
                        <p class="text-muted">Semua pembayaran manual sudah diproses</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>User</th>
                                    <th>Nominal</th>
                                    <th>Pengirim</th>
                                    <th>Catatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_payments as $payment): ?>
                                <?php 
                                $callback_data = json_decode($payment['callback_data'], true);
                                $sender_name = $callback_data['sender_name'] ?? 'N/A';
                                $note = $callback_data['note'] ?? '';
                                ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($payment['confirmation_date'] ?? $payment['created_at'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($payment['user_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($payment['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($sender_name) ?></td>
                                    <td><?= htmlspecialchars($note) ?: '-' ?></td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Setujui pembayaran ini?')">
                                            <input type="hidden" name="trx_id" value="<?= $payment['paydisini_trx_id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Setujui
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline ms-1" onsubmit="return confirm('Tolak pembayaran ini?')">
                                            <input type="hidden" name="trx_id" value="<?= $payment['paydisini_trx_id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>