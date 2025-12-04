<?php
// Kết nối database
require_once __DIR__ . '/../../../Database/db.php';

// Sử dụng kết nối từ db.php
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$message = "";
$messageType = "";

// ====================== XỬ LÝ BẢNG LƯƠNG (AJAX) - ĐẶT TRƯỚC TIÊN ======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_bangluong') {
    header('Content-Type: application/json');
    
    $nhanVienId = isset($_POST['nhanVienId']) ? intval($_POST['nhanVienId']) : 0;
    $thang = isset($_POST['thang']) ? intval($_POST['thang']) : 0;
    $nam = isset($_POST['nam']) ? intval($_POST['nam']) : 0;

    if (!$nhanVienId || !$thang || !$nam) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin', 'rows' => []]);
        $conn->close();
        exit;
    }

    if ($thang < 1 || $thang > 12) {
        echo json_encode(['success' => false, 'message' => 'Tháng không hợp lệ (1-12)', 'rows' => []]);
        $conn->close();
        exit;
    }

    // SỬA: Thêm các cột luong_co_ban, phu_cap theo SQL schema
    $sql = "SELECT bang_luong_id, nhan_vien_id, thang, nam, 
            luong_co_ban, phu_cap, thuong, khau_tru, thuc_linh, 
            ngay_thanh_toan, trang_thai
            FROM BangLuong 
            WHERE nhan_vien_id = ? AND thang = ? AND nam = ?
            ORDER BY nam DESC, thang DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Lỗi prepare statement: ' . $conn->error, 'rows' => []]);
        $conn->close();
        exit;
    }

    $stmt->bind_param("iii", $nhanVienId, $thang, $nam);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Lỗi execute: ' . $stmt->error, 'rows' => []]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'bang_luong_id' => $row['bang_luong_id'],
            'thang' => intval($row['thang']),
            'nam' => intval($row['nam']),
            'luong' => floatval($row['luong_co_ban']) + floatval($row['phu_cap']), // SỬA: Tính tổng lương
            'thuong' => floatval($row['thuong']),
            'khau_tru' => floatval($row['khau_tru']),
            'thuc_linh' => floatval($row['thuc_linh']),
            'ngay_thanh_toan' => $row['ngay_thanh_toan'],
            'trang_thai' => $row['trang_thai']
        ];
    }

    $stmt->close();

    if (count($rows) > 0) {
        echo json_encode(['success' => true, 'rows' => $rows]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không có dữ liệu', 'rows' => []]);
    }
    
    $conn->close();
    exit;
}

// ====================== XỬ LÝ CHẤM CÔNG ======================
if (isset($_GET['nhan_vien_id'])) {
    $nhanVienId = intval($_GET['nhan_vien_id']);

    $sql = "SELECT cham_cong_id, nhan_vien_id, ngay_cham_cong, gio_vao, gio_ra, so_gio_lam, trang_thai, ghi_chu
            FROM ChamCong
            WHERE nhan_vien_id = ?
            ORDER BY ngay_cham_cong DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $nhanVienId);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ====================== THÊM NHÂN VIÊN ======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $tenDangNhap = trim($_POST['tenDangNhap']);
    $matKhau = trim($_POST['matKhau']);
    $hoTen = trim($_POST['hoTen']);
    $email = trim($_POST['email']);
    $sdt = trim($_POST['sdt']);
    $cccd = trim($_POST['cccd']);
    $diaChi = trim($_POST['diaChi']);
    $ngaySinh = trim($_POST['ngaySinh']);
    $gioiTinh = trim($_POST['gioiTinh']);
    $vaiTro = trim($_POST['vaiTro']);
    $ngayVaoLam = trim($_POST['ngayVaoLam']);
    $luongCoBan = trim($_POST['luongCoBan']);
    $phongTapId = !empty($_POST['phongTapId']) ? intval($_POST['phongTapId']) : null;

    $errors = [];

    // Kiểm tra rỗng
    if (empty($tenDangNhap)) $errors[] = "⚠️ Tên đăng nhập không được để trống.";
    if (empty($matKhau)) $errors[] = "⚠️ Mật khẩu không được để trống.";
    if (empty($hoTen)) $errors[] = "⚠️ Họ tên không được để trống.";
    if (empty($email)) $errors[] = "⚠️ Email không được để trống.";
    if (empty($vaiTro)) $errors[] = "⚠️ Vai trò không được để trống.";
    if (empty($ngayVaoLam)) $errors[] = "⚠️ Ngày vào làm không được để trống.";

    // Kiểm tra định dạng
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "❌ Email không hợp lệ.";
    }
    if (!empty($sdt) && !preg_match('/^[0-9]{10,11}$/', $sdt)) {
        $errors[] = "❌ Số điện thoại phải có 10-11 chữ số.";
    }
    if (!empty($cccd) && !preg_match('/^[0-9]{12}$/', $cccd)) {
        $errors[] = "❌ CCCD phải có đúng 12 chữ số.";
    }
    if (!empty($luongCoBan) && (!is_numeric($luongCoBan) || $luongCoBan <= 0)) {
        $errors[] = "❌ Lương cơ bản phải là số dương.";
    }

    // Kiểm tra trùng tên đăng nhập
    $checkUser = $conn->prepare("SELECT * FROM TaiKhoan WHERE ten_dang_nhap = ?");
    $checkUser->bind_param("s", $tenDangNhap);
    $checkUser->execute();
    if ($checkUser->get_result()->num_rows > 0) {
        $errors[] = "❌ Tên đăng nhập đã tồn tại.";
    }

    // Kiểm tra trùng EMAIL
    if (!empty($email)) {
        $checkEmail = $conn->prepare("SELECT nhan_vien_id FROM NhanVien WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0) {
            $errors[] = "❌ Email đã được sử dụng bởi nhân viên khác.";
        }
    }

    // Kiểm tra trùng SDT
    if (!empty($sdt)) {
        $checkSdt = $conn->prepare("SELECT nhan_vien_id FROM NhanVien WHERE sdt = ?");
        $checkSdt->bind_param("s", $sdt);
        $checkSdt->execute();
        if ($checkSdt->get_result()->num_rows > 0) {
            $errors[] = "❌ Số điện thoại đã được sử dụng bởi nhân viên khác.";
        }
    }

    // Kiểm tra trùng CCCD
    if (!empty($cccd)) {
        $checkCccd = $conn->prepare("SELECT nhan_vien_id FROM NhanVien WHERE cccd = ?");
        $checkCccd->bind_param("s", $cccd);
        $checkCccd->execute();
        if ($checkCccd->get_result()->num_rows > 0) {
            $errors[] = "❌ CCCD đã được sử dụng bởi nhân viên khác.";
        }
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $messageType = "error";
    } else {
        $conn->begin_transaction();
        try {
            // Thêm tài khoản
            $sqlTaiKhoan = "INSERT INTO TaiKhoan (ten_dang_nhap, mat_khau, loai_tai_khoan) 
                            VALUES (?, ?, 'Nhân viên')";
            $stmtTaiKhoan = $conn->prepare($sqlTaiKhoan);
            $stmtTaiKhoan->bind_param("ss", $tenDangNhap, $matKhau);
            $stmtTaiKhoan->execute();

            // Thêm nhân viên với phong_tap_id
            $sqlNhanVien = "INSERT INTO NhanVien (ten_dang_nhap, ho_ten, email, sdt, cccd, dia_chi, ngay_sinh, 
                            gioi_tinh, vai_tro, ngay_vao_lam, luong_co_ban, phong_tap_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtNhanVien = $conn->prepare($sqlNhanVien);
            $stmtNhanVien->bind_param("ssssssssssdi", $tenDangNhap, $hoTen, $email, $sdt, $cccd, $diaChi, 
                                      $ngaySinh, $gioiTinh, $vaiTro, $ngayVaoLam, $luongCoBan, $phongTapId);
            $stmtNhanVien->execute();

            $conn->commit();
            $message = "✅ Thêm nhân viên thành công!";
            $messageType = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "❌ Lỗi khi thêm nhân viên: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// ====================== CẬP NHẬT NHÂN VIÊN ======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id']);
    $hoTen = trim($_POST['hoTen']);
    $email = trim($_POST['email']);
    $sdt = trim($_POST['sdt']);
    $cccd = trim($_POST['cccd']);
    $diaChi = trim($_POST['diaChi']);
    $ngaySinh = trim($_POST['ngaySinh']);
    $gioiTinh = trim($_POST['gioiTinh']);
    $vaiTro = trim($_POST['vaiTro']);
    $ngayVaoLam = trim($_POST['ngayVaoLam']);
    $luongCoBan = trim($_POST['luongCoBan']);
    $trangThai = trim($_POST['trangThai']);
    $phongTapId = !empty($_POST['phongTapId']) ? intval($_POST['phongTapId']) : null;
    
    $errors = [];

    // Kiểm tra định dạng
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "❌ Email không hợp lệ.";
    }
    if (!empty($sdt) && !preg_match('/^[0-9]{10,11}$/', $sdt)) {
        $errors[] = "❌ Số điện thoại phải có 10-11 chữ số.";
    }
    if (!empty($cccd) && !preg_match('/^[0-9]{12}$/', $cccd)) {
        $errors[] = "❌ CCCD phải có đúng 12 chữ số.";
    }
    if (!empty($luongCoBan) && (!is_numeric($luongCoBan) || $luongCoBan <= 0)) {
        $errors[] = "❌ Lương cơ bản phải là số dương.";
    }

    // Kiểm tra trùng EMAIL
    if (!empty($email)) {
        $checkEmail = $conn->prepare("SELECT nhan_vien_id FROM NhanVien WHERE email = ? AND nhan_vien_id != ?");
        $checkEmail->bind_param("si", $email, $id);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0) {
            $errors[] = "❌ Email đã được sử dụng bởi nhân viên khác.";
        }
    }

    // Kiểm tra trùng SDT
    if (!empty($sdt)) {
        $checkSdt = $conn->prepare("SELECT nhan_vien_id FROM NhanVien WHERE sdt = ? AND nhan_vien_id != ?");
        $checkSdt->bind_param("si", $sdt, $id);
        $checkSdt->execute();
        if ($checkSdt->get_result()->num_rows > 0) {
            $errors[] = "❌ Số điện thoại đã được sử dụng bởi nhân viên khác.";
        }
    }

    // Kiểm tra trùng CCCD
    if (!empty($cccd)) {
        $checkCccd = $conn->prepare("SELECT nhan_vien_id FROM NhanVien WHERE cccd = ? AND nhan_vien_id != ?");
        $checkCccd->bind_param("si", $cccd, $id);
        $checkCccd->execute();
        if ($checkCccd->get_result()->num_rows > 0) {
            $errors[] = "❌ CCCD đã được sử dụng bởi nhân viên khác.";
        }
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $messageType = "error";
    } else {
        $sql = "UPDATE NhanVien SET ho_ten=?, email=?, sdt=?, cccd=?, dia_chi=?, ngay_sinh=?, 
                gioi_tinh=?, vai_tro=?, ngay_vao_lam=?, luong_co_ban=?, trang_thai=?, phong_tap_id=? 
                WHERE nhan_vien_id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssdsis", $hoTen, $email, $sdt, $cccd, $diaChi, $ngaySinh, 
                          $gioiTinh, $vaiTro, $ngayVaoLam, $luongCoBan, $trangThai, $phongTapId, $id);
        
        if ($stmt->execute()) {
            $message = "✅ Cập nhật nhân viên thành công!";
            $messageType = "success";
        } else {
            $message = "❌ Lỗi: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    }
}

// ====================== XÓA NHÂN VIÊN ======================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $conn->begin_transaction();
    try {
        $sqlGetUsername = "SELECT ten_dang_nhap FROM NhanVien WHERE nhan_vien_id = ?";
        $stmtGet = $conn->prepare($sqlGetUsername);
        $stmtGet->bind_param("i", $id);
        $stmtGet->execute();
        $result = $stmtGet->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $tenDangNhap = $row['ten_dang_nhap'];

            $sqlDeleteNV = "DELETE FROM NhanVien WHERE nhan_vien_id=?";
            $stmtDeleteNV = $conn->prepare($sqlDeleteNV);
            $stmtDeleteNV->bind_param("i", $id);
            $stmtDeleteNV->execute();

            $sqlDeleteTK = "DELETE FROM TaiKhoan WHERE ten_dang_nhap=?";
            $stmtDeleteTK = $conn->prepare($sqlDeleteTK);
            $stmtDeleteTK->bind_param("s", $tenDangNhap);
            $stmtDeleteTK->execute();

            $conn->commit();
            $message = "✅ Xóa nhân viên và tài khoản thành công!";
            $messageType = "success";
        } else {
            $message = "❌ Không tìm thấy nhân viên cần xóa!";
            $messageType = "error";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = "❌ Không thể xóa nhân viên này do có dữ liệu liên quan!";
        $messageType = "error";
    }
}

// ====================== THÊM CHẤM CÔNG ======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_chamcong') {
    $nhanVienId = trim($_POST['nhanVienId']);
    $ngayChamCong = trim($_POST['ngayChamCong']);
    $gioVao = trim($_POST['gioVao']);
    $gioRa = trim($_POST['gioRa']);
    $trangThai = trim($_POST['trangThai']);
    $ghiChu = trim($_POST['ghiChu']);
    $errors = [];

    if (empty($nhanVienId)) $errors[] = "⚠️ Chưa chọn nhân viên.";
    if (empty($ngayChamCong)) $errors[] = "⚠️ Ngày chấm công không được để trống.";
    if (!empty($gioVao) && !preg_match('/^\d{2}:\d{2}$/', $gioVao)) $errors[] = "❌ Giờ vào không hợp lệ (hh:mm).";
    if (!empty($gioRa) && !preg_match('/^\d{2}:\d{2}$/', $gioRa)) $errors[] = "❌ Giờ ra không hợp lệ (hh:mm).";

    $check = $conn->prepare("SELECT * FROM ChamCong WHERE nhan_vien_id = ? AND ngay_cham_cong = ?");
    $check->bind_param("is", $nhanVienId, $ngayChamCong);
    $check->execute();
    if ($check->get_result()->num_rows > 0) $errors[] = "❌ Nhân viên này đã được chấm công trong ngày $ngayChamCong.";

    if (!empty($errors)) {
        $response = ['success' => false, 'message' => implode("\n", $errors)];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $soGioLam = null;
    if (!empty($gioVao) && !empty($gioRa)) {
        $timeVao = strtotime($gioVao);
        $timeRa = strtotime($gioRa);

        if ($timeRa < $timeVao) {
            $timeRa += 24 * 3600;
        }

        $diffHours = ($timeRa - $timeVao) / 3600;
        if ($diffHours < 8) {
            $response = ['success' => false, 'message' => "❌ Giờ ra phải cách giờ vào ít nhất 8 tiếng."];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } else {
            $soGioLam = round($diffHours, 2);
        }
    }

    $timestamp = strtotime($ngayChamCong);
    $thang = intval(date("m", $timestamp));
    $nam   = intval(date("Y", $timestamp));

    $sqlLuong = "SELECT luong_co_ban FROM NhanVien WHERE nhan_vien_id = ?";
    $stmtLuong = $conn->prepare($sqlLuong);
    $stmtLuong->bind_param("i", $nhanVienId);
    $stmtLuong->execute();
    $resultLuong = $stmtLuong->get_result();

    if ($resultLuong->num_rows == 0) {
        $response = ['success' => false, 'message' => "❌ Không tìm thấy thông tin lương cơ bản."];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $luongCoBan = floatval($resultLuong->fetch_assoc()['luong_co_ban']);
    $luong1h = $luongCoBan / 26 / 8;
    $luongcc = $luong1h * $soGioLam;

    $khauTruThem = ($trangThai == "Đi muộn") ? 50000 : 0;

    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO ChamCong (nhan_vien_id, ngay_cham_cong, gio_vao, gio_ra, so_gio_lam, trang_thai, ghi_chu)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssdss", $nhanVienId, $ngayChamCong, $gioVao, $gioRa, $soGioLam, $trangThai, $ghiChu);
        $stmt->execute();

        // SỬA: Kiểm tra và cập nhật bảng lương theo đúng schema
        $sqlCheckLuong = "SELECT * FROM BangLuong WHERE nhan_vien_id = ? AND thang = ? AND nam = ?";
        $stmtCheckLuong = $conn->prepare($sqlCheckLuong);
        $stmtCheckLuong->bind_param("iii", $nhanVienId, $thang, $nam);
        $stmtCheckLuong->execute();
        $resultCheck = $stmtCheckLuong->get_result();

        if ($resultCheck->num_rows > 0) {
            // CẬP NHẬT bản ghi có sẵn
            $currentRow = $resultCheck->fetch_assoc();
            $currentLuongCoBan = floatval($currentRow['luong_co_ban']);
            $currentPhuCap = floatval($currentRow['phu_cap']);
            $currentKhauTru = floatval($currentRow['khau_tru']);
            
            // Cộng thêm lương công vào lương cơ bản
            $newLuongCoBan = $currentLuongCoBan + $luongcc;
            $newKhauTru = $currentKhauTru + $khauTruThem;
            $newThucLinh = $newLuongCoBan + $currentPhuCap - $newKhauTru;
            
            $sqlUpdateLuong = "
                UPDATE BangLuong 
                SET luong_co_ban = ?, 
                    khau_tru = ?, 
                    thuc_linh = ?,
                    ngay_thanh_toan = NULL,
                    trang_thai = 'Chưa thanh toán'
                WHERE nhan_vien_id = ? AND thang = ? AND nam = ?";
            $stmtUpdate = $conn->prepare($sqlUpdateLuong);
            $stmtUpdate->bind_param("dddiii", $newLuongCoBan, $newKhauTru, $newThucLinh, $nhanVienId, $thang, $nam);
            $stmtUpdate->execute();
        } else {
            // THÊM MỚI bản ghi lương
            $thucLinh = $luongcc - $khauTruThem;
            $phuCap = 0; // Mặc định phụ cấp = 0
            
            $sqlInsertLuong = "
                INSERT INTO BangLuong (nhan_vien_id, thang, nam, luong_co_ban, phu_cap, thuong, khau_tru, thuc_linh, ngay_thanh_toan, trang_thai, ghi_chu)
                VALUES (?, ?, ?, ?, ?, 0, ?, ?, NULL, 'Chưa thanh toán', NULL)";
            $stmtInsert = $conn->prepare($sqlInsertLuong);
            $stmtInsert->bind_param("iiidddd", $nhanVienId, $thang, $nam, $luongcc, $phuCap, $khauTruThem, $thucLinh);
            $stmtInsert->execute();
        }

        $conn->commit();

        $response = [
            'success' => true,
            'message' => "✅ Thêm chấm công & cập nhật lương tháng $thang/$nam thành công!",
            'so_gio_lam' => $soGioLam,
            'luong_cong' => $luongcc
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $response = ['success' => false, 'message' => '❌ Lỗi hệ thống: ' . $e->getMessage()];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// ====================== CẬP NHẬT THANH TOÁN LƯƠNG ======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'thanh_toan_luong') {
    header('Content-Type: application/json');
    
    $bangLuongId = isset($_POST['bangLuongId']) ? intval($_POST['bangLuongId']) : 0;
    $phuongThuc = isset($_POST['phuongThuc']) ? trim($_POST['phuongThuc']) : 'Tiền mặt';

    if (!$bangLuongId) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bảng lương']);
        $conn->close();
        exit;
    }

    // Kiểm tra xem bản ghi có tồn tại không
    $sqlCheck = "SELECT * FROM BangLuong WHERE bang_luong_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $bangLuongId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy bản ghi lương']);
        $conn->close();
        exit;
    }

    $currentRow = $resultCheck->fetch_assoc();
    
    // Kiểm tra xem đã thanh toán chưa
    if ($currentRow['trang_thai'] == 'Đã thanh toán') {
        echo json_encode(['success' => false, 'message' => 'Lương đã được thanh toán trước đó']);
        $conn->close();
        exit;
    }

    // Cập nhật trạng thái thanh toán với phương thức
    $ngayThanhToan = date('Y-m-d');
    $phuongThucThanhToan = in_array($phuongThuc, ['Tiền mặt', 'Chuyển khoản', 'Ví điện tử']) ? $phuongThuc : 'Tiền mặt';
    
    $sqlUpdate = "UPDATE BangLuong 
                  SET trang_thai = 'Đã thanh toán', 
                      ngay_thanh_toan = ?,
                      ghi_chu = CONCAT(COALESCE(ghi_chu, ''), ' | Phương thức: ', ?)
                  WHERE bang_luong_id = ?";
    
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ssi", $ngayThanhToan, $phuongThucThanhToan, $bangLuongId);
    
    if ($stmtUpdate->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => '✅ Đã thanh toán lương thành công!',
            'ngay_thanh_toan' => $ngayThanhToan,
            'phuong_thuc' => $phuongThucThanhToan
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $stmtUpdate->error]);
    }
    
    $stmtUpdate->close();
    $conn->close();
    exit;
}

// ====================== LẤY DANH SÁCH PHÒNG TẬP ======================
$sqlPhongTap = "SELECT phong_tap_id, ma_phong_tap, ten_phong_tap FROM phongtap WHERE trang_thai = 'Hoạt động' ORDER BY ten_phong_tap";
$resultPhongTap = $conn->query($sqlPhongTap);
$phongTapList = [];
while ($row = $resultPhongTap->fetch_assoc()) {
    $phongTapList[] = $row;
}

// ====================== TÌM KIẾM VÀ LỌC ======================
$searchTerm = "";
$genderFilter = "";
$roleFilter = "";
$statusFilter = "";
$whereConditions = [];
$params = [];
$types = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $whereConditions[] = "(ten_dang_nhap LIKE ? OR ho_ten LIKE ? OR email LIKE ? OR sdt LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "ssss";
}

if (isset($_GET['gender']) && !empty($_GET['gender'])) {
    $genderFilter = $_GET['gender'];
    $whereConditions[] = "gioi_tinh = ?";
    $params[] = $genderFilter;
    $types .= "s";
}

if (isset($_GET['role']) && !empty($_GET['role'])) {
    $roleFilter = $_GET['role'];
    $whereConditions[] = "vai_tro = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $statusFilter = $_GET['status'];
    $whereConditions[] = "trang_thai = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Lấy thông tin nhân viên kèm tên phòng tập
$sql = "SELECT nv.*, pt.ten_phong_tap, pt.ma_phong_tap 
        FROM NhanVien nv 
        LEFT JOIN phongtap pt ON nv.phong_tap_id = pt.phong_tap_id 
        WHERE nv.vai_tro != 'Admin'";

if (!empty($whereConditions)) {
    $sql .= " AND " . implode(" AND ", $whereConditions);
}
$sql .= " ORDER BY nv.nhan_vien_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>