<?php
require_once '../includes/conn.php';

/** @var PDO $conn */ 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để xem lịch sử đơn hàng.";
    header('Location: dang-nhap.php');
    exit;
}

$user_id     = $_SESSION['user']['id'];
$success_msg = '';
$error_msg   = '';

if (isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);

    try {
        $conn->beginTransaction();

        $stmt_chk = $conn->prepare("SELECT status, coupon_code FROM orders WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt_chk->execute([$order_id, $user_id]);
        $order = $stmt_chk->fetch();

        if ($order && $order['status'] === 'Chờ xác nhận') {
            $stmt_cancel = $conn->prepare("UPDATE orders SET status = 'Đã hủy' WHERE id = ? AND user_id = ?");
            $stmt_cancel->execute([$order_id, $user_id]);

            $stmt_details = $conn->prepare("SELECT variant_id, quantity FROM order_details WHERE order_id = ?");
            $stmt_details->execute([$order_id]);
            $items = $stmt_details->fetchAll();

            foreach ($items as $item) {
                if (!empty($item['variant_id'])) {
                    $stmt_restock = $conn->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?");
                    $stmt_restock->execute([$item['quantity'], $item['variant_id']]);
                }
            }

            if (!empty($order['coupon_code'])) {
                $stmt_restore_cp = $conn->prepare("UPDATE coupons SET used_count = GREATEST(0, used_count - 1) WHERE code = ?");
                $stmt_restore_cp->execute([$order['coupon_code']]);
            }

            $conn->commit();
            $success_msg = "Hủy đơn hàng #NMK-$order_id thành công và đã hoàn trả lại kho sản phẩm!";
        } else {
            $conn->rollBack();
            $error_msg = "Không thể hủy đơn hàng do đơn đã được Shop xử lý, đang giao hoặc không tồn tại!";
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $error_msg = "Lỗi hệ thống: " . $e->getMessage();
    }
}

try {
    $sql_orders  = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->execute([$user_id]);
    $orders      = $stmt_orders->fetchAll();

} catch (PDOException $e) {
    die("Lỗi truy vấn dữ liệu: " . $e->getMessage());
}

include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-uppercase m-0 text-dark">
            <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Lịch sử mua hàng
        </h4>
        <span class="badge bg-dark px-3 py-2 small">Tài khoản: <?= htmlspecialchars($_SESSION['user']['fullname']) ?></span>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
            <i class="fa-solid fa-circle-xmark me-2"></i><?= htmlspecialchars($error_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5 border rounded bg-white shadow-sm">
            <i class="fa-solid fa-receipt text-muted mb-3" style="font-size: 60px;"></i>
            <h5 class="text-secondary fw-bold">Bạn chưa có đơn hàng nào!</h5>
            <p class="text-muted small">Hãy chọn cho mình những đôi giày cực chất tại cửa hàng NMK SHOP nhé.</p>
            <a href="cua-hang.php" class="btn btn-dark text-uppercase fw-bold btn-sm px-4 py-2 mt-2" style="background-color: #111;">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        
        <div class="table-responsive border rounded bg-white shadow-sm p-3 mb-5">
            <table class="table table-hover align-middle m-0">
                <thead class="border-bottom" style="font-size: 13px; text-transform: uppercase;">
                    <tr class="text-muted">
                        <th scope="col" style="width: 110px;">Mã đơn</th>
                        <th scope="col" style="width: 140px;">Ngày đặt</th>
                        <th scope="col">Chi tiết sản phẩm</th>
                        <th scope="col" style="width: 140px;" class="text-end">Tổng tiền</th>
                        <th scope="col" style="width: 130px;" class="text-center">Thanh toán</th>
                        <th scope="col" style="width: 140px;" class="text-center">Trạng thái</th>
                        <th scope="col" style="width: 110px;" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
                        $sql_details = "SELECT od.quantity, od.price, v.color, v.size, v.image, p.product_name 
                                        FROM order_details od
                                        LEFT JOIN product_variants v ON od.variant_id = v.id
                                        LEFT JOIN products p ON v.product_id = p.id
                                        WHERE od.order_id = ?";
                        $stmt_details = $conn->prepare($sql_details);
                        $stmt_details->execute([$order['id']]);
                        $items = $stmt_details->fetchAll();
                    ?>
                        <tr class="border-bottom-subtle">
                            <td class="fw-bold text-dark small">#NMK-<?= $order['id'] ?></td>
                            
                            <td class="text-secondary small" style="font-size: 12px;">
                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </td>
                            
                            <td>
                                <div class="pe-2">
                                    <?php foreach ($items as $item): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <img src="../uploads/<?= !empty($item['image']) ? htmlspecialchars($item['image']) : 'default_shoe.jpg' ?>" class="img-thumbnail me-2" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='https://placehold.co/50x50?text=NMK'">
                                            <div style="font-size: 12px;">
                                                <strong class="text-dark d-block"><?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm đã gỡ') ?></strong> 
                                                <span class="text-muted" style="font-size: 11px;">
                                                    Màu: <?= htmlspecialchars($item['color'] ?? 'N/A') ?> | Size: <?= htmlspecialchars($item['size'] ?? 'N/A') ?>
                                                </span>
                                                <span class="fw-bold text-danger ms-1">x<?= $item['quantity'] ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            
                            <td class="text-end fw-bold text-danger" style="font-size: 14px;">
                                <?= number_format($order['total_price'], 0, ',', '.') ?> đ
                            </td>

                            <td class="text-center small" style="font-size: 11px;">
                                <?php if ($order['payment_method'] === 'COD'): ?>
                                    <span class="badge bg-light text-dark border"><i class="fa-solid fa-money-bill-wave text-success me-1"></i>COD</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border"><i class="fa-solid fa-qrcode text-primary me-1"></i>Chuyển khoản</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-center">
                                <?php
                                $status = $order['status'];
                                $badge_class = 'bg-secondary';
                                
                                if ($status === 'Chờ xác nhận')     $badge_class = 'bg-warning text-dark';
                                elseif ($status === 'Đang xử lý')   $badge_class = 'bg-info text-dark';
                                elseif ($status === 'Đang giao')    $badge_class = 'bg-primary';
                                elseif ($status === 'Đã giao')      $badge_class = 'bg-success';
                                elseif ($status === 'Đã hủy')       $badge_class = 'bg-danger';
                                ?>
                                <span class="badge <?= $badge_class ?> fw-medium small text-uppercase" style="font-size: 11px; letter-spacing: 0.3px;">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            
                            <td class="text-center">
                                <?php if ($order['status'] === 'Chờ xác nhận'): ?>
                                    <form action="lich-su-don-hang.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng #NMK-<?= $order['id'] ?> không? (Hệ thống sẽ hoàn sản phẩm về lại kho)');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="cancel_order" class="btn btn-outline-danger btn-sm py-1 px-2 fw-bold text-uppercase" style="font-size: 10px;">
                                            <i class="fa-solid fa-xmark me-1"></i>Hủy đơn
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small" style="font-size: 11px; font-style: italic;">Không thể hủy</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
    <?php endif; ?>
</div>

<?php 
include_once '../includes/footer.php'; 
?>