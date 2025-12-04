<?php
session_start();

// Xóa tất cả session
$_SESSION = array();

// Xóa cookie session nếu có
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Xóa cookie remember_user nếu có
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// Hủy session
session_destroy();

// Redirect về trang đăng nhập
header('Location: login.php');
exit();
?>

