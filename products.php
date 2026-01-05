<?php
require_once 'config.php';
checkLogin();

// กำหนดตัวแปรสำหรับข้อความแจ้งเตือน
$success = '';
$error = '';
$edit_product = null;

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO products (barcode, name, stock, cost, price) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['barcode'], $_POST['name'], $_POST['stock'], $_POST['cost'], $_POST['price']]);
                $success = "เพิ่มสินค้าเรียบร้อย";
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("UPDATE products SET barcode = ?, name = ?, stock = ?, cost = ?, price = ? WHERE id = ?");
                $stmt->execute([$_POST['barcode'], $_POST['name'], $_POST['stock'], $_POST['cost'], $_POST['price'], $_POST['id']]);
                $success = "แก้ไขสินค้าเรียบร้อย";
                break;
                
            case 'delete':
                // ตรวจสอบสิทธิ์ผู้ใช้: เฉพาะแอดมินเท่านั้นที่ลบได้
                if ($_SESSION['user_role'] === 'admin') {
                    // ถ้าเป็นแอดมิน ให้ลบสินค้าได้ทันทีโดยไม่ต้องเช็คประวัติการขาย
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $success = "ลบสินค้าเรียบร้อย";
                    
                } else {
                    $error = "คุณไม่มีสิทธิ์ลบสินค้า";
                }
                break;
        }
    }
}

// Get products
$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll();

// Get product for editing
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_product = $stmt->fetch();
    if (!$edit_product) {
        $error = "ไม่พบสินค้าที่ต้องการแก้ไข";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - ระบบขายเสื้อผ้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <nav class="bg-blue-700 text-white shadow-lg z-50">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fas fa-tshirt text-2xl text-blue-300"></i>
                <h1 class="text-2xl font-bold">ระบบขายเสื้อผ้า</h1>
            </div>
            <a href="index.php" class="bg-blue-600 hover:bg-blue-800 transition-colors px-3 py-1 rounded-full text-sm font-medium">
                <i class="fas fa-home mr-2"></i>หน้าหลัก
            </a>
        </div>
    </nav>

    <div class="flex-1 p-6 lg:p-10">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">จัดการสินค้า</h2>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <div id="productForm" class="bg-white p-6 rounded-2xl shadow-lg mb-6 <?php echo $edit_product ? '' : 'hidden'; ?>">
                <h3 class="text-xl font-semibold text-gray-800 mb-4"><?php echo $edit_product ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่'; ?></h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                    <input type="hidden" name="id" value="<?php echo $edit_product['id'] ?? ''; ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">บาร์โค้ด</label>
                        <input type="text" name="barcode" value="<?php echo $edit_product['barcode'] ?? ''; ?>" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อสินค้า</label>
                        <input type="text" name="name" value="<?php echo $edit_product['name'] ?? ''; ?>" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">สต็อก</label>
                        <input type="number" name="stock" value="<?php echo $edit_product['stock'] ?? '0'; ?>" min="0" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ต้นทุน (บาท)</label>
                            <input type="number" name="cost" value="<?php echo $edit_product['cost'] ?? '0'; ?>" step="0.01" min="0" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ราคาขาย (บาท)</label>
                            <input type="number" name="price" value="<?php echo $edit_product['price'] ?? '0'; ?>" step="0.01" min="0" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">กำไร/ชิ้น</label>
                            <input type="text" id="profitDisplay" value="<?php echo ($edit_product) ? number_format($edit_product['price'] - $edit_product['cost'], 2) : '0.00'; ?>" class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" readonly>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                            <i class="fas fa-save mr-2"></i>บันทึก
                        </button>
                        <button type="button" onclick="hideForm()" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors font-semibold">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">รายการสินค้าทั้งหมด</h3>
                    <button onclick="showAddForm()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors font-semibold flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i>เพิ่มสินค้า
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-gray-600">
                        <thead>
                            <tr class="bg-gray-50 text-gray-700 uppercase tracking-wider font-semibold text-left">
                                <th class="py-3 px-4 rounded-tl-lg">บาร์โค้ด</th>
                                <th class="py-3 px-4">ชื่อสินค้า</th>
                                <th class="py-3 px-4 text-right">สต็อก</th>
                                <th class="py-3 px-4 text-right">ราคาขาย</th>
                                <th class="py-3 px-4 text-right">กำไร</th>
                                <th class="py-3 px-4 text-center rounded-tr-lg">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">ไม่มีสินค้าในระบบ</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4"><?php echo $product['barcode']; ?></td>
                                        <td class="py-3 px-4 font-medium text-gray-900"><?php echo $product['name']; ?></td>
                                        <td class="py-3 px-4 text-right"><?php echo number_format($product['stock']); ?></td>
                                        <td class="py-3 px-4 text-right"><?php echo number_format($product['price'], 2); ?></td>
                                        <td class="py-3 px-4 text-right text-green-600 font-medium"><?php echo number_format($product['price'] - $product['cost'], 2); ?></td>
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <a href="?edit=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-edit mr-1"></i>แก้ไข
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('คุณต้องการลบสินค้า <?php echo htmlspecialchars($product['name']); ?> หรือไม่?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800 ml-2">
                                                    <i class="fas fa-trash mr-1"></i>ลบ
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('productForm').classList.remove('hidden');
            document.querySelector('form').reset();
            document.querySelector('input[name="action"]').value = 'add';
            document.querySelector('h3').textContent = 'เพิ่มสินค้าใหม่';
        }

        function hideForm() {
            document.getElementById('productForm').classList.add('hidden');
        }

        // Calculate profit
        function calculateProfit() {
            const cost = parseFloat(document.querySelector('input[name="cost"]').value) || 0;
            const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
            document.getElementById('profitDisplay').value = (price - cost).toFixed(2);
        }

        document.querySelector('input[name="cost"]').addEventListener('input', calculateProfit);
        document.querySelector('input[name="price"]').addEventListener('input', calculateProfit);

        // Calculate initial profit if editing
        <?php if ($edit_product): ?>
            calculateProfit();
        <?php endif; ?>
    </script>
</body>
</html>