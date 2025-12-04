/**
 * Inbox Modal Handler
 * Xử lý modal hòm thư và load thông báo
 */
(function() {
  // Lấy tất cả modal inbox (nếu có nhiều do lỗi duplicate)
  const inboxModals = document.querySelectorAll('#inbox-modal');
  if (inboxModals.length === 0) return;
  
  // Chỉ sử dụng modal đầu tiên, ẩn các modal trùng lặp
  const inboxModal = inboxModals[0];
  if (inboxModals.length > 1) {
    console.warn('⚠️ Phát hiện ' + inboxModals.length + ' modal inbox trùng lặp. Chỉ sử dụng modal đầu tiên.');
    for (let i = 1; i < inboxModals.length; i++) {
      inboxModals[i].style.setProperty('display', 'none', 'important');
      inboxModals[i].remove(); // Xóa các modal trùng lặp
    }
  }
  
  // Đảm bảo function được expose ngay từ đầu
  window.closeInboxModal = function() {
    if (inboxModal) {
      // Xóa class active
      inboxModal.classList.remove('active');
      // Thêm class hidden để đảm bảo modal bị ẩn
      inboxModal.classList.add('hidden');
      
      // Force ẩn bằng inline style với !important
      inboxModal.style.setProperty('display', 'none', 'important');
      inboxModal.style.setProperty('visibility', 'hidden', 'important');
      inboxModal.style.setProperty('opacity', '0', 'important');
      inboxModal.style.setProperty('pointer-events', 'none', 'important');
      
      // Khôi phục scroll body
      document.body.style.removeProperty('overflow');
      document.documentElement.style.removeProperty('overflow');
      
      // Clear hash nếu có
      if (window.location.hash === '#inbox') {
        history.replaceState(null, '', window.location.pathname + window.location.search);
      }
      
      // Reset loading state
      const container = document.getElementById('inbox-notifications-list');
      if (container) {
        container.innerHTML = `
          <div class="empty-inbox" style="text-align: center; padding: 60px 20px; color: var(--muted);">
            <i class="fas fa-inbox" style="font-size: 64px; color: var(--muted); opacity: 0.3; margin-bottom: 20px;"></i>
            <p>Bạn chưa có thông báo nào</p>
          </div>
        `;
      }
      
      console.log('Inbox modal closed');
    }
  };

  // Thêm event handler cho nút đóng (đảm bảo hoạt động ngay cả khi đang loading)
  // Sử dụng event delegation để bắt tất cả nút đóng (kể cả khi được thêm động)
  // Sử dụng capture phase để chạy trước auth.js
  document.addEventListener('click', function(e) {
    const closeBtn = e.target.closest('#inbox-modal-close, .modal-close-btn');
    if (closeBtn && closeBtn.closest('#inbox-modal')) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      closeInboxModal();
      return false;
    }
  }, true); // true = capture phase

  // Đóng modal khi click vào overlay (đảm bảo hoạt động ngay cả khi đang loading)
  // Sử dụng event delegation
  document.addEventListener('click', function(e) {
    if (e.target === inboxModal || (e.target.classList.contains('modal-overlay') && e.target.id === 'inbox-modal')) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      closeInboxModal();
      return false;
    }
  }, true); // true = capture phase

  // Đóng modal bằng phím ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && inboxModal && inboxModal.classList.contains('active')) {
      e.preventDefault();
      e.stopPropagation();
      closeInboxModal();
    }
  }, true); // true = capture phase

  // Expose function globally để có thể gọi từ auth.js
  window.loadInboxNotifications = function() {
    loadInboxNotificationsInternal();
  };
  
  // Load thông báo khi modal được mở
  function loadInboxNotificationsInternal() {
    const container = document.getElementById('inbox-notifications-list');
    if (!container) return;

    // Hiển thị empty state ngay lập tức (không có loading spinner)
    container.innerHTML = `
      <div class="empty-inbox" style="text-align: center; padding: 60px 20px; color: var(--muted);">
        <i class="fas fa-inbox" style="font-size: 64px; color: var(--muted); opacity: 0.3; margin-bottom: 20px;"></i>
        <p>Bạn chưa có thông báo nào</p>
      </div>
    `;

    // Xác định đường dẫn đúng dựa trên vị trí hiện tại
    const isInUserFolder = window.location.pathname.includes('/user/');
    const inboxPath = isInUserFolder ? 'get_inbox.php' : 'user/get_inbox.php';
    
    console.log('Loading inbox from:', inboxPath);
    
    // Fetch dữ liệu trong background (không hiển thị loading)
    console.log('🔍 Starting fetch to:', inboxPath);
    // Thêm timestamp để tránh cache
    const cacheBuster = '?t=' + Date.now();
    fetch(inboxPath + cacheBuster, {
      method: 'GET',
      credentials: 'same-origin', // Đảm bảo gửi session cookie
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
        'Expires': '0'
      },
      cache: 'no-store' // Không cache để luôn lấy dữ liệu mới nhất
    })
      .then(response => {
        console.log('📡 Response status:', response.status);
        console.log('📡 Response headers:', response.headers);
        
        // Đọc response text trước để debug
        return response.text().then(text => {
          console.log('📄 Raw response text:', text);
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('❌ JSON parse error:', e);
            console.error('❌ Response text that failed to parse:', text);
            throw new Error('Invalid JSON response: ' + text.substring(0, 200));
          }
        });
      })
      .then(data => {
        console.log('✅ Inbox data received:', data);
        console.log('📊 Success:', data.success);
        console.log('📊 Number of notifications:', data.notifications?.length || 0);
        console.log('📊 Unread count:', data.unread_count || 0);
        
        if (data.success) {
          console.log('✅ Displaying notifications...');
          displayInboxNotifications(data);
          const totalEl = document.getElementById('inbox-total-count');
          const unreadEl = document.getElementById('inbox-unread-count');
          if (totalEl) {
            totalEl.textContent = data.notifications?.length || 0;
            console.log('✅ Updated total count:', data.notifications?.length || 0);
          } else {
            console.error('❌ Element inbox-total-count not found!');
          }
          if (unreadEl) {
            unreadEl.textContent = data.unread_count || 0;
            console.log('✅ Updated unread count:', data.unread_count || 0);
          } else {
            console.error('❌ Element inbox-unread-count not found!');
          }
          console.log('✅ Notifications displayed. Total:', data.notifications?.length || 0);
        } else {
          console.error('❌ Failed to load inbox data:', data.message || 'Unknown error');
          console.error('❌ Full error data:', data);
          // Nếu có lỗi, vẫn hiển thị empty state
          displayInboxNotifications({
            notifications: []
          });
        }
      })
      .catch(error => {
        console.error('❌ Error loading notifications:', error);
        console.error('❌ Error stack:', error.stack);
        // Nếu có lỗi, vẫn hiển thị empty state
        displayInboxNotifications({
          notifications: []
        });
      });
  }

  function displayInboxNotifications(data) {
    // Tìm container với retry logic
    let container = document.getElementById('inbox-notifications-list');
    if (!container) {
      console.warn('⚠️ Container inbox-notifications-list not found, retrying...');
      // Retry sau 100ms
      setTimeout(function() {
        container = document.getElementById('inbox-notifications-list');
        if (!container) {
          console.error('❌ Container inbox-notifications-list still not found after retry!');
          console.error('❌ Available elements with "inbox" in id:', 
            Array.from(document.querySelectorAll('[id*="inbox"]')).map(el => el.id));
          return;
        }
        displayInboxNotifications(data);
      }, 100);
      return;
    }

    const notifications = data.notifications || [];
    console.log('displayInboxNotifications called with', notifications.length, 'notifications');

    if (!notifications || notifications.length === 0) {
      console.log('No notifications, showing empty state');
      container.innerHTML = `
        <div class="empty-inbox" style="text-align: center; padding: 60px 20px; color: var(--muted);">
          <i class="fas fa-inbox" style="font-size: 64px; color: var(--muted); opacity: 0.3; margin-bottom: 20px;"></i>
          <p>Bạn chưa có thông báo nào</p>
        </div>
      `;
      return;
    }
    
    console.log('Rendering', notifications.length, 'notifications');

    let html = '';

    notifications.forEach(notif => {
      const isUnread = notif.da_doc == 0;
      const badgeClass = {
        'Hệ thống': 'badge-system',
        'Khuyến mãi': 'badge-promo',
        'Sự kiện': 'badge-event',
        'Nhắc nhở': 'badge-reminder'
      }[notif.loai_thong_bao] || 'badge-system';

      // Xử lý date - có thể là ngay_gui hoặc thoi_gian
      const dateValue = notif.ngay_gui || notif.thoi_gian;
      let dateStr = '';
      if (dateValue) {
        try {
          const date = new Date(dateValue);
          if (!isNaN(date.getTime())) {
            dateStr = date.toLocaleDateString('vi-VN', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            });
          } else {
            dateStr = dateValue; // Fallback nếu không parse được
          }
        } catch (e) {
          dateStr = dateValue; // Fallback nếu có lỗi
        }
      }

      // Đảm bảo tất cả các field đều có giá trị
      const thongBaoId = notif.thong_bao_id || notif.ho_tro_id || 0;
      const tieuDe = notif.tieu_de || 'Yêu cầu hỗ trợ';
      const noiDung = notif.noi_dung || '';
      const loaiThongBao = notif.loai_thong_bao || 'Hệ thống';
      
      console.log('📝 Rendering notification:', {
        id: thongBaoId,
        title: tieuDe.substring(0, 50),
        hasContent: !!noiDung
      });
      
      html += `
        <div class="notification-item" style="background: var(--bg-2); padding: 20px; border-radius: 12px; margin-bottom: 15px; border-left: 4px solid ${isUnread ? 'var(--accent)' : 'var(--primary)'}; transition: all 0.3s; cursor: pointer; ${isUnread ? 'background: rgba(255, 48, 64, 0.05);' : ''}" onclick="if(typeof markInboxAsRead === 'function') markInboxAsRead(${thongBaoId});">
          <div class="notification-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
            <div style="flex: 1;">
              <span class="notification-title" style="font-weight: 600; font-size: 16px; color: var(--text); flex: 1;">${escapeHtml(tieuDe)}</span>
              <span class="notification-badge ${badgeClass}" style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-left: 10px; ${badgeClass === 'badge-system' ? 'background: rgba(52, 152, 219, 0.1); color: #3498db;' : badgeClass === 'badge-promo' ? 'background: rgba(255, 193, 7, 0.1); color: #ffc107;' : badgeClass === 'badge-event' ? 'background: rgba(155, 89, 182, 0.1); color: #9b59b6;' : 'background: rgba(230, 126, 34, 0.1); color: #e67e22;'}">${escapeHtml(loaiThongBao)}</span>
              ${isUnread ? '<span class="notification-badge" style="background: var(--accent); color: white; margin-left: 5px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Mới</span>' : ''}
            </div>
            <div class="notification-date" style="color: var(--muted); font-size: 13px; white-space: nowrap;">${escapeHtml(dateStr)}</div>
          </div>
          <div class="notification-content" style="color: var(--muted); line-height: 1.6; font-size: 14px;">${escapeHtml(noiDung)}</div>
        </div>
      `;
    });

    console.log('✅ Generated HTML length:', html.length);
    console.log('✅ Container before update:', container);
    console.log('✅ Container innerHTML length before:', container.innerHTML.length);
    
    // Force update container
    try {
      container.innerHTML = html;
      console.log('✅ Container innerHTML updated successfully');
      console.log('✅ Container innerHTML length after:', container.innerHTML.length);
      console.log('✅ Container children count:', container.children.length);
      
      // Force reflow để đảm bảo browser render
      void container.offsetHeight;
      
      // Kiểm tra lại sau một chút
      setTimeout(function() {
        const checkContainer = document.getElementById('inbox-notifications-list');
        if (checkContainer) {
          console.log('✅ Final check - Container children:', checkContainer.children.length);
          console.log('✅ Final check - Container innerHTML length:', checkContainer.innerHTML.length);
          if (checkContainer.children.length === 0 && checkContainer.innerHTML.length > 0) {
            console.warn('⚠️ Container has HTML but no children - possible parsing issue');
          }
        }
      }, 100);
    } catch (e) {
      console.error('❌ Error setting innerHTML:', e);
      console.error('❌ Error stack:', e.stack);
    }
  }

  function escapeHtml(text) {
    if (!text) return '';
    // Escape HTML special characters và giữ lại \n
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;')
      .replace(/\n/g, '<br>'); // Convert \n thành <br> để hiển thị xuống dòng đúng
  }

  // Expose function globally
  window.markInboxAsRead = async function(id) {
    try {
      const formData = new FormData();
      formData.append('thong_bao_id', id);

      // Xác định đường dẫn đúng dựa trên vị trí hiện tại
      const markReadPath = window.location.pathname.includes('/user/') ? 'mark_read.php' : 'user/mark_read.php';

      const response = await fetch(markReadPath, {
        method: 'POST',
        body: formData
      });
      
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      // Reload notifications
      if (typeof window.loadInboxNotifications === 'function') {
        window.loadInboxNotifications();
      } else if (typeof loadInboxNotificationsInternal === 'function') {
        loadInboxNotificationsInternal();
      }
    } catch (error) {
      console.error('Error marking as read:', error);
    }
  };

  // Lắng nghe khi modal được mở
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
        if (inboxModal.classList.contains('active') && !inboxModal.classList.contains('hidden')) {
          console.log('Inbox modal became active, loading notifications...');
          loadInboxNotificationsInternal();
        }
      }
    });
  });

  observer.observe(inboxModal, {
    attributes: true,
    attributeFilter: ['class']
  });

  // Load ngay nếu modal đã active (khi trang load với hash #inbox)
  if (inboxModal.classList.contains('active') && !inboxModal.classList.contains('hidden')) {
    console.log('Inbox modal already active on page load, loading notifications...');
    loadInboxNotificationsInternal();
  }
  
  // Thêm event listener để gọi loadInboxNotifications khi modal được mở qua openModalById
  // Sử dụng setInterval để kiểm tra định kỳ (fallback nếu MutationObserver không hoạt động)
  let lastActiveState = inboxModal.classList.contains('active');
  let lastLoadTime = 0;
  const MIN_RELOAD_INTERVAL = 1000; // Chỉ reload nếu đã qua 1 giây kể từ lần load cuối
  
  setInterval(function() {
    const currentActiveState = inboxModal.classList.contains('active') && !inboxModal.classList.contains('hidden');
    const now = Date.now();
    
    if (currentActiveState && !lastActiveState) {
      // Modal vừa được mở
      console.log('📬 Inbox modal opened (detected by interval), loading notifications...');
      if (now - lastLoadTime > MIN_RELOAD_INTERVAL) {
        loadInboxNotificationsInternal();
        lastLoadTime = now;
      }
    } else if (currentActiveState && (now - lastLoadTime > 5000)) {
      // Nếu modal đang mở và đã qua 5 giây, reload lại để đảm bảo có dữ liệu mới nhất
      console.log('🔄 Inbox modal still open, reloading notifications after 5s...');
      loadInboxNotificationsInternal();
      lastLoadTime = now;
    }
    lastActiveState = currentActiveState;
  }, 500); // Kiểm tra mỗi 500ms
  
  // Thêm event listener trực tiếp cho các button mở inbox modal
  document.addEventListener('click', function(e) {
    const target = e.target.closest('[data-modal-target="inbox-modal"], [href="#inbox"], [onclick*="inbox"]');
    if (target) {
      console.log('🔘 Inbox button clicked, will load notifications...');
      // Đợi một chút để modal được mở trước
      setTimeout(function() {
        if (inboxModal.classList.contains('active') && !inboxModal.classList.contains('hidden')) {
          console.log('📬 Inbox modal opened via button click, loading notifications...');
          loadInboxNotificationsInternal();
          lastLoadTime = Date.now();
        }
      }, 200);
    }
  }, true);
})();

