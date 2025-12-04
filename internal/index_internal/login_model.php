<?php
/**
 * Login Model
 * Chứa các hàm truy vấn database cho đăng nhập
 */

require_once '../../Database/db.php';

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
        $stmt = $conn->prepare("SELECT ten_dang_nhap, mat_khau, loai_tai_khoan, trang_thai FROM TaiKhoan WHERE ten_dang_nhap = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn tài khoản: ' . $e->getMessage());
    }
}

/**
 * Lấy thông tin nhân viên theo username
 */
function getEmployeeByUsername($username) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("SELECT nhan_vien_id, ho_ten, vai_tro FROM NhanVien WHERE ten_dang_nhap = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn nhân viên: ' . $e->getMessage());
    }
}

/**
 * Cập nhật lần đăng nhập cuối
 */
function updateLastLogin($username) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("UPDATE TaiKhoan SET lan_dang_nhap_cuoi = NOW() WHERE ten_dang_nhap = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $username);
        return $stmt->execute();
    } catch (Exception $e) {
        throw new Exception('Lỗi cập nhật lần đăng nhập: ' . $e->getMessage());
    }
}

/**
 * Lưu lịch sử ra vào khi nhân viên đăng nhập
 */
function insertLichSuRaVao($ten_dang_nhap, $loai_tai_khoan) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("INSERT INTO LichSuRaVao (ten_dang_nhap, loai_tai_khoan, thoi_gian_vao) VALUES (?, ?, NOW())");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('ss', $ten_dang_nhap, $loai_tai_khoan);
        return $stmt->execute();
    } catch (Exception $e) {
        throw new Exception('Lỗi lưu lịch sử ra vào: ' . $e->getMessage());
    }
}

/**
 * Kiểm tra mật khẩu (hỗ trợ cả hash và plain text)
 * Vì admin trong SQL có thể là plain text 'admin123' hoặc đã hash
 */
function verifyPassword($password, $storedPassword) {
    // Kiểm tra nếu là password hash (bắt đầu bằng $2y$ hoặc $2a$ hoặc $2b$)
    if (preg_match('/^\$2[ayb]\$/', $storedPassword)) {
        return password_verify($password, $storedPassword);
    }
    // Kiểm tra plain text (cho trường hợp admin trong SQL là plain text)
    return $storedPassword === $password;
}
