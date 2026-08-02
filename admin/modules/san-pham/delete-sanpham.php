<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt_check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM order_details od 
            JOIN product_variants pv ON od.variant_id = pv.id 
            WHERE pv.product_id = ?
        ");
        $stmt_check->execute([$id]);
        $in_orders = $stmt_check->fetchColumn();

        if ($in_orders > 0) {
            $_SESSION['error'] = "Không thể xóa! Mẫu giày này đã có trong đơn hàng của khách. Bạn nên ẩn sản phẩm hoặc chỉnh số lượng về 0.";
            redirect(BASE_URL . 'admin/modules/san-pham/index-sanpham.php');
            exit();
        }

        $stmt_find = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt_find->execute([$id]);
        $product = $stmt_find->fetch();

        if ($product) {
            $stmt_del_prod = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt_del_prod->execute([$id]);

            if (!empty($product['image_url'])) {
                $file_path = UPLOAD_DIR . 'products/' . basename($product['image_url']);
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }

            $_SESSION['success'] = "Đã xóa vĩnh viễn sản phẩm khỏi hệ thống!";
        } else {
            $_SESSION['error'] = "Không tìm thấy sản phẩm cần xóa!";
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống khi xóa sản phẩm: " . $e->getMessage();
    }
}

redirect(BASE_URL . 'admin/modules/san-pham/index-sanpham.php');