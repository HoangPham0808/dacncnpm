<?php
// db_mysqli.php
require_once __DIR__ . '/config.php';

$conn = null;
$maxRetries = 3; // Số lần thử lại tối đa
$retryDelay = 1; // Thời gian chờ giữa các lần thử (giây)
$lastError = null;
$connected = false;

// Thử kết nối với retry logic
for ($retryCount = 0; $retryCount < $maxRetries && !$connected; $retryCount++) {
    try {
        // Tắt error reporting tạm thời để xử lý lỗi thủ công
        mysqli_report(MYSQLI_REPORT_OFF);
        
        // Tạo kết nối mysqli
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        // Kiểm tra kết nối
        if ($conn && !$conn->connect_error) {
            // Thiết lập timeout cho các operations (sau khi kết nối)
            // Lưu ý: MYSQLI_OPT_CONNECT_TIMEOUT chỉ có thể set trước khi connect
            // Nhưng chúng ta có thể set read/write timeout sau
            ini_set('default_socket_timeout', 15);
            
            // Kiểm tra kết nối bằng cách test query
            if ($conn->ping()) {
                // Thiết lập charset
                $conn->set_charset("utf8mb4");
                
                error_log("Database connected successfully (mysqli) - Host: " . DB_HOST . ", DB_NAME: " . DB_NAME . " (Attempt: " . ($retryCount + 1) . ")");
                $connected = true;
                break;
            } else {
                throw new Exception("Connection ping failed");
            }
        } else {
            $errorMsg = $conn ? $conn->connect_error : "Failed to create mysqli connection";
            throw new Exception($errorMsg);
        }
    } catch (Exception $e) {
        $lastError = $e;
        
        if ($conn) {
            $conn->close();
            $conn = null;
        }
        
        if ($retryCount < $maxRetries - 1) {
            error_log("Failed to connect (mysqli) (Attempt " . ($retryCount + 1) . "/{$maxRetries}): " . $e->getMessage() . " - Retrying in {$retryDelay}s...");
            sleep($retryDelay); // Chờ trước khi thử lại
        } else {
            error_log("Failed to connect (mysqli) after {$maxRetries} attempts: " . $e->getMessage());
        }
    }
}

// Kiểm tra kết nối cuối cùng
if (!$conn || !$connected || $conn->connect_error) {
    http_response_code(500);
    $errorMsg = $lastError ? $lastError->getMessage() : ($conn ? $conn->connect_error : "Failed to connect to database");
    
    if (defined('APP_ENV') && APP_ENV === 'local') {
        die("Kết nối thất bại: " . htmlspecialchars($errorMsg) . "<br><br>Chi tiết: Host=" . DB_HOST . ", Port=" . DB_PORT . ", DB=" . DB_NAME . ", User=" . DB_USER);
    } else {
        die("Database connection failed.");
    }
    error_log("DB ERROR (mysqli): " . $errorMsg);
    error_log("DB Connection details - Host: " . DB_HOST . ", Port: " . DB_PORT . ", DB: " . DB_NAME . ", User: " . DB_USER);
    exit;
}

