<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

$sql = "SELECT o.*, COALESCE(SUM(od.quantity), 0) AS total_items 
        FROM orders o 
        LEFT JOIN order_details od ON o.id = od.order_id 
        WHERE 1=1";
$params = [];

if (in_array($status_filter, ['Chờ xác nhận', 'Đang xử lý', 'Đang giao', 'Đã giao', 'Đã hủy'])) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

$sql .= " GROUP BY o.id ORDER BY o.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$status_badges = [
    'Chờ xác nhận' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-clock'],
    'Đang xử lý'   => ['class' => 'bg-info text-white', 'icon' => 'fa-box-archive'],
    'Đang giao'     => ['class' => 'bg-primary text-white', 'icon' => 'fa-truck-fast'],
    'Đã giao'       => ['class' => 'bg-success text-white', 'icon' => 'fa-circle-check'],
    'Đã hủy'        => ['class' => 'bg-danger text-white', 'icon' => 'fa-circle-xmark']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-cart-shopping brand-orange me-2"></i> Quản Lý Đơn Hàng - Shop Giày NMK
        </h3>
        <p class="text-muted small m-0 mt-1">Theo dõi quy trình xử lý đơn hàng và cập nhật trạng thái vận chuyển</p>
    </div>
</div>

<div class="mb-3 d-flex flex-wrap gap-2">
    <a href="index-donhang.php?status=all" 
       class="btn btn-sm <?= $status_filter === 'all' ? 'btn-dark fw-bold' : 'btn-light border' ?>">Tất Cả Đơn</a>
    <a href="index-donhang.php?status=Chờ xác nhận" 
       class="btn btn-sm <?= $status_filter === 'Chờ xác nhận' ? 'btn-warning fw-bold text-dark' : 'btn-light border' ?>">
        <i class="fa-solid fa-clock me-1"></i> Chờ Xác Nhận
    </a>
    <a href="index-donhang.php?status=Đang xử lý" 
       class="btn btn-sm <?= $status_filter === 'Đang xử lý' ? 'btn-info fw-bold text-white' : 'btn-light border' ?>">
        <i class="fa-solid fa-box-archive me-1"></i> Đang Xử Lý
    </a>
    <a href="index-donhang.php?status=Đang giao" 
       class="btn btn-sm <?= $status_filter === 'Đang giao' ? 'btn-primary fw-bold' : 'btn-light border' ?>">
        <i class="fa-solid fa-truck-fast me-1"></i> Đang Giao
    </a>
    <a href="index-donhang.php?status=Đã giao" 
       class="btn btn-sm <?= $status_filter === 'Đã giao' ? 'btn-success fw-bold' : 'btn-light border' ?>">
        <i class="fa-solid fa-circle-check me-1"></i> Đã Giao
    </a>
    <a href="index-donhang.php?status=Đã hủy" 
       class="btn btn-sm <?= $status_filter === 'Đã hủy' ? 'btn-danger fw-bold' : 'btn-light border' ?>">
        <i class="fa-solid fa-circle-xmark me-1"></i> Đã Hủy
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-inbox fs-1 mb-2 d-block"></i>
                Không tìm thấy đơn hàng nào phù hợp với bộ lọc.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-secondary small fw-bold">
                        <tr>
                            <th>Mã Đơn</th>
                            <th class="text-start ps-3">Khách Hàng</th>
                            <th>Số Sản Phẩm</th>
                            <th>Tổng Tiền</th>
                            <th>Thanh Toán</th>
                            <th>Trạng Thái</th>
                            <th>Ngày Đặt</th>
                            <th class="pe-3">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $row): 
                            $curr_st = !empty($row['status']) ? $row['status'] : 'Chờ xác nhận';
                            $st = $status_badges[$curr_st] ?? ['class' => 'bg-secondary text-white', 'icon' => 'fa-question'];
                        ?>
                            <tr>
                                <td class="fw-bold font-monospace text-dark">#NMK-<?= $row['id'] ?></td>
                                <td class="text-start ps-3">
                                    <div class="fw-bold text-dark"><?= sanitize($row['fullname']) ?></div>
                                    <small class="text-muted"><?= sanitize($row['phone']) ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $row['total_items'] ?> món</span></td>
                                <td class="fw-bold text-danger"><?= format_money($row['total_price']) ?></td>
                                <td><span class="badge bg-secondary"><?= strtoupper(sanitize($row['payment_method'])) ?></span></td>
                                <td>
                                    <span class="badge <?= $st['class'] ?> px-2.5 py-1.5 fw-semibold">
                                        <i class="fa-solid <?= $st['icon'] ?> me-1"></i><?= $curr_st ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="pe-3">
                                    <a href="detail-donhang.php?id=<?= $row['id'] ?>" 
                                       class="btn btn-warning btn-sm text-white fw-bold shadow-sm">
                                        <i class="fa-solid fa-eye me-1"></i> Chi Tiết
                                    </a>
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