<?php
session_start();

// 1. Kết nối cơ sở dữ liệu qua PDO bằng đường dẫn hệ thống của bạn
require_once '../../../includes/conn.php';

// Kiểm tra quyền admin (Nếu hệ thống của bạn yêu cầu phân quyền bằng Session)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Bạn có thể mở comment dòng dưới nếu hệ thống đã có chức năng đăng nhập admin
    // header("Location: ../auth/login.php"); exit();
}

// 2. Nhúng bộ khung giao diện Header và Sidebar dùng chung
include_once '../../includes/header.php';
include_once '../../includes/sidebar.php';

$error = '';
$success = '';

// Lấy danh mục và thương hiệu để đổ vào thẻ <select> trên form (Sử dụng PDO)
try {
    $categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $brands = $conn->query("SELECT * FROM brands ORDER BY brand_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi nạp danh mục/thương hiệu: " . $e->getMessage();
}

// 3. Xử lý logic khi bấm nút "LƯU SẢN PHẨM"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    
    // Lấy và chuẩn hóa thông tin sản phẩm chính
    $product_name = trim($_POST['product_name']);
    $category_id  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $brand_id     = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $price        = floatval($_POST['price']);
    $old_price    = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;
    $description  = trim($_POST['description']);
    $status       = isset($_POST['status']) ? (int)$_POST['status'] : 1; // Mặc định hiển thị
    
    // Đường dẫn thư mục upload ảnh đúng cấu trúc thư mục của bạn
    $upload_dir = "../../../assets/uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

    if (!empty($product_name) && $price > 0) {
        try {
            // Kích hoạt Database Transaction (Giao dịch an toàn bằng PDO)
            $conn->beginTransaction();

            // --- XỬ LÝ UPLOAD ẢNH CHÍNH DUY NHẤT ---
            $main_image_name = "";
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['main_image']['tmp_name'];
                $file_name = $_FILES['main_image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (in_array($file_ext, $allowed_extensions)) {
                    // Mã hóa tên file để không bị trùng lặp
                    $main_image_name = "prod_" . time() . "_" . uniqid() . "." . $file_ext;
                    move_uploaded_file($file_tmp, $upload_dir . $main_image_name);
                } else {
                    throw new Exception("Ảnh sản phẩm không đúng định dạng (Chấp nhận JPG, JPEG, PNG, WEBP).");
                }
            } else {
                throw new Exception("Vui lòng tải lên một hình ảnh cho sản phẩm.");
            }

            // --- THÊM DỮ LIỆU VÀO BẢNG products (Sửa lỗi thiếu trường ảnh ở code cũ) ---
            $sql_prod = "INSERT INTO products (product_name, category_id, brand_id, price, old_price, image_url, description, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_prod = $conn->prepare($sql_prod);
            $stmt_prod->execute([$product_name, $category_id, $brand_id, $price, $old_price, $main_image_name, $description, $status]);
            
            // Lấy ID sản phẩm vừa tạo để làm khóa ngoại
            $product_id = $conn->lastInsertId();

            // --- XỬ LÝ THÊM CÁC BIẾN THỂ (Đồng bộ số lượng mảng song song) ---
            if (isset($_POST['color']) && is_array($_POST['color'])) {
                
                $colors = $_POST['color'];
                $sizes  = $_POST['size'];
                $stocks = $_POST['stock'];

                $sql_var = "INSERT INTO product_variants (product_id, size, color, stock, image) VALUES (?, ?, ?, ?, NULL)";
                $stmt_var = $conn->prepare($sql_var);

                for ($i = 0; $i < count($colors); $i++) {
                    $color = trim($colors[$i]);
                    $size  = intval($sizes[$i]);
                    $stock = intval($stocks[$i]);

                    // Chỉ lưu nếu người dùng nhập đầy đủ dữ liệu thuộc tính
                    if (!empty($color) && $size > 0) {
                        $stmt_var->execute([$product_id, $size, $color, $stock]);
                    }
                }
            }

            // Cam kết lưu vĩnh viễn dữ liệu nếu không phát sinh lỗi
            $conn->commit();
            $success = "Thêm sản phẩm mới và cấu hình các biến thể thành công!";
            
        } catch (Exception $e) {
            // Hoàn tác dữ liệu nếu có lỗi xảy ra
            $conn->rollBack();
            $error = "Hệ thống lỗi: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng nhập Tên sản phẩm và Giá bán hợp lệ!";
    }
}
?>

<div class="container-fluid px-4 mt-4">
    <div class="mb-4">
        <h2 class="page-title m-0 fw-bold text-dark">Thêm Sản Phẩm Mới</h2>
        <p class="text-muted small m-0">Khai báo 1 ảnh duy nhất và thiết lập số lượng tồn kho theo từng thuộc tính vật lý</p>
    </div>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show fw-semibold" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show fw-semibold" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="add.php" method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i>Thông tin chung
                    </h5>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" class="form-control" required placeholder="Ví dụ: Nike Air Force 1">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Thương hiệu <span class="text-danger">*</span></label>
                            <select name="brand_id" class="form-select" required>
                                <option value="">Chọn thương hiệu</option>
                                <?php foreach($brands as $brand): ?>
                                    <option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Danh mục <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Chọn danh mục</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Giá bán hiện tại (đ) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" required placeholder="2000000">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Giá gốc cũ (đ)</label>
                            <input type="number" name="old_price" class="form-control" placeholder="2500000">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Trạng thái hiển thị</label>
                        <select name="status" class="form-select">
                            <option value="1">Hiển thị trên Web</option>
                            <option value="0">Tạm ẩn</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Ảnh sản phẩm đại diện <span class="text-danger">*</span></label>
                        <input type="file" name="main_image" class="form-control" accept="image/*" required>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Mẫu giày này sẽ dùng chung tấm ảnh này cho tất cả biến thể kho.</small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-secondary">Mô tả chi tiết</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Nhập chất liệu, phong cách thiết kế..."></textarea>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="add_product" class="btn btn-primary w-100 py-2.5 fw-bold text-uppercase shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Lưu sản phẩm
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary w-50 py-2.5 fw-bold text-uppercase">Hủy</a>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h5 class="fw-bold text-dark m-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Khai báo Size & Số lượng kho</h5>
                        <button type="button" class="btn btn-success btn-sm fw-bold" onclick="addVariant()">
                            <i class="fa-solid fa-plus me-1"></i> Thêm biến thể
                        </button>
                    </div>

                    <p class="text-muted small mb-3 border-start border-3 ps-2 border-warning">
                        Bạn có thể thêm nhiều biến thể kích cỡ cho sản phẩm. Hệ thống sẽ tự động đồng bộ ảnh chính sản phẩm làm ảnh nền hiển thị.
                    </p>

                    <div id="variants-container"></div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
let variantCount = 0;
const container = document.getElementById('variants-container');

function addVariant() {
    variantCount++;
    
    // Tạo phần tử div bọc ngoài hàng biến thể
    const variantRow = document.createElement('div');
    variantRow.className = 'p-3 border rounded-3 mb-3 bg-light position-relative';
    variantRow.id = `variant-row-${variantCount}`;
    
    // Gán mã HTML bên trong (SỬA LỖI MẤT DỮ LIỆU CŨ bằng cơ chế DOM chuẩn thay vì innerHTML +=)
    variantRow.innerHTML = `
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Màu sắc <span class="text-danger">*</span></label>
                <input type="text" name="color[]" class="form-control form-control-sm" placeholder="Ví dụ: Đỏ Đen" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Size <span class="text-danger">*</span></label>
                <input type="number" name="size[]" class="form-control form-control-sm" placeholder="42" required min="30" max="50">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Số lượng tồn <span class="text-danger">*</span></label>
                <input type="number" name="stock[]" class="form-control form-control-sm" value="10" required min="0">
            </div>
            <div class="col-md-2 d-flex align-items-end justify-content-end">
                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeVariant(${variantCount})">Xóa</button>
            </div>
        </div>
    `;
    
    container.appendChild(variantRow);
}

function removeVariant(id) {
    const row = document.getElementById(`variant-row-${id}`);
    if (row) {
        row.remove();
    }
}

// Tự động sinh ra dòng biến thể đầu tiên khi quản trị viên vừa tải trang
window.onload = () => addVariant();
</script>

<?php
// 4. Nhúng bộ khung giao diện Footer để kết thúc thẻ body
include_once '../../includes/footer.php';
?>