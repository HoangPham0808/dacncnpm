<?php
/**
 * get_my_packages.php - Lấy danh sách gói tập của khách hàng từ database
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập', 'packages' => []]);
    exit;
}

try {
    $username = $_SESSION['user']['username'];
    
    // Lấy khach_hang_id
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng', 'packages' => []]);
        exit;
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // Lấy tất cả các gói tập từ bảng DangKyGoiTap
    $stmt = $pdo->prepare("SELECT dk.dang_ky_id, dk.goi_tap_id, dk.ngay_dang_ky, dk.ngay_bat_dau, dk.ngay_ket_thuc, 
                                  dk.tong_tien, dk.trang_thai, gt.ten_goi, gt.ma_goi_tap, gt.loai_goi,
                                  DATEDIFF(dk.ngay_ket_thuc, CURDATE()) as so_ngay_con_lai,
                                  DATEDIFF(CURDATE(), dk.ngay_bat_dau) as so_ngay_da_dung,
                                  DATEDIFF(dk.ngay_ket_thuc, dk.ngay_bat_dau) as tong_so_ngay
                           FROM DangKyGoiTap dk
                           LEFT JOIN GoiTap gt ON dk.goi_tap_id = gt.goi_tap_id
                           WHERE dk.khach_hang_id = :kh_id 
                           ORDER BY dk.dang_ky_id DESC");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu và tính tiền hoàn lại
    foreach ($packages as &$pkg) {
        $pkg['ngay_dang_ky'] = date('d/m/Y', strtotime($pkg['ngay_dang_ky']));
        $pkg['ngay_bat_dau'] = date('d/m/Y', strtotime($pkg['ngay_bat_dau']));
        $pkg['ngay_ket_thuc'] = date('d/m/Y', strtotime($pkg['ngay_ket_thuc']));
        
        // Lưu giá trị gốc của tong_tien trước khi format
        $tong_tien_goc = (float)$pkg['tong_tien'];
        $pkg['tong_tien'] = number_format($tong_tien_goc, 0, ',', '.');
        
        $pkg['so_ngay_con_lai'] = max(0, (int)$pkg['so_ngay_con_lai']);
        $pkg['so_ngay_da_dung'] = max(0, (int)$pkg['so_ngay_da_dung']);
        $pkg['tong_so_ngay'] = max(1, (int)$pkg['tong_so_ngay']);
        
        // Tính tiền hoàn lại (giống logic trong cancel_package.php)
        $so_ngay_con_lai = $pkg['so_ngay_con_lai'];
        $tong_so_ngay = $pkg['tong_so_ngay'];
        
        $ty_le_hoan_tien = $tong_so_ngay > 0 ? ($so_ngay_con_lai / $tong_so_ngay) : 0;
        $tien_hoan_lai = $tong_tien_goc * $ty_le_hoan_tien;
        
        // Làm tròn đến 1000đ
        $tien_hoan_lai = round($tien_hoan_lai / 1000) * 1000;
        
        // Chính sách: Nếu đã dùng > 50% thời gian, không hoàn tiền
        if ($ty_le_hoan_tien <= 0.5) {
            $tien_hoan_lai = 0;
        }
        
        $pkg['tien_hoan_lai'] = (int)$tien_hoan_lai;
    }
    
    echo json_encode([
        'success' => true,
        'packages' => $packages
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get my packages error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
        'packages' => []
    ], JSON_UNESCAPED_UNICODE);
}

