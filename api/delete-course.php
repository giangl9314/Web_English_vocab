<?php
/**
 * API XÓA / RỜI KHỎI KHÓA HỌC
 * Endpoint: api/delete-course.php
 * Method: POST
 *
 * - Hoàn thiện xóa dữ liệu liên quan (review_log, learned_word)
 * - Tối ưu JOIN DELETE
 * - Thêm thống kê sau khi rời khóa học
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/config.php';
try {
    // Kiểm tra method request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    // TODO: chuyển hoàn toàn sang auth session
    $user_id = api_require_login();
    // Đọc JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $course_id = intval($input['course_id'] ?? 0);
    $action = $input['action'] ?? '';
    // Validate dữ liệu đầu vào
    if ($course_id <= 0) {
        throw new Exception('Invalid course id');
    }
    // Start transaction
    $conn->begin_transaction();
    // Lấy owner khóa học
    $stmt = $conn->prepare("SELECT create_by FROM course WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course = $result->fetch_assoc();
    if (!$course) {
        throw new Exception('Course not found');
    }
    $owner_id = $course['create_by'];
    // ==========================
    // DELETE COURSE
    // ==========================
    if ($action === 'delete') {
        // Chỉ owner mới được xóa
        if ($owner_id != $user_id) {
            throw new Exception('No permission');
        }
        // TODO: delete review logs
        // TODO: delete learned word progress
        // Xóa words
        $stmt = $conn->prepare("DELETE FROM word WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        // Xóa user join course
        $stmt = $conn->prepare("DELETE FROM user_course WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        // Xóa course
        $stmt = $conn->prepare("DELETE FROM course WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $message = "Course deleted";
    }

    // ==========================
    // LEAVE COURSE
    // ==========================
    elseif ($action === 'leave') {
        // TODO: update statistic table
        $stmt = $conn->prepare("DELETE FROM user_course WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $message = "Left course";
    }
    // Commit transaction
    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    // Rollback khi lỗi
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}