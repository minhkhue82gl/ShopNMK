<?php
require_once __DIR__ . '/../../../config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error'] = "Bạn không có quyền truy cập vào chức năng Quản lý người dùng!";
    header('Location: ' . BASE_URL . 'admin/index-admin.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullname = sanitize($_POST['fullname'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone    = sanitize($_POST['phone'] ?? '');
    $address  = sanitize($_POST['address'] ?? '');
    $role     = sanitize($_POST['role'] ?? 'customer');

    if (!empty($fullname) && !empty($email) && !empty($password)) {
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_check->execute([$email]);
            
            if ($stmt_check->fetch()) {
                $_SESSION['error'] = "Email '{$email}' đã tồn tại trong hệ thống!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, phone, address, role, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$fullname, $email, $hashed_password, $phone, $address, $role]);

                $_SESSION['success'] = "Tạo tài khoản '{$fullname}' thành công!";
                redirect(BASE_URL . 'admin/modules/nguoi-dung/index-nguoidung.php');
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi tạo tài khoản: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ Họ tên, Email và Mật khẩu!";
    }
}
?>

<div class="mb-4">
    <h3 class="fw-bold m-0 text-dark">Thêm Tài Khoản Người Dùng Mới</h3>
</div>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Họ và Tên <span class="text-danger">*</span></label>
                    <input type="text" name="fullname" class="form-control" required placeholder="VD: Nguyễn Văn A">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="VD: nguyenvana@gmail.com">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Mật khẩu khởi tạo <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="6" placeholder="Mật khẩu ít nhất 6 ký tự">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" placeholder="0901234567">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Phân quyền (Vai trò)</label>
                        <select name="role" class="form-select" required>
                            <option value="customer">Khách hàng (Customer)</option>
                            <option value="staff">Nhân viên (Staff)</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Địa chỉ giao hàng mặc định</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Số nhà, tên đường, Phường/Xã, Quận/Huyện..."></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="add_user" class="btn btn-warning text-white w-100 py-2.5 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Tài Khoản
                    </button>
                    <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/index-nguoidung.php" class="btn btn-light border w-50 py-2.5 fw-bold">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>