<?php
/**
 * Login View
 * Chỉ hiển thị HTML form đăng nhập
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include controller để xử lý logic đăng nhập
require_once 'login_controller.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - DFC Gym</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <!-- Background Animation -->
        <div class="background-animation">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <img src="../../assets/logo.png" alt="DFC Gym Logo" class="logo">
                </div>
                <h1 class="brand-name">DFC GYM</h1>
                <p class="subtitle">Hệ thống quản lý</p>
            </div>

            <form class="login-form" id="loginForm" method="POST" action="login.php" novalidate>
                <?php if ($error_message): ?>
                <div class="error-message" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
                <?php endif; ?>

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
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        <span>Mật khẩu</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Nhập mật khẩu"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Ghi nhớ đăng nhập</span>
                    </label>
                    <a href="forgot_password/forgot_password.php" class="forgot-password">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span class="btn-text">Đăng nhập</span>
                    <i class="fas fa-arrow-right btn-icon"></i>
                </button>
            </form>
        </div>
    </div>

    <script src="login.js"></script>
</body>
</html>

