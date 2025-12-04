<?php
// process_payment.php - Xử lý thanh toán gói tập
session_start();

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';
require_once __DIR__ . '/../getset/check_session.php';

// Kiểm tra đăng nhập
require_login();

// Fallback BASE_URL
if (!defined('BASE_URL')) {
    $base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    define('BASE_URL', ($base ? $base : '') . '/');
}

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: packages.html');
    exit;
}

// Lấy dữ liệu từ form
$package_name = trim($_POST['package_name'] ?? '');
$package_price = trim($_POST['package_price'] ?? '');
$package_id = trim($_POST['package_id'] ?? '');
$payment_method_raw = trim($_POST['payment_method'] ?? 'Chuyển khoản');

// Lấy phong_tap_id từ form - BẮT BUỘC phải có
// Ưu tiên lấy từ phong_tap_id, nếu không có thì lấy từ chi_nhanh_id (tương thích code cũ)
$phong_tap_id = null;
if (!empty($_POST['phong_tap_id'])) {
    $phong_tap_id = intval($_POST['phong_tap_id']);
} elseif (!empty($_POST['chi_nhanh_id'])) {
    $phong_tap_id = intval($_POST['chi_nhanh_id']);
}

// Log để debug
error_log("Payment form - phong_tap_id from POST: " . var_export($_POST['phong_tap_id'] ?? 'NOT SET', true));
error_log("Payment form - chi_nhanh_id from POST: " . var_export($_POST['chi_nhanh_id'] ?? 'NOT SET', true));
error_log("Payment form - Final phong_tap_id: " . var_export($phong_tap_id, true));

// Map phương thức thanh toán với ENUM trong SQL: 'Tiền mặt', 'Chuyển khoản', 'Thẻ', 'Ví điện tử'
$payment_method_mapping = [
    'Ngân hàng' => 'Chuyển khoản',
    'Chuyển khoản' => 'Chuyển khoản',
    'Bank' => 'Chuyển khoản',
    'Tiền mặt' => 'Tiền mặt',
    'Cash' => 'Tiền mặt',
    'Thẻ' => 'Thẻ',
    'Card' => 'Thẻ',
    'VISA' => 'Thẻ',
    'Visa' => 'Thẻ',
    'MasterCard' => 'Thẻ',
    'Ví điện tử' => 'Ví điện tử',
    'Momo' => 'Ví điện tử',
    'ZaloPay' => 'Ví điện tử',
    'PayPal' => 'Ví điện tử',
    'Electronic Wallet' => 'Ví điện tử'
];

$payment_method = $payment_method_mapping[$payment_method_raw] ?? 'Chuyển khoản';

// Validate dữ liệu
if ($package_name === '' || $package_price === '' || $package_id === '') {
    header('Location: packages.html?msg=' . urlencode('Thông tin gói không hợp lệ') . '&type=error');
    exit;
}

// Lấy username từ session
$username = $_SESSION['user']['username'];

try {
    // Lấy khach_hang_id từ username
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        header('Location: packages.html?msg=' . urlencode('Không tìm thấy thông tin khách hàng') . '&type=error');
        exit;
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // Lấy thông tin khách hàng từ form thanh toán
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $cccd = trim($_POST['cccd'] ?? '');
    $dia_chi = trim($_POST['dia_chi'] ?? '');
    $ngay_sinh = trim($_POST['ngay_sinh'] ?? '');
    $gioi_tinh = trim($_POST['gioi_tinh'] ?? '');
    $nguon_gioi_thieu = trim($_POST['nguon_gioi_thieu'] ?? '');
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');
    
    // Validate các trường bắt buộc
    if (empty($sdt) || empty($cccd) || empty($dia_chi) || empty($ngay_sinh) || empty($gioi_tinh)) {
        header('Location: packages.html?msg=' . urlencode('Vui lòng điền đầy đủ thông tin bắt buộc') . '&type=error');
        exit;
    }
    
    // Validate phòng tập - BẮT BUỘC phải chọn
    if (empty($phong_tap_id) || $phong_tap_id <= 0) {
        error_log("ERROR: phong_tap_id is empty or invalid: " . var_export($phong_tap_id, true));
        header('Location: packages.html?msg=' . urlencode('Vui lòng chọn phòng tập') . '&type=error');
        exit;
    }
    
    // Kiểm tra phòng tập có tồn tại và đang hoạt động không
    $stmt_check_pt = $pdo->prepare("SELECT phong_tap_id, ten_phong_tap, trang_thai FROM phongtap WHERE phong_tap_id = :pt_id LIMIT 1");
    $stmt_check_pt->execute([':pt_id' => $phong_tap_id]);
    $phongTapInfo = $stmt_check_pt->fetch();
    
    if (!$phongTapInfo) {
        error_log("ERROR: phong_tap_id {$phong_tap_id} does not exist in database");
        header('Location: packages.html?msg=' . urlencode('Phòng tập không tồn tại') . '&type=error');
        exit;
    }
    
    if ($phongTapInfo['trang_thai'] !== 'Hoạt động') {
        error_log("ERROR: phong_tap_id {$phong_tap_id} is not active (status: {$phongTapInfo['trang_thai']})");
        header('Location: packages.html?msg=' . urlencode('Phòng tập không đang hoạt động') . '&type=error');
        exit;
    }
    
    error_log("✓ Validated phong_tap_id: {$phong_tap_id} - {$phongTapInfo['ten_phong_tap']} (status: {$phongTapInfo['trang_thai']})");
    
    // Lấy ho_ten từ session nếu không có trong form
    if (empty($ho_ten)) {
        $ho_ten = $_SESSION['user']['full_name'] ?? '';
    }
    
    // Cập nhật thông tin khách hàng vào bảng KhachHang
    // QUAN TRỌNG: Phải lưu phong_tap_id để user gắn với phòng tập đã chọn
    try {
        // Đảm bảo phong_tap_id là integer
        $phong_tap_id = (int)$phong_tap_id;
        
        $stmt_update = $pdo->prepare("UPDATE KhachHang 
                                      SET ho_ten = :ho_ten,
                                          sdt = :sdt, 
                                          cccd = :cccd, 
                                          dia_chi = :dia_chi, 
                                          ngay_sinh = :ngay_sinh, 
                                          gioi_tinh = :gioi_tinh, 
                                          nguon_gioi_thieu = :nguon_gioi_thieu, 
                                          phong_tap_id = :phong_tap_id,
                                          ghi_chu = :ghi_chu,
                                          ngay_cap_nhat = NOW()
                                      WHERE khach_hang_id = :kh_id");
        $stmt_update->execute([
            ':ho_ten' => $ho_ten,
            ':sdt' => $sdt,
            ':cccd' => $cccd,
            ':dia_chi' => $dia_chi,
            ':ngay_sinh' => $ngay_sinh,
            ':gioi_tinh' => $gioi_tinh,
            ':nguon_gioi_thieu' => $nguon_gioi_thieu ?: null,
            ':phong_tap_id' => $phong_tap_id,  // Lưu phong_tap_id đã chọn
            ':ghi_chu' => $ghi_chu ?: null,
            ':kh_id' => $khach_hang_id
        ]);
        
        $rows_affected = $stmt_update->rowCount();
        error_log("=== UPDATE KHACHHANG ===");
        error_log("khach_hang_id: {$khach_hang_id}");
        error_log("phong_tap_id: {$phong_tap_id} (type: " . gettype($phong_tap_id) . ")");
        error_log("Rows affected: {$rows_affected}");
        
        // Xác nhận phong_tap_id đã được lưu đúng
        $stmt_verify = $pdo->prepare("SELECT phong_tap_id, ten_phong_tap FROM KhachHang kh 
                                     LEFT JOIN phongtap pt ON kh.phong_tap_id = pt.phong_tap_id 
                                     WHERE kh.khach_hang_id = :kh_id LIMIT 1");
        $stmt_verify->execute([':kh_id' => $khach_hang_id]);
        $verify_result = $stmt_verify->fetch();
        
        if ($verify_result) {
            $saved_phong_tap_id = $verify_result['phong_tap_id'] ? (int)$verify_result['phong_tap_id'] : null;
            if ($saved_phong_tap_id == $phong_tap_id) {
                error_log("✓ SUCCESS: phong_tap_id {$phong_tap_id} saved correctly for khach_hang_id: {$khach_hang_id}");
                error_log("  → Phòng tập: " . ($verify_result['ten_phong_tap'] ?? 'N/A'));
            } else {
                error_log("✗ ERROR: phong_tap_id verification failed!");
                error_log("  Expected: {$phong_tap_id} (type: " . gettype($phong_tap_id) . ")");
                error_log("  Got: " . var_export($saved_phong_tap_id, true) . " (type: " . ($saved_phong_tap_id ? gettype($saved_phong_tap_id) : 'NULL') . ")");
            }
        } else {
            error_log("✗ ERROR: Could not verify phong_tap_id - customer not found");
        }
        error_log("=== END UPDATE KHACHHANG ===");
        
        // Cập nhật session nếu có thay đổi ho_ten
        if ($ho_ten && isset($_SESSION['user'])) {
            $_SESSION['user']['full_name'] = $ho_ten;
        }
    } catch (PDOException $e) {
        error_log("Error updating customer info: " . $e->getMessage());
        // Không dừng quá trình thanh toán nếu cập nhật thông tin thất bại
    }
    
    // Xử lý giá tiền (bỏ dấu phẩy và chữ VND/₫)
    $amount = preg_replace('/[^0-9]/', '', $package_price);
    $amount = intval($amount);
    
    // Tạo mã hóa đơn (ví dụ: HD20250131001)
    $ma_hoa_don = 'HD' . date('YmdHis') . rand(100, 999);
    
    // Kiểm tra kết nối database
    if (!$pdo) {
        error_log("ERROR: PDO connection is null");
        header('Location: packages.html?msg=' . urlencode('Lỗi kết nối database. Vui lòng thử lại.') . '&type=error');
        exit;
    }
    
    // Kiểm tra database có tồn tại không
    try {
        $test_query = $pdo->query("SELECT 1 FROM HoaDon LIMIT 1");
    } catch (PDOException $e) {
        error_log("ERROR: Database table check failed: " . $e->getMessage());
        header('Location: packages.html?msg=' . urlencode('Lỗi database: ' . $e->getMessage()) . '&type=error');
        exit;
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    error_log("Transaction started for payment - khach_hang_id: {$khach_hang_id}, package_id: {$package_id}, amount: {$amount}");
    
    try {
        // 1. Insert vào bảng HoaDon
        error_log("Step 1: Inserting HoaDon - ma_hoa_don: {$ma_hoa_don}, khach_hang_id: {$khach_hang_id}, amount: {$amount}, payment_method: {$payment_method}");
        
        $stmt = $pdo->prepare("INSERT INTO HoaDon (
            ma_hoa_don, khach_hang_id, ngay_lap, tong_tien, tien_thanh_toan,
            phuong_thuc_thanh_toan, trang_thai, phong_tap_id
        ) VALUES (
            :ma_hd, :kh_id, CURDATE(), :tong_tien, :thanh_toan,
            :phuong_thuc, 'Đã thanh toán', :phong_tap_id
        )");
        
        try {
            $stmt->execute([
                ':ma_hd' => $ma_hoa_don,
                ':kh_id' => $khach_hang_id,
                ':tong_tien' => $amount,
                ':thanh_toan' => $amount,
                ':phuong_thuc' => $payment_method,
                ':phong_tap_id' => $phong_tap_id
            ]);
        } catch (PDOException $e) {
            error_log("ERROR inserting HoaDon: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            throw new Exception("Failed to insert HoaDon: " . $e->getMessage());
        }
        
        $hoa_don_id = $pdo->lastInsertId();
        
        // Kiểm tra hóa đơn đã được tạo chưa
        if (!$hoa_don_id) {
            throw new Exception("Failed to create HoaDon. ma_hoa_don: {$ma_hoa_don}");
        }
        
        error_log("Step 1: HoaDon created successfully - hoa_don_id: {$hoa_don_id}, ma_hoa_don: {$ma_hoa_don}");
        
        // 2. Lấy goi_tap_id dựa trên package_id
        // Nếu package_id là số, sử dụng trực tiếp làm goi_tap_id (từ database)
        // Nếu package_id là chuỗi (ngay, thang, quy...), sử dụng mapping cũ để tương thích
        $goi_tap_id = null;
        $ten_goi_mapping = $package_name; // Dùng tên gói từ form
        $thoi_han_ngay = 0;
        
        // Kiểm tra xem package_id có phải là số không (goi_tap_id từ database)
        if (is_numeric($package_id)) {
            // package_id là số, sử dụng trực tiếp làm goi_tap_id
            $goi_tap_id = intval($package_id);
            
            // Lấy thông tin gói từ database
            $stmt = $pdo->prepare("SELECT goi_tap_id, ten_goi, thoi_han_ngay, gia_tien FROM GoiTap WHERE goi_tap_id = :goi_id AND trang_thai = 'Đang áp dụng' LIMIT 1");
            $stmt->execute([':goi_id' => $goi_tap_id]);
            $goiTap = $stmt->fetch();
            
            if ($goiTap) {
                $ten_goi_mapping = $goiTap['ten_goi'];
                $thoi_han_ngay = intval($goiTap['thoi_han_ngay']);
                error_log("Step 2: Found GoiTap from database - goi_tap_id: {$goi_tap_id}, ten_goi: {$ten_goi_mapping}, thoi_han: {$thoi_han_ngay}");
            } else {
                throw new Exception("Gói tập với ID {$goi_tap_id} không tồn tại hoặc đã ngừng áp dụng");
            }
        } else {
            // package_id là chuỗi, sử dụng mapping cũ để tương thích
            $packageMapping = [
                'ngay' => ['ten' => 'Gói 1 ngày', 'thoi_han' => 1],
                'thang' => ['ten' => 'Gói 1 tháng', 'thoi_han' => 30],
                'quy' => ['ten' => 'Gói 3 tháng', 'thoi_han' => 90],
                'nua-nam' => ['ten' => 'Gói 6 tháng', 'thoi_han' => 180],
                'nam' => ['ten' => 'Gói 1 năm', 'thoi_han' => 365],
                'pt' => ['ten' => 'Gói PT cá nhân', 'thoi_han' => 30]
            ];
            
            if (isset($packageMapping[$package_id])) {
                $ten_goi_mapping = $packageMapping[$package_id]['ten'];
                $thoi_han_ngay = $packageMapping[$package_id]['thoi_han'];
                
                // Tìm goi_tap_id dựa trên tên gói
                $stmt = $pdo->prepare("SELECT goi_tap_id FROM GoiTap WHERE ten_goi = :ten_goi AND trang_thai = 'Đang áp dụng' LIMIT 1");
                $stmt->execute([':ten_goi' => $ten_goi_mapping]);
                $existingGoi = $stmt->fetch();
                
                if ($existingGoi) {
                    $goi_tap_id = $existingGoi['goi_tap_id'];
                    error_log("Step 2: Found GoiTap by name - goi_tap_id: {$goi_tap_id}, ten_goi: {$ten_goi_mapping}");
                } else {
                    // Tạo mã gói tập tự động
                    $ma_goi_tap = 'GT' . date('Ymd') . rand(100, 999);
                    
                    // Xác định loại gói
                    $loai_goi = 'Cơ bản';
                    if (strpos($ten_goi_mapping, 'PT') !== false) {
                        $loai_goi = 'PT cá nhân';
                    } elseif ($thoi_han_ngay >= 180) {
                        $loai_goi = 'VIP';
                    } elseif ($thoi_han_ngay >= 90) {
                        $loai_goi = 'Nâng cao';
                    }
                    
                    // Insert gói tập mới nếu chưa có
                    $stmt = $pdo->prepare("INSERT INTO GoiTap (ma_goi_tap, ten_goi, mo_ta, thoi_han_ngay, gia_tien, loai_goi, trang_thai)
                                           VALUES (:ma_goi, :ten_goi, :mo_ta, :thoi_han, :gia, :loai, 'Đang áp dụng')");
                    $stmt->execute([
                        ':ma_goi' => $ma_goi_tap,
                        ':ten_goi' => $ten_goi_mapping,
                        ':mo_ta' => $package_name,
                        ':thoi_han' => $thoi_han_ngay,
                        ':gia' => $amount,
                        ':loai' => $loai_goi
                    ]);
                    $goi_tap_id = $pdo->lastInsertId();
                    error_log("Step 2a: GoiTap created - goi_tap_id: {$goi_tap_id}, ma_goi_tap: {$ma_goi_tap}, ten_goi: {$ten_goi_mapping}");
                }
            } else {
                // Không tìm thấy mapping, thử tìm theo tên gói từ form
                $stmt = $pdo->prepare("SELECT goi_tap_id, thoi_han_ngay FROM GoiTap WHERE ten_goi = :ten_goi AND trang_thai = 'Đang áp dụng' LIMIT 1");
                $stmt->execute([':ten_goi' => $package_name]);
                $goiTapByName = $stmt->fetch();
                
                if ($goiTapByName) {
                    $goi_tap_id = $goiTapByName['goi_tap_id'];
                    $thoi_han_ngay = intval($goiTapByName['thoi_han_ngay']);
                    error_log("Step 2b: Found GoiTap by package_name - goi_tap_id: {$goi_tap_id}, ten_goi: {$package_name}");
                } else {
                    throw new Exception("Không tìm thấy gói tập: {$package_name}");
                }
            }
        }
        
        // Kiểm tra goi_tap_id cuối cùng
        if (!$goi_tap_id) {
            throw new Exception("Không thể xác định gói tập. package_id: {$package_id}, package_name: {$package_name}");
        }
        
        error_log("Step 2: GoiTap determined - goi_tap_id: {$goi_tap_id}, ten_goi: {$ten_goi_mapping}, thoi_han: {$thoi_han_ngay}");
        
        // Insert vào ChiTietHoaDon (luôn luôn có thông tin để hiển thị)
        error_log("Step 3: Inserting ChiTietHoaDon - hoa_don_id: {$hoa_don_id}, goi_tap_id: {$goi_tap_id}, ten_goi: {$package_name}, amount: {$amount}");
        
        $stmt = $pdo->prepare("INSERT INTO ChiTietHoaDon (
            hoa_don_id, goi_tap_id, ten_goi, so_luong, don_gia, thanh_tien
        ) VALUES (
            :hd_id, :goi_id, :ten, 1, :gia, :thanh_tien
        )");
        
        try {
            $stmt->execute([
                ':hd_id' => $hoa_don_id,
                ':goi_id' => $goi_tap_id,
                ':ten' => $package_name,
                ':gia' => $amount,
                ':thanh_tien' => $amount
            ]);
        } catch (PDOException $e) {
            error_log("ERROR inserting ChiTietHoaDon: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            throw new Exception("Failed to insert ChiTietHoaDon: " . $e->getMessage());
        }
        
        $chi_tiet_id = $pdo->lastInsertId();
        error_log("Step 3: ChiTietHoaDon created - chi_tiet_id: {$chi_tiet_id}, hoa_don_id: {$hoa_don_id}, goi_tap_id: {$goi_tap_id}");
        
        // 3. Tính ngày bắt đầu và kết thúc dựa trên gói
        // Sử dụng thoi_han_ngay từ mapping (đã tính ở trên)
        if ($thoi_han_ngay <= 0) {
            // Fallback: Tính lại từ package_id nếu mapping không có
            switch ($package_id) {
                case 'ngay': $thoi_han_ngay = 1; break;
                case 'thang': $thoi_han_ngay = 30; break;
                case 'quy': $thoi_han_ngay = 90; break;
                case 'nua-nam': $thoi_han_ngay = 180; break;
                case 'nam': $thoi_han_ngay = 365; break;
                case 'pt': $thoi_han_ngay = 30; break;
                default: $thoi_han_ngay = 30; // Mặc định 30 ngày
            }
        }
        
        // Sử dụng CURDATE() trong SQL để đảm bảo nhất quán với database
        // ngay_bat_dau = CURDATE(), ngay_ket_thuc = CURDATE() + thoi_han_ngay days
        error_log("Step 4: Calculating dates - thoi_han: {$thoi_han_ngay} days from CURDATE()");
        
        // 4. Insert vào DangKyGoiTap (QUAN TRỌNG - không được bỏ qua nếu lỗi)
        // Sử dụng CURDATE() và DATE_ADD để tính ngày kết thúc trực tiếp trong SQL
        $stmt = $pdo->prepare("INSERT INTO DangKyGoiTap (
            khach_hang_id, goi_tap_id, hoa_don_id, ngay_dang_ky,
            ngay_bat_dau, ngay_ket_thuc, tong_tien, trang_thai
        ) VALUES (
            :kh_id, :goi_id, :hd_id, CURDATE(),
            CURDATE(), DATE_ADD(CURDATE(), INTERVAL :thoi_han DAY), :tong_tien, 'Đang hoạt động'
        )");
        
        // Kiểm tra foreign key constraints trước khi insert
        // Kiểm tra goi_tap_id có tồn tại trong GoiTap không
        $stmt_check_goi = $pdo->prepare("SELECT goi_tap_id FROM GoiTap WHERE goi_tap_id = :goi_id LIMIT 1");
        $stmt_check_goi->execute([':goi_id' => $goi_tap_id]);
        if (!$stmt_check_goi->fetch()) {
            throw new Exception("GoiTap with id {$goi_tap_id} does not exist. Cannot insert into DangKyGoiTap.");
        }
        
        error_log("Step 5: Inserting DangKyGoiTap - khach_hang_id: {$khach_hang_id}, goi_tap_id: {$goi_tap_id}, hoa_don_id: {$hoa_don_id}, thoi_han: {$thoi_han_ngay}, tong_tien: {$amount}");
        
        try {
            $stmt->execute([
                ':kh_id' => $khach_hang_id,
                ':goi_id' => $goi_tap_id,
                ':hd_id' => $hoa_don_id,
                ':thoi_han' => $thoi_han_ngay,
                ':tong_tien' => $amount
            ]);
        } catch (PDOException $e) {
            error_log("ERROR inserting into DangKyGoiTap: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            error_log("SQL Error Info: " . print_r($stmt->errorInfo(), true));
            throw new Exception("Failed to insert into DangKyGoiTap: " . $e->getMessage() . " | khach_hang_id: {$khach_hang_id}, goi_tap_id: {$goi_tap_id}, hoa_don_id: {$hoa_don_id}");
        }
        
        $dang_ky_id = $pdo->lastInsertId();
        
        // Kiểm tra DangKyGoiTap đã được tạo chưa
        if (!$dang_ky_id) {
            error_log("ERROR: lastInsertId() returned 0 after inserting into DangKyGoiTap. khach_hang_id: {$khach_hang_id}, goi_tap_id: {$goi_tap_id}");
            throw new Exception("Failed to create DangKyGoiTap. lastInsertId() returned 0. khach_hang_id: {$khach_hang_id}, goi_tap_id: {$goi_tap_id}, hoa_don_id: {$hoa_don_id}");
        }
        
        error_log("Step 5: DangKyGoiTap created - dang_ky_id: {$dang_ky_id}, khach_hang_id: {$khach_hang_id}, goi_tap_id: {$goi_tap_id}");
        
        // Kiểm tra lại dữ liệu đã được insert chưa
        $stmt_check = $pdo->prepare("SELECT dang_ky_id, khach_hang_id, goi_tap_id, trang_thai, ngay_bat_dau, ngay_ket_thuc 
                                      FROM DangKyGoiTap 
                                      WHERE dang_ky_id = :dk_id 
                                      AND khach_hang_id = :kh_id");
        $stmt_check->execute([
            ':dk_id' => $dang_ky_id,
            ':kh_id' => $khach_hang_id
        ]);
        $verifyData = $stmt_check->fetch();
        
        if (!$verifyData) {
            throw new Exception("Failed to verify DangKyGoiTap insert. dang_ky_id: {$dang_ky_id}, khach_hang_id: {$khach_hang_id}");
        }
        
        // Log để debug
        error_log("Payment successful - khach_hang_id: {$khach_hang_id}, goi_tap_id: {$goi_tap_id}, dang_ky_id: {$dang_ky_id}, ngay_bat_dau: {$verifyData['ngay_bat_dau']}, ngay_ket_thuc: {$verifyData['ngay_ket_thuc']}, trang_thai: {$verifyData['trang_thai']}");
        
        // Kiểm tra tất cả dữ liệu đã được lưu đúng trước khi commit
        if (!$hoa_don_id || !$goi_tap_id || !$dang_ky_id) {
            throw new Exception("Missing required IDs: hoa_don_id={$hoa_don_id}, goi_tap_id={$goi_tap_id}, dang_ky_id={$dang_ky_id}");
        }
        
        // Commit transaction - TẤT CẢ dữ liệu sẽ được lưu vào database tại đây
        error_log("Attempting to commit transaction...");
        try {
            if (!$pdo->commit()) {
                error_log("ERROR: Commit transaction returned false!");
                throw new Exception("Failed to commit transaction. Data may not be saved.");
            }
        } catch (PDOException $e) {
            error_log("ERROR: Commit transaction threw exception: " . $e->getMessage());
            throw new Exception("Failed to commit transaction: " . $e->getMessage());
        }
        
        error_log("Transaction committed successfully!");
        
        // Log thành công sau khi commit
        error_log("Payment COMPLETED and SAVED to database - hoa_don_id: {$hoa_don_id}, goi_tap_id: {$goi_tap_id}, dang_ky_id: {$dang_ky_id}, khach_hang_id: {$khach_hang_id}");
        
        // Kiểm tra lại dữ liệu đã được lưu đúng chưa (sau commit)
        $stmt_check_hd = $pdo->prepare("SELECT hoa_don_id, ma_hoa_don, trang_thai FROM HoaDon WHERE hoa_don_id = :hd_id LIMIT 1");
        $stmt_check_hd->execute([':hd_id' => $hoa_don_id]);
        $check_hd = $stmt_check_hd->fetch();
        
        if (!$check_hd) {
            error_log("ERROR: HoaDon not found after commit - hoa_don_id: {$hoa_don_id}");
            throw new Exception("HoaDon not found after commit. Transaction may have failed.");
        }
        
        $stmt_check_ct = $pdo->prepare("SELECT chi_tiet_id, ten_goi FROM ChiTietHoaDon WHERE hoa_don_id = :hd_id LIMIT 1");
        $stmt_check_ct->execute([':hd_id' => $hoa_don_id]);
        $check_ct = $stmt_check_ct->fetch();
        
        if (!$check_ct) {
            error_log("ERROR: ChiTietHoaDon not found after commit - hoa_don_id: {$hoa_don_id}");
            throw new Exception("ChiTietHoaDon not found after commit. Transaction may have failed.");
        }
        
        $stmt_check_dk = $pdo->prepare("SELECT dang_ky_id, trang_thai FROM DangKyGoiTap WHERE dang_ky_id = :dk_id LIMIT 1");
        $stmt_check_dk->execute([':dk_id' => $dang_ky_id]);
        $check_dk = $stmt_check_dk->fetch();
        
        if (!$check_dk) {
            error_log("ERROR: DangKyGoiTap not found after commit - dang_ky_id: {$dang_ky_id}");
            throw new Exception("DangKyGoiTap not found after commit. Transaction may have failed.");
        }
        
        error_log("Payment verification SUCCESS - HoaDon: {$check_hd['ma_hoa_don']}, ChiTietHoaDon: {$check_ct['ten_goi']}, DangKyGoiTap: {$check_dk['dang_ky_id']}");
        
        // Kiểm tra lại dữ liệu đã được lưu đúng chưa
        $stmt_verify = $pdo->prepare("SELECT hd.hoa_don_id, hd.ma_hoa_don, hd.trang_thai, COUNT(cthd.chi_tiet_id) as so_chi_tiet
                                       FROM HoaDon hd
                                       LEFT JOIN ChiTietHoaDon cthd ON hd.hoa_don_id = cthd.hoa_don_id
                                       WHERE hd.hoa_don_id = :hd_id
                                       GROUP BY hd.hoa_don_id");
        $stmt_verify->execute([':hd_id' => $hoa_don_id]);
        $verify_result = $stmt_verify->fetch();
        
        if ($verify_result) {
            error_log("Payment verification - hoa_don_id: {$verify_result['hoa_don_id']}, ma_hoa_don: {$verify_result['ma_hoa_don']}, trang_thai: {$verify_result['trang_thai']}, so_chi_tiet: {$verify_result['so_chi_tiet']}");
        } else {
            error_log("ERROR: Payment verification failed - hoa_don_id: {$hoa_don_id} not found after commit!");
        }
        
        // Tự động lưu phương thức thanh toán khi thanh toán (mọi lần)
        try {
            // Tạo bảng PhuongThucThanhToan nếu chưa có
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
            
            // Kiểm tra xem đây có phải là lần mua gói tập đầu tiên không
            $stmt_check_first = $pdo->prepare("SELECT COUNT(*) as so_lan_mua 
                                                FROM DangKyGoiTap 
                                                WHERE khach_hang_id = :kh_id 
                                                AND dang_ky_id != :dk_id");
            $stmt_check_first->execute([
                ':kh_id' => $khach_hang_id,
                ':dk_id' => $dang_ky_id
            ]);
            $check_first = $stmt_check_first->fetch();
            $is_first_purchase = ($check_first['so_lan_mua'] == 0);
            
            // Map phương thức thanh toán sang tên hiển thị
            $ten_hien_thi_mapping = [
                'Chuyển khoản' => 'Ngân hàng',
                'Thẻ' => 'Visa',
                'Ví điện tử' => $payment_method_raw // Giữ nguyên Momo, ZaloPay, PayPal
            ];
            
            $ten_hien_thi = $ten_hien_thi_mapping[$payment_method] ?? $payment_method_raw;
            
            // Kiểm tra xem phương thức này đã tồn tại chưa
            $stmt_check_existing = $pdo->prepare("SELECT phuong_thuc_id FROM PhuongThucThanhToan 
                                                  WHERE khach_hang_id = :kh_id 
                                                  AND loai_phuong_thuc = :loai 
                                                  AND ten_hien_thi = :ten");
            $stmt_check_existing->execute([
                ':kh_id' => $khach_hang_id,
                ':loai' => $payment_method,
                ':ten' => $ten_hien_thi
            ]);
            
            // Nếu phương thức chưa tồn tại, lưu vào database
            if (!$stmt_check_existing->fetch()) {
                // Nếu là lần đầu mua, đánh dấu là mặc định và bỏ mặc định của các phương thức khác
                if ($is_first_purchase) {
                    $stmt_update_default = $pdo->prepare("UPDATE PhuongThucThanhToan 
                                                          SET mac_dinh = 0 
                                                          WHERE khach_hang_id = :kh_id");
                    $stmt_update_default->execute([':kh_id' => $khach_hang_id]);
                }
                
                // Lưu phương thức mới
                $mac_dinh_value = $is_first_purchase ? 1 : 0;
                $stmt_save = $pdo->prepare("INSERT INTO PhuongThucThanhToan 
                                            (khach_hang_id, loai_phuong_thuc, ten_hien_thi, thong_tin_chi_tiet, mac_dinh) 
                                            VALUES (:kh_id, :loai, :ten, :thong_tin, :mac_dinh)");
                $stmt_save->execute([
                    ':kh_id' => $khach_hang_id,
                    ':loai' => $payment_method,
                    ':ten' => $ten_hien_thi,
                    ':thong_tin' => $payment_method_raw,
                    ':mac_dinh' => $mac_dinh_value
                ]);
                
                error_log("Payment method saved - khach_hang_id: {$khach_hang_id}, method: {$ten_hien_thi}, type: {$payment_method}, is_default: {$mac_dinh_value}");
            } else {
                error_log("Payment method already exists - khach_hang_id: {$khach_hang_id}, method: {$ten_hien_thi}");
            }
        } catch (Exception $e) {
            // Log lỗi nhưng không làm gián đoạn quá trình thanh toán
            error_log("Error saving payment method: " . $e->getMessage());
        }
        
        // Redirect với thông báo thành công và force reload để cập nhật dữ liệu
        // Hiển thị modal thành công thay vì modal lịch sử thanh toán
        header('Location: packages.html?msg=' . urlencode('Thanh toán thành công! Gói tập của bạn đã được kích hoạt và lưu vào hệ thống.') . '&type=success&refresh=1');
        exit;
        
    } catch (Throwable $e) {
        // Rollback nếu có lỗi
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
            error_log("Transaction rolled back due to error: " . $e->getMessage());
        }
        throw $e;
    }
    
} catch (Throwable $e) {
    $error_msg = $e->getMessage();
    error_log('Payment error: ' . $error_msg);
    error_log('Payment error trace: ' . $e->getTraceAsString());
    
    // Hiển thị lỗi chi tiết hơn (chỉ trong development)
    $user_error_msg = 'Có lỗi xảy ra khi xử lý thanh toán. Vui lòng thử lại.';
    if (strpos($error_msg, 'does not exist') !== false) {
        $user_error_msg = 'Lỗi: Gói tập không tồn tại trong hệ thống. Vui lòng liên hệ admin.';
    } elseif (strpos($error_msg, 'Failed to insert') !== false) {
        $user_error_msg = 'Lỗi: Không thể lưu đăng ký gói tập. Vui lòng kiểm tra lại thông tin.';
    } elseif (strpos($error_msg, 'foreign key') !== false) {
        $user_error_msg = 'Lỗi: Dữ liệu không hợp lệ. Vui lòng kiểm tra lại thông tin gói tập.';
    }
    
    header('Location: packages.html?msg=' . urlencode($user_error_msg) . '&type=error');
    exit;
}

