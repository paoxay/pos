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
    <title>ເພີ່ມສິນຄ້າ & ພິມບາໂຄດ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; background-color: #f3f4f6; }
        .scroll-container {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .scroll-container::-webkit-scrollbar {
            width: 6px;
        }
        .scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .scroll-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        .scroll-container::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        /* ແກ້ໄຂບັນຫາ checkbox ຄລິກບໍ່ໄດ້ */
        .recent-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        tbody tr {
            cursor: pointer;
        }
        tbody tr:hover {
            background-color: #f0f9ff !important;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <nav class="bg-white shadow-sm border-b border-gray-100 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="index.php" class="bg-gray-100 p-2 rounded-lg hover:bg-gray-200 text-gray-600">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-800">ເພີ່ມສິນຄ້າ & ພິມບາໂຄດ</h1>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4 lg:p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-blue-600 mb-4"><i class="fas fa-plus-circle mr-2"></i>ຂໍ້ມູນສິນຄ້າໃໝ່</h2>
                <form id="productForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ບາໂຄດ</label>
                        <div class="flex gap-2">
                            <input type="text" id="barcode" name="barcode" class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-blue-500 outline-none uppercase" placeholder="ລະຫັດສິນຄ້າ..." required>
                            <button type="button" onclick="generateRandomBarcode()" class="bg-gray-100 text-gray-600 px-3 rounded-xl hover:bg-gray-200" title="ສຸ່ມບາໂຄດ"><i class="fas fa-random"></i></button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ຊື່ສິນຄ້າ</label>
                        <input type="text" id="name" name="name" class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="ຊື່ສິນຄ້າ..." required>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ຈຳນວນ</label>
                            <input type="number" id="stock" name="stock" value="1" min="1" class="w-full p-3 border rounded-xl text-center">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ຕົ້ນທຶນ</label>
                            <input type="number" id="cost" name="cost" value="0" class="w-full p-3 border rounded-xl text-right">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ລາຄາຂາຍ</label>
                            <input type="number" id="price" name="price" value="0" class="w-full p-3 border rounded-xl text-right font-bold text-blue-600">
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl hover:bg-blue-700 font-bold shadow-lg shadow-blue-500/30 transition-all">
                            <i class="fas fa-save mr-2"></i> ບັນທຶກ ແລະ ເພີ່ມລົງລາຍການພິມ
                        </button>
                    </div>
                </form>
            </div>

            <!-- ສ່ວນໃໝ່: ລາຍການສິນຄ້າທີ່ເພີ່ມໃໝ່ລ່າສຸດ -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col" style="max-height: 80vh;">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-lg font-bold text-purple-600"><i class="fas fa-history mr-2"></i>ສິນຄ້າລ່າສຸດ</h2>
                    <button onclick="loadRecentProducts()" class="text-sm text-blue-500 hover:text-blue-700"><i class="fas fa-sync-alt mr-1"></i>ໂຫຼດໃໝ່</button>
                </div>
                
                <!-- ເລືອກວັນທີ -->
                <div class="mb-3 p-3 bg-purple-50 rounded-xl">
                    <label class="block text-xs font-medium text-purple-700 mb-1"><i class="fas fa-calendar-alt mr-1"></i>ເລືອກວັນທີ</label>
                    <div class="flex gap-2">
                        <select id="dateSelect" onchange="loadProductsByDate()" class="flex-1 p-2 border rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none">
                            <option value="">-- ທັງໝົດ (ລ່າສຸດ 50) --</option>
                        </select>
                        <button onclick="loadAvailableDates()" class="bg-purple-100 text-purple-600 px-3 rounded-lg hover:bg-purple-200" title="ໂຫຼດວັນທີ">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" id="unprintedOnly" onchange="loadProductsByDate()" class="rounded">
                        <label for="unprintedOnly" class="text-xs text-red-600 font-medium">ສະແດງສະເພາະທີ່ຍັງບໍ່ພິມ</label>
                    </div>
                </div>

                <div class="flex gap-2 mb-3">
                    <button onclick="selectAllRecent()" class="text-xs bg-blue-100 text-blue-600 px-3 py-1 rounded-lg hover:bg-blue-200">ເລືອກທັງໝົດ</button>
                    <button onclick="selectUnprinted()" class="text-xs bg-red-100 text-red-600 px-3 py-1 rounded-lg hover:bg-red-200">ເລືອກທີ່ຍັງບໍ່ພິມ</button>
                    <button onclick="deselectAll()" class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-lg hover:bg-gray-200">ຍົກເລີກທັງໝົດ</button>
                </div>

                <div class="flex-1 scroll-container bg-gray-50 rounded-xl p-2 mb-4" id="recentProductsContainer" style="max-height: 400px; min-height: 200px;">
                    <table class="w-full text-sm">
                        <thead class="text-gray-500 border-b sticky top-0 bg-gray-50">
                            <tr>
                                <th class="pb-2 text-left w-8"><input type="checkbox" id="selectAllCheck" onchange="toggleSelectAll(this)"></th>
                                <th class="pb-2 text-left">ສິນຄ້າ</th>
                                <th class="pb-2 text-center">ສະຖານະ</th>
                            </tr>
                        </thead>
                        <tbody id="recentProductsBody">
                        </tbody>
                    </table>
                    <div id="recentEmptyMsg" class="text-center text-gray-400 mt-10">ກຳລັງໂຫຼດ...</div>
                </div>

                <div class="border-t pt-4">
                    <button onclick="addSelectedToQueue()" class="w-full bg-purple-600 text-white py-3 rounded-xl hover:bg-purple-700 font-bold shadow-lg shadow-purple-500/30 transition-all">
                        <i class="fas fa-plus mr-2"></i> ເພີ່ມທີ່ເລືອກລົງລາຍການພິມ
                    </button>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col" style="max-height: 80vh;">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-lg font-bold text-gray-800"><i class="fas fa-print mr-2"></i>ລາຍການທີ່ຈະພິມ</h2>
                    <button onclick="clearList()" class="text-sm text-red-500 hover:text-red-700">ລ້າງທັງໝົດ</button>
                </div>
                
                <div class="flex-1 scroll-container bg-gray-50 rounded-xl p-2 mb-4" id="printListContainer" style="max-height: 400px; min-height: 200px;">
                    <table class="w-full text-sm">
                        <thead class="text-gray-500 border-b">
                            <tr>
                                <th class="pb-2 text-left">ສິນຄ້າ</th>
                                <th class="pb-2 text-center">ຈຳນວນດວງ</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody id="printListBody">
                            </tbody>
                    </table>
                    <div id="emptyMsg" class="text-center text-gray-400 mt-10">ຍັງບໍ່ມີລາຍການ</div>
                </div>

                <div class="border-t pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-sm text-gray-600">ຂະໜາດ: 40mm x 20mm</span>
                        <span class="text-sm font-bold" id="totalLabels">ລວມ: 0 ດວງ</span>
                    </div>
                    <button onclick="printAllBarcodes()" class="w-full bg-green-600 text-white py-3 rounded-xl hover:bg-green-700 font-bold shadow-lg shadow-green-500/30 transition-all">
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