<?php
/**
 * login.php - Xử lý đăng nhập
 * Hỗ trợ đăng nhập bằng email hoặc username
 */

// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nạp config và database
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Hàm chuyển hướng với thông báo
function redirect_with_message($anchor, $message, $type = 'error') {
    // Xác định BASE_URL an toàn - đơn giản hóa để tránh crash
    if (!defined('BASE_URL')) {
        require_once __DIR__ . '/../../database/config.php';
    }
    
    // Sử dụng BASE_URL từ config (tự động detect cho cả local và hosting)
    $baseUrl = rtrim(BASE_URL, '/');
    // Nếu BASE_URL có chứa đường dẫn file system, sửa lại
    if (strpos($baseUrl, 'C:/') !== false || strpos($baseUrl, 'C:\\') !== false) {
        $baseUrl = '/doanchuyennganh';
    }
    $url = $baseUrl . '/index.html?msg=' . urlencode($message) . '&type=' . urlencode($type) . '#' . $anchor;
    
    // Đảm bảo header được gửi đúng cách
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        // Nếu headers đã gửi, dùng JavaScript redirect
        echo '<script>window.location.href = "' . htmlspecialchars($url, ENT_QUOTES) . '";</script>';
        exit;
    }
}

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('dang-nhap', 'Yêu cầu không hợp lệ', 'error');
}

// Lấy dữ liệu từ form
$identifier = trim($_POST['email'] ?? $_POST['username'] ?? $_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

// Kiểm tra dữ liệu đầu vào
if (empty($identifier) || empty($password)) {
    redirect_with_message('dang-nhap', 'Vui lòng nhập đầy đủ thông tin', 'error');
}

try {
    // Tìm tài khoản - hỗ trợ đăng nhập bằng email hoặc username
    $taiKhoan = null;
    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
    
    if ($isEmail) {
        // Đăng nhập bằng email - tìm trong KhachHang trước
        $stmt = $pdo->prepare("SELECT k.ten_dang_nhap, t.mat_khau, t.loai_tai_khoan, t.trang_thai
                               FROM KhachHang k
                               INNER JOIN TaiKhoan t ON k.ten_dang_nhap = t.ten_dang_nhap
                               WHERE k.email = :email
                               LIMIT 1");
        $stmt->execute([':email' => $identifier]);
        $taiKhoan = $stmt->fetch();
        
        // Nếu không tìm thấy trong KhachHang, tìm trong NhanVien
        if (!$taiKhoan) {
            $stmt = $pdo->prepare("SELECT n.ten_dang_nhap, t.mat_khau, t.loai_tai_khoan, t.trang_thai
                                   FROM NhanVien n
                                   INNER JOIN TaiKhoan t ON n.ten_dang_nhap = t.ten_dang_nhap
                                   WHERE n.email = :email
                                   LIMIT 1");
            $stmt->execute([':email' => $identifier]);
            $taiKhoan = $stmt->fetch();
        }
    } else {
        // Đăng nhập bằng username
        $stmt = $pdo->prepare("SELECT ten_dang_nhap, mat_khau, loai_tai_khoan, trang_thai
                               FROM TaiKhoan
                               WHERE ten_dang_nhap = :username
                               LIMIT 1");
        $stmt->execute([':username' => $identifier]);
        $taiKhoan = $stmt->fetch();
    }
    
    // Kiểm tra tài khoản tồn tại
    if (!$taiKhoan) {
        redirect_with_message('dang-nhap', 'Tài khoản và mật khẩu không chính xác', 'error');
    }
    
    // Kiểm tra trạng thái tài khoản
    if ($taiKhoan['trang_thai'] !== 'Hoạt động') {
        redirect_with_message('dang-nhap', 'Tài khoản đã bị khóa hoặc tạm ngưng', 'error');
    }
    
    // Kiểm tra mật khẩu (so sánh trực tiếp, không mã hóa)
    if ($taiKhoan['mat_khau'] !== $password) {
        redirect_with_message('dang-nhap', 'Tài khoản và mật khẩu không chính xác', 'error');
    }
    
    // Lấy thông tin chi tiết người dùng
    $hoTen = '';
    $email = '';
    $userRole = 'user';
    $khach_hang_id = null;
    $nhan_vien_id = null;
    
    // PHÂN QUYỀN: CHỈ CHO PHÉP TÀI KHOẢN KHÁCH HÀNG ĐĂNG NHẬP VÀO TRANG USER
    if ($taiKhoan['loai_tai_khoan'] === 'Admin' || $taiKhoan['loai_tai_khoan'] === 'Nhân viên') {
        // Tài khoản Admin và Nhân viên KHÔNG thể đăng nhập vào trang user
        redirect_with_message('dang-nhap', 'Tài khoản quản trị không thể đăng nhập tại đây. Vui lòng đăng nhập tại trang quản trị!', 'error');
    }
    
    // Chỉ cho phép Khách hàng đăng nhập vào trang user
    if ($taiKhoan['loai_tai_khoan'] === 'Khách hàng') {
        $stmt = $pdo->prepare("SELECT khach_hang_id, ho_ten, email 
                               FROM KhachHang 
                               WHERE ten_dang_nhap = :username 
                               LIMIT 1");
        $stmt->execute([':username' => $taiKhoan['ten_dang_nhap']]);
        $khachHang = $stmt->fetch();
        if ($khachHang) {
            $hoTen = $khachHang['ho_ten'];
            $email = $khachHang['email'];
            $khach_hang_id = $khachHang['khach_hang_id'];
        } else {
            // Nếu không tìm thấy trong bảng KhachHang → không cho đăng nhập
            redirect_with_message('dang-nhap', 'Tài khoản không hợp lệ. Vui lòng liên hệ quản trị viên!', 'error');
        }
    } else {
        // Nếu loại tài khoản khác → không cho đăng nhập
        redirect_with_message('dang-nhap', 'Tài khoản không có quyền truy cập. Chỉ tài khoản khách hàng mới có thể đăng nhập tại đây!', 'error');
    }
    
    // Cập nhật lần đăng nhập cuối
    try {
        $stmt = $pdo->prepare("UPDATE TaiKhoan 
                              SET lan_dang_nhap_cuoi = NOW() 
                              WHERE ten_dang_nhap = :username");
        $stmt->execute([':username' => $taiKhoan['ten_dang_nhap']]);
    } catch (Exception $e) {
        error_log("Failed to update last login time: " . $e->getMessage());
    }
    
    // Ghi log vào bảng LichSuRaVao khi đăng nhập
    try {
        $stmt = $pdo->prepare("INSERT INTO LichSuRaVao (ten_dang_nhap, loai_tai_khoan, thoi_gian_vao) 
                              VALUES (:username, :loai_tai_khoan, NOW())");
        $stmt->execute([
            ':username' => $taiKhoan['ten_dang_nhap'],
            ':loai_tai_khoan' => $taiKhoan['loai_tai_khoan']
        ]);
    } catch (Exception $e) {
        error_log("Failed to log login to LichSuRaVao: " . $e->getMessage());
        // Không chặn đăng nhập nếu ghi log thất bại
    }
    
    // Xóa session cũ để tránh session fixation
    session_regenerate_id(true);
    
    // Xóa tất cả dữ liệu session cũ
    $_SESSION = [];
    
    // Lưu thông tin vào session
    $_SESSION['user'] = [
        'username' => $taiKhoan['ten_dang_nhap'],
        'full_name' => $hoTen,
        'email' => $email,
        'role' => $userRole,
        'account_type' => $taiKhoan['loai_tai_khoan'],
        'khach_hang_id' => $khach_hang_id,
        'nhan_vien_id' => $nhan_vien_id,
        'login_time' => time()
    ];
    
    // Chuyển hướng về trang chủ với thông báo thành công
    // Sử dụng BASE_URL từ config (tự động detect cho cả local và hosting)
    $baseUrl = rtrim(BASE_URL, '/');
    // Nếu BASE_URL có chứa đường dẫn file system, sửa lại
    if (strpos($baseUrl, 'C:/') !== false || strpos($baseUrl, 'C:\\') !== false) {
        $baseUrl = '/doanchuyennganh';
    }
    $redirectUrl = $baseUrl . '/index.html?msg=' . urlencode('Đăng nhập thành công!') . '&type=success';
    
    // Đảm bảo header được gửi đúng cách
    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // Nếu headers đã gửi, dùng JavaScript redirect
        echo '<script>window.location.href = "' . htmlspecialchars($redirectUrl, ENT_QUOTES) . '";</script>';
        exit;
    }
    
} catch (PDOException $e) {
    error_log('Login database error: ' . $e->getMessage());
    redirect_with_message('dang-nhap', 'Có lỗi kết nối database, vui lòng thử lại sau', 'error');
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    redirect_with_message('dang-nhap', 'Có lỗi hệ thống, vui lòng thử lại sau', 'error');
}
