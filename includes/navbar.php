<?php
// Ensure authentication functions are available
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/database.php';
}

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Determine base path for includes based on current directory depth
$base_path = '';
if ($current_dir === 'modules' || $current_dir === 'admin' || $current_dir === 'user') {
    $base_path = '../';
} elseif (in_array($current_dir, ['courses', 'users', 'reviews', 'enrollments', 'trainer_profiles'])) {
    $base_path = '../../';
}
?>

<!-- Unified Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="<?php echo $base_path; ?>index.php">
                <i class="fas fa-graduation-cap"></i>
                <span>TeachVerse</span>
            </a>
        </div>

        <ul class="nav-menu" id="navMenu">
            <li><a href="<?php echo $base_path; ?>index.php" <?php echo ($current_page === 'index.php' && $current_dir !== 'modules' && $current_dir !== 'admin') ? 'class="active"' : ''; ?>>Home</a></li>
            <li><a href="<?php echo $base_path; ?>courses.php" <?php echo ($current_page === 'courses.php') ? 'class="active"' : ''; ?>>Courses</a></li>
            <li><a href="<?php echo $base_path; ?>modules/trainer_profiles/" <?php echo ($current_dir === 'trainer_profiles') ? 'class="active"' : ''; ?>>Trainers</a></li>
            <li><a href="<?php echo $base_path; ?>modules/reviews/index.php" <?php echo ($current_dir === 'reviews') ? 'class="active"' : ''; ?>>Reviews</a></li>
            <li><a href="<?php echo $base_path; ?>about.php" <?php echo ($current_page === 'about.php') ? 'class="active"' : ''; ?>>About</a></li>
            <li><a href="<?php echo $base_path; ?>contact.php" <?php echo ($current_page === 'contact.php') ? 'class="active"' : ''; ?>>Support</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="<?php echo $base_path; ?>dashboard.php" <?php echo ($current_page === 'dashboard.php') ? 'class="active"' : ''; ?>>Dashboard</a></li>
            <?php endif; ?>
        </ul>

        <!-- Mobile menu toggle -->
        <div class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </div>

        <div class="auth-buttons">
            <?php if (isLoggedIn()): ?>
                <div class="user-dropdown">
                    <button class="dropdown-toggle" id="userDropdown">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars(getCurrentUser()['name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <a href="<?php echo $base_path; ?>dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="<?php echo $base_path; ?>modules/enrollments/">
                            <i class="fas fa-graduation-cap"></i> My Learning
                        </a>
                        <?php if (hasRole('admin')): ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo $base_path; ?>admin/">
                                <i class="fas fa-cog"></i> Admin Panel
                            </a>
                            <a href="<?php echo $base_path; ?>modules/courses/">
                                <i class="fas fa-book"></i> Manage Courses
                            </a>
                            <a href="<?php echo $base_path; ?>modules/users/">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                            <a href="<?php echo $base_path; ?>modules/trainer_profiles/">
                                <i class="fas fa-chalkboard-teacher"></i> Manage Trainers
                            </a>
                            <a href="<?php echo $base_path; ?>modules/enrollments/admin_index.php">
                                <i class="fas fa-clipboard-list"></i> All Enrollments
                            </a>
                        <?php endif; ?>
                        <?php if (hasRole('trainer')): ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo $base_path; ?>modules/courses/">
                                <i class="fas fa-book"></i> My Courses
                            </a>
                            <a href="<?php echo $base_path; ?>modules/trainer_profiles/">
                                <i class="fas fa-id-card"></i> My Trainer Profile
                            </a>
                            <a href="<?php echo $base_path; ?>modules/courses/create.php">
                                <i class="fas fa-plus-circle"></i> Create New Course
                            </a>
                        <?php endif; ?>
                        <?php if (hasRole('student')): ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo $base_path; ?>modules/enrollments/">
                                <i class="fas fa-book-reader"></i> My Courses
                            </a>
                            <a href="<?php echo $base_path; ?>courses.php">
                                <i class="fas fa-search"></i> Browse Courses
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $base_path; ?>modules/reviews/index.php">
                            <i class="fas fa-star"></i> Reviews & Ratings
                        </a>
                        <a href="<?php echo $base_path; ?>contact.php">
                            <i class="fas fa-envelope"></i> Support
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $base_path; ?>logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>login.php" class="btn btn-secondary">
                     LOGIN
                </a>
                <a href="<?php echo $base_path; ?>register.php" class="btn btn-primary">
                     SIGN UP
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
/* Unified Navigation Styles */
.navbar {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: all 0.3s ease;
}

.nav-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

.logo a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    font-weight: 800;
    text-decoration: none;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.logo i {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 2rem;
    align-items: center;
    margin: 0;
    padding: 0;
}

.nav-menu a {
    color: #64748b;
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.nav-menu a:hover,
.nav-menu a.active {
    color: #6366f1;
    background: rgba(99, 102, 241, 0.1);
}

.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #64748b;
    cursor: pointer;
    padding: 0.5rem;
}

.auth-buttons {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    white-space: nowrap;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.btn-secondary {
    background: transparent;
    color: #6366f1;
    border: 2px solid #6366f1;
}

.btn-secondary:hover {
    background: #6366f1;
    color: white;
}

/* User Dropdown */
.user-dropdown {
    position: relative;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: none;
    color: #64748b;
    font-weight: 500;
    cursor: pointer;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.dropdown-toggle:hover {
    color: #6366f1;
    background: rgba(99, 102, 241, 0.1);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1);
    padding: 0.5rem 0;
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s ease;
}

.dropdown-menu a:hover {
    background: #f1f5f9;
    color: #6366f1;
}

.dropdown-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 0.5rem 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .nav-menu {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        background: white;
        flex-direction: column;
        padding: 2rem;
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-20px);
        transition: all 0.3s ease;
    }
    
    .nav-menu.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .mobile-menu-toggle {
        display: block;
    }
    
    .auth-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .dropdown-menu {
        position: static;
        box-shadow: none;
        border: none;
        background: transparent;
        opacity: 1;
        visibility: visible;
        transform: none;
    }
}

@media (max-width: 480px) {
    .nav-container {
        padding: 1rem;
    }
    
    .logo a {
        font-size: 1.25rem;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
}
</style>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.getElementById('navMenu');
    const userDropdown = document.getElementById('userDropdown');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    // Mobile menu
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('open');
            const icon = this.querySelector('i');
            if (navMenu.classList.contains('open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
    
    // User dropdown
    if (userDropdown && userDropdownMenu) {
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            userDropdownMenu.classList.remove('show');
        });
        
        userDropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>
