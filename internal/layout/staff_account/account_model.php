<?php
/**
 * Account Model
 * Chứa các hàm truy vấn database cho quản lý tài khoản cá nhân nhân viên
 */

require_once '../../../Database/db.php';

function getDBConnection() {
    global $conn;
    return $conn;
}

/**
 * Lấy thông tin tài khoản theo username
 */
function getAccountByUsername($username) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("
            SELECT t.ten_dang_nhap, t.loai_tai_khoan, t.trang_thai, t.ngay_tao, t.ngay_cap_nhat, t.lan_dang_nhap_cuoi,
                   n.ho_ten, n.vai_tro
            FROM taikhoan t
            LEFT JOIN nhanvien n ON t.ten_dang_nhap = n.ten_dang_nhap
            WHERE t.ten_dang_nhap = ?
        ");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn thông tin tài khoản: ' . $e->getMessage());
    }
}

/**
 * Kiểm tra mật khẩu hiện tại
 */
function verifyCurrentPassword($username, $password) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("SELECT mat_khau FROM taikhoan WHERE ten_dang_nhap = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            return false;
        }
        
        // Kiểm tra mật khẩu đã hash hoặc plain text
        if (password_verify($password, $user['mat_khau'])) {
            return true;
        } elseif ($user['mat_khau'] === $password) {
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        throw new Exception('Lỗi kiểm tra mật khẩu: ' . $e->getMessage());
    }
}

/**
 * Cập nhật mật khẩu
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
