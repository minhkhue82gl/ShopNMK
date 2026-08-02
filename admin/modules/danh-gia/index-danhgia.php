<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $review_id  = (int)($_GET['id'] ?? 0);
    $new_status = (int)($_GET['status'] ?? 0);

    if ($review_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE reviews SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $review_id]);
            $_SESSION['success'] = ($new_status === 1) ? "Đã duyệt và hiển thị đánh giá!" : "Đã ẩn đánh giá!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi cập nhật: " . $e->getMessage();
        }
    }
    redirect(BASE_URL . 'admin/modules/danh-gia/index-danhgia.php');
}

// Lọc theo trạng thái duyệt
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Sửa p.name thành p.product_name cho đúng với cấu trúc cơ sở dữ liệu
$sql = "SELECT r.*, p.product_name, p.image_url, u.fullname, u.email 
        FROM reviews r 
        JOIN products p ON r.product_id = p.id 
        JOIN users u ON r.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($status_filter === 'pending') {
    $sql .= " AND r.status = 0";
} elseif ($status_filter === 'approved') {
    $sql .= " AND r.status = 1";
}

$sql .= " ORDER BY r.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-star brand-orange me-2"></i> Quản Lý & Duyệt Đánh Giá
        </h3>
        <p class="text-muted small m-0 mt-1">Kiểm duyệt các phản hồi và số sao đánh giá từ khách hàng mua giày</p>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="mb-3 d-flex gap-2">
    <a href="<?= BASE_URL ?>admin/modules/danh-gia/index-danhgia.php?status=all" 
       class="btn btn-sm <?= $status_filter === 'all' ? 'btn-dark fw-bold' : 'btn-light border' ?>">
        Tất Cả Đánh Giá
    </a>
    <a href="<?= BASE_URL ?>admin/modules/danh-gia/index-danhgia.php?status=pending" 
       class="btn btn-sm <?= $status_filter === 'pending' ? 'btn-warning fw-bold text-dark' : 'btn-light border' ?>">
        <i class="fa-solid fa-clock me-1"></i> Chờ Duyệt
    </a>
    <a href="<?= BASE_URL ?>admin/modules/danh-gia/index-danhgia.php?status=approved" 
       class="btn btn-sm <?= $status_filter === 'approved' ? 'btn-success fw-bold' : 'btn-light border' ?>">
        <i class="fa-solid fa-circle-check me-1"></i> Đã Duyệt (Hiển Thị)
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <?php if (empty($reviews)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-comment-slash fs-1 mb-2 d-block"></i>
                Chưa có đánh giá nào phù hợp.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small fw-bold text-center">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th class="text-start ps-3" style="width: 25%;">Sản Phẩm</th>
                            <th class="text-start" style="width: 20%;">Khách Hàng</th>
                            <th style="width: 12%;">Đánh Giá</th>
                            <th class="text-start" style="width: 23%;">Nội Dung Bình Luận</th>
                            <th style="width: 10%;">Trạng Thái</th>
                            <th class="pe-3" style="width: 5%;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $row): ?>
                            <tr>
                                <td class="text-center font-monospace text-secondary">#<?= $row['id'] ?></td>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($row['image_url']) && file_exists(UPLOAD_DIR . 'products/' . $row['image_url'])): ?>
                                            <img src="<?= BASE_URL . 'assets/uploads/products/' . $row['image_url'] ?>" class="rounded border" style="width: 42px; height: 42px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div class="fw-bold text-dark small text-truncate" style="max-width: 180px;" title="<?= sanitize($row['product_name']) ?>">
                                            <?= sanitize($row['product_name']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark small"><?= sanitize($row['fullname']) ?></div>
                                    <small class="text-muted d-block"><?= sanitize($row['email']) ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="text-warning small">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fa-<?= $i <= $row['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted d-block mt-1"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="small text-secondary fst-italic">
                                        "<?= nl2br(sanitize($row['comment'])) ?>"
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if (isset($row['status']) && $row['status'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1.5 fw-bold">
                                            <i class="fa-solid fa-circle-check me-1"></i>Đã duyệt
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-dark px-2.5 py-1.5 fw-bold">
                                            <i class="fa-solid fa-clock me-1"></i>Chờ duyệt
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 text-center">
                                    <div class="btn-group shadow-sm" role="group">
                                        <?php if (!isset($row['status']) || $row['status'] == 0): ?>
                                            <a href="<?= BASE_URL ?>admin/modules/danh-gia/index-danhgia.php?action=toggle_status&id=<?= $row['id'] ?>&status=1" 
                                               class="btn btn-success btn-sm fw-bold" title="Duyệt đánh giá này">
                                                <i class="fa-solid fa-check"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>admin/modules/danh-gia/index-danhgia.php?action=toggle_status&id=<?= $row['id'] ?>&status=0" 
                                               class="btn btn-outline-warning btn-sm" title="Ẩn đánh giá này">
                                                <i class="fa-solid fa-eye-slash"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= BASE_URL ?>admin/modules/danh-gia/delete-danhgia.php?id=<?= $row['id'] ?>" 
                                           class="btn btn-outline-danger btn-sm" title="Xóa vĩnh viễn"
                                           onclick="return confirm('Bạn có chắc muốn XÓA bình luận này?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
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