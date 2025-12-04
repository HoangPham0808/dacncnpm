<?php
/**
 * get_profile.php - Lấy thông tin tài khoản từ database
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

try {
    $username = $_SESSION['user']['username'];
    
    // Lấy thông tin khách hàng từ bảng KhachHang
    $stmt = $pdo->prepare("SELECT khach_hang_id, ho_ten, email, sdt, cccd, dia_chi, ngay_sinh, gioi_tinh, ngay_dang_ky, trang_thai
                           FROM KhachHang 
                           WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin tài khoản']);
        exit;
    }
    
    // Format ngày sinh
    if ($profile['ngay_sinh']) {
        $profile['ngay_sinh'] = date('Y-m-d', strtotime($profile['ngay_sinh']));
    }
    
    // Format ngày đăng ký
    if ($profile['ngay_dang_ky']) {
        $profile['ngay_dang_ky'] = date('d/m/Y', strtotime($profile['ngay_dang_ky']));
    }
    
    echo json_encode([
        'success' => true,
        'profile' => $profile
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get profile error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

