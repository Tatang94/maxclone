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
$pageTitle = 'Pesan Perjalanan - RideMax';
include 'includes/header.php';
?>

<div class="container-fluid p-0" style="max-width: 480px; margin: 0 auto;">
    <!-- Header -->
    <div class="page-header bg-primary text-white p-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-white p-0 me-3" onclick="history.back()">
                <i class="fas fa-arrow-left fa-lg"></i>
            </button>
            <h5 class="mb-0">Pesan Perjalanan</h5>
        </div>
    </div>

    <!-- Order Form -->
    <div class="order-form px-3 py-2">
        <form id="orderForm">
            <!-- Location Selection -->
            <div class="location-section bg-white rounded-3 shadow-sm mb-3">
                <!-- Pickup Location -->
                <div class="location-item border-bottom" style="padding: 15px;">
                    <div class="d-flex align-items-center">
                        <div class="location-dot me-2" style="width: 12px; height: 12px; background: #dc3545; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 1px #dc3545;"></div>
                        <div class="flex-grow-1">
                            <div class="location-label text-muted mb-1" style="font-size: 11px;">Lokasi penjemputan</div>
                            <input type="text" class="form-control border-0 p-0" id="pickup_location" name="pickup_location" 
                                   placeholder="Gang Tiga Desa, 1" 
                                   style="font-size: 14px; font-weight: 500; background: transparent; color: #333; box-shadow: none;"
                                   required>
                            <input type="text" class="form-control border-0 p-0 mt-1" id="pickup_detail" name="pickup_detail" 
                                   placeholder="Titik penjemputan" 
                                   style="font-size: 12px; color: #888; background: transparent; box-shadow: none;">
                        </div>
                        <button type="button" class="btn btn-link text-muted p-1" id="useCurrentLocation">
                            <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Destination -->
                <div class="location-item" style="padding: 15px;">
                    <div class="d-flex align-items-center">
                        <div class="location-dot me-2" style="width: 12px; height: 12px; background: #28a745; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 1px #28a745;"></div>
                        <div class="flex-grow-1">
                            <div class="location-label text-muted mb-1" style="font-size: 11px;">Tujuan</div>
                            <input type="text" class="form-control border-0 p-0" id="destination" name="destination" 
                                   placeholder="Masukkan alamat tujuan" 
                                   style="font-size: 14px; font-weight: 500; background: transparent; color: #333; box-shadow: none;"
                                   required>
                        </div>
                        <button type="button" class="btn btn-link text-muted p-1">
                            <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Details Section -->
            <div class="details-section bg-white rounded-3 shadow-sm mb-3" style="padding: 15px;">
                <div class="d-flex align-items-center mb-2">
                    <div class="me-2">
                        <i class="fas fa-list-ul" style="color: #666; font-size: 16px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0" style="font-weight: 500; font-size: 14px;">Perincian</h6>
                    </div>
                </div>
                
                <div class="detail-options">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-1 detail-option active" data-option="account" style="font-size: 12px;">
                            <i class="fas fa-user me-1" style="font-size: 10px;"></i>Dari akun
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-1 detail-option" data-option="now" style="font-size: 12px;">
                            <i class="fas fa-clock me-1" style="font-size: 10px;"></i>Saat ini
                        </button>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="map-section bg-white rounded-3 shadow-sm mb-3" style="padding: 15px;">
                <div class="map-header mb-2">
                    <h6 class="mb-0" style="font-weight: 500; font-size: 14px;">Peta Rute</h6>
                    <small class="text-muted" style="font-size: 11px;">Rute akan ditampilkan setelah lokasi diisi</small>
                </div>
                <div id="map" style="height: 180px; border-radius: 8px; background: #f8f9fa; border: 1px solid #dee2e6;"></div>
                <div id="route-info" class="mt-2 d-none">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted" style="font-size: 11px;">Jarak:</small>
                            <span id="distance" class="fw-bold" style="font-size: 12px;">-</span>
                        </div>
                        <div>
                            <small class="text-muted" style="font-size: 11px;">Estimasi:</small>
                            <span id="duration" class="fw-bold" style="font-size: 12px;">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Selection -->
            <div class="vehicle-section">
                <div class="vehicle-options d-flex gap-2 mb-4">
                    <div class="vehicle-option flex-fill bg-white rounded-3 shadow-sm p-3 text-center vehicle-card active" data-type="bike" data-price="8000">
                        <div class="vehicle-icon mb-2">
                            <i class="fas fa-motorcycle text-warning" style="font-size: 2.5rem;"></i>
                        </div>
                        <h6 class="mb-1" style="font-weight: 600; font-size: 14px;">Bike</h6>
                        <div class="price-info">
                            <span class="text-muted" style="font-size: 10px;">Mulai dari</span><br>
                            <span class="fw-bold text-warning" id="bike-price" style="font-size: 12px;">Rp 8.000</span>
                        </div>
                    </div>
                    
                    <div class="vehicle-option flex-fill bg-white rounded-3 shadow-sm p-3 text-center vehicle-card" data-type="car" data-price="15000">
                        <div class="vehicle-icon mb-2">
                            <i class="fas fa-bicycle text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                        <h6 class="mb-1" style="font-weight: 600; font-size: 14px;">Car</h6>
                        <div class="price-info">
                            <span class="text-muted" style="font-size: 10px;">Mulai dari</span><br>
                            <span class="fw-bold text-primary" id="car-price" style="font-size: 12px;">Rp 15.000</span>
                        </div>
                    </div>
                    
                    <div class="vehicle-option flex-fill bg-white rounded-3 shadow-sm p-3 text-center vehicle-card" data-type="delivery" data-price="12000">
                        <div class="vehicle-icon mb-2">
                            <i class="fas fa-box text-success" style="font-size: 2.5rem;"></i>
                        </div>
                        <h6 class="mb-1" style="font-weight: 600; font-size: 14px;">Delivery</h6>
                        <div class="price-info">
                            <span class="text-muted" style="font-size: 10px;">Mulai dari</span><br>
                            <span class="fw-bold text-success" id="delivery-price" style="font-size: 12px;">Rp 12.000</span>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method Selection -->
                <div class="payment-section bg-white rounded-3 shadow-sm mb-3" style="padding: 15px;">
                    <h6 class="mb-3" style="font-weight: 500; font-size: 14px;">Metode Pembayaran</h6>
                    <div class="payment-options">
                        <div class="payment-option mb-2">
                            <input type="radio" class="btn-check" name="payment_method" id="wallet" value="wallet" checked>
                            <label class="btn btn-outline-success w-100 text-start d-flex align-items-center py-3" for="wallet">
                                <i class="fas fa-wallet me-3 text-success"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">Saldo Dompet</div>
                                    <small class="text-muted" id="wallet-balance-display">Memuat saldo...</small>
                                </div>
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" class="btn-check" name="payment_method" id="cash" value="cash">
                            <label class="btn btn-outline-primary w-100 text-start d-flex align-items-center py-3" for="cash">
                                <i class="fas fa-money-bill-wave me-3 text-primary"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">Tunai</div>
                                    <small class="text-muted">Bayar langsung ke driver</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Button -->
                <div class="order-button-container px-2 pb-3">
                    <button type="submit" class="btn btn-warning w-100 py-3 rounded-3 fw-bold" id="orderButton"
                            style="background: #FFC107; border: none; font-size: 16px; letter-spacing: 0.5px;">
                        MEMESAN
                    </button>
                </div>
                
                <!-- Order Confirmation Modal -->
                <div class="modal fade" id="orderConfirmModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-3">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Konfirmasi Pesanan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="order-summary">
                                    <div class="trip-info mb-3">
                                        <div class="d-flex mb-2">
                                            <div class="location-dot me-2 mt-1" style="width: 8px; height: 8px; background: #dc3545; border-radius: 50%;"></div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted">Dari</small>
                                                <div id="confirmPickup" class="fw-medium">-</div>
                                            </div>
                                        </div>
                                        <div class="d-flex">
                                            <div class="location-dot me-2 mt-1" style="width: 8px; height: 8px; background: #28a745; border-radius: 50%;"></div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted">Ke</small>
                                                <div id="confirmDestination" class="fw-medium">-</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="vehicle-info bg-light rounded-3 p-3 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div id="confirmVehicleIcon" class="me-3"></div>
                                            <div class="flex-grow-1">
                                                <div id="confirmVehicleType" class="fw-bold">-</div>
                                                <small class="text-muted">Estimasi tiba dalam 5-10 menit</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method-info bg-light rounded-3 p-3 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div id="confirmPaymentIcon" class="me-3"></div>
                                            <div class="flex-grow-1">
                                                <div id="confirmPaymentMethod" class="fw-bold">-</div>
                                                <small id="confirmPaymentDetail" class="text-muted">-</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="price-breakdown">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Jarak perjalanan</span>
                                            <span id="confirmDistance">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Estimasi waktu</span>
                                            <span id="confirmDuration">-</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold fs-5">
                                            <span>Total Biaya</span>
                                            <span id="confirmPrice" class="text-warning">-</span>
                                        </div>
                                        <div id="insufficient-balance-warning" class="alert alert-warning mt-3 d-none">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Saldo tidak mencukupi. Silakan isi saldo atau pilih metode pembayaran lain.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <div class="w-100">
                                    <button type="button" class="btn btn-success w-100 py-2 mb-2 fw-bold" id="confirmOrder">
                                        PESAN SEKARANG
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary w-100 py-2" data-bs-dismiss="modal">
                                        BATALKAN
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Custom CSS for Maxim-style design -->
<style>
.vehicle-card {
    border: 2px solid transparent;
    transition: all 0.3s ease;
    cursor: pointer;
}

.vehicle-card.active {
    border-color: #FFC107;
    background: #FFFBF0 !important;
}

.vehicle-card:hover {
    border-color: #FFC107;
    transform: translateY(-2px);
}

.detail-option {
    border: 2px solid #e9ecef;
    background: white;
    color: #666;
    transition: all 0.3s ease;
}

.detail-option.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.location-item {
    position: relative;
}

.location-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 8px;
    top: 100%;
    width: 2px;
    height: 20px;
    background: #ddd;
}

.order-button-container {
    position: static;
    margin: 20px auto 0;
    width: 100%;
    background: white;
    border-radius: 8px;
    z-index: 100;
    padding: 0;
}

.order-form {
    padding-bottom: 20px;
}

/* Mobile fixes */
@media (max-width: 767px) {
    .order-button-container {
        position: static;
        margin: 20px 0 80px 0;
        width: 100%;
        background: white;
        border-radius: 8px;
        z-index: 100;
        padding: 0;
    }
    
    .order-form {
        padding-bottom: 0;
        margin-bottom: 80px;
    }
}

@media (min-width: 768px) {
    .order-button-container {
        position: static;
        margin: 20px 0 0;
        width: 100%;
        box-shadow: none;
        border-radius: 8px;
        padding: 0;
    }
    
    .order-form {
        padding-bottom: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vehicle selection handling
    const vehicleCards = document.querySelectorAll('.vehicle-card');
    vehicleCards.forEach(card => {
        card.addEventListener('click', function() {
            vehicleCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Detail options handling
    const detailOptions = document.querySelectorAll('.detail-option');
    detailOptions.forEach(option => {
        option.addEventListener('click', function() {
            detailOptions.forEach(o => o.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Load wallet balance
    loadWalletBalance();
    
    // Initialize default pricing (5km default distance)
    updatePricing(5);
    
    // Auto-fill pickup location with current location
    document.getElementById('useCurrentLocation').addEventListener('click', function() {
        getCurrentLocationForMap();
    });
    
    // Form submission - show confirmation modal
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedVehicle = document.querySelector('.vehicle-card.active');
        const pickupLocation = document.getElementById('pickup_location').value.trim();
        const destination = document.getElementById('destination').value.trim();
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        // Validation
        if (!pickupLocation || !destination) {
            alert('Mohon lengkapi lokasi penjemputan dan tujuan');
            return;
        }
        
        if (!selectedVehicle) {
            alert('Mohon pilih jenis kendaraan');
            return;
        }
        
        if (!paymentMethod) {
            alert('Mohon pilih metode pembayaran');
            return;
        }
        
        if (pickupLocation.toLowerCase() === destination.toLowerCase()) {
            alert('Lokasi penjemputan dan tujuan tidak boleh sama');
            return;
        }
        
        // Show confirmation modal with order details
        showOrderConfirmation(selectedVehicle, pickupLocation, destination);
    });
    
    // Confirm order button
    document.getElementById('confirmOrder').addEventListener('click', function() {
        const selectedVehicle = document.querySelector('.vehicle-card.active');
        const pickupLocation = document.getElementById('pickup_location').value;
        const destination = document.getElementById('destination').value;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        const orderData = {
            pickup_location: pickupLocation,
            destination: destination,
            vehicle_type: selectedVehicle.dataset.type,
            estimated_price: selectedVehicle.dataset.price,
            payment_method: paymentMethod
        };
        
        // Disable button and show loading
        const confirmBtn = document.getElementById('confirmOrder');
        const originalText = confirmBtn.textContent;
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'MEMPROSES...';
        
        // Submit order via AJAX
        fetch('process/order_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'create_order',
                ...orderData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and redirect to order status
                bootstrap.Modal.getInstance(document.getElementById('orderConfirmModal')).hide();
                window.location.href = 'order_status.php?order_id=' + data.order_id;
            } else {
                alert(data.message || 'Terjadi kesalahan saat memesan');
                confirmBtn.disabled = false;
                confirmBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan koneksi');
            confirmBtn.disabled = false;
            confirmBtn.textContent = originalText;
        });
    });
});

// Load wallet balance
function loadWalletBalance() {
    fetch('process/get_balance.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('wallet-balance-display').textContent = data.formatted;
            window.userBalance = data.balance;
        } else {
            document.getElementById('wallet-balance-display').textContent = 'Gagal memuat saldo';
        }
    })
    .catch(error => {
        console.error('Error loading balance:', error);
        document.getElementById('wallet-balance-display').textContent = 'Gagal memuat saldo';
    });
}

// Show order confirmation modal
function showOrderConfirmation(selectedVehicle, pickupLocation, destination) {
    // Get vehicle info
    const vehicleType = selectedVehicle.dataset.type;
    const vehiclePrice = selectedVehicle.dataset.price;
    
    // Get vehicle icon
    let vehicleIcon = '';
    switch(vehicleType) {
        case 'bike':
            vehicleIcon = '<i class="fas fa-motorcycle text-warning" style="font-size: 2rem;"></i>';
            break;
        case 'car':
            vehicleIcon = '<i class="fas fa-car text-primary" style="font-size: 2rem;"></i>';
            break;
        case 'delivery':
            vehicleIcon = '<i class="fas fa-box text-success" style="font-size: 2rem;"></i>';
            break;
    }
    
    // Get distance and duration info
    const distanceEl = document.getElementById('distance');
    const durationEl = document.getElementById('duration');
    const distance = distanceEl.textContent !== '-' ? distanceEl.textContent : '5.0 km';
    const duration = durationEl.textContent !== '-' ? durationEl.textContent : '15 menit';
    
    // Update modal content
    document.getElementById('confirmPickup').textContent = pickupLocation;
    document.getElementById('confirmDestination').textContent = destination;
    document.getElementById('confirmVehicleIcon').innerHTML = vehicleIcon;
    document.getElementById('confirmVehicleType').textContent = vehicleType.charAt(0).toUpperCase() + vehicleType.slice(1);
    document.getElementById('confirmDistance').textContent = distance;
    document.getElementById('confirmDuration').textContent = duration;
    document.getElementById('confirmPrice').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(vehiclePrice);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('orderConfirmModal'));
    modal.show();
}

// OpenStreetMap Integration
let map;
let pickupMarker;
let destinationMarker;
let routeControl;

// Initialize map
function initMap() {
    // Start with Indonesia center view
    map = L.map('map').setView([-2.5489, 118.0149], 5);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Get current location with high accuracy
    getCurrentLocationForMap();
}

// Get current location with high accuracy for map
function getCurrentLocationForMap() {
    if (navigator.geolocation) {
        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        };
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                // Determine zoom level based on accuracy
                let zoomLevel = 16;
                if (accuracy > 100) zoomLevel = 14;
                else if (accuracy > 500) zoomLevel = 12;
                else if (accuracy > 1000) zoomLevel = 10;
                
                // Center map to current location with appropriate zoom
                map.setView([lat, lng], zoomLevel);
                
                // Set pickup location
                document.getElementById('pickup_location').value = 'Lokasi saat ini';
                document.getElementById('pickup_detail').value = `Akurasi: ${Math.round(accuracy)}m`;
                
                // Add current location marker
                updatePickupLocation(lat, lng);
                
                // Add accuracy circle
                const accuracyCircle = L.circle([lat, lng], {
                    radius: accuracy,
                    color: '#007bff',
                    fillColor: '#007bff',
                    fillOpacity: 0.1,
                    weight: 1
                }).addTo(map);
                
                console.log('Location found:', {lat, lng, accuracy, zoom: zoomLevel});
            },
            function(error) {
                console.error('Location error:', error);
                handleLocationError(error);
            },
            options
        );
    } else {
        console.error('Geolocation not supported');
        // Fallback to major Indonesian cities
        map.setView([-6.2088, 106.8456], 11); // Jakarta
    }
}

// Handle location errors
function handleLocationError(error) {
    let message = 'Tidak dapat mengakses lokasi';
    switch(error.code) {
        case error.PERMISSION_DENIED:
            message = 'Akses lokasi ditolak. Silakan aktifkan GPS.';
            break;
        case error.POSITION_UNAVAILABLE:
            message = 'Informasi lokasi tidak tersedia.';
            break;
        case error.TIMEOUT:
            message = 'Permintaan lokasi timeout.';
            break;
    }
    
    document.getElementById('pickup_detail').value = message;
    
    // Fallback to major Indonesian cities based on timezone or IP
    const fallbackCities = [
        {name: 'Jakarta', coords: [-6.2088, 106.8456], zoom: 11},
        {name: 'Surabaya', coords: [-7.2575, 112.7521], zoom: 11},
        {name: 'Bandung', coords: [-6.9175, 107.6191], zoom: 11},
        {name: 'Medan', coords: [3.5952, 98.6722], zoom: 11}
    ];
    
    // Use Jakarta as default fallback
    const fallback = fallbackCities[0];
    map.setView(fallback.coords, fallback.zoom);
    document.getElementById('pickup_location').value = `Area ${fallback.name}`;
}

// Update pickup location on map
function updatePickupLocation(lat, lng) {
    if (pickupMarker) {
        map.removeLayer(pickupMarker);
    }
    
    pickupMarker = L.marker([lat, lng], {
        icon: L.divIcon({
            className: 'pickup-marker',
            html: '<div style="background: #dc3545; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        })
    }).addTo(map);
    
    updateRoute();
}

// Update destination location on map
function updateDestinationLocation(lat, lng) {
    if (destinationMarker) {
        map.removeLayer(destinationMarker);
    }
    
    destinationMarker = L.marker([lat, lng], {
        icon: L.divIcon({
            className: 'destination-marker',
            html: '<div style="background: #28a745; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        })
    }).addTo(map);
    
    updateRoute();
}

// Update route between pickup and destination
function updateRoute() {
    if (pickupMarker && destinationMarker) {
        const pickup = pickupMarker.getLatLng();
        const destination = destinationMarker.getLatLng();
        
        // Remove existing route
        if (routeControl) {
            map.removeControl(routeControl);
        }
        
        // Add route line
        const routeLine = L.polyline([pickup, destination], {
            color: '#007bff',
            weight: 4,
            opacity: 0.7
        }).addTo(map);
        
        // Fit map to show both markers
        const group = new L.featureGroup([pickupMarker, destinationMarker]);
        map.fitBounds(group.getBounds().pad(0.1));
        
        // Calculate distance and update pricing
        const distance = pickup.distanceTo(destination) / 1000; // Convert to km
        updatePricing(distance);
        
        // Show route info
        document.getElementById('distance').textContent = distance.toFixed(1) + ' km';
        document.getElementById('duration').textContent = Math.ceil(distance * 3) + ' menit'; // Rough estimate
        document.getElementById('route-info').classList.remove('d-none');
    }
}

// Update pricing based on distance
function updatePricing(distance) {
    // Ensure distance is a valid number and not too large
    if (!distance || distance < 0) distance = 1;
    if (distance > 100) distance = 100; // Cap at 100km for safety
    
    const basePrices = {
        bike: 8000,
        car: 15000,
        delivery: 12000
    };
    
    const pricePerKm = {
        bike: 2000,
        car: 3500,
        delivery: 2500
    };
    
    Object.keys(basePrices).forEach(type => {
        const distancePrice = Math.round(distance * pricePerKm[type]);
        const totalPrice = Math.round(basePrices[type] + distancePrice);
        
        // Ensure price is reasonable (minimum 5000, maximum 500000)
        const finalPrice = Math.max(5000, Math.min(500000, totalPrice));
        
        const priceElement = document.getElementById(type + '-price');
        const vehicleElement = document.querySelector(`[data-type="${type}"]`);
        
        if (priceElement) {
            priceElement.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(finalPrice);
        }
        if (vehicleElement) {
            vehicleElement.dataset.price = finalPrice;
        }
    });
}

// Geocoding function using Nominatim
async function geocodeAddress(address) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&countrycodes=id&limit=1`);
        const data = await response.json();
        
        if (data.length > 0) {
            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon),
                display_name: data[0].display_name
            };
        }
    } catch (error) {
        console.error('Geocoding error:', error);
    }
    return null;
}

// Setup address autocomplete
function setupAddressAutocomplete() {
    const pickup = document.getElementById('pickup_location');
    const destination = document.getElementById('destination');
    
    let pickupTimeout;
    let destTimeout;
    
    pickup.addEventListener('input', function() {
        clearTimeout(pickupTimeout);
        pickupTimeout = setTimeout(async () => {
            if (this.value.length > 3) {
                const result = await geocodeAddress(this.value);
                if (result) {
                    updatePickupLocation(result.lat, result.lng);
                }
            }
        }, 1000);
    });
    
    destination.addEventListener('input', function() {
        clearTimeout(destTimeout);
        destTimeout = setTimeout(async () => {
            if (this.value.length > 3) {
                const result = await geocodeAddress(this.value);
                if (result) {
                    updateDestinationLocation(result.lat, result.lng);
                }
            }
        }, 1000);
    });
}

// Initialize everything when page loads
window.addEventListener('load', function() {
    initMap();
    setupAddressAutocomplete();
});
</script>

<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php include 'includes/footer.php'; ?>