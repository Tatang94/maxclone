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

$user_id = $_SESSION['user_id'];
$user = getCurrentUser();
$wallet = new WalletManager($pdo);
$current_balance = $wallet->getBalance($user_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Validasi nama
        if (empty($name)) {
            $errors[] = 'Nama tidak boleh kosong';
        }
        
        // Validasi password jika ingin diubah
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = 'Password saat ini harus diisi';
            } elseif (!password_verify($current_password, $user['password'])) {
                $errors[] = 'Password saat ini salah';
            } elseif (strlen($new_password) < 6) {
                $errors[] = 'Password baru minimal 6 karakter';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'Konfirmasi password tidak cocok';
            }
        }
        
        if (empty($errors)) {
            try {
                $updateData = ['name' => $name];
                if (!empty($phone)) {
                    $updateData['phone'] = $phone;
                }
                if (!empty($new_password)) {
                    $updateData['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                }
                
                updateData('users', $updateData, 'id = :id', ['id' => $user_id]);
                
                $_SESSION['user_name'] = $name;
                $success_message = 'Profil berhasil diperbarui';
                
                // Refresh user data
                $user = getCurrentUser();
                
            } catch (Exception $e) {
                $errors[] = 'Terjadi kesalahan saat memperbarui profil';
            }
        }
    }
}

$page_title = "Profil Saya";
require_once 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header -->
    <div class="bg-primary text-white p-4 mb-4">
        <div class="d-flex align-items-center">
            <button onclick="history.back()" class="btn btn-link text-white p-0 me-3">
                <i class="fas fa-arrow-left fa-lg"></i>
            </button>
            <h5 class="mb-0">Profil Saya</h5>
        </div>
    </div>

    <div class="px-3">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="user-avatar bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h5 class="fw-bold"><?= htmlspecialchars($user['name']) ?></h5>
                <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <div class="d-flex justify-content-center align-items-center">
                    <span class="badge bg-success me-2">
                        <i class="fas fa-wallet me-1"></i>
                        Rp <?= number_format($current_balance, 0, ',', '.') ?>
                    </span>
                    <?php if ($user['is_driver']): ?>
                    <span class="badge bg-info">
                        <i class="fas fa-car me-1"></i>
                        Driver
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Informasi Pribadi</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        <div class="form-text">Email tidak dapat diubah</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Nomor Telepon</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Contoh: 08123456789">
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Ubah Password</h6>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                        <div class="form-text">Kosongkan jika tidak ingin mengubah password</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-6">
                <a href="wallet.php" class="btn btn-outline-primary w-100 py-3">
                    <i class="fas fa-wallet mb-2 d-block"></i>
                    Dompet Digital
                </a>
            </div>
            <div class="col-6">
                <a href="history.php" class="btn btn-outline-secondary w-100 py-3">
                    <i class="fas fa-history mb-2 d-block"></i>
                    Riwayat
                </a>
            </div>
        </div>

        <!-- Account Info -->
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Informasi Akun</h6>
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Bergabung sejak</small>
                        <div class="fw-bold"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Terakhir login</small>
                        <div class="fw-bold">
                            <?= $user['last_login'] ? date('d M Y H:i', strtotime($user['last_login'])) : 'Belum ada' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePasswords() {
        if (newPassword.value && confirmPassword.value) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Password tidak cocok');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
    }
    
    newPassword.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
});
</script>