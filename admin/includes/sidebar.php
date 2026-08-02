<?php

$current_uri = $_SERVER['REQUEST_URI'];
?>

<div class="col-md-3 col-lg-2 bg-white sidebar p-3 position-fixed">
    <div class="text-center mb-3 border-bottom pb-3">
        <h4 class="fw-bold m-0 text-dark">NMK<span class="brand-orange">ADMIN</span></h4>
        <small class="text-muted d-block mt-1">
            <i class="fa-solid fa-user-shield me-1 text-success"></i>
            <?= sanitize($_SESSION['user']['fullname'] ?? 'Quản trị viên') ?>
        </small>
    </div>
    
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, 'admin/index-admin.php') !== false || strpos($current_uri, 'admin/index.php') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/index-admin.php">
                <i class="fa-solid fa-chart-line"></i> Tổng quan
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/san-pham/') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/modules/san-pham/index-sanpham.php">
                <i class="fa-solid fa-shoe-prints"></i> Sản phẩm
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/danh-muc/') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/modules/danh-muc/index-danhmuc.php">
                <i class="fa-solid fa-layer-group"></i> Danh mục
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/thuong-hieu/') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/modules/thuong-hieu/index-thuonghieu.php">
                <i class="fa-solid fa-copyright"></i> Thương hiệu
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/kho-hang/') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/modules/kho-hang/index-khohang.php">
                <i class="fa-solid fa-warehouse"></i> Kho hàng
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/don-hang/') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/modules/don-hang/index-donhang.php">
                <i class="fa-solid fa-box-open"></i> Đơn hàng
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/khuyen-mai/') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/modules/khuyen-mai/index-khuyenmai.php">
                <i class="fa-solid fa-ticket"></i> Mã giảm giá
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/danh-gia/') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/modules/danh-gia/index-danhgia.php">
                <i class="fa-solid fa-star"></i> Đánh giá
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/nguoi-dung/') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/modules/nguoi-dung/index-nguoidung.php">
                <i class="fa-solid fa-users-gear"></i> Người dùng
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/thong-ke/') !== false) ? 'active-module' : '' ?>" 
               href="<?= BASE_URL ?>admin/modules/thong-ke/index-thongke.php">
                <i class="fa-solid fa-calculator"></i> Thống kê
            </a>
        </li>

        <li class="nav-item mt-3">
            <a class="nav-link text-danger border border-danger-subtle text-center d-block fw-bold" 
               href="<?= BASE_URL ?>admin/logout.php"
               onclick="return confirm('Bạn chắc chắn muốn đăng xuất khỏi hệ thống NMK Admin?')">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Đăng xuất
            </a>
        </li>
    </ul>
</div>

<div class="col-md-9 col-lg-10 offset-md-3 offset-lg-2 main-content">
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>