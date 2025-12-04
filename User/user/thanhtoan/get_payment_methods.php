<?php
/**
 * get_payment_methods.php - Lấy danh sách phương thức thanh toán đã lưu
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập', 'methods' => []]);
    exit;
}

try {
    $username = $_SESSION['user']['username'];
    
    // Lấy khach_hang_id
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng', 'methods' => []]);
        exit;
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // Tạo bảng nếu chưa có
    $pdo->exec("CREATE TABLE IF NOT EXISTS PhuongThucThanhToan (
        phuong_thuc_id INT AUTO_INCREMENT PRIMARY KEY,
        khach_hang_id INT NOT NULL,
        loai_phuong_thuc ENUM('Tiền mặt', 'Chuyển khoản', 'Thẻ', 'Ví điện tử') NOT NULL,
        ten_hien_thi VARCHAR(100) NOT NULL,
        thong_tin_chi_tiet VARCHAR(255),
        mac_dinh BOOLEAN DEFAULT 0,
        ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (khach_hang_id) REFERENCES KhachHang(khach_hang_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Lấy danh sách phương thức đã lưu
    $stmt = $pdo->prepare("SELECT phuong_thuc_id, loai_phuong_thuc, ten_hien_thi, thong_tin_chi_tiet, mac_dinh, ngay_tao
                           FROM PhuongThucThanhToan 
                           WHERE khach_hang_id = :kh_id 
                           ORDER BY mac_dinh DESC, ngay_tao DESC");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($methods as &$method) {
        $method['ngay_tao'] = date('d/m/Y', strtotime($method['ngay_tao']));
    }
    
    echo json_encode([
        'success' => true,
        'methods' => $methods
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get payment methods error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
        'methods' => []
    ], JSON_UNESCAPED_UNICODE);
}

