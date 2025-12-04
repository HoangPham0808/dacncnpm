<?php
/**
 * Customer Model
 * Chứa các hàm truy vấn database cho quản lý khách hàng
 */

// Include file kết nối database
require_once '../../../Database/db.php';

// Kết nối database
function getDBConnection() {
    global $conn;
    return $conn;
}

/**
 * Lấy danh sách khách hàng với tìm kiếm và lọc
 */
function getCustomers($searchTerm = '', $filterGioiTinh = '') {
    $conn = getDBConnection();
    
    $query = "SELECT kh.* 
              FROM KhachHang kh 
              WHERE 1=1";
    $params = [];
    $types = '';
    
    // Thêm điều kiện tìm kiếm
    if ($searchTerm != '') {
        $query .= " AND (kh.ho_ten LIKE ? OR kh.email LIKE ? OR kh.sdt LIKE ? OR kh.ten_dang_nhap LIKE ? OR kh.cccd LIKE ?)";
        $searchPattern = "%{$searchTerm}%";
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $types .= 'sssss';
    }
    
    // Thêm điều kiện lọc theo giới tính
    if ($filterGioiTinh != '') {
        $query .= " AND kh.gioi_tinh = ?";
        $params[] = $filterGioiTinh;
        $types .= 's';
    }
    
    // Sắp xếp kết quả
    $query .= " ORDER BY kh.ngay_dang_ky DESC, kh.khach_hang_id DESC";
    
    try {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Lấy thông tin khách hàng theo ID
 */
function getCustomerById($id) {
    $conn = getDBConnection();
    
    try {
        $stmt = $conn->prepare("
            SELECT k.*, pt.ten_phong_tap, pt.ma_phong_tap
            FROM KhachHang k 
            LEFT JOIN phongtap pt ON k.phong_tap_id = pt.phong_tap_id
            WHERE k.khach_hang_id = ?
        ");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Lấy thông tin chi tiết khách hàng (bao gồm thông tin tài khoản và phòng tập)
 */
function getCustomerDetailById($id) {
    $conn = getDBConnection();
    
    try {
        $stmt = $conn->prepare("
            SELECT k.*, t.mat_khau, t.loai_tai_khoan, pt.ten_phong_tap, pt.ma_phong_tap
            FROM KhachHang k 
            LEFT JOIN TaiKhoan t ON k.ten_dang_nhap = t.ten_dang_nhap 
            LEFT JOIN phongtap pt ON k.phong_tap_id = pt.phong_tap_id
            WHERE k.khach_hang_id = ?
        ");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Kiểm tra tên đăng nhập đã tồn tại chưa
 */
function checkUsernameExists($username) {
    $conn = getDBConnection();
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM TaiKhoan WHERE ten_dang_nhap = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Kiểm tra email đã tồn tại chưa
 * @param string $email Email cần kiểm tra
 * @param int|null $excludeId ID khách hàng cần loại trừ (dùng khi edit)
 * @return bool True nếu email đã tồn tại, False nếu chưa
 */
function checkEmailExists($email, $excludeId = null) {
    $conn = getDBConnection();
    
    try {
        if ($excludeId !== null) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM KhachHang WHERE email = ? AND khach_hang_id != ?");
            if (!$stmt) {
                throw new Exception('Lỗi prepare: ' . $conn->error);
            }
            $stmt->bind_param('si', $email, $excludeId);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM KhachHang WHERE email = ?");
            if (!$stmt) {
                throw new Exception('Lỗi prepare: ' . $conn->error);
            }
            $stmt->bind_param('s', $email);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Kiểm tra ràng buộc trước khi xóa khách hàng
 */
function checkCustomerConstraints($khach_hang_id) {
    $conn = getDBConnection();
    $constraints = [];
    
    // Kiểm tra Hóa đơn
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM HoaDon WHERE khach_hang_id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare HoaDon: ' . $conn->error);
        }
        $stmt->bind_param('i', $khach_hang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $soHoaDon = $row['count'];
        if ($soHoaDon > 0) {
            $constraints[] = "Khách hàng này có {$soHoaDon} hóa đơn liên quan.";
        }
    } catch (Exception $e) {
        // Bỏ qua nếu bảng không tồn tại hoặc có lỗi
        error_log('Lỗi kiểm tra HoaDon: ' . $e->getMessage());
    }
    
    // Kiểm tra Đăng ký gói tập
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM DangKyGoiTap WHERE khach_hang_id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare DangKyGoiTap: ' . $conn->error);
        }
        $stmt->bind_param('i', $khach_hang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $soDangKyGoiTap = $row['count'];
        if ($soDangKyGoiTap > 0) {
            $constraints[] = "Khách hàng này có {$soDangKyGoiTap} đăng ký gói tập đang hoạt động.";
        }
    } catch (Exception $e) {
        // Bỏ qua nếu bảng không tồn tại hoặc có lỗi
        error_log('Lỗi kiểm tra DangKyGoiTap: ' . $e->getMessage());
    }
    
    // Kiểm tra Lịch sử khuyến mãi - Bỏ qua vì không có bảng LichSuKhuyenMai trong schema
    
    // Kiểm tra Đăng ký lịch tập
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM DangKyLichTap WHERE khach_hang_id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare DangKyLichTap: ' . $conn->error);
        }
        $stmt->bind_param('i', $khach_hang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $soDangKyLichTap = $row['count'];
        if ($soDangKyLichTap > 0) {
            $constraints[] = "Khách hàng này có {$soDangKyLichTap} đăng ký lịch tập.";
        }
    } catch (Exception $e) {
        // Bỏ qua nếu bảng không tồn tại hoặc có lỗi
        error_log('Lỗi kiểm tra DangKyLichTap: ' . $e->getMessage());
    }
    
    // Kiểm tra Lịch sử ra vào
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM LichSuRaVao WHERE khach_hang_id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare LichSuRaVao: ' . $conn->error);
        }
        $stmt->bind_param('i', $khach_hang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $soLichSuRaVao = $row['count'];
        if ($soLichSuRaVao > 0) {
            $constraints[] = "Khách hàng này có {$soLichSuRaVao} lịch sử ra vào.";
        }
    } catch (Exception $e) {
        // Bỏ qua nếu bảng không tồn tại hoặc có lỗi
        error_log('Lỗi kiểm tra LichSuRaVao: ' . $e->getMessage());
    }
    
    // Kiểm tra Đánh giá
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM DanhGia WHERE khach_hang_id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare DanhGia: ' . $conn->error);
        }
        $stmt->bind_param('i', $khach_hang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $soDanhGia = $row['count'];
        if ($soDanhGia > 0) {
            $constraints[] = "Khách hàng này có {$soDanhGia} đánh giá.";
        }
    } catch (Exception $e) {
        // Bỏ qua nếu bảng không tồn tại hoặc có lỗi
        error_log('Lỗi kiểm tra DanhGia: ' . $e->getMessage());
    }
    
    return $constraints;
}

/**
 * Thêm tài khoản vào bảng taikhoan
 */
function addAccount($conn, $tenDangNhap, $matKhau, $loaiTaiKhoan) {
    $query = "INSERT INTO TaiKhoan 
        (ten_dang_nhap, mat_khau, loai_tai_khoan, trang_thai, ngay_tao, ngay_cap_nhat, lan_dang_nhap_cuoi)
        VALUES (?, ?, ?, 'Hoạt động', NOW(), NOW(), NULL)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Lỗi prepare: ' . $conn->error);
    }
    $stmt->bind_param('sss', $tenDangNhap, $matKhau, $loaiTaiKhoan);
    $stmt->execute();
}

/**
 * Thêm khách hàng vào bảng khachhang
 * @param mysqli $conn Kết nối database
 * @param array $data Mảng chứa dữ liệu khách hàng
 * @throws Exception Nếu có lỗi xảy ra
 */
function addCustomer($conn, $data) {
    // Xử lý phong_tap_id có thể NULL
    $phongTapId = !empty($data['phong_tap_id']) && $data['phong_tap_id'] !== '' ? (int)$data['phong_tap_id'] : null;
    
    if ($phongTapId !== null && $phongTapId > 0) {
        $query = "INSERT INTO KhachHang 
            (ten_dang_nhap, ho_ten, email, sdt, cccd, dia_chi, ngay_sinh, gioi_tinh, nguon_gioi_thieu, 
             trang_thai, ghi_chu, phong_tap_id, ngay_dang_ky, ngay_tao, ngay_cap_nhat)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Hoạt động', ?, ?, CURDATE(), NOW(), NOW())";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('ssssssssssi',
            $data['ten_dang_nhap'],
            $data['ho_ten'],
            $data['email'],
            $data['sdt'],
            $data['cccd'],
            $data['dia_chi'],
            $data['ngay_sinh'],
            $data['gioi_tinh'],
            $data['nguon_gioi_thieu'],
            $data['ghi_chu'],
            $phongTapId
        );
    } else {
        $query = "INSERT INTO KhachHang 
            (ten_dang_nhap, ho_ten, email, sdt, cccd, dia_chi, ngay_sinh, gioi_tinh, nguon_gioi_thieu, 
             trang_thai, ghi_chu, phong_tap_id, ngay_dang_ky, ngay_tao, ngay_cap_nhat)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Hoạt động', ?, NULL, CURDATE(), NOW(), NOW())";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('ssssssssss',
            $data['ten_dang_nhap'],
            $data['ho_ten'],
            $data['email'],
            $data['sdt'],
            $data['cccd'],
            $data['dia_chi'],
            $data['ngay_sinh'],
            $data['gioi_tinh'],
            $data['nguon_gioi_thieu'],
            $data['ghi_chu']
        );
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi thực thi: ' . $stmt->error);
    }
}

/**
 * Cập nhật thông tin khách hàng
 * @param mysqli $conn Kết nối database
 * @param int $id ID khách hàng cần cập nhật
 * @param array $data Mảng chứa dữ liệu cần cập nhật
 * @throws Exception Nếu có lỗi xảy ra
 */
function updateCustomer($conn, $id, $data) {
    // Xử lý phong_tap_id có thể NULL
    $phongTapId = !empty($data['phong_tap_id']) && $data['phong_tap_id'] !== '' ? (int)$data['phong_tap_id'] : null;
    
    if ($phongTapId !== null && $phongTapId > 0) {
        $query = "UPDATE KhachHang SET ho_ten = ?, email = ?, sdt = ?, cccd = ?, 
                  dia_chi = ?, ngay_sinh = ?, gioi_tinh = ?, 
                  nguon_gioi_thieu = ?, ghi_chu = ?, phong_tap_id = ?, ngay_cap_nhat = NOW()
                  WHERE khach_hang_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('sssssssssii',
            $data['ho_ten'],
            $data['email'],
            $data['sdt'],
            $data['cccd'],
            $data['dia_chi'],
            $data['ngay_sinh'],
            $data['gioi_tinh'],
            $data['nguon_gioi_thieu'],
            $data['ghi_chu'],
            $phongTapId,
            $id
        );
    } else {
        $query = "UPDATE KhachHang SET ho_ten = ?, email = ?, sdt = ?, cccd = ?, 
                  dia_chi = ?, ngay_sinh = ?, gioi_tinh = ?, 
                  nguon_gioi_thieu = ?, ghi_chu = ?, phong_tap_id = NULL, ngay_cap_nhat = NOW()
                  WHERE khach_hang_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('sssssssssi',
            $data['ho_ten'],
            $data['email'],
            $data['sdt'],
            $data['cccd'],
            $data['dia_chi'],
            $data['ngay_sinh'],
            $data['gioi_tinh'],
            $data['nguon_gioi_thieu'],
            $data['ghi_chu'],
            $id
        );
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi thực thi: ' . $stmt->error);
    }
}

/**
 * Cập nhật mật khẩu
 */
function updatePassword($conn, $tenDangNhap, $matKhauMoi) {
    $query = "UPDATE TaiKhoan SET mat_khau = ?, ngay_cap_nhat = NOW() WHERE ten_dang_nhap = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Lỗi prepare: ' . $conn->error);
    }
    $stmt->bind_param('ss', $matKhauMoi, $tenDangNhap);
    $stmt->execute();
}

/**
 * Lấy danh sách phòng tập cho dropdown
 */
function getAllPhongTap() {
    $conn = getDBConnection();
    
    try {
        $stmt = $conn->query("SELECT phong_tap_id, ma_phong_tap, ten_phong_tap 
                              FROM phongtap 
                              WHERE trang_thai = 'Hoạt động' 
                              ORDER BY ma_phong_tap");
        if (!$stmt) {
            throw new Exception('Lỗi query: ' . $conn->error);
        }
        return $stmt->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Nếu bảng không tồn tại hoặc có lỗi, trả về mảng rỗng
        error_log('Lỗi lấy danh sách phòng tập: ' . $e->getMessage());
        return [];
    }
}

/**
 * Xóa khách hàng
 * @param mysqli $conn Kết nối database
 * @param int $khach_hang_id ID khách hàng cần xóa
 * @param string|null $tenDangNhap Tên đăng nhập của khách hàng (để xóa tài khoản)
 * @throws Exception Nếu có lỗi xảy ra
 */
function deleteCustomer($conn, $khach_hang_id, $tenDangNhap) {
    // Xóa khách hàng từ bảng KhachHang
    $stmt = $conn->prepare("DELETE FROM KhachHang WHERE khach_hang_id = ?");
    if (!$stmt) {
        throw new Exception('Lỗi prepare: ' . $conn->error);
    }
    $stmt->bind_param('i', $khach_hang_id);
    if (!$stmt->execute()) {
        throw new Exception('Lỗi xóa khách hàng: ' . $stmt->error);
    }
    
    // Xóa tài khoản từ bảng TaiKhoan (nếu có)
    // Lưu ý: Nếu có FOREIGN KEY với ON DELETE CASCADE, có thể không cần xóa thủ công
    if ($tenDangNhap) {
        $stmt = $conn->prepare("DELETE FROM TaiKhoan WHERE ten_dang_nhap = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $tenDangNhap);
        if (!$stmt->execute()) {
            throw new Exception('Lỗi xóa tài khoản: ' . $stmt->error);
        }
    }
}
