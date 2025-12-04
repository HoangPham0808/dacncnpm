/* ========= AUTH UI (NO FIREBASE) =========
 * - Mở/đóng modal theo data-modal-target
 * - Tự mở modal nếu URL có hash #dang-nhap / #dang-ky (từ redirect PHP)
 * - Hiển thị thông báo từ PHP qua ?msg=&type= (success|error|info)
 * - KHÔNG chặn submit -> form POST thẳng tới login.php / register.php
 */

console.log('auth.js loaded'); // Debug

(function () {
  // Elements - sẽ được query khi cần
  let loginModal, registerModal, forgotPasswordModal;
  let loginForm, registerForm, forgotPasswordForm;
  let modalOverlays;

  // Helpers
  function getModalElements() {
    if (!loginModal) loginModal = document.getElementById('login-modal');
    if (!registerModal) registerModal = document.getElementById('register-modal');
    if (!forgotPasswordModal) forgotPasswordModal = document.getElementById('forgot-password-modal');
    if (!modalOverlays || modalOverlays.length === 0) {
      modalOverlays = document.querySelectorAll('.modal-overlay');
    }
    if (!loginForm) loginForm = document.getElementById('login-form');
    if (!registerForm) registerForm = document.getElementById('register-form');
    if (!forgotPasswordForm) forgotPasswordForm = document.getElementById('forgot-password-form');
  }

  function openModalById(id) {
    console.log('openModalById called with id:', id); // Debug
    getModalElements();
    // Đóng tất cả modals trước
    if (modalOverlays && modalOverlays.length > 0) {
      modalOverlays.forEach(function(m) {
        m.classList.remove('active');
        m.classList.add('hidden');
        // Force ẩn bằng inline style
        m.style.setProperty('display', 'none', 'important');
        m.style.setProperty('visibility', 'hidden', 'important');
        m.style.setProperty('opacity', '0', 'important');
        // Clear hash khi đóng modal để tránh modal tự động mở lại
        const currentHash = window.location.hash;
        if (currentHash && (currentHash === '#profile' || currentHash === '#my-packages' || 
            currentHash === '#payment-history' || currentHash === '#payment-management' || 
            currentHash === '#change-password' || currentHash === '#inbox')) {
          history.replaceState(null, '', window.location.pathname + window.location.search);
        }
      });
    }
    // Mở modal mới
    const modal = document.getElementById(id);
    console.log('Modal element found:', modal); // Debug
    if (modal) {
      // BƯỚC 1: Xóa class hidden và tất cả classes có thể conflict
      modal.classList.remove('hidden');
      // Đảm bảo modal không bị ẩn bởi bất kỳ class nào
      modal.className = modal.className.replace(/\bhidden\b/g, '').trim();
      
      // BƯỚC 2: Force hiển thị modal-overlay bằng inline style với !important NGAY LẬP TỨC
      // Sử dụng setProperty từng cái để đảm bảo override Tailwind
      modal.style.removeProperty('display');
      modal.style.removeProperty('visibility');
      modal.style.removeProperty('opacity');
      
      // Set lại với !important
      modal.style.setProperty('display', 'flex', 'important');
      modal.style.setProperty('visibility', 'visible', 'important');
      modal.style.setProperty('opacity', '1', 'important');
      modal.style.setProperty('position', 'fixed', 'important');
      modal.style.setProperty('top', '0', 'important');
      modal.style.setProperty('left', '0', 'important');
      modal.style.setProperty('right', '0', 'important');
      modal.style.setProperty('bottom', '0', 'important');
      modal.style.setProperty('width', '100vw', 'important');
      modal.style.setProperty('height', '100vh', 'important');
      modal.style.setProperty('z-index', '99999', 'important'); // Z-index cực cao
      modal.style.setProperty('align-items', 'center', 'important');
      modal.style.setProperty('justify-content', 'center', 'important');
      modal.style.setProperty('background', 'rgba(0, 0, 0, 0.7)', 'important'); // Background đậm hơn cho modal sau khi đăng nhập
      modal.style.setProperty('backdrop-filter', 'blur(5px)', 'important');
      modal.style.setProperty('-webkit-backdrop-filter', 'blur(5px)', 'important');
      modal.style.setProperty('padding', '20px', 'important');
      modal.style.setProperty('margin', '0', 'important');
      modal.style.setProperty('overflow', 'auto', 'important');
      modal.style.setProperty('box-sizing', 'border-box', 'important');
      modal.style.setProperty('pointer-events', 'auto', 'important');
      
      // BƯỚC 3: Thêm class active - CSS từ style.css sẽ xử lý styling
      modal.classList.add('active');
      
      // Nếu là inbox-modal, gọi loadInboxNotifications() ngay lập tức
      if (id === 'inbox-modal') {
        console.log('📬 Inbox modal opened via openModalById');
        // Đợi một chút để đảm bảo modal đã render xong
        setTimeout(function() {
          if (typeof window.loadInboxNotifications === 'function') {
            console.log('📬 Calling loadInboxNotifications...');
            window.loadInboxNotifications();
          } else {
            console.warn('⚠️ loadInboxNotifications function not found');
            // Retry sau 500ms
            setTimeout(function() {
              if (typeof window.loadInboxNotifications === 'function') {
                console.log('📬 Retrying loadInboxNotifications...');
                window.loadInboxNotifications();
              }
            }, 500);
          }
        }, 200);
      }
      
      // Trigger modals-loader để load dữ liệu cho các modal khác
      // Các modal được quản lý bởi modals-loader.js: profile-modal, my-packages-modal, payment-history-modal, payment-management-modal
      const modalsToLoad = ['profile-modal', 'my-packages-modal', 'payment-history-modal', 'payment-management-modal'];
      if (modalsToLoad.includes(id)) {
        console.log('Triggering modals-loader for:', id);
        // Đợi một chút để đảm bảo modal đã render xong
        setTimeout(function() {
          // Kiểm tra xem modals-loader có function loadModalData không
          if (typeof window.loadModalData === 'function') {
            window.loadModalData(id);
          } else {
            // Nếu không có, thử dispatch custom event để modals-loader lắng nghe
            const event = new CustomEvent('modal-opened', { detail: { modalId: id } });
            document.dispatchEvent(event);
            
            // Hoặc thử gọi trực tiếp nếu có trong window
            if (window.modals && window.modals[id]) {
              window.modals[id]();
            }
          }
        }, 100);
      }
      
      // Force reflow để browser áp dụng styles
      void modal.offsetWidth;
      void modal.offsetHeight;
      
      // Đợi một frame để đảm bảo styles được áp dụng
      requestAnimationFrame(function() {
        // Kiểm tra lại và force nếu cần
        const computedStyle = window.getComputedStyle(modal);
        if (computedStyle.display === 'none') {
          modal.style.setProperty('display', 'flex', 'important');
        }
        if (computedStyle.visibility === 'hidden') {
          modal.style.setProperty('visibility', 'visible', 'important');
        }
      });
      
      // Ngăn scroll body khi modal mở - chỉ khi modal thực sự mở
      if (modal && modal.classList.contains('active')) {
        document.body.style.setProperty('overflow', 'hidden', 'important');
        document.documentElement.style.setProperty('overflow', 'hidden', 'important');
      }
      
      console.log('Modal opened - classes:', modal.className); // Debug
      console.log('Modal display:', window.getComputedStyle(modal).display); // Debug
      console.log('Modal background:', window.getComputedStyle(modal).background); // Debug
      console.log('Modal z-index:', window.getComputedStyle(modal).zIndex); // Debug
      
      // Clear error messages khi mở modal mới
      const errorMessages = modal.querySelectorAll('.error-text');
      errorMessages.forEach(function(err) { err.remove(); });
      const errorInputs = modal.querySelectorAll('input.error');
      errorInputs.forEach(function(inp) { inp.classList.remove('error'); });
    } else {
      console.error('Modal not found with id:', id);
    }
  }
  
  // Expose openModalById ra global scope để các file khác có thể sử dụng
  window.openModalById = openModalById;
  
  function closeModal(el) {
    if (el) {
      el.classList.remove('active');
      el.classList.add('hidden');
      // Force ẩn bằng inline style với !important
      el.style.setProperty('display', 'none', 'important');
      el.style.setProperty('visibility', 'hidden', 'important');
      el.style.setProperty('opacity', '0', 'important');
      
      // Khôi phục scroll body khi đóng modal - đảm bảo restore đầy đủ
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('position');
      document.body.style.removeProperty('top');
      document.body.style.removeProperty('width');
      document.documentElement.style.removeProperty('overflow');
      document.body.classList.remove('no-scroll');
      document.documentElement.classList.remove('menu-is-active');
      
      // Clear hash khi đóng modal để tránh modal tự động mở lại
      const currentHash = window.location.hash;
      if (currentHash && (currentHash === '#profile' || currentHash === '#my-packages' || 
          currentHash === '#payment-history' || currentHash === '#payment-management' || 
          currentHash === '#change-password' || currentHash === '#inbox')) {
        // Chỉ clear hash nếu là hash của account modal hoặc inbox
        history.replaceState(null, '', window.location.pathname + window.location.search);
      }
    }
  }

  // Sử dụng event delegation để bắt tất cả clicks vào buttons có data-modal-target
  // Cách này đảm bảo hoạt động ngay cả khi buttons được thêm vào DOM sau
  // CHỈ mở modal đăng nhập/đăng ký khi ở trang chủ
  document.addEventListener('click', function(e) {
    // Bỏ qua nếu click vào user menu button (để user-menu.js xử lý)
    const userMenuButton = e.target.closest('#user-menu-button');
    if (userMenuButton) {
      return;
    }
    
    // Tìm element có data-modal-target (có thể là button hoặc element cha)
    // Đặc biệt chú ý đến các link trong user dropdown menu
    const target = e.target.closest('[data-modal-target]');
    
    if (target) {
      const modalId = target.getAttribute('data-modal-target');
      
      // Nếu click vào link trong user dropdown, log để debug
      const clickedInDropdown = target.closest('#user-dropdown-menu');
      if (clickedInDropdown) {
        console.log('Link in user dropdown clicked - auth.js handler:', {
          target: target,
          modalId: modalId,
          href: target.getAttribute('href')
        });
      }
      
      // Kiểm tra xem có phải modal đăng nhập/đăng ký không
      const isAuthModal = modalId === 'login-modal' || modalId === 'register-modal' || modalId === 'forgot-password-modal' || modalId === 'reset-password-modal';
      
      // Kiểm tra xem có phải trang chủ không
      const isHomePage = window.location.pathname === '/' || 
                        window.location.pathname.includes('index.html') || 
                        window.location.pathname.endsWith('/');
      
      // Nếu là modal đăng nhập/đăng ký và KHÔNG ở trang chủ
      if (isAuthModal && !isHomePage) {
        // Kiểm tra xem modal có tồn tại trên trang hiện tại không
        const modalElement = document.getElementById(modalId);
        
        if (modalElement) {
          // Nếu modal đã có trên trang, mở modal ngay tại đây (không redirect)
          console.log('Modal exists on current page, opening directly:', modalId);
          e.preventDefault();
          e.stopPropagation();
          openModalById(modalId);
          return false;
        } else {
          // Nếu modal không có trên trang, redirect đến trang chủ
          e.preventDefault();
          e.stopPropagation();
          const hash = modalId === 'login-modal' ? '#dang-nhap' : 
                      modalId === 'register-modal' ? '#dang-ky' : 
                      modalId === 'forgot-password-modal' ? '#quen-mat-khau' : '';
          
          // Xác định đường dẫn đúng đến index.html
          // Sử dụng cách đơn giản và chắc chắn nhất
          const pathname = window.location.pathname;
          
          // Tìm base path từ pathname
          // Ví dụ: /doanchuyennganh/user/goitap/packages.html -> /doanchuyennganh/
          let basePath = '/';
          
          // Tách pathname thành các phần
          const pathParts = pathname.split('/').filter(p => p && p.trim() !== '');
          
          if (pathParts.length > 0) {
            // Lấy phần đầu tiên làm base path
            basePath = '/' + pathParts[0] + '/';
          }
          
          // Tạo origin một cách chắc chắn - luôn dùng protocol + hostname + port
          const protocol = window.location.protocol || 'http:';
          const hostname = window.location.hostname || 'localhost';
          const port = window.location.port ? ':' + window.location.port : '';
          const origin = protocol + '//' + hostname + port;
          
          // Tạo URL đầy đủ
          const indexPath = basePath + 'index.html';
          let finalUrl = origin + indexPath + hash;
          
          // Đảm bảo không có double slash (trừ sau http:// hoặc https://)
          finalUrl = finalUrl.replace(/([^:]\/)\/+/g, '$1');
          
          console.log('Modal not found on current page, redirecting to:', finalUrl);
          
          // Sử dụng window.location.replace để tránh history entry và đảm bảo redirect đúng
          window.location.replace(finalUrl);
          return false;
        }
      }
      
      console.log('Modal button clicked:', target, 'target modal:', modalId); // Debug
      
      // Nếu là payment-modal, lưu thông tin gói từ button trước khi mở modal
      if (modalId === 'payment-modal') {
        const card = target.closest('.package-card');
        if (card) {
          const packageId = target.getAttribute('data-package-id') || '';
          const packageName = target.getAttribute('data-package-name') || '';
          const packagePriceRaw = target.getAttribute('data-package-price') || '';
          
          if (packageId && packageName && packagePriceRaw) {
            // Lưu thông tin vào biến global
            window.__selectedPackageData = {
              id: packageId,
              name: packageName,
              price: packagePriceRaw,
              card: card,
              button: target
            };
            
            // Lưu reference đến button
            window.__lastClickedPaymentButton = target;
            
            // Lưu vào localStorage
            try {
              localStorage.removeItem('__selectedPackageData');
              localStorage.setItem('__selectedPackageData', JSON.stringify({
                id: packageId,
                name: packageName,
                price: packagePriceRaw
              }));
            } catch (err) {
              console.warn('Failed to save to localStorage:', err);
            }
            
            console.log('Package data saved in auth.js:', { id: packageId, name: packageName, price: packagePriceRaw });
          }
        }
      }
      
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      
      if (modalId) {
        console.log('Opening modal:', modalId); // Debug
        
        // Đảm bảo modal được mở đúng cách
        const modal = document.getElementById(modalId);
        if (modal) {
          console.log('Modal element found, opening...');
          openModalById(modalId);
        } else {
          console.error('Modal not found:', modalId);
        }
      }
      return false;
    }
  }, true); // Sử dụng capture phase để bắt sớm hơn

  // Close by [x] using event delegation
  document.addEventListener('click', function(e) {
    const closeBtn = e.target.closest('.modal-close-btn');
    if (closeBtn) {
      e.preventDefault();
      e.stopPropagation();
      const modal = closeBtn.closest('.modal-overlay');
      closeModal(modal);
      // Clear hash ngay lập tức
      const currentHash = window.location.hash;
      if (currentHash && (currentHash === '#profile' || currentHash === '#my-packages' || 
          currentHash === '#payment-history' || currentHash === '#payment-management' || 
          currentHash === '#change-password' || currentHash === '#inbox')) {
        history.replaceState(null, '', window.location.pathname + window.location.search);
      }
    }
  });

  // Close when click overlay using event delegation
  document.addEventListener('click', function(e) {
    const overlay = e.target.closest('.modal-overlay');
    if (overlay && e.target === overlay) {
      closeModal(overlay);
      // Clear hash ngay lập tức
      const currentHash = window.location.hash;
      if (currentHash && (currentHash === '#profile' || currentHash === '#my-packages' || 
          currentHash === '#payment-history' || currentHash === '#payment-management' || 
          currentHash === '#change-password' || currentHash === '#inbox')) {
        history.replaceState(null, '', window.location.pathname + window.location.search);
      }
    }
  });

  // Open modal by hash (for PHP redirects)
  // CHẠY NGAY LẬP TỨC để tránh conflict với các script khác
  // CHỈ hoạt động ở trang chủ (index.html) khi chưa đăng nhập
  function handleHashOpen() {
    // Kiểm tra xem có phải trang chủ không (có modal đăng nhập/đăng ký)
    const isHomePage = window.location.pathname === '/' || 
                      window.location.pathname.includes('index.html') || 
                      window.location.pathname.endsWith('/');
    
    // Chỉ xử lý hash nếu ở trang chủ
    if (!isHomePage) return;
    
    const h = (window.location.hash || '').replace('#','');
    
    // QUAN TRỌNG: Chỉ xử lý hash đăng nhập/đăng ký/quên mật khẩu
    // KHÔNG xử lý hash của account modal (#profile, #my-packages, etc.)
    if (h === 'dang-nhap') {
      openModalById('login-modal');
      // Đánh dấu để các script khác biết đã xử lý hash này
      window.__authHashHandled = true;
    } else if (h === 'dang-ky') {
      openModalById('register-modal');
      window.__authHashHandled = true;
    } else if (h === 'quen-mat-khau') {
      openModalById('forgot-password-modal');
      window.__authHashHandled = true;
    } else if (h === 'profile' || h === 'my-packages' || h === 'payment-history' || 
               h === 'payment-management' || h === 'change-password' || h === 'inbox') {
      // Clear hash của account modal nếu có trong URL (không tự động mở)
      history.replaceState(null, '', window.location.pathname + window.location.search);
    }
  }
  
  // Mở modal tự động khi có hash trong URL (từ redirect)
  // Chỉ mở khi ở trang chủ và chưa đăng nhập
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', handleHashOpen);
  } else {
    handleHashOpen();
  }
  
  // Xử lý hashchange event - mở modal khi hash thay đổi
  window.addEventListener('hashchange', function(e) {
    const h = (window.location.hash || '').replace('#','');
    
    // Nếu là hash của account modal, clear nó
    if (h === 'profile' || h === 'my-packages' || h === 'payment-history' || 
        h === 'payment-management' || h === 'change-password' || h === 'inbox') {
      // Clear hash ngay lập tức để tránh modal tự mở
      history.replaceState(null, '', window.location.pathname + window.location.search);
    } else if (h === 'dang-nhap' || h === 'dang-ky' || h === 'quen-mat-khau') {
      // Nếu là hash đăng nhập/đăng ký, mở modal
      handleHashOpen();
    }
  });
  
  // Hiển thị thông báo từ URL params (từ PHP redirect)
  function showMessageFromURL() {
    const params = new URLSearchParams(window.location.search);
    const msg = params.get('msg');
    const type = params.get('type') || 'error';
    const hash = (window.location.hash || '').replace('#', '');
    
    if (msg) {
      let targetElement = null;
      let targetModal = null;
      
      // Xác định modal và element cần hiển thị thông báo
      if (hash === 'dang-nhap') {
        targetElement = document.getElementById('login-message');
        targetModal = document.getElementById('login-modal');
      } else if (hash === 'dang-ky') {
        targetElement = document.getElementById('register-message');
        targetModal = document.getElementById('register-modal');
      } else if (hash === 'quen-mat-khau') {
        targetElement = document.getElementById('forgot-password-message');
        targetModal = document.getElementById('forgot-password-modal');
      }
      
      // Hiển thị thông báo
      if (targetElement) {
        targetElement.textContent = decodeURIComponent(msg);
        targetElement.className = 'auth-message message ' + type;
        targetElement.style.display = 'block';
        
        // TẮT TỰ ĐỘNG MỞ MODAL - chỉ hiển thị thông báo, không mở modal
        // if (targetModal && !targetModal.classList.contains('active')) {
        //   openModalById(targetModal.id);
        // }
      }
      
      // Xóa query params khỏi URL nhưng giữ hash
      const cleanUrl = window.location.origin + window.location.pathname + window.location.hash;
      window.history.replaceState({}, document.title, cleanUrl);
    }
  }
  
  // Chạy ngay nếu DOM đã sẵn sàng
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', showMessageFromURL);
  } else {
    showMessageFromURL();
  }

  // Form đổi mật khẩu sẽ submit bình thường (POST) và PHP sẽ redirect với thông báo
  // Không cần intercept AJAX vì đã có xử lý redirect và notification modal ở trên
  // Đảm bảo form đổi mật khẩu không bị chặn bởi bất kỳ event listener nào
  // Form sẽ submit POST trực tiếp đến update_password.php

  // === VALIDATION FORMS ===
  
  // Helper: Show error message
  function showError(input, message) {
    const formGroup = input.closest('.form-group');
    if (!formGroup) return;
    
    // Remove existing error
    const existingError = formGroup.querySelector('.error-text');
    if (existingError) existingError.remove();
    
    // Add error class
    input.classList.add('error');
    
    // Create error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-text';
    errorDiv.textContent = message;
    formGroup.appendChild(errorDiv);
  }
  
  // Helper: Clear error
  function clearError(input) {
    const formGroup = input.closest('.form-group');
    if (!formGroup) return;
    
    input.classList.remove('error');
    const existingError = formGroup.querySelector('.error-text');
    if (existingError) existingError.remove();
  }
  
  // Helper: Validate email
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }
  
  // Clear error on input using event delegation
  document.addEventListener('input', function(e) {
    if (e.target.tagName === 'INPUT') {
      clearError(e.target);
    }
  });
  
  // Validate Login Form using event delegation
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.id === 'login-form') {
      let isValid = true;
      let firstErrorInput = null;
      
      const emailInput = form.querySelector('[name="email"]');
      const passwordInput = form.querySelector('[name="password"]');
      
      // Clear previous errors
      if (emailInput) clearError(emailInput);
      if (passwordInput) clearError(passwordInput);
      
      // Validate email/username
      if (!emailInput || !emailInput.value.trim()) {
        if (emailInput) showError(emailInput, 'Vui lòng nhập email hoặc tên đăng nhập');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = emailInput;
      }
      
      // Validate password
      if (!passwordInput || !passwordInput.value) {
        if (passwordInput) showError(passwordInput, 'Vui lòng nhập mật khẩu');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = passwordInput;
      }
      
      // Nếu có lỗi, chặn submit
      if (!isValid) {
        e.preventDefault();
        if (firstErrorInput) {
          firstErrorInput.focus();
        }
        return false;
      }
      
      // Nếu hợp lệ, cho phép submit tự nhiên
      return true;
    }
  });
  
  // Validate Register Form using event delegation
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.id === 'register-form') {
      let isValid = true;
      let firstErrorInput = null;
      
      const fullNameInput = form.querySelector('[name="full_name"]');
      const emailInput = form.querySelector('[name="email"]');
      const passwordInput = form.querySelector('[name="password"]');
      const confirmPasswordInput = form.querySelector('[name="password_confirm"]');
      
      // Clear previous errors
      if (fullNameInput) clearError(fullNameInput);
      if (emailInput) clearError(emailInput);
      if (passwordInput) clearError(passwordInput);
      if (confirmPasswordInput) clearError(confirmPasswordInput);
      
      // Validate full name
      if (fullNameInput && !fullNameInput.value.trim()) {
        showError(fullNameInput, 'Vui lòng nhập họ và tên');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = fullNameInput;
      }
      
      // Validate email
      if (!emailInput || !emailInput.value.trim()) {
        if (emailInput) showError(emailInput, 'Vui lòng nhập email');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = emailInput;
      } else if (!isValidEmail(emailInput.value)) {
        showError(emailInput, 'Email không hợp lệ');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = emailInput;
      }
      
      // Validate password
      if (!passwordInput || !passwordInput.value) {
        if (passwordInput) showError(passwordInput, 'Vui lòng nhập mật khẩu');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = passwordInput;
      } else if (passwordInput.value.length < 6) {
        showError(passwordInput, 'Mật khẩu phải có ít nhất 6 ký tự');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = passwordInput;
      }
      
      // Validate confirm password
      if (confirmPasswordInput) {
        if (!confirmPasswordInput.value) {
          showError(confirmPasswordInput, 'Vui lòng xác nhận mật khẩu');
          isValid = false;
          if (!firstErrorInput) firstErrorInput = confirmPasswordInput;
        } else if (confirmPasswordInput.value !== passwordInput.value) {
          showError(confirmPasswordInput, 'Mật khẩu xác nhận không khớp');
          isValid = false;
          if (!firstErrorInput) firstErrorInput = confirmPasswordInput;
        }
      }
      
      // Nếu có lỗi, chặn submit
      if (!isValid) {
        e.preventDefault();
        if (firstErrorInput) {
          firstErrorInput.focus();
        }
        return false;
      }
      
      // Nếu hợp lệ, cho phép submit tự nhiên
      return true;
    }
  });
  
  // Validate Forgot Password Form - Step 1: Verify Identity
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.id === 'forgot-password-form') {
      e.preventDefault(); // Luôn chặn submit mặc định
      
      let isValid = true;
      let firstErrorInput = null;
      
      const phoneInput = form.querySelector('[name="sdt"]');
      const fullnameInput = form.querySelector('[name="ho_ten"]');
      const birthdayInput = form.querySelector('[name="ngay_sinh"]');
      const cccdInput = form.querySelector('[name="cccd"]');
      const submitBtn = form.querySelector('#verify-identity-btn');
      const messageEl = document.getElementById('forgot-password-message');
      
      // Clear previous errors
      if (phoneInput) clearError(phoneInput);
      if (fullnameInput) clearError(fullnameInput);
      if (birthdayInput) clearError(birthdayInput);
      if (cccdInput) clearError(cccdInput);
      if (messageEl) {
        messageEl.textContent = '';
        messageEl.className = 'auth-message message';
      }
      
      // Validate phone
      if (!phoneInput || !phoneInput.value.trim()) {
        if (phoneInput) showError(phoneInput, 'Vui lòng nhập số điện thoại');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = phoneInput;
      } else if (!/^[0-9]{10}$/.test(phoneInput.value)) {
        showError(phoneInput, 'Số điện thoại phải có 10 chữ số');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = phoneInput;
      }
      
      // Validate full name
      if (!fullnameInput || !fullnameInput.value.trim()) {
        if (fullnameInput) showError(fullnameInput, 'Vui lòng nhập họ và tên');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = fullnameInput;
      }
      
      // Validate birthday
      if (!birthdayInput || !birthdayInput.value) {
        if (birthdayInput) showError(birthdayInput, 'Vui lòng nhập ngày sinh');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = birthdayInput;
      }
      
      // Validate CCCD
      if (!cccdInput || !cccdInput.value.trim()) {
        if (cccdInput) showError(cccdInput, 'Vui lòng nhập căn cước công dân');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = cccdInput;
      } else if (!/^[0-9]{9,12}$/.test(cccdInput.value)) {
        showError(cccdInput, 'CCCD phải có từ 9-12 chữ số');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = cccdInput;
      }
      
      // Nếu có lỗi, focus vào trường đầu tiên
      if (!isValid) {
        if (firstErrorInput) firstErrorInput.focus();
        return false;
      }
      
      // Nếu hợp lệ, gửi AJAX request
      if (submitBtn) {
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xác thực...';
        
        // Xác định đúng path tới API dựa vào vị trí hiện tại
        const currentPath = window.location.pathname;
        let apiPath = 'user/getset/verify_identity.php';
        
        // Nếu đang ở trong thư mục user hoặc subfolder
        if (currentPath.includes('/user/')) {
          // Tính số level cần back up
          const pathParts = currentPath.split('/').filter(p => p);
          const userIndex = pathParts.indexOf('user');
          if (userIndex >= 0) {
            const levelsDeep = pathParts.length - userIndex - 1;
            if (levelsDeep > 0) {
              apiPath = '../'.repeat(levelsDeep) + 'getset/verify_identity.php';
            } else {
              apiPath = 'getset/verify_identity.php';
            }
          }
        }
        
        console.log('Calling API at:', apiPath); // Debug log
        
        // Gọi API verify identity
        fetch(apiPath, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            sdt: phoneInput.value,
            ho_ten: fullnameInput.value,
            ngay_sinh: birthdayInput.value,
            cccd: cccdInput.value
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            console.log('✓ Verification successful for user:', data.username);
            
            // Reset form trước khi chuyển
            form.reset();
            
            // Đóng modal xác thực
            const forgotModal = document.getElementById('forgot-password-modal');
            if (forgotModal) {
              if (typeof closeModal === 'function') {
                closeModal(forgotModal);
              } else {
                forgotModal.classList.remove('active');
                forgotModal.classList.add('hidden');
                forgotModal.style.display = 'none';
              }
            }
            
            // Mở modal đổi mật khẩu NGAY LẬP TỨC
            if (typeof openModalById === 'function') {
              openModalById('reset-password-modal');
            } else {
              const resetModal = document.getElementById('reset-password-modal');
              if (resetModal) {
                resetModal.classList.remove('hidden');
                resetModal.classList.add('active');
                resetModal.style.display = 'flex';
              }
            }
            
          } else {
            // Hiển thị lỗi
            if (messageEl) {
              messageEl.textContent = data.message || 'Thông tin không chính xác. Vui lòng kiểm tra lại.';
              messageEl.className = 'auth-message message error';
              messageEl.style.display = 'block';
            }
          }
        })
        .catch(error => {
          console.error('Error:', error);
          if (messageEl) {
            messageEl.textContent = 'Đã xảy ra lỗi. Vui lòng thử lại sau.';
            messageEl.className = 'auth-message message error';
            messageEl.style.display = 'block';
          }
        })
        .finally(() => {
          // Restore button
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        });
      }
      
      return false;
    }
  });
  
  // Validate Reset Password Form - Step 2: New Password
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.id === 'reset-password-form') {
      e.preventDefault(); // Luôn chặn submit mặc định
      e.stopPropagation(); // Chặn event bubbling
      e.stopImmediatePropagation(); // Chặn tất cả event listeners khác
      
      // Tắt autocomplete để tránh browser popup
      if (form) {
        form.setAttribute('autocomplete', 'off');
        const inputs = form.querySelectorAll('input[type="password"]');
        inputs.forEach(input => {
          input.setAttribute('autocomplete', 'new-password');
          input.setAttribute('data-form-type', 'other'); // Hint cho browser
        });
      }
      
      let isValid = true;
      let firstErrorInput = null;
      
      const tokenInput = form.querySelector('[name="token"]');
      const passwordInput = form.querySelector('[name="password"]');
      const confirmPasswordInput = form.querySelector('[name="confirm_password"]');
      const submitBtn = form.querySelector('#reset-password-btn');
      const messageEl = document.getElementById('reset-password-message');
      
      // Clear previous errors
      if (passwordInput) clearError(passwordInput);
      if (confirmPasswordInput) clearError(confirmPasswordInput);
      if (messageEl) {
        messageEl.textContent = '';
        messageEl.className = 'auth-message message';
      }
      
      
      // Validate password
      if (!passwordInput || !passwordInput.value) {
        if (passwordInput) showError(passwordInput, 'Vui lòng nhập mật khẩu mới');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = passwordInput;
      } else if (passwordInput.value.length < 6) {
        showError(passwordInput, 'Mật khẩu phải có ít nhất 6 ký tự');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = passwordInput;
      }
      
      // Validate confirm password
      if (!confirmPasswordInput || !confirmPasswordInput.value) {
        if (confirmPasswordInput) showError(confirmPasswordInput, 'Vui lòng xác nhận mật khẩu');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = confirmPasswordInput;
      } else if (confirmPasswordInput.value !== passwordInput.value) {
        showError(confirmPasswordInput, 'Mật khẩu xác nhận không khớp');
        isValid = false;
        if (!firstErrorInput) firstErrorInput = confirmPasswordInput;
      }
      
      // Nếu có lỗi, focus vào trường đầu tiên
      if (!isValid) {
        if (firstErrorInput) firstErrorInput.focus();
        return false;
      }
      
      // Nếu hợp lệ, gửi AJAX request
      if (submitBtn) {
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đổi mật khẩu...';
        
        // Xác định đúng path tới API dựa vào vị trí hiện tại
        const currentPath = window.location.pathname;
        let apiPath = 'user/getset/reset_password_direct.php';
        
        // Nếu đang ở trong thư mục user hoặc subfolder
        if (currentPath.includes('/user/')) {
          // Tính số level cần back up
          const pathParts = currentPath.split('/').filter(p => p);
          const userIndex = pathParts.indexOf('user');
          if (userIndex >= 0) {
            const levelsDeep = pathParts.length - userIndex - 1;
            if (levelsDeep > 0) {
              apiPath = '../'.repeat(levelsDeep) + 'getset/reset_password_direct.php';
            } else {
              apiPath = 'getset/reset_password_direct.php';
            }
          }
        }
        
        console.log('Calling reset API at:', apiPath); // Debug log
        
        // Gọi API reset password
        fetch(apiPath, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            password: passwordInput.value,
            confirm_password: confirmPasswordInput.value
          })
        })
        .then(response => {
          console.log('Response status:', response.status);
          console.log('Response headers:', response.headers.get('content-type'));
          
          // Kiểm tra content-type
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
              console.error('Non-JSON response:', text);
              throw new Error('Server returned non-JSON response. Check PHP error log.');
            });
          }
          
          return response.json();
        })
        .then(data => {
          console.log('Response data:', data);
          if (data.success) {
            console.log('✓ Password changed successfully');
            
            // Clear inputs NGAY LẬP TỨC để tránh browser popup
            if (passwordInput) passwordInput.value = '';
            if (confirmPasswordInput) confirmPasswordInput.value = '';
            
            // Reset form
            form.reset();
            
            // Hiển thị thông báo thành công
            if (messageEl) {
              messageEl.textContent = data.message || 'Đổi mật khẩu thành công! Đang chuyển đến đăng nhập...';
              messageEl.className = 'auth-message message success';
              messageEl.style.display = 'block';
            }
            
            // Đóng modal và chuyển sang login NGAY (chỉ đợi 300ms để user kịp thấy thông báo)
            setTimeout(() => {
              // Đóng modal reset password
              const resetModal = document.getElementById('reset-password-modal');
              if (resetModal) {
                if (typeof closeModal === 'function') {
                  closeModal(resetModal);
                } else {
                  resetModal.classList.remove('active');
                  resetModal.classList.add('hidden');
                  resetModal.style.setProperty('display', 'none', 'important');
                  // Force remove từ DOM để browser không thấy form
                  resetModal.style.setProperty('visibility', 'hidden', 'important');
                  resetModal.style.setProperty('opacity', '0', 'important');
                }
              }
              
              // Mở modal login ngay lập tức
              if (typeof openModalById === 'function') {
                openModalById('login-modal');
              } else {
                const loginModal = document.getElementById('login-modal');
                if (loginModal) {
                  loginModal.classList.remove('hidden');
                  loginModal.classList.add('active');
                  loginModal.style.setProperty('display', 'flex', 'important');
                }
              }
              
              // Thông báo thành công trên modal login
              setTimeout(() => {
                const loginMessage = document.getElementById('login-message');
                if (loginMessage) {
                  loginMessage.textContent = '✓ Đổi mật khẩu thành công! Vui lòng đăng nhập với mật khẩu mới.';
                  loginMessage.className = 'auth-message message success';
                  loginMessage.style.display = 'block';
                  
                  // Tự động ẩn sau 5 giây
                  setTimeout(() => {
                    loginMessage.style.display = 'none';
                  }, 5000);
                }
              }, 100);
            }, 300);
          } else {
            // Hiển thị lỗi
            if (messageEl) {
              messageEl.textContent = data.message || 'Không thể đổi mật khẩu. Vui lòng thử lại.';
              messageEl.className = 'auth-message message error';
              messageEl.style.display = 'block';
            }
            
            // Nếu phiên hết hạn, quay lại bước 1
            if (data.message && (data.message.includes('hết hạn') || data.message.includes('expired') || data.message.includes('Phiên'))) {
              console.log('✗ Session expired');
              
              setTimeout(() => {
                const resetModal = document.getElementById('reset-password-modal');
                if (resetModal) {
                  if (typeof closeModal === 'function') {
                    closeModal(resetModal);
                  } else {
                    resetModal.classList.remove('active');
                    resetModal.classList.add('hidden');
                    resetModal.style.display = 'none';
                  }
                }
                if (typeof openModalById === 'function') {
                  openModalById('forgot-password-modal');
                } else {
                  const forgotModal = document.getElementById('forgot-password-modal');
                  if (forgotModal) {
                    forgotModal.classList.remove('hidden');
                    forgotModal.classList.add('active');
                    forgotModal.style.display = 'flex';
                  }
                }
              }, 1500);
            }
          }
        })
        .catch(error => {
          console.error('Reset password error:', error);
          console.error('Error message:', error.message);
          console.error('Error stack:', error.stack);
          
          if (messageEl) {
            let errorMsg = 'Đã xảy ra lỗi. Vui lòng thử lại sau.';
            
            if (error.message && error.message.includes('non-JSON')) {
              errorMsg = 'Lỗi server. Vui lòng kiểm tra PHP error log hoặc liên hệ admin.';
            } else if (error.message) {
              errorMsg = 'Lỗi: ' + error.message;
            }
            
            messageEl.textContent = errorMsg;
            messageEl.className = 'auth-message message error';
            messageEl.style.display = 'block';
          }
        })
        .finally(() => {
          // Restore button
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        });
      }
      
      return false;
    }
  });
  
  // Khi mở modal reset password, tự động load token từ storage
  document.addEventListener('click', function(e) {
    const modalTrigger = e.target.closest('[data-modal-target="reset-password-modal"]');
  });

})();


/* ===================================== */
/* === SCRIPT: XỬ LÝ USER DROPDOWN MENU === */
/* ===================================== */
/* 
 * NOTE: Code xử lý dropdown menu chính đã được chuyển sang user-menu.js
 * Chỉ giữ lại phần đóng dropdown khi click vào link để tránh xung đột
 */
(function() {
    // Đợi DOM sẵn sàng
    function initDropdownLinks() {
        const userDropdownMenu = document.getElementById('user-dropdown-menu');
        
        if (userDropdownMenu) {
            // Lưu trạng thái ban đầu có class hidden không
            const hadHiddenClass = userDropdownMenu.classList.contains('hidden');
            if (hadHiddenClass) {
                userDropdownMenu.setAttribute('data-had-hidden', 'true');
            }
            
            // Đóng dropdown khi click vào link trong dropdown (để mở modal)
            const dropdownLinks = userDropdownMenu.querySelectorAll('a');
            dropdownLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    // Chỉ đóng dropdown nếu không phải link logout
                    if (!link.classList.contains('logout-link')) {
                        setTimeout(function() {
                            userDropdownMenu.classList.remove('active');
                            // Thêm lại hidden nếu ban đầu có
                            if (hadHiddenClass) {
                                userDropdownMenu.classList.add('hidden');
                            }
                        }, 100);
                    }
                });
            });
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDropdownLinks);
    } else {
        initDropdownLinks();
    }
})();


/* ===================================== */
/* === XỬ LÝ MODAL TÀI KHOẢN - Đã chuyển sang account-modal.js === */
/* ===================================== */
// Code xử lý account modal đã được chuyển sang file account-modal.js
// để tránh conflict và dễ quản lý

