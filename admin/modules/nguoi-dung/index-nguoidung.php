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

if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $user_id    = (int)($_GET['id'] ?? 0);
    $new_status = (int)($_GET['status'] ?? 1);

    if ($user_id === ($_SESSION['user']['id'] ?? 0)) {
        $_SESSION['error'] = "Bạn không thể tự khóa tài khoản của chính mình!";
    } else if ($user_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            $_SESSION['success'] = ($new_status === 1) ? "Đã mở khóa tài khoản thành công!" : "Đã khóa tài khoản thành công!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi cập nhật trạng thái: " . $e->getMessage();
        }
    }
    redirect(BASE_URL . 'admin/modules/nguoi-dung/index-nguoidung.php');
}

// Lọc theo vai trò
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : 'all';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (in_array($role_filter, ['admin', 'staff', 'customer'])) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-users-gear brand-orange me-2"></i> Quản Lý Người Dùng & Khách Hàng
        </h3>
        <p class="text-muted small m-0 mt-1">Phân quyền tài khoản (Admin, Nhân viên, Khách hàng) và quản lý trạng thái hoạt động</p>
    </div>
    <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/add-nguoidung.php" class="btn btn-warning text-white fw-bold px-3 py-2 rounded-3 shadow-sm">
        <i class="fa-solid fa-user-plus me-1"></i> Thêm Tài Khoản Mới
    </a>
</div>

<!-- Bộ lọc Vai trò -->
<div class="mb-3 d-flex gap-2">
    <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/index-nguoidung.php?role=all" 
       class="btn btn-sm <?= $role_filter === 'all' ? 'btn-dark fw-bold' : 'btn-light border' ?>">
        Tất Cả
    </a>
    <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/index-nguoidung.php?role=admin" 
       class="btn btn-sm <?= $role_filter === 'admin' ? 'btn-danger fw-bold' : 'btn-light border' ?>">
        <i class="fa-solid fa-user-shield me-1"></i> Quản Trị Viên (Admin)
    </a>
    <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/index-nguoidung.php?role=staff" 
       class="btn btn-sm <?= $role_filter === 'staff' ? 'btn-primary fw-bold' : 'btn-light border' ?>">
        <i class="fa-solid fa-user-tie me-1"></i> Nhân Viên (Staff)
    </a>
    <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/index-nguoidung.php?role=customer" 
       class="btn btn-sm <?= $role_filter === 'customer' ? 'btn-success fw-bold' : 'btn-light border' ?>">
        <i class="fa-solid fa-user me-1"></i> Khách Hàng (Customer)
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-user-slash fs-1 mb-2 d-block"></i>
                Không tìm thấy tài khoản người dùng nào.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-secondary small fw-bold">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th class="text-start ps-3">Họ và Tên</th>
                            <th class="text-start">Email / Số Điện Thoại</th>
                            <th>Vai Trò</th>
                            <th>Trạng Thái</th>
                            <th>Ngày Tạo</th>
                            <th class="pe-3">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $row): 
                            $status = $row['status'] ?? 1;
                        ?>
                            <tr>
                                <td class="font-monospace text-secondary">#<?= $row['id'] ?></td>
                                <td class="text-start ps-3 fw-bold text-dark">
                                    <?= sanitize($row['fullname']) ?>
                                    <?php if ($row['id'] == ($_SESSION['user']['id'] ?? 0)): ?>
                                        <span class="badge bg-warning text-dark ms-1">Bạn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-start">
                                    <div class="small fw-semibold"><?= sanitize($row['email']) ?></div>
                                    <small class="text-muted"><?= !empty($row['phone']) ? sanitize($row['phone']) : 'Chưa cập nhật SĐT' ?></small>
                                </td>
                                <td>
                                    <?php if ($row['role'] === 'admin'): ?>
                                        <span class="badge bg-danger px-2.5 py-1.5"><i class="fa-solid fa-user-shield me-1"></i>Admin</span>
                                    <?php elseif ($row['role'] === 'staff'): ?>
                                        <span class="badge bg-primary px-2.5 py-1.5"><i class="fa-solid fa-user-tie me-1"></i>Nhân viên</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-2.5 py-1.5"><i class="fa-solid fa-user me-1"></i>Khách hàng</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1.5 fw-bold">
                                            <i class="fa-solid fa-circle-check me-1"></i>Hoạt động
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-2.5 py-1.5 fw-bold">
                                            <i class="fa-solid fa-lock me-1"></i>Bị khóa
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                <td class="pe-3">
                                    <div class="btn-group shadow-sm" role="group">
                                        <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/edit-nguoidung.php?id=<?= $row['id'] ?>" 
                                           class="btn btn-outline-dark btn-sm fw-bold" title="Chỉnh sửa / Phân quyền">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <?php if ($row['id'] != ($_SESSION['user']['id'] ?? 0)): ?>
                                            <?php if ($status == 1): ?>
                                                <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/index-nguoidung.php?action=toggle_status&id=<?= $row['id'] ?>&status=0" 
                                                   class="btn btn-outline-danger btn-sm" title="Khóa tài khoản"
                                                   onclick="return confirm('Bạn có chắc muốn KHÓA tài khoản <?= sanitize($row['fullname']) ?>?')">
                                                    <i class="fa-solid fa-lock"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>admin/modules/nguoi-dung/index-nguoidung.php?action=toggle_status&id=<?= $row['id'] ?>&status=1" 
                                                   class="btn btn-outline-success btn-sm" title="Mở khóa tài khoản"
                                                   onclick="return confirm('Mở khóa cho tài khoản <?= sanitize($row['fullname']) ?>?')">
                                                    <i class="fa-solid fa-lock-open"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>