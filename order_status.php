<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header('Location: index.php');
    exit();
}

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit();
}

$pageTitle = 'Status Pesanan - RideMax';
include 'includes/header.php';
?>

<div class="container-fluid p-0" style="max-width: 480px; margin: 0 auto;">
    <!-- Header -->
    <div class="page-header bg-primary text-white p-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-white p-0 me-3" onclick="window.location.href='index.php'">
                <i class="fas fa-arrow-left fa-lg"></i>
            </button>
            <h5 class="mb-0">Status Pesanan</h5>
        </div>
    </div>

    <div class="order-status-container p-3">
        <!-- Order Status Card -->
        <div class="status-card bg-white rounded-3 shadow-sm mb-3 p-4">
            <div class="status-indicator text-center mb-4">
                <div class="status-icon-container">
                    <div class="status-icon searching">
                        <i class="fas fa-search fa-2x text-warning"></i>
                    </div>
                    <div class="status-animation">
                        <div class="pulse-ring"></div>
                        <div class="pulse-ring delay-1"></div>
                        <div class="pulse-ring delay-2"></div>
                    </div>
                </div>
                <h5 class="mt-3 mb-2" id="statusTitle">Mencari Driver Terdekat</h5>
                <p class="text-muted mb-0" id="statusMessage">Mohon tunggu, kami sedang mencarikan driver terbaik untuk Anda...</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="progress-steps mb-4">
                <div class="step-item active">
                    <div class="step-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="step-text">
                        <div class="step-title">Pesanan Dibuat</div>
                        <div class="step-time"><?= date('H:i', strtotime($order['created_at'])) ?></div>
                    </div>
                </div>
                
                <div class="step-item current">
                    <div class="step-icon">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                    </div>
                    <div class="step-text">
                        <div class="step-title">Mencari Driver</div>
                        <div class="step-time">Sedang proses...</div>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-icon">
                        <i class="fas fa-bicycle"></i>
                    </div>
                    <div class="step-text">
                        <div class="step-title">Pengendara Ditemukan</div>
                        <div class="step-time">-</div>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="step-text">
                        <div class="step-title">Perjalanan</div>
                        <div class="step-time">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trip Details -->
        <div class="trip-details bg-white rounded-3 shadow-sm mb-3 p-4">
            <h6 class="mb-3 fw-bold">Detail Perjalanan</h6>
            
            <div class="trip-route mb-3">
                <div class="d-flex mb-2">
                    <div class="location-dot me-3 mt-1" style="width: 12px; height: 12px; background: #dc3545; border-radius: 50%;"></div>
                    <div class="flex-grow-1">
                        <small class="text-muted">Penjemputan</small>
                        <div class="fw-medium"><?= htmlspecialchars($order['pickup_address']) ?></div>
                    </div>
                </div>
                
                <div class="d-flex">
                    <div class="location-dot me-3 mt-1" style="width: 12px; height: 12px; background: #28a745; border-radius: 50%;"></div>
                    <div class="flex-grow-1">
                        <small class="text-muted">Tujuan</small>
                        <div class="fw-medium"><?= htmlspecialchars($order['destination_address']) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="trip-info row">
                <div class="col-6">
                    <small class="text-muted">Kendaraan</small>
                    <div class="fw-medium"><?= ucfirst($order['vehicle_type']) ?></div>
                </div>
                <div class="col-6">
                    <small class="text-muted">Estimasi Biaya</small>
                    <div class="fw-medium text-warning">Rp <?= number_format($order['estimated_fare'], 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <!-- Driver Search Info -->
        <div class="search-info bg-light rounded-3 p-3 mb-3">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-info-circle text-primary"></i>
                </div>
                <div class="flex-grow-1">
                    <small class="text-muted">
                        Kami sedang mencarikan driver terdekat di area Anda. 
                        Rata-rata waktu tunggu <strong>2-5 menit</strong>.
                    </small>
                </div>
            </div>
        </div>

        <!-- Cancel Button -->
        <div class="cancel-section">
            <button type="button" class="btn btn-outline-danger w-100 py-2" id="cancelOrderBtn">
                <i class="fas fa-times me-2"></i>Batalkan Pesanan
            </button>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title">Batalkan Pesanan?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Apakah Anda yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">Ya, Batalkan</button>
            </div>
        </div>
    </div>
</div>

<style>
.status-icon-container {
    position: relative;
    display: inline-block;
}

.status-animation {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.pulse-ring {
    width: 80px;
    height: 80px;
    border: 2px solid #FFC107;
    border-radius: 50%;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation: pulse 2s ease-out infinite;
    opacity: 0;
}

.pulse-ring.delay-1 {
    animation-delay: 0.5s;
}

.pulse-ring.delay-2 {
    animation-delay: 1s;
}

@keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(2);
        opacity: 0;
    }
}

.progress-steps {
    position: relative;
}

.step-item {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    position: relative;
}

.step-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 20px;
    top: 40px;
    width: 2px;
    height: 20px;
    background: #dee2e6;
}

.step-item.active::after {
    background: #28a745;
}

.step-item.current::after {
    background: linear-gradient(to bottom, #28a745 50%, #dee2e6 50%);
}

.step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    margin-right: 15px;
    z-index: 1;
}

.step-item.active .step-icon {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.step-item.current .step-icon {
    background: #007bff;
    border-color: #007bff;
    color: white;
}

.step-text {
    flex-grow: 1;
}

.step-title {
    font-weight: 500;
    margin-bottom: 2px;
}

.step-time {
    font-size: 12px;
    color: #6c757d;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderId = <?= $order_id ?>;
    let searchInterval;
    
    // Start driver search simulation
    startDriverSearch();
    
    // Cancel order functionality
    document.getElementById('cancelOrderBtn').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('cancelConfirmModal'));
        modal.show();
    });
    
    document.getElementById('confirmCancelBtn').addEventListener('click', function() {
        cancelOrder();
    });
    
    function startDriverSearch() {
        // Simulate driver search process
        let searchTime = 0;
        const maxSearchTime = 120; // 2 minutes max search
        
        searchInterval = setInterval(function() {
            searchTime += 5;
            
            // Update search message
            if (searchTime < 30) {
                document.getElementById('statusMessage').textContent = 'Mencari driver di area Anda...';
            } else if (searchTime < 60) {
                document.getElementById('statusMessage').textContent = 'Memperluas area pencarian...';
            } else if (searchTime < 90) {
                document.getElementById('statusMessage').textContent = 'Menghubungi driver terdekat...';
            } else {
                document.getElementById('statusMessage').textContent = 'Driver sedang mempertimbangkan pesanan Anda...';
            }
            
            // Check for real driver acceptance (you can implement real-time checking here)
            checkDriverStatus();
            
            if (searchTime >= maxSearchTime) {
                // Simulate finding a driver after 2 minutes
                driverFound();
                clearInterval(searchInterval);
            }
        }, 5000);
    }
    
    function checkDriverStatus() {
        // Check order status from server
        fetch(`process/order_status_check.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'accepted') {
                    driverFound(data.driver);
                    clearInterval(searchInterval);
                } else if (data.status === 'cancelled') {
                    orderCancelled();
                    clearInterval(searchInterval);
                }
            })
            .catch(error => {
                console.error('Error checking status:', error);
            });
    }
    
    function driverFound(driver = null) {
        // Update status to driver found
        document.getElementById('statusTitle').textContent = 'Driver Ditemukan!';
        document.getElementById('statusMessage').textContent = 'Driver sedang menuju lokasi penjemputan Anda';
        
        // Update status icon
        document.querySelector('.status-icon i').className = 'fas fa-car fa-2x text-success';
        document.querySelector('.pulse-ring').style.borderColor = '#28a745';
        
        // Update progress steps
        document.querySelector('.step-item.current .step-icon').innerHTML = '<i class="fas fa-check"></i>';
        document.querySelector('.step-item.current').classList.remove('current');
        document.querySelector('.step-item.current').classList.add('active');
        
        // Activate next step
        const nextStep = document.querySelectorAll('.step-item')[2];
        nextStep.classList.add('current');
        nextStep.querySelector('.step-time').textContent = 'Driver menuju...';
        
        // Show driver info if available
        if (driver) {
            showDriverInfo(driver);
        }
        
        // Redirect to tracking page after 3 seconds
        setTimeout(() => {
            window.location.href = `order_tracking.php?order_id=${orderId}`;
        }, 3000);
    }
    
    function showDriverInfo(driver) {
        const driverInfo = `
            <div class="driver-info bg-white rounded-3 shadow-sm p-3 mt-3">
                <div class="d-flex align-items-center">
                    <div class="driver-avatar me-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; font-size: 20px;">
                            ${driver.name.charAt(0)}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${driver.name}</div>
                        <div class="text-muted small">${driver.vehicle} • ${driver.plate}</div>
                        <div class="text-warning small">
                            <i class="fas fa-star"></i> ${driver.rating} (${driver.trips} perjalanan)
                        </div>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-success btn-sm">
                            <i class="fas fa-phone"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.querySelector('.trip-details').insertAdjacentHTML('afterend', driverInfo);
    }
    
    function cancelOrder() {
        const confirmBtn = document.getElementById('confirmCancelBtn');
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Membatalkan...';
        
        fetch('process/order_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'cancel_order',
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('cancelConfirmModal')).hide();
                window.location.href = 'index.php';
            } else {
                alert(data.message || 'Gagal membatalkan pesanan');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Ya, Batalkan';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan koneksi');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Ya, Batalkan';
        });
    }
    
    function orderCancelled() {
        document.getElementById('statusTitle').textContent = 'Pesanan Dibatalkan';
        document.getElementById('statusMessage').textContent = 'Pesanan Anda telah dibatalkan';
        
        // Update status icon
        document.querySelector('.status-icon i').className = 'fas fa-times fa-2x text-danger';
        
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 3000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>