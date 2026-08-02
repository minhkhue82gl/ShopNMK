<?php
// Khởi chạy phiên làm việc hệ thống nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$is_logged_in = isset($_SESSION['user']) && is_array($_SESSION['user']);
$user_data = $is_logged_in ? $_SESSION['user'] : null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Giày NMK - Hệ Thống Giày Chính Hãng</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .top-bar { background-color: #f8f9fa; font-size: 13px; }
        .main-header { border-bottom: 1px solid #eceff1; padding: 15px 0; }
        .search-box .form-control { border-radius: 20px 0 0 20px; border-right: none; }
        .search-box .btn { border-radius: 0 20px 20px 0; background-color: #ff5722; color: white; border: 1px solid #ff5722; }
        .cart-icon { position: relative; font-size: 24px; color: #333; }
        .cart-badge { position: absolute; top: -5px; right: -10px; background: #ff5722; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; }
        .nav-menu { background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .nav-menu .nav-link { font-weight: 600; color: #333; text-transform: uppercase; padding: 12px 20px !important; }
        .nav-menu .nav-link:hover { color: #ff5722; }
    </style>
</head>
<body>

<div class="top-bar py-1 border-bottom">
    <div class="container d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-phone me-1"></i> Hotline: 0984981098</span>
        <div>
            <?php if($is_logged_in): ?>
                <!-- Khu vực tài khoản khi đã đăng nhập có Dropdown tiện ích -->
                <div class="dropdown d-inline-block">
                    <button class="btn btn-sm dropdown-toggle text-dark fw-bold py-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-user me-1 text-primary"></i> Xin chào, <strong><?= htmlspecialchars($user_data['fullname']) ?></strong>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-1" style="font-size: 13px;">
                        <li>
                            <a class="dropdown-item py-2" href="tai-khoan.php">
                                <i class="fa-solid fa-user-gear me-2 text-dark"></i> Quản lý tài khoản
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="lich-su-don-hang.php">
                                <i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i> Lịch sử đơn hàng
                            </a>
                        </li>
                        <?php if(isset($user_data['role']) && $user_data['role'] !== 'customer'): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger fw-bold" href="../admin/index-admin.php">
                                    <i class="fa-solid fa-gauge-high me-2"></i> Trang Quản Trị
                                </a>
                            </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" href="dang-xuat.php">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất
                            </a>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="dang-nhap.php" class="text-dark text-decoration-none me-3"><i class="fa-solid fa-user"></i> Đăng nhập</a>
                <a href="dang-ky.php" class="text-dark text-decoration-none"><i class="fa-solid fa-user-plus"></i> Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<header class="main-header bg-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-3 col-6">
                <a href="index.php" class="text-decoration-none">
                    <img src="../assets/images/logo.png" alt="NMK SHOP Logo" style="max-height: 50px; width: auto;">
                </a>
            </div>
            <div class="col-md-6 col-12 my-md-0 my-3">
                <form action="cua-hang.php" method="GET" class="search-box d-flex">
                    <input type="text" name="search" class="form-control" placeholder="Tìm kiếm đôi giày yêu thích của bạn..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button class="btn px-4" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
            <div class="col-md-3 col-6 text-end">
                <a href="gio-hang.php" class="d-inline-block text-decoration-none">
                    <div class="cart-icon me-2">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <span class="cart-badge"><?= $cart_count ?></span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</header>

<nav class="nav-menu navbar navbar-expand-lg navbar-light p-0 sticky-top">
    <div class="container">
        <button class="navbar-toggler my-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php">Trang Chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="cua-hang.php">Tất Cả Giày</a></li>
                <li class="nav-item"><a class="nav-link" href="cua-hang.php?category[]=1">Giày Thể Thao</a></li>
                <li class="nav-item"><a class="nav-link" href="cua-hang.php?category[]=2">Sneaker / Thời Trang</a></li>
                <li class="nav-item"><a class="nav-link" href="cua-hang.php?category[]=3">Giày Tây / Công Sở</a></li>
            </ul>
        </div>
    </div>
</nav>