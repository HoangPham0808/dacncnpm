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

// Lấy bộ lọc
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$ratingFilter = isset($_GET['rating']) ? $_GET['rating'] : '';

// Lấy thống kê tổng quan
$stats = [
    'total_reviews' => 0,
    'avg_rating' => 0,
    'five_star' => 0,
    'this_month' => 0,
    'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
];

// Tổng số đánh giá
$result = $conn->query("SELECT COUNT(*) as total FROM DanhGia");
if ($result) {
    $stats['total_reviews'] = $result->fetch_assoc()['total'];
}

// Điểm trung bình
$result = $conn->query("SELECT AVG(diem_danh_gia) as avg_rating FROM DanhGia");
if ($result) {
    $stats['avg_rating'] = floatval($result->fetch_assoc()['avg_rating']);
}

// Số đánh giá 5 sao
$result = $conn->query("SELECT COUNT(*) as five_star FROM DanhGia WHERE diem_danh_gia = 5");
if ($result) {
    $stats['five_star'] = $result->fetch_assoc()['five_star'];
}

// Đánh giá tháng này
$result = $conn->query("SELECT COUNT(*) as this_month FROM DanhGia WHERE MONTH(ngay_danh_gia) = MONTH(CURRENT_DATE()) AND YEAR(ngay_danh_gia) = YEAR(CURRENT_DATE())");
if ($result) {
    $stats['this_month'] = $result->fetch_assoc()['this_month'];
}

// Phân bố đánh giá
$result = $conn->query("SELECT diem_danh_gia, COUNT(*) as count FROM DanhGia GROUP BY diem_danh_gia");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats['distribution'][$row['diem_danh_gia']] = $row['count'];
    }
}

// Lấy danh sách đánh giá
$sql = "SELECT 
            dg.danh_gia_id,
            dg.loai_danh_gia,
            dg.diem_danh_gia,
            dg.noi_dung,
            dg.ngay_danh_gia,
            kh.ho_ten AS ten_khach_hang,
            pt.ho_ten AS ten_pt,
            tb.ten_thiet_bi,
            gt.ten_goi AS ten_goi_tap
        FROM DanhGia dg
        LEFT JOIN KhachHang kh ON dg.khach_hang_id = kh.khach_hang_id
        LEFT JOIN NhanVien pt ON dg.nhan_vien_pt_id = pt.nhan_vien_id
        LEFT JOIN ThietBi tb ON dg.thiet_bi_id = tb.thiet_bi_id
        LEFT JOIN GoiTap gt ON dg.goi_tap_id = gt.goi_tap_id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($typeFilter)) {
    $sql .= " AND dg.loai_danh_gia = ?";
    $params[] = $typeFilter;
    $types .= "s";
}

if (!empty($ratingFilter)) {
    $sql .= " AND dg.diem_danh_gia = ?";
    $params[] = intval($ratingFilter);
    $types .= "i";
}

$sql .= " ORDER BY dg.ngay_danh_gia DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$reviewsList = [];
while ($row = $result->fetch_assoc()) {
    $reviewsList[] = $row;
}
$stmt->close();

// Hàm format ngày tháng
function formatDate($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date('d/m/Y H:i', $timestamp);
}
?>