<?php
require_once '../includes/conn.php';

/** 
 * @var PDO $conn 
 */

try {
    $sql_new = "SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC LIMIT 8";
    $stmt_new = $conn->query($sql_new);
    $new_products = $stmt_new->fetchAll();

    $sql_bestseller = "
        SELECT p.*, COALESCE(SUM(od.quantity), 0) AS total_sold
        FROM products p
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        LEFT JOIN order_details od ON pv.id = od.variant_id
        LEFT JOIN orders o ON od.order_id = o.id AND o.status = 'Đã giao'
        WHERE p.status = 1
        GROUP BY p.id
        ORDER BY total_sold DESC, p.id DESC
        LIMIT 8
    ";
    $stmt_bestseller = $conn->query($sql_bestseller);
    $bestseller_products = $stmt_bestseller->fetchAll();

    $sql_sale = "SELECT * FROM products WHERE status = 1 AND old_price > price ORDER BY id DESC LIMIT 8";
    $stmt_sale = $conn->query($sql_sale);
    $sale_products = $stmt_sale->fetchAll();

} catch (PDOException $e) {
    die("Lỗi tải dữ liệu trang chủ: " . $e->getMessage());
}

include_once '../includes/header.php';


function getProductImageUrl($prod) {
    $img_filename = !empty($prod['image_url']) ? $prod['image_url'] : (!empty($prod['image']) ? $prod['image'] : '');

    if (!empty($img_filename)) {
        if (filter_var($img_filename, FILTER_VALIDATE_URL)) {
            return $img_filename;
        }

        $upload_dir = "assets/uploads/products/";

        if (file_exists($upload_dir . $img_filename)) {
            return $upload_dir . $img_filename;
        }

        if (file_exists("../" . $upload_dir . $img_filename)) {
            return "../" . $upload_dir . $img_filename;
        }

        if (file_exists($img_filename)) {
            return $img_filename;
        }
        if (file_exists("../" . $img_filename)) {
            return "../" . $img_filename;
        }
    }

    // Ảnh mặc định
    return "https://placehold.co/300x300?text=NMK+Shop";
}
?>

<div id="homeBanner" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#homeBanner" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#homeBanner" data-bs-slide-to="1"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="../assets/images/1.jpg" class="d-block w-100" alt="Săn Giày Giá Tốt" style="object-fit: cover; max-height: 450px;">
            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3">
                <h2 class="fw-bold text-white text-uppercase">Bùng nổ phong cách cùng NMK SHOP</h2>
                <p class="m-0">Hệ thống phân phối giày Sneaker và giày chạy bộ chính hãng hàng đầu Việt Nam.</p>
            </div>
        </div>
        <div class="carousel-item">
<img src="../assets/images/2.jpg" class="d-block w-100" alt="Bộ Sưu Tập Mới" style="object-fit: cover; max-height: 450px;">            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3">
                <h2 class="fw-bold text-white text-uppercase">Bộ sưu tập mới nhất 2026</h2>
                <p class="m-0">Cập nhật liên tục những xu hướng hot nhất từ Nike, Adidas, Biti's...</p>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#homeBanner" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#homeBanner" data-bs-slide-next">
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
                <?php $img_url = getProductImageUrl($prod); ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="card h-100 shadow-sm border product-card position-relative bg-white">
                        <span class="badge bg-success position-absolute m-2 top-0 start-0 small" style="z-index: 5;">NEW</span>
                        <?php if(!empty($prod['old_price']) && $prod['old_price'] > $prod['price']): ?>
                            <span class="badge bg-danger position-absolute m-2 top-0 end-0 small" style="z-index: 5;">SALE</span>
                        <?php endif; ?>
                        
                        <div class="p-3 text-center">
                            <img src="<?= htmlspecialchars($img_url) ?>" class="img-fluid" alt="<?= htmlspecialchars($prod['product_name']) ?>" style="max-height: 180px; object-fit: contain;" onerror="this.src='https://placehold.co/300x300?text=NMK+Shop'">
                        </div>

                        <div class="card-body d-flex flex-column justify-content-between pt-0">
                            <h6 class="card-title fw-bold text-dark text-truncate-2 mb-2" style="font-size: 14px; height: 40px; overflow: hidden; line-height: 20px;">
                                <?= htmlspecialchars($prod['product_name']) ?>
                            </h6>
                            <div class="price-group">
                                <span class="text-danger fw-bold fs-5"><?= number_format($prod['price'], 0, ',', '.') ?> đ</span>
                                <?php if(!empty($prod['old_price']) && $prod['old_price'] > $prod['price']): ?>
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
        <h4 class="fw-bold text-dark text-uppercase m-0"><i class="fa-solid fa-crown text-warning me-2"></i>Sản phẩm bán chạy nhất</h4>
        <a href="cua-hang.php" class="text-danger fw-bold small text-decoration-none">Xem tất cả <i class="fa-solid fa-angles-right"></i></a>
    </div>

    <div class="row g-3 mb-5">
        <?php if(count($bestseller_products) > 0): ?>
            <?php foreach($bestseller_products as $prod): ?>
                <?php $img_url = getProductImageUrl($prod); ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="card h-100 shadow-sm border product-card position-relative bg-white">
                        <span class="badge bg-warning text-dark position-absolute m-2 top-0 start-0 small" style="z-index: 5;"><i class="fa-solid fa-star me-1"></i>HOT</span>
                        
                        <div class="p-3 text-center">
                            <img src="<?= htmlspecialchars($img_url) ?>" class="img-fluid" alt="<?= htmlspecialchars($prod['product_name']) ?>" style="max-height: 180px; object-fit: contain;" onerror="this.src='https://placehold.co/300x300?text=NMK+Shop'">
                        </div>

                        <div class="card-body d-flex flex-column justify-content-between pt-0">
                            <h6 class="card-title fw-bold text-dark text-truncate-2 mb-2" style="font-size: 14px; height: 40px; overflow: hidden; line-height: 20px;">
                                <?= htmlspecialchars($prod['product_name']) ?>
                            </h6>
                            <div class="price-group">
                                <span class="text-danger fw-bold fs-5"><?= number_format($prod['price'], 0, ',', '.') ?> đ</span>
                                <?php if(!empty($prod['old_price']) && $prod['old_price'] > $prod['price']): ?>
                                    <span class="text-muted text-decoration-line-through small ms-2 d-block d-sm-inline"><?= number_format($prod['old_price'], 0, ',', '.') ?> đ</span>
                                <?php endif; ?>
                            </div>
                            <a href="chi-tiet.php?id=<?= $prod['id'] ?>" class="btn btn-dark btn-sm w-100 mt-3 fw-bold text-uppercase py-2" style="font-size: 12px; background-color: #111;">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><p class="text-muted text-center py-4">Đang cập nhật danh sách sản phẩm bán chạy...</p></div>
        <?php endif; ?>
    </div>

    <div class="section-title d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h4 class="fw-bold text-dark text-uppercase m-0"><i class="fa-solid fa-tags text-danger me-2"></i>Mẫu giày giá ưu đãi</h4>
        <a href="cua-hang.php?price_range=0-2500000" class="text-danger fw-bold small text-decoration-none">Xem thêm <i class="fa-solid fa-angles-right"></i></a>
    </div>

    <div class="row g-3 mb-5">
        <?php if(count($sale_products) > 0): ?>
            <?php foreach($sale_products as $prod): ?>
                <?php $img_url = getProductImageUrl($prod); ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="card h-100 shadow-sm border product-card position-relative bg-white">
                        <span class="badge bg-danger position-absolute m-2 top-0 start-0 small" style="z-index: 5;">GIẢM GIÁ</span>
                        
                        <div class="p-3 text-center">
                            <img src="<?= htmlspecialchars($img_url) ?>" class="img-fluid" alt="<?= htmlspecialchars($prod['product_name']) ?>" style="max-height: 180px; object-fit: contain;" onerror="this.src='https://placehold.co/300x300?text=NMK+Sho'">
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