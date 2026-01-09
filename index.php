<?php
require_once 'config.php';
checkLogin();

// ດຶງຂໍ້ມູນສຳລັບແດຊບອດ
$stmt = $pdo->query("SELECT SUM(stock) as total_stock, SUM(stock * cost) as total_cost FROM products");
$stock_data = $stmt->fetch();

$stmt = $pdo->query("SELECT SUM(total) as today_sales, SUM(profit) as today_profit, COUNT(*) as today_orders FROM sales WHERE DATE(sale_date) = CURDATE()");
$sales_data = $stmt->fetch();

$stmt = $pdo->query("SELECT SUM(si.quantity) as today_items FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE DATE(s.sale_date) = CURDATE()");
$items_data = $stmt->fetch();

// ດຶງລາຍການຂາຍມື້ນີ້
$stmt = $pdo->query("
    SELECT s.*, si.*, p.name, p.barcode 
    FROM sales s 
    JOIN sale_items si ON s.id = si.sale_id 
    JOIN products p ON si.product_id = p.id 
    WHERE DATE(s.sale_date) = CURDATE() 
    ORDER BY s.sale_date DESC
");
$today_sales = $stmt->fetchAll();

// ດຶງຂໍ້ມູນສິນຄ້າ
$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll();

// ດຶງອັດຕາແລກປ່ຽນ
$stmt = $pdo->query("SELECT * FROM currencies");
$currencies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລະບົບຂາຍເສື້ອຜ້າ POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
 <style>
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Noto+Sans+Lao:wght@400;700&display=swap');
    
    body { 
        font-family: 'Sarabun', 'Noto Sans Lao', sans-serif; 
        background-color: #f3f4f6; 
    }
    
    /* Smooth Transition Animations */
    .fade-in-up { animation: fadeInUp 0.4s ease-out forwards; }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Active Menu Style */
    .nav-item.active {
        background: linear-gradient(to right, #eff6ff, #ffffff);
        border-right: 4px solid #2563eb;
        color: #2563eb;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* =========================================
       PRINT STYLES (Optimized for 80mm Thermal Printer)
       ========================================= */
    @media print {
        body * { visibility: hidden; }
        #receiptModal, #receiptModal * { visibility: visible; }
        #receiptModal {
            position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0;
            background: white !important; box-shadow: none !important;
        }
        #receiptModal button, .no-print-in-modal, .flex.gap-2 { display: none !important; }
        .receipt-print {
            width: 72mm !important; max-width: 100% !important; padding: 0 !important; margin: 0 auto !important;
            font-family: 'Noto Sans Lao', sans-serif !important; font-size: 14px !important;
            font-weight: bold !important; color: #000000 !important; line-height: 1.2 !important;
        }
        .border-dashed { border-style: dashed !important; border-width: 1.5px !important; border-color: #000 !important; }
        .receipt-print div { margin-bottom: 2px !important; }
        @page { size: 80mm auto; margin: 0mm; }
    }
</style>
</head>
<body class="bg-gray-50 h-screen flex overflow-hidden">

    <aside class="w-64 bg-white shadow-2xl z-20 hidden md:flex flex-col transition-all duration-300">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-8">
                <div class="bg-blue-600 text-white p-2 rounded-xl shadow-lg shadow-blue-500/30">
                    <i class="fas fa-tshirt text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800 tracking-wide">POS System</h1>
                    <p class="text-xs text-gray-500">ຮ້ານຂາຍເສື້ອຜ້າ</p>
                </div>
            </div>

            <ul class="space-y-2">
                <li>
                    <a href="#" onclick="showPage('dashboard', this)" class="nav-item active flex items-center p-3 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-blue-600 transition-all duration-200">
                        <i class="fas fa-chart-pie w-8 text-center"></i>
                        <span class="font-medium">ແດຊບອດ</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showPage('sell', this)" class="nav-item flex items-center p-3 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-blue-600 transition-all duration-200">
                        <i class="fas fa-cash-register w-8 text-center"></i>
                        <span class="font-medium">ຂາຍສິນຄ້າ</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showPage('sales', this)" class="nav-item flex items-center p-3 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-blue-600 transition-all duration-200">
                        <i class="fas fa-receipt w-8 text-center"></i>
                        <span class="font-medium">ປະຫວັດການຂາຍ</span>
                    </a>
                </li>
                <li>
                    <a href="products.php" class="nav-item flex items-center p-3 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-blue-600 transition-all duration-200">
                        <i class="fas fa-box w-8 text-center"></i>
                        <span class="font-medium">ຈັດການສິນຄ້າ</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showPage('shop', this)" class="nav-item flex items-center p-3 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-blue-600 transition-all duration-200">
                        <i class="fas fa-store w-8 text-center"></i>
                        <span class="font-medium">ຕັ້ງຄ່າຮ້ານຄ້າ</span>
                    </a>
                </li>
                 <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <div class="pt-4 pb-2">
                    <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Admin</p>
                </div>
                <li><a href="employees.php" class="nav-item flex items-center p-3 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-blue-600 transition-all duration-200"><i class="fas fa-users w-8 text-center"></i><span class="font-medium">ພະນັກງານ</span></a></li>
                <li><a href="currency.php" class="nav-item flex items-center p-3 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-blue-600 transition-all duration-200"><i class="fas fa-coins w-8 text-center"></i><span class="font-medium">ສະກຸນເງິນ</span></a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="mt-auto p-4 border-t border-gray-100">
            <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-xl">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                    <?php echo substr($_SESSION['user_name'], 0, 1); ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-bold text-gray-800 truncate"><?php echo $_SESSION['user_name']; ?></p>
                    <a href="logout.php" class="text-xs text-red-500 hover:text-red-700 font-medium">ອອກຈາກລະບົບ</a>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto overflow-x-hidden p-4 md:p-8 relative">
        <div class="md:hidden flex justify-between items-center mb-6 bg-white p-4 rounded-xl shadow-sm">
            <h1 class="text-lg font-bold text-blue-600">POS System</h1>
            <a href="logout.php" class="text-red-500"><i class="fas fa-sign-out-alt"></i></a>
        </div>

        <div id="dashboard" class="page fade-in-up">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">ພາບລວມລະບົບ</h2>
                <span class="text-sm text-gray-500"><?php echo date('d/m/Y'); ?></span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg shadow-blue-500/30">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-blue-100 text-sm font-medium mb-1">ຍອດຂາຍມື້ນີ້</p>
                            <h3 class="text-3xl font-bold"><?php echo number_format($sales_data['today_sales'] ?? 0); ?></h3>
                        </div>
                        <div class="p-2 bg-white/20 rounded-lg"><i class="fas fa-chart-line text-2xl"></i></div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-lg shadow-green-500/30">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-green-100 text-sm font-medium mb-1">ກຳໄລມື້ນີ້</p>
                            <h3 class="text-3xl font-bold"><?php echo number_format($sales_data['today_profit'] ?? 0); ?></h3>
                        </div>
                        <div class="p-2 bg-white/20 rounded-lg"><i class="fas fa-coins text-2xl"></i></div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-6 text-gray-800 shadow-md border border-gray-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm font-medium mb-1">ບິນຂາຍມື້ນີ້</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo number_format($sales_data['today_orders'] ?? 0); ?></h3>
                        </div>
                        <div class="p-2 bg-purple-100 text-purple-600 rounded-lg"><i class="fas fa-file-invoice text-2xl"></i></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 text-gray-800 shadow-md border border-gray-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm font-medium mb-1">ສິນຄ້າຄົງເຫຼືອ</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo number_format($stock_data['total_stock'] ?? 0); ?></h3>
                        </div>
                        <div class="p-2 bg-orange-100 text-orange-600 rounded-lg"><i class="fas fa-boxes text-2xl"></i></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">ລາຍການຂາຍລ່າສຸດ</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">ເວລາ</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">ບາໂຄດ</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">ສິນຄ້າ</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">ຈຳນວນ</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">ລາຄາລວມ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($today_sales)): ?>
                                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">ຍັງບໍ່ມີການຂາຍມື້ນີ້</td></tr>
                            <?php else: ?>
                                <?php foreach ($today_sales as $sale): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('H:i', strtotime($sale['sale_date'])); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 font-mono"><?php echo $sale['barcode']; ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo $sale['name']; ?></td>
                                    <td class="px-6 py-4 text-sm text-center"><span class="bg-blue-100 text-blue-700 py-1 px-3 rounded-full text-xs font-bold"><?php echo $sale['quantity']; ?></span></td>
                                    <td class="px-6 py-4 text-sm text-right font-bold text-gray-700"><?php echo number_format($sale['price'] * $sale['quantity']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="sell" class="page hidden fade-in-up">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">ຂາຍສິນຄ້າ</h2>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-150px)]">
                <div class="lg:col-span-7 flex flex-col gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <form id="sellForm">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">ລະຫັດບາໂຄດ</label>
                                <div class="flex gap-2">
                                    <input type="text" id="barcodeInput" class="w-full p-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-lg" placeholder="ສະແກນ ຫຼື ພິມລະຫັດ..." autofocus>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ເລືອກສິນຄ້າ</label>
                                    <select id="productSelect" class="w-full p-3 border border-gray-200 rounded-xl bg-gray-50">
                                        <option value="">-- ຄົ້ນຫາສິນຄ້າ --</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                data-barcode="<?php echo htmlspecialchars($product['barcode']); ?>" 
                                                data-price="<?php echo $product['price']; ?>" 
                                                data-cost="<?php echo $product['cost']; ?>" 
                                                data-stock="<?php echo $product['stock']; ?>" 
                                                data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                <?php echo htmlspecialchars($product['name']); ?> (ເຫຼືອ: <?php echo $product['stock']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ຈຳນວນ</label>
                                    <input type="number" id="quantityInput" value="1" min="1" class="w-full p-3 border border-gray-200 rounded-xl text-center font-bold">
                                </div>
                            </div>
                            <input type="hidden" id="priceInput"> <div class="flex gap-3">
                                <button type="button" onclick="addToCart()" class="flex-1 bg-blue-600 text-white p-4 rounded-xl hover:bg-blue-700 font-bold shadow-lg shadow-blue-500/30 transition-all active:scale-95">
                                    <i class="fas fa-plus-circle mr-2"></i> ເພີ່ມລົງກະຕ່າ
                                </button>
                                <button type="button" onclick="clearSellForm()" class="px-6 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 font-medium">
                                    <i class="fas fa-eraser"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-5 flex flex-col h-full">
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 flex flex-col h-full overflow-hidden">
                        <div class="p-5 bg-gray-800 text-white flex justify-between items-center">
                            <h3 class="font-bold text-lg"><i class="fas fa-shopping-cart mr-2"></i> ກະຕ່າສິນຄ້າ</h3>
                            <button onclick="clearCart()" class="text-red-300 hover:text-white text-sm"><i class="fas fa-trash mr-1"></i> ລ້າງ</button>
                        </div>
                        
                        <div id="cartItems" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                            <div class="text-gray-400 text-center py-10 flex flex-col items-center">
                                <i class="fas fa-basket-shopping text-4xl mb-3 opacity-30"></i>
                                <p>ຍັງບໍ່ມີສິນຄ້າໃນກະຕ່າ</p>
                            </div>
                        </div>

                        <div class="p-6 bg-white border-t border-gray-100 shadow-[0_-5px_20px_rgba(0,0,0,0.05)]">
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between text-gray-600">
                                    <span>ລວມເງິນ:</span>
                                    <span id="subtotal" class="font-medium">0</span>
                                </div>
                                <div class="flex justify-between items-center text-gray-600">
                                    <span>ສ່ວນຫຼຸດ:</span>
                                    <div class="flex items-center gap-2">
                                        <input type="number" id="discountInput" value="0" min="0" max="100" class="w-16 p-1 border rounded text-center text-sm" oninput="updateCartTotal()">
                                        <span class="text-xs">%</span>
                                    </div>
                                    <span id="discountAmount" class="text-red-500">-0</span>
                                </div>
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>ກຳໄລ:</span>
                                    <span id="realProfit">0 บาท</span>
                                </div>
                                <div class="flex justify-between items-end pt-4 border-t border-dashed">
                                    <span class="text-gray-800 font-bold text-lg">ຍອດຊຳລະ:</span>
                                    <div class="text-right">
                                        <div id="cartTotal" class="text-2xl font-bold text-blue-600">0</div>
                                        <div id="cartTotalLak" class="text-sm text-gray-500">0 ກີບ</div>
                                    </div>
                                </div>
                            </div>
                            <button onclick="showCheckoutConfirm()" id="checkoutBtn" disabled class="w-full bg-green-600 text-white p-4 rounded-xl font-bold text-lg shadow-lg shadow-green-500/30 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-95">
                                <i class="fas fa-money-bill-wave mr-2"></i> ຊຳລະເງິນ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="sales" class="page hidden fade-in-up">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">ປະຫວັດການຂາຍ</h2>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                    <div class="flex items-center gap-2 bg-gray-50 p-1 rounded-lg border border-gray-200">
                        <input type="date" id="salesDateFilter" class="bg-transparent border-none p-2 outline-none text-gray-700" value="<?php echo date('Y-m-d'); ?>">
                        <button onclick="filterSales()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="flex-1 max-w-xs ml-4">
                        <input type="text" id="salesSearchInput" onkeyup="searchSalesTable()" placeholder="ຄົ້ນຫາ ບາໂຄດ..." class="w-full p-2 border border-gray-200 rounded-lg outline-none focus:border-blue-500">
                    </div>

                    <div class="flex gap-4">
                        <div class="text-right">
                            <p class="text-xs text-gray-500">ຍອດຂາຍລວມ</p>
                            <p class="text-xl font-bold text-blue-600" id="summaryTotalSales">0</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">ກຳໄລລວມ</p>
                            <p class="text-xl font-bold text-green-600" id="summaryTotalProfit">0</p>
                        </div>
                    </div>
                </div>
                <div id="salesList" class="overflow-x-auto">
                    </div>
            </div>
        </div>

        <div id="shop" class="page hidden fade-in-up">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">ຕັ້ງຄ່າຮ້ານຄ້າ</h2>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 max-w-2xl p-8">
                <form id="shopForm" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ຊື່ຮ້ານຄ້າ</label>
                        <input type="text" id="shopName" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ທີ່ຢູ່</label>
                        <textarea id="shopAddress" rows="3" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ເບີໂທລະສັບ</label>
                        <input type="text" id="shopPhone" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">QR Code ຮັບເງິນ (ສະແດງທ້າຍບິນ)</label>
                        <div class="flex items-center gap-4">
                            <input type="file" id="shopQRInput" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <img id="shopQRPreview" src="" class="h-16 w-16 object-contain border rounded hidden">
                        </div>
                        <button type="button" onclick="removeQRCode()" class="text-xs text-red-500 mt-1 hover:underline">ລົບຮູບ QR</button>
                    </div>

                    <button type="button" onclick="saveShopInfo()" class="bg-blue-600 text-white px-8 py-3 rounded-xl hover:bg-blue-700 font-medium shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save mr-2"></i> ບັນທຶກຂໍ້ມູນ
                    </button>
                </form>
            </div>
        </div>

    </main>

    <div id="checkoutModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl transform transition-all scale-100">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800">ຢືນຢັນການຊຳລະເງິນ</h3>
                <button onclick="closeCheckoutModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-gray-50 p-4 rounded-xl space-y-2">
                    <div class="flex justify-between text-sm text-gray-600"><span>ຈຳນວນລາຍການ:</span><span id="confirmItemCount" class="font-medium">0</span></div>
                    <div class="flex justify-between text-sm text-gray-600"><span>ຍອດລວມ:</span><span id="confirmSubtotal" class="font-medium">0</span></div>
                    <div class="flex justify-between text-sm text-red-500"><span>ສ່ວນຫຼຸດ:</span><span id="confirmDiscount">0</span></div>
                    <div class="border-t border-dashed pt-2 mt-2 flex justify-between items-end">
                        <span class="font-bold text-gray-800">ຍອດຊຳລະສຸດທິ:</span>
                        <div class="text-right">
                            <div id="confirmTotal" class="text-xl font-bold text-blue-600">0</div>
                            <div id="confirmTotalLak" class="text-xs text-gray-500">0 ກີບ</div>
                        </div>
                    </div>
                </div>
                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" id="printReceiptCheck" class="w-5 h-5 text-blue-600 rounded" checked>
                    <span class="text-gray-700 font-medium">ພິມໃບເສັດອັດຕະໂນມັດ</span>
                </label>
                <div class="flex gap-3 pt-2">
                    <button onclick="closeCheckoutModal()" class="flex-1 py-3 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">ຍົກເລີກ</button>
                    <button onclick="confirmCheckout()" class="flex-1 py-3 text-white bg-green-600 hover:bg-green-700 rounded-xl font-bold shadow-lg shadow-green-500/30">ຢືນຢັນ</button>
                </div>
            </div>
        </div>
    </div>

    <div id="receiptModal" class="fixed inset-0 bg-black/80 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white w-[350px] shadow-2xl"> <div class="p-4 border-b flex justify-between items-center no-print-in-modal">
                <h3 class="font-bold">ໃບເສັດ</h3>
                <button onclick="closeReceiptModal()"><i class="fas fa-times"></i></button>
            </div>
            <div id="receiptContent" class="p-4 bg-white text-black font-mono text-sm">
                </div>
            <div class="p-4 border-t flex gap-2 no-print-in-modal">
                <button onclick="printReceipt()" class="flex-1 bg-blue-600 text-white py-2 rounded hover:bg-blue-700"><i class="fas fa-print mr-2"></i> ພິມ</button>
                <button onclick="closeReceiptModal()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded hover:bg-gray-300">ປິດ</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center">
        <div class="bg-white p-8 rounded-2xl shadow-2xl text-center max-w-sm mx-4 animate-bounce-in">
            <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4 text-4xl">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">ຊຳລະເງິນສຳເລັດ!</h3>
            <p class="text-gray-500 mb-6">ບັນທຶກຂໍ້ມູນການຂາຍຮຽບຮ້ອຍແລ້ວ</p>
            <div class="flex gap-3">
                <button onclick="showReceiptFromSuccess()" class="flex-1 py-2 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl font-medium">ພິມໃບເສັດ</button>
                <button onclick="closeSuccessModal()" class="flex-1 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">ປິດ</button>
            </div>
        </div>
    </div>

    <div id="adminDetailModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden">
            <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                <h3 class="font-bold text-gray-800"><i class="fas fa-info-circle text-blue-600 mr-2"></i>ລາຍລະອຽດການຂາຍ (Admin)</h3>
                <button onclick="document.getElementById('adminDetailModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                    <div><span class="text-gray-500">ເລກບິນ:</span> <span id="ad_saleId" class="font-bold"></span></div>
                    <div><span class="text-gray-500">ວັນທີ:</span> <span id="ad_saleDate" class="font-bold"></span></div>
                    <div><span class="text-gray-500">ພະນັກງານ:</span> <span id="ad_saleEmp" class="font-bold"></span></div>
                </div>
                
                <table class="w-full text-sm text-left mb-4">
                    <thead class="bg-gray-100 text-gray-600">
                        <tr>
                            <th class="p-2">ສິນຄ້າ/ບາໂຄດ</th>
                            <th class="p-2 text-center">ຈຳນວນ</th>
                            <th class="p-2 text-right">ຕົ້ນທຶນ</th>
                            <th class="p-2 text-right">ຂາຍ</th>
                            <th class="p-2 text-right text-green-600">ກຳໄລ</th>
                        </tr>
                    </thead>
                    <tbody id="ad_itemList" class="divide-y divide-gray-100"></tbody>
                    <tfoot class="bg-gray-50 font-bold">
                        <tr>
                            <td colspan="4" class="p-2 text-right">ກຳໄລລວມ:</td>
                            <td class="p-2 text-right text-green-600" id="ad_totalProfit">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="p-4 border-t bg-gray-50 text-right">
                <button onclick="document.getElementById('adminDetailModal').classList.add('hidden')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300">ປິດ</button>
            </div>
        </div>
    </div>

    <script>
        // ປະກາດສຽງແຈ້ງເຕືອນ (ໄວ້ເທິງສຸດເລີຍ) 🔊
    const soundError = new Audio('sound/new-notification-010-352755.mp3');
        let cart = [];
        const products = <?php echo json_encode($products); ?>;
        const currencies = <?php echo json_encode($currencies); ?>;
        let currentSale = null;

        // --- Navigation ---
        function showPage(pageId, element) {
            // Update Active Menu
            if(element) {
                document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
                element.classList.add('active');
            }
            
            // Show Page with Animation
            document.querySelectorAll('.page').forEach(page => {
                page.classList.add('hidden');
                page.classList.remove('fade-in-up');
            });
            
            const selectedPage = document.getElementById(pageId);
            selectedPage.classList.remove('hidden');
            void selectedPage.offsetWidth; // Trigger Reflow
            selectedPage.classList.add('fade-in-up');

            if (pageId === 'sell') {
                setTimeout(() => document.getElementById('barcodeInput').focus(), 100);
            } else if (pageId === 'sales') {
                filterSales();
            }
        }

        // --- Selling Logic ---
        document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const barcode = this.value.trim().toUpperCase();
                const product = products.find(p => p.barcode.trim().toUpperCase() === barcode);
                if (product) {
                    addProductToCart(product);
                    this.value = '';
                } else {
                    soundError.currentTime = 0; // ຣີເຊັດສຽງ
                    soundError.play();          // 🔊 ຫຼິ້ນສຽງ
                    alert('ບໍ່ພົບສິນຄ້ານີ້!');
                    this.value = ''; // ລ້າງຊ່ອງປ້ອນຂໍ້ມູນ
                }
            }
        });

        document.getElementById('productSelect').addEventListener('change', function() {
            if (this.value) {
                const product = products.find(p => p.id == this.value);
                document.getElementById('priceInput').value = product.price; // Set default price
            }
        });

        function addToCart() {
            const select = document.getElementById('productSelect');
            if (!select.value) return alert('ກະລຸນາເລືອກສິນຄ້າ');
            const product = products.find(p => p.id == select.value);
            addProductToCart(product);
        }

        function addProductToCart(product) {
            const qtyInput = document.getElementById('quantityInput');
            const qty = parseInt(qtyInput.value);
            
            // Check Stock
            // Check Stock
    if (qty > product.stock) {
        soundError.currentTime = 0;
        soundError.play(); // 🔊 ຫຼິ້ນສຽງ
        return alert(`ສິນຄ້າເຫຼືອພຽງ ${product.stock} ອັນ`); // ແຈ້ງເຕືອນ
    }

            const existingItem = cart.find(item => item.id == product.id);
            if (existingItem) {
                if (existingItem.quantity + qty > product.stock) return alert('ສິນຄ້າບໍ່ພໍ');
                existingItem.quantity += qty;
            } else {
                cart.push({
                    id: product.id,
                    barcode: product.barcode,
                    name: product.name,
                    price: parseFloat(product.price),
                    cost: parseFloat(product.cost),
                    quantity: qty,
                    stock: product.stock
                });
            }
            updateCartUI();
            
            // Reset Form Inputs except Quantity
            document.getElementById('productSelect').value = '';
            document.getElementById('barcodeInput').value = '';
            document.getElementById('barcodeInput').focus();
        }

        function clearSellForm() {
            document.getElementById('sellForm').reset();
            document.getElementById('quantityInput').value = 1;
        }

        function updateCartUI() {
            const container = document.getElementById('cartItems');
            const count = document.getElementById('cartCount'); // Optional usage
            
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="text-gray-400 text-center py-10 flex flex-col items-center">
                        <i class="fas fa-basket-shopping text-4xl mb-3 opacity-30"></i>
                        <p>ຍັງບໍ່ມີສິນຄ້າໃນກະຕ່າ</p>
                    </div>`;
                document.getElementById('checkoutBtn').disabled = true;
            } else {
                let html = '';
                cart.forEach((item, index) => {
                    html += `
                    <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-800 text-sm">${item.name}</h4>
                            <div class="text-xs text-gray-500">${item.price.toLocaleString()} x ${item.quantity}</div>
                        </div>
                        <div class="text-right mx-3">
                            <span class="font-bold text-blue-600 text-sm">${(item.price * item.quantity).toLocaleString()}</span>
                        </div>
                        <button onclick="removeFromCart(${index})" class="w-8 h-8 rounded-full bg-red-50 text-red-500 hover:bg-red-100 flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
                    </div>`;
                });
                container.innerHTML = html;
                document.getElementById('checkoutBtn').disabled = false;
            }
            calculateTotals(); // Call calculation whenever UI updates
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function clearCart() {
            cart = [];
            updateCartUI();
        }

        function calculateTotals() {
            // ນີ້ຄືຟັງຊັນຫຼັກທີ່ຖືກຮຽກໃຊ້ທັງຕອນອັບເດດກະຕ່າ ແລະ ຕອນພິມສ່ວນຫຼຸດ (oninput)
            updateCartTotal(); 
            // Return values for checkout modal
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discountPercent = parseFloat(document.getElementById('discountInput').value) || 0;
            const discountAmount = subtotal * (discountPercent / 100);
            const total = subtotal - discountAmount;
            
            // Profit calculation
            const totalCost = cart.reduce((sum, item) => sum + (item.cost * item.quantity), 0);
            const profit = total - totalCost;

            return { subtotal, discount: discountPercent, total, profit };
        }

        function updateCartTotal() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            // ໃຊ້ oninput ແລ້ວຄ່າຈະຖືກດຶງມາຄຳນວນທັນທີ
            const discountPercent = parseFloat(document.getElementById('discountInput').value) || 0;
            const discountAmount = subtotal * (discountPercent / 100);
            const total = subtotal - discountAmount;
            
            // ຄຳນວນກຳໄລ
            const totalCost = cart.reduce((sum, item) => sum + (item.cost * item.quantity), 0);
            const realProfit = total - totalCost; // ກຳໄລຄິດຈາກຍອດຂາຍຫຼັງຫັກສ່ວນຫຼຸດ

            const lakRate = currencies.find(c => c.code === 'LAK')?.rate || 1;
            const totalInLak = total * lakRate;
            
            // ອັບເດດ UI
            if(document.getElementById('subtotal')) {
                document.getElementById('subtotal').innerText = subtotal.toLocaleString();
                document.getElementById('discountAmount').innerText = '-' + discountAmount.toLocaleString();
                document.getElementById('cartTotal').innerText = total.toLocaleString();
                document.getElementById('cartTotalLak').innerText = totalInLak.toLocaleString() + ' ກີບ';
                document.getElementById('realProfit').innerText = realProfit.toLocaleString();
            }
        }

        // --- Checkout & Modals ---
        function showCheckoutConfirm() {
            const totals = calculateTotals();
            const lakRate = currencies.find(c => c.code === 'LAK')?.rate || 1;

            document.getElementById('confirmItemCount').innerText = cart.length + ' ລາຍການ';
            document.getElementById('confirmSubtotal').innerText = totals.subtotal.toLocaleString();
            document.getElementById('confirmDiscount').innerText = '-' + (totals.subtotal * totals.discount / 100).toLocaleString();
            document.getElementById('confirmTotal').innerText = totals.total.toLocaleString();
            document.getElementById('confirmTotalLak').innerText = (totals.total * lakRate).toLocaleString() + ' ກີບ';
            
            document.getElementById('checkoutModal').classList.remove('hidden');
        }

        function closeCheckoutModal() { document.getElementById('checkoutModal').classList.add('hidden'); }

        function confirmCheckout() {
            const totals = calculateTotals();
            closeCheckoutModal();

            fetch('process_sale.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ 
                    items: cart, 
                    subtotal: totals.subtotal, 
                    discount: totals.discount, 
                    total: totals.total, 
                    profit: totals.profit 
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    currentSale = data.sale;
                    document.getElementById('successModal').classList.remove('hidden');
                    if(document.getElementById('printReceiptCheck').checked) {
                        setTimeout(() => showReceipt(currentSale, true), 500);
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່'));
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            clearCart();
            document.getElementById('discountInput').value = 0;
            // ໂຫຼດໜ້າໃໝ່ເພື່ອອັບເດດສະຕັອກ
            location.reload(); 
        }

        // --- Receipt ---
        function showReceiptFromSuccess() { if(currentSale) showReceipt(currentSale, true); }

        function showReceipt(sale, autoPrint = false) {
            const lakRate = currencies.find(c => c.code === 'LAK')?.rate || 1;
            const shopName = localStorage.getItem('shopName') || 'ຮ້ານຂາຍເສື້ອຜ້າ';
            const shopAddress = localStorage.getItem('shopAddress') || '';
            const shopPhone = localStorage.getItem('shopPhone') || '';
            const qrCodeData = localStorage.getItem('shopQRCode'); // ດຶງຮູບ QR
            
            let itemsHtml = '';
            sale.items.forEach(item => {
                itemsHtml += `
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                    <span>${item.name} <br><small>x${item.quantity}</small></span>
                    <span>${(item.price * item.quantity).toLocaleString()}</span>
                </div>`;
            });

            // ສ້າງ HTML ສຳລັບ QR Code (ຖ້າມີ)
            let qrHtml = '';
            if (qrCodeData) {
                qrHtml = `
                <div style="text-align: center; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #000;">
                    <p style="font-size: 10px; margin-bottom: 5px;">ສະແກນຈ່າຍເງິນ:</p>
                    <img src="${qrCodeData}" style="width: 100px; height: 100px; display: inline-block;">
                </div>`;
            }

            const html = `
                <div style="text-align: center; margin-bottom: 10px;">
                    <h2 style="font-size: 16px; font-weight: bold; margin: 0;">${shopName}</h2>
                    <p style="font-size: 10px; margin: 0;">${shopAddress}</p>
                    <p style="font-size: 10px; margin: 0;">Tel: ${shopPhone}</p>
                </div>
                <div style="border-bottom: 1px dashed #000; margin-bottom: 5px;"></div>
                <p style="font-size: 10px; margin: 2px 0;">ວັນທີ: ${new Date(sale.date).toLocaleString('lo-LA')}</p>
                <p style="font-size: 10px; margin: 2px 0;">Bill ID: ${sale.id}</p>
                <div style="border-bottom: 1px dashed #000; margin-bottom: 5px;"></div>
                ${itemsHtml}
                <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: bold;">
                    <span>ລວມ:</span>
                    <span>${parseFloat(sale.total).toLocaleString()}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 10px; color: #555;">
                    <span>(ກີບ):</span>
                    <span>${(sale.total * lakRate).toLocaleString()}</span>
                </div>
                ${qrHtml}
                <div style="text-align: center; margin-top: 15px; font-size: 10px;">
                    <p>ຂອບໃຈທີ່ໃຊ້ບໍລິການ</p>
                </div>
            `;
            
            document.getElementById('receiptContent').innerHTML = html;
            document.getElementById('receiptModal').classList.remove('hidden');
            
            if(autoPrint) {
                setTimeout(printReceipt, 300);
            }
        }

        function closeReceiptModal() { document.getElementById('receiptModal').classList.add('hidden'); }
        
        function printReceipt() {
            document.body.classList.add('printing-receipt');
            window.print();
            document.body.classList.remove('printing-receipt');
        }

        // --- Sales History & Delete Logic ---
        function filterSales() {
            const date = document.getElementById('salesDateFilter').value;
            fetch('get_sales.php?date=' + date)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('summaryTotalSales').innerText = parseFloat(data.summary?.total_sales || 0).toLocaleString();
                    document.getElementById('summaryTotalProfit').innerText = parseFloat(data.summary?.total_profit || 0).toLocaleString();
                    
                    const list = document.getElementById('salesList');
                    if(data.sales.length === 0) {
                        list.innerHTML = '<div class="text-center p-8 text-gray-400">ບໍ່ພົບລາຍການຂາຍ</div>';
                        return;
                    }
                    
                    let html = `<table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="p-3">ເວລາ</th>
                                <th class="p-3">ບາໂຄດສິນຄ້າ</th>
                                <th class="p-3 text-center">ຈຳນວນ</th>
                                <th class="p-3 text-right">ຍອດລວມ</th>
                                <th class="p-3 text-center">ຈັດການ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">`;
                    
                    data.sales.forEach(sale => {
                        let barcodes = sale.barcodes || '-';
                        let displayBarcodes = barcodes.length > 30 ? barcodes.substring(0, 30) + '...' : barcodes;

                        html += `
                        <tr class="hover:bg-gray-50 transition-colors sales-row" data-search="${barcodes.toLowerCase()} ${sale.id}">
                            <td class="p-3 text-gray-600 whitespace-nowrap">${new Date(sale.sale_date).toLocaleTimeString('lo-LA')}</td>
                            <td class="p-3 text-gray-500 font-mono text-xs" title="${barcodes}">
                                <i class="fas fa-barcode mr-1"></i>${displayBarcodes}
                            </td>
                            <td class="p-3 text-center text-gray-800 font-medium">${sale.item_count} ລາຍການ</td>
                            <td class="p-3 text-right font-bold text-blue-600">${parseFloat(sale.total).toLocaleString()}</td>
                            <td class="p-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="viewSaleDetail(${sale.id})" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="ພິມໃບເສັດ"><i class="fas fa-print"></i></button>
                                    <button onclick="viewAdminDetail(${sale.id})" class="p-2 text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-lg" title="ເບິ່ງຕົ້ນທຶນ/ກຳໄລ"><i class="fas fa-eye"></i></button>
                                    <button onclick="deleteSale(${sale.id})" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="ລຶບລາຍການ"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    list.innerHTML = html;
                });
        }
        
        function searchSalesTable() {
            const input = document.getElementById('salesSearchInput');
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.sales-row');

            rows.forEach(row => {
                const text = row.getAttribute('data-search');
                if (text.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function viewSaleDetail(id) {
            fetch('get_sale_detail.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if(data.success) showReceipt(data.sale);
                });
        }

        function viewAdminDetail(id) {
            fetch('get_sale_detail.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const s = data.sale;
                        document.getElementById('ad_saleId').innerText = s.id;
                        document.getElementById('ad_saleDate').innerText = new Date(s.date).toLocaleString('lo-LA');
                        document.getElementById('ad_saleEmp').innerText = s.employee_name;
                        document.getElementById('ad_totalProfit').innerText = parseFloat(s.profit).toLocaleString();

                        let html = '';
                        s.items.forEach(item => {
                            const itemProfit = (item.price - item.cost) * item.quantity;
                            html += `
                            <tr>
                                <td class="p-2 border-b">
                                    <div class="font-bold">${item.name}</div>
                                    <div class="text-xs text-gray-400">${item.barcode}</div>
                                </td>
                                <td class="p-2 border-b text-center">${item.quantity}</td>
                                <td class="p-2 border-b text-right text-gray-500">${parseFloat(item.cost).toLocaleString()}</td>
                                <td class="p-2 border-b text-right">${parseFloat(item.price).toLocaleString()}</td>
                                <td class="p-2 border-b text-right font-bold text-green-600">+${itemProfit.toLocaleString()}</td>
                            </tr>`;
                        });
                        document.getElementById('ad_itemList').innerHTML = html;
                        document.getElementById('adminDetailModal').classList.remove('hidden');
                    }
                });
        }

        function deleteSale(id) {
            if (confirm('ຕ້ອງການລຶບລາຍການຂາຍນີ້ແທ້ບໍ່?\n\n*ລະບົບຈະຄືນຈຳນວນສິນຄ້າເຂົ້າສະຕັອກໂດຍອັດຕະໂນມັດ')) {
                fetch('delete_sale.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        filterSales(); // Refresh sales list
                        // ຖ້າເປັນມື້ປັດຈຸບັນ Reload Dashboard
                        if(document.getElementById('salesDateFilter').value === new Date().toISOString().split('T')[0]) {
                            location.reload(); 
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່');
                });
            }
        }

        // --- Shop Info ---
        function saveShopInfo() {
            localStorage.setItem('shopName', document.getElementById('shopName').value);
            localStorage.setItem('shopAddress', document.getElementById('shopAddress').value);
            localStorage.setItem('shopPhone', document.getElementById('shopPhone').value);

            const fileInput = document.getElementById('shopQRInput');
            if (fileInput.files.length > 0) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    localStorage.setItem('shopQRCode', e.target.result);
                    document.getElementById('shopQRPreview').src = e.target.result;
                    document.getElementById('shopQRPreview').classList.remove('hidden');
                    alert('ບັນທຶກຂໍ້ມູນ ແລະ ຮູບ QR Code ສຳເລັດ!');
                };
                reader.readAsDataURL(fileInput.files[0]);
            } else {
                alert('ບັນທຶກສຳເລັດ!');
            }
        }

        function removeQRCode() {
            localStorage.removeItem('shopQRCode');
            document.getElementById('shopQRInput').value = '';
            document.getElementById('shopQRPreview').src = '';
            document.getElementById('shopQRPreview').classList.add('hidden');
            alert('ລົບຮູບ QR Code ແລ້ວ');
        }
        
        // Init
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('shopName').value = localStorage.getItem('shopName') || '';
            document.getElementById('shopAddress').value = localStorage.getItem('shopAddress') || '';
            document.getElementById('shopPhone').value = localStorage.getItem('shopPhone') || '';
            
            // Load QR Preview
            const qrData = localStorage.getItem('shopQRCode');
            if(qrData && document.getElementById('shopQRPreview')) {
                const img = document.getElementById('shopQRPreview');
                img.src = qrData;
                img.classList.remove('hidden');
            }
        });
    </script>
    
</body>
</html>