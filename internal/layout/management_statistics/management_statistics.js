/**
 * Management Statistics JavaScript
 * Xử lý tương tác cho trang báo cáo thống kê
 */

// Khởi tạo khi trang load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Statistics page loaded successfully');
    
    // Có thể thêm các xử lý khác ở đây
});

// Hàm filter theo period (đã được gọi từ HTML)
function filterByPeriod() {
    const period = document.getElementById('periodSelect').value;
    const url = new URL(window.location);
    url.searchParams.set('period', period);
    window.location.href = url.toString();
}

// Hàm xuất báo cáo
function exportReport() {
    // TODO: Implement export functionality
    alert('Tính năng xuất báo cáo đang được phát triển!');
}

console.log('Management Statistics JS loaded successfully');

