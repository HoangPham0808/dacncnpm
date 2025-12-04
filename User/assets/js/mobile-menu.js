/**
 * Mobile Menu Handler - Xử lý mobile menu cho tất cả các trang đã đăng nhập
 */
(function() {
  'use strict';
  
  function initMobileMenu() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    
    if (!mobileMenuToggle || !mobileMenu || !mobileMenuClose) {
      return;
    }
    
    // Toggle menu
    mobileMenuToggle.addEventListener('click', function() {
      mobileMenu.classList.remove('hidden');
    });
    
    // Close menu
    mobileMenuClose.addEventListener('click', function() {
      mobileMenu.classList.add('hidden');
    });
    
    // Close menu when clicking on links
    const mobileMenuLinks = mobileMenu.querySelectorAll('a[href]:not([data-modal-target])');
    mobileMenuLinks.forEach(link => {
      link.addEventListener('click', function() {
        setTimeout(() => {
          mobileMenu.classList.add('hidden');
        }, 300);
      });
    });
    
    // Close menu when modal opens
    document.addEventListener('click', function(e) {
      const modalButton = e.target.closest('[data-modal-target]');
      if (modalButton) {
        setTimeout(() => {
          if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
          }
        }, 100);
      }
    });
  }
  
  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileMenu);
  } else {
    initMobileMenu();
  }
})();

