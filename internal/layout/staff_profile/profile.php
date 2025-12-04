<?php
/**
 * Profile View
 * Chỉ hiển thị HTML và gọi controller/model
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include controller để xử lý các action
require_once 'profile_controller.php';
require_once 'profile_model.php';

// Lấy thông tin nhân viên hiện tại
$employee = null;
$vai_tro = $_SESSION['vai_tro'];
try {
    $username = $_SESSION['username'];
    $employee = getEmployeeByUsername($username, $vai_tro);
    
    if (!$employee) {
        die('Không tìm thấy thông tin nhân viên!');
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
    <title>Thông tin cá nhân - PT Staff</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> Thông tin cá nhân</h1>
            <p class="subtitle">Quản lý và cập nhật thông tin cá nhân của bạn</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alertMessage">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <span><?php echo $message; ?></span>
        </div>
        <?php endif; ?>

        <div class="profile-content">
            <form method="POST" action="" class="profile-form" id="profileForm" novalidate>
                <input type="hidden" name="action" value="update">
                
                <div class="form-section">
                    <h2><i class="fas fa-info-circle"></i> Thông tin cơ bản</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ten_dang_nhap">
                                <i class="fas fa-user"></i>
                                <span>Tên đăng nhập</span>
                            </label>
                            <input 
                                type="text" 
                                id="ten_dang_nhap" 
                                value="<?php echo htmlspecialchars($employee['ten_dang_nhap'] ?? ''); ?>" 
                                readonly
                                disabled
                            >
                            <small>Không thể thay đổi tên đăng nhập</small>
                        </div>

                        <div class="form-group">
                            <label for="ho_ten">
                                <i class="fas fa-id-card"></i>
                                <span>Họ và tên <span class="required">*</span></span>
                            </label>
                            <input 
                                type="text" 
                                id="ho_ten" 
                                name="ho_ten" 
                                value="<?php echo htmlspecialchars($employee['ho_ten'] ?? ''); ?>" 
                                required
                                placeholder="Nhập họ và tên"
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                <span>Email <span class="required">*</span></span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" 
                                required
                                placeholder="example@email.com"
                            >
                        </div>

                        <div class="form-group">
                            <label for="sdt">
                                <i class="fas fa-phone"></i>
                                <span>Số điện thoại <span class="required">*</span></span>
                            </label>
                            <input 
                                type="text" 
                                id="sdt" 
                                name="sdt" 
                                value="<?php echo htmlspecialchars($employee['sdt'] ?? ''); ?>" 
                                required
                                placeholder="0123456789"
                                pattern="[0-9]{10,11}"
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cccd">
                                <i class="fas fa-id-badge"></i>
                                <span>CCCD/CMND <span class="required">*</span></span>
                            </label>
                            <input 
                                type="text" 
                                id="cccd" 
                                name="cccd" 
                                value="<?php echo htmlspecialchars($employee['cccd'] ?? ''); ?>" 
                                required
                                placeholder="123456789012"
                                pattern="[0-9]{9,12}"
                            >
                        </div>

                        <div class="form-group">
                            <label for="gioi_tinh">
                                <i class="fas fa-venus-mars"></i>
                                <span>Giới tính <span class="required">*</span></span>
                            </label>
                            <select id="gioi_tinh" name="gioi_tinh" required>
                                <option value="">-- Chọn giới tính --</option>
                                <option value="Nam" <?php echo ($employee['gioi_tinh'] ?? '') === 'Nam' ? 'selected' : ''; ?>>Nam</option>
                                <option value="Nữ" <?php echo ($employee['gioi_tinh'] ?? '') === 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                                <option value="Khác" <?php echo ($employee['gioi_tinh'] ?? '') === 'Khác' ? 'selected' : ''; ?>>Khác</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ngay_sinh">
                                <i class="fas fa-birthday-cake"></i>
                                <span>Ngày sinh <span class="required">*</span></span>
                            </label>
                            <input 
                                type="date" 
                                id="ngay_sinh" 
                                name="ngay_sinh" 
                                value="<?php echo htmlspecialchars($employee['ngay_sinh'] ?? ''); ?>" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="vai_tro">
                                <i class="fas fa-user-tag"></i>
                                <span>Vai trò</span>
                            </label>
                            <input 
                                type="text" 
                                id="vai_tro" 
                                value="<?php echo htmlspecialchars($employee['vai_tro'] ?? ''); ?>" 
                                readonly
                                disabled
                            >
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="dia_chi">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Địa chỉ <span class="required">*</span></span>
                        </label>
                        <textarea 
                            id="dia_chi" 
                            name="dia_chi" 
                            rows="3" 
                            required
                            placeholder="Nhập địa chỉ đầy đủ"
                        ><?php echo htmlspecialchars($employee['dia_chi'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fas fa-clock"></i> Thông tin hệ thống</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ngay_vao_lam">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Ngày vào làm</span>
                            </label>
                            <input 
                                type="date" 
                                id="ngay_vao_lam" 
                                value="<?php echo htmlspecialchars($employee['ngay_vao_lam'] ?? ''); ?>" 
                                readonly
                                disabled
                            >
                        </div>

                        <div class="form-group">
                            <label for="lan_dang_nhap_cuoi">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Lần đăng nhập cuối</span>
                            </label>
                            <input 
                                type="text" 
                                id="lan_dang_nhap_cuoi" 
                                value="<?php echo $employee['lan_dang_nhap_cuoi'] ? date('d/m/Y H:i:s', strtotime($employee['lan_dang_nhap_cuoi'])) : 'Chưa đăng nhập'; ?>" 
                                readonly
                                disabled
                            >
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <span>Cập nhật thông tin</span>
                    </button>
                    <button type="reset" class="btn btn-secondary" onclick="location.reload()">
                        <i class="fas fa-redo"></i>
                        <span>Làm mới</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="profile.js"></script>
</body>
</html>

