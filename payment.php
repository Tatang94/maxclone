<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/paydisini.php';

// Cek login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$trx_id = $_GET['trx_id'] ?? '';
if (empty($trx_id)) {
    header('Location: wallet.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data transaksi
$stmt = $pdo->prepare("
    SELECT * FROM paydisini_transactions 
    WHERE paydisini_trx_id = ? AND user_id = ? AND status = 'pending'
");
$stmt->execute([$trx_id, $user_id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    $_SESSION['notification'] = [
        'type' => 'danger',
        'message' => 'Transaksi tidak ditemukan atau sudah selesai'
    ];
    header('Location: wallet.php');
    exit;
}

// Cek apakah sudah expired
$expired_at = strtotime($transaction['expired_at']);
$now = time();
$is_expired = $now > $expired_at;

$page_title = "Pembayaran QRIS";
require_once 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header -->
    <div class="bg-primary text-white p-4 mb-4">
        <div class="d-flex align-items-center">
            <button onclick="history.back()" class="btn btn-link text-white p-0 me-3">
                <i class="fas fa-arrow-left fa-lg"></i>
            </button>
            <h5 class="mb-0">Pembayaran QRIS</h5>
        </div>
    </div>

    <div class="px-3">
        <?php if ($is_expired): ?>
        <!-- Expired State -->
        <div class="text-center py-5">
            <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-4 d-inline-flex mb-3">
                <i class="fas fa-clock fa-2x"></i>
            </div>
            <h5 class="fw-bold text-danger">Waktu Pembayaran Habis</h5>
            <p class="text-muted mb-4">Kode QR telah kedaluwarsa. Silakan buat transaksi baru.</p>
            <button onclick="window.location.href='wallet.php'" class="btn btn-primary">
                Kembali ke Dompet
            </button>
        </div>
        <?php else: ?>
        
        <!-- Payment Info -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="card-title mb-1">Total Pembayaran</h6>
                        <h4 class="text-primary fw-bold">Rp <?= number_format($transaction['amount'], 0, ',', '.') ?></h4>
                        <?php if ($transaction['fee'] > 0): ?>
                        <small class="text-muted">
                            Nominal: Rp <?= number_format($transaction['amount'] - $transaction['fee'], 0, ',', '.') ?> + 
                            Fee: Rp <?= number_format($transaction['fee'], 0, ',', '.') ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                            <i class="fas fa-qrcode fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <h6 class="card-title">Scan QR Code untuk Pembayaran</h6>
                <div class="my-4">
                    <?php if ($transaction['payment_method'] === 'midtrans'): ?>
                    <!-- Midtrans Snap Payment -->
                    <div class="text-center">
                        <div class="bg-success bg-opacity-10 rounded p-4 mb-3">
                            <i class="fas fa-qrcode fa-4x text-success mb-3"></i>
                            <h6>Pembayaran QRIS & Metode Lainnya</h6>
                            <p class="text-muted small mb-0">QRIS, E-Wallet, Virtual Account, Kartu Kredit</p>
                        </div>
                        <button id="pay-button" class="btn btn-success btn-lg w-100 mb-2">
                            <i class="fas fa-qrcode me-2"></i>Bayar dengan QRIS
                        </button>
                        <small class="text-muted">Atau pilih metode pembayaran lainnya</small>
                    </div>
                    <?php else: ?>
                    <div class="bg-light p-5 rounded">
                        <i class="fas fa-qrcode fa-4x text-muted"></i>
                        <p class="mt-3 text-muted">Metode pembayaran tidak dikenali</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Countdown Timer -->
                <div class="alert alert-warning">
                    <i class="fas fa-clock me-2"></i>
                    <span id="countdown">Menghitung...</span>
                </div>
            </div>
        </div>

        <!-- Payment Instructions -->
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="card-title">Cara Pembayaran</h6>
                <ol class="mb-0">
                    <li>Buka aplikasi mobile banking atau e-wallet Anda</li>
                    <li>Pilih menu "Scan QR" atau "Bayar"</li>
                    <li>Arahkan kamera ke kode QR di atas</li>
                    <li>Konfirmasi pembayaran sesuai nominal</li>
                    <li>Tunggu konfirmasi pembayaran berhasil</li>
                </ol>
            </div>
        </div>

        <!-- Payment Methods Info -->
        <?php if ($transaction['payment_method'] === 'midtrans'): ?>
        <div class="alert alert-success">
            <h6><i class="fas fa-qrcode me-2"></i>Metode Pembayaran yang Tersedia</h6>
            <div class="row g-2 mt-2">
                <div class="col-3 text-center">
                    <div class="bg-white rounded p-2">
                        <i class="fas fa-qrcode text-success"></i>
                        <small class="d-block">QRIS</small>
                    </div>
                </div>
                <div class="col-3 text-center">
                    <div class="bg-white rounded p-2">
                        <i class="fas fa-mobile-alt text-warning"></i>
                        <small class="d-block">E-Wallet</small>
                    </div>
                </div>
                <div class="col-3 text-center">
                    <div class="bg-white rounded p-2">
                        <i class="fas fa-university text-info"></i>
                        <small class="d-block">Virtual Account</small>
                    </div>
                </div>
                <div class="col-3 text-center">
                    <div class="bg-white rounded p-2">
                        <i class="fab fa-cc-visa text-primary"></i>
                        <small class="d-block">Kartu Kredit</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="row g-2 mb-4">
            <div class="col-4">
                <button onclick="checkPaymentStatus()" class="btn btn-outline-primary w-100" id="checkBtn">
                    <i class="fas fa-sync-alt me-2"></i>Cek Status
                </button>
            </div>
            <div class="col-4">
                <button onclick="simulatePaymentSuccess()" class="btn btn-success w-100" id="simulateBtn">
                    <i class="fas fa-check me-2"></i>Simulasi Berhasil
                </button>
            </div>
            <div class="col-4">
                <button onclick="window.location.href='wallet.php'" class="btn btn-secondary w-100">
                    Batal
                </button>
            </div>
        </div>
        

        
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<?php if ($transaction['payment_method'] === 'midtrans'): ?>
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-qzmNh-oU_DSOM2IB"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($transaction['payment_method'] === 'midtrans' && !$is_expired): ?>
    // Initialize Midtrans Snap
    const payButton = document.getElementById('pay-button');
    if (payButton) {
        payButton.addEventListener('click', function () {
            payButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memuat...';
            payButton.disabled = true;
            
            snap.pay('<?= $transaction['qr_code'] ?>', {
                onSuccess: function(result) {
                    console.log('Payment success:', result);
                    window.location.href = 'wallet.php?payment=success';
                },
                onPending: function(result) {
                    console.log('Payment pending:', result);
                    window.location.href = 'wallet.php?payment=pending';
                },
                onError: function(result) {
                    console.log('Payment error:', result);
                    alert('Pembayaran gagal. Silakan coba lagi.');
                    payButton.innerHTML = '<i class="fas fa-lock me-2"></i>Bayar Sekarang';
                    payButton.disabled = false;
                },
                onClose: function() {
                    console.log('Payment popup closed');
                    payButton.innerHTML = '<i class="fas fa-lock me-2"></i>Bayar Sekarang';
                    payButton.disabled = false;
                }
            });
        });
    }
    
    <?php endif; ?>
    
    <?php if (!$is_expired): ?>
    // Countdown timer - berlaku untuk semua metode pembayaran
    const expiredAt = <?= $expired_at * 1000 ?>;
    const countdownElement = document.getElementById('countdown');
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = expiredAt - now;
        
        if (distance < 0) {
            countdownElement.innerHTML = "Waktu habis";
            location.reload();
            return;
        }
        
        const hours = Math.floor(distance / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        if (hours > 0) {
            countdownElement.innerHTML = `Sisa waktu: ${hours}j ${minutes}m ${seconds}s`;
        } else {
            countdownElement.innerHTML = `Sisa waktu: ${minutes}m ${seconds}s`;
        }
    }
    
    if (countdownElement) {
        updateCountdown();
        const countdownInterval = setInterval(updateCountdown, 1000);
        
        // Auto check payment status every 10 seconds
        const autoCheckInterval = setInterval(checkPaymentStatus, 10000);
        
        // Clear intervals when page unloads
        window.addEventListener('beforeunload', function() {
            clearInterval(countdownInterval);
            clearInterval(autoCheckInterval);
        });
    }
    <?php endif; ?>
});

function checkPaymentStatus() {
    const checkBtn = document.getElementById('checkBtn');
    if (checkBtn) {
        const originalContent = checkBtn.innerHTML;
        checkBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengecek...';
        checkBtn.disabled = true;
        
        fetch('process/check_payment_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'trx_id=<?= urlencode($trx_id) ?>'
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'paid') {
                    window.location.href = 'wallet.php?payment=success';
                } else if (data.status === 'expired') {
                    window.location.reload();
                } else {
                    // Status masih pending
                    checkBtn.innerHTML = originalContent;
                    checkBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error checking payment status:', error);
                checkBtn.innerHTML = originalContent;
                checkBtn.disabled = false;
            });
    }
}

function simulatePaymentSuccess() {
    const simulateBtn = document.getElementById('simulateBtn');
    const originalContent = simulateBtn.innerHTML;
    simulateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
    simulateBtn.disabled = true;
    
    fetch('process/simulate_payment_success.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'trx_id=<?= urlencode($trx_id) ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Simulasi pembayaran berhasil! Saldo bertambah Rp ' + new Intl.NumberFormat('id-ID').format(data.amount));
            window.location.href = 'wallet.php?payment=success';
        } else {
            alert('Gagal mensimulasikan pembayaran: ' + data.message);
            simulateBtn.innerHTML = originalContent;
            simulateBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error simulating payment:', error);
        alert('Terjadi kesalahan saat mensimulasikan pembayaran');
        simulateBtn.innerHTML = originalContent;
        simulateBtn.disabled = false;
    });
}

function confirmManualPayment() {
    const confirmBtn = document.getElementById('confirmBtn');
    const senderName = document.getElementById('senderName').value;
    const paymentNote = document.getElementById('paymentNote').value;
    
    if (!senderName.trim()) {
        alert('Mohon masukkan nama pengirim');
        return;
    }
    
    const originalContent = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
    confirmBtn.disabled = true;
    
    fetch('process/confirm_manual_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `trx_id=<?= urlencode($trx_id) ?>&sender_name=${encodeURIComponent(senderName)}&note=${encodeURIComponent(paymentNote)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Konfirmasi pembayaran berhasil dikirim. Admin akan memverifikasi dalam 1x24 jam.');
            window.location.href = 'wallet.php?status=pending';
        } else {
            alert('Gagal mengirim konfirmasi: ' + data.message);
            confirmBtn.innerHTML = originalContent;
            confirmBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
        confirmBtn.innerHTML = originalContent;
        confirmBtn.disabled = false;
    });
}

function checkPaymentStatus() {
    const checkBtn = document.getElementById('checkBtn');
    const originalContent = checkBtn.innerHTML;
    
    checkBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengecek...';
    checkBtn.disabled = true;
    
    fetch('process/check_payment_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'trx_id=<?= urlencode($trx_id) ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.paid) {
                // Payment successful
                window.location.href = 'wallet.php?payment=success';
            } else {
                // Still pending
                checkBtn.innerHTML = originalContent;
                checkBtn.disabled = false;
            }
        } else {
            alert('Gagal mengecek status pembayaran');
            checkBtn.innerHTML = originalContent;
            checkBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        checkBtn.innerHTML = originalContent;
        checkBtn.disabled = false;
    });
}


</script>