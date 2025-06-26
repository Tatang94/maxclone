<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getCurrentUser();
$pageTitle = 'Riwayat Pesanan - RideMax';
include 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header -->
    <div class="page-header bg-primary text-white p-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-white p-0 me-3" onclick="location.href='index.php'">
                <i class="fas fa-arrow-left fa-lg"></i>
            </button>
            <h5 class="mb-0">Riwayat Pesanan</h5>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs bg-white border-bottom">
        <div class="nav nav-pills px-3 py-2" role="tablist">
            <button class="nav-link active filter-btn" data-filter="all">Semua</button>
            <button class="nav-link filter-btn" data-filter="completed">Selesai</button>
            <button class="nav-link filter-btn" data-filter="cancelled">Dibatalkan</button>
            <button class="nav-link filter-btn" data-filter="pending">Berlangsung</button>
        </div>
    </div>

    <!-- Search and Sort -->
    <div class="search-sort bg-light p-3">
        <div class="row align-items-center">
            <div class="col-8">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Cari pesanan...">
                </div>
            </div>
            <div class="col-4">
                <select class="form-select" id="sortSelect">
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="price_high">Price High</option>
                    <option value="price_low">Price Low</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Orders List -->
    <div class="orders-list p-3">
        <div id="ordersContainer">
            <!-- Orders will be loaded here -->
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading your orders...</p>
            </div>
        </div>
    </div>

    <!-- Load More Button -->
    <div class="text-center p-3 d-none" id="loadMoreContainer">
        <button class="btn btn-outline-primary" id="loadMoreBtn">Load More Orders</button>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary d-none" id="reorderBtn">Book Again</button>
                <button type="button" class="btn btn-danger d-none" id="cancelOrderBtn">Cancel Order</button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this order?</p>
                <div class="mb-3">
                    <label for="cancelReason" class="form-label">Reason for cancellation:</label>
                    <select class="form-select" id="cancelReason">
                        <option value="">Select a reason</option>
                        <option value="changed_mind">Changed my mind</option>
                        <option value="found_alternative">Found alternative transport</option>
                        <option value="wrong_location">Wrong pickup location</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-3 d-none" id="otherReasonContainer">
                    <label for="otherReason" class="form-label">Please specify:</label>
                    <textarea class="form-control" id="otherReason" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Order</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">Cancel Order</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentFilter = 'all';
let currentSort = 'newest';
let currentPage = 1;
let hasMoreOrders = true;
let selectedOrderId = null;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
    
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            currentFilter = this.dataset.filter;
            currentPage = 1;
            loadOrders(true);
        });
    });
    
    // Search input
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadOrders(true);
        }, 500);
    });
    
    // Sort select
    document.getElementById('sortSelect').addEventListener('change', function() {
        currentSort = this.value;
        currentPage = 1;
        loadOrders(true);
    });
    
    // Load more button
    document.getElementById('loadMoreBtn').addEventListener('click', function() {
        currentPage++;
        loadOrders(false);
    });
    
    // Cancel reason select
    document.getElementById('cancelReason').addEventListener('change', function() {
        const otherContainer = document.getElementById('otherReasonContainer');
        if (this.value === 'other') {
            otherContainer.classList.remove('d-none');
        } else {
            otherContainer.classList.add('d-none');
        }
    });
    
    // Confirm cancel button
    document.getElementById('confirmCancelBtn').addEventListener('click', function() {
        cancelOrder();
    });
});

function loadOrders(reset = false) {
    const container = document.getElementById('ordersContainer');
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    
    if (reset) {
        container.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading your orders...</p>
            </div>
        `;
        loadMoreContainer.classList.add('d-none');
    }
    
    const params = new URLSearchParams({
        filter: currentFilter,
        sort: currentSort,
        page: currentPage,
        search: document.getElementById('searchInput').value
    });
    
    fetch(`process/fetch_order_status.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (reset) {
                    container.innerHTML = '';
                } else {
                    // Remove loading indicator
                    const loader = container.querySelector('.text-center.py-4');
                    if (loader) loader.remove();
                }
                
                if (data.orders.length === 0 && reset) {
                    container.innerHTML = `
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-receipt fa-3x mb-3 opacity-50"></i>
                            <h5>No Orders Found</h5>
                            <p>You haven't made any orders yet.</p>
                            <a href="order.php" class="btn btn-primary">Book Your First Ride</a>
                        </div>
                    `;
                } else {
                    data.orders.forEach(order => {
                        container.appendChild(createOrderCard(order));
                    });
                }
                
                hasMoreOrders = data.has_more;
                if (hasMoreOrders && data.orders.length > 0) {
                    loadMoreContainer.classList.remove('d-none');
                } else {
                    loadMoreContainer.classList.add('d-none');
                }
            } else {
                if (reset) {
                    container.innerHTML = `
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning"></i>
                            <h5>Error Loading Orders</h5>
                            <p>Please try again later.</p>
                            <button class="btn btn-primary" onclick="loadOrders(true)">Retry</button>
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            if (reset) {
                container.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-wifi fa-3x mb-3 text-danger"></i>
                        <h5>Connection Error</h5>
                        <p>Please check your internet connection.</p>
                        <button class="btn btn-primary" onclick="loadOrders(true)">Retry</button>
                    </div>
                `;
            }
        });
}

function createOrderCard(order) {
    const card = document.createElement('div');
    card.className = 'order-card bg-white rounded-3 shadow-sm p-3 mb-3';
    
    const statusColors = {
        'pending': 'warning',
        'accepted': 'info',
        'in_progress': 'primary',
        'completed': 'success',
        'cancelled': 'danger'
    };
    
    const statusIcons = {
        'pending': 'clock',
        'accepted': 'check',
        'in_progress': 'car',
        'completed': 'check-circle',
        'cancelled': 'times-circle'
    };
    
    card.innerHTML = `
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-1">
                    <h6 class="mb-0 me-2">Order #${order.id}</h6>
                    <span class="badge bg-${statusColors[order.status] || 'secondary'}">
                        <i class="fas fa-${statusIcons[order.status] || 'question'} me-1"></i>
                        ${order.status.replace('_', ' ').toUpperCase()}
                    </span>
                </div>
                <p class="text-muted small mb-1">
                    <i class="fas fa-calendar me-1"></i>
                    ${formatDate(order.created_at)}
                </p>
            </div>
            <div class="text-end">
                <div class="fw-bold text-primary">Rp ${parseInt(order.total_price).toLocaleString()}</div>
                <small class="text-muted">${order.vehicle_type.toUpperCase()}</small>
            </div>
        </div>
        
        <div class="route-info">
            <div class="route-item mb-1">
                <i class="fas fa-circle text-success me-2" style="font-size: 8px;"></i>
                <small class="text-muted">From:</small>
                <span class="ms-1">${order.pickup_location}</span>
            </div>
            <div class="route-item mb-2">
                <i class="fas fa-circle text-danger me-2" style="font-size: 8px;"></i>
                <small class="text-muted">To:</small>
                <span class="ms-1">${order.destination}</span>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center">
            <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(${order.id})">
                <i class="fas fa-eye me-1"></i> View Details
            </button>
            
            <div class="order-actions">
                ${order.status === 'completed' ? `
                    <button class="btn btn-primary btn-sm" onclick="reorderRide(${order.id})">
                        <i class="fas fa-redo me-1"></i> Book Again
                    </button>
                ` : ''}
                
                ${(order.status === 'pending' || order.status === 'accepted') ? `
                    <button class="btn btn-outline-danger btn-sm ms-1" onclick="showCancelModal(${order.id})">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    
    return card;
}

function viewOrderDetails(orderId) {
    selectedOrderId = orderId;
    
    fetch(`process/fetch_order_status.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.order) {
                showOrderDetailsModal(data.order);
            } else {
                alert('Failed to load order details.');
            }
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            alert('Network error. Please try again.');
        });
}

function showOrderDetailsModal(order) {
    const content = document.getElementById('orderDetailsContent');
    const reorderBtn = document.getElementById('reorderBtn');
    const cancelBtn = document.getElementById('cancelOrderBtn');
    
    content.innerHTML = `
        <div class="order-details">
            <div class="row mb-3">
                <div class="col-4 text-muted">Order ID:</div>
                <div class="col-8 fw-bold">#${order.id}</div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted">Status:</div>
                <div class="col-8">
                    <span class="badge bg-${getStatusColor(order.status)}">${order.status.replace('_', ' ').toUpperCase()}</span>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted">Vehicle:</div>
                <div class="col-8">${order.vehicle_type.toUpperCase()}</div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted">From:</div>
                <div class="col-8">${order.pickup_location}</div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted">To:</div>
                <div class="col-8">${order.destination}</div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted">Total Price:</div>
                <div class="col-8 fw-bold text-primary">Rp ${parseInt(order.total_price).toLocaleString()}</div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted">Payment:</div>
                <div class="col-8">${order.payment_method.toUpperCase()}</div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted">Ordered:</div>
                <div class="col-8">${formatDate(order.created_at)}</div>
            </div>
            ${order.notes ? `
                <div class="row mb-3">
                    <div class="col-4 text-muted">Notes:</div>
                    <div class="col-8">${order.notes}</div>
                </div>
            ` : ''}
            ${order.driver_name ? `
                <div class="row mb-3">
                    <div class="col-4 text-muted">Driver:</div>
                    <div class="col-8">${order.driver_name}</div>
                </div>
            ` : ''}
        </div>
    `;
    
    // Show/hide action buttons
    if (order.status === 'completed') {
        reorderBtn.classList.remove('d-none');
        cancelBtn.classList.add('d-none');
    } else if (order.status === 'pending' || order.status === 'accepted') {
        reorderBtn.classList.add('d-none');
        cancelBtn.classList.remove('d-none');
    } else {
        reorderBtn.classList.add('d-none');
        cancelBtn.classList.add('d-none');
    }
    
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    modal.show();
}

function showCancelModal(orderId) {
    selectedOrderId = orderId;
    const modal = new bootstrap.Modal(document.getElementById('cancelConfirmModal'));
    modal.show();
}

function cancelOrder() {
    const reason = document.getElementById('cancelReason').value;
    const otherReason = document.getElementById('otherReason').value;
    
    if (!reason) {
        alert('Please select a reason for cancellation.');
        return;
    }
    
    if (reason === 'other' && !otherReason.trim()) {
        alert('Please specify the reason for cancellation.');
        return;
    }
    
    const cancelReason = reason === 'other' ? otherReason : reason;
    
    fetch('process/update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: selectedOrderId,
            status: 'cancelled',
            cancel_reason: cancelReason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('cancelConfirmModal')).hide();
            loadOrders(true);
            alert('Order cancelled successfully.');
        } else {
            alert(data.message || 'Failed to cancel order.');
        }
    })
    .catch(error => {
        console.error('Error cancelling order:', error);
        alert('Network error. Please try again.');
    });
}

function reorderRide(orderId) {
    // Redirect to order page with prefilled data
    window.location.href = `order.php?reorder=${orderId}`;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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
