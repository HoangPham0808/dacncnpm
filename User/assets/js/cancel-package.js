/**
 * Cancel Package Handler
 * Xử lý hủy gói tập cho tất cả các trang
 */

// Hàm format số tiền
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

// Hàm mở modal hủy gói tập (global function)
window.openCancelPackageModal = function(dangKyId, tenGoi, tienHoanLai, soNgayDaDung, tongSoNgay) {
    console.log('openCancelPackageModal called with:', {dangKyId, tenGoi, tienHoanLai, soNgayDaDung, tongSoNgay});
    
    const modal = document.getElementById('cancel-package-modal');
    const packageName = document.getElementById('cancel-package-name');
    const refundAmount = document.getElementById('cancel-refund-amount');
    const usageInfo = document.getElementById('cancel-usage-info');
    const form = document.getElementById('cancel-package-form');
    const inputDangKyId = document.getElementById('cancel-dang-ky-id');
    const refundInfo = document.getElementById('cancel-refund-info');
    
    if (!modal) {
        console.error('Modal cancel-package-modal not found!');
        alert('Không tìm thấy modal hủy gói tập. Vui lòng tải lại trang.');
        return;
    }
    
    if (!form) {
        console.error('Form cancel-package-form not found!');
        alert('Không tìm thấy form hủy gói tập. Vui lòng tải lại trang.');
        return;
    }
    
    // Cập nhật thông tin
    if (packageName) packageName.textContent = tenGoi || 'Gói tập';
    if (usageInfo) usageInfo.textContent = (soNgayDaDung || 0) + '/' + (tongSoNgay || 0) + ' ngày đã sử dụng';
    
    if (tienHoanLai > 0) {
        if (refundAmount) {
            refundAmount.textContent = number_format(tienHoanLai, 0, ',', '.') + '₫';
        }
        if (refundInfo) {
            refundInfo.style.display = 'block';
        }
    } else {
        if (refundInfo) {
            refundInfo.style.display = 'none';
        }
    }
    
    if (inputDangKyId) {
        inputDangKyId.value = dangKyId || '';
    }
    
    // Hiển thị modal với z-index cao để hiển thị trên modal "Gói tập của tôi" và các modal khác
    // Đảm bảo modal hủy gói hiển thị NGAY LẬP TỨC trên modal "Gói tập của tôi"
    modal.classList.remove('hidden');
    
    // Force reflow để đảm bảo browser nhận biết thay đổi
    void modal.offsetWidth;
    
    // Đảm bảo z-index cao hơn tất cả modal khác (my-packages-modal có z-index 10000, nên đặt cao hơn)
    modal.style.setProperty('z-index', '100002', 'important');
    modal.style.setProperty('position', 'fixed', 'important');
    modal.style.setProperty('display', 'flex', 'important');
    modal.style.setProperty('visibility', 'visible', 'important');
    modal.style.setProperty('opacity', '1', 'important');
    modal.style.setProperty('pointer-events', 'auto', 'important');
    
    // Đảm bảo modal-content bên trong cũng có z-index cao
    const modalContent = modal.querySelector('.modal-content');
    if (modalContent) {
        modalContent.style.setProperty('z-index', '100003', 'important');
        modalContent.style.setProperty('position', 'relative', 'important');
    }
    
    modal.classList.add('active');
    // Chỉ lock scroll khi modal thực sự mở
    if (modal && modal.classList.contains('active')) {
      document.body.style.overflow = 'hidden';
    }
    
    console.log('Modal opened successfully');
}

// Hàm đóng modal hủy gói tập (global function)
window.closeCancelPackageModal = function() {
    const modal = document.getElementById('cancel-package-modal');
    if (modal) {
        modal.classList.remove('active');
        modal.classList.add('hidden');
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
        // Đảm bảo restore scroll đầy đủ
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        document.documentElement.style.overflow = '';
        document.body.classList.remove('no-scroll');
        const form = document.getElementById('cancel-package-form');
        if (form) form.reset();
    }
}

// Xử lý khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý click vào button hủy gói từ data attributes (cho button có class cancel-package-btn)
    const cancelButtons = document.querySelectorAll('.cancel-package-btn');
    cancelButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const dangKyId = parseInt(this.getAttribute('data-dang-ky-id')) || 0;
            const tenGoi = this.getAttribute('data-ten-goi') || '';
            const tienHoanLai = parseInt(this.getAttribute('data-tien-hoan-lai')) || 0;
            const soNgayDaDung = parseInt(this.getAttribute('data-so-ngay-da-dung')) || 0;
            const tongSoNgay = parseInt(this.getAttribute('data-tong-so-ngay')) || 0;
            
            if (window.openCancelPackageModal) {
                window.openCancelPackageModal(dangKyId, tenGoi, tienHoanLai, soNgayDaDung, tongSoNgay);
            } else {
                console.error('openCancelPackageModal function not found!');
                alert('Hàm hủy gói tập chưa sẵn sàng. Vui lòng tải lại trang.');
            }
        });
    });
    
    // Event delegation để xử lý button "Hủy gói" - XỬ LÝ TRỰC TIẾP THAY VÌ DỰA VÀO ONCLICK
    // Sử dụng document để bắt tất cả click trên button có onclick chứa openCancelPackageModal
    document.addEventListener('click', function(e) {
        // Tìm button "Hủy gói" từ element được click (có thể là button hoặc icon bên trong)
        const button = e.target.closest('button[onclick*="openCancelPackageModal"]');
        
        if (button) {
            const onclickAttr = button.getAttribute('onclick');
            const onclickProperty = button.onclick;
            
            console.log('Button found:', button);
            console.log('onclick attribute:', onclickAttr);
            console.log('onclick property:', onclickProperty);
            
            if (onclickAttr && onclickAttr.includes('openCancelPackageModal')) {
                // Ngăn onclick mặc định chạy
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Button "Hủy gói" clicked via event delegation');
                console.log('Full onclick attribute:', onclickAttr);
                
                // Kiểm tra hàm có tồn tại không
                if (typeof window.openCancelPackageModal !== 'function') {
                    console.error('window.openCancelPackageModal is not a function!');
                    alert('Chức năng hủy gói tập chưa sẵn sàng. Vui lòng tải lại trang.');
                    return false;
                }
                
                // Parse onclick attribute để lấy tham số
                // Format từ PHP: onclick="openCancelPackageModal(123, \"Gói 6 tháng\", 500000, 10, 180)"
                // Thử nhiều regex patterns để match
                let match = onclickAttr.match(/openCancelPackageModal\s*\(\s*([^)]+)\s*\)/);
                
                // Nếu không match, thử pattern khác (cho trường hợp có nested brackets hoặc đặc biệt)
                if (!match) {
                    match = onclickAttr.match(/openCancelPackageModal\s*\(\s*(.*?)\s*\)/);
                }
                
                // Nếu vẫn không match, thử pattern đơn giản nhất
                if (!match) {
                    match = onclickAttr.match(/openCancelPackageModal\s*\(([^)]*)\)/);
                }
                
                console.log('Full onclick attribute:', onclickAttr);
                console.log('Regex match result:', match);
                
                if (match && match[1] !== undefined && match[1].trim()) {
                    try {
                        // Sử dụng cách parse đơn giản và an toàn hơn
                        // Extract parameters bằng cách tách theo dấu phẩy nhưng bỏ qua dấu phẩy trong quotes
                        const paramsStr = match[1].trim();
                        console.log('Raw params string:', paramsStr);
                        
                        // Nếu không có params (chuỗi rỗng), không thể parse
                        if (!paramsStr || paramsStr.length === 0) {
                            throw new Error('onclick attribute không có tham số');
                        }
                        
                        // Tách tham số một cách thông minh hơn
                        const params = [];
                        let current = '';
                        let inQuotes = false;
                        let quoteType = '';
                        let escaped = false;
                        
                        for (let i = 0; i < paramsStr.length; i++) {
                            const c = paramsStr[i];
                            
                            if (escaped) {
                                current += c;
                                escaped = false;
                                continue;
                            }
                            
                            if (c === '\\') {
                                escaped = true;
                                current += c;
                                continue;
                            }
                            
                            // Xử lý quotes (chỉ khi không escaped)
                            if (c === '"' || c === "'") {
                                if (!inQuotes) {
                                    inQuotes = true;
                                    quoteType = c;
                                    current += c;
                                } else if (c === quoteType) {
                                    inQuotes = false;
                                    quoteType = '';
                                    current += c;
                                } else {
                                    current += c;
                                }
                            } else if (!inQuotes && c === ',') {
                                // Kết thúc một tham số (chỉ khi không trong quotes)
                                params.push(current.trim());
                                current = '';
                            } else {
                                current += c;
                            }
                        }
                        
                        // Thêm tham số cuối
                        if (current.trim()) {
                            params.push(current.trim());
                        }
                        
                        console.log('Parsed params:', params);
                        
                        if (params.length < 5) {
                            throw new Error('Không đủ 5 tham số, chỉ có ' + params.length + ' tham số');
                        }
                        
                        // Parse từng tham số
                        const dangKyId = parseInt(params[0].trim()) || 0;
                        
                        // Tham số thứ 2 là tên gói (có thể là JSON string từ json_encode)
                        let tenGoi = params[1].trim();
                        // Remove quotes nếu có
                        if ((tenGoi.startsWith('"') && tenGoi.endsWith('"')) || 
                            (tenGoi.startsWith("'") && tenGoi.endsWith("'"))) {
                            tenGoi = tenGoi.slice(1, -1);
                        }
                        // Unescape nếu có
                        tenGoi = tenGoi.replace(/\\"/g, '"').replace(/\\'/g, "'");
                        
                        const tienHoanLai = parseInt(params[2].trim()) || 0;
                        const soNgayDaDung = parseInt(params[3].trim()) || 0;
                        const tongSoNgay = parseInt(params[4].trim()) || 0;
                        
                        console.log('Final values:', {dangKyId, tenGoi, tienHoanLai, soNgayDaDung, tongSoNgay});
                        
                        // Gọi hàm
                        window.openCancelPackageModal(dangKyId, tenGoi, tienHoanLai, soNgayDaDung, tongSoNgay);
                        
                    } catch (err) {
                        console.error('Parse error:', err);
                        console.error('onclick:', onclickAttr);
                        console.error('Error details:', err.message, err.stack);
                        alert('Lỗi khi xử lý: ' + (err.message || 'Unknown error') + '. Vui lòng thử lại hoặc làm mới trang.');
                    }
                } else {
                    console.error('Cannot match onclick pattern:', onclickAttr);
                    alert('Lỗi: Không thể đọc thông tin nút hủy gói. Vui lòng làm mới trang.');
                }
                
                return false;
            }
        }
    }, true); // Use capture phase để bắt trước onclick
    
    // Xử lý đóng modal khi click outside
    const cancelPackageModal = document.getElementById('cancel-package-modal');
    if (cancelPackageModal) {
        cancelPackageModal.addEventListener('click', function(e) {
            if (e.target === cancelPackageModal) {
                closeCancelPackageModal();
            }
        });
        
        // Xử lý form submit
        const cancelForm = document.getElementById('cancel-package-form');
        if (cancelForm) {
            cancelForm.addEventListener('submit', function(e) {
                const dangKyId = document.getElementById('cancel-dang-ky-id').value;
                if (!dangKyId || dangKyId === '') {
                    e.preventDefault();
                    alert('Lỗi: Không tìm thấy ID gói tập. Vui lòng thử lại.');
                    return false;
                }
            });
        }
    }
});

