<?php
/**
 * Forgot Password View
 * Chỉ hiển thị HTML form quên mật khẩu
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include controller để xử lý logic các bước
require_once 'forgot_password_controller.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu - DFC Gym</title>
    <link rel="stylesheet" href="forgot_password.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="forgot-password-container">
        <div class="background-animation">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
        </div>

        <div class="forgot-password-card">
            <div class="card-header">
                <a href="../login.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="logo-container">
                    <img src="../../assets/logo.png" alt="DFC Gym Logo" class="logo" onerror="this.style.display='none'">
                </div>
                <h1 class="brand-name">Quên Mật Khẩu</h1>
                <p class="subtitle">Khôi phục mật khẩu cho nhân viên PT/PR/Lễ Tân</p>
            </div>

            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step-item <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    <div class="step-number">1</div>
                    <span class="step-label">Tên đăng nhập</span>
                </div>
                <div class="step-item <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    <div class="step-number">2</div>
                    <span class="step-label">Xác thực</span>
                </div>
                <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?>">
                    <div class="step-number">3</div>
                    <span class="step-label">Mật khẩu mới</span>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>

            <!-- Bước 1: Nhập tên đăng nhập -->
            <?php if ($step == 1): ?>
            <form method="POST" action="forgot_password.php" novalidate>
                <input type="hidden" name="step" value="1">
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        <span>Tên đăng nhập</span>
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        placeholder="Nhập tên đăng nhập"
                        required
                        autofocus
                    >
                </div>

                <button type="submit" class="submit-btn">
                    <span>Tiếp theo</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            <?php endif; ?>

            <!-- Bước 2: Xác thực email và số điện thoại -->
            <?php if ($step == 2): ?>
            <form method="POST" action="forgot_password.php" novalidate>
                <input type="hidden" name="step" value="2">
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <p>Nhập đầy đủ thông tin để xác thực:</p>
                    <p class="info-detail">Email: <strong><?php echo htmlspecialchars($masked_email); ?></strong></p>
                    <p class="info-detail">Số điện thoại: <strong><?php echo htmlspecialchars($masked_sdt); ?></strong></p>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        <span>Email đầy đủ</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Nhập email đầy đủ"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="sdt">
                        <i class="fas fa-phone"></i>
                        <span>Số điện thoại đầy đủ</span>
                    </label>
                    <input 
                        type="text" 
                        id="sdt" 
                        name="sdt" 
                        placeholder="Nhập số điện thoại đầy đủ"
                        required
                    >
                </div>

                <div class="form-actions">
                    <a href="forgot_password.php?step=1" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        <span>Quay lại</span>
                    </a>
                    <button type="submit" class="submit-btn">
                        <span>Xác thực</span>
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <!-- Bước 3: Đặt mật khẩu mới -->
            <?php if ($step == 3): ?>
            <form method="POST" action="forgot_password.php" novalidate>
                <input type="hidden" name="step" value="3">
                
                <div class="info-box success">
                    <i class="fas fa-check-circle"></i>
                    <p>Xác thực thành công! Đặt mật khẩu mới cho tài khoản <strong><?php echo htmlspecialchars($_SESSION['reset_username']); ?></strong></p>
                </div>

                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-lock"></i>
                        <span>Mật khẩu mới</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            placeholder="Tối thiểu 6 ký tự"
                            required
                            autofocus
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        <span>Xác nhận mật khẩu</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Nhập lại mật khẩu"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="forgot_password.php?step=2" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        <span>Quay lại</span>
                    </a>
                    <button type="submit" class="submit-btn">
                        <span>Đặt lại mật khẩu</span>
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <div class="card-footer">
                <p>Nhớ mật khẩu? <a href="../login.php">Đăng nhập ngay</a></p>
            </div>
        </div>
    </div>

    <script src="forgot_password.js"></script>
</body>
</html>
