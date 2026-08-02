<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Khởi tạo trước biến $low_stock_items dạng mảng rỗng để tránh lỗi Fatal Error count() khi SQL gặp sự cố
$low_stock_items = [];
$total_revenue = 0;
$pending_orders = 0;
$total_products = 0;

try {
    $stmt_rev = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status = 'Đã giao'");
    $total_revenue = $stmt_rev->fetchColumn() ?? 0;

    $stmt_ord = $pdo->query("SELECT COUNT(id) FROM orders WHERE status = 'Chờ xác nhận'");
    $pending_orders = $stmt_ord->fetchColumn() ?? 0;

    $stmt_prod = $pdo->query("SELECT COUNT(id) FROM products");
    $total_products = $stmt_prod->fetchColumn() ?? 0;

   $stmt_stock = $pdo->query("SELECT pv.*, p.product_name 
                            FROM product_variants pv 
                            INNER JOIN products p ON pv.product_id = p.id 
                            WHERE pv.stock <= 5 
                            ORDER BY pv.stock ASC");
$low_stock_items = $stmt_stock->fetchAll();

} catch (PDOException $e) {
    echo '<div class="alert alert-danger m-3"><i class="fa-solid fa-triangle-exclamation me-2"></i>Lỗi kết xuất dữ liệu báo cáo: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0 text-dark">
        <i class="fa-solid fa-chart-line brand-orange me-2"></i> Bảng Điều Khiển Tổng Quan
    </h3>
    <span class="text-secondary fw-semibold font-monospace bg-white px-3 py-2 border rounded shadow-sm">
        <i class="fa-regular fa-clock me-1 text-primary"></i> <?= date('d/m/Y H:i') ?>
    </span>
</div>

<div class="row g-4 mb-5">
    
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card stat-card bg-white p-4 border-start border-primary border-4 rounded-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-1">Doanh Thu Thực Thu</h6>
                    <h3 class="fw-bold text-dark m-0"><?= number_format($total_revenue, 0, ',', '.') ?> đ</h3>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="fa-solid fa-money-bill-trend-up fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="card stat-card bg-white p-4 border-start border-warning border-4 rounded-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-1">Đơn Hàng Cần Duyệt</h6>
                    <h3 class="fw-bold text-dark m-0"><?= $pending_orders ?> đơn</h3>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                    <i class="fa-solid fa-file-invoice-dollar fs-4"></i>
                </div>
            </div>
            <a href="<?= BASE_URL ?>admin/modules/don-hang/index-donhang.php" class="small text-warning mt-2 text-decoration-none fw-semibold">
                Xem chi tiết đơn mới <i class="fa-solid fa-arrow-right-long ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="card stat-card bg-white p-4 border-start border-success border-4 rounded-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-1">Mẫu Giày Kinh Doanh</h6>
                    <h3 class="fw-bold text-dark m-0"><?= $total_products ?> mẫu</h3>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="fa-solid fa-shoe-prints fs-4"></i>
                </div>
            </div>
            <a href="<?= BASE_URL ?>admin/modules/san-pham/index-sanpham.php" class="small text-success mt-2 text-decoration-none fw-semibold">
                Quản lý danh mục sản phẩm <i class="fa-solid fa-arrow-right-long ms-1"></i>
            </a>
        </div>
    </div>
    
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h5 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Hệ Thống Cảnh Báo Hết Hàng Trong Kho
        </h5>
        <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold shadow-sm">
            <?= count($low_stock_items) ?> Biến thể chạm ngưỡng nguy hiểm
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($low_stock_items)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-circle-check text-success fs-1 mb-3 d-block"></i>
                <span class="fw-bold text-dark d-block mb-1">Kho hàng đang ở trạng thái an toàn!</span>
                Toàn bộ các biến thể size và màu sắc của giày hiện tại đều đáp ứng tốt số lượng lưu kho tối thiểu.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-secondary small fw-bold border-top-0">
                        <tr>
                            <th class="text-start ps-4" style="width: 35%;">Tên mẫu giày</th>
                            <th style="width: 15%;">Màu sắc</th>
                            <th style="width: 12%;">Kích cỡ (Size)</th>
                            <th style="width: 15%;">Số lượng còn lại</th>
                            <th style="width: 13%;">Trạng thái</th>
                            <th class="pe-4" style="width: 10%;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_items as $item): ?>
                            <?php $stock = $item['stock_quantity'] ?? $item['stock'] ?? 0; ?>
                            <tr>
                                <td class="text-start fw-bold text-dark ps-4">
                                    <?= sanitize($item['product_name']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                                        <?= sanitize($item['color']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold fs-6"><?= sanitize($item['size']) ?></td>
                                <td class="text-danger fw-bold fs-6"><?= $stock ?> đôi</td>
                                <td>
                                    <?php if ($stock == 0): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 fw-bold">
                                            CHÁY HÀNG HOÀN TOÀN
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 fw-bold">
                                            SẮP CHÁY HÀNG
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4">
                                    <a href="<?= BASE_URL ?>admin/modules/kho-hang/index-khohang.php?variant_id=<?= $item['id'] ?>" 
                                       class="btn btn-outline-dark btn-sm fw-bold px-3 py-1.5">
                                        <i class="fa-solid fa-plus me-1"></i> Nhập kho
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

<?php
require_once __DIR__ . '/includes/footer.php';
?>