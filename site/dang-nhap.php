<?php
require_once '../includes/conn.php';
/** @var PDO $conn */ 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user']) && !is_array($_SESSION['user'])) {
    unset($_SESSION['user']);
}

if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $login_input = trim($_POST['username']); // Biến này có thể là username hoặc email
    $password = trim($_POST['password']);

    if (!empty($login_input) && !empty($password)) {
        try {
            if (isset($conn) && $conn instanceof PDO) {
                // SỬA ĐỔI TẠI ĐÂY: Cho phép tìm kiếm theo username HOẶC email
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$login_input, $login_input]);
                $user = $stmt->fetch();

                // Kiểm tra tài khoản có tồn tại, đúng mật khẩu và tài khoản đang hoạt động (status = 1)
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] == 0) {
                        $error_msg = "Tài khoản của bạn đã bị khóa!";
                    } else {
                        $_SESSION['user'] = [
                            'id'        => (int)$user['id'],
                            'username'  => $user['username'],
                            'fullname'  => $user['fullname'],
                            'email'     => $user['email'],
                            'phone'     => $user['phone'],
                            'address'   => $user['address'],
                            'role'      => $user['role']
                        ];

                        if ($user['role'] === 'admin' || $user['role'] === 'staff') {
                            header('Location: ../admin/index-admin.php');
                        } else {
                            header('Location: index.php');
                        }
                        exit;
                    }
                } else {
                    $error_msg = "Tên đăng nhập/Email hoặc mật khẩu không chính xác!";
                }
            } else {
                $error_msg = "Lỗi kết nối cơ sở dữ liệu!";
            }
        } catch (PDOException $e) {
            $error_msg = "Lỗi hệ thống CSDL: " . $e->getMessage();
        }
    } else {
        $error_msg = "Vui lòng điền đầy đủ thông tin đăng nhập và mật khẩu!";
    }
}

include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="card border rounded bg-white shadow-sm p-4">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-uppercase text-dark m-0">Đăng nhập hệ thống</h4>
                    <small class="text-muted" style="font-size: 12px;">Chào mừng bạn đến với NMK SHOP</small>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger p-2 small mb-3 text-center" style="font-size: 13px;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error_msg) ?>
                    </div>
                <?php endif; ?>

                <form action="dang-nhap.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Tên tài khoản hoặc Email *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-user" style="font-size: 13px;"></i></span>
                            <!-- Đổi placeholder cho phù hợp -->
                            <input type="text" name="username" class="form-control border-start-0 ps-0 text-dark small" placeholder="Nhập username hoặc email..." value="<?= isset($login_input) ? htmlspecialchars($login_input) : '' ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Mật khẩu truy cập *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-lock" style="font-size: 13px;"></i></span>
                            <input type="password" name="password" class="form-control border-start-0 ps-0 text-dark small" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4" style="font-size: 13px;">
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label text-muted" for="rememberMe">Ghi nhớ</label>
                        </div>
                        <a href="quen-mat-khau.php" class="text-decoration-none text-danger fw-medium">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" name="login_btn" class="btn btn-dark w-100 text-uppercase fw-bold py-2 mb-3" style="background-color: #111; font-size: 13px;">
                        Đăng nhập ngay <i class="fa-solid fa-right-to-bracket ms-1"></i>
                    </button>

                    <div class="text-center mt-2 small text-muted" style="font-size: 13px;">
                        Bạn chưa có tài khoản mua sắm? 
                        <a href="dang-ky.php" class="text-decoration-none text-primary fw-bold">Đăng ký thành viên mới</a>
                    </div>
                </form>
            </div>

            <div class="card mt-4 border border-warning-subtle bg-warning bg-opacity-10 p-3 rounded text-dark" style="font-size: 12px;">
                <h6 class="fw-bold m-0 mb-1 text-warning-emphasis"><i class="fa-solid fa-circle-info me-1"></i> Tài khoản mẫu kiểm thử:</h6>
                <p class="m-0 text-secondary">• <strong>Admin:</strong> `admin_nmk` hoặc `admin@nmkshoes.com` | Mật khẩu: `123456`</p>
                <p class="m-0 text-secondary">• <strong>Khách:</strong> `khachhang1` hoặc `khach1@gmail.com` | Mật khẩu: `123456`</p>
            </div>

        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>