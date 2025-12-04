<?php
// update_password.php — đổi mật khẩu cho người dùng đã đăng nhập
session_start();

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

function redirect_with_msg($msg, $type = 'error') {
    $url = rtrim(BASE_URL, '/') . '/index.html?msg=' . urlencode($msg) . '&type=' . urlencode($type) . '&notify=' . ($type === 'success' ? 'password_changed' : '') . '#change-password';
    header('Location: ' . $url);
    exit;
}

// Bắt buộc đăng nhập
if (!isset($_SESSION['user']['username'])) {
    redirect_with_msg('Bạn cần đăng nhập để đổi mật khẩu', 'error');
}

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_msg('Yêu cầu không hợp lệ', 'error');
}

$username         = $_SESSION['user']['username'];
$oldPassword      = $_POST['old_password'] ?? '';
$newPassword      = $_POST['new_password'] ?? '';
$confirmPassword  = $_POST['confirm_password'] ?? '';

// Validate cơ bản
if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
    redirect_with_msg('Vui lòng nhập đầy đủ thông tin', 'error');
}
if (strlen($newPassword) < 6) {
    redirect_with_msg('Mật khẩu mới phải có ít nhất 6 ký tự', 'error');
}
if ($newPassword !== $confirmPassword) {
    redirect_with_msg('Mật khẩu xác nhận không khớp', 'error');
}
if ($newPassword === $oldPassword) {
    redirect_with_msg('Mật khẩu mới phải khác mật khẩu cũ', 'error');
}

try {
    // Lấy hash cũ
    $stmt = $pdo->prepare('SELECT mat_khau FROM TaiKhoan WHERE ten_dang_nhap = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();
    if (!$row) {
        redirect_with_msg('Tài khoản không tồn tại', 'error');
    }

    if (!password_verify($oldPassword, $row['mat_khau'])) {
        redirect_with_msg('Mật khẩu cũ không đúng', 'error');
    }

    // Cập nhật mật khẩu mới
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE TaiKhoan SET mat_khau = :p, ngay_cap_nhat = NOW() WHERE ten_dang_nhap = :u');
    $upd->execute([':p' => $newHash, ':u' => $username]);
    
    // Verify lại mật khẩu mới đã được cập nhật thành công
    $verify_stmt = $pdo->prepare('SELECT mat_khau FROM TaiKhoan WHERE ten_dang_nhap = :u LIMIT 1');
    $verify_stmt->execute([':u' => $username]);
    $verify_row = $verify_stmt->fetch();
    if (!$verify_row || !password_verify($newPassword, $verify_row['mat_khau'])) {
        error_log('Password update verification failed for user: ' . $username);
        redirect_with_msg('Không thể cập nhật mật khẩu. Vui lòng thử lại.', 'error');
    }

    // Đảm bảo session được giữ nguyên sau khi đổi mật khẩu
    // KHÔNG regenerate session ID vì có thể gây mất session
    // Chỉ cập nhật lại thông tin user trong session nếu cần
    if (!isset($_SESSION['user']) || $_SESSION['user']['username'] !== $username) {
        // Nếu session user bị mất hoặc không khớp, lấy lại từ database
        $stmt_user = $pdo->prepare('SELECT ten_dang_nhap, email, ho_ten, vai_tro, loai_tai_khoan FROM TaiKhoan WHERE ten_dang_nhap = :u LIMIT 1');
        $stmt_user->execute([':u' => $username]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            $_SESSION['user'] = [
                'username' => $user_data['ten_dang_nhap'],
                'email' => $user_data['email'],
                'full_name' => $user_data['ho_ten'],
                'role' => $user_data['vai_tro'],
                'account_type' => $user_data['loai_tai_khoan'] ?? 'member'
            ];
        }
    }
    
    // Đảm bảo session được write (PHP tự động write khi script kết thúc, nhưng đảm bảo chắc chắn)
    // Không cần close và start lại vì có thể gây mất session

    redirect_with_msg('Đổi mật khẩu thành công', 'success');
} catch (Throwable $e) {
    error_log('Change password error: ' . $e->getMessage());
    redirect_with_msg('Có lỗi hệ thống, vui lòng thử lại', 'error');
}


