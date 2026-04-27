<?php
// FILE: api/admin/course_update_status.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/config.php';
require_once '../../includes/log_helper.php';

try {
    // 1. KIỂM TRA QUYỀN ADMIN
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        throw new Exception("Bạn không có quyền thực hiện hành động này.");
    }

    $admin_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);

    // 2. KIỂM TRA CSRF (Bật nếu bạn đã có logic Token ở Frontend)
    // if (!isset($input['csrf_token']) || $input['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    //     throw new Exception("Lỗi bảo mật CSRF.");
    // }

    // 3. KIỂM TRA DỮ LIỆU ĐẦU VÀO
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $statusRaw = $input['status'] ?? '';

    if ($id <= 0 || empty($statusRaw)) {
        throw new Exception("Dữ liệu đầu vào không hợp lệ.");
    }

    // Map trạng thái: Giao diện gửi 'active'/'hidden' -> DB lưu 'public'/'private'
    $visibility = ($statusRaw === 'active' || $statusRaw === 'public') ? 'public' : 'private';

    // 4. KIỂM TRA TỒN TẠI VÀ LẤY TÊN KHÓA HỌC
    $stmtCheck = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $course = $stmtCheck->get_result()->fetch_assoc();

    if (!$course) {
        throw new Exception("Khóa học không tồn tại.");
    }

    // 5. CẬP NHẬT TRẠNG THÁI
    $stmt = $conn->prepare("UPDATE course SET visibility = ? WHERE course_id = ?");
    $stmt->bind_param("si", $visibility, $id);

    if ($stmt->execute()) {
        // Ghi log chi tiết
        if (function_exists('writeAdminLog')) {
            $actionDesc = ($visibility === 'public') ? "Công khai khóa học" : "Ẩn khóa học";
            $courseName = $course['course_name'];
            writeAdminLog($conn, $admin_id, "$actionDesc: $courseName", $id);
        }

        echo json_encode([
            'status' => 'success', 
            'message' => 'Đã cập nhật trạng thái hiển thị thành công!',
            'data' => ['visibility' => $visibility]
        ]);
    } else {
        throw new Exception("Không thể cập nhật cơ sở dữ liệu.");
    }

} catch (Exception $e) {
    // Trả về mã lỗi phù hợp (403 nếu quyền, 400 nếu dữ liệu sai)
    if (http_response_code() === 200) http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}