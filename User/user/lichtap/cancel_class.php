<?php
// cancel_class.php - Xử lý hủy đăng ký lớp tập
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: ../index.html#dang-nhap?msg=' . urlencode('Vui lòng đăng nhập.') . '&type=error');
    exit;
}

// Kiểm tra POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedule.html?msg=' . urlencode('Yêu cầu không hợp lệ.') . '&type=error');
    exit;
}

// Lấy dữ liệu
$dang_ky_lich_id = $_POST['dang_ky_lich_id'] ?? null;
$lich_tap_id = $_POST['lich_tap_id'] ?? null;

if (!$dang_ky_lich_id && !$lich_tap_id) {
    back_to_schedule('Vui lòng chọn lớp để hủy.');
}

try {
    // 1. Lấy thông tin khách hàng
    $username = $_SESSION['user']['username'];
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        back_to_schedule('Không tìm thấy thông tin khách hàng.');
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // 2. Kiểm tra đăng ký lớp có tồn tại và thuộc về user này không
    if ($dang_ky_lich_id) {
        // Hủy bằng dang_ky_lich_id
        $stmt = $pdo->prepare("SELECT dk.dang_ky_lich_id, dk.lich_tap_id, lt.ten_lop, lt.ngay_tap, lt.trang_thai
                               FROM DangKyLichTap dk
                               JOIN LichTap lt ON dk.lich_tap_id = lt.lich_tap_id
                               WHERE dk.dang_ky_lich_id = :dk_id 
                               AND dk.khach_hang_id = :kh_id
                               AND dk.trang_thai = 'Đã đăng ký'");
        $stmt->execute([
            ':dk_id' => $dang_ky_lich_id,
            ':kh_id' => $khach_hang_id
        ]);
        $registration = $stmt->fetch();
    } else {
        // Hủy bằng lich_tap_id
        $stmt = $pdo->prepare("SELECT dk.dang_ky_lich_id, dk.lich_tap_id, lt.ten_lop, lt.ngay_tap, lt.trang_thai
                               FROM DangKyLichTap dk
                               JOIN LichTap lt ON dk.lich_tap_id = lt.lich_tap_id
                               WHERE dk.lich_tap_id = :lich_id 
                               AND dk.khach_hang_id = :kh_id
                               AND dk.trang_thai = 'Đã đăng ký'
                               LIMIT 1");
        $stmt->execute([
            ':lich_id' => $lich_tap_id,
            ':kh_id' => $khach_hang_id
        ]);
        $registration = $stmt->fetch();
    }
    
    if (!$registration) {
        back_to_schedule('Không tìm thấy đăng ký lớp này hoặc bạn không có quyền hủy.');
    }
    
    $dang_ky_lich_id = $registration['dang_ky_lich_id'];
    $lich_tap_id = $registration['lich_tap_id'];
    $ten_lop = htmlspecialchars($registration['ten_lop']);
    $ngay_tap = $registration['ngay_tap'];
    
    // Kiểm tra xem lớp đã diễn ra chưa (không cho hủy lớp đã qua)
    $today = date('Y-m-d');
    if ($ngay_tap < $today) {
        back_to_schedule('Không thể hủy lớp đã diễn ra.');
    }
    
    // 3. Cập nhật trạng thái đăng ký thành "Hủy"
    $stmt = $pdo->prepare("UPDATE DangKyLichTap 
                           SET trang_thai = 'Hủy' 
                           WHERE dang_ky_lich_id = :dk_id 
                           AND khach_hang_id = :kh_id");
    $stmt->execute([
        ':dk_id' => $dang_ky_lich_id,
        ':kh_id' => $khach_hang_id
    ]);
    
    // 4. Cập nhật trạng thái lớp nếu đã đầy -> chuyển về "Đang mở"
    $stmt = $pdo->prepare("SELECT 
                                  lt.lich_tap_id,
                                  lt.so_luong_toi_da,
                                  COUNT(CASE WHEN dk.trang_thai = 'Đã đăng ký' THEN 1 END) as so_luong_da_dang_ky,
                                  lt.trang_thai
                           FROM LichTap lt
                           LEFT JOIN DangKyLichTap dk ON lt.lich_tap_id = dk.lich_tap_id
                           WHERE lt.lich_tap_id = :lich_id
                           GROUP BY lt.lich_tap_id");
    $stmt->execute([':lich_id' => $lich_tap_id]);
    $lichTap = $stmt->fetch();
    
    if ($lichTap && $lichTap['trang_thai'] === 'Đã đầy') {
        // Kiểm tra lại số lượng sau khi hủy
        $so_luong_con_lai = $lichTap['so_luong_toi_da'] - $lichTap['so_luong_da_dang_ky'];
        if ($so_luong_con_lai > 0) {
            $stmt = $pdo->prepare("UPDATE LichTap SET trang_thai = 'Đang mở' WHERE lich_tap_id = :lich_id");
            $stmt->execute([':lich_id' => $lich_tap_id]);
        }
    }
    
    // Thành công
    header('Location: schedule.html?msg=' . urlencode("Đã hủy đăng ký lớp '{$ten_lop}' thành công!") . '&type=success');
    exit;
    
} catch (PDOException $e) {
    error_log("Cancel class error: " . $e->getMessage());
    back_to_schedule('Có lỗi xảy ra khi hủy lớp. Vui lòng thử lại.');
}

function back_to_schedule($message) {
    header('Location: schedule.html?msg=' . urlencode($message) . '&type=error');
    exit;
}

