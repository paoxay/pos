<?php
header('Content-Type: application/json');
require_once 'config.php';
checkLogin();

try {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // 1. ດຶງຂໍ້ມູນສະຫຼຸບຍອດຂາຍ
    $summary_stmt = $pdo->prepare("
        SELECT 
            SUM(total) as total_sales,
            SUM(profit) as total_profit
        FROM sales
        WHERE DATE(sale_date) = ?
    ");
    $summary_stmt->execute([$date]);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    // 2. ດຶງລາຍການຂາຍ (ແກ້ໄຂ: ເພີ່ມ Subquery ດຶງ Barcode)
    $sales_stmt = $pdo->prepare("
        SELECT 
            s.id, 
            s.sale_date, 
            s.subtotal, 
            s.discount, 
            s.total, 
            s.profit,
            (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count,
            (SELECT GROUP_CONCAT(p.barcode SEPARATOR ', ') 
             FROM sale_items si 
             JOIN products p ON si.product_id = p.id 
             WHERE si.sale_id = s.id) as barcodes
        FROM sales s
        WHERE DATE(s.sale_date) = ?
        ORDER BY s.sale_date DESC
    ");
    
    $sales_stmt->execute([$date]);
    $sales = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'summary' => [
            'total_sales' => $summary['total_sales'] ?? 0,
            'total_profit' => $summary['total_profit'] ?? 0,
        ],
        'sales' => $sales
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>