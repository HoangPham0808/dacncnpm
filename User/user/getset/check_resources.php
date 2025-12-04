<?php
/**
 * Script kiểm tra resources (CSS, JS, Images) có load được không
 * Truy cập: yourdomain.com/check_resources.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra Resources - DFC Gym</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #1a1a1a;
            color: #fff;
        }
        h1 {
            color: #22c55e;
        }
        .section {
            background: #2a2a2a;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .check-item {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .success {
            background: #22c55e;
            color: white;
        }
        .error {
            background: #ef4444;
            color: white;
        }
        .warning {
            background: #f59e0b;
            color: white;
        }
        .info {
            background: #3b82f6;
            color: white;
        }
        code {
            background: #1a1a1a;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        a {
            color: #22c55e;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>🔍 Kiểm tra Resources - DFC Gym</h1>
    
    <div class="section">
        <h2>1. Kiểm tra Tailwind CDN</h2>
        <?php
        $tailwindUrl = 'https://cdn.tailwindcss.com?plugins=forms,typography';
        $ch = curl_init($tailwindUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200) {
            echo '<div class="check-item success">✅ Tailwind CDN có thể truy cập được (HTTP ' . $httpCode . ')</div>';
        } else if ($error) {
            echo '<div class="check-item error">❌ Tailwind CDN không thể truy cập: ' . htmlspecialchars($error) . '</div>';
        } else {
            echo '<div class="check-item error">❌ Tailwind CDN trả về HTTP ' . $httpCode . '</div>';
        }
        ?>
        <div class="check-item info">
            <strong>URL:</strong> <code><?php echo htmlspecialchars($tailwindUrl); ?></code>
        </div>
    </div>

    <div class="section">
        <h2>2. Kiểm tra File CSS</h2>
        <?php
        $cssFiles = [
            '../../assets/css/style.css',
            '../../assets/css/packages.css',
            '../../assets/css/schedule.css',
            '../../assets/css/review.css',
            '../../assets/css/support.css',
        ];
        
        foreach ($cssFiles as $file) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                echo '<div class="check-item success">✅ ' . htmlspecialchars($file) . ' ('. number_format($size) . ' bytes)</div>';
            } else {
                echo '<div class="check-item error">❌ ' . htmlspecialchars($file) . ' không tồn tại</div>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>3. Kiểm tra File JavaScript</h2>
        <?php
        $jsFiles = [
            '../../assets/js/auth.js',
            '../../assets/js/navigation.js',
            '../../assets/js/packages.js',
            '../../assets/js/schedule.js',
            '../../assets/js/review.js',
            '../../assets/js/support.js',
            '../../assets/js/user-menu.js',
            '../../assets/js/inbox-modal-v2.js',
            '../../assets/js/modals-loader.js',
            '../../assets/js/mobile-menu.js',
        ];
        
        foreach ($jsFiles as $file) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                echo '<div class="check-item success">✅ ' . htmlspecialchars($file) . ' ('. number_format($size) . ' bytes)</div>';
            } else {
                echo '<div class="check-item error">❌ ' . htmlspecialchars($file) . ' không tồn tại</div>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>4. Kiểm tra Hình ảnh</h2>
        <?php
        $imageFiles = [
            '../../assets/img/logo.png',
            '../../assets/img/bank.jpg',
            '../../assets/img/momo.jpg',
            '../../assets/img/zalopay.jpg',
        ];
        
        foreach ($imageFiles as $file) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                echo '<div class="check-item success">✅ ' . htmlspecialchars($file) . ' ('. number_format($size) . ' bytes)</div>';
            } else {
                echo '<div class="check-item error">❌ ' . htmlspecialchars($file) . ' không tồn tại</div>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>5. Kiểm tra PHP</h2>
        <?php
        echo '<div class="check-item info">PHP Version: <code>' . phpversion() . '</code></div>';
        echo '<div class="check-item info">Session Status: <code>' . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . '</code></div>';
        
        if (function_exists('curl_init')) {
            echo '<div class="check-item success">✅ cURL extension đã được cài đặt</div>';
        } else {
            echo '<div class="check-item error">❌ cURL extension chưa được cài đặt</div>';
        }
        ?>
    </div>

    <div class="section">
        <h2>6. Hướng dẫn Debug</h2>
        <div class="check-item info">
            <strong>Bước 1:</strong> Mở Developer Tools (F12)<br>
            <strong>Bước 2:</strong> Xem tab <strong>Console</strong> để kiểm tra lỗi JavaScript<br>
            <strong>Bước 3:</strong> Xem tab <strong>Network</strong> để kiểm tra file nào không load được<br>
            <strong>Bước 4:</strong> Kiểm tra tab <strong>Elements</strong> để xem CSS có được áp dụng không
        </div>
        <div class="check-item warning">
            <strong>Lưu ý:</strong> Nếu Tailwind CDN không load được, xem file <code>HUONG_DAN_DEPLOY.md</code> để biết cách khắc phục.
        </div>
    </div>

    <div class="section">
        <h2>7. Test URL trực tiếp</h2>
        <p>Click vào các link sau để test file có load được không:</p>
        <ul>
            <li><a href="../../assets/css/style.css" target="_blank">../../assets/css/style.css</a></li>
            <li><a href="../../assets/js/auth.js" target="_blank">../../assets/js/auth.js</a></li>
            <li><a href="../../assets/img/logo.png" target="_blank">../../assets/img/logo.png</a></li>
        </ul>
        <p><strong>Nếu link trả về 404:</strong> Đường dẫn file không đúng hoặc file không tồn tại.</p>
        <p><strong>Nếu link trả về 403:</strong> File không có quyền đọc (check permissions).</p>
    </div>
</body>
</html>

