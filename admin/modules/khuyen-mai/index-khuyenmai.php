<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

// Xử lý bật/tắt kích hoạt mã giảm giá phải đặt trước các lệnh HTML hoặc include
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $id = (int)($_GET['id'] ?? 0);
    $status = (int)($_GET['status'] ?? 1);
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE coupons SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $_SESSION['success'] = "Cập nhật trạng thái mã giảm giá thành công!";
    }
    redirect(BASE_URL . 'admin/modules/khuyen-mai/index-khuyenmai.php');
}

// Lấy danh sách Voucher
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY id DESC");
$coupons = $stmt->fetchAll();
$current_time = date('Y-m-d H:i:s');

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-ticket brand-orange me-2"></i> Quản Lý Mã Giảm Giá (Voucher)
        </h3>
        <p class="text-muted small m-0 mt-1">Tạo và quản lý các chương trình ưu đãi, mã giảm giá cho khách hàng</p>
    </div>
    <a href="<?= BASE_URL ?>admin/modules/khuyen-mai/add-khuyenmai.php" class="btn btn-warning text-white fw-bold px-3 py-2 rounded-3 shadow-sm">
        <i class="fa-solid fa-plus me-1"></i> Tạo Mã Giảm Giá Mới
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <?php if (empty($coupons)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-ticket-simple fs-1 mb-2 d-block"></i>
                Chưa có mã giảm giá nào được tạo.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-secondary small fw-bold">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th class="text-start ps-3">Mã Voucher</th>
                            <th>Mức Giảm</th>
                            <th>Đơn Tối Thiểu</th>
                            <th>Lượt Dùng</th>
                            <th>Thời Gian Áp Dụng</th>
                            <th>Trạng Thái</th>
                            <th class="pe-3">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $row): 
                            $end_date = $row['end_date'] ?? date('Y-m-d H:i:s');
                            $start_date = $row['start_date'] ?? date('Y-m-d H:i:s');
                            $max_discount = $row['max_discount_amount'] ?? 0;

                            $is_expired = ($current_time > $end_date);
                            $is_out_of_limit = ($row['usage_limit'] > 0 && $row['used_count'] >= $row['usage_limit']);
                        ?>
                            <tr>
                                <td class="font-monospace text-secondary">#<?= $row['id'] ?></td>
                                <td class="text-start ps-3">
                                    <span class="badge bg-warning text-dark font-monospace fs-6 px-2.5 py-1.5 border border-warning">
                                        <?= sanitize($row['code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['discount_type'] === 'percent'): ?>
                                        <span class="fw-bold text-danger"><?= (float)$row['discount_value'] ?>%</span>
                                        <?php if ($max_discount > 0): ?>
                                            <div class="small text-muted">(Tối đa <?= format_money($max_discount) ?>)</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="fw-bold text-danger"><?= format_money($row['discount_value']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small fw-semibold"><?= format_money($row['min_order_amount']) ?></td>
                                <td class="small">
                                    <span class="fw-bold"><?= $row['used_count'] ?></span> / 
                                    <span><?= $row['usage_limit'] > 0 ? $row['usage_limit'] : '∞' ?></span>
                                </td>
                                <td class="small text-muted">
                                    <div>Từ: <?= date('d/m/Y H:i', strtotime($start_date)) ?></div>
                                    <div>Đến: <?= date('d/m/Y H:i', strtotime($end_date)) ?></div>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 0): ?>
                                        <span class="badge bg-secondary">Tắt Kích Hoạt</span>
                                    <?php elseif ($is_expired): ?>
                                        <span class="badge bg-danger">Hết Hạn</span>
                                    <?php elseif ($is_out_of_limit): ?>
                                        <span class="badge bg-dark">Hết Lượt Dùng</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">
                                            <i class="fa-solid fa-circle-check me-1"></i>Đang Khả Dụng
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3">
                                    <div class="btn-group shadow-sm" role="group">
                                        <a href="<?= BASE_URL ?>admin/modules/khuyen-mai/edit-khuyenmai.php?id=<?= $row['id'] ?>" 
                                           class="btn btn-outline-dark btn-sm fw-bold" title="Chỉnh sửa">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <?php if ($row['status'] == 1): ?>
                                            <a href="<?= BASE_URL ?>admin/modules/khuyen-mai/index-khuyenmai.php?action=toggle_status&id=<?= $row['id'] ?>&status=0" 
                                               class="btn btn-outline-danger btn-sm" title="Tắt mã"
                                               onclick="return confirm('Bạn có muốn tạm tắt mã khuyến mãi này?')">
                                                <i class="fa-solid fa-eye-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>admin/modules/khuyen-mai/index-khuyenmai.php?action=toggle_status&id=<?= $row['id'] ?>&status=1" 
                                               class="btn btn-outline-success btn-sm" title="Bật mã">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
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