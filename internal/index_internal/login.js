// Toggle password visibility
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const errorMessage = document.getElementById('errorMessage');

    // Toggle password visibility
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = togglePassword.querySelector('i');
            if (type === 'password') {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });
    }

    // Form validation và submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Ngăn submit mặc định để kiểm tra validation
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            // Basic validation
            if (!username || !password) {
                showError('Vui lòng điền đầy đủ thông tin đăng nhập!');
                resetButtonState();
                return false;
            }

            if (username.length < 3) {
                showError('Tên đăng nhập phải có ít nhất 3 ký tự!');
                resetButtonState();
                return false;
            }

            if (password.length < 6) {
                showError('Mật khẩu phải có ít nhất 6 ký tự!');
                resetButtonState();
                return false;
            }

            // Show loading state trước khi submit
            if (loginBtn && !loginBtn.disabled) {
                loginBtn.classList.add('loading');
                loginBtn.disabled = true;
                const btnText = loginBtn.querySelector('.btn-text');
                if (btnText) {
                    btnText.textContent = 'Đang đăng nhập...';
                }
            }

            // Nếu validation pass, submit form
            loginForm.submit();
        });
    }

    // Function to reset button state
    function resetButtonState() {
        if (loginBtn) {
            loginBtn.classList.remove('loading');
            loginBtn.disabled = false;
            const btnText = loginBtn.querySelector('.btn-text');
            if (btnText) {
                btnText.textContent = 'Đăng nhập';
            }
        }
    }

    // Auto-hide error message after 10 seconds
    if (errorMessage) {
        setTimeout(function() {
            errorMessage.style.opacity = '0';
            errorMessage.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                errorMessage.style.display = 'none';
            }, 500);
        }, 10000);
    }

    // Show error function
    function showError(message) {
        let errorDiv = document.getElementById('errorMessage');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'errorMessage';
            errorDiv.className = 'error-message';
            loginForm.insertBefore(errorDiv, loginForm.firstChild);
        }

        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
        `;
        errorDiv.style.display = 'flex';
        errorDiv.style.opacity = '1';

        // Scroll to error
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Auto-hide after 10 seconds
        setTimeout(function() {
            errorDiv.style.opacity = '0';
            setTimeout(function() {
                errorDiv.style.display = 'none';
            }, 500);
        }, 10000);
    }


    // Focus on username field on load
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.focus();
    }
});

