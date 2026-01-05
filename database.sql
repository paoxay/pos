-- สร้างฐานข้อมูล
CREATE DATABASE shirt_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shirt_shop;

-- ตารางพนักงาน
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') DEFAULT 'employee',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางสินค้า
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    stock INT DEFAULT 0,
    cost DECIMAL(10,2) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตารางการขาย
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    subtotal DECIMAL(10,2) NOT NULL,
    discount DECIMAL(5,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    profit DECIMAL(10,2) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ตารางรายการขาย
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ตารางสกุลเงิน
CREATE TABLE currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(50) NOT NULL,
    rate DECIMAL(10,4) DEFAULT 1,
    is_default BOOLEAN DEFAULT FALSE
);

-- เพิ่มข้อมูลเริ่มต้น
INSERT INTO employees (name, username, password, role) VALUES 
('ผู้ดูแลระบบ', 'admin', 'admin123', 'admin');

INSERT INTO currencies (code, name, rate, is_default) VALUES 
('THB', 'บาท', 1, TRUE),
('LAK', 'กีบ', 270, FALSE);

INSERT INTO products (barcode, name, stock, cost, price) VALUES 
('1234567890123', 'เสื้อฝ้าสีขาว ไซส์ M', 50, 80.00, 120.00),
('1234567890124', 'เสื้อฝ้าสีดำ ไซส์ L', 30, 85.00, 130.00),
('1234567890125', 'เสื้อฝ้าสีน้ำเงิน ไซส์ S', 25, 75.00, 110.00);