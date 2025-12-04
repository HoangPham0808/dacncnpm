// Base path for the application
const BASE_PATH = 'DACN/internal/';

// Function to convert relative to absolute path
function getAbsolutePath(relativePath) {
    return relativePath.startsWith('..') ? relativePath.replace('..', BASE_PATH) : relativePath;
}

// Function to convert absolute to relative path
function getRelativePath(absolutePath) {
    return absolutePath.replace(BASE_PATH, '..');
}

// Mapping menu items to their layout files
const menuRoutes = {
    'profile.php': '../../layout/staff_profile/profile.php',
    'account.php': '../../layout/staff_account/account.php',
    'promotion.php': '../../layout/manage_promotion/promotion.php',
    'management_support.php': '../../layout/management_support/management_support.php',   
    'customer.php': '../../layout/manage_customer/customer.php'
};
const contentWrapper = document.getElementById('content-wrapper');
const menuItems = document.querySelectorAll('.menu-item a');

// Function to load content
async function loadContent(href) {
    console.log('loadContent called with:', href);
    
    try {
        let filePath = menuRoutes[href];
        if (!filePath) {
            console.error('Route not found for:', href);
            throw new Error('Route not found');
        }

        console.log('File path from routes:', filePath);

        // Convert absolute path back to relative path for fetch/iframe
        const relativePath = filePath.replace(BASE_PATH, '..');
        console.log('Loading:', relativePath);

        // Nếu là file PHP, load qua iframe
        if (relativePath.endsWith('.php')) {
            console.log('Loading PHP file via iframe');
            
            // Show loading
            contentWrapper.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
            
            // Tạo iframe để load PHP
            const iframe = document.createElement('iframe');
            iframe.src = relativePath;
            iframe.style.width = '100%';
            iframe.style.height = '100vh';
            iframe.style.border = 'none';
            iframe.style.overflow = 'auto';
            
            // Đợi iframe load xong
            iframe.onload = function() {
                console.log('PHP page loaded successfully');
            };
            
            iframe.onerror = function() {
                console.error('Failed to load PHP file');
            };
            
            contentWrapper.innerHTML = '';
            contentWrapper.appendChild(iframe);
            
            return;
        }

        // Show loading state cho HTML
        contentWrapper.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';

        console.log('Fetching:', relativePath);
        const response = await fetch(relativePath);
        console.log('Response status:', response.status, response.ok);
        
        if (!response.ok) {
            throw new Error('Failed to load content: ' + response.status);
        }

        const html = await response.text();
        console.log('HTML loaded, length:', html.length);
        
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Extract body content
        const bodyContent = doc.body.innerHTML;
        console.log('Body content extracted, length:', bodyContent.length);
        
        // Load CSS file
        const cssPath = relativePath.replace('.html', '.css');
        const cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.href = cssPath;
        cssLink.id = 'dynamic-css';
        
        // Remove previous dynamic CSS if exists
        const oldCss = document.getElementById('dynamic-css');
        if (oldCss) {
            oldCss.remove();
        }
        
        document.head.appendChild(cssLink);
        console.log('CSS loaded:', cssPath);
        
        // Load Font Awesome if not already loaded
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const faLink = document.createElement('link');
            faLink.rel = 'stylesheet';
            faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
            document.head.appendChild(faLink);
        }

        // Set content with fade in animation
        contentWrapper.innerHTML = bodyContent;
        contentWrapper.style.opacity = '0';
        setTimeout(() => {
            contentWrapper.style.transition = 'opacity 0.3s ease';
            contentWrapper.style.opacity = '1';
        }, 10);
        
        console.log('Content loaded successfully');

    } catch (error) {
        console.error('Error loading content:', error);
        contentWrapper.innerHTML = `
            <div class="error-message" style="color: white; padding: 40px; text-align: center;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px;"></i>
                <h3>Không thể tải nội dung</h3>
                <p>Đã xảy ra lỗi khi tải trang. Vui lòng thử lại.</p>
                <p style="font-size: 12px; color: rgba(255,255,255,0.5);">Lỗi: ${error.message}</p>
                <button onclick="location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Tải lại trang
                </button>
            </div>
        `;
    }
}

// Function to update active menu item
function updateActiveMenu(clickedLink) {
    // Remove active class from all menu items
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to clicked item's parent
    clickedLink.closest('.menu-item').classList.add('active');
}

// Add click event listeners to menu items
menuItems.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        let href = link.getAttribute('href');
        
        // Remove # if exists
        if (href.startsWith('#')) {
            href = href.substring(1);
        }
        
        console.log('Clicked menu:', href);
        
        // Update active menu
        updateActiveMenu(link);
        
        // Load content
        loadContent(href);
        
        // Update URL hash (no need to add # as location.hash does it automatically)
        window.location.hash = href;
    });
});

// Load default content on page load
window.addEventListener('DOMContentLoaded', () => {
    // Get hash from URL (remove # character)
    const hash = window.location.hash.substring(1);
    const defaultPage = 'profile.php';
    
    // Determine which page to load
    let pageToLoad = defaultPage;
    
    if (hash && menuRoutes[hash]) {
        // If valid hash exists, load that page
        pageToLoad = hash;
    }
    
    // Find and activate the corresponding menu item
    const menuLink = document.querySelector(`.menu-item a[href="#${pageToLoad}"]`) || 
                     document.querySelector(`.menu-item a[href="${pageToLoad}"]`);
    if (menuLink) {
        updateActiveMenu(menuLink);
        loadContent(pageToLoad);
        
        // Set hash if not already set
        if (!hash) {
            window.location.hash = pageToLoad;
        }
    }
});

// Handle hash changes (browser back/forward buttons)
window.addEventListener('hashchange', () => {
    const hash = window.location.hash.substring(1);
    
    if (hash && menuRoutes[hash]) {
        const menuLink = document.querySelector(`.menu-item a[href="#${hash}"]`) || 
                         document.querySelector(`.menu-item a[href="${hash}"]`);
        if (menuLink) {
            updateActiveMenu(menuLink);
            loadContent(hash);
        }
    } else {
        // If invalid hash, redirect to default page
        window.location.hash = 'profile.php';
    }
});