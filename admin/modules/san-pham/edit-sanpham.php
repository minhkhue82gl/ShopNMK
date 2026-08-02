<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error'] = "Sản phẩm không tồn tại!";
    redirect(BASE_URL . 'admin/modules/san-pham/index-sanpham.php');
}

try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
    $brands     = $pdo->query("SELECT * FROM brands ORDER BY brand_name ASC")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Lỗi tải danh mục/thương hiệu: " . $e->getMessage();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_variant') {
    $variant_id = (int)($_GET['variant_id'] ?? 0);
    if ($variant_id > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM product_variants WHERE id = ? AND product_id = ?");
        $stmt_del->execute([$variant_id, $id]);
        $_SESSION['success'] = "Đã xóa biến thể thành công!";
    }
    redirect(BASE_URL . 'admin/modules/san-pham/edit-sanpham.php?id=' . $id);
}

$stmt_var = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
$stmt_var->execute([$id]);
$variants = $stmt_var->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_name = sanitize($_POST['name'] ?? '');
    $category_id  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $brand_id     = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $price        = (float)($_POST['price'] ?? 0);
    $old_price    = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $description  = sanitize($_POST['description'] ?? '');

    if (!empty($product_name) && $price > 0) {
        try {
            $pdo->beginTransaction();

            $image_url = $product['image_url'];
            
            // Xử lý upload ảnh mới
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $new_image = upload_image($_FILES['main_image'], 'products');
                if ($new_image) {
                    // Xóa ảnh cũ nếu tồn tại
                    $old_filename = basename($image_url);
                    $old_filepath = UPLOAD_DIR . 'products/' . $old_filename;
                    
                    if (!empty($old_filename) && file_exists($old_filepath)) {
                        @unlink($old_filepath);
                    }
                    $image_url = $new_image;
                }
            }

            $stmt_up = $pdo->prepare("UPDATE products SET product_name = ?, category_id = ?, brand_id = ?, price = ?, old_price = ?, image_url = ?, description = ? WHERE id = ?");
            $stmt_up->execute([$product_name, $category_id, $brand_id, $price, $old_price, $image_url, $description, $id]);

            if (isset($_POST['variant_id']) && is_array($_POST['variant_id'])) {
                $v_ids  = $_POST['variant_id'];
                $colors = $_POST['color'];
                $sizes  = $_POST['size'];
                $stocks = $_POST['stock'];

                for ($i = 0; $i < count($colors); $i++) {
                    $v_id  = (int)($v_ids[$i] ?? 0);
                    $color = sanitize($colors[$i] ?? '');
                    $size  = sanitize($sizes[$i] ?? '');
                    $stock = (int)($stocks[$i] ?? 0);

                    if (!empty($color) && !empty($size)) {
                        if ($v_id > 0) {
                            $stmt_v_up = $pdo->prepare("UPDATE product_variants SET size = ?, color = ?, stock = ? WHERE id = ? AND product_id = ?");
                            $stmt_v_up->execute([$size, $color, $stock, $v_id, $id]);
                        } else {
                            $stmt_v_in = $pdo->prepare("INSERT INTO product_variants (product_id, size, color, stock) VALUES (?, ?, ?, ?)");
                            $stmt_v_in->execute([$id, $size, $color, $stock]);
                        }
                    }
                }
            }

            $pdo->commit();
            $_SESSION['success'] = "Cập nhật sản phẩm thành công!";
            redirect(BASE_URL . 'admin/modules/san-pham/index-sanpham.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Lỗi cập nhật: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Vui lòng điền đầy đủ tên sản phẩm và giá hợp lệ!";
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0 text-dark"><i class="fa-solid fa-pen-to-square text-warning me-2"></i>Chỉnh Sửa Sản Phẩm #<?= $product['id'] ?></h3>
    <a href="<?= BASE_URL ?>admin/modules/san-pham/index-sanpham.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại danh sách
    </a>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form action="" method="POST" enctype="multipart/form-data">
    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm p-4 bg-white mb-4 rounded-3">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Thông Tin Cơ Bản</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= sanitize($product['product_name']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Thương hiệu</label>
                        <select name="brand_id" class="form-select">
                            <option value="">-- Chọn hãng --</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $b['id'] == $product['brand_id'] ? 'selected' : '' ?>><?= sanitize($b['brand_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Danh mục</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Chọn loại --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $product['category_id'] ? 'selected' : '' ?>><?= sanitize($c['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Giá bán (đ) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control" value="<?= $product['price'] ?>" required min="0" step="1000">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Giá cũ (đ)</label>
                        <input type="number" name="old_price" class="form-control" value="<?= $product['old_price'] ?>" min="0" step="1000">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Hình ảnh sản phẩm</label>
                    <?php 
                        $img_file = basename($product['image_url']);
                        $img_path = UPLOAD_DIR . 'products/' . $img_file;
                        if (!empty($img_file) && file_exists($img_path)): 
                    ?>
                        <div class="mb-2 d-flex align-items-center gap-3">
                            <img src="<?= BASE_URL . 'assets/uploads/products/' . $img_file ?>" class="rounded border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                            <span class="small text-muted">Ảnh hiện tại</span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="main_image" class="form-control" accept="image/*">
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-bold">Mô tả sản phẩm</label>
                    <textarea name="description" class="form-control" rows="4"><?= sanitize($product['description'] ?? '') ?></textarea>
                </div>
            </div>
            
            <div class="d-flex gap-2 mb-4">
                <button type="submit" name="update_product" class="btn btn-warning text-white w-100 py-2.5 fw-bold shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Thay Đổi
                </button>
                <a href="<?= BASE_URL ?>admin/modules/san-pham/index-sanpham.php" class="btn btn-light border w-50 py-2.5 fw-bold">Hủy</a>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-dark m-0"><i class="fa-solid fa-boxes-stacked text-warning me-2"></i>Biến Thể Size & Tồn Kho</h5>
                    <button type="button" class="btn btn-success btn-sm fw-bold" onclick="addVariant()">
                        <i class="fa-solid fa-plus me-1"></i> Thêm Biến Thể
                    </button>
                </div>

                <div id="variants-container">
                    <?php if (empty($variants)): ?>
                        <div class="text-center py-4 text-muted" id="no-variant-msg">
                            <i class="fa-solid fa-box-open fa-2x mb-2 d-block"></i>
                            Chưa có biến thể nào. Hãy bấm "Thêm Biến Thể" để tạo Size & Tồn kho!
                        </div>
                    <?php else: ?>
                        <?php foreach ($variants as $v): ?>
                            <div class="p-3 border rounded-3 mb-3 bg-light">
                                <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Màu sắc</label>
                                        <input type="text" name="color[]" class="form-control form-control-sm" value="<?= sanitize($v['color']) ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Size</label>
                                        <input type="text" name="size[]" class="form-control form-control-sm" value="<?= sanitize($v['size']) ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Tồn kho</label>
                                        <input type="number" name="stock[]" class="form-control form-control-sm" value="<?= $v['stock'] ?>" required min="0">
                                    </div>
                                    <div class="col-md-2">
                                        <a href="<?= BASE_URL ?>admin/modules/san-pham/edit-sanpham.php?id=<?= $id ?>&action=delete_variant&variant_id=<?= $v['id'] ?>" 
                                           class="btn btn-outline-danger btn-sm w-100" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa biến thể này?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let newCount = 0;
function addVariant() {
    newCount++;
    const container = document.getElementById('variants-container');
    const noMsg = document.getElementById('no-variant-msg');
    if (noMsg) noMsg.remove();

    const row = document.createElement('div');
    row.className = 'p-3 border rounded-3 mb-3 bg-light position-relative';
    row.id = `new-v-${newCount}`;
    row.innerHTML = `
        <input type="hidden" name="variant_id[]" value="0">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Màu sắc <span class="text-danger">*</span></label>
                <input type="text" name="color[]" class="form-control form-control-sm" placeholder="VD: Đen, Trắng" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Size <span class="text-danger">*</span></label>
                <input type="text" name="size[]" class="form-control form-control-sm" placeholder="VD: 40, 41" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Số lượng <span class="text-danger">*</span></label>
                <input type="number" name="stock[]" class="form-control form-control-sm" value="10" required min="0">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm w-100" onclick="document.getElementById('new-v-${newCount}').remove()">
                    <i class="fa-solid fa-xmark"></i> Hủy
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>