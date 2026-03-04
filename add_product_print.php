<?php
require_once 'config.php';

// API requests - ກວດສອບ session ກ່ອນ
if (isset($_GET['action']) || (isset($_POST['action']) && $_POST['action'] === 'mark_printed')) {
    // ກວດສອບວ່າ login ແລ້ວບໍ່
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ກະລຸນາ login ກ່ອນ']);
        exit;
    }
}

// ສຳລັບການໂຫຼດໜ້າ HTML ຕ້ອງ login
if (!isset($_GET['action']) && !isset($_POST['action'])) {
    checkLogin();
}

// ເພີ່ມ column barcode_printed ຖ້າຍັງບໍ່ມີ
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS barcode_printed TINYINT(1) DEFAULT 0");
} catch (Exception $e) {
    // ຖ້າມີແລ້ວກໍ່ບໍ່ເປັນຫຍັງ
}

// API: ດຶງລາຍການສິນຄ້າໃໝ່ລ່າສຸດ ຫຼື ຕາມວັນທີ
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_recent') {
    header('Content-Type: application/json');
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : null;
        $showUnprintedOnly = isset($_GET['unprinted']) && $_GET['unprinted'] === '1';
        
        $sql = "SELECT id, barcode, name, stock, price, IFNULL(barcode_printed, 0) as barcode_printed, created_at FROM products WHERE 1=1";
        $params = [];
        
        if ($date) {
            $sql .= " AND DATE(created_at) = :date";
            $params[':date'] = $date;
        }
        
        if ($showUnprintedOnly) {
            $sql .= " AND (barcode_printed = 0 OR barcode_printed IS NULL)";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT " . intval($limit);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $products]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// API: ດຶງລາຍການວັນທີທີ່ມີສິນຄ້າ
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_dates') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count, SUM(CASE WHEN barcode_printed = 0 OR barcode_printed IS NULL THEN 1 ELSE 0 END) as unprinted FROM products GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30");
        $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $dates]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// API: ອັບເດດສະຖານະພິມບາໂຄດ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_printed') {
    header('Content-Type: application/json');
    try {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE products SET barcode_printed = 1 WHERE id IN ($placeholders)");
            $stmt->execute($ids);
        }
        echo json_encode(['success' => true, 'message' => 'ອັບເດດສະຖານະສຳເລັດ!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// ສ່ວນຈັດການບັນທຶກຂໍ້ມູນລົງຖານຂໍ້ມູນ (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $barcode = strtoupper(trim($_POST['barcode'] ?? ''));
    $name = $_POST['name'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $cost = $_POST['cost'] ?? 0;
    $price = $_POST['price'] ?? 0;

    if (!$barcode || !$name) {
        echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນລະຫັດບາໂຄດ ແລະ ຊື່ສິນຄ້າ']);
        exit;
    }

    try {
        // ກວດສອບບາໂຄດຊ້ຳ
        $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = ?");
        $stmt->execute([$barcode]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ບາໂຄດນີ້ມີໃນລະບົບແລ້ວ!']);
            exit;
        }

        // ບັນທຶກຂໍ້ມູນ
        $stmt = $pdo->prepare("INSERT INTO products (barcode, name, stock, cost, price, barcode_printed) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$barcode, $name, $stock, $cost, $price]);
        
        $newId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'ບັນທຶກສຳເລັດ!', 'id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເພີ່ມສິນຄ້າ & ພິມບາໂຄດ - POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        * { font-family: 'Noto Sans Lao', 'Inter', sans-serif; }
        
        body {
            background: #f0f2f5;
            min-height: 100vh;
        }

        /* Cards */
        .card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        /* Icon badges */
        .icon-primary {
            background: linear-gradient(135deg, #6366f1, #818cf8);
        }
        .icon-indigo {
            background: linear-gradient(135deg, #6366f1, #818cf8);
        }
        .icon-success {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: white;
            border-radius: 12px;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.35);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border-radius: 12px;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.35);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: white;
            border-radius: 12px;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239,68,68,0.35);
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        .btn-ghost:hover {
            background: #f9fafb;
        }

        /* Inputs */
        .input-field {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        .input-field:focus {
            background: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
            outline: none;
        }

        /* Tables */
        .table-header {
            background: #f9fafb;
        }
        .table-header th {
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Scrollbar */
        .scroll-container {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f0f2f5; border-radius: 3px; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        /* Checkbox */
        .recent-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
            accent-color: #6366f1;
        }

        tbody tr {
            cursor: pointer;
        }
        tbody tr:hover {
            background-color: #f0f2ff !important;
        }

        /* Section title */
        .section-title {
            color: #111827;
            font-weight: 700;
        }
        .section-subtitle {
            color: #6b7280;
        }

        /* Filter box */
        .filter-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        /* Info bar */
        .info-bar {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }
    </style>
</head>
<body class="min-h-screen">

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="index.php" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-all border border-gray-200">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">ເພີ່ມສິນຄ້າ & ພິມບາໂຄດ</h1>
                    <p class="text-xs text-gray-400">ເພີ່ມສິນຄ້າໃໝ່ ແລະ ພິມສະຕິກເກີ</p>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Add Product Form -->
            <div class="card p-6">
                <h2 class="text-lg section-title mb-6 flex items-center">
                    <div class="w-10 h-10 icon-primary rounded-xl flex items-center justify-center text-white mr-3">
                        <i class="fas fa-plus"></i>
                    </div>
                    ຂໍ້ມູນສິນຄ້າໃໝ່
                </h2>
                <form id="productForm" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-barcode mr-2 text-indigo-500"></i>ບາໂຄດ</label>
                        <div class="flex gap-2">
                            <input type="text" id="barcode" name="barcode" class="input-field w-full p-3 uppercase font-medium" placeholder="ລະຫັດສິນຄ້າ..." required>
                            <button type="button" onclick="generateRandomBarcode()" class="btn-ghost text-gray-500 px-4" title="ສຸ່ມບາໂຄດ"><i class="fas fa-random"></i></button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-tag mr-2 text-indigo-500"></i>ຊື່ສິນຄ້າ</label>
                        <input type="text" id="name" name="name" class="input-field w-full p-3" placeholder="ຊື່ສິນຄ້າ..." required>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-boxes mr-2 text-indigo-500"></i>ຈຳນວນ</label>
                            <input type="number" id="stock" name="stock" value="1" min="1" class="input-field w-full p-3 text-center font-bold">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-coins mr-2 text-indigo-500"></i>ຕົ້ນທຶນ</label>
                            <input type="number" id="cost" name="cost" value="0" class="input-field w-full p-3 text-right font-medium">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-money-bill mr-2 text-indigo-500"></i>ລາຄາຂາຍ</label>
                            <input type="number" id="price" name="price" value="0" class="input-field w-full p-3 text-right font-bold">
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn-primary w-full py-3 font-bold">
                            <i class="fas fa-save mr-2"></i> ບັນທຶກ ແລະ ເພີ່ມລົງລາຍການພິມ
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Products -->
            <div class="card p-6 flex flex-col" style="max-height: 80vh;">
                <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200">
                    <h2 class="text-lg section-title flex items-center">
                        <div class="w-10 h-10 icon-indigo rounded-xl flex items-center justify-center text-white mr-3">
                            <i class="fas fa-history"></i>
                        </div>
                        ສິນຄ້າລ່າສຸດ
                    </h2>
                    <button onclick="loadRecentProducts()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium px-3 py-1 rounded-lg hover:bg-indigo-50 transition-all"><i class="fas fa-sync-alt mr-1"></i>ໂຫຼດໃໝ່</button>
                </div>
                
                <!-- Date Filter -->
                <div class="mb-4 p-4 filter-box">
                    <label class="block text-xs font-semibold text-gray-600 mb-2"><i class="fas fa-calendar-alt mr-1 text-indigo-500"></i>ເລືອກວັນທີ</label>
                    <div class="flex gap-2">
                        <select id="dateSelect" onchange="loadProductsByDate()" class="input-field flex-1 p-2 text-sm">
                            <option value="">-- ທັງໝົດ (ລ່າສຸດ 50) --</option>
                        </select>
                        <button onclick="loadAvailableDates()" class="btn-ghost text-indigo-600 px-3" title="ໂຫຼດວັນທີ">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <input type="checkbox" id="unprintedOnly" onchange="loadProductsByDate()" class="rounded accent-indigo-600">
                        <label for="unprintedOnly" class="text-xs text-red-600 font-semibold">ສະແດງສະເພາະທີ່ຍັງບໍ່ພິມ</label>
                    </div>
                </div>

                <div class="flex gap-2 mb-4">
                    <button onclick="selectAllRecent()" class="text-xs btn-primary px-3 py-2">ເລືອກທັງໝົດ</button>
                    <button onclick="selectUnprinted()" class="text-xs btn-danger px-3 py-2">ເລືອກທີ່ຍັງບໍ່ພິມ</button>
                    <button onclick="deselectAll()" class="text-xs btn-ghost px-3 py-2">ຍົກເລີກ</button>
                </div>

                <div class="flex-1 scroll-container bg-white rounded-xl p-3 mb-4 border border-gray-200" id="recentProductsContainer" style="max-height: 400px; min-height: 200px;">
                    <table class="w-full text-sm">
                        <thead class="table-header sticky top-0">
                            <tr>
                                <th class="pb-3 pt-2 text-left w-8"><input type="checkbox" id="selectAllCheck" onchange="toggleSelectAll(this)" class="accent-indigo-600"></th>
                                <th class="pb-3 pt-2 text-left">ສິນຄ້າ</th>
                                <th class="pb-3 pt-2 text-center">ສະຖານະ</th>
                            </tr>
                        </thead>
                        <tbody id="recentProductsBody">
                        </tbody>
                    </table>
                    <div id="recentEmptyMsg" class="text-center text-gray-400 mt-10"><i class="fas fa-spinner fa-spin mr-2"></i>ກຳລັງໂຫຼດ...</div>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <button onclick="addSelectedToQueue()" class="btn-primary w-full py-3 font-bold">
                        <i class="fas fa-plus mr-2"></i> ເພີ່ມທີ່ເລືອກລົງລາຍການພິມ
                    </button>
                </div>
            </div>

            <!-- Print Queue -->
            <div class="card p-6 flex flex-col" style="max-height: 80vh;">
                <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200">
                    <h2 class="text-lg section-title flex items-center">
                        <div class="w-10 h-10 icon-success rounded-xl flex items-center justify-center text-white mr-3">
                            <i class="fas fa-print"></i>
                        </div>
                        ລາຍການທີ່ຈະພິມ
                    </h2>
                    <button onclick="clearList()" class="text-sm text-red-500 hover:text-red-700 font-medium px-3 py-1 rounded-lg border border-red-200 hover:bg-red-50 transition-all"><i class="fas fa-trash mr-1"></i>ລ້າງ</button>
                </div>
                
                <div class="flex-1 scroll-container bg-white rounded-xl p-3 mb-4 border border-gray-200" id="printListContainer" style="max-height: 400px; min-height: 200px;">
                    <table class="w-full text-sm">
                        <thead class="table-header">
                            <tr>
                                <th class="pb-3 pt-2 text-left">ສິນຄ້າ</th>
                                <th class="pb-3 pt-2 text-center">ຈຳນວນດວງ</th>
                                <th class="pb-3 pt-2"></th>
                            </tr>
                        </thead>
                        <tbody id="printListBody">
                            </tbody>
                    </table>
                    <div id="emptyMsg" class="text-center text-gray-400 mt-10"><i class="fas fa-inbox text-4xl mb-3 block opacity-30"></i>ຍັງບໍ່ມີລາຍການ</div>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <div class="flex justify-between items-center mb-4 info-bar p-3">
                        <span class="text-sm text-gray-600 font-medium"><i class="fas fa-ruler mr-2 text-indigo-500"></i>ຂະໜາດ: 40mm x 20mm</span>
                        <span class="text-sm font-bold text-indigo-600" id="totalLabels">ລວມ: 0 ດວງ</span>
                    </div>
                    <button onclick="printAllBarcodes()" class="btn-success w-full py-3 font-bold">
                        <i class="fas fa-print mr-2"></i> ພິມບາໂຄດ
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        let printQueue = [];
        let recentProducts = [];

        // ໂຫຼດລາຍການວັນທີທີ່ມີສິນຄ້າ
        function loadAvailableDates() {
            fetch('add_product_print.php?action=get_dates')
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    console.log('Dates loaded:', data);
                    if (data.success) {
                        const select = document.getElementById('dateSelect');
                        select.innerHTML = '<option value="">-- ທັງໝົດ (ລ່າສຸດ 50) --</option>';
                        data.data.forEach(item => {
                            const unprintedBadge = item.unprinted > 0 ? ` (ຍັງບໍ່ພິມ: ${item.unprinted})` : '';
                            select.innerHTML += `<option value="${item.date}" ${item.unprinted > 0 ? 'class="text-red-600"' : ''}>${item.date} - ${item.count} ລາຍການ${unprintedBadge}</option>`;
                        });
                    } else {
                        console.error('Error loading dates:', data.message);
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                });
        }

        // ໂຫຼດສິນຄ້າຕາມວັນທີ
        function loadProductsByDate() {
            const selectedDate = document.getElementById('dateSelect').value;
            const unprintedOnly = document.getElementById('unprintedOnly').checked;
            
            let url = 'add_product_print.php?action=get_recent&limit=200';
            if (selectedDate) {
                url += '&date=' + selectedDate;
            }
            if (unprintedOnly) {
                url += '&unprinted=1';
            }
            
            console.log('Loading products from:', url);
            
            document.getElementById('recentEmptyMsg').innerText = 'ກຳລັງໂຫຼດ...';
            document.getElementById('recentEmptyMsg').classList.remove('hidden');
            document.getElementById('recentProductsBody').innerHTML = '';

            fetch(url)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok: ' + res.status);
                    return res.json();
                })
                .then(data => {
                    console.log('Products loaded:', data);
                    if (data.success) {
                        recentProducts = data.data;
                        renderRecentProducts();
                    } else {
                        document.getElementById('recentEmptyMsg').innerText = 'ເກີດຂໍ້ຜິດພາດ: ' + (data.message || 'Unknown error');
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    document.getElementById('recentEmptyMsg').innerText = 'ເກີດຂໍ້ຜິດພາດ: ' + err.message;
                });
        }

        // ໂຫຼດລາຍການສິນຄ້າລ່າສຸດ
        function loadRecentProducts() {
            document.getElementById('dateSelect').value = '';
            document.getElementById('unprintedOnly').checked = false;
            loadProductsByDate();
        }

        // ສະແດງລາຍການສິນຄ້າລ່າສຸດ
        function renderRecentProducts() {
            const tbody = document.getElementById('recentProductsBody');
            const emptyMsg = document.getElementById('recentEmptyMsg');
            tbody.innerHTML = '';

            if (recentProducts.length === 0) {
                emptyMsg.innerText = 'ບໍ່ມີສິນຄ້າ';
                emptyMsg.classList.remove('hidden');
            } else {
                emptyMsg.classList.add('hidden');
                recentProducts.forEach((product, index) => {
                    const isPrinted = product.barcode_printed == 1;
                    const statusClass = isPrinted ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600';
                    const statusText = isPrinted ? 'ພິມແລ້ວ' : 'ຍັງບໍ່ພິມ';
                    const rowClass = isPrinted ? '' : 'bg-red-50';
                    
                    const tr = document.createElement('tr');
                    tr.className = `border-b border-gray-100 last:border-0 ${rowClass}`;
                    tr.dataset.id = product.id;
                    tr.innerHTML = `
                        <td class="py-2 px-1">
                            <input type="checkbox" class="recent-checkbox" data-index="${index}" value="${product.id}">
                        </td>
                        <td class="py-2">
                            <div class="font-bold text-gray-800 text-xs">${product.name}</div>
                            <div class="text-xs text-gray-500 font-mono">${product.barcode}</div>
                            <div class="text-xs text-blue-600">${parseInt(product.price).toLocaleString()} ฿</div>
                        </td>
                        <td class="py-2 text-center">
                            <span class="text-xs px-2 py-1 rounded-full ${statusClass}">${statusText}</span>
                        </td>
                    `;
                    
                    // ຄລິກທີ່ແຖວເພື່ອ toggle checkbox
                    tr.addEventListener('click', function(e) {
                        if (e.target.type !== 'checkbox') {
                            const cb = this.querySelector('.recent-checkbox');
                            cb.checked = !cb.checked;
                        }
                    });
                    
                    tbody.appendChild(tr);
                });
            }
        }

        // ເລືອກທັງໝົດ
        function selectAllRecent() {
            document.querySelectorAll('.recent-checkbox').forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheck').checked = true;
        }

        // ເລືອກສະເພາະທີ່ຍັງບໍ່ພິມ
        function selectUnprinted() {
            document.querySelectorAll('.recent-checkbox').forEach(cb => {
                const index = cb.dataset.index;
                cb.checked = recentProducts[index].barcode_printed == 0;
            });
        }

        // ຍົກເລີກທັງໝົດ
        function deselectAll() {
            document.querySelectorAll('.recent-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheck').checked = false;
        }

        // Toggle ເລືອກທັງໝົດ
        function toggleSelectAll(checkbox) {
            document.querySelectorAll('.recent-checkbox').forEach(cb => cb.checked = checkbox.checked);
        }

        // ເພີ່ມລາຍການທີ່ເລືອກລົງ Queue ພິມ
        function addSelectedToQueue() {
            const selectedIds = [];
            document.querySelectorAll('.recent-checkbox:checked').forEach(cb => {
                const index = cb.dataset.index;
                const product = recentProducts[index];
                selectedIds.push(product.id);
                
                // ເພີ່ມລົງ Queue
                addToPrintQueue({
                    id: product.id,
                    code: product.barcode,
                    name: product.name,
                    price: product.price,
                    count: product.stock > 0 ? product.stock : 1
                });
            });

            if (selectedIds.length === 0) {
                alert('ກະລຸນາເລືອກສິນຄ້າກ່ອນ!');
                return;
            }

            alert('ເພີ່ມ ' + selectedIds.length + ' ລາຍການລົງລາຍການພິມແລ້ວ!');
            deselectAll();
        }

        // ໂຫຼດຂໍ້ມູນເມື່ອເປີດໜ້າ
        document.addEventListener('DOMContentLoaded', function() {
            loadAvailableDates();
            loadRecentProducts();
        });

        // ສຸ່ມບາໂຄດ
        function generateRandomBarcode() {
            const randomCode = Math.floor(Math.random() * 90000000) + 10000000;
            document.getElementById('barcode').value = randomCode;
        }

        // ຈັດການການສົ່ງຟອມ
        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('add_product_print.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // ບັນທຶກສຳເລັດ -> ເພີ່ມລົງ List ພິມ
                    addToPrintQueue({
                        id: data.id,
                        code: formData.get('barcode'),
                        name: formData.get('name'),
                        price: formData.get('price'),
                        count: parseInt(formData.get('stock')) || 1
                    });
                    
                    // ແຈ້ງເຕືອນ ແລະ ລ້າງຟອມ
                    alert('ບັນທຶກສຳເລັດ!');
                    this.reset();
                    document.getElementById('stock').value = 1;
                    document.getElementById('cost').value = 0;
                    document.getElementById('price').value = 0;
                    document.getElementById('barcode').focus();
                    
                    // ໂຫຼດລາຍການສິນຄ້າໃໝ່
                    loadRecentProducts();
                } else {
                    alert('ເກີດຂໍ້ຜິດພາດ: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່');
            });
        });

        // ເພີ່ມລົງຄິວ
        function addToPrintQueue(item) {
            // ກວດສອບວ່າມີໃນຄິວແລ້ວບໍ່
            const existing = printQueue.find(p => p.code === item.code);
            if(existing) {
                existing.count += item.count;
            } else {
                printQueue.push({
                    id: item.id || null,
                    code: item.code,
                    name: item.name,
                    price: item.price,
                    count: item.count || 1
                });
            }
            renderQueue();
        }

        // ສະແດງຜົນຄິວ
        function renderQueue() {
            const tbody = document.getElementById('printListBody');
            const emptyMsg = document.getElementById('emptyMsg');
            tbody.innerHTML = '';
            
            if(printQueue.length === 0) {
                emptyMsg.classList.remove('hidden');
            } else {
                emptyMsg.classList.add('hidden');
                let total = 0;
                printQueue.forEach((item, index) => {
                    total += item.count;
                    tbody.innerHTML += `
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="py-2">
                                <div class="font-bold text-gray-800">${item.name}</div>
                                <div class="text-xs text-gray-500 font-mono">${item.code}</div>
                                <div class="text-xs text-blue-600">${parseInt(item.price).toLocaleString()}</div>
                            </td>
                            <td class="py-2 text-center">
                                <input type="number" min="1" value="${item.count}" 
                                    onchange="updateCount(${index}, this.value)"
                                    class="w-16 p-1 border rounded text-center">
                            </td>
                            <td class="py-2 text-right">
                                <button onclick="removeFromQueue(${index})" class="text-red-400 hover:text-red-600">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                document.getElementById('totalLabels').innerText = `ລວມ: ${total} ດວງ`;
            }
        }

        function updateCount(index, val) {
            if(val > 0) {
                printQueue[index].count = parseInt(val);
                renderQueue();
            }
        }

        function removeFromQueue(index) {
            printQueue.splice(index, 1);
            renderQueue();
        }

        function clearList() {
            printQueue = [];
            renderQueue();
        }

        // ຟັງຊັນພິມ (ຄືກັບ script.js ແຕ່ປັບແຕ່ງໃຫ້ສະອາດຂຶ້ນ)
        function printAllBarcodes() {
            if (printQueue.length === 0) {
                alert("ກະລຸນາເພີ່ມລາຍການກ່ອນ!");
                return;
            }

            // ເກັບ IDs ສຳລັບອັບເດດສະຖານະ
            const productIds = printQueue.filter(item => item.id).map(item => item.id);

            let printContent = '';
            let barcodeIndex = 0;

            printQueue.forEach(item => {
                for (let i = 0; i < item.count; i++) {
                    const barcodeId = `barcode-${barcodeIndex++}`;
                    // ສ້າງ Block ບາໂຄດ
                    printContent += `
                        <div class="barcode-print">
                            <div class="label-content">
                                <div class="product-name">${item.name.substring(0, 15)}</div>
                                <svg id="${barcodeId}"></svg>
                                <div class="product-code">${item.code}</div>
                                ${item.price > 0 ? `<div class="price-text">${parseInt(item.price).toLocaleString()} ฿</div>` : ''}
                            </div>
                        </div>
                    `;
                }
            });

            // ອັບເດດສະຖານະພິມໃນຖານຂໍ້ມູນ
            if (productIds.length > 0) {
                const formData = new FormData();
                formData.append('action', 'mark_printed');
                formData.append('ids', JSON.stringify(productIds));
                
                fetch('add_product_print.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // ໂຫຼດລາຍການໃໝ່ເພື່ອອັບເດດສະຖານະ
                        loadRecentProducts();
                    }
                })
                .catch(err => console.error('Error updating print status:', err));
            }

            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Barcodes</title>
                    <style>
                        @page { size: 40mm 20mm; margin: 0; }
                        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
                        
                        .barcode-print {
                            width: 40mm;
                            height: 20mm;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            overflow: hidden;
                            box-sizing: border-box;
                            page-break-after: always;
                            text-align: center;
                        }
                        .label-content {
                            width: 100%;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            line-height: 1;
                        }
                        .product-name { font-size: 8px; font-weight: bold; margin-bottom: 2px; white-space: nowrap; overflow: hidden; }
                        svg { width: 90%; height: 25px; display: block; margin: 2px 0; }
                        .product-code { font-size: 8px; font-family: monospace; }
                        .price-text { font-size: 9px; font-weight: bold; margin-top: 2px; }
                    </style>
                    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                </head>
                <body>
                    ${printContent}
                    <script>
                        window.onload = function() {
                            const queue = ${JSON.stringify(printQueue)};
                            let idx = 0;
                            queue.forEach(item => {
                                for(let i=0; i<item.count; i++) {
                                    JsBarcode('#barcode-' + (idx++), item.code, {
                                        format: 'CODE128',
                                        width: 1.5,
                                        height: 30,
                                        displayValue: false,
                                        margin: 0
                                    });
                                }
                            });
                            
                            setTimeout(() => {
                                window.print();
                                window.close();
                            }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>