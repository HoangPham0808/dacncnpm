<?php
/**
 * Verify Identity for Password Reset
 * Xác thực thông tin người dùng trước khi cho phép đổi mật khẩu
 */

// Bắt đầu output buffering
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

try {
    // Clear output buffer
    ob_clean();
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!$input) {
        throw new Exception('Dữ liệu không hợp lệ');
    }
    
    $sdt = trim($input['sdt'] ?? '');
    $ho_ten = trim($input['ho_ten'] ?? '');
    $ngay_sinh = trim($input['ngay_sinh'] ?? '');
    $cccd = trim($input['cccd'] ?? '');
    
    // Kiểm tra các trường bắt buộc
    if (empty($sdt) || empty($ho_ten) || empty($ngay_sinh) || empty($cccd)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin');
    }
    
    // Validate số điện thoại
    if (!preg_match('/^[0-9]{10}$/', $sdt)) {
        throw new Exception('Số điện thoại không hợp lệ');
    }
    
    // Validate CCCD
    if (!preg_match('/^[0-9]{9,12}$/', $cccd)) {
        throw new Exception('Căn cước công dân không hợp lệ');
    }
    
    // Validate ngày sinh
    $date = DateTime::createFromFormat('Y-m-d', $ngay_sinh);
    if (!$date || $date->format('Y-m-d') !== $ngay_sinh) {
        throw new Exception('Ngày sinh không hợp lệ');
    }
    
    // Kiểm tra trong database
    // Join bảng KhachHang và TaiKhoan để lấy đầy đủ thông tin
    // So sánh không phân biệt hoa thường và loại bỏ khoảng trắng thừa
    $stmt = $pdo->prepare("
        SELECT 
            kh.khach_hang_id,
            kh.ten_dang_nhap,
            kh.ho_ten,
            kh.sdt,
            kh.ngay_sinh,
            kh.cccd,
            tk.email
        FROM KhachHang kh
        INNER JOIN TaiKhoan tk ON kh.ten_dang_nhap = tk.ten_dang_nhap
        WHERE kh.sdt = :sdt 
            AND LOWER(TRIM(kh.ho_ten)) = LOWER(TRIM(:ho_ten))
            AND kh.ngay_sinh = :ngay_sinh
            AND kh.cccd = :cccd
            AND tk.trang_thai = 'Hoạt động'
        LIMIT 1
    ");
    
    $stmt->execute([
        'sdt' => $sdt,
        'ho_ten' => $ho_ten,
        'ngay_sinh' => $ngay_sinh,
        'cccd' => $cccd
    ]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Log failed attempt
        error_log("Password reset verification failed for: SDT=$sdt, HoTen=$ho_ten, NgaySinh=$ngay_sinh, CCCD=$cccd");
        throw new Exception('Thông tin không chính xác. Vui lòng kiểm tra lại.');
    }
    
    // Lưu thông tin vào session để bước tiếp theo sử dụng
    $_SESSION['reset_verified'] = true;
    $_SESSION['reset_username'] = $user['ten_dang_nhap'];
    $_SESSION['reset_time'] = time();
    
    // Log successful verification
    error_log("Password reset verification successful for user: {$user['ten_dang_nhap']}");
    
    // Trả về kết quả thành công
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Xác thực thành công',
        'username' => $user['ten_dang_nhap']
    ]);
    exit;
    
} catch (PDOException $e) {
    ob_end_clean();
    error_log("Database error in verify_identity.php: " . $e->getMessage());
    error_log("SQL Error Code: " . $e->getCode());
    error_log("SQL Error Info: " . print_r($e->errorInfo ?? [], true));
    
    // Kiểm tra nếu cột reset_token chưa tồn tại
    if (strpos($e->getMessage(), 'Unknown column') !== false && 
        (strpos($e->getMessage(), 'reset_token') !== false || strpos($e->getMessage(), 'reset_token_expiry') !== false)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database chưa được cấu hình đúng. Vui lòng liên hệ admin.',
            'debug' => defined('APP_ENV') && APP_ENV === 'local' ? 'Missing database columns. Please run: database/add_reset_token_columns.sql' : null
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
    error_log("Fatal error in verify_identity.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.',
        'debug' => defined('APP_ENV') && APP_ENV === 'local' ? $e->getMessage() : null
    ]);
    exit;
}
?>
