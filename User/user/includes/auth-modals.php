<?php
/**
 * Auth Modals - Modal đăng nhập và đăng ký
 * Dùng cho tất cả các trang khi chưa đăng nhập
 */
// Xác định đường dẫn base dựa trên vị trí file include
// File này có thể được include từ nhiều vị trí khác nhau
$includeDir = dirname(__FILE__); // user/includes/
$rootDir = dirname(dirname($includeDir)); // root (doanchuyennganh/)
$assetsPath = 'assets/';

// Xác định đường dẫn dựa trên SCRIPT_NAME (URL path) thay vì file system path
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isInUserFolder = (strpos($scriptPath, '/user/') !== false || strpos($scriptPath, '\\user\\') !== false);
$isInSubFolder = (strpos($scriptPath, '/goitap/') !== false || strpos($scriptPath, '\\goitap\\') !== false ||
                  strpos($scriptPath, '/danhgia/') !== false || strpos($scriptPath, '\\danhgia\\') !== false ||
                  strpos($scriptPath, '/hotro/') !== false || strpos($scriptPath, '\\hotro\\') !== false ||
                  strpos($scriptPath, '/lichtap/') !== false || strpos($scriptPath, '\\lichtap\\') !== false ||
                  strpos($scriptPath, '/homthu/') !== false || strpos($scriptPath, '\\homthu\\') !== false ||
                  strpos($scriptPath, '/khuyenmai/') !== false || strpos($scriptPath, '\\khuyenmai\\') !== false ||
                  strpos($scriptPath, '/thanhtoan/') !== false || strpos($scriptPath, '\\thanhtoan\\') !== false);

// Xác định đường dẫn assets
if ($isInUserFolder && $isInSubFolder) {
  $assetsPath = '../../assets/';
} else if ($isInUserFolder) {
  $assetsPath = '../assets/';
} else {
  $assetsPath = 'assets/';
}

// Xác định đường dẫn action cho form
if ($isInUserFolder && $isInSubFolder) {
  // Nếu ở trong thư mục con của user/ (ví dụ: user/khuyenmai/)
  $loginAction = '../dangnhap/login.php';
  $registerAction = '../dangky/register.php';
  $forgotPasswordAction = '../getset/forgot_password.php';
  $indexPath = '../../index.html';
} else if ($isInUserFolder) {
  // Nếu ở trong user/ trực tiếp
  $loginAction = 'dangnhap/login.php';
  $registerAction = 'dangky/register.php';
  $forgotPasswordAction = 'getset/forgot_password.php';
  $indexPath = '../index.html';
} else {
  // Nếu ở root
  $loginAction = 'user/dangnhap/login.php';
  $registerAction = 'user/dangky/register.php';
  $forgotPasswordAction = 'user/getset/forgot_password.php';
  $indexPath = 'index.html';
}
?>

<!-- Login Modal - CHỈ cho trang chủ chưa đăng nhập -->
<div class="modal-overlay hidden" id="login-modal">
    <div class="modal-content auth-box">
        <button class="modal-close-btn"><i class="fas fa-times"></i></button>
        <form id="login-form" action="<?php echo $loginAction; ?>" method="POST" autocomplete="on" novalidate>
            <a href="<?php echo $indexPath; ?>" class="brand">
                <img src="<?php echo $assetsPath; ?>img/logo.png" alt="Logo">
                <span>DFC Gym</span>
            </a>
            <h2>Đăng nhập</h2>
            <p class="auth-message message" id="login-message"></p> 
            <div class="form-group">
                <label for="login-email">Email (hoặc Username)</label>
                <input type="text" id="login-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="login-password">Mật khẩu</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            <button type="submit" class="btn">Đăng nhập</button>
            <p class="switch-auth" style="margin-top: 10px;">
                <a href="#quen-mat-khau" class="switch-to-forgot" data-modal-target="forgot-password-modal">Quên mật khẩu?</a>
            </p>
            <p class="switch-auth">Chưa có tài khoản?
                <a href="#dang-ky" class="switch-to-register" data-modal-target="register-modal">Đăng ký ngay</a>
            </p>
        </form>
    </div>
</div>

<!-- Register Modal - CHỈ cho trang chủ chưa đăng nhập -->
<div class="modal-overlay hidden" id="register-modal">
    <div class="modal-content auth-box">
        <button class="modal-close-btn"><i class="fas fa-times"></i></button>
        <form id="register-form" action="<?php echo $registerAction; ?>" method="POST" autocomplete="on" novalidate>
            <a href="<?php echo $indexPath; ?>" class="brand">
                <img src="<?php echo $assetsPath; ?>img/logo.png" alt="Logo">
                <span>DFC Gym</span>
            </a>
            <h2>Đăng ký</h2>
            <p class="auth-message message" id="register-message"></p>
            <div class="form-group">
                <label for="register-username">Tên đăng nhập</label>
                <input type="text" id="register-username" name="username" required minlength="3" maxlength="50">
            </div>
            <div class="form-group">
                <label for="register-fullname">Họ và tên</label>
                <input type="text" id="register-fullname" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="register-email">Email</label>
                <input type="email" id="register-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="register-password">Mật khẩu</label>
                <input type="password" id="register-password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="register-confirm-password">Xác nhận mật khẩu</label>
                <input type="password" id="register-confirm-password" name="password_confirm" required>
            </div>
            <button type="submit" class="btn">Đăng ký</button>
            <p class="switch-auth">Đã có tài khoản?
                <a href="#dang-nhap" class="switch-to-login" data-modal-target="login-modal">Quay lại đăng nhập</a>
            </p>
        </form>
    </div>
</div>

<!-- Forgot Password Modal - Step 1: Xác thực thông tin -->
<div class="modal-overlay hidden" id="forgot-password-modal">
    <div class="modal-content auth-box">
        <button class="modal-close-btn"><i class="fas fa-times"></i></button>
        <form id="forgot-password-form" autocomplete="on" novalidate>
            <a href="<?php echo $indexPath; ?>" class="brand">
                <img src="<?php echo $assetsPath; ?>img/logo.png" alt="Logo">
                <span>DFC Gym</span>
            </a>
            <h2>Xác thực thông tin</h2>
            <p class="auth-message message" id="forgot-password-message"></p>
            <div class="form-group">
                <label for="verify-phone">Số điện thoại <span class="required">*</span></label>
                <input type="tel" id="verify-phone" name="sdt" required maxlength="10" pattern="[0-9]{10}" placeholder="Nhập số điện thoại">
            </div>
            <div class="form-group">
                <label for="verify-fullname">Họ và tên <span class="required">*</span></label>
                <input type="text" id="verify-fullname" name="ho_ten" required placeholder="Nhập họ và tên đầy đủ">
            </div>
            <div class="form-group">
                <label for="verify-birthday">Ngày sinh <span class="required">*</span></label>
                <input type="date" id="verify-birthday" name="ngay_sinh" required>
            </div>
            <div class="form-group">
                <label for="verify-cccd">Căn cước công dân <span class="required">*</span></label>
                <input type="text" id="verify-cccd" name="cccd" required maxlength="12" pattern="[0-9]{9,12}" placeholder="Nhập số CCCD">
            </div>
            <button type="submit" class="btn" id="verify-identity-btn">Xác thực</button>
            <p class="switch-auth">
                <a href="#dang-nhap" class="switch-to-login" data-modal-target="login-modal">Quay lại đăng nhập</a>
            </p>
        </form>
    </div>
</div>

<!-- Reset Password Modal - Step 2: Nhập mật khẩu mới -->
<div class="modal-overlay hidden" id="reset-password-modal">
    <div class="modal-content auth-box">
        <button class="modal-close-btn"><i class="fas fa-times"></i></button>
        <form id="reset-password-form" autocomplete="off" novalidate>
            <a href="<?php echo $indexPath; ?>" class="brand">
                <img src="<?php echo $assetsPath; ?>img/logo.png" alt="Logo">
                <span>DFC Gym</span>
            </a>
            <h2>Đặt mật khẩu mới</h2>
            <p class="auth-message message" id="reset-password-message"></p>
            <input type="hidden" id="reset-token" name="token" autocomplete="off">
            <div class="form-group">
                <label for="new-password">Mật khẩu mới <span class="required">*</span></label>
                <input type="password" id="new-password" name="password" required minlength="6" placeholder="Tối thiểu 6 ký tự" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirm-password">Xác nhận mật khẩu <span class="required">*</span></label>
                <input type="password" id="confirm-password" name="confirm_password" required minlength="6" placeholder="Nhập lại mật khẩu" autocomplete="new-password">
            </div>
            <button type="submit" class="btn" id="reset-password-btn">Đổi mật khẩu</button>
            <p class="switch-auth">
                <a href="#dang-nhap" class="switch-to-login" data-modal-target="login-modal">Quay lại đăng nhập</a>
            </p>
        </form>
    </div>
</div>

