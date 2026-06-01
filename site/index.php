<?php
// 1. Nhúng file kết nối cơ sở dữ liệu
require_once '../includes/conn.php';

try {
    // 2. Query lấy ra 8 sản phẩm MỚI NHẤT (Sắp xếp theo ngày tạo giảm dần)
    $sql_new = "SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC LIMIT 8";
    $stmt_new = $conn->query($sql_new);
    $new_products = $stmt_new->fetchAll();

    // 3. Query lấy ra 8 sản phẩm GIẢM GIÁ (Sản phẩm có giá cũ 'old_price' cao hơn giá hiện tại)
    $sql_sale = "SELECT * FROM products WHERE status = 1 AND old_price > price ORDER BY id DESC LIMIT 8";
    $stmt_sale = $conn->query($sql_sale);
    $sale_products = $stmt_sale->fetchAll();

} catch (PDOException $e) {
    die("Lỗi tải dữ liệu trang chủ: " . $e->getMessage());
}


include_once '../includes/header.php';
?>

<div id="homeBanner" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#homeBanner" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#homeBanner" data-bs-slide-to="1"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=1200&h=450&fit=crop" class="d-block w-100" alt="Săn Giày Giá Tốt" style="object-fit: crop; max-height: 450px;">
            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded">
                <h2 class="fw-bold text-white">BÙNG NỔ PHONG CÁCH CÙNG NMK SHOES</h2>
                <p>Hệ thống phân phối giày Sneaker và giày chạy bộ chính hãng hàng đầu.</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?q=80&w=1200&h=450&fit=crop" class="d-block w-100" alt="Bộ Sưu Tập Mới" style="object-fit: crop; max-height: 450px;">
            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded">
                <h2 class="fw-bold text-white">BỘ SƯU TẬP MỚI NHẤT 2026</h2>
                <p>Cập nhật liên tục những xu hướng hot nhất từ Nike, Adidas, Puma...</p>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#homeBanner" data-bs-slide="slide" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#homeBanner" data-bs-slide="slide" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<div class="container my-5">
    <div class="row g-3 text-center">
        <div class="col-md-4">
            <div class="p-3 border rounded shadow-sm bg-white h-100">
                <i class="fa-solid fa-truck-fast text-warning fs-2 mb-2"></i>
                <h6 class="fw-bold text-dark m-0">GIAO HÀNG TOÀN QUỐC</h6>
                <small class="text-muted">Nhận hàng kiểm tra thử giày trước khi thanh toán</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded shadow-sm bg-white h-100">
                <i class="fa-solid fa-rotate text-warning fs-2 mb-2"></i>
                <h6 class="fw-bold text-dark m-0">ĐỔI TRẢ TRONG 7 NGÀY</h6>
                <small class="text-muted">Đổi size miễn phí nếu đi không vừa chân</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded shadow-sm bg-white h-100">
                <i class="fa-solid fa-shield-halved text-warning fs-2 mb-2"></i>
                <h6 class="fw-bold text-dark m-0">CAM KẾT CHÍNH HÃNG</h6>
                <small class="text-muted">Đền gấp 5 lần nếu phát hiện hàng giả hàng nhái</small>
            </div>
        </div>
    </div>
</div>

<div class="container">
    
    <div class="section-title d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h4 class="fw-bold text-dark text-uppercase m-0"><i class="fa-solid fa-fire text-danger me-2"></i>Sản phẩm mới về</h4>
        <a href="cua-hang.php" class="text-danger fw-bold small text-decoration-none">Xem tất cả <i class="fa-solid fa-angles-right"></i></a>
    </div>

    <div class="row g-3 mb-5">
        <?php if(count($new_products) > 0): ?>
            <?php foreach($new_products as $prod): ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="card h-100 shadow-sm border product-card position-relative bg-white">
                        <?php if($prod['old_price'] > 0): ?>
                            <span class="badge bg-danger position-absolute m-2 top-0 start-0 small" style="z-index: 5;">SALE</span>
                        <?php endif; ?>
                        
                        <div class="p-3 text-center">
                            <img src="../assets/images/af1_white.jpg" class="img-fluid" alt="<?= htmlspecialchars($prod['product_name']) ?>" style="max-height: 180px; object-fit: contain;" onerror="this.src='https://placehold.co/300x300?text=NMK+Shoes'">
                        </div>

                        <div class="card-body d-flex flex-column justify-content-between pt-0">
                            <h6 class="card-title fw-bold text-dark text-truncate-2 mb-2" style="font-size: 14px; height: 40px; overflow: hidden; line-height: 20px;">
                                <?= htmlspecialchars($prod['product_name']) ?>
                            </h6>
                            <div class="price-group">
                                <span class="text-danger fw-bold fs-5"><?= number_format($prod['price'], 0, ',', '.') ?> đ</span>
                                <?php if($prod['old_price'] > 0): ?>
                                    <span class="text-muted text-decoration-line-through small ms-2 d-block d-sm-inline"><?= number_format($prod['old_price'], 0, ',', '.') ?> đ</span>
                                <?php endif; ?>
                            </div>
                            <a href="chi-tiet.php?id=<?= $prod['id'] ?>" class="btn btn-dark btn-sm w-100 mt-3 fw-bold text-uppercase py-2" style="font-size: 12px; background-color: #111;">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><p class="text-muted text-center py-4">Đang cập nhật các mẫu giày mới...</p></div>
        <?php endif; ?>
    </div>


    <div class="section-title d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h4 class="fw-bold text-dark text-uppercase m-0"><i class="fa-solid fa-tags text-danger me-2"></i>Mẫu giày giá ưu đãi</h4>
        <a href="cua-hang.php?price_range=0-2500000" class="text-danger fw-bold small text-decoration-none">Xem thêm <i class="fa-solid fa-angles-right"></i></a>
    </div>

    <div class="row g-3 mb-5">
        <?php if(count($sale_products) > 0): ?>
            <?php foreach($sale_products as $prod): ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="card h-100 shadow-sm border product-card position-relative bg-white">
                        <span class="badge bg-danger position-absolute m-2 top-0 start-0 small" style="z-index: 5;">GIẢM GIÁ</span>
                        
                        <div class="p-3 text-center">
                            <img src="../assets/images/af1_white.jpg" class="img-fluid" alt="<?= htmlspecialchars($prod['product_name']) ?>" style="max-height: 180px; object-fit: contain;" onerror="this.src='https://placehold.co/300x300?text=NMK+Shoes'">
                        </div>

                        <div class="card-body d-flex flex-column justify-content-between pt-0">
                            <h6 class="card-title fw-bold text-dark text-truncate-2 mb-2" style="font-size: 14px; height: 40px; overflow: hidden; line-height: 20px;">
                                <?= htmlspecialchars($prod['product_name']) ?>
                            </h6>
                            <div class="price-group">
                                <span class="text-danger fw-bold fs-5"><?= number_format($prod['price'], 0, ',', '.') ?> đ</span>
                                <span class="text-muted text-decoration-line-through small ms-2 d-block d-sm-inline"><?= number_format($prod['old_price'], 0, ',', '.') ?> đ</span>
                            </div>
                            <a href="chi-tiet.php?id=<?= $prod['id'] ?>" class="btn btn-dark btn-sm w-100 mt-3 fw-bold text-uppercase py-2" style="font-size: 12px; background-color: #111;">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><p class="text-muted text-center py-4">Hiện tại không có chương trình giảm giá đặc biệt.</p></div>
        <?php endif; ?>
    </div>

</div>

<?php 
include_once '../includes/footer.php'; 
?>