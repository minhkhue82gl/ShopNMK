<?php
// 1. Kết nối cơ sở dữ liệu dùng chung bằng PDO
require_once '../includes/conn.php';

// 2. Kiểm tra an toàn: Chỉ khởi động Session khi chưa có phiên nào hoạt động
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 3. Nếu Admin đã đăng nhập từ trước, tự động chuyển hướng thẳng vào trang Dashboard trung tâm
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    header('Location: index.php');
    exit;
}

$error = '';

// 4. Xử lý logic khi người dùng nhấn nút "ĐĂNG NHẬP HỆ THỐNG"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        try {
            /**
             * TRUY VẤN BẢO MẬT QUA PDO PREPARED STATEMENT
             * Dựa trên file shop_giay_nmk.sql:
             * - Trường `role` mang kiểu chuỗi ENUM: 'admin', 'staff', 'customer'.
             * - Câu lệnh SQL chặn và lọc ngay từ tầng dữ liệu, chỉ lấy tài khoản có quyền 'admin'.
             */
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Xác thực mật khẩu đã được băm mã hóa trong Database bằng hàm password_verify()
            if ($user && password_verify($password, $user['password'])) {
                
                // Khởi tạo Session lưu trữ phiên làm việc của Admin
                $_SESSION['user'] = [
                    'id'       => $user['id'],
                    'email'    => $user['email'],
                    'fullname' => $user['fullname'],
                    'role'     => $user['role']
                ];
                
                // Xóa bỏ các thông báo lỗi cũ nếu có và chuyển hướng vào trang quản trị
                unset($_SESSION['error']);
                header('Location: index.php');
                exit;
            } else {
                $error = "Tài khoản hoặc mật khẩu Quản trị viên không chính xác!";
            }
        } catch (PDOException $e) {
            $error = "Hệ thống gặp sự cố kết nối: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng điền đầy đủ thông tin Email và Mật khẩu!";
    }
}

// Lấy thông báo lỗi chuyển hướng từ các trang chặn quyền (nếu có)
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']); // Xóa ngay sau khi hiển thị để không bị lặp lại
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Hệ thống Quản trị - NMK SHOES</title>
    
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    
    <style>
        body { 
            background-color: #f4f6f9; 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
        }
        .login-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            background: #ffffff;
        }
        .btn-login {
            background-color: #212529;
            color: #ffffff;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .btn-login:hover {
            background-color: #ff5722;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(255, 87, 34, 0.2);
        }
        .form-control:focus {
            border-color: #ff5722;
            box-shadow: 0 0 0 0.25rem rgba(255, 87, 34, 0.15);
        }
    </style>
</head>
<body class="d-flex align-items-center">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-8 col-md-6 col-lg-4">
            
            <div class="card login-card p-4">
                <div class="card-body">
                    <h3 class="text-center fw-bold mb-1 text-dark">NMK<span style="color: #ff5722;">SHOE</span></h3>
                    <p class="text-center text-muted small mb-4 text-uppercase fw-semibold tracking-wider" style="font-size: 11px;">
                        Đăng nhập phân hệ quản trị viên
                    </p>
                    
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-3" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $error ?>
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Địa chỉ Email Admin</label>
                            <input type="email" name="email" class="form-control py-2" 
                                   placeholder="admin@nmkshoes.com" required autocomplete="email">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Mật khẩu bảo mật</label>
                            <input type="password" name="password" class="form-control py-2" 
                                   placeholder="••••••••" required autocomplete="current-password">
                        </div>
                        
                        <button type="submit" name="login_admin" class="btn btn-login w-100 text-uppercase">
                            Đăng Nhập Hệ Thống
                        </button>
                    </form>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>