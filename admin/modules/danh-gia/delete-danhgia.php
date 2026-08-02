<?php
require_once __DIR__ . '/../../../config.php';
check_admin_access();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Đã xóa bình luận thành công!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi khi xóa: " . $e->getMessage();
    }
}

redirect(BASE_URL . 'admin/modules/danh-gia/index-danhgia.php');