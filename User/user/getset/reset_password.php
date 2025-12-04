<?php
// reset_password.php — Trang đặt lại mật khẩu
session_start();

require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

if (!defined('BASE_URL')) {
    $base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    define('BASE_URL', ($base ? $base : '') . '/');
}

// Lấy token từ URL
$token = $_GET['token'] ?? '';

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } else {
        try {
            // Kiểm tra token - tìm trong bảng TaiKhoan
            $stmt = $pdo->prepare("SELECT ten_dang_nhap, reset_token_expiry FROM TaiKhoan WHERE reset_token = :token AND reset_token_expiry > NOW() LIMIT 1");
            $stmt->execute(['token' => $token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn';
            } else {
                // Cập nhật mật khẩu mới
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE TaiKhoan SET mat_khau = :hash, reset_token = NULL, reset_token_expiry = NULL WHERE ten_dang_nhap = :username");
                $stmt->execute([
                    'hash' => $password_hash,
                    'username' => $user['ten_dang_nhap']
                ]);
                
                // Chuyển về trang đăng nhập với thông báo thành công
                $url = rtrim(BASE_URL, '/').'/index.html?msg='.urlencode('Đặt lại mật khẩu thành công! Vui lòng đăng nhập.').'&type=success#dang-nhap';
                header("Location: $url");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Reset Password Error: " . $e->getMessage());
            $error = 'Đã xảy ra lỗi. Vui lòng thử lại sau.';
        }
    }
}

// Kiểm tra token hợp lệ khi load trang
$token_valid = false;
$email = '';
if (!empty($token)) {
    try {
        // Tìm email từ bảng KhachHang dựa trên ten_dang_nhap
        $stmt = $pdo->prepare("SELECT k.email FROM TaiKhoan t 
                               JOIN KhachHang k ON t.ten_dang_nhap = k.ten_dang_nhap 
                               WHERE t.reset_token = :token AND t.reset_token_expiry > NOW() LIMIT 1");
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch();
        if ($result) {
            $token_valid = true;
            $email = $result['email'];
        }
    } catch (PDOException $e) {
        error_log("Token Check Error: " . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đặt lại mật khẩu - DFC Gym</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* Ẩn thanh cuộn cho toàn bộ trang */
    html {
      overflow-y: scroll;
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE and Edge */
    }
    html::-webkit-scrollbar {
      display: none; /* Chrome, Safari, Opera */
    }
    
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .reset-container {
      background: white;
      border-radius: 12px;
      padding: 40px;
      max-width: 450px;
      width: 100%;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    .brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 30px;
      text-decoration: none;
      color: var(--text);
    }
    .brand img {
      width: 50px;
      height: 50px;
    }
    .brand span {
      font-size: 24px;
      font-weight: 700;
      color: var(--primary);
    }
    h2 {
      text-align: center;
      margin-bottom: 10px;
      color: var(--text);
    }
    .subtitle {
      text-align: center;
      color: var(--muted);
      font-size: 14px;
      margin-bottom: 30px;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text);
    }
    .form-group input {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 15px;
    }
    .form-group input:focus {
      outline: none;
      border-color: var(--primary);
    }
    .btn {
      width: 100%;
      padding: 14px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    .error-box {
      background: #fee;
      border: 1px solid #fcc;
      color: #c33;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
    }
    .success-box {
      background: #efe;
      border: 1px solid #cfc;
      color: #3c3;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
    }
    .back-link {
      text-align: center;
      margin-top: 20px;
    }
    .back-link a {
      color: var(--primary);
      text-decoration: none;
      font-size: 14px;
    }
    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="reset-container">
    <a href="../index.html" class="brand">
      <img src="../../assets/img/logo.png" alt="Logo">
      <span>DFC Gym</span>
    </a>
    
    <?php if (!$token_valid): ?>
      <h2>Link không hợp lệ</h2>
      <div class="error-box">
        Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.
      </div>
      <div class="back-link">
        <a href="../index.html#quen-mat-khau">← Yêu cầu link mới</a>
      </div>
    <?php else: ?>
      <h2>Đặt lại mật khẩu</h2>
      <p class="subtitle">Tài khoản: <?php echo htmlspecialchars($email); ?></p>
      
      <?php if (isset($error)): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      
      <form method="POST" action="">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        
        <div class="form-group">
          <label for="new-password">Mật khẩu mới</label>
          <input type="password" id="new-password" name="new_password" required minlength="6">
        </div>
        
        <div class="form-group">
          <label for="confirm-password">Xác nhận mật khẩu</label>
          <input type="password" id="confirm-password" name="confirm_password" required minlength="6">
        </div>
        
        <button type="submit" class="btn">Đặt lại mật khẩu</button>
      </form>
      
      <div class="back-link">
        <a href="../index.html#dang-nhap">← Quay lại đăng nhập</a>
      </div>
    <?php endif; ?>
  </div>
  
  <script>
    // Validation for reset password form
    const resetForm = document.querySelector('form');
    if (resetForm) {
      // Helper functions
      function showError(input, message) {
        const formGroup = input.closest('.form-group');
        if (!formGroup) return;
        
        const existingError = formGroup.querySelector('.error-text');
        if (existingError) existingError.remove();
        
        input.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-text';
        errorDiv.textContent = message;
        errorDiv.style.color = '#ff3040';
        errorDiv.style.fontSize = '13px';
        errorDiv.style.marginTop = '6px';
        formGroup.appendChild(errorDiv);
      }
      
      function clearError(input) {
        const formGroup = input.closest('.form-group');
        if (!formGroup) return;
        
        input.classList.remove('error');
        const existingError = formGroup.querySelector('.error-text');
        if (existingError) existingError.remove();
      }
      
      // Clear error on input
      const inputs = resetForm.querySelectorAll('input[type="password"]');
      inputs.forEach(input => {
        input.addEventListener('input', function() {
          clearError(this);
        });
      });
      
      // Validate on submit
      resetForm.addEventListener('submit', function(e) {
        let isValid = true;
        const newPassword = document.getElementById('new-password');
        const confirmPassword = document.getElementById('confirm-password');
        
        clearError(newPassword);
        clearError(confirmPassword);
        
        if (!newPassword.value) {
          showError(newPassword, 'Vui lòng nhập mật khẩu mới');
          isValid = false;
        } else if (newPassword.value.length < 6) {
          showError(newPassword, 'Mật khẩu phải có ít nhất 6 ký tự');
          isValid = false;
        }
        
        if (!confirmPassword.value) {
          showError(confirmPassword, 'Vui lòng xác nhận mật khẩu');
          isValid = false;
        } else if (confirmPassword.value !== newPassword.value) {
          showError(confirmPassword, 'Mật khẩu xác nhận không khớp');
          isValid = false;
        }
        
        if (!isValid) {
          e.preventDefault();
        }
      });
    }
  </script>
  
  <style>
    .form-group input.error {
      border-color: #ff3040 !important;
      background: rgba(255, 48, 64, 0.1) !important;
    }
    
    .form-group input.error:focus {
      border-color: #ff3040 !important;
      box-shadow: 0 0 0 4px rgba(255, 48, 64, 0.2) !important;
    }
  </style>
</body>
</html>

