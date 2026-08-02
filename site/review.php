<?php
require_once '../includes/conn.php';
/** @var PDO $conn */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để gửi đánh giá sản phẩm.";
    header('Location: dang-nhap.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_submit_review'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $rating     = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    $comment    = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $user_id    = $_SESSION['user']['id'];

    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;

    if ($product_id <= 0 || empty($comment)) {
        $_SESSION['error'] = "Vui lòng chọn số sao và nhập nội dung đánh giá!";
        header("Location: chi-tiet.php?id=" . $product_id);
        exit;
    }

    try {
        $sql_check_purchase = "SELECT od.id 
                               FROM order_details od
                               INNER JOIN orders o ON od.order_id = o.id
                               INNER JOIN product_variants pv ON od.variant_id = pv.id
                               WHERE o.user_id = ? 
                                 AND pv.product_id = ? 
                                 AND o.status = 'Đã giao'
                               LIMIT 1";
        
        $stmt_purchased = $conn->prepare($sql_check_purchase);
        $stmt_purchased->execute([$user_id, $product_id]);
        $has_purchased = $stmt_purchased->fetch();

        if (!$has_purchased) {
            $_SESSION['error'] = "Bạn chỉ có thể đánh giá sản phẩm này sau khi đã mua và nhận hàng thành công!";
            header("Location: chi-tiet.php?id=" . $product_id);
            exit;
        }

        $stmt_check_exist = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
        $stmt_check_exist->execute([$product_id, $user_id]);
        $existing_review = $stmt_check_exist->fetch();

        if ($existing_review) {
            $stmt_update = $conn->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE id = ?");
            $stmt_update->execute([$rating, $comment, $existing_review['id']]);
            $_SESSION['success'] = "Đã cập nhật lại đánh giá của bạn thành công!";
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt_insert->execute([$product_id, $user_id, $rating, $comment]);
            $_SESSION['success'] = "Cảm ơn bạn đã gửi đánh giá cho sản phẩm!";
        }

        header("Location: chi-tiet.php?id=" . $product_id);
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: chi-tiet.php?id=" . $product_id);
        exit;
    }
} else {
    header('Location: cua-hang.php');
    exit;
}