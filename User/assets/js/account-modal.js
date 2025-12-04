/**
 * Account Modal Handler
 * Xử lý modal tài khoản và chuyển tab cho tất cả các trang
 * Xử lý hash trong URL và chuyển đổi giữa các tab
 */
document.addEventListener('DOMContentLoaded', function() {
    const accountModal = document.getElementById('account-modal');
    if (!accountModal) {
        console.log('Account modal not found, skipping account-modal.js');
        return;
    }
    
    const tabMenu = accountModal.querySelector('.account-tab-menu');
    const modalSections = accountModal.querySelectorAll('.account-section-modal');
    const modalTabLinks = accountModal.querySelectorAll('.account-tab-link');
    
    // Hàm chuyển tab
    function switchToTab(hash) {
        // Chuyển hash sang ID section: #profile -> #acc-profile
        const hashToId = {
            '#profile': '#acc-profile',
            '#my-packages': '#acc-packages',
            '#payment-history': '#acc-history', 
            '#payment-management': '#acc-payment',
            '#change-password': '#acc-password'
        };
        
        const targetId = hashToId[hash] || '#acc-profile';
        
        // Ẩn tất cả sections
        modalSections.forEach(function(s) { s.classList.remove('active'); });
        
        // Tắt active tất cả tab links
        modalTabLinks.forEach(function(l) { l.classList.remove('active'); });
        
        // Hiện section được chọn
        const targetSection = accountModal.querySelector(targetId);
        if (targetSection) {
            targetSection.classList.add('active');
        }
        
        // Active tab tương ứng
        const targetTab = accountModal.querySelector('.account-tab-link[data-target="' + targetId + '"]');
        if (targetTab) {
            targetTab.classList.add('active');
        }
        
        // Đảm bảo menu tab luôn hiển thị
        if (tabMenu) {
            tabMenu.style.display = 'flex';
            tabMenu.style.visibility = 'visible';
            tabMenu.style.opacity = '1';
            tabMenu.style.height = 'auto';
            tabMenu.style.overflow = 'visible';
            tabMenu.style.margin = '';
            tabMenu.style.padding = '';
            tabMenu.style.border = '';
        }
    }
    
    // Hàm kiểm tra và xử lý hash khi modal mở
    function handleModalOpen() {
        // Kiểm tra xem modal có đang mở không
        if (accountModal && accountModal.classList.contains('active') && !accountModal.classList.contains('hidden')) {
            // Lấy hash từ URL hoặc từ biến global
            let hash = window.location.hash || window.__pendingAccountTab;
            
            // Nếu không có hash, mặc định là #profile
            if (!hash || !hash.startsWith('#')) {
                hash = '#profile';
            }
            
            // Chuyển đến tab đúng
            switchToTab(hash);
            
            // Clear biến global sau khi đã xử lý
            if (window.__pendingAccountTab) {
                window.__pendingAccountTab = null;
            }
        }
    }
    
    // Lắng nghe click vào các link trong dropdown menu
    // Sử dụng event delegation để đảm bảo hoạt động với dynamic content
    // Chạy TRƯỚC auth.js (capture: true) để có thể lưu hash trước khi auth.js xử lý
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[data-modal-target]');
        if (!link) return;
        
        const modalTarget = link.getAttribute('data-modal-target');
        if (!modalTarget) return;
        
        // Chỉ xử lý account-modal
        if (modalTarget !== 'account-modal') return;
        
        // Chỉ xử lý nếu link nằm trong dropdown hoặc menu panel
        const isInDropdown = link.closest('.user-dropdown') || link.closest('.menu-panel .user-menu-wrapper');
        if (!isInDropdown) return;
        
        const hash = link.getAttribute('href');
        if (!hash || !hash.startsWith('#')) return;
        
        // Lưu hash vào một biến global để sử dụng sau khi modal mở
        window.__pendingAccountTab = hash;
        
        // KHÔNG preventDefault ở đây - để auth.js xử lý mở modal
        // Chỉ xử lý chuyển tab sau khi modal đã mở
        
        // Đợi auth.js mở modal trước, sau đó chuyển tab
        // Sử dụng MutationObserver để đợi modal thực sự mở
        const checkModalOpen = function() {
            const modal = document.getElementById(modalTarget);
            if (modal && modal.classList.contains('active') && !modal.classList.contains('hidden')) {
                if (modalTarget === 'account-modal') {
                    // Chuyển đến tab đúng ngay lập tức
                    handleModalOpen();
                }
            } else {
                // Nếu modal chưa mở, thử lại sau 50ms (tối đa 2 giây)
                if (Date.now() - startTime < 2000) {
                    setTimeout(checkModalOpen, 50);
                }
            }
        };
        
        const startTime = Date.now();
        // Bắt đầu kiểm tra sau 100ms
        setTimeout(checkModalOpen, 100);
    }, true); // Chạy TRƯỚC auth.js (capture: true)
    
    // Lắng nghe sự kiện khi modal được mở (từ auth.js)
    // Sử dụng MutationObserver để phát hiện khi modal được mở
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                if (accountModal.classList.contains('active') && !accountModal.classList.contains('hidden')) {
                    // Modal đã mở, xử lý hash
                    handleModalOpen();
                }
            }
        });
    });
    
    // Bắt đầu quan sát modal
    observer.observe(accountModal, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Xử lý hash khi trang load (nếu modal đã mở sẵn)
    if (accountModal.classList.contains('active') && !accountModal.classList.contains('hidden')) {
        handleModalOpen();
    }
    
    // Hiện lại menu tab khi click vào các nút tab bên trong modal (nếu menu tab đang ẩn)
    // Sử dụng event delegation để đảm bảo hoạt động
    accountModal.addEventListener('click', function(e) {
        const btn = e.target.closest('.account-tab-link[data-target]');
        if (!btn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        // Hiện lại menu tab khi click vào tab (nếu đang ẩn)
        if (tabMenu && (tabMenu.style.display === 'none' || tabMenu.style.display === '')) {
            tabMenu.style.display = 'flex';
            tabMenu.style.visibility = 'visible';
            tabMenu.style.opacity = '1';
            tabMenu.style.height = 'auto';
            tabMenu.style.overflow = 'visible';
            tabMenu.style.margin = '';
            tabMenu.style.padding = '';
            tabMenu.style.border = '';
        }
        // Chuyển tab
        const targetId = btn.getAttribute('data-target');
        if (targetId) {
            modalSections.forEach(function(s) { s.classList.remove('active'); });
            modalTabLinks.forEach(function(l) { l.classList.remove('active'); });
            const section = accountModal.querySelector(targetId);
            if (section) section.classList.add('active');
            btn.classList.add('active');
        }
    });
    
    // Reset khi đóng modal
    const closeBtn = document.getElementById('account-modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            // Hiện lại menu tab khi đóng modal
            if (tabMenu) {
                tabMenu.style.display = 'flex';
                tabMenu.style.visibility = 'visible';
                tabMenu.style.opacity = '1';
                tabMenu.style.height = 'auto';
                tabMenu.style.overflow = 'visible';
                tabMenu.style.margin = '';
                tabMenu.style.padding = '';
                tabMenu.style.border = '';
            }
            // Reset về tab đầu tiên khi đóng
            switchToTab('#profile');
        });
    }
    
    // Đóng modal khi click vào overlay
    accountModal.addEventListener('click', function(e) {
        if (e.target === accountModal) {
            // Hiện lại menu tab khi đóng modal
            if (tabMenu) {
                tabMenu.style.display = 'flex';
                tabMenu.style.visibility = 'visible';
                tabMenu.style.opacity = '1';
                tabMenu.style.height = 'auto';
                tabMenu.style.overflow = 'visible';
                tabMenu.style.margin = '';
                tabMenu.style.padding = '';
                tabMenu.style.border = '';
            }
            // Reset về tab đầu tiên khi đóng
            switchToTab('#profile');
        }
    });
});
