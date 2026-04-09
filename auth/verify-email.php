<?php
/**
 * TRANG XÁC THỰC EMAIL
 * Người dùng nhập mã 6 số nhận được qua email
 */
require_once __DIR__ . '/../config/config.php';

// Kiểm tra đã có thông tin pending verification chưa
if (!isset($_SESSION['pending_verification'])) {
    set_message('Không tìm thấy thông tin xác thực. Vui lòng đăng ký lại.', MSG_ERROR);
    redirect('/VOCAB/pages/dangki.html');
}

$pending = $_SESSION['pending_verification'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/defaut/index.css">
    <link rel="stylesheet" href="../assets/css/defaut/body.css">
    <link rel="stylesheet" href="../assets/css/defaut/dangnhap.css">
    <title>Xác thực Email - VOCAB</title>
</head>
<body>
    <div id="header_index"></div>

    <div id="content">
        <div class="login-container">
            <h2>Xác thực Email</h2>
            <p>Mã xác thực đã được gửi đến email: <strong><?php echo htmlspecialchars($pending['email']); ?></strong></p>

            <form method="POST" action="../process/verify-email-process.php">
                <div class="input-group">
                    <label for="verification_code">Mã xác thực</label>
                    <input type="text" 
                           id="verification_code" 
                           name="verification_code" 
                           placeholder="Nhập mã 6 số" 
                           maxlength="6" 
                           required>
                </div>
                <button type="submit" class="login-button">Xác thực</button>
            </form>

            <p class="signup-link"><a href="/VOCAB/pages/dangki.html">← Quay lại đăng ký</a></p>
        </div>
    </div>

    <div id="footer"></div>
    <script src="../assets/js/defaut/include-layout.js" defer></script>
</body>
</html>
