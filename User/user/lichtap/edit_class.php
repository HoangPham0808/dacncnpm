<?php
// edit_class.php - Xử lý sửa đổi đăng ký lớp tập (chuyển sang lớp khác)
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
$lich_tap_id_cu = $_POST['lich_tap_id_cu'] ?? null;
$lich_tap_id_moi = $_POST['lich_tap_id_moi'] ?? null;

if (!$dang_ky_lich_id || !$lich_tap_id_cu || !$lich_tap_id_moi) {
    back_to_schedule('Vui lòng chọn lớp mới để chuyển đổi.');
}

if ($lich_tap_id_cu == $lich_tap_id_moi) {
    back_to_schedule('Vui lòng chọn lớp khác với lớp hiện tại.');
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
    
    // 2. Kiểm tra đăng ký lớp cũ có tồn tại và thuộc về user này không
    $stmt = $pdo->prepare("SELECT dk.dang_ky_lich_id, dk.lich_tap_id, lt.ten_lop, lt.ngay_tap
                           FROM DangKyLichTap dk
                           JOIN LichTap lt ON dk.lich_tap_id = lt.lich_tap_id
                           WHERE dk.dang_ky_lich_id = :dk_id 
                           AND dk.khach_hang_id = :kh_id
                           AND dk.trang_thai = 'Đã đăng ký'");
    $stmt->execute([
        ':dk_id' => $dang_ky_lich_id,
        ':kh_id' => $khach_hang_id
    ]);
    $oldRegistration = $stmt->fetch();
    
    if (!$oldRegistration || $oldRegistration['lich_tap_id'] != $lich_tap_id_cu) {
        back_to_schedule('Không tìm thấy đăng ký lớp này hoặc bạn không có quyền sửa.');
    }
    
    // 3. Kiểm tra lớp mới có tồn tại, còn chỗ, đang mở và thuộc phòng tập của khách hàng
    $stmt = $pdo->prepare("SELECT lt.lich_tap_id, lt.ten_lop, lt.so_luong_toi_da, 
                                  COUNT(CASE WHEN dk.trang_thai = 'Đã đăng ký' THEN 1 END) as so_luong_da_dang_ky,
                                  lt.trang_thai, lt.ngay_tap, lt.phong_tap_id
                           FROM LichTap lt
                           LEFT JOIN DangKyLichTap dk ON lt.lich_tap_id = dk.lich_tap_id
                           WHERE lt.lich_tap_id = :lich_id
                           GROUP BY lt.lich_tap_id");
    $stmt->execute([':lich_id' => $lich_tap_id_moi]);
    $newClass = $stmt->fetch();
    
    if (!$newClass) {
        back_to_schedule('Không tìm thấy lớp tập mới.');
    }
    
    // Kiểm tra lớp mới có thuộc phòng tập của khách hàng không
    if ($newClass['phong_tap_id'] != $phong_tap_id) {
        back_to_schedule('Lớp tập mới không thuộc phòng tập của bạn. Bạn chỉ có thể chuyển sang lịch tập của phòng tập mà bạn đã đăng ký.');
    }
    
    if ($newClass['trang_thai'] !== 'Đang mở') {
        back_to_schedule('Lớp tập mới đã đóng hoặc bị hủy.');
    }
    
    if ($newClass['so_luong_da_dang_ky'] >= $newClass['so_luong_toi_da']) {
        back_to_schedule('Lớp tập mới đã đầy.');
    }
    
    // 4. Kiểm tra đã đăng ký lớp mới chưa
    $stmt = $pdo->prepare("SELECT dang_ky_lich_id FROM DangKyLichTap 
                           WHERE khach_hang_id = :kh_id 
                           AND lich_tap_id = :lich_id 
                           AND trang_thai = 'Đã đăng ký'
                           LIMIT 1");
    $stmt->execute([
        ':kh_id' => $khach_hang_id,
        ':lich_id' => $lich_tap_id_moi
    ]);
    
    if ($stmt->fetch()) {
        back_to_schedule('Bạn đã đăng ký lớp mới này rồi.');
    }
    
    // 5. Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // 5a. Hủy đăng ký lớp cũ
        $stmt = $pdo->prepare("UPDATE DangKyLichTap 
                               SET trang_thai = 'Hủy' 
                               WHERE dang_ky_lich_id = :dk_id");
        $stmt->execute([':dk_id' => $dang_ky_lich_id]);
        
        // 5b. Đăng ký lớp mới
        $stmt = $pdo->prepare("INSERT INTO DangKyLichTap (khach_hang_id, lich_tap_id, ngay_dang_ky, trang_thai)
                               VALUES (:kh_id, :lich_id, NOW(), 'Đã đăng ký')");
        $stmt->execute([
            ':kh_id' => $khach_hang_id,
            ':lich_id' => $lich_tap_id_moi
        ]);
        
        // 5c. Cập nhật trạng thái lớp cũ nếu cần
        $stmt = $pdo->prepare("SELECT 
                                      lt.lich_tap_id,
                                      lt.so_luong_toi_da,
                                      COUNT(CASE WHEN dk.trang_thai = 'Đã đăng ký' THEN 1 END) as so_luong_da_dang_ky,
                                      lt.trang_thai
                               FROM LichTap lt
                               LEFT JOIN DangKyLichTap dk ON lt.lich_tap_id = dk.lich_tap_id
                               WHERE lt.lich_tap_id = :lich_id
                               GROUP BY lt.lich_tap_id");
        $stmt->execute([':lich_id' => $lich_tap_id_cu]);
        $oldClass = $stmt->fetch();
        
        if ($oldClass && $oldClass['trang_thai'] === 'Đã đầy') {
            $so_luong_con_lai = $oldClass['so_luong_toi_da'] - $oldClass['so_luong_da_dang_ky'];
            if ($so_luong_con_lai > 0) {
                $stmt = $pdo->prepare("UPDATE LichTap SET trang_thai = 'Đang mở' WHERE lich_tap_id = :lich_id");
                $stmt->execute([':lich_id' => $lich_tap_id_cu]);
            }
        }
        
        // 5d. Cập nhật trạng thái lớp mới nếu đầy
        $so_luong_con_lai_moi = $newClass['so_luong_toi_da'] - $newClass['so_luong_da_dang_ky'] - 1;
        if ($so_luong_con_lai_moi <= 0) {
            $stmt = $pdo->prepare("UPDATE LichTap SET trang_thai = 'Đã đầy' WHERE lich_tap_id = :lich_id");
            $stmt->execute([':lich_id' => $lich_tap_id_moi]);
        }
        
        $pdo->commit();
        
        $ten_lop_moi = htmlspecialchars($newClass['ten_lop']);
        header('Location: schedule.html?msg=' . urlencode("Đã chuyển sang lớp '{$ten_lop_moi}' thành công!") . '&type=success');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Edit class error: " . $e->getMessage());
    back_to_schedule('Có lỗi xảy ra khi chuyển lớp. Vui lòng thử lại.');
}

function back_to_schedule($message) {
    header('Location: schedule.html?msg=' . urlencode($message) . '&type=error');
    exit;
}

