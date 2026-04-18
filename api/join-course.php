<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once '../config/config.php';
require_once '../includes/notification_helper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Method Not Allowed');
    
    $user_id = api_require_login();

    $input = json_decode(file_get_contents('php://input'), true);

    $course_id = $input['course_id'] ?? 0;

    if ($user_id <= 0 || $course_id <= 0) {
        throw new Exception('Thiếu dữ liệu');
    }

    $checkCourseSql = "SELECT create_by FROM course WHERE course_id = ? AND visibility = 'public'";
    $stmtCourse = $conn->prepare($checkCourseSql);
    $stmtCourse->bind_param("i", $course_id);
    $stmtCourse->execute();
    $resCourse = $stmtCourse->get_result();

    if ($resCourse->num_rows == 0) {
        throw new Exception('Không tìm thấy khóa học');
    }

    $checkJoinedSql = "SELECT 1 FROM user_course WHERE user_id = ? AND course_id = ?";
    $stmtCheck = $conn->prepare($checkJoinedSql);
    $stmtCheck->bind_param("ii", $course_id, $user_id);
    $stmtCheck->execute();

    if ($stmtCheck->get_result()->num_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Đã tham gia rồi']);
        exit;
    }

    $sql = "INSERT INTO user_course (user_id, course_id, status, enrolled_at) 
            VALUES (?, ?, 'active', NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $course_id);

    if ($stmt->execute()) {

        $courseNameStmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ? ");
        $courseNameStmt->bind_param("i", $course_id);
        $courseNameStmt->execute();
        $courseNameResult = $courseNameStmt->get_result();

        if ($row = $courseNameResult->fetch_assoc()) {
            notifyCourseJoined($conn, $user_id, $row['course_name']);
        }

        echo json_encode(['success' => true, 'message' => 'Tham gia thành công']);
    } else {
        throw new Exception('Lỗi thêm dữ liệu');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>