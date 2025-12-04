// ============================================
// Navigation Module - Smooth Page Transitions
// ============================================
// Dùng cho TẤT CẢ các trang để chuyển trang mượt mà

(function() {
  'use strict';
  
  // Kiểm tra xem đã load chưa
  if (window.SmoothNavigation) {
    return;
  }

  window.SmoothNavigation = {
    isNavigating: false,
    transitionDuration: 250, // Giảm từ 400ms xuống 250ms cho mượt hơn
    
    init: function() {
      this.bindEvents();
      this.preloadOnHover();
      this.optimizePageLoad();
    },
    
    bindEvents: function() {
      const isLoggedIn = document.body.getAttribute('data-logged-in') === 'true';
      const allLinks = document.querySelectorAll('a');
      
      allLinks.forEach(link => {
        // Preload on mouseenter
        link.addEventListener('mouseenter', (e) => {
          const href = link.getAttribute('href');
          if (href && this.shouldPreload(href)) {
            this.preloadPage(href);
          }
        });
        
        // Handle click
        link.addEventListener('click', (e) => {
          const href = link.getAttribute('href');
          const target = link.getAttribute('target');
          
          // Bỏ qua các link đặc biệt
          if (!href || 
              href.startsWith('#') ||
              href.startsWith('mailto:') ||
              href.startsWith('tel:') ||
              href.startsWith('javascript:') ||
              link.hasAttribute('data-modal-target') ||
              target === '_blank' ||
              link.hasAttribute('download')
          ) {
            return;
          }
          
          // Kiểm tra xem có phải external link không
          if (this.isExternalLink(href)) {
            return; // Để trình duyệt xử lý
          }
          
          // Bỏ qua xử lý navigation - để trình duyệt xử lý trực tiếp
          // Không có hiệu ứng chuyển trang
          return;
        });
      });
      
      // Smooth scroll cho anchor links
      this.setupSmoothScroll();
    },
    
    shouldPreload: function(href) {
      return href && 
             !href.startsWith('#') &&
             !href.startsWith('mailto:') &&
             !href.startsWith('tel:') &&
             !href.startsWith('javascript:') &&
             !this.isExternalLink(href);
    },
    
    shouldHandleNavigation: function(href) {
      // Xử lý cho tất cả internal links, không phân biệt đăng nhập hay chưa
      return href && !this.isExternalLink(href);
    },
    
    isExternalLink: function(href) {
      try {
        const url = new URL(href, window.location.origin);
        return url.origin !== window.location.origin;
      } catch (e) {
        return false;
      }
    },
    
    preloadPage: function(href) {
      // Chỉ preload một lần
      if (this.preloadedPages && this.preloadedPages.has(href)) {
        return;
      }
      
      if (!this.preloadedPages) {
        this.preloadedPages = new Set();
      }
      
      // Tạo link element để browser preload
      const link = document.createElement('link');
      link.rel = 'prefetch';
      link.href = href;
      document.head.appendChild(link);
      this.preloadedPages.add(href);
    },
    
    preloadOnHover: function() {
      // Preload khi hover vào link navigation
      const navLinks = document.querySelectorAll('nav a[href], .menu-panel a[href]');
      navLinks.forEach(link => {
        link.addEventListener('mouseenter', () => {
          const href = link.getAttribute('href');
          if (this.shouldPreload(href)) {
            this.preloadPage(href);
          }
        }, { once: true }); // Chỉ preload một lần
      });
    },
    
    navigateTo: function(href) {
      // Chuyển trang trực tiếp không có hiệu ứng
      window.location.href = href;
    },
    
    setupSmoothScroll: function() {
      const navLinks = document.querySelectorAll('a[href^="#"]');
      navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
          const href = link.getAttribute('href');
          
          // Bỏ qua modal links
          if (link.hasAttribute('data-modal-target') || 
              href === '#dang-nhap' || 
              href === '#dang-ky') {
            return;
          }
          
          const targetId = href.substring(1);
          if (targetId) {
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
              e.preventDefault();
              
              // Tính offset cho header fixed
              const headerHeight = 72; // --header-height
              const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;
              
              window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
              });
              
              // Update URL hash
              history.pushState(null, '', href);
            }
          }
        });
      });
    },
    
    optimizePageLoad: function() {
      // Tối ưu khi page load
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
          this.onPageLoad();
        });
      } else {
        this.onPageLoad();
      }
    },
    
    onPageLoad: function() {
      // Bỏ qua fade-in animation
      // Preload các link navigation phổ biến
      setTimeout(() => {
        const commonLinks = document.querySelectorAll('nav a[href], .menu-panel a[href]');
        commonLinks.forEach((link, index) => {
          if (index < 3) { // Chỉ preload 3 link đầu tiên
            const href = link.getAttribute('href');
            if (window.SmoothNavigation.shouldPreload(href)) {
              window.SmoothNavigation.preloadPage(href);
            }
          }
        });
      }, 1000); // Preload sau 1 giây
    }
  };
  
  // Auto-init khi DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      window.SmoothNavigation.init();
    });
  } else {
    window.SmoothNavigation.init();
  }
  
})();

