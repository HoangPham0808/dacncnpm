<?php
/**
 * Profile Model
 * Chứa các hàm truy vấn database cho quản lý thông tin cá nhân nhân viên
 */

require_once '../../../Database/db.php';

function getDBConnection() {
    global $conn;
    return $conn;
}

/**
 * Lấy thông tin nhân viên theo username và vai trò
 */
function getEmployeeByUsername($username, $vai_tro) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("
            SELECT n.*, t.ten_dang_nhap, t.lan_dang_nhap_cuoi
            FROM nhanvien n
            INNER JOIN taikhoan t ON n.ten_dang_nhap = t.ten_dang_nhap
            WHERE n.ten_dang_nhap = ? AND n.vai_tro = ?
        ");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('ss', $username, $vai_tro);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn thông tin nhân viên: ' . $e->getMessage());
    }
}

/**
 * Cập nhật thông tin nhân viên
 */
function updateEmployeeProfile($username, $vai_tro, $data) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("
            UPDATE nhanvien 
            SET ho_ten = ?,
                email = ?,
                sdt = ?,
                cccd = ?,
                dia_chi = ?,
                ngay_sinh = ?,
                gioi_tinh = ?,
                ngay_cap_nhat = NOW()
            WHERE ten_dang_nhap = ? AND vai_tro = ?
        ");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('sssssssss', 
            $data['ho_ten'],
            $data['email'],
            $data['sdt'],
            $data['cccd'],
            $data['dia_chi'],
            $data['ngay_sinh'],
            $data['gioi_tinh'],
            $username,
            $vai_tro
        );
        return $stmt->execute();
    } catch (Exception $e) {
        throw new Exception('Lỗi cập nhật thông tin: ' . $e->getMessage());
    }
}
