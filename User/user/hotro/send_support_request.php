<?php
/**
 * send_support_request.php - Gửi yêu cầu hỗ trợ và tạo thông báo
 */
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

function redirect_back($msg, $type = 'error') {
    // Xác định URL hiện tại để redirect về đúng trang
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Xây dựng URL đúng cách - sử dụng relative path
    if (strpos($referer, 'index.html') !== false || strpos($referer, '/index.html') !== false) {
        // Nếu đang ở index.html thì redirect về đó
        $redirect_url = '../index.html';
    } else {
        // Redirect về support.html
        $redirect_url = 'support.html';
    }
    
    // Thêm query parameters
    $url = $redirect_url . '?msg=' . urlencode($msg) . '&type=' . urlencode($type);
    
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_back('Yêu cầu không hợp lệ', 'error');
}

$name    = trim($_POST['support_name']    ?? '');
$phone   = trim($_POST['support_phone']   ?? '');
$email   = trim($_POST['support_email']   ?? '');
$message = trim($_POST['support_message'] ?? '');

if ($name === '' || $phone === '' || $message === '') {
    redirect_back('Vui lòng điền đầy đủ Họ tên, Số điện thoại và Nội dung', 'error');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_back('Email không hợp lệ', 'error');
}

try {
    $username = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : null;
    $khach_hang_id = null;
    
    // Lấy khach_hang_id nếu đăng nhập
    if ($username) {
        $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $kh = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($kh && isset($kh['khach_hang_id'])) {
            $khach_hang_id = (int)$kh['khach_hang_id'];
            error_log('Found khach_hang_id: ' . $khach_hang_id . ' for user: ' . $username);
        } else {
            error_log('Cannot find khach_hang_id for user: ' . $username);
        }
    } else {
        error_log('User not logged in - cannot create notification');
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // 1. Lưu yêu cầu hỗ trợ vào bảng HoTroYeuCau
        // Kiểm tra xem bảng có tồn tại không
        $tableExists = false;
        try {
            $checkTable = $pdo->query("SHOW TABLES LIKE 'HoTroYeuCau'");
            $tableExists = $checkTable->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Error checking HoTroYeuCau table: ' . $e->getMessage());
        }
        
        // Tạo bảng HoTroYeuCau nếu chưa có (không dùng foreign key để tránh lỗi)
        if (!$tableExists) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS HoTroYeuCau (
                    yeu_cau_id INT AUTO_INCREMENT PRIMARY KEY,
                    ho_ten VARCHAR(100) NOT NULL,
                    sdt VARCHAR(20) NOT NULL,
                    email VARCHAR(100) NULL,
                    noi_dung TEXT NOT NULL,
                    trang_thai ENUM('Mới','Đang xử lý','Đã phản hồi','Đã đóng') DEFAULT 'Mới',
                    khach_hang_id INT NULL,
                    nguoi_dung VARCHAR(50) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_khach_hang (khach_hang_id),
                    INDEX idx_nguoi_dung (nguoi_dung)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                error_log('Created HoTroYeuCau table successfully');
            } catch (Exception $e) {
                error_log('Error creating HoTroYeuCau table: ' . $e->getMessage());
                // Tiếp tục thử insert, có thể bảng đã tồn tại với cấu trúc khác
            }
        }
        
        // 1a. Lưu vào bảng HoTroYeuCau (giữ lại để tương thích)
        $stmt = $pdo->prepare("INSERT INTO HoTroYeuCau (ho_ten, sdt, email, noi_dung, khach_hang_id, nguoi_dung, trang_thai)
                               VALUES (:name, :phone, :email, :content, :kh_id, :user, 'Mới')");
        $stmt->execute([
            ':name'    => $name,
            ':phone'   => $phone,
            ':email'   => $email ?: null,
            ':content' => $message,
            ':kh_id'   => $khach_hang_id ?: null,
            ':user'    => $username ?: null
        ]);
        
        $yeu_cau_id = $pdo->lastInsertId();
        error_log('✅ Yêu cầu hỗ trợ đã được lưu thành công - yeu_cau_id: ' . $yeu_cau_id);
        
        // 1b. Lưu vào bảng Hotro (bảng mới cho hòm thư)
        // Kiểm tra xem bảng Hotro có tồn tại không
        $tableHotroExists = false;
        try {
            $checkTableHotro = $pdo->query("SHOW TABLES LIKE 'Hotro'");
            $tableHotroExists = $checkTableHotro->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Error checking Hotro table: ' . $e->getMessage());
        }
        
        // Tạo bảng Hotro nếu chưa có
        if (!$tableHotroExists) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS Hotro (
                    ho_tro_id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(100) NOT NULL,
                    so_dien_thoai VARCHAR(20) NOT NULL,
                    thoi_gian DATETIME DEFAULT CURRENT_TIMESTAMP,
                    content TEXT NOT NULL,
                    nhan_vien_id INT NULL,
                    phan_hoi TEXT NULL,
                    khach_hang_id INT NULL,
                    trang_thai ENUM('Mới', 'Đang xử lý', 'Đã phản hồi', 'Đã đóng') DEFAULT 'Mới',
                    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
                    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_so_dien_thoai (so_dien_thoai),
                    INDEX idx_khach_hang (khach_hang_id),
                    INDEX idx_nhan_vien (nhan_vien_id),
                    INDEX idx_trang_thai (trang_thai),
                    INDEX idx_thoi_gian (thoi_gian)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                error_log('Created Hotro table successfully');
            } catch (Exception $e) {
                error_log('Error creating Hotro table: ' . $e->getMessage());
            }
        }
        
        // Insert vào Hotro (chỉ lưu nếu có khach_hang_id để hiển thị trong hòm thư)
        if ($khach_hang_id) {
            try {
                $stmtHotro = $pdo->prepare("INSERT INTO Hotro (email, so_dien_thoai, thoi_gian, content, khach_hang_id, trang_thai)
                                               VALUES (:email, :phone, NOW(), :content, :kh_id, 'Mới')");
                $stmtHotro->execute([
                    ':email'   => $email ?: '',
                    ':phone'   => $phone,
                    ':content' => $message,
                    ':kh_id'   => $khach_hang_id
                ]);
                $ho_tro_id = $pdo->lastInsertId();
                error_log('✅ Đã lưu vào Hotro - ho_tro_id: ' . $ho_tro_id . ', khach_hang_id: ' . $khach_hang_id);
                
                // Xác nhận dữ liệu đã được lưu
                $verifyStmt = $pdo->prepare("SELECT * FROM Hotro WHERE ho_tro_id = :ho_tro_id AND khach_hang_id = :kh_id");
                $verifyStmt->execute([':ho_tro_id' => $ho_tro_id, ':kh_id' => $khach_hang_id]);
                $verifyResult = $verifyStmt->fetch();
                if ($verifyResult) {
                    error_log('✅ Đã xác nhận dữ liệu tồn tại trong Hotro: ' . json_encode($verifyResult, JSON_UNESCAPED_UNICODE));
                } else {
                    error_log('⚠️ Cảnh báo: Không tìm thấy dữ liệu sau khi insert vào Hotro');
                }
            } catch (Exception $e) {
                error_log('⚠️ Lỗi khi lưu vào Hotro: ' . $e->getMessage());
                error_log('⚠️ Stack trace: ' . $e->getTraceAsString());
                // Tiếp tục, không rollback vì đã lưu vào HoTroYeuCau
            }
        } else {
            error_log('⚠️ Không lưu vào Hotro vì khach_hang_id là null (user chưa đăng nhập)');
        }
        
        // 2. Tạo thông báo cho khách hàng trong hòm thư (nếu đã đăng nhập)
        if ($khach_hang_id) {
            try {
                $tieu_de = "Yêu cầu hỗ trợ #{$yeu_cau_id} đã được gửi thành công";
                $noi_dung_thong_bao = "Cảm ơn bạn đã gửi yêu cầu hỗ trợ đến DFC Gym!\n\n" .
                                      "📋 Thông tin yêu cầu:\n" .
                                      "• Mã yêu cầu: #{$yeu_cau_id}\n" .
                                      "• Họ tên: {$name}\n" .
                                      "• Số điện thoại: {$phone}\n" .
                                      ($email ? "• Email: {$email}\n" : "") .
                                      "• Trạng thái: Mới\n\n" .
                                      "📝 Nội dung yêu cầu:\n" . 
                                      wordwrap($message, 80, "\n", true) . "\n\n" .
                                      "⏰ Chúng tôi đã nhận được yêu cầu của bạn và sẽ phản hồi trong thời gian sớm nhất (thường trong vòng 24 giờ).\n\n" .
                                      "Bạn có thể xem trạng thái yêu cầu trong hòm thư này.";
                
                // Kiểm tra và đảm bảo bảng ThongBao tồn tại với cấu trúc đúng
                $tableExists = false;
                try {
                    $checkTable = $pdo->query("SHOW TABLES LIKE 'ThongBao'");
                    $tableExists = $checkTable->rowCount() > 0;
                    error_log('📋 Bảng ThongBao ' . ($tableExists ? 'đã tồn tại' : 'chưa tồn tại'));
                } catch (Exception $e) {
                    error_log('Error checking ThongBao table: ' . $e->getMessage());
                }
                
                // Nếu bảng chưa tồn tại, tạo bảng
                if (!$tableExists) {
                    try {
                        $createTableSQL = "CREATE TABLE IF NOT EXISTS ThongBao (
                            thong_bao_id INT AUTO_INCREMENT PRIMARY KEY,
                            tieu_de VARCHAR(200) NOT NULL,
                            noi_dung TEXT NOT NULL,
                            loai_thong_bao ENUM('Hệ thống', 'Khuyến mãi', 'Sự kiện', 'Nhắc nhở') DEFAULT 'Hệ thống',
                            nhan_vien_gui_id INT NULL,
                            doi_tuong_nhan ENUM('Tất cả', 'Khách hàng', 'Nhân viên', 'Cá nhân') DEFAULT 'Cá nhân',
                            khach_hang_nhan_id INT NULL,
                            nhan_vien_nhan_id INT NULL,
                            ngay_gui DATETIME DEFAULT CURRENT_TIMESTAMP,
                            da_doc TINYINT(1) DEFAULT 0,
                            INDEX idx_khach_hang (khach_hang_nhan_id),
                            INDEX idx_doi_tuong (doi_tuong_nhan)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                        $pdo->exec($createTableSQL);
                        error_log('✅ Created ThongBao table successfully');
                    } catch (Exception $e) {
                        error_log('❌ Error creating ThongBao table: ' . $e->getMessage());
                        // Tiếp tục thử insert, có thể bảng đã tồn tại với cấu trúc khác
                    }
                } else {
                    // Kiểm tra xem các cột cần thiết có tồn tại không
                    try {
                        $checkColumns = $pdo->query("SHOW COLUMNS FROM ThongBao LIKE 'khach_hang_nhan_id'");
                        if ($checkColumns->rowCount() == 0) {
                            error_log('⚠️ Cột khach_hang_nhan_id không tồn tại, thử thêm cột...');
                            $pdo->exec("ALTER TABLE ThongBao ADD COLUMN khach_hang_nhan_id INT NULL AFTER doi_tuong_nhan");
                            error_log('✅ Đã thêm cột khach_hang_nhan_id');
                        }
                        
                        $checkColumns2 = $pdo->query("SHOW COLUMNS FROM ThongBao LIKE 'doi_tuong_nhan'");
                        if ($checkColumns2->rowCount() == 0) {
                            error_log('⚠️ Cột doi_tuong_nhan không tồn tại, thử thêm cột...');
                            $pdo->exec("ALTER TABLE ThongBao ADD COLUMN doi_tuong_nhan ENUM('Tất cả', 'Khách hàng', 'Nhân viên', 'Cá nhân') DEFAULT 'Cá nhân' AFTER loai_thong_bao");
                            error_log('✅ Đã thêm cột doi_tuong_nhan');
                        }
                    } catch (Exception $e) {
                        error_log('⚠️ Error checking/adding columns: ' . $e->getMessage());
                        // Tiếp tục, có thể cột đã tồn tại
                    }
                }
                
                // Tạo thông báo trong hòm thư
                error_log('🔄 Bắt đầu tạo thông báo - khach_hang_id: ' . $khach_hang_id . ', yeu_cau_id: ' . $yeu_cau_id);
                
                // Thử insert với nhiều cách để đảm bảo thành công
                try {
                    $stmt = $pdo->prepare("INSERT INTO ThongBao (tieu_de, noi_dung, loai_thong_bao, doi_tuong_nhan, khach_hang_nhan_id, da_doc)
                                           VALUES (:tieu_de, :noi_dung, 'Hệ thống', 'Cá nhân', :kh_id, 0)");
                    $result = $stmt->execute([
                        ':tieu_de' => $tieu_de,
                        ':noi_dung' => $noi_dung_thong_bao,
                        ':kh_id' => $khach_hang_id
                    ]);
                    
                    if (!$result) {
                        $errorInfo = $stmt->errorInfo();
                        error_log('❌ Insert failed: ' . json_encode($errorInfo));
                        throw new Exception('Insert failed: ' . $errorInfo[2]);
                    }
                    
                    $thong_bao_id = $pdo->lastInsertId();
                    
                    if (!$thong_bao_id || $thong_bao_id == 0) {
                        error_log('❌ lastInsertId returned 0 or false');
                        // Thử query lại để xác nhận
                        $checkStmt = $pdo->prepare("SELECT thong_bao_id FROM ThongBao WHERE khach_hang_nhan_id = :kh_id AND tieu_de = :tieu_de ORDER BY thong_bao_id DESC LIMIT 1");
                        $checkStmt->execute([':kh_id' => $khach_hang_id, ':tieu_de' => $tieu_de]);
                        $checkResult = $checkStmt->fetch();
                        if ($checkResult) {
                            $thong_bao_id = $checkResult['thong_bao_id'];
                            error_log('✅ Tìm thấy thông báo bằng query: ' . $thong_bao_id);
                        } else {
                            throw new Exception('Cannot retrieve notification ID after insert');
                        }
                    }
                    
                    error_log('✅ Thông báo đã được tạo thành công trong hòm thư - thong_bao_id: ' . $thong_bao_id . ', khach_hang_id: ' . $khach_hang_id . ', yeu_cau_id: ' . $yeu_cau_id);
                    
                    // Xác nhận thông báo đã được tạo bằng cách query lại
                    $verifyStmt = $pdo->prepare("SELECT COUNT(*) as count FROM ThongBao WHERE thong_bao_id = :tb_id AND khach_hang_nhan_id = :kh_id");
                    $verifyStmt->execute([':tb_id' => $thong_bao_id, ':kh_id' => $khach_hang_id]);
                    $verifyResult = $verifyStmt->fetch();
                    if ($verifyResult && $verifyResult['count'] > 0) {
                        error_log('✅ Đã xác nhận thông báo tồn tại trong database');
                    } else {
                        error_log('⚠️ Cảnh báo: Không tìm thấy thông báo sau khi insert');
                    }
                    
                } catch (PDOException $pdoError) {
                    error_log('❌ PDO Exception khi tạo thông báo: ' . $pdoError->getMessage());
                    error_log('❌ PDO Error Code: ' . $pdoError->getCode());
                    error_log('❌ PDO Error Info: ' . json_encode($pdoError->errorInfo ?? []));
                    throw $pdoError;
                }
            } catch (Exception $notifError) {
                // Log lỗi nhưng không rollback transaction - yêu cầu hỗ trợ vẫn được lưu
                error_log('⚠️ Lỗi khi tạo thông báo trong hòm thư: ' . $notifError->getMessage());
                error_log('⚠️ Stack trace: ' . $notifError->getTraceAsString());
                error_log('⚠️ Yêu cầu hỗ trợ vẫn được lưu thành công (yeu_cau_id: ' . $yeu_cau_id . ')');
                // Không throw để không làm gián đoạn việc lưu yêu cầu hỗ trợ
            }
        } else {
            error_log('⚠️ Không thể tạo thông báo: khach_hang_id is null (user chưa đăng nhập hoặc không tìm thấy)');
            error_log('⚠️ Thông tin session: ' . json_encode([
                'has_session' => isset($_SESSION),
                'has_user' => isset($_SESSION['user']),
                'username' => $username ?? 'null',
                'khach_hang_id' => $khach_hang_id ?? 'null'
            ]));
        }
        
        // Commit transaction - yêu cầu hỗ trợ và thông báo (nếu có) sẽ được lưu
        $pdo->commit();
        error_log('✅ Transaction committed successfully - yeu_cau_id: ' . $yeu_cau_id);
        
        // Thông báo thành công
        $successMessage = 'Gửi yêu cầu hỗ trợ thành công! Chúng tôi đã nhận được yêu cầu của bạn và sẽ phản hồi trong thời gian sớm nhất.';
        if ($khach_hang_id) {
            $successMessage .= ' Bạn có thể xem thông báo xác nhận trong hòm thư.';
        }
        redirect_back($successMessage, 'success');
        
    } catch (Exception $e) {
        // Kiểm tra xem yêu cầu hỗ trợ đã được lưu chưa
        $requestSaved = false;
        if (isset($yeu_cau_id) && $yeu_cau_id > 0) {
            try {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM HoTroYeuCau WHERE yeu_cau_id = :id");
                $checkStmt->execute([':id' => $yeu_cau_id]);
                $requestSaved = $checkStmt->fetchColumn() > 0;
            } catch (Exception $checkEx) {
                error_log('Error checking saved request: ' . $checkEx->getMessage());
            }
        }
        
        if ($requestSaved) {
            // Nếu yêu cầu đã được lưu, commit và redirect thành công
            try {
                if ($pdo->inTransaction()) {
                    $pdo->commit();
                }
            } catch (Exception $commitEx) {
                error_log('Error committing after save: ' . $commitEx->getMessage());
            }
            error_log('⚠️ Có lỗi phụ nhưng yêu cầu hỗ trợ đã được lưu thành công - yeu_cau_id: ' . $yeu_cau_id);
            $successMessage = 'Gửi yêu cầu hỗ trợ thành công! Chúng tôi đã nhận được yêu cầu của bạn và sẽ phản hồi trong thời gian sớm nhất.';
            redirect_back($successMessage, 'success');
        } else {
            // Nếu chưa lưu được, rollback và báo lỗi
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } catch (Exception $rollbackEx) {
                error_log('Error rolling back: ' . $rollbackEx->getMessage());
            }
            error_log('❌ Error in support request transaction: ' . $e->getMessage());
            error_log('❌ Stack trace: ' . $e->getTraceAsString());
            error_log('❌ Error code: ' . $e->getCode());
            // Log thêm thông tin về PDO error nếu có
            if (isset($pdo) && method_exists($pdo, 'errorInfo')) {
                $errorInfo = $pdo->errorInfo();
                if ($errorInfo[0] !== '00000') {
                    error_log('❌ PDO error info: ' . json_encode($errorInfo));
                }
            }
            throw $e;
        }
    }
    
} catch (Throwable $e) {
    error_log('❌ Support submit error: ' . $e->getMessage());
    error_log('❌ Support submit error trace: ' . $e->getTraceAsString());
    error_log('❌ Error code: ' . $e->getCode());
    
    // Hiển thị thông báo lỗi chi tiết hơn trong môi trường local để debug
    $errorMessage = 'Có lỗi hệ thống, vui lòng thử lại sau.';
    if (defined('APP_ENV') && APP_ENV === 'local') {
        $errorMessage .= ' (Lỗi: ' . htmlspecialchars($e->getMessage()) . ')';
    }
    
    redirect_back($errorMessage, 'error');
}
