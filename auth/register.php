<?php
/**
 * TRANG ĐĂNG KÝ
 * Form đăng ký tài khoản mới
 */
require_once __DIR__ . '/../config/config.php';

// Nếu đã đăng nhập, redirect
if (is_logged_in()) {
    redirect('/VOCAB/pages/user/user_Dashboard.html');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/defaut/index.css">
    <link rel="stylesheet" href="../assets/css/defaut/body.css">
    <link rel="stylesheet" href="../assets/css/defaut/dangnhap.css">
    <title>Đăng ký - VOCAB</title>
</head>
<body>
    <div id="header_index"></div>
    <div id="content">
        <div class="login-container">
            <h2>Đăng ký</h2>
            <p>Hãy tạo tài khoản của bạn để bắt đầu hành trình học tập.</p>

            <form method="POST" action="/VOCAB/process/register-process.php">
                <div class="input-group">
                    <div class="name">
                        <label for="name">Tên đầy đủ</label>
                        <input type="text" id="name" name="name" placeholder="Nhập tên của bạn" required>
                    </div>
                    <div class="email">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required>
                    </div>
                    <div class="password">
                        <label for="password">Mật khẩu</label>
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu của bạn" required>
                    </div>
                    <div class="confirm-password">
                        <label for="confirm-password">Xác nhận mật khẩu</label>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Xác nhận mật khẩu của bạn" required>
                    </div>
                </div>
                <button type="submit" class="login-button">Đăng ký</button>
            </form>

            <p class="signup-link">Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
        </div>
    </div>
    <div id="footer"></div>
    <script src="../assets/js/defaut/include-layout.js" defer></script>
</body>
</html>
