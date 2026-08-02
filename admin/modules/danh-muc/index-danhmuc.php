<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $name = trim(sanitize($_POST['name'] ?? ''));
    $description = sanitize($_POST['description'] ?? '');

    if (!empty($name)) {
        try {
            if ($edit_id > 0) {
                $check_stmt = $pdo->prepare("SELECT id FROM categories WHERE category_name = ? AND id != ?");
                $check_stmt->execute([$name, $edit_id]);
                
                if ($check_stmt->fetch()) {
                    $_SESSION['error'] = "Tên danh mục '$name' đã tồn tại trong hệ thống!";
                } else {
                    $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $edit_id]);
                    $_SESSION['success'] = "Cập nhật danh mục thành công!";
                    redirect(BASE_URL . 'admin/modules/danh-muc/index-danhmuc.php');
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $_SESSION['success'] = "Thêm danh mục mới thành công!";
                redirect(BASE_URL . 'admin/modules/danh-muc/index-danhmuc.php');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $_SESSION['error'] = "Tên danh mục '$name' đã tồn tại!";
            } else {
                $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['error'] = "Tên danh mục không được để trống!";
    }
}

// 2. Xử lý logic GET (Xóa)
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$del_id]);
        $_SESSION['success'] = "Đã xóa danh mục thành công! (Sản phẩm thuộc danh mục này đã được đưa về trạng thái Chưa phân loại)";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Không thể xóa danh mục này do có lỗi dữ liệu!";
    }
    redirect(BASE_URL . 'admin/modules/danh-muc/index-danhmuc.php');
}

// Lấy dữ liệu danh mục cần sửa
if ($edit_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();

    if (!$edit_data) {
        $_SESSION['error'] = "Danh mục không tồn tại!";
        redirect(BASE_URL . 'admin/modules/danh-muc/index-danhmuc.php');
    }
}

// Lấy danh sách danh mục & thống kê số lượng sản phẩm liên kết
try {
    $categories = $pdo->query("SELECT c.*, COUNT(p.id) AS total_products 
                              FROM categories c 
                              LEFT JOIN products p ON c.id = p.category_id 
                              GROUP BY c.id 
                              ORDER BY c.id DESC")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// 3. Nhúng Header & Sidebar
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0 text-dark">
        <i class="fa-solid fa-layer-group brand-orange me-2"></i> Quản Lý Danh Mục Sản Phẩm
    </h3>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Form Thêm / Sửa Danh Mục -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 fw-bold border-0">
                <?= $edit_data ? '<i class="fa-solid fa-pen-to-square me-1 text-primary"></i> Cập Nhật Danh Mục' : '<i class="fa-solid fa-plus me-1 text-success"></i> Thêm Danh Mục Mới' ?>
            </div>
            <div class="card-body pt-0">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Giày Sneaker, Giày Chạy Bộ" value="<?= sanitize($edit_data['category_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Mô tả chi tiết</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Nhập mô tả ngắn về dòng sản phẩm..."><?= sanitize($edit_data['description'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="save_category" class="btn btn-warning text-white fw-bold w-100 py-2 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> <?= $edit_data ? 'Lưu Thay Đổi' : 'Thêm Danh Mục' ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="<?= BASE_URL ?>admin/modules/danh-muc/index-danhmuc.php" class="btn btn-light border w-100 mt-2">
                            <i class="fa-solid fa-xmark me-1"></i> Hủy chỉnh sửa
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh Sách Danh Mục -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center border-0">
                <span><i class="fa-solid fa-list me-1 text-muted"></i> Danh Sách Danh Mục Hiện Có</span>
                <span class="badge bg-secondary"><?= count($categories) ?> Danh mục</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small fw-bold text-secondary">
                            <tr>
                                <th width="70" class="text-center">ID</th>
                                <th>Tên Danh Mục</th>
                                <th>Mô Tả</th>
                                <th width="120" class="text-center">Sản Phẩm</th>
                                <th width="120" class="text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Chưa có danh mục nào được khởi tạo.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td class="text-center fw-bold">#<?= $cat['id'] ?></td>
                                        <td class="fw-semibold text-dark"><?= sanitize($cat['category_name']) ?></td>
                                        <td class="text-muted small"><?= sanitize($cat['description'] ?: 'Chưa có mô tả') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-20 px-3 py-1">
                                                <?= $cat['total_products'] ?> mẫu
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Sửa"><i class="fa-solid fa-pen"></i></a>
                                            <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa danh mục này? Sản phẩm thuộc danh mục sẽ chuyển sang Chưa phân loại.')"><i class="fa-solid fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>