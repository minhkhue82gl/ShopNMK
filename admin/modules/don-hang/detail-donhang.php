<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Đơn hàng không tồn tại!";
    redirect(BASE_URL . 'admin/modules/don-hang/index-donhang.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitize($_POST['status'] ?? '');
    $old_status = $order['status'];

    $valid_statuses = ['Chờ xác nhận', 'Đang xử lý', 'Đang giao', 'Đã giao', 'Đã hủy'];

    if (in_array($new_status, $valid_statuses) && $new_status !== $old_status) {
        try {
            $pdo->beginTransaction();

            $stmt_items = $pdo->prepare("SELECT variant_id, quantity FROM order_details WHERE order_id = ? AND variant_id IS NOT NULL");
            $stmt_items->execute([$order_id]);
            $items = $stmt_items->fetchAll();

            if ($new_status === 'Đã hủy' && $old_status !== 'Đã hủy') {
                $stmt_restore = $pdo->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?");
                foreach ($items as $item) {
                    $stmt_restore->execute([$item['quantity'], $item['variant_id']]);
                }
            }
            
            if ($old_status === 'Đã hủy' && $new_status !== 'Đã hủy') {
                $stmt_deduct = $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");
                foreach ($items as $item) {
                    $stmt_deduct->execute([$item['quantity'], $item['variant_id']]);
                }
            }

            $stmt_up = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt_up->execute([$new_status, $order_id]);

            $pdo->commit();
            $_SESSION['success'] = "Cập nhật trạng thái đơn hàng thành công!";
            redirect(BASE_URL . "admin/modules/don-hang/detail-donhang.php?id={$order_id}");

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Lỗi cập nhật đơn hàng: " . $e->getMessage();
        }
    }
}

$sql_details = "SELECT od.*, v.size, v.color, p.product_name, p.image_url 
                FROM order_details od 
                LEFT JOIN product_variants v ON od.variant_id = v.id 
                LEFT JOIN products p ON v.product_id = p.id 
                WHERE od.order_id = ?";
$stmt_dt = $pdo->prepare($sql_details);
$stmt_dt->execute([$order_id]);
$details = $stmt_dt->fetchAll();

$status_badges = [
    'Chờ xác nhận' => 'bg-warning text-dark',
    'Đang xử lý'   => 'bg-info text-white',
    'Đang giao'     => 'bg-primary text-white',
    'Đã giao'       => 'bg-success text-white',
    'Đã hủy'        => 'bg-danger text-white'
];

$current_status = !empty($order['status']) ? $order['status'] : 'Chờ xác nhận';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">
            Chi Tiết Đơn Hàng: <span class="text-warning font-monospace">#NMK-<?= $order['id'] ?></span>
        </h3>
        <p class="text-muted small m-0 mt-1">Ngày đặt: <?= date('d/m/Y H:i:s', strtotime($order['created_at'])) ?></p>
    </div>
    <a href="index-donhang.php" class="btn btn-outline-secondary fw-bold px-3 py-2 rounded-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay Lại
    </a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm bg-white rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold text-dark m-0">Danh Sách Giày Khách Đặt</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 text-center">
                        <thead class="table-light text-secondary small fw-bold">
                            <tr>
                                <th class="text-start ps-3">Sản Phẩm</th>
                                <th>Đơn Giá</th>
                                <th>Số Lượng</th>
                                <th class="pe-3 text-end">Thành Tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($details as $row): 
                                $row_total = $row['price'] * $row['quantity'];
                                $subtotal += $row_total;
                            ?>
                                <tr>
                                    <td class="text-start ps-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($row['image_url'])): ?>
                                                <img src="<?= BASE_URL . 'assets/uploads/products/' . $row['image_url'] ?>" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark"><?= sanitize($row['product_name'] ?? 'Sản phẩm không xác định') ?></div>
                                                <div class="small text-muted">
                                                    Size: <span class="badge bg-dark"><?= sanitize($row['size'] ?? 'N/A') ?></span> | 
                                                    Màu: <span class="badge bg-secondary"><?= sanitize($row['color'] ?? 'N/A') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= format_money($row['price']) ?></td>
                                    <td class="fw-bold">x<?= $row['quantity'] ?></td>
                                    <td class="pe-3 text-end fw-bold text-dark"><?= format_money($row_total) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-light border-top">
                    <?php if (!empty($order['coupon_code'])): ?>
                        <div class="d-flex justify-content-between mb-1 small text-danger">
                            <span>Mã giảm giá đã dùng:</span>
                            <span class="fw-bold"><?= sanitize($order['coupon_code']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-dark border-top pt-2 mt-1">
                        <span>Tổng Thanh Toán:</span>
                        <span class="text-danger"><?= format_money($order['total_price']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm bg-white rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold text-dark m-0">Xử Lý Đơn Hàng</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Trạng thái hiện tại</label>
                        <div>
                            <span class="badge <?= $status_badges[$current_status] ?? 'bg-secondary' ?> px-3 py-2 fs-6 fw-semibold">
                                <?= $current_status ?>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Chuyển trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="Chờ xác nhận" <?= $current_status === 'Chờ xác nhận' ? 'selected' : '' ?>>Chờ xác nhận</option>
                            <option value="Đang xử lý" <?= $current_status === 'Đang xử lý' ? 'selected' : '' ?>>Đang xử lý</option>
                            <option value="Đang giao" <?= $current_status === 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
                            <option value="Đã giao" <?= $current_status === 'Đã giao' ? 'selected' : '' ?>>Đã giao</option>
                            <option value="Đã hủy" <?= $current_status === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>

                    <button type="submit" name="update_status" class="btn btn-warning text-white w-100 py-2.5 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Cập Nhật Trạng Thái
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-white rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold text-dark m-0">Thông Tin Khách Hàng</h5>
            </div>
            <div class="card-body small">
                <div class="mb-2">
                    <span class="text-muted d-block">Họ và tên:</span>
                    <strong class="text-dark fs-6"><?= sanitize($order['fullname']) ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Số điện thoại:</span>
                    <strong class="text-dark"><?= sanitize($order['phone']) ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Email:</span>
                    <span class="text-dark"><?= sanitize($order['email']) ?></span>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Địa chỉ nhận hàng:</span>
                    <span class="text-dark fw-semibold"><?= sanitize($order['address']) ?></span>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Hình thức thanh toán:</span>
                    <span class="badge bg-dark"><?= strtoupper(sanitize($order['payment_method'])) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>