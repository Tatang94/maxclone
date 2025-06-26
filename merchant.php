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

// Get user's merchant info
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE email = (SELECT email FROM users WHERE id = ?)");
$stmt->execute([$user_id]);
$merchant = $stmt->fetch();

// If no merchant account, create one
if (!$merchant) {
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();
    
    if ($user) {
        $create_merchant = $pdo->prepare("INSERT INTO merchants (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $create_merchant->execute([
            $user['name'] . "'s Kitchen",
            $user['email'],
            $user['phone'] ?? '',
            'Alamat belum diisi'
        ]);
        
        // Get the newly created merchant
        $stmt = $pdo->prepare("SELECT * FROM merchants WHERE email = ?");
        $stmt->execute([$user['email']]);
        $merchant = $stmt->fetch();
    }
}

// Get merchant's food items
$food_items = [];
if ($merchant) {
    $stmt = $pdo->prepare("SELECT * FROM food_items WHERE merchant_id = ? ORDER BY created_at DESC");
    $stmt->execute([$merchant['id']]);
    $food_items = $stmt->fetchAll();
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
                <h4 class="mb-1">🍽️ <?php echo htmlspecialchars($merchant['name'] ?? 'Merchant Panel'); ?></h4>
                <p class="mb-0 opacity-75">Kelola menu makanan Anda</p>
            </div>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addFoodModal">
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
                    <h5><?php echo count($food_items); ?></h5>
                    <small>Total Menu</small>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h5><?php echo count(array_filter($food_items, function($item) { return $item['is_available']; })); ?></h5>
                    <small>Menu Tersedia</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu List -->
    <div class="p-3">
        <h5 class="mb-3">Daftar Menu Makanan</h5>
        
        <?php if (empty($food_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                <h6>Belum Ada Menu</h6>
                <p class="text-muted">Tambahkan menu makanan pertama Anda</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFoodModal">
                    <i class="fas fa-plus"></i> Tambah Menu
                </button>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($food_items as $item): ?>
                    <div class="col-12">
                        <div class="card food-item-card">
                            <div class="row g-0">
                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/150x100/6c757d/ffffff?text=No+Image'); ?>" 
                                         class="img-fluid rounded-start h-100 object-fit-cover" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="col-8">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-<?php echo $item['category'] == 'nasi' ? 'warning' : ($item['category'] == 'mie' ? 'primary' : 'info'); ?>">
                                                        <?php echo ucfirst($item['category']); ?>
                                                    </span>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> <?php echo $item['preparation_time']; ?> menit
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="h6 text-primary mb-1">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-sm" onclick="editFood(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-<?php echo $item['is_available'] ? 'success' : 'secondary'; ?> btn-sm" 
                                                            onclick="toggleAvailability(<?php echo $item['id']; ?>, <?php echo $item['is_available'] ? 'false' : 'true'; ?>)">
                                                        <i class="fas fa-<?php echo $item['is_available'] ? 'eye' : 'eye-slash'; ?>"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteFood(<?php echo $item['id']; ?>)">
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

<!-- Add Food Modal -->
<div class="modal fade" id="addFoodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Menu Makanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addFoodForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="merchant_id" value="<?php echo $merchant['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Makanan</label>
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
                                <select class="form-select" name="category" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="nasi">Nasi</option>
                                    <option value="mie">Mie</option>
                                    <option value="minuman">Minuman</option>
                                    <option value="snack">Snack</option>
                                    <option value="dessert">Dessert</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Waktu Persiapan (menit)</label>
                        <input type="number" class="form-control" name="preparation_time" value="15" min="1" max="120">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">URL Gambar</label>
                        <input type="url" class="form-control" name="image_url" placeholder="https://example.com/image.jpg">
                        <small class="form-text text-muted">Masukkan URL gambar makanan</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Menu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add food form submission
document.getElementById('addFoodForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('process/merchant_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addFoodModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menambah menu');
    });
});

// Toggle food availability
function toggleAvailability(foodId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'toggle_availability');
    formData.append('food_id', foodId);
    formData.append('is_available', newStatus);
    
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

// Delete food item
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