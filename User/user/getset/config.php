<?php
// Auto-detect environment: local (XAMPP) vs hosting (InfinityFree)
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$isLocal = (
    $host === 'localhost' ||
    $host === '127.0.0.1' ||
    strpos($host, '192.168.') === 0 ||
    strpos($host, '10.') === 0 ||
    strpos($host, '172.16.') === 0
);

// Expose environment and ports
define('APP_ENV', $isLocal ? 'local' : 'production');

if ($isLocal) {
    // XAMPP local database
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'gym_management'); 
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', '3306');
} else {
    // InfinityFree hosting database
    define('DB_HOST', 'sql101.infinityfree.com');
    define('DB_NAME', 'if0_40194494_dfcgym');   
    define('DB_USER', 'if0_40194494');
    define('DB_PASS', 'sAFopMlFi6');
    define('DB_PORT', '3306');
}


// Auto-detect BASE_URL cho cả local và hosting (InfinityFree)
if (!defined('BASE_URL')) {
    // Lấy đường dẫn script hiện tại (ví dụ: /doanchuyennganh/user/dangnhap/login.php)
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    
    // Chuẩn hóa đường dẫn (thay \ bằng /)
    $scriptPath = str_replace('\\', '/', $scriptPath);
    
    // Tìm vị trí của 'doanchuyennganh' trong đường dẫn
    $pos = strpos($scriptPath, '/doanchuyennganh/');
    if ($pos !== false) {
        // Nếu tìm thấy, base là /doanchuyennganh/
        $base = '/doanchuyennganh/';
    } else {
        // Nếu không tìm thấy (có thể là hosting), lấy thư mục gốc của script
        $scriptDir = dirname($scriptPath);
        
        // Nếu script ở trong thư mục user/ hoặc bất kỳ thư mục con nào của user/
        if (strpos($scriptDir, '/user/') !== false || strpos($scriptDir, '\\user\\') !== false) {
            // Tìm vị trí /user/ và lấy phần trước đó
            $userPos = strpos($scriptDir, '/user/');
            if ($userPos !== false) {
                $base = substr($scriptDir, 0, $userPos);
            } else {
                $userPos = strpos($scriptDir, '\\user\\');
                if ($userPos !== false) {
                    $base = substr($scriptDir, 0, $userPos);
                } else {
                    $base = dirname($scriptDir);
                }
            }
        } else {
            // Script ở root hoặc thư mục khác
            $base = $scriptDir;
        }
        
        // Chuẩn hóa đường dẫn (thay \ bằng /)
        $base = str_replace('\\', '/', $base);
        
        // Đảm bảo có dấu / ở đầu
        if (substr($base, 0, 1) !== '/') {
            $base = '/' . $base;
        }
        
        // Loại bỏ dấu / ở cuối và thêm lại
        $base = rtrim($base, '/');
        $base = $base ? $base . '/' : '/';
    }
    
    define('BASE_URL', $base);
}
