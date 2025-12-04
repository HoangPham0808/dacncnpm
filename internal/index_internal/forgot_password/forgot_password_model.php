<?php
/**
 * Forgot Password Model
 * Chứa các hàm truy vấn database cho quên mật khẩu
 */

require_once '../../../Database/db.php';

function getDBConnection() {
    global $conn;
    return $conn;
}

/**
 * Lấy thông tin user theo username (chỉ cho nhân viên)
 */
function getUserByUsername($username) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("
            SELECT t.ten_dang_nhap, t.trang_thai, n.vai_tro, n.email, n.sdt, n.ho_ten
            FROM taikhoan t
            LEFT JOIN nhanvien n ON t.ten_dang_nhap = n.ten_dang_nhap
            WHERE t.ten_dang_nhap = ? AND t.loai_tai_khoan = 'Nhân viên'
        ");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn thông tin user: ' . $e->getMessage());
    }
}

/**
 * Cập nhật mật khẩu mới
 */
function updatePassword($username, $newPassword) {
    $conn = getDBConnection();
    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE taikhoan SET mat_khau = ?, ngay_cap_nhat = NOW() WHERE ten_dang_nhap = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('ss', $hashedPassword, $username);
        return $stmt->execute();
    } catch (Exception $e) {
        throw new Exception('Lỗi cập nhật mật khẩu: ' . $e->getMessage());
    }
}
