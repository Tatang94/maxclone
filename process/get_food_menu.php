<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    global $pdo;
    $category = $_GET['category'] ?? '';
    
    // Build query for menu items from merchants
    $sql = "SELECT 
                mi.id,
                mi.name,
                mi.description,
                mi.price,
                mi.image_path as image_url,
                mi.preparation_time,
                mi.is_available,
                m.business_name as merchant_name,
                m.business_address as merchant_address,
                fc.name as category
            FROM menu_items mi 
            JOIN merchants m ON mi.merchant_id = m.id 
            LEFT JOIN food_categories fc ON mi.category_id = fc.id
            WHERE mi.is_available = true AND m.is_active = true";
    
    $params = [];
    
    if (!empty($category)) {
        $sql .= " AND fc.name ILIKE ?";
        $params[] = "%{$category}%";
    }
    
    $sql .= " ORDER BY fc.name, mi.price ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for frontend compatibility
    $formatted_items = [];
    foreach ($menu_items as $item) {
        $formatted_items[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'description' => $item['description'],
            'price' => $item['price'],
            'image_url' => $item['image_url'] ?: 'https://via.placeholder.com/150x100/6c757d/ffffff?text=No+Image',
            'category' => $item['category'] ?: 'Umum',
            'preparation_time' => $item['preparation_time'],
            'merchant_name' => $item['merchant_name'],
            'merchant_address' => $item['merchant_address'],
            'is_available' => $item['is_available']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_items,
        'count' => count($formatted_items)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memuat menu makanan: ' . $e->getMessage()
    ]);
}
?>