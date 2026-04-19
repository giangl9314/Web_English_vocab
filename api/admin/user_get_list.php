<?php
/**
 * FILE: api/admin/admin_api.php
 * TỔNG HỢP LOGIC QUẢN TRỊ - NHÓM 7
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. Load cấu hình và thư viện
require_once '../../config/config.php';
require_once '../../includes/log_helper.php';

try {
    // 2. Kiểm tra quyền Admin chung cho tất cả action
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        throw new Exception("Bạn không có quyền truy cập vùng này.");
    }

    // 3. Xác định hành động (Action)
    $action = $_GET['action'] ?? '';

    switch ($action) {

        // --- HÀNH ĐỘNG 1: LẤY DANH SÁCH USER ---
        case 'get_users':
            $colName = 'name'; // Thay bằng 'fullname' nếu DB của bạn khác
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            $search = trim($_GET['search'] ?? '');
            
            $allowed_sort = ['user_id', 'email', 'status', 'created_at', $colName];
            $sort_by = in_array($_GET['sort_by'] ?? '', $allowed_sort) ? $_GET['sort_by'] : 'created_at';
            $order = strtoupper($_GET['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

            $where = "WHERE 1=1";
            $params = []; $types = "";
            if (!empty($search)) {
                $where .= " AND ($colName LIKE ? OR email LIKE ?)";
                $s = "%$search%"; $params[] = $s; $params[] = $s; $types .= "ss";
            }

            // Đếm tổng
            $stCount = $conn->prepare("SELECT COUNT(*) as total FROM user $where");
            if ($types) $stCount->bind_param($types, ...$params);
            $stCount->execute();
            $total = $stCount->get_result()->fetch_assoc()['total'];

            // Lấy data
            $sql = "SELECT user_id, $colName as name, email, avatar, status, created_at 
                    FROM user $where ORDER BY $sort_by $order LIMIT ?, ?";
            $params[] = $offset; $params[] = $limit; $types .= "ii";
            $stData = $conn->prepare($sql);
            $stData->bind_param($types, ...$params);
            $stData->execute();

            echo json_encode([
                'status' => 'success',
                'data' => $stData->get_result()->fetch_all(MYSQLI_ASSOC),
                'pagination' => ['current_page' => $page, 'total_pages' => ceil($total / $limit)]
            ]);
            break;

        // --- HÀNH ĐỘNG 2: CẬP NHẬT TRẠNG THÁI (KHÓA/MỞ) ---
        case 'update_status':
            $input = json_decode(file_get_contents("php://input"), true);
            $userId = (int)($input['user_id'] ?? 0);
            $status = ($input['status'] === 'active') ? 1 : 0;

            if ($userId <= 0) throw new Exception("ID không hợp lệ");
            if ($userId == $_SESSION['user_id']) throw new Exception("Không thể tự khóa chính mình");

            $stmt = $conn->prepare("UPDATE user SET status = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $status, $userId);
            if ($stmt->execute()) {
                if (function_exists('writeAdminLog')) {
                    writeAdminLog($conn, $_SESSION['user_id'], ($status==1?"Mở khóa":"Khóa")." user ID $userId", $userId);
                }
                echo json_encode(["status" => "success", "message" => "Đã cập nhật trạng thái"]);
            }
            break;

        // --- HÀNH ĐỘNG 3: CẬP NHẬT AVATAR ---
        case 'update_avatar':
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0 || !isset($_FILES['avatar'])) throw new Exception("Dữ liệu không hợp lệ");

            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'png', 'webp', 'jpeg'])) throw new Exception("Định dạng ảnh không hỗ trợ");

            $newFile = "avatar_{$user_id}_" . time() . ".$ext";
            if (!file_exists(UPLOAD_PATH . "/avatars/")) mkdir(UPLOAD_PATH . "/avatars/", 0777, true);

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_PATH . "/avatars/" . $newFile)) {
                $dbPath = "uploads/avatars/" . $newFile;
                $conn->prepare("UPDATE user SET avatar = ? WHERE user_id = ?")->bind_param("si", $dbPath, $user_id)->execute();
                echo json_encode(["status" => "success", "avatar_url" => SITE_URL . "/" . $dbPath]);
            }
            break;

        // --- HÀNH ĐỘNG 4: THỐNG KÊ DASHBOARD ---
        case 'get_stats':
            $res['total_users'] = (int)$conn->query("SELECT COUNT(*) FROM user WHERE role='user'")->fetch_row()[0];
            $res['total_courses'] = (int)$conn->query("SELECT COUNT(*) FROM course WHERE hide=0")->fetch_row()[0];
            
            $res['user_chart'] = array_reverse($conn->query("SELECT DATE_FORMAT(created_at, '%m/%Y') as m, COUNT(*) as c 
                                FROM user GROUP BY m ORDER BY MAX(created_at) DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC));

            echo json_encode(['status' => 'success', 'data' => $res]);
            break;

        default:
            throw new Exception("Hành động (Action) không được hỗ trợ.");
    }

} catch (Exception $e) {
    http_response_code(http_response_code() == 200 ? 400 : http_response_code());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}