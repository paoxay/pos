<?php
require_once 'config.php';
$error = '';
if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານ ບໍ່ຖືກຕ້ອງ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເຂົ້າສູ່ລະບົບ - POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Inter', 'Noto Sans Lao', sans-serif; }
        body { min-height: 100vh; }
        .login-bg {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
            position: relative; overflow: hidden;
        }
        .login-bg::before {
            content: ''; position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(99,102,241,0.3) 0%, transparent 70%);
            top: -100px; right: -100px; border-radius: 50%;
        }
        .login-bg::after {
            content: ''; position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(168,85,247,0.2) 0%, transparent 70%);
            bottom: -100px; left: -100px; border-radius: 50%;
        }
        .input-login {
            width: 100%; padding: 14px 16px 14px 48px;
            background: #f9fafb; border: 2px solid #e5e7eb;
            border-radius: 12px; font-size: 0.95rem;
            color: #1f2937; transition: all 0.25s ease; outline: none;
        }
        .input-login:focus {
            background: white; border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(99,102,241,0.4); }
        .btn-login:active { transform: translateY(0); }
        .slide-up { animation: slideUp 0.5s ease-out forwards; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="flex min-h-screen">
    <!-- Left: Branding Panel -->
    <div class="hidden lg:flex lg:w-1/2 login-bg items-center justify-center p-12 relative">
        <div class="relative z-10 text-center">
            <div class="w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-8 ring-1 ring-white/20">
                <i class="fas fa-store text-white text-3xl"></i>
            </div>
            <h1 class="text-4xl font-extrabold text-white mb-4 tracking-tight">POS System</h1>
            <p class="text-indigo-200/80 text-lg max-w-sm mx-auto leading-relaxed">ລະບົບຈຸດຂາຍສິນຄ້າ ທັນສະໄໝ ງ່າຍຕໍ່ການໃຊ້ງານ</p>
            <div class="mt-12 flex items-center justify-center gap-8 text-indigo-200/60 text-sm">
                <div class="flex items-center gap-2"><i class="fas fa-check-circle text-indigo-400"></i><span>ໄວ</span></div>
                <div class="flex items-center gap-2"><i class="fas fa-check-circle text-indigo-400"></i><span>ປອດໄພ</span></div>
                <div class="flex items-center gap-2"><i class="fas fa-check-circle text-indigo-400"></i><span>ງ່າຍ</span></div>
            </div>
        </div>
    </div>
    <!-- Right: Login Form -->
    <div class="flex-1 flex items-center justify-center p-6 md:p-12 bg-white">
        <div class="w-full max-w-md slide-up">
            <div class="lg:hidden text-center mb-10">
                <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-500/20">
                    <i class="fas fa-store text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900">POS System</h1>
            </div>
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">ເຂົ້າສູ່ລະບົບ</h2>
                <p class="text-gray-500 text-sm">ປ້ອນຊື່ຜູ້ໃຊ້ ແລະ ລະຫັດຜ່ານເພື່ອເລີ່ມຕົ້ນໃຊ້ງານ</p>
            </div>
            <?php if ($error): ?>
                <div class="bg-red-50 border-2 border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
                    </div>
                    <span class="font-medium"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ຊື່ຜູ້ໃຊ້</label>
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-user"></i></div>
                        <input type="text" name="username" class="input-login" placeholder="ປ້ອນຊື່ຜູ້ໃຊ້" required autocomplete="username">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ລະຫັດຜ່ານ</label>
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-lock"></i></div>
                        <input type="password" name="password" class="input-login" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="btn-login w-full text-white py-3.5 rounded-xl font-bold text-sm flex items-center justify-center gap-2 mt-2">
                    <span>ເຂົ້າສູ່ລະບົບ</span>
                    <i class="fas fa-arrow-right text-sm"></i>
                </button>
            </form>
            <div class="mt-10 pt-6 border-t border-gray-100 text-center">
                <p class="text-gray-400 text-xs">&copy; <?php echo date('Y'); ?> POS System</p>
            </div>
        </div>
    </div>
</body>
</html>