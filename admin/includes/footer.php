<?php

$current_uri = $_SERVER['REQUEST_URI'];
$base_url = (strpos($current_uri, '/modules/') !== false) ? '../../' : '';
?>

</div> </div> </div> <script src="<?= $base_url ?>../assets/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Tìm tất cả các thông báo alert (thành công, lỗi) có trên trang
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            // Thiết lập bộ đếm thời gian 4000ms (4 giây)
            setTimeout(function() {
                // Tạo hiệu ứng mờ dần bằng class Bootstrap 5 trước khi xóa bỏ
                alert.classList.add('fade');
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 4000);
        });
    });
</script>

</body>
</html>