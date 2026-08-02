<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

// 1. Xử lý logic PHP và redirect ĐẶT LÊN ĐẦU TIÊN trước khi include bất kỳ file HTML nào
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code                = strtoupper(trim(sanitize($_POST['code'] ?? '')));
    $discount_type       = sanitize($_POST['discount_type'] ?? 'percent');
    $discount_value      = (float)($_POST['discount_value'] ?? 0);
    $min_order_amount    = (float)($_POST['min_order_amount'] ?? 0);
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : NULL;
    $usage_limit         = (int)($_POST['usage_limit'] ?? 0);
    $start_date          = $_POST['start_date'] ?? '';
    $end_date            = $_POST['end_date'] ?? '';

    if (!empty($code) && $discount_value > 0 && !empty($start_date) && !empty($end_date)) {
        try {
            // Kiểm tra trùng mã
            $stmt_check = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt_check->execute([$code]);

            if ($stmt_check->fetch()) {
                $_SESSION['error'] = "Mã giảm giá '{$code}' đã tồn tại trên hệ thống!";
            } else {
                $sql = "INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, start_date, end_date, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $max_discount_amount, $usage_limit, $start_date, $end_date]);

                $_SESSION['success'] = "Tạo mã giảm giá '{$code}' thành công!";
                redirect(BASE_URL . 'admin/modules/khuyen-mai/index-khuyenmai.php');
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi lưu dữ liệu: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ Mã, Mức giảm giá và Thời gian áp dụng!";
    }
}

// 2. Sau khi xử lý xong các lệnh chuyển hướng mới gọi giao diện
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="mb-4">
    <h3 class="fw-bold m-0 text-dark">
        <i class="fa-solid fa-ticket brand-orange me-2"></i> Tạo Mã Giảm Giá Mới
    </h3>
    <p class="text-muted small m-0 mt-1">Thêm chương trình khuyến mãi và mã ưu đãi mới cho khách hàng</p>
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
                        <label class="form-label small fw-bold">Mã Khuyến Mãi (Code) <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase font-monospace fw-bold" placeholder="VD: NMK2026" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Loại Giảm Giá</label>
                        <select name="discount_type" class="form-select">
                            <option value="percent">Giảm theo phần trăm (%)</option>
                            <option value="fixed">Giảm số tiền cố định (VNĐ)</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Mức Giảm Giá <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="discount_value" class="form-control" placeholder="VD: 10 (nếu là %) hoặc 50000 (nếu là VNĐ)" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Giảm Tối Đa (VNĐ)</label>
                        <input type="number" name="max_discount_amount" class="form-control" placeholder="Bỏ trống nếu không giới hạn">
                        <small class="text-muted d-block mt-1">Chỉ áp dụng khi chọn giảm theo %</small>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Đơn Hàng Tối Thiểu (VNĐ)</label>
                        <input type="number" name="min_order_amount" class="form-control" value="0" placeholder="VD: 500000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Giới Hạn Lượt Sử Dụng</label>
                        <input type="number" name="usage_limit" class="form-control" value="0" placeholder="0 = Không giới hạn">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Thời Gian Bắt Đầu <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Thời Gian Hết Hạn <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+30 days')) ?>" required>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="add_coupon" class="btn btn-warning text-white w-100 py-2.5 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Mã Giảm Giá
                    </button>
                    <a href="<?= BASE_URL ?>admin/modules/khuyen-mai/index-khuyenmai.php" class="btn btn-light border w-50 py-2.5 fw-bold text-center">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>