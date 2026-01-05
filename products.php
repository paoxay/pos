<?php
require_once 'config.php';
checkLogin();

$success = '';
$error = '';
$edit_product = null;
$search = $_GET['search'] ?? ''; // ຮັບຄ່າຄົ້ນຫາ

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO products (barcode, name, stock, cost, price) VALUES (?, ?, ?, ?, ?)");
                try {
                    $stmt->execute([$_POST['barcode'], $_POST['name'], $_POST['stock'], $_POST['cost'], $_POST['price']]);
                    $success = "ເພີ່ມສິນຄ້າສຳເລັດ!";
                } catch (PDOException $e) {
                    $error = "ຜິດພາດ: ບາໂຄດຊ້ຳກັນ ຫຼື ຂໍ້ມູນບໍ່ຖືກຕ້ອງ";
                }
                break;
            case 'edit':
                $stmt = $pdo->prepare("UPDATE products SET barcode = ?, name = ?, stock = ?, cost = ?, price = ? WHERE id = ?");
                $stmt->execute([$_POST['barcode'], $_POST['name'], $_POST['stock'], $_POST['cost'], $_POST['price'], $_POST['id']]);
                $success = "ແກ້ໄຂສິນຄ້າສຳເລັດ!";
                break;
            case 'delete':
                if ($_SESSION['user_role'] === 'admin') {
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $success = "ລຶບສິນຄ້າສຳເລັດ!";
                } else {
                    $error = "ສະເພາະ Admin ເທົ່ານັ້ນທີ່ລຶບໄດ້";
                }
                break;
        }
    }
}

// ດຶງຂໍ້ມູນສິນຄ້າ (ພ້ອມລະບົບຄົ້ນຫາ)
$sql = "SELECT * FROM products";
$params = [];

if ($search) {
    $sql .= " WHERE name LIKE ? OR barcode LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// ດຶງຂໍ້ມູນສິນຄ້າທີ່ຈະແກ້ໄຂ
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_product = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການສິນຄ້າ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="index.php" class="bg-gray-100 p-2 rounded-lg hover:bg-gray-200 transition-colors text-gray-600">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-800">ຈັດການສິນຄ້າ</h1>
            </div>
            <div class="flex items-center gap-4">
                <a href="import_json.php" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 shadow-md text-sm font-medium">
                   <i class="fas fa-file-import mr-1"></i> Import JSON
                </a>
                <div class="text-sm text-gray-500">
                    <i class="fas fa-box mr-1"></i> ທັງໝົດ: <span class="font-bold text-blue-600"><?php echo count($products); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm flex items-center">
                <i class="fas fa-check-circle mr-2 text-xl"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r shadow-sm flex items-center">
                <i class="fas fa-exclamation-circle mr-2 text-xl"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-4">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                        <?php echo $edit_product ? '<i class="fas fa-edit text-orange-500 mr-2"></i>ແກ້ໄຂສິນຄ້າ' : '<i class="fas fa-plus-circle text-blue-500 mr-2"></i>ເພີ່ມສິນຄ້າໃໝ່'; ?>
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                        <input type="hidden" name="id" value="<?php echo $edit_product['id'] ?? ''; ?>">
                        
                        <div>
                            <label class="text-sm font-medium text-gray-700">ບາໂຄດ</label>
                            <input type="text" name="barcode" value="<?php echo $edit_product['barcode'] ?? ''; ?>" class="w-full mt-1 p-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" required placeholder="ລະຫັດສິນຄ້າ">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">ຊື່ສິນຄ້າ</label>
                            <input type="text" name="name" value="<?php echo $edit_product['name'] ?? ''; ?>" class="w-full mt-1 p-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" required placeholder="ຊື່ສິນຄ້າ">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">ຈຳນວນໃນສະຕັອກ</label>
                            <input type="number" name="stock" value="<?php echo $edit_product['stock'] ?? '0'; ?>" class="w-full mt-1 p-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">ຕົ້ນທຶນ</label>
                                <input type="number" name="cost" step="0.01" value="<?php echo $edit_product['cost'] ?? '0'; ?>" class="w-full mt-1 p-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" required>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">ລາຄາຂາຍ</label>
                                <input type="number" name="price" step="0.01" value="<?php echo $edit_product['price'] ?? '0'; ?>" class="w-full mt-1 p-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" required>
                            </div>
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl hover:bg-blue-700 font-medium shadow-lg shadow-blue-500/30 transition-all">
                                ບັນທຶກ
                            </button>
                            <?php if($edit_product): ?>
                                <a href="products.php" class="px-4 py-3 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 font-medium text-center">ຍົກເລີກ</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-4">
                    <form method="GET" class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                class="w-full py-2 pl-10 pr-4 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                placeholder="ຄົ້ນຫາຊື່ສິນຄ້າ ຫຼື ບາໂຄດ...">
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium transition-colors">
                            ຄົ້ນຫາ
                        </button>
                        <?php if($search): ?>
                            <a href="products.php" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">ສິນຄ້າ</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">ສະຕັອກ</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">ຕົ້ນທຶນ</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">ລາຄາຂາຍ</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">ຈັດການ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (count($products) == 0): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                            ບໍ່ພົບສິນຄ້າທີ່ຄົ້ນຫາ
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                
                                <?php foreach ($products as $p): ?>
                                <tr class="hover:bg-blue-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900"><?php echo $p['name']; ?></div>
                                        <div class="text-xs text-gray-400 font-mono"><?php echo $p['barcode']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $p['stock'] < 10 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>">
                                            <?php echo number_format($p['stock']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-500 text-sm"><?php echo number_format($p['cost']); ?></td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-700 text-sm"><?php echo number_format($p['price']); ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-2 opacity-100 lg:opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="?edit=<?php echo $p['id']; ?>&search=<?php echo urlencode($search); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-yellow-100 text-yellow-600 hover:bg-yellow-200">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('ຕ້ອງການລຶບແທ້ບໍ່?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>