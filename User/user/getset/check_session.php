<?php
// check_session.php

// Chỉ bắt đầu session nếu chưa có session nào active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login($role=null){
  // Nạp config để lấy BASE_URL nếu chưa có
  if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../database/config.php';
  }
  
  if (empty($_SESSION['user'])) {
    // Chuyển hướng về trang chủ với modal đăng nhập
    header("Location: ".rtrim(BASE_URL,'/')."/index.html#dang-nhap"); 
    exit;
  }
  if ($role && ($_SESSION['user']['role']??'user') !== $role){
    http_response_code(403);
    echo "Bạn không có quyền truy cập.";
    exit;
  }
}
?>