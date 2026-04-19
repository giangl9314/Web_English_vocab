<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. Khởi tạo kết nối và công cụ
require_once '../../config/config.php';
require_once '../../includes/log_helper.php';

class AdminEngine {
    private $db;
    private $admin_id;

    public function __construct($db_conn, $sid) {
        $this->db = $db_conn;
        $this->admin_id = (int)$sid;
    }

    // ========================================
    // MODULE: QUẢN LÝ NGƯỜI DÙNG
    // ========================================
    public function handleUsers($req) {
        $action = $req['sub_action'] ?? 'list';

        if ($action === 'list') {
            $page = max(1, (int)($req['page'] ?? 1));
            $search = "%" . ($req['search'] ?? '') . "%";
            $offset = ($page - 1) * 10;

            $stmt = $this->db->prepare("SELECT user_id, name, email, status FROM user WHERE name LIKE ? OR email LIKE ? LIMIT ?, 10");
            $stmt->bind_param("ssii", $search, $search, $offset, $offset); // Bind ảo cho offset
            // Fix nhỏ: LIMIT trong bind_param cần truyền giá trị trực tiếp hoặc bind kiểu 'i'
            $stmt = $this->db->prepare("SELECT user_id, name, email, status FROM user WHERE name LIKE ? OR email LIKE ? LIMIT 10 OFFSET ?");
            $stmt->bind_param("ssi", $search, $search, $offset);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        if ($action === 'toggle') {
            $target_id = (int)$req['target_id'];
            $new_status = ($req['status'] === 'active') ? 1 : 0;
            if ($target_id === $this->admin_id) throw new Exception("Không thể tự khóa chính mình.");

            $stmt = $this->db->prepare("UPDATE user SET status = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $new_status, $target_id);
            $stmt->execute();
            $this->log("Thay đổi trạng thái User ID $target_id", $target_id);
            return "Cập nhật thành công.";
        }
    }

    // ========================================
    // MODULE: QUẢN LÝ KHÓA HỌC & TAGS
    // ========================================
    public function handleCourses($req) {
        $mode = $req['mode'] ?? 'view';
        $this->db->begin_transaction();

        try {
            if ($mode === 'save') {
                $id = (int)($req['id'] ?? 0);
                $name = trim($req['name']);
                $visible = ($req['status'] === 'active') ? 'public' : 'private';

                if ($id > 0) {
                    $stmt = $this->db->prepare("UPDATE course SET course_name=?, description=?, visibility=? WHERE course_id=?");
                    $stmt->bind_param("sssi", $name, $req['description'], $visible, $id);
                } else {
                    $stmt = $this->db->prepare("INSERT INTO course (course_name, description, visibility, create_by, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("sssi", $name, $req['description'], $visible, $this->admin_id);
                }
                $stmt->execute();
                $course_id = ($id > 0) ? $id : $this->db->insert_id;

                // Đồng bộ Tags (Xóa sạch - Gắn mới)
                $this->db->prepare("DELETE FROM course_tag WHERE course_id = ?")->bind_param("i", $course_id)->execute();
                $tags = array_filter(array_map('trim', explode(',', $req['tags'] ?? '')));
                foreach ($tags as $t) {
                    $this->db->prepare("INSERT IGNORE INTO tag (tag_name) VALUES (?)")->bind_param("s", $t)->execute();
                    $res = $this->db->query("SELECT tag_id FROM tag WHERE tag_name = '$t'")->fetch_assoc();
                    $tid = $res['tag_id'];
                    $this->db->prepare("INSERT INTO course_tag (course_id, tag_id) VALUES (?, ?)")->bind_param("ii", $course_id, $tid)->execute();
                }

                $this->db->commit();
                $this->log("Lưu khóa học: $name", $course_id);
                return ["id" => $course_id];
            }

            if ($mode === 'delete') {
                $id = (int)$req['id'];
                // Kiểm tra quyền xóa (Chỉ xóa public hoặc của chính mình)
                $check = $this->db->prepare("SELECT visibility, create_by FROM course WHERE course_id = ?");
                $check->bind_param("i", $id); $check->execute();
                $course = $check->get_result()->fetch_assoc();

                if ($course['create_by'] != $this->admin_id && $course['visibility'] === 'private') {
                    throw new Exception("Không có quyền xóa nội dung riêng tư của người khác.");
                }

                $this->db->prepare("DELETE FROM course_tag WHERE course_id = ?")->bind_param("i", $id)->execute();
                $this->db->prepare("DELETE FROM course WHERE course_id = ?")->bind_param("i", $id)->execute();
                $this->db->commit();
                return "Đã xóa.";
            }
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function log($msg, $ref) {
        if (function_exists('writeAdminLog')) writeAdminLog($this->db, $this->admin_id, $msg, $ref);
    }
}

// --- CHẠY HỆ THỐNG ---
try {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') throw new Exception("Truy cập bị từ chối.", 403);

    $engine = new AdminEngine($conn, $_SESSION['user_id']);
    $route = $_GET['route'] ?? '';
    $data = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;

    switch ($route) {
        case 'users':  $result = $engine->handleUsers($data); break;
        case 'courses': $result = $engine->handleCourses($data); break;
        default: throw new Exception("Đường dẫn không hợp lệ.");
    }

    echo json_encode(['status' => 'success', 'data' => $result]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}