<?php
// Simple printable invoice page
require_once __DIR__ . '/../../../Database/db.php';
session_start();

if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
    http_response_code(400);
    echo 'ID hóa đơn không hợp lệ';
    exit;
}

$id = intval($_GET['id']);

// Basic permission check (optional)
$current_nhan_vien_id = $_SESSION['nhan_vien_id'] ?? null;
$current_vai_tro = $_SESSION['vai_tro'] ?? null;
$current_phong_tap_id = null;
if ($current_nhan_vien_id && $current_vai_tro !== 'Admin') {
    $stmt = $conn->prepare("SELECT phong_tap_id FROM NhanVien WHERE nhan_vien_id = ?");
    $stmt->bind_param('i', $current_nhan_vien_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $current_phong_tap_id = $row['phong_tap_id'];
    $stmt->close();
}

// Fetch invoice header
$sql = "SELECT hd.*, kh.ho_ten AS ten_khach_hang, kh.email AS email_khach_hang, kh.sdt AS sdt_khach_hang, kh.phong_tap_id AS khach_hang_phong_tap_id, nv.ho_ten AS ten_nhan_vien, km.ten_khuyen_mai
        FROM HoaDon hd
        LEFT JOIN KhachHang kh ON hd.khach_hang_id = kh.khach_hang_id
        LEFT JOIN NhanVien nv ON hd.nhan_vien_lap_id = nv.nhan_vien_id
        LEFT JOIN KhuyenMai km ON hd.khuyen_mai_id = km.khuyen_mai_id
        WHERE hd.hoa_don_id = ?";

if ($current_vai_tro !== 'Admin') {
    $sql .= " AND kh.phong_tap_id = ?";
}

$stmt = $conn->prepare($sql);
if ($current_vai_tro !== 'Admin') {
    $stmt->bind_param('ii', $id, $current_phong_tap_id);
} else {
    $stmt->bind_param('i', $id);
}
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo 'Không tìm thấy hóa đơn hoặc bạn không có quyền xem.';
    exit;
}
$invoice = $res->fetch_assoc();
$stmt->close();

// Fetch items
$stmt = $conn->prepare("SELECT ct.*, gt.ten_goi FROM ChiTietHoaDon ct LEFT JOIN GoiTap gt ON ct.goi_tap_id = gt.goi_tap_id WHERE ct.hoa_don_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>In hóa đơn - <?php echo htmlspecialchars($invoice['ma_hoa_don'] ?? ''); ?></title>
<style>
body { font-family: Arial, Helvetica, sans-serif; color: #222; }
.container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
.header { display:flex; justify-content: space-between; align-items: center; }
h1 { margin: 0; }
.table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.table th, .table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
.right { text-align: right; }
.meta { margin-top: 10px; }
.footer { margin-top: 20px; }
.print-btn { display:none; }
@media print {
  .print-btn { display:none; }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>Hóa đơn</h1>
            <div>Mã: <strong><?php echo htmlspecialchars($invoice['ma_hoa_don'] ?? ''); ?></strong></div>
            <div>Ngày: <?php echo !empty($invoice['ngay_lap']) ? date('d/m/Y', strtotime($invoice['ngay_lap'])) : ''; ?></div>
        </div>
        <div>
            <strong>DFC Gym</strong>
            <div>Thông tin phòng tập</div>
        </div>
    </div>

    <div class="meta">
        <div>Khách hàng: <strong><?php echo htmlspecialchars($invoice['ten_khach_hang'] ?? ''); ?></strong></div>
        <div>Email: <?php echo htmlspecialchars($invoice['email_khach_hang'] ?? ''); ?></div>
        <div>SĐT: <?php echo htmlspecialchars($invoice['sdt_khach_hang'] ?? ''); ?></div>
        <div>Nhân viên lập: <?php echo htmlspecialchars($invoice['ten_nhan_vien'] ?? ''); ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Gói tập</th>
                <th>Số lượng</th>
                <th>Đơn giá (VNĐ)</th>
                <th>Thành tiền (VNĐ)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            if ($items && $items->num_rows > 0) {
                while ($it = $items->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $i++ . '</td>';
                    echo '<td>' . htmlspecialchars($it['ten_goi'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($it['so_luong'] ?? 1) . '</td>';
                    echo '<td class="right">' . number_format($it['don_gia'] ?? 0, 0, ',', '.') . '</td>';
                    echo '<td class="right">' . number_format($it['thanh_tien'] ?? 0, 0, ',', '.') . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5">Không có dữ liệu chi tiết</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="footer">
        <div>Tổng tiền: <strong><?php echo number_format($invoice['tong_tien'] ?? 0, 0, ',', '.'); ?> ₫</strong></div>
        <div>Giảm giá KM: <?php echo number_format($invoice['giam_gia_khuyen_mai'] ?? 0, 0, ',', '.'); ?> ₫</div>
        <div>Giảm giá khác: <?php echo number_format($invoice['giam_gia_khac'] ?? 0, 0, ',', '.'); ?> ₫</div>
        <div>Tiền thanh toán: <strong><?php echo number_format($invoice['tien_thanh_toan'] ?? 0, 0, ',', '.'); ?> ₫</strong></div>
    </div>

    <div style="margin-top:20px;">
        <button onclick="window.print();" class="print-btn">In hóa đơn</button>
        <a href="#" onclick="window.close();">Đóng</a>
    </div>
</div>
</body>
</html>
