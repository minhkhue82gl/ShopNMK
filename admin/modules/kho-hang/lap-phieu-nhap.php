<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

// Lấy danh sách tất cả các biến thể giày (Đã sửa p.name thành p.product_name)
$sql = "SELECT v.id, v.size, v.color, v.stock, p.product_name AS product_name 
        FROM product_variants v 
        JOIN products p ON v.product_id = p.id 
        ORDER BY p.product_name ASC, v.color ASC, v.size ASC";
$all_variants = $pdo->query($sql)->fetchAll();

// Biến thể chọn sẵn nếu bấm nút "Nhập Kho" từ trang danh sách
$selected_variant_id = isset($_GET['variant_id']) ? (int)$_GET['variant_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_import'])) {
    $note = sanitize($_POST['note'] ?? '');
    $variant_ids   = $_POST['variant_id'] ?? [];
    $import_prices = $_POST['import_price'] ?? [];
    $quantities    = $_POST['quantity'] ?? [];

    if (!empty($variant_ids) && is_array($variant_ids)) {
        try {
            $pdo->beginTransaction();

            $import_code = 'NMK_IMP_' . date('Ymd_His');
            $total_cost  = 0;

            // Tính tổng tiền phiếu nhập
            for ($i = 0; $i < count($variant_ids); $i++) {
                $qty   = (int)$quantities[$i];
                $price = (float)$import_prices[$i];
                if ($qty > 0 && $price >= 0) {
                    $total_cost += ($qty * $price);
                }
            }

            // 1. Tạo phiếu nhập hàng mới
            $stmt_imp = $pdo->prepare("INSERT INTO import_orders (user_id, import_code, total_cost, note) VALUES (?, ?, ?, ?)");
            $stmt_imp->execute([$_SESSION['user']['id'] ?? null, $import_code, $total_cost, $note]);
            $import_id = $pdo->lastInsertId();

            // 2. Chèn chi tiết & Cập nhật TỰ ĐỘNG tồn kho cho biến thể
            $stmt_dt  = $pdo->prepare("INSERT INTO import_order_details (import_id, variant_id, import_price, quantity) VALUES (?, ?, ?, ?)");
            $stmt_up  = $pdo->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?");

            for ($i = 0; $i < count($variant_ids); $i++) {
                $v_id  = (int)$variant_ids[$i];
                $qty   = (int)$quantities[$i];
                $price = (float)$import_prices[$i];

                if ($v_id > 0 && $qty > 0) {
                    // Lưu chi tiết nhập
                    $stmt_dt->execute([$import_id, $v_id, $price, $qty]);
                    
                    // Tự động cộng số lượng vào tồn kho biến thể
                    $stmt_up->execute([$qty, $v_id]);
                }
            }

            $pdo->commit();
            $_SESSION['success'] = "Lập phiếu nhập hàng {$import_code} thành công! Kho đã được cộng tự động.";
            redirect(BASE_URL . 'admin/modules/kho-hang/lich-su-nhap.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Lỗi lập phiếu nhập: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Vui lòng chọn ít nhất một sản phẩm cần nhập hàng!";
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="mb-4">
    <h3 class="fw-bold m-0 text-dark">
        <i class="fa-solid fa-file-circle-plus brand-orange me-2"></i> Lập Phiếu Nhập Hàng Kho
    </h3>
    <p class="text-muted small m-0 mt-1">Khai báo danh sách giày nhập kho, số lượng và giá vốn nhập hàng</p>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form action="" method="POST">
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-dark m-0">Danh Sách Giày Nhập Kho</h5>
                    <button type="button" class="btn btn-success btn-sm fw-bold" onclick="addImportRow()">
                        <i class="fa-solid fa-plus me-1"></i> Thêm Dòng Nhập
                    </button>
                </div>

                <div id="import-items-container"></div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-3 mb-4">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Thông Tin Phiếu Nhập</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Người lập phiếu</label>
                    <input type="text" class="form-control" value="<?= sanitize($_SESSION['user']['fullname'] ?? 'Admin') ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Ghi chú nhập hàng</label>
                    <textarea name="note" class="form-control" rows="4" placeholder="Nhập tên nhà cung cấp, thông tin lô hàng..."></textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" name="save_import" class="btn btn-warning text-white w-100 py-2.5 fw-bold shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Hoàn Tất & Cộng Kho
                </button>
                <a href="<?= BASE_URL ?>admin/modules/kho-hang/index-khohang.php" class="btn btn-light border w-50 py-2.5 fw-bold text-center">Hủy</a>
            </div>
        </div>
    </div>
</form>

<script>
const variantsList = <?= json_encode($all_variants) ?>;
const defaultVariantId = <?= $selected_variant_id ?>;
let rowCount = 0;

function addImportRow(selectedId = 0) {
    rowCount++;
    const container = document.getElementById('import-items-container');
    const row = document.createElement('div');
    row.className = 'p-3 border rounded-3 mb-3 bg-light position-relative';
    row.id = `import-row-${rowCount}`;

    let optionsHtml = '<option value="">-- Chọn biến thể giày --</option>';
    variantsList.forEach(v => {
        const isSelected = (selectedId > 0 && selectedId == v.id) ? 'selected' : '';
        optionsHtml += `<option value="${v.id}" ${isSelected}>${v.product_name} - [Màu: ${v.color} | Size: ${v.size}] (Tồn: ${v.stock})</option>`;
    });

    row.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-secondary mb-1">Chọn mẫu giày & biến thể <span class="text-danger">*</span></label>
                <select name="variant_id[]" class="form-select form-select-sm" required>
                    ${optionsHtml}
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Giá nhập (đ/đôi) <span class="text-danger">*</span></label>
                <input type="number" name="import_price[]" class="form-control form-control-sm" placeholder="1500000" min="0" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Số lượng nhập <span class="text-danger">*</span></label>
                <input type="number" name="quantity[]" class="form-control form-control-sm" value="10" min="1" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm w-100" onclick="document.getElementById('import-row-${rowCount}').remove()">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
}

window.onload = () => {
    addImportRow(defaultVariantId);
};
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```[cite: 13]