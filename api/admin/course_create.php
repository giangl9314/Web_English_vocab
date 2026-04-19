<?php
// FILE: api/admin/course_create.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/config.php';
require_once '../../includes/log_helper.php';

try {
    // 1. Kiểm tra quyền Admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        throw new Exception("Bạn không có quyền thực hiện hành động này.");
    }

    $admin_id = $_SESSION['user_id'];
    
    // 2. Nhận dữ liệu JSON
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) throw new Exception("Dữ liệu đầu vào không hợp lệ.");

    // Kiểm tra CSRF (nếu hệ thống của bạn có sử dụng)
    if (!isset($input['csrf_token']) || $input['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        // throw new Exception("Lỗi bảo mật CSRF."); // Bật dòng này nếu bạn đã có logic CSRF
    }

    $name = trim($input['name'] ?? '');
    $desc = trim($input['description'] ?? '');
    $status = ($input['status'] === 'active') ? 'public' : 'private';
    $tagsRaw = $input['tags'] ?? '';
    
    if (empty($name)) throw new Exception("Tên khóa học không được để trống.");

    // Bắt đầu giao dịch (Transaction)
    $conn->begin_transaction();

    // 3. Tạo khóa học mới (Mặc định hide = 0 theo logic của bạn)
    $stmt = $conn->prepare("INSERT INTO course (course_name, description, visibility, create_by, created_at, hide) VALUES (?, ?, ?, ?, NOW(), 0)");
    $stmt->bind_param("sssi", $name, $desc, $status, $admin_id);
    
    if (!$stmt->execute()) throw new Exception("Lỗi khi tạo khóa học.");
    $new_id = $conn->insert_id;

    // 4. Cập nhật course_code (Tự động tạo mã dựa trên ID)
    $code = str_pad($new_id, 3, '0', STR_PAD_LEFT);
    $stmtUpdate = $conn->prepare("UPDATE course SET course_code = ? WHERE course_id = ?");
    $stmtUpdate->bind_param("si", $code, $new_id);
    $stmtUpdate->execute();

    // 5. Xử lý Tags (Nếu có)
    if (!empty($tagsRaw)) {
        // Chuyển chuỗi thành mảng, xóa khoảng trắng và lọc các phần tử rỗng
        $tags = array_unique(array_filter(array_map('trim', explode(',', $tagsRaw))));
        
        $stmtChk = $conn->prepare("SELECT tag_id FROM tag WHERE tag_name = ?");
        $stmtIns = $conn->prepare("INSERT INTO tag (tag_name) VALUES (?)");
        $stmtLnk = $conn->prepare("INSERT IGNORE INTO course_tag (course_id, tag_id) VALUES (?, ?)");

        foreach ($tags as $t) {
            $tid = 0;
            $stmtChk->bind_param("s", $t);
            $stmtChk->execute();
            $res = $stmtChk->get_result();
            
            if ($row = $res->fetch_assoc()) {
                $tid = $row['tag_id'];
            } else {
                $stmtIns->bind_param("s", $t);
                if ($stmtIns->execute()) {
                    $tid = $conn->insert_id;
                }
            }

            if ($tid) {
                $stmtLnk->bind_param("ii", $new_id, $tid);
                $stmtLnk->execute();
            }
        }
    }

    // 6. Ghi log hoạt động
    if (function_exists('writeAdminLog')) {
        writeAdminLog($conn, $admin_id, "Tạo khóa học mới: $name (ID: $new_id)", $new_id);
    }

    // Hoàn tất giao dịch
    $conn->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => "Đã tạo khóa học thành công!", 
        'data' => ['id' => $new_id, 'code' => $code]
    ]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback(); // Hủy bỏ các thay đổi nếu có lỗi
    http_response_code(http_response_code() == 200 ? 400 : http_response_code());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>