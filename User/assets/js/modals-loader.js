/**
 * modals-loader.js - Load dữ liệu từ API và hiển thị trong các modal
 */

(function() {
    'use strict';

    // Xác định đường dẫn API dựa trên vị trí hiện tại
    function getApiPath(endpoint) {
        const pathname = window.location.pathname;
        const isInUserFolder = pathname.includes('/user/');
        
        // Kiểm tra xem có đang ở trong thư mục con của user/ không (goitap/, danhgia/, etc.)
        const isInSubFolder = pathname.match(/\/user\/(goitap|danhgia|hotro|lichtap|homthu|thanhtoan|dangky|dangnhap|getset)\//);
        
        // Các file liên quan đến gói tập nằm trong thư mục goitap/
        const goitapFiles = ['get_my_packages.php', 'process_payment.php', 'cancel_package.php', 'export_invoice.php', 'check_package.php'];
        // Các file liên quan đến thanh toán nằm trong thư mục thanhtoan/
        const thanhtoanFiles = ['get_payment_history.php', 'get_payment_methods.php', 'save_payment_method.php', 'delete_payment_method.php'];
        // Các file liên quan đến đánh giá nằm trong thư mục danhgia/
        const danhgiaFiles = ['get_reviews.php', 'submit_review.php'];
        // Các file liên quan đến hỗ trợ nằm trong thư mục hotro/
        const hotroFiles = ['send_support_request.php'];
        // Các file liên quan đến lịch tập nằm trong thư mục lichtap/
        const lichtapFiles = ['check_schedule.php', 'book_class.php', 'cancel_class.php', 'edit_class.php'];
        // Các file liên quan đến getset (config, db, profile, etc.)
        const getsetFiles = ['get_profile.php', 'update_profile.php', 'update_password.php'];
        // Các file liên quan đến hòm thư nằm trong thư mục homthu/
        const homthuFiles = ['get_inbox.php', 'mark_read.php', 'mark_all_read.php', 'delete_notification.php'];
        
        // Nếu đang ở trong thư mục con (ví dụ: user/goitap/), dùng đường dẫn tương đối
        if (isInSubFolder) {
            if (goitapFiles.includes(endpoint)) {
                // Nếu đang ở trong goitap/, file cùng thư mục
                return pathname.includes('/goitap/') ? endpoint : '../goitap/' + endpoint;
            } else if (thanhtoanFiles.includes(endpoint)) {
                return '../thanhtoan/' + endpoint;
            } else if (danhgiaFiles.includes(endpoint)) {
                return '../danhgia/' + endpoint;
            } else if (hotroFiles.includes(endpoint)) {
                return '../hotro/' + endpoint;
            } else if (lichtapFiles.includes(endpoint)) {
                return '../lichtap/' + endpoint;
            } else if (getsetFiles.includes(endpoint)) {
                return '../getset/' + endpoint;
            } else if (homthuFiles.includes(endpoint)) {
                return '../homthu/' + endpoint;
            }
            return '../' + endpoint;
        }
        
        // Nếu đang ở trong user/ nhưng không phải subfolder, hoặc ở root
        if (goitapFiles.includes(endpoint)) {
            return isInUserFolder ? 'goitap/' + endpoint : 'user/goitap/' + endpoint;
        } else if (thanhtoanFiles.includes(endpoint)) {
            return isInUserFolder ? 'thanhtoan/' + endpoint : 'user/thanhtoan/' + endpoint;
        } else if (danhgiaFiles.includes(endpoint)) {
            return isInUserFolder ? 'danhgia/' + endpoint : 'user/danhgia/' + endpoint;
        } else if (hotroFiles.includes(endpoint)) {
            return isInUserFolder ? 'hotro/' + endpoint : 'user/hotro/' + endpoint;
        } else if (lichtapFiles.includes(endpoint)) {
            return isInUserFolder ? 'lichtap/' + endpoint : 'user/lichtap/' + endpoint;
        } else if (getsetFiles.includes(endpoint)) {
            return isInUserFolder ? 'getset/' + endpoint : 'user/getset/' + endpoint;
        } else if (homthuFiles.includes(endpoint)) {
            return isInUserFolder ? 'homthu/' + endpoint : 'user/homthu/' + endpoint;
        }
        return isInUserFolder ? endpoint : 'user/' + endpoint;
    }

    // Load thông tin tài khoản
    function loadProfile() {
        const content = document.getElementById('profile-content');
        if (!content) {
            console.error('profile-content not found');
            return;
        }

        // Hiển thị loading
        content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i><p style="margin-top: 15px; color: var(--muted);">Đang tải dữ liệu...</p></div>';

        const apiPath = getApiPath('get_profile.php');
        console.log('Fetching profile from:', apiPath);
        
        fetch(apiPath)
            .then(response => {
                console.log('Profile response status:', response.status, response.statusText); // Debug
                if (!response.ok) {
                    // Nếu là lỗi 403, thử lại với đường dẫn khác
                    if (response.status === 403) {
                        console.warn('403 Forbidden, trying alternative path...');
                        const altPath = window.location.pathname.includes('/user/') ? '../getset/get_profile.php' : 'user/getset/get_profile.php';
                        return fetch(altPath).then(altResponse => {
                            if (!altResponse.ok) throw new Error('HTTP ' + altResponse.status + ': ' + altResponse.statusText);
                            return altResponse.json();
                        });
                    }
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Profile data received:', data); // Debug
                if (data.success && data.profile) {
                    const profile = data.profile;
                    const updatePath = getApiPath('update_profile.php');
                    content.innerHTML = `
                        <form action="${updatePath}" method="POST">
                            <div class="form-group">
                                <label for="profile-fullname">Họ và tên</label>
                                <input type="text" id="profile-fullname" name="full_name" value="${escapeHtml(profile.ho_ten || '')}" required>
                            </div>
                            <div class="form-group">
                                <label for="profile-email">Email</label>
                                <input type="email" id="profile-email" name="email" value="${escapeHtml(profile.email || '')}" readonly disabled>
                            </div>
                            <div class="form-group">
                                <label for="profile-phone">Số điện thoại</label>
                                <input type="tel" id="profile-phone" name="phone" value="${escapeHtml(profile.sdt || '')}" maxlength="11" pattern="[0-9]{10,11}">
                            </div>
                            <div class="form-group">
                                <label for="profile-cccd">Căn cước công dân</label>
                                <input type="text" id="profile-cccd" name="cccd" value="${escapeHtml(profile.cccd || '')}" maxlength="12" pattern="[0-9]{9,12}">
                            </div>
                            <div class="form-group">
                                <label for="profile-address">Địa chỉ</label>
                                <input type="text" id="profile-address" name="address" value="${escapeHtml(profile.dia_chi || '')}">
                            </div>
                            <div class="form-group">
                                <label for="profile-birthday">Ngày sinh</label>
                                <input type="date" id="profile-birthday" name="birthday" value="${profile.ngay_sinh || ''}">
                            </div>
                            <div class="form-group">
                                <label for="profile-gender">Giới tính</label>
                                <select id="profile-gender" name="gender">
                                    <option value="">-- Chọn --</option>
                                    <option value="Nam" ${profile.gioi_tinh === 'Nam' ? 'selected' : ''}>Nam</option>
                                    <option value="Nữ" ${profile.gioi_tinh === 'Nữ' ? 'selected' : ''}>Nữ</option>
                                    <option value="Khác" ${profile.gioi_tinh === 'Khác' ? 'selected' : ''}>Khác</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ngày đăng ký</label>
                                <input type="text" value="${profile.ngay_dang_ky || ''}" readonly disabled>
                            </div>
                            <div id="profile-message" style="margin-bottom: 15px;"></div>
                            <button type="submit" class="btn">Lưu thay đổi</button>
                        </form>
                    `;
                    
                    // Xử lý form submit bằng AJAX để giữ modal mở
                    const profileForm = content.querySelector('form');
                    if (profileForm) {
                        profileForm.addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            const submitBtn = profileForm.querySelector('button[type="submit"]');
                            const messageDiv = document.getElementById('profile-message');
                            const originalBtnText = submitBtn.innerHTML;
                            
                            // Disable button và hiển thị loading
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
                            
                            // Lấy dữ liệu form
                            const formData = new FormData(profileForm);
                            
                            // Đánh dấu đây là AJAX request
                            formData.append('ajax', '1');
                            
                            // Thêm các field cần thiết với tên đúng
                            const phoneValue = document.getElementById('profile-phone')?.value || '';
                            const cccdValue = document.getElementById('profile-cccd')?.value || '';
                            const addressValue = document.getElementById('profile-address')?.value || '';
                            const birthdayValue = document.getElementById('profile-birthday')?.value || '';
                            const genderValue = document.getElementById('profile-gender')?.value || '';
                            
                            // Cập nhật hoặc thêm các field
                            if (formData.has('phone')) {
                                formData.set('sdt', phoneValue);
                            } else {
                                formData.append('sdt', phoneValue);
                            }
                            
                            if (formData.has('cccd')) {
                                formData.set('cccd', cccdValue);
                            } else {
                                formData.append('cccd', cccdValue);
                            }
                            
                            if (formData.has('address')) {
                                formData.set('dia_chi', addressValue);
                            } else {
                                formData.append('dia_chi', addressValue);
                            }
                            
                            if (formData.has('birthday')) {
                                formData.set('ngay_sinh', birthdayValue);
                            } else {
                                formData.append('ngay_sinh', birthdayValue);
                            }
                            
                            if (formData.has('gender')) {
                                formData.set('gioi_tinh', genderValue);
                            } else {
                                formData.append('gioi_tinh', genderValue);
                            }
                            
                            // Gửi request với header để đánh dấu là AJAX
                            fetch(updatePath, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            })
                            .then(response => {
                                // Kiểm tra content type
                                const contentType = response.headers.get('content-type');
                                if (contentType && contentType.includes('application/json')) {
                                    return response.json();
                                }
                                // Nếu không phải JSON, thử parse text
                                return response.text().then(text => {
                                    try {
                                        return JSON.parse(text);
                                    } catch {
                                        // Nếu không parse được, kiểm tra status
                                        if (response.ok || response.status === 200) {
                                            return { success: true, message: 'Cập nhật thông tin thành công!' };
                                        }
                                        return { success: false, message: 'Có lỗi xảy ra khi cập nhật thông tin.' };
                                    }
                                });
                            })
                            .then(data => {
                                if (data.success) {
                                    // Hiển thị thông báo thành công
                                    if (messageDiv) {
                                        messageDiv.innerHTML = `<p style="color: #39d98a; padding: 10px; background: rgba(57, 217, 138, 0.1); border-radius: 5px;"><i class="fas fa-check-circle"></i> ${data.message || 'Cập nhật thông tin thành công!'}</p>`;
                                    }
                                    
                                    // Reload lại dữ liệu profile sau 500ms
                                    setTimeout(() => {
                                        loadProfile();
                                    }, 500);
                                } else {
                                    // Hiển thị thông báo lỗi
                                    if (messageDiv) {
                                        messageDiv.innerHTML = `<p style="color: #ff6b6b; padding: 10px; background: rgba(255, 107, 107, 0.1); border-radius: 5px;"><i class="fas fa-exclamation-circle"></i> ${data.message || 'Có lỗi xảy ra khi cập nhật thông tin.'}</p>`;
                                    }
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalBtnText;
                                }
                            })
                            .catch(error => {
                                console.error('Error updating profile:', error);
                                if (messageDiv) {
                                    messageDiv.innerHTML = `<p style="color: #ff6b6b; padding: 10px; background: rgba(255, 107, 107, 0.1); border-radius: 5px;"><i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra: ${error.message}</p>`;
                                }
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalBtnText;
                            });
                        });
                    }
                } else {
                    content.innerHTML = `<p class="muted center">${data.message || 'Không thể tải thông tin tài khoản'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error loading profile:', error);
                const errorMsg = error.message.includes('403') ? 
                    'Lỗi 403: Không có quyền truy cập. Vui lòng kiểm tra đường dẫn API.' : 
                    'Có lỗi xảy ra khi tải thông tin tài khoản: ' + error.message;
                content.innerHTML = `<p class="muted center" style="color: #ff6b6b;">${errorMsg}</p>`;
            });
    }

    // Load gói tập
    function loadMyPackages() {
        const content = document.getElementById('my-packages-content');
        if (!content) {
            console.error('my-packages-content not found');
            return;
        }

        // Hiển thị loading
        content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i><p style="margin-top: 15px; color: var(--muted);">Đang tải dữ liệu...</p></div>';

        const apiPath = getApiPath('get_my_packages.php');
        console.log('Fetching packages from:', apiPath);
        
        fetch(apiPath)
            .then(response => {
                console.log('Packages response status:', response.status, response.statusText); // Debug
                if (!response.ok) {
                    if (response.status === 403) {
                        console.warn('403 Forbidden, trying alternative path...');
                        const altPath = window.location.pathname.includes('/user/') ? '../goitap/get_my_packages.php' : 'user/goitap/get_my_packages.php';
                        return fetch(altPath).then(altResponse => {
                            if (!altResponse.ok) throw new Error('HTTP ' + altResponse.status + ': ' + altResponse.statusText);
                            return altResponse.json();
                        });
                    }
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Packages data received:', data); // Debug
                if (data.success && data.packages) {
                    if (data.packages.length > 0) {
                        let html = '<div style="display: grid; gap: 15px;">';
                        data.packages.forEach(function(pkg) {
                            const isActive = pkg.trang_thai === 'Đang hoạt động' && pkg.so_ngay_con_lai >= 0;
                            const isExpired = pkg.trang_thai === 'Hết hạn' || (pkg.trang_thai === 'Đang hoạt động' && pkg.so_ngay_con_lai < 0);
                            const isCancelled = pkg.trang_thai === 'Hủy';
                            
                            html += `
                                <div style="background: var(--bg-2); padding: 20px; border-radius: 10px; border-left: 4px solid ${isActive ? 'var(--primary)' : (isExpired ? 'var(--muted)' : 'var(--accent)')};">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                                        <div style="flex: 1;">
                                            <h4 style="margin: 0 0 10px 0; color: var(--text);">
                                                ${isActive ? '<i class="fas fa-check-circle" style="color: var(--primary);"></i> ' : (isExpired ? '<i class="fas fa-clock" style="color: var(--muted);"></i> ' : '<i class="fas fa-times-circle" style="color: var(--accent);"></i> ')}
                                                ${escapeHtml(pkg.ten_goi || 'Gói tập')}
                                            </h4>
                                            <div style="color: var(--muted); font-size: 14px; line-height: 1.8;">
                                                <p style="margin: 5px 0;"><strong>Ngày bắt đầu:</strong> ${pkg.ngay_bat_dau}</p>
                                                <p style="margin: 5px 0;"><strong>Ngày hết hạn:</strong> ${pkg.ngay_ket_thuc}</p>
                                                <p style="margin: 5px 0;"><strong>Số tiền:</strong> ${pkg.tong_tien}₫</p>
                                                ${pkg.tong_so_ngay > 0 ? `<p style="margin: 5px 0;"><strong>Tiến độ:</strong> ${pkg.so_ngay_da_dung}/${pkg.tong_so_ngay} ngày đã sử dụng${pkg.so_ngay_con_lai > 0 ? ' • ' + pkg.so_ngay_con_lai + ' ngày còn lại' : ''}</p>` : ''}
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                            ${isActive ? '<span class="status-active" style="padding: 8px 15px; border-radius: 20px; white-space: nowrap;">Đang hoạt động</span>' : (isExpired ? '<span class="status-warning" style="padding: 8px 15px; border-radius: 20px; white-space: nowrap;">Đã hết hạn</span>' : '<span class="status-error" style="padding: 8px 15px; border-radius: 20px; white-space: nowrap;">Đã hủy</span>')}
                                            ${isActive ? `<button type="button" class="btn cancel-package-btn" style="padding: 8px 20px; background: rgba(255, 48, 64, 0.1); color: #ff3040; border: 1px solid #ff3040; border-radius: 20px; white-space: nowrap; cursor: pointer;" 
                                                    data-dang-ky-id="${pkg.dang_ky_id || ''}" 
                                                    data-ten-goi="${escapeHtml(pkg.ten_goi || 'Gói tập')}" 
                                                    data-tien-hoan-lai="${pkg.tien_hoan_lai || 0}" 
                                                    data-so-ngay-da-dung="${pkg.so_ngay_da_dung || 0}" 
                                                    data-tong-so-ngay="${pkg.tong_so_ngay || 0}">
                                                <i class="fas fa-times"></i> Hủy gói
                                            </button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        content.innerHTML = html;
                        
                        // Thêm event listener cho các nút hủy gói
                        setTimeout(function() {
                            const cancelButtons = content.querySelectorAll('.cancel-package-btn');
                            cancelButtons.forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const dangKyId = this.getAttribute('data-dang-ky-id');
                                    const tenGoi = this.getAttribute('data-ten-goi');
                                    const tienHoanLai = this.getAttribute('data-tien-hoan-lai');
                                    const soNgayDaDung = this.getAttribute('data-so-ngay-da-dung');
                                    const tongSoNgay = this.getAttribute('data-tong-so-ngay');
                                    
                                    if (typeof openCancelPackageModal === 'function') {
                                        openCancelPackageModal(dangKyId, tenGoi, tienHoanLai, soNgayDaDung, tongSoNgay);
                                    } else {
                                        console.error('openCancelPackageModal function not found');
                                    }
                                });
                            });
                        }, 100);
                    } else {
                        const packagesLink = window.location.pathname.includes('/user/') ? 'goitap/packages.html' : 'user/goitap/packages.html';
                        content.innerHTML = '<p class="muted center" style="padding: 20px;">Bạn chưa có gói tập nào. <a href="' + packagesLink + '" style="color: var(--primary);">Mua gói tập ngay</a></p>';
                    }
                } else {
                    content.innerHTML = `<p class="muted center">${data.message || 'Không thể tải danh sách gói tập'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error loading packages:', error);
                const errorMsg = error.message.includes('403') ? 
                    'Lỗi 403: Không có quyền truy cập. Vui lòng kiểm tra đường dẫn API.' : 
                    'Có lỗi xảy ra khi tải danh sách gói tập: ' + error.message;
                content.innerHTML = `<p class="muted center" style="color: #ff6b6b;">${errorMsg}</p>`;
            });
    }

    // Load lịch sử thanh toán
    function loadPaymentHistory() {
        const content = document.getElementById('payment-history-content');
        if (!content) {
            console.error('payment-history-content not found');
            return;
        }

        // Hiển thị loading
        content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i><p style="margin-top: 15px; color: var(--muted);">Đang tải dữ liệu...</p></div>';

        const apiPath = getApiPath('get_payment_history.php');
        console.log('Fetching payment history from:', apiPath);
        
        fetch(apiPath)
            .then(response => {
                console.log('Payment history response status:', response.status, response.statusText); // Debug
                if (!response.ok) {
                    if (response.status === 403) {
                        console.warn('403 Forbidden, trying alternative path...');
                        const altPath = window.location.pathname.includes('/user/') ? '../thanhtoan/get_payment_history.php' : 'user/thanhtoan/get_payment_history.php';
                        return fetch(altPath).then(altResponse => {
                            if (!altResponse.ok) throw new Error('HTTP ' + altResponse.status + ': ' + altResponse.statusText);
                            return altResponse.json();
                        });
                    }
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Payment history data received:', data); // Debug
                if (data.success && data.payments) {
                    if (data.payments.length > 0) {
                        let html = '<table class="data-table"><thead><tr><th>Ngày</th><th>Mã hóa đơn</th><th>Gói tập</th><th>Số tiền</th><th>Phương thức</th><th>Trạng thái</th><th>Hành động</th></tr></thead><tbody>';
                        data.payments.forEach(function(payment) {
                            const statusClass = payment.trang_thai === 'Đã thanh toán' ? 'status-active' : (payment.trang_thai === 'Chờ thanh toán' ? 'status-warning' : 'status-error');
                            const statusText = payment.trang_thai === 'Đã thanh toán' ? 'Thành công' : (payment.trang_thai === 'Chờ thanh toán' ? 'Chờ' : 'Đã hủy');
                            const hoaDonId = payment.hoa_don_id || '';
                            
                            html += `
                                <tr>
                                    <td>${payment.ngay_lap || ''}</td>
                                    <td>${escapeHtml(payment.ma_hoa_don || '')}</td>
                                    <td>${escapeHtml(payment.ten_goi || 'N/A')}</td>
                                    <td>${payment.tien_thanh_toan || '0'}₫</td>
                                    <td>${escapeHtml(payment.phuong_thuc_thanh_toan || '')}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                    <td style="text-align: center;">
                                        <button class="btn-print-invoice" data-hoa-don-id="${hoaDonId}" title="In hóa đơn" style="padding: 8px 16px; background: #22c55e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                            <i class="fas fa-print"></i> In
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                        html += '</tbody></table>';
                        content.innerHTML = html;
                        
                        // Thêm event listener cho các nút in
                        setTimeout(function() {
                            const printButtons = document.querySelectorAll('.btn-print-invoice');
                            console.log('Found print buttons:', printButtons.length);
                            printButtons.forEach(btn => {
                                btn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    const hoaDonId = this.getAttribute('data-hoa-don-id');
                                    console.log('Print button clicked, hoa_don_id:', hoaDonId);
                                    if (hoaDonId && hoaDonId !== '') {
                                        showPrintOptions(hoaDonId);
                                    } else {
                                        alert('Không tìm thấy mã hóa đơn');
                                    }
                                });
                                
                                // Hover effect
                                btn.addEventListener('mouseenter', function() {
                                    this.style.background = '#16a34a';
                                });
                                btn.addEventListener('mouseleave', function() {
                                    this.style.background = '#22c55e';
                                });
                            });
                        }, 100);
                    } else {
                        content.innerHTML = '<p class="muted center" style="padding: 20px;">Chưa có lịch sử thanh toán.</p>';
                    }
                } else {
                    content.innerHTML = `<p class="muted center">${data.message || 'Không thể tải lịch sử thanh toán'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error loading payment history:', error);
                const errorMsg = error.message.includes('403') ? 
                    'Lỗi 403: Không có quyền truy cập. Vui lòng kiểm tra đường dẫn API.' : 
                    'Có lỗi xảy ra khi tải lịch sử thanh toán: ' + error.message;
                content.innerHTML = `<p class="muted center" style="color: #ff6b6b;">${errorMsg}</p>`;
            });
    }

    // Load phương thức thanh toán
    function loadPaymentMethods() {
        const content = document.getElementById('payment-management-content');
        if (!content) {
            console.error('payment-management-content not found');
            return;
        }

        // Hiển thị loading
        content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i><p style="margin-top: 15px; color: var(--muted);">Đang tải dữ liệu...</p></div>';

        const apiPath = getApiPath('get_payment_methods.php');
        console.log('Fetching payment methods from:', apiPath);
        
        fetch(apiPath)
            .then(response => {
                console.log('Payment methods response status:', response.status, response.statusText); // Debug
                if (!response.ok) {
                    if (response.status === 403) {
                        console.warn('403 Forbidden, trying alternative path...');
                        const altPath = window.location.pathname.includes('/user/') ? '../thanhtoan/get_payment_methods.php' : 'user/thanhtoan/get_payment_methods.php';
                        return fetch(altPath).then(altResponse => {
                            if (!altResponse.ok) throw new Error('HTTP ' + altResponse.status + ': ' + altResponse.statusText);
                            return altResponse.json();
                        });
                    }
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Payment methods data received:', data); // Debug
                if (data.success && data.methods) {
                    let html = '<h3>Phương thức đã lưu</h3><div class="saved-methods-list">';
                    if (data.methods.length > 0) {
                        data.methods.forEach(function(method) {
                            html += `
                                <div class="saved-method-item">
                                    <div class="icon-brand"><i class="fas fa-credit-card"></i></div>
                                    <div class="method-info">
                                        <strong>${escapeHtml(method.ten_hien_thi || '')}</strong>
                                        ${method.mac_dinh ? '<span style="font-size: 11px; color: var(--primary);">(Mặc định)</span>' : ''}
                                        <span>${escapeHtml(method.thong_tin_chi_tiet || 'Đã lưu')}</span>
                                    </div>
                                    <form method="POST" action="${getApiPath('delete_payment_method.php')}" style="display: inline;" class="delete-payment-form">
                                        <input type="hidden" name="phuong_thuc_id" value="${method.phuong_thuc_id}">
                                        <button type="submit" class="btn-icon btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            `;
                        });
                    } else {
                        html += '<p class="muted center" style="padding: 20px;">Bạn chưa có phương thức thanh toán nào được lưu.</p>';
                    }
                    html += '</div>';
                    // Thêm form thêm phương thức mới
                    html += '<hr style="border-color: var(--border-color); margin: 20px 0;"><h3>Thêm phương thức mới</h3>';
                    const savePath = getApiPath('save_payment_method.php');
                    html += `
                        <form action="${savePath}" method="POST" id="save-payment-form-dynamic">
                            <div class="form-group">
                                <label for="payment-method-type-dynamic">Loại phương thức</label>
                                <select id="payment-method-type-dynamic" name="payment_type" required>
                                    <option value="">-- Chọn loại phương thức --</option>
                                    <option value="card">Thẻ tín dụng (Visa/Mastercard)</option>
                                    <option value="bank">Chuyển khoản (Ngân hàng)</option>
                                    <option value="ewallet">Ví điện tử (Momo/ZaloPay)</option>
                                    <option value="paypal">PayPal</option>
                                </select>
                            </div>
                            
                            <!-- Form thẻ tín dụng -->
                            <div id="form-card-dynamic" class="payment-form-section" style="display: none !important;">
                                <div class="form-group">
                                    <label>Số thẻ</label>
                                    <input type="text" name="card_number" placeholder="XXXX XXXX XXXX XXXX" maxlength="19">
                                </div>
                                <div class="form-group">
                                    <label>Tên chủ thẻ</label>
                                    <input type="text" name="card_name" placeholder="NGUYEN VAN A">
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div class="form-group">
                                        <label>Ngày hết hạn</label>
                                        <input type="text" name="card_expiry" placeholder="MM/YY" maxlength="5">
                                    </div>
                                    <div class="form-group">
                                        <label>CVC</label>
                                        <input type="text" name="card_cvc" placeholder="123" maxlength="4">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form ví điện tử -->
                            <div id="form-ewallet-dynamic" class="payment-form-section" style="display: none !important;">
                                <div class="form-group">
                                    <label>Loại ví điện tử</label>
                                    <select name="ewallet_type">
                                        <option value="">-- Chọn loại ví --</option>
                                        <option value="Momo">Momo</option>
                                        <option value="ZaloPay">ZaloPay</option>
                                        <option value="VNPay">VNPay</option>
                                        <option value="ShopeePay">ShopeePay</option>
                                        <option value="Other">Khác</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Số tài khoản / Số điện thoại</label>
                                    <input type="text" name="ewallet_account" placeholder="Nhập số tài khoản hoặc số điện thoại">
                                </div>
                                <div class="form-group">
                                    <label>Tên chủ tài khoản</label>
                                    <input type="text" name="ewallet_name" placeholder="Nhập tên chủ tài khoản">
                                </div>
                            </div>
                            
                            <!-- Form ngân hàng -->
                            <div id="form-bank-dynamic" class="payment-form-section" style="display: none !important;">
                                <div class="form-group">
                                    <label>Tên ngân hàng</label>
                                    <input type="text" name="bank_name" placeholder="Ví dụ: MB Bank, Vietcombank, BIDV">
                                </div>
                                <div class="form-group">
                                    <label>Số tài khoản</label>
                                    <input type="text" name="bank_account" placeholder="Nhập số tài khoản">
                                </div>
                                <div class="form-group">
                                    <label>Tên chủ tài khoản</label>
                                    <input type="text" name="account_holder" placeholder="NGUYEN VAN A">
                                </div>
                            </div>
                            
                            <!-- Form PayPal -->
                            <div id="form-paypal-dynamic" class="payment-form-section" style="display: none !important;">
                                <div class="form-group">
                                    <label>Email PayPal</label>
                                    <input type="email" name="paypal_email" placeholder="email@example.com">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn">Lưu phương thức</button>
                        </form>
                    `;
                    content.innerHTML = html;
                    
                    // Khởi tạo form handler sau khi HTML được insert
                    setTimeout(function() {
                        initPaymentMethodFormDynamic();
                    }, 100);
                } else {
                    content.innerHTML = `<p class="muted center">${data.message || 'Không thể tải phương thức thanh toán'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error loading payment methods:', error);
                const errorMsg = error.message.includes('403') ? 
                    'Lỗi 403: Không có quyền truy cập. Vui lòng kiểm tra đường dẫn API.' : 
                    'Có lỗi xảy ra khi tải phương thức thanh toán: ' + error.message;
                content.innerHTML = `<p class="muted center" style="color: #ff6b6b;">${errorMsg}</p>`;
            });
    }
    
    // Hàm khởi tạo form thanh toán động
    function initPaymentMethodFormDynamic() {
        const paymentMethodType = document.getElementById('payment-method-type-dynamic');
        if (!paymentMethodType) {
            console.warn('Payment method type select not found');
            return;
        }
        
        const formSections = {
            'card': document.getElementById('form-card-dynamic'),
            'bank': document.getElementById('form-bank-dynamic'),
            'ewallet': document.getElementById('form-ewallet-dynamic'),
            'paypal': document.getElementById('form-paypal-dynamic')
        };
        
        // Hàm ẩn tất cả form sections
        function hideAllForms() {
            Object.values(formSections).forEach(section => {
                if (section) {
                    section.style.display = 'none';
                    const inputs = section.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        input.removeAttribute('required');
                        if (input.type !== 'checkbox') {
                            input.value = '';
                        }
                    });
                }
            });
        }
        
        // Hàm hiển thị form tương ứng
        function showForm(type) {
            hideAllForms();
            if (formSections[type]) {
                formSections[type].style.display = 'block';
                const inputs = formSections[type].querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (input.name && input.type !== 'checkbox') {
                        input.setAttribute('required', 'required');
                    }
                });
                
            }
        }
        
        // Xử lý khi thay đổi dropdown
        paymentMethodType.addEventListener('change', function() {
            const selectedType = this.value;
            if (selectedType && formSections[selectedType]) {
                showForm(selectedType);
            } else {
                hideAllForms();
            }
        });
        
        // Đảm bảo ẩn tất cả form khi khởi tạo
        hideAllForms();
    }

    // Helper function để escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Map các modal với function load tương ứng
    const modals = {
        'profile-modal': loadProfile,
        'my-packages-modal': loadMyPackages,
        'payment-history-modal': loadPaymentHistory,
        'payment-management-modal': loadPaymentMethods
    };

    // Hàm để load dữ liệu khi modal được mở
    function loadModalData(modalId) {
        console.log('Loading data for modal:', modalId); // Debug
        const loadFunction = modals[modalId];
        if (loadFunction) {
            loadFunction();
        } else {
            console.warn('No load function found for modal:', modalId);
        }
    }
    
    // Export function ra global để có thể gọi từ auth.js
    window.loadModalData = loadModalData;

    // Lắng nghe khi modal được mở - sử dụng MutationObserver
    Object.keys(modals).forEach(function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.warn('Modal not found:', modalId);
            return;
        }

        let wasActive = modal.classList.contains('active') || !modal.classList.contains('hidden');
        
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isActive = modal.classList.contains('active') && !modal.classList.contains('hidden');
                    
                    // Nếu modal vừa được mở (chuyển từ không active sang active)
                    if (isActive && !wasActive) {
                        wasActive = true;
                        // Load dữ liệu ngay lập tức
                        setTimeout(function() {
                            loadModalData(modalId);
                        }, 100);
                    } else if (!isActive) {
                        wasActive = false;
                    }
                }
            });
        });

        observer.observe(modal, {
            attributes: true,
            attributeFilter: ['class']
        });
    });

    // Loại bỏ event listener click vì đã có MutationObserver xử lý
    // Tránh conflict với auth.js và tránh load dữ liệu 2 lần

    // Hiển thị menu chọn định dạng xuất hóa đơn
    function showPrintOptions(hoaDonId) {
        // Tạo overlay backdrop với z-index rất cao
        const overlay = document.createElement('div');
        overlay.className = 'print-format-overlay';
        overlay.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.85) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            z-index: 99999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        `;
        
        // Tạo modal chọn định dạng với z-index cao hơn overlay
        const formatMenu = document.createElement('div');
        formatMenu.className = 'print-format-menu';
        formatMenu.style.cssText = `
            position: relative !important;
            background: var(--card) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
            padding: 24px !important;
            z-index: 100000 !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.8) !important;
            min-width: 320px !important;
            max-width: 90vw !important;
            animation: fadeInScale 0.2s ease-out !important;
        `;
        
        // Thêm animation CSS nếu chưa có
        if (!document.getElementById('print-format-styles')) {
            const style = document.createElement('style');
            style.id = 'print-format-styles';
            style.textContent = `
                @keyframes fadeInScale {
                    from {
                        opacity: 0;
                        transform: scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        formatMenu.innerHTML = `
            <h3 style="margin: 0 0 20px; color: var(--text); font-size: 18px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-file-export" style="color: var(--primary);"></i> Chọn định dạng xuất hóa đơn
            </h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <button class="btn-export" data-format="word" style="padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; text-align: left; display: flex; align-items: center; gap: 10px; transition: transform 0.2s, box-shadow 0.2s;">
                    <i class="fas fa-file-word" style="font-size: 20px;"></i>
                    <span>Xuất Word (.docx)</span>
                </button>
                <button class="btn-export" data-format="excel" style="padding: 12px; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; text-align: left; display: flex; align-items: center; gap: 10px; transition: transform 0.2s, box-shadow 0.2s;">
                    <i class="fas fa-file-excel" style="font-size: 20px;"></i>
                    <span>Xuất Excel (.xlsx)</span>
                </button>
                <button class="btn-export" data-format="pdf" style="padding: 12px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; text-align: left; display: flex; align-items: center; gap: 10px; transition: transform 0.2s, box-shadow 0.2s;">
                    <i class="fas fa-file-pdf" style="font-size: 20px;"></i>
                    <span>Xuất PDF (.pdf)</span>
                </button>
            </div>
            <button class="btn-close-menu" style="margin-top: 20px; padding: 8px 16px; background: transparent; color: var(--muted); border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer; width: 100%; transition: background 0.2s;">
                Hủy
            </button>
        `;
        
        overlay.appendChild(formatMenu);
        document.body.appendChild(overlay);
        
        // Hover effects cho các nút
        formatMenu.querySelectorAll('.btn-export').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.3)';
            });
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
        
        // Hover effect cho nút hủy
        formatMenu.querySelector('.btn-close-menu').addEventListener('mouseenter', function() {
            this.style.background = 'var(--bg-2)';
        });
        formatMenu.querySelector('.btn-close-menu').addEventListener('mouseleave', function() {
            this.style.background = 'transparent';
        });
        
        // Đóng menu khi click nút hủy hoặc click overlay
        const closeMenu = () => {
            document.body.removeChild(overlay);
        };
        
        formatMenu.querySelector('.btn-close-menu').addEventListener('click', closeMenu);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeMenu();
            }
        });
        
        // Xử lý khi chọn định dạng
        formatMenu.querySelectorAll('.btn-export').forEach(btn => {
            btn.addEventListener('click', function() {
                const format = this.getAttribute('data-format');
                exportInvoice(hoaDonId, format);
                closeMenu();
            });
        });
    }
    
    // Xuất hóa đơn theo định dạng
    function exportInvoice(hoaDonId, format) {
        const apiPath = getApiPath('export_invoice.php');
        const url = `${apiPath}?hoa_don_id=${hoaDonId}&format=${format}`;
        window.open(url, '_blank');
    }

})();

