<?php
// Đảm bảo không có output
if (ob_get_level()) {
    ob_clean();
}

// Load config trước
if (!defined('DB_HOST')) {
    $configPath = __DIR__ . '/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        error_log('Config file not found: ' . $configPath);
        if (defined('APP_ENV') && APP_ENV === 'local') {
            die('Config file not found');
        } else {
            die(); // Không output trên production
        }
    }
}

// Load database connection
if (!isset($pdo)) {
    $dbConnectPath = __DIR__ . '/db_connect.php';
    if (file_exists($dbConnectPath)) {
        require_once $dbConnectPath;
    } else {
        error_log('Database connection file not found: ' . $dbConnectPath);
        if (defined('APP_ENV') && APP_ENV === 'local') {
            die('Database connection file not found');
        } else {
            die(); // Không output trên production
        }
    }
}

// Đảm bảo $pdo đã được khởi tạo
if (!isset($pdo) || !$pdo) {
    error_log('Database connection failed: $pdo is not set after loading db_connect.php');
    if (defined('APP_ENV') && APP_ENV === 'local') {
        // Chỉ output trên local
        if (ob_get_level()) {
            ob_clean();
        }
        die('Database connection error: $pdo is not set');
    } else {
        // Trên production, không output để tránh 400 Bad Request
        die();
    }
}

