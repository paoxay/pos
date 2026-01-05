<?php
require_once 'config.php';
checkLogin();

// ดึงข้อมูลสำหรับแดชบอร์ด
$stmt = $pdo->query("SELECT SUM(stock) as total_stock, SUM(stock * cost) as total_cost FROM products");
$stock_data = $stmt->fetch();

$stmt = $pdo->query("SELECT SUM(total) as today_sales, SUM(profit) as today_profit, COUNT(*) as today_orders FROM sales WHERE DATE(sale_date) = CURDATE()");
$sales_data = $stmt->fetch();

$stmt = $pdo->query("SELECT SUM(si.quantity) as today_items FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE DATE(s.sale_date) = CURDATE()");
$items_data = $stmt->fetch();

// ดึงรายการขายวันนี้
$stmt = $pdo->query("
    SELECT s.*, si.*, p.name, p.barcode 
    FROM sales s 
    JOIN sale_items si ON s.id = si.sale_id 
    JOIN products p ON si.product_id = p.id 
    WHERE DATE(s.sale_date) = CURDATE() 
    ORDER BY s.sale_date DESC
");
$today_sales = $stmt->fetchAll();

// ดึงข้อมูลสินค้า
$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll();

// ดึงอัตราแลกเปลี่ยน
$stmt = $pdo->query("SELECT * FROM currencies");
$currencies = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบขายเสื้อฝ้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        
        @media print {
    body, body * {
        visibility: hidden;
        margin: 0;
        padding: 0;
    }
    .receipt-print, .receipt-print * {
        visibility: visible;
    }
    .receipt-print {
        position: absolute;
        left: 0;
        top: 0;
        width: 80mm !important;
        /* ลบ height: 80mm เพื่อให้ความสูงปรับตามเนื้อหา */
        font-size: 10px !important;
        line-height: 1.2;
        padding: 5mm !important;
        box-sizing: border-box;
        page-break-after: avoid; /* ป้องกันการแบ่งหน้า */
        page-break-before: avoid;
        page-break-inside: avoid; /* หลีกเลี่ยงการแบ่งเนื้อหา */
    }
    @page {
        size: 80mm auto; /* เปลี่ยนจาก 80mm 80mm เป็น 80mm auto เพื่อให้ความสูงปรับตามเนื้อหา */
        margin: 2mm; /* เพิ่ม margin เล็กน้อยเพื่อป้องกันการตัดขอบ */
    }
    /* ซ่อน iframe และองค์ประกอบที่ไม่ต้องการ */
    iframe, .no-print {
        display: none !important;
    }
}
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
                    <span class="text-sm"><?php echo $_SESSION['user_name']; ?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm">
                        <i class="fas fa-sign-out-alt mr-1"></i>ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="flex">
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4">
                <ul class="space-y-2">
                    <li><a href="#" onclick="showPage('dashboard')" class="block w-full text-left p-3 rounded hover:bg-blue-50 flex items-center"><i class="fas fa-chart-dashboard mr-3"></i>หน้าหลัก</a></li>
                    <li><a href="#" onclick="showPage('sell')" class="block w-full text-left p-3 rounded hover:bg-blue-50 flex items-center"><i class="fas fa-cash-register mr-3"></i>ขายสินค้า</a></li>
                    <li><a href="#" onclick="showPage('sales')" class="block w-full text-left p-3 rounded hover:bg-blue-50 flex items-center"><i class="fas fa-receipt mr-3"></i>รายการขาย</a></li>
                    <li><a href="products.php" class="block w-full text-left p-3 rounded hover:bg-blue-50 flex items-center"><i class="fas fa-box mr-3"></i>จัดการสินค้า</a></li>
                    <li><a href="#" onclick="showPage('shop')" class="block w-full text-left p-3 rounded hover:bg-blue-50 flex items-center"><i class="fas fa-store mr-3"></i>จัดการร้านค้า</a></li>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li><a href="employees.php" class="block w-full text-left p-3 rounded hover:bg-blue-50 flex items-center"><i class="fas fa-users mr-3"></i>จัดการพนักงาน</a></li>
                    <li><a href="currency.php" class="block w-full text-left p-3 rounded hover:bg-blue-50 flex items-center"><i class="fas fa-coins mr-3"></i>จัดการสกุลเงิน</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <!-- Dashboard Page -->
            <div id="dashboard" class="page">
                <h2 class="text-2xl font-bold mb-6">แดชบอร์ด</h2>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="fas fa-boxes text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">สต็อกทั้งหมด</p>
                                <p class="text-2xl font-bold"><?php echo number_format($stock_data['total_stock'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="fas fa-dollar-sign text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">ต้นทุนสต็อก</p>
                                <p class="text-2xl font-bold"><?php echo number_format($stock_data['total_cost'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="fas fa-chart-line text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">ยอดขายวันนี้</p>
                                <p class="text-2xl font-bold"><?php echo number_format($sales_data['today_sales'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="fas fa-coins text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">กำไรวันนี้</p>
                                <p class="text-2xl font-bold"><?php echo number_format($sales_data['today_profit'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="fas fa-shopping-cart text-red-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">ขายแล้ววันนี้</p>
                                <p class="text-2xl font-bold"><?php echo number_format($items_data['today_items'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Sales -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold">รายการขายวันนี้</h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2">เวลา</th>
                                        <th class="text-left py-2">บาร์โค้ด</th>
                                        <th class="text-left py-2">สินค้า</th>
                                        <th class="text-left py-2">จำนวน</th>
                                        <th class="text-left py-2">ราคา</th>
                                        <th class="text-left py-2">รวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($today_sales)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-8 text-gray-500">ยังไม่มีรายการขายวันนี้</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($today_sales as $sale): ?>
                                        <tr>
                                            <td class="py-2"><?php echo date('H:i:s', strtotime($sale['sale_date'])); ?></td>
                                            <td class="py-2"><?php echo $sale['barcode']; ?></td>
                                            <td class="py-2"><?php echo $sale['name']; ?></td>
                                            <td class="py-2"><?php echo $sale['quantity']; ?></td>
                                            <td class="py-2"><?php echo number_format($sale['price']); ?> บาท</td>
                                            <td class="py-2"><?php echo number_format($sale['price'] * $sale['quantity']); ?> บาท</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Page -->
            <div id="sales" class="page hidden">
                <h2 class="text-2xl font-bold mb-6">รายการขาย</h2>
                
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold">ประวัติการขาย</h3>
                            <div class="flex space-x-2">
                                <input type="date" id="salesDateFilter" class="border rounded px-3 py-2" value="<?php echo date('Y-m-d'); ?>">
                                <button onclick="filterSales()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                    <i class="fas fa-search mr-2"></i>ค้นหา
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div id="salesList" class="overflow-x-auto">
                            <!-- Sales list will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shop Management Page -->
            <div id="shop" class="page hidden">
                <h2 class="text-2xl font-bold mb-6">จัดการร้านค้า</h2>
                
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold">ข้อมูลร้านค้า</h3>
                    </div>
                    <div class="p-6">
                        <form id="shopForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">ชื่อร้านค้า</label>
                                <input type="text" id="shopName" class="w-full p-3 border rounded-lg" placeholder="ชื่อร้านค้า" value="ร้านขายเสื้อฝ้า">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">ที่อยู่</label>
                                <textarea id="shopAddress" class="w-full p-3 border rounded-lg" rows="3" placeholder="ที่อยู่ร้านค้า"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">เบอร์โทรศัพท์</label>
                                <input type="text" id="shopPhone" class="w-full p-3 border rounded-lg" placeholder="เบอร์โทรศัพท์">
                            </div>
                            <button type="button" onclick="saveShopInfo()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
                                <i class="fas fa-save mr-2"></i>บันทึกข้อมูล
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sell Page -->
            <div id="sell" class="page hidden">
                <h2 class="text-2xl font-bold mb-6">ขายสินค้า</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Product Input -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">เพิ่มสินค้า</h3>
                        
                        <form id="sellForm">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">รหัสบาร์โค้ด</label>
                                    <input type="text" id="barcodeInput" class="w-full p-3 border rounded-lg" placeholder="สแกนหรือพิมพ์บาร์โค้ด" autofocus>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">เลือกสินค้า</label>
                                    <select id="productSelect" class="w-full p-3 border rounded-lg">
                                        <option value="">-- เลือกสินค้า --</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" data-barcode="<?php echo $product['barcode']; ?>" data-price="<?php echo $product['price']; ?>" data-cost="<?php echo $product['cost']; ?>" data-stock="<?php echo $product['stock']; ?>" data-name="<?php echo $product['name']; ?>">
                                                <?php echo $product['name']; ?> (คงเหลือ: <?php echo $product['stock']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-2">จำนวน</label>
                                        <input type="number" id="quantityInput" value="1" min="1" class="w-full p-3 border rounded-lg">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium mb-2">ราคาขาย (บาท)</label>
                                        <input type="number" id="priceInput" step="0.01" min="0" class="w-full p-3 border rounded-lg" placeholder="ราคาขาย">
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <button type="button" onclick="addToCart()" class="flex-1 bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-plus mr-2"></i>เพิ่มลงตะกร้า
                                    </button>
                                    <button type="button" onclick="clearSellForm()" class="bg-gray-500 text-white p-3 rounded-lg hover:bg-gray-600">
                                        <i class="fas fa-eraser"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Shopping Cart -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">ตะกร้าสินค้า <span id="cartCount" class="text-sm text-gray-500">(0 รายการ)</span></h3>
                            <button onclick="clearCart()" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash mr-1"></i>ล้างตะกร้า
                            </button>
                        </div>
                        
                        <div id="cartItems" class="mb-4 max-h-80 overflow-y-auto">
                            <div class="text-gray-500 text-center py-8">ตะกร้าว่าง</div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between">
                                    <span>ยอดรวม:</span>
                                    <span id="subtotal">0 บาท</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span>ส่วนลด:</span>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" id="discountInput" value="0" min="0" max="100" class="w-16 p-1 border rounded text-center" onchange="updateCartTotal()">
                                        <span>%</span>
                                        <span id="discountAmount" class="text-red-600">-0 บาท</span>
                                    </div>
                                </div>
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>กำไรจริง:</span>
                                    <span id="realProfit">0 บาท</span>
                                </div>
                                <div class="flex justify-between font-bold text-lg border-t pt-2">
                                    <span>ยอดชำระ:</span>
                                    <div class="text-right">
                                        <div id="cartTotal">0 บาท</div>
                                        <div id="cartTotalLak" class="text-sm text-gray-600">0 กีบ</div>
                                    </div>
                                </div>
                            </div>
                            
                            <button onclick="showCheckoutConfirm()" class="w-full bg-green-600 text-white p-3 rounded-lg hover:bg-green-700" id="checkoutBtn" disabled>
                                <i class="fas fa-credit-card mr-2"></i>ชำระเงิน
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Checkout Confirmation Modal -->
    <div id="checkoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">ยืนยันการชำระเงิน</h3>
                        <button onclick="closeCheckoutModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between mb-2">
                                <span>จำนวนรายการ:</span>
                                <span id="confirmItemCount">0 รายการ</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span>ยอดรวม:</span>
                                <span id="confirmSubtotal">0 บาท</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span>ส่วนลด:</span>
                                <span id="confirmDiscount" class="text-red-600">-0 บาท</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg border-t pt-2">
                                <span>ยอดชำระ:</span>
                                <div class="text-right">
                                    <div id="confirmTotal">0 บาท</div>
                                    <div id="confirmTotalLak" class="text-sm text-gray-600">0 กีบ</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" id="printReceiptCheck" class="mr-2" checked>
                            <span>พิมพ์ใบเสร็จหลังชำระเงิน</span>
                        </label>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button onclick="confirmCheckout()" class="flex-1 bg-green-600 text-white p-3 rounded-lg hover:bg-green-700">
                            <i class="fas fa-check mr-2"></i>ยืนยันชำระเงิน
                        </button>
                        <button onclick="closeCheckoutModal()" class="flex-1 bg-gray-500 text-white p-3 rounded-lg hover:bg-gray-600">
                            ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-6xl text-green-500"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">ชำระเงินสำเร็จ!</h3>
                    <p class="text-gray-600 mb-6">บันทึกการขายเรียบร้อยแล้ว</p>
                    
                    <div class="flex space-x-4">
                        <button onclick="showReceiptFromSuccess()" class="flex-1 bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-print mr-2"></i>พิมพ์ใบเสร็จ
                        </button>
                        <button onclick="closeSuccessModal()" class="flex-1 bg-gray-500 text-white p-3 rounded-lg hover:bg-gray-600">
                            ปิด
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">ใบเสร็จรับเงิน</h3>
                        <button onclick="closeReceiptModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div id="receiptContent" class="receipt-print border p-4 mb-4">
                        <!-- Receipt content will be generated here -->
                    </div>
                    
                    <div class="flex space-x-4">
                        <button onclick="printReceipt()" class="flex-1 bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-print mr-2"></i>พิมพ์ใบเสร็จ
                        </button>
                        <button onclick="closeReceiptModal()" class="flex-1 bg-gray-500 text-white p-3 rounded-lg hover:bg-gray-600">
                            ปิด
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let products = <?php echo json_encode($products); ?>;
        let currencies = <?php echo json_encode($currencies); ?>;
        let currentSale = null;

        // Page navigation
        function showPage(pageId) {
            document.querySelectorAll('.page').forEach(page => page.classList.add('hidden'));
            document.getElementById(pageId).classList.remove('hidden');
            if (pageId === 'sell') {
                document.getElementById('barcodeInput').focus();
            }
        }

        // Barcode input handler
        document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const barcode = this.value.trim();
                if (barcode) {
                    const product = products.find(p => p.barcode === barcode);
                    if (product) {
                        document.getElementById('productSelect').value = product.id;
                        document.getElementById('priceInput').value = product.price;
                        addToCart();
                    } else {
                        alert('ไม่พบสินค้าที่มีบาร์โค้ดนี้');
                        this.select();
                    }
                }
            }
        });

        // Product select handler
        document.getElementById('productSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('barcodeInput').value = selectedOption.dataset.barcode;
                document.getElementById('priceInput').value = selectedOption.dataset.price;
            } else {
                document.getElementById('barcodeInput').value = '';
                document.getElementById('priceInput').value = '';
            }
        });

        // Add to cart function
        function addToCart() {
            const productSelect = document.getElementById('productSelect');
            const quantity = parseInt(document.getElementById('quantityInput').value);
            const customPrice = parseFloat(document.getElementById('priceInput').value);
            const barcodeInput = document.getElementById('barcodeInput').value.trim();
            
            let selectedProduct = null;
            
            if (barcodeInput) {
                selectedProduct = products.find(p => p.barcode === barcodeInput);
            } else if (productSelect.value) {
                selectedProduct = products.find(p => p.id == productSelect.value);
            }
            
            if (!selectedProduct) {
                alert('กรุณาเลือกสินค้า');
                return;
            }
            
            if (!customPrice || customPrice <= 0) {
                alert('กรุณาใส่ราคาขาย');
                document.getElementById('priceInput').focus();
                return;
            }
            
            if (quantity > selectedProduct.stock) {
                alert('สินค้าไม่เพียงพอ คงเหลือ ' + selectedProduct.stock + ' ชิ้น');
                return;
            }
            
            const existingItem = cart.find(item => item.id == selectedProduct.id && item.price == customPrice);
            if (existingItem) {
                if (existingItem.quantity + quantity > selectedProduct.stock) {
                    alert('สินค้าไม่เพียงพอ คงเหลือ ' + selectedProduct.stock + ' ชิ้น');
                    return;
                }
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    id: selectedProduct.id,
                    barcode: selectedProduct.barcode,
                    name: selectedProduct.name,
                    price: customPrice,
                    cost: parseFloat(selectedProduct.cost),
                    quantity: quantity,
                    stock: selectedProduct.stock
                });
            }
            
            updateCartDisplay();
            clearSellForm();
            document.getElementById('barcodeInput').focus();
        }

        function clearSellForm() {
            document.getElementById('barcodeInput').value = '';
            document.getElementById('productSelect').value = '';
            document.getElementById('quantityInput').value = '1';
            document.getElementById('priceInput').value = '';
        }

        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const checkoutBtn = document.getElementById('checkoutBtn');
            const cartCount = document.getElementById('cartCount');

            cartCount.textContent = `(${cart.length} รายการ)`;

            if (cart.length === 0) {
                cartItems.innerHTML = '<div class="text-gray-500 text-center py-8">ตะกร้าว่าง</div>';
                checkoutBtn.disabled = true;
                checkoutBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                cartItems.innerHTML = `
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">สินค้า</th>
                                <th class="text-center py-2">จำนวน</th>
                                <th class="text-right py-2">รวม</th>
                                <th class="text-center py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${cart.map((item, index) => `
                                <tr class="border-b">
                                    <td class="py-2">
                                        <div class="font-medium">${item.name}</div>
                                        <div class="text-xs text-gray-500">${item.barcode}</div>
                                        <div class="text-xs text-gray-600">${item.price.toLocaleString()} บาท/ชิ้น</div>
                                    </td>
                                    <td class="py-2 text-center">
                                        <div class="flex items-center justify-center space-x-1">
                                            <button onclick="updateQuantity(${index}, -1)" class="w-6 h-6 bg-red-500 text-white rounded text-xs hover:bg-red-600">-</button>
                                            <span class="w-8 text-center">${item.quantity}</span>
                                            <button onclick="updateQuantity(${index}, 1)" class="w-6 h-6 bg-green-500 text-white rounded text-xs hover:bg-green-600">+</button>
                                        </div>
                                    </td>
                                    <td class="py-2 text-right font-medium">${(item.price * item.quantity).toLocaleString()} บาท</td>
                                    <td class="py-2 text-center">
                                        <button onclick="removeFromCart(${index})" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                checkoutBtn.disabled = false;
                checkoutBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }

            updateCartTotal();
        }

        function updateQuantity(index, change) {
            const item = cart[index];
            const newQuantity = item.quantity + change;
            
            if (newQuantity <= 0) {
                removeFromCart(index);
                return;
            }
            
            if (newQuantity > item.stock) {
                alert('สินค้าไม่เพียงพอ คงเหลือ ' + item.stock + ' ชิ้น');
                return;
            }
            
            item.quantity = newQuantity;
            updateCartDisplay();
        }

        function updateCartTotal() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discount = parseFloat(document.getElementById('discountInput').value) || 0;
            const discountAmount = subtotal * (discount / 100);
            const total = subtotal - discountAmount;
            
            // คำนวณกำไรจริง (หลังหักส่วนลด)
            const totalProfit = cart.reduce((sum, item) => sum + ((item.price - item.cost) * item.quantity), 0);
            const realProfit = totalProfit - (totalProfit * (discount / 100));
            
            const lakRate = currencies.find(c => c.code === 'LAK')?.rate || 270;
            const totalInLak = total * lakRate;
            
            document.getElementById('subtotal').textContent = subtotal.toLocaleString() + ' บาท';
            document.getElementById('discountAmount').textContent = '-' + discountAmount.toLocaleString() + ' บาท';
            document.getElementById('realProfit').textContent = realProfit.toLocaleString() + ' บาท';
            document.getElementById('cartTotal').textContent = total.toLocaleString() + ' บาท';
            document.getElementById('cartTotalLak').textContent = totalInLak.toLocaleString() + ' กีบ';
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }

        function clearCart() {
            cart = [];
            updateCartDisplay();
        }

        // Checkout confirmation
        function showCheckoutConfirm() {
            if (cart.length === 0) return;

            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discount = parseFloat(document.getElementById('discountInput').value) || 0;
            const discountAmount = subtotal * (discount / 100);
            const total = subtotal - discountAmount;
            const lakRate = currencies.find(c => c.code === 'LAK')?.rate || 270;
            const totalInLak = total * lakRate;

            document.getElementById('confirmItemCount').textContent = cart.length + ' รายการ';
            document.getElementById('confirmSubtotal').textContent = subtotal.toLocaleString() + ' บาท';
            document.getElementById('confirmDiscount').textContent = '-' + discountAmount.toLocaleString() + ' บาท';
            document.getElementById('confirmTotal').textContent = total.toLocaleString() + ' บาท';
            document.getElementById('confirmTotalLak').textContent = totalInLak.toLocaleString() + ' กีบ';

            document.getElementById('checkoutModal').classList.remove('hidden');
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.add('hidden');
        }

        function confirmCheckout() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discount = parseFloat(document.getElementById('discountInput').value) || 0;
            const total = subtotal * (1 - discount / 100);
            // คำนวณกำไรจริงหลังหักส่วนลด
            const totalProfit = cart.reduce((sum, item) => sum + ((item.price - item.cost) * item.quantity), 0);
            const profit = totalProfit * (1 - discount / 100);

            closeCheckoutModal();

            // Send to server
            fetch('process_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    items: cart,
                    subtotal: subtotal,
                    discount: discount,
                    total: total,
                    profit: profit
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentSale = data.sale;
                    
                    // Show success modal
                    document.getElementById('successModal').classList.remove('hidden');
                    
                    // Auto print if checked
                    if (document.getElementById('printReceiptCheck').checked) {
                        setTimeout(() => {
                            showReceipt(data.sale);
                        }, 1000);
                    }
                    
                    // ไม่ปิดออโต้ ให้ผู้ใช้ปิดเอง
                    
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            });
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            clearCart();
            document.getElementById('discountInput').value = '0';
            location.reload(); // Refresh to update stock
        }

        function showReceiptFromSuccess() {
            if (currentSale) {
                showReceipt(currentSale);
            }
        }

        function showReceipt(sale) {
            const modal = document.getElementById('receiptModal');
            const content = document.getElementById('receiptContent');
            
            const lakRate = currencies.find(c => c.code === 'LAK')?.rate || 270;
            const totalInLak = sale.total * lakRate;
            
            const shopName = localStorage.getItem('shopName') || 'ร้านขายเสื้อฝ้า';
            
            content.innerHTML = `
                <div class="text-center mb-3">
                    <h2 class="font-bold text-sm">${shopName}</h2>
                    <p class="text-xs">${new Date(sale.date).toLocaleString('th-TH')}</p>
                    <p class="text-xs">เลขที่: ${sale.id} | พนักงาน: <?php echo $_SESSION['user_name']; ?></p>
                </div>
                
                <div class="border-t border-b py-2 mb-2">
                    ${sale.items.map(item => `
                        <div class="text-xs mb-2">
                            <div class="font-medium">${item.name}</div>
                            <div class="flex justify-between">
                                <span>${item.quantity} × ${item.price.toLocaleString()}</span>
                                <span>${(item.price * item.quantity).toLocaleString()}</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <div class="text-xs space-y-1">
                    <div class="flex justify-between">
                        <span>รวม:</span>
                        <span>${sale.subtotal.toLocaleString()} บาท</span>
                    </div>
                    ${sale.discount > 0 ? `
                        <div class="flex justify-between">
                            <span>ส่วนลด (${sale.discount}%):</span>
                            <span>-${(sale.subtotal * sale.discount / 100).toLocaleString()} บาท</span>
                        </div>
                    ` : ''}
                    <div class="flex justify-between border-t pt-1 mb-2">
                        <span>อัตราแลกเปลี่ยน:</span>
                        <span>1 บาท = ${lakRate.toLocaleString()} กีบ</span>
                    </div>
                    <div class="text-center font-bold text-sm border-t pt-2">
                        <div>ยอดชำระ: ${sale.total.toLocaleString()} บาท</div>
                        <div>เท่ากับ: ${totalInLak.toLocaleString()} กีบ</div>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-xs border-t pt-2">
                    <p>ขอบคุณที่ใช้บริการ</p>
                </div>
            `;
            
            modal.classList.remove('hidden');
        }

        function closeReceiptModal() {
            document.getElementById('receiptModal').classList.add('hidden');
        }

        function printReceipt() {
            window.print();
        }

        // Shop management functions
        function saveShopInfo() {
            const shopName = document.getElementById('shopName').value;
            const shopAddress = document.getElementById('shopAddress').value;
            const shopPhone = document.getElementById('shopPhone').value;
            
            localStorage.setItem('shopName', shopName);
            localStorage.setItem('shopAddress', shopAddress);
            localStorage.setItem('shopPhone', shopPhone);
            
            alert('บันทึกข้อมูลร้านค้าเรียบร้อยแล้ว');
        }

        // Load shop info on page load
        function loadShopInfo() {
            const shopName = localStorage.getItem('shopName');
            const shopAddress = localStorage.getItem('shopAddress');
            const shopPhone = localStorage.getItem('shopPhone');
            
            if (shopName) document.getElementById('shopName').value = shopName;
            if (shopAddress) document.getElementById('shopAddress').value = shopAddress;
            if (shopPhone) document.getElementById('shopPhone').value = shopPhone;
        }

        // Sales list functions
        function filterSales() {
            const date = document.getElementById('salesDateFilter').value;
            
            fetch('get_sales.php?date=' + date)
            .then(response => response.json())
            .then(data => {
                displaySalesList(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            });
        }

        function displaySalesList(sales) {
            const salesList = document.getElementById('salesList');
            
            if (sales.length === 0) {
                salesList.innerHTML = '<div class="text-center py-8 text-gray-500">ไม่พบรายการขายในวันที่เลือก</div>';
                return;
            }
            
            let html = `
                <table class="w-full">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th class="text-left py-3 px-4">เวลา</th>
                            <th class="text-left py-3 px-4">รายการ</th>
                            <th class="text-right py-3 px-4">ยอดรวม</th>
                            <th class="text-right py-3 px-4">ส่วนลด</th>
                            <th class="text-right py-3 px-4">ยอดชำระ</th>
                            <th class="text-right py-3 px-4">กำไร</th>
                            <th class="text-center py-3 px-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            sales.forEach(sale => {
                html += `
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">${new Date(sale.sale_date).toLocaleTimeString('th-TH')}</td>
                        <td class="py-3 px-4">${sale.item_count} รายการ</td>
                        <td class="py-3 px-4 text-right">${parseFloat(sale.subtotal).toLocaleString()} บาท</td>
                        <td class="py-3 px-4 text-right">${sale.discount}%</td>
                        <td class="py-3 px-4 text-right font-bold">${parseFloat(sale.total).toLocaleString()} บาท</td>
                        <td class="py-3 px-4 text-right text-green-600">${parseFloat(sale.profit).toLocaleString()} บาท</td>
                        <td class="py-3 px-4 text-center">
                            <button onclick="viewSaleDetail(${sale.id})" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                <i class="fas fa-eye mr-1"></i>ดู
                            </button>
                            <button onclick="printSaleReceipt(${sale.id})" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600 ml-1">
                                <i class="fas fa-print mr-1"></i>พิมพ์
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            salesList.innerHTML = html;
        }

        function viewSaleDetail(saleId) {
            fetch('get_sale_detail.php?id=' + saleId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showReceipt(data.sale);
                } else {
                    alert('ไม่พบข้อมูลการขาย');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            });
        }

        function printSaleReceipt(saleId) {
            fetch('get_sales.php?id=' + saleId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showReceipt(data.sale);
                    setTimeout(() => {
                        printReceipt();
                    }, 500);
                } else {
                    alert('ไม่พบข้อมูลการขาย');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            });
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadShopInfo();
            // โหลดรายการขายวันนี้เมื่อเปิดหน้าเว็บ
            filterSales();
        });

        // แก้ไขฟังก์ชัน showPage ให้โหลดข้อมูลเมื่อเข้าหน้ารายการขาย
        function showPage(pageId) {
            document.querySelectorAll('.page').forEach(page => page.classList.add('hidden'));
            document.getElementById(pageId).classList.remove('hidden');
            
            if (pageId === 'sell') {
                document.getElementById('barcodeInput').focus();
            } else if (pageId === 'sales') {
                // โหลดรายการขายเมื่อเข้าหน้ารายการขาย
                filterSales();
            }
        }
    </script>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'982fd4cac2b0506a',t:'MTc1ODUyMzQ0MC4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>
