<?php
/**
 * export_invoice.php - Xuất hóa đơn ra các định dạng Word, Excel, PDF
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

if (!isset($_SESSION['user'])) {
    die('Vui lòng đăng nhập');
}

$hoa_don_id = isset($_GET['hoa_don_id']) ? (int)$_GET['hoa_don_id'] : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

if ($hoa_don_id <= 0) {
    die('Mã hóa đơn không hợp lệ');
}

try {
    $username = $_SESSION['user']['username'];
    
    // Lấy thông tin khách hàng
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        die('Không tìm thấy thông tin khách hàng');
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // Lấy thông tin hóa đơn
    $stmt = $pdo->prepare("SELECT hd.*, kh.ho_ten, kh.email, kh.sdt, kh.dia_chi,
                                  km.ten_khuyen_mai, km.ma_khuyen_mai
                           FROM HoaDon hd
                           INNER JOIN KhachHang kh ON hd.khach_hang_id = kh.khach_hang_id
                           LEFT JOIN KhuyenMai km ON hd.khuyen_mai_id = km.khuyen_mai_id
                           WHERE hd.hoa_don_id = :hoa_don_id AND hd.khach_hang_id = :kh_id
                           LIMIT 1");
    $stmt->execute([':hoa_don_id' => $hoa_don_id, ':kh_id' => $khach_hang_id]);
    $hoaDon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hoaDon) {
        die('Không tìm thấy hóa đơn');
    }
    
    // Lấy chi tiết hóa đơn
    $stmt = $pdo->prepare("SELECT * FROM ChiTietHoaDon WHERE hoa_don_id = :hoa_don_id");
    $stmt->execute([':hoa_don_id' => $hoa_don_id]);
    $chiTiet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu - Lưu cả giá trị gốc và giá trị đã format
    $hoaDon['ngay_lap'] = date('d/m/Y', strtotime($hoaDon['ngay_lap']));
    $hoaDon['tong_tien_raw'] = $hoaDon['tong_tien']; // Giữ giá trị gốc
    $hoaDon['tong_tien'] = number_format($hoaDon['tong_tien'], 0, ',', '.');
    $hoaDon['giam_gia_khuyen_mai_raw'] = $hoaDon['giam_gia_khuyen_mai'] ?? 0;
    $hoaDon['giam_gia_khuyen_mai'] = number_format($hoaDon['giam_gia_khuyen_mai'] ?? 0, 0, ',', '.');
    $hoaDon['giam_gia_khac_raw'] = $hoaDon['giam_gia_khac'] ?? 0;
    $hoaDon['giam_gia_khac'] = number_format($hoaDon['giam_gia_khac'] ?? 0, 0, ',', '.');
    $hoaDon['tien_thanh_toan_raw'] = $hoaDon['tien_thanh_toan'];
    $hoaDon['tien_thanh_toan'] = number_format($hoaDon['tien_thanh_toan'], 0, ',', '.');
    
    foreach ($chiTiet as &$item) {
        $item['don_gia_raw'] = $item['don_gia']; // Giữ giá trị gốc
        $item['don_gia'] = number_format($item['don_gia'], 0, ',', '.');
        $item['thanh_tien_raw'] = $item['thanh_tien'];
        $item['thanh_tien'] = number_format($item['thanh_tien'], 0, ',', '.');
    }
    
    // Tạo HTML cho hóa đơn
    $html = generateInvoiceHTML($hoaDon, $chiTiet);
    
    // Xuất theo định dạng
    switch ($format) {
        case 'word':
            exportWord($html, $hoaDon['ma_hoa_don']);
            break;
        case 'excel':
            exportExcel($hoaDon, $chiTiet);
            break;
        case 'pdf':
        default:
            exportPDF($html, $hoaDon['ma_hoa_don']);
            break;
    }
    
} catch (Exception $e) {
    die('Lỗi: ' . $e->getMessage());
}

// Tạo HTML cho hóa đơn
function generateInvoiceHTML($hoaDon, $chiTiet) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn ' . htmlspecialchars($hoaDon['ma_hoa_don']) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #22c55e;
            padding-bottom: 20px;
        }
        .invoice-header h1 {
            color: #22c55e;
            margin: 0;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-box {
            flex: 1;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            margin: 0 10px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #22c55e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #22c55e;
            color: white;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        .total-row {
            padding: 10px;
            font-size: 16px;
        }
        .total-final {
            font-size: 20px;
            font-weight: bold;
            color: #22c55e;
            border-top: 2px solid #22c55e;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1>DFC GYM - DYNAMIC FITNESS CLUB</h1>
        <p>HÓA ĐƠN THANH TOÁN</p>
    </div>
    
    <div class="invoice-info">
        <div class="info-box">
            <h3>Thông tin khách hàng</h3>
            <p><strong>Họ tên:</strong> ' . htmlspecialchars($hoaDon['ho_ten']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($hoaDon['email'] ?? '') . '</p>
            <p><strong>SĐT:</strong> ' . htmlspecialchars($hoaDon['sdt'] ?? '') . '</p>
            <p><strong>Địa chỉ:</strong> ' . htmlspecialchars($hoaDon['dia_chi'] ?? '') . '</p>
        </div>
        <div class="info-box">
            <h3>Thông tin hóa đơn</h3>
            <p><strong>Mã hóa đơn:</strong> ' . htmlspecialchars($hoaDon['ma_hoa_don']) . '</p>
            <p><strong>Ngày lập:</strong> ' . htmlspecialchars($hoaDon['ngay_lap']) . '</p>
            <p><strong>Phương thức:</strong> ' . htmlspecialchars($hoaDon['phuong_thuc_thanh_toan']) . '</p>
            <p><strong>Trạng thái:</strong> ' . htmlspecialchars($hoaDon['trang_thai']) . '</p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Tên gói tập</th>
                <th class="text-right">Số lượng</th>
                <th class="text-right">Đơn giá</th>
                <th class="text-right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>';
    
    $stt = 1;
    foreach ($chiTiet as $item) {
        $html .= '<tr>
            <td>' . $stt++ . '</td>
            <td>' . htmlspecialchars($item['ten_goi']) . '</td>
            <td class="text-right">' . $item['so_luong'] . '</td>
            <td class="text-right">' . $item['don_gia'] . '₫</td>
            <td class="text-right">' . $item['thanh_tien'] . '₫</td>
        </tr>';
    }
    
    $html .= '</tbody>
    </table>
    
    <div class="total-section">
        <div class="total-row">
            <strong>Tổng tiền:</strong> ' . $hoaDon['tong_tien'] . '₫
        </div>';
    
    if ($hoaDon['giam_gia_khuyen_mai'] > 0) {
        $html .= '<div class="total-row">
            <strong>Giảm giá khuyến mãi:</strong> -' . $hoaDon['giam_gia_khuyen_mai'] . '₫
        </div>';
    }
    
    if ($hoaDon['giam_gia_khac'] > 0) {
        $html .= '<div class="total-row">
            <strong>Giảm giá khác:</strong> -' . $hoaDon['giam_gia_khac'] . '₫
        </div>';
    }
    
    $html .= '<div class="total-row total-final">
            <strong>Thành tiền:</strong> ' . $hoaDon['tien_thanh_toan'] . '₫
        </div>
    </div>
    
    <div style="margin-top: 40px; text-align: center; color: #666; font-size: 12px;">
        <p>Cảm ơn quý khách đã sử dụng dịch vụ của DFC Gym!</p>
        <p>Hóa đơn được tạo tự động từ hệ thống</p>
    </div>
</body>
</html>';
    
    return $html;
}

// Xuất Word
function exportWord($html, $maHoaDon) {
    // Sử dụng MIME type cho Word 2003+ (compatible với Word)
    header('Content-Type: application/msword; charset=UTF-8');
    header('Content-Disposition: attachment; filename="HoaDon_' . $maHoaDon . '.doc"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // Chuyển đổi HTML sang format Word-compatible
    // Word có thể đọc HTML nhưng cần format đúng
    // Lấy style và body từ HTML gốc
    preg_match('/<style>(.*?)<\/style>/s', $html, $styleMatches);
    $styleContent = isset($styleMatches[1]) ? $styleMatches[1] : '';
    
    preg_match('/<body[^>]*>(.*?)<\/body>/s', $html, $bodyMatches);
    $bodyContent = isset($bodyMatches[1]) ? $bodyMatches[1] : '';
    
    // Tạo HTML cho Word với namespace đúng
    $wordHtml = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml>
<w:WordDocument>
<w:View>Print</w:View>
<w:Zoom>100</w:Zoom>
<w:DoNotOptimizeForBrowser/>
</w:WordDocument>
</xml>
<![endif]-->';
    
    if ($styleContent) {
        $wordHtml .= '<style>' . $styleContent . '</style>';
    }
    
    $wordHtml .= '</head>
<body>';
    
    if ($bodyContent) {
        $wordHtml .= $bodyContent;
    } else {
        // Nếu không tìm thấy body, lấy toàn bộ HTML và loại bỏ các tag không cần thiết
        $wordHtml .= preg_replace('/<html[^>]*>|<\/html>|<head[^>]*>.*?<\/head>/s', '', $html);
    }
    
    $wordHtml .= '</body></html>';
    
    echo $wordHtml;
    exit;
}

// Xuất Excel
function exportExcel($hoaDon, $chiTiet) {
    // Sử dụng MIME type cho Excel 2003+ (compatible với Excel)
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="HoaDon_' . $hoaDon['ma_hoa_don'] . '.xls"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // Tạo HTML table cho Excel (Excel có thể đọc HTML table)
    $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:excel"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml>
<x:ExcelWorkbook>
<x:ExcelWorksheets>
<x:ExcelWorksheet>
<x:Name>Hóa đơn</x:Name>
<x:WorksheetOptions>
<x:Print>
<x:ValidPrinterInfo/>
</x:Print>
</x:WorksheetOptions>
</x:ExcelWorksheet>
</x:ExcelWorksheets>
</x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
th, td { border: 1px solid #000; padding: 8px; text-align: left; }
th { background: #22c55e; color: white; font-weight: bold; }
.text-right { text-align: right; }
.total-row { font-weight: bold; background: #f0f0f0; }
</style>
</head>
<body>
    <h2 style="text-align: center;">HÓA ĐƠN ' . htmlspecialchars($hoaDon['ma_hoa_don']) . '</h2>
    <table>
        <tr><th style="width: 200px;">Mã hóa đơn</th><td>' . htmlspecialchars($hoaDon['ma_hoa_don']) . '</td></tr>
        <tr><th>Ngày lập</th><td>' . htmlspecialchars($hoaDon['ngay_lap']) . '</td></tr>
        <tr><th>Khách hàng</th><td>' . htmlspecialchars($hoaDon['ho_ten']) . '</td></tr>
        <tr><th>Email</th><td>' . htmlspecialchars($hoaDon['email'] ?? '') . '</td></tr>
        <tr><th>SĐT</th><td>' . htmlspecialchars($hoaDon['sdt'] ?? '') . '</td></tr>
        <tr><th>Địa chỉ</th><td>' . htmlspecialchars($hoaDon['dia_chi'] ?? '') . '</td></tr>
        <tr><th>Phương thức thanh toán</th><td>' . htmlspecialchars($hoaDon['phuong_thuc_thanh_toan']) . '</td></tr>
        <tr><th>Trạng thái</th><td>' . htmlspecialchars($hoaDon['trang_thai']) . '</td></tr>
    </table>
    <h3>Chi tiết hóa đơn</h3>
    <table>
        <tr>
            <th style="width: 50px;">STT</th>
            <th>Tên gói tập</th>
            <th style="width: 100px;" class="text-right">Số lượng</th>
            <th style="width: 150px;" class="text-right">Đơn giá</th>
            <th style="width: 150px;" class="text-right">Thành tiền</th>
        </tr>';
    
    $stt = 1;
    foreach ($chiTiet as $item) {
        // Sử dụng giá trị gốc nếu có, nếu không thì parse từ giá trị đã format
        $donGia = isset($item['don_gia_raw']) ? $item['don_gia_raw'] : (float)str_replace(['₫', '.', ','], '', $item['don_gia']);
        $thanhTien = isset($item['thanh_tien_raw']) ? $item['thanh_tien_raw'] : (float)str_replace(['₫', '.', ','], '', $item['thanh_tien']);
        
        $html .= '<tr>
            <td>' . $stt++ . '</td>
            <td>' . htmlspecialchars($item['ten_goi']) . '</td>
            <td class="text-right">' . $item['so_luong'] . '</td>
            <td class="text-right">' . number_format((float)$donGia, 0, ',', '.') . ' VNĐ</td>
            <td class="text-right">' . number_format((float)$thanhTien, 0, ',', '.') . ' VNĐ</td>
        </tr>';
    }
    
    // Sử dụng giá trị gốc nếu có, nếu không thì parse từ giá trị đã format
    $tongTienRaw = isset($hoaDon['tong_tien_raw']) ? $hoaDon['tong_tien_raw'] : (float)str_replace(['₫', '.', ','], '', $hoaDon['tong_tien']);
    $tienThanhToanRaw = isset($hoaDon['tien_thanh_toan_raw']) ? $hoaDon['tien_thanh_toan_raw'] : (float)str_replace(['₫', '.', ','], '', $hoaDon['tien_thanh_toan']);
    
    $html .= '<tr class="total-row">
            <td colspan="4" class="text-right"><strong>Tổng tiền:</strong></td>
            <td class="text-right"><strong>' . number_format((float)$tongTienRaw, 0, ',', '.') . ' VNĐ</strong></td>
        </tr>';
    
    if (isset($hoaDon['giam_gia_khuyen_mai_raw']) && $hoaDon['giam_gia_khuyen_mai_raw'] > 0) {
        $giamGiaKM = $hoaDon['giam_gia_khuyen_mai_raw'];
        $html .= '<tr>
            <td colspan="4" class="text-right"><strong>Giảm giá khuyến mãi:</strong></td>
            <td class="text-right"><strong>-' . number_format((float)$giamGiaKM, 0, ',', '.') . ' VNĐ</strong></td>
        </tr>';
    }
    
    if (isset($hoaDon['giam_gia_khac_raw']) && $hoaDon['giam_gia_khac_raw'] > 0) {
        $giamGiaKhac = $hoaDon['giam_gia_khac_raw'];
        $html .= '<tr>
            <td colspan="4" class="text-right"><strong>Giảm giá khác:</strong></td>
            <td class="text-right"><strong>-' . number_format((float)$giamGiaKhac, 0, ',', '.') . ' VNĐ</strong></td>
        </tr>';
    }
    
    $html .= '<tr class="total-row">
            <td colspan="4" class="text-right"><strong>Thành tiền:</strong></td>
            <td class="text-right"><strong>' . number_format((float)$tienThanhToanRaw, 0, ',', '.') . ' VNĐ</strong></td>
        </tr>
    </table>
    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
        <p>Cảm ơn quý khách đã sử dụng dịch vụ của DFC Gym!</p>
        <p>Hóa đơn được tạo tự động từ hệ thống</p>
    </div>
</body>
</html>';
    
    echo $html;
    exit;
}

// Xuất PDF
function exportPDF($html, $maHoaDon) {
    // Sử dụng HTML để in PDF (browser sẽ tự động convert khi in)
    header('Content-Type: text/html; charset=UTF-8');
    
    // Thêm script để tự động in
    $html = str_replace('</body>', '
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>', $html);
    
    echo $html;
    exit;
}

