<?php
// FILE: api/admin/course_get_list.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/config.php';

try {
    // 1. KIỂM TRA QUYỀN TRUY CẬP
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        throw new Exception("Bạn không có quyền truy cập.");
    }

    $admin_id = $_SESSION['user_id'];
    
    // 2. LẤY VÀ LÀM SẠCH THAM SỐ ĐẦU VÀO
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $limit   = 10;
    $offset  = ($page - 1) * $limit;
    $search  = trim($_GET['search'] ?? '');
    $status  = trim($_GET['status'] ?? '');
    
    // Whitelist Sort để chống SQL Injection (Chỉ cho phép sắp xếp theo các cột này)
    $allowed_sort = ['created_at', 'course_name', 'course_code', 'visibility'];
    $sort_by      = in_array($_GET['sort_by'] ?? '', $allowed_sort) ? $_GET['sort_by'] : 'created_at';
    $order        = (strtoupper($_GET['order'] ?? '') === 'ASC') ? 'ASC' : 'DESC';

    // 3. XÂY DỰNG ĐIỀU KIỆN WHERE
    // Mặc định hide = 0 (Chỉ lấy các khóa học đã hoàn tất/finalize)
    $where = "WHERE c.hide = 0";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $where .= " AND (c.course_name LIKE ? OR c.course_code LIKE ?)";
        $term = "%$search%";
        $params[] = $term; $params[] = $term;
        $types .= "ss";
    }

    if (!empty($status)) {
        $dbStatus = ($status === 'active') ? 'public' : 'private';
        $where .= " AND c.visibility = ?";
        $params[] = $dbStatus;
        $types .= "s";
    }

    // 4. ĐẾM TỔNG SỐ BẢN GHI (Cho phân trang)
    $sqlCount = "SELECT COUNT(*) as total FROM course c $where";
    $stmtCount = $conn->prepare($sqlCount);
    if (!empty($params)) $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $totalRecords = $stmtCount->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // 5. LẤY DỮ LIỆU CHI TIẾT
    // Sử dụng GROUP_CONCAT để lấy danh sách tags gọn gàng
    $sqlData = "SELECT c.course_id, c.course_code, c.course_name, c.visibility, c.created_at, c.description, c.create_by,
                       IFNULL(u.name, 'Hệ thống') as author_name,
                       GROUP_CONCAT(t.tag_name SEPARATOR ', ') as tags
                FROM course c
                LEFT JOIN user u ON c.create_by = u.user_id
                LEFT JOIN course_tag ct ON c.course_id = ct.course_id
                LEFT JOIN tag t ON ct.tag_id = t.tag_id
                $where
                GROUP BY c.course_id
                ORDER BY c.$sort_by $order
                LIMIT ?, ?";
    
    // Thêm tham số cho LIMIT
    $dataParams = $params;
    $dataParams[] = $offset; $dataParams[] = $limit;
    $dataTypes = $types . "ii";

    $stmt = $conn->prepare($sqlData);
    $stmt->bind_param($dataTypes, ...$dataParams);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 6. XỬ LÝ LOGIC PHÂN QUYỀN RIÊNG BIỆT (FRONTEND DÙNG)
    foreach ($data as &$course) {
        $is_owner = ($course['create_by'] == $admin_id);
        $is_public = ($course['visibility'] === 'public');
        
        // Logic: Có thể sửa từ vựng nếu là chủ sở hữu HOẶC khóa học đó ở chế độ công khai
        $course['can_edit_vocab'] = ($is_owner || $is_public);
        
        // Làm sạch dữ liệu tránh null rác
        $course['tags'] = $course['tags'] ?: '';
    }

    // 7. TRẢ KẾT QUẢ
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => (int)$totalRecords
        ]
    ]);

} catch (Exception $e) {
    http_response_code(http_response_code() === 200 ? 500 : http_response_code());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}