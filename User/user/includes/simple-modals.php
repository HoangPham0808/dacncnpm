<?php
/**
 * Simple Modals - Các modal đơn giản cho người dùng đã đăng nhập
 */
?>

<!-- Modal 1: Thông tin tài khoản -->
<div class="modal-overlay hidden" id="profile-modal">
    <div class="modal-content">
        <button class="modal-close-btn"><i class="fas fa-times"></i></button>
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Thông tin tài khoản</h2>
        </div>
        <div class="modal-body">
            <div id="profile-content">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i>
                    <p style="margin-top: 15px; color: var(--muted);">Đang tải dữ liệu...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Gói tập của bạn -->
<div class="modal-overlay hidden" id="my-packages-modal">
    <div class="modal-content">
        <button class="modal-close-btn"><i class="fas fa-times"></i></button>
        <div class="modal-header">
            <h2><i class="fas fa-box"></i> Gói tập của bạn</h2>
        </div>
        <div class="modal-body">
            <div id="my-packages-content">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i>
                    <p style="margin-top: 15px; color: var(--muted);">Đang tải dữ liệu...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Lịch sử thanh toán -->
<div class="modal-overlay hidden" id="payment-history-modal">
    <div class="modal-content">
        <button class="modal-close-btn"><i class="fas fa-times"></i></button>
        <div class="modal-header">
            <h2><i class="fas fa-history"></i> Lịch sử thanh toán</h2>
        </div>
        <div class="modal-body">
            <div id="payment-history-content">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i>
                    <p style="margin-top: 15px; color: var(--muted);">Đang tải dữ liệu...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 4: Quản lý thanh toán -->
<div class="modal-overlay hidden" id="payment-management-modal">
    <div class="modal-content">
        <button class="modal-close-btn"><i class="fas fa-times"></i></button>
        <div class="modal-header">
            <h2><i class="fas fa-credit-card"></i> Quản lý thanh toán</h2>
        </div>
        <div class="modal-body">
            <div id="payment-management-content">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i>
                    <p style="margin-top: 15px; color: var(--muted);">Đang tải dữ liệu...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 5: Đổi mật khẩu -->
<div class="modal-overlay hidden" id="change-password-modal">
    <div class="modal-content">
        <button class="modal-close-btn"><i class="fas fa-times"></i></button>
        <div class="modal-header">
            <h2><i class="fas fa-key"></i> Đổi mật khẩu</h2>
        </div>
        <div class="modal-body">
            <?php
            // Xác định đường dẫn dựa trên SCRIPT_NAME (URL path) thay vì file system path
            $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
            
            // Kiểm tra xem script có nằm trong /user/ hay không
            $isInUserFolder = (strpos($scriptPath, '/user/') !== false || strpos($scriptPath, '\\user\\') !== false);
            
            // Kiểm tra xem có đang ở trong thư mục con của user/ không
            $isInSubFolder = (strpos($scriptPath, '/goitap/') !== false || strpos($scriptPath, '\\goitap\\') !== false ||
                            strpos($scriptPath, '/danhgia/') !== false || strpos($scriptPath, '\\danhgia\\') !== false ||
                            strpos($scriptPath, '/hotro/') !== false || strpos($scriptPath, '\\hotro\\') !== false ||
                            strpos($scriptPath, '/lichtap/') !== false || strpos($scriptPath, '\\lichtap\\') !== false ||
                            strpos($scriptPath, '/homthu/') !== false || strpos($scriptPath, '\\homthu\\') !== false ||
                            strpos($scriptPath, '/khuyenmai/') !== false || strpos($scriptPath, '\\khuyenmai\\') !== false ||
                            strpos($scriptPath, '/thanhtoan/') !== false || strpos($scriptPath, '\\thanhtoan\\') !== false);
            
            // Xác định đường dẫn action cho form đổi mật khẩu
            // Sử dụng đường dẫn tuyệt đối từ root để tránh lỗi
            // Tìm base path từ SCRIPT_NAME
            $basePath = '/';
            if (strpos($scriptPath, '/doanchuyennganh/') !== false) {
              $basePath = '/doanchuyennganh/';
            } else {
              // Lấy thư mục gốc từ script path
              $scriptDir = dirname($scriptPath);
              if ($scriptDir !== '/' && $scriptDir !== '.') {
                $basePath = rtrim($scriptDir, '/') . '/';
              }
            }
            
            // Tạo đường dẫn tuyệt đối
            $updatePasswordPath = $basePath . 'user/getset/update_password.php';
            
            // Đảm bảo đường dẫn không có dấu / ở cuối và không có đường dẫn file system
            $updatePasswordPath = rtrim($updatePasswordPath, '/');
            $updatePasswordPath = str_replace('\\', '/', $updatePasswordPath);
            // Loại bỏ bất kỳ đường dẫn file system nào (C:, D:, etc.)
            $updatePasswordPath = preg_replace('/^[A-Z]:/', '', $updatePasswordPath);
            ?>
            <form action="<?php echo htmlspecialchars($updatePasswordPath, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                <div class="form-group">
                    <label for="change-old-pass">Mật khẩu cũ</label>
                    <input type="password" id="change-old-pass" name="old_password" required>
                </div>
                <div class="form-group">
                    <label for="change-new-pass">Mật khẩu mới</label>
                    <input type="password" id="change-new-pass" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="change-confirm-pass">Xác nhận mật khẩu mới</label>
                    <input type="password" id="change-confirm-pass" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">Đổi mật khẩu</button>
            </form>
        </div>
    </div>
</div>
