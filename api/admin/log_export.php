<?php
/**
 * API: api/admin/log_export.php
 * Chức năng: Xuất lịch sử hoạt động Admin ra file CSV (Excel)
 * Thực hiện bởi: Giang & Nhóm 7
 */

session_start();

// 1. Kiểm tra quyền truy cập (Quan trọng nhất)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Truy cập bị từ chối.");
}

// 2. Cài đặt hệ thống
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once '../../config/config.php'; // Sử dụng config chung của dự án
require_once '../../includes/log_helper.php';

// Xóa sạch bộ nhớ đệm để tránh ký tự thừa dính vào file CSV
while (ob_get_level()) ob_end_clean();

// 3. Ghi log hành động xuất file
if (function_exists('writeAdminLog')) {
    $log_action = "Xuất báo cáo lịch sử hệ thống (CSV)";
    if (!empty($_GET['start_date']) || !empty($_GET['search'])) {
        $log_action .= " [Có bộ lọc]";
    }
    writeAdminLog($conn, $_SESSION['user_id'], $log_action, 0);
}

// 4. Thiết lập Header tải file
$filename = "Export_Log_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 5. Mở luồng ghi và xử lý Font Tiếng Việt
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // Thêm BOM để Excel không lỗi font Tiếng Việt

// Tiêu đề cột
fputcsv($output, ['ID', 'Quản trị viên', 'Hành động', 'ID Đối tượng', 'Thời gian', 'Địa chỉ IP', 'Thiết bị']);

// 6. Xây dựng truy vấn an toàn (Prepared Statement)
$where = " WHERE 1=1 ";
$params = [];
$types = "";

if (!empty($_GET['search'])) {
    $searchTerm = "%" . trim($_GET['search']) . "%";
    $where .= " AND (l.action LIKE ? OR u.name LIKE ? OR l.ip_address LIKE ?)";
    $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($_GET['start_date'])) {
    $where .= " AND DATE(l.created_at) >= ?";
    $params[] = $_GET['start_date'];
    $types .= "s";
}

if (!empty($_GET['end_date'])) {
    $where .= " AND DATE(l.created_at) <= ?";
    $params[] = $_GET['end_date'];
    $types .= "s";
}

$sql = "SELECT 
            l.log_id, 
            IFNULL(u.name, 'Hệ thống') as admin_name, 
            l.action, 
            l.target_id, 
            l.created_at,
            l.ip_address,
            l.user_agent
        FROM admin_log l
        LEFT JOIN user u ON l.admin_id = u.user_id
        $where 
        ORDER BY l.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 7. Ghi dữ liệu vào CSV
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Định dạng lại ngày tháng cho Excel dễ đọc (YYYY-MM-DD HH:MM:SS)
        $row['created_at'] = date('d/m/Y H:i', strtotime($row['created_at']));
        
        // Rút gọn thông tin thiết bị (User Agent) cho gọn file
        $ua = $row['user_agent'];
        if (strpos($ua, 'Windows') !== false) $device = "Windows";
        elseif (strpos($ua, 'Android') !== false) $device = "Android";
        elseif (strpos($ua, 'iPhone') !== false) $device = "iPhone";
        else $device = "Khác";
        
        $row['user_agent'] = $device;

        fputcsv($output, [
            $row['log_id'],
            $row['admin_name'],
            $row['action'],
            $row['target_id'] ?: '-',
            $row['created_at'],
            $row['ip_address'] ?: '-',
            $row['user_agent']
        ]);
    }
}

fclose($output);
exit();