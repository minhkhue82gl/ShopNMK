<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Xử lý logic cập nhật dữ liệu và chuyển hướng ĐẶT LÊN ĐẦU TIÊN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_coupon'])) {
    $discount_type       = sanitize($_POST['discount_type'] ?? 'percent');
    $discount_value      = (float)($_POST['discount_value'] ?? 0);
    $min_order_amount    = (float)($_POST['min_order_amount'] ?? 0);
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : NULL;
    $usage_limit         = (int)($_POST['usage_limit'] ?? 0);
    $start_date          = $_POST['start_date'] ?? '';
    $end_date            = $_POST['end_date'] ?? '';
    $status              = (int)($_POST['status'] ?? 1);

    if ($discount_value > 0 && !empty($start_date) && !empty($end_date)) {
        try {
            $sql = "UPDATE coupons SET discount_type = ?, discount_value = ?, min_order_amount = ?, max_discount_amount = ?, usage_limit = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?";
            $stmt_up = $pdo->prepare($sql);
            $stmt_up->execute([$discount_type, $discount_value, $min_order_amount, $max_discount_amount, $usage_limit, $start_date, $end_date, $status, $id]);

            $_SESSION['success'] = "Cập nhật mã giảm giá thành công!";
            redirect(BASE_URL . 'admin/modules/khuyen-mai/index-khuyenmai.php');
        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi cập nhật: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Vui lòng kiểm tra lại thông tin nhập vào!";
    }
}

// Lấy thông tin mã giảm giá để hiển thị form
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
$stmt->execute([$id]);
$coupon = $stmt->fetch();

if (!$coupon) {
    $_SESSION['error'] = "Mã giảm giá không tồn tại!";
    redirect(BASE_URL . 'admin/modules/khuyen-mai/index-khuyenmai.php');
}

// Sau khi xử lý xong điều hướng mới gọi giao diện
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="mb-4">
    <h3 class="fw-bold m-0 text-dark">
        <i class="fa-solid fa-pen-to-square brand-orange me-2"></i> Chỉnh Sửa Mã Giảm Giá: <span class="text-warning font-monospace"><?= sanitize($coupon['code']) ?></span>
    </h3>
    <p class="text-muted small m-0 mt-1">Cập nhật thông tin chi tiết và thời gian áp dụng của mã ưu đãi</p>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12 col-md-10 col-lg-8">
        <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
            <form action="" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Mã Khuyến Mãi (Mặc định)</label>
                        <input type="text" class="form-control text-uppercase font-monospace fw-bold bg-light" value="<?= sanitize($coupon['code']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Loại Giảm Giá</label>
                        <select name="discount_type" class="form-select">
                            <option value="percent" <?= $coupon['discount_type'] === 'percent' ? 'selected' : '' ?>>Giảm theo phần trăm (%)</option>
                            <option value="fixed" <?= $coupon['discount_type'] === 'fixed' ? 'selected' : '' ?>>Giảm số tiền cố định (VNĐ)</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Mức Giảm Giá <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="discount_value" class="form-control" value="<?= $coupon['discount_value'] ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Giảm Tối Đa (VNĐ)</label>
                        <input type="number" name="max_discount_amount" class="form-control" value="<?= $coupon['max_discount_amount'] ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Đơn Hàng Tối Thiểu (VNĐ)</label>
                        <input type="number" name="min_order_amount" class="form-control" value="<?= $coupon['min_order_amount'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Giới Hạn Lượt Sử Dụng</label>
                        <input type="number" name="usage_limit" class="form-control" value="<?= $coupon['usage_limit'] ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Thời Gian Bắt Đầu <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_date" class="form-control" value="<?= !empty($coupon['start_date']) ? date('Y-m-d\TH:i', strtotime($coupon['start_date'])) : date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Thời Gian Hết Hạn <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_date" class="form-control" value="<?= !empty($coupon['end_date']) ? date('Y-m-d\TH:i', strtotime($coupon['end_date'])) : date('Y-m-d\TH:i', strtotime('+30 days')) ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Trạng Thái Kích Hoạt</label>
                    <select name="status" class="form-select">
                        <option value="1" <?= $coupon['status'] == 1 ? 'selected' : '' ?>>Kích hoạt</option>
                        <option value="0" <?= $coupon['status'] == 0 ? 'selected' : '' ?>>Tắt / Khóa</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="update_coupon" class="btn btn-warning text-white w-100 py-2.5 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Cập Nhật
                    </button>
                    <a href="<?= BASE_URL ?>admin/modules/khuyen-mai/index-khuyenmai.php" class="btn btn-light border w-50 py-2.5 fw-bold text-center">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>