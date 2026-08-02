<?php
require_once '../includes/conn.php';
/** @var PDO $conn */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để tiến hành thanh toán.";
    header('Location: dang-nhap.php');
    exit;
}

if (empty($_SESSION['cart'])) {
    header('Location: cua-hang.php');
    exit;
}

$discount_amount = 0;
$coupon_code     = '';
$coupon_error    = '';
$coupon_success  = '';

$total_cart_money = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_cart_money += $item['price'] * $item['quantity'];
}

if (isset($_POST['apply_coupon'])) {
    $coupon_code = trim($_POST['coupon_code']);
    
    $stmt_cp = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND status = 1");
    $stmt_cp->execute([$coupon_code]);
    $coupon = $stmt_cp->fetch(PDO::FETCH_ASSOC);

    $now = date('Y-m-d H:i:s');

    if ($coupon) {
        $is_expired = false;
        if (!empty($coupon['start_date']) && !empty($coupon['end_date'])) {
            if ($now < $coupon['start_date'] || $now > $coupon['end_date']) {
                $is_expired = true;
            }
        } elseif (!empty($coupon['expiry_date']) && $coupon['expiry_date'] !== '0000-00-00' && $coupon['expiry_date'] < date('Y-m-d')) {
            $is_expired = true;
        }

        if ($is_expired) {
            unset($_SESSION['coupon']);
            $coupon_error = "Mã giảm giá này đã hết hạn sử dụng!";
        } elseif ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
            unset($_SESSION['coupon']);
            $coupon_error = "Mã giảm giá này đã hết lượt sử dụng!";
        } elseif ($total_cart_money < $coupon['min_order_amount']) {
            unset($_SESSION['coupon']);
            $coupon_error = "Đơn hàng tối thiểu " . number_format($coupon['min_order_amount'], 0, ',', '.') . "đ mới dùng được mã này!";
        } else {
            if ($coupon['discount_type'] === 'fixed') {
                $discount_amount = $coupon['discount_value'];
            } elseif ($coupon['discount_type'] === 'percent') {
                $discount_amount = ($total_cart_money * $coupon['discount_value']) / 100;
                
                // Kiểm tra giảm tối đa
                $max_cap = !empty($coupon['max_discount_amount']) && $coupon['max_discount_amount'] > 0 
                           ? $coupon['max_discount_amount'] 
                           : $coupon['max_discount'];

                if (!empty($max_cap) && $max_cap > 0 && $discount_amount > $max_cap) {
                    $discount_amount = $max_cap;
                }
            }

            $_SESSION['coupon'] = [
                'id'              => $coupon['id'],
                'code'            => $coupon['code'],
                'discount_type'   => $coupon['discount_type'],
                'discount_value'  => $coupon['discount_value'],
                'discount_amount' => $discount_amount
            ];
            $coupon_success = "Áp dụng mã giảm giá thành công!";
        }
    } else {
        unset($_SESSION['coupon']);
        $coupon_error = "Mã giảm giá không hợp lệ hoặc đã bị khóa!";
    }
}

if (isset($_SESSION['coupon'])) {
    $coupon_code = $_SESSION['coupon']['code'];
    if (isset($_SESSION['coupon']['discount_amount'])) {
        $discount_amount = $_SESSION['coupon']['discount_amount'];
    } else {
        if ($_SESSION['coupon']['discount_type'] === 'fixed') {
            $discount_amount = $_SESSION['coupon']['discount_value'];
        } else {
            $discount_amount = ($total_cart_money * $_SESSION['coupon']['discount_value']) / 100;
        }
    }
}

if ($discount_amount > $total_cart_money) {
    $discount_amount = $total_cart_money;
}

$final_total = $total_cart_money - $discount_amount;
if ($final_total < 0) $final_total = 0;

if (isset($_POST['place_order'])) {
    $fullname       = trim($_POST['fullname']);
    $email          = trim($_POST['email']);
    $phone          = trim($_POST['phone']);
    $address        = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];
    $user_id        = $_SESSION['user']['id'];

    if (!empty($fullname) && !empty($email) && !empty($phone) && !empty($address)) {
        
        try {
            // KIỂM TRA TỒN KHO TRƯỚC
            foreach ($_SESSION['cart'] as $variant_id => $item) {
                $stmt_chk = $conn->prepare("SELECT stock FROM product_variants WHERE id = ?");
                $stmt_chk->execute([$variant_id]);
                $current_stock = $stmt_chk->fetchColumn();

                if ($current_stock === false || $current_stock < $item['quantity']) {
                    throw new Exception("Mẫu giày '" . $item['product_name'] . "' (Màu: " . $item['color'] . ", Size: " . $item['size'] . ") không đủ số lượng tồn kho!");
                }
            }

            if ($payment_method === 'Online') {
                $_SESSION['pending_order'] = [
                    'user_id'        => $user_id,
                    'fullname'       => $fullname,
                    'email'          => $email,
                    'phone'          => $phone,
                    'address'        => $address,
                    'final_total'    => $final_total,
                    'coupon_code'    => !empty($coupon_code) ? $coupon_code : null,
                    'coupon_id'      => $_SESSION['coupon']['id'] ?? null,
                    'payment_method' => 'Online'
                ];
                header('Location: qr-thanh-toan.php');
                exit;
            }

            $conn->beginTransaction();

            $sql_order = "INSERT INTO orders (user_id, fullname, email, phone, address, total_price, coupon_code, payment_method, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Chờ xác nhận', NOW())";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->execute([
                $user_id, $fullname, $email, $phone, $address, 
                $final_total, !empty($coupon_code) ? $coupon_code : null, $payment_method
            ]);
            
            $order_id = $conn->lastInsertId();

            foreach ($_SESSION['cart'] as $variant_id => $item) {
                $sql_detail = "INSERT INTO order_details (order_id, variant_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt_detail = $conn->prepare($sql_detail);
                $stmt_detail->execute([$order_id, $variant_id, $item['quantity'], $item['price']]);

                // Trừ kho biến thể sản phẩm
                $sql_update_stock = "UPDATE product_variants SET stock = stock - ? WHERE id = ?";
                $stmt_stock = $conn->prepare($sql_update_stock);
                $stmt_stock->execute([$item['quantity'], $variant_id]);
            }

            // Cộng số lượt đã sử dụng của coupon
            if (!empty($_SESSION['coupon']['id'])) {
                $stmt_dec_cp = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                $stmt_dec_cp->execute([$_SESSION['coupon']['id']]);
            }

            $conn->commit();

            $_SESSION['cart'] = [];
            unset($_SESSION['coupon']);

            echo "<script>
                    alert('Đặt hàng thành công! Mã đơn hàng của bạn là: #NMK-$order_id');
                    window.location.href = 'lich-su-don-hang.php';
                  </script>";
            exit;

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error_msg = $e->getMessage();
        }
    } else {
        $error_msg = "Vui lòng nhập đầy đủ tất cả các trường thông tin giao nhận hàng!";
    }
}

include_once '../includes/header.php';
?>

<div class="container my-5">
    <h4 class="fw-bold text-uppercase mb-4 text-dark">
        <i class="fa-solid fa-credit-card me-2 text-primary"></i> Tiến hành thanh toán đơn hàng
    </h4>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger font-monospace small mb-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border rounded bg-white shadow-sm p-4">
                <form action="thanh-toan.php" method="POST" id="mainOrderForm">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size: 16px;">
                        <i class="fa-solid fa-location-dot me-2 text-danger"></i>Thông tin giao nhận hàng
                    </h5>
                   
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

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size: 16px;">
                        <i class="fa-solid fa-wallet me-2 text-success"></i>Phương thức thanh toán
                    </h5>

                    <div class="form-check p-3 border rounded mb-2 bg-light">
                        <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_cod" value="COD" checked>
                        <label class="form-check-label fw-bold text-dark small" for="pay_cod">
                            <i class="fa-solid fa-money-bill-wave text-success me-1"></i> Thanh toán tiền mặt khi nhận hàng (COD)
                        </label>
                    </div>

                    <div class="form-check p-3 border rounded bg-light mb-4">
                        <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_online" value="Online">
                        <label class="form-check-label fw-bold text-dark small" for="pay_online">
                            <i class="fa-solid fa-qrcode text-primary me-1"></i> Chuyển khoản Internet Banking (Mã QR / Cổng Ngân hàng)
                        </label>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-dark btn-lg w-100 fw-bold text-uppercase py-3" style="background-color: #111; font-size: 15px;">
                        <i class="fa-solid fa-check-double me-2"></i> Xác nhận đặt hàng ngay
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border rounded bg-white shadow-sm p-3 mb-4">
                <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;">
                    <i class="fa-solid fa-ticket text-warning me-1"></i> Mã giảm giá ưu đãi
                </h6>
                <form action="thanh-toan.php" method="POST" class="d-flex gap-2">
                    <input type="text" name="coupon_code" class="form-control form-control-sm text-uppercase fw-bold" placeholder="Nhập mã..." value="<?= htmlspecialchars($coupon_code) ?>">
                    <button type="submit" name="apply_coupon" class="btn btn-dark btn-sm text-nowrap fw-bold text-uppercase" style="background-color: #111;">Áp dụng</button>
                </form>
                
                <?php if (!empty($coupon_error)): ?>
                    <small class="text-danger d-block mt-2 small" style="font-size: 11px;"><i class="fa-solid fa-circle-xmark me-1"></i><?= $coupon_error ?></small>
                <?php endif; ?>
                
                <?php if (!empty($coupon_success)): ?>
                    <small class="text-success d-block mt-2 small" style="font-size: 11px;"><i class="fa-solid fa-circle-check me-1"></i><?= $coupon_success ?></small>
                <?php endif; ?>
            </div>

            <div class="card border rounded bg-white shadow-sm p-3">
                <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-dark" style="font-size: 13px;">
                    Chi tiết đơn hàng (<?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?> sản phẩm)
                </h6>
                
                <div class="order-preview-items border-bottom pb-2 mb-3" style="max-height: 240px; overflow-y: auto;">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="d-flex align-items-center justify-content-between mb-2 small text-secondary">
                            <div class="text-truncate me-3" style="max-width: 65%;">
                                <strong class="text-dark small d-block text-truncate"><?= htmlspecialchars($item['product_name']) ?></strong>
                                <span class="d-block text-muted" style="font-size: 11px;">Màu: <?= htmlspecialchars($item['color']) ?> | Size: <?= htmlspecialchars($item['size']) ?></span>
                            </div>
                            <span class="text-nowrap text-dark fw-medium" style="font-size: 12px;">
                                x<?= $item['quantity'] ?> = <span class="text-danger fw-bold"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 small text-secondary">
                    <span>Tổng tiền tạm tính:</span>
                    <span class="text-dark fw-bold"><?= number_format($total_cart_money, 0, ',', '.') ?> đ</span>
                </div>
                
                <?php if ($discount_amount > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 small text-success">
                        <span>Số tiền được giảm (Mã: <strong><?= htmlspecialchars($coupon_code) ?></strong>):</span>
                        <span class="fw-bold">- <?= number_format($discount_amount, 0, ',', '.') ?> đ</span>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-3 small text-secondary">
                    <span>Phí vận chuyển toàn quốc:</span>
                    <span class="text-success fw-bold">FREE SHIP</span>
                </div>
                
                <hr class="border-secondary border-opacity-10 my-2">
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark" style="font-size: 15px;">Tổng cộng thanh toán:</span>
                    <span class="text-danger fw-bold fs-4"><?= number_format($final_total, 0, ',', '.') ?> đ</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include_once '../includes/footer.php'; 
?>