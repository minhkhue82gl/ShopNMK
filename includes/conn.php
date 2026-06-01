<?php
// Khởi tạo session nếu chưa có (rất quan trọng để quản lý giỏ hàng và đăng nhập)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'shop_giay_nmk';
$username = 'root';
$password = ''; // Điền password MySQL

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Cấu hình PDO báo lỗi khi có sai sót SQL
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Trả dữ liệu về dạng mảng Associate (Key-Value)
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
}
?>