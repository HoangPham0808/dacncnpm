// JavaScript cho packages.html

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

  // === SCRIPT XỬ LÝ MODAL THANH TOÁN ===
  const paymentModal = document.getElementById('payment-modal');
  if (paymentModal) { 
    const paymentForm = document.getElementById('payment-form');
    const paymentPackageName = document.getElementById('payment-package-name');
    const paymentPackagePrice = document.getElementById('payment-package-price');
    const paymentPackageId = document.getElementById('payment-package-id');
    const paymentMethodSelected = document.getElementById('payment-method-selected');
    
    // Hàm format giá tiền với định dạng đúng
    function formatPrice(priceRaw, card) {
      if (!priceRaw) return '';
      
      const priceNum = parseFloat(priceRaw);
      if (isNaN(priceNum) || priceNum <= 0) return '';
      
      // Format số với dấu chấm ngăn cách hàng nghìn
      let priceText = priceNum.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '₫';
      
      // Tìm đơn vị (ngày/tháng) từ DOM trong cùng card
      if (card) {
        const priceContainer = card.querySelector('.mb-8');
        if (priceContainer) {
          const unitEl = priceContainer.querySelector('span.text-gray-400');
          if (unitEl) {
            const unitText = unitEl.textContent.trim();
            if (unitText) {
              priceText += ' ' + unitText; // Thêm /ngày hoặc /tháng với khoảng trắng
            }
          }
        }
      }
      
      return priceText;
    }
    
    // Hàm điền thông tin gói vào form thanh toán - Sử dụng cách tiếp cận mạnh mẽ hơn
    function fillPaymentModal(packageId, packageName, packagePriceRaw, card) {
      // Tìm lại các element mỗi lần gọi để đảm bảo không bị null
      const nameInput = document.getElementById('payment-package-name');
      const priceInput = document.getElementById('payment-package-price');
      const idInput = document.getElementById('payment-package-id');
      
      if (!nameInput || !priceInput || !idInput) {
        console.warn('Payment modal elements not found', {
          nameInput: !!nameInput,
          priceInput: !!priceInput,
          idInput: !!idInput
        });
        return false;
      }
      
      // Lấy và format thông tin
      const finalPackageId = packageId || card?.getAttribute('data-goi-tap-id') || '';
      const finalPackageName = packageName || card?.querySelector('h2')?.textContent.trim() || '';
      const formattedPrice = formatPrice(packagePriceRaw, card);
      
      // Điền thông tin vào form - Sử dụng nhiều cách để đảm bảo
      nameInput.value = finalPackageName;
      nameInput.setAttribute('value', finalPackageName);
      
      priceInput.value = formattedPrice;
      priceInput.setAttribute('value', formattedPrice);
      
      idInput.value = finalPackageId;
      idInput.setAttribute('value', finalPackageId);
      
      // Lưu giá vào data attribute để bảo vệ khỏi bị ghi đè
      if (formattedPrice) {
        priceInput.setAttribute('data-selected-price', formattedPrice);
        priceInput.setAttribute('data-package-price', packagePriceRaw);
      }
      
      // Lưu tên và ID vào data attributes
      if (finalPackageName) {
        nameInput.setAttribute('data-package-name', finalPackageName);
      }
      if (finalPackageId) {
        idInput.setAttribute('data-package-id', finalPackageId);
      }
      
      console.log('Payment modal filled:', {
        id: finalPackageId,
        name: finalPackageName,
        price: formattedPrice,
        nameInputValue: nameInput.value,
        priceInputValue: priceInput.value,
        idInputValue: idInput.value
      });
      
      return true;
    }
    
    // Lưu thông tin gói được chọn vào biến global và localStorage để đảm bảo không bị mất
    window.__selectedPackageData = null;
    
    // Lưu reference đến button được click gần nhất
    window.__lastClickedPaymentButton = null;
    
    // Hàm lấy thông tin gói từ button được click gần nhất
    function getPackageDataFromLastClickedButton() {
      // Ưu tiên lấy từ button được click gần nhất
      if (window.__lastClickedPaymentButton) {
        const button = window.__lastClickedPaymentButton;
        const card = button.closest('.package-card');
        if (card) {
          const packageId = button.getAttribute('data-package-id');
          const packageName = button.getAttribute('data-package-name');
          const packagePriceRaw = button.getAttribute('data-package-price');
          
          if (packageId && packageName && packagePriceRaw) {
            return {
              id: packageId,
              name: packageName,
              price: packagePriceRaw,
              card: card,
              button: button
            };
          }
        }
      }
      
      return null;
    }
    
    // Lắng nghe click vào button "Mua gói" - Chạy TRƯỚC auth.js bằng cách đăng ký sớm hơn
    // Sử dụng DOMContentLoaded để đảm bảo chạy ngay khi DOM ready
    (function() {
      // Đăng ký listener ngay lập tức, không đợi DOMContentLoaded
      document.addEventListener('click', function(e) {
        const button = e.target.closest('a[data-modal-target="payment-modal"], button[data-modal-target="payment-modal"]');
        if (!button) return;
        
        const card = button.closest('.package-card');
        if (!card) return;
        
        console.log('Payment button clicked - packages.js handler');
        
        // Lưu reference đến button này ngay lập tức
        window.__lastClickedPaymentButton = button;
        
        // Lấy thông tin gói từ data attributes của button
        const packageId = button.getAttribute('data-package-id') || '';
        const packageName = button.getAttribute('data-package-name') || '';
        const packagePriceRaw = button.getAttribute('data-package-price') || '';
        
        console.log('Package data extracted from clicked button:', { 
          id: packageId, 
          name: packageName, 
          price: packagePriceRaw,
          button: button
        });
        
        if (!packageId || !packageName || !packagePriceRaw) {
          console.warn('Missing package data in button attributes');
          return;
        }
        
        // Lưu thông tin vào biến global
        window.__selectedPackageData = {
          id: packageId,
          name: packageName,
          price: packagePriceRaw,
          card: card,
          button: button
        };
        
        // Xóa localStorage cũ trước khi lưu mới để tránh nhầm lẫn
        try {
          localStorage.removeItem('__selectedPackageData');
          // Lưu vào localStorage để đảm bảo không bị mất
          localStorage.setItem('__selectedPackageData', JSON.stringify({
            id: packageId,
            name: packageName,
            price: packagePriceRaw
          }));
        } catch (e) {
          console.warn('Failed to save to localStorage:', e);
        }
        
        // Điền thông tin vào modal NGAY LẬP TỨC
        fillPaymentModal(packageId, packageName, packagePriceRaw, card);
        
        console.log('Package data saved and modal filled for package:', packageName, 'Price:', packagePriceRaw);
      }, true); // Capture phase - chạy TRƯỚC các script khác
    })();
    
    // Lắng nghe khi modal được mở (bởi auth.js hoặc script khác) để đảm bảo thông tin được điền
    if (paymentModal) {
      // Hàm điền lại thông tin nếu cần - Tìm từ nhiều nguồn
      const ensurePackageInfoFilled = function() {
        let data = window.__selectedPackageData;
        
        // Ưu tiên lấy từ button được click gần nhất (chính xác nhất)
        if (!data && window.__lastClickedPaymentButton) {
          const buttonData = getPackageDataFromLastClickedButton();
          if (buttonData) {
            data = buttonData;
            window.__selectedPackageData = data;
            console.log('Using data from last clicked button');
          }
        }
        
        // Nếu không có trong biến global và button, thử lấy từ localStorage
        if (!data) {
          try {
            const saved = localStorage.getItem('__selectedPackageData');
            if (saved) {
              const parsed = JSON.parse(saved);
              const buttonData = getPackageDataFromLastClickedButton();
              if (buttonData) {
                // Ưu tiên dữ liệu từ button, chỉ dùng localStorage nếu button không có
                data = {
                  id: buttonData.id || parsed.id,
                  name: buttonData.name || parsed.name,
                  price: buttonData.price || parsed.price,
                  card: buttonData.card,
                  button: buttonData.button
                };
              } else {
                data = {
                  id: parsed.id,
                  name: parsed.name,
                  price: parsed.price,
                  card: null
                };
              }
            }
          } catch (e) {
            console.warn('Failed to load from localStorage:', e);
          }
        }
        
        if (data) {
          console.log('Ensuring package info is filled:', data);
          fillPaymentModal(data.id, data.name, data.price, data.card);
        } else {
          console.log('No package data available to fill - trying to find from last clicked button');
          // Thử lấy từ button được click gần nhất
          if (window.__lastClickedPaymentButton) {
            const button = window.__lastClickedPaymentButton;
            const card = button.closest('.package-card');
            if (card) {
              const packageId = button.getAttribute('data-package-id');
              const packageName = button.getAttribute('data-package-name');
              const packagePriceRaw = button.getAttribute('data-package-price');
              if (packageId && packageName && packagePriceRaw) {
                console.log('Found package data from last clicked button:', { packageId, packageName, packagePriceRaw });
                fillPaymentModal(packageId, packageName, packagePriceRaw, card);
              }
            }
          }
        }
      };
      
      let wasActive = paymentModal.classList.contains('active') && !paymentModal.classList.contains('hidden');
      
      const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
          if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            const modal = mutation.target;
            const isActive = modal.id === 'payment-modal' && modal.classList.contains('active') && !modal.classList.contains('hidden');
            
            // Modal vừa được mở
            if (isActive && !wasActive) {
              wasActive = true;
              console.log('Payment modal opened, filling package info...');
              
              // Đảm bảo thông tin được điền ngay khi modal mở - Thử nhiều lần để đảm bảo
              const fillMultipleTimes = function(attempt) {
                ensurePackageInfoFilled();
                
                // Bảo vệ giá không bị ghi đè
                const priceInput = document.getElementById('payment-package-price');
                const nameInput = document.getElementById('payment-package-name');
                const idInput = document.getElementById('payment-package-id');
                
                if (priceInput) {
                  const savedPrice = priceInput.getAttribute('data-selected-price');
                  if (savedPrice && priceInput.value !== savedPrice) {
                    console.log('Restoring price (attempt ' + attempt + '):', savedPrice);
                    priceInput.value = savedPrice;
                    priceInput.setAttribute('value', savedPrice);
                  }
                }
                
                if (nameInput && !nameInput.value && nameInput.getAttribute('data-package-name')) {
                  nameInput.value = nameInput.getAttribute('data-package-name');
                  nameInput.setAttribute('value', nameInput.getAttribute('data-package-name'));
                }
                
                if (idInput && !idInput.value && idInput.getAttribute('data-package-id')) {
                  idInput.value = idInput.getAttribute('data-package-id');
                  idInput.setAttribute('value', idInput.getAttribute('data-package-id'));
                }
                
                // Log để debug
                console.log('Modal fields after fill (attempt ' + attempt + '):', {
                  name: nameInput?.value,
                  price: priceInput?.value,
                  id: idInput?.value
                });
                
                // Thử lại nếu vẫn chưa có giá trị và chưa quá 5 lần
                if (attempt < 5 && (!nameInput?.value || !priceInput?.value || !idInput?.value)) {
                  setTimeout(function() {
                    fillMultipleTimes(attempt + 1);
                  }, 50);
                }
              };
              
              // Bắt đầu điền ngay
              setTimeout(function() {
                fillMultipleTimes(1);
              }, 10);
              
              // Điền lại sau các khoảng thời gian khác nhau
              setTimeout(function() {
                fillMultipleTimes(2);
              }, 50);
              
              setTimeout(function() {
                fillMultipleTimes(3);
              }, 100);
              
              setTimeout(function() {
                fillMultipleTimes(4);
              }, 200);
            } else if (!isActive) {
              wasActive = false;
              // Xóa thông tin khi modal đóng (nhưng giữ lại button reference để có thể dùng lại)
              // window.__selectedPackageData = null;
              // Không xóa __lastClickedPaymentButton để có thể dùng lại nếu cần
            }
          }
        });
      });
      
      observer.observe(paymentModal, {
        attributes: true,
        attributeFilter: ['class']
      });
      
      // Kiểm tra lại sau một khoảng thời gian ngắn để đảm bảo
      const checkInterval = setInterval(function() {
        if (paymentModal.classList.contains('active') && !paymentModal.classList.contains('hidden')) {
          ensurePackageInfoFilled();
          
          // Đảm bảo các giá trị không bị mất
          const nameInput = document.getElementById('payment-package-name');
          const priceInput = document.getElementById('payment-package-price');
          const idInput = document.getElementById('payment-package-id');
          
          if (nameInput && !nameInput.value && nameInput.getAttribute('data-package-name')) {
            nameInput.value = nameInput.getAttribute('data-package-name');
          }
          
          if (priceInput && !priceInput.value && priceInput.getAttribute('data-selected-price')) {
            priceInput.value = priceInput.getAttribute('data-selected-price');
          }
          
          if (idInput && !idInput.value && idInput.getAttribute('data-package-id')) {
            idInput.value = idInput.getAttribute('data-package-id');
          }
        } else {
          // Dừng interval khi modal đóng
          clearInterval(checkInterval);
        }
      }, 100);
    }
    
    
    if (paymentForm) {
      // Các trường bắt buộc
      const requiredFields = {
        phone: document.getElementById('payment-phone'),
        cccd: document.getElementById('payment-cccd'),
        address: document.getElementById('payment-address'),
        birthday: document.getElementById('payment-birthday'),
        gender: document.getElementById('payment-gender')
      };
      
      // Hàm kiểm tra tất cả trường bắt buộc đã được điền
      function validateRequiredFields() {
        let isValid = true;
        
        // Kiểm tra từng trường
        if (!requiredFields.phone || !requiredFields.phone.value.trim()) {
          isValid = false;
        }
        if (!requiredFields.cccd || !requiredFields.cccd.value.trim()) {
          isValid = false;
        }
        if (!requiredFields.address || !requiredFields.address.value.trim()) {
          isValid = false;
        }
        if (!requiredFields.birthday || !requiredFields.birthday.value) {
          isValid = false;
        }
        if (!requiredFields.gender || !requiredFields.gender.value) {
          isValid = false;
        }
        
        return isValid;
      }
      
      // Hàm cập nhật trạng thái các nút thanh toán
      function updatePaymentButtons() {
        const isValid = validateRequiredFields();
        const submitButtons = paymentForm.querySelectorAll('button[type="submit"][data-payment-method]');
        
        submitButtons.forEach(function(btn) {
          if (isValid) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
          } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
          }
        });
      }
      
      // Lắng nghe sự kiện input trên tất cả các trường bắt buộc
      Object.values(requiredFields).forEach(function(field) {
        if (field) {
          field.addEventListener('input', updatePaymentButtons);
          field.addEventListener('change', updatePaymentButtons);
        }
      });
      
      // Kiểm tra ban đầu khi modal mở
      if (paymentModal) {
        const modalObserver = new MutationObserver(function() {
          if (paymentModal.classList.contains('active') && !paymentModal.classList.contains('hidden')) {
            setTimeout(updatePaymentButtons, 100);
          }
        });
        modalObserver.observe(paymentModal, {
          attributes: true,
          attributeFilter: ['class']
        });
      }
      
      // Xử lý khi click vào các button submit với data-payment-method
      const submitButtons = paymentForm.querySelectorAll('button[type="submit"][data-payment-method]');
      submitButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          // Kiểm tra validation trước khi submit
          if (!validateRequiredFields()) {
            e.preventDefault();
            alert('Vui lòng điền đầy đủ thông tin bắt buộc (Số điện thoại, CCCD, Địa chỉ, Ngày sinh, Giới tính)');
            return false;
          }
          
          const method = this.getAttribute('data-payment-method');
          if (paymentMethodSelected && method) {
            paymentMethodSelected.value = method;
          }
        });
      });
      
      // Xử lý khi click vào tab payment để cập nhật payment_method
      const paymentTabLinks = paymentForm.querySelectorAll('.payment-tab-link');
      paymentTabLinks.forEach(function(link) {
        link.addEventListener('click', function() {
          const target = this.getAttribute('data-target');
          let method = 'Ngân hàng'; // Mặc định
          
          if (target === '#pay-details-bank') {
            method = 'Ngân hàng';
          } else if (target === '#pay-details-momo') {
            method = 'Momo';
          } else if (target === '#pay-details-zalo') {
            method = 'ZaloPay';
          } else if (target === '#pay-details-visa') {
            method = 'Visa';
          } else if (target === '#pay-details-paypal') {
            method = 'PayPal';
          }
          
          if (paymentMethodSelected) {
            paymentMethodSelected.value = method;
          }
        });
      });
      
      paymentForm.addEventListener('submit', function(e) {
        const method = paymentMethodSelected ? paymentMethodSelected.value : '';
        if (!method) {
          e.preventDefault();
          alert('Vui lòng chọn phương thức thanh toán');
          return false;
        }
        
        // Cho phép form submit tự nhiên
        return true;
      });
    }
  }
});
