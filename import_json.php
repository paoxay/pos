<?php
require_once 'config.php';
checkLogin();

// ອະນຸຍາດສະເພາະ Admin
if ($_SESSION['user_role'] !== 'admin') {
    die("Access Denied: ສຳລັບຜູ້ດູແລລະບົບເທົ່ານັ້ນ");
}

$message = '';
$status = ''; // success ຫຼື error

if (isset($_POST['import'])) {
    // ກວດສອບວ່າມີການອັບໂຫຼດໄຟລ໌ບໍ່
    if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] == 0) {
        $fileName = $_FILES['json_file']['name'];
        $fileTmp = $_FILES['json_file']['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt === 'json') {
            // ອ່ານຂໍ້ມູນຈາກໄຟລ໌
            $jsonContent = file_get_contents($fileTmp);
            $products = json_decode($jsonContent, true);

            if ($products === null) {
                $message = "ຮູບແບບ JSON ບໍ່ຖືກຕ້ອງ! ກະລຸນາກວດສອບໄຟລ໌.";
                $status = 'error';
            } else {
                $count_new = 0;
                $count_update = 0;

                try {
                    $pdo->beginTransaction();

                    foreach ($products as $item) {
                        // ກວດສອບຂໍ້ມູນທີ່ຈຳເປັນ
                        if (!empty($item['barcode']) && !empty($item['name'])) {
                            $barcode = $item['barcode'];
                            $name = $item['name'];
                            $stock = isset($item['stock']) ? (int)$item['stock'] : 0;
                            $cost = isset($item['cost']) ? (float)$item['cost'] : 0;
                            $price = isset($item['price']) ? (float)$item['price'] : 0;

                            // ກວດສອບວ່າສິນຄ້າມີຢູ່ແລ້ວບໍ່
                            $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = ?");
                            $stmt->execute([$barcode]);
                            $exists = $stmt->fetch();

                            if ($exists) {
                                // ມີແລ້ວ -> ອັບເດດ
                                $sql = "UPDATE products SET name=?, stock=?, cost=?, price=? WHERE barcode=?";
                                $stmtUpdate = $pdo->prepare($sql);
                                $stmtUpdate->execute([$name, $stock, $cost, $price, $barcode]);
                                $count_update++;
                            } else {
                                // ຍັງບໍ່ມີ -> ເພີ່ມໃໝ່
                                $sql = "INSERT INTO products (barcode, name, stock, cost, price) VALUES (?, ?, ?, ?, ?)";
                                $stmtInsert = $pdo->prepare($sql);
                                $stmtInsert->execute([$barcode, $name, $stock, $cost, $price]);
                                $count_new++;
                            }
                        }
                    }

                    $pdo->commit();
                    $message = "ນຳເຂົ້າສຳເລັດ! <br>ເພີ່ມໃໝ່: <b>$count_new</b> ລາຍການ <br>ອັບເດດ: <b>$count_update</b> ລາຍການ";
                    $status = 'success';

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
                    $status = 'error';
                }
            }
        } else {
            $message = "ກະລຸນາເລືອກໄຟລ໌ນາມສະກຸນ .json ເທົ່ານັ້ນ";
            $status = 'error';
        }
    } else {
        $message = "ກະລຸນາເລືອກໄຟລ໌ກ່ອນກົດນຳເຂົ້າ";
        $status = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>ນຳເຂົ້າສິນຄ້າດ້ວຍ JSON</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-lg">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                <i class="fas fa-file-code"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">ນຳເຂົ້າສິນຄ້າ (JSON)</h1>
            <p class="text-gray-500 text-sm">ອັບໂຫຼດໄຟລ໌ JSON ເພື່ອບັນທຶກລົງຖານຂໍ້ມູນ</p>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo $status === 'success' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'; ?> border p-4 rounded-xl mb-6 flex items-start gap-3">
                <i class="fas <?php echo $status === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mt-1"></i>
                <div><?php echo $message; ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <div class="relative border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:border-yellow-500 hover:bg-yellow-50 transition-all cursor-pointer group">
                <input type="file" name="json_file" accept=".json" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="document.getElementById('fileName').innerText = this.files[0].name">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 group-hover:text-yellow-500 mb-3 transition-colors"></i>
                <p class="text-gray-600 font-medium">ຄິກເພື່ອເລືອກໄຟລ໌ JSON</p>
                <p id="fileName" class="text-sm text-blue-600 mt-2 font-bold"></p>
            </div>

            <div class="bg-gray-50 p-4 rounded-xl text-xs text-gray-500 border border-gray-100">
                <p class="font-bold mb-1 text-gray-700">ຕົວຢ່າງຮູບແບບ JSON:</p>
                <pre class="whitespace-pre-wrap font-mono bg-white p-2 rounded border border-gray-200 mt-1">
[
  {
    "barcode": "885123...",
    "name": "ຊື່ສິນຄ້າ...",
    "stock": 100,
    "cost": 5000,
    "price": 10000
  }
]</pre>
            </div>

            <div class="flex gap-3">
                <button type="submit" name="import" class="flex-1 bg-yellow-500 text-white py-3 rounded-xl hover:bg-yellow-600 font-bold shadow-lg shadow-yellow-500/30 transition-all active:scale-95">
                    <i class="fas fa-save mr-2"></i> ບັນທຶກຂໍ້ມູນ
                </button>
                <a href="products.php" class="px-6 py-3 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 font-medium">
                    ຍົກເລີກ
                </a>
            </div>
        </form>
    </div>

</body>
</html>