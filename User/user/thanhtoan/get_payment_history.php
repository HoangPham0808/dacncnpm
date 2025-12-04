<?php
/**
 * get_payment_history.php - Lấy lịch sử thanh toán từ database
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập', 'payments' => []]);
    exit;
}

try {
    $username = $_SESSION['user']['username'];
    
    // Lấy khach_hang_id
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng', 'payments' => []]);
        exit;
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // Lấy lịch sử thanh toán từ bảng HoaDon - Group by để tránh duplicate
    $stmt = $pdo->prepare("SELECT DISTINCT hd.hoa_don_id, hd.ma_hoa_don, hd.ngay_lap, hd.tong_tien, hd.giam_gia_khuyen_mai, 
                                  hd.giam_gia_khac, hd.tien_thanh_toan, hd.phuong_thuc_thanh_toan, hd.trang_thai,
                                  GROUP_CONCAT(DISTINCT cthd.ten_goi SEPARATOR ', ') as ten_goi
                           FROM HoaDon hd
                           LEFT JOIN ChiTietHoaDon cthd ON hd.hoa_don_id = cthd.hoa_don_id
                           WHERE hd.khach_hang_id = :kh_id 
                           GROUP BY hd.hoa_don_id
                           ORDER BY hd.ngay_lap DESC, hd.hoa_don_id DESC");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu
    foreach ($payments as &$payment) {
        $payment['ngay_lap'] = date('d/m/Y', strtotime($payment['ngay_lap']));
        $payment['tong_tien'] = number_format($payment['tong_tien'], 0, ',', '.');
        $payment['tien_thanh_toan'] = number_format($payment['tien_thanh_toan'], 0, ',', '.');
        if ($payment['giam_gia_khuyen_mai']) {
            $payment['giam_gia_khuyen_mai'] = number_format($payment['giam_gia_khuyen_mai'], 0, ',', '.');
        }
        if ($payment['giam_gia_khac']) {
            $payment['giam_gia_khac'] = number_format($payment['giam_gia_khac'], 0, ',', '.');
        }
    }
    
    echo json_encode([
        'success' => true,
        'payments' => $payments
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get payment history error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
        'payments' => []
    ], JSON_UNESCAPED_UNICODE);
}

