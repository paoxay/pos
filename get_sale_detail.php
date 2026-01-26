<?php
header('Content-Type: application/json');
require_once 'config.php';
checkLogin();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

$saleId = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT s.* FROM sales s WHERE s.id = ?");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Not found']);
        exit;
    }

    $sale['employee_name'] = $_SESSION['user_name'] ?? 'System';

    $stmt = $pdo->prepare("
        SELECT si.*, p.name, p.barcode 
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$saleId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // *** ເພີ່ມສ່ວນກວດສອບສິດ ***
    if ($_SESSION['user_role'] !== 'admin') {
        // ຖ້າບໍ່ແມ່ນ admin ໃຫ້ລຶບຂໍ້ມູນຕົ້ນທຶນ ແລະ ກຳໄລອອກ
        unset($sale['profit']);
        foreach ($items as &$item) {
            unset($item['cost']); // ລຶບຕົ້ນທຶນ ເພື່ອບໍ່ໃຫ້ຄຳນວນກຳໄລໄດ້
        }
    }

    $sale['items'] = $items;
    $sale['date'] = $sale['sale_date'];

    echo json_encode(['success' => true, 'sale' => $sale]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>