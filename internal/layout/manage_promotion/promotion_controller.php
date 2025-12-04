<?php
/**
 * Promotion Controller
 * Xử lý các action: add, edit, delete
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'promotion_model.php';

// ============================================
// HELPER FUNCTION: Validate dữ liệu khuyến mại
// ============================================
function validatePromotionData($data, $excludeId = null) {
    $errors = [];
    
    // Lấy các giá trị
    $ma = trim($data['ma_khuyen_mai'] ?? '');
    $loaiGiam = $data['loai_giam'] ?? '';
    $giaTriGiam = isset($data['gia_tri_giam']) ? floatval($data['gia_tri_giam']) : 0;
    $ngayBatDau = $data['ngay_bat_dau'] ?? '';
    $ngayKetThuc = $data['ngay_ket_thuc'] ?? '';
    
    // Kiểm tra mã khuyến mại đã tồn tại chưa
    if (checkPromotionCodeExists($ma, $excludeId)) {
        $errors[] = 'Mã khuyến mại đã tồn tại!';
    }
    
    // Kiểm tra ngày kết thúc phải sau ngày bắt đầu
    if (strtotime($ngayKetThuc) < strtotime($ngayBatDau)) {
        $errors[] = 'Ngày kết thúc phải sau ngày bắt đầu!';
    }
    
    // Kiểm tra giá trị giảm phần trăm hợp lệ
    if ($loaiGiam == 'Phần trăm' && ($giaTriGiam < 0 || $giaTriGiam > 100)) {
        $errors[] = 'Giá trị giảm phần trăm phải từ 0-100!';
    }
    
    return $errors;
}

// ============================================
// HELPER FUNCTION: Normalize dữ liệu từ POST
// ============================================
function normalizePromotionData($postData) {
    return [
        'ma_khuyen_mai' => trim($postData['ma_khuyen_mai'] ?? ''),
        'ten_khuyen_mai' => trim($postData['ten_khuyen_mai'] ?? ''),
        'mo_ta' => trim($postData['mo_ta'] ?? ''),
        'loai_giam' => $postData['loai_giam'] ?? '',
        'gia_tri_giam' => floatval($postData['gia_tri_giam'] ?? 0),
        'giam_toi_da' => !empty($postData['giam_toi_da']) ? floatval($postData['giam_toi_da']) : null,
        'gia_tri_don_hang_toi_thieu' => !empty($postData['gia_tri_don_hang_toi_thieu']) ? floatval($postData['gia_tri_don_hang_toi_thieu']) : null,
        'ap_dung_cho_goi_tap_id' => !empty($postData['ap_dung_cho_goi_tap_id']) ? intval($postData['ap_dung_cho_goi_tap_id']) : null,
        'ngay_bat_dau' => $postData['ngay_bat_dau'] ?? '',
        'ngay_ket_thuc' => $postData['ngay_ket_thuc'] ?? '',
        'so_luong_ma' => !empty($postData['so_luong_ma']) ? intval($postData['so_luong_ma']) : null,
        'trang_thai' => $postData['trang_thai'] ?? ''
    ];
}

// ============================================
// HELPER FUNCTION: Xử lý lỗi và redirect
// ============================================
function handleValidationError($errors, $redirectUrl = 'promotion.php') {
    $_SESSION['message'] = implode(' ', $errors);
    $_SESSION['messageType'] = 'error';
    header("Location: $redirectUrl");
    exit();
}

// ============================================
// XỬ LÝ XÓA KHUYẾN MẠI (AJAX)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    // Tắt hiển thị lỗi để tránh làm hỏng JSON response
    ini_set('display_errors', 0);
    
    header('Content-Type: application/json');
    
    try {
        // Kiểm tra ID có hợp lệ không
        if (!isset($_POST['khuyen_mai_id']) || !is_numeric($_POST['khuyen_mai_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID khuyến mại không hợp lệ!']);
            exit;
        }
        
        $khuyen_mai_id = intval($_POST['khuyen_mai_id']);
        
        if ($khuyen_mai_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID khuyến mại không hợp lệ!']);
            exit;
        }
        
        $conn = getDBConnection();
        $conn->begin_transaction();
        
        // Lấy thông tin khuyến mại cần xóa
        $promotion = getPromotionById($khuyen_mai_id);
        
        if (!$promotion) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy khuyến mại!']);
            exit;
        }
        
        // Kiểm tra ràng buộc
        $constraints = checkPromotionConstraints($khuyen_mai_id);
        if (!empty($constraints)) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Không thể xóa khuyến mại! ' . implode(' ', $constraints)]);
            exit;
        }
        
        // Xóa khuyến mại
        deletePromotion($conn, $khuyen_mai_id);
        
        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Xóa khuyến mại thành công!']);
        exit;
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// XỬ LÝ THÊM KHUYẾN MẠI
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $conn = getDBConnection();
        $conn->begin_transaction();
        
        $promotionData = normalizePromotionData($_POST);
                
        // Lấy nhan_vien_id từ session để lưu người tạo khuyến mãi
        if (isset($_SESSION['nhan_vien_id'])) {
            $promotionData['nhan_vien_id'] = intval($_SESSION['nhan_vien_id']);
        }
        
        $validationErrors = validatePromotionData($promotionData);
        
        if (!empty($validationErrors)) {
            $conn->rollback();
            handleValidationError($validationErrors);
        }
        
        addPromotion($conn, $promotionData);
        $conn->commit();
        
        $_SESSION['message'] = 'Thêm khuyến mại thành công!';
        $_SESSION['messageType'] = 'success';
        header("Location: promotion.php");
        exit();

    } catch (Exception $e) {
        if (isset($conn)) $conn->rollback();
        $_SESSION['message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['messageType'] = 'error';
        header("Location: promotion.php");
        exit();
    }
}

// ============================================
// XỬ LÝ CẬP NHẬT KHUYẾN MẠI
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    try {
        $id = intval($_POST['khuyen_mai_id'] ?? 0);
        if ($id <= 0) {
            handleValidationError(['ID khuyến mại không hợp lệ!']);
        }
        
        $conn = getDBConnection();
        $conn->begin_transaction();
        
        $promotionData = normalizePromotionData($_POST);
        $validationErrors = validatePromotionData($promotionData, $id);
        
        if (!empty($validationErrors)) {
            $conn->rollback();
            handleValidationError($validationErrors, "promotion.php?edit=$id");
        }
        
        updatePromotion($conn, $id, $promotionData);
        $conn->commit();
        
        $_SESSION['message'] = 'Cập nhật khuyến mại thành công!';
        $_SESSION['messageType'] = 'success';
        header("Location: promotion.php");
        exit();
    } catch (Exception $e) {
        if (isset($conn)) $conn->rollback();
        $_SESSION['message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['messageType'] = 'error';
        header("Location: promotion.php");
        exit();
    }
}
