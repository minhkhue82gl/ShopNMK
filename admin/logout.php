<?php
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}

// Xóa toàn bộ Session
session_destroy();

// Khởi tạo lại session mới để gửi thông báo sau khi đăng xuất
session_start();
$_SESSION['success'] = "Bạn đã đăng xuất khỏi hệ thống thành công!";

// Chuyển hướng về trang đăng nhập
redirect(BASE_URL . 'admin/login.php');