<?php
// 1. Khởi động hệ thống Session để định vị phiên làm việc hiện tại
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Xóa bỏ thông tin đăng nhập của người dùng khỏi Session
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}

// Mẹo thêm: Nếu bạn muốn xóa toàn bộ phiên làm việc (bao gồm cả giỏ hàng, mã giảm giá đang áp dụng)
// thì bạn có thể bỏ dấu comment (//) của lệnh phía dưới:
// session_destroy();

// 3. Chuyển hướng người dùng về trang chủ của Website sau khi thoát thành công
header('Location: index.php');
exit;
?>