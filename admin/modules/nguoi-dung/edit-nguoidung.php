<?php
require_once __DIR__ . '/../../../config.php';

// Kiểm tra quyền: Chỉ cho phép Quản trị viên (Admin) truy cập, chặn Nhân viên (Staff)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error'] = "Bạn không có quyền truy cập hoặc thao tác trong module Quản lý người dùng!";
    header('Location: ' . BASE_URL . 'admin/index-admin.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Tài khoản không tồn tại!";
    redirect(BASE_URL . 'admin/modules/nguoi-dung/index-nguoidung.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $fullname     = sanitize($_POST['fullname'] ?? '');
    $phone        = sanitize($_POST['phone'] ?? '');
    $address      = sanitize($_POST['address'] ?? '');
    $role         = sanitize($_POST['role'] ?? 'customer');
    $status       = (int)($_POST['status'] ?? 1);
    $new_password = $_POST['new_password'] ?? '';

    if (!empty($fullname)) {
        try {
            // Cập nhật thông tin cơ bản
            $sql_up = "UPDATE users SET fullname = ?, phone = ?, address = ?, role = ?, status = ? WHERE id = ?";
            $stmt_up = $pdo->prepare($sql_up);
            $stmt_up->execute([$fullname, $phone, $address, $role, $status, $id]);

            // Cập nhật mật khẩu nếu người dùng nhập mật khẩu mới
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt_pw = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_pw->execute([$hashed_password, $id]);
            }

            $_SESSION['success'] = "Cập nhật thông tin tài khoản thành công!";
            redirect(BASE_URL . 'admin/modules/nguoi-dung/index-nguoidung.php');

        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi cập nhật: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Vui lòng nhập Họ và Tên!";
    }
}
?>

<div class="mb-4">
    <h3 class="fw-bold m-0 text-dark">Chỉnh Sửa Tài Khoản #<?= $user['id'] ?></h3>
</div>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Email (Không thể sửa)</label>
                    <input type="email" class="form-control bg-light" value="<?= sanitize($user['email']) ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Họ và Tên <span class="text-danger">*</span></label>
                    <input type="text" name="fullname" class="form-control" value="<?= sanitize($user['fullname']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Phân quyền (Vai trò)</label>
                        <select name="role" class="form-select" <?= $user['id'] == ($_SESSION['user']['id'] ?? 0) ? 'disabled' : '' ?>>
                            <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Khách hàng (Customer)</option>
                            <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Nhân viên (Staff)</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                        </select>
                        <?php if ($user['id'] == ($_SESSION['user']['id'] ?? 0)): ?>
                            <input type="hidden" name="role" value="<?= $user['role'] ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Trạng thái tài khoản</label>
                    <select name="status" class="form-select" <?= $user['id'] == ($_SESSION['user']['id'] ?? 0) ? 'disabled' : '' ?>>
                        <option value="1" <?= ($user['status'] ?? 1) == 1 ? 'selected' : '' ?>>Hoạt động bình thường</option>
                        <option value="0" <?= ($user['status'] ?? 1) == 0 ? 'selected' : '' ?>>Bị khóa (Banned)</option>
                    </select>
                    <?php if ($user['id'] == ($_SESSION['user']['id'] ?? 0)): ?>
                        <input type="hidden" name="status" value="<?= $user['status'] ?? 1 ?>">
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Mật khẩu mới (Để trống nếu giữ nguyên)</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Đổi mật khẩu mới...">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Địa chỉ giao hàng</label>
                    <textarea name="address" class="form-control" rows="3"><?= sanitize($user['address'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="update_user" class="btn btn-warning text-white w-100 py-2.5 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Thay Đổi
                    </button>
                    <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/index-nguoidung.php" class="btn btn-light border w-50 py-2.5 fw-bold">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>