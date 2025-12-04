<?php
// get_phongtap_list.php - API lấy danh sách phòng tập cho form thanh toán
header('Content-Type: application/json');
error_reporting(E_ERROR | E_PARSE);

// Sử dụng config và db_connect chung
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

try {
    // Lấy danh sách phòng tập đang hoạt động
    // Sử dụng đúng tên cột trong database: phong_tap_id, ma_phong_tap, ten_phong_tap
    $sql = "SELECT 
                phong_tap_id,
                ma_phong_tap,
                ten_phong_tap,
                dia_chi,
                so_dien_thoai,
                trang_thai
            FROM phongtap 
            WHERE trang_thai = 'Hoạt động'
            ORDER BY ten_phong_tap ASC";
    
    $stmt = $pdo->query($sql);
    $phongtaps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Trả về dữ liệu với tên cột mới (phong_tap_id) và giữ tương thích với code cũ
    $result = [];
    foreach ($phongtaps as $pt) {
        $result[] = [
            'phong_tap_id' => $pt['phong_tap_id'],  // Tên mới
            'chi_nhanh_id' => $pt['phong_tap_id'],  // Tương thích với code cũ
            'ma_phong_tap' => $pt['ma_phong_tap'],  // Tên mới
            'ma_chi_nhanh' => $pt['ma_phong_tap'],  // Tương thích với code cũ
            'ten_phong_tap' => $pt['ten_phong_tap'], // Tên mới
            'ten_chi_nhanh' => $pt['ten_phong_tap'], // Tương thích với code cũ
            'dia_chi' => $pt['dia_chi'],
            'so_dien_thoai' => $pt['so_dien_thoai'],
            'trang_thai' => $pt['trang_thai']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (PDOException $e) {
    error_log("Error loading phong tap list: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi tải danh sách phòng tập: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error loading phong tap list: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>

