<?php
require_once 'config.php';
checkLogin();
checkAdmin();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['update_rate'])) {
        $stmt = $pdo->prepare("UPDATE currencies SET rate = ? WHERE code = 'LAK'");
        $stmt->execute([$_POST['lak_rate']]);
        $success = "อัพเดทอัตราแลกเปลี่ยนเรียบร้อย";
    }
}

// Get currencies
$stmt = $pdo->query("SELECT * FROM currencies ORDER BY is_default DESC");
$currencies = $stmt->fetchAll();
$lak_rate = 270;
foreach ($currencies as $currency) {
    if ($currency['code'] === 'LAK') {
        $lak_rate = $currency['rate'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสกุลเงิน - ระบบขายเสื้อฝ้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-tshirt text-2xl"></i>
                    <h1 class="text-xl font-bold">ระบบขายเสื้อฝ้า</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="hover:text-blue-200">กลับหน้าหลัก</a>
                    <span class="text-sm"><?php echo $_SESSION['user_name']; ?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm">
                        <i class="fas fa-sign-out-alt mr-1"></i>ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-6">
        <h2 class="text-2xl font-bold mb-6">จัดการสกุลเงิน</h2>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">อัตราแลกเปลี่ยน</h3>
            <form method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">1 บาท = ? กีบ</label>
                        <input type="number" name="lak_rate" value="<?php echo $lak_rate; ?>" min="1" step="0.01" class="w-full p-3 border rounded-lg" onchange="updateExample()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">ตัวอย่างการแปลง</label>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p>100 บาท = <span id="exampleConversion"><?php echo number_format(100 * $lak_rate); ?></span> กีบ</p>
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" name="update_rate" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>บันทึกอัตราแลกเปลี่ยน
                    </button>
                </div>
            </form>
        </div>

        <!-- Currencies Table -->
        <div class="bg-white rounded-lg shadow mt-6">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">สกุลเงินที่รองรับ</h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">รหัส</th>
                                <th class="text-left py-2">ชื่อ</th>
                                <th class="text-left py-2">อัตราแลกเปลี่ยน</th>
                                <th class="text-left py-2">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currencies as $currency): ?>
                                <tr>
                                    <td class="py-2"><?php echo $currency['code']; ?></td>
                                    <td class="py-2"><?php echo $currency['name']; ?></td>
                                    <td class="py-2">
                                        <?php if ($currency['code'] === 'THB'): ?>
                                            1 (สกุลเงินหลัก)
                                        <?php else: ?>
                                            <?php echo number_format($currency['rate'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded text-sm <?php echo $currency['is_default'] ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <?php echo $currency['is_default'] ? 'สกุลเงินหลัก' : 'สกุลเงินรอง'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateExample() {
            const rate = parseFloat(document.querySelector('input[name="lak_rate"]').value);
            document.getElementById('exampleConversion').textContent = (100 * rate).toLocaleString();
        }
    </script>
</body>
</html>