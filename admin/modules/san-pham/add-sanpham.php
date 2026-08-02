<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $brands     = $pdo->query("SELECT * FROM brands ORDER BY brand_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = sanitize($_POST['name'] ?? '');
    $category_id  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $brand_id     = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $price        = (float)($_POST['price'] ?? 0);
    $old_price    = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $description  = sanitize($_POST['description'] ?? '');

    if ($price > 999999999.99 || ($old_price !== null && $old_price > 999999999.99)) {
        $_SESSION['error'] = "Giá sản phẩm quá lớn (Tối đa 999,999,999 đ)!";
    } elseif (!empty($product_name) && $price > 0) {
        try {
            $pdo->beginTransaction();

            $main_image_name = "";
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $uploaded_filename = upload_image($_FILES['main_image'], 'products');
                if ($uploaded_filename) {
                    $main_image_name = $uploaded_filename;
                } else {
                    throw new Exception("File ảnh không hợp lệ hoặc lỗi phân quyền thư mục!");
                }
            } else { 
                throw new Exception("Vui lòng chọn ảnh đại diện sản phẩm!");
            }

            $sql_prod = "INSERT INTO products (product_name, category_id, brand_id, price, old_price, image_url, description) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_prod = $pdo->prepare($sql_prod);
            $stmt_prod->execute([$product_name, $category_id, $brand_id, $price, $old_price, $main_image_name, $description]);
            
            $product_id = $pdo->lastInsertId();

            if (isset($_POST['color']) && is_array($_POST['color'])) {
                $colors = $_POST['color'];
                $sizes  = $_POST['size'];
                $stocks = $_POST['stock'];

                $sql_var = "INSERT INTO product_variants (product_id, size, color, stock) VALUES (?, ?, ?, ?)";
                $stmt_var = $pdo->prepare($sql_var);

                $added_combinations = [];

                for ($i = 0; $i < count($colors); $i++) {
                    $color = trim(sanitize($colors[$i] ?? ''));
                    $size  = (int)($sizes[$i] ?? 0);
                    $stock = (int)($stocks[$i] ?? 0);

                    if (!empty($color) && $size > 0) {
                        $combo_key = mb_strtolower($color) . '_' . $size;
                        if (!in_array($combo_key, $added_combinations)) {
                            $stmt_var->execute([$product_id, $size, $color, $stock]);
                            $added_combinations[] = $combo_key;
                        }
                    }
                }
            }

            $pdo->commit();
            $_SESSION['success'] = "Thêm sản phẩm thành công!";
            redirect(BASE_URL . 'admin/modules/san-pham/index-sanpham.php');
            exit();
            
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Vui lòng nhập Tên sản phẩm và Giá hợp lệ!";
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0 text-dark"><i class="fa-solid fa-plus text-warning me-2"></i>Thêm Giày Mới</h3>
    <a href="<?= BASE_URL ?>admin/modules/san-pham/index-sanpham.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form action="" method="POST" enctype="multipart/form-data">
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Thông Tin Sản Phẩm</h5>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="Ví dụ: Giày Nike Air Force 1">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Danh mục</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= sanitize($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Thương hiệu</label>
                        <select name="brand_id" class="form-select">
                            <option value="">-- Chọn thương hiệu --</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= sanitize($b['brand_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control" required step="1000" placeholder="VD: 1500000">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Giá cũ (VNĐ)</label>
                        <input type="number" name="old_price" class="form-control" step="1000" placeholder="VD: 2000000">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Hình ảnh đại diện <span class="text-danger">*</span></label>
                    <input type="file" name="main_image" class="form-control" accept="image/*" required>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-bold">Mô tả sản phẩm</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả ngắn..."></textarea>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-dark m-0"><i class="fa-solid fa-boxes-stacked text-warning me-2"></i>Biến Thể Size & Tồn Kho</h5>
                    <button type="button" class="btn btn-success btn-sm fw-bold" onclick="addVariantRow()">
                        <i class="fa-solid fa-plus me-1"></i> Thêm Dòng
                    </button>
                </div>
                
                <div id="variant-container">
                    <div class="p-3 border rounded-3 mb-3 bg-light variant-item">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary mb-1">Màu sắc</label>
                                <input type="text" name="color[]" class="form-control form-control-sm" placeholder="VD: Đen" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Size</label>
                                <input type="number" name="size[]" class="form-control form-control-sm" placeholder="VD: 42" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Số lượng</label>
                                <input type="number" name="stock[]" class="form-control form-control-sm" value="10" required min="0">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.variant-item').remove()">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="add_product" class="btn btn-warning text-white fw-bold w-100 py-2 mt-3 shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Sản Phẩm
                </button>
            </div>
        </div>
    </div>
</form>

<script>
function addVariantRow() {
    const container = document.getElementById('variant-container');
    const div = document.createElement('div');
    div.className = 'p-3 border rounded-3 mb-3 bg-light variant-item';
    div.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="color[]" class="form-control form-control-sm" placeholder="VD: Trắng" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="size[]" class="form-control form-control-sm" placeholder="VD: 40" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="stock[]" class="form-control form-control-sm" value="10" required min="0">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.variant-item').remove()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>