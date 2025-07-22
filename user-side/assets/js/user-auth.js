/**
 * EduMentor Pro User Authentication System
 * Handles user login status and UI updates across all user pages
 */
class UserAuth {
    constructor() {
        this.init();
    }

    init() {
        this.checkAuthStatus();
        this.updateNavigation();
        this.bindLogoutEvents();
    }

    // Check if user is logged in
    isLoggedIn() {
        return sessionStorage.getItem('user_logged_in') === 'true';
    }

    // Get current user data
    getCurrentUser() {
        if (!this.isLoggedIn()) return null;
        
        return {
            id: sessionStorage.getItem('user_id'),
            email: sessionStorage.getItem('user_email'),
            name: sessionStorage.getItem('user_name'),
            loginTime: sessionStorage.getItem('user_login_time')
        };
    }

    // Update navigation based on auth status
    updateNavigation() {
        const navbarActions = document.querySelector('.navbar-actions');
        if (!navbarActions) return;

        if (this.isLoggedIn()) {
            // User is logged in - show user menu
            this.showUserMenu(navbarActions);
        } else {
            // User is not logged in - show login/signup buttons
            this.showAuthButtons(navbarActions);
        }
    }

    // Show user menu for logged in users
    showUserMenu(container) {
        const user = this.getCurrentUser();
        
        container.innerHTML = `
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name">${user.name}</span>
                        <span class="user-email">${user.email}</span>
                    </div>
                </div>
                <div class="user-actions">
                    <a href="profile.html" class="btn btn-outline btn-sm">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>
                    <button onclick="userAuth.logout()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
        `;
    }

    // Show login/signup buttons for non-logged users
    showAuthButtons(container) {
        container.innerHTML = `
            <a href="login.html" class="btn btn-outline btn-sm">Login</a>
            <a href="register.html" class="btn btn-primary btn-sm">Sign Up</a>
        `;
    }

    // Check if user should be redirected from auth pages
    checkAuthStatus() {
        const currentPage = window.location.pathname.split('/').pop();
        const authPages = ['login.html', 'login-modern.html', 'register.html', 'register-modern.html'];
        
        // If user is logged in and on auth page, redirect to home
        if (this.isLoggedIn() && authPages.includes(currentPage)) {
            window.location.href = 'index.html';
            return;
        }
    }

    // Logout functionality
    logout() {
        // Show confirmation dialog
        if (confirm('Are you sure you want to logout?')) {
            // Clear session data
            sessionStorage.removeItem('user_logged_in');
            sessionStorage.removeItem('user_id');
            sessionStorage.removeItem('user_email');
            sessionStorage.removeItem('user_name');
            sessionStorage.removeItem('user_login_time');
            
            // Show logout message
            this.showNotification('You have been logged out successfully!', 'info');
            
            // Redirect to home page after a delay
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1500);
        }
    }

    // Bind logout events
    bindLogoutEvents() {
        // Handle logout from any logout button
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-logout]')) {
                e.preventDefault();
                this.logout();
            }
        });

        // Auto-logout on session expiry (optional)
        this.checkSessionExpiry();
    }

    // Check session expiry (auto-logout after 24 hours)
    checkSessionExpiry() {
        if (!this.isLoggedIn()) return;

        const loginTime = sessionStorage.getItem('user_login_time');
        if (!loginTime) return;

        const loginDate = new Date(loginTime);
        const now = new Date();
        const hoursSinceLogin = (now - loginDate) / (1000 * 60 * 60);

        // Auto-logout after 24 hours
        if (hoursSinceLogin > 24) {
            this.showNotification('Your session has expired. Please login again.', 'warning');
            setTimeout(() => {
                this.logout();
            }, 2000);
        }
    }

    // Show notification
    showNotification(message, type = 'info') {
        // Create notification container if it doesn't exist
        let notificationContainer = document.getElementById('notification-container');
        if (!notificationContainer) {
            notificationContainer = document.createElement('div');
            notificationContainer.id = 'notification-container';
            notificationContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 350px;
            `;
            document.body.appendChild(notificationContainer);
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: ${this.getNotificationColor(type)};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        `;

        notification.innerHTML = `
            <i class="fas fa-${this.getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; margin-left: auto;">
                <i class="fas fa-times"></i>
            </button>
        `;

        notificationContainer.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    }

    getNotificationColor(type) {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        return colors[type] || colors.info;
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || icons.info;
    }
}

// Initialize authentication system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.userAuth = new UserAuth();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UserAuth;
}
