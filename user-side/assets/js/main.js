// EduMentor Pro - Main JavaScript File

// Global Configuration
const CONFIG = {
    API_BASE_URL: '/EduMentor-Pro/backend/api/',
    SITE_URL: '/EduMentor-Pro/',
    VERSION: '1.0.0'
};

// Utility Functions
const Utils = {
    // AJAX Helper
    ajax: async function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        };

        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(CONFIG.API_BASE_URL + url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Something went wrong');
            }
            
            return data;
        } catch (error) {
            console.error('AJAX Error:', error);
            throw error;
        }
    },

    // Show loading spinner
    showLoading: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        element.innerHTML = '<div class="spinner"></div>';
    },

    // Hide loading spinner
    hideLoading: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        // Implementation depends on what content should be restored
    },

    // Show alert message
    showAlert: function(message, type = 'info', container = 'body') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        const targetContainer = document.querySelector(container);
        targetContainer.insertBefore(alertDiv, targetContainer.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 5000);
    },

    // Format date
    formatDate: function(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return new Date(dateString).toLocaleDateString(undefined, options);
    },

    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },

    // Validate email
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Validate form
    validateForm: function(formElement) {
        const inputs = formElement.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('error');
            } else {
                input.classList.remove('error');
            }
        });
        
        return isValid;
    },

    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Get URL parameters
    getUrlParams: function() {
        const urlParams = new URLSearchParams(window.location.search);
        const params = {};
        for (const [key, value] of urlParams) {
            params[key] = value;
        }
        return params;
    },

    // Update URL parameters
    updateUrlParams: function(params) {
        const url = new URL(window.location);
        Object.keys(params).forEach(key => {
            if (params[key]) {
                url.searchParams.set(key, params[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        window.history.pushState({}, '', url);
    }
};

// Navigation Component
const Navigation = {
    init: function() {
        this.setupMobileMenu();
        this.setupDropdowns();
        this.highlightActiveLink();
    },

    setupMobileMenu: function() {
        const toggleBtn = document.querySelector('.navbar-toggle');
        const navMenu = document.querySelector('.navbar-nav');
        const navActions = document.querySelector('.navbar-actions');
        const body = document.body;

        if (toggleBtn && navMenu) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Toggle classes
                navMenu.classList.toggle('show');
                toggleBtn.classList.toggle('active');
                
                // Toggle navbar actions on mobile if they exist
                if (navActions && window.innerWidth <= 768) {
                    navActions.classList.toggle('show');
                }
                
                // Prevent body scroll when menu is open
                if (navMenu.classList.contains('show')) {
                    body.style.overflow = 'hidden';
                } else {
                    body.style.overflow = '';
                }
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!toggleBtn.contains(e.target) && !navMenu.contains(e.target)) {
                    navMenu.classList.remove('show');
                    toggleBtn.classList.remove('active');
                    if (navActions) {
                        navActions.classList.remove('show');
                    }
                    body.style.overflow = '';
                }
            });
            
            // Close menu when clicking on nav links
            const navLinks = navMenu.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    navMenu.classList.remove('show');
                    toggleBtn.classList.remove('active');
                    if (navActions) {
                        navActions.classList.remove('show');
                    }
                    body.style.overflow = '';
                });
            });
            
            // Handle window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) {
                    navMenu.classList.remove('show');
                    toggleBtn.classList.remove('active');
                    if (navActions) {
                        navActions.classList.remove('show');
                    }
                    body.style.overflow = '';
                }
            });
        }
    },

    setupDropdowns: function() {
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            if (toggle && menu) {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    menu.classList.toggle('show');
                });
            }
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    },

    highlightActiveLink: function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    }
};

// Modal Component
const Modal = {
    open: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    },

    close: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    },

    init: function() {
        // Setup close buttons
        document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                if (modal) {
                    this.close(modal.id);
                }
            });
        });

        // Close modal when clicking backdrop
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.close(modal.id);
                }
            });
        });
    }
};

// Form Handler
const FormHandler = {
    init: function() {
        this.setupFormSubmissions();
        this.setupFormValidation();
    },

    setupFormSubmissions: function() {
        document.querySelectorAll('form[data-ajax]').forEach(form => {
            form.addEventListener('submit', this.handleAjaxSubmit.bind(this));
        });
    },

    setupFormValidation: function() {
        document.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('blur', this.validateField);
            input.addEventListener('input', this.clearFieldError);
        });
    },

    handleAjaxSubmit: async function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Convert FormData to JSON
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        try {
            // Disable submit button
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="spinner"></div> Processing...';
            }

            const response = await Utils.ajax(form.action, {
                method: form.method || 'POST',
                body: JSON.stringify(data)
            });

            Utils.showAlert(response.message || 'Success!', 'success');
            
            // Reset form if successful
            if (response.success) {
                form.reset();
            }

        } catch (error) {
            Utils.showAlert(error.message || 'An error occurred', 'danger');
        } finally {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Submit';
            }
        }
    },

    validateField: function(e) {
        const field = e.target;
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Required validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }

        // Email validation
        if (field.type === 'email' && value && !Utils.validateEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }

        // Password validation
        if (field.type === 'password' && value && value.length < 6) {
            isValid = false;
            errorMessage = 'Password must be at least 6 characters long';
        }

        // Show/hide error
        if (!isValid) {
            FormHandler.showFieldError(field, errorMessage);
        } else {
            FormHandler.clearFieldError(field);
        }

        return isValid;
    },

    showFieldError: function(field, message) {
        field.classList.add('error');
        
        // Remove existing error message
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-danger';
        errorDiv.textContent = message;
        field.parentElement.appendChild(errorDiv);
    },

    clearFieldError: function(e) {
        const field = e.target || e;
        field.classList.remove('error');
        
        const errorDiv = field.parentElement.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
};

// Search Functionality
const Search = {
    init: function() {
        this.setupSearchInputs();
        this.setupFilters();
    },

    setupSearchInputs: function() {
        const searchInputs = document.querySelectorAll('input[data-search]');
        
        searchInputs.forEach(input => {
            const debouncedSearch = Utils.debounce(this.performSearch.bind(this), 300);
            input.addEventListener('input', debouncedSearch);
        });
    },

    setupFilters: function() {
        const filterInputs = document.querySelectorAll('select[data-filter], input[data-filter]');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', this.applyFilters.bind(this));
        });
    },

    performSearch: async function(e) {
        const input = e.target;
        const searchTerm = input.value.trim();
        const searchType = input.getAttribute('data-search');
        const resultsContainer = document.querySelector(`#${searchType}-results`);

        if (!searchTerm) {
            this.loadDefaultResults(searchType, resultsContainer);
            return;
        }

        try {
            Utils.showLoading(resultsContainer);
            
            const response = await Utils.ajax(`search/${searchType}`, {
                method: 'POST',
                body: JSON.stringify({ query: searchTerm })
            });

            this.displayResults(response.data, resultsContainer, searchType);
            
        } catch (error) {
            Utils.showAlert('Search failed: ' + error.message, 'danger');
        }
    },

    applyFilters: function() {
        const filters = {};
        const filterInputs = document.querySelectorAll('select[data-filter], input[data-filter]');
        
        filterInputs.forEach(input => {
            if (input.value) {
                filters[input.name] = input.value;
            }
        });

        // Update URL with filters
        Utils.updateUrlParams(filters);
        
        // Reload results with filters
        this.loadFilteredResults(filters);
    },

    loadDefaultResults: function(type, container) {
        // Implementation depends on the specific search type
        console.log('Loading default results for:', type);
    },

    loadFilteredResults: async function(filters) {
        const resultsContainer = document.querySelector('.results-container');
        
        try {
            Utils.showLoading(resultsContainer);
            
            const response = await Utils.ajax('filter', {
                method: 'POST',
                body: JSON.stringify(filters)
            });

            this.displayResults(response.data, resultsContainer, 'filtered');
            
        } catch (error) {
            Utils.showAlert('Filter failed: ' + error.message, 'danger');
        }
    },

    displayResults: function(results, container, type) {
        if (!results || results.length === 0) {
            container.innerHTML = '<div class="text-center p-4">No results found</div>';
            return;
        }

        let html = '';
        
        switch (type) {
            case 'courses':
                html = this.renderCourseResults(results);
                break;
            case 'trainers':
                html = this.renderTrainerResults(results);
                break;
            case 'blog':
                html = this.renderBlogResults(results);
                break;
            default:
                html = this.renderGenericResults(results);
        }

        container.innerHTML = html;
    },

    renderCourseResults: function(courses) {
        return courses.map(course => `
            <div class="card course-card">
                <img src="${course.image || '/assets/images/course-placeholder.jpg'}" alt="${course.title}" class="card-img">
                <div class="card-body">
                    <h3 class="card-title">${course.title}</h3>
                    <p class="card-text">${course.short_description}</p>
                    <div class="course-meta">
                        <span class="course-rating">★ ${course.rating}</span>
                        <span class="course-duration">${course.duration} hours</span>
                        <span class="course-level badge badge-primary">${course.level}</span>
                    </div>
                    <a href="/course/${course.id}" class="btn btn-primary">View Course</a>
                </div>
            </div>
        `).join('');
    },

    renderTrainerResults: function(trainers) {
        return trainers.map(trainer => `
            <div class="card trainer-card">
                <div class="card-body">
                    <img src="${trainer.profile_image || '/assets/images/trainer-placeholder.jpg'}" alt="${trainer.name}" class="trainer-avatar">
                    <h3 class="card-title">${trainer.first_name} ${trainer.last_name}</h3>
                    <p class="card-text">${trainer.bio || 'Experienced trainer'}</p>
                    <div class="trainer-meta">
                        <span class="trainer-rating">★ ${trainer.rating}</span>
                        <span class="trainer-students">${trainer.total_students} students</span>
                    </div>
                    <a href="/trainer/${trainer.id}" class="btn btn-outline">View Profile</a>
                </div>
            </div>
        `).join('');
    },

    renderBlogResults: function(posts) {
        return posts.map(post => `
            <div class="card">
                <img src="${post.featured_image || '/assets/images/blog-placeholder.jpg'}" alt="${post.title}" class="card-img">
                <div class="card-body">
                    <h3 class="card-title">${post.title}</h3>
                    <p class="card-text">${post.excerpt}</p>
                    <div class="blog-meta">
                        <span>${Utils.formatDate(post.created_at)}</span>
                        <span class="badge badge-secondary">${post.category}</span>
                    </div>
                    <a href="/blog/${post.slug}" class="btn btn-outline">Read More</a>
                </div>
            </div>
        `).join('');
    },

    renderGenericResults: function(results) {
        return results.map(result => `
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">${result.title || result.name}</h3>
                    <p class="card-text">${result.description || result.excerpt}</p>
                </div>
            </div>
        `).join('');
    }
};

// Progress Tracking
const ProgressTracker = {
    init: function() {
        this.updateProgressBars();
        this.setupProgressUpdates();
    },

    updateProgressBars: function() {
        const progressBars = document.querySelectorAll('.progress-bar[data-progress]');
        
        progressBars.forEach(bar => {
            const progress = parseFloat(bar.getAttribute('data-progress'));
            bar.style.width = `${Math.min(progress, 100)}%`;
        });
    },

    setupProgressUpdates: function() {
        // Setup automatic progress updates for course progress
        const courseProgress = document.querySelectorAll('[data-course-progress]');
        
        courseProgress.forEach(element => {
            const courseId = element.getAttribute('data-course-progress');
            this.trackCourseProgress(courseId);
        });
    },

    trackCourseProgress: async function(courseId) {
        try {
            const response = await Utils.ajax(`progress/course/${courseId}`);
            const progressElement = document.querySelector(`[data-course-progress="${courseId}"]`);
            
            if (progressElement && response.data) {
                this.updateProgressDisplay(progressElement, response.data.progress);
            }
        } catch (error) {
            console.error('Failed to track course progress:', error);
        }
    },

    updateProgressDisplay: function(element, progress) {
        const progressBar = element.querySelector('.progress-bar');
        const progressText = element.querySelector('.progress-text');
        
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
            progressBar.setAttribute('data-progress', progress);
        }
        
        if (progressText) {
            progressText.textContent = `${Math.round(progress)}% Complete`;
        }
    },

    updateProgress: async function(type, id, progress) {
        try {
            const response = await Utils.ajax(`progress/update`, {
                method: 'POST',
                body: JSON.stringify({
                    type: type,
                    id: id,
                    progress: progress
                })
            });

            if (response.success) {
                this.updateProgressDisplay(
                    document.querySelector(`[data-${type}-progress="${id}"]`), 
                    progress
                );
            }
        } catch (error) {
            console.error('Failed to update progress:', error);
        }
    }
};

// Dashboard functionality
const Dashboard = {
    init: function() {
        this.loadDashboardData();
        this.setupRefreshButtons();
    },

    loadDashboardData: async function() {
        try {
            const response = await Utils.ajax('dashboard/stats');
            this.updateDashboardCards(response.data);
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
        }
    },

    updateDashboardCards: function(data) {
        // Update various dashboard statistics
        Object.keys(data).forEach(key => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                element.textContent = data[key];
            }
        });
    },

    setupRefreshButtons: function() {
        const refreshButtons = document.querySelectorAll('[data-refresh]');
        
        refreshButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.loadDashboardData();
            });
        });
    }
};

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    Navigation.init();
    Modal.init();
    FormHandler.init();
    Search.init();
    ProgressTracker.init();
    
    // Initialize dashboard if on dashboard page
    if (document.body.classList.contains('dashboard-page')) {
        Dashboard.init();
    }
    
    // Setup smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Setup lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
});

// Export for global access
window.EduMentor = {
    Utils,
    Navigation,
    Modal,
    FormHandler,
    Search,
    ProgressTracker,
    Dashboard,
    CONFIG
};
