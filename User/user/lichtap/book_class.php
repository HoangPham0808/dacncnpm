<?php
// book_class.php - Xử lý đăng ký lớp tập
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: ../index.html#dang-nhap?msg=' . urlencode('Vui lòng đăng nhập để đăng ký lớp.') . '&type=error');
    exit;
}

// Kiểm tra POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedule.html?msg=' . urlencode('Yêu cầu không hợp lệ.') . '&type=error');
    exit;
}

// Lấy dữ liệu
$lich_tap_id = $_POST['lich_tap_id'] ?? null;

if (!$lich_tap_id) {
    back_to_schedule('Vui lòng chọn lớp để đăng ký.');
}

try {
    // 1. Lấy thông tin khách hàng và phòng tập
    $username = $_SESSION['user']['username'];
    $stmt = $pdo->prepare("SELECT khach_hang_id, phong_tap_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        back_to_schedule('Không tìm thấy thông tin khách hàng.');
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    $phong_tap_id = $khachHang['phong_tap_id'];
    
    if (!$phong_tap_id) {
        back_to_schedule('Bạn chưa có phòng tập. Vui lòng liên hệ quản trị viên.');
    }
    
    // 2. Kiểm tra gói tập còn hạn
    // Sử dụng DATE() để so sánh ngày chính xác hơn
    $stmt = $pdo->prepare("SELECT goi_tap_id, ngay_ket_thuc, ngay_bat_dau
                           FROM DangKyGoiTap 
                           WHERE khach_hang_id = :kh_id 
                           AND trang_thai = 'Đang hoạt động' 
                           AND DATE(ngay_ket_thuc) >= CURDATE()
                           ORDER BY ngay_ket_thuc DESC 
                           LIMIT 1");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    $goiTap = $stmt->fetch();
    
    if (!$goiTap) {
        back_to_schedule('Bạn chưa có gói tập hợp lệ. Vui lòng mua gói tập trước khi đăng ký lớp.');
    }
    
    // 3. Kiểm tra lớp tập có tồn tại, còn chỗ và thuộc phòng tập của khách hàng
    $stmt = $pdo->prepare("SELECT lt.lich_tap_id, lt.ten_lop, lt.so_luong_toi_da, 
                                  COUNT(dk.dang_ky_lich_id) as so_luong_da_dang_ky,
                                  lt.trang_thai, lt.phong_tap_id
                           FROM LichTap lt
                           LEFT JOIN DangKyLichTap dk ON lt.lich_tap_id = dk.lich_tap_id 
                               AND dk.trang_thai = 'Đã đăng ký'
                           WHERE lt.lich_tap_id = :lich_id
                           GROUP BY lt.lich_tap_id");
    $stmt->execute([':lich_id' => $lich_tap_id]);
    $lichTap = $stmt->fetch();
    
    if (!$lichTap) {
        back_to_schedule('Không tìm thấy lớp tập này.');
    }
    
    // Kiểm tra lớp tập có thuộc phòng tập của khách hàng không
    if ($lichTap['phong_tap_id'] != $phong_tap_id) {
        back_to_schedule('Lớp tập này không thuộc phòng tập của bạn. Bạn chỉ có thể đăng ký lịch tập của phòng tập mà bạn đã đăng ký.');
    }
    
    if ($lichTap['trang_thai'] !== 'Đang mở') {
        back_to_schedule('Lớp tập này đã đóng hoặc bị hủy.');
    }
    
    if ($lichTap['so_luong_da_dang_ky'] >= $lichTap['so_luong_toi_da']) {
        back_to_schedule('Lớp tập này đã đầy.');
    }
    
    // 4. Kiểm tra đã đăng ký lớp này chưa
    $stmt = $pdo->prepare("SELECT dang_ky_lich_id FROM DangKyLichTap 
                           WHERE khach_hang_id = :kh_id 
                           AND lich_tap_id = :lich_id 
                           AND trang_thai = 'Đã đăng ký'
                           LIMIT 1");
    $stmt->execute([
        ':kh_id' => $khach_hang_id,
        ':lich_id' => $lich_tap_id
    ]);
    
    if ($stmt->fetch()) {
        back_to_schedule('Bạn đã đăng ký lớp này rồi.');
    }
    
    // 5. Insert vào DangKyLichTap
    $stmt = $pdo->prepare("INSERT INTO DangKyLichTap (khach_hang_id, lich_tap_id, ngay_dang_ky, trang_thai)
                           VALUES (:kh_id, :lich_id, NOW(), 'Đã đăng ký')");
    $stmt->execute([
        ':kh_id' => $khach_hang_id,
        ':lich_id' => $lich_tap_id
    ]);
    
    // 6. Cập nhật trạng thái lớp nếu đầy
    $so_luong_con_lai = $lichTap['so_luong_toi_da'] - $lichTap['so_luong_da_dang_ky'] - 1;
    if ($so_luong_con_lai <= 0) {
        $stmt = $pdo->prepare("UPDATE LichTap SET trang_thai = 'Đã đầy' WHERE lich_tap_id = :lich_id");
        $stmt->execute([':lich_id' => $lich_tap_id]);
    }
    
    // Thành công
    $ten_lop = htmlspecialchars($lichTap['ten_lop']);
    header('Location: schedule.html?msg=' . urlencode("Đăng ký lớp '{$ten_lop}' thành công!") . '&type=success');
    exit;
    
} catch (PDOException $e) {
    error_log("Book class error: " . $e->getMessage());
    back_to_schedule('Có lỗi xảy ra khi đăng ký lớp. Vui lòng thử lại.');
}

function back_to_schedule($message) {
    header('Location: schedule.html?msg=' . urlencode($message) . '&type=error');
    exit;
}

