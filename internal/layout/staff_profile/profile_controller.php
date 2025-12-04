<?php
/**
 * Profile Controller
 * Xử lý các action: update profile
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'profile_model.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../index_internal/login.php');
    exit();
}

// Kiểm tra vai trò PT, PR hoặc Lễ Tân
if (!isset($_SESSION['vai_tro']) || ($_SESSION['vai_tro'] !== 'PT' && $_SESSION['vai_tro'] !== 'PR' && $_SESSION['vai_tro'] !== 'Lễ Tân')) {
    die('Bạn không có quyền truy cập trang này!');
}

$vai_tro = $_SESSION['vai_tro'];
$message = '';
$messageType = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $hoTen = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $cccd = trim($_POST['cccd'] ?? '');
    $diaChi = trim($_POST['dia_chi'] ?? '');
    $ngaySinh = trim($_POST['ngay_sinh'] ?? '');
    $gioiTinh = trim($_POST['gioi_tinh'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($hoTen)) {
        $errors[] = 'Họ tên không được để trống!';
    }
    
    if (empty($email)) {
        $errors[] = 'Email không được để trống!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ!';
    }
    
    if (empty($sdt)) {
        $errors[] = 'Số điện thoại không được để trống!';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $sdt)) {
        $errors[] = 'Số điện thoại phải có 10-11 chữ số!';
    }
    
    if (empty($cccd)) {
        $errors[] = 'CCCD không được để trống!';
    } elseif (!preg_match('/^[0-9]{9,12}$/', $cccd)) {
        $errors[] = 'CCCD phải có 9-12 chữ số!';
    }
    
    if (empty($ngaySinh)) {
        $errors[] = 'Ngày sinh không được để trống!';
    }
    
    if (empty($gioiTinh)) {
        $errors[] = 'Giới tính không được để trống!';
    }
    
    if (empty($errors)) {
        try {
            $username = $_SESSION['username'];
            $data = [
                'ho_ten' => $hoTen,
                'email' => $email,
                'sdt' => $sdt,
                'cccd' => $cccd,
                'dia_chi' => $diaChi,
                'ngay_sinh' => $ngaySinh,
                'gioi_tinh' => $gioiTinh
            ];
            
            updateEmployeeProfile($username, $vai_tro, $data);
            
            // Cập nhật session
            $_SESSION['ho_ten'] = $hoTen;
            
            $message = 'Cập nhật thông tin thành công!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Lỗi khi cập nhật: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

