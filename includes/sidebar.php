<?php
/**  @var PDO $conn */

if (!isset($conn)) {
    require_once '../includes/conn.php';
}

$sidebar_brands = $conn->query("SELECT * FROM brands")->fetchAll(PDO::FETCH_ASSOC);
$sidebar_categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

try {
    $sidebar_colors = $conn->query("SELECT DISTINCT color FROM product_variants WHERE color IS NOT NULL AND color != ''")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($sidebar_colors)) {
        $sidebar_colors = ['đen', 'trắng', 'đỏ', 'vàng', 'xám'];
    }
} catch (Exception $e) {
    $sidebar_colors = ['đen', 'trắng', 'đỏ', 'vàng', 'xám'];
}

$current_brands     = isset($_GET['brand']) ? (array)$_GET['brand'] : [];
$current_categories = isset($_GET['category']) ? (array)$_GET['category'] : [];
$current_price      = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$min_price          = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : '';
$max_price          = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : '';
$current_size       = isset($_GET['size']) ? $_GET['size'] : '';
$current_color      = isset($_GET['color']) ? $_GET['color'] : '';
?>

<div class="sidebar-filter bg-white p-3 border rounded shadow-sm">
    <h5 class="fw-bold mb-4 pb-2 border-bottom text-uppercase text-dark" style="font-size: 15px; letter-spacing: 0.5px;">
        <i class="fa-solid fa-filter me-1 text-secondary"></i> Bộ lọc sản phẩm
    </h5>
    
    <form action="cua-hang.php" method="GET" id="filterForm">
        <?php if(!empty($_GET['search'])): ?>
            <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
        <?php endif; ?>

        <!-- 1. LOẠI GIÀY -->
        <div class="filter-group mb-4">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;">Loại Giày</h6>
            <?php foreach($sidebar_categories as $cate): ?>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" name="category[]" value="<?= $cate['id'] ?>" id="cate_<?= $cate['id'] ?>" <?= in_array($cate['id'], $current_categories) ? 'checked' : '' ?>>
                    <label class="form-check-label small text-secondary" for="cate_<?= $cate['id'] ?>">
                        <?= htmlspecialchars($cate['category_name']) ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 2. THƯƠNG HIỆU -->
        <div class="filter-group mb-4">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;">Thương Hiệu</h6>
            <?php foreach($sidebar_brands as $br): ?>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" name="brand[]" value="<?= $br['id'] ?>" id="brand_<?= $br['id'] ?>" <?= in_array($br['id'], $current_brands) ? 'checked' : '' ?>>
                    <label class="form-check-label small text-secondary" for="brand_<?= $br['id'] ?>">
                        <?= htmlspecialchars($br['brand_name']) ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 3. KHOẢNG GIÁ -->
        <div class="filter-group mb-4">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;">Khoảng Giá</h6>
            
            <div class="form-check mb-1">
                <input class="form-check-input toggle-radio" type="radio" name="price_range" value="0-1000000" id="pr1" <?= $current_price == '0-1000000' ? 'checked' : '' ?>>
                <label class="form-check-label small text-secondary" for="pr1">Dưới 1.000.000đ</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input toggle-radio" type="radio" name="price_range" value="1000000-2500000" id="pr2" <?= $current_price == '1000000-2500000' ? 'checked' : '' ?>>
                <label class="form-check-label small text-secondary" for="pr2">1.000.000đ - 2.500.000đ</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input toggle-radio" type="radio" name="price_range" value="2500000-5000000" id="pr3" <?= $current_price == '2500000-5000000' ? 'checked' : '' ?>>
                <label class="form-check-label small text-secondary" for="pr3">2.500.000đ - 5.000.000đ</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input toggle-radio" type="radio" name="price_range" value="5000000-999999999" id="pr4" <?= $current_price == '5000000-999999999' ? 'checked' : '' ?>>
                <label class="form-check-label small text-secondary" for="pr4">Trên 5.000.000đ</label>
            </div>

            <div class="mt-3 pt-2 border-top">
                <label class="form-label small text-secondary fw-bold mb-2">Tùy chỉnh khoảng giá (VNĐ):</label>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" class="form-control form-control-sm px-2" name="min_price" placeholder="Từ" value="<?= htmlspecialchars($min_price) ?>" min="0" step="50000">
                    <span class="text-muted small">-</span>
                    <input type="number" class="form-control form-control-sm px-2" name="max_price" placeholder="Đến" value="<?= htmlspecialchars($max_price) ?>" min="0" step="50000">
                </div>
            </div>
        </div>

        <!-- 4. SIZE GIÀY -->
        <div class="filter-group mb-4">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;">Chọn Size Giày</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php for($s = 38; $s <= 44; $s++): ?>
                    <input type="radio" class="btn-check toggle-radio" name="size" value="<?= $s ?>" id="size_<?= $s ?>" <?= $current_size == $s ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary btn-sm px-0 text-center" style="width: 40px; font-size: 12px;" for="size_<?= $s ?>"><?= $s ?></label>
                <?php endfor; ?>
            </div>
        </div>

        <!-- 5. MÀU SẮC -->
        <div class="filter-group mb-4">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;">Màu Sắc</h6>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach($sidebar_colors as $clr): ?>
                    <div class="form-check me-3 mb-1">
                        <input class="form-check-input toggle-radio" type="radio" name="color" value="<?= htmlspecialchars($clr) ?>" id="clr_<?= htmlspecialchars($clr) ?>" <?= $current_color == $clr ? 'checked' : '' ?>>
                        <label class="form-check-label small text-secondary text-capitalize" for="clr_<?= htmlspecialchars($clr) ?>">
                            <?= htmlspecialchars($clr) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-dark btn-sm w-100 text-uppercase fw-bold mb-2 py-2" style="background-color: #111; font-size: 12px;">Áp dụng lọc</button>
        <a href="cua-hang.php" class="btn btn-outline-danger btn-sm w-100 text-uppercase fw-bold py-1 text-decoration-none" style="font-size: 11px;">Xóa bộ lọc</a>
    </form>
</div>

<!-- SCRIPT BỔ SUNG ĐỂ CHO PHÉP BỎ TÍCH CHỌN RADIO BUTTON -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radioButtons = document.querySelectorAll('.toggle-radio');

    radioButtons.forEach(radio => {
        radio.addEventListener('click', function () {
            // Nếu radio đã chọn từ trước thì kích hoạt bỏ tích (uncheck)
            if (this.dataset.wasChecked === 'true') {
                this.checked = false;
                this.dataset.wasChecked = 'false';
            } else {
                // Đặt lại thuộc tính wasChecked cho tất cả radio cùng name
                const name = this.getAttribute('name');
                document.querySelectorAll(`input[name="${name}"]`).forEach(r => r.dataset.wasChecked = 'false');
                this.dataset.wasChecked = 'true';
            }
        });

        // Khởi tạo trạng thái ban đầu cho các input được server check từ trước
        if (radio.checked) {
            radio.dataset.wasChecked = 'true';
        }
    });
});
</script>