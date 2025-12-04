(function () {
    'use strict';

    let activeBanner = null;
    let hideTimeout = null;
    let removeTimeout = null;

    function removeBanner(banner) {
        if (!banner) return;
        banner.classList.remove('show');
        if (hideTimeout) clearTimeout(hideTimeout);
        if (removeTimeout) clearTimeout(removeTimeout);
        removeTimeout = setTimeout(() => {
            try { banner.remove(); } catch (e) {}
            if (activeBanner === banner) activeBanner = null;
        }, 300);
    }

    function showFlashBanner(message, duration = 5000) {
        if (!message) {
            console.log('Flash banner: Không có message'); // Debug
            return;
        }

        console.log('Flash banner: Tạo banner với message:', message); // Debug

        if (activeBanner && activeBanner.parentNode) {
            activeBanner.remove();
        }

        const banner = document.createElement('div');
        banner.className = 'flash-banner';
        banner.textContent = message;
        document.body.appendChild(banner);
        activeBanner = banner;
        
        console.log('Flash banner: Banner đã được thêm vào DOM'); // Debug

        // click để đóng sớm
        banner.addEventListener('click', () => removeBanner(banner));

        // Đảm bảo banner hiển thị
        requestAnimationFrame(() => {
            banner.classList.add('show');
            console.log('Flash banner: Đã thêm class show'); // Debug
        });

        hideTimeout = setTimeout(() => removeBanner(banner), duration);

        // failsafe sau duration + 2s
        setTimeout(() => {
            if (activeBanner === banner && banner.parentNode) removeBanner(banner);
        }, duration + 2000);
    }

    function handleUrlNotification() {
        const params = new URLSearchParams(window.location.search);
        const notify = params.get('notify');
        const msg = params.get('msg');
        const type = params.get('type');

        // 1) Thông báo đổi mật khẩu
        if (notify === 'password_changed') {
            showFlashBanner('Đổi mật khẩu thành công! Mật khẩu mới của bạn đã được lưu vào hệ thống.', 5000);
        }

        // 2) Tất cả success khác: đặt gói, hủy gói, đặt lịch, hủy lịch, gửi hỗ trợ
        if (msg && type === 'success') {
            console.log('Flash banner: Hiển thị thông báo thành công:', msg); // Debug
            // Tăng thời gian hiển thị cho thông báo lưu phương thức thanh toán
            const isPaymentMethod = msg.includes('phương thức thanh toán') || msg.includes('payment method');
            const duration = isPaymentMethod ? 7000 : 5000;
            showFlashBanner(msg, duration);
        }
        
        // 3) Thông báo lỗi (nếu cần)
        if (msg && type === 'error') {
            console.log('Flash banner: Hiển thị thông báo lỗi:', msg); // Debug
            showFlashBanner(msg, 7000);
        }

        // Clean URL nếu có thông báo
        if (notify === 'password_changed' || (msg && (type === 'success' || type === 'error'))) {
            setTimeout(() => {
                const cleanUrl = window.location.origin + window.location.pathname + window.location.hash;
                window.history.replaceState({}, document.title, cleanUrl);
            }, 600);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', handleUrlNotification);
    } else {
        handleUrlNotification();
    }

    window.showFlashBanner = showFlashBanner;
})();


