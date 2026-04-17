<?php
// FILE: api/admin/user_update_avatar.php
session_start(); // 1. Luôn để session_start() ở dòng đầu tiên
header('Content-Type: application/json');

require_once '../../config/config.php'; 
require_once '../../includes/log_helper.php';

try {
    // 2. Kiểm tra phương thức và quyền Admin
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception("Phương thức không được hỗ trợ");
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        throw new Exception("Bạn không có quyền thực hiện hành động này.");
    }

    // 3. Validate ID User
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    if ($user_id <= 0) throw new Exception("ID người dùng không hợp lệ.");

    // 4. Kiểm tra file upload
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Vui lòng chọn ảnh đại diện.");
    }

    // 5. Cấu hình thư mục lưu (Dùng hằng số từ config.php đã định nghĩa)
    $uploadDir = UPLOAD_PATH . '/avatars/'; 
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileInfo = pathinfo($_FILES['avatar']['name']);
    $ext = strtolower($fileInfo['extension']);
    
    // 6. Kiểm tra định dạng và dung lượng
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) throw new Exception("Chỉ chấp nhận các định dạng: " . implode(', ', $allowed));
    if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) throw new Exception("Dung lượng ảnh tối đa là 5MB.");

    // 7. LẤY THÔNG TIN ẢNH CŨ ĐỂ DỌN DẸP
    $stmtOld = $conn->prepare("SELECT avatar FROM user WHERE user_id = ?");
    $stmtOld->bind_param("i", $user_id);
    $stmtOld->execute();
    $oldData = $stmtOld->get_result()->fetch_assoc();
    $oldAvatarPath = $oldData['avatar'] ?? '';

    // 8. Tạo tên file mới (tránh trùng lặp)
    $newFileName = "avatar_" . $user_id . "_" . time() . "." . $ext;
    $targetFile = $uploadDir . $newFileName;

    // 9. Di chuyển file vào thư mục lưu trữ
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
        // Đường dẫn tương đối để lưu vào Database
        $dbPath = "uploads/avatars/" . $newFileName; 
        
        $stmt = $conn->prepare("UPDATE user SET avatar = ? WHERE user_id = ?");
        $stmt->bind_param("si", $dbPath, $user_id);
        
        if ($stmt->execute()) {
            // XÓA FILE CŨ (Nếu có và không phải ảnh mặc định)
            if (!empty($oldAvatarPath)) {
                $fullOldPath = ROOT_PATH . '/' . $oldAvatarPath;
                if (file_exists($fullOldPath)) {
                    @unlink($fullOldPath);
                }
            }

            // Ghi log hoạt động của admin
            if (function_exists('writeAdminLog')) {
                writeAdminLog($conn, $_SESSION['user_id'], "Cập nhật avatar cho User ID: $user_id", $user_id);
            }

            echo json_encode([
                'status' => 'success', 
                'message' => 'Đã cập nhật ảnh đại diện thành công!',
                'data' => [
                    'avatar_url' => SITE_URL . '/' . $dbPath // Trả về link tuyệt đối để frontend dễ hiển thị
                ] 
            ]);
        } else {
            throw new Exception("Lỗi cập nhật dữ liệu vào Database.");
        }
    } else {
        throw new Exception("Không thể lưu file vào máy chủ. Kiểm tra quyền thư mục.");
    }

} catch (Exception $e) {
    if (http_response_code() === 200) http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>