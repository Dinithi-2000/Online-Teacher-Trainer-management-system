// User Interface JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeUserInterface();
});

function initializeUserInterface() {
    // Initialize components
    initializeUserDropdown();
    initializeProgressAnimations();
    initializeInteractiveElements();
    initializeNotifications();
    
    // Add loading states
    addLoadingStates();
    
    // Initialize tooltips
    initializeTooltips();
    
    console.log('User interface initialized successfully');
}

// User Dropdown Functionality
function initializeUserDropdown() {
    const dropdown = document.querySelector('.user-dropdown');
    const toggle = document.querySelector('.user-dropdown-toggle');
    const menu = document.querySelector('.user-dropdown-menu');
    
    if (!dropdown || !toggle || !menu) return;
    
    // Toggle dropdown on click
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropdown.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
    
    // Close dropdown on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdown.classList.remove('active');
        }
    });
    
    // Add keyboard navigation
    const menuItems = menu.querySelectorAll('a');
    menuItems.forEach((item, index) => {
        item.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = (index + 1) % menuItems.length;
                menuItems[nextIndex].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = (index - 1 + menuItems.length) % menuItems.length;
                menuItems[prevIndex].focus();
            }
        });
    });
}

// Progress Animations
function initializeProgressAnimations() {
    const progressBars = document.querySelectorAll('.progress-fill');
    const progressCards = document.querySelectorAll('.progress-card');
    
    // Animate progress bars on load
    setTimeout(() => {
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    }, 500);
    
    // Add hover effects to progress cards
    progressCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-2px) scale(1)';
        });
    });
    
    // Animate numbers counting up
    animateCounters();
}

// Counter Animation
function animateCounters() {
    const counters = document.querySelectorAll('.progress-info h3');
    
    counters.forEach(counter => {
        const target = parseInt(counter.textContent) || 0;
        const duration = 1000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target + (counter.textContent.includes('%') ? '%' : '');
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current) + (counter.textContent.includes('%') ? '%' : '');
            }
        }, 16);
    });
}

// Interactive Elements
function initializeInteractiveElements() {
    // Course card interactions
    const courseCards = document.querySelectorAll('.course-card, .recommended-course');
    
    courseCards.forEach(card => {
        // Add ripple effect on click
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a')) return;
            
            const ripple = document.createElement('div');
            ripple.className = 'ripple';
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(99, 102, 241, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                pointer-events: none;
            `;
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
        
        // Add focus indicators
        card.addEventListener('focus', function() {
            this.style.outline = '2px solid #6366f1';
            this.style.outlineOffset = '2px';
        });
        
        card.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
    });
    
    // Button hover effects
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(99, 102, 241, 0.3)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
    
    // Star rating interactions
    const starRatings = document.querySelectorAll('.review-rating');
    starRatings.forEach(rating => {
        const stars = rating.querySelectorAll('i');
        stars.forEach((star, index) => {
            star.addEventListener('mouseenter', function() {
                stars.forEach((s, i) => {
                    s.style.color = i <= index ? '#fbbf24' : '#d1d5db';
                    s.style.transform = i <= index ? 'scale(1.1)' : 'scale(1)';
                });
            });
        });
        
        rating.addEventListener('mouseleave', function() {
            stars.forEach(star => {
                star.style.transform = 'scale(1)';
            });
        });
    });
}

// Notifications System
function initializeNotifications() {
    // Check for URL parameters to show success messages
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('enrolled') === 'success') {
        showNotification('Successfully enrolled in course!', 'success');
        // Clean URL
        window.history.replaceState({}, '', window.location.pathname);
    }
    
    if (urlParams.get('review') === 'success') {
        showNotification('Review submitted successfully!', 'success');
        window.history.replaceState({}, '', window.location.pathname);
    }
    
    if (urlParams.get('progress') === 'updated') {
        showNotification('Progress updated!', 'info');
        window.history.replaceState({}, '', window.location.pathname);
    }
}

// Show Notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icon = type === 'success' ? 'fas fa-check-circle' : 
                 type === 'error' ? 'fas fa-exclamation-circle' : 
                 'fas fa-info-circle';
    
    notification.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#06b6d4'};
        color: white;
        padding: 16px 20px;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 9999;
        max-width: 400px;
        animation: slideInRight 0.3s ease-out;
        font-weight: 500;
    `;
    
    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.style.cssText = `
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        margin-left: auto;
    `;
    
    closeBtn.addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    });
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Loading States
function addLoadingStates() {
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.classList.contains('loading')) return;
            
            // Don't add loading state for navigation links
            if (this.href && !this.href.includes('enroll') && !this.href.includes('review')) {
                return;
            }
            
            this.classList.add('loading');
            const originalText = this.innerHTML;
            
            this.innerHTML = `
                <div class="spinner"></div>
                <span>Loading...</span>
            `;
            
            // Add spinner styles
            const spinner = this.querySelector('.spinner');
            if (spinner) {
                spinner.style.cssText = `
                    width: 16px;
                    height: 16px;
                    border: 2px solid transparent;
                    border-top: 2px solid currentColor;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                `;
            }
            
            // Reset after 3 seconds (in case of network issues)
            setTimeout(() => {
                this.classList.remove('loading');
                this.innerHTML = originalText;
            }, 3000);
        });
    });
}

// Tooltips
function initializeTooltips() {
    const elementsWithTooltips = document.querySelectorAll('[data-tooltip]');
    
    elementsWithTooltips.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            
            tooltip.style.cssText = `
                position: absolute;
                background: #1f2937;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 9999;
                pointer-events: none;
                opacity: 0;
                transform: translateY(10px);
                transition: all 0.2s ease;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.bottom + 8 + 'px';
            
            setTimeout(() => {
                tooltip.style.opacity = '1';
                tooltip.style.transform = 'translateY(0)';
            }, 10);
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

// Utility Functions
function formatPrice(price) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
}

function formatDate(dateString) {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    }).format(new Date(dateString));
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
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
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .btn.loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .notification {
        transition: all 0.3s ease;
    }
    
    .tooltip {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    /* Smooth scrolling */
    html {
        scroll-behavior: smooth;
    }
    
    /* Focus indicators */
    .course-card:focus,
    .recommended-course:focus,
    .review-item:focus {
        outline: 2px solid #6366f1;
        outline-offset: 2px;
    }
    
    /* Mobile menu toggle */
    @media (max-width: 768px) {
        .nav-menu-toggle {
            display: block;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--user-text-primary);
            cursor: pointer;
        }
        
        .nav-menu {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--user-surface);
            border-top: 1px solid var(--user-border);
            padding: 20px;
            transform: translateY(-100%);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .nav-menu.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .nav-link {
            display: block;
            margin-bottom: 8px;
        }
    }
`;
document.head.appendChild(style);

// Export functions for external use
window.UserInterface = {
    showNotification,
    formatPrice,
    formatDate
};
