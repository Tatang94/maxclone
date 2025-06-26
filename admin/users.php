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

$pageTitle = 'Kelola Pengguna - Admin RideMax';
include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Admin Header -->
    <div class="admin-header bg-dark text-white p-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-white p-0 me-3" onclick="location.href='dashboard.php'">
                <i class="fas fa-arrow-left fa-lg"></i>
            </button>
            <h5 class="mb-0">Kelola Pengguna</h5>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section bg-light p-3">
        <div class="row align-items-center">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari pengguna...">
                </div>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <select class="form-select" id="userTypeFilter">
                    <option value="">Semua Jenis Pengguna</option>
                    <option value="user">Penumpang</option>
                    <option value="driver">Driver</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" onclick="loadUsers()">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="users-section p-3">
        <div class="bg-white rounded-3 shadow-sm">
            <div class="table-header p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Users List</h6>
                    <button class="btn btn-success btn-sm" onclick="showAddUserModal()">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <!-- Users will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <div class="table-footer p-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div id="usersCount" class="text-muted">Loading...</div>
                    <nav id="usersPagination">
                        <!-- Pagination will be loaded here -->
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" id="userId" name="user_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="userName" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="userName" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="userEmail" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="userEmail" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="userPhone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="userPhone" name="phone" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="userType" class="form-label">User Type *</label>
                            <select class="form-select" id="userType" name="user_type" required>
                                <option value="">Select type</option>
                                <option value="user">Passenger</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row" id="passwordSection">
                        <div class="col-md-6 mb-3">
                            <label for="userPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="userPassword" name="password">
                            <div class="form-text">Leave blank to keep current password</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="userStatus" class="form-label">Status</label>
                            <select class="form-select" id="userStatus" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Driver Fields -->
                    <div id="driverFields" class="d-none">
                        <hr>
                        <h6>Driver Information</h6>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="isDriver" name="is_driver">
                            <label class="form-check-label" for="isDriver">
                                Enable Driver Account
                            </label>
                        </div>
                        
                        <div id="driverDetails" class="d-none">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="licenseNumber" class="form-label">License Number</label>
                                    <input type="text" class="form-control" id="licenseNumber" name="license_number">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="vehiclePlate" class="form-label">Vehicle Plate</label>
                                    <input type="text" class="form-control" id="vehiclePlate" name="vehicle_plate">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="vehicleMake" class="form-label">Vehicle Make</label>
                                    <input type="text" class="form-control" id="vehicleMake" name="vehicle_make">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="vehicleModel" class="form-label">Vehicle Model</label>
                                    <input type="text" class="form-control" id="vehicleModel" name="vehicle_model">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveUserBtn">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- User details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    All user data including orders and history will be permanently deleted.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete User</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let selectedUserId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    
    // Search functionality
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadUsers();
        }, 500);
    });
    
    // Filter change events
    document.getElementById('userTypeFilter').addEventListener('change', () => {
        currentPage = 1;
        loadUsers();
    });
    
    document.getElementById('statusFilter').addEventListener('change', () => {
        currentPage = 1;
        loadUsers();
    });
    
    // User form submission
    document.getElementById('userForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveUser();
    });
    
    // Driver checkbox toggle
    document.getElementById('isDriver').addEventListener('change', function() {
        const driverDetails = document.getElementById('driverDetails');
        if (this.checked) {
            driverDetails.classList.remove('d-none');
        } else {
            driverDetails.classList.add('d-none');
        }
    });
    
    // Delete confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        deleteUser();
    });
});

function loadUsers() {
    const search = document.getElementById('searchInput').value;
    const userType = document.getElementById('userTypeFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams({
        action: 'get_users',
        page: currentPage,
        search: search,
        user_type: userType,
        status: status
    });
    
    fetch(`../process/admin_process.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderUsersTable(data.users);
                renderPagination(data.pagination);
                document.getElementById('usersCount').textContent = 
                    `Showing ${data.pagination.start}-${data.pagination.end} of ${data.pagination.total} users`;
            } else {
                document.getElementById('usersTableBody').innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                            Error loading users: ${data.message || 'Unknown error'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            document.getElementById('usersTableBody').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-wifi fa-2x mb-2"></i><br>
                        Connection error. Please try again.
                    </td>
                </tr>
            `;
        });
}

function renderUsersTable(users) {
    const tbody = document.getElementById('usersTableBody');
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-users fa-2x mb-2"></i><br>
                    No users found
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>#${user.id}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="user-avatar bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 12px;">
                        ${user.name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div class="fw-semibold">${escapeHtml(user.name)}</div>
                        ${user.is_driver == 1 ? '<small class="text-success"><i class="fas fa-bicycle me-1"></i>Pengendara Sepeda</small>' : ''}
                    </div>
                </div>
            </td>
            <td>${escapeHtml(user.email)}</td>
            <td>${escapeHtml(user.phone || 'N/A')}</td>
            <td>
                <span class="badge bg-${getUserTypeBadgeColor(user.user_type)}">${user.user_type.toUpperCase()}</span>
            </td>
            <td>
                <span class="badge bg-${getStatusBadgeColor(user.status || 'active')}">${(user.status || 'active').toUpperCase()}</span>
            </td>
            <td>
                <small>${formatDate(user.created_at)}</small>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="viewUser(${user.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-warning" onclick="editUser(${user.id})" title="Edit User">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="showDeleteModal(${user.id})" title="Delete User">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderPagination(pagination) {
    const nav = document.getElementById('usersPagination');
    
    if (pagination.total_pages <= 1) {
        nav.innerHTML = '';
        return;
    }
    
    let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
    
    // Previous button
    if (pagination.current_page > 1) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="goToPage(${pagination.current_page - 1})">Previous</a>
            </li>
        `;
    }
    
    // Page numbers
    for (let i = Math.max(1, pagination.current_page - 2); 
         i <= Math.min(pagination.total_pages, pagination.current_page + 2); 
         i++) {
        paginationHtml += `
            <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${i})">${i}</a>
            </li>
        `;
    }
    
    // Next button
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
    loadUsers();
}

function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add New User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('passwordSection').style.display = 'block';
    document.getElementById('userPassword').required = true;
    
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}

function editUser(userId) {
    fetch(`../process/admin_process.php?action=get_user&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                const user = data.user;
                
                document.getElementById('userModalTitle').textContent = 'Edit User';
                document.getElementById('userId').value = user.id;
                document.getElementById('userName').value = user.name;
                document.getElementById('userEmail').value = user.email;
                document.getElementById('userPhone').value = user.phone || '';
                document.getElementById('userType').value = user.user_type;
                document.getElementById('userStatus').value = user.status || 'active';
                document.getElementById('userPassword').required = false;
                
                // Driver fields
                if (user.is_driver == 1) {
                    document.getElementById('isDriver').checked = true;
                    document.getElementById('driverDetails').classList.remove('d-none');
                    document.getElementById('licenseNumber').value = user.license_number || '';
                    document.getElementById('vehiclePlate').value = user.vehicle_plate || '';
                    document.getElementById('vehicleMake').value = user.vehicle_make || '';
                    document.getElementById('vehicleModel').value = user.vehicle_model || '';
                }
                
                const modal = new bootstrap.Modal(document.getElementById('userModal'));
                modal.show();
            } else {
                alert('Failed to load user details.');
            }
        })
        .catch(error => {
            console.error('Error loading user:', error);
            alert('Network error. Please try again.');
        });
}

function viewUser(userId) {
    fetch(`../process/admin_process.php?action=get_user_details&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                showUserDetails(data.user);
            } else {
                alert('Failed to load user details.');
            }
        })
        .catch(error => {
            console.error('Error loading user details:', error);
            alert('Network error. Please try again.');
        });
}

function showUserDetails(user) {
    const content = document.getElementById('userDetailsContent');
    
    content.innerHTML = `
        <div class="user-details">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h5>${escapeHtml(user.name)}</h5>
                    <p class="text-muted mb-1">${escapeHtml(user.email)}</p>
                    <p class="text-muted">${escapeHtml(user.phone || 'No phone number')}</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-${getUserTypeBadgeColor(user.user_type)} fs-6">${user.user_type.toUpperCase()}</span><br>
                    <span class="badge bg-${getStatusBadgeColor(user.status || 'active')} mt-1">${(user.status || 'active').toUpperCase()}</span>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>Account Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>User ID:</td>
                            <td>#${user.id}</td>
                        </tr>
                        <tr>
                            <td>Member Since:</td>
                            <td>${formatDate(user.created_at)}</td>
                        </tr>
                        <tr>
                            <td>Last Updated:</td>
                            <td>${formatDate(user.updated_at)}</td>
                        </tr>
                        <tr>
                            <td>Total Orders:</td>
                            <td>${user.total_orders || 0}</td>
                        </tr>
                    </table>
                </div>
                
                ${user.is_driver == 1 ? `
                <div class="col-md-6">
                    <h6>Driver Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>License Number:</td>
                            <td>${escapeHtml(user.license_number || 'N/A')}</td>
                        </tr>
                        <tr>
                            <td>Vehicle Plate:</td>
                            <td>${escapeHtml(user.vehicle_plate || 'N/A')}</td>
                        </tr>
                        <tr>
                            <td>Vehicle:</td>
                            <td>${escapeHtml((user.vehicle_make || '') + ' ' + (user.vehicle_model || '') || 'N/A')}</td>
                        </tr>
                        <tr>
                            <td>Status:</td>
                            <td>
                                <span class="badge bg-${user.is_online ? 'success' : 'secondary'}">
                                    ${user.is_online ? 'Online' : 'Offline'}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                ` : ''}
            </div>
            
            ${user.recent_orders && user.recent_orders.length > 0 ? `
            <div class="mt-4">
                <h6>Recent Orders</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${user.recent_orders.map(order => `
                                <tr>
                                    <td>#${order.id}</td>
                                    <td>${formatDate(order.created_at)}</td>
                                    <td class="small">${escapeHtml(order.pickup_location)} → ${escapeHtml(order.destination)}</td>
                                    <td><span class="badge bg-${getStatusBadgeColor(order.status)}">${order.status.toUpperCase()}</span></td>
                                    <td>Rp ${parseInt(order.total_price).toLocaleString()}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            ` : ''}
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    modal.show();
}

function saveUser() {
    const formData = new FormData(document.getElementById('userForm'));
    formData.append('action', 'save_user');
    
    const btn = document.getElementById('saveUserBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    fetch('../process/admin_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            loadUsers();
            alert('User saved successfully!');
        } else {
            alert(data.message || 'Failed to save user.');
        }
    })
    .catch(error => {
        console.error('Error saving user:', error);
        alert('Network error. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Save User';
    });
}

function showDeleteModal(userId) {
    selectedUserId = userId;
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}

function deleteUser() {
    if (!selectedUserId) return;
    
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
    
    fetch('../process/admin_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete_user',
            user_id: selectedUserId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('deleteUserModal')).hide();
            loadUsers();
            alert('User deleted successfully!');
        } else {
            alert(data.message || 'Failed to delete user.');
        }
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        alert('Network error. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Delete User';
        selectedUserId = null;
    });
}

// Utility functions
function getUserTypeBadgeColor(type) {
    const colors = {
        'user': 'primary',
        'driver': 'success',
        'admin': 'danger'
    };
    return colors[type] || 'secondary';
}

function getStatusBadgeColor(status) {
    const colors = {
        'active': 'success',
        'inactive': 'secondary',
        'suspended': 'danger',
        'pending': 'warning',
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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include '../includes/footer.php'; ?>
