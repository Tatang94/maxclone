<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if merchant is logged in
if (!isset($_SESSION['merchant_id'])) {
    header('Location: merchant_login.php');
    exit();
}

$merchant_id = $_SESSION['merchant_id'];

// Get merchant data
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE id = ?");
$stmt->execute([$merchant_id]);
$merchant = $stmt->fetch();

if (!$merchant) {
    session_destroy();
    header('Location: merchant_login.php');
    exit();
}

$pageTitle = 'Dashboard Merchant - ' . $merchant['business_name'];
include 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header -->
    <div class="hero-section bg-primary text-white p-4">
        <div class="row align-items-center">
            <div class="col-8">
                <h4 class="mb-1">Halo, <?php echo htmlspecialchars($merchant['business_name']); ?>!</h4>
                <p class="mb-0 opacity-75">Kelola bisnis Anda dengan mudah</p>
            </div>
            <div class="col-4 text-end">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="merchant_profile.php"><i class="fas fa-user"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="merchant_settings.php"><i class="fas fa-cog"></i> Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="process/merchant_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-section p-3">
        <div class="row g-3" id="statsContainer">
            <!-- Stats will be loaded here -->
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions p-3">
        <h5 class="mb-3">Kelola Menu</h5>
        <div class="row g-3">
            <div class="col-6">
                <div class="action-card bg-white shadow-sm rounded-3 p-3 text-center h-100" onclick="showCategoryModal()">
                    <div class="action-icon bg-primary text-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-tags fa-lg"></i>
                    </div>
                    <h6 class="mb-1">Kelola Kategori</h6>
                    <small class="text-muted">Tambah & Edit Kategori</small>
                </div>
            </div>
            <div class="col-6">
                <div class="action-card bg-white shadow-sm rounded-3 p-3 text-center h-100" onclick="showMenuModal()">
                    <div class="action-icon bg-success text-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-utensils fa-lg"></i>
                    </div>
                    <h6 class="mb-1">Tambah Menu</h6>
                    <small class="text-muted">Menu Makanan & Minuman</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="categories-section p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Kategori Menu</h5>
            <button class="btn btn-sm btn-outline-primary" onclick="loadCategories()">
                <i class="fas fa-refresh"></i>
            </button>
        </div>
        <div id="categoriesContainer" class="row g-3">
            <!-- Categories will be loaded here -->
        </div>
    </div>

    <!-- Menu Items Section -->
    <div class="menu-section p-3 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Menu Items</h5>
            <button class="btn btn-sm btn-outline-primary" onclick="loadMenuItems()">
                <i class="fas fa-refresh"></i>
            </button>
        </div>
        
        <div class="menu-filters mb-3">
            <select class="form-select form-select-sm" id="categoryFilter" onchange="loadMenuItems()">
                <option value="">Semua Kategori</option>
            </select>
        </div>
        
        <div id="menuItemsContainer" class="row g-3">
            <!-- Menu items will be loaded here -->
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kelola Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="category_id" name="category_id">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Nama Kategori *</label>
                        <input type="text" class="form-control" id="category_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="category_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Menu Modal -->
<div class="modal fade" id="menuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah/Edit Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="menuForm" enctype="multipart/form-data">
                    <input type="hidden" id="menu_id" name="menu_id">
                    
                    <div class="mb-3">
                        <label for="menu_category" class="form-label">Kategori *</label>
                        <select class="form-select" id="menu_category" name="category_id" required>
                            <option value="">Pilih Kategori</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menu_name" class="form-label">Nama Menu *</label>
                        <input type="text" class="form-control" id="menu_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menu_description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="menu_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="menu_price" class="form-label">Harga (Rp) *</label>
                            <input type="number" class="form-control" id="menu_price" name="price" required min="0">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="menu_prep_time" class="form-label">Waktu Siap (menit)</label>
                            <input type="number" class="form-control" id="menu_prep_time" name="preparation_time" min="1" max="120" value="15">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menu_image" class="form-label">Foto Menu</label>
                        <input type="file" class="form-control" id="menu_image" name="image" accept="image/*">
                        <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 5MB</small>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="menu_available" name="is_available" checked>
                        <label class="form-check-label" for="menu_available">
                            Tersedia untuk dipesan
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Simpan Menu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadCategories();
    loadMenuItems();
    setupForms();
});

function loadStats() {
    fetch('process/merchant_process.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('statsContainer').innerHTML = `
                    <div class="col-3">
                        <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                            <div class="stat-value text-primary h5 mb-1">${stats.total_categories}</div>
                            <div class="stat-label small text-muted">Kategori</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                            <div class="stat-value text-success h5 mb-1">${stats.total_menu}</div>
                            <div class="stat-label small text-muted">Menu</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                            <div class="stat-value text-warning h5 mb-1">${stats.available_menu}</div>
                            <div class="stat-label small text-muted">Tersedia</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card bg-white rounded-3 shadow-sm p-3 text-center">
                            <div class="stat-value text-info h5 mb-1">${stats.total_orders || 0}</div>
                            <div class="stat-label small text-muted">Pesanan</div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function loadCategories() {
    fetch('process/merchant_process.php?action=get_categories')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('categoriesContainer');
                const categoryFilter = document.getElementById('categoryFilter');
                const menuCategorySelect = document.getElementById('menu_category');
                
                if (data.data.length > 0) {
                    container.innerHTML = data.data.map(category => `
                        <div class="col-6">
                            <div class="card category-card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1">${category.name}</h6>
                                            <p class="card-text small text-muted mb-2">${category.description || 'Tidak ada deskripsi'}</p>
                                            <small class="text-primary">${category.menu_count} menu</small>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="editCategory(${category.id})">Edit</a></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteCategory(${category.id})">Hapus</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    
                    // Update filter dropdown
                    categoryFilter.innerHTML = '<option value="">Semua Kategori</option>' + 
                        data.data.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
                    
                    // Update menu category dropdown
                    menuCategorySelect.innerHTML = '<option value="">Pilih Kategori</option>' + 
                        data.data.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
                } else {
                    container.innerHTML = `
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h6>Belum Ada Kategori</h6>
                            <p class="text-muted">Tambahkan kategori untuk mengelompokkan menu</p>
                            <button class="btn btn-primary btn-sm" onclick="showCategoryModal()">
                                <i class="fas fa-plus"></i> Tambah Kategori
                            </button>
                        </div>
                    `;
                }
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

function loadMenuItems() {
    const categoryId = document.getElementById('categoryFilter').value;
    const url = categoryId ? 
        `process/merchant_process.php?action=get_menu_items&category_id=${categoryId}` : 
        'process/merchant_process.php?action=get_menu_items';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('menuItemsContainer');
                
                if (data.data.length > 0) {
                    container.innerHTML = data.data.map(item => `
                        <div class="col-6">
                            <div class="card menu-item-card h-100">
                                <div class="position-relative">
                                    <img src="${item.image_path || 'https://via.placeholder.com/150x100/6c757d/ffffff?text=No+Image'}" 
                                         class="card-img-top" alt="${item.name}" style="height: 120px; object-fit: cover;">
                                    <span class="badge ${item.is_available ? 'bg-success' : 'bg-secondary'} position-absolute top-0 end-0 m-2">
                                        ${item.is_available ? 'Tersedia' : 'Tidak Tersedia'}
                                    </span>
                                </div>
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1 text-truncate">${item.name}</h6>
                                    <p class="card-text small text-muted mb-1 text-truncate">${item.description || 'Tidak ada deskripsi'}</p>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-primary fw-bold">Rp ${parseInt(item.price).toLocaleString('id-ID')}</span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> ${item.preparation_time}m
                                        </small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary flex-fill" onclick="editMenu(${item.id})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm ${item.is_available ? 'btn-outline-warning' : 'btn-outline-success'}" onclick="toggleMenuAvailability(${item.id}, ${item.is_available})">
                                            <i class="fas ${item.is_available ? 'fa-eye-slash' : 'fa-eye'}"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteMenu(${item.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                            <h6>Belum Ada Menu</h6>
                            <p class="text-muted">Tambahkan menu untuk mulai berjualan</p>
                            <button class="btn btn-primary btn-sm" onclick="showMenuModal()">
                                <i class="fas fa-plus"></i> Tambah Menu
                            </button>
                        </div>
                    `;
                }
            }
        })
        .catch(error => console.error('Error loading menu items:', error));
}

function showCategoryModal(categoryId = null) {
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    const form = document.getElementById('categoryForm');
    
    if (categoryId) {
        // Edit mode - load category data
        fetch(`process/merchant_process.php?action=get_category&id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const category = data.data;
                    document.getElementById('category_id').value = category.id;
                    document.getElementById('category_name').value = category.name;
                    document.getElementById('category_description').value = category.description || '';
                }
            });
    } else {
        // Add mode - clear form
        form.reset();
        document.getElementById('category_id').value = '';
    }
    
    modal.show();
}

function showMenuModal(menuId = null) {
    const modal = new bootstrap.Modal(document.getElementById('menuModal'));
    const form = document.getElementById('menuForm');
    
    if (menuId) {
        // Edit mode - load menu data
        fetch(`process/merchant_process.php?action=get_menu_item&id=${menuId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const menu = data.data;
                    document.getElementById('menu_id').value = menu.id;
                    document.getElementById('menu_category').value = menu.category_id;
                    document.getElementById('menu_name').value = menu.name;
                    document.getElementById('menu_description').value = menu.description || '';
                    document.getElementById('menu_price').value = menu.price;
                    document.getElementById('menu_prep_time').value = menu.preparation_time;
                    document.getElementById('menu_available').checked = menu.is_available;
                }
            });
    } else {
        // Add mode - clear form
        form.reset();
        document.getElementById('menu_id').value = '';
        document.getElementById('menu_available').checked = true;
    }
    
    modal.show();
}

function editCategory(categoryId) {
    showCategoryModal(categoryId);
}

function editMenu(menuId) {
    showMenuModal(menuId);
}

function deleteCategory(categoryId) {
    if (confirm('Yakin hapus kategori ini? Menu dalam kategori ini akan ikut terhapus.')) {
        fetch('process/merchant_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_category&category_id=${categoryId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCategories();
                loadMenuItems();
                loadStats();
            } else {
                alert(data.message);
            }
        });
    }
}

function deleteMenu(menuId) {
    if (confirm('Yakin hapus menu ini?')) {
        fetch('process/merchant_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_menu_item&menu_id=${menuId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadMenuItems();
                loadStats();
            } else {
                alert(data.message);
            }
        });
    }
}

function toggleMenuAvailability(menuId, currentStatus) {
    const newStatus = !currentStatus;
    fetch('process/merchant_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=toggle_menu_availability&menu_id=${menuId}&is_available=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadMenuItems();
            loadStats();
        } else {
            alert(data.message);
        }
    });
}

function setupForms() {
    // Category form
    document.getElementById('categoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save_category');
        
        fetch('process/merchant_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
                loadCategories();
                loadStats();
                this.reset();
            } else {
                alert(data.message);
            }
        });
    });
    
    // Menu form
    document.getElementById('menuForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save_menu_item');
        
        fetch('process/merchant_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('menuModal')).hide();
                loadMenuItems();
                loadStats();
                this.reset();
            } else {
                alert(data.message);
            }
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>