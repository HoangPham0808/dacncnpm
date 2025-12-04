<?php
require_once __DIR__ . '/management_function.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý các phòng tập - DFC Gym</title>
    <link rel="stylesheet" href="management_branch.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <!-- Message -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1><i class="fas fa-map-marker-alt"></i> Quản lý các phòng tập</h1>
            <button class="btn btn-primary" onclick="openAddDialog()">
                <i class="fas fa-plus"></i> Thêm phòng tập
            </button>
        </div>

        <!-- Search and Filter -->
        <div class="filter-section">
            <form method="GET" class="search-box">
                <div class="search-input-wrapper">
                    <input type="text" 
                           name="search" 
                           placeholder="Tìm kiếm theo mã, tên, địa chỉ, SĐT..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <select name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="Hoạt động" <?php echo $statusFilter == 'Hoạt động' ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="Tạm ngưng" <?php echo $statusFilter == 'Tạm ngưng' ? 'selected' : ''; ?>>Tạm ngưng</option>
                    <option value="Đóng cửa" <?php echo $statusFilter == 'Đóng cửa' ? 'selected' : ''; ?>>Đóng cửa</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetFilter()">
                    <i class="fas fa-redo"></i> Đặt lại
                </button>
            </form>
        </div>

        <div class="branches-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($branch = $result->fetch_assoc()): 
                    $statusClass = '';
                    switch($branch['trang_thai']) {
                        case 'Hoạt động':
                            $statusClass = 'active';
                            break;
                        case 'Tạm ngưng':
                            $statusClass = 'inactive';
                            break;
                        case 'Đóng cửa':
                            $statusClass = 'closed';
                            break;
                    }
                ?>
                    <div class="branch-card">
                        <div class="branch-header">
                            <h3><?php echo htmlspecialchars($branch['ten_phong_tap']); ?></h3>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($branch['trang_thai']); ?>
                            </span>
                        </div>
                        <div class="branch-info">
                            <p><i class="fas fa-barcode"></i> Mã: <strong><?php echo htmlspecialchars($branch['ma_phong_tap']); ?></strong></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($branch['dia_chi']); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($branch['so_dien_thoai'] ?? 'Chưa có'); ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($branch['email'] ?? 'Chưa có'); ?></p>
                            <?php if ($branch['ngay_thanh_lap']): ?>
                                <p><i class="fas fa-calendar-alt"></i> Thành lập: <?php echo date('d/m/Y', strtotime($branch['ngay_thanh_lap'])); ?></p>
                            <?php endif; ?>
                            <?php if ($branch['ghi_chu']): ?>
                                <p><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($branch['ghi_chu']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="branch-actions">
                            <button class="btn btn-edit" onclick="editBranch(<?php echo $branch['phong_tap_id']; ?>)">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-danger" onclick="deleteBranch(<?php echo $branch['phong_tap_id']; ?>)">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>Không có phòng tập nào</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Dialog -->
    <div id="addDialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2><i class="fas fa-plus"></i> Thêm phòng tập Mới</h2>
                <button class="btn-close" onclick="closeDialog('addDialog')">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="dialog-body">
                    <div class="form-group full-width" style="background: rgba(14, 165, 233, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(14, 165, 233, 0.3);">
                        <p style="margin: 0; color: rgba(255, 255, 255, 0.9); font-size: 14px;">
                            <i class="fas fa-info-circle" style="color: #0ea5e9;"></i>
                            <strong>Lưu ý:</strong> Mã phòng tập sẽ được hệ thống tự động tạo (VD: PT001, PT002, ...)
                        </p>
                    </div>
                    <div class="form-group full-width">
                        <label>Tên phòng tập <span class="required">*</span></label>
                        <input type="text" name="ten_phong_tap" required placeholder="VD: DFC Gym Hà Đông">
                    </div>
                    <div class="form-group full-width">
                        <label>Địa Chỉ <span class="required">*</span></label>
                        <input type="text" name="dia_chi" required placeholder="Số nhà, đường, phường/xã, quận/huyện">
                    </div>
                    <div class="form-group">
                        <label>Số Điện Thoại</label>
                        <input type="text" name="so_dien_thoai" placeholder="0901234567" maxlength="11">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="phongtap@dfcgym.vn">
                    </div>
                    <div class="form-group">
                        <label>Trạng Thái <span class="required">*</span></label>
                        <select name="trang_thai" required>
                            <option value="Hoạt động">Hoạt động</option>
                            <option value="Tạm ngưng">Tạm ngưng</option>
                            <option value="Đóng cửa">Đóng cửa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày Thành Lập</label>
                        <input type="date" name="ngay_thanh_lap">
                    </div>
                    <div class="form-group full-width">
                        <label>Ghi Chú</label>
                        <textarea name="ghi_chu" rows="3" placeholder="Thông tin bổ sung về phòng tập..." maxlength="500"></textarea>
                    </div>
                </div>
                <div class="dialog-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDialog('addDialog')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Dialog -->
    <div id="editDialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2><i class="fas fa-edit"></i> Chỉnh Sửa phòng tập</h2>
                <button class="btn-close" onclick="closeDialog('editDialog')">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_phong_tap_id" name="phong_tap_id">
                <div class="dialog-body">
                    <div class="form-group">
                        <label>Mã phòng tập</label>
                        <input type="text" id="edit_ma_phong_tap" readonly>
                    </div>
                    <div class="form-group">
                        <label>Tên phòng tập <span class="required">*</span></label>
                        <input type="text" id="edit_ten_phong_tap" name="ten_phong_tap" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Địa Chỉ <span class="required">*</span></label>
                        <input type="text" id="edit_dia_chi" name="dia_chi" required>
                    </div>
                    <div class="form-group">
                        <label>Số Điện Thoại</label>
                        <input type="text" id="edit_so_dien_thoai" name="so_dien_thoai" maxlength="11">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="edit_email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Trạng Thái <span class="required">*</span></label>
                        <select id="edit_trang_thai" name="trang_thai" required>
                            <option value="Hoạt động">Hoạt động</option>
                            <option value="Tạm ngưng">Tạm ngưng</option>
                            <option value="Đóng cửa">Đóng cửa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày Thành Lập</label>
                        <input type="date" id="edit_ngay_thanh_lap" name="ngay_thanh_lap">
                    </div>
                    <div class="form-group full-width">
                        <label>Ghi Chú</label>
                        <textarea id="edit_ghi_chu" name="ghi_chu" rows="3" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="dialog-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDialog('editDialog')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập Nhật</button>
                </div>
            </form>
        </div>
    </div>

    <script src="management_branch.js"></script>
</body>
</html>
<?php
$conn->close();
?>