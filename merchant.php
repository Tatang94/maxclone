<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Get user's merchant info
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt->execute([$user_id]);
$merchant = $stmt->fetch();

// If no merchant account, create one
if (!$merchant) {
    $create_merchant = $pdo->prepare("INSERT INTO merchants (user_id, business_name, business_address, business_phone, business_category) VALUES (?, ?, ?, ?, ?)");
    $create_merchant->execute([
        $user_id,
        $user['name'] . "'s Kitchen",
        'Alamat belum diisi',
        $user['phone'] ?? '',
        'Makanan & Minuman'
    ]);
    
    // Get the newly created merchant
    $stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $merchant = $stmt->fetch();
}

// Get merchant's menu items
$menu_items = [];
if ($merchant) {
    $stmt = $pdo->prepare("SELECT mi.*, fc.name as category_name FROM menu_items mi LEFT JOIN food_categories fc ON mi.category_id = fc.id WHERE mi.merchant_id = ? ORDER BY mi.created_at DESC");
    $stmt->execute([$merchant['id']]);
    $menu_items = $stmt->fetchAll();
}

// Get food categories
$categories = [];
if ($merchant) {
    $stmt = $pdo->prepare("SELECT * FROM food_categories WHERE merchant_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$merchant['id']]);
    $categories = $stmt->fetchAll();
}

$title = "Merchant - Kelola Menu Makanan";
$hideNavigation = false;
include 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header -->
    <div class="bg-primary text-white p-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-1">🍽️ <?php echo htmlspecialchars($merchant['business_name'] ?? 'Merchant Panel'); ?></h4>
                <p class="mb-0 opacity-75">Kelola menu makanan Anda</p>
            </div>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                <i class="fas fa-plus"></i> Tambah Menu
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 p-3">
        <div class="col-6">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-utensils fa-2x mb-2"></i>
                    <h5><?php echo count($menu_items); ?></h5>
                    <small>Total Menu</small>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h5><?php echo count(array_filter($menu_items, function($item) { return $item['is_available']; })); ?></h5>
                    <small>Menu Tersedia</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu List -->
    <div class="p-3">
        <h5 class="mb-3">Daftar Menu Makanan</h5>
        
        <?php if (empty($menu_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                <h6>Belum Ada Menu</h6>
                <p class="text-muted">Tambahkan menu makanan pertama Anda</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                    <i class="fas fa-plus"></i> Tambah Menu
                </button>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($menu_items as $item): ?>
                    <div class="col-12">
                        <div class="card food-item-card">
                            <div class="row g-0">
                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($item['image_path'] ?: 'https://via.placeholder.com/150x100/6c757d/ffffff?text=No+Image'); ?>" 
                                         class="img-fluid rounded-start h-100 object-fit-cover" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="col-8">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-primary">
                                                        <?php echo htmlspecialchars($item['category_name'] ?: 'Umum'); ?>
                                                    </span>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> <?php echo $item['preparation_time']; ?> menit
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="h6 text-primary mb-1">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-sm" onclick="editMenuItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-<?php echo $item['is_available'] ? 'success' : 'secondary'; ?> btn-sm" 
                                                            onclick="toggleAvailability(<?php echo $item['id']; ?>, <?php echo $item['is_available'] ? 'false' : 'true'; ?>)">
                                                        <i class="fas fa-<?php echo $item['is_available'] ? 'eye' : 'eye-slash'; ?>"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteMenuItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Menu Modal -->
<div class="modal fade" id="addMenuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Menu Makanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addMenuForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_menu_item">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Harga (Rp)</label>
                                <input type="number" class="form-control" name="price" required min="1000">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Waktu Persiapan (menit)</label>
                        <input type="number" class="form-control" name="preparation_time" value="15" min="1" max="120">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Upload Foto Menu</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <small class="form-text text-muted">Upload gambar makanan (JPG, PNG, GIF, maksimal 5MB)</small>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus"></i> Tambah Kategori Baru
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        Tambah Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCategoryForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add menu form submission
document.getElementById('addMenuForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    
    const formData = new FormData(this);
    
    fetch('process/merchant_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addMenuModal')).hide();
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat menambah menu', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
    });
});

// Add category form submission
document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('process/merchant_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat menambah kategori', 'error');
    });
});

// Toggle menu item availability
function toggleAvailability(itemId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_menu_item');
    formData.append('item_id', itemId);
    formData.append('is_available', newStatus);
    
    fetch('process/merchant_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            showNotification(data.message, 'error');
        }
    });
}

// Edit menu item
function editMenuItem(itemId) {
    // For now, just show a simple prompt - could be enhanced with a modal
    const newName = prompt('Masukkan nama menu baru:');
    if (newName) {
        const formData = new FormData();
        formData.append('action', 'update_menu_item');
        formData.append('item_id', itemId);
        formData.append('name', newName);
        
        fetch('process/merchant_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showNotification(data.message, 'error');
            }
        });
    }
}

// Delete menu item
function deleteMenuItem(itemId) {
    if (confirm('Apakah Anda yakin ingin menghapus menu ini?')) {
        const formData = new FormData();
        formData.append('action', 'delete_menu_item');
        formData.append('item_id', itemId);
        
        fetch('process/merchant_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showNotification(data.message, 'error');
            }
        });
    }
}

// Show notification
function showNotification(message, type = 'info') {
    // Use existing notification system from main script
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        // Fallback to alert
        alert(message);
    }
}
function deleteFood(foodId) {
    if (confirm('Yakin ingin menghapus menu ini?')) {
        const formData = new FormData();
        formData.append('action', 'delete_food');
        formData.append('food_id', foodId);
        
        fetch('process/merchant_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<style>
.food-item-card {
    border: 1px solid #e3e6f0;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.food-item-card:hover {
    border-color: #4e73df;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.object-fit-cover {
    object-fit: cover;
}
</style>

<?php include 'includes/footer.php'; ?>