<?php
session_start();
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$pageTitle = 'Masuk - RideMax';
$hideNavigation = true;
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="auth-card bg-white rounded-4 shadow p-4">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <div class="logo-container">
                            <i class="fas fa-bicycle text-primary fa-3x mb-2"></i>
                            <h3 class="text-primary fw-bold">RideMax</h3>
                            <p class="text-muted">Perjalanan Anda, Pilihan Anda</p>
                        </div>
                    </div>

                    <!-- Login Form -->
                    <form id="loginForm" action="process/login_process.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Alamat Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Kata Sandi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>

                        <div id="loginAlert" class="alert d-none" role="alert"></div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3" id="loginBtn">
                            <span class="btn-text">Masuk</span>
                            <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </form>

                    <!-- Quick Login Options -->
                    <div class="quick-login mb-3">
                        <div class="divider mb-3">
                            <span class="divider-text text-muted">Login Cepat</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="quickLogin('user')">
                                    <i class="fas fa-user me-1"></i> Penumpang
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-success btn-sm w-100" onclick="quickLogin('driver')">
                                    <i class="fas fa-car me-1"></i> Driver
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Register Link -->
                    <div class="text-center">
                        <p class="mb-0">Belum punya akun? <a href="register.php" class="text-primary text-decoration-none fw-semibold">Daftar</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('loginBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnSpinner = btn.querySelector('.btn-spinner');
    const alert = document.getElementById('loginAlert');
    
    // Show loading state
    btn.disabled = true;
    btnText.textContent = 'Signing In...';
    btnSpinner.classList.remove('d-none');
    alert.classList.add('d-none');
    
    const formData = new FormData(this);
    
    fetch('process/login_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert.className = 'alert alert-success';
            alert.textContent = 'Login successful! Redirecting...';
            alert.classList.remove('d-none');
            
            setTimeout(() => {
                window.location.href = data.redirect || 'index.php';
            }, 1000);
        } else {
            alert.className = 'alert alert-danger';
            alert.textContent = data.message || 'Login failed. Please try again.';
            alert.classList.remove('d-none');
        }
    })
    .catch(error => {
        alert.className = 'alert alert-danger';
        alert.textContent = 'Network error. Please try again.';
        alert.classList.remove('d-none');
    })
    .finally(() => {
        btn.disabled = false;
        btnText.textContent = 'Sign In';
        btnSpinner.classList.add('d-none');
    });
});

// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Quick login function for demo purposes
function quickLogin(type) {
    const credentials = {
        user: { email: 'user@demo.com', password: 'password' },
        driver: { email: 'driver@demo.com', password: 'password' },
        admin: { email: 'admin@demo.com', password: 'password' }
    };
    
    if (credentials[type]) {
        document.getElementById('email').value = credentials[type].email;
        document.getElementById('password').value = credentials[type].password;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
