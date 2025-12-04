<?php
/**
 * System Controller
 * Xử lý các action: lock_account, unlock_account
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'system_model.php';

// ============================================
// XỬ LÝ KHÓA TÀI KHOẢN
// ============================================
if (isset($_POST['action']) && $_POST['action'] == 'lock_account') {
    header('Content-Type: application/json');
    
    try {
        $ten_dang_nhap = $_POST['ten_dang_nhap'] ?? '';
        if (empty($ten_dang_nhap)) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập không hợp lệ']);
            exit;
        }
        
        $conn = getDBConnection();
        $result = lockAccount($conn, $ten_dang_nhap);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Khóa tài khoản thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// XỬ LÝ MỞ KHÓA TÀI KHOẢN
// ============================================
if (isset($_POST['action']) && $_POST['action'] == 'unlock_account') {
    header('Content-Type: application/json');
    
    try {
        $ten_dang_nhap = $_POST['ten_dang_nhap'] ?? '';
        if (empty($ten_dang_nhap)) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập không hợp lệ']);
            exit;
        }
        
        $conn = getDBConnection();
        $result = unlockAccount($conn, $ten_dang_nhap);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Mở khóa tài khoản thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// XỬ LÝ XEM LỊCH SỬ RA VÀO
// ============================================
if (isset($_POST['action']) && $_POST['action'] == 'view_ra_vao_history') {
    header('Content-Type: application/json');
    
    try {
        $ten_dang_nhap = $_POST['ten_dang_nhap'] ?? '';
        if (empty($ten_dang_nhap)) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập không hợp lệ', 'history' => []]);
            exit;
        }
        
        // Lấy lịch sử ra vào của tài khoản được chọn
        $history = getLichSuRaVao($ten_dang_nhap);
        
        // Format dữ liệu để hiển thị
        $formatted_history = [];
        foreach ($history as $item) {
            $formatted_history[] = [
                'lich_su_id' => $item['lich_su_id'],
                'ten_dang_nhap' => $item['ten_dang_nhap'],
                'loai_tai_khoan' => $item['loai_tai_khoan'],
                'thoi_gian_vao' => $item['thoi_gian_vao'] ? date('d/m/Y H:i:s', strtotime($item['thoi_gian_vao'])) : 'N/A',
                'thoi_gian_truoc' => $item['thoi_gian_truoc'] ?? 'N/A'
            ];
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Lấy lịch sử ra vào thành công',
            'history' => $formatted_history,
            'ten_dang_nhap' => $ten_dang_nhap
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage(), 'history' => []]);
        exit;
    }
}

// ============================================
// XỬ LÝ XEM LỊCH SỬ ĐĂNG NHẬP
// ============================================
if (isset($_POST['action']) && $_POST['action'] == 'view_login_history') {
    header('Content-Type: application/json');
    
    try {
        $ten_dang_nhap = $_POST['ten_dang_nhap'] ?? '';
        if (empty($ten_dang_nhap)) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập không hợp lệ', 'history' => []]);
            exit;
        }
        
        // Lấy lịch sử đăng nhập của tài khoản được chọn
        $history = getLoginHistory($ten_dang_nhap);
        
        // Format dữ liệu để hiển thị
        $formatted_history = [];
        foreach ($history as $item) {
            $formatted_history[] = [
                'ten_dang_nhap' => $item['ten_dang_nhap'],
                'thoi_gian' => $item['thoi_gian_dang_nhap'] ? date('d/m/Y H:i:s', strtotime($item['thoi_gian_dang_nhap'])) : 'Chưa đăng nhập',
                'thoi_gian_dang_nhap' => $item['thoi_gian_dang_nhap'], // Giữ nguyên để check trong JS
                'thoi_gian_truoc' => $item['thoi_gian_truoc'] ?? 'Chưa đăng nhập',
                'loai_tai_khoan' => $item['loai_tai_khoan'] ?? 'N/A',
                'trang_thai_tai_khoan' => $item['trang_thai_tai_khoan'] ?? 'N/A',
                'trang_thai' => $item['thoi_gian_dang_nhap'] ? 'Thành công' : 'Chưa đăng nhập',
                'ngay_tao' => isset($item['ngay_tao']) ? date('d/m/Y H:i:s', strtotime($item['ngay_tao'])) : 'N/A',
                'ngay_cap_nhat' => isset($item['ngay_cap_nhat']) ? date('d/m/Y H:i:s', strtotime($item['ngay_cap_nhat'])) : 'N/A'
            ];
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Lấy lịch sử đăng nhập thành công',
            'history' => $formatted_history,
            'ten_dang_nhap' => $ten_dang_nhap
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage(), 'history' => []]);
        exit;
    }
}