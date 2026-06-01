<?php
// Bảo vệ tránh lỗi nếu file conn.php chưa được nhúng trước đó
if (!isset($conn)) {
    require_once '../includes/conn.php';
}

// Lấy danh sách danh mục và thương hiệu từ CSDL đổ ra bộ lọc
$sidebar_brands = $conn->query("SELECT * FROM brands")->fetchAll();
$sidebar_categories = $conn->query("SELECT * FROM categories")->fetchAll();

// Thu thập các trạng thái hiện tại từ thanh URL
$current_brands = isset($_GET['brand']) ? (array)$_GET['brand'] : [];
$current_categories = isset($_GET['category']) ? (array)$_GET['category'] : [];
$current_price = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$current_size = isset($_GET['size']) ? $_GET['size'] : '';
?>
<div class="sidebar-filter bg-white p-3 border rounded shadow-sm">
    <h5 class="fw-bold mb-4 pb-2 border-bottom text-uppercase text-dark" style="font-size: 15px; letter-spacing: 0.5px;">
        <i class="fa-solid fa-filter me-1 text-secondary"></i> Bộ lọc sản phẩm
    </h5>
    
    <form action="cua-hang.php" method="GET">
        <?php if(!empty($_GET['search'])): ?>
            <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
        <?php endif; ?>

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

        <div class="filter-group mb-4">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;">Khoảng Giá</h6>
            <div class="form-check mb-1">
                <input class="form-check-input" type="radio" name="price_range" value="0-1000000" id="pr1" <?= $current_price == '0-1000000' ? 'checked' : '' ?>>
                <label class="form-check-label small text-secondary" for="pr1">Dưới 1.000.000đ</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input" type="radio" name="price_range" value="1000000-2500000" id="pr2" <?= $current_price == '1000000-2500000' ? 'checked' : '' ?>>
                <label class="form-check-label small text-secondary" for="pr2">1.000.000đ - 2.500.000đ</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input" type="radio" name="price_range" value="2500000-5000000" id="pr3" <?= $current_price == '2500000-5000000' ? 'checked' : '' ?>>
                <label class="form-check-label small text-secondary" for="pr3">2.500.000đ - 5.000.000đ</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input" type="radio" name="price_range" value="5000000-999999999" id="pr4" <?= $current_price == '5000000-999999999' ? 'checked' : '' ?>>
                <label class="form-check-label small text-secondary" for="pr4">Trên 5.000.000đ</label>
            </div>
        </div>

        <div class="filter-group mb-4">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;">Chọn Size Giày</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php for($s = 38; $s <= 44; $s++): ?>
                    <input type="radio" class="btn-check" name="size" value="<?= $s ?>" id="size_<?= $s ?>" <?= $current_size == $s ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary btn-sm px-0 text-center" style="width: 40px; font-size: 12px;" for="size_<?= $s ?>"><?= $s ?></label>
                <?php endfor; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-dark btn-sm w-100 text-uppercase fw-bold mb-2 py-2" style="background-color: #111; font-size: 12px;">Áp dụng lọc</button>
        <a href="cua-hang.php" class="btn btn-outline-danger btn-sm w-100 text-uppercase fw-bold py-1 text-decoration-none" style="font-size: 11px;">Xóa bộ lọc</a>
    </form>
</div>