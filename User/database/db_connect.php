<?php
// db_connect.php
require_once __DIR__ . '/config.php';

try {
  // Thử kết nối với localhost trước, nếu thất bại thì thử 127.0.0.1
  $hosts = [DB_HOST];
  if (DB_HOST === 'localhost' && defined('APP_ENV') && APP_ENV === 'local') {
    $hosts = ['127.0.0.1', 'localhost'];
  }
  
  $pdo = null;
  $lastError = null;
  $maxRetries = 3; // Số lần thử lại tối đa
  $retryDelay = 1; // Thời gian chờ giữa các lần thử (giây)
  
  foreach ($hosts as $host) {
    $retryCount = 0;
    $connected = false;
    
    // Retry logic cho mỗi host
    while ($retryCount < $maxRetries && !$connected) {
      try {
        $dsn = "mysql:host={$host};port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_PERSISTENT => false,
          PDO::ATTR_TIMEOUT => 15, // Tăng timeout lên 15 giây cho hosting
        ]);
        // Kiểm tra kết nối bằng cách test query với timeout
        $pdo->setAttribute(PDO::ATTR_TIMEOUT, 15);
        $pdo->query("SELECT 1");
        error_log("Database connected successfully - Host: {$host}, DB_NAME: " . DB_NAME . " (Attempt: " . ($retryCount + 1) . ")");
        $connected = true;
        break; // Kết nối thành công, thoát vòng lặp retry
      } catch (PDOException $e) {
        $lastError = $e;
        $retryCount++;
        
        if ($retryCount < $maxRetries) {
          error_log("Failed to connect to {$host} (Attempt {$retryCount}/{$maxRetries}): " . $e->getMessage() . " - Retrying in {$retryDelay}s...");
          sleep($retryDelay); // Chờ trước khi thử lại
        } else {
          error_log("Failed to connect to {$host} after {$maxRetries} attempts: " . $e->getMessage());
        }
      }
    }
    
    if ($connected) {
      break; // Kết nối thành công, thoát vòng lặp hosts
    }
  }
  
  if (!$pdo || !$connected) {
    throw $lastError ?: new PDOException("Failed to connect to database after all retries");
  }
  
} catch (PDOException $e) {
  // Log error trước
  error_log("DB ERROR: ".$e->getMessage());
  error_log("DB Connection details - Host: " . DB_HOST . ", Port: " . DB_PORT . ", DB: " . DB_NAME . ", User: " . DB_USER);
  
  // Trên production, không output để tránh 400 Bad Request
  // Chỉ output trên local để debug
  if (defined('APP_ENV') && APP_ENV === 'local') {
    // Xóa output buffer nếu có
    if (ob_get_level()) {
      ob_clean();
    }
    http_response_code(500);
    $errorMsg = "Database connection failed: " . htmlspecialchars($e->getMessage());
    $errorMsg .= "<br><br><strong>Hướng dẫn khắc phục:</strong>";
    $errorMsg .= "<br>1. Mở XAMPP Control Panel";
    $errorMsg .= "<br>2. Khởi động MySQL (nhấn nút 'Start' bên cạnh MySQL)";
    $errorMsg .= "<br>3. Đảm bảo MySQL đang chạy (nút chuyển sang màu xanh)";
    $errorMsg .= "<br>4. Kiểm tra database 'gym_management' đã được tạo chưa";
    $errorMsg .= "<br><br>Chi tiết kết nối: Host=" . DB_HOST . ", Port=" . DB_PORT . ", DB=" . DB_NAME . ", User=" . DB_USER;
    die($errorMsg);
  } else {
    // Trên production, chỉ log và die không output
    http_response_code(500);
    die(); // Không output để tránh 400 Bad Request
  }
}
// Không có whitespace hoặc output sau đây
