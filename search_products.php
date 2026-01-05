<?php
header('Content-Type: application/json');
require_once 'config.php';
// ไม่จำเป็นต้อง checkLogin() ก็ได้เพื่อให้ค้นหาสินค้าได้เร็วขึ้น แต่ถ้าต้องการความปลอดภัยสูงให้เปิดใช้งาน
// checkLogin(); 

// รับคำค้นหาจาก parameter 'q'
$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

try {
    // ค้นหาสินค้าจาก "ชื่อ" หรือ "บาร์โค้ด"
    // ใช้ LIKE %...% เพื่อให้ค้นหาบางส่วนของคำได้
    // LIMIT 10 เพื่อจำกัดผลลัพธ์ไม่ให้เยอะเกินไป ทำให้หน้าเว็บตอบสนองเร็ว
    $stmt = $pdo->prepare("
        SELECT id, name, barcode, price, cost, stock 
        FROM products 
        WHERE (name LIKE ? OR barcode LIKE ?) AND stock > 0
        LIMIT 10
    ");
    
    // ใส่ % รอบคำค้นหา
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm]);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($products);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
