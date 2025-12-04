<?php
/**
 * Account View
 * Chỉ hiển thị HTML và gọi controller/model
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include controller để xử lý các action
require_once 'account_controller.php';
require_once 'account_model.php';

// Lấy thông tin tài khoản hiện tại
$accountInfo = null;
try {
    $username = $_SESSION['username'];
    $accountInfo = getAccountByUsername($username);
    
    if (!$accountInfo) {
        die('Không tìm thấy thông tin tài khoản!');
    }
} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản cá nhân - DFC Gym</title>
    <link rel="stylesheet" href="account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-shield"></i> Tài khoản cá nhân</h1>
        </div>
        <div class="main-content">
            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Thông tin tài khoản -->
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Thông tin tài khoản</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ten_dang_nhap">
                            <i class="fas fa-user"></i>
                            <span>Tên đăng nhập</span>
                        </label>
                        <input 
                            type="text" 
                            id="ten_dang_nhap" 
                            value="<?php echo htmlspecialchars($accountInfo['ten_dang_nhap'] ?? ''); ?>" 
                            readonly
                            disabled
                        >
                        <small>Không thể thay đổi tên đăng nhập</small>
                    </div>

                    <div class="form-group">
                        <label for="ho_ten">
                            <i class="fas fa-signature"></i>
                            <span>Họ và tên</span>
                        </label>
                        <input 
                            type="text" 
                            id="ho_ten" 
                            value="<?php echo htmlspecialchars($accountInfo['ho_ten'] ?? ''); ?>" 
                            readonly
                            disabled
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="loai_tai_khoan">
                            <i class="fas fa-user-tag"></i>
                            <span>Loại tài khoản</span>
                        </label>
                        <input 
                            type="text" 
                            id="loai_tai_khoan" 
                            value="<?php echo htmlspecialchars($accountInfo['loai_tai_khoan'] ?? ''); ?>" 
                            readonly
                            disabled
                        >
                    </div>

                    <div class="form-group">
                        <label for="vai_tro">
                            <i class="fas fa-briefcase"></i>
                            <span>Vai trò</span>
                        </label>
                        <input 
                            type="text" 
                            id="vai_tro" 
                            value="<?php echo htmlspecialchars($accountInfo['vai_tro'] ?? ''); ?>" 
                            readonly
                            disabled
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="trang_thai">
                            <i class="fas fa-check-circle"></i>
                            <span>Trạng thái</span>
                        </label>
                        <input 
                            type="text" 
                            id="trang_thai" 
                            value="<?php echo htmlspecialchars($accountInfo['trang_thai'] ?? ''); ?>" 
                            readonly
                            disabled
                        >
                    </div>

                    <div class="form-group">
                        <label for="lan_dang_nhap_cuoi">
                            <i class="fas fa-clock"></i>
                            <span>Lần đăng nhập cuối</span>
                        </label>
                        <input 
                            type="text" 
                            id="lan_dang_nhap_cuoi" 
                            value="<?php echo htmlspecialchars($accountInfo['lan_dang_nhap_cuoi'] ?? 'Chưa có'); ?>" 
                            readonly
                            disabled
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ngay_tao">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Ngày tạo</span>
                        </label>
                        <input 
                            type="text" 
                            id="ngay_tao" 
                            value="<?php echo htmlspecialchars($accountInfo['ngay_tao'] ?? ''); ?>" 
                            readonly
                            disabled
                        >
                    </div>

                    <div class="form-group">
                        <label for="ngay_cap_nhat">
                            <i class="fas fa-calendar-check"></i>
                            <span>Ngày cập nhật</span>
                        </label>
                        <input 
                            type="text" 
                            id="ngay_cap_nhat" 
                            value="<?php echo htmlspecialchars($accountInfo['ngay_cap_nhat'] ?? ''); ?>" 
                            readonly
                            disabled
                        >
                    </div>
                </div>
            </div>

            <!-- Đổi mật khẩu -->
            <div class="form-section">
                <h2><i class="fas fa-lock"></i> Đổi mật khẩu</h2>
                
                <form action="" method="POST" class="password-form" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group full-width">
                        <label for="current_password">
                            <i class="fas fa-key"></i>
                            <span>Mật khẩu hiện tại <span class="required">*</span></span>
                        </label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                placeholder="Nhập mật khẩu hiện tại"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" data-target="current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">
                                <i class="fas fa-lock"></i>
                                <span>Mật khẩu mới <span class="required">*</span></span>
                            </label>
                            <div class="password-input-wrapper">
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
                                    required
                                    autocomplete="new-password"
                                    minlength="6"
                                >
                                <button type="button" class="toggle-password" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i>
                                <span>Xác nhận mật khẩu mới <span class="required">*</span></span>
                            </label>
                            <div class="password-input-wrapper">
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    placeholder="Nhập lại mật khẩu mới"
                                    required
                                    autocomplete="new-password"
                                    minlength="6"
                                >
                                <button type="button" class="toggle-password" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Đổi mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="account.js"></script>
</body>
</html>

