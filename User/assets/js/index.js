// JavaScript cho index.html

document.addEventListener('DOMContentLoaded', function() {
  // Menu Mobile Script - Giống mobile.txt
  const menuToggle = document.querySelector('.menu-toggle');
  const menuPanel = document.querySelector('#main-menu');
  const overlay = document.querySelector('#menu-overlay');
  const closeBtn = document.querySelector('.nav-close');
  const pageWrapper = document.querySelector('#page-wrapper');
  const menuLinks = menuPanel ? menuPanel.querySelectorAll('a:not(.user-menu-wrapper a), span:not(.menu-header span)') : []; 
  const userMenuLinks = menuPanel ? menuPanel.querySelectorAll('.user-menu-wrapper a') : [];
  const body = document.body;
  
  if (menuToggle && menuPanel) {
    function closeMenu() {
      // Khôi phục scroll position
      const scrollY = document.documentElement.style.getPropertyValue('--scroll-y');
      
      // Đảm bảo restore tất cả style đã thay đổi
      body.style.position = '';
      body.style.top = '';
      body.style.width = '';
      body.style.overflow = '';
      document.documentElement.style.overflow = '';
      
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
      
      // Force restore scroll sau một chút để đảm bảo
      setTimeout(function() {
        body.style.position = '';
        body.style.overflow = '';
        document.documentElement.style.overflow = '';
        body.classList.remove('no-scroll', 'menu-is-active');
        document.documentElement.classList.remove('menu-is-active');
      }, 100);
    }
    
    function openMenu() {
      // Lưu scroll position trước khi lock
      const scrollY = window.scrollY;
      document.documentElement.style.setProperty('--scroll-y', `${scrollY}px`);
      
      // QUAN TRỌNG: Reset scroll và đảm bảo menu header ở đầu TRƯỚC KHI set active
      function resetMenuScroll() {
        if (menuPanel) {
          // Reset scroll về đầu
          menuPanel.scrollTop = 0;
          
          // Đảm bảo menu header là phần tử đầu tiên trong DOM
          const menuHeader = menuPanel.querySelector('.menu-header');
          if (menuHeader) {
            // Di chuyển header lên đầu nếu chưa phải phần tử đầu tiên
            if (menuPanel.firstChild !== menuHeader) {
              menuPanel.insertBefore(menuHeader, menuPanel.firstChild);
            }
            
            // Force style inline để đảm bảo header luôn visible và ở đầu
            menuHeader.style.cssText = `
              display: flex !important;
              visibility: visible !important;
              opacity: 1 !important;
              transform: translateY(0) translateX(0) !important;
              position: relative !important;
              top: 0 !important;
              z-index: 100 !important;
              order: -1 !important;
              margin: 0 !important;
              padding: 24px 28px 20px 28px !important;
              width: 100% !important;
              box-sizing: border-box !important;
            `;
          }
          
          // Reset scroll lại để đảm bảo
          menuPanel.scrollTop = 0;
        }
      }
      
      // BƯỚC 1: Reset scroll và đảm bảo header ở đầu TRƯỚC KHI làm bất cứ điều gì
      if (menuPanel) {
        menuPanel.scrollTop = 0;
        menuPanel.scrollLeft = 0;
      }
      resetMenuScroll();
      
      // BƯỚC 2: Set active class
      body.classList.add('menu-is-active', 'no-scroll');
      document.documentElement.classList.add('menu-is-active');
      menuToggle.classList.add('is-active');
      menuPanel.classList.add('is-active');
      if (pageWrapper) pageWrapper.classList.add('is-active');
      menuToggle.setAttribute('aria-expanded', 'true');
      menuToggle.setAttribute('aria-label', 'Đóng menu');
      
      // BƯỚC 3: Chặn scroll bằng cách set position fixed
      body.style.position = 'fixed';
      body.style.top = `-${scrollY}px`;
      body.style.width = '100%';
      
      // BƯỚC 4: Reset scroll lại NHIỀU LẦN sau khi set active để đảm bảo
      // Reset ngay lập tức
      if (menuPanel) {
        menuPanel.scrollTop = 0;
      }
      resetMenuScroll();
      
      // Reset với nhiều delay khác nhau
      setTimeout(() => {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
        }
        resetMenuScroll();
      }, 0);
      
      setTimeout(() => {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
        }
        resetMenuScroll();
      }, 5);
      
      setTimeout(() => {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
        }
        resetMenuScroll();
      }, 10);
      
      setTimeout(() => {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
        }
        resetMenuScroll();
      }, 20);
      
      setTimeout(() => {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
        }
        resetMenuScroll();
      }, 50);
      
      setTimeout(() => {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
        }
        resetMenuScroll();
      }, 100);
      
      setTimeout(() => {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
        }
        resetMenuScroll();
      }, 200);
      
      setTimeout(() => {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
        }
        resetMenuScroll();
      }, 400); // Sau khi transition hoàn thành
      
      // Reset sau khi transition với requestAnimationFrame
      requestAnimationFrame(() => {
        if (menuPanel) {
          menuPanel.scrollTop = 0;
        }
        resetMenuScroll();
        requestAnimationFrame(() => {
          if (menuPanel) {
            menuPanel.scrollTop = 0;
          }
          resetMenuScroll();
        });
      });
      
      // Đảm bảo menu header luôn ở đầu khi menu panel scroll
      // Thêm listener để CHẶN scroll quá đầu (không cho scroll về phía trước header)
      const preventScrollPastHeader = () => {
        if (menuPanel && menuPanel.classList.contains('is-active')) {
          // Nếu scroll quá đầu (scrollTop < 0 hoặc > 0 nhưng header không ở đầu), reset về 0
          if (menuPanel.scrollTop > 0) {
            const menuHeader = menuPanel.querySelector('.menu-header');
            if (menuHeader) {
              const headerOffset = menuHeader.offsetTop;
              // Nếu scroll đã vượt qua header, reset về đầu
              if (menuPanel.scrollTop > headerOffset + 5) {
                menuPanel.scrollTop = 0;
              } else if (menuPanel.scrollTop > 5) {
                // Cho phép scroll một chút nhưng không quá nhiều
                menuPanel.scrollTop = Math.min(menuPanel.scrollTop, 5);
              }
            } else {
              // Nếu không tìm thấy header, reset về 0
              menuPanel.scrollTop = 0;
            }
          }
        }
      };
      
      // Kiểm tra khi menu panel scroll - chặn scroll quá đầu
      // Remove old listener nếu có để tránh duplicate
      const oldScrollHandler = menuPanel._scrollHandler;
      if (oldScrollHandler) {
        menuPanel.removeEventListener('scroll', oldScrollHandler);
      }
      menuPanel._scrollHandler = preventScrollPastHeader;
      menuPanel.addEventListener('scroll', preventScrollPastHeader, { passive: false });
      
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

  // === SCRIPT XỬ LÝ MODAL THANH TOÁN ===
  const paymentModal = document.getElementById('payment-modal');
  if (paymentModal) { 
    const paymentForm = document.getElementById('payment-form');
    const allPackageButtons = document.querySelectorAll('.package-card .btn');
    const paymentPackageName = document.getElementById('payment-package-name');
    const paymentPackagePrice = document.getElementById('payment-package-price');
    const paymentPackageId = document.getElementById('payment-package-id');
    const paymentMethodSelected = document.getElementById('payment-method-selected');
    
    allPackageButtons.forEach(btn => {
      if (btn.getAttribute('data-modal-target') === 'payment-modal') {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const card = this.closest('.package-card');
          const name = card.querySelector('h3').textContent;
          const price = card.querySelector('.money').textContent;
          const packageId = card.id;
          
          paymentPackageName.value = name;
          paymentPackagePrice.value = price;
          paymentPackageId.value = packageId;
          
          if (typeof openModalById === 'function') {
            openModalById('payment-modal');
          } else {
            paymentModal.classList.add('active');
          }
        });
      }
    });
    
    if (paymentForm) {
      paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const method = paymentMethodSelected.value;
        if (!method) {
          alert('Vui lòng chọn phương thức thanh toán');
          return;
        }
        
        // Gửi form đến server
        this.submit();
      });
    }
  }
  
  // === SCRIPT XỬ LÝ SWIPER ===
  const heroSwiper = document.querySelector('.hero-swiper');
  if (heroSwiper) {
    new Swiper('.hero-swiper', {
      slidesPerView: 1,
      spaceBetween: 0,
      loop: true,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
    });
  }
  
  // === SCRIPT XỬ LÝ SCROLL ANIMATION ===
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };
  
  const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, observerOptions);
  
  document.querySelectorAll('.card, .section-title').forEach(el => {
    observer.observe(el);
  });
  
  // === SCRIPT XỬ LÝ SMOOTH SCROLL ===
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      if (href === '#' || href === '#!') return;
      
      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
  
  // Program Filter Functionality
  const filterButtons = document.querySelectorAll('.filter-btn');
  const programCards = document.querySelectorAll('.program-card');
  
  if (filterButtons.length > 0 && programCards.length > 0) {
    filterButtons.forEach(button => {
      button.addEventListener('click', function() {
        // Remove active class from all buttons
        filterButtons.forEach(btn => btn.classList.remove('active'));
        // Add active class to clicked button
        this.classList.add('active');
        
        // Get filter value
        const filterValue = this.getAttribute('data-filter');
        
        // Filter cards
        programCards.forEach(card => {
          if (filterValue === 'all') {
            card.classList.remove('hidden');
            card.style.display = '';
          } else {
            const cardCategory = card.getAttribute('data-category');
            if (cardCategory === filterValue) {
              card.classList.remove('hidden');
              card.style.display = '';
            } else {
              card.classList.add('hidden');
              card.style.display = 'none';
            }
          }
        });
      });
    });
  }

  // === SCRIPT XỬ LÝ TÍNH BMI ===
  const bmiCalcBtn = document.getElementById('bmi-calc-btn');
  const bmiForm = document.getElementById('bmi-form');
  const bmiHeightInput = document.getElementById('bmi-height');
  const bmiWeightInput = document.getElementById('bmi-weight');
  const bmiResult = document.getElementById('bmi-result');

  if (bmiCalcBtn && bmiForm && bmiHeightInput && bmiWeightInput && bmiResult) {
    // Xử lý khi click nút tính toán
    bmiCalcBtn.addEventListener('click', function(e) {
      e.preventDefault();
      calculateBMI();
    });

    // Xử lý khi nhấn Enter trong form
    bmiForm.addEventListener('submit', function(e) {
      e.preventDefault();
      calculateBMI();
    });

    // Hàm tính BMI
    function calculateBMI() {
      const height = parseFloat(bmiHeightInput.value);
      const weight = parseFloat(bmiWeightInput.value);

      // Kiểm tra dữ liệu đầu vào
      if (!height || height <= 0 || height > 3) {
        bmiResult.innerHTML = '<p class="error">Vui lòng nhập chiều cao hợp lệ (0.5 - 3.0 m)</p>';
        bmiResult.style.display = 'block';
        return;
      }

      if (!weight || weight <= 0 || weight > 500) {
        bmiResult.innerHTML = '<p class="error">Vui lòng nhập cân nặng hợp lệ (1 - 500 kg)</p>';
        bmiResult.style.display = 'block';
        return;
      }

      // Tính BMI: BMI = cân nặng (kg) / (chiều cao (m))²
      const bmi = weight / (height * height);
      const bmiRounded = bmi.toFixed(1);

      // Phân loại BMI
      let category = '';
      let categoryClass = '';
      let advice = '';

      if (bmi < 18.5) {
        category = 'Dưới cân';
        categoryClass = 'warning';
        advice = 'Bạn nên tăng cường dinh dưỡng và tập luyện để tăng cân một cách lành mạnh.';
      } else if (bmi >= 18.5 && bmi < 25) {
        category = 'Bình thường';
        categoryClass = 'success';
        advice = 'Chỉ số BMI của bạn ở mức lý tưởng. Hãy duy trì chế độ ăn uống và tập luyện hiện tại.';
      } else if (bmi >= 25 && bmi < 30) {
        category = 'Thừa cân';
        categoryClass = 'warning';
        advice = 'Bạn nên có chế độ ăn uống hợp lý và tập luyện thường xuyên để giảm cân.';
      } else {
        category = 'Béo phì';
        categoryClass = 'error';
        advice = 'Bạn nên tham khảo ý kiến bác sĩ và có kế hoạch giảm cân khoa học.';
      }

      // Hiển thị kết quả
      bmiResult.innerHTML = `
        <div class="bmi-result-content" style="margin-top: 20px; padding: 20px; background: var(--bg-2, rgba(16, 34, 19, 0.6)); border-radius: 12px; border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));">
          <div style="text-align: center; margin-bottom: 15px;">
            <div style="font-size: 48px; font-weight: 700; color: var(--primary, #22c55e); margin-bottom: 8px;">${bmiRounded}</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--text, #fff); margin-bottom: 8px;">${category}</div>
            <div style="font-size: 14px; color: var(--muted, #999);">Chỉ số BMI của bạn</div>
          </div>
          <div style="padding-top: 15px; border-top: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));">
            <p style="font-size: 14px; color: var(--muted, #ccc); line-height: 1.6; text-align: center;">${advice}</p>
          </div>
        </div>
      `;
      bmiResult.style.display = 'block';
    }
  }
});
