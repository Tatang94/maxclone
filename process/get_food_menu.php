<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $category = $_GET['category'] ?? '';
    
    // Build query based on category filter
    $sql = "SELECT f.*, m.name as merchant_name, m.address as merchant_address 
            FROM food_items f 
            JOIN merchants m ON f.merchant_id = m.id 
            WHERE f.is_available = true AND m.is_active = true";
    
    $params = [];
    
    if (!empty($category)) {
        $sql .= " AND f.category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY f.category, f.price ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $food_items
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memuat menu makanan: ' . $e->getMessage()
    ]);
}
?>