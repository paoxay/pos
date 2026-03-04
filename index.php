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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ລະບົບຂາຍເສື້ອຜ້າ POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap');
        
        * { font-family: 'Inter', 'Noto Sans Lao', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background: #f0f2f5;
            min-height: 100vh;
        }
        
        /* ===== SIDEBAR ===== */
        .sidebar {
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
            position: relative;
            overflow: hidden;
        }
        .sidebar::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .nav-item {
            position: relative;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            padding: 11px 16px;
        }
        .nav-item:hover {
            color: rgba(255, 255, 255, 0.95);
            background: rgba(255, 255, 255, 0.08);
        }
        .nav-item.active {
            color: white;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.5) 0%, rgba(168, 85, 247, 0.3) 100%);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .nav-item.active i { color: #a5b4fc; }
        
        /* ===== MAIN AREA ===== */
        .main-area { background: #f0f2f5; }

        /* ===== STAT CARDS ===== */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }
        .stat-card .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.02em;
        }
        .stat-card .stat-glow {
            position: absolute;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            filter: blur(50px);
            opacity: 0.15;
            top: -20px;
            right: -20px;
            pointer-events: none;
        }
        
        /* Card themes */
        .stat-card.blue .stat-icon { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
        .stat-card.blue .stat-value { color: #4338ca; }
        .stat-card.blue .stat-glow { background: #6366f1; }
        
        .stat-card.green .stat-icon { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
        .stat-card.green .stat-value { color: #059669; }
        .stat-card.green .stat-glow { background: #10b981; }

        .stat-card.purple .stat-icon { background: linear-gradient(135deg, #8b5cf6, #d946ef); color: white; }
        .stat-card.purple .stat-value { color: #7c3aed; }
        .stat-card.purple .stat-glow { background: #8b5cf6; }

        .stat-card.orange .stat-icon { background: linear-gradient(135deg, #f97316, #fb923c); color: white; }
        .stat-card.orange .stat-value { color: #ea580c; }
        .stat-card.orange .stat-glow { background: #f97316; }

        /* ===== CONTENT CARDS ===== */
        .content-card {
            background: white;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        /* ===== TABLE ===== */
        .table-modern { width: 100%; border-collapse: collapse; }
        .table-modern thead th {
            padding: 14px 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            background: #f9fafb;
            border-bottom: 2px solid #f3f4f6;
            text-align: left;
        }
        .table-modern tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
            color: #374151;
        }
        .table-modern tbody tr { transition: background 0.15s ease; }
        .table-modern tbody tr:hover { background: #f9fafb; }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.35);
        }
        .btn-primary:active { transform: translateY(0); }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.35);
        }

        .btn-ghost {
            background: transparent;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-ghost:hover { background: #f9fafb; border-color: #d1d5db; color: #374151; }

        /* Backward compat aliases */
        .btn-modern {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.35); }

        /* ===== INPUT ===== */
        .input-modern {
            width: 100%;
            padding: 12px 16px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95rem;
            color: #1f2937;
            transition: all 0.25s ease;
            outline: none;
        }
        .input-modern:focus {
            background: white;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .input-modern::placeholder { color: #9ca3af; }

        /* ===== BADGES ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-blue { background: #eef2ff; color: #4338ca; }
        .badge-green { background: #ecfdf5; color: #059669; }
        .badge-purple { background: #f5f3ff; color: #7c3aed; }
        .badge-orange { background: #fff7ed; color: #ea580c; }
        .badge-red { background: #fef2f2; color: #dc2626; }

        /* ===== GRADIENT helpers ===== */
        .gradient-blue { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .gradient-green { background: linear-gradient(135deg, #10b981, #34d399); }
        .gradient-purple { background: linear-gradient(135deg, #8b5cf6, #d946ef); }
        .gradient-orange { background: linear-gradient(135deg, #f97316, #fb923c); }

        /* ===== ANIMATIONS ===== */
        .fade-in-up { animation: fadeInUp 0.4s ease-out forwards; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-in { animation: slideIn 0.3s ease-out forwards; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-16px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes pulse-slow { 
            0%, 100% { opacity: 0.15; } 
            50% { opacity: 0.25; } 
        }
        .animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }
        
        .mobile-nav-item.active { color: #6366f1; }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 100px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
        .sidebar ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); }
        .sidebar ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }

        /* ===== GLASS (for modals / mobile) ===== */
        .glass {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* ===== PRINT ===== */
        @media print {
            body * { visibility: hidden; }
            #receiptModal, #receiptModal * { visibility: visible; }
            #receiptModal {
                position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0;
                background: white !important; box-shadow: none !important;
            }
            #receiptModal button, .no-print-in-modal, .flex.gap-2, .mobile-nav { display: none !important; }
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
<body class="min-h-screen flex flex-col md:flex-row overflow-hidden">

    <!-- Sidebar Desktop -->
    <aside class="w-[260px] sidebar shadow-2xl z-20 hidden md:flex flex-col transition-all duration-300 h-screen sticky top-0">
        <div class="p-5 flex-1 overflow-y-auto relative z-10">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10 px-2">
                <div class="w-11 h-11 bg-indigo-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 ring-2 ring-indigo-400/30">
                    <i class="fas fa-store text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white tracking-tight">POS System</h1>
                    <p class="text-[11px] text-indigo-300/70">ລະບົບຂາຍເສື້ອຜ້າ</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav>
                <p class="text-[11px] font-semibold text-indigo-300/50 uppercase tracking-widest mb-3 px-3">ເມນູຫຼັກ</p>
                <ul class="space-y-1">
                    <li><a href="#" onclick="showPage('dashboard', this)" class="nav-item active flex items-center gap-3"><i class="fas fa-chart-pie w-5 text-center"></i><span class="font-medium text-sm">ແດຊບອດ</span></a></li>
                    <li><a href="#" onclick="showPage('sell', this)" class="nav-item flex items-center gap-3"><i class="fas fa-cash-register w-5 text-center"></i><span class="font-medium text-sm">ຂາຍສິນຄ້າ</span></a></li>
                    <li><a href="#" onclick="showPage('sales', this)" class="nav-item flex items-center gap-3"><i class="fas fa-receipt w-5 text-center"></i><span class="font-medium text-sm">ປະຫວັດການຂາຍ</span></a></li>
                    <li><a href="products.php" class="nav-item flex items-center gap-3"><i class="fas fa-box w-5 text-center"></i><span class="font-medium text-sm">ຈັດການສິນຄ້າ</span></a></li>
                    <li><a href="add_product_print.php" class="nav-item flex items-center gap-3"><i class="fas fa-barcode w-5 text-center"></i><span class="font-medium text-sm">ພິມບາໂຄດ</span></a></li>
                    <li><a href="#" onclick="showPage('shop', this)" class="nav-item flex items-center gap-3"><i class="fas fa-cog w-5 text-center"></i><span class="font-medium text-sm">ຕັ້ງຄ່າຮ້ານ</span></a></li>
                </ul>
                
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <p class="text-[11px] font-semibold text-indigo-300/50 uppercase tracking-widest mt-8 mb-3 px-3">ຜູ້ດູແລລະບົບ</p>
                <ul class="space-y-1">
                    <li><a href="employees.php" class="nav-item flex items-center gap-3"><i class="fas fa-users w-5 text-center"></i><span class="font-medium text-sm">ພະນັກງານ</span></a></li>
                    <li><a href="currency.php" class="nav-item flex items-center gap-3"><i class="fas fa-coins w-5 text-center"></i><span class="font-medium text-sm">ສະກຸນເງິນ</span></a></li>
                </ul>
                <?php endif; ?>
            </nav>
        </div>
        
        <!-- User Profile -->
        <div class="p-4 border-t border-white/10 relative z-10">
            <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 hover:bg-white/10 transition-colors">
                <div class="w-9 h-9 rounded-xl gradient-purple flex items-center justify-center text-white text-sm font-bold shadow-lg ring-2 ring-purple-400/20"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate"><?php echo $_SESSION['user_name']; ?></p>
                    <p class="text-[11px] text-indigo-300/60"><?php echo $_SESSION['user_role'] === 'admin' ? 'ຜູ້ດູແລລະບົບ' : 'ພະນັກງານ'; ?></p>
                </div>
                <a href="logout.php" class="w-8 h-8 rounded-lg bg-red-500/20 text-red-300 flex items-center justify-center hover:bg-red-500/30 hover:text-red-200 transition-all" title="ອອກຈາກລະບົບ">
                    <i class="fas fa-sign-out-alt text-sm"></i>
                </a>
            </div>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto overflow-x-hidden relative w-full h-screen main-area">
        <!-- Top Bar Desktop -->
        <div class="hidden md:flex items-center justify-between px-8 py-4 bg-white border-b border-gray-100 sticky top-0 z-10">
            <div>
                <h2 id="pageTitle" class="text-xl font-bold text-gray-900">ພາບລວມລະບົບ</h2>
                <p id="pageSubtitle" class="text-sm text-gray-500">ຂໍ້ມູນສະຖິຕິການຂາຍປະຈຳວັນ</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-xl text-sm text-gray-600">
                    <i class="fas fa-calendar-day text-indigo-500"></i>
                    <span class="font-medium"><?php echo date('d/m/Y'); ?></span>
                </div>
                <div class="flex items-center gap-3 pl-4 border-l border-gray-200">
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-800"><?php echo $_SESSION['user_name']; ?></p>
                        <p class="text-xs text-gray-400"><?php echo $_SESSION['user_role'] === 'admin' ? 'Admin' : 'Staff'; ?></p>
                    </div>
                    <div class="w-9 h-9 rounded-xl gradient-blue flex items-center justify-center text-white text-sm font-bold"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
                </div>
            </div>
        </div>

        <!-- Mobile Header -->
        <div class="md:hidden flex justify-between items-center mb-4 bg-white p-4 border-b border-gray-100 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 gradient-blue rounded-xl flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-store text-sm"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-900">POS System</h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm font-semibold text-gray-600"><?php echo $_SESSION['user_name']; ?></span>
                <a href="logout.php" class="text-red-500 bg-red-50 p-2 rounded-xl hover:bg-red-100 transition-colors"><i class="fas fa-sign-out-alt text-sm"></i></a>
            </div>
        </div>

        <div id="dashboard" class="page fade-in-up pb-20 md:pb-0 px-4 md:px-8 py-6">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                <!-- Stat: ยอดขาย -->
                <div class="stat-card blue">
                    <div class="stat-glow"></div>
                    <div class="flex justify-between items-start mb-4">
                        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                        <span class="badge badge-blue"><i class="fas fa-arrow-up text-[10px]"></i> ມື້ນີ້</span>
                    </div>
                    <div class="stat-value mb-1"><?php echo number_format($sales_data['today_sales'] ?? 0); ?></div>
                    <p class="text-sm text-gray-500 font-medium">ຍອດຂາຍມື້ນີ້</p>
                </div>
                
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <!-- Stat: กำไร -->
                <div class="stat-card green">
                    <div class="stat-glow"></div>
                    <div class="flex justify-between items-start mb-4">
                        <div class="stat-icon"><i class="fas fa-coins"></i></div>
                        <span class="badge badge-green"><i class="fas fa-trending-up text-[10px]"></i> ກຳໄລ</span>
                    </div>
                    <div class="stat-value mb-1"><?php echo number_format($sales_data['today_profit'] ?? 0); ?></div>
                    <p class="text-sm text-gray-500 font-medium">ກຳໄລມື້ນີ້</p>
                </div>
                <?php endif; ?>
                
                <!-- Stat: บิลขาย -->
                <div class="stat-card purple">
                    <div class="stat-glow"></div>
                    <div class="flex justify-between items-start mb-4">
                        <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                        <span class="badge badge-purple"><?php echo number_format($items_data['today_items'] ?? 0); ?> ຊິ້ນ</span>
                    </div>
                    <div class="stat-value mb-1"><?php echo number_format($sales_data['today_orders'] ?? 0); ?></div>
                    <p class="text-sm text-gray-500 font-medium">ບິນຂາຍມື້ນີ້</p>
                </div>

                <!-- Stat: สินค้าคงเหลือ -->
                <div class="stat-card orange">
                    <div class="stat-glow"></div>
                    <div class="flex justify-between items-start mb-4">
                        <div class="stat-icon"><i class="fas fa-boxes-stacked"></i></div>
                        <span class="badge badge-orange">ໃນສາງ</span>
                    </div>
                    <div class="stat-value mb-1"><?php echo number_format($stock_data['total_stock'] ?? 0); ?></div>
                    <p class="text-sm text-gray-500 font-medium">ສິນຄ້າຄົງເຫຼືອ</p>
                </div>
            </div>

            <!-- Recent Sales Table -->
            <div class="content-card">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-indigo-50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clock text-indigo-500"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">ລາຍການຂາຍລ່າສຸດ</h3>
                            <p class="text-xs text-gray-400">ຂໍ້ມູນການຂາຍປະຈຳວັນນີ້</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>ເວລາ</th>
                                <th>ບາໂຄດ</th>
                                <th>ສິນຄ້າ</th>
                                <th class="text-center">ຈຳນວນ</th>
                                <th class="text-right">ລາຄາລວມ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($today_sales)): ?>
                                <tr><td colspan="5" class="text-center py-16">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4">
                                            <i class="fas fa-inbox text-3xl text-gray-300"></i>
                                        </div>
                                        <p class="text-gray-400 font-medium">ຍັງບໍ່ມີການຂາຍມື້ນີ້</p>
                                        <p class="text-gray-300 text-xs mt-1">ເລີ່ມຂາຍສິນຄ້າເພື່ອເບິ່ງຂໍ້ມູນ</p>
                                    </div>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($today_sales as $sale): ?>
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                            <span class="text-gray-600"><?php echo date('H:i', strtotime($sale['sale_date'])); ?></span>
                                        </div>
                                    </td>
                                    <td><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded-md text-gray-500"><?php echo $sale['barcode']; ?></span></td>
                                    <td class="font-semibold text-gray-800"><?php echo $sale['name']; ?></td>
                                    <td class="text-center"><span class="badge badge-blue"><?php echo $sale['quantity']; ?></span></td>
                                    <td class="text-right font-bold text-gray-800"><?php echo number_format($sale['price'] * $sale['quantity']); ?> ₭</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="sell" class="page hidden fade-in-up pb-24 md:pb-0 px-4 md:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-auto lg:h-[calc(100vh-100px)]">
                <div class="lg:col-span-7 flex flex-col gap-5 order-1 lg:order-1">
                    <div class="content-card p-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-9 h-9 bg-indigo-50 rounded-xl flex items-center justify-center">
                                <i class="fas fa-barcode text-indigo-500"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">ຂາຍສິນຄ້າ</h3>
                                <p class="text-xs text-gray-400">ສະແກນບາໂຄດ ຫຼື ຄົ້ນຫາສິນຄ້າ</p>
                            </div>
                        </div>
                        <form id="sellForm">
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ລະຫັດບາໂຄດ</label>
                                <input type="text" id="barcodeInput" class="input-modern text-lg font-medium" placeholder="ສະແກນ ຫຼື ພິມລະຫັດ..." autofocus>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">ເລືອກສິນຄ້າ</label>
                                    <select id="productSelect" class="input-modern">
                                        <option value="">-- ຄົ້ນຫາສິນຄ້າ --</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                data-barcode="<?php echo htmlspecialchars($product['barcode']); ?>" 
                                                data-price="<?php echo $product['price']; ?>" 
                                                data-cost="<?php echo $product['cost']; ?>" 
                                                data-stock="<?php echo $product['stock']; ?>" 
                                                data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">ຈຳນວນ</label>
                                    <input type="number" id="quantityInput" value="1" min="1" class="input-modern text-center font-bold text-lg">
                                </div>
                            </div>
                            <input type="hidden" id="priceInput"> 
                            <div class="flex gap-3">
                                <button type="button" onclick="addToCart()" class="btn-primary flex-1 p-3.5 text-sm">
                                    <i class="fas fa-plus-circle mr-2"></i> ເພີ່ມລົງກະຕ່າ
                                </button>
                                <button type="button" onclick="clearSellForm()" class="btn-ghost px-5 text-sm">
                                    <i class="fas fa-eraser"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-5 flex flex-col h-full order-2 lg:order-2">
                    <div class="content-card flex flex-col h-full min-h-[400px]">
                        <div class="px-5 py-4 flex justify-between items-center border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 gradient-purple rounded-xl flex items-center justify-center text-white">
                                    <i class="fas fa-shopping-cart text-sm"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-sm">ກະຕ່າສິນຄ້າ</h3>
                                    <p class="text-xs text-gray-400"><span id="cartCountMobile">0</span> ລາຍການ</p>
                                </div>
                            </div>
                            <button onclick="clearCart()" class="text-xs text-gray-400 hover:text-red-500 bg-gray-50 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-all font-medium"><i class="fas fa-trash mr-1"></i> ລ້າງ</button>
                        </div>
                        
                        <div id="cartItems" class="flex-1 overflow-y-auto p-4 space-y-2 bg-gray-50/50 max-h-[300px] lg:max-h-none">
                            <div class="text-gray-400 text-center py-10 flex flex-col items-center">
                                <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                                    <i class="fas fa-basket-shopping text-2xl text-gray-300"></i>
                                </div>
                                <p class="font-medium text-sm">ຍັງບໍ່ມີສິນຄ້າໃນກະຕ່າ</p>
                            </div>
                        </div>

                        <div class="p-5 bg-white border-t border-gray-100">
                            <div class="space-y-2.5 mb-5 text-sm">
                                <div class="flex justify-between text-gray-500">
                                    <span>ລວມເງິນ:</span>
                                    <span id="subtotal" class="font-semibold text-gray-700">0</span>
                                </div>
                                <div class="flex justify-between items-center text-gray-500">
                                    <span>ສ່ວນຫຼຸດ:</span>
                                    <div class="flex items-center gap-2">
                                        <input type="number" id="discountInput" value="0" min="0" max="100" class="w-14 p-1.5 bg-gray-50 border border-gray-200 rounded-lg text-center text-xs font-semibold focus:ring-2 focus:ring-indigo-500 outline-none transition-all" oninput="updateCartTotal()">
                                        <span class="text-xs font-medium text-gray-400">%</span>
                                    </div>
                                    <span id="discountAmount" class="text-red-500 font-semibold">-0</span>
                                </div>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <div class="flex justify-between text-xs text-green-600 bg-green-50 px-3 py-2 rounded-lg">
                                    <span>ກຳໄລ:</span>
                                    <span id="realProfit" class="font-bold">0 บาท</span>
                                </div>
                                <?php endif; ?>
                                <div class="flex justify-between items-end pt-3 border-t border-dashed border-gray-200">
                                    <span class="text-gray-800 font-bold">ຍອດຊຳລະ:</span>
                                    <div class="text-right">
                                        <div id="cartTotal" class="text-2xl font-extrabold text-indigo-600">0</div>
                                        <div id="cartTotalLak" class="text-xs text-gray-400 font-medium">0 ກີບ</div>
                                    </div>
                                </div>
                            </div>
                            <button onclick="showCheckoutConfirm()" id="checkoutBtn" disabled class="btn-success w-full p-3.5 text-sm font-bold disabled:opacity-40 disabled:cursor-not-allowed disabled:transform-none transition-all active:scale-[0.98]">
                                <i class="fas fa-money-bill-wave mr-2"></i> ຊຳລະເງິນ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="sales" class="page hidden fade-in-up pb-24 md:pb-0 px-4 md:px-8 py-6">
            <div class="content-card p-6">
                <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                    
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-2 bg-gray-50 p-3 rounded-xl w-full md:w-auto">
                        <div class="flex items-center w-full md:w-auto gap-2">
                            <span class="text-gray-500 text-sm whitespace-nowrap font-medium">ຈາກ:</span>
                            <input type="date" id="startDate" class="input-modern p-2 text-sm" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="flex items-center w-full md:w-auto gap-2">
                            <span class="text-gray-500 text-sm whitespace-nowrap font-medium">ຫາ:</span>
                            <input type="date" id="endDate" class="input-modern p-2 text-sm" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <button onclick="filterSales()" class="btn-primary px-4 py-2 text-sm w-full md:w-auto">
                            <i class="fas fa-search mr-1"></i> ຄົ້ນຫາ
                        </button>
                    </div>
                    
                    <div class="flex-1 max-w-xs md:ml-4 w-full">
                        <input type="text" id="salesSearchInput" onkeyup="searchSalesTable()" placeholder="ຄົ້ນຫາ ບາໂຄດ..." class="input-modern p-2.5 text-sm">
                    </div>

                    <div class="flex gap-4 w-full md:w-auto justify-end">
                        <div class="text-right bg-indigo-50 px-4 py-2.5 rounded-xl">
                            <p class="text-[11px] text-indigo-500 font-semibold uppercase tracking-wide">ຍອດຂາຍລວມ</p>
                            <p class="text-lg font-extrabold text-indigo-600" id="summaryTotalSales">0</p>
                        </div>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <div class="text-right bg-green-50 px-4 py-2.5 rounded-xl">
                            <p class="text-[11px] text-green-500 font-semibold uppercase tracking-wide">ກຳໄລລວມ</p>
                            <p class="text-lg font-extrabold text-green-600" id="summaryTotalProfit">0</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="salesList" class="overflow-x-auto -mx-4 md:mx-0">
                    </div>
            </div>
        </div>

        <div id="shop" class="page hidden fade-in-up pb-24 md:pb-0 px-4 md:px-8 py-6">
            <div class="content-card max-w-2xl p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-indigo-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-cog text-indigo-500"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">ຕັ້ງຄ່າຮ້ານຄ້າ</h3>
                        <p class="text-xs text-gray-400">ແກ້ໄຂຂໍ້ມູນຮ້ານຄ້າ</p>
                    </div>
                </div>
                <form id="shopForm" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ຊື່ຮ້ານຄ້າ</label>
                        <input type="text" id="shopName" class="input-modern">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ທີ່ຢູ່</label>
                        <textarea id="shopAddress" rows="3" class="input-modern resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ເບີໂທລະສັບ</label>
                        <input type="text" id="shopPhone" class="input-modern">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">QR Code ຮັບເງິນ</label>
                        <div class="flex items-center gap-4">
                            <input type="file" id="shopQRInput" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 transition-all cursor-pointer">
                            <img id="shopQRPreview" src="" class="h-16 w-16 object-contain border border-gray-200 rounded-xl hidden">
                        </div>
                        <button type="button" onclick="removeQRCode()" class="text-xs text-red-500 mt-2 hover:underline font-medium">ລົບຮູບ QR</button>
                    </div>

                    <button type="button" onclick="saveShopInfo()" class="btn-primary px-8 py-3 text-sm">
                        <i class="fas fa-save mr-2"></i> ບັນທຶກຂໍ້ມູນ
                    </button>
                </form>
            </div>
        </div>

    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 flex justify-around py-2 z-30 mobile-nav shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
        <button onclick="showPage('dashboard', this)" class="mobile-nav-item active flex flex-col items-center p-2 text-gray-400 w-full transition-all">
            <i class="fas fa-chart-pie text-lg mb-1"></i> <span class="text-[10px] font-semibold">ແດຊບອດ</span>
        </button>
        <button onclick="showPage('sell', this)" class="mobile-nav-item flex flex-col items-center p-2 text-gray-400 w-full transition-all">
            <i class="fas fa-cash-register text-lg mb-1"></i> <span class="text-[10px] font-semibold">ຂາຍ</span>
        </button>
        <button onclick="showPage('sales', this)" class="mobile-nav-item flex flex-col items-center p-2 text-gray-400 w-full transition-all">
            <i class="fas fa-receipt text-lg mb-1"></i> <span class="text-[10px] font-semibold">ປະຫວັດ</span>
        </button>
        <button onclick="toggleMobileMenu()" class="mobile-nav-item flex flex-col items-center p-2 text-gray-400 w-full transition-all">
            <i class="fas fa-bars text-lg mb-1"></i> <span class="text-[10px] font-semibold">ເມນູ</span>
        </button>
    </nav>

    <!-- Mobile Menu Modal -->
    <div id="mobileMenuModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden transition-opacity">
        <div class="absolute right-0 top-0 bottom-0 w-72 bg-white shadow-2xl p-6 transform translate-x-full transition-transform duration-300" id="mobileMenuContent">
            <div class="flex justify-between items-center mb-8">
                <h3 class="font-bold text-lg text-gray-900">ເມນູເພີ່ມເຕີມ</h3>
                <button onclick="toggleMobileMenu()" class="text-gray-400 text-xl hover:text-gray-600 transition-colors w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fas fa-times"></i></button>
            </div>
            <ul class="space-y-1">
                <li><a href="products.php" class="flex items-center gap-3 text-gray-600 p-3 rounded-xl hover:bg-gray-50 transition-all font-medium text-sm"><i class="fas fa-box w-8 text-center text-indigo-500"></i> ຈັດການສິນຄ້າ</a></li>
                <li><a href="add_product_print.php" class="flex items-center gap-3 text-gray-600 p-3 rounded-xl hover:bg-gray-50 transition-all font-medium text-sm"><i class="fas fa-barcode w-8 text-center text-indigo-500"></i> ພິມບາໂຄດ</a></li>
                <li><a href="#" onclick="showPage('shop', null); toggleMobileMenu();" class="flex items-center gap-3 text-gray-600 p-3 rounded-xl hover:bg-gray-50 transition-all font-medium text-sm"><i class="fas fa-cog w-8 text-center text-indigo-500"></i> ຕັ້ງຄ່າຮ້ານຄ້າ</a></li>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <div class="border-t border-gray-100 my-3"></div>
                <p class="text-[11px] text-gray-400 uppercase font-bold px-3 tracking-wide">ຜູ້ດູແລລະບົບ</p>
                <li><a href="employees.php" class="flex items-center gap-3 text-gray-600 p-3 rounded-xl hover:bg-gray-50 transition-all font-medium text-sm"><i class="fas fa-users w-8 text-center text-indigo-500"></i> ພະນັກງານ</a></li>
                <li><a href="currency.php" class="flex items-center gap-3 text-gray-600 p-3 rounded-xl hover:bg-gray-50 transition-all font-medium text-sm"><i class="fas fa-coins w-8 text-center text-indigo-500"></i> ສະກຸນເງິນ</a></li>
                <?php endif; ?>
                <div class="border-t border-gray-100 my-3"></div>
                <li><a href="logout.php" class="flex items-center gap-3 text-red-500 p-3 rounded-xl hover:bg-red-50 transition-all font-medium text-sm"><i class="fas fa-sign-out-alt w-8 text-center"></i> ອອກຈາກລະບົບ</a></li>
            </ul>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl transform transition-all scale-100 max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-green-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">ຢືນຢັນການຊຳລະ</h3>
                </div>
                <button onclick="closeCheckoutModal()" class="text-gray-400 hover:text-gray-600 transition-colors w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div class="bg-gray-50 p-4 rounded-xl space-y-3">
                    <div class="flex justify-between text-sm text-gray-600"><span>ຈຳນວນລາຍການ:</span><span id="confirmItemCount" class="font-bold text-gray-800">0</span></div>
                    <div class="flex justify-between text-sm text-gray-600"><span>ຍອດລວມ:</span><span id="confirmSubtotal" class="font-bold text-gray-800">0</span></div>
                    <div class="flex justify-between text-sm text-red-500"><span>ສ່ວນຫຼຸດ:</span><span id="confirmDiscount" class="font-bold">0</span></div>
                    <div class="border-t border-dashed border-gray-200 pt-3 mt-3 flex justify-between items-end">
                        <span class="font-bold text-gray-800">ຍອດຊຳລະ:</span>
                        <div class="text-right">
                            <div id="confirmTotal" class="text-2xl font-extrabold text-indigo-600">0</div>
                            <div id="confirmTotalLak" class="text-xs text-gray-400 font-medium">0 ກີບ</div>
                        </div>
                    </div>
                </div>
                <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-all">
                    <input type="checkbox" id="printReceiptCheck" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" checked>
                    <span class="text-gray-700 font-medium text-sm">ພິມໃບເສັດອັດຕະໂນມັດ</span>
                </label>
                <div class="flex gap-3 pt-2">
                    <button onclick="closeCheckoutModal()" class="btn-ghost flex-1 py-3 text-sm">ຍົກເລີກ</button>
                    <button onclick="confirmCheckout()" class="btn-success flex-1 py-3 text-sm font-bold">
                        <i class="fas fa-check mr-2"></i>ຢືນຢັນ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white w-[350px] shadow-2xl max-h-[90vh] flex flex-col rounded-2xl overflow-hidden"> 
            <div class="p-4 border-b border-gray-100 flex justify-between items-center no-print-in-modal">
                <div class="flex items-center gap-2">
                    <i class="fas fa-receipt text-indigo-500"></i>
                    <h3 class="font-bold text-gray-800 text-sm">ໃບເສັດ</h3>
                </div>
                <button onclick="closeReceiptModal()" class="text-gray-400 hover:text-gray-600 transition-colors w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fas fa-times text-sm"></i></button>
            </div>
            <div id="receiptContent" class="p-4 bg-white text-black font-mono text-sm overflow-y-auto flex-1">
                </div>
            <div class="p-4 border-t border-gray-100 flex gap-3 no-print-in-modal bg-white">
                <button onclick="printReceipt()" class="btn-primary flex-1 py-2 text-sm"><i class="fas fa-print mr-2"></i> ພິມ</button>
                <button onclick="closeReceiptModal()" class="btn-ghost flex-1 py-2 text-sm">ປິດ</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white p-8 rounded-2xl shadow-2xl text-center w-full max-w-sm">
            <div class="w-20 h-20 gradient-green text-white rounded-2xl flex items-center justify-center mx-auto mb-6 text-4xl shadow-lg shadow-green-500/20">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">ຊຳລະເງິນສຳເລັດ!</h3>
            <p class="text-gray-400 text-sm mb-8">ບັນທຶກຂໍ້ມູນການຂາຍຮຽບຮ້ອຍແລ້ວ</p>
            <div class="flex gap-3">
                <button onclick="showReceiptFromSuccess()" class="btn-primary flex-1 py-3 text-sm"><i class="fas fa-print mr-2"></i>ພິມໃບເສັດ</button>
                <button onclick="closeSuccessModal()" class="btn-ghost flex-1 py-3 text-sm">ປິດ</button>
            </div>
        </div>
    </div>

    <!-- Sale Items Modal (ສຳລັບທຸກ Role - ລຶບລາຍການດຽວ) -->
    <div id="saleItemsModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-4 bg-white border-b border-gray-100 flex justify-between items-center sticky top-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-indigo-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-list-ul text-indigo-500"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-sm">ລາຍການສິນຄ້າໃນບິນ</h3>
                        <p class="text-xs text-gray-400">ບິນ #<span id="si_saleId"></span></p>
                    </div>
                </div>
                <button onclick="document.getElementById('saleItemsModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition-colors w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 overflow-y-auto">
                <div id="si_itemList" class="space-y-2"></div>
                <div class="mt-4 pt-3 border-t border-gray-200 flex justify-between items-center">
                    <span class="text-sm font-bold text-gray-700">ຍອດລວມ:</span>
                    <span id="si_total" class="text-lg font-extrabold text-indigo-600">0</span>
                </div>
            </div>
            <div class="p-4 border-t border-gray-100 bg-white text-right sticky bottom-0">
                <button onclick="document.getElementById('saleItemsModal').classList.add('hidden')" class="btn-ghost px-6 py-2 text-sm">ປິດ</button>
            </div>
        </div>
    </div>

    <!-- Admin Detail Modal -->
    <div id="adminDetailModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-4 bg-white border-b border-gray-100 flex justify-between items-center sticky top-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-orange-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-bar text-orange-500"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 text-sm">ລາຍລະອຽດການຂາຍ (Admin)</h3>
                </div>
                <button onclick="document.getElementById('adminDetailModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition-colors w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto">
                <div class="grid grid-cols-2 gap-4 mb-6 text-sm bg-gray-50 p-4 rounded-xl">
                    <div><span class="text-gray-500">ເລກບິນ:</span> <span id="ad_saleId" class="font-bold text-indigo-600"></span></div>
                    <div><span class="text-gray-500">ວັນທີ:</span> <span id="ad_saleDate" class="font-bold text-gray-800"></span></div>
                    <div><span class="text-gray-500">ພະນັກງານ:</span> <span id="ad_saleEmp" class="font-bold text-gray-800"></span></div>
                </div>
                
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>ສິນຄ້າ/ບາໂຄດ</th>
                            <th class="text-center">ຈຳນວນ</th>
                            <th class="text-right">ຕົ້ນທຶນ</th>
                            <th class="text-right">ຂາຍ</th>
                            <th class="text-right">ກຳໄລ</th>
                            <th class="text-center" style="width:50px;">ລຶບ</th>
                        </tr>
                    </thead>
                    <tbody id="ad_itemList"></tbody>
                    <tfoot>
                        <tr class="bg-green-50">
                            <td colspan="5" class="p-3 text-right font-bold text-sm text-gray-700">ກຳໄລລວມ:</td>
                            <td class="p-3 text-right text-green-600 text-lg font-extrabold" id="ad_totalProfit">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100 bg-white text-right sticky bottom-0">
                <button onclick="document.getElementById('adminDetailModal').classList.add('hidden')" class="btn-ghost px-6 py-2 text-sm">ປິດ</button>
            </div>
        </div>
    </div>

    <script>
        // ປະກາດ Role ຂອງ User
        const userRole = "<?php echo $_SESSION['user_role']; ?>";
        // ປະກາດສຽງແຈ້ງເຕືອນ (ຟັງຊັນເກົ່າຂອງເຈົ້າ)
        const soundError = new Audio('sound/new-notification-010-352755.mp3');
        
        let cart = [];
        const products = <?php echo json_encode($products); ?>;
        const currencies = <?php echo json_encode($currencies); ?>;
        let currentSale = null;

        // --- Mobile Menu Logic ---
        function toggleMobileMenu() {
            const modal = document.getElementById('mobileMenuModal');
            const content = document.getElementById('mobileMenuContent');
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                setTimeout(() => content.classList.remove('translate-x-full'), 10);
            } else {
                content.classList.add('translate-x-full');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }
        }

        // --- Navigation ---
        function showPage(pageId, element) {
            // Update Active Menu
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.mobile-nav-item').forEach(el => el.classList.remove('active', 'text-blue-600'));
            
            // Highlight Desktop Menu
            const desktopLink = document.querySelector(`.nav-item[onclick="showPage('${pageId}', this)"]`);
            if (desktopLink) desktopLink.classList.add('active');

            // Highlight Mobile Menu
            const mobileBtn = document.querySelector(`.mobile-nav-item[onclick="showPage('${pageId}', this)"]`);
            if (mobileBtn) {
                mobileBtn.classList.add('active', 'text-blue-600');
                mobileBtn.classList.remove('text-gray-400');
            }
            
            // Update top bar title
            const titles = {
                'dashboard': ['ພາບລວມລະບົບ', 'ຂໍ້ມູນສະຖິຕິການຂາຍປະຈຳວັນ'],
                'sell': ['ຂາຍສິນຄ້າ', 'ສະແກນບາໂຄດ ຫຼື ຄົ້ນຫາສິນຄ້າ'],
                'sales': ['ປະຫວັດການຂາຍ', 'ເບິ່ງລາຍການຂາຍທັງໝົດ'],
                'shop': ['ຕັ້ງຄ່າຮ້ານຄ້າ', 'ແກ້ໄຂຂໍ້ມູນຮ້ານຄ້າ'],
            };
            const t = titles[pageId] || ['POS System', ''];
            const pt = document.getElementById('pageTitle');
            const ps = document.getElementById('pageSubtitle');
            if (pt) pt.textContent = t[0];
            if (ps) ps.textContent = t[1];

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
            
            // Check Stock (ຟັງຊັນເກົ່າຂອງເຈົ້າ)
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
            const mobileCount = document.getElementById('cartCountMobile');
            
            if (mobileCount) mobileCount.innerText = cart.length; // ອັບເດດເລກໃນມືຖື

            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="text-gray-400 text-center py-10 flex flex-col items-center">
                        <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                            <i class="fas fa-basket-shopping text-2xl text-gray-300"></i>
                        </div>
                        <p class="text-sm font-medium">ຍັງບໍ່ມີສິນຄ້າໃນກະຕ່າ</p>
                    </div>`;
                document.getElementById('checkoutBtn').disabled = true;
            } else {
                let html = '';
                cart.forEach((item, index) => {
                    html += `
                    <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-gray-100 hover:border-gray-200 transition-all">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-800 text-sm truncate">${item.name}</h4>
                            <div class="text-xs text-gray-400 mt-0.5">${item.price.toLocaleString()} x ${item.quantity}</div>
                        </div>
                        <div class="text-right mx-3">
                            <span class="font-bold text-indigo-600 text-sm">${(item.price * item.quantity).toLocaleString()}</span>
                        </div>
                        <button onclick="removeFromCart(${index})" class="w-7 h-7 rounded-lg bg-gray-50 text-gray-400 hover:bg-red-50 hover:text-red-500 flex items-center justify-center transition-all"><i class="fas fa-times text-xs"></i></button>
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
                
                // (Hide Profit Logic)
                if(document.getElementById('realProfit')) {
                    document.getElementById('realProfit').innerText = realProfit.toLocaleString();
                }
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
            // (Date Range Filter Logic - Updated)
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            if (!startDate || !endDate) { alert("ກະລຸນາເລືອກວັນທີໃຫ້ຄົບຖ້ວນ"); return; }

            fetch(`get_sales.php?start_date=${startDate}&end_date=${endDate}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('summaryTotalSales').innerText = parseFloat(data.summary?.total_sales || 0).toLocaleString();
                    
                    if(document.getElementById('summaryTotalProfit')) {
                        document.getElementById('summaryTotalProfit').innerText = parseFloat(data.summary?.total_profit || 0).toLocaleString();
                    }
                    
                    const list = document.getElementById('salesList');
                    if(data.sales.length === 0) {
                        list.innerHTML = '<div class="text-center p-8 text-gray-400">ບໍ່ພົບລາຍການຂາຍໃນຊ່ວງເວລານີ້</div>';
                        return;
                    }
                    
                    let html = `<table class="table-modern">
                        <thead>
                            <tr>
                                <th>ເວລາ</th>
                                <th>ບາໂຄດສິນຄ້າ</th>
                                <th class="text-center">ຈຳນວນ</th>
                                <th class="text-right">ຍອດລວມ</th>
                                <th class="text-center">ຈັດການ</th>
                            </tr>
                        </thead>
                        <tbody>`;
                    
                    data.sales.forEach(sale => {
                        let barcodes = sale.barcodes || '-';
                        let displayBarcodes = barcodes.length > 30 ? barcodes.substring(0, 30) + '...' : barcodes;
                        
                        // Admin Button Check
                        let adminBtn = '';
                        if (userRole === 'admin') {
                            adminBtn = `<button onclick="viewAdminDetail(${sale.id})" class="w-8 h-8 rounded-lg text-gray-400 hover:text-orange-600 hover:bg-orange-50 flex items-center justify-center transition-all" title="ເບິ່ງກຳໄລ"><i class="fas fa-eye text-sm"></i></button>`;
                        }
                        // ປຸ່ມເບິ່ງລາຍການໃນບິນ (Admin ເທົ່ານັ້ນ)
                        let itemsBtn = '';
                        if (userRole === 'admin') {
                            itemsBtn = `<button onclick="viewSaleItems(${sale.id})" class="w-8 h-8 rounded-lg text-gray-400 hover:text-purple-600 hover:bg-purple-50 flex items-center justify-center transition-all" title="ເບິ່ງ/ລຶບລາຍການ"><i class="fas fa-list-ul text-sm"></i></button>`;
                        }

                        html += `
                        <tr class="sales-row" data-search="${barcodes.toLowerCase()} ${sale.id}">
                            <td>
                                <div class="text-gray-700 font-medium">${new Date(sale.sale_date).toLocaleDateString('lo-LA')}</div>
                                <div class="text-xs text-gray-400">${new Date(sale.sale_date).toLocaleTimeString('lo-LA')}</div>
                            </td>
                            <td><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded-md text-gray-500" title="${barcodes}">${displayBarcodes}</span></td>
                            <td class="text-center"><span class="badge badge-purple">${sale.item_count} ລາຍການ</span></td>
                            <td class="text-right font-bold text-indigo-600">${parseFloat(sale.total).toLocaleString()}</td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="viewSaleDetail(${sale.id})" class="w-8 h-8 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-all" title="ພິມໃບເສັດ"><i class="fas fa-print text-sm"></i></button>
                                    ${itemsBtn}
                                    ${adminBtn}
                                    <button onclick="deleteSale(${sale.id})" class="w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition-all" title="ລຶບ"><i class="fas fa-trash-alt text-sm"></i></button>
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

                        currentAdminSaleId = s.id;
                        let html = '';
                        s.items.forEach(item => {
                            const itemProfit = (item.price - item.cost) * item.quantity;
                            html += `
                            <tr id="ad_row_${item.item_id}">
                                <td class="p-2 border-b">
                                    <div class="font-bold">${item.name}</div>
                                    <div class="text-xs text-gray-400">${item.barcode}</div>
                                </td>
                                <td class="p-2 border-b text-center">${item.quantity}</td>
                                <td class="p-2 border-b text-right text-gray-500">${parseFloat(item.cost).toLocaleString()}</td>
                                <td class="p-2 border-b text-right">${parseFloat(item.price).toLocaleString()}</td>
                                <td class="p-2 border-b text-right font-bold text-green-600">+${itemProfit.toLocaleString()}</td>
                                <td class="p-2 border-b text-center">
                                    <button onclick="deleteSaleItem(${item.item_id}, '${item.name.replace(/'/g, "\\'")  }', ${item.quantity})" class="w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition-all mx-auto" title="ລຶບ & ຄືນ stock">
                                        <i class="fas fa-trash-alt text-sm"></i>
                                    </button>
                                </td>
                            </tr>`;
                        });
                        document.getElementById('ad_itemList').innerHTML = html;
                        document.getElementById('adminDetailModal').classList.remove('hidden');
                    }
                });
        }

        let currentAdminSaleId = null;
        let currentSaleItemsId = null;

        // ========== ເບິ່ງລາຍການສິນຄ້າໃນບິນ (ທຸກ Role) ==========
        function viewSaleItems(id) {
            currentSaleItemsId = id;
            fetch('get_sale_detail.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const s = data.sale;
                        document.getElementById('si_saleId').innerText = s.id;
                        document.getElementById('si_total').innerText = parseFloat(s.total).toLocaleString();

                        let html = '';
                        s.items.forEach(item => {
                            const itemTotal = item.price * item.quantity;
                            html += `
                            <div id="si_row_${item.item_id}" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-red-50/30 transition-colors group">
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-gray-800 text-sm truncate">${item.name}</div>
                                    <div class="text-xs text-gray-400 font-mono">${item.barcode}</div>
                                </div>
                                <div class="text-center px-3">
                                    <div class="text-sm font-bold text-indigo-600">x${item.quantity}</div>
                                </div>
                                <div class="text-right px-3 min-w-[80px]">
                                    <div class="text-sm font-bold text-gray-800">${itemTotal.toLocaleString()}</div>
                                    <div class="text-xs text-gray-400">${parseFloat(item.price).toLocaleString()}/ຊິ້ນ</div>
                                </div>
                                <button onclick="deleteSaleItem(${item.item_id}, '${item.name.replace(/'/g, "\\\'")}', ${item.quantity})" 
                                    class="w-9 h-9 rounded-xl text-gray-300 hover:text-red-600 hover:bg-red-100 flex items-center justify-center transition-all opacity-50 group-hover:opacity-100" 
                                    title="ລຶບ & ຄືນ stock">
                                    <i class="fas fa-trash-alt text-sm"></i>
                                </button>
                            </div>`;
                        });
                        document.getElementById('si_itemList').innerHTML = html;
                        document.getElementById('saleItemsModal').classList.remove('hidden');
                    }
                });
        }

        function deleteSaleItem(itemId, itemName, qty) {
            if (!confirm(`ຕ້ອງການລຶບ "${itemName}" x${qty} ອອກຈາກບິນນີ້ແທ້ບໍ່?\n\n*ລະບົບຈະຄືນ stock ${qty} ຊິ້ນ ໂດຍອັດຕະໂນມັດ`)) return;

            fetch('delete_sale_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sale_item_id: itemId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    if (data.sale_deleted) {
                        // ບິນຖືກລຶບໝົດແລ້ວ → ປິດ modal ທັງສອງ
                        document.getElementById('adminDetailModal').classList.add('hidden');
                        document.getElementById('saleItemsModal').classList.add('hidden');
                    } else {
                        // ໂຫຼດ modal ໃໝ່
                        if (!document.getElementById('saleItemsModal').classList.contains('hidden')) {
                            viewSaleItems(data.sale_id);
                        }
                        if (!document.getElementById('adminDetailModal').classList.contains('hidden')) {
                            viewAdminDetail(data.sale_id);
                        }
                    }
                    filterSales(); // Refresh ລາຍການ
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່');
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
                        if(document.getElementById('startDate').value === new Date().toISOString().split('T')[0]) {
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