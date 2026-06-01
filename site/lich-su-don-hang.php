<?php
// Kiểm tra session nếu chưa khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để sử dụng tính năng này.";
    header('Location: dang-nhap.php');
    exit; // Dừng việc tải file ngay tại đây
}
?>
<?php
// 1. Nhúng file kết nối CSDL và khởi động Session
require_once '../includes/conn.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra nếu khách hàng chưa đăng nhập thì bắt buộc chuyển hướng sang trang đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: dang-nhap.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$success_msg = '';
$error_msg = '';

// ================= LUỒNG XỬ LÝ: HỦY ĐƠN HÀNG =================
if (isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);

    try {
        // Kiểm tra xem đơn hàng có thực sự thuộc về người dùng này và đang ở trạng thái "Chờ xác nhận" hay không
        $stmt_chk = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
        $stmt_chk->execute([$order_id, $user_id]);
        $order_status = $stmt_chk->fetchColumn();

        if ($order_status === 'Chờ xác nhận') {
            // Cập nhật trạng thái đơn hàng thành 'Đã hủy'
            $stmt_cancel = $conn->prepare("UPDATE orders SET status = 'Đã hủy' WHERE id = ? AND user_id = ?");
            $stmt_cancel->execute([$order_id, $user_id]);
            $success_msg = "Hủy đơn hàng #NMK-$order_id thành công!";
        } else {
            $error_msg = "Không thể hủy đơn hàng do đơn hàng đã được shop xử lý hoặc đang giao!";
        }
    } catch (PDOException $e) {
        $error_msg = "Lỗi hệ thống: " . $e->getMessage();
    }
}

try {
    // ================= LUỒNG XỬ LÝ: LẤY DANH SÁCH ĐƠN HÀNG =================
    // Lấy tất cả các đơn hàng của user này từ bảng orders
    $sql_orders = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->execute([$user_id]);
    $orders = $stmt_orders->fetchAll();

} catch (PDOException $e) {
    die("Lỗi truy vấn dữ liệu: " . $e->getMessage());
}

// Nhúng thanh điều hướng Header dùng chung
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
        <div class="alert alert-success small"><i class="fa-solid fa-circle-check me-2"></i><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger small"><i class="fa-solid fa-circle-xmark me-2"></i><?= $error_msg ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5 border rounded bg-white shadow-sm">
            <i class="fa-solid fa-receipt text-muted mb-3" style="font-size: 60px;"></i>
            <h5 class="text-secondary fw-bold">Bạn chưa có đơn hàng nào!</h5>
            <p class="text-muted small">Hãy chọn cho mình những sản phẩm giày cực chất tại cửa hàng NMK Shoes nhé.</p>
            <a href="cua-hang.php" class="btn btn-dark text-uppercase fw-bold btn-sm px-4 py-2 mt-2" style="background-color: #111;">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        
        <div class="table-responsive border rounded bg-white shadow-sm p-3 mb-5">
            <table class="table table-hover align-middle m-0">
                <thead class="border-bottom" style="font-size: 13px; text-transform: uppercase;">
                    <tr class="text-muted">
                        <th scope="col" style="width: 120px;">Mã đơn hàng</th>
                        <th scope="col" style="width: 150px;">Ngày đặt hàng</th>
                        <th scope="col">Thông tin sản phẩm</th>
                        <th scope="col" style="width: 150px;" class="text-end">Tổng thanh toán</th>
                        <th scope="col" style="width: 140px;" class="text-center">Trạng thái</th>
                        <th scope="col" style="width: 110px;" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
                        // Lấy danh sách sản phẩm chi tiết của từng đơn hàng cụ thể
                        $sql_details = "SELECT od.quantity, od.price, v.color, v.size, p.product_name 
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
                            
                            <td class="text-secondary small" style="font-size: 13px;">
                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </td>
                            
                            <td>
                                <div class="pe-2">
                                    <?php foreach ($items as $item): ?>
                                        <div class="mb-1 small text-dark" style="font-size: 13px;">
                                            • <strong><?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm đã xóa') ?></strong> 
                                            <span class="text-muted" style="font-size: 11px;">
                                                (Màu: <?= htmlspecialchars($item['color']) ?> | Size: <?= $item['size'] ?>)
                                            </span>
                                            <span class="fw-bold text-secondary">x<?= $item['quantity'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            
                            <td class="text-end fw-bold text-danger" style="font-size: 14px;">
                                <?= number_format($order['total_price'], 0, ',', '.') ?> đ
                            </td>
                            
                            <td class="text-center">
                                <?php
                                $status = $order['status'];
                                $badge_class = 'bg-secondary'; // Mặc định
                                
                                if ($status === 'Chờ xác nhận') $badge_class = 'bg-warning text-dark';
                                elseif ($status === 'Đang xử lý') $badge_class = 'bg-info text-dark';
                                elseif ($status === 'Đang giao') $badge_class = 'bg-primary';
                                elseif ($status === 'Đã giao') $badge_class = 'bg-success';
                                elseif ($status === 'Đã hủy') $badge_class = 'bg-danger';
                                ?>
                                <span class="badge <?= $badge_class ?> fw-medium small text-uppercase" style="font-size: 11px; letter-spacing: 0.3px;">
                                    <?= $status ?>
                                </span>
                            </td>
                            
                            <td class="text-center">
                                <?php if ($order['status'] === 'Chờ xác nhận'): ?>
                                    <form action="lich-su-don-hang.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn yêu cầu hủy đơn hàng #NMK-<?= $order['id'] ?> không?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="cancel_order" class="btn btn-outline-danger btn-xs py-1 px-2 fw-bold text-uppercase" style="font-size: 10px;">
                                            <i class="fa-solid fa-rectangle-xmark me-1"></i>Hủy đơn
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small" style="font-size: 11px; font-style: italic;">Khóa bộ lọc</span>
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