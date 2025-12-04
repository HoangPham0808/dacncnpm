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

// Lấy thông báo từ session nếu có
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

// Kiểm tra đăng nhập và lấy thông tin nhân viên
if (!isset($_SESSION['nhan_vien_id']) || !isset($_SESSION['vai_tro'])) {
    header("Location: ../login.php");
    exit();
}

$nhan_vien_id = $_SESSION['nhan_vien_id'];
$vai_tro = $_SESSION['vai_tro'];

// Lấy phong_tap_id của nhân viên (nếu là PT)
$nhan_vien_phong_tap_id = null;
if ($vai_tro === 'PT') {
    $stmt = $conn->prepare("SELECT phong_tap_id FROM nhanvien WHERE nhan_vien_id = ?");
    $stmt->bind_param("i", $nhan_vien_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nhan_vien_phong_tap_id = $row['phong_tap_id'];
    }
    $stmt->close();
}

// Xử lý THÊM lịch tập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $ten_lop = trim($_POST['ten_lop']);
    $mo_ta = !empty($_POST['mo_ta']) ? trim($_POST['mo_ta']) : null;
    $nhan_vien_pt_id = !empty($_POST['nhan_vien_pt_id']) ? intval($_POST['nhan_vien_pt_id']) : null;
    $ngay_tap = $_POST['ngay_tap'];
    $gio_bat_dau = $_POST['gio_bat_dau'];
    $gio_ket_thuc = $_POST['gio_ket_thuc'];
    $so_luong_toi_da = !empty($_POST['so_luong_toi_da']) ? intval($_POST['so_luong_toi_da']) : 20;
    $phong = !empty($_POST['phong']) ? trim($_POST['phong']) : null;
    $trang_thai = $_POST['trang_thai'] ?? 'Đang mở';
    $phong_tap_id = !empty($_POST['phong_tap_id']) ? intval($_POST['phong_tap_id']) : null;
    
    if (empty($ten_lop) || empty($ngay_tap) || empty($gio_bat_dau) || empty($gio_ket_thuc) || empty($nhan_vien_pt_id) || empty($phong_tap_id)) {
        $message = "Vui lòng điền đầy đủ thông tin bắt buộc (Tên lớp, Ngày tập, Giờ, Huấn luyện viên, Phòng tập)!";
        $messageType = "error";
    } else {
        // Kiểm tra huấn luyện viên có vai trò PT không
        $checkPT = $conn->prepare("SELECT vai_tro FROM nhanvien WHERE nhan_vien_id = ? AND vai_tro = 'PT'");
        $checkPT->bind_param("i", $nhan_vien_pt_id);
        $checkPT->execute();
        $checkPT->store_result();
        
        if ($checkPT->num_rows === 0) {
            $message = "Nhân viên được chọn không có vai trò Huấn luyện viên (PT)!";
            $messageType = "error";
            $checkPT->close();
        } else {
            $checkPT->close();
            
            // Nếu là PT, chỉ được thêm vào phòng của mình
            if ($vai_tro === 'PT' && $phong_tap_id != $nhan_vien_phong_tap_id) {
                $message = "Bạn chỉ có thể thêm lịch tập vào phòng tập của mình!";
                $messageType = "error";
            } else {
                // Chuẩn hóa giá trị NULL
                $mo_ta = ($mo_ta === '' || $mo_ta === null) ? null : trim($mo_ta);
                $phong = ($phong === '' || $phong === null) ? null : trim($phong);
                
                $sql = "INSERT INTO LichTap (ten_lop, mo_ta, nhan_vien_pt_id, ngay_tap, gio_bat_dau, gio_ket_thuc, so_luong_toi_da, phong, trang_thai, phong_tap_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("ssisssissi", $ten_lop, $mo_ta, $nhan_vien_pt_id, $ngay_tap, $gio_bat_dau, $gio_ket_thuc, $so_luong_toi_da, $phong, $trang_thai, $phong_tap_id);
                    
                    if ($stmt->execute()) {
                        $stmt->close();
                        $_SESSION['message'] = "Thêm lịch tập thành công!";
                        $_SESSION['messageType'] = "success";
                        $redirectUrl = $_SERVER['PHP_SELF'];
                        if (isset($_GET['week'])) {
                            $redirectUrl .= "?week=" . intval($_GET['week']);
                        }
                        if (isset($_GET['phong_tap_id'])) {
                            $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . "phong_tap_id=" . intval($_GET['phong_tap_id']);
                        }
                        header("Location: " . $redirectUrl);
                        exit();
                    } else {
                        $message = "Có lỗi xảy ra khi thêm lịch tập: " . $stmt->error;
                        $messageType = "error";
                        $stmt->close();
                    }
                } else {
                    $message = "Có lỗi xảy ra khi chuẩn bị câu lệnh SQL: " . $conn->error;
                    $messageType = "error";
                }
            }
        }
    }
}

// Xử lý SỬA lịch tập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $lich_tap_id = intval($_POST['lich_tap_id']);
    $ten_lop = trim($_POST['ten_lop']);
    $mo_ta = !empty($_POST['mo_ta']) ? trim($_POST['mo_ta']) : null;
    $nhan_vien_pt_id = !empty($_POST['nhan_vien_pt_id']) ? intval($_POST['nhan_vien_pt_id']) : null;
    $ngay_tap = $_POST['ngay_tap'];
    $gio_bat_dau = $_POST['gio_bat_dau'];
    $gio_ket_thuc = $_POST['gio_ket_thuc'];
    $so_luong_toi_da = !empty($_POST['so_luong_toi_da']) ? intval($_POST['so_luong_toi_da']) : 20;
    $phong = !empty($_POST['phong']) ? trim($_POST['phong']) : null;
    $trang_thai = $_POST['trang_thai'] ?? 'Đang mở';
    $phong_tap_id = !empty($_POST['phong_tap_id']) ? intval($_POST['phong_tap_id']) : null;
    
    if (empty($ten_lop) || empty($ngay_tap) || empty($gio_bat_dau) || empty($gio_ket_thuc) || empty($nhan_vien_pt_id) || empty($phong_tap_id)) {
        $message = "Vui lòng điền đầy đủ thông tin bắt buộc!";
        $messageType = "error";
    } else {
        // Kiểm tra huấn luyện viên có vai trò PT không
        $checkPT = $conn->prepare("SELECT vai_tro FROM nhanvien WHERE nhan_vien_id = ? AND vai_tro = 'PT'");
        $checkPT->bind_param("i", $nhan_vien_pt_id);
        $checkPT->execute();
        $checkPT->store_result();
        
        if ($checkPT->num_rows === 0) {
            $message = "Nhân viên được chọn không có vai trò Huấn luyện viên (PT)!";
            $messageType = "error";
            $checkPT->close();
        } else {
            $checkPT->close();
            
            // Nếu là PT, chỉ được sửa lịch của phòng mình
            if ($vai_tro === 'PT' && $phong_tap_id != $nhan_vien_phong_tap_id) {
                $message = "Bạn chỉ có thể sửa lịch tập của phòng tập của mình!";
                $messageType = "error";
            } else {
                $mo_ta = ($mo_ta === '' || $mo_ta === null) ? null : trim($mo_ta);
                $phong = ($phong === '' || $phong === null) ? null : trim($phong);
                
                $sql = "UPDATE LichTap SET ten_lop = ?, mo_ta = ?, nhan_vien_pt_id = ?, ngay_tap = ?, gio_bat_dau = ?, gio_ket_thuc = ?, so_luong_toi_da = ?, phong = ?, trang_thai = ?, phong_tap_id = ? WHERE lich_tap_id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("ssisssissii", $ten_lop, $mo_ta, $nhan_vien_pt_id, $ngay_tap, $gio_bat_dau, $gio_ket_thuc, $so_luong_toi_da, $phong, $trang_thai, $phong_tap_id, $lich_tap_id);
                    
                    if ($stmt->execute()) {
                        $stmt->close();
                        $_SESSION['message'] = "Cập nhật lịch tập thành công!";
                        $_SESSION['messageType'] = "success";
                        $redirectUrl = $_SERVER['PHP_SELF'];
                        if (isset($_GET['week'])) {
                            $redirectUrl .= "?week=" . intval($_GET['week']);
                        }
                        if (isset($_GET['phong_tap_id'])) {
                            $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . "phong_tap_id=" . intval($_GET['phong_tap_id']);
                        }
                        header("Location: " . $redirectUrl);
                        exit();
                    } else {
                        $message = "Có lỗi xảy ra khi cập nhật lịch tập: " . $stmt->error;
                        $messageType = "error";
                        $stmt->close();
                    }
                } else {
                    $message = "Có lỗi xảy ra khi chuẩn bị câu lệnh SQL: " . $conn->error;
                    $messageType = "error";
                }
            }
        }
    }
}

// Xử lý XÓA lịch tập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $lich_tap_id = intval($_POST['lich_tap_id']);
    
    // Kiểm tra xem có khách hàng đã đăng ký không
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM DangKyLichTap WHERE lich_tap_id = ?");
    $checkStmt->bind_param("i", $lich_tap_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($row['count'] > 0) {
        $updateStmt = $conn->prepare("UPDATE LichTap SET trang_thai = 'Hủy' WHERE lich_tap_id = ?");
        $updateStmt->bind_param("i", $lich_tap_id);
        if ($updateStmt->execute()) {
            $updateStmt->close();
            $_SESSION['message'] = "Lịch tập đã có người đăng ký, trạng thái được cập nhật thành 'Hủy'!";
            $_SESSION['messageType'] = "success";
            $redirectUrl = $_SERVER['PHP_SELF'];
            if (isset($_GET['week'])) {
                $redirectUrl .= "?week=" . intval($_GET['week']);
            }
            if (isset($_GET['phong_tap_id'])) {
                $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . "phong_tap_id=" . intval($_GET['phong_tap_id']);
            }
            header("Location: " . $redirectUrl);
            exit();
        } else {
            $message = "Có lỗi xảy ra: " . $updateStmt->error;
            $messageType = "error";
            $updateStmt->close();
        }
    } else {
        $deleteStmt = $conn->prepare("DELETE FROM LichTap WHERE lich_tap_id = ?");
        $deleteStmt->bind_param("i", $lich_tap_id);
        if ($deleteStmt->execute()) {
            $deleteStmt->close();
            $_SESSION['message'] = "Xóa lịch tập thành công!";
            $_SESSION['messageType'] = "success";
            $redirectUrl = $_SERVER['PHP_SELF'];
            if (isset($_GET['week'])) {
                $redirectUrl .= "?week=" . intval($_GET['week']);
            }
            if (isset($_GET['phong_tap_id'])) {
                $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . "phong_tap_id=" . intval($_GET['phong_tap_id']);
            }
            header("Location: " . $redirectUrl);
            exit();
        } else {
            $message = "Có lỗi xảy ra khi xóa lịch tập: " . $deleteStmt->error;
            $messageType = "error";
            $deleteStmt->close();
        }
    }
}

// Lấy tuần hiện tại hoặc từ query
$weekOffset = isset($_GET['week']) ? intval($_GET['week']) : 0;
$today = new DateTime();
$today->modify("+{$weekOffset} weeks");

$monday = clone $today;
$dayOfWeek = (int)$monday->format('w');
if ($dayOfWeek == 0) {
    $monday->modify('-6 days');
} else {
    $monday->modify('-' . ($dayOfWeek - 1) . ' days');
}

$sunday = clone $monday;
$sunday->modify('+6 days');

$weekStart = $monday->format('Y-m-d');
$weekEnd = $sunday->format('Y-m-d');
$weekDisplay = $monday->format('d/m') . ' - ' . $sunday->format('d/m/Y');

// Lấy danh sách phòng tập
$phongTapList = [];
$phongTapStmt = $conn->query("SELECT phong_tap_id, ten_phong_tap FROM phongtap ORDER BY phong_tap_id ASC");
if ($phongTapStmt) {
    while ($row = $phongTapStmt->fetch_assoc()) {
        $phongTapList[] = $row;
    }
    $phongTapStmt->close();
}

// Xác định phòng tập hiển thị
$selected_phong_tap_id = null;
if ($vai_tro === 'PT') {
    // PT chỉ xem phòng của mình
    $selected_phong_tap_id = $nhan_vien_phong_tap_id;
} else {
    // Admin có thể chọn phòng hoặc xem phòng đầu tiên
    if (isset($_GET['phong_tap_id'])) {
        $selected_phong_tap_id = intval($_GET['phong_tap_id']);
    } elseif (!empty($phongTapList)) {
        $selected_phong_tap_id = $phongTapList[0]['phong_tap_id'];
    }
}

// Lấy danh sách nhân viên PT theo phòng tập
$trainers = [];
if ($selected_phong_tap_id) {
    $trainerStmt = $conn->prepare("SELECT nhan_vien_id, ho_ten FROM nhanvien WHERE vai_tro = 'PT' AND trang_thai = 'Đang làm' AND phong_tap_id = ? ORDER BY ho_ten");
    $trainerStmt->bind_param("i", $selected_phong_tap_id);
    $trainerStmt->execute();
    $result = $trainerStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $trainers[] = $row;
    }
    $trainerStmt->close();
}

// Lấy lịch tập trong tuần theo phòng tập
$scheduleData = [];
$allTimeSlots = [];

if ($selected_phong_tap_id) {
    $stmt = $conn->prepare("
        SELECT 
            l.lich_tap_id,
            l.ten_lop,
            l.mo_ta,
            l.ngay_tap,
            l.gio_bat_dau,
            l.gio_ket_thuc,
            l.so_luong_toi_da,
            l.phong,
            l.trang_thai,
            l.nhan_vien_pt_id,
            l.phong_tap_id,
            n.ho_ten as ten_huan_luyen_vien,
            COUNT(d.dang_ky_lich_id) as so_luong_da_dang_ky
        FROM LichTap l
        LEFT JOIN nhanvien n ON l.nhan_vien_pt_id = n.nhan_vien_id
        LEFT JOIN DangKyLichTap d ON l.lich_tap_id = d.lich_tap_id AND d.trang_thai = 'Đã đăng ký'
        WHERE l.ngay_tap BETWEEN ? AND ? AND l.phong_tap_id = ?
        GROUP BY l.lich_tap_id
        ORDER BY l.ngay_tap ASC, l.gio_bat_dau ASC
    ");
    $stmt->bind_param("ssi", $weekStart, $weekEnd, $selected_phong_tap_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $ngay_tap = $row['ngay_tap'];
        $dayOfWeek = date('w', strtotime($ngay_tap));
        
        if ($dayOfWeek == 0) {
            $dayOfWeek = 7;
        }
        
        $timeSlot = substr($row['gio_bat_dau'], 0, 5) . ' - ' . substr($row['gio_ket_thuc'], 0, 5);
        $allTimeSlots[$timeSlot] = strtotime($row['gio_bat_dau']);
        
        if (!isset($scheduleData[$timeSlot])) {
            $scheduleData[$timeSlot] = [];
        }
        
        $scheduleData[$timeSlot][$dayOfWeek] = $row;
    }
    $stmt->close();
}

asort($allTimeSlots);
$sortedTimeSlots = array_keys($allTimeSlots);

function formatTime($time) {
    return date('H:i', strtotime($time));
}

function getDayName($day) {
    $days = [
        1 => 'Thứ Hai',
        2 => 'Thứ Ba',
        3 => 'Thứ Tư',
        4 => 'Thứ Năm',
        5 => 'Thứ Sáu',
        6 => 'Thứ Bảy',
        7 => 'Chủ Nhật'
    ];
    return $days[$day] ?? '';
}
?>