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

    <!-- Menu Makanan Section -->
    <div class="food-menu-section p-3 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">🍽️ Menu Makanan</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="refreshFoodMenu()">
                    <i class="fas fa-refresh"></i>
                </button>
            </div>
        </div>
        
        <div class="food-categories mb-3">
            <div class="d-flex gap-2 overflow-auto pb-2">
                <button class="btn btn-sm btn-primary category-btn active" data-category="">Semua</button>
                <button class="btn btn-sm btn-outline-primary category-btn" data-category="makanan">Makanan</button>
                <button class="btn btn-sm btn-outline-primary category-btn" data-category="minuman">Minuman</button>
                <button class="btn btn-sm btn-outline-primary category-btn" data-category="snack">Snack</button>
            </div>
        </div>
        
        <div id="foodMenuContainer" class="row g-3">
            <div class="col-12 text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memuat menu makanan...</p>
            </div>
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
// Load recent orders and food menu on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRecentOrders();
    loadFoodMenu();
    
    // Auto-refresh every 30 seconds
    setInterval(loadRecentOrders, 30000);
    
    // Setup category filter buttons
    setupCategoryFilters();
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
                        <i class="fas fa-history fa-2x mb-2 opacity-50"></i>
                        <p>Belum ada pesanan terbaru</p>
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

// Setup category filter buttons
function setupCategoryFilters() {
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('.category-btn').forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary', 'active');
            
            // Load menu with filter
            const category = this.dataset.category;
            loadFoodMenu(category);
        });
    });
}

// Load food menu
function loadFoodMenu(category = '') {
    const container = document.getElementById('foodMenuContainer');
    
    // Show loading state
    container.innerHTML = `
        <div class="col-12 text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Memuat menu makanan...</p>
        </div>
    `;
    
    const url = category ? `process/get_food_menu.php?category=${encodeURIComponent(category)}` : 'process/get_food_menu.php';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                container.innerHTML = data.data.map(item => `
                    <div class="col-6">
                        <div class="card food-item-card h-100">
                            <div class="position-relative">
                                <img src="${item.image_url}" class="card-img-top" alt="${item.name}" style="height: 120px; object-fit: cover;">
                                <span class="badge bg-primary position-absolute top-0 end-0 m-2">${item.category}</span>
                            </div>
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1 text-truncate">${item.name}</h6>
                                <p class="card-text small text-muted mb-1 text-truncate">${item.description}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-primary fw-bold">Rp ${parseInt(item.price).toLocaleString('id-ID')}</span>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> ${item.preparation_time}m
                                    </small>
                                </div>
                                <small class="text-muted d-block text-truncate">
                                    <i class="fas fa-store"></i> ${item.merchant_name}
                                </small>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="col-12 text-center py-4">
                        <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                        <h6>Belum Ada Menu</h6>
                        <p class="text-muted">Menu makanan akan muncul di sini ketika merchant menambahkan</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading food menu:', error);
            container.innerHTML = `
                <div class="col-12 text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                    <p class="text-muted">Gagal memuat menu makanan</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="loadFoodMenu()">
                        <i class="fas fa-refresh"></i> Coba Lagi
                    </button>
                </div>
            `;
        });
}

// Refresh food menu
function refreshFoodMenu() {
    const activeCategory = document.querySelector('.category-btn.active');
    const category = activeCategory ? activeCategory.dataset.category : '';
    loadFoodMenu(category);
}
</script>

<?php include 'includes/footer.php'; ?>
