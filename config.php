<?php
ob_start(); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shop_giay_nmk');

define('BASE_URL', 'http://localhost/shop-giay-nmk/'); 
define('ROOT_PATH', __DIR__ . '/');

define('UPLOAD_DIR', ROOT_PATH . 'assets/uploads/');

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]; 
    
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        $options
    );

    $conn = $pdo;

} catch (PDOException $e) {
    die("Lỗi kết nối cơ sở dữ liệu NMK: " . $e->getMessage());
}

function format_money($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}


function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}


function redirect($url) {
    header("Location: " . $url);
    exit();
}


function is_loggedin() {
    return isset($_SESSION['user_id']) || isset($_SESSION['user']);
}


function check_admin_access() {
    $role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
    if (!is_loggedin() || !in_array($role, ['admin', 'staff'])) {
        redirect(BASE_URL . 'site/dang-nhap.php');
    }
}


function upload_image($file, $folder = 'products') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_exts)) {
        return false;
    }

    $new_filename = 'nmk_' . time() . '_' . uniqid() . '.' . $file_ext;
    
    $target_dir = UPLOAD_DIR . ($folder ? trim($folder, '/') . '/' : '');

    // Tự động khởi tạo cấu trúc thư mục nếu chưa có
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
        return $new_filename;
    }

    return false;
}


function get_cart_total() {
    $total = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}


function get_cart_count() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}


function get_order_status_badge($status) {
    switch ($status) {
        case 'Chờ xác nhận':
            return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Chờ xác nhận</span>';
        case 'Đang xử lý':
            return '<span class="badge bg-info text-dark"><i class="fa-solid fa-gear me-1"></i>Đang xử lý</span>';
        case 'Đang giao':
            return '<span class="badge bg-primary"><i class="fa-solid fa-truck me-1"></i>Đang giao</span>';
        case 'Đã giao':
            return '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>Đã giao</span>';
        case 'Đã hủy':
            return '<span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Đã hủy</span>';
        default:
            return '<span class="badge bg-secondary">' . sanitize($status) . '</span>';
    }
}