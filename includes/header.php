<?php
/**
 * Header Template for RideMax Super App
 * Includes navigation, meta tags, and CSS/JS resources
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default values
$pageTitle = $pageTitle ?? 'RideMax - Perjalanan Anda, Pilihan Anda';
$hideNavigation = $hideNavigation ?? false;
$currentUser = isLoggedIn() ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="RideMax - Layanan transportasi online terpercaya. Pesan perjalanan instan dengan aplikasi mobile yang dioptimalkan.">
    <meta name="keywords" content="transportasi online, taksi, ojek, aplikasi mobile, pemesanan">
    <meta name="author" content="RideMax">
    
    <!-- Mobile Optimization -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#007bff">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>assets/css/style.css" rel="stylesheet">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="<?php echo $hideNavigation ? 'auth-page' : ''; ?>">
    
    <?php if (!$hideNavigation && isLoggedIn()): ?>
    <!-- Mobile Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top mobile-nav">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo isAdmin() ? 'admin/dashboard.php' : 'index.php'; ?>">
                <i class="fas fa-bicycle me-2"></i>RideMax
            </a>
            
            <!-- Mobile menu toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                <div class="navbar-toggler-icon-custom">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>
            
            <!-- Desktop navigation -->
            <div class="collapse navbar-collapse d-none d-lg-block">
                <ul class="navbar-nav me-auto">
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/users.php">
                                <i class="fas fa-users me-1"></i>Pengguna
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/orders.php">
                                <i class="fas fa-receipt me-1"></i>Pesanan
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home me-1"></i>Beranda
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="order.php">
                                <i class="fas fa-bicycle me-1"></i>Pesan Perjalanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="wallet.php">
                                <i class="fas fa-wallet me-1"></i>Dompet
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="history.php">
                                <i class="fas fa-history me-1"></i>Riwayat
                            </a>
                        </li>
                        <?php if (isDriver()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="driver.php">
                                <i class="fas fa-car-side me-1"></i>Driver
                            </a>
                        </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <!-- User menu -->
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar bg-white text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px;">
                                <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                            </div>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="wallet.php"><i class="fas fa-wallet me-2"></i>Dompet</a></li>
                            <li><a class="dropdown-item" href="help.php"><i class="fas fa-headset me-2"></i>Bantuan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-success" href="merchant.php"><i class="fas fa-store me-2"></i>Merchant Panel</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Sidebar Menu -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header bg-primary text-white">
            <div class="d-flex align-items-center">
                <div class="user-avatar bg-white text-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                </div>
                <div>
                    <h6 class="mb-0"><?php echo htmlspecialchars($currentUser['name']); ?></h6>
                    <small class="opacity-75"><?php echo htmlspecialchars($currentUser['email']); ?></small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush">
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-3"></i>Dashboard
                    </a>
                    <a href="admin/users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-3"></i>Kelola Pengguna
                    </a>
                    <a href="admin/orders.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-receipt me-3"></i>Kelola Pesanan
                    </a>
                    <a href="admin/settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-3"></i>Pengaturan
                    </a>
                <?php else: ?>
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-3"></i>Beranda
                    </a>
                    <a href="order.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-bicycle me-3"></i>Pesan Perjalanan
                    </a>
                    <a href="wallet.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-wallet me-3"></i>Dompet Digital
                    </a>
                    <a href="history.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-history me-3"></i>Riwayat Pesanan
                    </a>
                    <?php if (isDriver()): ?>
                    <a href="driver.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-car-side me-3"></i>Dashboard Driver
                    </a>
                    <?php endif; ?>
                    <div class="border-top my-2"></div>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-3"></i>Profil Saya
                    </a>
                    <a href="help.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-headset me-3"></i>Bantuan
                    </a>
                    <div class="border-top my-2"></div>
                    <a href="merchant.php" class="list-group-item list-group-item-action text-success">
                        <i class="fas fa-store me-3"></i>Merchant Panel
                    </a>
                <?php endif; ?>
                <div class="border-top my-2"></div>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt me-3"></i>Keluar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main content wrapper -->
    <main class="main-content">
    <?php endif; ?>
    
    <?php if ($hideNavigation): ?>
    <!-- Auth page wrapper -->
    <main class="auth-main">
    <?php endif; ?>
