<?php
require_once '../auth.php';
requireAdmin();

// Get user data from database
try {
    $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get analytics data
    $stmt = $db->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY date");
    $userGrowth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM courses WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY date");
    $courseGrowth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT c.title, COUNT(e.id) as enrollments FROM courses c LEFT JOIN enrollments e ON c.id = e.course_id GROUP BY c.id ORDER BY enrollments DESC LIMIT 10");
    $popularCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT tp.first_name, tp.last_name, COUNT(c.id) as course_count FROM trainer_profiles tp LEFT JOIN courses c ON tp.user_id = c.created_by GROUP BY tp.id ORDER BY course_count DESC LIMIT 10");
    $topTrainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - TeachVerse Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/admin-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .metric-comparison {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .comparison-item {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }
        
        .comparison-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--admin-primary);
            margin-bottom: 0.25rem;
        }
        
        .comparison-label {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .top-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .top-item:last-child {
            border-bottom: none;
        }
        
        .top-item-name {
            font-weight: 500;
            color: var(--admin-primary);
        }
        
        .top-item-value {
            font-weight: 600;
            color: var(--admin-accent);
        }
    </style>
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-graduation-cap"></i>
                <h3>TeachVerse Admin</h3>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item active">
                    <a href="analytics.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../modules/users/index.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        Users
                        <span class="nav-badge">New</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../modules/courses/index.php" class="nav-link">
                        <i class="fas fa-book"></i>
                        Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../modules/trainer_profiles/index.php" class="nav-link">
                        <i class="fas fa-user-tie"></i>
                        Trainers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../modules/reviews/index.php" class="nav-link">
                        <i class="fas fa-star"></i>
                        Reviews
                    </a>
                </li>
                <li class="nav-item">
                    <a href="contacts.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        Contact Messages
                    </a>
                </li>
                <div class="nav-divider"></div>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="backup.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        Backup
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logs.php" class="nav-link">
                        <i class="fas fa-list-alt"></i>
                        System Logs
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="admin-info">
                    <h4><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></h4>
                    <p>Administrator</p>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Analytics Dashboard</h1>
            </div>
            
            <div class="topbar-right">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </button>
                
                <div class="admin-user-menu">
                    <img src="../assets/images/default-avatar.jpg" alt="Admin" class="user-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="admin-avatar" style="display: none;">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="admin-content">
            <!-- Overview Metrics -->
            <div class="metric-comparison">
                <div class="comparison-item">
                    <div class="comparison-value"><?php echo count($userGrowth); ?></div>
                    <div class="comparison-label">Days with Activity</div>
                </div>
                <div class="comparison-item">
                    <div class="comparison-value"><?php echo array_sum(array_column($userGrowth, 'count')); ?></div>
                    <div class="comparison-label">New Users (30d)</div>
                </div>
                <div class="comparison-item">
                    <div class="comparison-value"><?php echo array_sum(array_column($courseGrowth, 'count')); ?></div>
                    <div class="comparison-label">New Courses (30d)</div>
                </div>
                <div class="comparison-item">
                    <div class="comparison-value"><?php echo count($popularCourses); ?></div>
                    <div class="comparison-label">Active Courses</div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="analytics-grid">
                <!-- User Growth Chart -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> User Growth (30 Days)</h3>
                        <button class="card-action">Export</button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Course Enrollment Chart -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> Course Creation (30 Days)</h3>
                        <button class="card-action">Export</button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="courseGrowthChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Popular Courses -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy"></i> Most Popular Courses</h3>
                        <button class="card-action">View All</button>
                    </div>
                    <div class="card-body">
                        <div class="top-list">
                            <?php if (!empty($popularCourses)): ?>
                                <?php foreach ($popularCourses as $course): ?>
                                    <div class="top-item">
                                        <span class="top-item-name"><?php echo htmlspecialchars($course['title']); ?></span>
                                        <span class="top-item-value"><?php echo $course['enrollments']; ?> enrollments</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state-small">
                                    <i class="fas fa-chart-bar"></i>
                                    <p>No course data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Trainers -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-tie"></i> Top Trainers</h3>
                        <button class="card-action">View All</button>
                    </div>
                    <div class="card-body">
                        <div class="top-list">
                            <?php if (!empty($topTrainers)): ?>
                                <?php foreach ($topTrainers as $trainer): ?>
                                    <div class="top-item">
                                        <span class="top-item-name"><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></span>
                                        <span class="top-item-value"><?php echo $trainer['course_count']; ?> courses</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state-small">
                                    <i class="fas fa-user-tie"></i>
                                    <p>No trainer data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/admin.js"></script>
    <script>
        // Initialize charts with real data
        document.addEventListener('DOMContentLoaded', function() {
            initializeAnalyticsCharts();
        });

        function initializeAnalyticsCharts() {
            // User Growth Chart
            const userGrowthData = <?php echo json_encode($userGrowth); ?>;
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: userGrowthData.map(item => item.date),
                    datasets: [{
                        label: 'New Users',
                        data: userGrowthData.map(item => item.count),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Course Growth Chart
            const courseGrowthData = <?php echo json_encode($courseGrowth); ?>;
            const courseGrowthCtx = document.getElementById('courseGrowthChart').getContext('2d');
            
            new Chart(courseGrowthCtx, {
                type: 'bar',
                data: {
                    labels: courseGrowthData.map(item => item.date),
                    datasets: [{
                        label: 'New Courses',
                        data: courseGrowthData.map(item => item.count),
                        backgroundColor: '#27ae60',
                        borderColor: '#229954',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
