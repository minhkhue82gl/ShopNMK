<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_details' && isset($_GET['import_id'])) {
    header('Content-Type: application/json');
    $import_id = (int)$_GET['import_id'];
    
    $sql_detail = "SELECT iod.*, p.product_name, pv.color, pv.size 
                   FROM import_order_details iod
                   INNER JOIN product_variants pv ON iod.variant_id = pv.id
                   INNER JOIN products p ON pv.product_id = p.id
                   WHERE iod.import_id = :import_id";
                   
    $stmt = $pdo->prepare($sql_detail);
    $stmt->execute([':import_id' => $import_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($details);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$sql = "SELECT io.*, u.fullname AS staff_name 
        FROM import_orders io 
        LEFT JOIN users u ON io.user_id = u.id 
        ORDER BY io.id DESC";
$imports = $pdo->query($sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark">
            <i class="fa-solid fa-clock-rotate-left brand-orange me-2"></i> Lịch Sử Nhập Hàng Kho
        </h3>
        <p class="text-muted small m-0 mt-1">Danh sách phiếu nhập kho đã khởi tạo và tổng chi phí vốn nhập hàng</p>
    </div>
    <a href="<?= BASE_URL ?>admin/modules/kho-hang/lap-phieu-nhap.php" class="btn btn-warning text-white fw-bold px-3 py-2 rounded-3 shadow-sm">
        <i class="fa-solid fa-file-circle-plus me-1"></i> Lập Phiếu Nhập Mới
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <?php if (empty($imports)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-receipt fs-1 mb-2 d-block"></i>
                Chưa có phiếu nhập hàng nào được tạo.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-secondary small fw-bold">
                        <tr>
                            <th>Mã Phiếu</th>
                            <th>Ngày Lập</th>
                            <th>Người Thực Hiện</th>
                            <th>Tổng Chi Phí</th>
                            <th class="text-start">Ghi Chú</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($imports as $row): ?>
                            <tr>
                                <td class="fw-bold font-monospace text-dark"><?= sanitize($row['import_code']) ?></td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= sanitize($row['staff_name'] ?? 'Admin') ?></span></td>
                                <td class="fw-bold text-danger"><?= format_money($row['total_cost']) ?></td>
                                <td class="text-start small text-muted"><?= !empty($row['note']) ? sanitize($row['note']) : 'Không có ghi chú' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info rounded-2 px-2 py-1 btn-view-detail" 
                                            data-id="<?= $row['id'] ?>" 
                                            data-code="<?= sanitize($row['import_code']) ?>">
                                        <i class="fa-solid fa-eye me-1"></i> Chi Tiết
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Xem Chi Tiết Phiếu Nhập Kho -->
<div class="modal fade" id="importDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-boxes-packing text-info me-2"></i>
                    Chi Tiết Phiếu Nhập Kho: <span id="modalImportCode" class="font-monospace text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0 text-center">
                        <thead class="table-light small fw-bold text-secondary">
                            <tr>
                                <th>#</th>
                                <th class="text-start">Tên Sản Phẩm</th>
                                <th>Phân Loại</th>
                                <th>Số Lượng</th>
                                <th>Đơn Giá Nhập</th>
                                <th>Thành Tiền</th>
                            </tr>
                        </thead>
                        <tbody id="importDetailContent">
                            <!-- Nội dung AJAX sẽ đổ vào đây -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-2 px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailButtons = document.querySelectorAll('.btn-view-detail');
    const modalElement = document.getElementById('importDetailModal');
    const detailModal = new bootstrap.Modal(modalElement);
    const modalCodeSpan = document.getElementById('modalImportCode');
    const detailContent = document.getElementById('importDetailContent');

    detailButtons.forEach(button => {
        button.addEventListener('click', function() {
            const importId = this.getAttribute('data-id');
            const importCode = this.getAttribute('data-code');

            modalCodeSpan.textContent = importCode;
            detailContent.innerHTML = '<tr><td colspan="6" class="py-4 text-center text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</td></tr>';
            
            detailModal.show();

            // Gọi AJAX tải danh sách chi tiết mặt hàng
            fetch(`lich-su-nhap.php?ajax=get_details&import_id=${importId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        detailContent.innerHTML = '<tr><td colspan="6" class="py-3 text-center text-muted">Không tìm thấy chi tiết nào cho phiếu nhập này.</td></tr>';
                        return;
                    }

                    let html = '';
                    let stt = 1;
                    data.forEach(item => {
                        const importPrice = parseFloat(item.import_price);
                        const quantity = parseInt(item.quantity);
                        const subtotal = importPrice * quantity;

                        html += `
                            <tr>
                                <td class="fw-bold">${stt++}</td>
                                <td class="text-start fw-bold text-dark">${item.product_name}</td>
                                <td><span class="badge bg-light text-secondary border">Màu: ${item.color} - Size: ${item.size}</span></td>
                                <td class="fw-bold text-primary">${quantity}</td>
                                <td>${new Intl.NumberFormat('vi-VN').format(importPrice)} đ</td>
                                <td class="fw-bold text-danger">${new Intl.NumberFormat('vi-VN').format(subtotal)} đ</td>
                            </tr>
                        `;
                    });
                    detailContent.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    detailContent.innerHTML = '<tr><td colspan="6" class="py-3 text-center text-danger">Có lỗi xảy ra khi tải dữ liệu!</td></tr>';
                });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>