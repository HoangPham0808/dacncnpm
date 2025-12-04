<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dfcgym";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Xác định period filter và custom date range
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$customStartDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$customEndDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$startDate = null;
$endDate = null;
$prevStartDate = null;
$prevEndDate = null;

// Tính toán thời gian dựa trên period hoặc custom date
$today = date('Y-m-d');
$currentYear = date('Y');
$currentMonth = date('m');

if ($period === 'custom' && $customStartDate && $customEndDate) {
    // Sử dụng ngày tùy chỉnh
    $startDate = $customStartDate;
    $endDate = $customEndDate;
    
    // Tính kỳ trước (cùng độ dài thời gian)
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    $days = $interval->days;
    
    $prevEnd = clone $start;
    $prevEnd->modify('-1 day');
    $prevStart = clone $prevEnd;
    $prevStart->modify('-' . $days . ' days');
    
    $prevStartDate = $prevStart->format('Y-m-d');
    $prevEndDate = $prevEnd->format('Y-m-d');
} else {
    switch ($period) {
        case 'month':
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
            $prevStartDate = date('Y-m-01', strtotime('-1 month'));
            $prevEndDate = date('Y-m-t', strtotime('-1 month'));
            break;
        case 'quarter':
            $currentQuarter = ceil($currentMonth / 3);
            $startMonth = (($currentQuarter - 1) * 3) + 1;
            $startDate = date("Y-{$startMonth}-01");
            $endMonth = $currentQuarter * 3;
            $endDate = date("Y-{$endMonth}-t");
            $prevQuarter = $currentQuarter - 1;
            if ($prevQuarter == 0) {
                $prevQuarter = 4;
                $prevYear = $currentYear - 1;
            } else {
                $prevYear = $currentYear;
            }
            $prevStartMonth = (($prevQuarter - 1) * 3) + 1;
            $prevStartDate = date("{$prevYear}-{$prevStartMonth}-01");
            $prevEndMonth = $prevQuarter * 3;
            $prevEndDate = date("{$prevYear}-{$prevEndMonth}-t");
            break;
        case 'year':
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
            $prevYear = $currentYear - 1;
            $prevStartDate = date("{$prevYear}-01-01");
            $prevEndDate = date("{$prevYear}-12-31");
            break;
        default:
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
            $prevStartDate = date('Y-m-01', strtotime('-1 month'));
            $prevEndDate = date('Y-m-t', strtotime('-1 month'));
    }
}

// Hàm tính % thay đổi
function calculateChange($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

// Hàm format giá tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// Hàm format số
function formatNumber($number) {
    return number_format($number, 0, ',', '.');
}

// =============================================
// 1. DOANH THU
// =============================================
$stmt = $conn->prepare("SELECT COALESCE(SUM(tien_thanh_toan), 0) as doanh_thu FROM HoaDon WHERE trang_thai = 'Đã thanh toán' AND DATE(ngay_lap) BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$revenueRow = $result->fetch_assoc();
$revenue = floatval($revenueRow['doanh_thu'] ?? 0);
$stmt->close();

// Doanh thu kỳ trước
$stmt = $conn->prepare("SELECT COALESCE(SUM(tien_thanh_toan), 0) as doanh_thu FROM HoaDon WHERE trang_thai = 'Đã thanh toán' AND DATE(ngay_lap) BETWEEN ? AND ?");
$stmt->bind_param("ss", $prevStartDate, $prevEndDate);
$stmt->execute();
$result = $stmt->get_result();
$prevRevenueRow = $result->fetch_assoc();
$prevRevenue = floatval($prevRevenueRow['doanh_thu'] ?? 0);
$stmt->close();

$revenueChange = calculateChange($revenue, $prevRevenue);

// =============================================
// 2. TỔNG KHÁCH HÀNG & KHÁCH HÀNG MỚI
// =============================================
$stmt = $conn->query("SELECT COUNT(*) as total FROM KhachHang");
$customerRow = $stmt->fetch_assoc();
$totalCustomers = intval($customerRow['total'] ?? 0);
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM KhachHang WHERE DATE(ngay_dang_ky) BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$newCustomersRow = $result->fetch_assoc();
$newCustomers = intval($newCustomersRow['total'] ?? 0);
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM KhachHang WHERE DATE(ngay_dang_ky) BETWEEN ? AND ?");
$stmt->bind_param("ss", $prevStartDate, $prevEndDate);
$stmt->execute();
$result = $stmt->get_result();
$prevNewCustomersRow = $result->fetch_assoc();
$prevNewCustomers = intval($prevNewCustomersRow['total'] ?? 0);
$stmt->close();

$customerChange = calculateChange($newCustomers, $prevNewCustomers);

// =============================================
// 3. GÓI TẬP ĐÃ BÁN
// =============================================
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT cthd.chi_tiet_id) as total 
    FROM ChiTietHoaDon cthd
    INNER JOIN HoaDon hd ON cthd.hoa_don_id = hd.hoa_don_id
    WHERE hd.trang_thai = 'Đã thanh toán' AND DATE(hd.ngay_lap) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$packagesSoldRow = $result->fetch_assoc();
$packagesSold = intval($packagesSoldRow['total'] ?? 0);
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT cthd.chi_tiet_id) as total 
    FROM ChiTietHoaDon cthd
    INNER JOIN HoaDon hd ON cthd.hoa_don_id = hd.hoa_don_id
    WHERE hd.trang_thai = 'Đã thanh toán' AND DATE(hd.ngay_lap) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $prevStartDate, $prevEndDate);
$stmt->execute();
$result = $stmt->get_result();
$prevPackagesSoldRow = $result->fetch_assoc();
$prevPackagesSold = intval($prevPackagesSoldRow['total'] ?? 0);
$stmt->close();

$packagesSoldChange = calculateChange($packagesSold, $prevPackagesSold);

// =============================================
// 4. TỶ LỆ THAM GIA
// =============================================
$stmt = $conn->query("
    SELECT COUNT(DISTINCT dk.khach_hang_id) as active_customers
    FROM DangKyGoiTap dk
    WHERE dk.trang_thai = 'Đang hoạt động' AND DATE(dk.ngay_ket_thuc) >= CURDATE()
");
$activeCustomersRow = $stmt->fetch_assoc();
$activeCustomers = intval($activeCustomersRow['active_customers'] ?? 0);
$stmt->close();

$participationRate = $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 1) : 0;

$prevEndDateObj = new DateTime($prevEndDate);
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT dk.khach_hang_id) as active_customers
    FROM DangKyGoiTap dk
    WHERE dk.trang_thai = 'Đang hoạt động' AND DATE(dk.ngay_ket_thuc) >= ?
");
$prevEndDateStr = $prevEndDateObj->format('Y-m-d');
$stmt->bind_param("s", $prevEndDateStr);
$stmt->execute();
$result = $stmt->get_result();
$prevActiveCustomersRow = $result->fetch_assoc();
$prevActiveCustomers = intval($prevActiveCustomersRow['active_customers'] ?? 0);
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM KhachHang WHERE DATE(ngay_dang_ky) <= ?");
$stmt->bind_param("s", $prevEndDateStr);
$stmt->execute();
$result = $stmt->get_result();
$prevTotalCustomersRow = $result->fetch_assoc();
$prevTotalCustomers = intval($prevTotalCustomersRow['total'] ?? 0);
$stmt->close();

$prevParticipationRate = $prevTotalCustomers > 0 ? round(($prevActiveCustomers / $prevTotalCustomers) * 100, 1) : 0;
$participationRateChange = calculateChange($participationRate, $prevParticipationRate);

// =============================================
// 5. DOANH THU THEO THÁNG - Động dựa vào khoảng thời gian chọn
// =============================================
$revenueByMonth = [];
$start = new DateTime($startDate);
$end = new DateTime($endDate);
$interval = $start->diff($end);
$totalMonths = ($interval->y * 12) + $interval->m + 1;

// Giới hạn tối đa 12 tháng để hiển thị
$displayMonths = min($totalMonths, 12);

for ($i = $displayMonths - 1; $i >= 0; $i--) {
    $monthDate = clone $end;
    $monthDate->modify("-{$i} months");
    $monthStart = $monthDate->format('Y-m-01');
    $monthEnd = $monthDate->format('Y-m-t');
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(tien_thanh_toan), 0) as doanh_thu FROM HoaDon WHERE trang_thai = 'Đã thanh toán' AND DATE(ngay_lap) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthRevenueRow = $result->fetch_assoc();
    $monthRevenue = floatval($monthRevenueRow['doanh_thu'] ?? 0);
    $stmt->close();
    
    $revenueByMonth[] = [
        'month' => $monthDate->format('m/Y'),
        'revenue' => $monthRevenue
    ];
}

// =============================================
// 6. PHÂN BỐ GÓI TẬP
// =============================================
$packageDistribution = [];
$stmt = $conn->query("
    SELECT 
        gt.loai_goi,
        gt.ten_goi,
        COUNT(cthd.chi_tiet_id) as so_luong
    FROM ChiTietHoaDon cthd
    INNER JOIN HoaDon hd ON cthd.hoa_don_id = hd.hoa_don_id
    LEFT JOIN GoiTap gt ON cthd.goi_tap_id = gt.goi_tap_id
    WHERE hd.trang_thai = 'Đã thanh toán'
    GROUP BY gt.loai_goi, gt.ten_goi
    ORDER BY so_luong DESC
    LIMIT 10
");
while ($row = $stmt->fetch_assoc()) {
    $packageDistribution[] = [
        'label' => !empty($row['ten_goi']) ? $row['ten_goi'] : ($row['loai_goi'] ?? 'Chưa xác định'),
        'value' => intval($row['so_luong'] ?? 0)
    ];
}
$stmt->close();

// =============================================
// 7. KHÁCH HÀNG ĐĂNG KÝ MỚI THEO THÁNG - Động
// =============================================
$newCustomersByMonth = [];
for ($i = $displayMonths - 1; $i >= 0; $i--) {
    $monthDate = clone $end;
    $monthDate->modify("-{$i} months");
    $monthStart = $monthDate->format('Y-m-01');
    $monthEnd = $monthDate->format('Y-m-t');
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM KhachHang WHERE DATE(ngay_dang_ky) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthCustomerRow = $result->fetch_assoc();
    $monthCustomers = intval($monthCustomerRow['total'] ?? 0);
    $stmt->close();
    
    $newCustomersByMonth[] = [
        'month' => $monthDate->format('m/Y'),
        'count' => $monthCustomers
    ];
}

// =============================================
// 8. HIỆU SUẤT NHÂN VIÊN (Top 10 theo doanh thu)
// =============================================
$employeePerformance = [];
$stmt = $conn->prepare("
    SELECT 
        nv.nhan_vien_id,
        nv.ho_ten,
        nv.vai_tro,
        COUNT(DISTINCT hd.hoa_don_id) as so_hoa_don,
        COALESCE(SUM(hd.tien_thanh_toan), 0) as tong_doanh_thu
    FROM NhanVien nv
    LEFT JOIN HoaDon hd ON nv.nhan_vien_id = hd.nhan_vien_lap_id 
        AND hd.trang_thai = 'Đã thanh toán'
        AND DATE(hd.ngay_lap) BETWEEN ? AND ?
    WHERE nv.trang_thai = 'Đang làm'
    GROUP BY nv.nhan_vien_id, nv.ho_ten, nv.vai_tro
    ORDER BY tong_doanh_thu DESC
    LIMIT 10
");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $employeePerformance[] = [
        'name' => $row['ho_ten'],
        'role' => $row['vai_tro'],
        'invoices' => intval($row['so_hoa_don']),
        'revenue' => floatval($row['tong_doanh_thu'])
    ];
}
$stmt->close();

// =============================================
// 9. TÌNH TRẠNG THIẾT BỊ
// =============================================
$equipmentStatus = [];
$stmt = $conn->query("
    SELECT 
        trang_thai,
        COUNT(*) as so_luong
    FROM ThietBi
    GROUP BY trang_thai
");
while ($row = $stmt->fetch_assoc()) {
    $equipmentStatus[] = [
        'status' => $row['trang_thai'],
        'count' => intval($row['so_luong'])
    ];
}
$stmt->close();

// TẤT CẢ THIẾT BỊ VÀ TRẠNG THÁI BẢO TRÌ
$equipmentMaintenanceAll = [];
$stmt = $conn->query("
    SELECT 
        tb.thiet_bi_id,
        tb.ma_thiet_bi,
        tb.ten_thiet_bi,
        tb.loai_thiet_bi,
        tb.vi_tri,
        tb.trang_thai,
        tb.ngay_mua,
        MAX(bt.ngay_bao_tri) as lan_bao_tri_cuoi,
        CASE 
            WHEN MAX(bt.ngay_bao_tri) IS NOT NULL THEN DATEDIFF(CURDATE(), MAX(bt.ngay_bao_tri))
            WHEN tb.ngay_mua IS NOT NULL THEN DATEDIFF(CURDATE(), tb.ngay_mua)
            ELSE 0
        END as ngay_chua_bao_tri
    FROM ThietBi tb
    LEFT JOIN BaoTriThietBi bt ON tb.thiet_bi_id = bt.thiet_bi_id
    GROUP BY tb.thiet_bi_id, tb.ma_thiet_bi, tb.ten_thiet_bi, tb.loai_thiet_bi, tb.vi_tri, tb.trang_thai, tb.ngay_mua
    ORDER BY ngay_chua_bao_tri DESC
");
while ($row = $stmt->fetch_assoc()) {
    $daysSince = intval($row['ngay_chua_bao_tri'] ?? 0);
    $needsMaintenance = $daysSince > 90;
    
    $equipmentMaintenanceAll[] = [
        'code' => $row['ma_thiet_bi'],
        'name' => $row['ten_thiet_bi'],
        'type' => $row['loai_thiet_bi'],
        'location' => $row['vi_tri'],
        'status' => $row['trang_thai'],
        'last_maintenance' => $row['lan_bao_tri_cuoi'],
        'days_since' => $daysSince,
        'needs_maintenance' => $needsMaintenance
    ];
}
$stmt->close();

// Thiết bị CẦN bảo trì (> 90 ngày)
$equipmentMaintenanceNeeded = array_filter($equipmentMaintenanceAll, function($item) {
    return $item['needs_maintenance'];
});

// =============================================
// 10. GÓI TẬP BÁN CHẠY (Top 10)
// =============================================
$topSellingPackages = [];
$stmt = $conn->prepare("
    SELECT 
        gt.ma_goi_tap,
        gt.ten_goi,
        gt.loai_goi,
        gt.gia_tien,
        COUNT(cthd.chi_tiet_id) as so_luong_ban,
        SUM(cthd.thanh_tien) as tong_doanh_thu
    FROM GoiTap gt
    INNER JOIN ChiTietHoaDon cthd ON gt.goi_tap_id = cthd.goi_tap_id
    INNER JOIN HoaDon hd ON cthd.hoa_don_id = hd.hoa_don_id
    WHERE hd.trang_thai = 'Đã thanh toán' AND DATE(hd.ngay_lap) BETWEEN ? AND ?
    GROUP BY gt.goi_tap_id
    ORDER BY so_luong_ban DESC
    LIMIT 10
");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $topSellingPackages[] = [
        'code' => $row['ma_goi_tap'],
        'name' => $row['ten_goi'],
        'type' => $row['loai_goi'],
        'price' => floatval($row['gia_tien']),
        'quantity' => intval($row['so_luong_ban']),
        'revenue' => floatval($row['tong_doanh_thu'])
    ];
}
$stmt->close();

// =============================================
// 11. DOANH THU THEO PHÒNG TẬP
// =============================================
$revenueByGym = [];
$stmt = $conn->prepare("
    SELECT 
        pt.phong_tap_id,
        pt.ma_phong_tap,
        pt.ten_phong_tap,
        pt.dia_chi,
        COUNT(DISTINCT hd.hoa_don_id) as so_hoa_don,
        COALESCE(SUM(hd.tien_thanh_toan), 0) as tong_doanh_thu,
        COUNT(DISTINCT hd.khach_hang_id) as so_khach_hang
    FROM phongtap pt
    LEFT JOIN HoaDon hd ON pt.phong_tap_id = hd.phong_tap_id 
        AND hd.trang_thai = 'Đã thanh toán'
        AND DATE(hd.ngay_lap) BETWEEN ? AND ?
    WHERE pt.trang_thai = 'Hoạt động'
    GROUP BY pt.phong_tap_id
    ORDER BY tong_doanh_thu DESC
");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $revenueByGym[] = [
        'id' => intval($row['phong_tap_id']),
        'code' => $row['ma_phong_tap'],
        'name' => $row['ten_phong_tap'],
        'address' => $row['dia_chi'],
        'invoices' => intval($row['so_hoa_don']),
        'revenue' => floatval($row['tong_doanh_thu']),
        'customers' => intval($row['so_khach_hang'])
    ];
}
$stmt->close();
?>