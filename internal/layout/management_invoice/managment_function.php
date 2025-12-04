<?php
// Tắt hiển thị lỗi để tránh output HTML trước JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
session_start();

// Set header JSON ngay từ đầu
header('Content-Type: application/json; charset=utf-8');

// ================== KẾT NỐI DATABASE ==================
require_once __DIR__ . '/../../../Database/db.php';

// Import InvoiceHandler
require_once __DIR__ . '/invoice_handler.php';

// Sử dụng kết nối từ db.php
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Kết nối thất bại: ' . $conn->connect_error]);
    exit;
}

// ================== KIỂM TRA PHÂN QUYỀN ==================
// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Lấy thông tin người dùng hiện tại
$current_nhan_vien_id = $_SESSION['nhan_vien_id'] ?? null;
$current_vai_tro = $_SESSION['vai_tro'] ?? null;
$current_phong_tap_id = null;

// Lấy phòng tập của nhân viên hiện tại (nếu không phải Admin)
if ($current_nhan_vien_id && $current_vai_tro !== 'Admin') {
    $stmt = $conn->prepare("SELECT phong_tap_id FROM NhanVien WHERE nhan_vien_id = ?");
    $stmt->bind_param("i", $current_nhan_vien_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $current_phong_tap_id = $row['phong_tap_id'];
    }
    $stmt->close();
}

// ================== XỬ LÝ YÊU CẦU ==================

// Kiểm tra action
if (!isset($_GET['action']) && !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Không có action hợp lệ']);
    exit;
}

// Lấy action (Ưu tiên POST)
$action = $_POST['action'] ?? $_GET['action'];

// ================== LẤY DANH SÁCH KHÁCH HÀNG ==================
if ($action === 'getCustomers') {
    if ($current_vai_tro === 'Admin') {
        $sql = "SELECT kh.khach_hang_id, kh.ho_ten, kh.phong_tap_id, pt.ten_phong_tap 
                FROM khachhang kh 
                LEFT JOIN phongtap pt ON kh.phong_tap_id = pt.phong_tap_id 
                ORDER BY kh.ho_ten";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT kh.khach_hang_id, kh.ho_ten, kh.phong_tap_id, pt.ten_phong_tap 
                FROM khachhang kh 
                LEFT JOIN phongtap pt ON kh.phong_tap_id = pt.phong_tap_id 
                WHERE kh.phong_tap_id = ? 
                ORDER BY kh.ho_ten";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $current_phong_tap_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
    $conn->close();
    exit;
}

// ================== LẤY DANH SÁCH GÓI TẬP ==================
if ($action === 'getpackage') {
    $sql = "SELECT goi_tap_id, ten_goi FROM goitap ORDER BY ten_goi";
    $result = $conn->query($sql);
    
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }  
    echo json_encode(['success' => true, 'data' => $data]);
    $conn->close();
    exit;
}

// ================== LẤY DANH SÁCH NHÂN VIÊN ==================
if ($action === 'getstaff') {
    if ($current_vai_tro === 'Admin') {
        $sql = "SELECT nv.nhan_vien_id, nv.ho_ten, nv.phong_tap_id, pt.ten_phong_tap 
                FROM nhanvien nv 
                LEFT JOIN phongtap pt ON nv.phong_tap_id = pt.phong_tap_id 
                ORDER BY nv.nhan_vien_id";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT nv.nhan_vien_id, nv.ho_ten, nv.phong_tap_id, pt.ten_phong_tap 
                FROM nhanvien nv 
                LEFT JOIN phongtap pt ON nv.phong_tap_id = pt.phong_tap_id 
                WHERE nv.phong_tap_id = ? 
                ORDER BY nv.nhan_vien_id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $current_phong_tap_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }  
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
    $conn->close();
    exit;
}

// ================== LẤY NHÂN VIÊN THEO PHÒNG TẬP CỦA KHÁCH HÀNG ==================
if ($action === 'getStaffByCustomer' && isset($_GET['khach_hang_id'])) {
    $khach_hang_id = intval($_GET['khach_hang_id']);
    
    $stmt = $conn->prepare("SELECT phong_tap_id FROM khachhang WHERE khach_hang_id = ?");
    $stmt->bind_param("i", $khach_hang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $phong_tap_id = null;
    
    if ($row = $result->fetch_assoc()) {
        $phong_tap_id = $row['phong_tap_id'];
    }
    $stmt->close();
    
    if ($phong_tap_id) {
        $stmt = $conn->prepare("SELECT nhan_vien_id, ho_ten, phong_tap_id FROM nhanvien WHERE phong_tap_id = ? ORDER BY ho_ten");
        $stmt->bind_param("i", $phong_tap_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Khách hàng chưa được gán phòng tập']);
    }
    
    $conn->close();
    exit;
}

// ================== LẤY KHÁCH HÀNG THEO PHÒNG TẬP CỦA NHÂN VIÊN ==================
if ($action === 'getCustomersByStaff' && isset($_GET['nhan_vien_id'])) {
    $nhan_vien_id = intval($_GET['nhan_vien_id']);
    
    $stmt = $conn->prepare("SELECT phong_tap_id FROM nhanvien WHERE nhan_vien_id = ?");
    $stmt->bind_param("i", $nhan_vien_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $phong_tap_id = null;
    
    if ($row = $result->fetch_assoc()) {
        $phong_tap_id = $row['phong_tap_id'];
    }
    $stmt->close();
    
    if ($phong_tap_id) {
        $stmt = $conn->prepare("SELECT khach_hang_id, ho_ten, phong_tap_id FROM khachhang WHERE phong_tap_id = ? ORDER BY ho_ten");
        $stmt->bind_param("i", $phong_tap_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Nhân viên chưa được gán phòng tập']);
    }
    
    $conn->close();
    exit;
}

// ================== LẤY DANH SÁCH KHUYẾN MÃI ==================
if ($action === 'getPromotions') {
    $sql = "SELECT khuyen_mai_id, ten_khuyen_mai, gia_tri_giam
            FROM KhuyenMai
            WHERE trang_thai = 'Đang áp dụng'
              AND (ngay_bat_dau IS NULL OR ngay_bat_dau <= CURDATE())
              AND (ngay_ket_thuc IS NULL OR ngay_ket_thuc >= CURDATE())
            ORDER BY ten_khuyen_mai";
    
    $result = $conn->query($sql);
    
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!isset($row['gia_tri_giam'])) {
                $row['gia_tri_giam'] = 0;
            }
            $data[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $conn->close();
    exit;
}

// ================== CHI TIẾT HÓA ĐƠN ==================
if ($action === 'getInvoiceDetail' && isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID hóa đơn không hợp lệ']);
            $conn->close();
            exit;
        }

        $sql = "SELECT hd.*, 
                   kh.ho_ten AS ten_khach_hang,
                   kh.email AS email_khach_hang,
                   kh.sdt AS sdt_khach_hang,
                   kh.phong_tap_id AS khach_hang_phong_tap_id,
                   nv.ho_ten AS ten_nhan_vien,
                   nv.phong_tap_id AS nhan_vien_phong_tap_id,
                   km.ten_khuyen_mai,
                   ct.goi_tap_id
            FROM HoaDon hd
            LEFT JOIN khachhang kh ON hd.khach_hang_id = kh.khach_hang_id
            LEFT JOIN NhanVien nv ON hd.nhan_vien_lap_id = nv.nhan_vien_id
            LEFT JOIN KhuyenMai km ON hd.khuyen_mai_id = km.khuyen_mai_id
            LEFT JOIN ChiTietHoaDon ct ON hd.hoa_don_id = ct.hoa_don_id
            WHERE hd.hoa_don_id = ?";
        
        if ($current_vai_tro !== 'Admin') {
            $sql .= " AND kh.phong_tap_id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Lỗi prepare statement: ' . $conn->error]);
            $conn->close();
            exit;
        }
        
        if ($current_vai_tro !== 'Admin') {
            $stmt->bind_param("ii", $id, $current_phong_tap_id);
        } else {
            $stmt->bind_param("i", $id);
        }
        
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Lỗi execute: ' . $stmt->error]);
            $stmt->close();
            $conn->close();
            exit;
        }
        
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $invoice = $result->fetch_assoc();
            foreach ($invoice as $key => $value) {
                if ($value === null) {
                    $invoice[$key] = '';
                }
            }
            $invoice['nhan_vien_id'] = $invoice['nhan_vien_lap_id'];
            echo json_encode(['success' => true, 'data' => $invoice], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy hóa đơn hoặc bạn không có quyền xem hóa đơn này']);
        }
        
        $stmt->close();
        $conn->close();
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        if (isset($conn)) $conn->close();
        exit;
    }
}

// ================== THÊM HÓA ĐƠN ==================
if ($action === 'addInvoice') {
    $handler = new InvoiceHandler($conn, $current_nhan_vien_id, $current_vai_tro, $current_phong_tap_id);
    $result = $handler->addInvoice($_POST);
    echo json_encode($result);
    $conn->close();
    exit;
}

// ================== CẬP NHẬT HÓA ĐƠN ==================
if ($action === 'updateInvoice' && isset($_POST['hoa_don_id'])) {
    $handler = new InvoiceHandler($conn, $current_nhan_vien_id, $current_vai_tro, $current_phong_tap_id);
    $hoa_don_id = intval($_POST['hoa_don_id']);
    $result = $handler->updateInvoice($hoa_don_id, $_POST);
    echo json_encode($result);
    $conn->close();
    exit;
}

// ================== XÓA HÓA ĐƠN ==================
if ($action === 'delete' && isset($_GET['id'])) {
    $conn->begin_transaction();
    
    try {
        $id = intval($_GET['id']);
        
        // Kiểm tra quyền xóa
        if ($current_vai_tro !== 'Admin') {
            // Lễ Tân chỉ xóa hóa đơn của khách hàng cùng phòng
            $stmt = $conn->prepare("SELECT kh.phong_tap_id FROM HoaDon hd 
                                    LEFT JOIN khachhang kh ON hd.khach_hang_id = kh.khach_hang_id 
                                    WHERE hd.hoa_don_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if (!$row || $row['phong_tap_id'] !== $current_phong_tap_id) {
                throw new Exception('Bạn không có quyền xóa hóa đơn này!');
            }
        }
        
        // Xóa đăng ký gói tập trước (nếu có)
        $sqlDangKy = "DELETE FROM DangKyGoiTap WHERE hoa_don_id = ?";
        $stmt = $conn->prepare($sqlDangKy);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Xóa chi tiết hóa đơn
        $sqlDetail = "DELETE FROM ChiTietHoaDon WHERE hoa_don_id = ?";
        $stmt = $conn->prepare($sqlDetail);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Xóa hóa đơn
        $sql = "DELETE FROM HoaDon WHERE hoa_don_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception('Lỗi xóa hóa đơn: ' . $stmt->error);
        }
        
        $stmt->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Xóa hóa đơn thành công']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $conn->close();
    exit;
}

if ($action === 'exportExcel') {
    try {
        // Tạo câu truy vấn
         // Lấy thông tin gói tập từ ChiTietHoaDon (HoaDon không chứa goi_tap_id trực tiếp)
         $sql = "SELECT hd.ma_hoa_don,
                 kh.ho_ten AS ten_khach_hang,
                 hd.ngay_lap,
                 hd.tong_tien,
                 hd.giam_gia_khuyen_mai,
                 hd.giam_gia_khac,
                 hd.tien_thanh_toan,
                 hd.phuong_thuc_thanh_toan,
                 hd.trang_thai,
                 gt.ten_goi AS ten_goi_tap,
                 nv.ho_ten AS ten_nhan_vien,
                 km.ten_khuyen_mai
             FROM HoaDon hd
             LEFT JOIN KhachHang kh ON hd.khach_hang_id = kh.khach_hang_id
             LEFT JOIN ChiTietHoaDon ct ON hd.hoa_don_id = ct.hoa_don_id
             LEFT JOIN GoiTap gt ON ct.goi_tap_id = gt.goi_tap_id
             LEFT JOIN NhanVien nv ON hd.nhan_vien_lap_id = nv.nhan_vien_id
             LEFT JOIN KhuyenMai km ON hd.khuyen_mai_id = km.khuyen_mai_id
             WHERE 1=1";
        
        // Lễ Tân chỉ xuất hóa đơn của phòng mình
        if ($current_vai_tro !== 'Admin') {
            $sql .= " AND kh.phong_tap_id = ?";
        }
        
        $conditions = [];
        $types = "";
        $params = [];
        
        if ($current_vai_tro !== 'Admin') {
            $params[] = $current_phong_tap_id;
            $types .= "i";
        }

        // Áp dụng bộ lọc từ GET parameters
        if (!empty($_GET['search'])) {
            $conditions[] = "(hd.ma_hoa_don LIKE ? OR kh.ho_ten LIKE ?)";
            $searchParam = '%' . $_GET['search'] . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        if (!empty($_GET['status'])) {
            $conditions[] = "hd.trang_thai = ?";
            $params[] = $_GET['status'];
            $types .= "s";
        }

        if (!empty($_GET['date'])) {
            $conditions[] = "DATE(hd.ngay_lap) = ?";
            $params[] = $_GET['date'];
            $types .= "s";
        }

        // Thêm điều kiện vào SQL
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY hd.ngay_lap DESC, hd.hoa_don_id DESC";
        
        // Thực thi truy vấn
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Lỗi prepare statement: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi execute: " . $stmt->error);
        }
        
        $result = $stmt->get_result();

        // Thiết lập header cho file CSV
        $filename = 'hoa_don_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Mở output stream
        $output = fopen('php://output', 'w');
        
        // Thêm UTF-8 BOM để Excel hiển thị đúng tiếng Việt
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header của file CSV
        fputcsv($output, [
            'Mã hóa đơn',
            'Khách hàng',
            'Ngày lập',
            'Gói tập',
            'Nhân viên',
            'Khuyến mãi',
            'Tổng tiền (VNĐ)',
            'Giảm giá KM (VNĐ)',
            'Giảm giá khác (VNĐ)',
            'Thanh toán (VNĐ)',
            'PT thanh toán',
            'Trạng thái'
        ]);
        
        // Kiểm tra có dữ liệu không
        if ($result->num_rows === 0) {
            fputcsv($output, ['Không có dữ liệu phù hợp với bộ lọc']);
        } else {
            // Xuất dữ liệu
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['ma_hoa_don'] ?? '',
                    $row['ten_khach_hang'] ?? 'N/A',
                    !empty($row['ngay_lap']) ? date('d/m/Y', strtotime($row['ngay_lap'])) : '',
                    $row['ten_goi_tap'] ?? 'N/A',
                    $row['ten_nhan_vien'] ?? 'N/A',
                    $row['ten_khuyen_mai'] ?? 'Không áp dụng',
                    number_format($row['tong_tien'] ?? 0, 0, ',', '.'),
                    number_format($row['giam_gia_khuyen_mai'] ?? 0, 0, ',', '.'),
                    number_format($row['giam_gia_khac'] ?? 0, 0, ',', '.'),
                    number_format($row['tien_thanh_toan'] ?? 0, 0, ',', '.'),
                    $row['phuong_thuc_thanh_toan'] ?? '',
                    $row['trang_thai'] ?? ''
                ]);
            }
        }
        
        fclose($output);
        $stmt->close();
        $conn->close();
        exit;
        
    } catch (Exception $e) {
        // Log lỗi
        error_log("Lỗi xuất Excel: " . $e->getMessage());
        
        // Trả về JSON error nếu chưa output
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi xuất file: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// ================== XUẤT 1 HÓA ĐƠN (CSV) ==================
if ($action === 'exportSingleInvoice' && isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);
        if ($id <= 0) {
            throw new Exception('ID hóa đơn không hợp lệ');
        }

        // Lấy thông tin hóa đơn
        $sql = "SELECT hd.*, kh.ho_ten AS ten_khach_hang, kh.email AS email_khach_hang, kh.sdt AS sdt_khach_hang, kh.phong_tap_id AS khach_hang_phong_tap_id, nv.ho_ten AS ten_nhan_vien, km.ten_khuyen_mai
                FROM HoaDon hd
                LEFT JOIN KhachHang kh ON hd.khach_hang_id = kh.khach_hang_id
                LEFT JOIN NhanVien nv ON hd.nhan_vien_lap_id = nv.nhan_vien_id
                LEFT JOIN KhuyenMai km ON hd.khuyen_mai_id = km.khuyen_mai_id
                WHERE hd.hoa_don_id = ?";

        if ($current_vai_tro !== 'Admin') {
            $sql .= " AND kh.phong_tap_id = ?";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception('Lỗi prepare: ' . $conn->error);

        if ($current_vai_tro !== 'Admin') {
            $stmt->bind_param('ii', $id, $current_phong_tap_id);
        } else {
            $stmt->bind_param('i', $id);
        }

        if (!$stmt->execute()) throw new Exception('Lỗi execute: ' . $stmt->error);
        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) throw new Exception('Không tìm thấy hóa đơn hoặc bạn không có quyền xem');

        $invoice = $result->fetch_assoc();
        $stmt->close();

        // Lấy chi tiết
        $sqlItems = "SELECT ct.goi_tap_id, gt.ten_goi, ct.so_luong, ct.don_gia, ct.thanh_tien
                     FROM ChiTietHoaDon ct
                     LEFT JOIN GoiTap gt ON ct.goi_tap_id = gt.goi_tap_id
                     WHERE ct.hoa_don_id = ?";
        $stmt = $conn->prepare($sqlItems);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $itemsResult = $stmt->get_result();

        // Ghi CSV
        $filename = 'hoa_don_' . ($invoice['ma_hoa_don'] ?? $id) . '_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // BOM
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Thông tin hóa đơn (metadata)
        fputcsv($output, ['Mã hóa đơn', $invoice['ma_hoa_don'] ?? '']);
        fputcsv($output, ['Ngày lập', !empty($invoice['ngay_lap']) ? date('d/m/Y', strtotime($invoice['ngay_lap'])) : '']);
        fputcsv($output, ['Khách hàng', $invoice['ten_khach_hang'] ?? '']);
        fputcsv($output, ['Email', $invoice['email_khach_hang'] ?? '']);
        fputcsv($output, ['SĐT', $invoice['sdt_khach_hang'] ?? '']);
        fputcsv($output, ['Nhân viên lập', $invoice['ten_nhan_vien'] ?? '']);
        fputcsv($output, ['Khuyến mãi', $invoice['ten_khuyen_mai'] ?? '']);
        fputcsv($output, []);

        // Header chi tiết
        fputcsv($output, ['Gói tập', 'Số lượng', 'Đơn giá (VNĐ)', 'Thành tiền (VNĐ)']);
        if ($itemsResult && $itemsResult->num_rows > 0) {
            while ($row = $itemsResult->fetch_assoc()) {
                fputcsv($output, [
                    $row['ten_goi'] ?? 'N/A',
                    $row['so_luong'] ?? 1,
                    number_format($row['don_gia'] ?? 0, 0, ',', '.'),
                    number_format($row['thanh_tien'] ?? 0, 0, ',', '.')
                ]);
            }
        } else {
            fputcsv($output, ['Không có mục chi tiết']);
        }

        fputcsv($output, []);
        fputcsv($output, ['Tổng tiền', number_format($invoice['tong_tien'] ?? 0, 0, ',', '.')]);
        fputcsv($output, ['Giảm giá KM', number_format($invoice['giam_gia_khuyen_mai'] ?? 0, 0, ',', '.')]);
        fputcsv($output, ['Giảm giá khác', number_format($invoice['giam_gia_khac'] ?? 0, 0, ',', '.')]);
        fputcsv($output, ['Tiền thanh toán', number_format($invoice['tien_thanh_toan'] ?? 0, 0, ',', '.')]);

        fclose($output);
        $stmt->close();
        $conn->close();
        exit;
    } catch (Exception $e) {
        error_log('Lỗi exportSingleInvoice: ' . $e->getMessage());
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } else {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
// ================== TÍNH TOÁN HÓA ĐƠN (KHÔNG LƯU) ==================
// Thêm đoạn code này vào managment_function.php, trước phần "MẶC ĐỊNH: ACTION KHÔNG HỢP LỆ"

if ($action === 'calculateInvoice') {
    try {
        $goi_tap_id = intval($_POST['goi_tap_id']);
        $khuyen_mai_id = !empty($_POST['khuyen_mai_id']) ? intval($_POST['khuyen_mai_id']) : null;
        $giam_gia_khac = floatval($_POST['giam_gia_khac'] ?? 0);
        
        // Lấy thông tin gói tập
        $sql = "SELECT gia_tien, ten_goi FROM GoiTap WHERE goi_tap_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $goi_tap_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy gói tập']);
            $stmt->close();
            $conn->close();
            exit;
        }
        
        $package = $result->fetch_assoc();
        $stmt->close();
        
        $gia_tien = floatval($package['gia_tien']);
        $ten_goi = $package['ten_goi'];
        
        // Tính giảm giá khuyến mãi
        $giam_gia_km = 0;
        if ($khuyen_mai_id) {
            $sql = "SELECT gia_tri_giam FROM KhuyenMai 
                    WHERE khuyen_mai_id = ? AND trang_thai = 'Đang áp dụng'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $khuyen_mai_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $gia_tri_giam = floatval($result->fetch_assoc()['gia_tri_giam']);
                $giam_gia_km = $gia_tien * $gia_tri_giam / 100;
            }
            $stmt->close();
        }
        
        // Tính tổng tiền thanh toán
        $tong_tien = $gia_tien;
        $tien_thanh_toan = max(0, $tong_tien - $giam_gia_km - $giam_gia_khac);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'tong_tien' => $tong_tien,
                'giam_gia_km' => $giam_gia_km,
                'giam_gia_khac' => $giam_gia_khac,
                'tien_thanh_toan' => $tien_thanh_toan,
                'ten_goi' => $ten_goi
            ]
        ]);
        
        $conn->close();
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi tính toán: ' . $e->getMessage()]);
        $conn->close();
        exit;
    }
}
// ================== MẶC ĐỊNH: ACTION KHÔNG HỢP LỆ ==================
echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
$conn->close();
exit;
?>