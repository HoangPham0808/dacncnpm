<?php
/**
 * Xử lý hủy gói tập và hoàn tiền
 */

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../dangnhap/login.php');
    exit;
}

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Chỉ xử lý POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: packages.html?msg=' . urlencode('Yêu cầu không hợp lệ.') . '&type=error');
    exit;
}

// Lấy thông tin khách hàng
$username = $_SESSION['user']['username'];
$stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
$stmt->execute([':username' => $username]);
$khachHang = $stmt->fetch();

if (!$khachHang) {
    header('Location: packages.html?msg=' . urlencode('Không tìm thấy thông tin khách hàng.') . '&type=error');
    exit;
}

$khach_hang_id = $khachHang['khach_hang_id'];
$dang_ky_id = isset($_POST['dang_ky_id']) ? (int)$_POST['dang_ky_id'] : 0;

if ($dang_ky_id <= 0) {
    header('Location: packages.html?msg=' . urlencode('Không tìm thấy gói tập cần hủy.') . '&type=error');
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 1. Kiểm tra gói tập có thuộc về user này không và đang hoạt động
    // Lấy cả phong_tap_id từ hóa đơn gốc hoặc từ khách hàng
    $stmt = $pdo->prepare("SELECT dk.dang_ky_id, dk.khach_hang_id, dk.goi_tap_id, dk.hoa_don_id, 
                                  dk.ngay_bat_dau, dk.ngay_ket_thuc, dk.tong_tien, dk.trang_thai,
                                  hd.tong_tien as hoa_don_tong_tien, hd.trang_thai as hoa_don_trang_thai,
                                  hd.ngay_lap, hd.phong_tap_id as hoa_don_phong_tap_id,
                                  kh.phong_tap_id as khach_hang_phong_tap_id,
                                  gt.ten_goi
                           FROM DangKyGoiTap dk
                           LEFT JOIN HoaDon hd ON dk.hoa_don_id = hd.hoa_don_id
                           LEFT JOIN KhachHang kh ON dk.khach_hang_id = kh.khach_hang_id
                           LEFT JOIN GoiTap gt ON dk.goi_tap_id = gt.goi_tap_id
                           WHERE dk.dang_ky_id = :dang_ky_id 
                           AND dk.khach_hang_id = :kh_id
                           AND dk.trang_thai = 'Đang hoạt động'");
    $stmt->execute([
        ':dang_ky_id' => $dang_ky_id,
        ':kh_id' => $khach_hang_id
    ]);
    $goiTap = $stmt->fetch();
    
    if (!$goiTap) {
        throw new Exception('Không tìm thấy gói tập đang hoạt động hoặc bạn không có quyền hủy gói này.');
    }
    
    // Lấy phong_tap_id từ hóa đơn gốc, nếu không có thì lấy từ khách hàng
    $phong_tap_id = $goiTap['hoa_don_phong_tap_id'] ?? $goiTap['khach_hang_phong_tap_id'] ?? null;
    
    // 2. Tính số ngày đã sử dụng
    $ngay_bat_dau = new DateTime($goiTap['ngay_bat_dau']);
    $ngay_ket_thuc = new DateTime($goiTap['ngay_ket_thuc']);
    $ngay_hien_tai = new DateTime();
    
    // Nếu ngày hiện tại > ngày kết thúc, gói đã hết hạn
    if ($ngay_hien_tai > $ngay_ket_thuc) {
        throw new Exception('Gói tập đã hết hạn, không thể hủy và hoàn tiền.');
    }
    
    // Tính số ngày đã sử dụng
    $so_ngay_da_su_dung = $ngay_hien_tai->diff($ngay_bat_dau)->days + 1;
    $tong_so_ngay = $ngay_ket_thuc->diff($ngay_bat_dau)->days + 1;
    
    // 3. Tính tiền hoàn lại (tỷ lệ % số ngày chưa dùng)
    $so_ngay_con_lai = $tong_so_ngay - $so_ngay_da_su_dung;
    if ($so_ngay_con_lai < 0) {
        $so_ngay_con_lai = 0;
    }
    
    $ty_le_hoan_tien = $tong_so_ngay > 0 ? ($so_ngay_con_lai / $tong_so_ngay) : 0;
    $tien_hoan_lai = $goiTap['tong_tien'] * $ty_le_hoan_tien;
    
    // Làm tròn đến 1000đ
    $tien_hoan_lai = round($tien_hoan_lai / 1000) * 1000;
    
    // Chính sách: Nếu đã dùng > 50% thời gian, không hoàn tiền
    if ($ty_le_hoan_tien <= 0.5) {
        $tien_hoan_lai = 0;
    }
    
    // 4. Cập nhật trạng thái gói tập thành "Hủy"
    // Lưu ý: Bảng DangKyGoiTap không có cột ghi_chu trong schema mới
    $stmt = $pdo->prepare("UPDATE DangKyGoiTap 
                           SET trang_thai = 'Hủy'
                           WHERE dang_ky_id = :dang_ky_id");
    $stmt->execute([
        ':dang_ky_id' => $dang_ky_id
    ]);
    
    // 5. Tạo hóa đơn hoàn tiền (nếu có tiền hoàn lại)
    $hoa_don_hoan_tien_id = null;
    if ($tien_hoan_lai > 0) {
        // Tạo mã hóa đơn hoàn tiền
        $ma_hoa_don_hoan = 'HT' . date('YmdHis') . rand(100, 999);
        
        // Lưu ý: phuong_thuc_thanh_toan ENUM chỉ có: 'Tiền mặt', 'Chuyển khoản', 'Thẻ', 'Ví điện tử'
        // Vì đây là hoàn tiền, dùng 'Chuyển khoản' làm mặc định
        // Thêm phong_tap_id vào hóa đơn hoàn tiền
        $stmt = $pdo->prepare("INSERT INTO HoaDon (ma_hoa_don, khach_hang_id, ngay_lap, tong_tien, 
                                                    giam_gia_khac, tien_thanh_toan, phuong_thuc_thanh_toan, 
                                                    trang_thai, ghi_chu, phong_tap_id)
                               VALUES (:ma_hoa_don, :kh_id, CURDATE(), :tong_tien, 0, :tien_thanh_toan, 
                                       'Chuyển khoản', 'Đã thanh toán', :ghi_chu, :phong_tap_id)");
        $stmt->execute([
            ':ma_hoa_don' => $ma_hoa_don_hoan,
            ':kh_id' => $khach_hang_id,
            ':tong_tien' => $tien_hoan_lai,
            ':tien_thanh_toan' => $tien_hoan_lai,
            ':ghi_chu' => 'Hoàn tiền do hủy gói tập #' . $dang_ky_id . ' - ' . $goiTap['ten_goi'],
            ':phong_tap_id' => $phong_tap_id
        ]);
        
        $hoa_don_hoan_tien_id = $pdo->lastInsertId();
        
        // Tạo chi tiết hóa đơn hoàn tiền
        $stmt = $pdo->prepare("INSERT INTO ChiTietHoaDon (hoa_don_id, goi_tap_id, ten_goi, so_luong, 
                                                          don_gia, thanh_tien)
                               VALUES (:hoa_don_id, :goi_tap_id, :ten_goi, 1, :don_gia, :thanh_tien)");
        $stmt->execute([
            ':hoa_don_id' => $hoa_don_hoan_tien_id,
            ':goi_tap_id' => $goiTap['goi_tap_id'],
            ':ten_goi' => 'Hoàn tiền: ' . $goiTap['ten_goi'],
            ':don_gia' => $tien_hoan_lai,
            ':thanh_tien' => $tien_hoan_lai
        ]);
    }
    
    // 6. Hủy tất cả lịch tập đã đăng ký trong gói này (các lớp chưa diễn ra)
    $stmt = $pdo->prepare("UPDATE DangKyLichTap 
                           SET trang_thai = 'Hủy'
                           WHERE khach_hang_id = :kh_id 
                           AND trang_thai = 'Đã đăng ký'
                           AND lich_tap_id IN (
                               SELECT lich_tap_id FROM LichTap 
                               WHERE ngay_tap >= CURDATE()
                           )");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    
    $pdo->commit();
    
    // Thông báo thành công
    $message = 'Đã hủy gói tập thành công.';
    if ($tien_hoan_lai > 0) {
        $message .= ' Số tiền hoàn lại: ' . number_format($tien_hoan_lai, 0, ',', '.') . '₫';
        $message .= ' (đã dùng ' . $so_ngay_da_su_dung . '/' . $tong_so_ngay . ' ngày)';
    } else {
        $message .= ' (Đã sử dụng > 50% thời gian, không hoàn tiền theo chính sách)';
    }
    
    // Redirect về trang packages.html với hash để mở modal "Gói tập của bạn"
    header('Location: packages.html?msg=' . urlencode($message) . '&type=success#my-packages');
    exit;
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Cancel package error: " . $e->getMessage());
    header('Location: packages.html?msg=' . urlencode($e->getMessage()) . '&type=error');
    exit;
}

?>

