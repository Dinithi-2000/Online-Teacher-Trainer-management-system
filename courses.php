<?php
require_once 'config/database.php';

$pdo = getDBConnection();

// Handle search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) ? sanitize($_GET['order']) : 'DESC';

// Build query
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE title LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$sql = "SELECT c.*, u.name as creator_name 
        FROM courses c 
        LEFT JOIN users u ON c.created_by = u.user_id 
        $where_clause 
        ORDER BY $sort $order";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get course statistics
$total_courses = count($courses);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Courses - TeachVerse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <a href="index.php">
                    <i class="fas fa-graduation-cap"></i> TeachVerse
                </a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php" class="active">Courses</a></li>
                <li><a href="trainers.php">Trainers</a></li>
                <li><a href="modules/reviews/index.php">Reviews</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact Us</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="profile.php"><i class="fas fa-user-edit"></i> Profile</a>
                            <a href="my-courses.php"><i class="fas fa-book"></i> My Courses</a>
                            <?php if (hasRole('admin')): ?>
                                <a href="admin/index.php"><i class="fas fa-cog"></i> Admin Panel</a>
                            <?php endif; ?>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="auth.php?mode=login&type=user" class="btn btn-secondary btn-sm">Login</a></li>
                    <li><a href="auth.php?mode=register&type=user" class="btn btn-primary btn-sm">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">
                <i class="fas fa-book"></i> All Courses
            </h1>
            <p class="page-subtitle">Discover <?php echo $total_courses; ?> training courses designed to advance your teaching career</p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Search and Filter -->
            <div class="search-filter">
                <form method="GET" class="grid grid-3">
                    <div class="form-group">
                        <label class="form-label">Search Courses</label>
                        <input type="text" name="search" class="form-input" 
                               placeholder="Search by title or description..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select">
                            <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title A-Z</option>
                            <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price Low to High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Courses Grid -->
            <?php if (!empty($courses)): ?>
                <div class="grid grid-3">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <img src="assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                 class="course-image"
                                 onerror="this.src='assets/images/default-course.jpg'">
                            <div class="course-content">
                                <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p class="course-description" style="color: var(--text-secondary); margin-bottom: 1rem;">
                                    <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                                </p>
                                <div class="course-meta" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <span class="course-duration">
                                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?>
                                    </span>
                                    <span class="course-price"><?php echo formatPrice($course['price']); ?></span>
                                </div>
                                
                                <?php if ($course['creator_name']): ?>
                                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem;">
                                        <i class="fas fa-user"></i> By <?php echo htmlspecialchars($course['creator_name']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="course-actions">
                                    <a href="course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary w-full">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                        <i class="fas fa-search" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <h3>No Courses Found</h3>
                        <p style="margin-bottom: 2rem;">No courses match your search criteria. Try adjusting your search terms.</p>
                        <a href="courses.php" class="btn btn-primary">
                            <i class="fas fa-refresh"></i> View All Courses
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-graduation-cap"></i> TeachVerse</h3>
                    <p>Empowering educators worldwide with comprehensive online training programs.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="courses.php">Courses</a>
                    <a href="trainers.php">Trainers</a>
                    <a href="about.php">About Us</a>
                    <a href="contact.php">Contact</a>
                </div>
                <div class="footer-section">
                    <h3>Student Resources</h3>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="my-courses.php">My Courses</a>
                    <a href="certificates.php">Certificates</a>
                    <a href="support.php">Support</a>
                </div>
                <div class="footer-section">
                    <h3>For Trainers</h3>
                    <a href="trainer-dashboard.php">Trainer Portal</a>
                    <a href="create-course.php">Create Course</a>
                    <a href="trainer-resources.php">Resources</a>
                    <a href="earnings.php">Earnings</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 TeachVerse. All rights reserved. | Developed by Team TeachVerse</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
