<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var PDO $conn */ 
require_once '../includes/conn.php';

if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_dang_ky'])) {
    $username    = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';
    $re_password = $_POST['re_password'] ?? '';
    $fullname    = trim($_POST['fullname'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $address     = trim($_POST['address'] ?? '');

    if (empty($username) || empty($password) || empty($fullname) || empty($email)) {
        $error = "Vui lòng điền đầy đủ các thông tin có dấu (*)!";
    } elseif ($password !== $re_password) {
        $error = "Mật khẩu nhập lại không trùng khớp!";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải chứa ít nhất 6 ký tự!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Định dạng Email không hợp lệ!";
    } else {
        try {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->execute([$username, $email]);

            if ($stmt_check->rowCount() > 0) {
                $error = "Tên đăng nhập hoặc Email này đã được sử dụng!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $phone_val   = !empty($phone) ? $phone : null;
                $address_val = !empty($address) ? $address : null;

                $sql = "INSERT INTO users (username, password, fullname, email, phone, address, role, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'customer', 1)";
                
                $stmt_insert = $conn->prepare($sql);
                $stmt_insert->execute([
                    $username, 
                    $hashed_password, 
                    $fullname, 
                    $email, 
                    $phone_val, 
                    $address_val
                ]);

                $success = "Tạo tài khoản thành công! Đang chuyển hướng sang trang đăng nhập...";
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'dang-nhap.php';
                    }, 2000);
                </script>";
            }
        } catch (PDOException $e) {
            $error = "Đã xảy ra lỗi hệ thống: " . $e->getMessage();
        }
    }
}

include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border border-light-subtle rounded-3">
                <div class="card-body p-4">
                    <h3 class="text-center fw-bold mb-4 text-uppercase text-dark">Đăng Ký Tài Khoản</h3>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger p-2 small" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success p-2 small" role="alert">
                            <i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <form action="dang-ky.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tên đăng nhập *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="Nhập tên tài khoản" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Mật khẩu *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Nhập lại mật khẩu *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-key"></i></span>
                                    <input type="password" name="re_password" class="form-control" placeholder="Xác nhận lại" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Họ và tên của bạn *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" name="fullname" class="form-control" placeholder="Ví dụ: Nguyễn Minh Khôi" value="<?= isset($fullname) ? htmlspecialchars($fullname) : '' ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Địa chỉ Email *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="username@gmail.com" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Số điện thoại</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-phone"></i></span>
                                <input type="text" name="phone" class="form-control" placeholder="Nhập số di động nhận hàng" value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Địa chỉ giao hàng</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-map-location-dot"></i></span>
                                <textarea name="address" class="form-control" rows="2" placeholder="Số nhà, tên đường, phường/xã, quận/huyện..."><?= isset($address) ? htmlspecialchars($address) : '' ?></textarea>
                            </div>
                        </div>

                        <button type="submit" name="btn_dang_ky" class="btn btn-dark w-100 fw-bold py-2 text-uppercase mb-3" style="background-color: #111;">
                            <i class="fa-solid fa-user-plus me-1"></i> Tạo tài khoản mới
                        </button>

                        <div class="text-center small text-muted">
                            Bạn đã có sẵn tài khoản? <a href="dang-nhap.php" class="text-danger fw-bold text-decoration-none">Đăng nhập ngay</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>