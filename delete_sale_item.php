<?php
require_once 'config.php';
header('Content-Type: application/json');

// ກວດສອບການ login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາເຂົ້າສູ່ລະບົບ']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$sale_item_id = $input['sale_item_id'] ?? 0;

if (!$sale_item_id) {
    echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບລະຫັດລາຍການ']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. ດຶງຂໍ້ມູນ sale_item ທີ່ຈະລຶບ
    $stmt = $pdo->prepare("SELECT si.*, p.name as product_name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.id = ?");
    $stmt->execute([$sale_item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບລາຍການນີ້ໃນລະບົບ']);
        exit;
    }

    $sale_id = $item['sale_id'];
    $product_id = $item['product_id'];
    $quantity = $item['quantity'];
    $item_total = $item['price'] * $item['quantity'];
    $item_cost_total = $item['cost'] * $item['quantity'];
    $item_profit = $item_total - $item_cost_total;

    // 2. ຄືນ stock ສິນຄ້າ
    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
    $stmt->execute([$quantity, $product_id]);

    // 3. ລຶບ sale_item
    $stmt = $pdo->prepare("DELETE FROM sale_items WHERE id = ?");
    $stmt->execute([$sale_item_id]);

    // 4. ກວດສອບວ່າ ບິນນີ້ ຍັງມີລາຍການຢູ່ບໍ່
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$sale_id]);
    $remaining = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($remaining['cnt'] == 0) {
        // ຖ້າບໍ່ມີລາຍການເຫຼືອ → ລຶບບິນເລີຍ
        $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->execute([$sale_id]);
        $sale_deleted = true;
    } else {
        // 5. ອັບເດດຍອດລວມຂອງບິນ
        $stmt = $pdo->prepare("SELECT s.discount FROM sales s WHERE s.id = ?");
        $stmt->execute([$sale_id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
        $discount = $sale['discount'] ?? 0;

        // ຄຳນວນ subtotal ໃໝ່ຈາກລາຍການທີ່ເຫຼືອ
        $stmt = $pdo->prepare("SELECT SUM(price * quantity) as new_subtotal, SUM(cost * quantity) as new_cost_total FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$sale_id]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);

        $new_subtotal = $totals['new_subtotal'];
        $new_total = $new_subtotal * (1 - $discount / 100);
        $new_profit = $new_total - $totals['new_cost_total'];

        $stmt = $pdo->prepare("UPDATE sales SET subtotal = ?, total = ?, profit = ? WHERE id = ?");
        $stmt->execute([$new_subtotal, $new_total, $new_profit, $sale_id]);
        $sale_deleted = false;
    }

    $pdo->commit();

    $msg = "ລຶບ \"{$item['product_name']}\" x{$quantity} ສຳເລັດ! ຄືນ stock {$quantity} ຊິ້ນແລ້ວ.";
    if ($sale_deleted) {
        $msg .= " (ບິນ #{$sale_id} ຖືກລຶບແລ້ວ ເນື່ອງຈາກບໍ່ມີລາຍການເຫຼືອ)";
    }

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'sale_deleted' => $sale_deleted,
        'sale_id' => $sale_id
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage()]);
}
?>
