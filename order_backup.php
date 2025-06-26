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

<div class="container-fluid p-0">
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
    <div class="order-form p-3">
        <form id="orderForm">
            <!-- Location Selection -->
            <div class="location-section bg-white rounded-4 shadow-sm mb-3">
                <!-- Pickup Location -->
                <div class="location-item border-bottom" style="padding: 20px;">
                    <div class="d-flex align-items-center">
                        <div class="location-dot me-3" style="width: 16px; height: 16px; background: #dc3545; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #dc3545;"></div>
                        <div class="flex-grow-1">
                            <div class="location-label text-muted mb-1" style="font-size: 13px;">Lokasi penjemputan</div>
                            <input type="text" class="form-control border-0 p-0" id="pickup_location" name="pickup_location" 
                                   placeholder="Gang Tiga Desa, 1" 
                                   style="font-size: 16px; font-weight: 500; background: transparent; color: #333; box-shadow: none;"
                                   required>
                            <input type="text" class="form-control border-0 p-0 mt-1" id="pickup_detail" name="pickup_detail" 
                                   placeholder="Titik penjemputan" 
                                   style="font-size: 14px; color: #888; background: transparent; box-shadow: none;">
                        </div>
                        <button type="button" class="btn btn-link text-muted p-2" id="useCurrentLocation">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Destination -->
                <div class="location-item" style="padding: 20px;">
                    <div class="d-flex align-items-center">
                        <div class="location-dot me-3" style="width: 16px; height: 16px; background: #28a745; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #28a745;"></div>
                        <div class="flex-grow-1">
                            <div class="location-label text-muted mb-1" style="font-size: 13px;">Tujuan</div>
                            <input type="text" class="form-control border-0 p-0" id="destination" name="destination" 
                                   placeholder="Masukkan alamat tujuan" 
                                   style="font-size: 16px; font-weight: 500; background: transparent; color: #333; box-shadow: none;"
                                   required>
                        </div>
                        <button type="button" class="btn btn-link text-muted p-2">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Details Section -->
            <div class="details-section bg-white rounded-4 shadow-sm mb-3" style="padding: 20px;">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-list-ul" style="color: #666; font-size: 18px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0" style="font-weight: 500;">Perincian</h6>
                    </div>
                </div>
                
                <div class="detail-options mt-3">
                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 detail-option active" data-option="account">
                            <i class="fas fa-user me-2"></i>Dari akun
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 detail-option" data-option="now">
                            <i class="fas fa-clock me-2"></i>Saat ini
                        </button>
                    </div>
                </div>
            </div>

            <!-- Vehicle Selection -->
            <div class="vehicle-section">
                <div class="vehicle-options d-flex gap-3 mb-4">
                    <div class="vehicle-option flex-fill bg-white rounded-4 shadow-sm p-4 text-center vehicle-card active" data-type="bike" data-price="8000">
                        <div class="vehicle-icon mb-3">
                            <i class="fas fa-motorcycle text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="mb-0" style="font-weight: 600;">Bike</h6>
                    </div>
                    
                    <div class="vehicle-option flex-fill bg-white rounded-4 shadow-sm p-4 text-center vehicle-card" data-type="car" data-price="15000">
                        <div class="vehicle-icon mb-3">
                            <i class="fas fa-car text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="mb-0" style="font-weight: 600;">Car</h6>
                    </div>
                    
                    <div class="vehicle-option flex-fill bg-white rounded-4 shadow-sm p-4 text-center vehicle-card" data-type="delivery" data-price="12000">
                        <div class="vehicle-icon mb-3">
                            <i class="fas fa-box text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="mb-0" style="font-weight: 600;">Delivery</h6>
                    </div>
                </div>
                
                <!-- Order Button -->
                <div class="order-button-container px-3 pb-4">
                    <button type="submit" class="btn btn-warning w-100 py-3 rounded-4 fw-bold" 
                            style="background: #FFC107; border: none; font-size: 18px; letter-spacing: 0.5px;">
                        MEMESAN
                    </button>
                </div>
        </form>
    </div>
</div>

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
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid #eee;
    z-index: 100;
}

.order-form {
    padding-bottom: 100px;
}

@media (min-width: 768px) {
    .order-button-container {
        position: relative;
        border-top: none;
    }
    
    .order-form {
        padding-bottom: 0;
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
    
    // Auto-fill pickup location with current location
    document.getElementById('useCurrentLocation').addEventListener('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                // In a real app, you would reverse geocode this
                document.getElementById('pickup_location').value = 'Lokasi saat ini';
                document.getElementById('pickup_detail').value = 'Menggunakan GPS';
            });
        }
    });
    
    // Form submission
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedVehicle = document.querySelector('.vehicle-card.active');
        const pickupLocation = document.getElementById('pickup_location').value;
        const destination = document.getElementById('destination').value;
        
        if (!pickupLocation || !destination) {
            alert('Mohon lengkapi lokasi penjemputan dan tujuan');
            return;
        }
        
        // Simulate order submission
        const orderData = {
            pickup_location: pickupLocation,
            destination: destination,
            vehicle_type: selectedVehicle.dataset.type,
            estimated_price: selectedVehicle.dataset.price
        };
        
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
                window.location.href = 'history.php';
            } else {
                alert(data.message || 'Terjadi kesalahan saat memesan');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan koneksi');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
        timeInput.required = true;
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        dateInput.value = today;
        
        // Set minimum time to current time if date is today
        const now = new Date();
        const currentTime = now.toTimeString().slice(0, 5);
        timeInput.min = currentTime;
        timeInput.value = currentTime;
    } else {
        scheduleDateTime.classList.add('d-none');
        dateInput.required = false;
        timeInput.required = false;
    }
});

// Swap locations
document.getElementById('swapLocations').addEventListener('click', function() {
    const pickup = document.getElementById('pickup_location');
    const destination = document.getElementById('destination');
    
    const temp = pickup.value;
    pickup.value = destination.value;
    destination.value = temp;
});

// Use current location
document.getElementById('useCurrentLocation').addEventListener('click', function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            // In a real app, you would reverse geocode the coordinates
            document.getElementById('pickup_location').value = 'Current Location';
        }, function(error) {
            alert('Unable to get your location. Please enter manually.');
        });
    } else {
        alert('Geolocation is not supported by this browser.');
    }
});

// Form submission
document.getElementById('orderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('bookRideBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnSpinner = btn.querySelector('.btn-spinner');
    
    // Validate form
    const pickup = document.getElementById('pickup_location').value.trim();
    const destination = document.getElementById('destination').value.trim();
    
    if (!pickup || !destination) {
        alert('Please enter both pickup and destination locations.');
        return;
    }
    
    if (pickup === destination) {
        alert('Pickup and destination cannot be the same.');
        return;
    }
    
    // Show loading state
    btn.disabled = true;
    btnText.textContent = 'Booking Ride...';
    btnSpinner.classList.remove('d-none');
    
    const formData = new FormData(this);
    
    fetch('process/order_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show confirmation modal
            document.getElementById('orderIdDisplay').textContent = data.order_id;
            document.getElementById('vehicleTypeDisplay').textContent = document.getElementById('vehicle_type').value.toUpperCase();
            document.getElementById('totalPriceDisplay').textContent = document.getElementById('totalPrice').textContent;
            
            const modal = new bootstrap.Modal(document.getElementById('orderConfirmModal'));
            modal.show();
        } else {
            alert(data.message || 'Failed to book ride. Please try again.');
        }
    })
    .catch(error => {
        alert('Network error. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btnText.textContent = 'Pesan RideMax';
        btnSpinner.classList.add('d-none');
    });
});

// Map functionality
let map, directionsService, directionsRenderer;
let pickupMarker, destinationMarker;

function initMap() {
    // Default location (Jakarta center)
    const defaultLocation = { lat: -6.2088, lng: 106.8456 };
    
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 13,
        center: defaultLocation,
        styles: [
            {
                "featureType": "poi",
                "elementType": "labels",
                "stylers": [{"visibility": "off"}]
            }
        ]
    });

    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        draggable: true,
        suppressMarkers: false,
        polylineOptions: {
            strokeColor: '#007bff',
            strokeWeight: 5,
            strokeOpacity: 0.8
        }
    });
    directionsRenderer.setMap(map);

    // Setup autocomplete for address inputs
    setupAddressAutocomplete();
}

function setupAddressAutocomplete() {
    const pickupInput = document.getElementById('pickup_location');
    const destinationInput = document.getElementById('destination');

    const pickupAutocomplete = new google.maps.places.Autocomplete(pickupInput, {
        componentRestrictions: { country: 'id' }
    });

    const destinationAutocomplete = new google.maps.places.Autocomplete(destinationInput, {
        componentRestrictions: { country: 'id' }
    });

    pickupAutocomplete.addListener('place_changed', function() {
        const place = pickupAutocomplete.getPlace();
        if (place.geometry) {
            updateRoute();
        }
    });

    destinationAutocomplete.addListener('place_changed', function() {
        const place = destinationAutocomplete.getPlace();
        if (place.geometry) {
            updateRoute();
        }
    });

    // Also update route when user types manually
    pickupInput.addEventListener('blur', updateRoute);
    destinationInput.addEventListener('blur', updateRoute);
}

function updateRoute() {
    const pickup = document.getElementById('pickup_location').value.trim();
    const destination = document.getElementById('destination').value.trim();

    if (pickup && destination && pickup !== destination) {
        document.getElementById('mapSection').style.display = 'block';
        
        const request = {
            origin: pickup,
            destination: destination,
            travelMode: google.maps.TravelMode.DRIVING,
            unitSystem: google.maps.UnitSystem.METRIC,
            avoidHighways: false,
            avoidTolls: false
        };

        directionsService.route(request, function(result, status) {
            if (status === 'OK') {
                directionsRenderer.setDirections(result);
                
                // Update distance and time estimates
                const route = result.routes[0];
                const leg = route.legs[0];
                
                document.getElementById('estimatedDistance').textContent = leg.distance.text;
                document.getElementById('estimatedTime').textContent = leg.duration.text;
                
                // Update pricing based on distance
                updatePricingBasedOnDistance(leg.distance.value);
            } else {
                console.error('Directions request failed due to ' + status);
                // Hide map if route calculation fails
                document.getElementById('mapSection').style.display = 'none';
            }
        });
    } else {
        document.getElementById('mapSection').style.display = 'none';
    }
}

function updatePricingBasedOnDistance(distanceInMeters) {
    const distanceInKm = distanceInMeters / 1000;
    const baseRate = 5000; // Base rate
    const perKmRate = 2000; // Rate per km
    
    // Calculate new prices for each vehicle type
    const economyPrice = Math.max(15000, baseRate + (distanceInKm * perKmRate));
    const comfortPrice = Math.max(25000, baseRate + (distanceInKm * perKmRate * 1.5));
    const premiumPrice = Math.max(40000, baseRate + (distanceInKm * perKmRate * 2));
    
    // Update vehicle option prices
    document.querySelector('[data-type="economy"]').dataset.price = Math.round(economyPrice);
    document.querySelector('[data-type="comfort"]').dataset.price = Math.round(comfortPrice);
    document.querySelector('[data-type="premium"]').dataset.price = Math.round(premiumPrice);
    
    // Update displayed prices
    document.querySelector('[data-type="economy"] .price').textContent = `Rp ${Math.round(economyPrice).toLocaleString()}`;
    document.querySelector('[data-type="comfort"] .price').textContent = `Rp ${Math.round(comfortPrice).toLocaleString()}`;
    document.querySelector('[data-type="premium"] .price').textContent = `Rp ${Math.round(premiumPrice).toLocaleString()}`;
    
    // Update total if a vehicle is selected
    const selectedVehicle = document.querySelector('.vehicle-option.active');
    if (selectedVehicle) {
        const newPrice = parseInt(selectedVehicle.dataset.price);
        const serviceFee = 2000;
        const totalPrice = newPrice + serviceFee;
        
        document.getElementById('estimated_price').value = totalPrice;
        document.getElementById('baseFare').textContent = `Rp ${newPrice.toLocaleString()}`;
        document.getElementById('totalPrice').textContent = `Rp ${totalPrice.toLocaleString()}`;
    }
}

// Initialize map when page loads
window.addEventListener('load', function() {
    // Only load map if Google Maps API is available
    if (typeof google !== 'undefined' && google.maps) {
        initMap();
    }
});
</script>

<!-- Google Maps API - Demo version with fallback -->
<script>
// Fallback map implementation when Google Maps API is not available
function initDemoMap() {
    const mapElement = document.getElementById('map');
    if (mapElement) {
        mapElement.innerHTML = `
            <div style="height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                        display: flex; align-items: center; justify-content: center; color: white; text-align: center;">
                <div>
                    <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                    <h5>Peta Rute</h5>
                    <p class="mb-0">Masukkan alamat jemput dan tujuan<br>untuk melihat rute perjalanan</p>
                </div>
            </div>
        `;
    }
}

// Initialize demo map if Google Maps is not available
if (typeof google === 'undefined') {
    window.addEventListener('load', initDemoMap);
}
</script>

<!-- Note: Google Maps API key needed for full functionality -->
<script async defer 
    src="https://maps.googleapis.com/maps/api/js?key=DEMO_KEY&libraries=places&callback=initMap"
    onerror="initDemoMap()">
</script>

<?php include 'includes/footer.php'; ?>
