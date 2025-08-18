<?php
session_start();
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    die("Database connection failed. Please check your database configuration.");
}

// Handle search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;

// Handle sort mapping
$sort_mapping = [
    'newest' => 'c.created_at DESC',
    'oldest' => 'c.created_at ASC', 
    'title_asc' => 'c.title ASC',
    'title_desc' => 'c.title DESC',
    'price_low' => 'c.price ASC',
    'price_high' => 'c.price DESC'
];

$order_clause = isset($sort_mapping[$sort]) ? $sort_mapping[$sort] : 'c.created_at DESC';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $where_conditions[] = "c.category = ?";
    $params[] = $category;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
$offset = ($page - 1) * $per_page;

$query = "SELECT c.course_id, c.title, c.description, c.category, c.image, c.duration, c.price, c.created_at, u.name AS creator_name,
          COALESCE(e.enrollment_count, 0) AS enrollment_count,
          COALESCE(r.avg_rating, 0) AS avg_rating,
          COALESCE(r.review_count, 0) AS review_count
          FROM courses c 
          LEFT JOIN users u ON c.created_by = u.user_id 
          LEFT JOIN (
              SELECT course_id, COUNT(*) AS enrollment_count 
              FROM enrollments 
              GROUP BY course_id
          ) e ON c.course_id = e.course_id
          LEFT JOIN (
              SELECT course_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
              FROM reviews 
              GROUP BY course_id
          ) r ON c.course_id = r.course_id
          $where_clause 
          ORDER BY $order_clause 
          LIMIT $offset, $per_page";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM courses c $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_courses = $count_stmt->fetchColumn();
$total_pages = ceil($total_courses / $per_page);

// Get unique categories for filter
$categories_query = "SELECT DISTINCT category FROM courses WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categories_stmt = $pdo->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - TeachVerse</title>
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
            
            /* Border Colors */
            --border-color: #e2e8f0;
            --border-dark: #cbd5e1;
            
            /* Shadows */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1), 0 10px 10px rgba(0, 0, 0, 0.04);
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            
            /* Spacing */
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-12: 3rem;
            --space-16: 4rem;
            
            /* Transitions */
            --transition-normal: all 0.3s ease;
            
            /* Typography */
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Hero Section */
        .hero-section {
            background: var(--hero-gradient);
            color: var(--text-inverse);
            padding: 6rem 0 4rem;
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
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .hero-search {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: var(--radius-lg);
            backdrop-filter: blur(10px);
        }

        .search-input {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .search-btn {
            padding: 1rem 2rem;
            background: var(--primary);
            color: var(--text-inverse);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .search-btn:hover {
            background: var(--primary-dark);
        }

        /* Filters Section */
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

        .results-count {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
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

        /* Course Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
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
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .course-image {
            width: 100%;
            height: 200px;
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
            flex: 1;
        }

        .course-price {
            font-size: 1.25rem;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            white-space: nowrap;
            margin-left: 1rem;
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
            justify-content: center;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--text-inverse);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 3rem 0;
        }

        .pagination a,
        .pagination span {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--text-primary);
            transition: var(--transition-normal);
            min-width: 44px;
            text-align: center;
        }

        .pagination a:hover {
            background: var(--primary);
            color: var(--text-inverse);
            border-color: var(--primary);
        }

        .pagination .current {
            background: var(--primary);
            color: var(--text-inverse);
            border-color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
            margin: 2rem 0;
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

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .hero-search {
                flex-direction: column;
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
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }
            
            .hero-section {
                padding: 4rem 0 2rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    <i class="fas fa-book-open"></i>
                    Discover Amazing Courses
                </h1>
                <p class="hero-subtitle">
                    Unlock your potential with our comprehensive collection of expert-led courses. 
                    Learn new skills, advance your career, and achieve your goals.
                </p>
                <form class="hero-search" method="GET" action="courses.php">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Search for courses..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Filters Section -->
            <section class="filters-section">
                <div class="filters-header">
                    <h3>
                        <i class="fas fa-filter"></i>
                        Filter & Sort
                    </h3>
                    <div class="results-count">
                        <?php echo number_format($total_courses); ?> courses found
                    </div>
                </div>
                <form method="GET" action="courses.php">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="category" class="form-label">
                                <i class="fas fa-tags"></i>
                                Category
                            </label>
                            <select name="category" id="category" class="form-control" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="sort" class="form-label">
                                <i class="fas fa-sort"></i>
                                Sort By
                            </label>
                            <select name="sort" id="sort" class="form-control" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
                                <option value="title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>Title Z-A</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" style="margin-top: 1.75rem;">
                                <i class="fas fa-search"></i>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Courses Grid -->
            <?php if (!empty($courses)): ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                        <article class="course-card">
                            <?php 
                            $image_path = 'assets/images/courses/' . ($course['image'] ?: 'default-course.jpg');
                            ?>
                            <img 
                                src="<?php echo htmlspecialchars($image_path); ?>" 
                                alt="<?php echo htmlspecialchars($course['title']); ?>"
                                class="course-image"
                                loading="lazy"
                                onerror="this.src='assets/images/courses/default-course.jpg'"
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
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('M j, Y', strtotime($course['created_at'])); ?></span>
                                    </div>
                                </div>

                                <div class="course-stats">
                                    <div class="stat-group">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo number_format($course['enrollment_count']); ?> enrolled</span>
                                    </div>
                                    <div class="stat-group">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo number_format($course['avg_rating'], 1); ?> rating</span>
                                    </div>
                                    <div class="stat-group">
                                        <i class="fas fa-comments"></i>
                                        <span><?php echo number_format($course['review_count']); ?> reviews</span>
                                    </div>
                                </div>
                                
                                <div class="course-actions">
                                    <a href="course-details.php?id=<?php echo $course['course_id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                    <?php if (isLoggedIn()): ?>
                                        <a href="enroll.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-outline btn-sm">
                                            <i class="fas fa-graduation-cap"></i>
                                            Enroll Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>No Courses Found</h3>
                    <p>
                        <?php if (!empty($search) || !empty($category)): ?>
                            No courses match your current search criteria. Try adjusting your filters or search terms.
                        <?php else: ?>
                            There are no courses available at the moment. Check back later for new content!
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search) || !empty($category)): ?>
                        <a href="courses.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i>
                            View All Courses
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        // Auto-submit forms on filter change
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('#category, #sort');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    this.form.submit();
                });
            });
        });
    </script>
</body>
</html>
