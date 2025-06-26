<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in as merchant
if (isset($_SESSION['merchant_id'])) {
    header('Location: merchant_dashboard.php');
    exit();
}

$pageTitle = 'Login Merchant - RideMax';
include 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header -->
    <div class="bg-primary text-white p-4 mb-4">
        <div class="d-flex align-items-center mb-3">
            <a href="index.php" class="btn btn-light btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h4 class="mb-0">Login Merchant</h4>
        </div>
        <p class="mb-0 opacity-75">Masuk ke dashboard merchant RideMax</p>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-store fa-3x text-primary mb-3"></i>
                            <h5>Selamat Datang Kembali</h5>
                            <p class="text-muted">Login untuk mengakses dashboard merchant</p>
                        </div>

                        <form id="merchantLoginForm" method="POST" action="process/merchant_login_process.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                                <label class="form-check-label" for="remember_me">
                                    Ingat saya
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">Belum punya akun merchant? <a href="merchant_register.php" class="text-primary">Daftar di sini</a></p>
                            <a href="#" class="text-muted small">Lupa password?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('merchantLoginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    submitBtn.disabled = true;
    
    fetch('process/merchant_login_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect || 'merchant_dashboard.php';
        } else {
            alert(data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script>

<?php include 'includes/footer.php'; ?>