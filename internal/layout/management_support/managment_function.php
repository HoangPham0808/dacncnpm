<?php
session_start();
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

// Xử lý TRẢ LỜI hỗ trợ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $ho_tro_id = intval($_POST['ho_tro_id']);
    $phan_hoi = trim($_POST['phan_hoi']);
    $nhan_vien_id = $_SESSION['admin']['nhan_vien_id'] ?? null;
    
    if (empty($phan_hoi)) {
        $message = "Vui lòng nhập phản hồi!";
        $messageType = "error";
    } else {
        // Cập nhật phản hồi và trạng thái
        $sql = "UPDATE Hotro SET phan_hoi = ?, nhan_vien_id = ?, trang_thai = 'Đã phản hồi', ngay_cap_nhat = NOW() WHERE ho_tro_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $phan_hoi, $nhan_vien_id, $ho_tro_id);
        
        if ($stmt->execute()) {
            // Lấy thông tin khách hàng để tạo thông báo
            $getKhStmt = $conn->prepare("SELECT khach_hang_id, email, content FROM Hotro WHERE ho_tro_id = ?");
            $getKhStmt->bind_param("i", $ho_tro_id);
            $getKhStmt->execute();
            $result = $getKhStmt->get_result();
            $hotroData = $result->fetch_assoc();
            $getKhStmt->close();
            
            // Tạo thông báo cho khách hàng
            if ($hotroData && $hotroData['khach_hang_id']) {
                $tieu_de = "Phản hồi yêu cầu hỗ trợ #" . $ho_tro_id;
                $noi_dung = "Xin chào,\n\nChúng tôi đã nhận được yêu cầu hỗ trợ của bạn và đã phản hồi như sau:\n\n" . 
                           "📝 Yêu cầu của bạn: " . $hotroData['content'] . "\n\n" .
                           "💬 Phản hồi từ nhân viên:\n" . $phan_hoi . "\n\n" .
                           "Trân trọng,\nĐội ngũ hỗ trợ DFC Gym";
                
                // Kiểm tra xem bảng ThongBao có tồn tại không
                $checkTableStmt = $conn->query("SHOW TABLES LIKE 'ThongBao'");
                if ($checkTableStmt && $checkTableStmt->num_rows > 0) {
                    $insertNotifStmt = $conn->prepare("INSERT INTO ThongBao (tieu_de, noi_dung, loai_thong_bao, doi_tuong_nhan, khach_hang_nhan_id, da_doc) VALUES (?, ?, 'Hệ thống', 'Cá nhân', ?, 0)");
                    $insertNotifStmt->bind_param("ssi", $tieu_de, $noi_dung, $hotroData['khach_hang_id']);
                    $insertNotifStmt->execute();
                    $insertNotifStmt->close();
                }
            }
            
            $message = "Đã trả lời yêu cầu hỗ trợ thành công!";
            $messageType = "success";
        } else {
            $message = "Có lỗi xảy ra khi trả lời: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Xử lý ĐÁNH DẤU ĐÃ XỬ LÝ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_resolved') {
    $ho_tro_id = intval($_POST['ho_tro_id']);
    $nhan_vien_id = $_SESSION['admin']['nhan_vien_id'] ?? null;
    
    $sql = "UPDATE Hotro SET trang_thai = 'Đã phản hồi', nhan_vien_id = ?, ngay_cap_nhat = NOW() WHERE ho_tro_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $nhan_vien_id, $ho_tro_id);
    
    if ($stmt->execute()) {
        // Lấy thông tin khách hàng để tạo thông báo
        $getKhStmt = $conn->prepare("SELECT khach_hang_id, email, content FROM Hotro WHERE ho_tro_id = ?");
        $getKhStmt->bind_param("i", $ho_tro_id);
        $getKhStmt->execute();
        $result = $getKhStmt->get_result();
        $hotroData = $result->fetch_assoc();
        $getKhStmt->close();
        
        // Tạo thông báo cho khách hàng
        if ($hotroData && $hotroData['khach_hang_id']) {
            $tieu_de = "Yêu cầu hỗ trợ #" . $ho_tro_id . " đã được xử lý";
            $noi_dung = "Xin chào,\n\nYêu cầu hỗ trợ của bạn đã được đánh dấu là đã xử lý.\n\n" .
                       "📝 Yêu cầu: " . $hotroData['content'] . "\n\n" .
                       "Cảm ơn bạn đã liên hệ với chúng tôi.\n\n" .
                       "Trân trọng,\nĐội ngũ hỗ trợ DFC Gym";
            
            // Kiểm tra xem bảng ThongBao có tồn tại không
            $checkTableStmt = $conn->query("SHOW TABLES LIKE 'ThongBao'");
            if ($checkTableStmt && $checkTableStmt->num_rows > 0) {
                $insertNotifStmt = $conn->prepare("INSERT INTO ThongBao (tieu_de, noi_dung, loai_thong_bao, doi_tuong_nhan, khach_hang_nhan_id, da_doc) VALUES (?, ?, 'Hệ thống', 'Cá nhân', ?, 0)");
                $insertNotifStmt->bind_param("ssi", $tieu_de, $noi_dung, $hotroData['khach_hang_id']);
                $insertNotifStmt->execute();
                $insertNotifStmt->close();
            }
        }
        
        $message = "Đã đánh dấu yêu cầu hỗ trợ là đã xử lý!";
        $messageType = "success";
    } else {
        $message = "Có lỗi xảy ra: " . $stmt->error;
        $messageType = "error";
    }
    $stmt->close();
}

// Xử lý ĐÓNG yêu cầu hỗ trợ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close') {
    $ho_tro_id = intval($_POST['ho_tro_id']);
    $nhan_vien_id = $_SESSION['admin']['nhan_vien_id'] ?? null;
    
    $sql = "UPDATE Hotro SET trang_thai = 'Đã đóng', nhan_vien_id = ?, ngay_cap_nhat = NOW() WHERE ho_tro_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $nhan_vien_id, $ho_tro_id);
    
    if ($stmt->execute()) {
        // Lấy thông tin khách hàng để tạo thông báo
        $getKhStmt = $conn->prepare("SELECT khach_hang_id, email, content FROM Hotro WHERE ho_tro_id = ?");
        $getKhStmt->bind_param("i", $ho_tro_id);
        $getKhStmt->execute();
        $result = $getKhStmt->get_result();
        $hotroData = $result->fetch_assoc();
        $getKhStmt->close();
        
        // Tạo thông báo cho khách hàng
        if ($hotroData && $hotroData['khach_hang_id']) {
            $tieu_de = "Yêu cầu hỗ trợ #" . $ho_tro_id . " đã được đóng";
            $noi_dung = "Xin chào,\n\nYêu cầu hỗ trợ của bạn đã được đóng.\n\n" .
                       "📝 Yêu cầu: " . $hotroData['content'] . "\n\n" .
                       "Nếu bạn có thắc mắc khác, vui lòng liên hệ lại với chúng tôi.\n\n" .
                       "Trân trọng,\nĐội ngũ hỗ trợ DFC Gym";
            
            // Kiểm tra xem bảng ThongBao có tồn tại không
            $checkTableStmt = $conn->query("SHOW TABLES LIKE 'ThongBao'");
            if ($checkTableStmt && $checkTableStmt->num_rows > 0) {
                $insertNotifStmt = $conn->prepare("INSERT INTO ThongBao (tieu_de, noi_dung, loai_thong_bao, doi_tuong_nhan, khach_hang_nhan_id, da_doc) VALUES (?, ?, 'Hệ thống', 'Cá nhân', ?, 0)");
                $insertNotifStmt->bind_param("ssi", $tieu_de, $noi_dung, $hotroData['khach_hang_id']);
                $insertNotifStmt->execute();
                $insertNotifStmt->close();
            }
        }
        
        $message = "Đã đóng yêu cầu hỗ trợ thành công!";
        $messageType = "success";
    } else {
        $message = "Có lỗi xảy ra: " . $stmt->error;
        $messageType = "error";
    }
    $stmt->close();
}

// Lấy danh sách hỗ trợ từ database
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT 
            h.ho_tro_id,
            h.email,
            h.so_dien_thoai,
            h.content,
            h.phan_hoi,
            h.trang_thai,
            h.thoi_gian,
            h.ngay_tao,
            h.ngay_cap_nhat,
            h.khach_hang_id,
            h.nhan_vien_id,
            kh.ho_ten AS ten_khach_hang,
            nv.ho_ten AS ten_nhan_vien
        FROM Hotro h
        LEFT JOIN KhachHang kh ON h.khach_hang_id = kh.khach_hang_id
        LEFT JOIN NhanVien nv ON h.nhan_vien_id = nv.nhan_vien_id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($statusFilter)) {
    // Map filter value to database status
    $statusMap = [
        'pending' => 'Mới',
        'processing' => 'Đang xử lý',
        'resolved' => 'Đã phản hồi',
        'closed' => 'Đã đóng'
    ];
    
    if (isset($statusMap[$statusFilter])) {
        $sql .= " AND h.trang_thai = ?";
        $params[] = $statusMap[$statusFilter];
        $types .= "s";
    }
}

$sql .= " ORDER BY h.ngay_tao DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$supportList = [];
while ($row = $result->fetch_assoc()) {
    $supportList[] = $row;
}
$stmt->close();

// Hàm format ngày tháng
function formatDate($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date('d/m/Y H:i', $timestamp);
}

// Hàm map trạng thái sang class CSS
function getStatusClass($trang_thai) {
    $statusMap = [
        'Mới' => 'pending',
        'Đang xử lý' => 'processing',
        'Đã phản hồi' => 'resolved',
        'Đã đóng' => 'closed'
    ];
    return $statusMap[$trang_thai] ?? 'pending';
}

// Hàm map trạng thái sang text hiển thị
function getStatusText($trang_thai) {
    $statusMap = [
        'Mới' => 'Chờ xử lý',
        'Đang xử lý' => 'Đang xử lý',
        'Đã phản hồi' => 'Đã giải quyết',
        'Đã đóng' => 'Đã đóng'
    ];
    return $statusMap[$trang_thai] ?? $trang_thai;
}
?>

