<?php
/**
 * mark_read.php - Đánh dấu thông báo đã đọc
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

$thong_bao_id = (int)($_POST['thong_bao_id'] ?? 0);

if ($thong_bao_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
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
    
    // Kiểm tra xem thông báo có thuộc về user này không và đánh dấu đã đọc
    $stmt = $pdo->prepare("UPDATE ThongBao SET da_doc = 1 WHERE thong_bao_id = :id AND khach_hang_nhan_id = :kh_id");
    $stmt->execute([':id' => $thong_bao_id, ':kh_id' => $khach_hang_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Đã đánh dấu đã đọc']);
    } else {
        // Kiểm tra xem có phải là thông báo từ Hotro không
        // Hotro không có trường da_doc, nhưng có thể cập nhật trạng thái
        $stmt = $pdo->prepare("SELECT ho_tro_id FROM Hotro WHERE ho_tro_id = :id AND khach_hang_id = :kh_id");
        $stmt->execute([':id' => $thong_bao_id, ':kh_id' => $khach_hang_id]);
        $hotro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($hotro) {
            // Đối với Hotro, không có trường da_doc, nhưng vẫn trả về success
            // Vì trong get_inbox.php, Hotro luôn có da_doc = 0
            echo json_encode(['success' => true, 'message' => 'Đã đánh dấu đã đọc']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông báo hoặc bạn không có quyền']);
        }
    }
    
} catch (Exception $e) {
    error_log('Mark read error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

