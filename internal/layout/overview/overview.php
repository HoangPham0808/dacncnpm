<?php
/**
 * Overview View
 * Chỉ hiển thị HTML và gọi model để lấy dữ liệu thống kê
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'overview_model.php';

// ============================================
// LẤY CÁC THỐNG KÊ TỪ DATABASE
// ============================================

try {
    // 1. Tổng số khách hàng
    $tongKhachHang = getTotalActiveCustomers();

    // 2. Tổng số nhân viên đang làm việc
    $tongNhanVien = getTotalActiveEmployees();

    // 3. Doanh thu tháng này
    $thangHienTai = date('m');
    $namHienTai = date('Y');
    $doanhThuThang = getMonthlyRevenue($thangHienTai, $namHienTai);

    // 4. Doanh thu hôm nay
    $ngayHomNay = date('Y-m-d');
    $doanhThuHomNay = getTodayRevenue($ngayHomNay);

    // 5. Số hóa đơn chờ thanh toán
    $hoaDonChoThanhToan = getPendingInvoices();

    // 6. Số khách hàng đã check-in hôm nay
    $khachHangHomNay = getTodayCheckIns($ngayHomNay);

    // Lấy danh sách hóa đơn gần đây (5 hóa đơn mới nhất)
    $hoaDonGanDay = getRecentInvoices(5);
} catch (Exception $e) {
    // Xử lý lỗi nếu có
    $tongKhachHang = 0;
    $tongNhanVien = 0;
    $doanhThuThang = 0;
    $doanhThuHomNay = 0;
    $hoaDonChoThanhToan = 0;
    $khachHangHomNay = 0;
    $hoaDonGanDay = [];
    $error_message = 'Lỗi khi tải dữ liệu: ' . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tổng Quan Hệ Thống - DFC Gym</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="overview.css">
</head>
<body>
    <div class="overview-container">
        <!-- Header -->
        <header class="overview-header">
            <h1><i class="fas fa-chart-line"></i> Tổng Quan Hệ Thống</h1>
            <div class="header-info">
                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y'); ?></span>
                <span><i class="fas fa-clock"></i> <?php echo date('H:i:s'); ?></span>
            </div>
        </header>

        <?php if (isset($error_message)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <!-- Thống kê chính -->
        <section class="stats-grid">
            <!-- Card 1: Tổng khách hàng -->
            <div class="stat-card card-blue">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Tổng Khách Hàng</h3>
                    <p class="stat-number"><?php echo number_format($tongKhachHang); ?></p>
                    <span class="stat-label">Đang hoạt động</span>
                </div>
            </div>

            <!-- Card 2: Tổng nhân viên -->
            <div class="stat-card card-green">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <h3>Tổng Nhân Viên</h3>
                    <p class="stat-number"><?php echo number_format($tongNhanVien); ?></p>
                    <span class="stat-label">Đang làm việc</span>
                </div>
            </div>

            <!-- Card 3: Doanh thu tháng này -->
            <div class="stat-card card-orange">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3>Doanh Thu Tháng</h3>
                    <p class="stat-number"><?php echo number_format($doanhThuThang, 0, ',', '.'); ?> đ</p>
                    <span class="stat-label">Tháng <?php echo $thangHienTai; ?>/<?php echo $namHienTai; ?></span>
                </div>
            </div>

            <!-- Card 4: Doanh thu hôm nay -->
            <div class="stat-card card-purple">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-content">
                    <h3>Doanh Thu Hôm Nay</h3>
                    <p class="stat-number"><?php echo number_format($doanhThuHomNay, 0, ',', '.'); ?> đ</p>
                    <span class="stat-label"><?php echo date('d/m/Y'); ?></span>
                </div>
            </div>

            <!-- Card 5: Hóa đơn chờ thanh toán -->
            <div class="stat-card card-red">
                <div class="stat-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="stat-content">
                    <h3>Hóa Đơn Chờ Thanh Toán</h3>
                    <p class="stat-number"><?php echo number_format($hoaDonChoThanhToan); ?></p>
                    <span class="stat-label">Cần xử lý</span>
                </div>
            </div>

            <!-- Card 6: Khách hàng check-in hôm nay -->
            <div class="stat-card card-teal">
                <div class="stat-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="stat-content">
                    <h3>Khách Check-in Hôm Nay</h3>
                    <p class="stat-number"><?php echo number_format($khachHangHomNay); ?></p>
                    <span class="stat-label"><?php echo date('d/m/Y'); ?></span>
                </div>
            </div>
        </section>

        <!-- Bảng thông tin -->
        <section class="info-tables">
            <!-- Hóa đơn gần đây -->
            <div class="info-table-card">
                <h2><i class="fas fa-receipt"></i> Hóa Đơn Gần Đây</h2>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã HĐ</th>
                                <th>Khách Hàng</th>
                                <th>Ngày Lập</th>
                                <th>Thành Tiền</th>
                                <th>Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($hoaDonGanDay)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Chưa có hóa đơn nào</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($hoaDonGanDay as $hd): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($hd['ma_hoa_don']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($hd['ten_khach_hang'] ?? '-'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($hd['ngay_lap'])); ?></td>
                                        <td class="text-right"><?php echo number_format($hd['tien_thanh_toan'], 0, ',', '.'); ?> đ</td>
                                        <td>
                                            <span class="status-badge status-<?php 
                                                echo $hd['trang_thai'] == 'Đã thanh toán' ? 'success' : 
                                                    ($hd['trang_thai'] == 'Chờ thanh toán' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo htmlspecialchars($hd['trang_thai']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <script src="overview.js"></script>
</body>
</html>
