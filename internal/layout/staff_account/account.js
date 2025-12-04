document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide messages after 5 seconds
    const message = document.querySelector('.message');
    if (message) {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 300);
        }, 5000);
    }

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    });

    // Form validation for password change
    const passwordForm = document.querySelector('.password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value.trim();
            const newPassword = document.getElementById('new_password').value.trim();
            const confirmPassword = document.getElementById('confirm_password').value.trim();
            
            let errors = [];

            if (!currentPassword) {
                errors.push('Vui lòng nhập mật khẩu hiện tại.');
            }

            if (!newPassword) {
                errors.push('Vui lòng nhập mật khẩu mới.');
            } else if (newPassword.length < 6) {
                errors.push('Mật khẩu mới phải có ít nhất 6 ký tự.');
            }

            if (!confirmPassword) {
                errors.push('Vui lòng xác nhận mật khẩu mới.');
            } else if (newPassword !== confirmPassword) {
                errors.push('Mật khẩu xác nhận không khớp.');
            }

            if (errors.length > 0) {
                e.preventDefault();
                const errorMessageDiv = document.createElement('div');
                errorMessageDiv.classList.add('message', 'error');
                errorMessageDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> <span>${errors.join('<br>')}</span>`;
                
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    const existingMessage = mainContent.querySelector('.message');
                    if (existingMessage) existingMessage.remove();
                    mainContent.prepend(errorMessageDiv);
                    // Auto-hide after 5 seconds
                    setTimeout(() => {
                        errorMessageDiv.style.opacity = '0';
                        setTimeout(() => errorMessageDiv.remove(), 300);
                    }, 5000);
                }
                return false;
            }

            // Show loading state
            const submitBtn = this.querySelector('.btn-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            }
        });
    }
});

