<?php
// forgot_password.php — Xử lý yêu cầu quên mật khẩu
session_start();

// Nạp config + DB
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Fallback BASE_URL
if (!defined('BASE_URL')) {
    $base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    define('BASE_URL', ($base ? $base : '') . '/');
}

/**
 * Hàm gửi email đặt lại mật khẩu
 */
function sendPasswordResetEmail($to, $name, $reset_link) {
    $subject = 'Đặt lại mật khẩu - DFC Gym';
    
    // Email template HTML
    $message = '
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #22c55e; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .button:hover { background: #16a34a; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>DFC Gym</h1>
                <p>Đặt lại mật khẩu</p>
            </div>
            <div class="content">
                <p>Xin chào <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
                <p>Vui lòng click vào nút bên dưới để đặt lại mật khẩu:</p>
                <div style="text-align: center;">
                    <a href="' . htmlspecialchars($reset_link) . '" class="button">Đặt lại mật khẩu</a>
                </div>
                <p>Hoặc copy và dán link sau vào trình duyệt:</p>
                <p style="word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px;">' . htmlspecialchars($reset_link) . '</p>
                <div class="warning">
                    <strong>Lưu ý:</strong> Link này chỉ có hiệu lực trong 1 giờ. Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.
                </div>
                <p>Trân trọng,<br><strong>Đội ngũ DFC Gym</strong></p>
            </div>
            <div class="footer">
                <p>Email này được gửi tự động, vui lòng không trả lời email này.</p>
                <p>&copy; ' . date('Y') . ' DFC Gym. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Headers cho email HTML
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: DFC Gym <noreply@dfcgym.com>\r\n";
    $headers .= "Reply-To: DFC Gym <support@dfcgym.com>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Thử gửi email
    $result = @mail($to, $subject, $message, $headers);
    
    if (!$result) {
        error_log("Failed to send password reset email to: {$to}");
        // Trong môi trường development, vẫn coi như thành công để test
        if (defined('APP_ENV') && APP_ENV === 'local') {
            return true;
        }
        return false;
    }
    
    return true;
}

// Hàm chuyển hướng về index.php kèm thông báo
function back_to_page($page, $anchor, $msg, $type='error'){
    $baseUrl = rtrim(BASE_URL, '/');
    // Nếu BASE_URL có chứa đường dẫn file system, sửa lại
    if (strpos($baseUrl, 'C:/') !== false || strpos($baseUrl, 'C:\\') !== false) {
        $baseUrl = '/doanchuyennganh';
    }
    $u = $baseUrl.'/'.$page.'?msg='.urlencode($msg).'&type='.urlencode($type).'#'.$anchor;
    header("Location: $u");
    exit;
}

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back_to_page('index.html', 'quen-mat-khau', 'Yêu cầu không hợp lệ', 'error');
}

// Lấy email từ form
$email = trim($_POST['email'] ?? '');

// Kiểm tra email trống
if ($email === '') {
    back_to_page('index.html', 'quen-mat-khau', 'Vui lòng nhập email', 'error');
}

// Kiểm tra định dạng email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back_to_page('index.html', 'quen-mat-khau', 'Email không hợp lệ', 'error');
}

try {
    // Kiểm tra email có tồn tại trong database không - tìm trong bảng KhachHang
    $stmt = $pdo->prepare("SELECT ten_dang_nhap, email, ho_ten FROM KhachHang WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Không tìm thấy email - vẫn hiển thị thông báo thành công để tránh lộ thông tin
        back_to_page('index.html', 'quen-mat-khau', 
            'Nếu email này tồn tại, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu.', 'success');
    }

    // Kiểm tra xem bảng TaiKhoan đã có cột reset_token và reset_token_expiry chưa
    $checkResetToken = $pdo->query("SHOW COLUMNS FROM TaiKhoan LIKE 'reset_token'");
    $hasResetToken = $checkResetToken->rowCount() > 0;
    
    $checkResetTokenExpiry = $pdo->query("SHOW COLUMNS FROM TaiKhoan LIKE 'reset_token_expiry'");
    $hasResetTokenExpiry = $checkResetTokenExpiry->rowCount() > 0;
    
    // Thêm cột reset_token nếu chưa có
    if (!$hasResetToken) {
        try {
            $pdo->exec("ALTER TABLE TaiKhoan ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL");
            error_log("Added reset_token column to TaiKhoan table");
        } catch (PDOException $alterError) {
            // Nếu lỗi do cột đã tồn tại, bỏ qua
            if (strpos($alterError->getMessage(), 'Duplicate column') === false) {
                error_log("Failed to add reset_token column: " . $alterError->getMessage());
                if (defined('APP_ENV') && APP_ENV === 'local') {
                    back_to_page('index.html', 'quen-mat-khau', 
                        'Lỗi: Không thể thêm cột reset_token. ' . $alterError->getMessage(), 'error');
                } else {
                    back_to_page('index.html', 'quen-mat-khau', 
                        'Hệ thống đang bảo trì. Vui lòng thử lại sau.', 'error');
                }
            }
        }
    }
    
    // Thêm cột reset_token_expiry nếu chưa có
    if (!$hasResetTokenExpiry) {
        try {
            $pdo->exec("ALTER TABLE TaiKhoan ADD COLUMN reset_token_expiry DATETIME NULL DEFAULT NULL");
            error_log("Added reset_token_expiry column to TaiKhoan table");
        } catch (PDOException $alterError) {
            // Nếu lỗi do cột đã tồn tại, bỏ qua
            if (strpos($alterError->getMessage(), 'Duplicate column') === false) {
                error_log("Failed to add reset_token_expiry column: " . $alterError->getMessage());
                if (defined('APP_ENV') && APP_ENV === 'local') {
                    back_to_page('index.html', 'quen-mat-khau', 
                        'Lỗi: Không thể thêm cột reset_token_expiry. ' . $alterError->getMessage(), 'error');
                } else {
                    back_to_page('index.html', 'quen-mat-khau', 
                        'Hệ thống đang bảo trì. Vui lòng thử lại sau.', 'error');
                }
            }
        }
    }

    // Tạo token reset password (random 64 ký tự)
    $token = bin2hex(random_bytes(32));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token hết hạn sau 1 giờ

    // Cập nhật token vào database
    $stmt = $pdo->prepare("UPDATE TaiKhoan SET reset_token = :token, reset_token_expiry = :expiry WHERE ten_dang_nhap = :username");
    $stmt->execute([
        'token' => $token,
        'expiry' => $token_expiry,
        'username' => $user['ten_dang_nhap']
    ]);

    // Tạo link reset password
    $baseUrl = rtrim(BASE_URL, '/');
    // Nếu BASE_URL có chứa đường dẫn file system, sửa lại
    if (strpos($baseUrl, 'C:/') !== false || strpos($baseUrl, 'C:\\') !== false) {
        $baseUrl = '/doanchuyennganh';
    }
    $reset_link = $baseUrl.'/user/getset/reset_password.php?token='.$token;

    // Gửi email reset password
    $email_sent = sendPasswordResetEmail($email, $user['ho_ten'], $reset_link);
    
    // Trong môi trường development, lưu link vào session để test
    if (defined('APP_ENV') && APP_ENV === 'local') {
        $_SESSION['reset_link'] = $reset_link;
        $_SESSION['reset_email'] = $email;
        error_log("Password Reset Link for {$email}: {$reset_link}");
    }

    // Chuyển về trang thông báo thành công
    back_to_page('index.html', 'quen-mat-khau', 
        'Link đặt lại mật khẩu đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.', 'success');

} catch (PDOException $e) {
    error_log("Forgot Password Error: " . $e->getMessage());
    error_log("SQL Error Code: " . $e->getCode());
    error_log("SQL Error Info: " . print_r($e->errorInfo, true));
    
    // Hiển thị lỗi chi tiết hơn trong development mode
    $errorMessage = 'Đã xảy ra lỗi. Vui lòng thử lại sau.';
    if (defined('APP_ENV') && APP_ENV === 'local') {
        // Trong development, hiển thị lỗi chi tiết
        $errorMessage = 'Lỗi: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')';
        // Kiểm tra nếu lỗi do thiếu cột
        if (strpos($e->getMessage(), 'reset_token') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
            $errorMessage = 'Lỗi: Bảng TaiKhoan chưa có cột reset_token. Vui lòng chạy file migration: database/migration_add_reset_token.sql';
        }
    }
    
    back_to_page('index.html', 'quen-mat-khau', $errorMessage, 'error');
}

