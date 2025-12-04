<?php

session_start();

// Kết nối database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dfcgym";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$message = "";
$messageType = "";

// Xử lý THÊM gói tập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $ma_goi_tap = $_POST['ma_goi_tap'];
    $ten_goi = $_POST['ten_goi'];
    $mo_ta = $_POST['mo_ta'];
    $thoi_han_ngay = $_POST['thoi_han_ngay'];
    $gia_tien = $_POST['gia_tien'];
    $loai_goi = $_POST['loai_goi'];
    $trang_thai = $_POST['trang_thai'];
    
    // Kiểm tra mã gói tập đã tồn tại chưa
    $checkSql = "SELECT COUNT(*) as count FROM goitap WHERE ma_goi_tap = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $ma_goi_tap);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkRow = $checkResult->fetch_assoc();
    
    if ($checkRow['count'] > 0) {
        $message = "Mã gói tập đã tồn tại!";
        $messageType = "error";
    } else {
        $sql = "INSERT INTO goitap (ma_goi_tap, ten_goi, mo_ta, thoi_han_ngay, gia_tien, loai_goi, trang_thai) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssidss", $ma_goi_tap, $ten_goi, $mo_ta, $thoi_han_ngay, $gia_tien, $loai_goi, $trang_thai);
        
        if ($stmt->execute()) {
            $message = "Thêm gói tập thành công!";
            $messageType = "success";
        } else {
            $message = "Có lỗi xảy ra khi thêm gói tập!";
            $messageType = "error";
        }
        $stmt->close();
    }
    $checkStmt->close();
}

// Xử lý SỬA gói tập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $goi_tap_id = $_POST['goi_tap_id'];
    $ma_goi_tap = $_POST['ma_goi_tap'];
    $ten_goi = $_POST['ten_goi'];
    $mo_ta = $_POST['mo_ta'];
    $thoi_han_ngay = $_POST['thoi_han_ngay'];
    $gia_tien = $_POST['gia_tien'];
    $loai_goi = $_POST['loai_goi'];
    $trang_thai = $_POST['trang_thai'];
    
    // Kiểm tra mã gói tập đã tồn tại chưa (trừ chính nó)
    $checkSql = "SELECT COUNT(*) as count FROM goitap WHERE ma_goi_tap = ? AND goi_tap_id != ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("si", $ma_goi_tap, $goi_tap_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkRow = $checkResult->fetch_assoc();
    
    if ($checkRow['count'] > 0) {
        $message = "Mã gói tập đã tồn tại!";
        $messageType = "error";
    } else {
        $sql = "UPDATE goitap SET 
                ma_goi_tap = ?,
                ten_goi = ?,
                mo_ta = ?,
                thoi_han_ngay = ?,
                gia_tien = ?,
                loai_goi = ?,
                trang_thai = ?
                WHERE goi_tap_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssidssi", $ma_goi_tap, $ten_goi, $mo_ta, $thoi_han_ngay, $gia_tien, $loai_goi, $trang_thai, $goi_tap_id);
        
        if ($stmt->execute()) {
            $message = "Cập nhật gói tập thành công!";
            $messageType = "success";
        } else {
            $message = "Có lỗi xảy ra khi cập nhật gói tập!";
            $messageType = "error";
        }
        $stmt->close();
    }
    $checkStmt->close();
}

// Xử lý XÓA gói tập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $goi_tap_id = $_POST['goi_tap_id'];
    
    // Kiểm tra xem gói tập có đang được sử dụng không trong bảng dangkigoitap
    $checkSql = "SELECT COUNT(*) as count FROM dangkygoitap WHERE goi_tap_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $goi_tap_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkRow = $checkResult->fetch_assoc();

        $sql = "DELETE FROM goitap WHERE goi_tap_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $goi_tap_id);
        
        if ($stmt->execute()) {
            $message = "Xóa gói tập thành công!";
            $messageType = "success";
        } else {
            $message = "Có lỗi xảy ra khi xóa gói tập!";
            $messageType = "error";
        }
    $checkStmt->close();
}

// Lấy danh sách gói tập với explicit column names
$sql = "SELECT 
    goi_tap_id,
    ma_goi_tap,
    ten_goi,
    mo_ta,
    thoi_han_ngay,
    gia_tien,
    loai_goi,
    trang_thai,
    ngay_tao
FROM goitap ORDER BY ngay_tao DESC";
$result = $conn->query($sql);
$packages = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $packages[] = $row;
    }
}
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// Làm sạch chuỗi đầu vào
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate số điện thoại Việt Nam
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(0[3|5|7|8|9])+([0-9]{8})$/', $phone);
}

// Tạo mã ngẫu nhiên
function generateCode($prefix = '', $length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = $prefix;
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Chuyển đổi ngày tháng
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

// Tính số ngày giữa 2 ngày
function daysBetween($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

// Tính ngày hết hạn
function calculateExpireDate($startDate, $days) {
    $date = new DateTime($startDate);
    $date->add(new DateInterval('P' . $days . 'D'));
    return $date->format('Y-m-d');
}

// Kiểm tra ngày hết hạn
function isExpired($expireDate) {
    $today = new DateTime();
    $expire = new DateTime($expireDate);
    return $today > $expire;
}

// Tạo slug từ chuỗi tiếng Việt
function createSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return $str;
}

// Cắt chuỗi với độ dài xác định
function truncateString($string, $length = 100, $append = '...') {
    $string = trim($string);
    if(strlen($string) > $length) {
        $string = wordwrap($string, $length);
        $string = explode("\n", $string, 2);
        $string = $string[0] . $append;
    }
    return $string;
}

// Upload file
function uploadFile($file, $uploadDir = 'uploads/', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['name']) || empty($file['name'])) {
        return ['success' => false, 'message' => 'Không có file nào được chọn'];
    }
    
    $fileName = basename($file['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    
    // Kiểm tra loại file
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Loại file không được phép'];
    }
    
    // Kiểm tra kích thước (5MB)
    if ($fileSize > 5242880) {
        return ['success' => false, 'message' => 'File quá lớn (tối đa 5MB)'];
    }
    
    // Tạo tên file mới
    $newFileName = uniqid() . '.' . $fileType;
    $targetFile = $uploadDir . $newFileName;
    
    // Tạo thư mục nếu chưa tồn tại
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Upload file
    if (move_uploaded_file($fileTmpName, $targetFile)) {
        return ['success' => true, 'fileName' => $newFileName, 'filePath' => $targetFile];
    } else {
        return ['success' => false, 'message' => 'Có lỗi khi upload file'];
    }
}

// Xóa file
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Gửi email (cần cấu hình SMTP)
function sendEmail($to, $subject, $message, $from = 'noreply@dfcgym.com') {
    $headers = "From: " . $from . "\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Tạo password hash
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Kiểm tra password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Log hoạt động
function logActivity($userId, $action, $description, $conn) {
    $sql = "INSERT INTO activity_log (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $action, $description);
    return $stmt->execute();
}

// Xuất Excel (cần PHPSpreadsheet)
function exportToExcel($data, $fileName = 'export.xlsx') {
    // Cần cài đặt PHPSpreadsheet
    // composer require phpoffice/phpspreadsheet
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
}

// Pagination
function paginate($totalItems, $itemsPerPage, $currentPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
        'itemsPerPage' => $itemsPerPage,
        'offset' => $offset,
        'hasNext' => $currentPage < $totalPages,
        'hasPrev' => $currentPage > 1
    ];
}

// Tạo token ngẫu nhiên
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Kiểm tra quyền truy cập
function checkPermission($userRole, $requiredRole) {
    $roles = ['member' => 1, 'staff' => 2, 'admin' => 3];
    return $roles[$userRole] >= $roles[$requiredRole];
}

// Tính phần trăm
function calculatePercentage($value, $total) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100, 2);
}

// Format số
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

// Kiểm tra chuỗi rỗng
function isEmpty($value) {
    return empty($value) && $value !== '0';
}

// Debug function
function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

// Redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Get client IP
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
?>