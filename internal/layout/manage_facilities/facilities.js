// Tự động đóng thông báo sau 3 giây và đóng modal khi thành công
document.addEventListener('DOMContentLoaded', function() {
    var alert = document.querySelector('.alert');
    if (alert) {
        // Nếu có thông báo thành công, đóng modal ngay lập tức
        if (alert.classList.contains('alert-success')) {
            var modal = document.querySelector('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        setTimeout(function() {
            alert.style.display = 'none';
        }, 3000);
    }
});