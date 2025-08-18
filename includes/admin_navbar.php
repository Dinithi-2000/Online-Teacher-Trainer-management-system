<?php
// Ensure authentication functions are available
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/database.php';
}

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Determine base path for admin pages
$base_path = '../';
?>

<!-- Admin Navigation -->
<nav class="admin-navbar">
    <div class="admin-nav-container">
        <div class="admin-logo">
            <a href="<?php echo $base_path; ?>index.php">
                <i class="fas fa-graduation-cap"></i>
                <span>TeachVerse</span>
                <small>Admin Panel</small>
            </a>
        </div>

        <ul class="admin-nav-menu">
            <li><a href="index.php" <?php echo ($current_page === 'index.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-home"></i> Dashboard
            </a></li>
            <li><a href="<?php echo $base_path; ?>modules/users/" <?php echo ($current_dir === 'users') ? 'class="active"' : ''; ?>>
                <i class="fas fa-users"></i> Users
            </a></li>
            <li><a href="<?php echo $base_path; ?>modules/courses/" <?php echo ($current_dir === 'courses') ? 'class="active"' : ''; ?>>
                <i class="fas fa-book"></i> Courses
            </a></li>
            <li><a href="<?php echo $base_path; ?>modules/enrollments/" <?php echo ($current_dir === 'enrollments') ? 'class="active"' : ''; ?>>
                <i class="fas fa-clipboard-list"></i> Enrollments
            </a></li>
            <li><a href="<?php echo $base_path; ?>modules/reviews/" <?php echo ($current_dir === 'reviews') ? 'class="active"' : ''; ?>>
                <i class="fas fa-star"></i> Reviews
            </a></li>
            <li><a href="<?php echo $base_path; ?>modules/trainer_profiles/" <?php echo ($current_dir === 'trainer_profiles') ? 'class="active"' : ''; ?>>
                <i class="fas fa-chalkboard-teacher"></i> Trainers
            </a></li>
            <li><a href="analytics.php" <?php echo ($current_page === 'analytics.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-chart-bar"></i> Analytics
            </a></li>
            <li><a href="settings.php" <?php echo ($current_page === 'settings.php') ? 'class="active"' : ''; ?>>
                <i class="fas fa-cog"></i> Settings
            </a></li>
        </ul>

        <div class="admin-auth-buttons">
            <?php if (isLoggedIn()): ?>
                <div class="admin-user-dropdown">
                    <button class="admin-dropdown-toggle" id="adminUserDropdown">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars(getCurrentUser()['name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="admin-dropdown-menu" id="adminUserDropdownMenu">
                        <a href="<?php echo $base_path; ?>dashboard.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                        <a href="<?php echo $base_path; ?>courses.php">
                            <i class="fas fa-eye"></i> View Site
                        </a>
                        <div class="admin-dropdown-divider"></div>
                        <a href="<?php echo $base_path; ?>logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile menu toggle -->
        <div class="admin-mobile-menu-toggle" id="adminMobileMenuToggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</nav>

<style>
/* Admin Navigation Styles */
.admin-navbar {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-bottom: 1px solid #475569;
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: all 0.3s ease;
}

.admin-nav-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

.admin-logo a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: white;
}

.admin-logo i {
    font-size: 1.5rem;
    color: #6366f1;
}

.admin-logo span {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
}

.admin-logo small {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 500;
    margin-left: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 4px;
    border: 1px solid rgba(99, 102, 241, 0.2);
}

.admin-nav-menu {
    display: flex;
    list-style: none;
    gap: 0.5rem;
    align-items: center;
    margin: 0;
    padding: 0;
}

.admin-nav-menu a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #cbd5e1;
    text-decoration: none;
    font-weight: 500;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.admin-nav-menu a:hover,
.admin-nav-menu a.active {
    color: white;
    background: rgba(99, 102, 241, 0.2);
    transform: translateY(-2px);
}

.admin-nav-menu a i {
    font-size: 0.9rem;
}

.admin-mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #cbd5e1;
    cursor: pointer;
    padding: 0.5rem;
}

.admin-auth-buttons {
    display: flex;
    gap: 1rem;
    align-items: center;
}

/* Admin User Dropdown */
.admin-user-dropdown {
    position: relative;
}

.admin-dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 500;
    cursor: pointer;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.admin-dropdown-toggle:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.admin-dropdown-menu {
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

.admin-dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.admin-dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s ease;
}

.admin-dropdown-menu a:hover {
    background: #f1f5f9;
    color: #6366f1;
}

.admin-dropdown-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 0.5rem 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .admin-nav-menu {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        flex-direction: column;
        padding: 2rem;
        border-top: 1px solid #475569;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-20px);
        transition: all 0.3s ease;
    }
    
    .admin-nav-menu.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .admin-mobile-menu-toggle {
        display: block;
    }
    
    .admin-auth-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .admin-dropdown-menu {
        position: static;
        box-shadow: none;
        border: none;
        background: transparent;
        opacity: 1;
        visibility: visible;
        transform: none;
    }
}
</style>

<script>
// Admin mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const adminMobileMenuToggle = document.getElementById('adminMobileMenuToggle');
    const adminNavMenu = document.querySelector('.admin-nav-menu');
    const adminUserDropdown = document.getElementById('adminUserDropdown');
    const adminUserDropdownMenu = document.getElementById('adminUserDropdownMenu');
    
    // Admin mobile menu
    if (adminMobileMenuToggle && adminNavMenu) {
        adminMobileMenuToggle.addEventListener('click', function() {
            adminNavMenu.classList.toggle('open');
            const icon = this.querySelector('i');
            if (adminNavMenu.classList.contains('open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
    
    // Admin user dropdown
    if (adminUserDropdown && adminUserDropdownMenu) {
        adminUserDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            adminUserDropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            adminUserDropdownMenu.classList.remove('show');
        });
        
        adminUserDropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>
