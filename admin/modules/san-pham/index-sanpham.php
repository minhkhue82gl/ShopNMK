<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

try {
    $sql = "SELECT p.*, c.category_name AS category_name, b.brand_name AS brand_name,
            (SELECT SUM(stock) FROM product_variants WHERE product_id = p.id) AS total_stock
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            ORDER BY p.id DESC";
            
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Lỗi truy vấn sản phẩm: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-shoe-prints brand-orange me-2"></i> Quản Lý Danh Sách Sản Phẩm
        </h3>
        <p class="text-muted small m-0 mt-1">Quản lý các mẫu giày, thương hiệu, giá cả và biến thể tồn kho trong hệ thống NMK</p>
    </div>
    <a href="<?= BASE_URL ?>admin/modules/san-pham/add-sanpham.php" class="btn btn-warning text-white fw-bold px-4 py-2 rounded-3 shadow-sm">
        <i class="fa-solid fa-plus me-2"></i> Thêm Sản Phẩm Mới
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <?php if (empty($products)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-shoe-prints fs-1 mb-3 d-block text-secondary"></i>
                <span class="fw-bold text-dark d-block mb-1">Chưa có dữ liệu sản phẩm!</span>
                Vui lòng nhấn nút "Thêm Sản Phẩm Mới" để thiết lập mẫu giày đầu tiên.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-secondary small fw-bold border-top-0">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th class="text-start ps-3" style="width: 10%;">Hình ảnh</th>
                            <th class="text-start" style="width: 25%;">Tên mẫu giày</th>
                            <th style="width: 15%;">Thương hiệu / Loại</th>
                            <th style="width: 15%;">Giá bán</th>
                            <th style="width: 12%;">Tổng tồn kho</th>
                            <th class="pe-3" style="width: 18%;">Thao tác xử lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $row): ?>
                            <tr>
                                <td class="text-secondary font-monospace">#<?= $row['id'] ?></td>
                                
                                <td class="text-start ps-3">
                                    <?php if (!empty($row['image_url'])): ?>
                                        <img src="<?= BASE_URL . 'assets/uploads/products/' . htmlspecialchars($row['image_url']) ?>" alt="NMK Shoe" class="rounded border" style="width: 55px; height: 55px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted small fw-semibold" style="width: 55px; height: 55px;">
                                            No Image
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-start">
                                    <div class="fw-bold text-dark mb-0"><?= sanitize($row['product_name']) ?></div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 250px;" title="<?= sanitize($row['description'] ?? '') ?>">
                                        <?= !empty($row['description']) ? sanitize($row['description']) : 'Chưa có mô tả.' ?>
                                    </small>
                                </td>
                                
                                <td>
                                    <span class="badge bg-dark px-2 py-1 mb-1 d-inline-block font-monospace">
                                        <?= sanitize($row['brand_name'] ?? 'Không rõ') ?>
                                    </span>
                                    <div class="small text-secondary fw-semibold">
                                        <?= sanitize($row['category_name'] ?? 'Chưa phân loại') ?>
                                    </div>
                                </td>
                                
                                <td class="fw-bold text-dark">
                                    <?= format_money($row['price']) ?>
                                    <?php if (!empty($row['old_price']) && $row['old_price'] > $row['price']): ?>
                                        <small class="text-muted text-decoration-line-through d-block font-monospace">
                                            <?= format_money($row['old_price']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php 
                                        $stock = $row['total_stock'];
                                        if ($stock === null): 
                                    ?>
                                        <span class="text-warning small fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Chưa tạo kho</span>
                                    <?php elseif ($stock == 0): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 fw-bold">CHÁY HÀNG</span>
                                    <?php elseif ($stock <= 5): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1 fw-bold"><?= $stock ?> đôi (Sắp hết)</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 fw-bold"><?= $stock ?> đôi</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="pe-3">
                                    <div class="btn-group shadow-sm" role="group">
                                        <a href="<?= BASE_URL ?>admin/modules/san-pham/edit-sanpham.php?id=<?= $row['id'] ?>" class="btn btn-outline-dark btn-sm fw-bold" title="Chỉnh sửa">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>admin/modules/san-pham/delete-sanpham.php?id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" title="Xóa sản phẩm"
                                           onclick="return confirm('CẢNH BÁO: Bạn có chắc muốn xóa mẫu giày <?= sanitize($row['product_name']) ?>?')">
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