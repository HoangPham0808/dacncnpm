// JavaScript cho review.html

document.addEventListener('DOMContentLoaded', function() {
  // Menu Mobile Script
  const menuToggle = document.querySelector('.menu-toggle');
  const menuPanel = document.querySelector('#main-menu');
  const closeBtn = document.querySelector('.nav-close');
  const pageWrapper = document.querySelector('#page-wrapper');
  const menuLinks = menuPanel ? menuPanel.querySelectorAll('a:not(.user-menu-wrapper a), span:not(.menu-header span)') : []; 
  const userMenuLinks = menuPanel ? menuPanel.querySelectorAll('.user-menu-wrapper a') : [];
  const body = document.body;
  
  if (menuToggle && menuPanel) {
    function closeMenu() {
      // Khôi phục scroll position
      const scrollY = document.documentElement.style.getPropertyValue('--scroll-y');
      body.style.position = '';
      body.style.top = '';
      body.style.width = '';
      
      body.classList.remove('menu-is-active', 'no-scroll');
      document.documentElement.classList.remove('menu-is-active');
      menuToggle.classList.remove('is-active');
      menuPanel.classList.remove('is-active');
      if (pageWrapper) pageWrapper.classList.remove('is-active');
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.setAttribute('aria-label', 'Mở menu');
      
      // Khôi phục scroll position
      if (scrollY) {
        window.scrollTo(0, parseInt(scrollY || '0', 10));
      }
      
      // Reset animation
      menuLinks.forEach(item => {
        item.style.animation = '';
      });
      userMenuLinks.forEach(item => {
        item.style.animation = '';
      });
    }
    
    function openMenu() {
      // Lưu scroll position trước khi lock
      const scrollY = window.scrollY;
      document.documentElement.style.setProperty('--scroll-y', `${scrollY}px`);
      
      // QUAN TRỌNG: Reset scroll và đảm bảo menu header ở đầu TRƯỚC KHI set active
      function resetMenuScroll() {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
          const menuHeader = menuPanel.querySelector('.menu-header');
          if (menuHeader) {
            if (menuPanel.firstChild !== menuHeader) {
              menuPanel.insertBefore(menuHeader, menuPanel.firstChild);
            }
            menuHeader.style.setProperty('display', 'flex', 'important');
            menuHeader.style.setProperty('visibility', 'visible', 'important');
            menuHeader.style.setProperty('opacity', '1', 'important');
            menuHeader.style.setProperty('transform', 'translateY(0) translateX(0)', 'important');
            menuHeader.style.setProperty('position', 'relative', 'important');
            menuHeader.style.setProperty('top', '0', 'important');
            menuHeader.style.setProperty('z-index', '100', 'important');
            menuHeader.style.setProperty('order', '-1', 'important');
          }
          menuPanel.scrollTop = 0;
        }
      }
      resetMenuScroll();
      
      // Sau đó mới set active
      body.classList.add('menu-is-active', 'no-scroll');
      document.documentElement.classList.add('menu-is-active');
      menuToggle.classList.add('is-active');
      menuPanel.classList.add('is-active');
      if (pageWrapper) pageWrapper.classList.add('is-active');
      menuToggle.setAttribute('aria-expanded', 'true');
      menuToggle.setAttribute('aria-label', 'Đóng menu');
      
      // Chặn scroll bằng cách set position fixed
      body.style.position = 'fixed';
      body.style.top = `-${scrollY}px`;
      body.style.width = '100%';
      
      // Reset lại nhiều lần để đảm bảo
      setTimeout(resetMenuScroll, 0);
      setTimeout(resetMenuScroll, 10);
      setTimeout(resetMenuScroll, 50);
      setTimeout(resetMenuScroll, 100);
      setTimeout(resetMenuScroll, 200);
      requestAnimationFrame(() => {
        resetMenuScroll();
        requestAnimationFrame(resetMenuScroll);
      });
      
      // Reset và đảm bảo menu links luôn có thể click được
      menuLinks.forEach((link, index) => {
        link.style.pointerEvents = 'auto';
        link.style.cursor = 'pointer';
        // Reset opacity và transform trước khi animation
        link.style.opacity = '0';
        link.style.transform = 'translateX(50px)';
        // Fade từng item với animation
        link.style.animation = `navLinkFade 0.5s ease forwards ${index/7 + 0.25}s`;
      });
      
      // User menu items đã được xử lý bởi CSS animation với delay
    }
    
    // Toggle menu khi click vào nút menu
    menuToggle.addEventListener('click', function(e) {
      e.stopPropagation();
      if (body.classList.contains('menu-is-active')) {
        closeMenu();
      } else {
        openMenu();
      }
    });
    
    // Đóng menu khi click vào nút close trong menu
    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        closeMenu();
      });
    }
    
    // Đóng menu khi click vào các link trong menu
    menuLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        // Không đóng menu ngay nếu là button đăng nhập (có data-modal-target)
        if (link.hasAttribute('data-modal-target')) {
          // Cho phép modal mở trước, sau đó đóng menu
          setTimeout(() => {
            closeMenu();
          }, 100);
        } else {
          // Đóng menu ngay lập tức cho các link khác
          closeMenu();
        }
      });
    });
    
    // ESC để đóng menu
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && body.classList.contains('menu-is-active')) {
        closeMenu();
      }
    });
  }
  
  // --- Message Handling Script ---
  const loginMsgTarget = document.getElementById('login-message');
  const regMsgTarget = document.getElementById('register-message');
  const params = new URLSearchParams(window.location.search);
  const msg = params.get('msg');
  const type = params.get('type') || 'error';
  const hash = (window.location.hash || '').replace('#', '');
  
  if (msg) { 
    let targetElement = null;
    let targetModal = null;
    
    if (hash === 'dang-nhap' || hash === 'login') {
      targetElement = loginMsgTarget;
      targetModal = document.getElementById('login-modal');
    } else if (hash === 'dang-ky' || hash === 'register') {
      targetElement = regMsgTarget;
      targetModal = document.getElementById('register-modal');
    }
    
    if (targetElement && targetModal) {
      targetElement.textContent = msg;
      targetElement.className = 'auth-message ' + type;
      targetModal.classList.add('active');
    }
  }
  
  // --- Review Form Script ---
  const reviewForm = document.getElementById('review-form');
  if (reviewForm) {
    reviewForm.addEventListener('submit', function(e) {
      const rating = document.querySelector('input[name="rating"]:checked');
      const comment = document.getElementById('review-comment').value.trim();
      
      if (!rating) {
        e.preventDefault();
        alert('Vui lòng chọn đánh giá sao');
        return false;
      }
      
      if (!comment) {
        e.preventDefault();
        alert('Vui lòng nhập nhận xét');
        return false;
      }
    });
  }
  
  // --- Review Display Script ---
  const reviewCards = document.querySelectorAll('.review-card');
  reviewCards.forEach(card => {
    const rating = card.getAttribute('data-rating');
    const stars = card.querySelectorAll('.star');
    if (stars.length > 0 && rating) {
      const ratingNum = parseInt(rating);
      stars.forEach((star, index) => {
        if (index < ratingNum) {
          star.classList.add('active');
        }
      });
    }
  });
  
  // --- Pagination Script ---
  const reviewPagination = document.querySelectorAll('.review-pagination a');
  reviewPagination.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const page = this.getAttribute('data-page');
      if (page) {
        window.location.href = 'review.html?page=' + page;
      }
    });
  });
  
  // --- Filter Script ---
  const filterButtons = document.querySelectorAll('.review-filter-btn');
  filterButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      const filter = this.getAttribute('data-filter');
      // Reload page with filter
      window.location.href = 'review.html?filter=' + filter;
    });
  });
  
  // --- Load More Script ---
  const loadMoreBtn = document.getElementById('load-more-reviews');
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function() {
      const currentPage = parseInt(this.getAttribute('data-page') || '1');
      const nextPage = currentPage + 1;
      // Load more reviews via AJAX or reload
      window.location.href = 'review.html?page=' + nextPage;
    });
  }

  // Page Navigation đã được xử lý bởi navigation.js

  // Header Fixed on Scroll - Đảm bảo header luôn cố định - CHẠY NGAY LẬP TỨC
  const header = document.querySelector('.header');
  if (header) {
    // Function để force header fixed và visible
    function forceHeaderFixed() {
      header.style.setProperty('position', 'fixed', 'important');
      header.style.setProperty('top', '0', 'important');
      header.style.setProperty('left', '0', 'important');
      header.style.setProperty('width', '100%', 'important');
      header.style.setProperty('z-index', '1000', 'important');
      header.style.setProperty('transform', 'translateX(0) translateY(0)', 'important');
      header.style.setProperty('display', 'block', 'important');
      header.style.setProperty('visibility', 'visible', 'important');
      header.style.setProperty('opacity', '1', 'important');
      header.style.setProperty('pointer-events', 'auto', 'important');
      header.style.setProperty('margin-top', '0', 'important');
      header.style.setProperty('margin-bottom', '0', 'important');
    }
    
    // Đảm bảo header fixed ngay từ đầu - CHẠY NHIỀU LẦN
    forceHeaderFixed();
    setTimeout(forceHeaderFixed, 0);
    setTimeout(forceHeaderFixed, 50);
    setTimeout(forceHeaderFixed, 100);
    
    // Sử dụng requestAnimationFrame để tối ưu performance
    let ticking = false;
    
    function updateHeaderOnScroll() {
      const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
      
      // Luôn force header fixed
      forceHeaderFixed();
      
      // Thêm shadow mạnh hơn khi scroll xuống
      if (currentScroll > 10) {
        header.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.5)';
        header.style.background = 'linear-gradient(180deg, rgba(11, 15, 26, .99), rgba(11, 15, 26, .95))';
      } else {
        header.style.boxShadow = '0 4px 24px rgba(0, 0, 0, 0.3)';
        header.style.background = 'linear-gradient(180deg, rgba(11, 15, 26, .98), rgba(11, 15, 26, .85))';
      }
      
      ticking = false;
    }
    
    window.addEventListener('scroll', function() {
      if (!ticking) {
        window.requestAnimationFrame(updateHeaderOnScroll);
        ticking = true;
      }
    }, { passive: true });
    
    // Đảm bảo header fixed khi resize
    window.addEventListener('resize', forceHeaderFixed);
    
    // Đảm bảo header fixed sau khi page load
    window.addEventListener('load', forceHeaderFixed);
    
    // Đảm bảo header fixed khi hash change (smooth scroll đến section)
    window.addEventListener('hashchange', function() {
      setTimeout(forceHeaderFixed, 100);
      setTimeout(forceHeaderFixed, 300);
    });
    
    // Kiểm tra và force lại định kỳ để đảm bảo không bị override - KIỂM TRA THƯỜNG XUYÊN HƠN
    setInterval(function() {
      if (!document.body.classList.contains('menu-is-active')) {
        const computedStyle = window.getComputedStyle(header);
        // Luôn force lại để đảm bảo
        forceHeaderFixed();
        
        // Kiểm tra và sửa nếu cần
        if (computedStyle.position !== 'fixed' || 
            computedStyle.top !== '0px' || 
            computedStyle.visibility === 'hidden' || 
            computedStyle.display === 'none' ||
            computedStyle.opacity === '0') {
          forceHeaderFixed();
        }
      }
    }, 100); // Kiểm tra mỗi 100ms thay vì 300ms
  }
});

