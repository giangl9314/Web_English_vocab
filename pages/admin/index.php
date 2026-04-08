<?php 
require_once __DIR__ . '/../../config/config.php'; 
require_once __DIR__ . '/../../includes/auth_check.php';
check_admin_auth();
$requested_file = $_GET['page'] ?? 'trangchu_admin.html';

include $page;
?> 