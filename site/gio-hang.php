
<?php
require_once '../includes/conn.php';

/** @var PDO $conn */ 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để sử dụng tính năng giỏ hàng.";
    header('Location: dang-nhap.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_id = intval($_POST['product_id']);
        $color      = trim($_POST['selected_color'] ?? '');
        $size       = trim($_POST['selected_size'] ?? '');
        $quantity   = intval($_POST['quantity'] ?? 1);

        if ($quantity <= 0) $quantity = 1;

        // Truy vấn bảng product_variants để lấy chính xác variant_id và stock
        $stmt_var = $conn->prepare("SELECT v.*, p.product_name, p.price 
                                    FROM product_variants v 
                                    JOIN products p ON v.product_id = p.id 
                                    WHERE v.product_id = ? AND v.color = ? AND v.size = ?");
        $stmt_var->execute([$product_id, $color, $size]);
        $variant = $stmt_var->fetch();

        if ($variant) {
            $variant_id = $variant['id'];

            if (isset($_SESSION['cart'][$variant_id])) {
                $new_qty = $_SESSION['cart'][$variant_id]['quantity'] + $quantity;
                
                if ($new_qty > $variant['stock']) {
                    $_SESSION['cart'][$variant_id]['quantity'] = $variant['stock'];
                } else {
                    $_SESSION['cart'][$variant_id]['quantity'] = $new_qty;
                }
            } else {
                if ($quantity > $variant['stock']) {
                    $quantity = $variant['stock'];
                }
                
                $_SESSION['cart'][$variant_id] = [
                    'product_name' => $variant['product_name'],
                    'color'        => $variant['color'],
                    'size'         => $variant['size'],
                    'price'        => $variant['price'],
                    'image'        => $variant['image'],
                    'quantity'     => $quantity,
                    'max_stock'    => $variant['stock']
                ];
            }
            $_SESSION['success'] = "Đã thêm sản phẩm vào giỏ hàng!";
        } else {
            $_SESSION['error'] = "Sản phẩm hoặc biến thể không tồn tại!";
        }
        
        header('Location: gio-hang.php');
        exit;
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_qty']) && is_array($_POST['update_qty'])) {
            foreach ($_POST['update_qty'] as $v_id => $qty) {
                $v_id = intval($v_id);
                $qty  = intval($qty);

                if (isset($_SESSION['cart'][$v_id])) {
                    if ($qty <= 0) {
                        unset($_SESSION['cart'][$v_id]); // Nếu giảm về 0 thì xóa luôn khỏi giỏ
                    } else {
                        if ($qty > $_SESSION['cart'][$v_id]['max_stock']) {
                            $_SESSION['cart'][$v_id]['quantity'] = $_SESSION['cart'][$v_id]['max_stock'];
                        } else {
                            $_SESSION['cart'][$v_id]['quantity'] = $qty;
                        }
                    }
                }
            }
            $_SESSION['success'] = "Đã cập nhật số lượng giỏ hàng!";
        }
        header('Location: gio-hang.php');
        exit;
    }

    if ($action === 'delete' && isset($_GET['variant_id'])) {
        $v_id = intval($_GET['variant_id']);
        if (isset($_SESSION['cart'][$v_id])) {
            unset($_SESSION['cart'][$v_id]);
            $_SESSION['success'] = "Đã xóa sản phẩm khỏi giỏ hàng!";
        }
        header('Location: gio-hang.php');
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        unset($_SESSION['coupon']); // Xóa luôn mã giảm giá
        $_SESSION['success'] = "Đã dọn dẹp sạch giỏ hàng!";
        header('Location: gio-hang.php');
        exit;
    }

    if ($action === 'apply_coupon' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $coupon_code = trim($_POST['coupon_code'] ?? '');

        if (empty($coupon_code)) {
            $_SESSION['error'] = "Vui lòng nhập mã giảm giá!";
        } else {
            // Truy vấn kiểm tra mã giảm giá
            $stmt_cp = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND status = 1");
            $stmt_cp->execute([$coupon_code]);
            $coupon = $stmt_cp->fetch();

            if (!$coupon) {
                $_SESSION['error'] = "Mã giảm giá không hợp lệ hoặc đã bị khóa!";
            } else {
                $total_cart_money = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $total_cart_money += $item['price'] * $item['quantity'];
                }

                $today = date('Y-m-d');
                if (!empty($coupon['expiry_date']) && $coupon['expiry_date'] !== '0000-00-00' && $coupon['expiry_date'] < $today) {
                    $_SESSION['error'] = "Mã giảm giá này đã hết hạn sử dụng!";
                }
                elseif (!is_null($coupon['usage_limit']) && $coupon['used_count'] >= $coupon['usage_limit']) {
                    $_SESSION['error'] = "Mã giảm giá này đã hết lượt sử dụng!";
                }
                elseif ($total_cart_money < $coupon['min_order_amount']) {
                    $_SESSION['error'] = "Đơn hàng tối thiểu " . number_format($coupon['min_order_amount'], 0, ',', '.') . "đ mới có thể dùng mã này!";
                } else {
                    $_SESSION['coupon'] = [
                        'id'               => $coupon['id'],
                        'code'             => $coupon['code'],
                        'discount_type'    => $coupon['discount_type'],
                        'discount_value'   => $coupon['discount_value'],
                        'min_order_amount' => $coupon['min_order_amount']
                    ];
                    $_SESSION['success'] = "Áp dụng mã giảm giá thành công!";
                }
            }
        }
        header('Location: gio-hang.php');
        exit;
    }

    if ($action === 'remove_coupon') {
        unset($_SESSION['coupon']);
        $_SESSION['success'] = "Đã hủy áp dụng mã giảm giá!";
        header('Location: gio-hang.php');
        exit;
    }

} catch (PDOException $e) {
    die("Lỗi xử lý giỏ hàng: " . $e->getMessage());
}


include_once '../includes/header.php';
?>

<div class="container my-5">
    <h4 class="fw-bold text-uppercase mb-4 text-dark"><i class="fa-solid fa-basket-shopping me-2 text-danger"></i>Giỏ hàng của bạn</h4>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="text-center py-5 border rounded bg-white shadow-sm">
            <i class="fa-solid fa-bag-shopping text-muted mb-3" style="font-size: 60px;"></i>
            <h5 class="text-secondary fw-bold">Giỏ hàng của bạn đang trống!</h5>
            <p class="text-muted small">Hãy chọn cho mình một đôi giày thật ưng ý tại cửa hàng nhé.</p>
            <a href="cua-hang.php" class="btn btn-dark text-uppercase fw-bold btn-sm px-4 py-2 mt-2" style="background-color: #111;">Quay lại cửa hàng</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            
            <div class="col-lg-8">
                <form action="gio-hang.php?action=update" method="POST">
                    <div class="table-responsive border rounded bg-white shadow-sm p-3">
                        <table class="table table-borderless align-middle m-0">
                            <thead class="border-bottom" style="font-size: 13px; text-transform: uppercase;">
                                <tr class="text-muted">
                                    <th scope="col" style="width: 100px;">Hình ảnh</th>
                                    <th scope="col">Thông tin giày</th>
                                    <th scope="col" class="text-center" style="width: 120px;">Số lượng</th>
                                    <th scope="col" class="text-end" style="width: 140px;">Thành tiền</th>
                                    <th scope="col" class="text-center" style="width: 50px;">Xóa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_cart_money = 0; // Biến tích lũy tổng tiền cả giỏ hàng
                                foreach ($_SESSION['cart'] as $v_id => $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total_cart_money += $subtotal;
                                ?>
                                    <tr class="border-bottom-subtle">
                                        <td>
                                            <img src="../uploads/<?= !empty($item['image']) ? htmlspecialchars($item['image']) : 'default_shoe.jpg' ?>" class="img-fluid rounded border" alt="<?= htmlspecialchars($item['product_name']) ?>" style="max-height: 70px; object-fit: contain;" onerror="this.src='https://placehold.co/100x100?text=NMK'">
                                        </td>
                                        <td>
                                            <h6 class="fw-bold text-dark m-0 mb-1" style="font-size: 14px;"><?= htmlspecialchars($item['product_name']) ?></h6>
                                            <small class="text-muted d-block" style="font-size: 12px;">
                                                Phối màu: <?= htmlspecialchars($item['color']) ?> | Size: <?= htmlspecialchars($item['size']) ?>
                                            </small>
                                            <small class="text-danger fw-bold" style="font-size: 13px;"><?= number_format($item['price'], 0, ',', '.') ?> đ</small>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="update_qty[<?= $v_id ?>]" class="form-control form-control-sm text-center fw-bold" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['max_stock'] ?>" style="font-size: 13px;">
                                            <small class="text-muted text-nowrap" style="font-size: 10px;">Kho: <?= $item['max_stock'] ?></small>
                                        </td>
                                        <td class="text-end fw-bold text-dark" style="font-size: 14px;">
                                            <?= number_format($subtotal, 0, ',', '.') ?> đ
                                        </td>
                                        <td class="text-center">
                                            <a href="gio-hang.php?action=delete&variant_id=<?= $v_id ?>" class="text-muted hover-danger" onclick="return confirm('Xóa sản phẩm này khỏi giỏ hàng?');"><i class="fa-solid fa-trash-can"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
                            <a href="cua-hang.php" class="btn btn-outline-secondary btn-sm fw-bold text-uppercase" style="font-size: 11px;"><i class="fa-solid fa-arrow-left me-1"></i> Tiếp tục mua thêm</a>
                            <div>
                                <button type="submit" class="btn btn-outline-dark btn-sm fw-bold text-uppercase me-2" style="font-size: 11px;"><i class="fa-solid fa-rotate me-1"></i> Cập nhật giỏ hàng</button>
                                <a href="gio-hang.php?action=clear" class="btn btn-outline-danger btn-sm fw-bold text-uppercase" style="font-size: 11px;" onclick="return confirm('Bạn có chắc muốn xóa sạch toàn bộ sản phẩm trong giỏ hàng không?');"><i class="fa-solid fa-xmark me-1"></i> Xóa hết</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                
                <div class="card border rounded bg-white shadow-sm p-3 mb-3">
                    <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-dark" style="font-size: 14px;">
                        <i class="fa-solid fa-ticket text-warning me-1"></i> Mã giảm giá
                    </h6>

                    <?php if (isset($_SESSION['coupon'])): ?>
                        <div class="d-flex justify-content-between align-items-center p-2 border border-success bg-success-subtle rounded">
                            <div>
                                <strong class="text-success text-uppercase"><?= htmlspecialchars($_SESSION['coupon']['code']) ?></strong>
                                <small class="d-block text-muted" style="font-size: 11px;">
                                    Giảm: <?= $_SESSION['coupon']['discount_type'] === 'percent' ? $_SESSION['coupon']['discount_value'] . '%' : number_format($_SESSION['coupon']['discount_value'], 0, ',', '.') . 'đ' ?>
                                </small>
                            </div>
                            <a href="gio-hang.php?action=remove_coupon" class="btn btn-sm btn-outline-danger" style="font-size: 11px;">Hủy mã</a>
                        </div>
                    <?php else: ?>
                        <form action="gio-hang.php?action=apply_coupon" method="POST">
                            <div class="input-group">
                                <input type="text" name="coupon_code" class="form-control form-control-sm text-uppercase" placeholder="Nhập mã ưu đãi..." required>
                                <button class="btn btn-dark btn-sm fw-bold" type="submit" style="background-color: #111;">Áp dụng</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="card border rounded bg-white shadow-sm p-3">
                    <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-dark" style="font-size: 14px;">Tóm tắt đơn hàng</h6>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2 small text-secondary">
                        <span>Tổng số lượng:</span>
                        <span class="fw-bold text-dark"><?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?> đôi</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 small text-secondary">
                        <span>Tạm tính:</span>
                        <span class="fw-bold text-dark"><?= number_format($total_cart_money, 0, ',', '.') ?> đ</span>
                    </div>

                    <?php 
                    $discount_amount = 0;
                    if (isset($_SESSION['coupon'])) {
                        $cp = $_SESSION['coupon'];
                        if ($cp['discount_type'] === 'percent') {
                            $discount_amount = ($total_cart_money * $cp['discount_value']) / 100;
                        } else {
                            $discount_amount = $cp['discount_value'];
                        }
                        if ($discount_amount > $total_cart_money) {
                            $discount_amount = $total_cart_money;
                        }
                    }
                    $final_total = $total_cart_money - $discount_amount;
                    ?>

                    <?php if ($discount_amount > 0): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 small text-success">
                            <span>Mã giảm giá (<?= htmlspecialchars($_SESSION['coupon']['code']) ?>):</span>
                            <span class="fw-bold">-<?= number_format($discount_amount, 0, ',', '.') ?> đ</span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-3 small text-secondary">
                        <span>Phí vận chuyển:</span>
                        <span class="text-success fw-bold">Miễn phí</span>
                    </div>
                    
                    <hr class="border-secondary border-opacity-10 my-2">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-dark" style="font-size: 15px;">Tổng thanh toán:</span>
                        <span class="text-danger fw-bold fs-4"><?= number_format($final_total, 0, ',', '.') ?> đ</span>
                    </div>

                    <a href="thanh-toan.php" class="btn btn-dark btn-lg w-100 text-uppercase fw-bold py-3" style="background-color: #111; font-size: 14px;">
                        Tiến hành thanh toán <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                    
                    <div class="mt-3 text-center text-muted" style="font-size: 11px;">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> Bảo mật thông tin mua sắm tuyệt đối.
                    </div>
                </div>

            </div>

        </div>
    <?php endif; ?>
</div>

<?php 
include_once '../includes/footer.php'; 
?>