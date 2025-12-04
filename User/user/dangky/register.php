<?php
// register.php — xử lý ĐĂNG KÝ

// Đảm bảo không có output trước header
if (ob_get_level()) {
    ob_clean();
}
ob_start();

// Luôn nạp config + kết nối DB
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// BASE_URL đã được tự động detect trong config.php

// Hàm chuyển hướng về index.html kèm thông báo
function back_index($anchor, $msg, $type='error'){
    // Xóa output buffer trước khi redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Chuyển hướng về index.html với tham số msg và type
    $baseUrl = rtrim(BASE_URL, '/');
    // Nếu BASE_URL có chứa đường dẫn file system, sửa lại
    if (strpos($baseUrl, 'C:/') !== false || strpos($baseUrl, 'C:\\') !== false) {
        $baseUrl = '/doanchuyennganh';
    }
    $u = $baseUrl.'/index.html?msg='.urlencode($msg).'&type='.urlencode($type).'#'.$anchor;
    if (!headers_sent()) {
        header("Location: $u");
        exit;
    } else {
        echo '<script>window.location.href = "' . htmlspecialchars($u, ENT_QUOTES) . '";</script>';
        exit;
    }
}

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back_index('dang-ky', 'Yêu cầu không hợp lệ', 'error');
}

// Lấy dữ liệu từ form (linh hoạt với các tên trường có thể có)
$full_name = trim($_POST['full_name'] ?? $_POST['name'] ?? $_POST['fullname'] ?? '');
$username  = trim($_POST['username']  ?? '');
$email     = trim($_POST['email']     ?? $_POST['register_email'] ?? '');
$password  = $_POST['password']       ?? $_POST['register_password'] ?? '';
$confirm   = $_POST['password_confirm'] ?? $_POST['confirm'] ?? $_POST['repassword'] ?? '';

// === Kiểm tra dữ liệu -> báo lỗi ===
if ($email === '' || $password === '' || $full_name === '') { // Các trường bắt buộc
    back_index('dang-ky','Vui lòng nhập đầy đủ thông tin (Họ tên, Email, Mật khẩu)','error');
}
// Kiểm tra password confirm chỉ khi trường confirm được gửi lên
if (isset($_POST['password_confirm']) && $password !== $confirm) {
    back_index('dang-ky','Mật khẩu xác nhận không khớp','error');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // Email hợp lệ
    back_index('dang-ky','Email không hợp lệ','error');
}
if (strlen($password) < 6) { // Mật khẩu đủ dài
    back_index('dang-ky','Mật khẩu tối thiểu 6 ký tự','error');
}
// Nếu không có username, dùng email làm username
if ($username === '') {
    $username = $email;
}
// === Hết kiểm tra ===

try {
    // Kiểm tra trùng username trong TaiKhoan
    $stmt = $pdo->prepare("SELECT ten_dang_nhap FROM TaiKhoan WHERE ten_dang_nhap = :u LIMIT 1");
    $stmt->execute([':u'=>$username]);
    if ($stmt->fetch()) {
        back_index('dang-ky','Tên đăng nhập đã tồn tại','error');
    }
    
    // Kiểm tra trùng email trong KhachHang
    $stmt = $pdo->prepare("SELECT email FROM KhachHang WHERE email = :e LIMIT 1");
    $stmt->execute([':e'=>$email]);
    if ($stmt->fetch()) {
        back_index('dang-ky','Email đã được đăng ký','error');
    }

    // Không mã hóa mật khẩu (lưu plain text)

    // Bắt đầu transaction
    $pdo->beginTransaction();

    try {
        // 1. Thêm vào bảng TaiKhoan
        $insTaiKhoan = $pdo->prepare("INSERT INTO TaiKhoan (ten_dang_nhap, mat_khau, loai_tai_khoan, trang_thai)
                                      VALUES (:u, :p, 'Khách hàng', 'Hoạt động')");
        $insTaiKhoan->execute([
            ':u' => $username,
            ':p' => $password
        ]);

        // 2. Thêm vào bảng KhachHang
        $insKhachHang = $pdo->prepare("INSERT INTO KhachHang (ten_dang_nhap, ho_ten, email, ngay_dang_ky, trang_thai)
                                       VALUES (:u, :name, :e, CURDATE(), 'Hoạt động')");
        $insKhachHang->execute([
            ':u' => $username,
            ':name' => $full_name,
            ':e' => $email
        ]);

        // Commit transaction
        $pdo->commit();

        // Đăng ký thành công -> báo thành công và yêu cầu đăng nhập
        back_index('dang-nhap','Đăng ký thành công! Vui lòng đăng nhập.','success');
        
    } catch (Throwable $e) {
        // Rollback nếu có lỗi
        $pdo->rollBack();
        throw $e;
    }

} catch (Throwable $e) { // Bắt lỗi hệ thống
    error_log('Register error: '.$e->getMessage()); // Ghi log
    back_index('dang-ky','Có lỗi hệ thống, vui lòng thử lại sau.','error'); // Báo lỗi chung
}