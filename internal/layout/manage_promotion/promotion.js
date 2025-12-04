// Open/Close Dialog
function openDialog(dialogId) {
    const dialog = document.getElementById(dialogId);
    if (dialog) {
        dialog.style.display = 'flex';
        dialog.classList.add('active');
    }
}

function closeDialog(dialogId) {
    const dialog = document.getElementById(dialogId);
    if (dialog) {
        dialog.style.display = 'none';
        dialog.classList.remove('active');
    }
}

// Close dialog when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeDialog('modal');
    }
});

// Xóa khuyến mại
function deletePromotion(id, tenKhuyenMai) {
    const confirmMessage = document.getElementById('confirm-message');
    if (confirmMessage) {
        confirmMessage.textContent = `Bạn có chắc chắn muốn xóa khuyến mại "${tenKhuyenMai}"? Hành động này không thể hoàn tác!`;
    }
    openDialog('confirm-dialog');
    
    // Xử lý khi click nút xác nhận
    const confirmOkBtn = document.getElementById('confirm-ok-btn');
    if (confirmOkBtn) {
        // Xóa event listener cũ nếu có
        const newConfirmOkBtn = confirmOkBtn.cloneNode(true);
        confirmOkBtn.parentNode.replaceChild(newConfirmOkBtn, confirmOkBtn);
        
        // Thêm event listener mới
        newConfirmOkBtn.addEventListener('click', function() {
            performDelete(id);
        });
    }
}

function performDelete(id) {
    if (!id || !Number.isInteger(Number(id)) || Number(id) <= 0) {
        showMessageDialog('Lỗi', 'ID khuyến mại không hợp lệ!', 'error');
        return;
    }
    
    const confirmOkBtn = document.getElementById('confirm-ok-btn');
    const originalText = confirmOkBtn ? confirmOkBtn.textContent : '';
    
    // Disable nút và hiển thị trạng thái loading
    if (confirmOkBtn) {
        confirmOkBtn.disabled = true;
        confirmOkBtn.textContent = 'Đang xử lý...';
        confirmOkBtn.style.opacity = '0.6';
        confirmOkBtn.style.cursor = 'not-allowed';
    }
    
    // Tạo FormData để gửi request
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('khuyen_mai_id', id);
    
    // Gửi request AJAX
    fetch('promotion.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            const errors = {
                404: 'Không tìm thấy trang xử lý (404)',
                500: 'Lỗi máy chủ (500). Vui lòng liên hệ quản trị viên!',
                403: 'Không có quyền thực hiện thao tác này (403)!',
                400: 'Yêu cầu không hợp lệ (400)'
            };
            throw new Error(errors[response.status] || `Lỗi HTTP ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server trả về dữ liệu không đúng định dạng');
        }
        
        return response.json();
    })
    .then(data => {
        if (confirmOkBtn) {
            confirmOkBtn.disabled = false;
            confirmOkBtn.textContent = originalText;
            confirmOkBtn.style.opacity = '1';
            confirmOkBtn.style.cursor = 'pointer';
        }
        closeConfirmDialog();
        
        if (data && data.success) {
            // Hiển thị thông báo thành công
            showMessageDialog('Thành công', data.message || 'Xóa khuyến mại thành công!', 'success');
            // Reload trang sau 1.5 giây
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        } else {
            // Hiển thị thông báo lỗi
            showMessageDialog('Lỗi', data?.message || 'Không thể xóa khuyến mại. Vui lòng thử lại!', 'error');
        }
    })
    .catch(error => {
        if (confirmOkBtn) {
            confirmOkBtn.disabled = false;
            confirmOkBtn.textContent = originalText;
            confirmOkBtn.style.opacity = '1';
            confirmOkBtn.style.cursor = 'pointer';
        }
        closeConfirmDialog();
        
        let errorMessage = 'Đã xảy ra lỗi khi xóa khuyến mại!';
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            errorMessage = 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng!';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        showMessageDialog('Lỗi', errorMessage, 'error');
    });
}

// Đóng dialog xác nhận
function closeConfirmDialog() {
    closeDialog('confirm-dialog');
}

// Hiển thị dialog thông báo
function showMessageDialog(title, message, type) {
    const messageTitle = document.getElementById('message-title');
    const messageContent = document.getElementById('message-content');
    const messageDialog = document.getElementById('message-dialog');
    
    if (messageTitle && messageContent && messageDialog) {
        // Xóa các class cũ
        messageDialog.classList.remove('message-success', 'message-error');
        
        // Cập nhật icon và title
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        messageTitle.innerHTML = `<i class="fas ${icon}"></i> ${title}`;
        messageContent.textContent = message;
        
        // Thêm class để styling
        messageDialog.classList.add('message-' + type);
        
        // Hiển thị dialog
        openDialog('message-dialog');
    }
}

// Đóng dialog thông báo
function closeMessageDialog() {
    closeDialog('message-dialog');
    const messageDialog = document.getElementById('message-dialog');
    if (messageDialog) {
        messageDialog.classList.remove('message-success', 'message-error');
    }
}

// Form Validation
function validateForm() {
    const form = document.querySelector('.form-khuyen-mai');
    if (!form) return true;
    
    const getValue = (name) => form.querySelector(`[name="${name}"]`)?.value || '';
    const getFloat = (name) => parseFloat(getValue(name)) || 0;
    const getInt = (name) => parseInt(getValue(name)) || 0;
    
    const errors = [];
    const ma = getValue('ma_khuyen_mai').trim();
    const ten = getValue('ten_khuyen_mai').trim();
    const moTa = getValue('mo_ta').trim();
    const loaiGiam = getValue('loai_giam');
    const giaTriGiam = getFloat('gia_tri_giam');
    const giamToiDa = getValue('giam_toi_da');
    const donToiThieu = getValue('gia_tri_don_hang_toi_thieu');
    const ngayBatDau = getValue('ngay_bat_dau');
    const ngayKetThuc = getValue('ngay_ket_thuc');
    const soLuongMa = getValue('so_luong_ma');
    const trangThai = getValue('trang_thai');
    
    // Validation rules
    if (!ma) errors.push('Mã khuyến mại không được để trống!');
    else if (ma.length > 20) errors.push('Mã khuyến mại không được vượt quá 20 ký tự!');
    else if (!/^[A-Z0-9]+$/.test(ma)) errors.push('Mã khuyến mại chỉ được chứa chữ in hoa và số!');
    
    if (!ten) errors.push('Tên khuyến mại không được để trống!');
    else if (ten.length > 200) errors.push('Tên khuyến mại không được vượt quá 200 ký tự!');
    
    if (moTa.length > 500) errors.push('Mô tả không được vượt quá 500 ký tự!');
    if (!loaiGiam) errors.push('Vui lòng chọn loại giảm!');
    
    if (giaTriGiam <= 0) errors.push('Giá trị giảm phải lớn hơn 0!');
    else if (loaiGiam === 'Phần trăm' && giaTriGiam > 100) errors.push('Giá trị giảm phần trăm không được vượt quá 100%!');
    
    if (giamToiDa) {
        const giamToiDaNum = parseFloat(giamToiDa);
        if (giamToiDaNum <= 0) errors.push('Giảm tối đa phải lớn hơn 0 VNĐ!');
        else if (loaiGiam === 'Số tiền' && giamToiDaNum < giaTriGiam) errors.push('Giảm tối đa không được nhỏ hơn giá trị giảm!');
    }
    
    if (donToiThieu && parseFloat(donToiThieu) <= 0) errors.push('Đơn tối thiểu phải lớn hơn 0 VNĐ!');
    if (!ngayBatDau) errors.push('Ngày bắt đầu không được để trống!');
    if (!ngayKetThuc) errors.push('Ngày kết thúc không được để trống!');
    else if (ngayBatDau && new Date(ngayKetThuc) < new Date(ngayBatDau)) errors.push('Ngày kết thúc phải sau hoặc bằng ngày bắt đầu!');
    if (soLuongMa && getInt('so_luong_ma') < 1) errors.push('Số lượng mã phải lớn hơn hoặc bằng 1!');
    if (!trangThai) errors.push('Vui lòng chọn trạng thái!');
    
    if (errors.length > 0) {
        alert('Vui lòng sửa các lỗi sau:\n• ' + errors.join('\n• '));
        return false;
    }
    return true;
}

// Auto-hide message after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const message = document.querySelector('.thong-bao');
    if (message) {
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 5000); // Tăng lên 5 giây để đọc được nhiều lỗi
    }
    
    // Thêm validation cho form
    const form = document.querySelector('.form-khuyen-mai');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Add event listener for open modal button
    const openModalBtn = document.getElementById('btn-open-modal');
    if (openModalBtn) {
        openModalBtn.addEventListener('click', function() {
            openDialog('modal');
        });
    }
    
    // Add event listener for close modal button
    const closeModalBtn = document.getElementById('btn-close-modal');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            closeDialog('modal');
        });
    }
    
    // Add event listener for message dialog close button
    const messageCloseBtn = document.getElementById('message-close-btn');
    if (messageCloseBtn) {
        messageCloseBtn.addEventListener('click', function() {
            closeMessageDialog();
        });
    }
    
    // Close dialog when clicking outside
    const dialogs = document.querySelectorAll('.dialog-overlay');
    dialogs.forEach(function(dialog) {
        dialog.addEventListener('click', function(e) {
            if (e.target === dialog) {
                if (dialog.id === 'confirm-dialog') {
                    closeConfirmDialog();
                } else if (dialog.id === 'message-dialog') {
                    closeMessageDialog();
                }
            }
        });
    });
});