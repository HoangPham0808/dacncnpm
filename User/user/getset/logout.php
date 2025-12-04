<?php
/**
 * logout.php - Xử lý đăng xuất
 * Xóa toàn bộ session và chuyển hướng về trang chủ
 */

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nạp config và database để ghi log
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Lưu thông tin đăng xuất trước khi xóa session
$logoutMessage = 'Đã đăng xuất thành công';
$username = null;
$accountType = null;

if (isset($_SESSION['user'])) {
    $username = $_SESSION['user']['username'] ?? null;
    $accountType = $_SESSION['user']['account_type'] ?? null;
    
    // Ghi log vào bảng LichSuRaVao khi đăng xuất
    if ($username && $accountType) {
        try {
            $stmt = $pdo->prepare("INSERT INTO LichSuRaVao (ten_dang_nhap, loai_tai_khoan, thoi_gian_vao) 
                                  VALUES (:username, :loai_tai_khoan, NOW())");
            $stmt->execute([
                ':username' => $username,
                ':loai_tai_khoan' => $accountType
            ]);
        } catch (Exception $e) {
            error_log("Failed to log logout to LichSuRaVao: " . $e->getMessage());
            // Không chặn đăng xuất nếu ghi log thất bại
        }
    }
}

// Xóa tất cả biến session
$_SESSION = [];

// Xóa cookie session nếu có
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hủy session
session_destroy();

// Tạo session mới để tránh lỗi
session_start();
session_regenerate_id(true);

// Chuyển hướng về trang chủ với thông báo
$redirectUrl = rtrim(BASE_URL, '/') . '/index.html?msg=' . urlencode($logoutMessage) . '&type=success';
header("Location: " . $redirectUrl);
exit;
