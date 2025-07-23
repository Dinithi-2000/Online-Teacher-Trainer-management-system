<?php
require_once '../config/database.php';
requireAdmin(); // Only admins can access this area

$user = getCurrentUser();
$pdo = getDBConnection();

// Get comprehensive dashboard statistics
try {
    // Users statistics with growth
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch()['total_users'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_admins FROM users WHERE role = 'admin'");
    $total_admins = $stmt->fetch()['total_admins'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_trainers FROM users WHERE role = 'trainer'");
    $total_trainers = $stmt->fetch()['total_trainers'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM users WHERE role = 'student'");
    $total_students = $stmt->fetch()['total_students'];
    
    // Growth this month
    $stmt = $pdo->query("SELECT COUNT(*) as new_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $new_users_month = $stmt->fetch()['new_users'];
    
    // Courses statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_courses FROM courses");
    $total_courses = $stmt->fetch()['total_courses'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as active_courses FROM courses WHERE status = 'active'");
    $active_courses = $stmt->fetch()['active_courses'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as new_courses FROM courses WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $new_courses_month = $stmt->fetch()['new_courses'];
    
    // Enrollments statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_enrollments FROM enrollments");
    $total_enrollments = $stmt->fetch()['total_enrollments'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as completed_enrollments FROM enrollments WHERE progress >= 100");
    $completed_enrollments = $stmt->fetch()['completed_enrollments'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as new_enrollments FROM enrollments WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $new_enrollments_month = $stmt->fetch()['new_enrollments'];
    
    // Reviews statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_reviews FROM reviews");
    $total_reviews = $stmt->fetch()['total_reviews'];
    
    $stmt = $pdo->query("SELECT AVG(rating) as avg_rating FROM reviews");
    $avg_rating = round($stmt->fetch()['avg_rating'] ?? 0, 1);
    
    $stmt = $pdo->query("SELECT COUNT(*) as new_reviews FROM reviews WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $new_reviews_month = $stmt->fetch()['new_reviews'];
    
    // Trainer profiles
    $stmt = $pdo->query("SELECT COUNT(*) as total_trainer_profiles FROM trainer_profiles");
    $total_trainer_profiles = $stmt->fetch()['total_trainer_profiles'];
    
    // Contact inquiries
    $stmt = $pdo->query("SELECT COUNT(*) as total_contacts FROM contacts");
    $total_contacts = $stmt->fetch()['total_contacts'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as pending_contacts FROM contacts WHERE status = 'pending'");
    $pending_contacts = $stmt->fetch()['pending_contacts'] ?? 0;
    
    // Recent activities
    $stmt = $pdo->query("
        SELECT 'user' as type, name, email, created_at, role 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'course' as type, title as name, CONCAT('by ', u.name) as email, c.created_at, 'course' as role
        FROM courses c 
        JOIN users u ON c.created_by = u.user_id 
        WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recent_activities = $stmt->fetchAll();
    
    // Top performing courses
    $stmt = $pdo->query("
        SELECT c.title, c.course_id, COUNT(e.enrollment_id) as enrollments, 
               AVG(r.rating) as avg_rating, u.name as instructor
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN reviews r ON c.course_id = r.course_id
        LEFT JOIN users u ON c.trainer_id = u.user_id
        GROUP BY c.course_id
        ORDER BY enrollments DESC, avg_rating DESC
        LIMIT 5
    ");
    $top_courses = $stmt->fetchAll();
    
    // System health metrics
    $completion_rate = $total_enrollments > 0 ? round(($completed_enrollments / $total_enrollments) * 100, 1) : 0;
    $course_utilization = $total_courses > 0 ? round(($total_enrollments / $total_courses), 1) : 0;
    
} catch (Exception $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TeachVerse</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-nav">
            <div class="admin-logo">
                <i class="fas fa-shield-alt"></i>
                <span>TeachVerse Admin</span>
            </div>
            <div class="admin-nav-right">
                <div class="admin-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search users, courses, reviews...">
                </div>
                <div class="admin-notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($pending_contacts > 0): ?>
                        <span class="notification-badge"><?php echo $pending_contacts; ?></span>
                    <?php endif; ?>
                </div>
                <div class="admin-user-menu">
                    <div class="admin-user-info">
                        <span class="admin-user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                        <span class="admin-user-role">Administrator</span>
                    </div>
                    <div class="admin-user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-dropdown">
                        <a href="../index.php"><i class="fas fa-globe"></i> View Site</a>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="admin-layout">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <nav class="admin-menu">
                <div class="admin-menu-section">
                    <h3>Overview</h3>
                    <a href="dashboard.php" class="admin-menu-item active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="analytics.php" class="admin-menu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                </div>

                <div class="admin-menu-section">
                    <h3>User Management</h3>
                    <a href="../modules/users/index.php" class="admin-menu-item">
                        <i class="fas fa-users"></i>
                        <span>All Users</span>
                        <span class="menu-badge"><?php echo $total_users; ?></span>
                    </a>
                    <a href="../modules/trainer_profiles/index.php" class="admin-menu-item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Trainer Profiles</span>
                        <span class="menu-badge"><?php echo $total_trainer_profiles; ?></span>
                    </a>
                    <a href="user-roles.php" class="admin-menu-item">
                        <i class="fas fa-user-tag"></i>
                        <span>Role Management</span>
                    </a>
                </div>

                <div class="admin-menu-section">
                    <h3>Course Management</h3>
                    <a href="../modules/courses/index.php" class="admin-menu-item">
                        <i class="fas fa-book"></i>
                        <span>All Courses</span>
                        <span class="menu-badge"><?php echo $total_courses; ?></span>
                    </a>
                    <a href="../modules/enrollments/index.php" class="admin-menu-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Enrollments</span>
                        <span class="menu-badge"><?php echo $total_enrollments; ?></span>
                    </a>
                    <a href="course-categories.php" class="admin-menu-item">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </div>

                <div class="admin-menu-section">
                    <h3>Content Management</h3>
                    <a href="../modules/reviews/index.php" class="admin-menu-item">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                        <span class="menu-badge"><?php echo $total_reviews; ?></span>
                    </a>
                    <a href="contacts.php" class="admin-menu-item">
                        <i class="fas fa-envelope"></i>
                        <span>Contact Inquiries</span>
                        <?php if ($pending_contacts > 0): ?>
                            <span class="menu-badge danger"><?php echo $pending_contacts; ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="admin-menu-section">
                    <h3>System</h3>
                    <a href="settings.php" class="admin-menu-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="backup.php" class="admin-menu-item">
                        <i class="fas fa-database"></i>
                        <span>Backup & Restore</span>
                    </a>
                    <a href="logs.php" class="admin-menu-item">
                        <i class="fas fa-file-alt"></i>
                        <span>System Logs</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-page-header">
                <h1>Dashboard Overview</h1>
                <p>Welcome back, <?php echo htmlspecialchars($user['name']); ?>! Here's what's happening with TeachVerse today.</p>
            </div>

            <!-- Quick Stats -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_users); ?></h3>
                        <p>Total Users</p>
                        <span class="stat-change positive">+<?php echo $new_users_month; ?> this month</span>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="stat-icon courses">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_courses); ?></h3>
                        <p>Total Courses</p>
                        <span class="stat-change positive">+<?php echo $new_courses_month; ?> this month</span>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="stat-icon enrollments">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_enrollments); ?></h3>
                        <p>Total Enrollments</p>
                        <span class="stat-change positive">+<?php echo $new_enrollments_month; ?> this month</span>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="stat-icon reviews">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $avg_rating; ?>/5</h3>
                        <p>Average Rating</p>
                        <span class="stat-change neutral"><?php echo $total_reviews; ?> total reviews</span>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="admin-secondary-stats">
                <div class="admin-metric-card">
                    <h4>User Distribution</h4>
                    <div class="metric-grid">
                        <div class="metric-item">
                            <span class="metric-label">Administrators</span>
                            <span class="metric-value"><?php echo $total_admins; ?></span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Trainers</span>
                            <span class="metric-value"><?php echo $total_trainers; ?></span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Students</span>
                            <span class="metric-value"><?php echo $total_students; ?></span>
                        </div>
                    </div>
                </div>

                <div class="admin-metric-card">
                    <h4>System Health</h4>
                    <div class="metric-grid">
                        <div class="metric-item">
                            <span class="metric-label">Completion Rate</span>
                            <span class="metric-value"><?php echo $completion_rate; ?>%</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Course Utilization</span>
                            <span class="metric-value"><?php echo $course_utilization; ?></span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Active Courses</span>
                            <span class="metric-value"><?php echo $active_courses; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Sections -->
            <div class="admin-dashboard-grid">
                <!-- Recent Activities -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fas fa-clock"></i> Recent Activities</h3>
                        <a href="activities.php" class="view-all-link">View All</a>
                    </div>
                    <div class="admin-card-content">
                        <div class="activity-list">
                            <?php if (empty($recent_activities)): ?>
                                <div class="empty-state-small">
                                    <i class="fas fa-clock"></i>
                                    <p>No recent activities</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?php echo $activity['type']; ?>">
                                            <i class="fas fa-<?php echo $activity['type'] === 'user' ? 'user-plus' : 'book'; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <p><strong><?php echo htmlspecialchars($activity['name']); ?></strong></p>
                                            <span><?php echo htmlspecialchars($activity['email']); ?></span>
                                            <small><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Courses -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fas fa-trophy"></i> Top Performing Courses</h3>
                        <a href="../modules/courses/index.php" class="view-all-link">Manage Courses</a>
                    </div>
                    <div class="admin-card-content">
                        <div class="course-list">
                            <?php if (empty($top_courses)): ?>
                                <div class="empty-state-small">
                                    <i class="fas fa-book"></i>
                                    <p>No courses available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($top_courses as $course): ?>
                                    <div class="course-item">
                                        <div class="course-content">
                                            <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                            <p>by <?php echo htmlspecialchars($course['instructor'] ?? 'Unknown'); ?></p>
                                            <div class="course-metrics">
                                                <span class="metric">
                                                    <i class="fas fa-users"></i> 
                                                    <?php echo $course['enrollments']; ?> enrolled
                                                </span>
                                                <?php if ($course['avg_rating']): ?>
                                                    <span class="metric">
                                                        <i class="fas fa-star"></i> 
                                                        <?php echo round($course['avg_rating'], 1); ?>/5
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <a href="../course-details.php?id=<?php echo $course['course_id']; ?>" class="course-action">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="admin-card-content">
                        <div class="quick-actions">
                            <a href="../modules/users/create.php" class="quick-action-btn">
                                <i class="fas fa-user-plus"></i>
                                <span>Add User</span>
                            </a>
                            <a href="../modules/courses/create.php" class="quick-action-btn">
                                <i class="fas fa-plus-circle"></i>
                                <span>Create Course</span>
                            </a>
                            <a href="../modules/trainer_profiles/create_new.php" class="quick-action-btn">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Add Trainer</span>
                            </a>
                            <a href="backup.php" class="quick-action-btn">
                                <i class="fas fa-download"></i>
                                <span>Backup Data</span>
                            </a>
                            <a href="../quick_add_users.php" class="quick-action-btn">
                                <i class="fas fa-users"></i>
                                <span>Add Sample Users</span>
                            </a>
                            <a href="settings.php" class="quick-action-btn">
                                <i class="fas fa-cog"></i>
                                <span>System Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="assets/admin.js"></script>
</body>
</html>
