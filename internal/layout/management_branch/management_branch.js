// Open/Close Dialog
function openDialog(dialogId) {
    document.getElementById(dialogId).classList.add('active');
}

function closeDialog(dialogId) {
    document.getElementById(dialogId).classList.remove('active');
}

// Open Add Dialog
function openAddDialog() {
    const form = document.querySelector('#addDialog form');
    if (form) form.reset();
    openDialog('addDialog');
}

// Close dialog when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('dialog-overlay')) {
        closeDialog('addDialog');
        closeDialog('editDialog');
    }
});

// Auto-hide message after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const message = document.querySelector('.message');
    if (message) {
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 3000);
    }
});

// Edit Branch
async function editBranch(id) {
    try {
        const response = await fetch(`management_function.php?action=getDetail&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Fill form
            document.getElementById('edit_phong_tap_id').value = data.phong_tap_id || '';
            document.getElementById('edit_ma_phong_tap').value = data.ma_phong_tap || '';
            document.getElementById('edit_ten_phong_tap').value = data.ten_phong_tap || '';
            document.getElementById('edit_dia_chi').value = data.dia_chi || '';
            document.getElementById('edit_so_dien_thoai').value = data.so_dien_thoai || '';
            document.getElementById('edit_email').value = data.email || '';
            document.getElementById('edit_trang_thai').value = data.trang_thai || 'Hoạt động';
            document.getElementById('edit_ngay_thanh_lap').value = data.ngay_thanh_lap || '';
            document.getElementById('edit_ghi_chu').value = data.ghi_chu || '';
            
            openDialog('editDialog');
        } else {
            alert('❌ ' + (result.message || 'Không thể tải thông tin phòng tập'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Có lỗi xảy ra khi tải thông tin phòng tập');
    }
}

// Delete Branch
function deleteBranch(id) {
    if (confirm('⚠️ Bạn có chắc chắn muốn xóa phòng tập này?\n\nLưu ý: Thao tác này không thể hoàn tác!')) {
        window.location.href = '?delete=' + id;
    }
}

// Reset Filter
function resetFilter() {
    window.location.href = 'management_branch.php';
}

// Show Alert
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `message ${type}`;
    alertDiv.textContent = message;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? 'rgba(57, 217, 138, 0.9)' : 'rgba(255, 48, 64, 0.9)'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
        font-size: 14px;
        font-weight: 600;
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (alertDiv.parentNode) {
                document.body.removeChild(alertDiv);
            }
        }, 300);
    }, 3000);
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);