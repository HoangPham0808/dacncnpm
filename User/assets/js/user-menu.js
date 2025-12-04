/**
 * User Menu Dropdown Handler
 * Xử lý menu dropdown của icon người dùng cho tất cả các trang
 */
(function() {
    'use strict';
    
    function initUserMenu() {
        console.log('Initializing user menu...');
        
        const userMenuButton = document.getElementById('user-menu-button');
        const userDropdownMenu = document.getElementById('user-dropdown-menu');
        
        console.log('User menu elements:', { userMenuButton, userDropdownMenu });
        
        if (!userMenuButton || !userDropdownMenu) {
            console.warn('User menu elements not found');
            return;
        }
        
        // Kiểm tra xem dropdown có class hidden ban đầu không (để restore khi đóng)
        const hadHiddenClass = userDropdownMenu.classList.contains('hidden');
        
        // Helper function để toggle dropdown state
        function toggleDropdown() {
            const isActive = userDropdownMenu.classList.contains('active');
            const hasHidden = userDropdownMenu.classList.contains('hidden');
            
            console.log('Toggling dropdown:', { isActive, hasHidden });
            
            // Nếu đang hiển thị (có active và không có hidden)
            if (isActive && !hasHidden) {
                // Ẩn dropdown: xóa active, thêm hidden (nếu ban đầu có)
                userDropdownMenu.classList.remove('active');
                if (hadHiddenClass) {
                    userDropdownMenu.classList.add('hidden');
                }
                console.log('Dropdown hidden');
            } else {
                // Hiển thị dropdown: xóa hidden, thêm active
                userDropdownMenu.classList.remove('hidden');
                userDropdownMenu.classList.add('active');
                console.log('Dropdown shown');
            }
        }
        
        // Helper function để ẩn dropdown
        function hideDropdown() {
            userDropdownMenu.classList.remove('active');
            if (hadHiddenClass) {
                userDropdownMenu.classList.add('hidden');
            }
        }
        
        // Xử lý click vào button - toggle dropdown
        // Sử dụng capture phase để chạy TRƯỚC auth.js
        userMenuButton.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            
            console.log('User menu button clicked');
            toggleDropdown();
        }, true); // Capture phase để chạy trước các script khác
        
        // Đảm bảo click vào icon cũng hoạt động
        const iconInside = userMenuButton.querySelector('i');
        if (iconInside) {
            // Set pointer-events-none để click vào icon cũng trigger button
            iconInside.style.pointerEvents = 'none';
        }
        
        // Đóng dropdown khi click ra ngoài - Chạy sau capture phase
        // KHÔNG chặn event khi click vào link trong dropdown - để auth.js xử lý mở modal
        document.addEventListener('click', function(event) {
            const clickedButton = event.target.closest('#user-menu-button');
            const clickedDropdown = event.target.closest('#user-dropdown-menu');
            const clickedLink = event.target.closest('#user-dropdown-menu a');
            
            // Nếu click vào button, toggle dropdown (đã xử lý ở trên)
            if (clickedButton) {
                return; // Để handler ở trên xử lý
            }
            
            // Nếu click vào link trong dropdown, đóng dropdown sau khi auth.js xử lý
            // KHÔNG preventDefault hoặc stopPropagation - để auth.js có thể xử lý
            if (clickedLink) {
                console.log('Link in dropdown clicked, will close dropdown after modal opens');
                // Đợi một chút để auth.js mở modal trước, sau đó đóng dropdown
                setTimeout(function() {
                    hideDropdown();
                }, 200);
                // KHÔNG return ở đây - để event tiếp tục lan truyền đến auth.js
            }
            
            // Nếu click ra ngoài, đóng dropdown
            if (!clickedButton && !clickedDropdown && !clickedLink) {
                hideDropdown();
            }
        }, false); // Bubble phase để chạy sau capture phase
    }
    
    // Export function để có thể gọi từ bên ngoài
    window.initUserMenu = initUserMenu;
    
    // Chạy ngay nếu DOM đã sẵn sàng - với delay để đảm bảo tất cả scripts đã load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initUserMenu, 200);
        });
    } else {
        setTimeout(initUserMenu, 200);
    }
})();

