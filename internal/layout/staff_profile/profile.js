document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alert messages after 5 seconds
    const alertMessage = document.getElementById('alertMessage');
    if (alertMessage) {
        setTimeout(() => {
            alertMessage.style.opacity = '0';
            setTimeout(() => {
                alertMessage.style.display = 'none';
            }, 300);
        }, 5000);
    }

    // Form validation
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            // Basic validation is handled by HTML5 required attributes
            // Additional custom validation can be added here if needed
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Đang cập nhật...</span>';
            }
        });
    }

    // Format phone number input
    const sdtInput = document.getElementById('sdt');
    if (sdtInput) {
        sdtInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Format CCCD input
    const cccdInput = document.getElementById('cccd');
    if (cccdInput) {
        cccdInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
});

