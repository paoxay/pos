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
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການພະນັກງານ - POS System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Noto Sans Lao', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5; min-height: 100vh; color: #1f2937;
        }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
        .top-nav {
            background: #fff; border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03); position: sticky; top: 0; z-index: 50;
        }
        .top-nav-inner {
            max-width: 1200px; margin: 0 auto; padding: 0 24px;
            height: 60px; display: flex; align-items: center; justify-content: space-between;
        }
        .content-card {
            background: #fff; border-radius: 20px;
            border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: #fff; border: none; border-radius: 12px; padding: 10px 20px;
            font-weight: 600; font-size: 0.875rem; cursor: pointer;
            transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;
            font-family: inherit;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(99,102,241,0.3); }
        .btn-success {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: #fff; border: none; border-radius: 12px; padding: 10px 20px;
            font-weight: 600; font-size: 0.875rem; cursor: pointer;
            transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;
            font-family: inherit;
        }
        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(16,185,129,0.3); }
        .btn-ghost {
            background: transparent; color: #6b7280; border: 1px solid #e5e7eb;
            border-radius: 12px; padding: 10px 20px; font-weight: 500; font-size: 0.875rem;
            cursor: pointer; transition: all 0.2s ease; font-family: inherit;
        }
        .btn-ghost:hover { background: #f9fafb; }
        .btn-danger-sm {
            width: 36px; height: 36px; border-radius: 10px;
            background: #fef2f2; border: 1px solid #fecaca; color: #ef4444;
            cursor: pointer; transition: all 0.2s ease;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-danger-sm:hover { background: #fee2e2; transform: translateY(-1px); }
        .input-modern {
            width: 100%; padding: 12px 16px; background: #f9fafb;
            border: 2px solid #e5e7eb; border-radius: 12px;
            font-size: 0.9rem; color: #1f2937; outline: none;
            transition: all 0.25s ease; font-family: inherit;
        }
        .input-modern:focus { background: #fff; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
        select.input-modern { cursor: pointer; appearance: auto; }
        .table-modern { width: 100%; border-collapse: collapse; }
        .table-modern thead th {
            padding: 14px 16px; text-align: left; background: #f9fafb; color: #6b7280;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;
        }
        .table-modern tbody td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .table-modern tbody tr:hover { background: #f9fafb; }
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
        }
        .badge-admin { background: #fef3c7; color: #d97706; }
        .badge-employee { background: #ede9fe; color: #7c3aed; }
        .badge-active { background: #d1fae5; color: #059669; }
        .badge-inactive { background: #f3f4f6; color: #6b7280; }
        .badge-code { background: #f3f4f6; color: #6b7280; font-family: 'Inter', monospace; font-size: 0.8rem; padding: 4px 10px; border-radius: 8px; display: inline-block; }
        .icon-badge { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .icon-indigo { background: #eef2ff; color: #6366f1; }
        .icon-green { background: #d1fae5; color: #10b981; }
        .alert-success {
            background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #22c55e;
            border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 12px;
            color: #166534; font-size: 0.9rem; font-weight: 500;
        }
        a { text-decoration: none; color: inherit; }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-inner">
            <div style="display:flex; align-items:center; gap:16px;">
                <a href="index.php" style="width:40px; height:40px; border-radius:12px; border:1px solid #e5e7eb; display:flex; align-items:center; justify-content:center; color:#6b7280; transition:all 0.2s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 style="font-size:1.15rem; font-weight:700; color:#1f2937;">ຈັດການພະນັກງານ</h1>
                    <p style="font-size:0.75rem; color:#9ca3af;">ເພີ່ມ ແລະ ລຶບພະນັກງານ</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="background:#f9fafb; padding:8px 14px; border-radius:10px; font-size:0.85rem; color:#6b7280; border:1px solid #e5e7eb;">
                    <i class="fas fa-user" style="color:#6366f1; margin-right:6px;"></i><?php echo $_SESSION['user_name']; ?>
                </div>
                <a href="logout.php" style="background:#fef2f2; color:#ef4444; padding:8px 14px; border-radius:10px; font-size:0.85rem; font-weight:600; border:1px solid #fecaca; transition:all 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                    <i class="fas fa-sign-out-alt" style="margin-right:4px;"></i>ອອກ
                </a>
            </div>
        </div>
    </nav>
    <div style="max-width:1200px; margin:0 auto; padding:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div class="icon-badge icon-indigo"><i class="fas fa-users"></i></div>
                <div>
                    <h2 style="font-size:1.35rem; font-weight:700; color:#1f2937;">ພະນັກງານທັງໝົດ</h2>
                    <p style="font-size:0.85rem; color:#9ca3af;"><?php echo count($employees); ?> ຄົນ</p>
                </div>
            </div>
            <button onclick="showAddForm()" class="btn-primary"><i class="fas fa-plus"></i> ເພີ່ມພະນັກງານ</button>
        </div>
        <?php if (isset($success)): ?>
            <div class="alert-success" style="margin-bottom:20px;">
                <div class="icon-badge icon-green" style="width:36px; height:36px; border-radius:10px; font-size:0.9rem;"><i class="fas fa-check"></i></div>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <div id="employeeForm" class="content-card" style="padding:28px; margin-bottom:24px; display:none;">
            <h3 style="font-size:1.05rem; font-weight:700; color:#1f2937; margin-bottom:20px; display:flex; align-items:center; gap:12px; padding-bottom:16px; border-bottom:1px solid #f3f4f6;">
                <div class="icon-badge icon-indigo" style="width:38px; height:38px; border-radius:10px; font-size:0.95rem;"><i class="fas fa-user-plus"></i></div>
                ເພີ່ມພະນັກງານໃໝ່
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:16px;">
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:8px;"><i class="fas fa-user" style="color:#6366f1; margin-right:6px;"></i>ຊື່-ນາມສະກຸນ</label>
                        <input type="text" name="name" class="input-modern" required placeholder="ຊື່ພະນັກງານ">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:8px;"><i class="fas fa-id-badge" style="color:#6366f1; margin-right:6px;"></i>ຕໍາແໜ່ງ</label>
                        <select name="role" class="input-modern" required>
                            <option value="employee">ພະນັກງານ</option>
                            <option value="admin">ຜູ້ດູແລລະບົບ</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:8px;"><i class="fas fa-at" style="color:#6366f1; margin-right:6px;"></i>ຊື່ຜູ້ໃຊ້</label>
                        <input type="text" name="username" class="input-modern" required placeholder="Username">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:8px;"><i class="fas fa-lock" style="color:#6366f1; margin-right:6px;"></i>ລະຫັດຜ່ານ</label>
                        <input type="password" name="password" class="input-modern" required placeholder="Password">
                    </div>
                </div>
                <div style="display:flex; gap:12px; margin-top:20px;">
                    <button type="submit" class="btn-success"><i class="fas fa-save"></i> ບັນທຶກ</button>
                    <button type="button" onclick="hideForm()" class="btn-ghost">ຍົກເລີກ</button>
                </div>
            </form>
        </div>
        <div class="content-card" style="overflow:hidden;">
            <div style="padding:20px; overflow-x:auto;">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>ຊື່-ນາມສະກຸນ</th>
                            <th>ຕໍາແໜ່ງ</th>
                            <th>ຊື່ຜູ້ໃຊ້</th>
                            <th>ສະຖານະ</th>
                            <th>ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td style="font-weight:600; color:#1f2937;"><?php echo $employee['name']; ?></td>
                                <td>
                                    <span class="badge <?php echo $employee['role'] === 'admin' ? 'badge-admin' : 'badge-employee'; ?>">
                                        <i class="fas <?php echo $employee['role'] === 'admin' ? 'fa-shield-alt' : 'fa-user'; ?>"></i>
                                        <?php echo $employee['role'] === 'admin' ? 'ຜູ້ດູແລລະບົບ' : 'ພະນັກງານ'; ?>
                                    </span>
                                </td>
                                <td><span class="badge-code"><?php echo $employee['username']; ?></span></td>
                                <td>
                                    <span class="badge <?php echo $employee['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                        <i class="fas fa-circle" style="font-size:6px;"></i>
                                        <?php echo $employee['status'] === 'active' ? 'ໃຊ້ງານໄດ້' : 'ປິດໃຊ້ງານ'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($employee['id'] == 1): ?>
                                        <span style="color:#9ca3af; font-size:0.85rem;"><i class="fas fa-shield-alt" style="margin-right:4px;"></i>ບໍ່ສາມາດລຶບໄດ້</span>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('ຕ້ອງການລຶບພະນັກງານຄົນນີ້ແທ້ບໍ່?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                                            <button type="submit" class="btn-danger-sm"><i class="fas fa-trash" style="font-size:0.8rem;"></i></button>
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
    <script>
        function showAddForm() { document.getElementById('employeeForm').style.display = 'block'; }
        function hideForm() { document.getElementById('employeeForm').style.display = 'none'; }
    </script>
</body>
</html>
