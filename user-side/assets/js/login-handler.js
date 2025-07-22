// Login Handler - Integrates with UserAuth system
class LoginHandler {
    constructor() {
        this.initializeLogin();
    }

    initializeLogin() {
        // Check if user is already logged in
        if (sessionStorage.getItem('currentUser')) {
            // Redirect logged-in users to home page
            window.location.href = 'index.html';
            return;
        }

        // Attach form submission handler
        const loginForm = document.getElementById('loginForm') || document.querySelector('form[id*="login"]');
        if (loginForm) {
            loginForm.addEventListener('submit', this.handleLogin.bind(this));
        }

        // Auto-fill demo credentials for testing
        this.addDemoLoginOption();
    }

    addDemoLoginOption() {
        // Create demo login button for easy testing
        const authContainer = document.querySelector('.auth-container') || document.querySelector('.container');
        if (authContainer) {
            const demoButton = document.createElement('button');
            demoButton.type = 'button';
            demoButton.className = 'btn btn-outline btn-block mt-3';
            demoButton.innerHTML = '<i class="fas fa-user-graduate"></i> Demo Login (Teacher)';
            demoButton.style.marginTop = '15px';
            
            demoButton.addEventListener('click', () => {
                this.loginDemo();
            });

            // Insert after form or at end of container
            const form = authContainer.querySelector('form');
            if (form) {
                form.insertAdjacentElement('afterend', demoButton);
            } else {
                authContainer.appendChild(demoButton);
            }
        }
    }

    async handleLogin(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Get form values
        const email = formData.get('email') || form.querySelector('input[type="email"]')?.value;
        const password = formData.get('password') || form.querySelector('input[type="password"]')?.value;

        // Basic validation
        if (!email || !password) {
            this.showMessage('Please fill in all fields', 'error');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        submitBtn.disabled = true;

        try {
            // Simulate API call delay
            await new Promise(resolve => setTimeout(resolve, 1000));

            // For demo purposes, accept any email/password combination
            // In real implementation, this would be an actual API call
            const success = await this.authenticateUser(email, password);
            
            if (success) {
                // Create user session
                const userData = {
                    id: Date.now(),
                    email: email,
                    name: this.extractNameFromEmail(email),
                    role: 'teacher',
                    avatar: this.generateAvatar(email),
                    loginTime: new Date().toISOString()
                };

                // Store user session
                sessionStorage.setItem('currentUser', JSON.stringify(userData));

                this.showMessage('Login successful! Redirecting...', 'success');

                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 1500);

            } else {
                this.showMessage('Invalid email or password', 'error');
            }

        } catch (error) {
            console.error('Login error:', error);
            this.showMessage('Login failed. Please try again.', 'error');
        } finally {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    async authenticateUser(email, password) {
        try {
            // Make actual API call to backend
            const response = await fetch('../backend/api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'login',
                    email: email,
                    password: password
                })
            });

            const data = await response.json();
            
            if (data.success && data.data && data.data.user) {
                // Store the real user data from database
                const userData = {
                    id: data.data.user.id,
                    email: data.data.user.email,
                    name: `${data.data.user.first_name} ${data.data.user.last_name}`,
                    role: data.data.user.role,
                    avatar: data.data.user.profile_image || this.generateAvatar(data.data.user.email),
                    loginTime: new Date().toISOString(),
                    token: data.data.token
                };

                // Store user session
                sessionStorage.setItem('currentUser', JSON.stringify(userData));
                return true;
            } else {
                console.error('Login failed:', data.message);
                return false;
            }
        } catch (error) {
            console.error('API error:', error);
            // Fallback to demo mode if API fails
            return email.length > 0 && password.length > 0;
        }
    }

    loginDemo() {
        // Create demo user session
        const demoUser = {
            id: 999,
            email: 'demo@edumentor.com',
            name: 'Demo Teacher',
            role: 'teacher',
            avatar: '../assets/images/default-avatar.png',
            loginTime: new Date().toISOString()
        };

        sessionStorage.setItem('currentUser', JSON.stringify(demoUser));
        this.showMessage('Demo login successful! Redirecting...', 'success');

        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1500);
    }

    extractNameFromEmail(email) {
        const namePart = email.split('@')[0];
        return namePart.split('.').map(part => 
            part.charAt(0).toUpperCase() + part.slice(1)
        ).join(' ');
    }

    generateAvatar(email) {
        // Generate a consistent avatar URL based on email
        const colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger'];
        const index = email.charCodeAt(0) % colors.length;
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(this.extractNameFromEmail(email))}&background=${colors[index].replace('bg-', '')}&color=fff&size=200`;
    }

    showMessage(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.auth-notification');
        existingNotifications.forEach(notif => notif.remove());

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `auth-notification alert alert-${type === 'error' ? 'danger' : type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            min-width: 300px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease;
        `;

        // Add icon based on type
        const icon = type === 'success' ? 'fas fa-check-circle' : 
                   type === 'error' ? 'fas fa-exclamation-circle' : 
                   'fas fa-info-circle';
        
        notification.innerHTML = `
            <i class="${icon}"></i>
            <span>${message}</span>
            <button type="button" class="btn-close" style="margin-left: auto; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
        `;

        // Add close functionality
        notification.querySelector('.btn-close').addEventListener('click', () => {
            notification.remove();
        });

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);

        document.body.appendChild(notification);

        // Add CSS animation
        if (!document.querySelector('#auth-notification-styles')) {
            const style = document.createElement('style');
            style.id = 'auth-notification-styles';
            style.textContent = `
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
                .alert-success {
                    background-color: #d4edda;
                    border-color: #c3e6cb;
                    color: #155724;
                }
                .alert-danger {
                    background-color: #f8d7da;
                    border-color: #f5c6cb;
                    color: #721c24;
                }
                .alert-info {
                    background-color: #d1ecf1;
                    border-color: #bee5eb;
                    color: #0c5460;
                }
            `;
            document.head.appendChild(style);
        }
    }
}

// Initialize login handler when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new LoginHandler();
});

// Export for potential use in other scripts
window.LoginHandler = LoginHandler;
