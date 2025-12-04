<?php
/**
 * System Model
 * Chứa các hàm truy vấn database cho quản lý hệ thống (tài khoản)
 */

// Include file kết nối database
require_once '../../../Database/db.php';

// Kết nối database
function getDBConnection() {
    global $conn;
    return $conn;
}

/**
 * Lấy danh sách tất cả tài khoản (loại trừ admin)
 */
function getAllAccounts() {
    $conn = getDBConnection();
    
    try {
        $result = $conn->query("SELECT ten_dang_nhap, loai_tai_khoan, trang_thai, ngay_tao, ngay_cap_nhat, lan_dang_nhap_cuoi 
                                FROM TaiKhoan 
                                WHERE ten_dang_nhap != 'admin'
                                ORDER BY ngay_tao DESC");
        if (!$result) {
            throw new Exception('Lỗi query: ' . $conn->error);
        }
        
        $accounts = [];
        while ($row = $result->fetch_assoc()) {
            $accounts[] = $row;
        }
        
        return $accounts;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn danh sách tài khoản: ' . $e->getMessage());
    }
}

/**
 * Khóa tài khoản
 */
function lockAccount($conn, $ten_dang_nhap) {
    try {
        $stmt = $conn->prepare("UPDATE TaiKhoan SET trang_thai = 'Khóa', ngay_cap_nhat = NOW() WHERE ten_dang_nhap = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $ten_dang_nhap);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            return true;
        }
        return false;
    } catch (Exception $e) {
        throw new Exception('Lỗi khóa tài khoản: ' . $e->getMessage());
    }
}

/**
 * Mở khóa tài khoản
 */
function unlockAccount($conn, $ten_dang_nhap) {
    try {
        $stmt = $conn->prepare("UPDATE TaiKhoan SET trang_thai = 'Hoạt động', ngay_cap_nhat = NOW() WHERE ten_dang_nhap = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $ten_dang_nhap);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            return true;
        }
        return false;
    } catch (Exception $e) {
        throw new Exception('Lỗi mở khóa tài khoản: ' . $e->getMessage());
    }
}

/**
 * Lấy lịch sử đăng nhập của tài khoản
 * Hệ thống chỉ lưu lần đăng nhập cuối trong cột lan_dang_nhap_cuoi
 */
function getLoginHistory($ten_dang_nhap) {
    $conn = getDBConnection();
    
    try {
        // Lấy thông tin lần đăng nhập cuối từ bảng TaiKhoan
        $stmt = $conn->prepare("SELECT ten_dang_nhap, lan_dang_nhap_cuoi, ngay_tao, ngay_cap_nhat, loai_tai_khoan, trang_thai
                                FROM TaiKhoan 
                                WHERE ten_dang_nhap = ?");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $ten_dang_nhap);
        $stmt->execute();
        $result = $stmt->get_result();
        $account = $result->fetch_assoc();
        
        $history = [];
        
        if ($account) {
            // Tính toán thời gian từ lần đăng nhập cuối đến hiện tại
            $thoi_gian_truoc = 'Chưa đăng nhập';
            if ($account['lan_dang_nhap_cuoi']) {
                // Đảm bảo timezone được set đúng
                date_default_timezone_set('Asia/Ho_Chi_Minh');
                
                $thoi_gian_dang_nhap = new DateTime($account['lan_dang_nhap_cuoi'], new DateTimeZone('Asia/Ho_Chi_Minh'));
                $thoi_gian_hien_tai = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
                $interval = $thoi_gian_dang_nhap->diff($thoi_gian_hien_tai);
                
                // Kiểm tra nếu thời gian trong tương lai (do timezone sai) hoặc quá nhỏ
                if ($thoi_gian_dang_nhap > $thoi_gian_hien_tai) {
                    $thoi_gian_truoc = 'Vừa xong';
                } elseif ($interval->y > 0) {
                    $thoi_gian_truoc = $interval->y . ' năm trước';
                } elseif ($interval->m > 0) {
                    $thoi_gian_truoc = $interval->m . ' tháng trước';
                } elseif ($interval->d > 0) {
                    $thoi_gian_truoc = $interval->d . ' ngày trước';
                } elseif ($interval->h > 0) {
                    $thoi_gian_truoc = $interval->h . ' giờ trước';
                } elseif ($interval->i > 0) {
                    $thoi_gian_truoc = $interval->i . ' phút trước';
                } elseif ($interval->s > 30) {
                    $thoi_gian_truoc = $interval->s . ' giây trước';
                } else {
                    $thoi_gian_truoc = 'Vừa xong';
                }
            }
            
            $history[] = [
                'ten_dang_nhap' => $account['ten_dang_nhap'],
                'thoi_gian_dang_nhap' => $account['lan_dang_nhap_cuoi'],
                'thoi_gian_truoc' => $thoi_gian_truoc,
                'ip_address' => 'N/A',
                'user_agent' => 'N/A',
                'trang_thai' => 'Thành công',
                'ngay_tao' => $account['ngay_tao'],
                'ngay_cap_nhat' => $account['ngay_cap_nhat'],
                'loai_tai_khoan' => $account['loai_tai_khoan'],
                'trang_thai_tai_khoan' => $account['trang_thai']
            ];
        }
        
        return $history;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn lịch sử đăng nhập: ' . $e->getMessage());
    }
}

/**
 * Lấy tất cả lịch sử đăng nhập cuối của tất cả các tài khoản
 */
function getAllLoginHistory() {
    $conn = getDBConnection();
    
    try {
        // Lấy tất cả tài khoản với lần đăng nhập cuối
        $result = $conn->query("SELECT ten_dang_nhap, lan_dang_nhap_cuoi, ngay_tao, ngay_cap_nhat, loai_tai_khoan, trang_thai
                                FROM TaiKhoan 
                                WHERE ten_dang_nhap != 'admin'
                                ORDER BY lan_dang_nhap_cuoi DESC, ten_dang_nhap ASC");
        if (!$result) {
            throw new Exception('Lỗi query: ' . $conn->error);
        }
        
        $history = [];
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $thoi_gian_hien_tai = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        
        while ($account = $result->fetch_assoc()) {
            // Tính toán thời gian từ lần đăng nhập cuối đến hiện tại
            $thoi_gian_truoc = 'Chưa đăng nhập';
            if ($account['lan_dang_nhap_cuoi']) {
                // Đảm bảo timezone được set đúng
                date_default_timezone_set('Asia/Ho_Chi_Minh');
                
                $thoi_gian_dang_nhap = new DateTime($account['lan_dang_nhap_cuoi'], new DateTimeZone('Asia/Ho_Chi_Minh'));
                $interval = $thoi_gian_dang_nhap->diff($thoi_gian_hien_tai);
                
                // Kiểm tra nếu thời gian trong tương lai (do timezone sai) hoặc quá nhỏ
                if ($thoi_gian_dang_nhap > $thoi_gian_hien_tai) {
                    $thoi_gian_truoc = 'Vừa xong';
                } elseif ($interval->y > 0) {
                    $thoi_gian_truoc = $interval->y . ' năm trước';
                } elseif ($interval->m > 0) {
                    $thoi_gian_truoc = $interval->m . ' tháng trước';
                } elseif ($interval->d > 0) {
                    $thoi_gian_truoc = $interval->d . ' ngày trước';
                } elseif ($interval->h > 0) {
                    $thoi_gian_truoc = $interval->h . ' giờ trước';
                } elseif ($interval->i > 0) {
                    $thoi_gian_truoc = $interval->i . ' phút trước';
                } elseif ($interval->s > 30) {
                    $thoi_gian_truoc = $interval->s . ' giây trước';
                } else {
                    $thoi_gian_truoc = 'Vừa xong';
                }
            }
            
            $history[] = [
                'ten_dang_nhap' => $account['ten_dang_nhap'],
                'thoi_gian_dang_nhap' => $account['lan_dang_nhap_cuoi'],
                'thoi_gian_truoc' => $thoi_gian_truoc,
                'loai_tai_khoan' => $account['loai_tai_khoan'],
                'trang_thai_tai_khoan' => $account['trang_thai'],
                'ngay_tao' => $account['ngay_tao'],
                'ngay_cap_nhat' => $account['ngay_cap_nhat']
            ];
        }
        
        return $history;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn lịch sử đăng nhập: ' . $e->getMessage());
    }
}

/**
 * Lấy lịch sử ra vào của tài khoản từ bảng LichSuRaVao
 * @param string $ten_dang_nhap - Tên đăng nhập cần lấy lịch sử
 * @return array - Mảng chứa lịch sử ra vào
 */
function getLichSuRaVao($ten_dang_nhap) {
    $conn = getDBConnection();
    
    try {
        // Kiểm tra xem bảng LichSuRaVao có tồn tại không
        $checkTable = $conn->query("SHOW TABLES LIKE 'LichSuRaVao'");
        if ($checkTable->num_rows == 0) {
            return [];
        }
        
        // Lấy lịch sử ra vào từ bảng LichSuRaVao
        $stmt = $conn->prepare("SELECT lich_su_id, ten_dang_nhap, loai_tai_khoan, thoi_gian_vao 
                                FROM LichSuRaVao 
                                WHERE ten_dang_nhap = ? 
                                ORDER BY thoi_gian_vao DESC");
        if (!$stmt) {
            throw new Exception('Lỗi prepare: ' . $conn->error);
        }
        $stmt->bind_param('s', $ten_dang_nhap);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $thoi_gian_hien_tai = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        
        while ($row = $result->fetch_assoc()) {
            // Tính toán thời gian từ lần vào đến hiện tại
            $thoi_gian_truoc = '';
            if ($row['thoi_gian_vao']) {
                $thoi_gian_vao = new DateTime($row['thoi_gian_vao'], new DateTimeZone('Asia/Ho_Chi_Minh'));
                $interval = $thoi_gian_vao->diff($thoi_gian_hien_tai);
                
                if ($interval->y > 0) {
                    $thoi_gian_truoc = $interval->y . ' năm trước';
                } elseif ($interval->m > 0) {
                    $thoi_gian_truoc = $interval->m . ' tháng trước';
                } elseif ($interval->d > 0) {
                    $thoi_gian_truoc = $interval->d . ' ngày trước';
                } elseif ($interval->h > 0) {
                    $thoi_gian_truoc = $interval->h . ' giờ trước';
                } elseif ($interval->i > 0) {
                    $thoi_gian_truoc = $interval->i . ' phút trước';
                } elseif ($interval->s > 30) {
                    $thoi_gian_truoc = $interval->s . ' giây trước';
                } else {
                    $thoi_gian_truoc = 'Vừa xong';
                }
            }
            
            $history[] = [
                'lich_su_id' => $row['lich_su_id'],
                'ten_dang_nhap' => $row['ten_dang_nhap'],
                'loai_tai_khoan' => $row['loai_tai_khoan'],
                'thoi_gian_vao' => $row['thoi_gian_vao'],
                'thoi_gian_truoc' => $thoi_gian_truoc
            ];
        }
        
        return $history;
    } catch (Exception $e) {
        throw new Exception('Lỗi truy vấn lịch sử ra vào: ' . $e->getMessage());
    }
}
