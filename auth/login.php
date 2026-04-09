<?php
/**
 * TRANG ĐĂNG NHẬP
 * Form đăng nhập cho người dùng
 */
require_once __DIR__ . '/../config/config.php';

// Nếu đã đăng nhập, redirect theo role
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
    <title>Đăng nhập - VOCAB</title>
</head>
<body>
    <div id="header_index"></div>
    <div id="content">
        <div class="login-container">
            <h2>Đăng nhập</h2>
            <p>Chào mừng quay lại! Hãy đăng nhập vào tài khoản của bạn.</p>

            <form method="POST" action="/VOCAB/process/login-process.php">
                <div class="input-group">
                    <div class="email">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required>
                    </div>
                    <div class="password">
                        <label for="password">Mật khẩu</label>
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu của bạn" required>
                    </div>
                </div>
                <button type="submit" class="login-button">Đăng nhập</button>
            </form>

            <p class="signup-link">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
        </div>
    </div>
    <div id="footer"></div>
    <script src="../assets/js/defaut/include-layout.js" defer></script>
</body>
</html>
