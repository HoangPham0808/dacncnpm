/**
 * Inbox Modal Handler V2 - Đơn giản và rõ ràng
 * Xử lý modal hòm thư và load thông báo
 */
(function() {
  'use strict';
  
  let inboxModal = null;
  let notificationsContainer = null;
  let totalCountEl = null;
  let unreadCountEl = null;
  let isInitialized = false;
  let isLoading = false;
  
  // Function để init
  function init() {
    if (isInitialized) return;
    
    console.log('📬 Inbox Modal V2 initializing...');
    
    // Tìm modal
    inboxModal = document.getElementById('inbox-modal');
    if (!inboxModal) {
      console.warn('⚠️ Inbox modal not found, will retry...');
      setTimeout(init, 500);
      return;
    }
    
    // Tìm container
    notificationsContainer = document.getElementById('inbox-notifications-list');
    if (!notificationsContainer) {
      console.error('❌ Container inbox-notifications-list not found');
      setTimeout(init, 500);
      return;
    }
    
    // Tìm các element cần update
    totalCountEl = document.getElementById('inbox-total-count');
    unreadCountEl = document.getElementById('inbox-unread-count');
    
    console.log('✅ All elements found');
    console.log('✅ Modal:', inboxModal ? 'found' : 'not found');
    console.log('✅ Container:', notificationsContainer ? 'found' : 'not found');
    console.log('✅ Total count el:', totalCountEl ? 'found' : 'not found');
    console.log('✅ Unread count el:', unreadCountEl ? 'found' : 'not found');
    
    setupEventListeners();
    isInitialized = true;
    console.log('✅ Inbox Modal V2 initialized successfully');
    
    // Load ngay nếu modal đã active
    if (inboxModal.classList.contains('active') && !inboxModal.classList.contains('hidden')) {
      console.log('📬 Modal already active on init, loading notifications...');
      loadNotifications();
    }
  }
  
  // Setup event listeners
  function setupEventListeners() {
    let hasLoaded = false;
    
    // Lắng nghe khi modal được mở - chỉ load một lần
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
          const isActive = inboxModal.classList.contains('active') && !inboxModal.classList.contains('hidden');
          if (isActive && !hasLoaded && !isLoading) {
            console.log('📬 Modal opened (MutationObserver), loading notifications...');
            hasLoaded = true;
            loadNotifications();
          } else if (!isActive) {
            hasLoaded = false; // Reset khi đóng modal
          }
        }
      });
    });
    
    observer.observe(inboxModal, {
      attributes: true,
      attributeFilter: ['class']
    });
    
    // Event listeners cho close button
    const closeBtn = document.getElementById('inbox-modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function() {
        hasLoaded = false;
        closeModal();
      });
    }
    
    // Close khi click overlay
    inboxModal.addEventListener('click', function(e) {
      if (e.target === inboxModal) {
        hasLoaded = false;
        closeModal();
      }
    });
    
    // Close với ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && inboxModal.classList.contains('active')) {
        hasLoaded = false;
        closeModal();
      }
    });
    
    // Listen for button clicks - chỉ load một lần
    document.addEventListener('click', function(e) {
      const target = e.target.closest('[data-modal-target="inbox-modal"], [href="#inbox"]');
      if (target && !hasLoaded) {
        console.log('🔘 Inbox button clicked');
        setTimeout(function() {
          if (inboxModal.classList.contains('active') && !inboxModal.classList.contains('hidden') && !isLoading) {
            console.log('📬 Modal opened (button click), loading notifications...');
            hasLoaded = true;
            loadNotifications();
          }
        }, 100);
      }
    }, true);
  }
  
  // Function để load thông báo
  function loadNotifications() {
    if (isLoading) {
      console.log('⏳ Already loading, skipping...');
      return;
    }
    
    if (!notificationsContainer) {
      console.error('❌ Container not found, reinitializing...');
      init();
      return;
    }
    
    isLoading = true;
    console.log('🔄 Loading notifications...');
    
    // Hiển thị loading state
    notificationsContainer.innerHTML = `
      <div style="text-align: center; padding: 40px; color: var(--muted);">
        <i class="fas fa-spinner fa-spin" style="font-size: 32px; margin-bottom: 15px;"></i>
        <p>Đang tải thông báo...</p>
      </div>
    `;
    
    // Xác định đường dẫn
    const pathname = window.location.pathname;
    const isInUserFolder = pathname.includes('/user/');
    const isInSubFolder = pathname.match(/\/user\/(goitap|danhgia|hotro|lichtap|homthu|thanhtoan|dangky|dangnhap|getset)\//);
    
    let inboxPath;
    if (isInSubFolder) {
        // Nếu đang ở trong thư mục con, dùng đường dẫn tương đối
        inboxPath = pathname.includes('/homthu/') ? 'get_inbox.php' : '../homthu/get_inbox.php';
    } else {
        inboxPath = isInUserFolder ? 'homthu/get_inbox.php' : 'user/homthu/get_inbox.php';
    }
    const url = inboxPath + '?t=' + Date.now();
    
    console.log('📡 Fetching from:', url);
    
    // Fetch dữ liệu
    fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Cache-Control': 'no-cache'
      },
      cache: 'no-store'
    })
    .then(response => {
      console.log('📡 Response status:', response.status, response.statusText);
      if (!response.ok) {
        // Nếu là lỗi 403, thử lại với đường dẫn khác
        if (response.status === 403) {
          console.warn('403 Forbidden, trying alternative path...');
          const altPathname = window.location.pathname;
          const altIsInSubFolder = altPathname.match(/\/user\/(goitap|danhgia|hotro|lichtap|homthu|thanhtoan|dangky|dangnhap|getset)\//);
          const altInboxPath = altIsInSubFolder ? '../homthu/get_inbox.php' : (altPathname.includes('/user/') ? 'homthu/get_inbox.php' : 'user/homthu/get_inbox.php');
          const altUrl = altInboxPath + '?t=' + Date.now();
          console.log('📡 Retrying with:', altUrl);
          return fetch(altUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'Cache-Control': 'no-cache'
            },
            cache: 'no-store'
          }).then(altResponse => {
            if (!altResponse.ok) throw new Error('HTTP ' + altResponse.status + ': ' + altResponse.statusText);
            return altResponse.json();
          });
        }
        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
      }
      return response.json();
    })
    .then(data => {
      isLoading = false;
      console.log('✅ Data received:', data);
      console.log('📊 Success:', data.success);
      console.log('📊 Notifications count:', data.notifications?.length || 0);
      console.log('📊 Unread count:', data.unread_count || 0);
      
      if (data.success && data.notifications && Array.isArray(data.notifications) && data.notifications.length > 0) {
        console.log('✅ Displaying', data.notifications.length, 'notifications');
        displayNotifications(data.notifications);
        updateCounts(data.notifications.length, data.unread_count || 0);
      } else {
        console.log('⚠️ No notifications or empty array');
        displayEmpty();
        updateCounts(0, 0);
      }
    })
    .catch(error => {
      isLoading = false;
      console.error('❌ Error loading notifications:', error);
      console.error('❌ Error stack:', error.stack);
      const errorMsg = error.message.includes('403') ? 
        'HTTP 403: Không có quyền truy cập. Vui lòng kiểm tra đường dẫn API.' : 
        error.message;
      displayError(errorMsg);
      updateCounts(0, 0);
    });
  }
  
  // Function để hiển thị thông báo
  function displayNotifications(notifications) {
    console.log('📝 Displaying', notifications.length, 'notifications');
    
    if (!notificationsContainer) {
      console.error('❌ Container not found');
      return;
    }
    
    let html = '';
    
    notifications.forEach((notif, index) => {
      const id = notif.thong_bao_id || notif.ho_tro_id || index;
      const title = notif.tieu_de || 'Thông báo';
      const content = notif.noi_dung || '';
      const isUnread = notif.da_doc == 0 || notif.da_doc === 0;
      const type = notif.loai_thong_bao || 'Hệ thống';
      
      // Format date
      let dateStr = '';
      const dateValue = notif.ngay_gui || notif.thoi_gian;
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
          }
        } catch (e) {
          dateStr = dateValue;
        }
      }
      
      // Escape HTML và convert \n thành <br>
      const escapeHtmlWithBr = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
      };
      
      html += `
        <div class="notification-item" 
             data-notification-id="${id}"
             style="background: ${isUnread ? 'rgba(255, 48, 64, 0.05)' : 'var(--bg-2)'}; 
                    padding: 20px; 
                    border-radius: 12px; 
                    margin-bottom: 15px; 
                    border-left: 4px solid ${isUnread ? 'var(--accent)' : 'var(--primary)'}; 
                    transition: all 0.3s;
                    position: relative;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
            <div style="flex: 1; min-width: 200px;">
              <div style="font-weight: 600; font-size: 16px; color: var(--text); margin-bottom: 8px;">
                ${escapeHtmlWithBr(title)}
              </div>
              <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <span style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; 
                             background: rgba(52, 152, 219, 0.1); color: #3498db;">
                  ${escapeHtmlWithBr(type)}
                </span>
                ${isUnread ? '<span style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: var(--accent); color: white;">Mới</span>' : ''}
              </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
              ${dateStr ? `<div style="color: var(--muted); font-size: 13px; white-space: nowrap;">${escapeHtmlWithBr(dateStr)}</div>` : ''}
              <div style="display: flex; gap: 6px;">
                <button onclick="event.stopPropagation(); if(typeof markInboxAsRead === 'function') markInboxAsRead(${id});" 
                        style="padding: 6px 12px; background: rgba(52, 152, 219, 0.1); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); border-radius: 6px; cursor: pointer; font-size: 12px; display: flex; align-items: center; gap: 4px; transition: all 0.2s;"
                        onmouseover="this.style.background='rgba(52, 152, 219, 0.2)'" 
                        onmouseout="this.style.background='rgba(52, 152, 219, 0.1)'"
                        title="Đánh dấu đã đọc">
                  <i class="fas fa-check"></i>
                </button>
                <button onclick="event.stopPropagation(); if(typeof deleteInboxNotification === 'function') deleteInboxNotification(${id});" 
                        style="padding: 6px 12px; background: rgba(255, 48, 64, 0.1); color: #ff3040; border: 1px solid rgba(255, 48, 64, 0.3); border-radius: 6px; cursor: pointer; font-size: 12px; display: flex; align-items: center; gap: 4px; transition: all 0.2s;"
                        onmouseover="this.style.background='rgba(255, 48, 64, 0.2)'" 
                        onmouseout="this.style.background='rgba(255, 48, 64, 0.1)'"
                        title="Xóa thông báo">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
          <div style="color: var(--muted); line-height: 1.6; font-size: 14px; margin-top: 10px;">
            ${escapeHtmlWithBr(content)}
          </div>
        </div>
      `;
    });
    
    // Update container
    try {
      notificationsContainer.innerHTML = html;
      console.log('✅ Notifications displayed successfully');
      console.log('✅ Container children:', notificationsContainer.children.length);
    } catch (e) {
      console.error('❌ Error setting innerHTML:', e);
      displayError('Lỗi hiển thị thông báo');
    }
  }
  
  // Function để hiển thị empty state
  function displayEmpty() {
    if (!notificationsContainer) return;
    notificationsContainer.innerHTML = `
      <div style="text-align: center; padding: 60px 20px; color: var(--muted);">
        <i class="fas fa-inbox" style="font-size: 64px; color: var(--muted); opacity: 0.3; margin-bottom: 20px;"></i>
        <p style="font-size: 16px;">Bạn chưa có thông báo nào</p>
      </div>
    `;
  }
  
  // Function để escape HTML
  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Function để hiển thị lỗi
  function displayError(message) {
    if (!notificationsContainer) return;
    notificationsContainer.innerHTML = `
      <div style="text-align: center; padding: 40px; color: var(--accent);">
        <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
        <p style="font-size: 16px;">Có lỗi xảy ra: ${escapeHtml(message)}</p>
        <button onclick="if(typeof loadInboxNotifications === 'function') loadInboxNotifications();" 
                style="margin-top: 15px; padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">
          Thử lại
        </button>
      </div>
    `;
  }
  
  // Function để update counts
  function updateCounts(total, unread) {
    if (totalCountEl) {
      totalCountEl.textContent = total;
      console.log('✅ Total count updated:', total);
    }
    if (unreadCountEl) {
      unreadCountEl.textContent = unread;
      console.log('✅ Unread count updated:', unread);
    }
  }
  
  // Function để đóng modal
  function closeModal() {
    if (inboxModal) {
      inboxModal.classList.remove('active');
      inboxModal.classList.add('hidden');
      inboxModal.style.setProperty('display', 'none', 'important');
      document.body.style.removeProperty('overflow');
      document.documentElement.style.removeProperty('overflow');
      if (window.location.hash === '#inbox') {
        history.replaceState(null, '', window.location.pathname);
      }
    }
  }
  
  // Expose functions globally
  window.loadInboxNotifications = function() {
    if (!isInitialized) {
      init();
      setTimeout(function() {
        loadNotifications();
      }, 100);
    } else {
      loadNotifications();
    }
  };
  
  window.closeInboxModal = closeModal;
  
  // Mark as read function
  window.markInboxAsRead = async function(id) {
    try {
      console.log('📖 Marking notification as read:', id);
      const pathname = window.location.pathname;
      const isInUserFolder = pathname.includes('/user/');
      const isInSubFolder = pathname.match(/\/user\/(goitap|danhgia|hotro|lichtap|homthu|thanhtoan|dangky|dangnhap|getset)\//);
      
      let path;
      if (isInSubFolder) {
        path = pathname.includes('/homthu/') ? 'mark_read.php' : '../homthu/mark_read.php';
      } else {
        path = isInUserFolder ? 'homthu/mark_read.php' : 'user/homthu/mark_read.php';
      }
      
      const response = await fetch(path, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'thong_bao_id=' + id
      });
      
      const data = await response.json();
      if (data.success) {
        console.log('✅ Notification marked as read');
        // Reload notifications
        loadNotifications();
      } else {
        console.error('❌ Failed to mark as read:', data.message);
      }
    } catch (error) {
      console.error('❌ Error marking as read:', error);
    }
  };
  
  // Mark all as read function
  window.markAllInboxAsRead = async function() {
    try {
      console.log('📖 Marking all notifications as read');
      const pathname = window.location.pathname;
      const isInUserFolder = pathname.includes('/user/');
      const isInSubFolder = pathname.match(/\/user\/(goitap|danhgia|hotro|lichtap|homthu|thanhtoan|dangky|dangnhap|getset)\//);
      
      let path;
      if (isInSubFolder) {
        path = pathname.includes('/homthu/') ? 'mark_all_read.php' : '../homthu/mark_all_read.php';
      } else {
        path = isInUserFolder ? 'homthu/mark_all_read.php' : 'user/homthu/mark_all_read.php';
      }
      
      const response = await fetch(path, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      });
      
      const data = await response.json();
      if (data.success) {
        console.log('✅ All notifications marked as read');
        // Reload notifications
        loadNotifications();
      } else {
        console.error('❌ Failed to mark all as read:', data.message);
        alert('Không thể đánh dấu tất cả đã đọc: ' + (data.message || 'Lỗi không xác định'));
      }
    } catch (error) {
      console.error('❌ Error marking all as read:', error);
      alert('Có lỗi xảy ra khi đánh dấu tất cả đã đọc');
    }
  };
  
  // Delete notification function
  window.deleteInboxNotification = async function(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
      return;
    }
    
    try {
      console.log('🗑️ Deleting notification:', id);
      const pathname = window.location.pathname;
      const isInUserFolder = pathname.includes('/user/');
      const isInSubFolder = pathname.match(/\/user\/(goitap|danhgia|hotro|lichtap|homthu|thanhtoan|dangky|dangnhap|getset)\//);
      
      let path;
      if (isInSubFolder) {
        path = pathname.includes('/homthu/') ? 'delete_notification.php' : '../homthu/delete_notification.php';
      } else {
        path = isInUserFolder ? 'homthu/delete_notification.php' : 'user/homthu/delete_notification.php';
      }
      
      const response = await fetch(path, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'thong_bao_id=' + id
      });
      
      const data = await response.json();
      if (data.success) {
        console.log('✅ Notification deleted');
        // Reload notifications
        loadNotifications();
      } else {
        console.error('❌ Failed to delete:', data.message);
        alert('Không thể xóa thông báo: ' + (data.message || 'Lỗi không xác định'));
      }
    } catch (error) {
      console.error('❌ Error deleting notification:', error);
      alert('Có lỗi xảy ra khi xóa thông báo');
    }
  };
  
  // Initialize khi DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    // DOM đã ready
    init();
  }
  
  // Fallback: init sau 1 giây nếu chưa init
  setTimeout(function() {
    if (!isInitialized) {
      console.warn('⚠️ Not initialized after 1s, retrying...');
      init();
    }
  }, 1000);
  
  console.log('✅ Inbox Modal V2 script loaded');
})();

