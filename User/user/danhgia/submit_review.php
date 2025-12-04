<?php
/**
 * submit_review.php - Gửi đánh giá và tạo thông báo
 */
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user'])) {
    header('Location: review.html?msg=' . urlencode('Vui lòng đăng nhập để gửi đánh giá') . '&type=error');
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: review.html?msg=' . urlencode('Phương thức không hợp lệ') . '&type=error');
    exit;
}

// Lấy dữ liệu từ form
$rating = $_POST['rating'] ?? '';
$comment = trim($_POST['comment'] ?? '');
$review_type = trim($_POST['review_type'] ?? 'Tổng thể');

// Kiểm tra dữ liệu đầu vào
if (empty($rating) || empty($comment)) {
    header('Location: review.html?msg=' . urlencode('Vui lòng điền đầy đủ thông tin') . '&type=error');
    exit;
}

// Kiểm tra rating hợp lệ (1-5)
if (!in_array($rating, ['1', '2', '3', '4', '5'])) {
    header('Location: review.html?msg=' . urlencode('Đánh giá không hợp lệ') . '&type=error');
    exit;
}

try {
    $username = $_SESSION['user']['username'];
    
    // Lấy khach_hang_id
    $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $khachHang = $stmt->fetch();
    
    if (!$khachHang) {
        header('Location: review.html?msg=' . urlencode('Không tìm thấy thông tin khách hàng') . '&type=error');
        exit;
    }
    
    $khach_hang_id = $khachHang['khach_hang_id'];
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // Lưu đánh giá vào bảng DanhGia theo cấu trúc SQL
        $stmt = $pdo->prepare("INSERT INTO DanhGia (khach_hang_id, loai_danh_gia, diem_danh_gia, noi_dung)
                               VALUES (:kh_id, :loai, :diem, :noi_dung)");
        $stmt->execute([
            ':kh_id' => $khach_hang_id,
            ':loai' => $review_type,
            ':diem' => (int)$rating,
            ':noi_dung' => $comment
        ]);
        
        $danh_gia_id = $pdo->lastInsertId();
        
        // Tạo thông báo cảm ơn cho khách hàng
        $tieu_de = "Cảm ơn bạn đã đánh giá!";
        $noi_dung = "Cảm ơn bạn đã gửi đánh giá {$rating} sao cho DFC Gym. Phản hồi của bạn giúp chúng tôi cải thiện chất lượng dịch vụ tốt hơn!";
        
        $stmt = $pdo->prepare("INSERT INTO ThongBao (tieu_de, noi_dung, loai_thong_bao, doi_tuong_nhan, khach_hang_nhan_id, da_doc)
                               VALUES (:tieu_de, :noi_dung, 'Hệ thống', 'Cá nhân', :kh_id, 0)");
        $stmt->execute([
            ':tieu_de' => $tieu_de,
            ':noi_dung' => $noi_dung,
            ':kh_id' => $khach_hang_id
        ]);
        
        $pdo->commit();
        
        header('Location: review.html?msg=' . urlencode('Cảm ơn bạn đã đánh giá! Đánh giá của bạn đã được gửi thành công.') . '&type=success');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error in submit_review.php: " . $e->getMessage());
    header('Location: review.html?msg=' . urlencode('Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại.') . '&type=error');
    exit;
} catch (Exception $e) {
    error_log("Error in submit_review.php: " . $e->getMessage());
    header('Location: review.html?msg=' . urlencode('Có lỗi hệ thống, vui lòng thử lại sau.') . '&type=error');
    exit;
}
