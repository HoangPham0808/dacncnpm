<?php
/**
 * Login Controller
 * Xử lý logic đăng nhập
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'login_model.php';

$error_message = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    // Kiểm tra dữ liệu đầu vào
    if (empty($username) || empty($password)) {
        $error_message = 'Vui lòng điền đầy đủ thông tin đăng nhập!';
    } else {
        try {
            $user = getAccountByUsername($username);

            if ($user) {
                // Kiểm tra loại tài khoản - CHỈ cho phép tài khoản Nhân viên đăng nhập vào hệ thống nội bộ
                if ($user['loai_tai_khoan'] !== 'Nhân viên') {
                    $error_message = 'Bạn không có quyền truy cập vào hệ thống này. Vui lòng sử dụng trang đăng nhập dành cho khách hàng!';
                } else {
                    // Kiểm tra trạng thái tài khoản
                    if ($user['trang_thai'] !== 'Hoạt động') {
                        $error_message = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên!';
                    } else {
                        // Kiểm tra mật khẩu
                        if (verifyPassword($password, $user['mat_khau'])) {
                            // Kiểm tra nếu đã đăng nhập ở tab khác (tránh đăng nhập đồng thời)
                            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                                // Nếu đã đăng nhập với tài khoản khác, đăng xuất trước
                                if (isset($_SESSION['username']) && $_SESSION['username'] !== $username) {
                                    session_destroy();
                                    session_start();
                                } else {
                                    // Đã đăng nhập với cùng tài khoản, redirect luôn
                                    $redirect_url = '../dfc_ad/dfc_ad.html';
                                    if (isset($_SESSION['vai_tro'])) {
                                        switch ($_SESSION['vai_tro']) {
                                            case 'Admin':
                                                $redirect_url = '../dfc_ad/dfc_ad.html';
                                                break;
                                            case 'PR':
                                                $redirect_url = '../Staff/PR/Staff_PR.html';
                                                break;
                                            case 'PT':
                                                $redirect_url = '../Staff/PT/Staff_PT.html';
                                                break;
                                            case 'Lễ Tân':
                                                $redirect_url = '../Staff/Receptionist/Staff_Receptionist.html';
                                                break;
                                        }
                                    }
                                    header('Location: ' . $redirect_url);
                                    exit();
                                }
                            }
                            
                            // Đăng nhập thành công
                            // Tạo session ID mới để tránh session fixation
                            session_regenerate_id(true);
                            
                            $_SESSION['user_id'] = $user['ten_dang_nhap'];
                            $_SESSION['username'] = $user['ten_dang_nhap'];
                            $_SESSION['user_type'] = $user['loai_tai_khoan'];
                            $_SESSION['logged_in'] = true;

                            $redirect_url = '';

                            // Lấy thông tin nhân viên
                            $nhanVien = getEmployeeByUsername($username);
                            
                            if ($nhanVien) {
                                $_SESSION['nhan_vien_id'] = $nhanVien['nhan_vien_id'];
                                $_SESSION['ho_ten'] = $nhanVien['ho_ten'];
                                $_SESSION['vai_tro'] = $nhanVien['vai_tro'];
                                
                                // Redirect theo vai trò
                                switch ($nhanVien['vai_tro']) {
                                    case 'Admin':
                                        $redirect_url = '../dfc_ad/dfc_ad.html';
                                        break;
                                    case 'PR':
                                        $redirect_url = '../Staff/PR/Staff_PR.html';
                                        break;
                                    case 'PT':
                                        $redirect_url = '../Staff/PT/Staff_PT.html';
                                        break;
                                    case 'Lễ Tân':
                                        $redirect_url = '../Staff/Receptionist/Staff_Receptionist.html';
                                        break;
                                    default:
                                        $redirect_url = '../quanly/quanly.html';
                                        break;
                                }
                                
                                // Cập nhật lần đăng nhập cuối (chỉ cho PR, PT và Lễ Tân, không lưu cho Admin)
                                if ($nhanVien['vai_tro'] === 'PR' || $nhanVien['vai_tro'] === 'PT' || $nhanVien['vai_tro'] === 'Lễ Tân') {
                                    updateLastLogin($username);
                                    // Lưu lịch sử ra vào
                                    insertLichSuRaVao($username, 'Nhân viên');
                                }
                            } else {
                                // Nếu không tìm thấy trong bảng nhân viên nhưng là loại "Nhân viên"
                                $redirect_url = '../dfc_ad/dfc_ad.html';
                            }

                            // Ghi nhớ đăng nhập (cookie)
                            if ($remember) {
                                setcookie('remember_user', $username, time() + (86400 * 30), '/'); // 30 ngày
                            }

                            // Giải phóng session lock trước khi redirect
                            session_write_close();

                            // Redirect đến trang tương ứng
                            header('Location: ' . $redirect_url);
                            exit();
                        } else {
                            $error_message = 'Tên đăng nhập hoặc mật khẩu không đúng!';
                        }
                    }
                }
            } else {
                $error_message = 'Tên đăng nhập hoặc mật khẩu không đúng!';
            }
        } catch (Exception $e) {
            $error_message = 'Đã xảy ra lỗi khi đăng nhập. Vui lòng thử lại sau!';
        }
    }
}

