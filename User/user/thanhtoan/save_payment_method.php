<?php
/**
 * save_payment_method.php - Lưu phương thức thanh toán mới
 */
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';
require_once __DIR__ . '/../getset/check_session.php';

// Kiểm tra đăng nhập
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../goitap/packages.html');
    exit;
}

try {
    $username = $_SESSION['user']['username'];
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        header('Location: ../goitap/packages.html?msg=' . urlencode('Không tìm thấy thông tin khách hàng') . '&type=error');
        exit;
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // Tạo bảng nếu chưa có
    $pdo->exec("CREATE TABLE IF NOT EXISTS PhuongThucThanhToan (
        phuong_thuc_id INT AUTO_INCREMENT PRIMARY KEY,
        khach_hang_id INT NOT NULL,
        loai_phuong_thuc ENUM('Tiền mặt', 'Chuyển khoản', 'Thẻ', 'Ví điện tử') NOT NULL,
        ten_hien_thi VARCHAR(100) NOT NULL,
        thong_tin_chi_tiet VARCHAR(255),
        mac_dinh BOOLEAN DEFAULT 0,
        ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (khach_hang_id) REFERENCES KhachHang(khach_hang_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Lấy loại phương thức từ form
    $payment_type = trim($_POST['payment_type'] ?? '');
    $loai_phuong_thuc = '';
    $ten_hien_thi = '';
    $thong_tin_chi_tiet = '';
    
    if ($payment_type === 'card') {
        // Thẻ tín dụng
        $card_number = trim($_POST['card_number'] ?? '');
        $card_name = trim($_POST['card_name'] ?? '');
        $card_expiry = trim($_POST['card_expiry'] ?? '');
        $card_cvc = trim($_POST['card_cvc'] ?? '');
        
        if (empty($card_number) || empty($card_name) || empty($card_expiry) || empty($card_cvc)) {
            header('Location: ../goitap/packages.html?msg=' . urlencode('Vui lòng điền đầy đủ thông tin thẻ') . '&type=error');
            exit;
        }
        
        $loai_phuong_thuc = 'Thẻ';
        // Xác định loại thẻ dựa trên số thẻ
        $card_number_clean = preg_replace('/\s+/', '', $card_number);
        if (substr($card_number_clean, 0, 1) == '4') {
            $ten_hien_thi = 'Visa';
        } elseif (substr($card_number_clean, 0, 1) == '5' || substr($card_number_clean, 0, 2) == '2') {
            $ten_hien_thi = 'Mastercard';
        } else {
            $ten_hien_thi = 'Thẻ tín dụng';
        }
        // Lưu 4 số cuối của thẻ
        $last4 = substr($card_number_clean, -4);
        $thong_tin_chi_tiet = '**** **** **** ' . $last4 . ' - ' . $card_name;
        
    } elseif ($payment_type === 'ewallet') {
        // Ví điện tử
        $ewallet_type = trim($_POST['ewallet_type'] ?? '');
        $ewallet_account = trim($_POST['ewallet_account'] ?? '');
        $ewallet_name = trim($_POST['ewallet_name'] ?? '');
        
        if (empty($ewallet_type) || empty($ewallet_account) || empty($ewallet_name)) {
            header('Location: ../goitap/packages.html?msg=' . urlencode('Vui lòng điền đầy đủ thông tin ví điện tử') . '&type=error');
            exit;
        }
        
        $loai_phuong_thuc = 'Ví điện tử';
        $ten_hien_thi = $ewallet_type; // Ví dụ: Momo, ZaloPay
        // Mask số tài khoản
        if (strlen($ewallet_account) > 4) {
            $thong_tin_chi_tiet = '****' . substr($ewallet_account, -4) . ' - ' . $ewallet_name;
        } else {
            $thong_tin_chi_tiet = $ewallet_account . ' - ' . $ewallet_name;
        }
        
    } elseif ($payment_type === 'bank') {
        // Ngân hàng
        $bank_name = trim($_POST['bank_name'] ?? '');
        $bank_account = trim($_POST['bank_account'] ?? '');
        $account_holder = trim($_POST['account_holder'] ?? '');
        
        if (empty($bank_name) || empty($bank_account) || empty($account_holder)) {
            header('Location: ../goitap/packages.html?msg=' . urlencode('Vui lòng điền đầy đủ thông tin ngân hàng') . '&type=error');
            exit;
        }
        
        $loai_phuong_thuc = 'Chuyển khoản';
        $ten_hien_thi = $bank_name; // Tên ngân hàng
        // Mask số tài khoản
        $last4 = substr($bank_account, -4);
        $thong_tin_chi_tiet = '**** **** ' . $last4 . ' - ' . $account_holder;
        
    } elseif ($payment_type === 'paypal') {
        // PayPal
        $paypal_email = trim($_POST['paypal_email'] ?? '');
        
        if (empty($paypal_email) || !filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ../goitap/packages.html?msg=' . urlencode('Vui lòng nhập email PayPal hợp lệ') . '&type=error');
            exit;
        }
        
        $loai_phuong_thuc = 'Ví điện tử';
        $ten_hien_thi = 'PayPal';
        // Mask email
        $emailParts = explode('@', $paypal_email);
        $username = $emailParts[0];
        $domain = $emailParts[1] ?? '';
        if (strlen($username) > 2) {
            $maskedUsername = substr($username, 0, 2) . '****@' . $domain;
        } else {
            $maskedUsername = '****@' . $domain;
        }
        $thong_tin_chi_tiet = $maskedUsername;
        
    } else {
        header('Location: ../goitap/packages.html?msg=' . urlencode('Loại phương thức thanh toán không hợp lệ') . '&type=error');
        exit;
    }
    
    // Kiểm tra xem phương thức này đã tồn tại chưa
    $stmt_check = $pdo->prepare("SELECT phuong_thuc_id FROM PhuongThucThanhToan 
                                WHERE khach_hang_id = :kh_id 
                                AND loai_phuong_thuc = :loai 
                                AND ten_hien_thi = :ten
                                AND thong_tin_chi_tiet = :thong_tin");
    $stmt_check->execute([
        ':kh_id' => $khach_hang_id,
        ':loai' => $loai_phuong_thuc,
        ':ten' => $ten_hien_thi,
        ':thong_tin' => $thong_tin_chi_tiet
    ]);
    
    if ($stmt_check->fetch()) {
        header('Location: ../goitap/packages.html?msg=' . urlencode('Phương thức thanh toán này đã được lưu trước đó') . '&type=warning');
        exit;
    }
    
    // Kiểm tra xem có phương thức nào được đánh dấu mặc định chưa
    $stmt_check_default = $pdo->prepare("SELECT COUNT(*) as count FROM PhuongThucThanhToan 
                                         WHERE khach_hang_id = :kh_id AND mac_dinh = 1");
    $stmt_check_default->execute([':kh_id' => $khach_hang_id]);
    $has_default = $stmt_check_default->fetch()['count'] > 0;
    
    // Nếu chưa có phương thức mặc định, đánh dấu phương thức này là mặc định
    // (Tự động đặt phương thức đầu tiên làm mặc định)
    $mac_dinh = $has_default ? 0 : 1;
    
    // Lưu phương thức mới
    $stmt_insert = $pdo->prepare("INSERT INTO PhuongThucThanhToan 
                                  (khach_hang_id, loai_phuong_thuc, ten_hien_thi, thong_tin_chi_tiet, mac_dinh) 
                                  VALUES (:kh_id, :loai, :ten, :thong_tin, :mac_dinh)");
    $stmt_insert->execute([
        ':kh_id' => $khach_hang_id,
        ':loai' => $loai_phuong_thuc,
        ':ten' => $ten_hien_thi,
        ':thong_tin' => $thong_tin_chi_tiet,
        ':mac_dinh' => $mac_dinh
    ]);
    
    header('Location: ../goitap/packages.html?msg=' . urlencode('Đã lưu phương thức thanh toán thành công') . '&type=success');
    exit;
    
} catch (Exception $e) {
    error_log('Save payment method error: ' . $e->getMessage());
    header('Location: ../goitap/packages.html?msg=' . urlencode('Có lỗi xảy ra khi lưu phương thức thanh toán') . '&type=error');
    exit;
}

