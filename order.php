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

            <!-- Menu Makanan -->
            <div class="food-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0" style="font-weight: 500; font-size: 14px;">Pilih Menu Makanan</h6>
                    <div class="category-filter">
                        <select class="form-select form-select-sm" id="categoryFilter" style="font-size: 12px; width: auto;">
                            <option value="">Semua Kategori</option>
                            <option value="nasi">Nasi</option>
                            <option value="mie">Mie</option>
                            <option value="minuman">Minuman</option>
                            <option value="snack">Snack</option>
                            <option value="dessert">Dessert</option>
                        </select>
                    </div>
                </div>
                
                <div id="foodList" class="row g-2 mb-4">
                    <!-- Food items will be loaded here dynamically -->
                </div>
                
                <div id="loadingFood" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Memuat menu...</span>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Memuat menu makanan...</small>
                    </div>
                </div>
                
                <div id="noFood" class="text-center py-4 d-none">
                    <i class="fas fa-utensils fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">Tidak ada menu makanan tersedia</p>
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
.food-card {
    border: 2px solid transparent;
    transition: all 0.3s ease;
    cursor: pointer;
}

.food-card.active {
    border-color: #FFC107;
    background: #FFFBF0 !important;
}

.food-card:hover {
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
    // Food selection handling
    const foodCards = document.querySelectorAll('.food-card');
    foodCards.forEach(card => {
        card.addEventListener('click', function() {
            foodCards.forEach(c => c.classList.remove('active'));
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
    
    // Load food menu from database
    loadFoodMenu();
    
    // Initialize default pricing (5km default distance)
    updatePricing(5);
    
    // Auto-fill pickup location with current location
    document.getElementById('useCurrentLocation').addEventListener('click', function() {
        getCurrentLocationForMap();
    });
    
    // Category filter event listener
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            loadFoodMenu(this.value);
        });
    }
    
    // Form submission - show confirmation modal
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedFood = document.querySelector('.food-card.active');
        const pickupLocation = document.getElementById('pickup_location').value.trim();
        const destination = document.getElementById('destination').value.trim();
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        // Validation
        if (!pickupLocation || !destination) {
            alert('Mohon lengkapi lokasi penjemputan dan tujuan');
            return;
        }
        
        if (!selectedFood) {
            alert('Mohon pilih menu makanan');
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
        showOrderConfirmation(selectedFood, pickupLocation, destination);
    });
    
    // Confirm order button
    document.getElementById('confirmOrder').addEventListener('click', function() {
        const selectedFood = document.querySelector('.food-card.active');
        const pickupLocation = document.getElementById('pickup_location').value;
        const destination = document.getElementById('destination').value;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        // Disable button to prevent double submission
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
        
        // Prepare order data
        const orderData = {
            food_id: selectedFood.dataset.id,
            food_name: selectedFood.dataset.name,
            food_type: selectedFood.dataset.type,
            price: selectedFood.dataset.price,
            merchant_name: selectedFood.dataset.merchant,
            preparation_time: selectedFood.dataset.preparationTime,
            pickup_location: pickupLocation,
            destination: destination,
            payment_method: paymentMethod
        };
        
        // Submit order
        submitFoodOrder(orderData);
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

// Load food menu from database
function loadFoodMenu(category = '') {
    const loadingEl = document.getElementById('loadingFood');
    const noFoodEl = document.getElementById('noFood');
    const foodListEl = document.getElementById('foodList');
    
    loadingEl.style.display = 'block';
    noFoodEl.classList.add('d-none');
    foodListEl.innerHTML = '';
    
    const url = category ? `process/get_food_menu.php?category=${encodeURIComponent(category)}` : 'process/get_food_menu.php';
    
    fetch(url)
    .then(response => response.json())
    .then(data => {
        loadingEl.style.display = 'none';
        
        if (data.success && data.data.length > 0) {
            displayFoodItems(data.data);
        } else {
            noFoodEl.classList.remove('d-none');
        }
    })
    .catch(error => {
        console.error('Error loading food menu:', error);
        loadingEl.style.display = 'none';
        noFoodEl.classList.remove('d-none');
    });
}

// Display food items
function displayFoodItems(foodItems) {
    const foodListEl = document.getElementById('foodList');
    foodListEl.innerHTML = '';
    
    foodItems.forEach((item, index) => {
        const categoryColors = {
            'nasi': { bg: 'warning', icon: 'bowl-rice' },
            'mie': { bg: 'primary', icon: 'bowl-food' },
            'minuman': { bg: 'info', icon: 'glass-water' },
            'snack': { bg: 'secondary', icon: 'cookie-bite' },
            'dessert': { bg: 'pink', icon: 'ice-cream' }
        };
        
        const colorScheme = categoryColors[item.category] || { bg: 'secondary', icon: 'utensils' };
        const isActive = index === 0 ? 'active' : '';
        
        const foodCard = `
            <div class="col-12">
                <div class="food-card bg-white rounded-3 shadow-sm p-3 ${isActive}" 
                     data-type="${item.category}" 
                     data-price="${item.price}" 
                     data-id="${item.id}"
                     data-name="${item.name}"
                     data-merchant="${item.merchant_name}"
                     data-preparation-time="${item.preparation_time}">
                    <div class="row g-0">
                        <div class="col-3">
                            <div class="food-image-container">
                                <img src="${item.image_url}" 
                                     class="img-fluid rounded" 
                                     alt="${item.name}"
                                     style="width: 100%; height: 60px; object-fit: cover;"
                                     onerror="this.src='https://via.placeholder.com/80x60/6c757d/ffffff?text=No+Image'">
                            </div>
                        </div>
                        <div class="col-9">
                            <div class="d-flex justify-content-between align-items-start h-100">
                                <div class="flex-grow-1 ps-3">
                                    <h6 class="mb-1" style="font-size: 13px; font-weight: 600;">${item.name}</h6>
                                    <p class="text-muted mb-1" style="font-size: 11px;">${item.description || 'Tidak ada deskripsi'}</p>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-${colorScheme.bg}" style="font-size: 9px;">
                                            <i class="fas fa-${colorScheme.icon} me-1"></i>${item.category}
                                        </span>
                                        <small class="text-muted" style="font-size: 10px;">
                                            <i class="fas fa-clock"></i> ${item.preparation_time} min
                                        </small>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted" style="font-size: 10px;">
                                            <i class="fas fa-store"></i> ${item.merchant_name}
                                        </small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-${colorScheme.bg}" style="font-size: 12px;">
                                        Rp ${new Intl.NumberFormat('id-ID').format(item.price)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        foodListEl.innerHTML += foodCard;
    });
    
    // Add click event listeners to food cards
    setupFoodCardListeners();
    
    // Update pricing for first item
    if (foodItems.length > 0) {
        updatePricing(5, foodItems[0].price);
    }
}

// Setup food card click listeners
function setupFoodCardListeners() {
    const foodCards = document.querySelectorAll('.food-card');
    foodCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all cards
            foodCards.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked card
            this.classList.add('active');
            
            // Update pricing
            const price = parseInt(this.dataset.price);
            const currentDistance = parseFloat(document.getElementById('distance').textContent) || 5;
            updatePricing(currentDistance, price);
        });
    });
}

// Submit food order to database
function submitFoodOrder(orderData) {
    const confirmBtn = document.getElementById('confirmOrder');
    const originalText = confirmBtn.textContent;
    
    // Submit order via AJAX
    fetch('process/food_order_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            food_id: orderData.food_id,
            food_name: orderData.food_name,
            food_type: orderData.food_type,
            merchant_name: orderData.merchant_name,
            preparation_time: orderData.preparation_time,
            pickup_location: orderData.pickup_location,
            destination: orderData.destination,
            estimated_price: orderData.price,
            payment_method: orderData.payment_method
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and redirect to order status
            bootstrap.Modal.getInstance(document.getElementById('orderConfirmModal')).hide();
            window.location.href = 'order_status.php?order_id=' + data.order_id;
        } else {
            alert(data.message || 'Terjadi kesalahan saat memesan makanan');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan koneksi');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    });
}

// Show order confirmation modal
function showOrderConfirmation(selectedFood, pickupLocation, destination) {
    // Get food info
    const foodType = selectedFood.dataset.type;
    const foodPrice = selectedFood.dataset.price;
    
    // Get food icon
    let foodIcon = '';
    switch(foodType) {
        case 'nasi':
            foodIcon = '<i class="fas fa-bowl-rice text-warning" style="font-size: 2rem;"></i>';
            break;
        case 'mie':
            foodIcon = '<i class="fas fa-bowl-food text-primary" style="font-size: 2rem;"></i>';
            break;
        case 'minuman':
            foodIcon = '<i class="fas fa-glass-water text-success" style="font-size: 2rem;"></i>';
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
    document.getElementById('confirmVehicleIcon').innerHTML = foodIcon;
    document.getElementById('confirmVehicleType').textContent = foodType.charAt(0).toUpperCase() + foodType.slice(1);
    document.getElementById('confirmDistance').textContent = distance;
    document.getElementById('confirmDuration').textContent = duration;
    document.getElementById('confirmPrice').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(foodPrice);
    
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