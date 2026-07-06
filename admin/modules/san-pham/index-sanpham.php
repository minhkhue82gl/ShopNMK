<?php
require_once '../../../includes/conn.php';

include_once '../../includes/header.php';
include_once '../../includes/sidebar.php';

try {
   
    $sql = "SELECT p.*, c.category_name, b.brand_name,
            (SELECT SUM(stock) FROM product_variants WHERE product_id = p.id) AS total_stock
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            ORDER BY p.id DESC";
            
    $stmt = $conn->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo '<div class="alert alert-danger m-3">Lỗi truy vấn danh sách sản phẩm: ' . $e->getMessage() . '</div>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title m-0">Quản Lý Danh Sách Sản Phẩm</h2>
        <p class="text-muted small m-0">Xem thông tin định danh chính, danh mục và tổng kho của các mẫu giày NMK</p>
    </div>
    <a href="add.php" class="btn btn-brand px-4 py-2 rounded-3 shadow-sm text-decoration-none fw-bold">
        <i class="fa-solid fa-plus me-2"></i> Thêm Sản Phẩm Mới
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <?php if (empty($products)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-shoe-prints fs-1 mb-3 d-block text-secondary"></i>
                <span class="fw-bold text-dark d-block mb-1">Chưa có dữ liệu sản phẩm!</span>
                Vui lòng bấm vào nút "Thêm Sản Phẩm Mới" để thiết lập mẫu giày đầu tiên cho kệ hàng.
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
                            <th style="width: 15%;">Giá hiện tại</th>
                            <th style="width: 12%;">Tổng tồn kho</th>
                            <th class="pe-3" style="width: 18%;">Thao tác xử lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $row): ?>
                            <tr>
                                <td class="text-secondary font-monospace"><?= $row['id'] ?></td>
                                
                                <td class="text-start ps-3">
                                    <?php 
                                        // Kiểm tra nếu có ảnh trong CSDL và file tồn tại, ngược lại dùng ảnh mặc định tạm thời
                                        $image_path = "../../../assets/uploads/" . $row['image_url'];
                                        if (!empty($row['image_url']) && file_exists($image_path)): 
                                    ?>
                                        <img src="<?= $image_path ?>" alt="NMK Shoe" class="rounded border" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted small fw-semibold" style="width: 60px; height: 60px;">
                                            No Image
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-start">
                                    <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($row['product_name']) ?></div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($row['description']) ?>">
                                        <?= !empty($row['description']) ? htmlspecialchars($row['description']) : 'Chưa có mô tả chi tiết cho sản phẩm.' ?>
                                    </small>
                                </td>
                                
                                <td>
                                    <span class="badge bg-dark px-2 py-1.5 mb-1 d-inline-block font-monospace" style="font-size: 10px;">
                                        <?= htmlspecialchars($row['brand_name'] ?? 'Không rõ') ?>
                                    </span>
                                    <div class="small text-secondary fw-semibold">
                                        <?= htmlspecialchars($row['category_name'] ?? 'Chưa phân loại') ?>
                                    </div>
                                </td>
                                
                                <td class="fw-bold text-dark">
                                    <?= number_format($row['price'], 0, ',', '.') ?> đ
                                    <?php if (!empty($row['old_price']) && $row['old_price'] > $row['price']): ?>
                                        <small class="text-muted text-decoration-line-through d-block font-monospace" style="font-size: 11px;">
                                            <?= number_format($row['old_price'], 0, ',', '.') ?> đ
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
                                        <span class="badge bg-danger bg-opacity-10 text-danger badge-soft px-2.5 py-1.5 fw-bold">CHÁY HÀNG</span>
                                    <?php elseif ($stock <= 5): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning badge-soft px-2.5 py-1.5 fw-bold"><?= $stock ?> đôi (Sắp hết)</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success badge-soft px-2.5 py-1.5 fw-bold"><?= $stock ?> đôi</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="pe-3">
                                    <div class="btn-group shadow-sm" role="group">
                                        <a href="../kho-hang/index.php?product_id=<?= $row['id'] ?>" class="btn btn-outline-secondary btn-sm" title="Quản lý biến thể kho">
                                            <i class="fa-solid fa-boxes-stacked"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-outline-dark btn-sm fw-bold" title="Chỉnh sửa sản phẩm">
                                            <i class="fa-solid fa-pen-to-square"></i> Sửa
                                        </a>
                                        <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" title="Xóa sản phẩm"
                                           onclick="return confirm('CẢNH BÁO: Hành động này sẽ xóa vĩnh viễn mẫu giày \'<?= htmlspecialchars($row['product_name']) ?>\' và toàn bộ các biến thể size/màu liên quan của nó! Bạn vẫn muốn tiếp tục?')">
                                            <i class="fa-solid fa-trash-can"></i>
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

<?php
include_once '../../includes/footer.php';
?>