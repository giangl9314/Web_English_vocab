<?php
/**
 * API LƯU MỤC TIÊU HỌC
 */

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
}

require_once '../config/config.php';

try {

    // lấy dữ liệu
    $data = json_decode(file_get_contents('php://input'), true);

    $user_id = $data['user_id'] ?? 0;
    $daily_words_target = $data['daily_words_target'] ?? 0;
    $is_recurring = $data['is_recurring'] ?? 0;

    // validate sơ sơ
    if (!$user_id || !$daily_words_target) {
        throw new Exception('Thiếu dữ liệu');
    }

    if ($daily_words_target <= 0) {
        throw new Exception('Sai số từ');
    }

    // check user (viết đơn giản)
    $check = $conn->prepare("SELECT user_id FROM user WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows == 0) {
        throw new Exception('Không có user');
    }

    // insert hoặc update (viết gọn)
    $sql = "INSERT INTO user_daily_goal (user_id, daily_words_target, is_recurring)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            daily_words_target = ?, is_recurring = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $user_id, $daily_words_target, $is_recurring, $daily_words_target, $is_recurring);

    if (!$stmt->execute()) {
        throw new Exception('Lưu lỗi');
    }

    echo json_encode([
        'success' => true,
        'message' => 'OK rồi',
        'data' => [
            'user_id' => $user_id,
            'daily_words_target' => $daily_words_target,
            'is_recurring' => $is_recurring
        ]
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>