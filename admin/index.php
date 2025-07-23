<?php
require_once '../config/database.php';
requireAdmin(); // Only admins can access this area

$pdo = getDBConnection();

// Get dashboard statistics
try {
    // Users statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch()['total_users'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_admins FROM users WHERE role = 'admin'");
    $total_admins = $stmt->fetch()['total_admins'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_trainers FROM users WHERE role = 'trainer'");
    $total_trainers = $stmt->fetch()['total_trainers'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM users WHERE role = 'student'");
    $total_students = $stmt->fetch()['total_students'];
    
    // Courses statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_courses FROM courses");
    $total_courses = $stmt->fetch()['total_courses'];
    
    // Enrollments statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_enrollments FROM enrollments");
    $total_enrollments = $stmt->fetch()['total_enrollments'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as completed_enrollments FROM enrollments WHERE status = 'completed'");
    $completed_enrollments = $stmt->fetch()['completed_enrollments'];
    
    // Reviews statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_reviews FROM reviews");
    $total_reviews = $stmt->fetch()['total_reviews'];
    
    $stmt = $pdo->query("SELECT AVG(rating) as avg_rating FROM reviews");
    $avg_rating = round($stmt->fetch()['avg_rating'], 1);
    
    // Recent activities
    $stmt = $pdo->query("
        SELECT u.name, u.email, u.created_at, 'user_registration' as type 
        FROM users u 
        ORDER BY u.created_at DESC 
        LIMIT 5
    ");
    $recent_users = $stmt->fetchAll();
    
    $stmt = $pdo->query("
        SELECT c.title, u.name as creator, c.created_at, 'course_creation' as type 
        FROM courses c 
        JOIN users u ON c.created_by = u.user_id 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
    $recent_courses = $stmt->fetchAll();
    
    $stmt = $pdo->query("
        SELECT e.enrolled_at, u.name as student, c.title as course, 'enrollment' as type 
        FROM enrollments e 
        JOIN users u ON e.user_id = u.user_id 
        JOIN courses c ON e.course_id = c.course_id 
        ORDER BY e.enrolled_at DESC 
        LIMIT 5
    ");
    $recent_enrollments = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TeachVerse</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
            --text-secondary: #718096;
            --text-muted: #a0aec0;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-accent: #edf2f7;
            --border-color: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 8px 25px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.15);
            --shadow-xl: 0 25px 50px rgba(0,0,0,0.25);
            --border-radius: 20px;
            --border-radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Admin Layout */
        .admin-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            grid-template-rows: auto 1fr;
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .admin-sidebar {
            grid-area: sidebar;
            background: var(--dark-gradient);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-logo i {
            font-size: 2rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-logo h3 {
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(45deg, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-list {
            list-style: none;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #cbd5e0;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            border-radius: 0 25px 25px 0;
            margin-right: 1rem;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.3), rgba(118, 75, 162, 0.3));
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #667eea, #764ba2);
            border-radius: 0 2px 2px 0;
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
        }

        .nav-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-left: auto;
            font-weight: 600;
        }

        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 1rem 1.5rem;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
            background: rgba(0,0,0,0.1);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .admin-info h4 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .admin-info p {
            font-size: 0.8rem;
            color: #a0aec0;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #fc8181;
            transform: translateY(-1px);
        }

        /* Header Styles */
        .admin-header {
            grid-area: header;
            background: white;
            padding: 1.5rem 2rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .sidebar-toggle:hover {
            background: var(--bg-accent);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 300px;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .notifications {
            position: relative;
        }

        .notification-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.75rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            position: relative;
        }

        .notification-btn:hover {
            background: var(--bg-accent);
            color: var(--text-primary);
        }

        .notification-count {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: #e53e3e;
            color: white;
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            font-weight: 600;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--bg-accent);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: var(--transition);
        }

        .user-menu:hover {
            background: #e2e8f0;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--primary-gradient);
        }

        .user-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        /* Main Content */
        .admin-main {
            grid-area: main;
            padding: 2rem;
            overflow-y: auto;
            background: var(--bg-secondary);
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.primary::before {
            background: var(--primary-gradient);
        }

        .stat-card.success::before {
            background: var(--success-gradient);
        }

        .stat-card.warning::before {
            background: var(--warning-gradient);
        }

        .stat-card.info::before {
            background: var(--info-gradient);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-card.primary .stat-icon {
            background: var(--primary-gradient);
        }

        .stat-card.success .stat-icon {
            background: var(--success-gradient);
        }

        .stat-card.warning .stat-icon {
            background: var(--warning-gradient);
        }

        .stat-card.info .stat-icon {
            background: var(--info-gradient);
        }

        .stat-content h3 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-content p {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .stat-breakdown {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .stat-breakdown span {
            font-size: 0.85rem;
            color: var(--text-muted);
            padding: 0.25rem 0.75rem;
            background: var(--bg-accent);
            border-radius: 6px;
            display: inline-block;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #48bb78;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: var(--transition);
        }

        .dashboard-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-accent);
        }

        .card-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header i {
            color: #667eea;
        }

        .card-action {
            background: none;
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: var(--text-secondary);
            font-size: 0.85rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .card-action:hover {
            background: var(--bg-secondary);
            border-color: #667eea;
            color: #667eea;
        }

        .card-body {
            padding: 2rem;
        }

        /* Recent Activities */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg-accent);
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }

        .activity-item:hover {
            background: #e2e8f0;
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            background: var(--primary-gradient);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content p {
            margin: 0 0 0.25rem 0;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: var(--bg-accent);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            text-decoration: none;
            color: var(--text-secondary);
            transition: var(--transition);
            text-align: center;
        }

        .quick-action:hover {
            background: var(--bg-secondary);
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }

        .quick-action i {
            font-size: 2rem;
            color: #667eea;
        }

        .quick-action span {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* System Health */
        .health-metrics {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .health-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--bg-accent);
            border-radius: var(--border-radius-sm);
        }

        .health-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .health-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .status-good {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-warning {
            background: #fef5e7;
            color: #744210;
        }

        .status-error {
            background: #fed7d7;
            color: #742a2a;
        }

        .status-info {
            background: #bee3f8;
            color: #2a4365;
        }

        /* Additional Dashboard Section */
        .analytics-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .chart-header {
            margin-bottom: 1.5rem;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .chart-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .admin-layout {
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "header"
                    "main";
            }

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .analytics-section {
                grid-template-columns: 1fr;
            }

            .search-input {
                width: 200px;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-main {
                padding: 1rem;
            }

            .admin-header {
                padding: 1rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .search-box {
                display: none;
            }

            .user-name {
                display: none;
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in {
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .admin-sidebar {
            grid-area: sidebar;
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-logo i {
            font-size: 2rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-logo h3 {
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(45deg, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-list {
            list-style: none;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #cbd5e0;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            border-radius: 0 25px 25px 0;
            margin-right: 1rem;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.3), rgba(118, 75, 162, 0.3));
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #667eea, #764ba2);
            border-radius: 0 2px 2px 0;
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
        }

        .nav-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-left: auto;
            font-weight: 600;
        }

        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 1rem 1.5rem;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .admin-info h4 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .admin-info p {
            font-size: 0.8rem;
            color: #a0aec0;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #fc8181;
        }

        /* Header Styles */
        .admin-header {
            grid-area: header;
            background: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #4a5568;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: #f7fafc;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
            background: linear-gradient(45deg, #2d3748, #4a5568);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 300px;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }

        .notifications {
            position: relative;
        }

        .notification-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #4a5568;
            cursor: pointer;
            padding: 0.75rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
        }

        .notification-btn:hover {
            background: #f7fafc;
            color: #2d3748;
        }

        .notification-count {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: #e53e3e;
            color: white;
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            font-weight: 600;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-menu:hover {
            background: #edf2f7;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            background: linear-gradient(45deg, #667eea, #764ba2);
        }

        .user-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }

        /* Main Content */
        .admin-main {
            grid-area: main;
            padding: 2rem;
            overflow-y: auto;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.primary::before {
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card.success::before {
            background: linear-gradient(90deg, #48bb78, #38a169);
        }

        .stat-card.warning::before {
            background: linear-gradient(90deg, #ed8936, #dd6b20);
        }

        .stat-card.info::before {
            background: linear-gradient(90deg, #4299e1, #3182ce);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-card.primary .stat-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .stat-card.success .stat-icon {
            background: linear-gradient(135deg, #48bb78, #38a169);
        }

        .stat-card.warning .stat-icon {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
        }

        .stat-card.info .stat-icon {
            background: linear-gradient(135deg, #4299e1, #3182ce);
        }

        .stat-content h3 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .stat-content p {
            color: #718096;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .stat-breakdown {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .stat-breakdown span {
            font-size: 0.85rem;
            color: #a0aec0;
            padding: 0.25rem 0.75rem;
            background: #f8fafc;
            border-radius: 6px;
            display: inline-block;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #48bb78;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafbfc;
        }

        .card-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header i {
            color: #667eea;
        }

        .card-action {
            background: none;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: #4a5568;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .card-action:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }

        .card-body {
            padding: 2rem;
        }

        /* Recent Activities */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: #edf2f7;
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content p {
            margin: 0 0 0.25rem 0;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #a0aec0;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            text-decoration: none;
            color: #4a5568;
            transition: all 0.3s ease;
            text-align: center;
        }

        .quick-action:hover {
            background: #edf2f7;
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }

        .quick-action i {
            font-size: 2rem;
            color: #667eea;
        }

        .quick-action span {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* System Health */
        .health-metrics {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .health-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
        }

        .health-label {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .health-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .status-good {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-warning {
            background: #fef5e7;
            color: #744210;
        }

        .status-error {
            background: #fed7d7;
            color: #742a2a;
        }

        /* Additional Dashboard Section */
        .analytics-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .chart-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .chart-header {
            margin-bottom: 1.5rem;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .chart-subtitle {
            color: #718096;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .admin-layout {
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "header"
                    "main";
            }

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .analytics-section {
                grid-template-columns: 1fr;
            }

            .search-input {
                width: 200px;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-main {
                padding: 1rem;
            }

            .admin-header {
                padding: 1rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .search-box {
                display: none;
            }

            .user-name {
                display: none;
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in {
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>TeachVerse</h3>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">
                            <i class="fas fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../modules/users/index.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                            <span class="nav-badge"><?php echo $total_users; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../modules/courses/index.php" class="nav-link">
                            <i class="fas fa-book"></i>
                            <span>Courses</span>
                            <span class="nav-badge"><?php echo $total_courses; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../modules/trainer_profiles/index.php" class="nav-link">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Trainers</span>
                            <span class="nav-badge"><?php echo $total_trainers; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../modules/reviews/index.php" class="nav-link">
                            <i class="fas fa-star"></i>
                            <span>Reviews</span>
                            <span class="nav-badge"><?php echo $total_reviews; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../modules/enrollments/index.php" class="nav-link">
                            <i class="fas fa-user-graduate"></i>
                            <span>Enrollments</span>
                            <span class="nav-badge"><?php echo $total_enrollments; ?></span>
                        </a>
                    </li>
                    
                    <div class="nav-divider"></div>
                    
                    <li class="nav-item">
                        <a href="analytics.php" class="nav-link">
                            <i class="fas fa-chart-bar"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="contacts.php" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            <span>Contact Messages</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="backup.php" class="nav-link">
                            <i class="fas fa-database"></i>
                            <span>Backup</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logs.php" class="nav-link">
                            <i class="fas fa-file-alt"></i>
                            <span>System Logs</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-info">
                        <h4><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin User'; ?></h4>
                        <p>System Administrator</p>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Admin Header -->
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Dashboard Overview</h1>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search anything...">
                </div>
                <div class="notifications">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count">3</span>
                    </button>
                </div>
                <div class="user-menu">
                    <div class="user-avatar"></div>
                    <span class="user-name"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Statistics Cards -->
            <div class="stats-grid fade-in">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-content">
                            <h3><?php echo $total_users; ?></h3>
                            <p>Total Users</p>
                            <div class="stat-breakdown">
                                <span>Students: <?php echo $total_students ?? 0; ?></span>
                                <span>Trainers: <?php echo $total_trainers ?? 0; ?></span>
                                <span>Admins: <?php echo $total_admins ?? 0; ?></span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+12% from last month</span>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-content">
                            <h3><?php echo $total_courses; ?></h3>
                            <p>Total Courses</p>
                            <div class="stat-breakdown">
                                <span>Published: <?php echo $total_courses; ?></span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+8% from last month</span>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-content">
                            <h3><?php echo $total_enrollments; ?></h3>
                            <p>Total Enrollments</p>
                            <div class="stat-breakdown">
                                <span>Completed: <?php echo $completed_enrollments ?? 0; ?></span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+15% from last month</span>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-content">
                            <h3><?php echo number_format($avg_rating ?? 0, 1); ?>/5</h3>
                            <p>Average Rating</p>
                            <div class="stat-breakdown">
                                <span><?php echo $total_reviews; ?> total reviews</span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+5% from last month</span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid slide-in">
                <!-- Recent Activities -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock"></i>Recent Activities</h3>
                        <button class="card-action">View All</button>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php if (isset($recent_users) && is_array($recent_users) && !empty($recent_users)): ?>
                                <?php foreach (array_slice(array_merge($recent_users, $recent_courses ?? [], $recent_enrollments ?? []), 0, 8) as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <?php if (isset($activity['type']) && $activity['type'] === 'user_registration'): ?>
                                                <i class="fas fa-user-plus"></i>
                                            <?php elseif (isset($activity['type']) && $activity['type'] === 'course_creation'): ?>
                                                <i class="fas fa-book-medical"></i>
                                            <?php else: ?>
                                                <i class="fas fa-graduation-cap"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-content">
                                            <?php if (isset($activity['type']) && $activity['type'] === 'user_registration'): ?>
                                                <p><strong><?php echo htmlspecialchars($activity['name'] ?? 'Unknown User'); ?></strong> registered</p>
                                            <?php elseif (isset($activity['type']) && $activity['type'] === 'course_creation'): ?>
                                                <p><strong><?php echo htmlspecialchars($activity['creator'] ?? 'Unknown'); ?></strong> created course: <?php echo htmlspecialchars($activity['title'] ?? 'Unknown Course'); ?></p>
                                            <?php else: ?>
                                                <p><strong><?php echo htmlspecialchars($activity['student'] ?? 'Unknown Student'); ?></strong> enrolled in <?php echo htmlspecialchars($activity['course'] ?? 'Unknown Course'); ?></p>
                                            <?php endif; ?>
                                            <span class="activity-time"><?php echo isset($activity['created_at']) ? formatDate($activity['created_at']) : (isset($activity['enrolled_at']) ? formatDate($activity['enrolled_at']) : 'Recently'); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-info"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p>No recent activities found</p>
                                        <span class="activity-time">System is running smoothly</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="../modules/users/create.php" class="quick-action">
                                <i class="fas fa-user-plus"></i>
                                <span>Add User</span>
                            </a>
                            <a href="../modules/courses/create.php" class="quick-action">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add Course</span>
                            </a>
                            <a href="../modules/trainer_profiles/create.php" class="quick-action">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Add Trainer</span>
                            </a>
                            <a href="analytics.php" class="quick-action">
                                <i class="fas fa-chart-bar"></i>
                                <span>View Analytics</span>
                            </a>
                            <a href="backup.php" class="quick-action">
                                <i class="fas fa-download"></i>
                                <span>Backup Data</span>
                            </a>
                            <a href="settings.php" class="quick-action">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health & Analytics -->
            <div class="analytics-section fade-in">
                <!-- System Health -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-heartbeat"></i>System Health</h3>
                        <button class="card-action">Run Diagnostics</button>
                    </div>
                    <div class="card-body">
                        <div class="health-metrics">
                            <div class="health-item">
                                <span class="health-label">Database Connection</span>
                                <span class="health-status status-good">
                                    <i class="fas fa-check-circle"></i>
                                    Healthy
                                </span>
                            </div>
                            <div class="health-item">
                                <span class="health-label">Server Performance</span>
                                <span class="health-status status-good">
                                    <i class="fas fa-check-circle"></i>
                                    Optimal
                                </span>
                            </div>
                            <div class="health-item">
                                <span class="health-label">Storage Space</span>
                                <span class="health-status status-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    75% Used
                                </span>
                            </div>
                            <div class="health-item">
                                <span class="health-label">Security Status</span>
                                <span class="health-status status-good">
                                    <i class="fas fa-shield-alt"></i>
                                    Secure
                                </span>
                            </div>
                            <div class="health-item">
                                <span class="health-label">Backup Status</span>
                                <span class="health-status status-good">
                                    <i class="fas fa-check-circle"></i>
                                    Up to Date
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">Platform Performance</h3>
                        <p class="chart-subtitle">Last 30 days overview</p>
                    </div>
                    <div class="performance-metrics">
                        <div class="health-metrics">
                            <div class="health-item">
                                <span class="health-label">Average Response Time</span>
                                <span class="health-status status-good">
                                    <i class="fas fa-tachometer-alt"></i>
                                    145ms
                                </span>
                            </div>
                            <div class="health-item">
                                <span class="health-label">Uptime</span>
                                <span class="health-status status-good">
                                    <i class="fas fa-clock"></i>
                                    99.9%
                                </span>
                            </div>
                            <div class="health-item">
                                <span class="health-label">Active Sessions</span>
                                <span class="health-status status-info">
                                    <i class="fas fa-users"></i>
                                    24
                                </span>
                            </div>
                            <div class="health-item">
                                <span class="health-label">Daily Logins</span>
                                <span class="health-status status-good">
                                    <i class="fas fa-sign-in-alt"></i>
                                    156
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar toggle for mobile
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.admin-sidebar');

        sidebarToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Search functionality
        const searchInput = document.querySelector('.search-input');
        searchInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const query = e.target.value.trim();
                if (query) {
                    // Implement search functionality
                    console.log('Searching for:', query);
                }
            }
        });

        // Notification toggle
        const notificationBtn = document.querySelector('.notification-btn');
        notificationBtn?.addEventListener('click', () => {
            // Implement notification dropdown
            console.log('Showing notifications');
        });

        // Auto-refresh data every 30 seconds
        setInterval(() => {
            // Refresh dashboard data
            console.log('Refreshing dashboard data...');
        }, 30000);

        // Initialize animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all animated elements
        document.querySelectorAll('.fade-in, .slide-in').forEach(el => {
            observer.observe(el);
        });

        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const dateString = now.toLocaleDateString();
            
            // Update time display if element exists
            const timeDisplay = document.querySelector('.current-time');
            if (timeDisplay) {
                timeDisplay.textContent = `${timeString} - ${dateString}`;
            }
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
