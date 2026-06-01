<?php
// 1. Nhúng file kết nối CSDL và khởi động Session
require_once '../includes/conn.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// BẢO VỆ CHỐNG LỖI: Nếu tồn tại Session nhưng sai cấu trúc (là chuỗi thay vì mảng), tự động dọn dẹp
if (isset($_SESSION['user']) && !is_array($_SESSION['user'])) {
    unset($_SESSION['user']);
}

// Nếu người dùng đã đăng nhập chuẩn chỉnh từ trước, lập tức chuyển hướng về trang chủ
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error_msg = '';

// ================= LUỒNG XỬ LÝ: TIẾP NHẬN FORM ĐĂNG NHẬP =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            // Truy vấn thông tin người dùng từ bảng users theo username
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Nếu tìm thấy tài khoản, tiến hành so sánh mã hóa mật khẩu bằng password_verify
            if ($user && password_verify($password, $user['password'])) {
                
                // KHỞI TẠO MẢNG SESSION CHUẨN: Bắt buộc là một MẢNG (Array) đầy đủ cặp Key => Value
                $_SESSION['user'] = [
                    'id'        => (int)$user['id'],
                    'username'  => $user['username'],
                    'fullname'  => $user['fullname'],
                    'email'     => $user['email'],
                    'phone'     => $user['phone'],
                    'address'   => $user['address'],
                    'role'      => $user['role'] // 'admin', 'staff', 'customer'
                ];

                // Kiểm tra phân quyền (Role) để điều hướng trang đích phù hợp
                if ($user['role'] === 'admin' || $user['role'] === 'staff') {
                    // Nếu là Admin hoặc Nhân viên, chuyển hướng thẳng vào khu vực quản trị Backend
                    header('Location: ../admin/dashboard.php');
                } else {
                    // Nếu là Khách hàng, quay trở lại trang chủ mua sắm Frontend
                    header('Location: index.php');
                }
                exit;

            } else {
                $error_msg = "Tên đăng nhập hoặc mật khẩu của bạn không chính xác!";
            }
        } catch (PDOException $e) {
            $error_msg = "Lỗi hệ thống CSDL: " . $e->getMessage();
        }
    } else {
        $error_msg = "Vui lòng điền đầy đủ cả tên tài khoản và mật khẩu!";
    }
}

// Nhúng thanh điều hướng Header dùng chung
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="card border rounded bg-white shadow-sm p-4">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-uppercase text-dark m-0">Đăng nhập hệ thống</h4>
                    <small class="text-muted" style="font-size: 12px;">Chào mừng bạn đến với NMK Shoes Shop</small>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger p-2 small mb-3 text-center" style="font-size: 13px;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <form action="dang-nhap.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Tên tài khoản (Username) *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-user" style="font-size: 13px;"></i></span>
                            <input type="text" name="username" class="form-control border-start-0 ps-0 text-dark small" placeholder="Nhập tài khoản của bạn..." value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required>
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
                <p class="m-0 text-secondary">• <strong>Tài khoản Khách:</strong> `khachhang1` | Mật khẩu: `123456`</p>
                <p class="m-0 text-secondary">• <strong>Tài khoản Admin:</strong> `admin_nmk` | Mật khẩu: `123456`</p>
            </div>

        </div>
    </div>
</div>

<?php 
include_once '../includes/footer.php'; 
?>