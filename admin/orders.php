<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Auto-login admin
if (!isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@ridemax.com' AND user_type = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    if ($admin) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_email'] = $admin['email'];
        $_SESSION['user_type'] = $admin['user_type'];
        $_SESSION['is_driver'] = false;
        $_SESSION['login_time'] = time();
    }
}

$pageTitle = 'Kelola Pesanan - Admin RideMax';
include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Admin Header -->
    <div class="admin-header bg-dark text-white p-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-white p-0 me-3" onclick="location.href='dashboard.php'">
                <i class="fas fa-arrow-left fa-lg"></i>
            </button>
            <h5 class="mb-0">Kelola Pesanan</h5>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section bg-light p-3">
        <div class="row align-items-center">
            <div class="col-md-3 mb-2 mb-md-0">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari pesanan...">
                </div>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <select class="form-select" id="statusFilter">
                    <option value="">Semua Status</option>
                    <option value="pending">Menunggu</option>
                    <option value="accepted">Accepted</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <select class="form-select" id="vehicleFilter">
                    <option value="">All Vehicles</option>
                    <option value="economy">Economy</option>
                    <option value="comfort">Comfort</option>
                    <option value="premium">Premium</option>
                </select>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <input type="date" class="form-control" id="dateFilter">
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <select class="form-select" id="sortFilter">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="price_high">Price High</option>
                    <option value="price_low">Price Low</option>
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100" onclick="loadOrders()">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats p-3">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat-card bg-warning text-white rounded-3 p-3 text-center">
                    <h4 id="pendingCount">0</h4>
                    <small>Pending</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-info text-white rounded-3 p-3 text-center">
                    <h4 id="activeCount">0</h4>
                    <small>Active</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-success text-white rounded-3 p-3 text-center">
                    <h4 id="completedCount">0</h4>
                    <small>Completed</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-danger text-white rounded-3 p-3 text-center">
                    <h4 id="cancelledCount">0</h4>
                    <small>Cancelled</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="orders-section p-3">
        <div class="bg-white rounded-3 shadow-sm">
            <div class="table-header p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Orders List</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="exportOrders()">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="loadOrders()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Driver</th>
                            <th>Route</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <!-- Orders will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <div class="table-footer p-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div id="ordersCount" class="text-muted">Loading...</div>
                    <nav id="ordersPagination">
                        <!-- Pagination will be loaded here -->
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
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
                <div id="orderActions">
                    <!-- Action buttons will be added here based on order status -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" id="updateOrderId" name="order_id">
                    
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">New Status</label>
                        <select class="form-select" id="newStatus" name="status" required>
                            <option value="">Select status</option>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="statusReason" class="form-label">Reason (Optional)</label>
                        <textarea class="form-control" id="statusReason" name="reason" rows="3" placeholder="Enter reason for status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="updateStatusBtn">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Driver Modal -->
<div class="modal fade" id="assignDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignDriverForm">
                <div class="modal-body">
                    <input type="hidden" id="assignOrderId" name="order_id">
                    
                    <div class="mb-3">
                        <label for="driverSelect" class="form-label">Select Driver</label>
                        <select class="form-select" id="driverSelect" name="driver_id" required>
                            <option value="">Loading drivers...</option>
                        </select>
                    </div>
                    
                    <div id="driverInfo" class="d-none">
                        <!-- Driver info will be displayed here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="assignDriverBtn">Assign Driver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let selectedOrderId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
    loadOrderStats();
    
    // Search functionality
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadOrders();
        }, 500);
    });
    
    // Filter change events
    ['statusFilter', 'vehicleFilter', 'dateFilter', 'sortFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            currentPage = 1;
            loadOrders();
        });
    });
    
    // Form submissions
    document.getElementById('updateStatusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateOrderStatus();
    });
    
    document.getElementById('assignDriverForm').addEventListener('submit', function(e) {
        e.preventDefault();
        assignDriver();
    });
    
    // Auto-refresh every 30 seconds
    setInterval(() => {
        loadOrders();
        loadOrderStats();
    }, 30000);
});

function loadOrders() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const vehicle = document.getElementById('vehicleFilter').value;
    const date = document.getElementById('dateFilter').value;
    const sort = document.getElementById('sortFilter').value;
    
    const params = new URLSearchParams({
        action: 'get_orders',
        page: currentPage,
        search: search,
        status: status,
        vehicle_type: vehicle,
        date: date,
        sort: sort
    });
    
    fetch(`../process/admin_process.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderOrdersTable(data.orders);
                renderPagination(data.pagination);
                document.getElementById('ordersCount').textContent = 
                    `Showing ${data.pagination.start}-${data.pagination.end} of ${data.pagination.total} orders`;
            } else {
                document.getElementById('ordersTableBody').innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                            Error loading orders: ${data.message || 'Unknown error'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            document.getElementById('ordersTableBody').innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-wifi fa-2x mb-2"></i><br>
                        Connection error. Please try again.
                    </td>
                </tr>
            `;
        });
}

function loadOrderStats() {
    fetch('../process/admin_process.php?action=get_order_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('pendingCount').textContent = data.stats.pending || 0;
                document.getElementById('activeCount').textContent = (data.stats.accepted || 0) + (data.stats.in_progress || 0);
                document.getElementById('completedCount').textContent = data.stats.completed || 0;
                document.getElementById('cancelledCount').textContent = data.stats.cancelled || 0;
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function renderOrdersTable(orders) {
    const tbody = document.getElementById('ordersTableBody');
    
    if (orders.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    <i class="fas fa-receipt fa-2x mb-2"></i><br>
                    No orders found
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td>
                <div class="fw-bold">#${order.id}</div>
                <small class="text-muted">${formatTimeAgo(order.created_at)}</small>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="user-avatar bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 12px;">
                        ${order.user_name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div class="fw-semibold">${escapeHtml(order.user_name)}</div>
                        <small class="text-muted">${escapeHtml(order.user_email)}</small>
                    </div>
                </div>
            </td>
            <td>
                ${order.driver_name ? `
                    <div class="d-flex align-items-center">
                        <div class="user-avatar bg-success text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 12px;">
                            ${order.driver_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div class="fw-semibold">${escapeHtml(order.driver_name)}</div>
                            <small class="text-muted">${escapeHtml(order.driver_phone || '')}</small>
                        </div>
                    </div>
                ` : `
                    <span class="text-muted">
                        <i class="fas fa-user-slash me-1"></i>
                        Not assigned
                    </span>
                `}
            </td>
            <td>
                <div class="route-info">
                    <div class="route-item mb-1 small">
                        <i class="fas fa-circle text-success me-1" style="font-size: 6px;"></i>
                        ${escapeHtml(order.pickup_location)}
                    </div>
                    <div class="route-item small">
                        <i class="fas fa-circle text-danger me-1" style="font-size: 6px;"></i>
                        ${escapeHtml(order.destination)}
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-light text-dark">${order.vehicle_type.toUpperCase()}</span>
            </td>
            <td>
                <span class="badge bg-${getStatusBadgeColor(order.status)}">${order.status.replace('_', ' ').toUpperCase()}</span>
            </td>
            <td>
                <div class="fw-bold">Rp ${parseInt(order.total_price).toLocaleString()}</div>
                <small class="text-muted">${order.payment_method.toUpperCase()}</small>
            </td>
            <td>
                <div>${formatDate(order.created_at)}</div>
                ${order.completed_at ? `<small class="text-success">Completed: ${formatDate(order.completed_at)}</small>` : ''}
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="viewOrder(${order.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-warning" onclick="showUpdateStatusModal(${order.id})" title="Update Status">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${!order.driver_id && (order.status === 'pending' || order.status === 'accepted') ? `
                        <button class="btn btn-outline-success" onclick="showAssignDriverModal(${order.id})" title="Assign Driver">
                            <i class="fas fa-user-plus"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function renderPagination(pagination) {
    const nav = document.getElementById('ordersPagination');
    
    if (pagination.total_pages <= 1) {
        nav.innerHTML = '';
        return;
    }
    
    let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
    
    if (pagination.current_page > 1) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="goToPage(${pagination.current_page - 1})">Previous</a>
            </li>
        `;
    }
    
    for (let i = Math.max(1, pagination.current_page - 2); 
         i <= Math.min(pagination.total_pages, pagination.current_page + 2); 
         i++) {
        paginationHtml += `
            <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${i})">${i}</a>
            </li>
        `;
    }
    
    if (pagination.current_page < pagination.total_pages) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="goToPage(${pagination.current_page + 1})">Next</a>
            </li>
        `;
    }
    
    paginationHtml += '</ul>';
    nav.innerHTML = paginationHtml;
}

function goToPage(page) {
    currentPage = page;
    loadOrders();
}

function viewOrder(orderId) {
    fetch(`../process/admin_process.php?action=get_order_details&order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.order) {
                showOrderDetails(data.order);
            } else {
                alert('Failed to load order details.');
            }
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            alert('Network error. Please try again.');
        });
}

function showOrderDetails(order) {
    const content = document.getElementById('orderDetailsContent');
    const actions = document.getElementById('orderActions');
    
    content.innerHTML = `
        <div class="order-details">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h5>Order #${order.id}</h5>
                    <p class="text-muted mb-0">Created: ${formatDate(order.created_at)}</p>
                    ${order.completed_at ? `<p class="text-success mb-0">Completed: ${formatDate(order.completed_at)}</p>` : ''}
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-${getStatusBadgeColor(order.status)} fs-6">${order.status.replace('_', ' ').toUpperCase()}</span><br>
                    <div class="h4 mt-2 text-primary">Rp ${parseInt(order.total_price).toLocaleString()}</div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>Customer Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Name:</td>
                            <td>${escapeHtml(order.user_name)}</td>
                        </tr>
                        <tr>
                            <td>Email:</td>
                            <td>${escapeHtml(order.user_email)}</td>
                        </tr>
                        <tr>
                            <td>Phone:</td>
                            <td>${escapeHtml(order.user_phone || 'N/A')}</td>
                        </tr>
                    </table>
                    
                    <h6 class="mt-3">Trip Details</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Pickup:</td>
                            <td>${escapeHtml(order.pickup_location)}</td>
                        </tr>
                        <tr>
                            <td>Destination:</td>
                            <td>${escapeHtml(order.destination)}</td>
                        </tr>
                        <tr>
                            <td>Vehicle Type:</td>
                            <td>${order.vehicle_type.toUpperCase()}</td>
                        </tr>
                        <tr>
                            <td>Payment Method:</td>
                            <td>${order.payment_method.toUpperCase()}</td>
                        </tr>
                        ${order.notes ? `
                        <tr>
                            <td>Notes:</td>
                            <td>${escapeHtml(order.notes)}</td>
                        </tr>
                        ` : ''}
                    </table>
                </div>
                
                <div class="col-md-6">
                    ${order.driver_name ? `
                    <h6>Driver Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Name:</td>
                            <td>${escapeHtml(order.driver_name)}</td>
                        </tr>
                        <tr>
                            <td>Phone:</td>
                            <td>${escapeHtml(order.driver_phone || 'N/A')}</td>
                        </tr>
                        <tr>
                            <td>Vehicle:</td>
                            <td>${escapeHtml((order.vehicle_make || '') + ' ' + (order.vehicle_model || '') || 'N/A')}</td>
                        </tr>
                        <tr>
                            <td>Plate Number:</td>
                            <td>${escapeHtml(order.vehicle_plate || 'N/A')}</td>
                        </tr>
                    </table>
                    ` : `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-user-slash fa-3x mb-2"></i>
                        <p>No driver assigned</p>
                    </div>
                    `}
                    
                    <h6 class="mt-3">Order Timeline</h6>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Order Created</div>
                                <small class="text-muted">${formatDate(order.created_at)}</small>
                            </div>
                        </div>
                        ${order.accepted_at ? `
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Order Accepted</div>
                                <small class="text-muted">${formatDate(order.accepted_at)}</small>
                            </div>
                        </div>
                        ` : ''}
                        ${order.started_at ? `
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Trip Started</div>
                                <small class="text-muted">${formatDate(order.started_at)}</small>
                            </div>
                        </div>
                        ` : ''}
                        ${order.completed_at ? `
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Trip Completed</div>
                                <small class="text-muted">${formatDate(order.completed_at)}</small>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Set action buttons based on order status
    actions.innerHTML = '';
    if (order.status === 'pending') {
        actions.innerHTML = `
            <button type="button" class="btn btn-success me-2" onclick="quickUpdateStatus(${order.id}, 'accepted')">Accept Order</button>
            <button type="button" class="btn btn-danger" onclick="quickUpdateStatus(${order.id}, 'cancelled')">Cancel Order</button>
        `;
    } else if (order.status === 'accepted') {
        actions.innerHTML = `
            <button type="button" class="btn btn-primary me-2" onclick="quickUpdateStatus(${order.id}, 'in_progress')">Start Trip</button>
            <button type="button" class="btn btn-danger" onclick="quickUpdateStatus(${order.id}, 'cancelled')">Cancel Order</button>
        `;
    } else if (order.status === 'in_progress') {
        actions.innerHTML = `
            <button type="button" class="btn btn-success" onclick="quickUpdateStatus(${order.id}, 'completed')">Complete Trip</button>
        `;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    modal.show();
}

function showUpdateStatusModal(orderId) {
    selectedOrderId = orderId;
    document.getElementById('updateOrderId').value = orderId;
    document.getElementById('newStatus').value = '';
    document.getElementById('statusReason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}

function updateOrderStatus() {
    const formData = new FormData(document.getElementById('updateStatusForm'));
    formData.append('action', 'update_order_status');
    
    const btn = document.getElementById('updateStatusBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    
    fetch('../process/admin_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('updateStatusModal')).hide();
            loadOrders();
            loadOrderStats();
            alert('Order status updated successfully!');
        } else {
            alert(data.message || 'Failed to update order status.');
        }
    })
    .catch(error => {
        console.error('Error updating order status:', error);
        alert('Network error. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Update Status';
    });
}

function quickUpdateStatus(orderId, status) {
    if (confirm(`Are you sure you want to update this order status to "${status.replace('_', ' ')}"?`)) {
        fetch('../process/admin_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_order_status',
                order_id: orderId,
                status: status,
                reason: `Updated by admin`
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal')).hide();
                loadOrders();
                loadOrderStats();
                alert('Order status updated successfully!');
            } else {
                alert(data.message || 'Failed to update order status.');
            }
        })
        .catch(error => {
            console.error('Error updating order status:', error);
            alert('Network error. Please try again.');
        });
    }
}

function showAssignDriverModal(orderId) {
    selectedOrderId = orderId;
    document.getElementById('assignOrderId').value = orderId;
    
    // Load available drivers
    fetch('../process/admin_process.php?action=get_available_drivers')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('driverSelect');
            
            if (data.success && data.drivers.length > 0) {
                select.innerHTML = '<option value="">Select a driver</option>' +
                    data.drivers.map(driver => `
                        <option value="${driver.id}" data-name="${escapeHtml(driver.name)}" data-phone="${escapeHtml(driver.phone || '')}" data-vehicle="${escapeHtml((driver.vehicle_make || '') + ' ' + (driver.vehicle_model || ''))}">
                            ${escapeHtml(driver.name)} - ${escapeHtml((driver.vehicle_make || '') + ' ' + (driver.vehicle_model || '') || 'Vehicle info missing')}
                        </option>
                    `).join('');
            } else {
                select.innerHTML = '<option value="">No available drivers</option>';
            }
        })
        .catch(error => {
            console.error('Error loading drivers:', error);
            document.getElementById('driverSelect').innerHTML = '<option value="">Error loading drivers</option>';
        });
    
    const modal = new bootstrap.Modal(document.getElementById('assignDriverModal'));
    modal.show();
}

function assignDriver() {
    const formData = new FormData(document.getElementById('assignDriverForm'));
    formData.append('action', 'assign_driver');
    
    const btn = document.getElementById('assignDriverBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning...';
    
    fetch('../process/admin_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('assignDriverModal')).hide();
            loadOrders();
            alert('Driver assigned successfully!');
        } else {
            alert(data.message || 'Failed to assign driver.');
        }
    })
    .catch(error => {
        console.error('Error assigning driver:', error);
        alert('Network error. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Assign Driver';
    });
}

function exportOrders() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const vehicle = document.getElementById('vehicleFilter').value;
    const date = document.getElementById('dateFilter').value;
    
    const params = new URLSearchParams({
        action: 'export_orders',
        search: search,
        status: status,
        vehicle_type: vehicle,
        date: date
    });
    
    window.open(`../process/admin_process.php?${params}`, '_blank');
}

// Utility functions
function getStatusBadgeColor(status) {
    const colors = {
        'pending': 'warning',
        'accepted': 'info',
        'in_progress': 'primary',
        'completed': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include '../includes/footer.php'; ?>
