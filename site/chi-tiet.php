<?php
// 1. Nhúng file kết nối cơ sở dữ liệu và khởi động Session an toàn
require_once '../includes/conn.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Lấy mã sản phẩm từ URL, nếu không có thì đẩy về trang cửa hàng
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    header('Location: cua-hang.php');
    exit;
}

try {
    // 2. Lấy thông tin chi tiết của sản phẩm, danh mục và thương hiệu
    $sql_prod = "SELECT p.*, c.category_name, b.brand_name 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN brands b ON p.brand_id = b.id
                 WHERE p.id = ? AND p.status = 1";
    $stmt_prod = $conn->prepare($sql_prod);
    $stmt_prod->execute([$product_id]);
    $product = $stmt_prod->fetch();

    if (!$product) {
        die("<div class='container my-5 alert alert-danger'>Sản phẩm giày này không tồn tại hoặc đã bị ngừng kinh doanh!</div>");
    }

    // 3. Lấy danh sách các biến thể Màu sắc và Size còn hàng (stock > 0) từ kho
    $sql_variants = "SELECT * FROM product_variants WHERE product_id = ? AND stock > 0";
    $stmt_var = $conn->prepare($sql_variants);
    $stmt_var->execute([$product_id]);
    $variants = $stmt_var->fetchAll();

    // Gom mảng danh sách Màu và Size độc nhất để hiển thị lên các nút giao diện
    $colors = array_unique(array_column($variants, 'color'));
    $sizes = array_unique(array_column($variants, 'size'));
    sort($sizes); // Sắp xếp size từ nhỏ đến lớn (38 -> 44)

    // 4. Lấy danh sách đánh giá và phản hồi của khách hàng cho sản phẩm này
    $sql_reviews = "SELECT r.*, u.fullname FROM reviews r 
                    JOIN users u ON r.user_id = u.id 
                    WHERE r.product_id = ? 
                    ORDER BY r.created_at DESC";
    $stmt_rev = $conn->prepare($sql_reviews);
    $stmt_rev->execute([$product_id]);
    $reviews = $stmt_rev->fetchAll();

    // Tính điểm sao trung bình
    $avg_rating = 0;
    if (count($reviews) > 0) {
        $avg_rating = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
    }

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

// Nhúng thanh Header dùng chung
include_once '../includes/header.php';
?>

<div class="container my-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-secondary">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="cua-hang.php" class="text-decoration-none text-secondary">Cửa hàng</a></li>
            <li class="breadcrumb-item active text-dark" aria-current="page"><?= htmlspecialchars($product['product_name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <div class="col-md-6 text-center">
            <div class="border rounded p-3 bg-white shadow-sm">
                <?php 
                    $main_image = (!empty($variants) && !empty($variants[0]['image'])) ? $variants[0]['image'] : 'default_shoe.jpg';
                ?>
                <img src="../assets/uploads/<?= $main_image ?>" id="largeProductImage" class="img-fluid" alt="<?= htmlspecialchars($product['product_name']) ?>" style="max-height: 400px; object-fit: contain;" onerror="this.src='https://placehold.co/500x500?text=NMK+Shoes'">
            </div>
        </div>

        <div class="col-md-6">
            <span class="badge bg-secondary mb-2 text-uppercase"><?= htmlspecialchars($product['brand_name']) ?></span>
            <h2 class="fw-bold text-dark mb-2"><?= htmlspecialchars($product['product_name']) ?></h2>
            
            <div class="mb-3 text-warning small">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="<?= $i <= $avg_rating ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                <?php endfor; ?>
                <span class="text-muted ms-2">(<?= count($reviews) ?> bình luận từ khách hàng)</span>
            </div>

            <div class="p-3 bg-light rounded mb-4 d-flex align-items-center">
                <span class="text-danger fw-bold fs-3 me-3"><?= number_format($product['price'], 0, ',', '.') ?> đ</span>
                <?php if ($product['old_price'] > 0): ?>
                    <span class="text-muted text-decoration-line-through small"><?= number_format($product['old_price'], 0, ',', '.') ?> đ</span>
                <?php endif; ?>
            </div>

            <?php if (empty($variants)): ?>
                <div class="alert alert-danger fw-bold text-center text-uppercase">Sản phẩm tạm thời hết hàng</div>
            <?php else: ?>
                <form action="gio-hang.php?action=add" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Phối màu có sẵn:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach($colors as $index => $color): ?>
                                <input type="radio" class="btn-check" name="selected_color" id="color_<?= $index ?>" value="<?= htmlspecialchars($color) ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                                <label class="btn btn-outline-dark btn-sm px-3" for="color_<?= $index ?>"><?= htmlspecialchars($color) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Kích cỡ (Size vừa chân):</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach($sizes as $index => $size): ?>
                                <input type="radio" class="btn-check" name="selected_size" id="size_<?= $index ?>" value="<?= $size ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                                <label class="btn btn-outline-secondary btn-sm style-size-box" style="width: 45px;" for="size_<?= $index ?>"><?= $size ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4 row align-items-center">
                        <div class="col-md-4 col-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Số lượng mua:</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" max="10" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark btn-lg w-100 text-uppercase fw-bold py-3 mb-2" style="background-color: #111; font-size: 15px;">
                        <i class="fa-solid fa-cart-plus me-2"></i> Thêm vào giỏ hàng của bạn
                    </button>
                </form>
            <?php endif; ?>

            <div class="mt-4 border-top pt-3 small text-muted">
                <p class="m-0 mb-1"><i class="fa-solid fa-circle-check text-success me-1"></i> Danh mục: <strong><?= htmlspecialchars($product['category_name']) ?></strong></p>
                <p class="m-0"><i class="fa-solid fa-truck text-primary me-1"></i> Giao hàng toàn quốc từ 2 - 4 ngày làm việc.</p>
            </div>
        </div>
    </div>

    <div class="card mt-5 border shadow-sm">
        <div class="card-header bg-white fw-bold text-uppercase py-3">Mô tả sản phẩm chi tiết</div>
        <div class="card-body" style="line-height: 26px; color: #444;">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
        </div>
    </div>

    <div class="card mt-4 border shadow-sm mb-5">
        <div class="card-header bg-white fw-bold text-uppercase py-3 d-flex justify-content-between align-items-center">
            <span>Đánh giá từ khách hàng (<?= count($reviews) ?>)</span>
        </div>
        <div class="card-body">
            
            <?php if (isset($_SESSION['user'])): ?>
                <form action="review.php" method="POST" class="bg-light p-3 rounded mb-4 border border-light-subtle">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <h6 class="fw-bold mb-3 text-dark">Gửi đánh giá trải nghiệm của bạn</h6>
                    
                    <div class="mb-2 row align-items-center">
                        <div class="col-auto">
                            <label class="small fw-bold text-secondary">Chọn mức điểm sao:</label>
                        </div>
                        <div class="col-auto">
                            <select name="rating" class="form-select form-select-sm" required>
                                <option value="5">⭐⭐⭐⭐⭐ 5 Sao (Rất tốt)</option>
                                <option value="4">⭐⭐⭐⭐ 4 Sao (Tốt)</option>
                                <option value="3">⭐⭐⭐ 3 Sao (Bình thường)</option>
                                <option value="2">⭐⭐ 2 Sao (Kém)</option>
                                <option value="1">⭐ 1 Sao (Rất tệ)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="Viết cảm nhận của bạn về chất lượng da, form giày, đế đi êm chân không... *" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold text-uppercase">Gửi bình luận</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning p-2 small text-center mb-4">
                    Bạn phải <a href="dang-nhap.php" class="fw-bold text-dark text-decoration-none">Đăng nhập tài khoản</a> để gửi bình luận chấm điểm sao cho sản phẩm này.
                </div>
            <?php endif; ?>

            <div class="review-list">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach($reviews as $rev): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-dark small"><i class="fa-solid fa-circle-user me-1 text-secondary"></i> <?= htmlspecialchars($rev['fullname']) ?></strong>
                                <span class="text-muted" style="font-size: 11px;"><?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div class="text-warning small mb-1" style="font-size: 11px;">
                                <?php for($s = 1; $s <= 5; $s++): ?>
                                    <i class="<?= $s <= $rev['rating'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="m-0 small text-secondary" style="font-size: 13px; font-style: italic;">
                                "<?= htmlspecialchars($rev['comment']) ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center small py-3 m-0">Chưa có bình luận nào cho mẫu giày này. Hãy là người đầu tiên trải nghiệm!</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php 
include_once '../includes/footer.php'; 
?>
