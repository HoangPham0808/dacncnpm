<?php
/**
 * mark_all_read.php - Đánh dấu tất cả thông báo đã đọc
 */
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

try {
    $username = $_SESSION['user']['username'];
    
    // Lấy khach_hang_id
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$khachHang || !isset($khachHang['khach_hang_id'])) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng']);
        exit;
    }
    
    $khach_hang_id = (int)$khachHang['khach_hang_id'];
    
    // Đánh dấu tất cả thông báo trong ThongBao đã đọc
    $stmt = $pdo->prepare("UPDATE ThongBao SET da_doc = 1 WHERE khach_hang_nhan_id = :kh_id AND da_doc = 0");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    $thongBaoCount = $stmt->rowCount();
    
    // Đánh dấu tất cả thông báo trong Hotro (không có trường da_doc, nhưng có thể cập nhật trạng thái)
    // Lưu ý: Hotro không có trường da_doc, nên chỉ cập nhật ThongBao
    
    echo json_encode([
        'success' => true, 
        'message' => 'Đã đánh dấu tất cả thông báo đã đọc',
        'updated_count' => $thongBaoCount
    ]);
    
} catch (Exception $e) {
    error_log('Mark all read error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

