/**
 * Management Reviews JavaScript
 * Xử lý tất cả tương tác giao diện cho trang quản lý đánh giá
 */

// Hàm lọc đánh giá
function filterReviews() {
    const typeFilter = document.getElementById('typeFilter').value;
    const ratingFilter = document.getElementById('ratingFilter').value;
    const url = new URL(window.location);
    
    if (typeFilter) {
        url.searchParams.set('type', typeFilter);
    } else {
        url.searchParams.delete('type');
    }
    
    if (ratingFilter) {
        url.searchParams.set('rating', ratingFilter);
    } else {
        url.searchParams.delete('rating');
    }
    
    window.location.href = url.toString();
}

// Animation cho progress bars khi load trang
window.addEventListener('DOMContentLoaded', function() {
    const progressBars = document.querySelectorAll('.progress-fill');
    
    progressBars.forEach((bar, index) => {
        const width = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.width = width;
        }, 100 + (index * 100));
    });
    
    // Animation cho stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 50 + (index * 100));
    });
    
    // Animation cho review items
    const reviewItems = document.querySelectorAll('.review-item');
    reviewItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, 100 + (index * 50));
    });
});

console.log('Management Reviews JS loaded successfully');