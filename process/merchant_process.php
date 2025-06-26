<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Make PDO globally available
global $pdo;

// Handle file upload
function handleImageUpload($file, $folder) {
    $upload_dir = "../uploads/$folder/";
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 5MB.'];
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $filename, 'path' => "uploads/$folder/" . $filename];
    }
    
    return ['success' => false, 'message' => 'Gagal mengupload file.'];
}

// Get merchant info
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt->execute([$user_id]);
$merchant = $stmt->fetch();

if (!$merchant) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Merchant not found']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'update_merchant_profile':
        try {
            $business_name = $_POST['business_name'] ?? '';
            $business_address = $_POST['business_address'] ?? '';
            $business_phone = $_POST['business_phone'] ?? '';
            $business_category = $_POST['business_category'] ?? '';
            $description = $_POST['description'] ?? '';
            $opening_hours = $_POST['opening_hours'] ?? '';
            $delivery_fee = $_POST['delivery_fee'] ?? 0;
            $minimum_order = $_POST['minimum_order'] ?? 0;
            
            $logo_path = $merchant['logo_path'];
            $banner_path = $merchant['banner_path'];
            
            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handleImageUpload($_FILES['logo'], 'merchant/logos');
                if ($upload_result['success']) {
                    // Delete old logo if exists
                    if ($logo_path && file_exists('../' . $logo_path)) {
                        unlink('../' . $logo_path);
                    }
                    $logo_path = $upload_result['path'];
                } else {
                    echo json_encode($upload_result);
                    exit();
                }
            }
            
            // Handle banner upload
            if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handleImageUpload($_FILES['banner'], 'merchant/banners');
                if ($upload_result['success']) {
                    // Delete old banner if exists
                    if ($banner_path && file_exists('../' . $banner_path)) {
                        unlink('../' . $banner_path);
                    }
                    $banner_path = $upload_result['path'];
                } else {
                    echo json_encode($upload_result);
                    exit();
                }
            }
            
            global $pdo;
            $stmt = $pdo->prepare("UPDATE merchants SET business_name = ?, business_address = ?, business_phone = ?, business_category = ?, description = ?, logo_path = ?, banner_path = ?, opening_hours = ?, delivery_fee = ?, minimum_order = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->execute([
                $business_name,
                $business_address,
                $business_phone,
                $business_category,
                $description,
                $logo_path,
                $banner_path,
                $opening_hours,
                $delivery_fee,
                $minimum_order,
                $user_id
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Profil merchant berhasil diperbarui']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
        break;
        
    case 'add_category':
        try {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $sort_order = $_POST['sort_order'] ?? 0;
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Nama kategori harus diisi']);
                exit();
            }
            
            global $pdo;
            $stmt = $pdo->prepare("INSERT INTO food_categories (merchant_id, name, description, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$merchant['id'], $name, $description, $sort_order]);
            
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil ditambahkan']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
        break;
        
    case 'add_menu_item':
        try {
            $category_id = $_POST['category_id'] ?? null;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $preparation_time = $_POST['preparation_time'] ?? 15;
            
            if (empty($name) || $price <= 0) {
                echo json_encode(['success' => false, 'message' => 'Nama menu dan harga harus diisi dengan benar']);
                exit();
            }
            
            $image_path = null;
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handleImageUpload($_FILES['image'], 'menu_items');
                if ($upload_result['success']) {
                    $image_path = $upload_result['path'];
                } else {
                    echo json_encode($upload_result);
                    exit();
                }
            }
            
            global $pdo;
            $stmt = $pdo->prepare("INSERT INTO menu_items (merchant_id, category_id, name, description, price, image_path, preparation_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$merchant['id'], $category_id, $name, $description, $price, $image_path, $preparation_time]);
            
            echo json_encode(['success' => true, 'message' => 'Menu berhasil ditambahkan']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
        break;
        
    case 'update_menu_item':
        try {
            $item_id = $_POST['item_id'] ?? 0;
            $category_id = $_POST['category_id'] ?? null;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $preparation_time = $_POST['preparation_time'] ?? 15;
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            
            if (empty($name) || $price <= 0) {
                echo json_encode(['success' => false, 'message' => 'Nama menu dan harga harus diisi dengan benar']);
                exit();
            }
            
            // Get current item
            $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND merchant_id = ?");
            $stmt->execute([$item_id, $merchant['id']]);
            $current_item = $stmt->fetch();
            
            if (!$current_item) {
                echo json_encode(['success' => false, 'message' => 'Item menu tidak ditemukan']);
                exit();
            }
            
            $image_path = $current_item['image_path'];
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handleImageUpload($_FILES['image'], 'menu_items');
                if ($upload_result['success']) {
                    // Delete old image if exists
                    if ($image_path && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    $image_path = $upload_result['path'];
                } else {
                    echo json_encode($upload_result);
                    exit();
                }
            }
            
            $stmt = $pdo->prepare("UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image_path = ?, preparation_time = ?, is_available = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND merchant_id = ?");
            $stmt->execute([$category_id, $name, $description, $price, $image_path, $preparation_time, $is_available, $item_id, $merchant['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Menu berhasil diperbarui']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete_menu_item':
        try {
            $item_id = $_POST['item_id'] ?? 0;
            
            // Get current item
            $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND merchant_id = ?");
            $stmt->execute([$item_id, $merchant['id']]);
            $current_item = $stmt->fetch();
            
            if (!$current_item) {
                echo json_encode(['success' => false, 'message' => 'Item menu tidak ditemukan']);
                exit();
            }
            
            // Delete image file if exists
            if ($current_item['image_path'] && file_exists('../' . $current_item['image_path'])) {
                unlink('../' . $current_item['image_path']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND merchant_id = ?");
            $stmt->execute([$item_id, $merchant['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Menu berhasil dihapus']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_menu_items':
        try {
            global $pdo;
            $stmt = $pdo->prepare("SELECT mi.*, fc.name as category_name FROM menu_items mi LEFT JOIN food_categories fc ON mi.category_id = fc.id WHERE mi.merchant_id = ? ORDER BY mi.created_at DESC");
            $stmt->execute([$merchant['id']]);
            $items = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $items]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>