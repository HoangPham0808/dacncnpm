<?php
require_once __DIR__ . '/management_function.php';

// Tên file xuất
$periodName = '';
switch ($period) {
    case 'month':
        $periodName = 'Thang_' . date('m_Y');
        break;
    case 'quarter':
        $periodName = 'Quy_' . ceil(date('m') / 3) . '_' . date('Y');
        break;
    case 'year':
        $periodName = 'Nam_' . date('Y');
        break;
}

$filename = "BaoCaoThongKe_" . $periodName . "_" . date('dmY_His') . ".xls";

// Set headers cho file Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Bắt đầu xuất nội dung HTML cho Excel
echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #0ea5e9; text-align: center; }
        h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #0ea5e9; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background-color: #0ea5e9; color: white; padding: 10px; text-align: left; font-weight: bold; }
        td { padding: 8px; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .summary-box { background-color: #e0f2fe; padding: 15px; margin: 20px 0; border-left: 4px solid #0ea5e9; }
        .info-text { color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>BÁO CÁO THỐNG KÊ PHÒNG TẬP GYM</h1>
    
    <div class="summary-box">
        <p><strong>Kỳ báo cáo:</strong> 
            <?php 
            switch ($period) {
                case 'month': echo "Tháng " . date('m/Y'); break;
                case 'quarter': echo "Quý " . ceil(date('m') / 3) . "/" . date('Y'); break;
                case 'year': echo "Năm " . date('Y'); break;
            }
            ?>
        </p>
        <p><strong>Từ ngày:</strong> <?php echo date('d/m/Y', strtotime($startDate)); ?> 
           <strong>đến ngày:</strong> <?php echo date('d/m/Y', strtotime($endDate)); ?></p>
        <p><strong>Ngày xuất báo cáo:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <!-- TỔNG QUAN -->
    <h2>1. TỔNG QUAN</h2>
    <table>
        <tr>
            <th>Chỉ số</th>
            <th>Giá trị</th>
            <th>So với kỳ trước</th>
        </tr>
        <tr>
            <td>Tổng doanh thu</td>
            <td><?php echo formatPrice($revenue); ?></td>
            <td><?php echo ($revenueChange >= 0 ? '+' : '') . number_format($revenueChange, 1) . '%'; ?></td>
        </tr>
        <tr>
            <td>Tổng khách hàng</td>
            <td><?php echo formatNumber($totalCustomers); ?></td>
            <td>-</td>
        </tr>
        <tr>
            <td>Khách hàng mới</td>
            <td><?php echo formatNumber($newCustomers); ?></td>
            <td><?php echo ($customerChange >= 0 ? '+' : '') . number_format($customerChange, 1) . '%'; ?></td>
        </tr>
        <tr>
            <td>Gói tập đã bán</td>
            <td><?php echo formatNumber($packagesSold); ?></td>
            <td><?php echo ($packagesSoldChange >= 0 ? '+' : '') . number_format($packagesSoldChange, 1) . '%'; ?></td>
        </tr>
        <tr>
            <td>Tỷ lệ tham gia</td>
            <td><?php echo number_format($participationRate, 1); ?>%</td>
            <td><?php echo ($participationRateChange >= 0 ? '+' : '') . number_format($participationRateChange, 1) . '%'; ?></td>
        </tr>
    </table>

    <!-- DOANH THU THEO THÁNG -->
    <h2>2. DOANH THU THEO THÁNG (12 tháng gần nhất)</h2>
    <table>
        <tr>
            <th>Tháng</th>
            <th>Doanh thu</th>
        </tr>
        <?php foreach ($revenueByMonth as $item): ?>
        <tr>
            <td><?php echo $item['month']; ?></td>
            <td><?php echo formatPrice($item['revenue']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- KHÁCH HÀNG MỚI THEO THÁNG -->
    <h2>3. KHÁCH HÀNG ĐĂNG KÝ MỚI THEO THÁNG</h2>
    <table>
        <tr>
            <th>Tháng</th>
            <th>Số lượng khách hàng mới</th>
        </tr>
        <?php foreach ($newCustomersByMonth as $item): ?>
        <tr>
            <td><?php echo $item['month']; ?></td>
            <td><?php echo formatNumber($item['count']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- TOP GÓI TẬP BÁN CHẠY -->
    <h2>4. TOP 10 GÓI TẬP BÁN CHẠY</h2>
    <?php if (!empty($topSellingPackages)): ?>
    <table>
        <tr>
            <th>STT</th>
            <th>Mã gói</th>
            <th>Tên gói tập</th>
            <th>Loại gói</th>
            <th>Giá</th>
            <th>Số lượng bán</th>
            <th>Doanh thu</th>
        </tr>
        <?php foreach ($topSellingPackages as $index => $package): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($package['code']); ?></td>
            <td><?php echo htmlspecialchars($package['name']); ?></td>
            <td><?php echo htmlspecialchars($package['type']); ?></td>
            <td><?php echo formatPrice($package['price']); ?></td>
            <td><?php echo formatNumber($package['quantity']); ?></td>
            <td><?php echo formatPrice($package['revenue']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p class="info-text">Chưa có dữ liệu</p>
    <?php endif; ?>

    <!-- HIỆU SUẤT NHÂN VIÊN -->
    <h2>5. HIỆU SUẤT NHÂN VIÊN (Top 10)</h2>
    <?php if (!empty($employeePerformance)): ?>
    <table>
        <tr>
            <th>STT</th>
            <th>Họ tên</th>
            <th>Vai trò</th>
            <th>Số hóa đơn</th>
            <th>Tổng doanh thu</th>
        </tr>
        <?php foreach ($employeePerformance as $index => $emp): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($emp['name']); ?></td>
            <td><?php echo htmlspecialchars($emp['role']); ?></td>
            <td><?php echo formatNumber($emp['invoices']); ?></td>
            <td><?php echo formatPrice($emp['revenue']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p class="info-text">Chưa có dữ liệu</p>
    <?php endif; ?>

    <!-- DOANH THU THEO PHÒNG TẬP -->
    <h2>6. DOANH THU THEO PHÒNG TẬP</h2>
    <?php if (!empty($revenueByGym)): ?>
    <table>
        <tr>
            <th>STT</th>
            <th>Mã phòng</th>
            <th>Tên phòng tập</th>
            <th>Địa chỉ</th>
            <th>Số hóa đơn</th>
            <th>Số khách hàng</th>
            <th>Doanh thu</th>
        </tr>
        <?php foreach ($revenueByGym as $index => $gym): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($gym['code']); ?></td>
            <td><?php echo htmlspecialchars($gym['name']); ?></td>
            <td><?php echo htmlspecialchars($gym['address']); ?></td>
            <td><?php echo formatNumber($gym['invoices']); ?></td>
            <td><?php echo formatNumber($gym['customers']); ?></td>
            <td><?php echo formatPrice($gym['revenue']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p class="info-text">Chưa có dữ liệu</p>
    <?php endif; ?>

    <!-- TÌNH TRẠNG THIẾT BỊ -->
    <h2>7. TÌNH TRẠNG THIẾT BỊ</h2>
    <?php if (!empty($equipmentStatus)): ?>
    <table>
        <tr>
            <th>Trạng thái</th>
            <th>Số lượng</th>
        </tr>
        <?php foreach ($equipmentStatus as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['status']); ?></td>
            <td><?php echo formatNumber($item['count']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p class="info-text">Chưa có dữ liệu</p>
    <?php endif; ?>

    <!-- THIẾT BỊ CẦN BẢO TRÌ -->
    <h2>8. THIẾT BỊ CẦN BẢO TRÌ</h2>
    <?php if (!empty($equipmentMaintenanceNeeded)): ?>
    <table>
        <tr>
            <th>STT</th>
            <th>Mã thiết bị</th>
            <th>Tên thiết bị</th>
            <th>Loại thiết bị</th>
            <th>Vị trí</th>
            <th>Lần bảo trì cuối</th>
            <th>Số ngày chưa bảo trì</th>
        </tr>
        <?php foreach ($equipmentMaintenanceNeeded as $index => $eq): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($eq['code']); ?></td>
            <td><?php echo htmlspecialchars($eq['name']); ?></td>
            <td><?php echo htmlspecialchars($eq['type']); ?></td>
            <td><?php echo htmlspecialchars($eq['location']); ?></td>
            <td><?php echo $eq['last_maintenance'] ? date('d/m/Y', strtotime($eq['last_maintenance'])) : 'Chưa bảo trì'; ?></td>
            <td><?php echo $eq['days_since']; ?> ngày</td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p class="info-text">Tất cả thiết bị đều được bảo trì định kỳ</p>
    <?php endif; ?>

    <!-- PHÂN BỐ GÓI TẬP -->
    <h2>9. PHÂN BỐ GÓI TẬP</h2>
    <?php if (!empty($packageDistribution)): ?>
    <table>
        <tr>
            <th>Tên gói</th>
            <th>Số lượng</th>
        </tr>
        <?php foreach ($packageDistribution as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['label']); ?></td>
            <td><?php echo formatNumber($item['value']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p class="info-text">Chưa có dữ liệu</p>
    <?php endif; ?>

    <div style="margin-top: 50px; padding-top: 20px; border-top: 2px solid #ddd;">
        <p class="info-text">
            <strong>Ghi chú:</strong><br>
            - Báo cáo này được tạo tự động từ hệ thống quản lý phòng tập gym<br>
            - Dữ liệu được tính từ <?php echo date('d/m/Y', strtotime($startDate)); ?> 
              đến <?php echo date('d/m/Y', strtotime($endDate)); ?><br>
            - Tỷ lệ % thay đổi được so sánh với kỳ trước tương ứng<br>
        </p>
    </div>
</body>
</html>
<?php
$conn->close();
exit();
?>