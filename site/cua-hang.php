<?php
require_once '../includes/conn.php';

/** @var PDO $conn */

$sql = "SELECT DISTINCT p.* FROM products p 
        LEFT JOIN product_variants pv ON p.id = pv.product_id 
        WHERE p.status = 1";
$params = [];

if (!empty($_GET['search'])) {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = "%" . trim($_GET['search']) . "%";
}

if (!empty($_GET['category'])) {
    $cate_ids = (array)$_GET['category'];
    $in_query = implode(',', array_fill(0, count($cate_ids), '?'));
    $sql .= " AND p.category_id IN ($in_query)";
    $params = array_merge($params, $cate_ids);
}

if (!empty($_GET['brand'])) {
    $brand_ids = (array)$_GET['brand'];
    $in_query = implode(',', array_fill(0, count($brand_ids), '?'));
    $sql .= " AND p.brand_id IN ($in_query)";
    $params = array_merge($params, $brand_ids);
}

if (!empty($_GET['price_range'])) {
    list($min_r, $max_r) = explode('-', $_GET['price_range']);
    $sql .= " AND p.price >= ? AND p.price <= ?";
    $params[] = floatval($min_r);
    $params[] = floatval($max_r);
}

if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
    $sql .= " AND p.price >= ?";
    $params[] = floatval($_GET['min_price']);
}
if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
    $sql .= " AND p.price <= ?";
    $params[] = floatval($_GET['max_price']);
}

if (!empty($_GET['size'])) {
    $sql .= " AND pv.size = ?";
    $params[] = $_GET['size'];
}

if (!empty($_GET['color'])) {
    $sql .= " AND pv.color = ?";
    $params[] = $_GET['color'];
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/header.php';

function getProductImageUrl($prod) {
    $img_filename = !empty($prod['image_url']) ? $prod['image_url'] : (!empty($prod['image']) ? $prod['image'] : '');

    if (!empty($img_filename)) {
        if (filter_var($img_filename, FILTER_VALIDATE_URL)) {
            return $img_filename;
        }
        $relative_path = "assets/uploads/products/" . $img_filename;
        if (file_exists("../" . $relative_path)) return "../" . $relative_path;
        if (file_exists($relative_path)) return $relative_path;
        if (file_exists("../" . $img_filename)) return "../" . $img_filename;
        if (file_exists($img_filename)) return $img_filename;
    }
    return "https://placehold.co/300x300?text=NMK+Shop";
}
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-secondary">Trang chủ</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Cửa hàng & Tìm kiếm</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-3 col-md-4">
            <?php 
                if (file_exists('../includes/sidebar.php')) {
                    include_once '../includes/sidebar.php';
                } else if (file_exists('../includes/navbar.php')) {
                    include_once '../includes/navbar.php';
                }
            ?>
        </div>

        <div class="col-lg-9 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 border rounded shadow-sm">
                <span class="small text-secondary">
                    <?php if(!empty($_GET['search'])): ?>
                        Kết quả cho từ khóa: "<strong class="text-dark"><?= htmlspecialchars($_GET['search']) ?></strong>" — 
                    <?php endif; ?>
                    Tìm thấy <strong class="text-danger fs-6"><?= count($products) ?></strong> sản phẩm phù hợp.
                </span>
            </div>

            <div class="row g-3">
                <?php if(count($products) > 0): ?>
                    <?php foreach($products as $prod): ?>
                        <?php $img_url = getProductImageUrl($prod); ?>
                        <div class="col-lg-4 col-md-6 col-6">
                            <div class="card h-100 product-card shadow-sm border position-relative bg-white">
                                <div class="p-3 text-center">
                                    <img src="<?= htmlspecialchars($img_url) ?>" class="img-fluid" alt="<?= htmlspecialchars($prod['product_name']) ?>" style="max-height: 180px; object-fit: contain;">
                                </div>
                                <div class="card-body d-flex flex-column justify-content-between pt-0">
                                    <h6 class="card-title fw-bold text-dark text-truncate-2 mb-2" style="font-size: 14px; height: 40px; overflow: hidden;">
                                        <?= htmlspecialchars($prod['product_name']) ?>
                                    </h6>
                                    <div class="price-group my-2">
                                        <span class="text-danger fw-bold fs-5"><?= number_format($prod['price'], 0, ',', '.') ?> đ</span>
                                    </div>
                                    <a href="chi-tiet.php?id=<?= $prod['id'] ?>" class="btn btn-dark btn-sm w-100 mt-2 fw-bold text-uppercase py-2" style="font-size: 12px; background-color: #111;">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center my-5 py-5 bg-white border rounded shadow-sm">
                        <i class="fa-solid fa-folder-open text-muted display-4 mb-3"></i>
                        <h5 class="fw-bold text-secondary">Không tìm thấy sản phẩm phù hợp</h5>
                        <a href="cua-hang.php" class="btn btn-dark btn-sm fw-bold px-4 py-2 mt-2" style="background-color: #111;">XÓA BỘ LỌC</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>