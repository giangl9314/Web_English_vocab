<?php
/**
 * API: ĐẶT LẠI MẬT KHẨU
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';

// chỉ cho POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Sai method']);
    exit;
}

// check session (viết đơn giản)
if (!isset($_SESSION['verified_reset'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Hết phiên rồi'
    ]);
    exit;
}

// check thời gian (không quá kỹ)
if (time() - $_SESSION['verified_reset']['verified_at'] > 600) {
    unset($_SESSION['verified_reset']);
    echo json_encode([
        'success' => false,
        'message' => 'Hết hạn'
    ]);
    exit;
}

// lấy dữ liệu
$input = json_decode(file_get_contents('php://input'), true);

$password = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

// validate sơ sơ
if ($password == '') {
    echo json_encode(['success' => false, 'message' => 'Nhập password']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Quá ngắn']);
    exit;
}

// check giống nhau
if ($password != $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Không khớp']);
    exit;
}

try {

    global $conn;

    $verified = $_SESSION['verified_reset'];

    // check lại code (viết gọn)
    $stmt = $conn->prepare("SELECT email FROM user WHERE user_id = ? AND reset_code = ?");
    $stmt->bind_param("is", $verified['user_id'], $verified['code']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        unset($_SESSION['verified_reset']);
        echo json_encode([
            'success' => false,
            'message' => 'Code sai hoặc hết hạn'
        ]);
        exit;
    }

    $user = $res->fetch_assoc();

    // hash password
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // update
    $stmt = $conn->prepare("UPDATE user SET password = ?, reset_code = NULL WHERE user_id = ?");
    $stmt->bind_param("si", $hash, $verified['user_id']);
    $stmt->execute();

    // ghi log (viết đại)
    file_put_contents(
        __DIR__ . '/../logs/reset.log',
        date('Y-m-d H:i:s') . " reset pass user_id=" . $verified['user_id'] . "\n",
        FILE_APPEND
    );

    // xóa session
    unset($_SESSION['verified_reset']);

    echo json_encode([
        'success' => true,
        'message' => 'Đổi mật khẩu ok'
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Lỗi rồi'
    ]);
}