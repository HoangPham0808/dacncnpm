// ============================================
// QUẢN LÝ KHÁCH HÀNG - JAVASCRIPT
// ============================================

/**
 * Mở dialog
 * @param {string} dialogId - ID của dialog cần mở
 */
function openDialog(dialogId) {
    document.getElementById(dialogId).classList.add('active');
}

/**
 * Đóng dialog
 * @param {string} dialogId - ID của dialog cần đóng
 */
function closeDialog(dialogId) {
    document.getElementById(dialogId).classList.remove('active');
}

/**
 * Xóa tất cả bộ lọc tìm kiếm và reload trang
 */
function clearSearch() {
    window.location.href = 'customer.php';
}

/**
 * Xóa khách hàng với dialog xác nhận
 * @param {number} khach_hang_id - ID khách hàng cần xóa
 * @param {string} ho_ten - Họ tên khách hàng
 */
function deleteCustomer(khach_hang_id, ho_ten) {
    showConfirmDialog(
        `Bạn có chắc muốn xóa khách hàng "${ho_ten}"?<br><small style="color: #ff6b6b;">Hành động này không thể hoàn tác!</small>`,
        function() {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('khach_hang_id', khach_hang_id);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload trang để hiển thị message từ session
                    location.reload();
                } else {
                    showMessageDialog('error', 'Lỗi', data.message || 'Có lỗi xảy ra khi xóa khách hàng!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessageDialog('error', 'Lỗi', 'Có lỗi xảy ra khi xóa khách hàng!');
            });
        }
    );
}

/**
 * Hiển thị dialog xác nhận
 * @param {string} message - Nội dung thông báo
 * @param {Function} onConfirm - Hàm callback khi xác nhận
 */
function showConfirmDialog(message, onConfirm) {
    const dialog = document.getElementById('confirm-dialog');
    const messageEl = document.getElementById('confirm-message');
    const okBtn = document.getElementById('confirm-ok-btn');
    
    messageEl.innerHTML = message;
    dialog.classList.add('active');
    document.body.classList.add('dialog-open');
    
    // Xóa event listener cũ và thêm mới
    const newOkBtn = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOkBtn, okBtn);
    
    newOkBtn.addEventListener('click', function() {
        closeConfirmDialog();
        if (onConfirm) {
            onConfirm();
        }
    });
}

/**
 * Đóng dialog xác nhận
 */
function closeConfirmDialog() {
    const dialog = document.getElementById('confirm-dialog');
    dialog.classList.remove('active');
    document.body.classList.remove('dialog-open');
}

/**
 * Hiển thị dialog thông báo
 * @param {string} type - Loại thông báo: 'success', 'error', 'info'
 * @param {string} title - Tiêu đề
 * @param {string} message - Nội dung
 */
function showMessageDialog(type, title, message) {
    const dialog = document.getElementById('message-dialog');
    const titleEl = document.getElementById('message-title');
    const contentEl = document.getElementById('message-content');
    
    // Đặt icon và màu sắc theo loại
    let icon = 'fa-info-circle';
    if (type === 'success') {
        icon = 'fa-check-circle';
    } else if (type === 'error') {
        icon = 'fa-exclamation-circle';
    }
    
    titleEl.innerHTML = `<i class="fas ${icon}"></i> ${title}`;
    contentEl.textContent = message;
    
    // Thêm class để styling
    dialog.className = 'dialog-overlay';
    if (type === 'success') {
        dialog.classList.add('message-success');
    } else if (type === 'error') {
        dialog.classList.add('message-error');
    }
    
    dialog.classList.add('active');
    document.body.classList.add('dialog-open');
}

/**
 * Đóng dialog thông báo
 */
function closeMessageDialog() {
    const dialog = document.getElementById('message-dialog');
    dialog.classList.remove('active');
    dialog.classList.remove('message-success');
    dialog.classList.remove('message-error');
    document.body.classList.remove('dialog-open');
}

// Đóng dialog khi click vào overlay
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('dialog-overlay')) {
        const dialogId = e.target.id;
        if (dialogId === 'confirm-dialog') {
            closeConfirmDialog();
        } else if (dialogId === 'message-dialog') {
            closeMessageDialog();
        }
    }
});

// Event listener cho nút close của message dialog
document.addEventListener('DOMContentLoaded', function() {
    const messageCloseBtn = document.getElementById('message-close-btn');
    if (messageCloseBtn) {
        messageCloseBtn.addEventListener('click', function() {
            closeMessageDialog();
        });
    }
    
    // Tự động ẩn message banner từ PHP sau 5 giây
    const messageBanner = document.querySelector('.main-content .message');
    if (messageBanner) {
        setTimeout(() => {
            messageBanner.style.opacity = '0';
            messageBanner.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                messageBanner.remove();
            }, 300);
        }, 5000);
    }
    
    // Xử lý form thêm khách hàng bằng AJAX
    const addForm = document.querySelector('#addDialog form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Disable nút submit và hiển thị trạng thái loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang xử lý...';
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Khôi phục nút submit
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
                
                if (data.success) {
                    // Đóng dialog
                    closeDialog('addDialog');
                    // Reset form
                    this.reset();
                    // Reload trang để hiển thị message từ session và cập nhật danh sách
                    location.reload();
                } else {
                    // Hiển thị lỗi trong dialog, giữ dialog mở
                    showMessageDialog('error', 'Lỗi', data.message || 'Có lỗi xảy ra khi thêm khách hàng!');
                }
            })
            .catch(error => {
                // Khôi phục nút submit
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
                
                console.error('Error:', error);
                showMessageDialog('error', 'Lỗi', 'Có lỗi xảy ra khi thêm khách hàng!');
            });
        });
    }
});