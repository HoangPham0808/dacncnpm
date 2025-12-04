// Open/Close Dialog
function openDialog(dialogId) {
    document.getElementById(dialogId).classList.add('active');
}

function closeDialog(dialogId) {
    document.getElementById(dialogId).classList.remove('active');
}

// Close dialog when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('dialog-overlay')) {
        closeDialog('addDialog');
        closeDialog('editDialog');
        closeDialog('viewDialog');
        closeDialog('viewChamCongDialog');
        closeDialog('viewBangLuongDialog');
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

    // Show/hide clear button based on input
    const searchInput = document.getElementById('searchInput');
    const btnClearSearch = document.getElementById('btnClearSearch');
    
    if (searchInput && btnClearSearch) {
        searchInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                btnClearSearch.classList.add('show');
            } else {
                btnClearSearch.classList.remove('show');
            }
        });
    }
});

// Clear search function
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const genderFilter = document.getElementById('genderFilter');
    const roleFilter = document.getElementById('roleFilter');
    
    if (searchInput) searchInput.value = '';
    if (genderFilter) genderFilter.value = '';
    if (roleFilter) roleFilter.value = '';
    
    window.location.href = 'management_staff.php';
}

// View employee details
function viewEmployee(data) {
    document.getElementById('view_id').textContent = data.nhan_vien_id || '';
    document.getElementById('view_tenDangNhap').textContent = data.ten_dang_nhap || '';
    document.getElementById('view_hoTen').textContent = data.ho_ten || '';
    document.getElementById('view_email').textContent = data.email || '';
    document.getElementById('view_sdt').textContent = data.sdt || 'Chưa có';
    document.getElementById('view_cccd').textContent = data.cccd || 'Chưa có';
    document.getElementById('view_ngaySinh').textContent = data.ngay_sinh ? formatDate(data.ngay_sinh) : 'Chưa có';
    document.getElementById('view_gioiTinh').textContent = data.gioi_tinh || 'Chưa có';
    
    // Hiển thị phòng tập
    const phongTapText = data.ten_phong_tap 
        ? `${data.ma_phong_tap} - ${data.ten_phong_tap}` 
        : 'Chưa phân công';
    document.getElementById('view_phongTap').textContent = phongTapText;
    
    document.getElementById('view_vaiTro').textContent = data.vai_tro || '';
    document.getElementById('view_ngayVaoLam').textContent = data.ngay_vao_lam ? formatDate(data.ngay_vao_lam) : '';
    document.getElementById('view_luongCoBan').textContent = data.luong_co_ban ? formatCurrency(data.luong_co_ban) : 'Chưa có';
    document.getElementById('view_trangThai').textContent = data.trang_thai || '';
    document.getElementById('view_ngayTao').textContent = data.ngay_tao ? formatDateTime(data.ngay_tao) : '';
    document.getElementById('view_ngayCapNhat').textContent = data.ngay_cap_nhat ? formatDateTime(data.ngay_cap_nhat) : '';
    document.getElementById('view_diaChi').textContent = data.dia_chi || 'Chưa có';
    openDialog('viewDialog');
}

// Edit employee
function editEmployee(data) {
    document.getElementById('edit_id').value = data.nhan_vien_id || '';
    document.getElementById('edit_tenDangNhap').value = data.ten_dang_nhap || '';
    document.getElementById('edit_hoTen').value = data.ho_ten || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_sdt').value = data.sdt || '';
    document.getElementById('edit_cccd').value = data.cccd || '';
    document.getElementById('edit_ngaySinh').value = data.ngay_sinh || '';
    document.getElementById('edit_gioiTinh').value = data.gioi_tinh || 'Nam';
    document.getElementById('edit_phongTapId').value = data.phong_tap_id || '';
    document.getElementById('edit_vaiTro').value = data.vai_tro || 'Lễ Tân';
    document.getElementById('edit_ngayVaoLam').value = data.ngay_vao_lam || '';
    document.getElementById('edit_luongCoBan').value = data.luong_co_ban || '';
    document.getElementById('edit_trangThai').value = data.trang_thai || 'Đang làm';
    document.getElementById('edit_diaChi').value = data.dia_chi || '';
    openDialog('editDialog');
}

// Delete employee with confirmation
function deleteEmployee(id) {
    if (confirm('⚠️ Bạn có chắc chắn muốn xóa nhân viên này?\n\nLưu ý: Thao tác này không thể hoàn tác!')) {
        window.location.href = '?delete=' + id;
    }
}

// Format date (YYYY-MM-DD to DD/MM/YYYY)
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Format datetime (YYYY-MM-DD HH:MM:SS to DD/MM/YYYY HH:MM)
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

// Format currency (VND)
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// =================== CHẤM CÔNG ===================
let currentNhanVienId = null;
let currentEmployeeName = '';

// Mở dialog chấm công
function viewAttendance(nhanVienId) {
    console.log('viewAttendance called with ID:', nhanVienId);
    
    currentNhanVienId = nhanVienId;
    
    const nhanVienInput = document.getElementById("cc_nhanVienId");
    if (nhanVienInput) {
        nhanVienInput.value = nhanVienId;
    }
    
    const rows = document.querySelectorAll('tbody tr');
    for (let row of rows) {
        const idCell = row.querySelector('td:first-child');
        if (idCell && idCell.textContent.trim() == nhanVienId) {
            const nameCell = row.querySelector('td:nth-child(3)');
            if (nameCell) {
                currentEmployeeName = nameCell.textContent.trim();
                break;
            }
        }
    }
    
    const employeeNameEl = document.getElementById("cc_employee_name");
    if (employeeNameEl) {
        employeeNameEl.innerText = currentEmployeeName || "Nhân viên #" + nhanVienId;
    }
    
    openDialog("viewChamCongDialog");
    
    loadChamCong(nhanVienId);
}

// Load dữ liệu chấm công
function loadChamCong(nhanVienId) {
    console.log('loadChamCong called with ID:', nhanVienId);
    
    const tableBody = document.getElementById('chamCongTableBody');
    if (!tableBody) {
        console.error('Không tìm thấy element chamCongTableBody');
        return;
    }
    
    tableBody.innerHTML = `<tr><td colspan="7" class="no-data">⏳ Đang tải dữ liệu...</td></tr>`;

    fetch(`managment_function.php?nhan_vien_id=${nhanVienId}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            
            if (!Array.isArray(data) || data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7" class="no-data">Không có dữ liệu chấm công</td></tr>`;
                return;
            }

            tableBody.innerHTML = data.map(row => `
                <tr>
                    <td>${row.cham_cong_id}</td>
                    <td>${formatDate(row.ngay_cham_cong)}</td>
                    <td>${row.gio_vao || '-'}</td>
                    <td>${row.gio_ra || '-'}</td>
                    <td>${row.so_gio_lam || '0'}</td>
                    <td>${renderStatusBadge(row.trang_thai)}</td>
                    <td>${row.ghi_chu || ''}</td>
                </tr>
            `).join('');
        })
        .catch(err => {
            console.error('Error loading attendance:', err);
            tableBody.innerHTML = `<tr><td colspan="7" class="no-data">❌ Lỗi tải dữ liệu: ${err.message}</td></tr>`;
        });
}

// Render status as colored badge
function renderStatusBadge(status) {
    if (!status) return '';
    const s = status.toLowerCase();
    if (s.includes('có mặt')) {
        return `<span class="status-badge success">${status}</span>`;
    }
    if (s.includes('nghỉ')) {
        return `<span class="status-badge warning">${status}</span>`;
    }
    if (s.includes('muộn')) {
        return `<span class="status-badge danger">${status}</span>`;
    }
    return `<span class="badge">${status}</span>`;
}

// Show inline toast in attendance dialog
function showCcToast(message, success=true) {
    const container = document.getElementById('cc_toast_container');
    if (!container) {
        alert(message);
        return;
    }
    
    container.innerHTML = '';
    const el = document.createElement('div');
    el.className = 'cc-toast ' + (success ? 'success' : 'error');
    el.textContent = message;
    container.appendChild(el);
    
    setTimeout(() => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 3000);
}

// Submit add attendance form via AJAX
function submitAddChamCong(e) {
    e.preventDefault();
    
    const form = document.getElementById('addChamCongForm');
    if (!form) {
        console.error('Không tìm thấy form addChamCongForm');
        return false;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'add_chamcong');

    const nhanVienId = formData.get('nhanVienId');
    
    console.log('Submitting attendance for employee:', nhanVienId);

    fetch('managment_function.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(json => {
        console.log('Response:', json);
        
        if (json.success) {
            loadChamCong(nhanVienId);
            
            showCcToast(json.message || 'Thêm chấm công thành công', true);
            
            document.getElementById('cc_ngayChamCong').value = '';
            document.getElementById('cc_gioVao').value = '';
            document.getElementById('cc_gioRa').value = '';
            document.getElementById('cc_ghiChu').value = '';
            document.getElementById('cc_trangThai').value = 'Có mặt';
        } else {
            showCcToast(json.message || 'Lỗi khi thêm chấm công', false);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showCcToast('❌ Lỗi kết nối', false);
    });
    
    return false;
}

// =================== BẢNG LƯƠNG ===================

// Mở dialog bảng lương
function openBangLuongDialog() {
    console.log('openBangLuongDialog called, currentNhanVienId:', currentNhanVienId);
    
    if (!currentNhanVienId) {
        alert("⚠️ Vui lòng chọn nhân viên trước!");
        return;
    }
    
    const blEmployeeName = document.getElementById("bl_employee_name");
    if (blEmployeeName) {
        blEmployeeName.innerText = currentEmployeeName || "Nhân viên #" + currentNhanVienId;
    }
    
    const now = new Date();
    const thangInput = document.getElementById("bl_thang");
    const namInput = document.getElementById("bl_nam");
    
    if (thangInput) thangInput.value = now.getMonth() + 1;
    if (namInput) namInput.value = now.getFullYear();
    
    openDialog("viewBangLuongDialog");
    
    setTimeout(() => {
        loadBangLuong();
    }, 100);
}

// Load dữ liệu bảng lương
function loadBangLuong() {
    console.log('loadBangLuong called');
    
    const thang = document.getElementById("bl_thang").value;
    const nam = document.getElementById("bl_nam").value;

    if (!thang || !nam) {
        alert("⚠️ Vui lòng nhập tháng và năm!");
        return;
    }

    if (thang < 1 || thang > 12) {
        alert("⚠️ Tháng phải từ 1 đến 12!");
        return;
    }
    
    if (!currentNhanVienId) {
        alert("⚠️ Không có thông tin nhân viên!");
        return;
    }

    console.log('Fetching salary for:', {nhanVienId: currentNhanVienId, thang, nam});

    const tbody = document.getElementById("bangLuongTableBody");
    if (!tbody) {
        console.error('Không tìm thấy element bangLuongTableBody');
        return;
    }
    
    tbody.innerHTML = `
        <tr>
            <td colspan="8" style="padding: 40px; text-align: center; color: #6c757d;">
                <div style="font-size: 48px; margin-bottom: 10px;" class="loading-pulse">⏳</div>
                <div style="font-size: 16px; font-weight: 500;">Đang tải dữ liệu...</div>
            </td>
        </tr>
    `;

    fetch("managment_function.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: new URLSearchParams({
            action: 'get_bangluong',
            nhanVienId: currentNhanVienId,
            thang: thang,
            nam: nam
        })
    })
    .then(res => {
        console.log('Response status:', res.status);
        if (!res.ok) throw new Error('Network error');
        return res.json();
    })
    .then(data => {
        console.log('Salary data received:', data);
        tbody.innerHTML = "";

        if (data.success && data.rows && data.rows.length > 0) {
            data.rows.forEach(row => {
                const tr = document.createElement("tr");
                
                let trangThaiHtml = '';
                let actionButtonHtml = '';
                
                if (row.trang_thai === 'Đã thanh toán') {
                    trangThaiHtml = '<span class="status-badge success">✅ Đã thanh toán</span>';
                    actionButtonHtml = '<button class="btn-paid" disabled style="opacity: 0.5; cursor: not-allowed;">Đã thanh toán</button>';
                } else {
                    trangThaiHtml = '<span class="status-badge warning">⏳ Chưa thanh toán</span>';
                    actionButtonHtml = `<button class="btn-pay" onclick="thanhToanLuong(${row.bang_luong_id})">💰 Thanh toán</button>`;
                }
                
                tr.innerHTML = `
                    <td style="text-align: center; font-weight: 600;">${row.thang}</td>
                    <td style="text-align: center; font-weight: 600;">${row.nam}</td>
                    <td style="text-align: right; color: #66bb6a; font-weight: 500;">${Number(row.luong).toLocaleString("vi-VN")} ₫</td>
                    <td style="text-align: right; color: #4fc3f7; font-weight: 500;">${Number(row.thuong).toLocaleString("vi-VN")} ₫</td>
                    <td style="text-align: right; color: #ef5350; font-weight: 500;">${Number(row.khau_tru).toLocaleString("vi-VN")} ₫</td>
                    <td style="text-align: right; font-weight: 700; color: #ab47bc; font-size: 15px;">${Number(row.thuc_linh).toLocaleString("vi-VN")} ₫</td>
                    <td style="text-align: center;">${trangThaiHtml}</td>
                    <td style="text-align: center;">${actionButtonHtml}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="padding: 40px; text-align: center; color: #6c757d;">
                        <div style="font-size: 48px; margin-bottom: 10px;">🔭</div>
                        <div style="font-size: 16px; font-weight: 500;">Không có dữ liệu lương</div>
                        <div style="font-size: 13px; color: #adb5bd; margin-top: 5px;">Tháng ${thang}/${nam} chưa có bản ghi nào</div>
                    </td>
                </tr>
            `;
        }
    })
    .catch(err => {
        console.error('Error loading salary:', err);
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="padding: 40px; text-align: center; color: #dc3545;">
                    <div style="font-size: 48px; margin-bottom: 10px;">❌</div>
                    <div style="font-size: 16px; font-weight: 500;">Lỗi khi tải dữ liệu</div>
                    <div style="font-size: 13px; color: #adb5bd; margin-top: 5px;">Vui lòng thử lại sau</div>
                </td>
            </tr>
        `;
    });
}

// Thanh toán lương
function thanhToanLuong(bangLuongId) {
    if (!confirm('💰 Xác nhận thanh toán lương cho nhân viên này?')) {
        return;
    }

    console.log('Thanh toán lương ID:', bangLuongId);

    fetch("managment_function.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: new URLSearchParams({
            action: 'thanh_toan_luong',
            bangLuongId: bangLuongId
        })
    })
    .then(res => res.json())
    .then(data => {
        console.log('Payment response:', data);
        
        if (data.success) {
            alert(data.message);
            // Reload lại bảng lương
            loadBangLuong();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('❌ Lỗi khi thanh toán: ' + err.message);
    });
}