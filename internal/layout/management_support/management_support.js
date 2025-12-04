/**
 * Management Support JavaScript
 * Xử lý tất cả tương tác giao diện cho trang quản lý hỗ trợ
 */

// Hàm lọc theo trạng thái
function filterByStatus() {
    const statusFilter = document.getElementById('statusFilter').value;
    const url = new URL(window.location);
    
    if (statusFilter) {
        url.searchParams.set('status', statusFilter);
    } else {
        url.searchParams.delete('status');
    }
    
    window.location.href = url.toString();
}

// Hàm mở modal trả lời
function openReplyModal(ho_tro_id, customerName, requestContent) {
    const modal = document.getElementById('replyModal');
    const form = document.getElementById('replyForm');
    
    document.getElementById('reply_ho_tro_id').value = ho_tro_id;
    document.getElementById('reply_customer_name').textContent = customerName;
    document.getElementById('reply_request_content').innerHTML = requestContent.replace(/\n/g, '<br>');
    document.getElementById('reply_phan_hoi').value = '';
    
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
        document.getElementById('reply_phan_hoi').focus();
    }, 10);
}

// Hàm đóng modal trả lời
function closeReplyModal() {
    const modal = document.getElementById('replyModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        document.getElementById('replyForm').reset();
    }, 300);
}

// Hàm đánh dấu đã xử lý
function markResolved(ho_tro_id, customerName) {
    if (!confirm('Bạn có chắc chắn muốn đánh dấu yêu cầu hỗ trợ của "' + customerName + '" là đã xử lý?')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'mark_resolved';
    form.appendChild(actionInput);
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'ho_tro_id';
    idInput.value = ho_tro_id;
    form.appendChild(idInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Hàm đóng yêu cầu hỗ trợ
function closeSupport(ho_tro_id, customerName) {
    if (!confirm('Bạn có chắc chắn muốn đóng yêu cầu hỗ trợ của "' + customerName + '"?\n\nSau khi đóng, yêu cầu này sẽ không thể chỉnh sửa nữa.')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'close';
    form.appendChild(actionInput);
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'ho_tro_id';
    idInput.value = ho_tro_id;
    form.appendChild(idInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Đóng modal khi click bên ngoài
window.onclick = function(event) {
    const replyModal = document.getElementById('replyModal');
    if (event.target === replyModal) {
        closeReplyModal();
    }
}

// Đóng modal khi nhấn ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeReplyModal();
    }
});

// Tự động ẩn thông báo sau 5 giây
window.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    }
});

console.log('Management Support JS loaded successfully');

