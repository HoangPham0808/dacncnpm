<?php
/**
 * load_schedule.php - API endpoint để load lại phần lịch tập
 * Không reload toàn bộ trang, chỉ load lại phần schedule table
 */
session_start();
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

if (!isset($_SESSION['user'])) {
    echo '<div class="p-4 text-center text-gray-500">Vui lòng đăng nhập</div>';
    exit;
}

$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : null;

try {
    $username = $_SESSION['user']['username'];
    $stmt = $pdo->prepare("SELECT khach_hang_id, phong_tap_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        echo '<div class="p-4 text-center text-gray-500">Không tìm thấy thông tin khách hàng</div>';
        exit;
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    // Đảm bảo phong_tap_id là integer, không phải string
    $phong_tap_id = isset($khachHang['phong_tap_id']) && $khachHang['phong_tap_id'] !== null ? (int)$khachHang['phong_tap_id'] : null;
    error_log("Load schedule - khach_hang_id: {$khach_hang_id}, phong_tap_id: " . ($phong_tap_id ?? 'NULL') . " (type: " . ($phong_tap_id ? gettype($phong_tap_id) : 'NULL') . ")");
    
    // Debug log
    error_log("Load schedule - khach_hang_id: {$khach_hang_id}, phong_tap_id: " . ($phong_tap_id ?? 'NULL') . ", package_id: " . ($package_id ?? 'NULL'));
    
    // Kiểm tra xem có lịch tập nào của phòng tập này không
    if ($phong_tap_id) {
        $stmt_check_lt = $pdo->prepare("SELECT COUNT(*) as total FROM LichTap WHERE phong_tap_id = :phong_tap_id AND trang_thai != 'Hủy'");
        $stmt_check_lt->execute([':phong_tap_id' => $phong_tap_id]);
        $check_lt = $stmt_check_lt->fetch();
        error_log("Load schedule - Total classes for phong_tap_id {$phong_tap_id}: " . ($check_lt['total'] ?? 0));
    } else {
        error_log("Load schedule - WARNING: phong_tap_id is NULL for khach_hang_id: {$khach_hang_id}");
    }
    
    // Tính toán tuần hiện tại
    $stmt_date = $pdo->query("SELECT 
        CASE 
            WHEN DAYOFWEEK(CURDATE()) = 1 THEN DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            ELSE DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 2) DAY)
        END as monday,
        CASE 
            WHEN DAYOFWEEK(CURDATE()) = 1 THEN DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ELSE DATE_ADD(DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 2) DAY), INTERVAL 6 DAY)
        END as sunday");
    $weekDates = $stmt_date->fetch();
    
    if (!$weekDates) {
        throw new Exception("Could not calculate week dates");
    }
    
    $startDate = $weekDates['monday'];
    $endDate = $weekDates['sunday'];
    
    // Lấy thông tin gói tập
    $userClassIds = [];
    $userRegistrationsMap = [];
    $hasValidPackage = false;
    $packageStartDate = null;
    $packageEndDate = null;
    
    $sql_pkg = "SELECT dang_ky_id, ngay_bat_dau, ngay_ket_thuc
                FROM DangKyGoiTap 
                WHERE khach_hang_id = :kh_id 
                AND trang_thai = 'Đang hoạt động'
                AND DATE(ngay_ket_thuc) >= CURDATE()";
    $params_pkg = [':kh_id' => $khach_hang_id];
    
    if ($package_id) {
        $sql_pkg .= " AND dang_ky_id = :package_id";
        $params_pkg[':package_id'] = $package_id;
    }
    
    $sql_pkg .= " ORDER BY dang_ky_id DESC LIMIT 1";
    
    $stmt_pkg = $pdo->prepare($sql_pkg);
    $stmt_pkg->execute($params_pkg);
    $validPackage = $stmt_pkg->fetch();
    $hasValidPackage = ($validPackage !== false);
    
    if ($hasValidPackage) {
        $packageStartDate = $validPackage['ngay_bat_dau'];
        $packageEndDate = $validPackage['ngay_ket_thuc'];
        
        if ($packageStartDate > $startDate) {
            $startDate = $packageStartDate;
        }
        if ($packageEndDate < $endDate) {
            $endDate = $packageEndDate;
        }
    }
    
    // Lấy danh sách lớp đã đăng ký
    $stmt = $pdo->prepare("SELECT dang_ky_lich_id, lich_tap_id FROM DangKyLichTap 
                           WHERE khach_hang_id = :kh_id 
                           AND trang_thai = 'Đã đăng ký'");
    $stmt->execute([':kh_id' => $khach_hang_id]);
    $userRegistrations = $stmt->fetchAll();
    foreach ($userRegistrations as $reg) {
        $userClassIds[] = $reg['lich_tap_id'];
        $userRegistrationsMap[$reg['lich_tap_id']] = $reg['dang_ky_lich_id'];
    }
    
    // Lấy lịch tập
    // QUAN TRỌNG: Phải lấy phong_tap_id từ LichTap để đảm bảo filter đúng
    // CHỈ HIỂN THỊ LỊCH TẬP CỦA PHÒNG TẬP MÀ KHÁCH HÀNG ĐÃ ĐĂNG KÝ
    $sql = "SELECT lt.lich_tap_id, lt.ten_lop, lt.ngay_tap, lt.gio_bat_dau, lt.gio_ket_thuc, 
                   lt.so_luong_toi_da, lt.phong, lt.trang_thai, lt.phong_tap_id,
                   nv.ho_ten as ten_huan_luyen_vien,
                   COUNT(dk.dang_ky_lich_id) as so_luong_da_dang_ky
            FROM LichTap lt
            LEFT JOIN nhanvien nv ON lt.nhan_vien_pt_id = nv.nhan_vien_id
            LEFT JOIN DangKyLichTap dk ON lt.lich_tap_id = dk.lich_tap_id 
                AND dk.trang_thai = 'Đã đăng ký'
            WHERE lt.ngay_tap >= :start_date AND lt.ngay_tap <= :end_date
            AND lt.trang_thai != 'Hủy'";
    
    // BẮT BUỘC: User chỉ xem được lịch tập của phòng tập mà họ đã đăng ký
    if ($phong_tap_id && $phong_tap_id > 0) {
        // Sử dụng so sánh trực tiếp với integer (không cần CAST vì đã ép kiểu ở trên)
        $sql .= " AND lt.phong_tap_id = :phong_tap_id";
        error_log("Load schedule filter - Only showing classes for phong_tap_id: {$phong_tap_id} (type: " . gettype($phong_tap_id) . ")");
    } else {
        // Nếu khách hàng chưa có phòng tập, không hiển thị lịch nào
        $sql .= " AND 1 = 0";
        error_log("Load schedule filter - No phong_tap_id or invalid, showing no classes. phong_tap_id value: " . var_export($phong_tap_id, true));
    }
    
    // Nếu có gói tập hợp lệ, chỉ hiển thị các lớp trong khoảng thời gian gói tập
    // Nếu không có gói tập hợp lệ (đã hủy), vẫn hiển thị lịch tập nhưng không cho đăng ký
    if ($hasValidPackage && $packageStartDate && $packageEndDate) {
        $sql .= " AND lt.ngay_tap >= :package_start AND lt.ngay_tap <= :package_end";
    }
    // Nếu không có gói tập hợp lệ, vẫn hiển thị lịch tập (không filter theo ngày gói tập)
    
    $sql .= " GROUP BY lt.lich_tap_id
              ORDER BY lt.ngay_tap ASC, lt.gio_bat_dau ASC";
    
    $stmt = $pdo->prepare($sql);
    $params = [
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ];
    
    // Thêm điều kiện lọc theo phòng tập - ĐẢM BẢO LÀ INTEGER
    if ($phong_tap_id && $phong_tap_id > 0) {
        $params[':phong_tap_id'] = (int)$phong_tap_id; // Ép kiểu về integer
        error_log("Load schedule binding phong_tap_id: " . (int)$phong_tap_id . " (type: " . gettype((int)$phong_tap_id) . ")");
    } else {
        error_log("ERROR: phong_tap_id is missing or invalid. Value: " . var_export($phong_tap_id, true));
    }
    
    if ($hasValidPackage && $packageStartDate && $packageEndDate) {
        $params[':package_start'] = $packageStartDate;
        $params[':package_end'] = $packageEndDate;
    }
    
    // Debug: Log tất cả parameters
    error_log("=== LOAD SCHEDULE QUERY DEBUG ===");
    error_log("User phong_tap_id: " . var_export($phong_tap_id, true) . " (type: " . ($phong_tap_id ? gettype($phong_tap_id) : 'NULL') . ")");
    error_log("Query params: " . print_r($params, true));
    error_log("Query SQL: " . $sql);
    
    $stmt->execute($params);
    $classes = $stmt->fetchAll();
    
    // Debug: Log số lượng kết quả và chi tiết
    error_log("Query result: " . count($classes) . " classes found");
    if (count($classes) > 0) {
        error_log("First class phong_tap_id: " . var_export($classes[0]['phong_tap_id'] ?? 'N/A', true));
        // Kiểm tra tất cả lịch tập có đúng phong_tap_id không
        $wrong_phong_tap = [];
        foreach ($classes as $class) {
            $class_pt_id = isset($class['phong_tap_id']) ? (int)$class['phong_tap_id'] : null;
            if ($class_pt_id != $phong_tap_id) {
                $wrong_phong_tap[] = "lich_tap_id: {$class['lich_tap_id']}, phong_tap_id: {$class_pt_id}";
            }
        }
        if (!empty($wrong_phong_tap)) {
            error_log("WARNING: Found classes with wrong phong_tap_id: " . implode(", ", $wrong_phong_tap));
        } else {
            error_log("✓ All classes have correct phong_tap_id: {$phong_tap_id}");
        }
    } else {
        // Kiểm tra xem có lịch tập nào trong database không
        $stmt_check = $pdo->prepare("SELECT COUNT(*) as total, GROUP_CONCAT(DISTINCT phong_tap_id) as phong_tap_ids FROM LichTap WHERE ngay_tap >= :start_date AND ngay_tap <= :end_date AND trang_thai != 'Hủy'");
        $stmt_check->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $check_result = $stmt_check->fetch();
        error_log("Total classes in DB for date range: " . ($check_result['total'] ?? 0) . ", phong_tap_ids: " . ($check_result['phong_tap_ids'] ?? 'N/A'));
        
        // Kiểm tra lịch tập của phòng tập này
        if ($phong_tap_id && $phong_tap_id > 0) {
            $stmt_check_pt = $pdo->prepare("SELECT COUNT(*) as total FROM LichTap WHERE phong_tap_id = :phong_tap_id AND ngay_tap >= :start_date AND ngay_tap <= :end_date AND trang_thai != 'Hủy'");
            $stmt_check_pt->execute([':phong_tap_id' => $phong_tap_id, ':start_date' => $startDate, ':end_date' => $endDate]);
            $check_pt_result = $stmt_check_pt->fetch();
            error_log("Classes for phong_tap_id {$phong_tap_id}: " . ($check_pt_result['total'] ?? 0));
        }
    }
    error_log("=== END LOAD SCHEDULE QUERY DEBUG ===");
    
    // Tổ chức dữ liệu theo ngày và giờ
    $scheduleData = [];
    foreach ($classes as $class) {
        $ngay = $class['ngay_tap'];
        
        if ($hasValidPackage && $packageStartDate && $packageEndDate) {
            if ($ngay < $packageStartDate || $ngay > $packageEndDate) {
                continue;
            }
        }
        
        $dayOfWeekNum = date('N', strtotime($ngay));
        $gio_bd = substr($class['gio_bat_dau'], 0, 5);
        $gio_kt = substr($class['gio_ket_thuc'], 0, 5);
        
        // Tính toán các timeSlot mà lịch tập này span qua
        list($bd_h, $bd_m) = explode(':', $gio_bd);
        list($kt_h, $kt_m) = explode(':', $gio_kt);
        $startMinutes = (int)$bd_h * 60 + (int)$bd_m;
        $endMinutes = (int)$kt_h * 60 + (int)$kt_m;
        
        // Tính số giờ (làm tròn lên)
        $durationHours = ceil(($endMinutes - $startMinutes) / 60);
        
        // Định nghĩa các timeSlot có sẵn trong hệ thống (mở rộng để hỗ trợ lịch tập dài hơn)
        $availableTimeSlots = [
            '06:00 - 07:00',
            '07:00 - 08:00',
            '08:00 - 09:00',
            '09:00 - 10:00',
            '10:00 - 11:00',
            '11:00 - 12:00',
            '12:00 - 13:00',
            '13:00 - 14:00',
            '14:00 - 15:00',
            '15:00 - 16:00',
            '16:00 - 17:00',
            '17:00 - 18:00',
            '18:00 - 19:00',
            '19:00 - 20:00',
            '19:30 - 20:30',
            '20:00 - 21:00',
            '21:00 - 22:00',
            '22:00 - 23:00'
        ];
        
        // Tìm các timeSlot mà lịch tập này span qua (bao gồm cả các timeSlot không có trong danh sách)
        $affectedTimeSlots = [];
        $currentMinutes = $startMinutes;
        
        // Tính số giờ cần span (làm tròn lên để đảm bảo bao gồm timeSlot cuối cùng)
        $hoursToSpan = ceil(($endMinutes - $startMinutes) / 60);
        
        for ($i = 0; $i < $hoursToSpan; $i++) {
            $currentHour = floor($currentMinutes / 60);
            $currentMin = $currentMinutes % 60;
            $nextHour = floor(($currentMinutes + 60) / 60);
            $nextMin = ($currentMinutes + 60) % 60;
            
            $slotStart = sprintf('%02d:%02d', $currentHour, $currentMin);
            $slotEnd = sprintf('%02d:%02d', $nextHour, $nextMin);
            $timeSlot = $slotStart . ' - ' . $slotEnd;
            
            // Thêm tất cả các timeSlot mà lịch tập span qua (không chỉ những cái có trong danh sách)
            $affectedTimeSlots[] = $timeSlot;
            $currentMinutes += 60;
        }
        
        // Tìm timeSlot đầu tiên mà lịch tập bắt đầu (ưu tiên timeSlot có trong danh sách)
        $firstTimeSlot = null;
        
        // Tìm trong danh sách timeSlot có sẵn trước
        foreach ($availableTimeSlots as $slot) {
            list($slotStart, $slotEnd) = explode(' - ', $slot);
            list($slot_h, $slot_m) = explode(':', $slotStart);
            $slotStartMinutes = (int)$slot_h * 60 + (int)$slot_m;
            list($slotEnd_h, $slotEnd_m) = explode(':', $slotEnd);
            $slotEndMinutes = (int)$slotEnd_h * 60 + (int)$slotEnd_m;
            
            // Kiểm tra xem lịch tập có bắt đầu trong khoảng timeSlot này không
            if ($startMinutes >= $slotStartMinutes && $startMinutes < $slotEndMinutes) {
                $firstTimeSlot = $slot;
                break;
            }
        }
        
        // Nếu không tìm thấy trong danh sách, dùng timeSlot đầu tiên từ affectedTimeSlots
        if (!$firstTimeSlot && !empty($affectedTimeSlots)) {
            $firstTimeSlot = $affectedTimeSlots[0];
        }
        
        // Nếu vẫn không có, tạo timeSlot từ giờ bắt đầu và kết thúc
        if (!$firstTimeSlot) {
            $firstTimeSlot = $gio_bd . ' - ' . $gio_kt;
        }
        
        // Lưu thông tin duration và affected timeSlots vào class
        $class['duration_hours'] = $durationHours;
        $class['original_timeSlot'] = $gio_bd . ' - ' . $gio_kt;
        $class['affected_timeSlots'] = $affectedTimeSlots;
        
        if (!isset($scheduleData[$firstTimeSlot])) {
            $scheduleData[$firstTimeSlot] = [];
        }
        
        // Cho phép nhiều lịch tập trong cùng timeSlot và dayOfWeekNum
        if (!isset($scheduleData[$firstTimeSlot][$dayOfWeekNum])) {
            $scheduleData[$firstTimeSlot][$dayOfWeekNum] = [];
        }
        
        // Đảm bảo luôn là mảng các lịch tập
        if (!is_array($scheduleData[$firstTimeSlot][$dayOfWeekNum])) {
            // Nếu đã có dữ liệu cũ (không phải mảng), chuyển thành mảng
            $scheduleData[$firstTimeSlot][$dayOfWeekNum] = [$scheduleData[$firstTimeSlot][$dayOfWeekNum]];
        }
        
        // Thêm lịch tập vào mảng (không ghi đè)
        $scheduleData[$firstTimeSlot][$dayOfWeekNum][] = $class;
    }
    
    // Định nghĩa các khung giờ để hiển thị (mở rộng để hỗ trợ lịch tập dài hơn)
    $timeSlots = [
        '06:00 - 07:00',
        '07:00 - 08:00',
        '08:00 - 09:00',
        '09:00 - 10:00',
        '10:00 - 11:00',
        '11:00 - 12:00',
        '12:00 - 13:00',
        '13:00 - 14:00',
        '14:00 - 15:00',
        '15:00 - 16:00',
        '16:00 - 17:00',
        '17:00 - 18:00',
        '18:00 - 19:00',
        '19:00 - 20:00',
        '19:30 - 20:30',
        '20:00 - 21:00',
        '21:00 - 22:00',
        '22:00 - 23:00'
    ];
    
    // Tạo mảng để track các ô đã bị span
    $spannedCells = [];
    
    // Render lịch tập
    foreach ($timeSlots as $slotIndex => $timeSlot) {
        echo '<tr>';
        echo '<td class="p-4 border-r border-b border-gray-200 dark:border-gray-800 font-medium text-gray-800 dark:text-gray-200 text-center align-middle">' . htmlspecialchars($timeSlot) . '</td>';
        
        for ($day = 1; $day <= 7; $day++) {
            // Kiểm tra xem ô này có bị span không
            $cellKey = "{$slotIndex}_{$day}";
            if (isset($spannedCells[$cellKey])) {
                // Ô này đã bị span, bỏ qua
                continue;
            }
            
            // Kiểm tra và chuẩn hóa dữ liệu
            $classes = [];
            if (isset($scheduleData[$timeSlot][$day]) && !empty($scheduleData[$timeSlot][$day])) {
                $data = $scheduleData[$timeSlot][$day];
                
                if (is_array($data)) {
                    if (isset($data[0]) && is_array($data[0]) && isset($data[0]['lich_tap_id'])) {
                        $classes = $data;
                    } elseif (isset($data['lich_tap_id'])) {
                        $classes = [$data];
                    }
                }
            }
            
            // Tính rowspan cho lịch tập nhiều giờ
            $rowspan = 1;
            
            if (!empty($classes)) {
                foreach ($classes as $class) {
                    if (isset($class['duration_hours']) && $class['duration_hours'] > 1) {
                        // Tìm index của timeSlot hiện tại trong danh sách hiển thị (đây là ô đầu tiên)
                        $firstTimeSlotIndex = array_search($timeSlot, $timeSlots);
                        
                        // Tính số timeSlot trong danh sách hiển thị mà lịch tập span qua
                        $matchingTimeSlots = 0;
                        if (isset($class['affected_timeSlots']) && is_array($class['affected_timeSlots'])) {
                            foreach ($class['affected_timeSlots'] as $affectedSlot) {
                                // Kiểm tra xem timeSlot này có trong danh sách hiển thị không
                                if (in_array($affectedSlot, $timeSlots)) {
                                    $matchingTimeSlots++;
                                }
                            }
                        }
                        
                        // Nếu không tìm thấy timeSlot nào match, dùng duration_hours
                        if ($matchingTimeSlots > 0) {
                            $rowspan = max($rowspan, $matchingTimeSlots);
                        } else {
                            $rowspan = max($rowspan, $class['duration_hours']);
                        }
                        
                        // Đánh dấu các ô bị span (chỉ các timeSlot có trong danh sách hiển thị, trừ ô đầu tiên)
                        if (isset($class['affected_timeSlots']) && is_array($class['affected_timeSlots'])) {
                            foreach ($class['affected_timeSlots'] as $affectedSlot) {
                                // Kiểm tra xem timeSlot này có trong danh sách hiển thị không
                                $affectedSlotIndex = array_search($affectedSlot, $timeSlots);
                                if ($affectedSlotIndex !== false && $affectedSlotIndex !== $firstTimeSlotIndex) {
                                    // Đánh dấu ô này bị span (trừ ô đầu tiên)
                                    $spannedCells["{$affectedSlotIndex}_{$day}"] = true;
                                }
                            }
                        }
                    }
                }
            }
            
            if (!empty($classes)) {
                $class = $classes[0]; // Lấy class đầu tiên
                $lich_tap_id = $class['lich_tap_id'];
                $ten_lop = htmlspecialchars($class['ten_lop']);
                $ten_hv = htmlspecialchars($class['ten_huan_luyen_vien'] ?? 'Chưa có');
                $so_luong_da_dk = (int)$class['so_luong_da_dang_ky'];
                $so_luong_toi_da = (int)$class['so_luong_toi_da'];
                $trang_thai = $class['trang_thai'];
                $gio_bd = substr($class['gio_bat_dau'], 0, 5);
                $duration = isset($class['duration_hours']) ? $class['duration_hours'] : 1;
                $timeDisplay = isset($class['original_timeSlot']) ? $class['original_timeSlot'] : $gio_bd;
                
                $isRegistered = in_array($lich_tap_id, $userClassIds);
                $isFull = ($trang_thai === 'Đã đầy') || ($so_luong_da_dk >= $so_luong_toi_da);
                $canBook = $hasValidPackage && $trang_thai === 'Đang mở' && !$isRegistered && !$isFull;
                
                $borderClass = ($day < 7) ? 'border-r border-b' : 'border-b';
                if ($timeSlot === '19:30 - 20:30') {
                    $borderClass = ($day < 7) ? 'border-r' : '';
                }
                
                echo '<td class="p-4 ' . $borderClass . ' border-gray-200 dark:border-gray-800 align-top"' . ($rowspan > 1 ? ' rowspan="' . $rowspan . '"' : '') . '>';
                echo '<div class="text-sm font-bold text-primary">' . $timeDisplay;
                if ($duration > 1) {
                    echo ' <span style="font-size: 10px; color: rgba(34, 197, 94, 0.7);">(' . $duration . 'h - ' . $timeDisplay . ')</span>';
                }
                echo '</div>';
                echo '<div class="mt-1 font-semibold text-gray-900 dark:text-white">' . $ten_lop . '</div>';
                echo '<div class="text-xs mt-1">' . $ten_hv . '</div>';
                echo '<div class="flex items-center justify-center mt-2 text-xs">';
                echo '<span class="material-symbols-outlined text-base mr-1">groups</span>' . $so_luong_da_dk . '/' . $so_luong_toi_da;
                echo '</div>';
                
                $ngay_tap_class = $class['ngay_tap'];
                $can_cancel_class = ($ngay_tap_class >= date('Y-m-d'));
                $dang_ky_lich_id_for_class = isset($userRegistrationsMap[$lich_tap_id]) ? $userRegistrationsMap[$lich_tap_id] : null;
                
                if ($canBook) {
                    echo '<form method="POST" action="book_class.php" class="mt-2">';
                    echo '<input type="hidden" name="lich_tap_id" value="' . $lich_tap_id . '">';
                    echo '<button type="submit" class="w-full bg-primary text-white text-xs font-semibold py-1.5 px-3 rounded hover:bg-green-700 transition-colors"><i class="fas fa-plus-circle"></i> Đăng ký</button>';
                    echo '</form>';
                } elseif ($isRegistered) {
                    echo '<div class="mt-2 space-y-1">';
                    echo '<button type="button" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-semibold py-1.5 px-3 rounded cursor-default" disabled>';
                    echo '<i class="fas fa-check"></i> Đã đăng ký</button>';
                    
                    if ($can_cancel_class && $dang_ky_lich_id_for_class) {
                        echo '<button type="button" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-semibold py-1.5 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors" onclick="openEditClassModal(' . $dang_ky_lich_id_for_class . ', ' . $lich_tap_id . ', ' . json_encode($ten_lop) . ')">';
                        echo '<i class="fas fa-edit"></i> Sửa</button>';
                        
                        $confirmMsg2 = json_encode('Bạn có chắc chắn muốn hủy lớp này?');
                        echo '<form method="POST" action="cancel_class.php" onsubmit="return confirm(' . $confirmMsg2 . ');">';
                        echo '<input type="hidden" name="dang_ky_lich_id" value="' . (int)$dang_ky_lich_id_for_class . '">';
                        echo '<input type="hidden" name="lich_tap_id" value="' . (int)$lich_tap_id . '">';
                        echo '<button type="submit" class="w-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-semibold py-1.5 px-3 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">';
                        echo '<i class="fas fa-times"></i> Hủy</button>';
                        echo '</form>';
                    }
                    echo '</div>';
                } elseif (!$hasValidPackage) {
                    echo '<div class="mt-2 text-xs text-center text-gray-500 dark:text-gray-400">';
                    echo '<i class="fas fa-info-circle"></i> Vui lòng chọn gói tập ở trên';
                    echo '</div>';
                } elseif ($trang_thai === 'Đã đầy' || $isFull) {
                    echo '<button type="button" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 text-xs font-semibold py-1.5 px-3 rounded cursor-default opacity-50" disabled>';
                    echo '<i class="fas fa-times"></i> Đã đầy</button>';
                } elseif ($trang_thai === 'Hủy') {
                    echo '<button type="button" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 text-xs font-semibold py-1.5 px-3 rounded cursor-default opacity-50" disabled>';
                    echo '<i class="fas fa-ban"></i> Đã hủy</button>';
                } elseif ($trang_thai !== 'Đang mở') {
                    echo '<button type="button" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 text-xs font-semibold py-1.5 px-3 rounded cursor-default opacity-50" disabled>';
                    echo '<i class="fas fa-ban"></i> Đã đóng</button>';
                }
                
                echo '</td>';
            } else {
                // Kiểm tra xem ô này có bị span không
                if (!isset($spannedCells[$cellKey])) {
                    // Ô trống bình thường
                    $borderClass = ($day < 7) ? 'border-r border-b' : 'border-b';
                    if ($timeSlot === '19:30 - 20:30') {
                        $borderClass = ($day < 7) ? 'border-r' : '';
                    }
                    echo '<td class="p-4 ' . $borderClass . ' border-gray-200 dark:border-gray-800 text-center text-gray-400">-</td>';
                }
                // Nếu bị span, không render gì
            }
        }
        echo '</tr>';
    }
    
} catch (Exception $e) {
    echo '<div class="p-4 text-center text-red-500">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

