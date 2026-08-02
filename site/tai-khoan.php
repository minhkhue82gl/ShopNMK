<?php
require_once '../includes/conn.php';
/** @var PDO $conn */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: dang-nhap.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$error_info = '';
$success_info = '';
$error_pass = '';
$success_pass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_info'])) {
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);

    if (empty($fullname) || empty($email)) {
        $error_info = "Họ tên và Email không được để trống!";
    } else {
        try {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check->execute([$email, $user_id]);

            if ($stmt_check->rowCount() > 0) {
                $error_info = "Email này đã được sử dụng bởi tài khoản khác!";
            } else {
                $stmt_up = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt_up->execute([$fullname, $email, $phone, $address, $user_id]);

                $_SESSION['user']['fullname'] = $fullname;
                $_SESSION['user']['email']    = $email;
                $_SESSION['user']['phone']    = $phone;
                $_SESSION['user']['address']  = $address;

                $success_info = "Cập nhật thông tin cá nhân thành công!";
            }
        } catch (PDOException $e) {
            $error_info = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_change_pass'])) {
    $current_pass = $_POST['current_pass'];
    $new_pass     = $_POST['new_pass'];
    $re_new_pass  = $_POST['re_new_pass'];

    if (empty($current_pass) || empty($new_pass) || empty($re_new_pass)) {
        $error_pass = "Vui lòng nhập đầy đủ thông tin đổi mật khẩu!";
    } elseif ($new_pass !== $re_new_pass) {
        $error_pass = "Mật khẩu mới nhập lại không khớp!";
    } elseif (strlen($new_pass) < 6) {
        $error_pass = "Mật khẩu mới phải có ít nhất 6 ký tự!";
    } else {
        try {
            $stmt_user = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt_user->execute([$user_id]);
            $db_pass = $stmt_user->fetchColumn();

            if (password_verify($current_pass, $db_pass)) {
                $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_pass->execute([$hashed_new_pass, $user_id]);

                $success_pass = "Đổi mật khẩu thành công!";
            } else {
                $error_pass = "Mật khẩu hiện tại không chính xác!";
            }
        } catch (PDOException $e) {
            $error_pass = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}

$stmt_curr = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_curr->execute([$user_id]);
$account_info = $stmt_curr->fetch();

include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row g-4">
        <!-- Sidebar Menu Hồ Sơ -->
        <div class="col-md-4 col-lg-3">
            <div class="card border rounded-3 shadow-sm bg-white p-3">
                <div class="text-center mb-3 pb-3 border-bottom">
                    <div class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px; font-size: 24px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h6 class="fw-bold m-0 text-dark"><?= htmlspecialchars($account_info['fullname']) ?></h6>
                    <small class="text-muted">@<?= htmlspecialchars($account_info['username']) ?></small>
                </div>
                
                <div class="nav flex-column nav-pills gap-1">
                    <a class="nav-link active fw-bold bg-dark text-white" href="tai-khoan.php">
                        <i class="fa-solid fa-user-gear me-2"></i> Thông tin tài khoản
                    </a>
                    <a class="nav-link text-secondary" href="lich-su-don-hang.php">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i> Lịch sử đơn hàng
                    </a>
                    <a class="nav-link text-danger" href="dang-xuat.php">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-lg-9">
            
            <div class="card border rounded-3 shadow-sm bg-white p-4 mb-4">
                <h5 class="fw-bold text-dark border-bottom pb-3 mb-4">
                    <i class="fa-solid fa-id-card-clip text-warning me-2"></i> Hồ Sơ Cá Nhân
                </h5>

                <?php if (!empty($error_info)): ?>
                    <div class="alert alert-danger p-2 small"><?= htmlspecialchars($error_info) ?></div>
                <?php endif; ?>
                <?php if (!empty($success_info)): ?>
                    <div class="alert alert-success p-2 small"><?= htmlspecialchars($success_info) ?></div>
                <?php endif; ?>

                <form action="tai-khoan.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tên đăng nhập</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($account_info['username']) ?>" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Họ và tên *</label>
                            <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($account_info['fullname']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($account_info['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($account_info['phone']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Địa chỉ nhận hàng</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($account_info['address']) ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="btn_update_info" class="btn btn-dark fw-bold px-4 py-2 mt-3" style="background-color: #111;">
                        Lưu thông tin
                    </button>
                </form>
            </div>

            <div class="card border rounded-3 shadow-sm bg-white p-4">
                <h5 class="fw-bold text-dark border-bottom pb-3 mb-4">
                    <i class="fa-solid fa-key text-danger me-2"></i> Đổi Mật Khẩu
                </h5>

                <?php if (!empty($error_pass)): ?>
                    <div class="alert alert-danger p-2 small"><?= htmlspecialchars($error_pass) ?></div>
                <?php endif; ?>
                <?php if (!empty($success_pass)): ?>
                    <div class="alert alert-success p-2 small"><?= htmlspecialchars($success_pass) ?></div>
                <?php endif; ?>

                <form action="tai-khoan.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mật khẩu hiện tại *</label>
                            <input type="password" name="current_pass" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mật khẩu mới *</label>
                            <input type="password" name="new_pass" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Xác nhận mật khẩu mới *</label>
                            <input type="password" name="re_new_pass" class="form-control" placeholder="Nhập lại mật khẩu mới" required>
                        </div>
                    </div>
                    <button type="submit" name="btn_change_pass" class="btn btn-outline-danger fw-bold px-4 py-2 mt-3">
                        Cập nhật mật khẩu
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>