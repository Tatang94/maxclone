<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Auto-login demo user for testing
if (!isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'demo@ridemax.com'");
    $stmt->execute();
    $demoUser = $stmt->fetch();
    
    if ($demoUser) {
        $_SESSION['user_id'] = $demoUser['id'];
        $_SESSION['user_name'] = $demoUser['name'];
        $_SESSION['user_email'] = $demoUser['email'];
        $_SESSION['user_type'] = $demoUser['user_type'];
        $_SESSION['is_driver'] = $demoUser['is_driver'];
        $_SESSION['login_time'] = time();
    } else {
        header('Location: login.php');
        exit();
    }
}

$user = getCurrentUser();

// Handle case when user data is not available
if (!$user || !is_array($user)) {
    // Logout and redirect to login if user data is corrupted
    session_destroy();
    header('Location: login.php');
    exit();
}

$pageTitle = 'RideMax - Beranda';
include 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Hero Section -->
    <div class="hero-section bg-primary text-white p-4">
        <div class="row align-items-center">
            <div class="col-8">
                <h4 class="mb-1">Halo, <?php echo htmlspecialchars($user['name']); ?>!</h4>
                <p class="mb-0 opacity-75">Mau kemana hari ini?</p>
            </div>
            <div class="col-4 text-end">
                <div class="profile-avatar bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions p-3">
        <div class="row g-3">
            <div class="col-6">
                <div class="action-card bg-white shadow-sm rounded-3 p-3 text-center h-100" onclick="location.href='order.php'">
                    <div class="action-icon bg-primary text-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-car fa-lg"></i>
                    </div>
                    <h6 class="mb-1">Pesan Perjalanan</h6>
                    <small class="text-muted">Cepat & Aman</small>
                </div>
            </div>
            <div class="col-6">
                <div class="action-card bg-white shadow-sm rounded-3 p-3 text-center h-100" onclick="location.href='history.php'">
                    <div class="action-icon bg-success text-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-history fa-lg"></i>
                    </div>
                    <h6 class="mb-1">Riwayat</h6>
                    <small class="text-muted">Perjalanan Sebelumnya</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="recent-orders p-3">
        <h5 class="mb-3">Pesanan Terbaru</h5>
        <div id="recentOrdersContainer">
            <!-- Recent orders will be loaded here via AJAX -->
        </div>
    </div>

    <!-- Driver Mode Toggle (if user is also a driver) -->
    <?php if ($user['is_driver'] == 1): ?>
    <div class="driver-mode p-3 bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">Mode Driver</h6>
                <small class="text-muted">Mulai menerima perjalanan</small>
            </div>
            <a href="driver.php" class="btn btn-outline-primary btn-sm">Online</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Load recent orders on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRecentOrders();
    
    // Auto-refresh every 30 seconds
    setInterval(loadRecentOrders, 30000);
});

function loadRecentOrders() {
    fetch('process/fetch_order_status.php?recent=1')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('recentOrdersContainer');
            if (data.success && data.orders.length > 0) {
                container.innerHTML = data.orders.map(order => `
                    <div class="order-item bg-white rounded-3 shadow-sm p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${order.pickup_location}</h6>
                                <p class="mb-1 text-muted small">to ${order.destination}</p>
                                <small class="text-muted">${order.created_at}</small>
                            </div>
                            <span class="badge bg-${getStatusColor(order.status)}">${order.status}</span>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-car fa-2x mb-2 opacity-50"></i>
                        <p>Belum ada pesanan terbaru</p>
                        <a href="order.php" class="btn btn-primary btn-sm">Pesan Perjalanan Pertama</a>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading orders:', error));
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'in_progress': 'primary',
        'completed': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}
</script>

<?php include 'includes/footer.php'; ?>
