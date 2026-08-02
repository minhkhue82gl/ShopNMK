<?php
require_once '../includes/conn.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/** 
 * @var PDO $conn 
 */

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    header('Location: cua-hang.php');
    exit;
}

function getProductImageUrl($filename) {
    if (!empty($filename)) {
        if (filter_var($filename, FILTER_VALIDATE_URL)) {
            return $filename;
        }

        $relative_path = "assets/uploads/products/" . $filename;

        if (file_exists("../" . $relative_path)) {
            return "../" . $relative_path;
        }
        
        if (file_exists($relative_path)) {
            return $relative_path;
        }

        if (file_exists("../" . $filename)) {
            return "../" . $filename;
        }
        if (file_exists($filename)) {
            return $filename;
        }
    }

    return "https://placehold.co/500x500?text=NMK+Shop";
}

try {
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

    $sql_variants = "SELECT * FROM product_variants WHERE product_id = ?";
    $stmt_var = $conn->prepare($sql_variants);
    $stmt_var->execute([$product_id]);
    $variants = $stmt_var->fetchAll();

    $colors = array_unique(array_filter(array_column($variants, 'color')));
    $sizes = array_unique(array_filter(array_column($variants, 'size')));
    sort($sizes);

    $gallery_images = [];
    if (!empty($product['image'])) {
        $gallery_images[] = $product['image'];
    }
    if (!empty($product['image_url']) && !in_array($product['image_url'], $gallery_images)) {
        $gallery_images[] = $product['image_url'];
    }
    foreach ($variants as $v) {
        if (!empty($v['image']) && !in_array($v['image'], $gallery_images)) {
            $gallery_images[] = $v['image'];
        }
    }

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

include_once '../includes/header.php';
?>

<div class="container my-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-secondary">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="cua-hang.php" class="text-decoration-none text-secondary">Cửa hàng</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page"><?= htmlspecialchars($product['product_name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <div class="col-md-6">
            <div class="border rounded p-3 bg-white shadow-sm text-center mb-3">
                <?php 
                    $first_img = !empty($gallery_images) ? $gallery_images[0] : '';
                    $main_img_url = getProductImageUrl($first_img); 
                ?>
                <img src="<?= htmlspecialchars($main_img_url) ?>" 
                     id="largeProductImage" 
                     class="img-fluid" 
                     alt="<?= htmlspecialchars($product['product_name']) ?>" 
                     style="max-height: 400px; object-fit: contain;" 
                     onerror="this.src='https://placehold.co/500x500?text=NMK+Shop'">
            </div>

            <!-- Danh sách Thumbnail ảnh phụ -->
            <?php if (count($gallery_images) > 1): ?>
                <div class="d-flex gap-2 overflow-auto pb-2 justify-content-center">
                    <?php foreach ($gallery_images as $idx => $img_filename): ?>
                        <?php $thumb_url = getProductImageUrl($img_filename); ?>
                        <img src="<?= htmlspecialchars($thumb_url) ?>" 
                             class="img-thumbnail thumbnail-btn <?= $idx === 0 ? 'border-dark' : '' ?>" 
                             style="width: 75px; height: 75px; object-fit: cover; cursor: pointer;" 
                             onclick="changeMainImage(this, '<?= htmlspecialchars($thumb_url) ?>')" 
                             onerror="this.src='https://placehold.co/100x100?text=NMK'">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <span class="badge bg-secondary mb-2 text-uppercase"><?= htmlspecialchars($product['brand_name'] ?? 'NMK SHOP') ?></span>
            <h2 class="fw-bold text-dark mb-2"><?= htmlspecialchars($product['product_name']) ?></h2>
            
            <div class="mb-3 text-warning small d-flex align-items-center gap-1">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="<?= $i <= floor($avg_rating) ? 'fa-solid' : ($i - $avg_rating <= 0.5 && $i - $avg_rating > 0 ? 'fa-solid fa-star-half-stroke' : 'fa-regular') ?> fa-star"></i>
                <?php endfor; ?>
                <span class="text-dark fw-bold ms-2"><?= $avg_rating ?>/5</span>
                <span class="text-muted ms-1">(<?= count($reviews) ?> đánh giá)</span>
            </div>

            <div class="p-3 bg-light rounded mb-4 d-flex align-items-center">
                <span class="text-danger fw-bold fs-3 me-3"><?= number_format($product['price'], 0, ',', '.') ?> đ</span>
                <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                    <span class="text-muted text-decoration-line-through small"><?= number_format($product['old_price'], 0, ',', '.') ?> đ</span>
                <?php endif; ?>
            </div>

            <?php if (empty($variants)): ?>
                <div class="alert alert-danger fw-bold text-center text-uppercase p-3"><i class="fa-solid fa-circle-exclamation me-2"></i>Mẫu giày này hiện đang tạm hết hàng</div>
            <?php else: ?>
                <form action="gio-hang.php?action=add" method="POST" id="addToCartForm">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="variant_id" id="selectedVariantId" value="">

                    <?php if (!empty($colors)): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-secondary">1. Chọn Phối màu:</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach(array_values($colors) as $index => $color): ?>
                                    <input type="radio" class="btn-check variant-selector" name="selected_color" id="color_<?= $index ?>" value="<?= htmlspecialchars($color) ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                                    <label class="btn btn-outline-dark btn-sm px-3" for="color_<?= $index ?>"><?= htmlspecialchars($color) ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($sizes)): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-secondary">2. Chọn Kích cỡ Size vừa chân:</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach(array_values($sizes) as $index => $size): ?>
                                    <input type="radio" class="btn-check variant-selector" name="selected_size" id="size_<?= $index ?>" value="<?= $size ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                                    <label class="btn btn-outline-secondary btn-sm" style="width: 45px;" for="size_<?= $index ?>"><?= $size ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <span id="stockStatus" class="badge bg-success-subtle text-success border border-success p-2 small">
                            <i class="fa-solid fa-box-archive me-1"></i> Đang kiểm tra tồn kho...
                        </span>
                    </div>

                    <div class="mb-4 row align-items-center">
                        <div class="col-md-4 col-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Số lượng mua:</label>
                            <input type="number" id="inputQuantity" name="quantity" class="form-control" value="1" min="1" max="10" required>
                        </div>
                    </div>

                    <button type="submit" id="btnAddToCart" class="btn btn-dark btn-lg w-100 text-uppercase fw-bold py-3 mb-2" style="background-color: #111; font-size: 15px;">
                        <i class="fa-solid fa-cart-plus me-2"></i> Thêm vào giỏ hàng của bạn
                    </button>
                </form>
            <?php endif; ?>

            <div class="mt-4 border-top pt-3 small text-muted">
                <p class="m-0 mb-1"><i class="fa-solid fa-circle-check text-success me-1"></i> Danh mục: <strong><?= htmlspecialchars($product['category_name'] ?? 'Giày Sneaker') ?></strong></p>
                <p class="m-0"><i class="fa-solid fa-truck-fast text-primary me-1"></i> Miễn phí giao hàng cho đơn từ 1.000.000đ toàn quốc.</p>
            </div>
        </div>
    </div>

    <div class="card mt-5 border shadow-sm">
        <div class="card-header bg-white fw-bold text-uppercase py-3"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Mô tả sản phẩm chi tiết</div>
        <div class="card-body" style="line-height: 26px; color: #444;">
            <?= nl2br(htmlspecialchars($product['description'] ?? 'Đang cập nhật mô tả...')) ?>
        </div>
    </div>

    <div class="card mt-4 border shadow-sm mb-5">
        <div class="card-header bg-white fw-bold text-uppercase py-3 d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-comments me-2 text-warning"></i>Đánh giá từ khách hàng (<?= count($reviews) ?>)</span>
        </div>
        <div class="card-body">
            
            <?php if (isset($_SESSION['user'])): ?>
                <form action="review.php" method="POST" class="bg-light p-3 rounded mb-4 border border-light-subtle">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-pen-to-square me-1"></i>Gửi đánh giá trải nghiệm của bạn</h6>
                    
                    <div class="mb-3 row align-items-center">
                        <div class="col-auto">
                            <label class="small fw-bold text-secondary">Chọn mức điểm sao:</label>
                        </div>
                        <div class="col-auto">
                            <select name="rating" class="form-select form-select-sm" required>
                                <option value="5">⭐⭐⭐⭐⭐ 5 Sao (Rất hài lòng)</option>
                                <option value="4">⭐⭐⭐⭐ 4 Sao (Hài lòng/Tốt)</option>
                                <option value="3">⭐⭐⭐ 3 Sao (Bình thường)</option>
                                <option value="2">⭐⭐ 2 Sao (Không hài lòng)</option>
                                <option value="1">⭐ 1 Sao (Rất tệ)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="Viết cảm nhận của bạn về chất lượng da, form giày, độ êm chân... *" required></textarea>
                    </div>
                    <button type="submit" name="btn_submit_review" class="btn btn-dark btn-sm px-4 fw-bold text-uppercase" style="background-color: #111;">
                        <i class="fa-solid fa-paper-plane me-1"></i> Gửi đánh giá
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning p-2 small text-center mb-4">
                    Bạn phải <a href="dang-nhap.php" class="fw-bold text-dark text-decoration-none">Đăng nhập tài khoản</a> để gửi đánh giá cho sản phẩm này.
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
                            <p class="m-0 small text-secondary" style="font-size: 13px;">
                                "<?= htmlspecialchars($rev['comment']) ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center small py-3 m-0">Chưa có đánh giá nào cho mẫu giày này. Hãy là người đầu tiên trải nghiệm!</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
const productVariants = <?= json_encode($variants) ?>;

function changeMainImage(element, imageSrc) {
    document.getElementById('largeProductImage').src = imageSrc;
    document.querySelectorAll('.thumbnail-btn').forEach(btn => btn.classList.remove('border-dark'));
    element.classList.add('border-dark');
}

function updateStockCheck() {
    const selectedColorEl = document.querySelector('input[name="selected_color"]:checked');
    const selectedSizeEl = document.querySelector('input[name="selected_size"]:checked');
    
    const selectedColor = selectedColorEl ? selectedColorEl.value : null;
    const selectedSize = selectedSizeEl ? selectedSizeEl.value : null;

    const stockStatus = document.getElementById('stockStatus');
    const btnAddToCart = document.getElementById('btnAddToCart');
    const inputQuantity = document.getElementById('inputQuantity');
    const inputVariantId = document.getElementById('selectedVariantId');

    if (!selectedColor || !selectedSize) return;

    const matched = productVariants.find(v => 
        String(v.color).trim() === String(selectedColor).trim() && 
        String(v.size).trim() === String(selectedSize).trim()
    );

    if (matched) {
        inputVariantId.value = matched.id;
        const stock = parseInt(matched.stock);
        if (stock > 0) {
            stockStatus.className = "badge bg-success-subtle text-success border border-success p-2 small";
            stockStatus.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> Còn hàng trong kho (${stock} sản phẩm)`;
            btnAddToCart.disabled = false;
            inputQuantity.max = stock;
            if (parseInt(inputQuantity.value) > stock) inputQuantity.value = stock;
        } else {
            stockStatus.className = "badge bg-danger-subtle text-danger border border-danger p-2 small";
            stockStatus.innerHTML = `<i class="fa-solid fa-circle-xmark me-1"></i> Size & Màu này tạm thời đã HẾT HÀNG`;
            btnAddToCart.disabled = true;
        }
    } else {
        inputVariantId.value = "";
        stockStatus.className = "badge bg-secondary-subtle text-secondary border border-secondary p-2 small";
        stockStatus.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> Biến thể này không tồn tại`;
        btnAddToCart.disabled = true;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.variant-selector').forEach(input => {
        input.addEventListener('change', updateStockCheck);
    });
    updateStockCheck();
});
</script>

<?php include_once '../includes/footer.php'; ?>