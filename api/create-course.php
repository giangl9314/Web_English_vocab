<?php
/**
 * API TẠO KHÓA HỌC MỚI
 */

// --- CORS ---
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
}

// tắt lỗi cho gọn
error_reporting(0);
ini_set('display_errors', 0);

// trả json
header('Content-Type: application/json; charset=utf-8');

require_once '../config/config.php';
require_once '../includes/notification_helper.php';

try {

    // chỉ cho POST
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception('Sai method');
    }

    // lấy user từ session
    $user_id = api_require_login();

    $input = json_decode(file_get_contents('php://input'), true);

    // lấy dữ liệu kiểu đơn giản
    $course_name = $input['course_name'] ?? '';
    $description = $input['description'] ?? '';
    $visibility = $input['visibility'] ?? 'public';
    $tags = $input['tags'] ?? [];

    if ($course_name == '' || !$user_id) {
        throw new Exception('Thiếu dữ liệu');
    }

    // bắt đầu transaction
    $conn->begin_transaction();

    // insert course
    $sql = "INSERT INTO course (course_name, description, visibility, create_by, created_at, hide)
            VALUES (?, ?, ?, ?, NOW(), 0)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('SQL lỗi');
    }

    $stmt->bind_param("sssi", $course_name, $description, $visibility, $user_id);

    if (!$stmt->execute()) {
        throw new Exception('Insert lỗi');
    }

    $course_id = $stmt->insert_id;

    // xử lý tags (viết đơn giản)
    if (!empty($tags)) {

        $stmt1 = $conn->prepare("SELECT tag_id FROM tag WHERE tag_name = ?");
        $stmt2 = $conn->prepare("INSERT INTO tag (tag_name) VALUES (?)");
        $stmt3 = $conn->prepare("INSERT INTO course_tag (course_id, tag_id) VALUES (?, ?)");

        foreach ($tags as $t) {

            $t = trim($t);
            if ($t == '') continue;

            $tag_id = 0;

            // check tồn tại
            $stmt1->bind_param("s", $t);
            $stmt1->execute();
            $res = $stmt1->get_result();

            if ($row = $res->fetch_assoc()) {
                $tag_id = $row['tag_id'];
            } else {
                // tạo mới
                $stmt2->bind_param("s", $t);
                if ($stmt2->execute()) {
                    $tag_id = $stmt2->insert_id;
                }
            }

            // gán vào course
            if ($tag_id > 0) {
                $stmt3->bind_param("ii", $course_id, $tag_id);
                $stmt3->execute();
            }
        }
    }

    $conn->commit();

    // tạo thông báo
    notifyCourseCreated($conn, $user_id, $course_name);

    echo json_encode([
        'success' => true,
        'message' => 'Tạo thành công',
        'course_id' => $course_id
    ]);

} catch (Exception $e) {

    if (isset($conn)) {
        $conn->rollback();
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>