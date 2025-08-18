<?php
require_once '../../config/database.php';
requireLogin();

$pdo = getDBConnection();
$error = '';
$success = '';

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialization = isset($_GET['specialization']) ? trim($_GET['specialization']) : '';
$availability_filter = isset($_GET['availability']) ? trim($_GET['availability']) : '';
$experience_filter = isset($_GET['experience']) ? trim($_GET['experience']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build WHERE conditions
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR tp.bio LIKE ? OR tp.experience LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($specialization) {
    $where_conditions[] = "tp.certificates LIKE ?";
    $params[] = "%$specialization%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Sort options
$sort_options = [
    'newest' => 'tp.created_at DESC',
    'oldest' => 'tp.created_at ASC',
    'name_asc' => 'u.name ASC',
    'name_desc' => 'u.name DESC',
    'experience' => 'tp.experience DESC'
];

$order_by = isset($sort_options[$sort]) ? $sort_options[$sort] : 'tp.created_at DESC';

// Get trainer profiles with user information and course statistics
try {
    $query = "
        SELECT 
            tp.*,
            u.name,
            u.email,
            u.created_at as user_created_at,
            COUNT(DISTINCT c.course_id) as course_count,
            COUNT(DISTINCT e.enroll_id) as total_enrollments,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(DISTINCT r.review_id) as review_count
        FROM trainer_profiles tp
        INNER JOIN users u ON tp.user_id = u.user_id
        LEFT JOIN courses c ON tp.user_id = c.created_by
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN reviews r ON c.course_id = r.course_id
        $where_clause
        GROUP BY tp.profile_id, tp.user_id, tp.bio, tp.experience, tp.certificates, tp.profile_image, tp.created_at, tp.updated_at, u.name, u.email, u.created_at
        ORDER BY $order_by
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching trainer profiles: " . $e->getMessage();
    $trainers = [];
}

// Get statistics
try {
    $stats_query = "
        SELECT 
            COUNT(DISTINCT tp.profile_id) as total_profiles,
            COUNT(DISTINCT tp.user_id) as unique_trainers,
            COUNT(DISTINCT CASE WHEN tp.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN tp.profile_id END) as new_profiles,
            COUNT(DISTINCT c.course_id) as total_courses,
            COUNT(DISTINCT e.enroll_id) as total_enrollments,
            COALESCE(AVG(r.rating), 0) as avg_rating
        FROM trainer_profiles tp
        LEFT JOIN courses c ON tp.user_id = c.created_by
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN reviews r ON c.course_id = r.course_id
    ";
    $stats_stmt = $pdo->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = [
        'total_profiles' => 0, 'unique_trainers' => 0, 'new_profiles' => 0,
        'total_courses' => 0, 'total_enrollments' => 0, 'avg_rating' => 0
    ];
}

// Get unique specializations for filter
$specializations = [];
try {
    $spec_query = "SELECT DISTINCT certificates FROM trainer_profiles WHERE certificates IS NOT NULL AND certificates != ''";
    $spec_stmt = $pdo->query($spec_query);
    while ($row = $spec_stmt->fetch(PDO::FETCH_ASSOC)) {
        $certs = explode(',', $row['certificates']);
        foreach ($certs as $cert) {
            $cert = trim($cert);
            if ($cert && !in_array($cert, $specializations)) {
                $specializations[] = $cert;
            }
        }
    }
    sort($specializations);
} catch (PDOException $e) {
    $specializations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Profiles - TeachVerse</title>
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
            
            /* Modern Gradients */
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --hero-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            
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
            --text-primary: var(--gray-900);
            --text-secondary: var(--gray-600);
            --text-muted: var(--gray-500);
            --text-inverse: var(--white);
            
            /* Background Colors */
            --bg-primary: var(--white);
            --bg-secondary: var(--gray-50);
            --bg-tertiary: var(--gray-100);
            
            /* Shadows */
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-2xl: 24px;
            --radius-full: 9999px;
            
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
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Typography */
            --font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            
            /* Border */
            --border-color: var(--gray-200);
        }

        /* Reset and Base Styles */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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

        /* Container System */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-6);
        }

        /* Hero Section */
        .hero-section {
            background: var(--hero-gradient);
            color: var(--text-inverse);
            padding: var(--space-20) 0 var(--space-16);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: var(--space-4);
            background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto var(--space-8);
        }

        /* Statistics Cards */
        .stats-section {
            margin: calc(var(--space-16) * -1) 0 var(--space-16) 0;
            position: relative;
            z-index: 10;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-6);
            margin-bottom: var(--space-16);
        }

        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            text-align: center;
            box-shadow: var(--shadow-lg);
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

        .stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: var(--radius-full);
            background: var(--primary-gradient);
            color: var(--text-inverse);
            font-size: 1.5rem;
            margin-bottom: var(--space-4);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Filters Section */
        .filters-section {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            margin-bottom: var(--space-12);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .filters-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-6);
        }

        .filters-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .results-count {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
        }

        .filter-label {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .filter-input,
        .filter-select {
            padding: var(--space-3) var(--space-4);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            transition: var(--transition-normal);
            background: var(--bg-primary);
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: var(--space-3);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
            padding: var(--space-3) var(--space-6);
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
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--bg-primary);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: var(--text-inverse);
        }

        .btn-sm {
            padding: var(--space-2) var(--space-4);
            font-size: 0.8rem;
        }

        /* Trainers Grid */
        .trainers-section {
            margin-bottom: var(--space-16);
        }

        .trainers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: var(--space-8);
        }

        .trainer-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: var(--transition-normal);
            position: relative;
        }

        .trainer-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .trainer-header {
            position: relative;
            padding: var(--space-8);
            background: var(--card-gradient);
            text-align: center;
        }

        .trainer-avatar {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-full);
            object-fit: cover;
            border: 4px solid var(--bg-primary);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--space-4);
        }

        .trainer-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }

        .trainer-email {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: var(--space-4);
        }

        .trainer-stats {
            display: flex;
            justify-content: center;
            gap: var(--space-6);
        }

        .trainer-stat {
            text-align: center;
        }

        .trainer-stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .trainer-stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .trainer-body {
            padding: var(--space-6);
        }

        .trainer-bio {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: var(--space-4);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .trainer-meta {
            display: flex;
            flex-direction: column;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }

        .trainer-meta-item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            font-size: 0.9rem;
        }

        .trainer-meta-icon {
            color: var(--primary);
            width: 16px;
        }

        .trainer-certificates {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
            margin-bottom: var(--space-6);
        }

        .certificate-badge {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .trainer-actions {
            display: flex;
            gap: var(--space-3);
        }

        /* Rating Stars */
        .rating-stars {
            display: flex;
            gap: var(--space-1);
            margin-bottom: var(--space-2);
        }

        .star {
            color: #fbbf24;
            font-size: 0.9rem;
        }

        .star.empty {
            color: var(--gray-300);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: var(--space-20) var(--space-8);
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            border: 2px dashed var(--border-color);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: var(--space-4);
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }

        .empty-text {
            color: var(--text-secondary);
            margin-bottom: var(--space-6);
        }

        /* Alert Messages */
        .alert {
            padding: var(--space-4) var(--space-6);
            border-radius: var(--radius-sm);
            margin-bottom: var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 var(--space-4);
            }

            .hero-section {
                padding: var(--space-16) 0 var(--space-12);
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: var(--space-4);
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .trainers-grid {
                grid-template-columns: 1fr;
            }

            .trainer-stats {
                gap: var(--space-4);
            }

            .trainer-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }

            .stat-card {
                padding: var(--space-6);
            }

            .trainer-card {
                margin: 0 var(--space-2);
            }
        }
    </style>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Meet Our Expert Trainers
                </h1>
                <p class="hero-subtitle">
                    Discover talented educators and industry professionals who are passionate about 
                    sharing their knowledge and helping you achieve your learning goals.
                </p>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_profiles']); ?></div>
                    <div class="stat-label">Expert Trainers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_courses']); ?></div>
                    <div class="stat-label">Courses Created</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_enrollments']); ?></div>
                    <div class="stat-label">Students Taught</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container">
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

        <!-- Filters Section -->
        <section class="filters-section">
            <div class="filters-header">
                <h3 class="filters-title">
                    <i class="fas fa-filter"></i>
                    Find Trainers
                </h3>
                <div class="results-count">
                    <?php echo count($trainers); ?> trainer<?php echo count($trainers) !== 1 ? 's' : ''; ?> found
                </div>
            </div>

            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            class="filter-input" 
                            placeholder="Search by name, bio, or experience..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Specialization</label>
                        <select name="specialization" class="filter-select">
                            <option value="">All Specializations</option>
                            <?php foreach ($specializations as $spec): ?>
                                <option value="<?php echo htmlspecialchars($spec); ?>" 
                                        <?php echo $specialization === $spec ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Sort By</label>
                        <select name="sort" class="filter-select">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                            <option value="experience" <?php echo $sort === 'experience' ? 'selected' : ''; ?>>Most Experienced</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Apply Filters
                    </button>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-undo"></i>
                        Clear All
                    </a>
                </div>
            </form>
        </section>

        <!-- Trainers Section -->
        <section class="trainers-section">
            <?php if (empty($trainers)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3 class="empty-title">No Trainers Found</h3>
                    <p class="empty-text">
                        <?php if ($search || $specialization): ?>
                            No trainers match your current search criteria. Try adjusting your filters.
                        <?php else: ?>
                            There are no trainer profiles available at the moment.
                        <?php endif; ?>
                    </p>
                    <?php if (hasRole('admin')): ?>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add First Trainer
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="trainers-grid">
                    <?php foreach ($trainers as $trainer): ?>
                        <div class="trainer-card">
                            <div class="trainer-header">
                                <img 
                                    src="../../assets/images/trainers/<?php echo htmlspecialchars($trainer['profile_image'] ?: 'default-trainer.jpg'); ?>" 
                                    alt="<?php echo htmlspecialchars($trainer['name']); ?>"
                                    class="trainer-avatar"
                                    onerror="this.src='../../assets/images/trainers/default-trainer.jpg'"
                                >
                                <h3 class="trainer-name"><?php echo htmlspecialchars($trainer['name']); ?></h3>
                                <p class="trainer-email"><?php echo htmlspecialchars($trainer['email']); ?></p>
                                
                                <div class="trainer-stats">
                                    <div class="trainer-stat">
                                        <span class="trainer-stat-number"><?php echo $trainer['course_count']; ?></span>
                                        <span class="trainer-stat-label">Courses</span>
                                    </div>
                                    <div class="trainer-stat">
                                        <span class="trainer-stat-number"><?php echo $trainer['total_enrollments']; ?></span>
                                        <span class="trainer-stat-label">Students</span>
                                    </div>
                                    <div class="trainer-stat">
                                        <span class="trainer-stat-number"><?php echo number_format($trainer['avg_rating'], 1); ?></span>
                                        <span class="trainer-stat-label">Rating</span>
                                    </div>
                                </div>
                            </div>

                            <div class="trainer-body">
                                <?php if ($trainer['bio']): ?>
                                    <p class="trainer-bio"><?php echo htmlspecialchars($trainer['bio']); ?></p>
                                <?php endif; ?>

                                <div class="trainer-meta">
                                    <?php if ($trainer['experience']): ?>
                                        <div class="trainer-meta-item">
                                            <i class="fas fa-briefcase trainer-meta-icon"></i>
                                            <span><?php echo htmlspecialchars($trainer['experience']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="trainer-meta-item">
                                        <i class="fas fa-calendar-alt trainer-meta-icon"></i>
                                        <span>Joined <?php echo date('M Y', strtotime($trainer['user_created_at'])); ?></span>
                                    </div>

                                    <?php if ($trainer['review_count'] > 0): ?>
                                        <div class="trainer-meta-item">
                                            <div class="rating-stars">
                                                <?php
                                                $rating = $trainer['avg_rating'];
                                                for ($i = 1; $i <= 5; $i++):
                                                ?>
                                                    <i class="fas fa-star star <?php echo $i <= $rating ? '' : 'empty'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span>(<?php echo $trainer['review_count']; ?> review<?php echo $trainer['review_count'] !== 1 ? 's' : ''; ?>)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($trainer['certificates']): ?>
                                    <div class="trainer-certificates">
                                        <?php
                                        $certificates = array_filter(array_map('trim', explode(',', $trainer['certificates'])));
                                        foreach ($certificates as $cert):
                                        ?>
                                            <span class="certificate-badge"><?php echo htmlspecialchars($cert); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="trainer-actions">
                                    <a href="view.php?id=<?php echo $trainer['profile_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                        View Profile
                                    </a>
                                    
                                    <?php if (hasRole('admin') || (isLoggedIn() && getCurrentUser()['user_id'] == $trainer['user_id'])): ?>
                                        <a href="edit.php?id=<?php echo $trainer['profile_id']; ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Add New Trainer Button for Admins -->
        <?php if (hasRole('admin')): ?>
            <div style="text-align: center; margin-top: var(--space-12);">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add New Trainer Profile
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Auto-submit form on filter change
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('.filter-select');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    // Auto-submit when sort changes
                    if (this.name === 'sort') {
                        this.form.submit();
                    }
                });
            });

            // Search with debounce
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                let debounceTimer;
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        // Optional: Auto-submit search after typing stops
                        // this.form.submit();
                    }, 500);
                });
            }
        });
    </script>
</body>
</html>
