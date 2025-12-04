<?php
// check_package.php - Script để kiểm tra gói tập trong database
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    die('Vui lòng đăng nhập để kiểm tra.');
}

$username = $_SESSION['user']['username'];

echo "<h2>Kiểm tra Gói Tập - Username: {$username}</h2>";
echo "<hr>";

try {
    // 1. Kiểm tra KhachHang
    echo "<h3>1. Thông tin Khách hàng:</h3>";
    $stmt = $pdo->prepare("SELECT khach_hang_id, ho_ten, ten_dang_nhap, email FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if ($khachHang) {
        echo "<pre>";
        print_r($khachHang);
        echo "</pre>";
        $khach_hang_id = $khachHang['khach_hang_id'];
    } else {
        die("Không tìm thấy khách hàng với username: {$username}");
    }
    
    // 2. Kiểm tra HoaDon
    echo "<h3>2. Hóa đơn đã thanh toán:</h3>";
    $stmt = $pdo->prepare("SELECT hoa_don_id, ma_hoa_don, ngay_lap, tong_tien, trang_thai FROM HoaDon WHERE khach_hang_id = :kh_id ORDER BY hoa_don_id DESC LIMIT 5");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    $hoaDons = $stmt->fetchAll();
    
    if (count($hoaDons) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Mã HĐ</th><th>Ngày</th><th>Tổng tiền</th><th>Trạng thái</th></tr>";
        foreach ($hoaDons as $hd) {
            echo "<tr>";
            echo "<td>{$hd['hoa_don_id']}</td>";
            echo "<td>{$hd['ma_hoa_don']}</td>";
            echo "<td>{$hd['ngay_lap']}</td>";
            echo "<td>" . number_format($hd['tong_tien'], 0, ',', '.') . "₫</td>";
            echo "<td>{$hd['trang_thai']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Không có hóa đơn nào!</p>";
    }
    
    // 3. Kiểm tra GoiTap
    echo "<h3>3. Gói tập trong hệ thống (GoiTap):</h3>";
    $stmt = $pdo->prepare("SELECT goi_tap_id, ma_goi_tap, ten_goi, thoi_han_ngay, gia_tien, trang_thai FROM GoiTap ORDER BY goi_tap_id DESC LIMIT 10");
    $stmt->execute();
    $goiTaps = $stmt->fetchAll();
    
    if (count($goiTaps) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Mã Gói</th><th>Tên</th><th>Thời hạn</th><th>Giá</th><th>Trạng thái</th></tr>";
        foreach ($goiTaps as $gt) {
            echo "<tr>";
            echo "<td>{$gt['goi_tap_id']}</td>";
            echo "<td>{$gt['ma_goi_tap']}</td>";
            echo "<td>{$gt['ten_goi']}</td>";
            echo "<td>{$gt['thoi_han_ngay']} ngày</td>";
            echo "<td>" . number_format($gt['gia_tien'], 0, ',', '.') . "₫</td>";
            echo "<td>{$gt['trang_thai']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Không có gói tập nào trong bảng GoiTap! Cần tạo gói tập trước.</p>";
    }
    
    // 4. Kiểm tra DangKyGoiTap (QUAN TRỌNG)
    echo "<h3>4. Đăng ký Gói Tập (DangKyGoiTap) - QUAN TRỌNG:</h3>";
    $stmt = $pdo->prepare("SELECT dk.dang_ky_id, dk.khach_hang_id, dk.goi_tap_id, dk.hoa_don_id, 
                                  dk.ngay_dang_ky, dk.ngay_bat_dau, dk.ngay_ket_thuc, 
                                  dk.tong_tien, dk.trang_thai, gt.ten_goi
                           FROM DangKyGoiTap dk
                           LEFT JOIN GoiTap gt ON dk.goi_tap_id = gt.goi_tap_id
                           WHERE dk.khach_hang_id = :kh_id 
                           ORDER BY dk.dang_ky_id DESC");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    $dangKyGoiTaps = $stmt->fetchAll();
    
    if (count($dangKyGoiTaps) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ĐK ID</th><th>KH ID</th><th>Gói ID</th><th>HĐ ID</th><th>Ngày ĐK</th><th>Bắt đầu</th><th>Kết thúc</th><th>Tổng tiền</th><th>Trạng thái</th><th>Tên gói</th></tr>";
        foreach ($dangKyGoiTaps as $dk) {
            echo "<tr>";
            echo "<td>{$dk['dang_ky_id']}</td>";
            echo "<td>{$dk['khach_hang_id']}</td>";
            echo "<td>{$dk['goi_tap_id']}</td>";
            echo "<td>{$dk['hoa_don_id']}</td>";
            echo "<td>{$dk['ngay_dang_ky']}</td>";
            echo "<td>{$dk['ngay_bat_dau']}</td>";
            echo "<td>{$dk['ngay_ket_thuc']}</td>";
            echo "<td>" . number_format($dk['tong_tien'], 0, ',', '.') . "₫</td>";
            echo "<td><strong>{$dk['trang_thai']}</strong></td>";
            echo "<td>{$dk['ten_goi']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red; font-size: 16px; font-weight: bold;'>";
        echo "❌ KHÔNG TÌM THẤY ĐĂNG KÝ GÓI TẬP NÀO trong bảng DangKyGoiTap!<br>";
        echo "Đây là lý do tại sao trang lịch tập không hiển thị gói tập.<br>";
        echo "Cần kiểm tra xem quá trình thanh toán có insert vào bảng này không.";
        echo "</p>";
    }
    
    // 5. Tổng kết
    echo "<hr>";
    echo "<h3>5. Tổng kết:</h3>";
    echo "<ul>";
    echo "<li>Số hóa đơn: <strong>" . count($hoaDons) . "</strong></li>";
    echo "<li>Số gói tập trong hệ thống: <strong>" . count($goiTaps) . "</strong></li>";
    echo "<li>Số đăng ký gói tập: <strong>" . count($dangKyGoiTaps) . "</strong></li>";
    echo "</ul>";
    
    if (count($hoaDons) > 0 && count($dangKyGoiTaps) === 0) {
        echo "<p style='color: red; font-weight: bold;'>";
        echo "⚠️ VẤN ĐỀ: Có hóa đơn nhưng không có đăng ký gói tập!<br>";
        echo "Có thể quá trình thanh toán không insert vào bảng DangKyGoiTap.<br>";
        echo "Vui lòng kiểm tra log file để xem lỗi chi tiết.";
        echo "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<a href='schedule.html'>← Quay lại trang Lịch tập</a> | ";
echo "<a href='packages.html'>Quay lại trang Gói tập</a>";
?>

