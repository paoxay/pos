<?php
header('Content-Type: application/json');
require_once 'config.php';
checkLogin();

// ตรวจสอบว่ามี ID ของใบเสร็จส่งมาหรือไม่
if (!isset($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุเลขที่ใบเสร็จ']);
    exit;
}

$saleId = $_GET['id'];

try {
    // --- 1. ดึงข้อมูลหลักของใบเสร็จ (แก้ไข: ไม่ต้อง JOIN ตาราง users แล้ว) ---
    $stmt = $pdo->prepare("
        SELECT s.*
        FROM sales s
        WHERE s.id = ?
    ");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลการขาย']);
        exit;
    }

    // --- เพิ่มส่วนนี้: ดึงชื่อพนักงานจาก Session แทน ---
    $sale['employee_name'] = $_SESSION['user_name'] ?? 'ไม่ระบุ';

    // --- 2. ดึงข้อมูลรายการสินค้าในใบเสร็จ พร้อมบาร์โค้ด ---
    $stmt = $pdo->prepare("
        SELECT si.*, p.name, p.barcode 
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$saleId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // นำรายการสินค้าใส่เข้าไปในข้อมูลใบเสร็จ
    $sale['items'] = $items;
    $sale['date'] = $sale['sale_date']; // เพิ่ม key 'date' ให้เข้ากับฟังก์ชัน showReceipt ใน Javascript

    // --- 3. ส่งข้อมูลกลับไป ---
    echo json_encode(['success' => true, 'sale' => $sale]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

