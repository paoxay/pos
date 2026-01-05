<?php
require_once 'config.php';
checkLogin();
checkAdmin();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO employees (name, username, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['username'], $_POST['password'], $_POST['role']]);
                $success = "เพิ่มพนักงานเรียบร้อย";
                break;
                
            case 'delete':
                if ($_POST['id'] != 1) { // ป้องกันการลบ admin หลัก
                    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $success = "ลบพนักงานเรียบร้อย";
                }
                break;
        }
    }
}

// Get employees
$stmt = $pdo->query("SELECT * FROM employees ORDER BY name");
$employees = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการพนักงาน - ระบบขายเสื้อฝ้า</title>
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
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">จัดการพนักงาน</h2>
            <button onclick="showAddForm()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>เพิ่มพนักงาน
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Add Employee Form -->
        <div id="employeeForm" class="bg-white p-6 rounded-lg shadow mb-6 hidden">
            <h3 class="text-lg font-semibold mb-4">เพิ่มพนักงานใหม่</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">ชื่อ-นามสกุล</label>
                        <input type="text" name="name" class="w-full p-3 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">ตำแหน่ง</label>
                        <select name="role" class="w-full p-3 border rounded-lg" required>
                            <option value="employee">พนักงาน</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">ชื่อผู้ใช้</label>
                        <input type="text" name="username" class="w-full p-3 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">รหัสผ่าน</label>
                        <input type="password" name="password" class="w-full p-3 border rounded-lg" required>
                    </div>
                </div>
                <div class="flex space-x-4 mt-6">
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-save mr-2"></i>บันทึก
                    </button>
                    <button type="button" onclick="hideForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>

        <!-- Employees Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">ชื่อ-นามสกุล</th>
                                <th class="text-left py-2">ตำแหน่ง</th>
                                <th class="text-left py-2">ชื่อผู้ใช้</th>
                                <th class="text-left py-2">สถานะ</th>
                                <th class="text-left py-2">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td class="py-2"><?php echo $employee['name']; ?></td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded text-sm <?php echo $employee['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <?php echo $employee['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน'; ?>
                                        </span>
                                    </td>
                                    <td class="py-2"><?php echo $employee['username']; ?></td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded text-sm <?php echo $employee['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $employee['status'] === 'active' ? 'ใช้งานได้' : 'ปิดใช้งาน'; ?>
                                        </span>
                                    </td>
                                    <td class="py-2">
                                        <?php if ($employee['id'] == 1): ?>
                                            <span class="text-gray-400">ไม่สามารถลบได้</span>
                                        <?php else: ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('คุณต้องการลบพนักงานคนนี้หรือไม่?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800">
                                                    <i class="fas fa-trash mr-1"></i>ลบ
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
        function showAddForm() {
            document.getElementById('employeeForm').classList.remove('hidden');
        }

        function hideForm() {
            document.getElementById('employeeForm').classList.add('hidden');
        }
    </script>
</body>
</html>