// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    // Notification functionality
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            // Add notification dropdown functionality here
            showNotification('Notifications feature coming soon!', 'info');
        });
    }
    
    // Admin user menu functionality
    const userMenu = document.querySelector('.admin-user-menu');
    if (userMenu) {
        userMenu.addEventListener('click', function() {
            // Add user menu dropdown functionality here
            console.log('User menu clicked');
        });
    }
    
    // Real-time stats update (demo)
    updateStatsAnimation();
    
    // Auto-refresh dashboard data every 30 seconds
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            refreshDashboardData();
        }
    }, 30000);
});

// Stats animation on load
function updateStatsAnimation() {
    const statNumbers = document.querySelectorAll('.stat-content h3');
    
    statNumbers.forEach(function(element) {
        const finalNumber = parseInt(element.textContent);
        let currentNumber = 0;
        const increment = Math.ceil(finalNumber / 50);
        
        const timer = setInterval(function() {
            currentNumber += increment;
            if (currentNumber >= finalNumber) {
                currentNumber = finalNumber;
                clearInterval(timer);
            }
            
            // Handle decimal values (like ratings)
            if (element.textContent.includes('.')) {
                element.textContent = (currentNumber / 10).toFixed(1);
            } else {
                element.textContent = currentNumber.toLocaleString();
            }
        }, 30);
    });
}

// Refresh dashboard data
function refreshDashboardData() {
    // This would typically make an AJAX call to refresh stats
    console.log('Refreshing dashboard data...');
    
    // Show a subtle indicator that data is being refreshed
    showNotification('Dashboard data refreshed', 'success', 2000);
}

// Show notification function
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `admin-notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button class="notification-close">&times;</button>
    `;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        animation: slideInRight 0.3s ease;
        max-width: 300px;
    `;
    
    document.body.appendChild(notification);
    
    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', function() {
        removeNotification(notification);
    });
    
    // Auto remove
    setTimeout(function() {
        removeNotification(notification);
    }, duration);
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function getNotificationColor(type) {
    const colors = {
        'success': '#27ae60',
        'error': '#e74c3c',
        'warning': '#f39c12',
        'info': '#3498db'
    };
    return colors[type] || '#3498db';
}

function removeNotification(notification) {
    notification.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(function() {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Add CSS animations for notifications
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
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
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        margin-left: auto;
        padding: 0;
        opacity: 0.8;
    }
    
    .notification-close:hover {
        opacity: 1;
    }
`;
document.head.appendChild(notificationStyles);

// Dashboard chart functionality (if charts are added later)
function initializeCharts() {
    // Placeholder for chart initialization
    console.log('Charts would be initialized here');
}

// Export functions for use in other admin pages
window.AdminDashboard = {
    showNotification: showNotification,
    refreshDashboardData: refreshDashboardData
};
