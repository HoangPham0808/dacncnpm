<?php
/**
 * Reset Password Direct
 * API để đổi mật khẩu sau khi đã xác thực thông tin
 */

// Bắt đầu output buffering để bắt mọi output không mong muốn
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);

session_start();

// Force local environment for CLI testing
if (php_sapi_name() === 'cli' && !isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

// Set headers NGAY LẬP TỨC
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../database/config.php';
    require_once '../../database/db_connect.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi kết nối database. Vui lòng thử lại sau.',
        'error' => defined('APP_ENV') && APP_ENV === 'local' ? $e->getMessage() : null
    ]);
    exit;
}

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ chấp nhận POST request'
    ]);
    exit;
}

try {
    // Clear output buffer trước khi xử lý
    ob_clean();
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!$input) {
        throw new Exception('Dữ liệu không hợp lệ');
    }
    
    $password = trim($input['password'] ?? '');
    $confirm_password = trim($input['confirm_password'] ?? '');
    
    // Kiểm tra các trường bắt buộc
    if (empty($password) || empty($confirm_password)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin');
    }
    
    // Validate mật khẩu
    if (strlen($password) < 6) {
        throw new Exception('Mật khẩu phải có ít nhất 6 ký tự');
    }
    
    if ($password !== $confirm_password) {
        throw new Exception('Mật khẩu xác nhận không khớp');
    }
    
    // Kiểm tra session xác thực
    if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
        throw new Exception('Phiên xác thực không hợp lệ. Vui lòng xác thực lại.');
    }
    
    if (!isset($_SESSION['reset_username'])) {
        throw new Exception('Không tìm thấy thông tin tài khoản. Vui lòng xác thực lại.');
    }
    
    // Kiểm tra thời gian xác thực (10 phút)
    if (!isset($_SESSION['reset_time']) || (time() - $_SESSION['reset_time']) > 600) {
        // Xóa session hết hạn
        unset($_SESSION['reset_verified']);
        unset($_SESSION['reset_username']);
        unset($_SESSION['reset_time']);
        throw new Exception('Phiên xác thực đã hết hạn. Vui lòng xác thực lại.');
    }
    
    $username = $_SESSION['reset_username'];
    
    // Kiểm tra tài khoản còn hoạt động không
    $checkStmt = $pdo->prepare("SELECT trang_thai FROM TaiKhoan WHERE ten_dang_nhap = :username LIMIT 1");
    $checkStmt->execute(['username' => $username]);
    $account = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        throw new Exception('Tài khoản không tồn tại.');
    }
    
    if ($account['trang_thai'] !== 'Hoạt động') {
        throw new Exception('Tài khoản không hoạt động. Vui lòng liên hệ admin.');
    }
    
    // Log thành công
    error_log("Valid session found. User: {$username}");
    
    // Hash mật khẩu mới
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // Cập nhật mật khẩu mới
        $updateStmt = $pdo->prepare("
            UPDATE TaiKhoan 
            SET mat_khau = :password
            WHERE ten_dang_nhap = :username
        ");
        
        $updateSuccess = $updateStmt->execute([
            'password' => $hashed_password,
            'username' => $username
        ]);
        
        if (!$updateSuccess) {
            throw new Exception('Không thể cập nhật mật khẩu');
        }
        
        // Log password change
        error_log("Password successfully changed for user: {$username}");
        
        // Commit transaction
        $pdo->commit();
        
        // Xóa session reset
        unset($_SESSION['reset_verified']);
        unset($_SESSION['reset_username']);
        unset($_SESSION['reset_time']);
        
        // Trả về kết quả thành công
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công! Vui lòng đăng nhập với mật khẩu mới.'
        ]);
        exit;
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $pdo->rollback();
        throw $e;
    }
    
} catch (PDOException $e) {
    ob_end_clean();
    error_log("Database error in reset_password_direct.php: " . $e->getMessage());
    error_log("SQL Error Code: " . $e->getCode());
    error_log("SQL Error Info: " . print_r($e->errorInfo ?? [], true));
    
    // Kiểm tra nếu cột reset_token chưa tồn tại
    if (strpos($e->getMessage(), 'Unknown column') !== false && 
        (strpos($e->getMessage(), 'reset_token') !== false || strpos($e->getMessage(), 'reset_token_expiry') !== false)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database chưa được cấu hình đúng. Vui lòng liên hệ admin.',
            'debug' => defined('APP_ENV') && APP_ENV === 'local' ? 'Missing database columns. Please run: ALTER TABLE TaiKhoan ADD COLUMN reset_token VARCHAR(255) NULL, ADD COLUMN reset_token_expiry DATETIME NULL;' : null
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.',
            'debug' => defined('APP_ENV') && APP_ENV === 'local' ? $e->getMessage() : null
        ]);
    }
    exit;
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    error_log("Fatal error in reset_password_direct.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.',
        'debug' => defined('APP_ENV') && APP_ENV === 'local' ? $e->getMessage() : null
    ]);
    exit;
}
?>
