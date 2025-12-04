<?php
/**
 * Overview Model
 * Chứa các hàm truy vấn database cho trang tổng quan
 * Đã được cập nhật để phù hợp với schema database mới
 */

// Include file kết nối database
require_once '../../../Database/db.php';

// Kết nối database
function getDBConnection() {
    global $conn;
    return $conn;
}

/**
 * Lấy tổng số khách hàng đang hoạt động
 */
function getTotalActiveCustomers() {
    $conn = getDBConnection();
    
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM KhachHang WHERE trang_thai = 'Hoạt động'");
        if (!$result) {
            throw new Exception('Lỗi query: ' . $conn->error);
        }
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Lấy tổng số nhân viên đang làm việc
 */
function getTotalActiveEmployees() {
    $conn = getDBConnection();
    
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM NhanVien WHERE trang_thai = 'Đang làm'");
        if (!$result) {
            throw new Exception('Lỗi query: ' . $conn->error);
        }
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Lấy doanh thu tháng hiện tại
 */
function getMonthlyRevenue($thang, $nam) {
    $conn = getDBConnection();
    
    try {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(tien_thanh_toan), 0) as total 
                               FROM HoaDon 
                               WHERE MONTH(ngay_lap) = ? AND YEAR(ngay_lap) = ? 
                               AND trang_thai = 'Đã thanh toán'");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('ii', $thang, $nam);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Lấy doanh thu hôm nay
 */
function getTodayRevenue($ngay) {
    $conn = getDBConnection();
    
    try {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(tien_thanh_toan), 0) as total 
                               FROM HoaDon 
                               WHERE DATE(ngay_lap) = ? 
                               AND trang_thai = 'Đã thanh toán'");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $ngay);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Lấy số hóa đơn chờ thanh toán
 */
function getPendingInvoices() {
    $conn = getDBConnection();
    
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM HoaDon WHERE trang_thai = 'Chờ thanh toán'");
        if (!$result) {
            throw new Exception('Lỗi query: ' . $conn->error);
        }
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Lấy số khách hàng đã check-in hôm nay
 * Sử dụng bảng LichSuRaVao với cột khach_hang_id theo schema mới
 */
function getTodayCheckIns($ngay) {
    $conn = getDBConnection();
    
    try {
        // Kiểm tra xem bảng LichSuRaVao có tồn tại không
        $checkTable = $conn->query("SHOW TABLES LIKE 'LichSuRaVao'");
        if ($checkTable->num_rows == 0) {
            return 0;
        }
        
        // Kiểm tra xem bảng có cột khach_hang_id không
        $checkColumn = $conn->query("SHOW COLUMNS FROM LichSuRaVao LIKE 'khach_hang_id'");
        if ($checkColumn->num_rows > 0) {
            // Schema mới: sử dụng khach_hang_id
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT khach_hang_id) as total 
                                   FROM LichSuRaVao 
                                   WHERE DATE(thoi_gian_vao) = ? 
                                   AND khach_hang_id IS NOT NULL");
        } else {
            // Schema cũ: có thể có ten_dang_nhap
            $checkColumnTenDN = $conn->query("SHOW COLUMNS FROM LichSuRaVao LIKE 'ten_dang_nhap'");
            if ($checkColumnTenDN->num_rows > 0) {
                $stmt = $conn->prepare("SELECT COUNT(DISTINCT ten_dang_nhap) as total 
                                       FROM LichSuRaVao 
                                       WHERE DATE(thoi_gian_vao) = ? 
                                       AND ten_dang_nhap IS NOT NULL");
            } else {
                return 0;
            }
        }
        
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $ngay);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Lấy danh sách hóa đơn gần đây
 */
function getRecentInvoices($limit = 5) {
    $conn = getDBConnection();
    
    try {
        $stmt = $conn->prepare("SELECT hd.hoa_don_id, hd.ma_hoa_don, hd.ngay_lap, hd.tien_thanh_toan, hd.trang_thai,
                                    kh.ho_ten as ten_khach_hang
                             FROM HoaDon hd
                             LEFT JOIN KhachHang kh ON hd.khach_hang_id = kh.khach_hang_id
                             ORDER BY hd.ngay_lap DESC, hd.hoa_don_id DESC
                             LIMIT ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}
