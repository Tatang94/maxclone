<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's merchant ID
$stmt = $pdo->prepare("SELECT id FROM merchants WHERE email = (SELECT email FROM users WHERE id = ?)");
$stmt->execute([$user_id]);
$merchant = $stmt->fetch();

if (!$merchant) {
    echo json_encode(['success' => false, 'message' => 'Merchant tidak ditemukan']);
    exit();
}

$merchant_id = $merchant['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_food':
        case '':  // Default action for adding food
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = intval($_POST['price'] ?? 0);
            $category = trim($_POST['category'] ?? '');
            $preparation_time = intval($_POST['preparation_time'] ?? 15);
            $image_url = trim($_POST['image_url'] ?? '');
            
            // Validation
            if (empty($name) || $price <= 0 || empty($category)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
                exit();
            }
            
            // Set default image if empty
            if (empty($image_url)) {
                $category_colors = [
                    'nasi' => 'FF6B35',
                    'mie' => '4ECDC4', 
                    'minuman' => '45B7D1',
                    'snack' => 'FFA07A',
                    'dessert' => 'DDA0DD'
                ];
                $color = $category_colors[$category] ?? '6c757d';
                $image_url = "https://via.placeholder.com/300x200/{$color}/FFFFFF?text=" . urlencode(ucfirst($category));
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO food_items (merchant_id, name, description, price, category, image_url, preparation_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$merchant_id, $name, $description, $price, $category, $image_url, $preparation_time]);
                
                echo json_encode(['success' => true, 'message' => 'Menu berhasil ditambahkan']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Gagal menambahkan menu: ' . $e->getMessage()]);
            }
            break;
            
        case 'toggle_availability':
            $food_id = intval($_POST['food_id'] ?? 0);
            $is_available = $_POST['is_available'] === 'true';
            
            try {
                $stmt = $pdo->prepare("UPDATE food_items SET is_available = ? WHERE id = ? AND merchant_id = ?");
                $stmt->execute([$is_available, $food_id, $merchant_id]);
                
                $status = $is_available ? 'tersedia' : 'tidak tersedia';
                echo json_encode(['success' => true, 'message' => "Menu berhasil diubah menjadi {$status}"]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Gagal mengubah status menu']);
            }
            break;
            
        case 'delete_food':
            $food_id = intval($_POST['food_id'] ?? 0);
            
            try {
                $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ? AND merchant_id = ?");
                $stmt->execute([$food_id, $merchant_id]);
                
                echo json_encode(['success' => true, 'message' => 'Menu berhasil dihapus']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus menu']);
            }
            break;
            
        case 'edit_food':
            $food_id = intval($_POST['food_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = intval($_POST['price'] ?? 0);
            $category = trim($_POST['category'] ?? '');
            $preparation_time = intval($_POST['preparation_time'] ?? 15);
            $image_url = trim($_POST['image_url'] ?? '');
            
            // Validation
            if (empty($name) || $price <= 0 || empty($category)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
                exit();
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE food_items SET name = ?, description = ?, price = ?, category = ?, preparation_time = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND merchant_id = ?");
                $stmt->execute([$name, $description, $price, $category, $preparation_time, $image_url, $food_id, $merchant_id]);
                
                echo json_encode(['success' => true, 'message' => 'Menu berhasil diperbarui']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui menu']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
}
?>