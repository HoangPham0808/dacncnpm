/**
 * Management Package JavaScript
 * Xử lý tất cả tương tác giao diện cho trang quản lý gói tập
 */

// Hàm mở modal thêm/sửa gói tập
function openModal(action, data = null) {
    const modal = document.getElementById('packageModal');
    const form = document.getElementById('packageForm');
    const modalTitle = document.getElementById('modalTitle');
    
    // Reset form
    form.reset();
    
    if (action === 'add') {
        // Chế độ thêm mới
        modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Thêm gói tập mới';
        document.getElementById('formAction').value = 'add';
        document.getElementById('goi_tap_id').value = '';
        document.getElementById('ma_goi_tap').removeAttribute('readonly');
    } else if (action === 'edit' && data) {
        // Chế độ sửa
        modalTitle.innerHTML = '<i class="fas fa-edit"></i> Sửa gói tập';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('goi_tap_id').value = data.goi_tap_id;
        document.getElementById('ma_goi_tap').value = data.ma_goi_tap;
        document.getElementById('ten_goi').value = data.ten_goi;
        document.getElementById('thoi_han_ngay').value = data.thoi_han_ngay;
        document.getElementById('gia_tien').value = data.gia_tien;
        document.getElementById('loai_goi').value = data.loai_goi;
        document.getElementById('trang_thai').value = data.trang_thai;
        document.getElementById('mo_ta').value = data.mo_ta || '';
        
        // Khóa trường mã gói tập khi sửa (tùy chọn)
        // document.getElementById('ma_goi_tap').setAttribute('readonly', 'readonly');
    }
    
    // Hiển thị modal với animation
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}

// Hàm đóng modal thêm/sửa
function closeModal() {
    const modal = document.getElementById('packageModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Hàm mở modal xác nhận xóa
function confirmDelete(id, name) {
    const modal = document.getElementById('deleteModal');
    document.getElementById('deletePackageId').value = id;
    document.getElementById('deletePackageName').textContent = name;
    
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}

// Hàm đóng modal xóa
function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Đóng modal khi click bên ngoài
window.onclick = function(event) {
    const packageModal = document.getElementById('packageModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === packageModal) {
        closeModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}

// Xử lý đóng modal bằng phím ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
        closeDeleteModal();
    }
});

// Tự động ẩn thông báo sau 3 giây
window.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 3000);
    }
});

// Validate form trước khi submit
document.getElementById('packageForm')?.addEventListener('submit', function(e) {
    const thoiHan = parseInt(document.getElementById('thoi_han_ngay').value);
    const giaTien = parseFloat(document.getElementById('gia_tien').value);
    
    // Validate thời hạn
    if (thoiHan <= 0) {
        e.preventDefault();
        showNotification('Thời hạn phải lớn hơn 0!', 'error');
        return false;
    }
    
    // Validate giá tiền
    if (giaTien < 0) {
        e.preventDefault();
        showNotification('Giá tiền không được âm!', 'error');
        return false;
    }
    
    // Hiển thị loading trên nút submit
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
});

// Format giá tiền khi nhập
document.getElementById('gia_tien')?.addEventListener('blur', function() {
    let value = this.value.replace(/[^0-9]/g, '');
    if (value) {
        // Round to nearest thousand
        value = Math.round(value / 1000) * 1000;
        this.value = value;
    }
});

// Tự động chuyển mã gói tập thành chữ hoa
document.getElementById('ma_goi_tap')?.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Đếm ký tự textarea
document.getElementById('mo_ta')?.addEventListener('input', function() {
    const maxLength = this.getAttribute('maxlength');
    const currentLength = this.value.length;
    
    // Có thể thêm hiển thị số ký tự còn lại nếu muốn
    // console.log(`${currentLength}/${maxLength}`);
});

// Hàm hiển thị thông báo động
function showNotification(message, type = 'success') {
    // Xóa thông báo cũ nếu có
    const oldAlert = document.querySelector('.alert');
    if (oldAlert) {
        oldAlert.remove();
    }
    
    // Tạo thông báo mới
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    // Thêm vào đầu page container
    const pageContainer = document.querySelector('.page-container');
    const pageHeader = document.querySelector('.page-header');
    pageContainer.insertBefore(alert, pageHeader.nextSibling);
    
    // Tự động ẩn sau 3 giây
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            alert.remove();
        }, 300);
    }, 3000);
}

// Xác nhận trước khi rời trang nếu form đang được chỉnh sửa
let formChanged = false;

document.getElementById('packageForm')?.addEventListener('input', function() {
    formChanged = true;
});

document.getElementById('packageForm')?.addEventListener('submit', function() {
    formChanged = false;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});

// Animation cho các card khi scroll vào view
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Áp dụng animation cho tất cả package cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.package-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `all 0.5s ease ${index * 0.1}s`;
        observer.observe(card);
    });
});

// Thêm hiệu ứng ripple cho buttons
document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});

// Log để debug (có thể xóa trong production)
console.log('Management Package JS loaded successfully');
console.log('Current packages count:', document.querySelectorAll('.package-card').length);