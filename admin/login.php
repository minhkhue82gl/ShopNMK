<?php
require_once __DIR__ . '/../config.php';

if (is_loggedin() && in_array($_SESSION['user']['role'], ['admin', 'staff'])) {
    redirect(BASE_URL . 'admin/index-admin.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin'])) {
    $account = sanitize($_POST['account'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($account) && !empty($password)) {
        try {
            // Truy vấn lấy người dùng theo Email hoặc Username và có quyền Admin/Staff
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND role IN ('admin', 'staff') LIMIT 1");
            $stmt->execute([$account, $account]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'email'    => $user['email'],
                    'fullname' => $user['fullname'],
                    'role'     => $user['role']
                ];
                
                $_SESSION['success'] = "Đăng nhập thành công! Chào mừng " . htmlspecialchars($user['fullname']);
                redirect(BASE_URL . 'admin/index-admin.php');
            } else {
                $error = "Tài khoản hoặc mật khẩu Quản trị viên không chính xác!";
            }
        } catch (PDOException $e) {
            $error = "Hệ thống gặp sự cố kết nối: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng điền đầy đủ Tên đăng nhập/Email và Mật khẩu!";
    }
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Hệ thống Quản trị - NMK SHOP</title>
    
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-body d-flex align-items-center">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-8 col-md-6 col-lg-4">
            
            <div class="card login-card p-4">
                <div class="card-body">
                    <h3 class="text-center fw-bold mb-1 text-dark">NMK<span class="brand-orange">SHOE</span></h3>
                    <p class="text-center text-muted small mb-4 text-uppercase fw-semibold tracking-wider">
                        Đăng nhập phân hệ quản trị viên
                    </p>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-3" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $error ?>
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Email hoặc Tên đăng nhập</label>
                            <input type="text" name="account" class="form-control py-2" 
                                   placeholder="admin@nmkshoes.com" 
                                   value="<?= htmlspecialchars($_POST['account'] ?? '') ?>" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Mật khẩu bảo mật</label>
                            <input type="password" name="password" class="form-control py-2" 
                                   placeholder="••••••••" required>
                        </div>
                        
                        <button type="submit" name="login_admin" class="btn btn-login w-100 text-uppercase">
                            <i class="fa-solid fa-right-to-bracket me-2"></i> Đăng Nhập Hệ Thống
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-2 border-top">
                        <a href="<?= BASE_URL ?>site/index.php" class="text-decoration-none text-muted small">
                            <i class="fa-solid fa-arrow-left me-1"></i> Quay lại trang bán hàng
                        </a>
                    </div>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>