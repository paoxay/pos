<?php
// การตั้งค่าฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'shirt_shop');
define('DB_USER', 'root'); // เปลี่ยนตามการตั้งค่าของคุณ
define('DB_PASS', ''); // เปลี่ยนตามการตั้งค่าของคุณ

// เชื่อมต่อฐานข้อมูล
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}

// เริ่ม Session
session_start();

// ฟังก์ชันตรวจสอบการล็อกอิน
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// ฟังก์ชันตรวจสอบสิทธิ์ Admin
function checkAdmin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        die('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
}
?>