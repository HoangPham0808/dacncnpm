<?php
/**
 * Script kiểm tra lịch tập trong database
 * Chạy file này để xem lịch tập có trong database không
 */

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Tính toán tuần hiện tại theo PHP
$today = new DateTime();
$dayOfWeek = $today->format('N'); // 1 (Monday) to 7 (Sunday)
$daysFromMonday = $dayOfWeek - 1;
$monday = clone $today;
$monday->modify("-{$daysFromMonday} days");
$sunday = clone $monday;
$sunday->modify('+6 days');

$startDate = $monday->format('Y-m-d');
$endDate = $sunday->format('Y-m-d');

echo "<h2>Kiểm tra Lịch Tập</h2>";
echo "<p><strong>Tuần hiện tại (PHP):</strong> {$startDate} đến {$endDate}</p>";
echo "<p><strong>Ngày hiện tại:</strong> " . date('Y-m-d') . " (" . date('l') . ")</p>";

// Kiểm tra trong database
try {
    // Lấy tất cả lịch tập trong tuần
    $stmt = $pdo->prepare("SELECT 
                                lt.lich_tap_id, 
                                lt.ten_lop, 
                                lt.ngay_tap, 
                                DAYNAME(lt.ngay_tap) as 'Thu',
                                lt.gio_bat_dau, 
                                lt.gio_ket_thuc, 
                                lt.phong, 
                                lt.trang_thai,
                                COUNT(dk.dang_ky_lich_id) as so_luong_da_dk,
                                lt.so_luong_toi_da
                            FROM LichTap lt
                            LEFT JOIN DangKyLichTap dk ON lt.lich_tap_id = dk.lich_tap_id 
                                AND dk.trang_thai = 'Đã đăng ký'
                            WHERE lt.ngay_tap >= :start_date AND lt.ngay_tap <= :end_date
                            GROUP BY lt.lich_tap_id
                            ORDER BY lt.ngay_tap ASC, lt.gio_bat_dau ASC");
    $stmt->execute([
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ]);
    $classes = $stmt->fetchAll();
    
    echo "<h3>Tìm thấy " . count($classes) . " lớp trong tuần này:</h3>";
    
    if (count($classes) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr>";
        echo "<th>ID</th><th>Tên lớp</th><th>Ngày</th><th>Thứ</th><th>Giờ</th><th>Phòng</th><th>Đã đăng ký</th><th>Tối đa</th><th>Trạng thái</th>";
        echo "</tr>";
        
        foreach ($classes as $class) {
            echo "<tr>";
            echo "<td>{$class['lich_tap_id']}</td>";
            echo "<td>{$class['ten_lop']}</td>";
            echo "<td>{$class['ngay_tap']}</td>";
            echo "<td>{$class['Thu']}</td>";
            echo "<td>" . substr($class['gio_bat_dau'], 0, 5) . " - " . substr($class['gio_ket_thuc'], 0, 5) . "</td>";
            echo "<td>{$class['phong']}</td>";
            echo "<td>{$class['so_luong_da_dk']}</td>";
            echo "<td>{$class['so_luong_toi_da']}</td>";
            echo "<td>{$class['trang_thai']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>❌ Không tìm thấy lớp nào trong tuần này!</strong></p>";
        
        // Kiểm tra xem có lịch tập nào trong database không
        $stmt_all = $pdo->query("SELECT COUNT(*) as total FROM LichTap");
        $total = $stmt_all->fetch()['total'];
        echo "<p>Tổng số lịch tập trong database: <strong>{$total}</strong></p>";
        
        if ($total > 0) {
            // Lấy 5 lịch tập gần nhất
            $stmt_recent = $pdo->query("SELECT lich_tap_id, ten_lop, ngay_tap, DAYNAME(ngay_tap) as 'Thu', gio_bat_dau, gio_ket_thuc 
                                        FROM LichTap 
                                        ORDER BY ngay_tap DESC 
                                        LIMIT 5");
            $recent = $stmt_recent->fetchAll();
            
            echo "<h4>5 lịch tập gần nhất trong database:</h4>";
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Tên lớp</th><th>Ngày</th><th>Thứ</th><th>Giờ</th></tr>";
            foreach ($recent as $r) {
                echo "<tr>";
                echo "<td>{$r['lich_tap_id']}</td>";
                echo "<td>{$r['ten_lop']}</td>";
                echo "<td>{$r['ngay_tap']}</td>";
                echo "<td>{$r['Thu']}</td>";
                echo "<td>" . substr($r['gio_bat_dau'], 0, 5) . " - " . substr($r['gio_ket_thuc'], 0, 5) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<p style='color: orange;'><strong>⚠️ Có thể ngày của lịch tập không nằm trong tuần hiện tại!</strong></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Lỗi:</strong> " . $e->getMessage() . "</p>";
}

// Kiểm tra cách tính ngày của MySQL
echo "<hr>";
echo "<h3>So sánh cách tính ngày:</h3>";

$stmt_mysql = $pdo->query("SELECT 
    CURDATE() as 'Ngay_hien_tai',
    DAYNAME(CURDATE()) as 'Thu',
    DAYOFWEEK(CURDATE()) as 'So_thu',
    DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 2) DAY) as 'Thu_Hai_cua_tuan',
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 2) DAY), INTERVAL 6 DAY) as 'Chu_Nhat_cua_tuan'");
$mysql_week = $stmt_mysql->fetch();

echo "<p><strong>MySQL tính:</strong></p>";
echo "<ul>";
echo "<li>Ngày hiện tại: {$mysql_week['Ngay_hien_tai']} ({$mysql_week['Thu']})</li>";
echo "<li>Thứ Hai của tuần: {$mysql_week['Thu_Hai_cua_tuan']}</li>";
echo "<li>Chủ Nhật của tuần: {$mysql_week['Chu_Nhat_cua_tuan']}</li>";
echo "</ul>";

echo "<p><strong>PHP tính:</strong></p>";
echo "<ul>";
echo "<li>Ngày hiện tại: " . date('Y-m-d') . " (" . date('l') . ")</li>";
echo "<li>Thứ Hai của tuần: {$startDate}</li>";
echo "<li>Chủ Nhật của tuần: {$endDate}</li>";
echo "</ul>";

?>

