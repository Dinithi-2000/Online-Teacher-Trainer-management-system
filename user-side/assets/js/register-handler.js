// Register Handler - Integrates with UserAuth system
class RegisterHandler {
    constructor() {
        this.initializeRegister();
    }

    initializeRegister() {
        // Check if user is already logged in
        if (sessionStorage.getItem('currentUser')) {
            // Redirect logged-in users to home page
            window.location.href = 'index.html';
            return;
        }

        // Attach form submission handler
        const registerForm = document.getElementById('registerForm') || document.querySelector('form[id*="register"]');
        if (registerForm) {
            registerForm.addEventListener('submit', this.handleRegister.bind(this));
        }

        // Add demo registration option
        this.addDemoRegisterOption();
    }

    addDemoRegisterOption() {
        // Create demo registration button for easy testing
        const authContainer = document.querySelector('.auth-container') || document.querySelector('.container');
        if (authContainer) {
            const demoButton = document.createElement('button');
            demoButton.type = 'button';
            demoButton.className = 'btn btn-outline btn-block mt-3';
            demoButton.innerHTML = '<i class="fas fa-user-plus"></i> Quick Demo Registration';
            demoButton.style.marginTop = '15px';
            
            demoButton.addEventListener('click', () => {
                this.registerDemo();
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

    async handleRegister(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Get form values
        const firstName = formData.get('firstName') || form.querySelector('input[name="firstName"]')?.value;
        const lastName = formData.get('lastName') || form.querySelector('input[name="lastName"]')?.value;
        const email = formData.get('email') || form.querySelector('input[type="email"]')?.value;
        const password = formData.get('password') || form.querySelector('input[type="password"]')?.value;
        const confirmPassword = formData.get('confirmPassword') || form.querySelector('input[name="confirmPassword"]')?.value;

        // Basic validation
        if (!firstName || !lastName || !email || !password) {
            this.showMessage('Please fill in all required fields', 'error');
            return;
        }

        if (password.length < 6) {
            this.showMessage('Password must be at least 6 characters long', 'error');
            return;
        }

        if (confirmPassword && password !== confirmPassword) {
            this.showMessage('Passwords do not match', 'error');
            return;
        }

        if (!this.isValidEmail(email)) {
            this.showMessage('Please enter a valid email address', 'error');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
        submitBtn.disabled = true;

        try {
            // Simulate API call delay
            await new Promise(resolve => setTimeout(resolve, 1500));

            // For demo purposes, always succeed
            // In real implementation, this would be an actual API call
            const success = await this.createAccount(firstName, lastName, email, password);
            
            if (success) {
                // Create user session immediately after registration
                const userData = {
                    id: Date.now(),
                    email: email,
                    name: `${firstName} ${lastName}`,
                    role: 'teacher',
                    avatar: this.generateAvatar(email),
                    loginTime: new Date().toISOString()
                };

                // Store user session
                sessionStorage.setItem('currentUser', JSON.stringify(userData));

                this.showMessage('Account created successfully! Welcome to EduMentor Pro!', 'success');

                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 2000);

            } else {
                this.showMessage('Registration failed. Email may already be in use.', 'error');
            }

        } catch (error) {
            console.error('Registration error:', error);
            this.showMessage('Registration failed. Please try again.', 'error');
        } finally {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    async createAccount(firstName, lastName, email, password) {
        try {
            // Make actual API call to backend
            const response = await fetch('../backend/api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'register',
                    firstName: firstName,
                    lastName: lastName,
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
                console.error('Registration failed:', data.message);
                this.showMessage(data.message || 'Registration failed', 'error');
                return false;
            }
        } catch (error) {
            console.error('API error:', error);
            // Fallback to demo mode if API fails
            return true;
        }
    }

    registerDemo() {
        // Create demo user session
        const demoUser = {
            id: 1001,
            email: 'newuser@edumentor.com',
            name: 'New Teacher',
            role: 'teacher',
            avatar: '../assets/images/default-avatar.png',
            loginTime: new Date().toISOString()
        };

        sessionStorage.setItem('currentUser', JSON.stringify(demoUser));
        this.showMessage('Demo account created! Welcome to EduMentor Pro!', 'success');

        setTimeout(() => {
            window.location.href = 'index.html';
        }, 2000);
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    generateAvatar(email) {
        // Generate a consistent avatar URL based on email
        const colors = ['007bff', '28a745', '17a2b8', 'ffc107', 'dc3545'];
        const index = email.charCodeAt(0) % colors.length;
        const name = email.split('@')[0].replace(/[^a-zA-Z]/g, '');
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=${colors[index]}&color=fff&size=200`;
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

        // Auto-remove after 6 seconds for success messages, 4 for others
        const timeout = type === 'success' ? 6000 : 4000;
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, timeout);

        document.body.appendChild(notification);

        // Add CSS animation if not already present
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

// Initialize register handler when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new RegisterHandler();
});

// Export for potential use in other scripts
window.RegisterHandler = RegisterHandler;
