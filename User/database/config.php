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
if (!defined('APP_ENV')) {
    define('APP_ENV', $isLocal ? 'local' : 'production');
}

if ($isLocal) {
    // XAMPP local database
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'dfcgym');  // Đổi từ dinhbavu sang dfcgym để khớp với phpMyAdmin
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
    $scriptPath = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    
    // Chuẩn hóa đường dẫn (thay \ bằng /)
    $scriptPath = str_replace('\\', '/', $scriptPath);
    
    // Tìm vị trí của 'doanchuyennganh' trong đường dẫn (cho local)
    $pos = strpos($scriptPath, '/doanchuyennganh/');
    if ($pos !== false) {
        // Nếu tìm thấy, base là /doanchuyennganh/
        $base = '/doanchuyennganh/';
    } else {
        // Trên hosting (InfinityFree), tính toán BASE_URL từ SCRIPT_NAME
        // Trên InfinityFree, files thường ở root hoặc trong subdirectory
        // SCRIPT_NAME sẽ là: /dfcadm/internal/nhanvien/index_internal/login_process.php
        // hoặc: /login.html, /user/dangnhap/login.php, etc.
        
        // Lấy đường dẫn từ SCRIPT_NAME
        $scriptPath = str_replace('\\', '/', $scriptPath);
        
        // Nếu script ở root (ví dụ: /login.html, /index.html)
        if (substr_count($scriptPath, '/') <= 1) {
            $base = '/';
        } else {
            // Script ở trong subdirectory
            // Tìm thư mục gốc của project
            // Nếu có /user/, lấy phần trước /user/
            // Nếu có /dfcadm/, lấy phần trước /dfcadm/ (hoặc root nếu /dfcadm/ ở đầu)
            // Nếu không có cả hai, lấy dirname của script path
            
            if (strpos($scriptPath, '/user/') !== false) {
                $userPos = strpos($scriptPath, '/user/');
                $base = substr($scriptPath, 0, $userPos);
            } elseif (strpos($scriptPath, '/dfcadm/') !== false) {
                $dfcPos = strpos($scriptPath, '/dfcadm/');
                $base = substr($scriptPath, 0, $dfcPos);
                // Nếu /dfcadm/ ở đầu, base là root
                if ($dfcPos === 0) {
                    $base = '/';
                }
            } else {
                // Script ở root hoặc thư mục khác
                // Lấy phần đầu tiên của path (thư mục gốc)
                $parts = explode('/', trim($scriptPath, '/'));
                if (count($parts) > 0) {
                    // Trên InfinityFree, thường files ở root
                    $base = '/';
                } else {
                    $base = '/';
                }
            }
        }
        
        // Chuẩn hóa đường dẫn
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
// Không có whitespace hoặc output sau đây

