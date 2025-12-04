<?php
// fix_missing_registrations.php - Script để chèn lại đăng ký gói tập từ các hóa đơn đã thanh toán
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    die('Vui lòng đăng nhập để chạy script này.');
}

$username = $_SESSION['user']['username'];

echo "<h2>Chèn lại Đăng ký Gói Tập từ Hóa Đơn</h2>";
echo "<p>Username: {$username}</p>";
echo "<hr>";

try {
    // 1. Lấy khach_hang_id
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        die("Không tìm thấy khách hàng với username: {$username}");
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // 2. Lấy tất cả hóa đơn đã thanh toán nhưng chưa có đăng ký gói tập
    $stmt = $pdo->prepare("SELECT hd.hoa_don_id, hd.ma_hoa_don, hd.ngay_lap, hd.tong_tien, cthd.goi_tap_id, cthd.ten_goi
                           FROM HoaDon hd
                           LEFT JOIN ChiTietHoaDon cthd ON hd.hoa_don_id = cthd.hoa_don_id
                           LEFT JOIN DangKyGoiTap dk ON hd.hoa_don_id = dk.hoa_don_id
                           WHERE hd.khach_hang_id = :kh_id 
                           AND hd.trang_thai = 'Đã thanh toán'
                           AND dk.dang_ky_id IS NULL
                           ORDER BY hd.hoa_don_id DESC");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    $hoaDons = $stmt->fetchAll();
    
    if (count($hoaDons) === 0) {
        echo "<p style='color: green;'>✅ Tất cả hóa đơn đã có đăng ký gói tập!</p>";
        echo "<p><a href='goitap/check_package.php'>Quay lại kiểm tra</a></p>";
        exit;
    }
    
    echo "<h3>Tìm thấy " . count($hoaDons) . " hóa đơn chưa có đăng ký gói tập:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Hóa Đơn ID</th><th>Mã HĐ</th><th>Ngày</th><th>Tổng Tiền</th><th>Gói Tập ID</th><th>Tên Gói</th><th>Trạng Thái</th></tr>";
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($hoaDons as $hd) {
        $hoa_don_id = $hd['hoa_don_id'];
        $ma_hoa_don = $hd['ma_hoa_don'];
        $ngay_lap = $hd['ngay_lap'];
        $tong_tien = $hd['tong_tien'];
        $goi_tap_id = $hd['goi_tap_id'];
        $ten_goi = $hd['ten_goi'];
        
        // Nếu không có goi_tap_id, thử tìm dựa trên ten_goi
        if (!$goi_tap_id && $ten_goi) {
            $stmt_goi = $pdo->prepare("SELECT goi_tap_id FROM GoiTap WHERE ten_goi = :ten_goi LIMIT 1");
            $stmt_goi->execute([':ten_goi' => $ten_goi]);
            $goi = $stmt_goi->fetch();
            if ($goi) {
                $goi_tap_id = $goi['goi_tap_id'];
            }
        }
        
        // Nếu vẫn không có goi_tap_id, dùng giá trị mặc định (1)
        if (!$goi_tap_id) {
            $goi_tap_id = 1;
        }
        
        // Tính ngày bắt đầu và kết thúc
        // Lấy thoi_han_ngay từ GoiTap
        $stmt_thoi_han = $pdo->prepare("SELECT thoi_han_ngay FROM GoiTap WHERE goi_tap_id = :goi_id LIMIT 1");
        $stmt_thoi_han->execute([':goi_id' => $goi_tap_id]);
        $thoi_han = $stmt_thoi_han->fetch();
        $thoi_han_ngay = $thoi_han ? $thoi_han['thoi_han_ngay'] : 30; // Mặc định 30 ngày
        
        $ngay_bat_dau = $ngay_lap; // Ngày bắt đầu = ngày lập hóa đơn
        $ngay_ket_thuc = date('Y-m-d', strtotime("{$ngay_lap} +{$thoi_han_ngay} days"));
        
        // Kiểm tra xem đã có đăng ký gói tập cho hóa đơn này chưa
        $stmt_check = $pdo->prepare("SELECT dang_ky_id FROM DangKyGoiTap WHERE hoa_don_id = :hd_id LIMIT 1");
        $stmt_check->execute([':hd_id' => $hoa_don_id]);
        if ($stmt_check->fetch()) {
            echo "<tr>";
            echo "<td>{$hoa_don_id}</td>";
            echo "<td>{$ma_hoa_don}</td>";
            echo "<td>{$ngay_lap}</td>";
            echo "<td>" . number_format($tong_tien, 0, ',', '.') . "₫</td>";
            echo "<td>{$goi_tap_id}</td>";
            echo "<td>{$ten_goi}</td>";
            echo "<td style='color: green;'>✅ Đã có</td>";
            echo "</tr>";
            continue;
        }
        
        // Chèn vào DangKyGoiTap
        try {
            $stmt_insert = $pdo->prepare("INSERT INTO DangKyGoiTap (
                khach_hang_id, goi_tap_id, hoa_don_id, ngay_dang_ky,
                ngay_bat_dau, ngay_ket_thuc, tong_tien, trang_thai
            ) VALUES (
                :kh_id, :goi_id, :hd_id, :ngay_dk,
                :ngay_bd, :ngay_kt, :tong_tien, 'Đang hoạt động'
            )");
            
            $stmt_insert->execute([
                ':kh_id' => $khach_hang_id,
                ':goi_id' => $goi_tap_id,
                ':hd_id' => $hoa_don_id,
                ':ngay_dk' => $ngay_lap,
                ':ngay_bd' => $ngay_bat_dau,
                ':ngay_kt' => $ngay_ket_thuc,
                ':tong_tien' => $tong_tien
            ]);
            
            $dang_ky_id = $pdo->lastInsertId();
            
            if ($dang_ky_id) {
                $success_count++;
                echo "<tr>";
                echo "<td>{$hoa_don_id}</td>";
                echo "<td>{$ma_hoa_don}</td>";
                echo "<td>{$ngay_lap}</td>";
                echo "<td>" . number_format($tong_tien, 0, ',', '.') . "₫</td>";
                echo "<td>{$goi_tap_id}</td>";
                echo "<td>{$ten_goi}</td>";
                echo "<td style='color: green;'>✅ Đã chèn (ID: {$dang_ky_id})</td>";
                echo "</tr>";
            } else {
                $error_count++;
                echo "<tr>";
                echo "<td>{$hoa_don_id}</td>";
                echo "<td>{$ma_hoa_don}</td>";
                echo "<td>{$ngay_lap}</td>";
                echo "<td>" . number_format($tong_tien, 0, ',', '.') . "₫</td>";
                echo "<td>{$goi_tap_id}</td>";
                echo "<td>{$ten_goi}</td>";
                echo "<td style='color: red;'>❌ Lỗi: Không chèn được</td>";
                echo "</tr>";
            }
        } catch (PDOException $e) {
            $error_count++;
            echo "<tr>";
            echo "<td>{$hoa_don_id}</td>";
            echo "<td>{$ma_hoa_don}</td>";
            echo "<td>{$ngay_lap}</td>";
            echo "<td>" . number_format($tong_tien, 0, ',', '.') . "₫</td>";
            echo "<td>{$goi_tap_id}</td>";
            echo "<td>{$ten_goi}</td>";
            echo "<td style='color: red;'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    echo "<hr>";
    echo "<h3>Kết quả:</h3>";
    echo "<ul>";
    echo "<li>✅ Thành công: <strong>{$success_count}</strong></li>";
    echo "<li>❌ Lỗi: <strong>{$error_count}</strong></li>";
    echo "</ul>";
    
    if ($success_count > 0) {
        echo "<p style='color: green; font-size: 16px; font-weight: bold;'>";
        echo "✅ Đã chèn {$success_count} đăng ký gói tập thành công!<br>";
        echo "Bây giờ bạn có thể kiểm tra lại trang lịch tập.";
        echo "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='goitap/check_package.php'>← Kiểm tra lại dữ liệu</a> | <a href='schedule.html'>Xem trang Lịch tập</a></p>";
?>

