<?php
require_once __DIR__ . '/management_function.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê chi tiết</title>
    <link rel="stylesheet" href="management_statistics.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .date-filter-container {
            display: flex;
            gap: 12px;
            align-items: center;
            background: rgba(15, 23, 42, 0.8);
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .date-input-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .date-input-group label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            font-weight: 500;
        }
        
        .date-input-group input[type="date"] {
            padding: 8px 12px;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            color: #ffffff;
            font-size: 13px;
            cursor: pointer;
        }
        
        .date-input-group input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
        
        .btn-apply {
            padding: 8px 16px;
            background: #0ea5e9;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
        }
        
        .btn-apply:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }
        
        .period-info {
            background: rgba(14, 165, 233, 0.1);
            border-left: 3px solid #0ea5e9;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #ffffff;
            font-size: 14px;
        }
        
        .period-info strong {
            color: #0ea5e9;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Báo cáo thống kê chi tiết</h1>
            <div class="header-actions">
                <select class="period-select" id="periodSelect" onchange="handlePeriodChange()">
                    <option value="month" <?php echo ($period === 'month') ? 'selected' : ''; ?>>Tháng này</option>
                    <option value="quarter" <?php echo ($period === 'quarter') ? 'selected' : ''; ?>>Quý này</option>
                    <option value="year" <?php echo ($period === 'year') ? 'selected' : ''; ?>>Năm này</option>
                    <option value="custom" <?php echo ($period === 'custom') ? 'selected' : ''; ?>>Tùy chỉnh</option>
                </select>
                <button class="btn btn-secondary" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
            </div>
        </div>

        <!-- Date Range Picker -->
        <div class="date-filter-container" id="dateFilterContainer" style="<?php echo ($period !== 'custom') ? 'display: none;' : ''; ?>">
            <div class="date-input-group">
                <label><i class="fas fa-calendar-alt"></i> Từ ngày:</label>
                <input type="date" id="startDate" value="<?php echo $customStartDate ?? date('Y-m-01'); ?>">
            </div>
            <div class="date-input-group">
                <label><i class="fas fa-calendar-alt"></i> Đến ngày:</label>
                <input type="date" id="endDate" value="<?php echo $customEndDate ?? date('Y-m-t'); ?>">
            </div>
            <button class="btn-apply" onclick="applyCustomDate()">
                <i class="fas fa-filter"></i> Áp dụng
            </button>
        </div>

        <!-- Thông tin kỳ báo cáo -->
        <div class="period-info">
            <strong><i class="fas fa-info-circle"></i> Kỳ báo cáo:</strong> 
            Từ ngày <?php echo date('d/m/Y', strtotime($startDate)); ?> 
            đến ngày <?php echo date('d/m/Y', strtotime($endDate)); ?>
        </div>

        <!-- Stats cơ bản -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Doanh thu</h3>
                    <p class="stat-value"><?php echo formatPrice($revenue); ?></p>
                    <p class="stat-change <?php echo ($revenueChange >= 0) ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-arrow-<?php echo ($revenueChange >= 0) ? 'up' : 'down'; ?>"></i> 
                        <?php echo ($revenueChange >= 0) ? '+' : ''; ?><?php echo number_format($revenueChange, 1); ?>%
                    </p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon customers">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Khách hàng mới</h3>
                    <p class="stat-value"><?php echo formatNumber($newCustomers); ?></p>
                    <p class="stat-change <?php echo ($customerChange >= 0) ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-arrow-<?php echo ($customerChange >= 0) ? 'up' : 'down'; ?>"></i> 
                        <?php echo ($customerChange >= 0) ? '+' : ''; ?><?php echo number_format($customerChange, 1); ?>%
                    </p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon packages">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div class="stat-info">
                    <h3>Gói tập đã bán</h3>
                    <p class="stat-value"><?php echo formatNumber($packagesSold); ?></p>
                    <p class="stat-change <?php echo ($packagesSoldChange >= 0) ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-arrow-<?php echo ($packagesSoldChange >= 0) ? 'up' : 'down'; ?>"></i> 
                        <?php echo ($packagesSoldChange >= 0) ? '+' : ''; ?><?php echo number_format($packagesSoldChange, 1); ?>%
                    </p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon attendance">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Tỷ lệ tham gia</h3>
                    <p class="stat-value"><?php echo number_format($participationRate, 1); ?>%</p>
                    <p class="stat-change <?php echo ($participationRateChange >= 0) ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-arrow-<?php echo ($participationRateChange >= 0) ? 'up' : 'down'; ?>"></i> 
                        <?php echo ($participationRateChange >= 0) ? '+' : ''; ?><?php echo number_format($participationRateChange, 1); ?>%
                    </p>
                </div>
            </div>
        </div>

        <!-- Biểu đồ doanh thu và khách hàng -->
        <div class="grid-2">
            <div class="chart-card">
                <h3>Doanh thu theo tháng</h3>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3>Khách hàng đăng ký mới theo tháng</h3>
                <div class="chart-container">
                    <canvas id="customerChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top gói tập bán chạy -->
        <div class="table-container">
            <h3><i class="fas fa-trophy"></i> Top 10 gói tập bán chạy</h3>
            <?php if (!empty($topSellingPackages)): ?>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã gói</th>
                        <th>Tên gói tập</th>
                        <th>Loại gói</th>
                        <th>Giá</th>
                        <th>Số lượng bán</th>
                        <th>Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topSellingPackages as $index => $package): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($package['code']); ?></td>
                        <td><?php echo htmlspecialchars($package['name']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($package['type']); ?></span></td>
                        <td><?php echo formatPrice($package['price']); ?></td>
                        <td><?php echo formatNumber($package['quantity']); ?></td>
                        <td><?php echo formatPrice($package['revenue']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">Chưa có dữ liệu</div>
            <?php endif; ?>
        </div>

        <!-- Hiệu suất nhân viên -->
        <div class="table-container">
            <h3><i class="fas fa-user-tie"></i> Hiệu suất nhân viên (Top 10)</h3>
            <?php if (!empty($employeePerformance)): ?>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Họ tên</th>
                        <th>Vai trò</th>
                        <th>Số hóa đơn</th>
                        <th>Tổng doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employeePerformance as $index => $emp): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($emp['name']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($emp['role']); ?></span></td>
                        <td><?php echo formatNumber($emp['invoices']); ?></td>
                        <td><?php echo formatPrice($emp['revenue']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">Chưa có dữ liệu</div>
            <?php endif; ?>
        </div>

        <!-- Doanh thu theo phòng tập -->
        <div class="table-container">
            <h3><i class="fas fa-building"></i> Doanh thu theo phòng tập</h3>
            <?php if (!empty($revenueByGym)): ?>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã phòng</th>
                        <th>Tên phòng tập</th>
                        <th>Địa chỉ</th>
                        <th>Số hóa đơn</th>
                        <th>Số khách hàng</th>
                        <th>Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
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
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">Chưa có dữ liệu</div>
            <?php endif; ?>
        </div>

        <!-- DANH SÁCH TẤT CẢ THIẾT BỊ VÀ TRẠNG THÁI BẢO TRÌ -->
        <div class="table-container" style="margin: 0;">
            <h3><i class="fas fa-tools"></i> Danh sách thiết bị và trạng thái bảo trì</h3>
            <?php if (!empty($equipmentMaintenanceAll)): ?>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã TB</th>
                        <th>Tên thiết bị</th>
                        <th>Loại</th>
                        <th>Vị trí</th>
                        <th>Trạng thái</th>
                        <th>Lần bảo trì cuối</th>
                        <th>Số ngày chưa bảo trì</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipmentMaintenanceAll as $index => $eq): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($eq['code']); ?></td>
                        <td><?php echo htmlspecialchars($eq['name']); ?></td>
                        <td><?php echo htmlspecialchars($eq['type']); ?></td>
                        <td><?php echo htmlspecialchars($eq['location']); ?></td>
                        <td>
                            <span class="badge <?php 
                                echo $eq['status'] == 'Đang sử dụng' ? 'badge-success' : 
                                    ($eq['status'] == 'Bảo trì' ? 'badge-warning' : 'badge-danger'); 
                            ?>">
                                <?php echo htmlspecialchars($eq['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $eq['last_maintenance'] ? date('d/m/Y', strtotime($eq['last_maintenance'])) : 'Chưa bảo trì'; ?></td>
                        <td>
                            <span class="badge <?php 
                                echo $eq['days_since'] > 180 ? 'badge-danger' : 
                                    ($eq['days_since'] > 90 ? 'badge-warning' : 'badge-success'); 
                            ?>">
                                <?php echo $eq['days_since']; ?> ngày
                                <?php if ($eq['needs_maintenance']): ?>
                                    <i class="fas fa-exclamation-triangle"></i>
                                <?php endif; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">Chưa có thiết bị nào</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Xử lý thay đổi period
        function handlePeriodChange() {
            const period = document.getElementById('periodSelect').value;
            const dateFilter = document.getElementById('dateFilterContainer');
            
            if (period === 'custom') {
                dateFilter.style.display = 'flex';
            } else {
                dateFilter.style.display = 'none';
                window.location.href = '?period=' + period;
            }
        }

        // Áp dụng ngày tùy chỉnh
        function applyCustomDate() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (!startDate || !endDate) {
                alert('Vui lòng chọn đầy đủ ngày bắt đầu và kết thúc!');
                return;
            }
            
            if (startDate > endDate) {
                alert('Ngày bắt đầu phải nhỏ hơn ngày kết thúc!');
                return;
            }
            
            window.location.href = `?period=custom&start_date=${startDate}&end_date=${endDate}`;
        }

        // Export Excel
        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'export_excel.php?' + params.toString();
        }

        // Charts
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js chưa được load!');
                return;
            }

            // Dữ liệu từ PHP
            const revenueByMonth = <?php echo json_encode($revenueByMonth, JSON_UNESCAPED_UNICODE); ?>;
            const newCustomersByMonth = <?php echo json_encode($newCustomersByMonth, JSON_UNESCAPED_UNICODE); ?>;

            // Biểu đồ doanh thu
            if (revenueByMonth && revenueByMonth.length > 0) {
                const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: revenueByMonth.map(item => item.month),
                        datasets: [{
                            label: 'Doanh thu (VNĐ)',
                            data: revenueByMonth.map(item => item.revenue),
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14, 165, 233, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                callbacks: {
                                    label: function(context) {
                                        return new Intl.NumberFormat('vi-VN', {
                                            style: 'currency',
                                            currency: 'VND'
                                        }).format(context.parsed.y);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { ticks: { color: '#ffffff' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                            y: { ticks: { color: '#ffffff' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } }
                        }
                    }
                });
            }

            // Biểu đồ khách hàng mới
            if (newCustomersByMonth && newCustomersByMonth.length > 0) {
                const customerCtx = document.getElementById('customerChart').getContext('2d');
                new Chart(customerCtx, {
                    type: 'bar',
                    data: {
                        labels: newCustomersByMonth.map(item => item.month),
                        datasets: [{
                            label: 'Khách hàng mới',
                            data: newCustomersByMonth.map(item => item.count),
                            backgroundColor: '#39d98a',
                            borderColor: '#39d98a',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff'
                            }
                        },
                        scales: {
                            x: { ticks: { color: '#ffffff' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                            y: { ticks: { color: '#ffffff' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>