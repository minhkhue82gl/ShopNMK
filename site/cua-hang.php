<?php
// Nhúng cấu hình kết nối CSDL
require_once '../includes/conn.php';

// Khởi tạo câu lệnh SQL gốc
$sql = "SELECT DISTINCT p.* FROM products p 
        LEFT JOIN product_variants pv ON p.id = pv.product_id 
        WHERE p.status = 1";
$params = [];

// 1. Xử lý bộ lọc: Tìm kiếm theo từ khóa văn bản
if (!empty($_GET['search'])) {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}

// 2. Xử lý bộ lọc: Loại danh mục (Mảng checkbox)
if (!empty($_GET['category'])) {
    $cate_ids = (array)$_GET['category'];
    $in_query = implode(',', array_fill(0, count($cate_ids), '?'));
    $sql .= " AND p.category_id IN ($in_query)";
    $params = array_merge($params, $cate_ids);
}

// 3. Xử lý bộ lọc: Thương hiệu (Mảng checkbox)
if (!empty($_GET['brand'])) {
    $brand_ids = (array)$_GET['brand'];
    $in_query = implode(',', array_fill(0, count($brand_ids), '?'));
    $sql .= " AND p.brand_id IN ($in_query)";
    $params = array_merge($params, $brand_ids);
}

// 4. Xử lý bộ lọc: Khoảng giá bán (Radio button)
if (!empty($_GET['price_range'])) {
    list($min_price, $max_price) = explode('-', $_GET['price_range']);
    $sql .= " AND p.price BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
}

// 5. Xử lý bộ lọc: Kích thước Size giày (Radio button)
if (!empty($_GET['size'])) {
    $sql .= " AND pv.size = ?";
    $params[] = $_GET['size'];
}

// Thực thi câu truy vấn sau khi tổng hợp bộ lọc dữ liệu hoàn tất
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Nhúng Header giao diện vào đầu trang
include_once '../includes/header.php';
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Danh mục sản phẩm</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Cột trái chiếm 3 phần: Khu vực Bộ lọc Sidebar -->
        <div class="col-md-3">
            <?php include_once '../includes/sidebar.php'; ?>
        </div>

        <!-- Cột phải chiếm 9 phần: Danh sách sản phẩm thu về -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-2 border rounded">
                <span class="small text-muted">Tìm thấy <strong><?= count($products) ?></strong> sản phẩm phù hợp.</span>
            </div>

            <div class="row g-3">
                <?php if(count($products) > 0): ?>
                    <?php foreach($products as $prod): ?>
                        <div class="col-md-4 col-6">
                            <div class="card h-100 product-card shadow-sm border">
                                <!-- Hiển thị nhãn giảm giá nếu có giá cũ -->
                                <?php if($prod['old_price'] > 0): ?>
                                    <span class="badge bg-danger position-absolute m-2 top-0 start-0">Giảm giá</span>
                                <?php endif; ?>
                                
                                <img src="../assets/images/af1_white.jpg" class="card-img-top p-2" alt="<?= htmlspecialchars($prod['product_name']) ?>" style="object-fit: contain; max-height: 200px;">
                                
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <h6 class="card-title fw-bold text-dark text-truncate-2" style="font-size: 14px; height: 40px; overflow: hidden;">
                                        <?= htmlspecialchars($prod['product_name']) ?>
                                    </h6>
                                    <div class="price-group my-2">
                                        <span class="text-danger fw-bold fs-5"><?= number_format($prod['price'], 0, ',', '.') ?> đ</span>
                                        <?php if($prod['old_price'] > 0): ?>
                                            <br><small class="text-muted text-decoration-line-through"><?= number_format($prod['old_price'], 0, ',', '.') ?> đ</small>
                                        <?php endif; ?>
                                    </div>
                                    <a href="chi-tiet.php?id=<?= $prod['id'] ?>" class="btn btn-outline-dark btn-sm w-100 mt-2">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center my-5">
                        <i class="fa-solid fa-folder-open text-muted fs-1 mb-3"></i>
                        <p class="text-muted">Không tìm thấy đôi giày nào đáp ứng tiêu chí lọc của bạn.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
include_once '../includes/footer.php'; 
?>