<?php
// db_connect.php
require_once __DIR__ . '/config.php';

try {
  $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT => false, // Không dùng persistent connection
  ]);
  // Kiểm tra kết nối bằng cách test query
  $pdo->query("SELECT 1");
  error_log("Database connected successfully - DB_NAME: " . DB_NAME);
} catch (PDOException $e) {
  http_response_code(500);
  if (defined('APP_ENV') && APP_ENV === 'local') {
    echo "Database connection failed: " . htmlspecialchars($e->getMessage());
  } else {
    echo "Database connection failed.";
  }
  error_log("DB ERROR: ".$e->getMessage());
  error_log("DB Connection details - Host: " . DB_HOST . ", DB: " . DB_NAME . ", User: " . DB_USER);
  exit;
}