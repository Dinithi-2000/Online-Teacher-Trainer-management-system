// TeachVerse - Main JavaScript Functions

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize Application
function initializeApp() {
    initStarRating();
    initModals();
    initDropdowns();
    initFormValidation();
    initSearchFilters();
    initProgressBars();
    initTooltips();
    initMobileMenu();
}

// Mobile Menu Toggle
function toggleMobileMenu() {
    const navMenu = document.getElementById('navMenu');
    if (navMenu) {
        navMenu.classList.toggle('active');
    }
}

function initMobileMenu() {
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const navMenu = document.getElementById('navMenu');
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        
        if (navMenu && !navMenu.contains(event.target) && !mobileToggle.contains(event.target)) {
            navMenu.classList.remove('active');
        }
    });
    
    // Close mobile menu when clicking on a link
    const navLinks = document.querySelectorAll('#navMenu a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            const navMenu = document.getElementById('navMenu');
            if (navMenu) {
                navMenu.classList.remove('active');
            }
        });
    });
}

// Star Rating System
function initStarRating() {
    const starContainers = document.querySelectorAll('.star-rating');
    
    starContainers.forEach(container => {
        const stars = container.querySelectorAll('.star');
        const ratingInput = container.querySelector('input[type="hidden"]');
        
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                const rating = index + 1;
                if (ratingInput) {
                    ratingInput.value = rating;
                }
                updateStars(stars, rating);
            });
            
            star.addEventListener('mouseover', function() {
                const rating = index + 1;
                updateStars(stars, rating);
            });
        });
        
        container.addEventListener('mouseleave', function() {
            const currentRating = ratingInput ? ratingInput.value : 0;
            updateStars(stars, currentRating);
        });
    });
}

function updateStars(stars, rating) {
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('filled');
        } else {
            star.classList.remove('filled');
        }
    });
}

// Modal Functions
function initModals() {
    const modals = document.querySelectorAll('.modal');
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const closeBtns = document.querySelectorAll('.close');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                showModal(modal);
            }
        });
    });
    
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                hideModal(modal);
            }
        });
    });
    
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideModal(modal);
            }
        });
    });
}

function showModal(modal) {
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function hideModal(modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Dropdown Functions
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    document.addEventListener('click', function(e) {
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                const content = dropdown.querySelector('.dropdown-content');
                if (content) {
                    content.style.display = 'none';
                }
            }
        });
    });
}

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email');
            isValid = false;
        }
    });
    
    // Password confirmation
    const passwordField = form.querySelector('input[name="password"]');
    const confirmField = form.querySelector('input[name="confirm_password"]');
    
    if (passwordField && confirmField && passwordField.value !== confirmField.value) {
        showFieldError(confirmField, 'Passwords do not match');
        isValid = false;
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#ef4444';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.25rem';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
    field.style.borderColor = '#ef4444';
}

function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '';
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Search and Filter Functions
function initSearchFilters() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const targetSelector = this.getAttribute('data-target');
            const items = document.querySelectorAll(targetSelector);
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}

// Progress Bar Animation
function initProgressBars() {
    const progressBars = document.querySelectorAll('.progress-bar');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const progressBar = entry.target;
                const targetWidth = progressBar.getAttribute('data-width') || '0%';
                progressBar.style.width = targetWidth;
            }
        });
    });
    
    progressBars.forEach(bar => {
        observer.observe(bar);
    });
}

// Tooltip Functions
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const element = e.target;
    const tooltipText = element.getAttribute('data-tooltip');
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.cssText = `
        position: absolute;
        background: #1f2937;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        white-space: nowrap;
        z-index: 1000;
        pointer-events: none;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    element._tooltip = tooltip;
}

function hideTooltip(e) {
    const element = e.target;
    if (element._tooltip) {
        element._tooltip.remove();
        delete element._tooltip;
    }
}

// AJAX Helper Functions
function makeAjaxRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        
        if (method === 'POST' && data instanceof FormData) {
            // Don't set content type for FormData
        } else if (method === 'POST') {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        resolve(xhr.responseText);
                    }
                } else {
                    reject(new Error('Request failed'));
                }
            }
        };
        
        xhr.send(data);
    });
}

// Show Alert Messages
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Confirm Dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Loading State
function showLoading(element) {
    const loading = document.createElement('span');
    loading.className = 'loading';
    loading.style.marginLeft = '0.5rem';
    element.appendChild(loading);
    element.disabled = true;
}

function hideLoading(element) {
    const loading = element.querySelector('.loading');
    if (loading) {
        loading.remove();
    }
    element.disabled = false;
}

// Format Currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Format Date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Debounce Function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Course Enrollment Function
function enrollInCourse(courseId, userId) {
    const data = new FormData();
    data.append('course_id', courseId);
    data.append('user_id', userId);
    
    makeAjaxRequest('/modules/enrollments/enroll.php', 'POST', data)
        .then(response => {
            if (response.success) {
                showAlert('Successfully enrolled in course!', 'success');
                location.reload();
            } else {
                showAlert(response.message || 'Enrollment failed', 'error');
            }
        })
        .catch(error => {
            showAlert('An error occurred', 'error');
        });
}

// Update Progress Function
function updateProgress(enrollId, progress) {
    const data = new FormData();
    data.append('enroll_id', enrollId);
    data.append('progress', progress);
    
    makeAjaxRequest('/modules/enrollments/update_progress.php', 'POST', data)
        .then(response => {
            if (response.success) {
                showAlert('Progress updated!', 'success');
            } else {
                showAlert(response.message || 'Update failed', 'error');
            }
        })
        .catch(error => {
            showAlert('An error occurred', 'error');
        });
}

// Delete Confirmation
function deleteItem(url, itemName = 'item') {
    if (confirm(`Are you sure you want to delete this ${itemName}? This action cannot be undone.`)) {
        window.location.href = url;
    }
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Smooth scrolling for anchor links
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
