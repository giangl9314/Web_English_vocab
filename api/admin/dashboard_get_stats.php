<?php
// FILE: api/admin/dashboard_get_stats.php

// 1. Cấu hình & Bảo mật
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Tắt hiển thị lỗi trực tiếp để tránh lộ cấu trúc folder, chỉ log vào file
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';

// 2. Định nghĩa Hằng số
const LIMIT_RECENT_LOGS = 6;
const LIMIT_TOP_COURSES = 5;
const LIMIT_CHART_MONTHS = 6;

// 3. Kiểm tra Session (Bỏ phần gán cứng user_id để bảo mật)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Truy cập bị từ chối"]);
    exit();
}

try {
    if (!$conn) throw new Exception("Lỗi kết nối CSDL.");
    $response = [];

    // --- A. THỐNG KÊ TỔNG QUAN ---
    
    // 1. Tổng người dùng (loại bỏ admin ra khỏi thống kê học viên)
    $sqlUser = "SELECT COUNT(*) as total FROM user WHERE role = 'user'";
    $res = $conn->query($sqlUser);
    $response['total_users'] = (int)$res->fetch_assoc()['total'];

    // 2. Tổng khóa học
    $sqlCourse = "SELECT COUNT(*) as total FROM course WHERE hide = 0";
    $res = $conn->query($sqlCourse);
    $response['total_courses'] = (int)$res->fetch_assoc()['total'];

    // 3. Người dùng mới hôm nay
    $sqlToday = "SELECT COUNT(*) as total FROM user 
                 WHERE role = 'user' AND DATE(created_at) = CURDATE()";
    $res = $conn->query($sqlToday);
    $response['today_activity'] = (int)$res->fetch_assoc()['total'];

    // --- B. DỮ LIỆU DANH SÁCH & BIỂU ĐỒ ---

    // 4. Log hoạt động (Recent Activities)
    $response['recent_activities'] = [];
    $sqlLog = "SELECT l.action, l.created_at, u.name as admin_name 
               FROM admin_log l 
               LEFT JOIN user u ON l.admin_id = u.user_id 
               ORDER BY l.created_at DESC LIMIT " . LIMIT_RECENT_LOGS;
    $resLog = $conn->query($sqlLog);
    if ($resLog) {
        while ($row = $resLog->fetch_assoc()) {
            $response['recent_activities'][] = $row;
        }
    }

    // 5. Khóa học phổ biến (Dùng INNER JOIN để đảm bảo chỉ lấy khóa học có người học)
    $response['popular_courses'] = [];
    $sqlTop = "SELECT c.course_name, COUNT(uc.user_id) as learning_count 
               FROM course c 
               JOIN user_course uc ON c.course_id = uc.course_id 
               WHERE c.hide = 0
               GROUP BY c.course_id 
               ORDER BY learning_count DESC LIMIT " . LIMIT_TOP_COURSES;
    $resTop = $conn->query($sqlTop);
    if ($resTop) {
        while ($row = $resTop->fetch_assoc()) {
            $response['popular_courses'][] = $row;
        }
    }

    // 6. Dữ liệu biểu đồ (Thống kê user 6 tháng gần nhất)
    $response['user_chart'] = [];
    $sqlChart = "SELECT DATE_FORMAT(created_at, '%m/%Y') as month_year, COUNT(*) as count 
                 FROM user 
                 WHERE role = 'user'
                 GROUP BY month_year 
                 ORDER BY MAX(created_at) DESC 
                 LIMIT " . LIMIT_CHART_MONTHS;
    
    $resChart = $conn->query($sqlChart);
    if ($resChart) {
        while ($row = $resChart->fetch_assoc()) {
            $response['user_chart'][] = $row;
        }
    }
    // Đảo ngược lại để biểu đồ chạy từ cũ đến mới
    $response['user_chart'] = array_reverse($response['user_chart']);

    echo json_encode(['status' => 'success', 'data' => $response]);

} catch (Exception $e) {
    error_log($e->getMessage()); // Ghi lỗi vào log hệ thống thay vì hiện ra ngoài
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Đã xảy ra lỗi khi tải dữ liệu thống kê.']);
}
?>