<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_data = null;

// Xử lý LƯU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_brand'])) {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    if (!empty($name)) {
        if ($edit_id > 0) {
            $stmt = $pdo->prepare("UPDATE brands SET brand_name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $edit_id]);
            $_SESSION['success'] = "Cập nhật thương hiệu thành công!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO brands (brand_name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $_SESSION['success'] = "Thêm thương hiệu mới thành công!";
        }
        redirect(BASE_URL . 'admin/modules/thuong-hieu/index-thuonghieu.php');
    } else {
        $_SESSION['error'] = "Tên thương hiệu không được để trống!";
    }
}

// Xử lý XÓA
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
        $stmt->execute([$del_id]);
        $_SESSION['success'] = "Đã xóa thương hiệu!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Không thể xóa thương hiệu này do đã có sản phẩm liên kết!";
    }
    redirect(BASE_URL . 'admin/modules/thuong-hieu/index-thuonghieu.php');
}

if ($edit_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}

$brands = $pdo->query("SELECT b.*, COUNT(p.id) AS product_count 
                       FROM brands b 
                       LEFT JOIN products p ON b.id = p.brand_id 
                       GROUP BY b.id 
                       ORDER BY b.id DESC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0 text-dark"><i class="fa-solid fa-copyright brand-orange me-2"></i>Quản Lý Thương Hiệu</h3>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 fw-bold">
                <?= $edit_data ? '<i class="fa-solid fa-pen-to-square me-1"></i> Cập Nhật Thương Hiệu' : '<i class="fa-solid fa-plus me-1"></i> Thêm Thương Hiệu Mới' ?>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên thương hiệu <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Nike, Adidas, Puma, Jordan" value="<?= sanitize($edit_data['brand_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả thương hiệu</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Giới thiệu thương hiệu..."><?= sanitize($edit_data['description'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="save_brand" class="btn btn-warning text-white fw-bold w-100">
                        <i class="fa-solid fa-floppy-disk me-1"></i> <?= $edit_data ? 'Lưu Thay Đổi' : 'Thêm Thương Hiệu' ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="<?= BASE_URL ?>admin/modules/thuong-hieu/index-thuonghieu.php" class="btn btn-light w-100 mt-2">Hủy chỉnh sửa</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Bảng Danh Sách -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="fa-solid fa-list me-1"></i> Danh Sách Thương Hiệu
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="60">ID</th>
                                <th>Tên Thương Hiệu</th>
                                <th>Mô Tả</th>
                                <th width="120" class="text-center">Số Sản Phẩm</th>
                                <th width="120" class="text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($brands)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Chưa có thương hiệu nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($brands as $b): ?>
                                    <tr>
                                        <td><strong>#<?= $b['id'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= sanitize($b['brand_name']) ?></td>
                                        <td class="text-muted small"><?= sanitize($b['description'] ?: 'Không có mô tả') ?></td>
                                        <td class="text-center"><span class="badge bg-info text-dark"><?= $b['product_count'] ?></span></td>
                                        <td class="text-center">
                                            <a href="?edit=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Sửa"><i class="fa-solid fa-pen"></i></a>
                                            <a href="?delete=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" title="Xóa" onclick="return confirm('Xóa thương hiệu này?')"><i class="fa-solid fa-trash"></i></a>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>