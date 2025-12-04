<?php
/**
 * Promotion View
 * Chỉ hiển thị HTML và gọi controller/model
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include controller để xử lý các action
require_once 'promotion_controller.php';
require_once 'promotion_model.php';

// ============================================
// BIẾN MESSAGE
// ============================================
$message = '';
$messageType = '';

// ============================================
// LẤY THÔNG TIN ĐỂ CHỈNH SỬA
// ============================================
$editPromotion = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $editPromotion = getPromotionById($_GET['edit']);
        
        if (!$editPromotion) {
            $_SESSION['message'] = 'Không tìm thấy khuyến mại!';
            $_SESSION['messageType'] = 'error';
            header("Location: promotion.php");
            exit();
        }
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// ============================================
// TÌM KIẾM VÀ LỌC DỮ LIỆU
// ============================================
// Lấy tham số tìm kiếm từ URL
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $promotions = getPromotions($searchTerm);
} catch (Exception $e) {
    $message = 'Lỗi: ' . $e->getMessage();
    $messageType = 'error';
    $promotions = [];
}

// ============================================
// LẤY DANH SÁCH GÓI TẬP CHO DROPDOWN
// ============================================
$packages = [];
try {
    $packages = getAllPackagesForPromotion();
} catch (Exception $e) {
    // Không hiển thị lỗi nếu không lấy được danh sách gói tập
}

// ============================================
// HELPER FUNCTION: Format ngày cho input date
// ============================================
function formatDateForInput($dateValue) {
    if (empty($dateValue)) {
        return '';
    }
    // Nếu là string, lấy 10 ký tự đầu (Y-m-d)
    if (is_string($dateValue)) {
        return substr($dateValue, 0, 10);
    }
    // Nếu là timestamp hoặc DateTime object
    return date('Y-m-d', strtotime($dateValue));
}

// ============================================
// LẤY MESSAGE TỪ SESSION
// ============================================
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Khuyến Mại - DFC Gym</title>
    <link rel="stylesheet" href="promotion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tags"></i> Quản Lý Khuyến Mại</h1>
            <button type="button" class="btn-primary" id="btn-open-modal">
                <i class="fas fa-plus"></i> Thêm Khuyến Mại
            </button>
        </div>

        <!-- THÔNG BÁO -->
        <?php if ($message): ?>
        <div class="thong-bao <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
            <button class="btn-close-alert" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <!-- TÌM KIẾM -->
        <div class="search-box">
            <form method="GET">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Tìm kiếm theo mã, tên hoặc mô tả..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit">Tìm kiếm</button>
            </form>
        </div>

        <!-- DANH SÁCH KHUYẾN MẠI -->
        <div class="khuyen-mai-grid">
            <?php if (count($promotions) > 0): ?>
                <?php foreach ($promotions as $km): ?>
                <div class="khuyen-mai-card">
                    <div class="badge-giam">
                        <?php if ($km['loai_giam'] == 'Phần trăm'): ?>
                            -<?php echo htmlspecialchars($km['gia_tri_giam']); ?>%
                        <?php else: ?>
                            -<?php echo number_format($km['gia_tri_giam'], 0, ',', '.'); ?>đ
                        <?php endif; ?>
                    </div>

                    <div class="badge-trang-thai <?php
                        if ($km['trang_thai'] == 'Đang áp dụng') echo 'dang-ap-dung';
                        elseif ($km['trang_thai'] == 'Hết hạn') echo 'da-ket-thuc';
                        else echo 'chua-ap-dung';
                    ?>">
                        <?php echo htmlspecialchars($km['trang_thai']); ?>
                    </div>

                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($km['ten_khuyen_mai']); ?></h3>
                        <p class="mo-ta"><?php echo htmlspecialchars($km['mo_ta']); ?></p>

                        <div class="thong-tin">
                            <div class="thong-tin-item">
                                <i class="fas fa-ticket-alt"></i>
                                <span>Mã: <strong><?php echo htmlspecialchars($km['ma_khuyen_mai']); ?></strong></span>
                            </div>
                            <div class="thong-tin-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo $km['ngay_bat_dau'] ? date('d/m/Y', strtotime($km['ngay_bat_dau'])) : '-'; ?> đến <?php echo $km['ngay_ket_thuc'] ? date('d/m/Y', strtotime($km['ngay_ket_thuc'])) : '-'; ?></span>
                            </div>
                            <?php if (!empty($km['gia_tri_don_hang_toi_thieu'])): ?>
                            <div class="thong-tin-item">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Đơn tối thiểu: <?php echo number_format($km['gia_tri_don_hang_toi_thieu'], 0, ',', '.'); ?>đ</span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($km['so_luong_ma'])): ?>
                            <div class="thong-tin-item">
                                <i class="fas fa-hashtag"></i>
                                <span>Đã dùng: <?php echo htmlspecialchars($km['so_luong_da_dung']); ?>/<?php echo htmlspecialchars($km['so_luong_ma']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions">
                            <a href="?edit=<?php echo (int)$km['khuyen_mai_id']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <button class="btn-delete" onclick="deletePromotion(<?php echo (int)$km['khuyen_mai_id']; ?>, '<?php echo htmlspecialchars($km['ten_khuyen_mai'], ENT_QUOTES, 'UTF-8'); ?>')">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p><?php echo $searchTerm ? 'Không tìm thấy kết quả phù hợp' : 'Chưa có khuyến mại nào'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL THÊM/SỬA -->
    <div id="modal" class="modal <?php echo $editPromotion ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-<?php echo $editPromotion ? 'edit' : 'plus-circle'; ?>"></i>
                    <?php echo $editPromotion ? 'Cập Nhật Khuyến Mại' : 'Thêm Khuyến Mại Mới'; ?>
                </h2>
                <a href="promotion.php" class="btn-close" id="btn-close-modal">&times;</a>
            </div>

            <form class="form-khuyen-mai" method="POST">
                <?php if ($editPromotion): ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="khuyen_mai_id" value="<?php echo (int)$editPromotion['khuyen_mai_id']; ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="add">
                <?php endif; ?>

                <div class="form-fields">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Mã Khuyến Mại <span class="required">*</span></label>
                            <input type="text" name="ma_khuyen_mai" required 
                                   placeholder="VD: SUMMER2024"
                                   value="<?php echo htmlspecialchars($editPromotion['ma_khuyen_mai'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tên Khuyến Mại <span class="required">*</span></label>
                            <input type="text" name="ten_khuyen_mai" required 
                                   placeholder="VD: Giảm giá mùa hè"
                                   value="<?php echo htmlspecialchars($editPromotion['ten_khuyen_mai'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Trạng Thái <span class="required">*</span></label>
                            <select name="trang_thai" required>
                                <option value="Đang áp dụng" <?php echo ($editPromotion && $editPromotion['trang_thai'] == 'Đang áp dụng') ? 'selected' : ''; ?>>Đang áp dụng</option>
                                <option value="Hết hạn" <?php echo ($editPromotion && $editPromotion['trang_thai'] == 'Hết hạn') ? 'selected' : ''; ?>>Hết hạn</option>
                                <option value="Tạm dừng" <?php echo ($editPromotion && $editPromotion['trang_thai'] == 'Tạm dừng') ? 'selected' : ''; ?>>Tạm dừng</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Loại Giảm <span class="required">*</span></label>
                            <select name="loai_giam" required>
                                <option value="Phần trăm" <?php echo ($editPromotion && $editPromotion['loai_giam'] == 'Phần trăm') ? 'selected' : ''; ?>>Phần trăm (%)</option>
                                <option value="Số tiền" <?php echo ($editPromotion && $editPromotion['loai_giam'] == 'Số tiền') ? 'selected' : ''; ?>>Số tiền (VNĐ)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Giá Trị Giảm <span class="required">*</span></label>
                            <input type="number" name="gia_tri_giam" step="0.01" min="0" required 
                                   placeholder="VD: 10 hoặc 50000"
                                   value="<?php echo htmlspecialchars($editPromotion['gia_tri_giam'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Giảm Tối Đa (VNĐ)</label>
                            <input type="number" name="giam_toi_da" step="0.01" min="0"
                                   placeholder="VD: 100000"
                                   value="<?php echo htmlspecialchars($editPromotion['giam_toi_da'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Đơn Tối Thiểu (VNĐ)</label>
                            <input type="number" name="gia_tri_don_hang_toi_thieu" step="0.01" min="0"
                                   placeholder="VD: 200000"
                                   value="<?php echo htmlspecialchars($editPromotion['gia_tri_don_hang_toi_thieu'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Mã Gói Tập</label>
                            <select name="ap_dung_cho_goi_tap_id">
                                <option value="">Áp dụng cho tất cả gói tập</option>
                                <?php if (!empty($packages)): ?>
                                    <?php foreach ($packages as $package): ?>
                                        <option value="<?php echo (int)$package['goi_tap_id']; ?>"
                                            <?php echo (isset($editPromotion['ap_dung_cho_goi_tap_id']) && (int)$editPromotion['ap_dung_cho_goi_tap_id'] === (int)$package['goi_tap_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($package['ma_goi_tap']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Số Lượng Mã</label>
                            <input type="number" name="so_luong_ma" min="1"
                                   placeholder="Để trống nếu không giới hạn"
                                   value="<?php echo htmlspecialchars($editPromotion['so_luong_ma'] ?? ''); ?>">
                        </div>
                        <div class="form-group span-2">
                            <label>Ngày Bắt Đầu <span class="required">*</span></label>
                            <input type="date" name="ngay_bat_dau" required 
                                   value="<?php echo htmlspecialchars(formatDateForInput($editPromotion['ngay_bat_dau'] ?? date('Y-m-d'))); ?>">
                        </div>
                        <div class="form-group">
                            <label>Ngày Kết Thúc <span class="required">*</span></label>
                            <input type="date" name="ngay_ket_thuc" required 
                                   value="<?php echo htmlspecialchars(formatDateForInput($editPromotion['ngay_ket_thuc'] ?? '')); ?>">
                        </div>
                        <div class="form-group span-full">
                            <label>Mô Tả</label>
                            <textarea name="mo_ta" rows="2" placeholder="Mô tả chi tiết về chương trình khuyến mại..."><?php echo htmlspecialchars($editPromotion['mo_ta'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="promotion.php" class="btn-cancel"><i class="fas fa-times"></i> Hủy</a>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> <?php echo $editPromotion ? 'Cập Nhật' : 'Thêm Mới'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Dialog Xác Nhận Xóa -->
    <div id="confirm-dialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Xác Nhận Xóa</h2>
                <button class="btn-close" onclick="closeConfirmDialog()">&times;</button>
            </div>
            <div class="dialog-body">
                <p id="confirm-message"></p>
            </div>
            <div class="dialog-footer">
                <button class="btn-secondary" onclick="closeConfirmDialog()">Hủy</button>
                <button class="btn-primary" id="confirm-ok-btn">Xác Nhận</button>
            </div>
        </div>
    </div>

    <!-- Dialog Thông Báo -->
    <div id="message-dialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2 id="message-title"><i class="fas fa-info-circle"></i> Thông Báo</h2>
                <button class="btn-close" id="message-close-btn">&times;</button>
            </div>
            <div class="dialog-body">
                <p id="message-content"></p>
            </div>
            <div class="dialog-footer">
                <button class="btn-primary" onclick="closeMessageDialog()">Đóng</button>
            </div>
        </div>
    </div>

    <script src="promotion.js"></script>
</body>
</html>
