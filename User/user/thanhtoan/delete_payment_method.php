<?php
/**
 * delete_payment_method.php - Xóa phương thức thanh toán
 */
// Chỉ bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';
require_once __DIR__ . '/../getset/check_session.php';

// Kiểm tra đăng nhập
require_login();

// Lấy referer để redirect về đúng trang
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$redirect_url = '../goitap/packages.html';

// Xác định trang hiện tại từ referer
if (strpos($referer, 'index.html') !== false || strpos($referer, '/index.html') !== false) {
    $redirect_url = '../index.html';
} elseif (strpos($referer, 'packages.html') !== false || strpos($referer, '/packages.html') !== false || strpos($referer, 'goitap') !== false) {
    $redirect_url = '../goitap/packages.html';
} elseif (strpos($referer, 'schedule.html') !== false || strpos($referer, '/schedule.html') !== false) {
    $redirect_url = '../lichtap/schedule.html';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect_url);
    exit;
}

$phuong_thuc_id = isset($_POST['phuong_thuc_id']) ? (int)$_POST['phuong_thuc_id'] : 0;

if ($phuong_thuc_id <= 0) {
    header('Location: ' . $redirect_url . '?msg=' . urlencode('ID phương thức không hợp lệ') . '&type=error');
    exit;
}

try {
    $username = $_SESSION['user']['username'];
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        header('Location: ' . $redirect_url . '?msg=' . urlencode('Không tìm thấy thông tin khách hàng') . '&type=error');
        exit;
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // Kiểm tra phương thức thanh toán thuộc về người dùng này
    $stmt = $pdo->prepare("SELECT phuong_thuc_id FROM PhuongThucThanhToan 
                           WHERE phuong_thuc_id = :pt_id AND khach_hang_id = :kh_id");
    $stmt->execute([
        ':pt_id' => $phuong_thuc_id,
        ':kh_id' => $khach_hang_id
    ]);
    
    if (!$stmt->fetch()) {
        header('Location: ' . $redirect_url . '?msg=' . urlencode('Phương thức thanh toán không tồn tại hoặc không thuộc về bạn') . '&type=error');
        exit;
    }
    
    // Xóa phương thức thanh toán
    $stmt = $pdo->prepare("DELETE FROM PhuongThucThanhToan 
                           WHERE phuong_thuc_id = :pt_id AND khach_hang_id = :kh_id");
    $stmt->execute([
        ':pt_id' => $phuong_thuc_id,
        ':kh_id' => $khach_hang_id
    ]);
    
    header('Location: ' . $redirect_url . '?msg=' . urlencode('Đã xóa phương thức thanh toán thành công') . '&type=success');
    exit;
    
} catch (Exception $e) {
    error_log('Delete payment method error: ' . $e->getMessage());
    header('Location: ' . $redirect_url . '?msg=' . urlencode('Có lỗi xảy ra khi xóa phương thức thanh toán') . '&type=error');
    exit;
}

