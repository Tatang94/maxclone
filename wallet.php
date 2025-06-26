<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/WalletManager.php';
require_once 'includes/paydisini.php';

// Cek login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$wallet = new WalletManager($pdo);
$current_balance = $wallet->getBalance($user_id);
$transaction_history = $wallet->getTransactionHistory($user_id, 20);

// Ambil notifikasi jika ada
$notification = $_SESSION['notification'] ?? null;
unset($_SESSION['notification']);

$page_title = "Dompet Digital";
require_once 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Balance Card -->
    <div class="bg-primary text-white p-4 mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-1">Saldo Dompet</h5>
                <h2 class="mb-0 fw-bold">Rp <?= number_format($current_balance, 0, ',', '.') ?></h2>
            </div>
            <div class="col-auto">
                <i class="fas fa-wallet fa-2x opacity-75"></i>
            </div>
        </div>
    </div>

    <?php if ($notification): ?>
    <div class="alert alert-<?= $notification['type'] ?> alert-dismissible fade show mx-3" role="alert">
        <?= htmlspecialchars($notification['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4 px-3">
        <div class="col-6">
            <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#topupModal">
                <i class="fas fa-plus-circle mb-2 d-block"></i>
                Isi Saldo
            </button>
        </div>
        <div class="col-6">
            <button class="btn btn-outline-primary w-100 py-3" onclick="window.location.href='history.php'">
                <i class="fas fa-history mb-2 d-block"></i>
                Riwayat
            </button>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="px-3">
        <h6 class="fw-bold mb-3">Transaksi Terbaru</h6>
        
        <?php if (empty($transaction_history)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-receipt fa-3x mb-3 opacity-50"></i>
            <p>Belum ada transaksi</p>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($transaction_history as $transaction): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <?php if ($transaction['transaction_type'] === 'deposit'): ?>
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-2">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <?php else: ?>
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="fw-medium"><?= htmlspecialchars($transaction['description']) ?></div>
                        <small class="text-muted"><?= date('d M Y H:i', strtotime($transaction['created_at'])) ?></small>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold <?= $transaction['transaction_type'] === 'deposit' ? 'text-success' : 'text-danger' ?>">
                        <?= $transaction['transaction_type'] === 'deposit' ? '+' : '-' ?>Rp <?= number_format($transaction['amount'], 0, ',', '.') ?>
                    </div>
                    <small class="text-muted">
                        <span class="badge bg-<?= $transaction['status'] === 'completed' ? 'success' : 'warning' ?> bg-opacity-20 text-<?= $transaction['status'] === 'completed' ? 'success' : 'warning' ?>">
                            <?= ucfirst($transaction['status']) ?>
                        </span>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Top Up Modal -->
<div class="modal fade" id="topupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Isi Saldo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process/topup_process.php" method="POST" id="topupForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Nominal</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="50000">
                                    Rp 50.000
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="100000">
                                    Rp 100.000
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="200000">
                                    Rp 200.000
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="500000">
                                    Rp 500.000
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="custom_amount" class="form-label">Atau Masukkan Nominal Lain</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="custom_amount" name="amount" min="10000" max="5000000" placeholder="Minimal Rp 10.000">
                        </div>
                        <div class="form-text">Minimal Rp 10.000, Maksimal Rp 5.000.000</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-qrcode me-2"></i>
                        Pembayaran menggunakan QRIS dan metode lainnya melalui Midtrans yang aman dan terpercaya.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-qrcode me-2"></i>Buat Kode QR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle amount selection
    const amountButtons = document.querySelectorAll('.amount-btn');
    const customAmountInput = document.getElementById('custom_amount');
    
    amountButtons.forEach(button => {
        button.addEventListener('click', function() {
            const amount = this.dataset.amount;
            customAmountInput.value = amount;
            
            // Update button states
            amountButtons.forEach(btn => btn.classList.remove('btn-primary'));
            amountButtons.forEach(btn => btn.classList.add('btn-outline-primary'));
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
        });
    });
    
    // Clear button selection when typing custom amount
    customAmountInput.addEventListener('input', function() {
        amountButtons.forEach(btn => btn.classList.remove('btn-primary'));
        amountButtons.forEach(btn => btn.classList.add('btn-outline-primary'));
    });
    
    // Form validation
    document.getElementById('topupForm').addEventListener('submit', function(e) {
        const amount = parseInt(customAmountInput.value);
        
        if (!amount || amount < 10000) {
            e.preventDefault();
            alert('Minimal top up Rp 10.000');
            return;
        }
        
        if (amount > 5000000) {
            e.preventDefault();
            alert('Maksimal top up Rp 5.000.000');
            return;
        }
    });
});
</script>