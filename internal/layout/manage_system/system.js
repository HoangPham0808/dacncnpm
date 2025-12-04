// ============================================
// QUẢN LÝ TÀI KHOẢN - JAVASCRIPT
// ============================================

/**
 * Xem lịch sử ra vào
 * @param {string} ten_dang_nhap - Tên đăng nhập cần xem lịch sử
 */
function viewRaVaoHistory(ten_dang_nhap) {
    console.log('viewRaVaoHistory called with:', ten_dang_nhap);
    
    const dialog = document.getElementById('ra-vao-history-dialog');
    const usernameEl = document.getElementById('ra-vao-username');
    const loadingEl = document.getElementById('ra-vao-loading');
    const contentEl = document.getElementById('ra-vao-content');
    const tbodyEl = document.getElementById('ra-vao-tbody');
    const emptyEl = document.getElementById('ra-vao-empty');
    
    if (!dialog) {
        console.error('Dialog không tồn tại!');
        alert('Lỗi: Không tìm thấy dialog lịch sử ra vào');
        return;
    }
    
    if (usernameEl) {
        usernameEl.textContent = ten_dang_nhap;
    }
    dialog.classList.add('active');
    document.body.classList.add('dialog-open');
    
    loadingEl.style.display = 'block';
    contentEl.style.display = 'none';
    emptyEl.style.display = 'none';
    tbodyEl.innerHTML = '';
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=view_ra_vao_history&ten_dang_nhap=${encodeURIComponent(ten_dang_nhap)}`
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        loadingEl.style.display = 'none';
        
        if (data.success && data.history && data.history.length > 0) {
            contentEl.style.display = 'block';
            emptyEl.style.display = 'none';
            
            tbodyEl.innerHTML = '';
            data.history.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>
                        <div class="time-main">${item.thoi_gian_vao}</div>
                    </td>
                    <td>
                        <span class="time-ago">${item.thoi_gian_truoc || 'N/A'}</span>
                    </td>
                `;
                tbodyEl.appendChild(row);
            });
        } else {
            contentEl.style.display = 'block';
            emptyEl.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        loadingEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'none';
        if (emptyEl) {
            emptyEl.style.display = 'block';
            emptyEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra khi tải lịch sử ra vào: ' + error.message;
        }
    });
}

/**
 * Đóng dialog lịch sử ra vào
 */
function closeRaVaoHistoryDialog() {
    const dialog = document.getElementById('ra-vao-history-dialog');
    if (dialog) {
        dialog.classList.remove('active');
        document.body.classList.remove('dialog-open');
    }
}

/**
 * Khóa tài khoản
 * @param {string} ten_dang_nhap - Tên đăng nhập cần khóa
 */
function lockAccount(ten_dang_nhap) {
    showConfirmDialog(
        `Bạn có chắc muốn khóa tài khoản "${ten_dang_nhap}"?`,
        function() {
            // Thực hiện khóa tài khoản
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=lock_account&ten_dang_nhap=${encodeURIComponent(ten_dang_nhap)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload trang khi thành công, không hiển thị dialog
                    location.reload();
                } else {
                    showMessageDialog('error', 'Lỗi', data.message || 'Có lỗi xảy ra khi khóa tài khoản!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessageDialog('error', 'Lỗi', 'Có lỗi xảy ra khi khóa tài khoản!');
            });
        }
    );
}

/**
 * Mở khóa tài khoản
 * @param {string} ten_dang_nhap - Tên đăng nhập cần mở khóa
 */
function unlockAccount(ten_dang_nhap) {
    showConfirmDialog(
        `Bạn có chắc muốn mở khóa tài khoản "${ten_dang_nhap}"?`,
        function() {
            // Thực hiện mở khóa tài khoản
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=unlock_account&ten_dang_nhap=${encodeURIComponent(ten_dang_nhap)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload trang khi thành công, không hiển thị dialog
                    location.reload();
                } else {
                    showMessageDialog('error', 'Lỗi', data.message || 'Có lỗi xảy ra khi mở khóa tài khoản!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessageDialog('error', 'Lỗi', 'Có lỗi xảy ra khi mở khóa tài khoản!');
            });
        }
    );
}

/**
 * Xóa nội dung tìm kiếm
 */
function clearSearch() {
    document.getElementById('search-input').value = '';
    filterTable();
}

/**
 * Hiển thị/ẩn nút xóa tìm kiếm
 */
function toggleClearButton() {
    const searchInput = document.getElementById('search-input');
    const clearBtn = document.querySelector('.clear-search');
    if (clearBtn) {
        clearBtn.style.display = searchInput.value.length > 0 ? 'block' : 'none';
    }
}

// Lọc theo trạng thái và loại tài khoản - sẽ được gán trong DOMContentLoaded

/**
 * Lọc bảng theo điều kiện tìm kiếm, trạng thái và loại tài khoản
 */
function filterTable() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const statusFilter = document.getElementById('status-filter').value;
    const typeFilter = document.getElementById('type-filter').value;
    const rows = document.querySelectorAll('#accounts-tbody tr');
    
    rows.forEach(row => {
        const username = row.cells[0].textContent.toLowerCase();
        const type = row.cells[1].textContent.trim();
        const status = row.cells[2].textContent.trim();
        
        const matchSearch = username.includes(searchTerm);
        const matchStatus = !statusFilter || status === statusFilter;
        const matchType = !typeFilter || type === typeFilter;
        
        if (matchSearch && matchStatus && matchType) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    toggleClearButton();
}

/**
 * Làm bảng responsive cho mobile
 */
function makeTableResponsive() {
    if (window.innerWidth <= 768) {
        const headers = [];
        document.querySelectorAll('#accounts-table thead th').forEach((th, index) => {
            headers[index] = th.textContent;
        });
        
        document.querySelectorAll('#accounts-table tbody td').forEach((td, index) => {
            const headerIndex = index % headers.length;
            td.setAttribute('data-label', headers[headerIndex]);
        });
    }
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
    
    messageEl.textContent = message;
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
    
    // Xử lý khi đóng
    const closeHandler = function() {
        closeMessageDialog();
    };
    
    // Xóa event listener cũ nếu có
    const oldHandler = dialog._closeHandler;
    if (oldHandler) {
        dialog.removeEventListener('click', oldHandler);
    }
    
    // Thêm event listener mới cho overlay (chỉ khi click vào overlay, không phải dialog content)
    const clickHandler = function(e) {
        if (e.target === dialog) {
            closeHandler();
        }
    };
    
    dialog.addEventListener('click', clickHandler);
    dialog._closeHandler = clickHandler;
    
    // Xử lý nút close riêng
    const closeBtn = dialog.querySelector('#message-close-btn');
    if (closeBtn) {
        const closeBtnHandler = function() {
            closeHandler();
        };
        // Xóa handler cũ nếu có
        if (closeBtn._handler) {
            closeBtn.removeEventListener('click', closeBtn._handler);
        }
        closeBtn.addEventListener('click', closeBtnHandler);
        closeBtn._handler = closeBtnHandler;
    }
}

/**
 * Đóng dialog thông báo
 */
function closeMessageDialog() {
    const dialog = document.getElementById('message-dialog');
    
    // Xóa event listener của overlay nếu có
    if (dialog._closeHandler) {
        dialog.removeEventListener('click', dialog._closeHandler);
        delete dialog._closeHandler;
    }
    
    // Xóa event listener của nút close nếu có
    const closeBtn = dialog.querySelector('#message-close-btn');
    if (closeBtn && closeBtn._handler) {
        closeBtn.removeEventListener('click', closeBtn._handler);
        delete closeBtn._handler;
    }
    
    dialog.classList.remove('active');
    dialog.classList.remove('message-success');
    dialog.classList.remove('message-error');
    document.body.classList.remove('dialog-open');
}

/**
 * Cắt ngắn text nếu quá dài
 */
function truncateText(text, maxLength) {
    if (!text || text === 'N/A') return 'N/A';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// Khởi tạo khi trang load
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý đóng dialog khi click vào overlay
    const raVaoHistoryDialog = document.getElementById('ra-vao-history-dialog');
    if (raVaoHistoryDialog) {
        raVaoHistoryDialog.addEventListener('click', function(e) {
            if (e.target === raVaoHistoryDialog) {
                closeRaVaoHistoryDialog();
            }
        });
    }
    
    // Lọc theo trạng thái
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterTable();
        });
    }
    
    // Lọc theo loại tài khoản
    const typeFilter = document.getElementById('type-filter');
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            filterTable();
        });
    }
    // Khởi tạo các chức năng khi trang được tải
    // Thêm nút xóa vào thanh tìm kiếm
    const searchInput = document.getElementById('search-input');
    const searchBar = document.querySelector('.search-bar');
    
    // Tạo wrapper cho input
    const inputWrapper = document.createElement('div');
    inputWrapper.className = 'search-input-wrapper';
    
    // Di chuyển input vào wrapper
    searchBar.insertBefore(inputWrapper, searchInput);
    inputWrapper.appendChild(searchInput);
    
    // Tạo nút xóa
    const clearButton = document.createElement('button');
    clearButton.type = 'button';
    clearButton.className = 'clear-search';
    clearButton.innerHTML = '✕';
    clearButton.title = 'Xóa tìm kiếm';
    clearButton.addEventListener('click', clearSearch);
    
    inputWrapper.appendChild(clearButton);
    
    // Ẩn nút xóa ban đầu
    clearButton.style.display = 'none';
    
    // Thêm sự kiện input để hiển thị nút xóa và filter
    searchInput.addEventListener('input', function() {
        toggleClearButton();
        filterTable();
    });
    
    // Khởi tạo các chức năng khác
    makeTableResponsive();
    window.addEventListener('resize', makeTableResponsive);
});