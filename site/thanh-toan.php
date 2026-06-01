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

// Nếu giỏ hàng trống, không cho phép thanh toán, đẩy ngược về trang cửa hàng
if (empty($_SESSION['cart'])) {
    header('Location: cua-hang.php');
    exit;
}

// Khởi tạo các biến xử lý coupon mã giảm giá
$discount_amount = 0;
$coupon_code = '';
$coupon_error = '';
$coupon_success = '';

// Tính tổng tiền tạm tính ban đầu của toàn bộ giỏ hàng
$total_cart_money = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_cart_money += $item['price'] * $item['quantity'];
}

// --- LUỒNG XỬ LÝ 1: KIỂM TRA MÃ GIẢM GIÁ (COUPON) ---
if (isset($_POST['apply_coupon'])) {
    $coupon_code = trim($_POST['coupon_code']);
    
    $stmt_cp = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND status = 1 AND expiry_date >= CURDATE()");
    $stmt_cp->execute([$coupon_code]);
    $coupon = $stmt_cp->fetch();

    if ($coupon) {
        if ($coupon['discount_type'] === 'fixed') {
            $discount_amount = $coupon['discount_value'];
        } elseif ($coupon['discount_type'] === 'percent') {
            $discount_amount = ($total_cart_money * $coupon['discount_value']) / 100;
        }
        // Lưu thông tin coupon vào session để giữ lại khi submit form đặt hàng
        $_SESSION['discount'] = [
            'code' => $coupon['code'],
            'amount' => $discount_amount
        ];
        $coupon_success = "Áp dụng mã giảm giá thành công!";
    } else {
        unset($_SESSION['discount']);
        $coupon_error = "Mã giảm giá không hợp lệ hoặc đã hết hạn sử dụng!";
    }
}

// Đọc lại giá trị giảm giá từ Session nếu có
if (isset($_SESSION['discount'])) {
    $coupon_code = $_SESSION['discount']['code'];
    $discount_amount = $_SESSION['discount']['amount'];
}

// Tính tổng tiền cuối cùng khách phải trả
$final_total = $total_cart_money - $discount_amount;
if ($final_total < 0) $final_total = 0;


// --- LUỒNG XỬ LÝ 2: TIẾP NHẬN ĐẶT HÀNG & LƯU VÀO CSDL ---
if (isset($_POST['place_order'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];
    $user_id = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;

    if (!empty($fullname) && !empty($email) && !empty($phone) && !empty($address)) {
        try {
            // Bắt đầu một Transaction nhằm đảm bảo an toàn dữ liệu (Nếu lỗi ở bất kỳ bước nào sẽ hủy toàn bộ)
            $conn->beginTransaction();

            // Bước A: Thêm thông tin vào bảng orders
            $sql_order = "INSERT INTO orders (user_id, fullname, email, phone, address, total_price, coupon_code, payment_method, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Chờ xác nhận')";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->execute([$user_id, $fullname, $email, $phone, $address, $final_total, !empty($coupon_code) ? $coupon_code : null, $payment_method]);
            
            // Lấy ID tự động tăng của đơn hàng vừa tạo
            $order_id = $conn->lastInsertId();

            // Bước B: Duyệt qua giỏ hàng để lưu chi tiết đơn hàng và TRỪ KHO
            foreach ($_SESSION['cart'] as $variant_id => $item) {
                // 1. Kiểm tra lại số lượng tồn kho thực tế một lần nữa trước khi trừ
                $stmt_chk = $conn->prepare("SELECT stock FROM product_variants WHERE id = ? FOR UPDATE");
                $stmt_chk->execute([$variant_id]);
                $current_stock = $stmt_chk->fetchColumn();

                if ($current_stock < $item['quantity']) {
                    // Nếu kho không đủ đáp ứng, dừng toàn bộ tiến trình và báo lỗi
                    throw new Exception("Mẫu giày '" . $item['product_name'] . "' (Size: " . $item['size'] . ") vừa hết hàng hoặc không đủ số lượng trong kho! Vui lòng kiểm tra lại giỏ hàng.");
                }

                // 2. Ghi dữ liệu vào bảng order_details
                $sql_detail = "INSERT INTO order_details (order_id, variant_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt_detail = $conn->prepare($sql_detail);
                $stmt_detail->execute([$order_id, $variant_id, $item['quantity'], $item['price']]);

                // 3. Tiến hành trừ số lượng sản phẩm trong kho biến thể (product_variants)
                $sql_update_stock = "UPDATE product_variants SET stock = stock - ? WHERE id = ?";
                $stmt_stock = $conn->prepare($sql_update_stock);
                $stmt_stock->execute([$item['quantity'], $variant_id]);
            }

            // Nếu mọi thứ chạy mượt mà, chốt lưu vĩnh viễn vào MySQL
            $conn->commit();

            // Xóa sạch giỏ hàng và dữ liệu mã giảm giá sau khi đặt hàng thành công
            $_SESSION['cart'] = [];
            unset($_SESSION['discount']);

            echo "<script>
                    alert('Chúc mừng bạn đã đặt hàng thành công! Mã đơn hàng của bạn là: #NMK-$order_id');
                    window.location.href = 'index.php';
                  </script>";
            exit;

        } catch (Exception $e) {
            // Có lỗi xảy ra (Ví dụ: Hết kho giữa chừng), hoàn tác quay lại trạng thái ban đầu để tránh lỗi lệch dữ liệu
            $conn->rollBack();
            $error_msg = $e->getMessage();
        }
    } else {
        $error_msg = "Vui lòng nhập đầy đủ tất cả các trường thông tin giao nhận hàng!";
    }
}

// Nhúng thanh Header dùng chung
include_once '../includes/header.php';
?>

<div class="container my-5">
    <h4 class="fw-bold text-uppercase mb-4 text-dark"><i class="fa-solid fa-credit-card me-2 text-primary"></i> Tiến hành thanh toán đơn hàng</h4>

    <?php if(isset($error_msg)): ?>
        <div class="alert alert-danger font-monospace small"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border rounded bg-white shadow-sm p-4">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size: 16px;">Thông tin giao nhận hàng</h5>
                
                <form action="thanh-toan.php" method="POST" id="mainOrderForm">
                   
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Họ và tên người nhận *</label>
                        <input type="text" name="fullname" class="form-control" value="<?= isset($_SESSION['user']['fullname']) ? htmlspecialchars($_SESSION['user']['fullname']) : '' ?>" placeholder="Nhập đầy đủ họ và tên..." required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary">Số điện thoại liên hệ *</label>
                            <input type="tel" name="phone" class="form-control" value="<?= isset($_SESSION['user']['phone']) ? htmlspecialchars($_SESSION['user']['phone']) : '' ?>" placeholder="Ví dụ: 0901234567" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary">Địa chỉ Email nhận hóa đơn *</label>
                            <input type="email" name="email" class="form-control" value="<?= isset($_SESSION['user']['email']) ? htmlspecialchars($_SESSION['user']['email']) : '' ?>" placeholder="name@example.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Địa chỉ nhận hàng chi tiết *</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Ghi rõ số nhà, tên đường, xã/phường, quận/huyện, tỉnh thành..." required><?= isset($_SESSION['user']['address']) ? htmlspecialchars($_SESSION['user']['address']) : '' ?></textarea>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size: 16px;">Phương thức thanh toán</h5>
                    <div class="form-check p-3 border rounded mb-2 bg-light">
                        <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_cod" value="COD" checked>
                        <label class="form-check-label fw-bold text-dark small" for="pay_cod">
                            <i class="fa-solid fa-money-bill-wave text-success me-1"></i> Thanh toán tiền mặt khi nhận hàng (COD)
                        </label>
                        <small class="d-block text-muted ms-4 mt-1" style="font-size: 11px;">Bạn sẽ thanh toán bằng tiền mặt trực tiếp cho nhân viên giao hàng khi nhận được bưu kiện giày.</small>
                    </div>

                    <div class="form-check p-3 border rounded bg-light">
                        <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_online" value="Online">
                        <label class="form-check-label fw-bold text-dark small" for="pay_online">
                            <i class="fa-solid fa-credit-card text-primary me-1"></i> Chuyển khoản Internet Banking (Tự động)
                        </label>
                        <small class="d-block text-muted ms-4 mt-1" style="font-size: 11px;">Hệ thống chuyển hướng quét mã QR hoặc cổng Napas bảo mật an toàn cao.</small>
                    </div>

                    <input type="hidden" name="place_order" value="1">
                    <div class="mt-4">
        <button type="submit" name="place_order_btn" class="btn btn-primary btn-lg w-100 fw-bold text-uppercase py-3">
            <i class="fa-solid fa-check-double me-2"></i> Xác nhận đặt hàng
        </button>
        <p class="text-center text-muted small mt-2">
            Nhấn "Xác nhận đặt hàng" đồng nghĩa với việc bạn đồng ý với các điều khoản vận chuyển của chúng tôi.
        </p>
    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
           
            <div class="card border rounded bg-white shadow-sm p-3 mb-4">
                <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;"><i class="fa-solid fa-ticket text-danger me-1"></i> Bạn có mã giảm giá?</h6>
                <form action="thanh-toan.php" method="POST" class="d-flex gap-2">
                    <input type="text" name="coupon_code" class="form-control form-control-sm text-uppercase fw-bold" placeholder="Ví dụ: NMK100K" value="<?= htmlspecialchars($coupon_code) ?>">
                    <button type="submit" name="apply_coupon" class="btn btn-outline-dark btn-sm text-nowrap fw-bold text-uppercase">Áp dụng</button>
                </form>
                <?php if(!empty($coupon_error)): ?>
                    <small class="text-danger d-block mt-1 small" style="font-size: 11px;"><i class="fa-solid fa-circle-xmark me-1"></i><?= $coupon_error ?></small>
                <?php endif; ?>
                <?php if(!empty($coupon_success)): ?>
                    <small class="text-success d-block mt-1 small" style="font-size: 11px;"><i class="fa-solid fa-circle-check me-1"></i><?= $coupon_success ?></small>
                <?php endif; ?>
            </div>

            <div class="card border rounded bg-white shadow-sm p-3">
                <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-dark" style="font-size: 13px;">Chi tiết đơn hàng mua</h6>
                
                <div class="order-preview-items border-bottom pb-2 mb-3" style="max-height: 220px; overflow-y: auto;">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="d-flex align-items-center justify-content-between mb-2 small text-secondary">
                            <div class="text-truncate me-3" style="max-width: 65%;">
                                <strong class="text-dark small"><?= htmlspecialchars($item['product_name']) ?></strong>
                                <span class="d-block" style="font-size: 11px;">Màu: <?= htmlspecialchars($item['color']) ?> | Size: <?= $item['size'] ?></span>
                            </div>
                            <span class="text-nowrap text-dark fw-medium">x <?= $item['quantity'] ?> $\rightarrow$ <span class="text-danger fw-bold"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</span></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 small text-secondary">
                    <span>Tổng tiền tạm tính:</span>
                    <span class="text-dark fw-bold"><?= number_format($total_cart_money, 0, ',', '.') ?> đ</span>
                </div>
                
                <?php if ($discount_amount > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 small text-secondary">
                        <span>Số tiền được giảm (Mã: <strong><?= htmlspecialchars($coupon_code) ?></strong>):</span>
                        <span class="text-success fw-bold">- <?= number_format($discount_amount, 0, ',', '.') ?> đ</span>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-3 small text-secondary">
                    <span>Phí vận chuyển toàn quốc:</span>
                    <span class="text-success fw-bold">FREE SHIP</span>
                </div>
                
                <hr class="border-secondary border-opacity-10 my-2">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="fw-bold text-dark" style="font-size: 15px;">Tổng cộng cuối cùng:</span>
                    <span class="text-danger fw-bold fs-4"><?= number_format($final_total, 0, ',', '.') ?> đ</span>
                </div>