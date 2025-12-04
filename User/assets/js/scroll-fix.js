/**
 * scroll-fix.js - Đảm bảo scroll luôn hoạt động bình thường
 * Fix các trường hợp scroll bị lock không được restore
 */

(function() {
  'use strict';
  
  // Hàm để force restore scroll
  function forceRestoreScroll() {
    const body = document.body;
    const html = document.documentElement;
    
    // Remove tất cả các style có thể lock scroll
    body.style.removeProperty('overflow');
    body.style.removeProperty('position');
    body.style.removeProperty('top');
    body.style.removeProperty('width');
    html.style.removeProperty('overflow');
    
    // Remove các class có thể lock scroll
    body.classList.remove('no-scroll', 'menu-is-active');
    html.classList.remove('menu-is-active');
  }
  
  // Kiểm tra và restore scroll định kỳ (safety check)
  setInterval(function() {
    // Chỉ restore nếu không có modal nào đang mở và không có menu đang mở
    const hasActiveModal = document.querySelector('.modal-overlay.active:not(.hidden)');
    const hasActiveMenu = document.body.classList.contains('menu-is-active');
    
    if (!hasActiveModal && !hasActiveMenu) {
      // Kiểm tra xem scroll có bị lock không
      const bodyOverflow = window.getComputedStyle(document.body).overflow;
      const bodyPosition = window.getComputedStyle(document.body).position;
      const htmlOverflow = window.getComputedStyle(document.documentElement).overflow;
      
      // Nếu scroll bị lock nhưng không có modal/menu nào mở, restore lại
      if ((bodyOverflow === 'hidden' || bodyPosition === 'fixed' || htmlOverflow === 'hidden') && 
          !hasActiveModal && !hasActiveMenu) {
        console.warn('⚠️ Scroll bị lock không đúng cách, đang restore...');
        forceRestoreScroll();
      }
    }
  }, 1000); // Kiểm tra mỗi giây
  
  // Restore scroll khi page visibility thay đổi (user quay lại tab)
  document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
      const hasActiveModal = document.querySelector('.modal-overlay.active:not(.hidden)');
      const hasActiveMenu = document.body.classList.contains('menu-is-active');
      
      if (!hasActiveModal && !hasActiveMenu) {
        forceRestoreScroll();
      }
    }
  });
  
  // Restore scroll khi window focus
  window.addEventListener('focus', function() {
    setTimeout(function() {
      const hasActiveModal = document.querySelector('.modal-overlay.active:not(.hidden)');
      const hasActiveMenu = document.body.classList.contains('menu-is-active');
      
      if (!hasActiveModal && !hasActiveMenu) {
        forceRestoreScroll();
      }
    }, 100);
  });
  
  // Expose function globally để các file khác có thể sử dụng
  window.forceRestoreScroll = forceRestoreScroll;
  
  console.log('✅ Scroll fix initialized');
})();

