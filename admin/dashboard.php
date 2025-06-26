<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Auto-login admin for now (temporary solution)
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@ridemax.com' AND user_type = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['user_name'] = $admin['name'];
    $_SESSION['user_email'] = $admin['email'];
    $_SESSION['user_type'] = $admin['user_type'];
    $_SESSION['is_driver'] = false;
    $_SESSION['login_time'] = time();
}

$pageTitle = 'Dashboard Admin - RideMax';
include '../includes/header.php';

// Get dashboard statistics
try {
    // Total counts
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE user_type = 'user'");
    $totalUsers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_drivers FROM users WHERE is_driver = true");
    $totalDrivers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $totalOrders = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(actual_fare) as total_revenue FROM orders WHERE status = 'completed'");
    $totalRevenue = $stmt->fetchColumn() ?: 0;
    
    // Today's statistics
    $stmt = $pdo->query("SELECT COUNT(*) as today_orders FROM orders WHERE DATE(created_at) = CURRENT_DATE");
    $todayOrders = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(actual_fare) as today_revenue FROM orders WHERE DATE(created_at) = CURRENT_DATE AND status = 'completed'");
    $todayRevenue = $stmt->fetchColumn() ?: 0;
    
    // Active drivers
    $stmt = $pdo->query("SELECT COUNT(*) as active_drivers FROM drivers WHERE is_online = true");
    $activeDrivers = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $totalUsers = $totalDrivers = $totalOrders = $totalRevenue = 0;
    $todayOrders = $todayRevenue = $activeDrivers = 0;
}
?>

<div class="container-fluid p-0">
    <!-- Admin Header -->
    <div class="admin-header bg-dark text-white p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">Dashboard Admin</h4>
<p class="mb-0 opacity-75">Selamat datang kembali, Administrator</p>
            </div>
            <div class="admin-controls">
                <button class="btn btn-outline-light btn-sm me-2" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Perbarui
                </button>
                <div class="dropdown d-inline">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cog"></i> Pengaturan
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Pengaturan Sistem</a></li>
                        <li><a class="dropdown-item" href="users.php"><i class="fas fa-users me-2"></i>Kelola Pengguna</a></li>
                        <li><a class="dropdown-item" href="orders.php"><i class="fas fa-receipt me-2"></i>Kelola Pesanan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats p-3">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat-card bg-primary text-white rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1"><?php echo number_format($totalUsers); ?></h3>
                            <small class="opacity-75">Total Pengguna</small>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="stat-card bg-success text-white rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1"><?php echo number_format($totalDrivers); ?></h3>
                            <small class="opacity-75">Total Driver</small>
                        </div>
                        <i class="fas fa-bicycle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="stat-card bg-info text-white rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1"><?php echo number_format($totalOrders); ?></h3>
                            <small class="opacity-75">Total Pesanan</small>
                        </div>
                        <i class="fas fa-receipt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="stat-card bg-warning text-white rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">Rp <?php echo number_format($totalRevenue); ?></h3>
                            <small class="opacity-75">Total Pendapatan</small>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Stats -->
    <div class="today-stats p-3">
        <h6 class="mb-3">Performa Hari Ini</h6>
        <div class="row g-3">
            <div class="col-4">
                <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                    <h4 class="text-primary mb-1"><?php echo number_format($todayOrders); ?></h4>
                    <small class="text-muted">Pesanan Hari Ini</small>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                    <h4 class="text-success mb-1">Rp <?php echo number_format($todayRevenue); ?></h4>
                    <small class="text-muted">Pendapatan Hari Ini</small>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                    <h4 class="text-warning mb-1"><?php echo number_format($activeDrivers); ?></h4>
                    <small class="text-muted">Active Drivers</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions p-3">
        <h6 class="mb-3">Quick Actions</h6>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <a href="users.php" class="action-card bg-white rounded-3 shadow-sm p-3 text-decoration-none text-dark d-block text-center">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h6 class="mb-0">Manage Users</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="orders.php" class="action-card bg-white rounded-3 shadow-sm p-3 text-decoration-none text-dark d-block text-center">
                    <i class="fas fa-receipt fa-2x text-success mb-2"></i>
                    <h6 class="mb-0">Manage Orders</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <button class="action-card bg-white rounded-3 shadow-sm p-3 border-0 text-dark d-block text-center w-100" onclick="viewRealTimeOrders()">
                    <i class="fas fa-eye fa-2x text-info mb-2"></i>
                    <h6 class="mb-0">Live Orders</h6>
                </button>
            </div>
            <div class="col-6 col-md-3">
                <a href="settings.php" class="action-card bg-white rounded-3 shadow-sm p-3 text-decoration-none text-dark d-block text-center">
                    <i class="fas fa-cog fa-2x text-warning mb-2"></i>
                    <h6 class="mb-0">Settings</h6>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="manual_payments.php" class="action-card bg-white rounded-3 shadow-sm p-3 text-decoration-none text-dark d-block text-center">
                    <i class="fas fa-credit-card fa-2x text-success mb-2"></i>
                    <h6 class="mb-0">Manual Payments</h6>
                </a>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section p-3">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="chart-card bg-white rounded-3 shadow-sm p-3">
                    <h6 class="mb-3">Orders This Week</h6>
                    <canvas id="ordersChart" height="200"></canvas>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="chart-card bg-white rounded-3 shadow-sm p-3">
                    <h6 class="mb-3">Revenue This Week</h6>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="recent-activities p-3">
        <h6 class="mb-3">Recent Activities</h6>
        <div class="activity-list bg-white rounded-3 shadow-sm">
            <div id="activitiesContainer">
                <!-- Activities will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Real-time Orders Modal -->
<div class="modal fade" id="liveOrdersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Live Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="liveOrdersContainer">
                    <!-- Live orders will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let ordersChart, revenueChart;

document.addEventListener('DOMContentLoaded', function() {
    loadRecentActivities();
    initializeCharts();
    
    // Auto-refresh dashboard every 30 seconds
    setInterval(() => {
        loadRecentActivities();
        updateCharts();
    }, 30000);
});

function loadRecentActivities() {
    fetch('../process/admin_process.php?action=get_recent_activities')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('activitiesContainer');
            
            if (data.success && data.activities.length > 0) {
                container.innerHTML = data.activities.map(activity => `
                    <div class="activity-item p-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="activity-icon bg-${getActivityColor(activity.type)} text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-${getActivityIcon(activity.type)}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${activity.title}</h6>
                                <p class="mb-0 text-muted small">${activity.description}</p>
                                <small class="text-muted">${formatTimeAgo(activity.created_at)}</small>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history fa-2x mb-2 opacity-50"></i>
                        <p>No recent activities</p>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading activities:', error));
}

function initializeCharts() {
    // Orders Chart
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    ordersChart = new Chart(ordersCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Orders',
                data: [0, 0, 0, 0, 0, 0, 0], // Will be populated by updateCharts()
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Revenue (Rp)',
                data: [0, 0, 0, 0, 0, 0, 0], // Will be populated by updateCharts()
                backgroundColor: '#28a745',
                borderColor: '#28a745',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    updateCharts();
}

function updateCharts() {
    fetch('../process/admin_process.php?action=get_chart_data')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update orders chart
                ordersChart.data.datasets[0].data = data.orders_data;
                ordersChart.update();
                
                // Update revenue chart
                revenueChart.data.datasets[0].data = data.revenue_data;
                revenueChart.update();
            }
        })
        .catch(error => console.error('Error updating charts:', error));
}

function viewRealTimeOrders() {
    fetch('../process/admin_process.php?action=get_live_orders')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('liveOrdersContainer');
            
            if (data.success && data.orders.length > 0) {
                container.innerHTML = data.orders.map(order => `
                    <div class="live-order-item bg-light rounded p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Order #${order.id}</h6>
                                <p class="mb-1 small">${order.user_name} → ${order.pickup_location}</p>
                                <span class="badge bg-${getStatusColor(order.status)}">${order.status.toUpperCase()}</span>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">Rp ${parseInt(order.total_price).toLocaleString()}</div>
                                <small class="text-muted">${formatTimeAgo(order.created_at)}</small>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-bicycle fa-2x mb-2 opacity-50"></i>
                        <p>No active orders</p>
                    </div>
                `;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('liveOrdersModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading live orders:', error);
            alert('Failed to load live orders.');
        });
}

function refreshDashboard() {
    location.reload();
}

function getActivityColor(type) {
    const colors = {
        'order': 'primary',
        'user': 'success',
        'driver': 'info',
        'system': 'warning'
    };
    return colors[type] || 'secondary';
}

function getActivityIcon(type) {
    const icons = {
        'order': 'receipt',
        'user': 'user',
        'driver': 'car',
        'system': 'cog'
    };
    return icons[type] || 'info';
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

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));
    
    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h ago`;
    
    const diffInDays = Math.floor(diffInHours / 24);
    return `${diffInDays}d ago`;
}
</script>

<?php include '../includes/footer.php'; ?>
