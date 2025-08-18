<?php
require_once '../../config/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

$error = '';
$success = '';

// Handle search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) ? sanitize($_GET['order']) : 'DESC';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$level = isset($_GET['level']) ? sanitize($_GET['level']) : '';
$price_filter = isset($_GET['price_filter']) ? sanitize($_GET['price_filter']) : '';

// Build query with enhanced filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.title LIKE ? OR c.description LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $where_conditions[] = "c.category = ?";
    $params[] = $category;
}

// Skip level filter since column doesn't exist yet
// if (!empty($level)) {
//     $where_conditions[] = "c.level = ?";
//     $params[] = $level;
// }

if (!empty($price_filter)) {
    if ($price_filter === 'free') {
        $where_conditions[] = "c.price = 0";
    } elseif ($price_filter === 'paid') {
        $where_conditions[] = "c.price > 0";
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT c.*, u.name as creator_name, u.email as creator_email,
               COUNT(DISTINCT e.enrollment_id) as enrollment_count,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(DISTINCT r.review_id) as review_count,
               'Intermediate' as level
        FROM courses c 
        LEFT JOIN users u ON c.created_by = u.user_id 
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN reviews r ON c.course_id = r.course_id
        $where_clause 
        GROUP BY c.course_id, c.title, c.description, c.category, c.image, c.duration, c.price, c.created_by, c.created_at, c.updated_at, u.name, u.email
        ORDER BY $sort $order";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching courses: " . $e->getMessage();
    $courses = [];
}

// Get statistics
try {
    $stats_query = "
        SELECT 
            COUNT(DISTINCT c.course_id) as total_courses,
            COUNT(DISTINCT c.created_by) as total_instructors,
            COUNT(DISTINCT CASE WHEN c.price = 0 THEN c.course_id END) as free_courses,
            COUNT(DISTINCT CASE WHEN c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN c.course_id END) as new_courses,
            SUM(c.price) as total_value,
            COUNT(DISTINCT e.enrollment_id) as total_enrollments,
            AVG(r.rating) as avg_rating
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN reviews r ON c.course_id = r.course_id
    ";
    $stats_stmt = $pdo->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $stats = [
        'total_courses' => 0, 'total_instructors' => 0, 'free_courses' => 0, 
        'new_courses' => 0, 'total_value' => 0, 'total_enrollments' => 0, 'avg_rating' => 0
    ];
}

// Get categories for filters
$categories = [];
try {
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM courses WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    // Continue with empty arrays
}

// Hardcode levels since column doesn't exist yet
$levels = ['Beginner', 'Intermediate', 'Advanced'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - TeachVerse</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Modern Color System */
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #06b6d4;
            --secondary-dark: #0891b2;
            --accent: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            
            /* Modern Gradients */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --secondary-gradient: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --error-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --dark-gradient: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            
            /* Neutral Colors */
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Text Colors */
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --text-inverse: #ffffff;
            
            /* Background Colors */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --bg-accent: #e2e8f0;
            --bg-hover: #f1f5f9;
            
            /* Border Colors */
            --border-light: #f1f5f9;
            --border-color: #e2e8f0;
            --border-dark: #cbd5e1;
            
            /* Shadows */
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1), 0 10px 10px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px rgba(0, 0, 0, 0.25);
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-2xl: 24px;
            --radius-3xl: 32px;
            
            /* Spacing */
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-10: 2.5rem;
            --space-12: 3rem;
            --space-16: 4rem;
            --space-20: 5rem;
            --space-24: 6rem;
            
            /* Transitions */
            --transition-fast: all 0.15s ease;
            --transition-normal: all 0.3s ease;
            --transition-slow: all 0.5s ease;
            --transition-bounce: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            
            /* Typography */
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            
            /* Z-Index */
            --z-dropdown: 1000;
            --z-sticky: 1020;
            --z-fixed: 1030;
            --z-modal: 1050;
            --z-tooltip: 1070;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-sans);
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-secondary);
            overflow-x: hidden;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: var(--z-fixed);
            transition: var(--transition-normal);
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
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-menu a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            transition: var(--transition-normal);
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        /* Page Header */
        .page-header {
            background: var(--primary-gradient);
            color: var(--text-inverse);
            padding: 4rem 0 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .page-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .page-subtitle {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Statistics Dashboard */
        .stats-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 3rem 0;
        }

        .stat-card {
            background: var(--bg-primary);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: var(--transition-normal);
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
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stat-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--text-inverse);
        }

        .stat-icon.primary { background: var(--primary-gradient); }
        .stat-icon.secondary { background: var(--secondary-gradient); }
        .stat-icon.success { background: var(--success-gradient); }
        .stat-icon.warning { background: var(--warning-gradient); }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Filters */
        .filters-section {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .filters-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .filters-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            transition: var(--transition-normal);
            background: var(--bg-primary);
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition-normal);
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: var(--text-inverse);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--bg-hover);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-warning {
            background: var(--warning);
            color: var(--text-inverse);
        }

        .btn-danger {
            background: var(--error);
            color: var(--text-inverse);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        /* Courses Grid */
        .courses-section {
            margin: 2rem 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
        }

        .course-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: var(--transition-normal);
            position: relative;
        }

        .course-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .course-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: var(--bg-tertiary);
        }

        .course-content {
            padding: 1.5rem;
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .course-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.4;
            margin-bottom: 0.5rem;
        }

        .course-price {
            font-size: 1.25rem;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            white-space: nowrap;
        }

        .course-price.free {
            background: var(--success);
            color: var(--text-inverse);
        }

        .course-price.paid {
            background: var(--primary);
            color: var(--text-inverse);
        }

        .course-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .meta-item i {
            color: var(--primary);
            width: 16px;
        }

        .course-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius-sm);
        }

        .stat-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .stat-group i {
            color: var(--primary);
        }

        .course-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
        }

        .action-group {
            display: flex;
            gap: 0.5rem;
        }

        /* Badge System */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge.level-beginner { background: #dcfce7; color: #166534; }
        .badge.level-intermediate { background: #fef3c7; color: #92400e; }
        .badge.level-advanced { background: #fecaca; color: #991b1b; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fecaca;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .filters-grid {
                grid-template-columns: 1fr 1fr 1fr;
            }
            
            .filters-grid .form-group:last-child {
                grid-column: 1 / -1;
                justify-self: center;
            }
        }

        @media (max-width: 768px) {
            .stats-dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .course-meta,
            .course-stats {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .course-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .action-group {
                justify-content: center;
            }
            
            .nav-menu {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .stats-dashboard {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 0 1rem;
            }
        }

        /* Remove animations and loading states */
        .stats-dashboard,
        .filters-section,
        .courses-grid,
        .loading {
            opacity: 1;
            transform: none;
        }

        /* Remove animation keyframes */
    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">
                <i class="fas fa-book-open"></i>
                Course Management
            </h1>
            <p class="page-subtitle">
                Manage and organize all training courses in your educational platform. 
                Create, edit, and monitor course performance from this central hub.
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Error/Success Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Dashboard -->
            <section class="stats-dashboard">
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon primary">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_courses']); ?></h3>
                            <p>Total Courses</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon secondary">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_instructors']); ?></h3>
                            <p>Active Instructors</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon success">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['free_courses']); ?></h3>
                            <p>Free Courses</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon warning">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['new_courses']); ?></h3>
                            <p>New This Month</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon primary">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$<?php echo number_format($stats['total_value'], 0); ?></h3>
                            <p>Total Value</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon secondary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_enrollments']); ?></h3>
                            <p>Total Enrollments</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon warning">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['avg_rating'], 1); ?></h3>
                            <p>Average Rating</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Advanced Filters -->
            <section class="filters-section">
                <div class="filters-header">
                    <h3>
                        <i class="fas fa-filter"></i>
                        Advanced Filters
                    </h3>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add New Course
                    </a>
                </div>
                <form method="GET" action="index.php">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="search" class="form-label">
                                <i class="fas fa-search"></i>
                                Search
                            </label>
                            <input 
                                type="text" 
                                id="search" 
                                name="search" 
                                class="form-control" 
                                placeholder="Search by title, description, or instructor..."
                                value="<?php echo htmlspecialchars($search); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="category" class="form-label">
                                <i class="fas fa-tags"></i>
                                Category
                            </label>
                            <select name="category" id="category" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo $category == $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="level" class="form-label">
                                <i class="fas fa-layer-group"></i>
                                Level
                            </label>
                            <select name="level" id="level" class="form-control">
                                <option value="">All Levels</option>
                                <?php foreach ($levels as $lev): ?>
                                    <option value="<?php echo htmlspecialchars($lev); ?>" 
                                            <?php echo $level == $lev ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($lev)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_filter" class="form-label">
                                <i class="fas fa-dollar-sign"></i>
                                Price
                            </label>
                            <select name="price_filter" id="price_filter" class="form-control">
                                <option value="">All Prices</option>
                                <option value="free" <?php echo $price_filter == 'free' ? 'selected' : ''; ?>>Free</option>
                                <option value="paid" <?php echo $price_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div style="display: flex; gap: 0.75rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Filter
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Courses Section -->
            <section class="courses-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-graduation-cap"></i>
                        Courses Library
                        <small style="font-size: 1rem; font-weight: 400; color: var(--text-secondary);">
                            (<?php echo count($courses); ?> courses found)
                        </small>
                    </h2>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <select name="sort" onchange="updateSort(this)" class="form-control" style="width: auto;">
                            <option value="created_at-DESC" <?php echo ($sort == 'created_at' && $order == 'DESC') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="created_at-ASC" <?php echo ($sort == 'created_at' && $order == 'ASC') ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="title-ASC" <?php echo ($sort == 'title' && $order == 'ASC') ? 'selected' : ''; ?>>Title A-Z</option>
                            <option value="title-DESC" <?php echo ($sort == 'title' && $order == 'DESC') ? 'selected' : ''; ?>>Title Z-A</option>
                            <option value="price-ASC" <?php echo ($sort == 'price' && $order == 'ASC') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price-DESC" <?php echo ($sort == 'price' && $order == 'DESC') ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>
                </div>

                <?php if (!empty($courses)): ?>
                    <div class="courses-grid">
                        <?php foreach ($courses as $course): ?>
                            <article class="course-card">
                                <img 
                                    src="../../assets/images/courses/<?php echo htmlspecialchars($course['image'] ?: 'default-course.jpg'); ?>" 
                                    alt="<?php echo htmlspecialchars($course['title']); ?>"
                                    class="course-image"
                                    loading="lazy"
                                    onerror="this.src='../../assets/images/courses/default-course.jpg'"
                                >
                                <div class="course-content">
                                    <div class="course-header">
                                        <h3 class="course-title">
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </h3>
                                        <div class="course-price <?php echo $course['price'] == 0 ? 'free' : 'paid'; ?>">
                                            <?php if ($course['price'] == 0): ?>
                                                Free
                                            <?php else: ?>
                                                $<?php echo number_format($course['price'], 2); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($course['description']): ?>
                                        <p class="course-description">
                                            <?php echo htmlspecialchars($course['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="course-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($course['creator_name'] ?? 'Unknown'); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo htmlspecialchars($course['duration'] ?? 'N/A'); ?></span>
                                        </div>
                                        <?php if ($course['category']): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-tag"></i>
                                            <span><?php echo htmlspecialchars($course['category']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($course['level']): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-signal"></i>
                                            <span class="badge level-<?php echo strtolower($course['level']); ?>">
                                                <?php echo htmlspecialchars(ucfirst($course['level'])); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="course-stats">
                                        <div class="stat-group">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo number_format($course['enrollment_count']); ?> enrolled</span>
                                        </div>
                                        <div class="stat-group">
                                            <i class="fas fa-star"></i>
                                            <span><?php echo number_format($course['avg_rating'] ?: 0, 1); ?> rating</span>
                                        </div>
                                        <div class="stat-group">
                                            <i class="fas fa-comments"></i>
                                            <span><?php echo number_format($course['review_count']); ?> reviews</span>
                                        </div>
                                        <div class="stat-group">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('M j, Y', strtotime($course['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="course-actions">
                                        <a href="../../course-details.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-secondary btn-sm">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </a>
                                        <div class="action-group">
                                            <?php if (hasRole('admin') || $user['user_id'] == $course['created_by']): ?>
                                                <a href="edit.php?id=<?php echo $course['course_id']; ?>" 
                                                   class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                    Edit
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (hasRole('admin')): ?>
                                                <button onclick="deleteItem('delete.php?id=<?php echo $course['course_id']; ?>', 'course')" 
                                                        class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3>No Courses Found</h3>
                        <p>
                            <?php if (!empty($search) || !empty($category) || !empty($level) || !empty($price_filter)): ?>
                                No courses match your current filters. Try adjusting your search criteria or clear filters to see all courses.
                            <?php else: ?>
                                You haven't created any courses yet. Start building your educational content library today!
                            <?php endif; ?>
                        </p>
                        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                            <?php if (!empty($search) || !empty($category) || !empty($level) || !empty($price_filter)): ?>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Clear Filters
                                </a>
                            <?php endif; ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Create First Course
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- JavaScript for Enhanced Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Loading animations
            const loadingElements = document.querySelectorAll('.loading');
            loadingElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, 100 + (index * 150));
            });

            // Enhanced search functionality
            const searchForm = document.querySelector('.filters-section form');
            const searchInput = document.getElementById('search');
            
            if (searchForm && searchInput) {
                let searchTimeout;
                
                // Auto-search with debouncing
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    
                    // Visual feedback
                    this.style.borderColor = 'var(--primary)';
                    
                    searchTimeout = setTimeout(() => {
                        this.style.borderColor = '';
                        if (this.value.length >= 3 || this.value.length === 0) {
                            searchForm.submit();
                        }
                    }, 800);
                });

                // Form submission enhancement
                searchForm.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
                        submitBtn.disabled = true;
                        
                        // Re-enable after delay for better UX
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 2000);
                    }
                });
            }

            // Course card animations
            const courseCards = document.querySelectorAll('.course-card');
            courseCards.forEach((card, index) => {
                // Staggered entrance animation
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 300 + (index * 100));

                // Enhanced hover effects
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                    this.style.zIndex = '10';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.zIndex = '1';
                });
            });

            // Stats counter animation
            const statNumbers = document.querySelectorAll('.stat-info h3');
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent.replace(/[^0-9.]/g, ''));
                const isDecimal = stat.textContent.includes('.');
                const prefix = stat.textContent.match(/^\$/) ? '$' : '';
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 50) || 1;
                
                const counter = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(counter);
                    }
                    
                    if (isDecimal) {
                        stat.textContent = prefix + currentValue.toFixed(1);
                    } else {
                        stat.textContent = prefix + currentValue.toLocaleString();
                    }
                }, 40);
            });

            // Filter change handlers
            const filterSelects = document.querySelectorAll('#category, #level, #price_filter');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    searchForm.submit();
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + K for search focus
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }
                
                // Escape to clear search
                if (e.key === 'Escape') {
                    if (searchInput && document.activeElement === searchInput) {
                        searchInput.value = '';
                        searchInput.blur();
                    }
                }
            });

            // Add search shortcut hint
            if (searchInput) {
                searchInput.setAttribute('placeholder', 
                    searchInput.getAttribute('placeholder') + ' (Ctrl+K)');
            }
        });

        // Sort functionality
        function updateSort(select) {
            const [sortField, sortOrder] = select.value.split('-');
            const url = new URL(window.location);
            url.searchParams.set('sort', sortField);
            url.searchParams.set('order', sortOrder);
            window.location.href = url.toString();
        }

        // Enhanced delete confirmation with modern modal
        function deleteItem(url, itemType) {
            // Create custom modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.6);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
                backdrop-filter: blur(5px);
            `;
            
            modal.innerHTML = `
                <div style="
                    background: var(--bg-primary);
                    padding: 2.5rem;
                    border-radius: var(--radius-xl);
                    box-shadow: var(--shadow-2xl);
                    max-width: 450px;
                    text-align: center;
                    transform: scale(0.9);
                    transition: transform 0.3s ease;
                    border: 1px solid var(--border-color);
                ">
                    <div style="
                        width: 80px;
                        height: 80px;
                        background: var(--error-gradient);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 1.5rem;
                        color: white;
                        font-size: 2rem;
                    ">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 style="margin-bottom: 1rem; color: var(--text-primary); font-size: 1.5rem;">Delete ${itemType}?</h3>
                    <p style="margin-bottom: 2rem; color: var(--text-secondary); line-height: 1.6;">
                        This action cannot be undone. The ${itemType} will be permanently removed from the system along with all related data.
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button onclick="this.closest('div').parentElement.remove()" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button onclick="window.location.href='${url}'" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete ${itemType}
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Animate in
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.firstElementChild.style.transform = 'scale(1)';
            }, 10);
            
            // Close on background click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.opacity = '0';
                    setTimeout(() => modal.remove(), 300);
                }
            });
        }

        console.log('TeachVerse Course Management loaded successfully! 🚀');
    </script>
</body>
</html>
