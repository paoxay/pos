<?php
header('Content-Type: application/json');
require_once 'config.php';
checkLogin();

try {
    // รับวันที่จาก parameter ถ้าไม่ส่งมาให้ใช้วันที่ปัจจุบัน
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // --- 1. ดึงข้อมูลสรุปยอดขายและกำไรของวันที่เลือก ---
    $summary_stmt = $pdo->prepare("
        SELECT 
            SUM(total) as total_sales,
            SUM(profit) as total_profit
        FROM sales
        WHERE DATE(sale_date) = ?
    ");
    $summary_stmt->execute([$date]);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    // --- 2. ดึงรายการขายทั้งหมดของวันที่เลือก (แก้ไข: ไม่ต้อง JOIN ตาราง users) ---
    $sales_stmt = $pdo->prepare("
        SELECT 
            s.id, 
            s.sale_date, 
            s.subtotal, 
            s.discount, 
            s.total, 
            s.profit,
            (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
        FROM sales s
        WHERE DATE(s.sale_date) = ?
        ORDER BY s.sale_date DESC
    ");
    
    $sales_stmt->execute([$date]);
    $sales = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // --- 3. สร้างข้อมูลตอบกลับในรูปแบบ JSON ---
    $response = [
        'summary' => [
            // ถ้าไม่มีข้อมูล ให้ค่าเป็น 0
            'total_sales' => $summary['total_sales'] ?? 0,
            'total_profit' => $summary['total_profit'] ?? 0,
        ],
        'sales' => $sales
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // ส่งข้อความ error กลับไปในรูปแบบ JSON
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

