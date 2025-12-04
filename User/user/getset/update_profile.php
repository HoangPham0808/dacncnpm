<?php
// update_profile.php - Cập nhật thông tin khách hàng
session_start();

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';
require_once __DIR__ . '/check_session.php';

// Kiểm tra đăng nhập
require_login();

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: goitap/packages.html');
    exit;
}

// Lấy dữ liệu từ form
$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$sdt = trim($_POST['sdt'] ?? '');
$cccd = trim($_POST['cccd'] ?? '');
$dia_chi = trim($_POST['dia_chi'] ?? '');
$ngay_sinh = trim($_POST['ngay_sinh'] ?? '');
$gioi_tinh = trim($_POST['gioi_tinh'] ?? '');
$nguon_gioi_thieu = trim($_POST['nguon_gioi_thieu'] ?? '');
$ghi_chu = trim($_POST['ghi_chu'] ?? '');

// Lấy username hiện tại từ session
$current_username = $_SESSION['user']['username'];

try {
    // Lấy khach_hang_id từ username
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $current_username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        header('Location: goitap/packages.html?msg=' . urlencode('Không tìm thấy thông tin khách hàng') . '&type=error');
        exit;
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // Kiểm tra username mới có trùng với user khác không (nếu có thay đổi)
    if ($username && $username !== $current_username) {
        $stmt_check = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username AND khach_hang_id != :kh_id LIMIT 1");
        $stmt_check->execute([
            ':username' => $username,
            ':kh_id' => $khach_hang_id
        ]);
        if ($stmt_check->fetch()) {
            header('Location: goitap/packages.html?msg=' . urlencode('Tên đăng nhập đã được sử dụng') . '&type=error');
            exit;
        }
    }
    
    // Lấy trang_thai hiện tại để giữ nguyên (trừ khi admin thay đổi)
    $stmt_get_status = $pdo->prepare("SELECT trang_thai FROM KhachHang WHERE khach_hang_id = :kh_id LIMIT 1");
    $stmt_get_status->execute([':kh_id' => $khach_hang_id]);
    $current_status = $stmt_get_status->fetchColumn();
    $trang_thai = $current_status ?: 'Hoạt động';
    
    // Cập nhật thông tin khách hàng
    $stmt_update = $pdo->prepare("UPDATE KhachHang 
                                  SET ho_ten = :ho_ten,
                                      ten_dang_nhap = :ten_dang_nhap,
                                      sdt = :sdt,
                                      cccd = :cccd,
                                      dia_chi = :dia_chi,
                                      ngay_sinh = :ngay_sinh,
                                      gioi_tinh = :gioi_tinh,
                                      nguon_gioi_thieu = :nguon_gioi_thieu,
                                      ghi_chu = :ghi_chu,
                                      trang_thai = :trang_thai,
                                      ngay_cap_nhat = NOW()
                                  WHERE khach_hang_id = :kh_id");
    
    $stmt_update->execute([
        ':ho_ten' => $full_name,
        ':ten_dang_nhap' => $username ?: $current_username,
        ':sdt' => $sdt ?: null,
        ':cccd' => $cccd ?: null,
        ':dia_chi' => $dia_chi ?: null,
        ':ngay_sinh' => $ngay_sinh ?: null,
        ':gioi_tinh' => $gioi_tinh ?: null,
        ':nguon_gioi_thieu' => $nguon_gioi_thieu ?: null,
        ':ghi_chu' => $ghi_chu ?: null,
        ':trang_thai' => $trang_thai,
        ':kh_id' => $khach_hang_id
    ]);
    
    // Cập nhật username trong bảng TaiKhoan nếu có thay đổi
    if ($username && $username !== $current_username) {
        $stmt_update_tk = $pdo->prepare("UPDATE TaiKhoan 
                                         SET ten_dang_nhap = :ten_moi,
                                             ngay_cap_nhat = NOW()
                                         WHERE ten_dang_nhap = :ten_cu");
        $stmt_update_tk->execute([
            ':ten_moi' => $username,
            ':ten_cu' => $current_username
        ]);
        
        // Cập nhật session
        $_SESSION['user']['username'] = $username;
    }
    
    // Cập nhật full_name trong session
    $_SESSION['user']['full_name'] = $full_name;
    
    error_log("Profile updated - khach_hang_id: {$khach_hang_id}, ho_ten: {$full_name}");
    
    // Kiểm tra xem có phải AJAX request không
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $isAjax = $isAjax || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    
    // Nếu là AJAX request, trả về JSON
    if ($isAjax || (isset($_POST['ajax']) && $_POST['ajax'] == '1')) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công!',
            'profile' => [
                'ho_ten' => $full_name,
                'email' => $_SESSION['user']['email'] ?? '',
                'sdt' => $sdt,
                'dia_chi' => $dia_chi,
                'ngay_sinh' => $ngay_sinh,
                'gioi_tinh' => $gioi_tinh
            ]
        ]);
        exit;
    }
    
    // Nếu không phải AJAX, redirect như bình thường
    // Lấy URL referer để redirect về trang trước đó
    $referer = $_SERVER['HTTP_REFERER'] ?? 'goitap/packages.html';
    // Loại bỏ query string cũ nếu có
    $referer = preg_replace('/[?&]msg=.*$/', '', $referer);
    $referer = preg_replace('/[?&]type=.*$/', '', $referer);
    
    // Redirect với thông báo thành công
    $separator = strpos($referer, '?') !== false ? '&' : '?';
    header('Location: ' . $referer . $separator . 'msg=' . urlencode('Cập nhật thông tin thành công!') . '&type=success');
    exit;
    
} catch (PDOException $e) {
    error_log("Error updating profile: " . $e->getMessage());
    
    // Kiểm tra xem có phải AJAX request không
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $isAjax = $isAjax || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    
    // Nếu là AJAX request, trả về JSON
    if ($isAjax || (isset($_POST['ajax']) && $_POST['ajax'] == '1')) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi cập nhật thông tin. Vui lòng thử lại.'
        ]);
        exit;
    }
    
    // Nếu không phải AJAX, redirect như bình thường
    // Lấy URL referer để redirect về trang trước đó
    $referer = $_SERVER['HTTP_REFERER'] ?? 'goitap/packages.html';
    $referer = preg_replace('/[?&]msg=.*$/', '', $referer);
    $referer = preg_replace('/[?&]type=.*$/', '', $referer);
    
    $separator = strpos($referer, '?') !== false ? '&' : '?';
    header('Location: ' . $referer . $separator . 'msg=' . urlencode('Có lỗi xảy ra khi cập nhật thông tin. Vui lòng thử lại.') . '&type=error');
    exit;
}

