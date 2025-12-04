<?php
// Kết nối database
require_once __DIR__ . '/../../../Database/db.php';

// Sử dụng kết nối từ db.php
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$message = "";
$messageType = "";

// ====================== HÀM TẠO MÃ PHÒNG TẬP TỰ ĐỘNG ======================
function generateBranchCode($conn) {
    // Lấy mã phòng tập lớn nhất hiện tại
    $sql = "SELECT ma_phong_tap FROM phongtap WHERE ma_phong_tap LIKE 'PT%' ORDER BY ma_phong_tap DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastCode = $row['ma_phong_tap'];
        // Lấy phần số từ mã (ví dụ: PT001 -> 001)
        $number = intval(substr($lastCode, 2));
        // Tăng lên 1
        $newNumber = $number + 1;
        // Format lại thành 3 chữ số
        $newCode = 'PT' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    } else {
        // Nếu chưa có phòng tập nào, bắt đầu từ PT001
        $newCode = 'PT001';
    }
    
    return $newCode;
}

// ====================== THÊM PHÒNG TẬP ======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    // Tự động tạo mã phòng tập
    $ma_phong_tap = generateBranchCode($conn);
    
    $ten_phong_tap = trim($_POST['ten_phong_tap']);
    $dia_chi = trim($_POST['dia_chi']);
    $so_dien_thoai = trim($_POST['so_dien_thoai']);
    $email = trim($_POST['email']);
    $trang_thai = $_POST['trang_thai'];
    $ngay_thanh_lap = !empty($_POST['ngay_thanh_lap']) ? $_POST['ngay_thanh_lap'] : null;
    $ghi_chu = trim($_POST['ghi_chu']);

    $errors = [];

    // Validate
    if (empty($ten_phong_tap)) $errors[] = "⚠️ Tên phòng tập không được để trống.";
    if (empty($dia_chi)) $errors[] = "⚠️ Địa chỉ không được để trống.";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "❌ Email không hợp lệ.";
    if (!empty($so_dien_thoai) && !preg_match('/^[0-9]{10,11}$/', $so_dien_thoai)) {
        $errors[] = "❌ Số điện thoại phải có 10-11 chữ số.";
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $messageType = "error";
    } else {
        $sql = "INSERT INTO phongtap (ma_phong_tap, ten_phong_tap, dia_chi, so_dien_thoai, email, 
                trang_thai, ngay_thanh_lap, ghi_chu) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $ma_phong_tap, $ten_phong_tap, $dia_chi, $so_dien_thoai, 
                         $email, $trang_thai, $ngay_thanh_lap, $ghi_chu);
        
        if ($stmt->execute()) {
            $message = "✅ Thêm phòng tập thành công! Mã phòng tập: " . $ma_phong_tap;
            $messageType = "success";
        } else {
            $message = "❌ Lỗi: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    }
}

// ====================== CẬP NHẬT PHÒNG TẬP ======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $phong_tap_id = intval($_POST['phong_tap_id']);
    $ten_phong_tap = trim($_POST['ten_phong_tap']);
    $dia_chi = trim($_POST['dia_chi']);
    $so_dien_thoai = trim($_POST['so_dien_thoai']);
    $email = trim($_POST['email']);
    $trang_thai = $_POST['trang_thai'];
    $ngay_thanh_lap = !empty($_POST['ngay_thanh_lap']) ? $_POST['ngay_thanh_lap'] : null;
    $ghi_chu = trim($_POST['ghi_chu']);

    $errors = [];

    // Validate
    if (empty($ten_phong_tap)) $errors[] = "⚠️ Tên phòng tập không được để trống.";
    if (empty($dia_chi)) $errors[] = "⚠️ Địa chỉ không được để trống.";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "❌ Email không hợp lệ.";
    if (!empty($so_dien_thoai) && !preg_match('/^[0-9]{10,11}$/', $so_dien_thoai)) {
        $errors[] = "❌ Số điện thoại phải có 10-11 chữ số.";
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $messageType = "error";
    } else {
        $sql = "UPDATE phongtap SET ten_phong_tap=?, dia_chi=?, so_dien_thoai=?, email=?, 
                trang_thai=?, ngay_thanh_lap=?, ghi_chu=? 
                WHERE phong_tap_id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $ten_phong_tap, $dia_chi, $so_dien_thoai, $email, 
                         $trang_thai, $ngay_thanh_lap, $ghi_chu, $phong_tap_id);
        
        if ($stmt->execute()) {
            $message = "✅ Cập nhật phòng tập thành công!";
            $messageType = "success";
        } else {
            $message = "❌ Lỗi: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    }
}

// ====================== XÓA PHÒNG TẬP ======================
if (isset($_GET['delete'])) {
    $phong_tap_id = intval($_GET['delete']);
    
    try {
        $sql = "DELETE FROM phongtap WHERE phong_tap_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $phong_tap_id);
        
        if ($stmt->execute()) {
            $message = "✅ Xóa phòng tập thành công!";
            $messageType = "success";
        } else {
            $message = "❌ Lỗi: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    } catch (Exception $e) {
        $message = "❌ Không thể xóa phòng tập này do có dữ liệu liên quan!";
        $messageType = "error";
    }
}

// ====================== LẤY CHI TIẾT PHÒNG TẬP (AJAX) ======================
if (isset($_GET['action']) && $_GET['action'] == 'getDetail' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    $phong_tap_id = intval($_GET['id']);
    
    $sql = "SELECT * FROM phongtap WHERE phong_tap_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $phong_tap_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phòng tập']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// ====================== TÌM KIẾM VÀ LỌC ======================
$searchTerm = "";
$statusFilter = "";
$whereConditions = [];
$params = [];
$types = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $whereConditions[] = "(ma_phong_tap LIKE ? OR ten_phong_tap LIKE ? OR dia_chi LIKE ? OR so_dien_thoai LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "ssss";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $statusFilter = $_GET['status'];
    $whereConditions[] = "trang_thai = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$sql = "SELECT * FROM phongtap";

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY ngay_tao DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>