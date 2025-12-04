<?php
/**
 * get_reviews.php - Lấy danh sách đánh giá từ database
 */
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Lấy tất cả đánh giá kèm thông tin khách hàng
    $stmt = $pdo->prepare("SELECT dg.danh_gia_id, dg.loai_danh_gia, dg.diem_danh_gia, dg.noi_dung, dg.ngay_danh_gia,
                                  kh.ho_ten, kh.email
                           FROM DanhGia dg
                           LEFT JOIN KhachHang kh ON dg.khach_hang_id = kh.khach_hang_id
                           ORDER BY dg.ngay_danh_gia DESC
                           LIMIT 50");
    $stmt->execute();
    $reviews = $stmt->fetchAll();
    
    // Tính điểm trung bình
    $stmt_avg = $pdo->query("SELECT AVG(diem_danh_gia) as avg_rating, COUNT(*) as total_count FROM DanhGia");
    $stats = $stmt_avg->fetch();
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'avg_rating' => round((float)$stats['avg_rating'], 1),
        'total_count' => (int)$stats['total_count']
    ]);
    
} catch (Exception $e) {
    error_log('Get reviews error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}

