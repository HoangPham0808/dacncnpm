<?php
require_once __DIR__ . '/managment_function.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý gói tập</title>
    <link rel="stylesheet" href="management_package.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-dumbbell"></i> Quản lý gói tập</h1>
            <button class="btn btn-primary" onclick="openModal('add')">
                <i class="fas fa-plus"></i> Thêm gói tập
            </button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="packages-grid">
            <?php foreach ($packages as $package): ?>
            <div class="package-card <?php if ($package['loai_goi'] === 'VIP') {echo 'featured';} elseif ($package['loai_goi'] === 'PT cá nhân') {echo 'featured1';}?>">

                <div class="package-header">
                    <h3><?php echo htmlspecialchars($package['ten_goi']); ?></h3>
                    <span class="package-badge <?php echo $package['trang_thai'] === 'Đang áp dụng' ? 'active' : 'inactive'; ?>">
                        <?php echo htmlspecialchars($package['trang_thai']); ?>
                    </span>
                </div>
                <div class="package-price">
                    <?php echo number_format($package['gia_tien'], 0, ',', '.'); ?>₫
                </div>
                <div class="package-info">
                    <p><i class="fas fa-clock"></i> Thời hạn: <strong><?php echo $package['thoi_han_ngay']; ?> ngày</strong></p>
                    <p><i class="fas fa-tag"></i> Loại: <strong><?php echo htmlspecialchars($package['loai_goi']); ?></strong></p>
                </div>
                <div class="package-features">
                    <?php if ($package['mo_ta']): ?>
                        <?php 
                        $features = explode("\n", $package['mo_ta']);
                        foreach ($features as $feature): 
                            if (trim($feature)):
                        ?>
                            <p><i class="fas fa-check"></i> <?php echo htmlspecialchars(trim($feature)); ?></p>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    <?php endif; ?>
                </div>
                <div class="package-actions">
                    <button class="btn btn-edit" onclick='openModal("edit", <?php echo json_encode($package, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn btn-danger" onclick="confirmDelete(<?php echo $package['goi_tap_id']; ?>, '<?php echo htmlspecialchars($package['ten_goi'], ENT_QUOTES); ?>')">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal thêm/sửa gói tập -->
    <div id="packageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Thêm gói tập mới</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="packageForm" method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="goi_tap_id" id="goi_tap_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ma_goi_tap"><i class="fas fa-barcode"></i> Mã gói tập *</label>
                        <input type="text" id="ma_goi_tap" name="ma_goi_tap" required maxlength="20" placeholder="VD: GOI001">
                    </div>
                    
                    <div class="form-group">
                        <label for="ten_goi"><i class="fas fa-tag"></i> Tên gói *</label>
                        <input type="text" id="ten_goi" name="ten_goi" required maxlength="100" placeholder="VD: Gói Tháng">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="thoi_han_ngay"><i class="fas fa-calendar-alt"></i> Thời hạn (ngày) *</label>
                        <input type="number" id="thoi_han_ngay" name="thoi_han_ngay" required min="1" placeholder="VD: 30">
                    </div>
                    
                    <div class="form-group">
                        <label for="gia_tien"><i class="fas fa-money-bill-wave"></i> Giá tiền (VNĐ) *</label>
                        <input type="number" id="gia_tien" name="gia_tien" required min="0" step="1000" placeholder="VD: 450000">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="loai_goi"><i class="fas fa-crown"></i> Loại gói *</label>
                        <select id="loai_goi" name="loai_goi" required>
                            <option value="Cơ bản">Cơ bản</option>
                            <option value="Nâng cao">Nâng cao</option>
                            <option value="VIP">VIP</option>
                            <option value="PT cá nhân">PT cá nhân</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="trang_thai"><i class="fas fa-toggle-on"></i> Trạng thái *</label>
                        <select id="trang_thai" name="trang_thai" required>
                            <option value="Đang áp dụng">Đang áp dụng</option>
                            <option value="Ngừng áp dụng">Ngừng áp dụng</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mo_ta"><i class="fas fa-align-left"></i> Mô tả (mỗi dòng là 1 tính năng)</label>
                    <textarea id="mo_ta" name="mo_ta" rows="5" maxlength="500" placeholder="VD:&#10;Không giới hạn buổi&#10;01 buổi PT thử&#10;Ưu đãi mua kèm găng/đai"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal xác nhận xóa -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Xác nhận xóa</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa gói tập <strong id="deletePackageName"></strong>?</p>
                <p class="warning-text"><i class="fas fa-info-circle"></i> Hành động này không thể hoàn tác!</p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="goi_tap_id" id="deletePackageId">
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="management_package.js"></script>
</body>
</html>
<?php
$conn->close();
?>