<?php
// site/review.php
require_once '../includes/conn.php';
session_start();

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    $_SESSION['review_error'] = "Vui lòng đăng nhập để đánh giá!";
    header('Location: dang-nhap.php');
    exit;
}

// 2. Xử lý khi nhấn nút Gửi đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user']['id'];

    if ($product_id > 0 && $rating >= 1 && $rating <= 5) {
        $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $user_id, $rating, $comment]);
        $_SESSION['review_success'] = "Cảm ơn bạn đã đánh giá!";
    }
    
    header("Location: chi-tiet.php?id=" . $product_id);
    exit;
}
?>