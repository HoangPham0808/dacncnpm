<?php
/**
 * Script kiểm tra và thêm cột reset_token vào bảng TaiKhoan
 * Chạy file này một lần để đảm bảo database đã có đủ cột cần thiết
 */

// Force local environment when running from command line
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

echo "=== Kiểm tra và thêm cột reset_token vào bảng TaiKhoan ===\n\n";

try {
    // Kiểm tra cột reset_token
    $checkResetToken = $pdo->query("SHOW COLUMNS FROM TaiKhoan LIKE 'reset_token'");
    $hasResetToken = $checkResetToken->rowCount() > 0;
    
    // Kiểm tra cột reset_token_expiry
    $checkResetTokenExpiry = $pdo->query("SHOW COLUMNS FROM TaiKhoan LIKE 'reset_token_expiry'");
    $hasResetTokenExpiry = $checkResetTokenExpiry->rowCount() > 0;
    
    echo "Trạng thái hiện tại:\n";
    echo "- Cột reset_token: " . ($hasResetToken ? "✓ Đã có" : "✗ Chưa có") . "\n";
    echo "- Cột reset_token_expiry: " . ($hasResetTokenExpiry ? "✓ Đã có" : "✗ Chưa có") . "\n\n";
    
    if (!$hasResetToken) {
        echo "Đang thêm cột reset_token...\n";
        try {
            $pdo->exec("ALTER TABLE TaiKhoan ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL");
            echo "✓ Đã thêm cột reset_token\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠ Cột reset_token đã tồn tại (bỏ qua)\n";
            } else {
                throw $e;
            }
        }
    } else {
        echo "✓ Cột reset_token đã tồn tại\n";
    }
    
    if (!$hasResetTokenExpiry) {
        echo "Đang thêm cột reset_token_expiry...\n";
        try {
            $pdo->exec("ALTER TABLE TaiKhoan ADD COLUMN reset_token_expiry DATETIME NULL DEFAULT NULL");
            echo "✓ Đã thêm cột reset_token_expiry\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠ Cột reset_token_expiry đã tồn tại (bỏ qua)\n";
            } else {
                throw $e;
            }
        }
    } else {
        echo "✓ Cột reset_token_expiry đã tồn tại\n";
    }
    
    // Kiểm tra và thêm index
    $checkIndex = $pdo->query("SHOW INDEX FROM TaiKhoan WHERE Key_name = 'idx_reset_token'");
    if ($checkIndex->rowCount() == 0) {
        echo "Đang thêm index idx_reset_token...\n";
        $pdo->exec("CREATE INDEX idx_reset_token ON TaiKhoan(reset_token)");
        echo "✓ Đã thêm index idx_reset_token\n";
    }
    
    $checkIndexExpiry = $pdo->query("SHOW INDEX FROM TaiKhoan WHERE Key_name = 'idx_reset_token_expiry'");
    if ($checkIndexExpiry->rowCount() == 0) {
        echo "Đang thêm index idx_reset_token_expiry...\n";
        $pdo->exec("CREATE INDEX idx_reset_token_expiry ON TaiKhoan(reset_token_expiry)");
        echo "✓ Đã thêm index idx_reset_token_expiry\n";
    }
    
    echo "\n=== Hoàn tất! ===\n";
    echo "Bảng TaiKhoan đã có đầy đủ cột và index cần thiết cho chức năng quên mật khẩu.\n";
    
} catch (PDOException $e) {
    echo "\n✗ Lỗi: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}

