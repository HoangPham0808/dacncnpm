<?php
/**
 * Promotion Model
 * Chứa các hàm truy vấn database cho quản lý khuyến mại
 */

// Include file kết nối database
require_once '../../../Database/db.php';

// Kết nối database
function getDBConnection() {
    global $conn;
    return $conn;
}

/**
 * Lấy danh sách gói tập để hiển thị trong dropdown
 */
function getAllPackagesForPromotion() {
    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare("SELECT goi_tap_id, ma_goi_tap, ten_goi FROM goitap WHERE trang_thai = 'Đang áp dụng' ORDER BY ma_goi_tap ASC");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn gói tập: ' . $e->getMessage());
    }
}

/**
 * Lấy danh sách khuyến mại với tìm kiếm
 */
function getPromotions($searchTerm = '') {
    $conn = getDBConnection();
    
    $query = "SELECT * FROM khuyenmai WHERE 1=1";
    $params = [];
    $types = '';
    
    // Thêm điều kiện tìm kiếm
    if ($searchTerm != '') {
        $query .= " AND (ma_khuyen_mai LIKE ? OR ten_khuyen_mai LIKE ? OR mo_ta LIKE ?)";
        $searchPattern = "%{$searchTerm}%";
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $types .= 'sss';
    }
    
    // Sắp xếp kết quả
    $query .= " ORDER BY khuyen_mai_id DESC";
    
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
 * Lấy thông tin khuyến mại theo ID
 */
function getPromotionById($id) {
    $conn = getDBConnection();
    
    try {
        $stmt = $conn->prepare("SELECT * FROM khuyenmai WHERE khuyen_mai_id = ?");
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
 * Kiểm tra mã khuyến mại đã tồn tại chưa
 */
function checkPromotionCodeExists($ma_khuyen_mai, $excludeId = null) {
    $conn = getDBConnection();
    
    try {
        if ($excludeId) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM khuyenmai WHERE ma_khuyen_mai = ? AND khuyen_mai_id != ?");
            if (!$stmt) {
                throw new Exception('Lỗi prepare: ' . $conn->error);
            }
            $stmt->bind_param('si', $ma_khuyen_mai, $excludeId);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM khuyenmai WHERE ma_khuyen_mai = ?");
            if (!$stmt) {
                throw new Exception('Lỗi prepare: ' . $conn->error);
            }
            $stmt->bind_param('s', $ma_khuyen_mai);
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
 * Kiểm tra ràng buộc trước khi xóa khuyến mại
 */
function checkPromotionConstraints($khuyen_mai_id) {
    $conn = getDBConnection();
    $constraints = [];
    
    try {
        // Kiểm tra Lịch sử khuyến mại
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM lichsukhuyenmai WHERE khuyen_mai_id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('i', $khuyen_mai_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $soLichSuKhuyenMai = $row['count'];
        if ($soLichSuKhuyenMai > 0) {
            $constraints[] = "Khuyến mại này đã được sử dụng {$soLichSuKhuyenMai} lần trong hóa đơn.";
        }
        
        // Kiểm tra Hóa đơn
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM hoadon WHERE khuyen_mai_id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('i', $khuyen_mai_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $soHoaDon = $row['count'];
        if ($soHoaDon > 0) {
            $constraints[] = "Khuyến mại này đã được áp dụng cho {$soHoaDon} hóa đơn.";
        }
        
        return $constraints;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn: ' . $e->getMessage());
    }
}

/**
 * Thêm khuyến mại mới
 */
function addPromotion($conn, $data) {
    // Xử lý và chuẩn hóa dữ liệu
    $ma = $data['ma_khuyen_mai'];
    $ten = $data['ten_khuyen_mai'];
    $moTa = $data['mo_ta'];
    $loaiGiam = $data['loai_giam'];
    $giaTriGiam = (float) $data['gia_tri_giam'];
    $giamToiDa = !empty($data['giam_toi_da']) ? (float) $data['giam_toi_da'] : null;
    $donToiThieu = !empty($data['gia_tri_don_hang_toi_thieu']) ? (float) $data['gia_tri_don_hang_toi_thieu'] : null;
    $apDungGoi = !empty($data['ap_dung_cho_goi_tap_id']) ? (int) $data['ap_dung_cho_goi_tap_id'] : null;
    $ngayBatDau = $data['ngay_bat_dau'];
    $ngayKetThuc = $data['ngay_ket_thuc'];
    $soLuongMa = !empty($data['so_luong_ma']) ? (int) $data['so_luong_ma'] : null;
    $trangThai = $data['trang_thai'];
    $nhanVienId = !empty($data['nhan_vien_id']) ? (int) $data['nhan_vien_id'] : null;
    
    // Xây dựng query động để xử lý NULL
    $columns = ['ma_khuyen_mai', 'ten_khuyen_mai', 'mo_ta', 'loai_giam', 'gia_tri_giam'];
    $placeholders = ['?', '?', '?', '?', '?'];
    $types = 'ssssd';
    $params = [&$ma, &$ten, &$moTa, &$loaiGiam, &$giaTriGiam];
    
    // Xử lý giam_toi_da
    if ($giamToiDa !== null) {
        $columns[] = 'giam_toi_da';
        $placeholders[] = '?';
        $types .= 'd';
        $params[] = &$giamToiDa;
    } else {
        $columns[] = 'giam_toi_da';
        $placeholders[] = 'NULL';
    }
    
    // Xử lý gia_tri_don_hang_toi_thieu
    if ($donToiThieu !== null) {
        $columns[] = 'gia_tri_don_hang_toi_thieu';
        $placeholders[] = '?';
        $types .= 'd';
        $params[] = &$donToiThieu;
    } else {
        $columns[] = 'gia_tri_don_hang_toi_thieu';
        $placeholders[] = 'NULL';
    }
    
    // Xử lý ap_dung_cho_goi_tap_id
    if ($apDungGoi !== null) {
        $columns[] = 'ap_dung_cho_goi_tap_id';
        $placeholders[] = '?';
        $types .= 'i';
        $params[] = &$apDungGoi;
    } else {
        $columns[] = 'ap_dung_cho_goi_tap_id';
        $placeholders[] = 'NULL';
    }
    
    // Các trường bắt buộc tiếp theo
    $columns[] = 'ngay_bat_dau';
    $columns[] = 'ngay_ket_thuc';
    $placeholders[] = '?';
    $placeholders[] = '?';
    $types .= 'ss';
    $params[] = &$ngayBatDau;
    $params[] = &$ngayKetThuc;
    
    // Xử lý so_luong_ma
    if ($soLuongMa !== null) {
        $columns[] = 'so_luong_ma';
        $placeholders[] = '?';
        $types .= 'i';
        $params[] = &$soLuongMa;
    } else {
        $columns[] = 'so_luong_ma';
        $placeholders[] = 'NULL';
    }
    
    // Các trường cuối
    $columns[] = 'so_luong_da_dung';
    $columns[] = 'trang_thai';
    $placeholders[] = '0';
    $placeholders[] = '?';
    $types .= 's';
    $params[] = &$trangThai;
    
    // Xử lý nhan_vien_tao_id (lưu ID nhân viên tạo khuyến mãi)
    if ($nhanVienId !== null) {
        $columns[] = 'nhan_vien_tao_id';
        $placeholders[] = '?';
        $types .= 'i';
        $params[] = &$nhanVienId;
    } else {
        $columns[] = 'nhan_vien_tao_id';
        $placeholders[] = 'NULL';
    }
    
    $query = "INSERT INTO khuyenmai (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Lỗi prepare: ' . $conn->error);
    }
    
    if (!empty($params)) {
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    }
    
    $stmt->execute();
}

/**
 * Cập nhật khuyến mại
 */
function updatePromotion($conn, $id, $data) {
    // Xử lý và chuẩn hóa dữ liệu
    $ma = $data['ma_khuyen_mai'];
    $ten = $data['ten_khuyen_mai'];
    $moTa = $data['mo_ta'];
    $loaiGiam = $data['loai_giam'];
    $giaTriGiam = (float) $data['gia_tri_giam'];
    $giamToiDa = !empty($data['giam_toi_da']) ? (float) $data['giam_toi_da'] : null;
    $donToiThieu = !empty($data['gia_tri_don_hang_toi_thieu']) ? (float) $data['gia_tri_don_hang_toi_thieu'] : null;
    $apDungGoi = !empty($data['ap_dung_cho_goi_tap_id']) ? (int) $data['ap_dung_cho_goi_tap_id'] : null;
    $ngayBatDau = $data['ngay_bat_dau'];
    $ngayKetThuc = $data['ngay_ket_thuc'];
    $soLuongMa = !empty($data['so_luong_ma']) ? (int) $data['so_luong_ma'] : null;
    $trangThai = $data['trang_thai'];
    
    // Xây dựng SET clauses động để xử lý NULL
    $setClauses = [];
    $types = '';
    $params = [];
    
    // Các trường bắt buộc (không thể NULL)
    $setClauses[] = 'ma_khuyen_mai = ?'; $types .= 's'; $params[] = &$ma;
    $setClauses[] = 'ten_khuyen_mai = ?'; $types .= 's'; $params[] = &$ten;
    $setClauses[] = 'mo_ta = ?'; $types .= 's'; $params[] = &$moTa;
    $setClauses[] = 'loai_giam = ?'; $types .= 's'; $params[] = &$loaiGiam;
    $setClauses[] = 'gia_tri_giam = ?'; $types .= 'd'; $params[] = &$giaTriGiam;
    $setClauses[] = 'ngay_bat_dau = ?'; $types .= 's'; $params[] = &$ngayBatDau;
    $setClauses[] = 'ngay_ket_thuc = ?'; $types .= 's'; $params[] = &$ngayKetThuc;
    $setClauses[] = 'trang_thai = ?'; $types .= 's'; $params[] = &$trangThai;
    
    // Xử lý các trường có thể NULL
    if ($giamToiDa !== null) {
        $setClauses[] = 'giam_toi_da = ?';
        $types .= 'd';
        $params[] = &$giamToiDa;
    } else {
        $setClauses[] = 'giam_toi_da = NULL';
    }
    
    if ($donToiThieu !== null) {
        $setClauses[] = 'gia_tri_don_hang_toi_thieu = ?';
        $types .= 'd';
        $params[] = &$donToiThieu;
    } else {
        $setClauses[] = 'gia_tri_don_hang_toi_thieu = NULL';
    }
    
    if ($apDungGoi !== null) {
        $setClauses[] = 'ap_dung_cho_goi_tap_id = ?';
        $types .= 'i';
        $params[] = &$apDungGoi;
    } else {
        $setClauses[] = 'ap_dung_cho_goi_tap_id = NULL';
    }
    
    if ($soLuongMa !== null) {
        $setClauses[] = 'so_luong_ma = ?';
        $types .= 'i';
        $params[] = &$soLuongMa;
    } else {
        $setClauses[] = 'so_luong_ma = NULL';
    }
    
    // Thêm WHERE clause
    $types .= 'i';
    $params[] = &$id;
    
    $query = "UPDATE khuyenmai SET " . implode(', ', $setClauses) . " WHERE khuyen_mai_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Lỗi prepare: ' . $conn->error);
    }
    
    if (!empty($params)) {
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    }
    
    $stmt->execute();
}

/**
 * Xóa khuyến mại
 */
function deletePromotion($conn, $khuyen_mai_id) {
    $stmt = $conn->prepare("DELETE FROM khuyenmai WHERE khuyen_mai_id = ?");
    if (!$stmt) {
        throw new Exception('Lỗi prepare: ' . $conn->error);
    }
    $stmt->bind_param('i', $khuyen_mai_id);
    $stmt->execute();
}
