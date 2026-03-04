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
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການສະກຸນເງິນ - POS System</title>
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
            max-width: 1000px; margin: 0 auto; padding: 0 24px;
            height: 60px; display: flex; align-items: center; justify-content: space-between;
        }
        .content-card {
            background: #fff; border-radius: 20px;
            border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .btn-success {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: #fff; border: none; border-radius: 12px; padding: 12px 24px;
            font-weight: 600; font-size: 0.875rem; cursor: pointer;
            transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;
            font-family: inherit;
        }
        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(16,185,129,0.3); }
        .input-modern {
            width: 100%; padding: 14px 16px 14px 44px; background: #f9fafb;
            border: 2px solid #e5e7eb; border-radius: 12px;
            font-size: 1rem; color: #1f2937; outline: none;
            transition: all 0.25s ease; font-family: inherit;
        }
        .input-modern:focus { background: #fff; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
        .table-modern { width: 100%; border-collapse: collapse; }
        .table-modern thead th {
            padding: 14px 16px; text-align: left; background: #f9fafb; color: #6b7280;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;
        }
        .table-modern tbody td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; }
        .table-modern tbody tr:hover { background: #f9fafb; }
        .icon-badge { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .icon-indigo { background: #eef2ff; color: #6366f1; }
        .icon-green { background: #d1fae5; color: #10b981; }
        .icon-amber { background: #fef3c7; color: #d97706; }
        .badge-primary { background: #eef2ff; color: #6366f1; padding: 5px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .badge-green { background: #d1fae5; color: #059669; padding: 5px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .badge-code { background: #f3f4f6; color: #6b7280; font-family: 'Inter', monospace; font-size: 0.85rem; padding: 5px 12px; border-radius: 8px; font-weight: 600; }
        .example-box {
            background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 14px; padding: 18px;
        }
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
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="icon-badge icon-indigo"><i class="fas fa-coins"></i></div>
                    <div>
                        <h1 style="font-size:1.15rem; font-weight:700; color:#1f2937;">ຈັດການສະກຸນເງິນ</h1>
                        <p style="font-size:0.75rem; color:#9ca3af;">ຕັ້ງຄ່າອັດຕາແລກປ່ຽນ</p>
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="background:#f9fafb; padding:8px 14px; border-radius:10px; font-size:0.85rem; color:#6b7280; border:1px solid #e5e7eb;">
                    <i class="fas fa-user-shield" style="color:#6366f1; margin-right:6px;"></i><?php echo $_SESSION['user_name']; ?>
                </div>
                <a href="logout.php" style="background:#fef2f2; color:#ef4444; padding:8px 14px; border-radius:10px; font-size:0.85rem; font-weight:600; border:1px solid #fecaca;">
                    <i class="fas fa-sign-out-alt" style="margin-right:4px;"></i>ອອກ
                </a>
            </div>
        </div>
    </nav>

    <div style="max-width:1000px; margin:0 auto; padding:24px;">
        <?php if (isset($success)): ?>
            <div class="alert-success" style="margin-bottom:20px;">
                <i class="fas fa-check-circle" style="font-size:1.1rem; color:#22c55e;"></i>
                <span>ອັບເດດອັດຕາແລກປ່ຽນສຳເລັດແລ້ວ!</span>
            </div>
        <?php endif; ?>

        <!-- Exchange Rate Card -->
        <div class="content-card" style="padding:28px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:24px; padding-bottom:18px; border-bottom:1px solid #f3f4f6;">
                <div class="icon-badge icon-indigo"><i class="fas fa-exchange-alt"></i></div>
                <div>
                    <h3 style="font-size:1.1rem; font-weight:700; color:#1f2937;">ອັດຕາແລກປ່ຽນ</h3>
                    <p style="font-size:0.8rem; color:#9ca3af;">ກຳນົດຄ່າແລກປ່ຽນລະຫວ່າງບາດ ແລະ ກີບ</p>
                </div>
            </div>
            <form method="POST">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:10px;">
                            <i class="fas fa-calculator" style="color:#6366f1; margin-right:6px;"></i>1 ບາດ = ? ກີບ
                        </label>
                        <div style="position:relative;">
                            <input type="number" name="lak_rate" value="<?php echo $lak_rate; ?>" min="1" step="0.01" class="input-modern" onchange="updateExample()">
                            <div style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#6366f1;"><i class="fas fa-coins"></i></div>
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:10px;">
                            <i class="fas fa-eye" style="color:#10b981; margin-right:6px;"></i>ຕົວຢ່າງການແປງ
                        </label>
                        <div class="example-box">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:40px; height:40px; background:#eef2ff; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                                        <span style="color:#6366f1; font-weight:700;">฿</span>
                                    </div>
                                    <span style="font-weight:600; color:#374151;">100 ບາດ</span>
                                </div>
                                <i class="fas fa-arrow-right" style="color:#6366f1;"></i>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="font-weight:700; color:#6366f1; font-size:1.1rem;" id="exampleConversion"><?php echo number_format(100 * $lak_rate); ?></span>
                                    <div style="width:40px; height:40px; background:#eef2ff; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                                        <span style="color:#6366f1; font-weight:700;">₭</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end;">
                    <button type="submit" name="update_rate" class="btn-success"><i class="fas fa-save"></i> ບັນທຶກອັດຕາແລກປ່ຽນ</button>
                </div>
            </form>
        </div>

        <!-- Currencies Table -->
        <div class="content-card" style="overflow:hidden; margin-bottom:24px;">
            <div style="padding:20px 24px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:14px;">
                <div class="icon-badge icon-green"><i class="fas fa-globe"></i></div>
                <div>
                    <h3 style="font-size:1.05rem; font-weight:700; color:#1f2937;">ສະກຸນເງິນທີ່ຮອງຮັບ</h3>
                    <p style="font-size:0.8rem; color:#9ca3af;">ລາຍການສະກຸນເງິນທັງໝົດໃນລະບົບ</p>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag" style="margin-right:6px;"></i>ລະຫັດ</th>
                            <th><i class="fas fa-tag" style="margin-right:6px;"></i>ຊື່</th>
                            <th><i class="fas fa-percentage" style="margin-right:6px;"></i>ອັດຕາແລກປ່ຽນ</th>
                            <th><i class="fas fa-info-circle" style="margin-right:6px;"></i>ສະຖານະ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currencies as $currency): ?>
                            <tr>
                                <td><span class="badge-code"><?php echo $currency['code']; ?></span></td>
                                <td style="font-weight:500; color:#374151;"><?php echo $currency['name']; ?></td>
                                <td>
                                    <?php if ($currency['code'] === 'THB'): ?>
                                        <span style="color:#9ca3af;">1 <span style="font-size:0.75rem;">(ສະກຸນເງິນຫຼັກ)</span></span>
                                    <?php else: ?>
                                        <span style="font-weight:700; color:#6366f1;"><?php echo number_format($currency['rate'], 2); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($currency['is_default']): ?>
                                        <span class="badge-primary"><i class="fas fa-star"></i> ສະກຸນເງິນຫຼັກ</span>
                                    <?php else: ?>
                                        <span class="badge-green"><i class="fas fa-circle" style="font-size:6px;"></i> ສະກຸນເງິນຮອງ</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tips Card -->
        <div class="content-card" style="padding:24px;">
            <div style="display:flex; gap:16px;">
                <div class="icon-badge icon-amber" style="flex-shrink:0;"><i class="fas fa-lightbulb"></i></div>
                <div>
                    <h4 style="font-weight:700; color:#1f2937; margin-bottom:10px;">ຄຳແນະນຳ</h4>
                    <ul style="list-style:none; padding:0; margin:0; font-size:0.875rem; color:#6b7280; display:flex; flex-direction:column; gap:8px;">
                        <li><i class="fas fa-check" style="color:#10b981; margin-right:8px;"></i>ອັດຕາແລກປ່ຽນຈະຖືກນຳໃຊ້ໃນການຄິດໄລ່ລາຄາສິນຄ້າ</li>
                        <li><i class="fas fa-check" style="color:#10b981; margin-right:8px;"></i>ກະລຸນາອັບເດດອັດຕາແລກປ່ຽນເປັນປະຈຳເພື່ອຄວາມຖືກຕ້ອງ</li>
                        <li><i class="fas fa-check" style="color:#10b981; margin-right:8px;"></i>ລູກຄ້າສາມາດເລືອກຊຳລະເປັນບາດ ຫຼື ກີບໄດ້</li>
                    </ul>
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