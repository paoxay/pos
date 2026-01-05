<?php
require_once 'config.php';
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // บันทึกการขาย
    $stmt = $pdo->prepare("INSERT INTO sales (employee_id, subtotal, discount, total, profit) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $input['subtotal'],
        $input['discount'],
        $input['total'],
        $input['profit']
    ]);
    
    $sale_id = $pdo->lastInsertId();
    
    // บันทึกรายการขาย และอัพเดทสต็อก
    foreach ($input['items'] as $item) {
        // บันทึกรายการขาย
        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, cost) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $sale_id,
            $item['id'],
            $item['quantity'],
            $item['price'],
            $item['cost']
        ]);
        
        // อัพเดทสต็อก
        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['id']]);
    }
    
    $pdo->commit();
    
    // ส่งข้อมูลใบเสร็จกลับ
    echo json_encode([
        'success' => true,
        'sale' => [
            'id' => $sale_id,
            'date' => date('Y-m-d H:i:s'),
            'items' => $input['items'],
            'subtotal' => $input['subtotal'],
            'discount' => $input['discount'],
            'total' => $input['total'],
            'profit' => $input['profit']
        ]
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>