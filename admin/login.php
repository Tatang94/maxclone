<?php
/**
 * Admin Login Page
 * Dedicated login page for administrators
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if already logged in as admin
if (isLoggedIn() && isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Set page-specific variables
$hideNavigation = true;
$pageTitle = 'Login Admin - RideMax';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .admin-login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .admin-login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .admin-login-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 40px 30px 30px;
            text-align: center;
        }
        
        .admin-login-header i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .admin-login-body {
            padding: 40px 30px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating input {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px 15px;
            font-size: 16px;
        }
        
        .form-floating input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-floating label {
            font-weight: 500;
            color: #6c757d;
        }
        
        .btn-admin-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-admin-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
        }
        
        .back-to-app {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .back-to-app a {
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-to-app a:hover {
            color: #667eea;
        }
    </style>
</head>

<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <i class="fas fa-shield-alt"></i>
                <h2 class="mb-0">Admin Panel</h2>
                <p class="mb-0 opacity-75">RideMax Administration</p>
            </div>
            
            <div class="admin-login-body">
                <div id="alertContainer"></div>
                
                <form id="adminLoginForm">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>Email Administrator
                        </label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Ingat saya
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-admin-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        <span id="loginBtnText">Masuk sebagai Admin</span>
                        <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                    </button>
                </form>
                
                <div class="back-to-app">
                    <a href="../login.php">
                        <i class="fas fa-arrow-left me-2"></i>
                        Kembali ke halaman pengguna
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('adminLoginForm');
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginSpinner = document.getElementById('loginSpinner');
            const alertContainer = document.getElementById('alertContainer');
            
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                loginBtn.disabled = true;
                loginBtnText.textContent = 'Memproses...';
                loginSpinner.classList.remove('d-none');
                
                // Clear previous alerts
                alertContainer.innerHTML = '';
                
                // Get form data
                const formData = new FormData(loginForm);
                formData.append('admin_login', '1'); // Flag to indicate admin login
                
                // Send login request
                fetch('../process/login_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success - redirect to admin dashboard
                        showAlert('success', 'Login berhasil! Mengalihkan ke dashboard...');
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 1000);
                    } else {
                        // Error - show message
                        showAlert('danger', data.message || 'Login gagal. Silakan coba lagi.');
                        resetButton();
                    }
                })
                .catch(error => {
                    console.error('Login error:', error);
                    showAlert('danger', 'Terjadi kesalahan. Silakan coba lagi.');
                    resetButton();
                });
            });
            
            function resetButton() {
                loginBtn.disabled = false;
                loginBtnText.textContent = 'Masuk sebagai Admin';
                loginSpinner.classList.add('d-none');
            }
            
            function showAlert(type, message) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                alertContainer.innerHTML = alertHtml;
            }
        });
    </script>
</body>
</html>