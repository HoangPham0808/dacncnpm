// Biến lưu trữ dữ liệu
let customers = [];
let staff = [];
let packages = [];
let promotions = [];

// Khởi tạo khi trang load
document.addEventListener('DOMContentLoaded', function() {
    // Load danh sách khách hàng và khuyến mãi
    loadCustomers();
    loadpackage();
    loadStaff();
    loadPromotions();
    
    // Set ngày hiện tại cho input ngày lập
    const today = new Date().toISOString().split('T')[0];
    const ngayLapInput = document.getElementById('ngay_lap');
    if (ngayLapInput) {
        ngayLapInput.value = today;
    }
    
    // Xử lý submit form
    const form = document.getElementById('invoiceForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
    
    // Xử lý thay đổi khách hàng - lọc nhân viên cùng phòng
    const khachHangSelect = document.getElementById('khach_hang_id');
    if (khachHangSelect) {
        khachHangSelect.addEventListener('change', handleCustomerChange);
    }
    
    // Xử lý thay đổi nhân viên - lọc khách hàng cùng phòng
    const nhanVienSelect = document.getElementById('nhan_vien_id');
    if (nhanVienSelect) {
        nhanVienSelect.addEventListener('change', handleStaffChange);
    }
});

// Xử lý khi chọn khách hàng - lọc nhân viên cùng phòng
async function handleCustomerChange(e) {
    const khachHangId = e.target.value;
    
    if (!khachHangId) {
        // Reset danh sách nhân viên về ban đầu
        loadStaff();
        return;
    }
    
    try {
        const response = await fetch(`managment_function.php?action=getStaffByCustomer&khach_hang_id=${khachHangId}`);
        const result = await response.json();
        
        if (result && result.success && Array.isArray(result.data)) {
            const nhanVienSelect = document.getElementById('nhan_vien_id');
            if (nhanVienSelect) {
                const currentValue = nhanVienSelect.value;
                nhanVienSelect.innerHTML = '<option value="">-- Chọn nhân viên --</option>';
                
                result.data.forEach(nv => {
                    const option = document.createElement('option');
                    option.value = nv.nhan_vien_id;
                    option.textContent = nv.ho_ten;
                    nhanVienSelect.appendChild(option);
                });
                
                // Giữ lại giá trị đã chọn nếu còn trong danh sách
                if (currentValue) {
                    const stillExists = result.data.some(nv => nv.nhan_vien_id == currentValue);
                    if (stillExists) {
                        nhanVienSelect.value = currentValue;
                    }
                }
            }
        } else {
            showAlert('Không tìm thấy nhân viên cùng phòng tập với khách hàng này', 'warning');
            const nhanVienSelect = document.getElementById('nhan_vien_id');
            if (nhanVienSelect) {
                nhanVienSelect.innerHTML = '<option value="">-- Không có nhân viên --</option>';
            }
        }
    } catch (error) {
        console.error('Lỗi lấy danh sách nhân viên:', error);
    }
}

// Xử lý khi chọn nhân viên - lọc khách hàng cùng phòng
async function handleStaffChange(e) {
    const nhanVienId = e.target.value;
    
    if (!nhanVienId) {
        // Reset danh sách khách hàng về ban đầu
        loadCustomers();
        return;
    }
    
    try {
        const response = await fetch(`managment_function.php?action=getCustomersByStaff&nhan_vien_id=${nhanVienId}`);
        const result = await response.json();
        
        if (result && result.success && Array.isArray(result.data)) {
            const khachHangSelect = document.getElementById('khach_hang_id');
            if (khachHangSelect) {
                const currentValue = khachHangSelect.value;
                khachHangSelect.innerHTML = '<option value="">-- Chọn khách hàng --</option>';
                
                result.data.forEach(kh => {
                    const option = document.createElement('option');
                    option.value = kh.khach_hang_id;
                    option.textContent = kh.ho_ten;
                    khachHangSelect.appendChild(option);
                });
                
                // Giữ lại giá trị đã chọn nếu còn trong danh sách
                if (currentValue) {
                    const stillExists = result.data.some(kh => kh.khach_hang_id == currentValue);
                    if (stillExists) {
                        khachHangSelect.value = currentValue;
                    }
                }
            }
        } else {
            showAlert('Không tìm thấy khách hàng cùng phòng tập với nhân viên này', 'warning');
            const khachHangSelect = document.getElementById('khach_hang_id');
            if (khachHangSelect) {
                khachHangSelect.innerHTML = '<option value="">-- Không có khách hàng --</option>';
            }
        }
    } catch (error) {
        console.error('Lỗi lấy danh sách khách hàng:', error);
    }
}

// Load danh sách khách hàng
async function loadCustomers() {
    try {
        const response = await fetch('managment_function.php?action=getCustomers');
        
        if (!response.ok) {
            console.error('HTTP error:', response.status);
            showAlert('Không thể kết nối tới server', 'error');
            return;
        }
        
        const text = await response.text();
        console.debug('Customers raw response:', text);
        
        if (!text || text.trim() === '') {
            console.error('Empty response from server');
            showAlert('Server trả về dữ liệu rỗng', 'error');
            return;
        }
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            showAlert('Lỗi định dạng dữ liệu từ server. Kiểm tra console để xem chi tiết.', 'error');
            return;
        }
        
        console.debug('Customers response:', result);
        
        if (result && result.success && Array.isArray(result.data)) {
            customers = result.data;
            const select = document.getElementById('khach_hang_id');
            if (select) {
                select.innerHTML = '<option value="">-- Chọn khách hàng --</option>';
                
                customers.forEach(customer => {
                    const option = document.createElement('option');
                    option.value = customer.khach_hang_id;
                    option.textContent = customer.ho_ten;
                    if (customer.ten_phong_tap) {
                        option.textContent += ` (${customer.ten_phong_tap})`;
                    }
                    select.appendChild(option);
                });
                
                console.debug(`Đã load ${customers.length} khách hàng`);
            }
        } else {
            console.error('Không load được khách hàng:', result);
            showAlert('Không thể tải danh sách khách hàng', 'error');
        }
    } catch (error) {
        console.error('Lỗi load khách hàng:', error);
        showAlert('Lỗi kết nối: ' + (error.message || error), 'error');
    }
}

async function loadpackage() {
    try {
        const response = await fetch('managment_function.php?action=getpackage');
        
        if (!response.ok) {
            console.error('HTTP error:', response.status);
            showAlert('Không thể kết nối tới server', 'error');
            return;
        }
        
        const text = await response.text();
        console.debug('Packages raw response:', text);
        
        if (!text || text.trim() === '') {
            console.error('Empty response from server');
            showAlert('Server trả về dữ liệu rỗng', 'error');
            return;
        }
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            showAlert('Lỗi định dạng dữ liệu từ server. Kiểm tra console để xem chi tiết.', 'error');
            return;
        }
        
        console.debug('Package response:', result);
        
        if (result && result.success && Array.isArray(result.data)) {
            packages = result.data;
            const select = document.getElementById('goi_tap_id');
            if (select) {
                select.innerHTML = '<option value="">-- Chọn gói tập --</option>';
                
                packages.forEach(pkg => {
                    const option = document.createElement('option');
                    option.value = pkg.goi_tap_id;
                    option.textContent = pkg.ten_goi;
                    select.appendChild(option);
                });
                
                console.debug(`Đã load ${packages.length} gói tập`);
            }
        } else {
            console.error('Không load được gói tập:', result);
            showAlert('Không thể tải danh sách gói tập', 'error');
        }
    } catch (error) {
        console.error('Lỗi load gói tập:', error);
        showAlert('Lỗi kết nối: ' + (error.message || error), 'error');
    }
}

async function loadStaff() {
    try {
        const response = await fetch('managment_function.php?action=getstaff');
        
        if (!response.ok) {
            console.error('HTTP error:', response.status);
            showAlert('Không thể kết nối tới server', 'error');
            return;
        }
        
        const text = await response.text();
        console.debug('Staff raw response:', text);
        
        if (!text || text.trim() === '') {
            console.error('Empty response from server');
            showAlert('Server trả về dữ liệu rỗng', 'error');
            return;
        }
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            showAlert('Lỗi định dạng dữ liệu từ server. Kiểm tra console để xem chi tiết.', 'error');
            return;
        }
        
        console.debug('Staff response:', result);
        
        if (result && result.success && Array.isArray(result.data)) {
            staff = result.data;
            const select = document.getElementById('nhan_vien_id');
            if (select) {
                select.innerHTML = '<option value="">-- Chọn nhân viên --</option>';
                staff.forEach(s => {
                    const option = document.createElement('option');
                    option.value = s.nhan_vien_id;
                    option.textContent = s.ho_ten;
                    if (s.ten_phong_tap) {
                        option.textContent += ` (${s.ten_phong_tap})`;
                    }
                    select.appendChild(option);
                });
                
                console.debug(`Đã load ${staff.length} nhân viên`);
            }
        } else {
            console.error('Không load được nhân viên:', result);
            showAlert('Không thể tải danh sách nhân viên', 'error');
        }
    } catch (error) {
        console.error('Lỗi load nhân viên:', error);
        showAlert('Lỗi kết nối: ' + (error.message || error), 'error');
    }
}

// Load danh sách khuyến mãi
async function loadPromotions() {
    try {
        const response = await fetch('managment_function.php?action=getPromotions');
        
        if (!response.ok) {
            console.error('HTTP error:', response.status);
            return;
        }
        
        const text = await response.text();
        console.debug('Promotions raw response:', text);
        
        if (!text || text.trim() === '') {
            console.debug('Empty response from promotions');
            return;
        }
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            return;
        }
        
        console.debug('Promotions response:', result);
        
        if (result && result.success && Array.isArray(result.data)) {
            promotions = result.data;
            const select = document.getElementById('khuyen_mai_id');
            if (select) {
                select.innerHTML = '<option value="">-- Không áp dụng --</option>';
                
                promotions.forEach(promotion => {
                    const option = document.createElement('option');
                    option.value = promotion.khuyen_mai_id;
                    option.textContent = `${promotion.ten_khuyen_mai} (${promotion.gia_tri_giam}%)`;
                    select.appendChild(option);
                });
                
                console.debug(`Đã load ${promotions.length} khuyến mãi`);
            }
        } else {
            console.debug('Không có dữ liệu khuyến mãi hoặc server trả về lỗi.', result);
        }
    } catch (error) {
        console.error('Lỗi load khuyến mãi:', error);
    }
}

// Mở modal thêm mới
function openAddModal() {
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('invoiceForm');
    const hoaDonId = document.getElementById('hoa_don_id');
    const ngayLap = document.getElementById('ngay_lap');

    if (modalTitle) modalTitle.textContent = 'Thêm hóa đơn mới';
    if (form) form.reset();
    if (hoaDonId) hoaDonId.value = '';
    
    // Set ngày hiện tại
    const today = new Date().toISOString().split('T')[0];
    if (ngayLap) ngayLap.value = today;
    
    // Tự động tạo mã hóa đơn
    generateInvoiceCode();
    
    // Load lại danh sách nếu chưa có
    if (customers.length === 0) {
        loadCustomers();
    }
    if (packages.length === 0) {
        loadpackage();
    }
    if (staff.length === 0) {
        loadStaff();
    }
    if (promotions.length === 0) {
        loadPromotions();
    }
    
    const invoiceModal = document.getElementById('invoiceModal');
    if (invoiceModal) invoiceModal.style.display = 'block';
}

// Tạo mã hóa đơn tự động
function generateInvoiceCode() {
    const prefix = 'HD';
    const timestamp = Date.now().toString().slice(-8);
    const random = Math.floor(Math.random() * 100).toString().padStart(2, '0');
    const maInput = document.getElementById('ma_hoa_don');
    if (maInput) {
        maInput.value = `${prefix}${timestamp}${random}`;
    }
}

// Đóng modal
function closeModal() {
    const invoiceModal = document.getElementById('invoiceModal');
    const form = document.getElementById('invoiceForm');
    if (invoiceModal) invoiceModal.style.display = 'none';
    if (form) form.reset();
}

// Đóng modal chi tiết
function closeViewModal() {
    const viewModal = document.getElementById('viewModal');
    if (viewModal) viewModal.style.display = 'none';
}

// Click outside modal để đóng
window.onclick = function(event) {
    const invoiceModal = document.getElementById('invoiceModal');
    const viewModal = document.getElementById('viewModal');
    
    if (invoiceModal && event.target === invoiceModal) {
        closeModal();
    }
    if (viewModal && event.target === viewModal) {
        closeViewModal();
    }
}

// Xử lý submit form
// ================ THAY THẾ HÀM handleFormSubmit TRONG management_invoice.js ================

// Xử lý submit form - Chuyển sang modal thanh toán thay vì submit trực tiếp
// Xử lý submit form - Tính toán từ server trước khi mở modal
// ============== THAY THẾ HÀM handleFormSubmit ==============
// Tìm và thay thế toàn bộ hàm handleFormSubmit cũ bằng hàm này:

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    if (!form) return;
    
    // Validate
    const khachHangId = document.getElementById('khach_hang_id').value;
    const goitapId = document.getElementById('goi_tap_id').value;
    const nhanvienId = document.getElementById('nhan_vien_id').value;
    const maHoaDon = document.getElementById('ma_hoa_don').value;
    const ngayLap = document.getElementById('ngay_lap').value;
    
    if (!khachHangId) {
        showAlert('Vui lòng chọn khách hàng', 'error');
        return;
    }
    if (!goitapId) {
        showAlert('Vui lòng chọn gói tập', 'error');
        return;
    }
    if (!nhanvienId) {
        showAlert('Vui lòng chọn nhân viên lập hóa đơn', 'error');
        return;
    }
    if (!maHoaDon) {
        showAlert('Vui lòng nhập mã hóa đơn', 'error');
        return;
    }
    if (!ngayLap) {
        showAlert('Vui lòng chọn ngày lập', 'error');
        return;
    }
    
    // Thu thập dữ liệu form
    const invoiceData = {
        hoa_don_id: document.getElementById('hoa_don_id').value,
        ma_hoa_don: maHoaDon,
        khach_hang_id: khachHangId,
        goi_tap_id: goitapId,
        nhan_vien_id: nhanvienId,
        ngay_lap: ngayLap,
        khuyen_mai_id: document.getElementById('khuyen_mai_id').value || '',
        giam_gia_khac: document.getElementById('giam_gia_khac').value || 0,
        ghi_chu: document.getElementById('ghi_chu').value || ''
    };
    
    // Gọi API để tính toán chính xác từ server
    try {
        showAlert('Đang tính toán...', 'info');
        
        const formData = new FormData();
        for (let key in invoiceData) {
            formData.append(key, invoiceData[key]);
        }
        formData.append('action', 'calculateInvoice');
        
        const response = await fetch('managment_function.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result && result.success) {
            // Thêm thông tin tính toán vào invoiceData
            invoiceData.calculated = {
                tong_tien: result.data.tong_tien,
                giam_gia_km: result.data.giam_gia_km,
                giam_gia_khac: result.data.giam_gia_khac,
                tien_thanh_toan: result.data.tien_thanh_toan,
                ten_goi: result.data.ten_goi
            };
            
            // Mở modal thanh toán với dữ liệu đã tính
            openPaymentModal(invoiceData);
        } else {
            showAlert(result.message || 'Lỗi tính toán hóa đơn', 'error');
        }
    } catch (error) {
        console.error('Lỗi:', error);
        showAlert('Có lỗi xảy ra khi tính toán: ' + error.message, 'error');
    }
}
// Các hàm khác (viewInvoice, editInvoice, deleteInvoice, printInvoice, formatCurrency, formatDate, showAlert)
// ... (giữ nguyên như code cũ)
// Xem chi tiết hóa đơn
async function viewInvoice(id) {
    try {
        console.log('Đang tải chi tiết hóa đơn ID:', id);
        const response = await fetch(`managment_function.php?action=getInvoiceDetail&id=${id}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Response text:', text);
        
        let result;
        
        try {
            result = JSON.parse(text);
            console.log('Parsed result:', result);
        } catch (e) {
            console.error('Response không phải JSON:', text);
            console.error('Parse error:', e);
            throw new Error('Server trả về dữ liệu không hợp lệ');
        }
        
        if (result && result.success === true && result.data) {
            const invoice = result.data;
            
            // Xác định class trạng thái
            let statusClass = '';
            switch (invoice.trang_thai) {
                case 'Đã thanh toán':
                    statusClass = 'status-paid';
                    break;
                case 'Chờ thanh toán':
                    statusClass = 'status-pending';
                    break;
                case 'Hủy':
                    statusClass = 'status-cancelled';
                    break;
            }
            
            const detailHTML = `
                <div class="detail-section">
                    <h3>Thông tin hóa đơn</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Mã hóa đơn:</label>
                            <span>${invoice.ma_hoa_don || ''}</span>
                        </div>
                        <div class="detail-item">
                            <label>Ngày lập:</label>
                            <span>${formatDate(invoice.ngay_lap)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Khách hàng:</label>
                            <span>${invoice.ten_khach_hang || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span>${invoice.email_khach_hang || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Số điện thoại:</label>
                            <span>${invoice.sdt_khach_hang || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Nhân viên lập:</label>
                            <span>${invoice.ten_nhan_vien || 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Thông tin thanh toán</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Tổng tiền:</label>
                            <span>${formatCurrency(invoice.tong_tien)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Khuyến mãi:</label>
                            <span>${invoice.ten_khuyen_mai || 'Không áp dụng'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Giảm giá KM:</label>
                            <span>${formatCurrency(invoice.giam_gia_khuyen_mai)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Giảm giá khác:</label>
                            <span>${formatCurrency(invoice.giam_gia_khac)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Phương thức thanh toán:</label>
                            <span>${invoice.phuong_thuc_thanh_toan || ''}</span>
                        </div>
                        <div class="detail-item">
                            <label>Trạng thái:</label>
                            <span class="status-badge ${statusClass}">${invoice.trang_thai || ''}</span>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <label>Tiền thanh toán:</label>
                            <span class="highlight">${formatCurrency(invoice.tien_thanh_toan)}</span>
                        </div>
                    </div>
                </div>

                ${invoice.ghi_chu ? `
                <div class="detail-section">
                    <h3>Ghi chú</h3>
                    <p style="color: rgba(255,255,255,0.8); line-height: 1.6;">${invoice.ghi_chu}</p>
                </div>
                ` : ''}

                <div class="detail-actions">
                    <button class="btn btn-secondary" onclick="closeViewModal()">Đóng</button>
                    <button class="btn btn-primary" onclick="closeViewModal(); editInvoice(${encodeURIComponent(id)});">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <div class="export-dropdown" style="position: relative; display: inline-block;">
                        <button class="btn btn-primary dropdown-toggle" onclick="toggleExportMenu(event)">
                            <i class="fas fa-download"></i> Xuất <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="export-menu" style="display: none;">
                            <a href="#" onclick="printInvoice(${encodeURIComponent(id)}); return false;">
                                <i class="fas fa-print"></i> In hóa đơn (PDF)
                            </a>
                            <a href="#" onclick="exportSingleInvoice(${encodeURIComponent(id)}); return false;">
                                <i class="fas fa-file-excel"></i> Xuất Excel (1 HĐ)
                            </a>
                        </div>
                    </div>
                </div>
            `;
            
            const container = document.getElementById('invoiceDetail');
            if (container) {
                container.innerHTML = detailHTML;
            }
            const viewModal = document.getElementById('viewModal');
            if (viewModal) viewModal.style.display = 'block';
        } else {
            const errorMsg = result && result.message ? result.message : 'Không tìm thấy thông tin hóa đơn';
            console.error('Không tìm thấy dữ liệu:', result);
            showAlert(errorMsg, 'error');
        }
    } catch (error) {
        console.error('Lỗi khi tải chi tiết hóa đơn:', error);
        showAlert('Có lỗi xảy ra khi tải chi tiết hóa đơn: ' + (error.message || error), 'error');
    }
}

// Sửa hóa đơn
async function editInvoice(id) {
    try {
        const response = await fetch(`managment_function.php?action=getInvoiceDetail&id=${encodeURIComponent(id)}`);
        const result = await response.json();
        
        if (result && result.success && result.data) {
            const invoice = result.data;
            
            // Load danh sách trước
            await loadCustomers();
            await loadpackage();
            await loadStaff();
            await loadPromotions();
            
            const modalTitle = document.getElementById('modalTitle');
            if (modalTitle) modalTitle.textContent = 'Sửa hóa đơn';
            if (document.getElementById('hoa_don_id')) document.getElementById('hoa_don_id').value = invoice.hoa_don_id || '';
            if (document.getElementById('ma_hoa_don')) document.getElementById('ma_hoa_don').value = invoice.ma_hoa_don || '';
            if (document.getElementById('khach_hang_id')) document.getElementById('khach_hang_id').value = invoice.khach_hang_id || '';
            if (document.getElementById('goi_tap_id')) document.getElementById('goi_tap_id').value = invoice.goi_tap_id || '';
            if (document.getElementById('nhan_vien_id')) document.getElementById('nhan_vien_id').value = invoice.nhan_vien_id || '';
            if (document.getElementById('ngay_lap')) document.getElementById('ngay_lap').value = invoice.ngay_lap || '';
            if (document.getElementById('khuyen_mai_id')) document.getElementById('khuyen_mai_id').value = invoice.khuyen_mai_id || '';
            if (document.getElementById('giam_gia_khac')) document.getElementById('giam_gia_khac').value = invoice.giam_gia_khac || 0;
            if (document.getElementById('phuong_thuc_thanh_toan')) document.getElementById('phuong_thuc_thanh_toan').value = invoice.phuong_thuc_thanh_toan || '';
            if (document.getElementById('trang_thai')) document.getElementById('trang_thai').value = invoice.trang_thai || '';
            if (document.getElementById('ghi_chu')) document.getElementById('ghi_chu').value = invoice.ghi_chu || '';
            
            const invoiceModal = document.getElementById('invoiceModal');
            if (invoiceModal) invoiceModal.style.display = 'block';
        } else {
            showAlert('Không tìm thấy thông tin hóa đơn', 'error');
        }
    } catch (error) {
        console.error('Lỗi:', error);
        showAlert('Có lỗi xảy ra khi tải thông tin hóa đơn', 'error');
    }
}

// Xóa hóa đơn
async function deleteInvoice(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa hóa đơn này?')) {
        return;
    }
    
    try {
        const response = await fetch(`managment_function.php?action=delete&id=${encodeURIComponent(id)}`);
        const result = await response.json();
        
        if (result && result.success) {
            showAlert(result.message || 'Xóa thành công', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } else {
            showAlert((result && result.message) || 'Xóa thất bại', 'error');
        }
    } catch (error) {
        console.error('Lỗi:', error);
        showAlert('Có lỗi xảy ra khi xóa hóa đơn', 'error');
    }
}

// In hóa đơn
function printInvoice(id) {
    window.open(`print_invoice.php?id=${encodeURIComponent(id)}`, '_blank');
}

// Reset filter
function resetFilter() {
    window.location.href = 'management_invoice.php';
}

// Format tiền tệ
function formatCurrency(amount) {
    const num = Number(amount) || 0;
    try {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(num);
    } catch (e) {
        return num.toLocaleString('vi-VN') + ' ₫';
    }
}

// Format ngày
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Hiển thị thông báo
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? 'rgba(57, 217, 138, 0.9)' : type === 'warning' ? 'rgba(255, 193, 7, 0.9)' : 'rgba(255, 48, 64, 0.9)'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
        font-size: 14px;
        font-weight: 600;
    `;
    const icon = type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle';
    alertDiv.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
    
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

// Thêm CSS cho animation
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
function exportAllInvoices() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    params.append('action', 'exportExcel');
    
    // Hiển thị thông báo
    showAlert('Đang xuất file Excel...', 'info');
    
    // Mở link download
    window.location.href = 'managment_function.php?' + params.toString();
}
function toggleExportMenu(event) {
    event.stopPropagation();
    const menu = event.currentTarget.nextElementSibling;
    
    // Đóng tất cả menu khác
    document.querySelectorAll('.export-menu').forEach(m => {
        if (m !== menu) m.style.display = 'none';
    });
    
    // Toggle menu hiện tại
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

// Đóng menu khi click ra ngoài
document.addEventListener('click', function() {
    document.querySelectorAll('.export-menu').forEach(m => {
        m.style.display = 'none';
    });
});

// Xuất 1 hóa đơn cụ thể ra Excel
function exportSingleInvoice(id) {
    showAlert('Đang xuất hóa đơn...', 'info');
    window.location.href = `managment_function.php?action=exportSingleInvoice&id=${encodeURIComponent(id)}`;
}
// ================ THÊM VÀO CUỐI FILE management_invoice.js ================

// Biến lưu trữ dữ liệu thanh toán tạm
let pendingInvoiceData = null;

// Mở modal thanh toán
function openPaymentModal(invoiceFormData) {
    pendingInvoiceData = invoiceFormData;
    
    // Hiển thị thông tin hóa đơn trong modal thanh toán
    document.getElementById('payment_ma_hoa_don').textContent = invoiceFormData.ma_hoa_don || '';
    document.getElementById('payment_khach_hang').textContent = getCustomerName(invoiceFormData.khach_hang_id) || '';
    
    // Sử dụng dữ liệu đã được tính toán từ server
    if (invoiceFormData.calculated) {
        document.getElementById('payment_goi_tap').textContent = invoiceFormData.calculated.ten_goi || '';
        document.getElementById('payment_tien_thanh_toan').textContent = formatCurrency(invoiceFormData.calculated.tien_thanh_toan);
    } else {
        // Fallback (không nên xảy ra nếu dùng cách mới)
        document.getElementById('payment_goi_tap').textContent = getPackageName(invoiceFormData.goi_tap_id) || '';
        document.getElementById('payment_tien_thanh_toan').textContent = '0₫';
    }
    
    // Mặc định chọn Ngân hàng
    selectPaymentMethod('bank');
    
    // Hiển thị modal thanh toán
    document.getElementById('paymentModal').style.display = 'block';
    
    // Đóng modal thêm hóa đơn
    closeModal();
}

// Đóng modal thanh toán
function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    pendingInvoiceData = null;
}

// Chọn phương thức thanh toán
function selectPaymentMethod(methodId) {
    // Bỏ chọn tất cả
    document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Chọn method hiện tại
    const selectedCard = document.querySelector(`[data-method="${methodId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }
    
    // Ẩn tất cả payment details
    document.querySelectorAll('.payment-details').forEach(detail => {
        detail.style.display = 'none';
    });
    
    // Hiển thị detail tương ứng
    const detailSection = document.getElementById(`${methodId}_details`);
    if (detailSection) {
        detailSection.style.display = 'block';
    }
    
    // Nếu chọn Tiền mặt, tự động chuyển trạng thái sang "Đã thanh toán"
    if (methodId === 'cash') {
        document.getElementById('payment_trang_thai').value = 'Đã thanh toán';
        document.getElementById('payment_trang_thai').disabled = true;
        showAlert('Thanh toán tiền mặt sẽ tự động đánh dấu là "Đã thanh toán"', 'info');
    } else {
        document.getElementById('payment_trang_thai').disabled = false;
        document.getElementById('payment_trang_thai').value = 'Chờ thanh toán';
    }
}
async function confirmPayment() {
    if (!pendingInvoiceData) {
        showAlert('Không có dữ liệu hóa đơn', 'error');
        return;
    }
    const selectedCard = document.querySelector('.payment-method-card.selected');
    if (!selectedCard) {
        showAlert('Vui lòng chọn phương thức thanh toán', 'error');
        return;
    }
    const methodId = selectedCard.getAttribute('data-method');
    let phuong_thuc_thanh_toan = '';
    
    switch(methodId) {
        case 'bank':
            phuong_thuc_thanh_toan = 'Chuyển khoản';
            break;
        case 'momo':
            phuong_thuc_thanh_toan = 'Ví điện tử';
            break;
        case 'zalopay':
            phuong_thuc_thanh_toan = 'Ví điện tử';
            break;
        case 'card':
            phuong_thuc_thanh_toan = 'Thẻ';
            break;
        case 'cash':
            phuong_thuc_thanh_toan = 'Tiền mặt';
            break;
        default:
            phuong_thuc_thanh_toan = 'Tiền mặt';
    }
    const trang_thai = document.getElementById('payment_trang_thai').value;

    const finalData = {
        ...pendingInvoiceData,
        phuong_thuc_thanh_toan: phuong_thuc_thanh_toan,
        trang_thai: trang_thai
    }; 
    const formData = new FormData();
    for (let key in finalData) {
        formData.append(key, finalData[key]);
    }
    const hoaDonId = document.getElementById('hoa_don_id').value;
    formData.append('action', hoaDonId ? 'updateInvoice' : 'addInvoice');
    try {
        const response = await fetch('managment_function.php',{
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result && result.success) {
            showAlert(result.message || 'Lưu thành công', 'success');
            closePaymentModal();
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } else {
            showAlert((result && result.message) || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Lỗi:', error);
        showAlert('Có lỗi xảy ra khi lưu dữ liệu: ' + (error.message || error), 'error');
    }
}
function getCustomerName(id) {
    const customer = customers.find(c => c.khach_hang_id == id);
    return customer ? customer.ho_ten : 'N/A';
}
function getPackageName(id) {
    const pkg = packages.find(p => p.goi_tap_id == id);
    return pkg ? pkg.ten_goi : 'N/A';
}
function markAsPaid() {
    document.getElementById('payment_trang_thai').value = 'Đã thanh toán';
    confirmPayment();
}