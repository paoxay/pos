<?php
header('Content-Type: application/json');
require_once 'config.php';
checkLogin();

try {
    $start_date = $_GET['start_date'] ?? date('Y-m-d');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    // 1. ດຶງຂໍ້ມູນສະຫຼຸບຍອດຂາຍ
    $summary_stmt = $pdo->prepare("
        SELECT 
            SUM(total) as total_sales,
            SUM(profit) as total_profit
        FROM sales
        WHERE DATE(sale_date) BETWEEN ? AND ?
    ");
    $summary_stmt->execute([$start_date, $end_date]);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    // 2. ດຶງລາຍການຂາຍ
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
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        ORDER BY s.sale_date DESC
    ");
    
    $sales_stmt->execute([$start_date, $end_date]);
    $sales = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // *** ເພີ່ມສ່ວນກວດສອບສິດ (Security Check) ***
    if ($_SESSION['user_role'] !== 'admin') {
        // ຖ້າບໍ່ແມ່ນ admin ໃຫ້ປິດບັງກຳໄລ
        $summary['total_profit'] = 0;
        foreach ($sales as &$sale) {
            unset($sale['profit']); // ລຶບ key profit ອອກຈາກ array
        }
    }
    
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