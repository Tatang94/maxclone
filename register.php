<?php
session_start();
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$pageTitle = 'Daftar - RideMax';
$hideNavigation = true;
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center py-4">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="auth-card bg-white rounded-4 shadow p-4">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <div class="logo-container">
                            <i class="fas fa-bicycle text-primary fa-3x mb-2"></i>
                            <h3 class="text-primary fw-bold">RideMax</h3>
                            <p class="text-muted">Bergabung dengan revolusi transportasi</p>
                        </div>
                    </div>

                    <!-- Registration Form -->
                    <form id="registerForm" action="process/register_process.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Alamat Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor Telepon</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Kata Sandi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Kata sandi minimal 6 karakter</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="user_type" class="form-label">Jenis Akun</label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="">Pilih jenis akun</option>
                                <option value="user">Penumpang</option>
                                <option value="driver">Pengendara Sepeda</option>
                            </select>
                        </div>

                        <!-- Bicycle specific fields -->
                        <div id="driverFields" class="d-none">
                            <div class="mb-3">
                                <label for="license_number" class="form-label">Nomor KTP</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="license_number" name="license_number" placeholder="1234567890123456">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="vehicle_make" class="form-label">Merk Sepeda</label>
                                        <input type="text" class="form-control" id="vehicle_make" name="vehicle_make" placeholder="Polygon">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="vehicle_model" class="form-label">Tipe Sepeda</label>
                                        <input type="text" class="form-control" id="vehicle_model" name="vehicle_model" placeholder="Sepeda Gunung">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="vehicle_plate" class="form-label">Nomor Registrasi</label>
                                <input type="text" class="form-control" id="vehicle_plate" name="vehicle_plate" placeholder="REG-001">
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                Saya setuju dengan <a href="#" class="text-primary">Syarat Layanan</a> dan <a href="#" class="text-primary">Kebijakan Privasi</a>
                            </label>
                        </div>

                        <div id="registerAlert" class="alert d-none" role="alert"></div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3" id="registerBtn">
                            <span class="btn-text">Buat Akun</span>
                            <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </form>

                    <!-- Login Link -->
                    <div class="text-center">
                        <p class="mb-0">Sudah punya akun? <a href="login.php" class="text-primary text-decoration-none fw-semibold">Masuk</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide driver fields based on user type
document.getElementById('user_type').addEventListener('change', function() {
    const driverFields = document.getElementById('driverFields');
    const driverInputs = driverFields.querySelectorAll('input');
    
    if (this.value === 'driver') {
        driverFields.classList.remove('d-none');
        driverInputs.forEach(input => input.required = true);
    } else {
        driverFields.classList.add('d-none');
        driverInputs.forEach(input => {
            input.required = false;
            input.value = '';
        });
    }
});

// Form submission
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Password confirmation check
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        showAlert('Passwords do not match', 'danger');
        return;
    }
    
    const btn = document.getElementById('registerBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnSpinner = btn.querySelector('.btn-spinner');
    
    // Show loading state
    btn.disabled = true;
    btnText.textContent = 'Creating Account...';
    btnSpinner.classList.remove('d-none');
    
    const formData = new FormData(this);
    
    fetch('process/register_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Account created successfully! Redirecting to login...', 'success');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        } else {
            showAlert(data.message || 'Registration failed. Please try again.', 'danger');
        }
    })
    .catch(error => {
        showAlert('Network error. Please try again.', 'danger');
    })
    .finally(() => {
        btn.disabled = false;
        btnText.textContent = 'Create Account';
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

function showAlert(message, type) {
    const alert = document.getElementById('registerAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.classList.remove('d-none');
    
    // Auto-hide success messages
    if (type === 'success') {
        setTimeout(() => {
            alert.classList.add('d-none');
        }, 5000);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
