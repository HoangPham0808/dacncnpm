<?php
/**
 * Account Controller
 * Xử lý các action: change_password
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'account_model.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../index_internal/login.php');
    exit();
}

// Kiểm tra vai trò PT, PR hoặc Lễ Tân
if (!isset($_SESSION['vai_tro']) || ($_SESSION['vai_tro'] !== 'PT' && $_SESSION['vai_tro'] !== 'PR' && $_SESSION['vai_tro'] !== 'Lễ Tân')) {
    die('Bạn không có quyền truy cập trang này!');
}

$message = '';
$messageType = '';

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($currentPassword)) {
        $errors[] = 'Vui lòng nhập mật khẩu hiện tại!';
    }
    
    if (empty($newPassword)) {
        $errors[] = 'Vui lòng nhập mật khẩu mới!';
    } elseif (strlen($newPassword) < 6) {
        $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    }
    
    if (empty($confirmPassword)) {
        $errors[] = 'Vui lòng xác nhận mật khẩu mới!';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Mật khẩu xác nhận không khớp!';
    }
    
    if (empty($errors)) {
        try {
            $username = $_SESSION['username'];
            
            if (!verifyCurrentPassword($username, $currentPassword)) {
                $errors[] = 'Mật khẩu hiện tại không đúng!';
            } else {
                updatePassword($username, $newPassword);
                $message = 'Đổi mật khẩu thành công!';
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $errors[] = 'Lỗi khi đổi mật khẩu: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

