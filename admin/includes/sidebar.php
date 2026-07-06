<?php
/**
 * THUẬT TOÁN TỰ ĐỘNG NHẬN DIỆN MODULE HIỆN TẠI (ACTIVE MENU LINK)
 * Đoạn mã này lấy tên thư mục hoặc tên file đang chạy trên trình duyệt,
 * mục đích để so sánh và tự động gán class 'active-module' giúp làm sáng 
 * mục menu tương ứng trên Sidebar, tăng trải nghiệm người dùng Admin.
 */
$current_uri = $_SERVER['REQUEST_URI'];

// Xác định base_url lùi cấp tương tự như file header.php
$base_url = (strpos($current_uri, '/modules/') !== false) ? '../../' : '';
?>

<div class="col-md-3 col-lg-2 bg-white sidebar p-3 position-fixed">
    
    <div class="text-center mb-4 border-bottom pb-3">
        <h4 class="fw-bold m-0 text-dark">NMK<span style="color: #ff5722;">ADMIN</span></h4>
        <small class="text-muted d-block mt-1">
            <i class="fa-solid fa-user-shield me-1 text-success"></i>
            <?= htmlspecialchars($_SESSION['user']['fullname']) ?>
        </small>
    </div>
    
    <ul class="nav flex-column">
        
        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, 'admin/index.php') !== false || $current_uri == '/admin/') ? 'active-module' : '' ?>" 
               href="<?= $base_url ?>index.php">
                <i class="fa-solid fa-chart-line"></i> Tổng quan
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/san-pham/') !== false) ? 'active-module' : '' ?>" 
               href="<?= $base_url ?>modules/san-pham/index.php">
                <i class="fa-solid fa-shoe-prints"></i> Sản phẩm
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/kho-hang/') !== false) ? 'active-module' : '' ?>" 
               href="<?= $base_url ?>modules/kho-hang/index.php">
                <i class="fa-solid fa-warehouse"></i> Kho hàng
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/don-hang/') !== false) ? 'active-module' : '' ?>" 
               href="<?= $base_url ?>modules/don-hang/index.php">
                <i class="fa-solid fa-box-open"></i> Đơn hàng
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/nguoi-dung/') !== false) ? 'active-module' : '' ?>" 
               href="<?= $base_url ?>modules/nguoi-dung/index.php">
                <i class="fa-solid fa-users-gear"></i> Người dùng
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?= (strpos($current_uri, '/modules/thong-ke/') !== false) ? 'active-module' : '' ?>" 
               href="<?= $base_url ?>modules/thong-ke/index.php">
                <i class="fa-solid fa-calculator"></i> Thống kê
            </a>
        </li>
        
        <li class="nav-item mt-4">
            <a class="nav-link text-danger border border-danger-subtle text-center d-block fw-bold" 
               href="<?= $base_url ?>../site/dang-xuat.php"
               onclick="return confirm('Bạn chắc chắn muốn đăng xuất khỏi hệ thống NMK Admin?')">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Đăng xuất
            </a>
        </li>
        
    </ul>
</div>

<div class="col-md-9 col-lg-10 offset-md-3 offset-lg-2 main-content">