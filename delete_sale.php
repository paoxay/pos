<?php
require_once 'config.php';
header('Content-Type: application/json');

// ກວດສອບການລອກອິນ ແລະ ສິດ Admin (ຖ້າຕ້ອງການໃຫ້ພະນັກງານລົບໄດ້ ໃຫ້ຕັດເງື່ອນໄຂ admin ອອກ)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາເຂົ້າສູ່ລະບົບ']);
    exit;
}

// ຮັບຄ່າ ID ທີ່ສົ່ງມາ
$input = json_decode(file_get_contents('php://input'), true);
$sale_id = $input['id'] ?? 0;

if ($sale_id) {
    try {
        $pdo->beginTransaction();

        // 1. ດຶງຂໍ້ມູນສິນຄ້າໃນບິນນັ້ນ ເພື່ອຄືນ Stock
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$sale_id]);
        $items = $stmt->fetchAll();

        // 2. ວົນລູບ ຄືນສະຕັອກສິນຄ້າ
        foreach ($items as $item) {
            $updateStock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $updateStock->execute([$item['quantity'], $item['product_id']]);
        }

        // 3. ລຶບບິນຂາຍ (ເນື່ອງຈາກໃນ Database ຕັ້ງ CASCADE ໄວ້ ມັນຈະລົບ sale_items ໃຫ້ເອງ)
        $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->execute([$sale_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'ລຶບລາຍການຂາຍ ແລະ ຄືນສະຕັອກສຳເລັດ!']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບລະຫັດການຂາຍ']);
}
?>