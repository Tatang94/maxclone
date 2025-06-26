<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check authentication and driver status
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getCurrentUser();
if ($user['is_driver'] != 1) {
    header('Location: index.php');
    exit();
}

$pageTitle = 'Dashboard Driver - RideMax';
include 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Driver Status Header -->
    <div class="driver-status-header bg-primary text-white p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Selamat datang, <?php echo htmlspecialchars($user['name']); ?>!</h5>
                <p class="mb-0 opacity-75">Dashboard Driver</p>
            </div>
            <div class="driver-toggle">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="driverOnlineToggle">
                    <label class="form-check-label text-white" for="driverOnlineToggle" id="statusLabel">
                        Online
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-section p-3">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                    <div class="stat-icon text-success mb-2">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                    <h6 class="stat-value mb-1" id="todayEarnings">Rp 0</h6>
                    <small class="text-muted">Pendapatan Hari Ini</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                    <div class="stat-icon text-primary mb-2">
                        <i class="fas fa-car fa-2x"></i>
                    </div>
                    <h6 class="stat-value mb-1" id="todayRides">0</h6>
                    <small class="text-muted">Rides Today</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                    <div class="stat-icon text-warning mb-2">
                        <i class="fas fa-star fa-2x"></i>
                    </div>
                    <h6 class="stat-value mb-1" id="rating">4.8</h6>
                    <small class="text-muted">Rating</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                    <div class="stat-icon text-info mb-2">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <h6 class="stat-value mb-1" id="onlineTime">0h 0m</h6>
                    <small class="text-muted">Online Time</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Ride (if active) -->
    <div id="currentRideSection" class="current-ride p-3 d-none">
        <div class="bg-white rounded-3 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Current Ride</h6>
                <span class="badge bg-primary" id="currentRideStatus">In Progress</span>
            </div>
            <div id="currentRideDetails">
                <!-- Current ride details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Available Rides -->
    <div class="available-rides p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Available Rides</h6>
            <button class="btn btn-outline-primary btn-sm" onclick="refreshAvailableRides()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div id="availableRidesContainer">
            <!-- Available rides will be loaded here -->
        </div>
    </div>

    <!-- Recent Completed Rides -->
    <div class="recent-rides p-3">
        <h6 class="mb-3">Recent Completed Rides</h6>
        <div id="recentRidesContainer">
            <!-- Recent rides will be loaded here -->
        </div>
    </div>
</div>

<!-- Accept Ride Modal -->
<div class="modal fade" id="acceptRideModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Ride Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="rideRequestDetails">
                <!-- Ride request details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Decline</button>
                <button type="button" class="btn btn-primary" id="acceptRideBtn">Accept Ride</button>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Modal -->
<div class="modal fade" id="navigationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Navigation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-map-marked-alt fa-3x text-primary mb-3"></i>
                    <h6>Navigate to Destination</h6>
                    <p class="text-muted mb-3" id="destinationAddress"></p>
                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-primary w-100" onclick="openGoogleMaps()">
                                <i class="fab fa-google me-1"></i> Google Maps
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-success w-100" onclick="openWaze()">
                                <i class="fas fa-route me-1"></i> Waze
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let driverOnline = false;
let currentRideId = null;
let selectedRideId = null;
let onlineStartTime = null;
let onlineTimer = null;

// Initialize driver dashboard
document.addEventListener('DOMContentLoaded', function() {
    loadDriverStats();
    loadRecentRides();
    checkCurrentRide();
    
    // Driver online toggle
    document.getElementById('driverOnlineToggle').addEventListener('change', function() {
        toggleDriverStatus(this.checked);
    });
    
    // Auto-refresh available rides when online
    setInterval(() => {
        if (driverOnline && !currentRideId) {
            loadAvailableRides();
        }
    }, 10000); // Refresh every 10 seconds
    
    // Auto-refresh stats every minute
    setInterval(loadDriverStats, 60000);
});

function toggleDriverStatus(online) {
    const toggle = document.getElementById('driverOnlineToggle');
    const label = document.getElementById('statusLabel');
    
    fetch('process/driver_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle_status',
            online: online
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            driverOnline = online;
            
            if (online) {
                label.textContent = 'Online';
                label.classList.add('text-success');
                label.classList.remove('text-white');
                onlineStartTime = new Date();
                startOnlineTimer();
                loadAvailableRides();
            } else {
                label.textContent = 'Offline';
                label.classList.remove('text-success');
                label.classList.add('text-white');
                stopOnlineTimer();
                clearAvailableRides();
            }
        } else {
            // Revert toggle if failed
            toggle.checked = !online;
            alert(data.message || 'Failed to update status');
        }
    })
    .catch(error => {
        console.error('Error toggling status:', error);
        toggle.checked = !online;
        alert('Network error. Please try again.');
    });
}

function startOnlineTimer() {
    onlineTimer = setInterval(() => {
        if (onlineStartTime) {
            const now = new Date();
            const diff = now - onlineStartTime;
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            document.getElementById('onlineTime').textContent = `${hours}h ${minutes}m`;
        }
    }, 1000);
}

function stopOnlineTimer() {
    if (onlineTimer) {
        clearInterval(onlineTimer);
        onlineTimer = null;
    }
    onlineStartTime = null;
}

function loadDriverStats() {
    fetch('process/driver_process.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('todayEarnings').textContent = `Rp ${parseInt(data.stats.today_earnings || 0).toLocaleString()}`;
                document.getElementById('todayRides').textContent = data.stats.today_rides || '0';
                document.getElementById('rating').textContent = data.stats.rating || '5.0';
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function loadAvailableRides() {
    fetch('process/driver_process.php?action=get_available_rides')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('availableRidesContainer');
            
            if (data.success && data.rides.length > 0) {
                container.innerHTML = data.rides.map(ride => `
                    <div class="ride-card bg-white rounded-3 shadow-sm p-3 mb-3 border-start border-primary border-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Ride Request #${ride.id}</h6>
                                <p class="text-muted small mb-1">
                                    <i class="fas fa-user me-1"></i> ${ride.user_name}
                                </p>
                                <p class="text-muted small mb-0">
                                    <i class="fas fa-clock me-1"></i> ${formatTimeAgo(ride.created_at)}
                                </p>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">Rp ${parseInt(ride.total_price).toLocaleString()}</div>
                                <small class="text-muted">${ride.vehicle_type.toUpperCase()}</small>
                            </div>
                        </div>
                        
                        <div class="route-info mb-3">
                            <div class="route-item mb-1">
                                <i class="fas fa-circle text-success me-2" style="font-size: 8px;"></i>
                                <span class="small">${ride.pickup_location}</span>
                            </div>
                            <div class="route-item">
                                <i class="fas fa-circle text-danger me-2" style="font-size: 8px;"></i>
                                <span class="small">${ride.destination}</span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="distance-info">
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-route me-1"></i> ~${ride.estimated_distance || '5'} km
                                </span>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="showRideRequest(${ride.id})">
                                <i class="fas fa-check me-1"></i> Accept
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-car fa-2x mb-2 opacity-50"></i>
                        <p>No available rides at the moment</p>
                        <small>Stay online to receive ride requests</small>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading available rides:', error));
}

function clearAvailableRides() {
    document.getElementById('availableRidesContainer').innerHTML = `
        <div class="text-center text-muted py-4">
            <i class="fas fa-toggle-off fa-2x mb-2 opacity-50"></i>
            <p>You're offline</p>
            <small>Go online to see available rides</small>
        </div>
    `;
}

function loadRecentRides() {
    fetch('process/driver_process.php?action=get_recent_rides')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('recentRidesContainer');
            
            if (data.success && data.rides.length > 0) {
                container.innerHTML = data.rides.map(ride => `
                    <div class="ride-card bg-white rounded-3 shadow-sm p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">#${ride.id} - ${ride.user_name}</h6>
                                <p class="text-muted small mb-0">${ride.pickup_location} → ${ride.destination}</p>
                                <small class="text-muted">${formatDate(ride.completed_at)}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">Rp ${parseInt(ride.total_price).toLocaleString()}</div>
                                <div class="rating">
                                    ${generateStars(ride.rating || 5)}
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history fa-2x mb-2 opacity-50"></i>
                        <p>No completed rides yet</p>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading recent rides:', error));
}

function checkCurrentRide() {
    fetch('process/driver_process.php?action=get_current_ride')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ride) {
                showCurrentRide(data.ride);
                currentRideId = data.ride.id;
            } else {
                hideCurrentRide();
                currentRideId = null;
            }
        })
        .catch(error => console.error('Error checking current ride:', error));
}

function showCurrentRide(ride) {
    const section = document.getElementById('currentRideSection');
    const details = document.getElementById('currentRideDetails');
    
    details.innerHTML = `
        <div class="current-ride-info">
            <div class="passenger-info mb-3">
                <div class="d-flex align-items-center">
                    <div class="passenger-avatar bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">${ride.user_name}</h6>
                        <small class="text-muted">${ride.user_phone || 'No phone'}</small>
                    </div>
                    <div class="ms-auto">
                        <button class="btn btn-outline-primary btn-sm" onclick="callPassenger('${ride.user_phone}')">
                            <i class="fas fa-phone"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="route-info mb-3">
                <div class="route-item mb-2">
                    <i class="fas fa-circle text-success me-2"></i>
                    <strong>Pickup:</strong> ${ride.pickup_location}
                </div>
                <div class="route-item mb-2">
                    <i class="fas fa-circle text-danger me-2"></i>
                    <strong>Destination:</strong> ${ride.destination}
                </div>
            </div>
            
            <div class="ride-actions">
                ${ride.status === 'accepted' ? `
                    <button class="btn btn-primary w-100 mb-2" onclick="startRide(${ride.id})">
                        <i class="fas fa-play me-2"></i> Start Ride
                    </button>
                    <button class="btn btn-outline-primary w-100" onclick="showNavigation('${ride.pickup_location}')">
                        <i class="fas fa-navigation me-2"></i> Navigate to Pickup
                    </button>
                ` : ''}
                
                ${ride.status === 'in_progress' ? `
                    <button class="btn btn-success w-100 mb-2" onclick="completeRide(${ride.id})">
                        <i class="fas fa-check me-2"></i> Complete Ride
                    </button>
                    <button class="btn btn-outline-primary w-100" onclick="showNavigation('${ride.destination}')">
                        <i class="fas fa-navigation me-2"></i> Navigate to Destination
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    
    section.classList.remove('d-none');
}

function hideCurrentRide() {
    document.getElementById('currentRideSection').classList.add('d-none');
}

function showRideRequest(rideId) {
    selectedRideId = rideId;
    
    fetch(`process/driver_process.php?action=get_ride_details&ride_id=${rideId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ride) {
                const details = document.getElementById('rideRequestDetails');
                const ride = data.ride;
                
                details.innerHTML = `
                    <div class="ride-request-details">
                        <div class="passenger-info mb-3">
                            <h6>Passenger: ${ride.user_name}</h6>
                            <p class="text-muted mb-0">Vehicle: ${ride.vehicle_type.toUpperCase()}</p>
                        </div>
                        
                        <div class="route-info mb-3">
                            <div class="mb-2">
                                <strong>Pickup:</strong><br>
                                <span class="text-muted">${ride.pickup_location}</span>
                            </div>
                            <div class="mb-2">
                                <strong>Destination:</strong><br>
                                <span class="text-muted">${ride.destination}</span>
                            </div>
                        </div>
                        
                        <div class="ride-details mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Estimated Distance</small>
                                    <div class="fw-bold">~${ride.estimated_distance || '5'} km</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Total Fare</small>
                                    <div class="fw-bold text-success">Rp ${parseInt(ride.total_price).toLocaleString()}</div>
                                </div>
                            </div>
                        </div>
                        
                        ${ride.notes ? `
                            <div class="special-instructions mb-3">
                                <small class="text-muted">Special Instructions:</small>
                                <div class="bg-light p-2 rounded">${ride.notes}</div>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                const modal = new bootstrap.Modal(document.getElementById('acceptRideModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Error loading ride details:', error);
            alert('Failed to load ride details.');
        });
}

function acceptRide() {
    if (!selectedRideId) return;
    
    const btn = document.getElementById('acceptRideBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Accepting...';
    
    fetch('process/driver_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'accept_ride',
            ride_id: selectedRideId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('acceptRideModal')).hide();
            checkCurrentRide();
            loadAvailableRides();
            alert('Ride accepted! Contact the passenger and head to pickup location.');
        } else {
            alert(data.message || 'Failed to accept ride.');
        }
    })
    .catch(error => {
        console.error('Error accepting ride:', error);
        alert('Network error. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Accept Ride';
    });
}

// Bind accept ride button
document.getElementById('acceptRideBtn').addEventListener('click', acceptRide);

function startRide(rideId) {
    if (confirm('Confirm that you have picked up the passenger?')) {
        updateRideStatus(rideId, 'in_progress');
    }
}

function completeRide(rideId) {
    if (confirm('Confirm that the ride is completed?')) {
        updateRideStatus(rideId, 'completed');
    }
}

function updateRideStatus(rideId, status) {
    fetch('process/driver_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_ride_status',
            ride_id: rideId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (status === 'completed') {
                currentRideId = null;
                hideCurrentRide();
                loadDriverStats();
                loadRecentRides();
                alert('Ride completed successfully!');
            } else {
                checkCurrentRide();
            }
        } else {
            alert(data.message || 'Failed to update ride status.');
        }
    })
    .catch(error => {
        console.error('Error updating ride status:', error);
        alert('Network error. Please try again.');
    });
}

function showNavigation(address) {
    document.getElementById('destinationAddress').textContent = address;
    const modal = new bootstrap.Modal(document.getElementById('navigationModal'));
    modal.show();
}

function openGoogleMaps() {
    const address = document.getElementById('destinationAddress').textContent;
    const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
    window.open(url, '_blank');
}

function openWaze() {
    const address = document.getElementById('destinationAddress').textContent;
    const url = `https://waze.com/ul?q=${encodeURIComponent(address)}`;
    window.open(url, '_blank');
}

function callPassenger(phone) {
    if (phone && phone !== 'No phone') {
        window.location.href = `tel:${phone}`;
    } else {
        alert('Passenger phone number not available.');
    }
}

function refreshAvailableRides() {
    if (driverOnline && !currentRideId) {
        loadAvailableRides();
    }
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

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function generateStars(rating) {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars.push('<i class="fas fa-star text-warning"></i>');
        } else {
            stars.push('<i class="far fa-star text-muted"></i>');
        }
    }
    return stars.join('');
}
</script>

<?php include 'includes/footer.php'; ?>
