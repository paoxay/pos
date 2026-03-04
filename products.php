<?php
require_once 'config.php';
checkLogin();

$success = '';
$error = '';
$edit_product = null;
$search = $_GET['search'] ?? ''; // ຮັບຄ່າຄົ້ນຫາ
$sort = $_GET['sort'] ?? 'name'; // ຮັບຄ່າການຈັດລຽງ (Default: ຕາມຊື່)

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // ແກ້ໄຂ: ແປງບາໂຄດເປັນຕົວພິມໃຫຍ່ ແລະ ຕັດຍະຫວ່າງ
                $barcode = strtoupper(trim($_POST['barcode']));
                
                $stmt = $pdo->prepare("INSERT INTO products (barcode, name, stock, cost, price) VALUES (?, ?, ?, ?, ?)");
                try {
                    $stmt->execute([$barcode, $_POST['name'], $_POST['stock'], $_POST['cost'], $_POST['price']]);
                    $success = "ເພີ່ມສິນຄ້າສຳເລັດ!";
                } catch (PDOException $e) {
                    // ກວດສອບ Error Code 23000 (Duplicate entry)
                    if ($e->getCode() == 23000) {
                        $error = "ຜິດພາດ: ບາໂຄດ '$barcode' ມີໃນລະບົບແລ້ວ! ກະລຸນາໃຊ້ລະຫັດອື່ນ.";
                    } else {
                        $error = "ຜິດພາດ: ຂໍ້ມູນບໍ່ຖືກຕ້ອງ ຫຼື ເກີດຂໍ້ຜິດພາດໃນລະບົບ";
                    }
                }
                break;
                
            case 'edit':
                // ແກ້ໄຂ: ແປງບາໂຄດເປັນຕົວພິມໃຫຍ່ເຊັ່ນກັນ
                $barcode = strtoupper(trim($_POST['barcode']));
                
                $stmt = $pdo->prepare("UPDATE products SET barcode = ?, name = ?, stock = ?, cost = ?, price = ? WHERE id = ?");
                try {
                    $stmt->execute([$barcode, $_POST['name'], $_POST['stock'], $_POST['cost'], $_POST['price'], $_POST['id']]);
                    $success = "ແກ້ໄຂສິນຄ້າສຳເລັດ!";
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = "ຜິດພາດ: ບາໂຄດ '$barcode' ໄປຊ້ຳກັບສິນຄ້າອື່ນ!";
                    } else {
                        $error = "ຜິດພາດ: " . $e->getMessage();
                    }
                }
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

// ດຶງຂໍ້ມູນສິນຄ້າ (ພ້ອມລະບົບຄົ້ນຫາ ແລະ ຈັດລຽງ)
$sql = "SELECT * FROM products";
$params = [];

if ($search) {
    $sql .= " WHERE name LIKE ? OR barcode LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// ເພີ່ມເງື່ອນໄຂການຈັດລຽງ
if ($sort == 'newest') {
    $sql .= " ORDER BY id DESC"; // ລຽງຕາມ ID ລ່າສຸດ (ຫຼື created_at)
} else {
    $sql .= " ORDER BY name ASC"; // ລຽງຕາມຊື່
}

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
    <title>ຈັດການສິນຄ້າ - POS System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Noto Sans Lao', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            color: #1f2937;
            -webkit-font-smoothing: antialiased;
        }

        /* ─── Scrollbar ─── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        /* ─── Nav ─── */
        .top-nav {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .top-nav-inner {
            max-width: 1360px;
            margin: 0 auto;
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-left, .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-back {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 10px; border: 1px solid #e5e7eb;
            color: #6b7280; background: #fff;
            text-decoration: none;
            transition: all .15s ease;
        }
        .nav-back:hover { background: #f9fafb; color: #111827; border-color: #d1d5db; }
        .nav-title { font-size: 17px; font-weight: 700; color: #111827; }
        .nav-subtitle { font-size: 11px; color: #9ca3af; font-weight: 400; }
        .nav-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 10px;
            font-size: 13px; color: #6b7280;
            background: #f9fafb; border: 1px solid #e5e7eb;
        }
        .nav-badge strong { color: #6366f1; font-weight: 700; }

        /* ─── Layout ─── */
        .page-wrap { max-width: 1360px; margin: 0 auto; padding: 24px; }
        .grid-layout { display: grid; grid-template-columns: 1fr; gap: 24px; }
        @media (min-width: 1024px) {
            .grid-layout { grid-template-columns: 380px 1fr; }
        }

        /* ─── Content Card ─── */
        .content-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .card-body { padding: 24px; }

        /* ─── Alerts ─── */
        .alert {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 20px; border-radius: 14px; margin-bottom: 20px;
            font-size: 14px; font-weight: 500;
            border: 1px solid transparent;
        }
        .alert-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 14px;
        }
        .alert-success { background: #ecfdf5; border-color: #d1fae5; color: #065f46; }
        .alert-success .alert-icon { background: #10b981; color: #fff; }
        .alert-error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .alert-error .alert-icon { background: #ef4444; color: #fff; }

        /* ─── Buttons ─── */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 20px; border-radius: 12px; font-size: 13px; font-weight: 600;
            border: none; cursor: pointer; text-decoration: none;
            transition: all .15s ease; line-height: 1.2;
            font-family: inherit;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
            color: #fff; box-shadow: 0 1px 3px rgba(99,102,241,0.3);
        }
        .btn-primary:hover { box-shadow: 0 4px 14px rgba(99,102,241,0.35); transform: translateY(-1px); }
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: #fff; box-shadow: 0 1px 3px rgba(16,185,129,0.3);
        }
        .btn-success:hover { box-shadow: 0 4px 14px rgba(16,185,129,0.35); transform: translateY(-1px); }
        .btn-ghost {
            background: transparent; color: #6b7280;
            border: 1px solid #e5e7eb;
        }
        .btn-ghost:hover { background: #f9fafb; color: #374151; border-color: #d1d5db; }
        .btn-sm { padding: 7px 14px; font-size: 12px; border-radius: 10px; }
        .btn-icon {
            width: 34px; height: 34px; padding: 0; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-icon-edit { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .btn-icon-edit:hover { background: #fde68a; }
        .btn-icon-delete { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .btn-icon-delete:hover { background: #fecaca; }

        /* ─── Inputs ─── */
        .input-modern {
            width: 100%; padding: 11px 14px;
            background: #f9fafb; border: 2px solid #e5e7eb;
            border-radius: 12px; font-size: 14px;
            color: #1f2937; outline: none;
            transition: all .15s ease;
            font-family: inherit;
        }
        .input-modern:focus {
            background: #fff; border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        .input-modern::placeholder { color: #9ca3af; }
        .input-modern.text-center { text-align: center; }
        .input-modern.font-bold { font-weight: 700; }
        .input-modern.font-medium { font-weight: 500; }
        .input-modern.text-lg { font-size: 17px; }
        .input-modern.uppercase { text-transform: uppercase; }

        /* ─── Form ─── */
        .form-group { margin-bottom: 18px; }
        .form-label {
            display: block; margin-bottom: 6px;
            font-size: 13px; font-weight: 600; color: #374151;
        }
        .form-label i { color: #6366f1; margin-right: 6px; }
        .form-hint { font-size: 11px; color: #9ca3af; margin-top: 6px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-header {
            display: flex; align-items: center; gap: 12px;
            padding-bottom: 18px; margin-bottom: 20px;
            border-bottom: 1px solid #f3f4f6;
        }
        .form-header-icon {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 15px; flex-shrink: 0;
        }
        .form-header-icon.add { background: linear-gradient(135deg, #6366f1, #818cf8); }
        .form-header-icon.edit { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .form-header h3 { font-size: 16px; font-weight: 700; color: #111827; }
        .form-actions { display: flex; gap: 10px; padding-top: 10px; }
        .form-actions .btn-primary { flex: 1; padding: 12px 20px; }

        /* ─── Search & Sort Bar ─── */
        .search-bar { display: flex; gap: 10px; }
        .search-input-wrap {
            position: relative; flex: 1;
        }
        .search-input-wrap i {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #9ca3af; font-size: 14px;
        }
        .search-input-wrap .input-modern { padding-left: 40px; }
        .sort-row {
            display: flex; gap: 8px; margin-top: 16px;
            padding-top: 16px; border-top: 1px solid #f3f4f6;
        }
        .sort-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 16px; border-radius: 10px; font-size: 12px; font-weight: 600;
            text-decoration: none; transition: all .15s ease;
            border: 1px solid #e5e7eb; color: #6b7280; background: #fff;
        }
        .sort-pill:hover { background: #f9fafb; color: #374151; }
        .sort-pill.active {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: #fff; border-color: transparent;
            box-shadow: 0 1px 4px rgba(99,102,241,0.25);
        }
        .sort-pill.active-amber {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: #fff; border-color: transparent;
            box-shadow: 0 1px 4px rgba(245,158,11,0.25);
        }

        /* ─── Table ─── */
        .table-modern { width: 100%; border-collapse: collapse; white-space: nowrap; }
        .table-modern thead th {
            padding: 12px 20px;
            background: #f9fafb;
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.05em; color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        .table-modern thead th:first-child { padding-left: 24px; }
        .table-modern thead th:last-child { padding-right: 24px; }
        .table-modern tbody td {
            padding: 14px 20px; font-size: 14px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .table-modern tbody td:first-child { padding-left: 24px; }
        .table-modern tbody td:last-child { padding-right: 24px; }
        .table-modern tbody tr:last-child td { border-bottom: none; }
        .table-modern tbody tr { transition: background .1s ease; }
        .table-modern tbody tr:hover { background: #f9fafb; }

        .product-name { font-weight: 600; color: #111827; font-size: 14px; }
        .product-barcode {
            display: inline-block; margin-top: 4px;
            padding: 2px 8px; border-radius: 6px;
            font-size: 11px; font-family: 'SF Mono', 'Fira Code', monospace;
            background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;
        }

        /* ─── Badges ─── */
        .badge {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 4px 12px; border-radius: 9999px;
            font-size: 12px; font-weight: 700;
        }
        .badge-success { background: #ecfdf5; color: #059669; }
        .badge-danger { background: #fef2f2; color: #dc2626; }

        .date-tag {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 8px;
            font-size: 11px; font-weight: 600;
            background: #eef2ff; color: #4f46e5;
        }
        .date-sub { font-size: 10px; color: #9ca3af; margin-top: 2px; text-align: center; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-muted { color: #6b7280; }
        .text-dark { color: #111827; font-weight: 700; }
        .text-sm { font-size: 13px; }

        .actions-cell { display: flex; align-items: center; justify-content: center; gap: 6px; }

        .empty-state {
            padding: 48px 24px; text-align: center; color: #9ca3af;
        }
        .empty-state i { font-size: 40px; margin-bottom: 12px; opacity: 0.3; display: block; }
        .empty-state p { font-size: 14px; font-weight: 500; }

        .sticky-card { position: sticky; top: 84px; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="top-nav">
        <div class="top-nav-inner">
            <div class="nav-left">
                <a href="index.php" class="nav-back"><i class="fas fa-arrow-left"></i></a>
                <div>
                    <div class="nav-title">ຈັດການສິນຄ້າ</div>
                    <div class="nav-subtitle">ເພີ່ມ, ແກ້ໄຂ ແລະ ລຶບສິນຄ້າ</div>
                </div>
            </div>
            <div class="nav-right">
                <a href="import_json.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-file-import"></i> Import JSON
                </a>
                <div class="nav-badge">
                    <i class="fas fa-box" style="color:#6366f1"></i> ທັງໝົດ: <strong><?php echo count($products); ?></strong>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-wrap">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <div class="alert-icon"><i class="fas fa-check"></i></div>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">
                <div class="alert-icon"><i class="fas fa-exclamation"></i></div>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid-layout">
            <!-- Add/Edit Form -->
            <div>
                <div class="content-card sticky-card">
                    <div class="card-body">
                        <div class="form-header">
                            <?php if ($edit_product): ?>
                                <div class="form-header-icon edit"><i class="fas fa-edit"></i></div>
                                <h3>ແກ້ໄຂສິນຄ້າ</h3>
                            <?php else: ?>
                                <div class="form-header-icon add"><i class="fas fa-plus"></i></div>
                                <h3>ເພີ່ມສິນຄ້າໃໝ່</h3>
                            <?php endif; ?>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                            <input type="hidden" name="id" value="<?php echo $edit_product['id'] ?? ''; ?>">

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-barcode"></i>ບາໂຄດ</label>
                                <input type="text" name="barcode" value="<?php echo $edit_product['barcode'] ?? ''; ?>" class="input-modern uppercase font-medium" required placeholder="ລະຫັດສິນຄ້າ (A123...)">
                                <div class="form-hint"><i class="fas fa-info-circle" style="margin-right:4px"></i>ລະບົບຈະປ່ຽນເປັນຕົວພິມໃຫຍ່ອັດຕະໂນມັດ</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-tag"></i>ຊື່ສິນຄ້າ</label>
                                <input type="text" name="name" value="<?php echo $edit_product['name'] ?? ''; ?>" class="input-modern" required placeholder="ຊື່ສິນຄ້າ">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-boxes"></i>ຈຳນວນໃນສະຕັອກ</label>
                                <input type="number" name="stock" value="<?php echo $edit_product['stock'] ?? '0'; ?>" class="input-modern text-center font-bold text-lg" required>
                            </div>
                            <div class="form-group">
                                <div class="form-row">
                                    <div>
                                        <label class="form-label"><i class="fas fa-coins"></i>ຕົ້ນທຶນ</label>
                                        <input type="number" name="cost" step="0.01" value="<?php echo $edit_product['cost'] ?? '0'; ?>" class="input-modern text-center font-medium" required>
                                    </div>
                                    <div>
                                        <label class="form-label"><i class="fas fa-money-bill"></i>ລາຄາຂາຍ</label>
                                        <input type="number" name="price" step="0.01" value="<?php echo $edit_product['price'] ?? '0'; ?>" class="input-modern text-center font-bold" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> ບັນທຶກ
                                </button>
                                <?php if($edit_product): ?>
                                    <a href="products.php" class="btn btn-ghost">ຍົກເລີກ</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products List -->
            <div>
                <!-- Search & Sort -->
                <div class="content-card" style="margin-bottom:20px">
                    <div class="card-body">
                        <form method="GET" class="search-bar">
                            <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                            <div class="search-input-wrap">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                    class="input-modern" 
                                    placeholder="ຄົ້ນຫາຊື່ສິນຄ້າ ຫຼື ບາໂຄດ...">
                            </div>
                            <button type="submit" class="btn btn-primary">ຄົ້ນຫາ</button>
                            <?php if($search): ?>
                                <a href="products.php?sort=<?php echo $sort; ?>" class="btn btn-ghost btn-icon" title="ລ້າງ">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </form>

                        <div class="sort-row">
                            <a href="products.php?sort=name<?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                               class="sort-pill <?php echo $sort!='newest' ? 'active' : ''; ?>">
                                <i class="fas fa-sort-alpha-down"></i> ລຽງຕາມຊື່ (ກ-ຮ)
                            </a>
                            <a href="products.php?sort=newest<?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                               class="sort-pill <?php echo $sort=='newest' ? 'active-amber' : ''; ?>">
                                <i class="fas fa-clock"></i> ✨ ມາໃໝ່ລ່າສຸດ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="content-card" style="overflow:hidden">
                    <div style="overflow-x:auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th class="text-left">ສິນຄ້າ</th>
                                    <?php if($sort == 'newest'): ?>
                                        <th class="text-center">ວັນທີເພີ່ມ</th>
                                    <?php endif; ?>
                                    <th class="text-center">ສະຕັອກ</th>
                                    <th class="text-right">ຕົ້ນທຶນ</th>
                                    <th class="text-right">ລາຄາຂາຍ</th>
                                    <th class="text-center">ຈັດການ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($products) == 0): ?>
                                    <tr>
                                        <td colspan="<?php echo $sort == 'newest' ? '6' : '5'; ?>">
                                            <div class="empty-state">
                                                <i class="fas fa-box-open"></i>
                                                <p>ບໍ່ພົບສິນຄ້າທີ່ຄົ້ນຫາ</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <div class="product-name"><?php echo $p['name']; ?></div>
                                        <div class="product-barcode"><?php echo $p['barcode']; ?></div>
                                    </td>

                                    <?php if($sort == 'newest'): ?>
                                    <td class="text-center">
                                        <div>
                                            <div class="date-tag">
                                                📅 <?php echo date('d/m/y', strtotime($p['created_at'])); ?>
                                            </div>
                                            <div class="date-sub">
                                                ⏰ <?php echo date('H:i', strtotime($p['created_at'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <?php endif; ?>

                                    <td class="text-center">
                                        <span class="badge <?php echo $p['stock'] < 10 ? 'badge-danger' : 'badge-success'; ?>">
                                            <?php echo number_format($p['stock']); ?>
                                        </span>
                                    </td>
                                    <td class="text-right text-muted text-sm"><?php echo number_format($p['cost']); ?></td>
                                    <td class="text-right text-dark"><?php echo number_format($p['price']); ?></td>
                                    <td class="text-center">
                                        <div class="actions-cell">
                                            <a href="?edit=<?php echo $p['id']; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="btn btn-icon btn-icon-edit" title="ແກ້ໄຂ">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('ຕ້ອງການລຶບແທ້ບໍ່?');" style="display:inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="btn btn-icon btn-icon-delete" title="ລຶບ">
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

    <?php if ($error): ?>
<script>
    const audioErr = new Audio('https://www.soundjay.com/buttons/sounds/button-10.mp3');
    window.onload = function() {
        audioErr.play().catch(function(error) {
            console.log("Audio play failed: " + error);
        });
    };
</script>
<?php endif; ?>

</body>
</html>