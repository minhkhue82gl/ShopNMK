<?php
require_once '../includes/conn.php';
/** @var PDO $conn */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || empty($_SESSION['pending_order']) || empty($_SESSION['cart'])) {
    header('Location: cua-hang.php');
    exit;
}

$pending = $_SESSION['pending_order'];
$total_amount = $pending['final_total'];

if (isset($_POST['confirm_online_payment'])) {
    try {
        $conn->beginTransaction();

        $sql_order = "INSERT INTO orders (user_id, fullname, email, phone, address, total_price, coupon_code, payment_method, status, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Chờ xác nhận', NOW())";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->execute([
            $pending['user_id'], 
            $pending['fullname'], 
            $pending['email'], 
            $pending['phone'], 
            $pending['address'], 
            $pending['final_total'], 
            $pending['coupon_code'], 
            'Online'
        ]);
        
        $order_id = $conn->lastInsertId();

        foreach ($_SESSION['cart'] as $variant_id => $item) {
            $stmt_chk = $conn->prepare("SELECT stock FROM product_variants WHERE id = ? FOR UPDATE");
            $stmt_chk->execute([$variant_id]);
            $current_stock = $stmt_chk->fetchColumn();

            if ($current_stock < $item['quantity']) {
                throw new Exception("Mẫu giày '" . $item['product_name'] . "' vừa hết hàng trong kho. Giao dịch bị hủy!");
            }

            $sql_detail = "INSERT INTO order_details (order_id, variant_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($sql_detail);
            $stmt_detail->execute([$order_id, $variant_id, $item['quantity'], $item['price']]);

            $sql_update_stock = "UPDATE product_variants SET stock = stock - ? WHERE id = ?";
            $stmt_stock = $conn->prepare($sql_update_stock);
            $stmt_stock->execute([$item['quantity'], $variant_id]);
        }

        if (!empty($pending['coupon_code'])) {
            $stmt_cp = $conn->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt_cp->execute([$pending['coupon_code']]);
            $cp_id = $stmt_cp->fetchColumn();
            if ($cp_id) {
                $stmt_dec_cp = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                $stmt_dec_cp->execute([$cp_id]);
            }
        }

        $conn->commit();

        $_SESSION['cart'] = [];
        unset($_SESSION['coupon']);
        unset($_SESSION['pending_order']);

        echo "<script>
                alert('Thanh toán trực tuyến thành công! Mã đơn hàng của bạn là: #NMK-$order_id');
                window.location.href = 'lich-su-don-hang.php';
              </script>";
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error_msg = $e->getMessage();
    }
}

include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border rounded bg-white shadow-sm p-4 text-center">
                <h4 class="fw-bold text-uppercase text-dark mb-3">
                    <i class="fa-solid fa-qrcode text-primary me-2"></i> Quét mã QR thanh toán
                </h4>
                <p class="text-muted small mb-4">Vui lòng sử dụng ứng dụng Ngân hàng hoặc Ví điện tử để quét mã chuyển khoản bên dưới.</p>

                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger small mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error_msg) ?>
                    </div>
                <?php endif; ?>

                <!-- Thông tin tài khoản nhận tiền mẫu (có thể thay đổi theo ngân hàng của bạn) -->
                <div class="bg-light p-3 rounded border mb-3 text-start small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Ngân hàng:</span>
                        <strong class="text-dark">MB Bank (Quân Đội)</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Chủ tài khoản:</span>
                        <strong class="text-dark">NGUYEN MINH KHUE</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Số tài khoản:</span>
                        <strong class="text-primary font-monospace" style="font-size: 14px;">090123456789</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Số tiền cần chuyển:</span>
                        <strong class="text-danger fs-6"><?= number_format($total_amount, 0, ',', '.') ?> đ</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Nội dung chuyển khoản:</span>
                        <strong class="text-dark">NMK SHOP <?= rand(1000, 9999) ?></strong>
                    </div>
                </div>

                <!-- Hình ảnh mã QR chuyển khoản giả lập (hoặc tích hợp API VietQR động) -->
                <div class="mb-4">
                    <img src="https://img.vietqr.io/image/MB-090123456789-compact2.png?amount=<?= $total_amount ?>&addInfo=Thanh%20toan%20don%20hang%20NMK%20Shop" alt="Mã QR thanh toán" class="img-fluid border rounded p-2 bg-white" style="max-width: 250px;">
                </div>

                <form action="qr-thanh-toan.php" method="POST">
                    <button type="submit" name="confirm_online_payment" class="btn btn-success w-100 fw-bold text-uppercase py-2 mb-2">
                        <i class="fa-solid fa-circle-check me-1"></i> Tôi đã chuyển khoản thành công
                    </button>
                    <a href="thanh-toan.php" class="btn btn-outline-secondary w-100 btn-sm text-uppercase fw-bold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại chọn phương thức khác
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
include_once '../includes/footer.php'; 
?>