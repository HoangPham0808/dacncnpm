<?php
/**
 * Forgot Password Controller
 * Xử lý logic các bước quên mật khẩu
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'forgot_password_model.php';

$error_message = '';
$success_message = '';
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// ============================================
// BƯỚC 1: NHẬP TÊN ĐĂNG NHẬP
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == '1') {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
        $error_message = 'Vui lòng nhập tên đăng nhập!';
    } else {
        try {
            $user = getUserByUsername($username);
            
            if (!$user) {
                $error_message = 'Tên đăng nhập không tồn tại!';
            } elseif ($user['vai_tro'] !== 'PT' && $user['vai_tro'] !== 'PR' && $user['vai_tro'] !== 'Lễ Tân') {
                $error_message = 'Chức năng này chỉ dành cho nhân viên PT, PR và Lễ Tân!';
            } elseif ($user['trang_thai'] !== 'Hoạt động') {
                $error_message = 'Tài khoản của bạn đã bị khóa!';
            } elseif (empty($user['email']) || empty($user['sdt'])) {
                $error_message = 'Tài khoản chưa có đầy đủ email và số điện thoại!';
            } else {
                // Lưu thông tin vào session
                $_SESSION['reset_username'] = $user['ten_dang_nhap'];
                $_SESSION['reset_email'] = $user['email'];
                $_SESSION['reset_sdt'] = $user['sdt'];
                $_SESSION['reset_ho_ten'] = $user['ho_ten'];
                
                // Chuyển sang bước 2
                header('Location: forgot_password.php?step=2');
                exit();
            }
        } catch (Exception $e) {
            $error_message = 'Đã xảy ra lỗi. Vui lòng thử lại!';
        }
    }
}

// ============================================
// BƯỚC 2: XÁC THỰC EMAIL VÀ SỐ ĐIỆN THOẠI
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == '2') {
    $input_email = trim($_POST['email'] ?? '');
    $input_sdt = trim($_POST['sdt'] ?? '');
    
    if (empty($input_email) || empty($input_sdt)) {
        $error_message = 'Vui lòng điền đầy đủ thông tin!';
    } else {
        // Chuẩn hóa số điện thoại (chỉ lấy số)
        $input_sdt_clean = preg_replace('/[^0-9]/', '', $input_sdt);
        $session_sdt_clean = preg_replace('/[^0-9]/', '', $_SESSION['reset_sdt'] ?? '');
        
        // So sánh email và số điện thoại
        if (strtolower($input_email) === strtolower($_SESSION['reset_email'] ?? '') && 
            $input_sdt_clean === $session_sdt_clean) {
            // Xác thực thành công
            $_SESSION['reset_verified'] = true;
            header('Location: forgot_password.php?step=3');
            exit();
        } else {
            $error_message = 'Thông tin xác thực không đúng!';
        }
    }
}

// ============================================
// BƯỚC 3: ĐẶT MẬT KHẨU MỚI
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == '3') {
    // Kiểm tra đã xác thực chưa
    if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
        header('Location: forgot_password.php?step=2');
        exit();
    }
    
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Mật khẩu xác nhận không khớp!';
    } else {
        try {
            updatePassword($_SESSION['reset_username'], $new_password);
            
            // Xóa session
            session_destroy();
            session_start();
            
            $success_message = 'Đặt lại mật khẩu thành công! Đang chuyển đến trang đăng nhập...';
            header('refresh:3;url=../login.php');
        } catch (Exception $e) {
            $error_message = 'Đã xảy ra lỗi khi đặt lại mật khẩu!';
        }
    }
}

// Kiểm tra session
if ($step == 2 && empty($_SESSION['reset_username'])) {
    header('Location: forgot_password.php?step=1');
    exit();
}

if ($step == 3 && (empty($_SESSION['reset_username']) || !isset($_SESSION['reset_verified']))) {
    header('Location: forgot_password.php?step=2');
    exit();
}

// Lấy thông tin để hiển thị (ẩn một phần)
$masked_email = '';
$masked_sdt = '';
if (!empty($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
    $parts = explode('@', $email);
    if (count($parts) == 2) {
        $local = $parts[0];
        $domain = $parts[1];
        $masked_email = substr($local, 0, 2) . '***@' . $domain;
    }
}

if (!empty($_SESSION['reset_sdt'])) {
    $sdt = $_SESSION['reset_sdt'];
    $masked_sdt = substr($sdt, 0, 3) . '***' . substr($sdt, -2);
}

