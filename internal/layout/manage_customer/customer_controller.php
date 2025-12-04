<?php
/**
 * Customer Controller
 * Xử lý các action: add, edit, delete, search
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'customer_model.php';

// ============================================
// XỬ LÝ XÓA KHÁCH HÀNG (AJAX)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json');
    
    try {
        // Kiểm tra ID có hợp lệ không
        if (!isset($_POST['khach_hang_id']) || !is_numeric($_POST['khach_hang_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID khách hàng không hợp lệ!']);
            exit;
        }
        
        $khach_hang_id = intval($_POST['khach_hang_id']);
        $conn = getDBConnection();
        $conn->begin_transaction();
        
        // Lấy thông tin khách hàng cần xóa
        $customer = getCustomerById($khach_hang_id);
        
        if (!$customer) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng!']);
            exit;
        }
        
        // Kiểm tra ràng buộc
       
        
        // Xóa khách hàng
        deleteCustomer($conn, $khach_hang_id, $customer['ten_dang_nhap']);
        
        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = 'Xóa khách hàng thành công!';
        $_SESSION['messageType'] = 'success';
        echo json_encode(['success' => true, 'message' => 'Xóa khách hàng thành công!']);
        exit;
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// XỬ LÝ THÊM KHÁCH HÀNG
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // Kiểm tra nếu là AJAX request
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    try {
        $conn = getDBConnection();
        $conn->begin_transaction();

        // Lấy dữ liệu từ form
        $tenDangNhap   = trim($_POST['ten_dang_nhap']);
        $matKhau       = trim($_POST['mat_khau']);
        $hoTen         = trim($_POST['ho_ten']);
        $email         = trim($_POST['email']);
        $sdt           = trim($_POST['sdt']);
        $cccd          = trim($_POST['cccd']);
        $diaChi        = trim($_POST['dia_chi']);
        $ngaySinh      = !empty($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : null;
        $gioiTinh      = $_POST['gioi_tinh'];
        $loaiTaiKhoan  = 'Khách hàng';
        $ghiChu        = !empty($_POST['ghi_chu']) ? trim($_POST['ghi_chu']) : '';
        $nguoiGioiThieu = !empty($_POST['nguoi_gioi_thieu']) ? trim($_POST['nguoi_gioi_thieu']) : '';

        // Kiểm tra tên đăng nhập đã tồn tại chưa
        if (checkUsernameExists($tenDangNhap)) {
            $conn->rollback();
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại!']);
                exit();
            }
            $_SESSION['message'] = 'Tên đăng nhập đã tồn tại!';
            $_SESSION['messageType'] = 'error';
            header("Location: customer.php");
            exit();
        }

        // Kiểm tra email đã tồn tại chưa
        if (checkEmailExists($email)) {
            $conn->rollback();
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Email đã tồn tại!']);
                exit();
            }
            $_SESSION['message'] = 'Email đã tồn tại!';
            $_SESSION['messageType'] = 'error';
            header("Location: customer.php");
            exit();
        }

        // Thêm tài khoản
        addAccount($conn, $tenDangNhap, $matKhau, $loaiTaiKhoan);

        // Thêm khách hàng
        $phongTapId = !empty($_POST['phong_tap_id']) ? trim($_POST['phong_tap_id']) : '';
        $customerData = [
            'ten_dang_nhap' => $tenDangNhap,
            'ho_ten' => $hoTen,
            'email' => $email,
            'sdt' => $sdt,
            'cccd' => $cccd,
            'dia_chi' => $diaChi,
            'ngay_sinh' => $ngaySinh,
            'gioi_tinh' => $gioiTinh,
            'nguon_gioi_thieu' => $nguoiGioiThieu, // Map từ form field sang database column
            'ghi_chu' => $ghiChu,
            'phong_tap_id' => $phongTapId
        ];
        addCustomer($conn, $customerData);

        // Commit transaction và thông báo thành công
        $conn->commit();
        $_SESSION['message'] = 'Thêm khách hàng thành công!';
        $_SESSION['messageType'] = 'success';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Thêm khách hàng thành công!']);
            exit();
        }
        header("Location: customer.php");
        exit();

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
            exit();
        }
        $_SESSION['message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['messageType'] = 'error';
    }
}

// ============================================
// XỬ LÝ CẬP NHẬT KHÁCH HÀNG
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    try {
        $conn = getDBConnection();
        $conn->begin_transaction();
        
        // Lấy dữ liệu từ form
        $id = $_POST['khach_hang_id'];
        $hoTen = trim($_POST['ho_ten']);
        $email = trim($_POST['email']);
        $sdt = trim($_POST['sdt']);
        $cccd = trim($_POST['cccd']);
        $diaChi = trim($_POST['dia_chi']);
        $ngaySinh = !empty($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : null;
        $gioiTinh = $_POST['gioi_tinh'];
        $ghiChu = isset($_POST['ghi_chu']) ? trim($_POST['ghi_chu']) : '';
        $nguoiGioiThieu = isset($_POST['nguoi_gioi_thieu']) ? trim($_POST['nguoi_gioi_thieu']) : '';
        
        // Lấy tên đăng nhập để cập nhật tài khoản
        $customer = getCustomerById($id);
        
        if (!$customer) {
            throw new Exception("Không tìm thấy khách hàng");
        }
        
        // Cập nhật thông tin khách hàng
        $phongTapId = !empty($_POST['phong_tap_id']) ? trim($_POST['phong_tap_id']) : '';
        $customerData = [
            'ho_ten' => $hoTen,
            'email' => $email,
            'sdt' => $sdt,
            'cccd' => $cccd,
            'dia_chi' => $diaChi,
            'ngay_sinh' => $ngaySinh,
            'gioi_tinh' => $gioiTinh,
            'nguon_gioi_thieu' => $nguoiGioiThieu, // Map từ form field sang database column
            'ghi_chu' => $ghiChu,
            'phong_tap_id' => $phongTapId
        ];
        updateCustomer($conn, $id, $customerData);
        
        // Cập nhật mật khẩu nếu có nhập mới
        if (!empty($_POST['mat_khau_moi'])) {
            $matKhauMoi = trim($_POST['mat_khau_moi']);
            updatePassword($conn, $customer['ten_dang_nhap'], $matKhauMoi);
        }
        
        // Commit transaction và thông báo thành công
        $conn->commit();
        $_SESSION['message'] = 'Cập nhật khách hàng thành công!';
        $_SESSION['messageType'] = 'success';
        header("Location: customer.php");
        exit();
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        $_SESSION['message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['messageType'] = 'error';
        header("Location: customer.php");
        exit();
    }
}
