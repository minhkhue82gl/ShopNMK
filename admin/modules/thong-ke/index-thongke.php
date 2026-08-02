<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$current_year = date('Y');

$total_revenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status = 'Đã giao'")->fetchColumn() ?: 0;
$total_cost    = $pdo->query("SELECT SUM(total_cost) FROM import_orders")->fetchColumn() ?: 0;
$total_profit  = $total_revenue - $total_cost;
$total_orders  = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
$total_users   = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn() ?: 0;
$total_stock   = $pdo->query("SELECT SUM(stock) FROM product_variants")->fetchColumn() ?: 0;

$monthly_revenue = array_fill(1, 12, 0);
$stmt_chart = $pdo->prepare("
    SELECT 
        MONTH(created_at) AS month_num, 
        SUM(total_price) AS total_rev
    FROM orders 
    WHERE status = 'Đã giao' AND YEAR(created_at) = ?
    GROUP BY MONTH(created_at)
");
$stmt_chart->execute([$current_year]);
foreach ($stmt_chart->fetchAll() as $row) {
    $monthly_revenue[$row['month_num']] = (float)$row['total_rev'];
}

$sql_top_products = "
    SELECT 
        p.id, 
        p.product_name AS name, 
        p.image_url, 
        SUM(od.quantity) AS total_sold
    FROM order_details od
    JOIN orders o ON od.order_id = o.id
    JOIN product_variants v ON od.variant_id = v.id
    JOIN products p ON v.product_id = p.id
    WHERE o.status = 'Đã giao'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
";
$top_products = $pdo->query($sql_top_products)->fetchAll();

$sql_low_stock = "
    SELECT p.product_name, v.color, v.size, v.stock, v.low_stock_threshold 
    FROM product_variants v
    JOIN products p ON v.product_id = p.id
    WHERE v.stock <= v.low_stock_threshold
    ORDER BY v.stock ASC
    LIMIT 5
";
$low_stock_items = $pdo->query($sql_low_stock)->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-chart-line text-danger me-2"></i> Báo Cáo & Thống Kê NMK SHOP
        </h3>
        <p class="text-muted small m-0 mt-1">Tổng quan kết quả kinh doanh năm <?= $current_year ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block">Tổng Doanh Thu</span>
                    <h4 class="fw-bold text-danger m-0 mt-1"><?= number_format($total_revenue, 0, ',', '.') ?> đ</h4>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
                    <i class="fa-solid fa-sack-dollar fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block">Ước Tính Lợi Nhuận</span>
                    <h4 class="fw-bold <?= $total_profit >= 0 ? 'text-success' : 'text-warning' ?> m-0 mt-1">
                        <?= number_format($total_profit, 0, ',', '.') ?> đ
                    </h4>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                    <i class="fa-solid fa-chart-pie fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block">Tổng Đơn Hàng</span>
                    <h4 class="fw-bold text-dark m-0 mt-1"><?= number_format($total_orders) ?> đơn</h4>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                    <i class="fa-solid fa-cart-shopping fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block">Sản Phẩm Tồn Kho</span>
                    <h4 class="fw-bold text-dark m-0 mt-1"><?= number_format($total_stock) ?> đôi</h4>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                    <i class="fa-solid fa-boxes-stacked fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100">
            <h5 class="fw-bold text-dark mb-3">Biểu Đồ Doanh Thu Theo Tháng (Năm <?= $current_year ?>)</h5>
            <div class="card-body p-0">
                <canvas id="revenueChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-4">
            <h5 class="fw-bold text-dark mb-3">TOP 5 Bán Chạy</h5>
            <?php if (empty($top_products)): ?>
                <p class="text-muted small m-0">Chưa có đơn hàng nào đã giao.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($top_products as $index => $item): ?>
                        <div class="list-group-item d-flex align-items-center gap-2 px-0 py-2 border-0 border-bottom">
                            <span class="badge rounded-circle bg-dark text-white" style="width: 24px; height: 24px; line-height: 18px;"><?= $index + 1 ?></span>
                            <div class="flex-grow-1 text-truncate">
                                <div class="fw-bold small text-truncate"><?= htmlspecialchars($item['name']) ?></div>
                                <small class="text-muted">Đã bán: <strong class="text-danger"><?= $item['total_sold'] ?></strong> đôi</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
            <h5 class="fw-bold text-danger mb-3"><i class="fa-solid fa-triangle-exclamation me-1"></i> Sắp Hết Hàng</h5>
            <?php if (empty($low_stock_items)): ?>
                <p class="text-muted small m-0">Kho hàng ổn định.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($low_stock_items as $stock): ?>
                        <li class="list-group-item px-0 d-flex justify-content-between border-0 border-bottom">
                            <span><?= htmlspecialchars($stock['product_name']) ?> (<?= $stock['color'] ?>/<?= $stock['size'] ?>)</span>
                            <strong class="text-danger"><?= $stock['stock'] ?> đôi</strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: <?= json_encode(array_values($monthly_revenue)) ?>,
                    backgroundColor: '#111111',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>