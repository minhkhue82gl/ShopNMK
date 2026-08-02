<?php
require_once '../includes/conn.php';

// Nhúng các file PHPMailer thủ công (đặt trong thư mục includes/phpmailer/)
require_once '../includes/phpmailer/Exception.php';
require_once '../includes/phpmailer/PHPMailer.php';
require_once '../includes/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/** @var PDO $conn */ 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_btn'])) {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        try {
            $stmt = $conn->prepare("SELECT id, username, fullname FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // 1. Tạo mật khẩu ngẫu nhiên mới
                $new_random_pass = 'nmk' . rand(100000, 999999);
                $hashed_pass = password_hash($new_random_pass, PASSWORD_DEFAULT);

                // 2. Cập nhật mật khẩu mới vào CSDL
                $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_update->execute([$hashed_pass, $user['id']]);

                // 3. Tiến hành gửi email thật qua SMTP Gmail
                $mail = new PHPMailer(true);
                try {
                    // Cấu hình Server gửi mail
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'khue0804gl@gmail.com'; // Email gửi đi thực tế
                    $mail->Password   = 'zungdofddnxetoul';     // Mật khẩu ứng dụng 16 ký tự
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    // Người gửi và người nhận
                    $mail->setFrom('khue0804gl@gmail.com', 'NMK SHOP Shop');
                    $mail->addAddress($email, $user['fullname']);

                    // Nội dung email có kèm tên tài khoản đăng nhập
                    $mail->isHTML(true);
                    $mail->Subject = 'Khoi phuc mat khau tai khoan NMK SHOP Shop';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                            <h2 style='color: #333;'>Xin chào " . htmlspecialchars($user['fullname']) . ",</h2>
                            <p>Hệ thống nhận được yêu cầu cấp lại mật khẩu từ tài khoản của bạn tại <b>NMK SHOP Shop</b>.</p>
                            <p>Thông tin tài khoản của bạn:</p>
                            <ul>
                                <li>Tên đăng nhập (Username): <b style='color: #007bff;'>" . htmlspecialchars($user['username']) . "</b></li>
                                <li>Mật khẩu mới: <strong style='color: red; font-size: 18px;'>$new_random_pass</strong></li>
                            </ul>
                            <p>Vui lòng đăng nhập lại bằng tên đăng nhập và mật khẩu mới này, sau đó tiến hành đổi lại mật khẩu trong phần thông tin cá nhân để bảo mật.</p>
                            <br>
                            <p>Trân trọng,<br><b>Ban quản trị NMK SHOP Shop</b></p>
                        </div>
                    ";

                    $mail->send();
                    $success_msg = "Mật khẩu mới đã được gửi thành công về email <strong>$email</strong>. Vui lòng kiểm tra Hộp thư đến hoặc Thư rác (Spam)!";
                } catch (Exception $e) {
                    $error_msg = "Không thể gửi email. Lỗi hệ thống: {$mail->ErrorInfo}";
                }

            } else {
                $error_msg = "Địa chỉ email này không tồn tại trong hệ thống!";
            }
        } catch (PDOException $e) {
            $error_msg = "Lỗi hệ thống CSDL: " . $e->getMessage();
        }
    } else {
        $error_msg = "Vui lòng nhập địa chỉ email của bạn!";
    }
}

include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card border rounded bg-white shadow-sm p-4">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-uppercase text-dark m-0">Khôi phục mật khẩu</h4>
                    <small class="text-muted" style="font-size: 12px;">Hệ thống hỗ trợ NMK SHOP Shop</small>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger p-2 small mb-3 text-center" style="font-size: 13px;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error_msg) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success p-2 small mb-3 text-center" style="font-size: 13px;">
                        <i class="fa-solid fa-circle-check me-1"></i> <?= $success_msg ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="dang-nhap.php" class="btn btn-dark w-100 text-uppercase fw-bold py-2" style="background-color: #111; font-size: 13px;">Đến trang đăng nhập</a>
                    </div>
                <?php else: ?>
                    <form action="quen-mat-khau.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Nhập Email đăng ký *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-envelope" style="font-size: 13px;"></i></span>
                                <input type="email" name="email" class="form-control border-start-0 ps-0 text-dark small" placeholder="name@example.com" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                            </div>
                        </div>

                        <button type="submit" name="forgot_btn" class="btn btn-dark w-100 text-uppercase fw-bold py-2 mb-3" style="background-color: #111; font-size: 13px;">
                            Gửi thông tin qua Email <i class="fa-solid fa-paper-plane ms-1"></i>
                        </button>

                        <div class="text-center mt-2 small text-muted" style="font-size: 13px;">
                            Đã nhớ lại mật khẩu? 
                            <a href="dang-nhap.php" class="text-decoration-none text-primary fw-bold">Đăng nhập</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>