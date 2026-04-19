<?php
// FILE: api/admin/course_update.php
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
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) throw new Exception("Dữ liệu không hợp lệ.");

    // Kiểm tra CSRF (Bỏ comment nếu bạn đã dùng token ở Frontend)
    // if (!isset($input['csrf_token']) || $input['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) throw new Exception("Lỗi bảo mật CSRF.");

    $id = (int)($input['id'] ?? 0);
    $newName = trim($input['name'] ?? '');
    $newDesc = trim($input['description'] ?? '');
    $newStatus = ($input['status'] === 'active') ? 'public' : 'private';
    
    if ($id <= 0 || empty($newName)) throw new Exception("Dữ liệu thiếu hoặc ID không hợp lệ.");

    // Xử lý mảng Tags mới gửi lên
    $rawTags = $input['tags'] ?? '';
    $newTagsArray = array_unique(array_filter(array_map('trim', explode(',', $rawTags))));
    sort($newTagsArray);

    // 2. LẤY DỮ LIỆU CŨ ĐỂ SO SÁNH (Tránh Update thừa)
    $sqlOld = "SELECT c.course_name, c.description, c.visibility, 
               (SELECT GROUP_CONCAT(t.tag_name ORDER BY t.tag_name ASC) 
                FROM course_tag ct 
                JOIN tag t ON ct.tag_id = t.tag_id 
                WHERE ct.course_id = c.course_id) as current_tags
               FROM course c WHERE c.course_id = ?";
    
    $stmtOld = $conn->prepare($sqlOld);
    $stmtOld->bind_param("i", $id);
    $stmtOld->execute();
    $oldData = $stmtOld->get_result()->fetch_assoc();

    if (!$oldData) throw new Exception("Khóa học không tồn tại.");

    $oldTagsArray = $oldData['current_tags'] ? explode(',', $oldData['current_tags']) : [];
    // (Tags đã được ORDER BY trong SQL nên không cần sort lại mảng cũ)

    // 3. SO SÁNH SỰ THAY ĐỔI
    $isInfoChanged = ($oldData['course_name'] !== $newName || 
                      $oldData['description'] !== $newDesc || 
                      $oldData['visibility'] !== $newStatus);
    $isTagsChanged = ($oldTagsArray !== $newTagsArray);

    if (!$isInfoChanged && !$isTagsChanged) {
        echo json_encode([
            'status' => 'warning',
            'message' => 'Bạn chưa thay đổi thông tin nào.'
        ]);
        exit();
    }

    // 4. TIẾN HÀNH CẬP NHẬT
    $conn->begin_transaction();

    // Cập nhật thông tin cơ bản
    if ($isInfoChanged) {
        $stmtUp = $conn->prepare("UPDATE course SET course_name=?, description=?, visibility=? WHERE course_id=?");
        $stmtUp->bind_param("sssi", $newName, $newDesc, $newStatus, $id);
        if (!$stmtUp->execute()) throw new Exception("Không thể cập nhật thông tin khóa học.");
    }

    // Cập nhật Tags (Xóa cũ - Thêm mới)
    if ($isTagsChanged) {
        $stmtDel = $conn->prepare("DELETE FROM course_tag WHERE course_id = ?");
        $stmtDel->bind_param("i", $id);
        $stmtDel->execute();

        if (!empty($newTagsArray)) {
            $stmtChk = $conn->prepare("SELECT tag_id FROM tag WHERE tag_name = ?");
            $stmtIns = $conn->prepare("INSERT INTO tag (tag_name) VALUES (?)");
            $stmtLnk = $conn->prepare("INSERT IGNORE INTO course_tag (course_id, tag_id) VALUES (?, ?)");

            foreach ($newTagsArray as $t) {
                $tid = 0;
                $stmtChk->bind_param("s", $t);
                $stmtChk->execute();
                $res = $stmtChk->get_result();
                
                if ($row = $res->fetch_assoc()) {
                    $tid = $row['tag_id'];
                } else {
                    $stmtIns->bind_param("s", $t);
                    if ($stmtIns->execute()) $tid = $conn->insert_id;
                }

                if ($tid) {
                    $stmtLnk->bind_param("ii", $id, $tid);
                    $stmtLnk->execute();
                }
            }
        }
    }

    // 5. Ghi Log và Commit
    $logAction = "Cập nhật khóa học ID $id: $newName" . ($isTagsChanged ? " (kèm Tags)" : "");
    if (function_exists('writeAdminLog')) {
        writeAdminLog($conn, $admin_id, $logAction, $id);
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Cập nhật khóa học thành công!']);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    http_response_code(http_response_code() == 200 ? 400 : http_response_code());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}