<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

// Xử lý cập nhật ngưỡng cảnh báo tồn kho thấp phải đặt trước các lệnh HTML hoặc include
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_threshold'])) {
    $variant_id = (int)$_POST['variant_id'];
    $threshold  = max(1, (int)$_POST['threshold']);

    try {
        $stmt = $pdo->prepare("UPDATE product_variants SET low_stock_threshold = ? WHERE id = ?");
        $stmt->execute([$threshold, $variant_id]);
        $_SESSION['success'] = "Đã cập nhật ngưỡng cảnh báo thành công!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi cập nhật ngưỡng: " . $e->getMessage();
    }
    redirect(BASE_URL . 'admin/modules/kho-hang/index-khohang.php');
}

// Báo lọc: 'all' hoặc 'warning'
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';

// Sửa p.name thành p.product_name và b.name thành b.brand_name
$sql = "SELECT v.*, p.product_name AS product_name, p.image_url, b.brand_name AS brand_name 
        FROM product_variants v 
        JOIN products p ON v.product_id = p.id 
        LEFT JOIN brands b ON p.brand_id = b.id ";

if ($filter === 'warning') {
    $sql .= " WHERE v.stock <= v.low_stock_threshold ";
}

$sql .= " ORDER BY (v.stock <= v.low_stock_threshold) DESC, v.stock ASC";
$variants = $pdo->query($sql)->fetchAll();

// Đếm số biến thể đang cảnh báo
$warning_count = $pdo->query("SELECT COUNT(*) FROM product_variants WHERE stock <= low_stock_threshold")->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-boxes-stacked brand-orange me-2"></i> Quản Lý Tồn Kho & Cảnh Báo
        </h3>
        <p class="text-muted small m-0 mt-1">Theo dõi tồn kho theo từng biến thể Size/Màu và điều chỉnh ngưỡng cảnh báo hết hàng</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>admin/modules/kho-hang/lich-su-nhap.php" class="btn btn-outline-secondary fw-bold px-3 py-2 rounded-3">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> Lịch Sử Nhập Hàng
        </a>
        <a href="<?= BASE_URL ?>admin/modules/kho-hang/lap-phieu-nhap.php" class="btn btn-warning text-white fw-bold px-3 py-2 rounded-3 shadow-sm">
            <i class="fa-solid fa-file-circle-plus me-1"></i> Lập Phiếu Nhập Hàng
        </a>
    </div>
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

<?php if ($warning_count > 0 && $filter !== 'warning'): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center mb-4 rounded-3">
        <div>
            <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
            <strong>Cảnh báo:</strong> Có <strong><?= $warning_count ?></strong> biến thể giày sắp hết hoặc đã hết hàng trong kho!
        </div>
        <a href="<?= BASE_URL ?>admin/modules/kho-hang/index-khohang.php?filter=warning" class="btn btn-dark btn-sm fw-bold">
            Xem Sản Phẩm Sắp Hết
        </a>
    </div>
<?php endif; ?>

<!-- Bộ Lọc Trạng Thái -->
<div class="mb-3 d-flex gap-2">
    <a href="<?= BASE_URL ?>admin/modules/kho-hang/index-khohang.php?filter=all" 
       class="btn btn-sm <?= $filter === 'all' ? 'btn-dark fw-bold' : 'btn-light border' ?>">
        Tất Cả Biến Thể
    </a>
    <a href="<?= BASE_URL ?>admin/modules/kho-hang/index-khohang.php?filter=warning" 
       class="btn btn-sm <?= $filter === 'warning' ? 'btn-danger fw-bold' : 'btn-light border' ?>">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Sắp Hết Hàng (<= Ngưỡng)
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <?php if (empty($variants)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-box-open fs-1 mb-2 d-block"></i>
                Không tìm thấy dữ liệu tồn kho phù hợp.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-secondary small fw-bold">
                        <tr>
                            <th class="text-start ps-3">Mẫu Giày</th>
                            <th>Màu Sắc</th>
                            <th>Kích Cỡ</th>
                            <th>Số Lượng Tồn</th>
                            <th>Trạng Thái</th>
                            <th>Ngưỡng Cảnh Báo</th>
                            <th class="pe-3">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variants as $row): 
                            $is_warning = $row['stock'] <= $row['low_stock_threshold'];
                        ?>
                            <tr class="<?= $is_warning ? 'table-warning bg-opacity-10' : '' ?>">
                                <td class="text-start ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($row['image_url']) && file_exists(UPLOAD_DIR . 'products/' . $row['image_url'])): ?>
                                            <img src="<?= BASE_URL . 'assets/uploads/products/' . $row['image_url'] ?>" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold text-dark mb-0"><?= sanitize($row['product_name']) ?></div>
                                            <small class="text-muted"><?= sanitize($row['brand_name'] ?? 'NMK') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary"><?= sanitize($row['color']) ?></span></td>
                                <td><span class="badge bg-dark"><?= sanitize($row['size']) ?></span></td>
                                <td>
                                    <span class="fw-bold fs-6 <?= $is_warning ? 'text-danger' : 'text-success' ?>">
                                        <?= $row['stock'] ?> đôi
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['stock'] == 0): ?>
                                        <span class="badge bg-danger px-2.5 py-1.5"><i class="fa-solid fa-xmark me-1"></i>HẾT HÀNG</span>
                                    <?php elseif ($is_warning): ?>
                                        <span class="badge bg-warning text-dark px-2.5 py-1.5"><i class="fa-solid fa-triangle-exclamation me-1"></i>SẮP HẾT</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1.5"><i class="fa-solid fa-check me-1"></i>AN TOÀN</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="" method="POST" class="d-inline-flex align-items-center gap-1">
                                        <input type="hidden" name="variant_id" value="<?= $row['id'] ?>">
                                        <input type="number" name="threshold" class="form-control form-control-sm text-center" style="width: 65px;" value="<?= $row['low_stock_threshold'] ?>" min="1" required>
                                        <button type="submit" name="update_threshold" class="btn btn-outline-secondary btn-sm" title="Lưu ngưỡng">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="pe-3">
                                    <a href="<?= BASE_URL ?>admin/modules/kho-hang/lap-phieu-nhap.php?variant_id=<?= $row['id'] ?>" class="btn btn-warning btn-sm text-white fw-bold shadow-sm">
                                        <i class="fa-solid fa-plus me-1"></i> Nhập Kho
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>