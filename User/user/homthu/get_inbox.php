<?php
/**
 * get_inbox.php - Lấy danh sách thông báo và tin nhắn cho người dùng
 */
// Chỉ bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    error_log('⚠️ get_inbox.php: User not logged in');
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập', 'notifications' => [], 'unread_count' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

error_log('✅ get_inbox.php: User logged in - ' . ($_SESSION['user']['username'] ?? 'unknown'));

try {
    $username = $_SESSION['user']['username'];
    
    // Lấy khach_hang_id
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$khachHang || !isset($khachHang['khach_hang_id'])) {
        error_log('⚠️ get_inbox.php: Không tìm thấy khach_hang_id cho username: ' . $username);
        error_log('⚠️ Có thể user này là nhân viên, không phải khách hàng');
        // Vẫn trả về success nhưng với mảng rỗng
        echo json_encode([
            'success' => true, 
            'message' => 'Không tìm thấy thông tin khách hàng (có thể là nhân viên)', 
            'notifications' => [], 
            'unread_count' => 0
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $khach_hang_id = (int)$khachHang['khach_hang_id'];
    error_log('✅ get_inbox.php: Tìm thấy khach_hang_id: ' . $khach_hang_id . ' (type: ' . gettype($khach_hang_id) . ') cho username: ' . $username);
    
    // Lấy tất cả thông báo của user (cá nhân và chung)
    $notifications = [];
    $hotroCount = 0;
    
    try {
        error_log('🔍 Đang lấy thông báo cho khach_hang_id: ' . $khach_hang_id);
        
        // ƯU TIÊN: Lấy dữ liệu từ bảng Hotro TRƯỚC (hòm thư hỗ trợ)
        try {
            $checkTableHotro = $pdo->query("SHOW TABLES LIKE 'Hotro'");
            if ($checkTableHotro->rowCount() > 0) {
                error_log('🔍 Đang lấy dữ liệu từ Hotro cho khach_hang_id: ' . $khach_hang_id);
                
                // Kiểm tra xem có dữ liệu nào trong bảng Hotro không
                // Thử cả INT và STRING để đảm bảo match
                $checkDataStmt = $pdo->prepare("SELECT COUNT(*) as total FROM Hotro WHERE khach_hang_id = :kh_id OR khach_hang_id = CAST(:kh_id AS CHAR)");
                $checkDataStmt->execute([':kh_id' => $khach_hang_id]);
                $checkData = $checkDataStmt->fetch(PDO::FETCH_ASSOC);
                $hotroCount = (int)($checkData['total'] ?? 0);
                error_log('📊 Tổng số bản ghi trong Hotro cho khach_hang_id ' . $khach_hang_id . ' (type: ' . gettype($khach_hang_id) . '): ' . $hotroCount);
                
                // Debug: Kiểm tra trực tiếp với query đơn giản
                $debugStmt = $pdo->prepare("SELECT ho_tro_id, khach_hang_id FROM Hotro WHERE khach_hang_id = ? LIMIT 5");
                $debugStmt->execute([$khach_hang_id]);
                $debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('🔍 Debug query trực tiếp: Tìm thấy ' . count($debugResults) . ' bản ghi');
                if (count($debugResults) > 0) {
                    error_log('🔍 Debug: ' . json_encode($debugResults, JSON_UNESCAPED_UNICODE));
                }
                
                // Debug: Kiểm tra tất cả dữ liệu trong Hotro (không filter)
                $allHotroStmt = $pdo->query("SELECT COUNT(*) as total FROM Hotro");
                $allHotro = $allHotroStmt->fetch();
                error_log('📊 Tổng số bản ghi trong Hotro (tất cả): ' . ($allHotro['total'] ?? 0));
                
                // Debug: Kiểm tra các khach_hang_id có trong Hotro
                $allKhIdsStmt = $pdo->query("SELECT DISTINCT khach_hang_id FROM Hotro WHERE khach_hang_id IS NOT NULL");
                $allKhIds = $allKhIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                error_log('📊 Các khach_hang_id có trong Hotro: ' . json_encode($allKhIds));
                
                if ($hotroCount > 0) {
                    // Query với CAST để đảm bảo kiểu dữ liệu đúng
                    // Sử dụng ? thay vì :kh_id để tránh vấn đề với PDO binding
                    $stmtHotro = $pdo->prepare("SELECT 
                        CAST(ho_tro_id AS UNSIGNED) as thong_bao_id,
                        CONCAT('Yêu cầu hỗ trợ #', ho_tro_id) as tieu_de,
                        CONCAT('📧 Email: ', COALESCE(email, ''), '\n📞 Số điện thoại: ', COALESCE(so_dien_thoai, ''), '\n📝 Nội dung: ', COALESCE(content, ''),
                               IFNULL(CONCAT('\n\n💬 Phản hồi từ nhân viên:\n', phan_hoi), '')) as noi_dung,
                        'Hệ thống' as loai_thong_bao,
                        COALESCE(thoi_gian, ngay_tao, NOW()) as ngay_gui,
                        0 as da_doc,
                        'Cá nhân' as doi_tuong_nhan,
                        CAST(khach_hang_id AS UNSIGNED) as khach_hang_nhan_id
                        FROM Hotro
                        WHERE khach_hang_id = ? AND (trang_thai IS NULL OR trang_thai != 'Đã đóng')
                        ORDER BY COALESCE(thoi_gian, ngay_tao) DESC");
                    $stmtHotro->execute([$khach_hang_id]);
                    $hotroNotifications = $stmtHotro->fetchAll(PDO::FETCH_ASSOC);
                    
                    error_log('🔍 Query executed với khach_hang_id = ' . $khach_hang_id . ' (type: ' . gettype($khach_hang_id) . ')');
                    error_log('🔍 PDO error info: ' . json_encode($stmtHotro->errorInfo()));
                    
                    error_log('📬 Đã lấy được ' . count($hotroNotifications) . ' thông báo từ Hotro');
                    if (count($hotroNotifications) > 0) {
                        error_log('📧 Thông báo đầu tiên từ Hotro: ' . json_encode($hotroNotifications[0], JSON_UNESCAPED_UNICODE));
                        // Đảm bảo tất cả các field đều có giá trị
                        foreach ($hotroNotifications as &$notif) {
                            $notif['thong_bao_id'] = (int)($notif['thong_bao_id'] ?? 0);
                            $notif['da_doc'] = (int)($notif['da_doc'] ?? 0);
                            $notif['khach_hang_nhan_id'] = (int)($notif['khach_hang_nhan_id'] ?? 0);
                            $notif['loai_thong_bao'] = $notif['loai_thong_bao'] ?? 'Hệ thống';
                            $notif['doi_tuong_nhan'] = $notif['doi_tuong_nhan'] ?? 'Cá nhân';
                            $notif['tieu_de'] = $notif['tieu_de'] ?? 'Yêu cầu hỗ trợ';
                            $notif['noi_dung'] = $notif['noi_dung'] ?? '';
                            $notif['ngay_gui'] = $notif['ngay_gui'] ?? date('Y-m-d H:i:s');
                        }
                        unset($notif);
                        
                        // Thêm vào đầu mảng để ưu tiên hiển thị
                        $notifications = array_merge($hotroNotifications, $notifications);
                        error_log('✅ Đã merge ' . count($hotroNotifications) . ' thông báo từ Hotro vào notifications array');
                    } else {
                        error_log('⚠️ Query trả về 0 kết quả mặc dù COUNT > 0. Có thể có vấn đề với query.');
                    }
                } else {
                    error_log('⚠️ Không có dữ liệu trong Hotro cho khach_hang_id: ' . $khach_hang_id);
                }
            } else {
                error_log('⚠️ Bảng Hotro không tồn tại');
            }
        } catch (Exception $e) {
            error_log('⚠️ Lỗi khi lấy dữ liệu từ Hotro: ' . $e->getMessage());
            error_log('⚠️ Stack trace: ' . $e->getTraceAsString());
        }
        
        // Sau đó lấy thông báo từ ThongBao
        try {
            $stmt = $pdo->prepare("SELECT thong_bao_id, tieu_de, noi_dung, loai_thong_bao, ngay_gui, da_doc, doi_tuong_nhan, khach_hang_nhan_id
                                   FROM ThongBao
                                   WHERE (doi_tuong_nhan = 'Tất cả') 
                                      OR (doi_tuong_nhan = 'Khách hàng')
                                      OR (doi_tuong_nhan = 'Cá nhân' AND khach_hang_nhan_id = :kh_id)
                                   ORDER BY ngay_gui DESC");
            $stmt->execute([':kh_id' => $khach_hang_id]);
            $thongBaoNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Gộp với thông báo từ Hotro (đã thêm ở trên)
            $notifications = array_merge($notifications, $thongBaoNotifications);
            
            // Sắp xếp lại theo thời gian (mới nhất trước)
            usort($notifications, function($a, $b) {
                $timeA = strtotime($a['ngay_gui'] ?? $a['thoi_gian'] ?? '1970-01-01');
                $timeB = strtotime($b['ngay_gui'] ?? $b['thoi_gian'] ?? '1970-01-01');
                return $timeB - $timeA;
            });
            
            error_log('📬 Đã lấy được ' . count($thongBaoNotifications) . ' thông báo từ ThongBao');
        } catch (Exception $e) {
            error_log('❌ Error fetching notifications from ThongBao: ' . $e->getMessage());
        }
        
        error_log('📬 Tổng cộng đã lấy được ' . count($notifications) . ' thông báo');
        
        // Debug: Log một vài thông báo đầu tiên
        if (count($notifications) > 0) {
            error_log('📧 Thông báo đầu tiên: ' . json_encode($notifications[0], JSON_UNESCAPED_UNICODE));
        } else {
            // Kiểm tra xem có thông báo nào trong database không
            $checkAllStmt = $pdo->prepare("SELECT COUNT(*) as total FROM ThongBao");
            $checkAllStmt->execute();
            $totalCount = $checkAllStmt->fetch();
            error_log('📊 Tổng số thông báo trong database: ' . ($totalCount['total'] ?? 0));
            
            // Kiểm tra thông báo cá nhân
            $checkPersonalStmt = $pdo->prepare("SELECT COUNT(*) as total FROM ThongBao WHERE khach_hang_nhan_id = :kh_id");
            $checkPersonalStmt->execute([':kh_id' => $khach_hang_id]);
            $personalCount = $checkPersonalStmt->fetch();
            error_log('👤 Số thông báo cá nhân cho khach_hang_id ' . $khach_hang_id . ': ' . ($personalCount['total'] ?? 0));
        }
    } catch (Exception $e) {
        error_log('❌ Error fetching notifications: ' . $e->getMessage());
        error_log('❌ Stack trace: ' . $e->getTraceAsString());
        $notifications = [];
    }
    
    // Đếm số thông báo chưa đọc (bao gồm cả từ Hotro và ThongBao)
    $unread_count = 0;
    try {
        // Đếm từ ThongBao
        $stmt_unread = $pdo->prepare("SELECT COUNT(*) as unread_count
                                       FROM ThongBao
                                       WHERE da_doc = 0
                                       AND ((doi_tuong_nhan = 'Tất cả') 
                                            OR (doi_tuong_nhan = 'Khách hàng')
                                            OR (doi_tuong_nhan = 'Cá nhân' AND khach_hang_nhan_id = :kh_id))");
        $stmt_unread->execute([':kh_id' => $khach_hang_id]);
        $unread = $stmt_unread->fetch();
        $unread_count = (int)($unread['unread_count'] ?? 0);
        
        // Đếm từ Hotro (tất cả đều chưa đọc vì da_doc = 0)
        try {
            $checkTableHotro = $pdo->query("SHOW TABLES LIKE 'Hotro'");
            if ($checkTableHotro->rowCount() > 0) {
                $stmt_hotro_unread = $pdo->prepare("SELECT COUNT(*) as unread_count FROM Hotro WHERE khach_hang_id = :kh_id");
                $stmt_hotro_unread->execute([':kh_id' => $khach_hang_id]);
                $hotro_unread = $stmt_hotro_unread->fetch();
                $hotro_unread_count = (int)($hotro_unread['unread_count'] ?? 0);
                $unread_count += $hotro_unread_count;
                error_log('📊 Unread từ Hotro: ' . $hotro_unread_count . ', Tổng unread: ' . $unread_count);
            }
        } catch (Exception $e) {
            error_log('⚠️ Error counting unread from Hotro: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log('Error counting unread: ' . $e->getMessage());
    }
    
    // Log trước khi trả về
    error_log('📤 get_inbox.php: Sending response - success: true, notifications count: ' . count($notifications) . ', unread_count: ' . $unread_count);
    if (count($notifications) > 0) {
        error_log('📧 First notification: ' . json_encode($notifications[0], JSON_UNESCAPED_UNICODE));
    }
    
    $response = [
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get inbox error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
        'notifications' => [],
        'unread_count' => 0
    ], JSON_UNESCAPED_UNICODE);
}

