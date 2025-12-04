// ============================================
// TỔNG QUAN HỆ THỐNG - JAVASCRIPT
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Cập nhật thời gian thực
    updateTime();
    setInterval(updateTime, 1000);

    // Thêm hiệu ứng animation cho các card khi load trang
    animateCards();

    // Thêm hiệu ứng hover cho các card
    addCardHoverEffects();
});

/**
 * Cập nhật thời gian hiển thị
 */
function updateTime() {
    const now = new Date();
    const timeElement = document.querySelector('.header-info span:last-child');
    if (timeElement) {
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        timeElement.innerHTML = `<i class="fas fa-clock"></i> ${hours}:${minutes}:${seconds}`;
    }
}

/**
 * Animation cho các card khi load trang
 */
function animateCards() {
    const cards = document.querySelectorAll('.stat-card, .secondary-card, .info-table-card');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

/**
 * Thêm hiệu ứng hover cho các card
 */
function addCardHoverEffects() {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });
}

/**
 * Format số tiền với định dạng VNĐ
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

/**
 * Format số với dấu phẩy ngăn cách hàng nghìn
 */
function formatNumber(number) {
    return new Intl.NumberFormat('vi-VN').format(number);
}

