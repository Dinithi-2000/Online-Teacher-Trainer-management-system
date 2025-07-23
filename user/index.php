<?php
require_once '../config/database.php';
requireLogin(); // Users must be logged in

// Redirect admins to admin panel
if (hasRole('admin')) {
    header('Location: ../admin/index.php');
    exit();
}

$pdo = getDBConnection();
$user = getCurrentUser();

// Get user's enrollments and progress
try {
    $stmt = $pdo->prepare("
        SELECT e.*, c.title, c.description, c.image, c.duration, c.price
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.course_id 
        WHERE e.user_id = ? 
        ORDER BY e.enrolled_at DESC
        LIMIT 6
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_enrollments = $stmt->fetchAll();
    
    // Get enrollment statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_enrollments = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM enrollments WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id']]);
    $completed_courses = $stmt->fetch()['completed'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as in_progress FROM enrollments WHERE user_id = ? AND status = 'in_progress'");
    $stmt->execute([$_SESSION['user_id']]);
    $in_progress_courses = $stmt->fetch()['in_progress'];
    
    $stmt = $pdo->prepare("SELECT AVG(progress) as avg_progress FROM enrollments WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $avg_progress = round($stmt->fetch()['avg_progress'] ?? 0);
    
    // Get user's reviews
    $stmt = $pdo->prepare("
        SELECT r.*, c.title as course_title 
        FROM reviews r 
        JOIN courses c ON r.course_id = c.course_id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_reviews = $stmt->fetchAll();
    
    // Get recommended courses (not enrolled)
    $stmt = $pdo->prepare("
        SELECT c.* FROM courses c 
        WHERE c.course_id NOT IN (
            SELECT course_id FROM enrollments WHERE user_id = ?
        ) 
        ORDER BY c.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recommended_courses = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - TeachVerse</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/user-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="user-body">
    <!-- User Navigation -->
    <nav class="user-navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="../index.php">
                    <i class="fas fa-graduation-cap"></i>
                    <span>TeachVerse</span>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../courses.php" class="nav-link">
                    <i class="fas fa-book"></i>
                    <span>Browse Courses</span>
                </a>
                <a href="../modules/enrollments/index.php" class="nav-link">
                    <i class="fas fa-user-graduate"></i>
                    <span>My Courses</span>
                </a>
                <a href="../modules/reviews/index.php" class="nav-link">
                    <i class="fas fa-star"></i>
                    <span>My Reviews</span>
                </a>
                <a href="../contact.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span>Contact</span>
                </a>
            </div>
            
            <div class="nav-user">
                <div class="user-dropdown">
                    <button class="user-dropdown-toggle">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                            <span class="user-role"><?php echo ucfirst($user['role']); ?></span>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown-menu">
                        <a href="../profile.php"><i class="fas fa-user-edit"></i> My Profile</a>
                        <a href="../modules/enrollments/index.php"><i class="fas fa-graduation-cap"></i> My Courses</a>
                        <a href="../modules/reviews/index.php"><i class="fas fa-star"></i> My Reviews</a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- User Main Content -->
    <main class="user-main">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="container">
                <div class="welcome-content">
                    <div class="welcome-text">
                        <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h1>
                        <p>Continue your learning journey and track your progress</p>
                    </div>
                    <div class="welcome-actions">
                        <a href="../courses.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Explore Courses
                        </a>
                        <a href="../modules/enrollments/index.php" class="btn btn-outline">
                            <i class="fas fa-book-open"></i> Continue Learning
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <div class="container">
                <!-- Progress Overview -->
                <div class="progress-overview">
                    <h2><i class="fas fa-chart-line"></i> Your Learning Progress</h2>
                    <div class="progress-cards">
                        <div class="progress-card">
                            <div class="progress-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="progress-info">
                                <h3><?php echo $total_enrollments; ?></h3>
                                <p>Enrolled Courses</p>
                            </div>
                        </div>
                        
                        <div class="progress-card">
                            <div class="progress-icon completed">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="progress-info">
                                <h3><?php echo $completed_courses; ?></h3>
                                <p>Completed</p>
                            </div>
                        </div>
                        
                        <div class="progress-card">
                            <div class="progress-icon in-progress">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            <div class="progress-info">
                                <h3><?php echo $in_progress_courses; ?></h3>
                                <p>In Progress</p>
                            </div>
                        </div>
                        
                        <div class="progress-card">
                            <div class="progress-icon average">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="progress-info">
                                <h3><?php echo $avg_progress; ?>%</h3>
                                <p>Average Progress</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Recent Courses -->
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h3><i class="fas fa-clock"></i> Continue Learning</h3>
                            <a href="../modules/enrollments/index.php" class="section-link">View All</a>
                        </div>
                        <div class="course-cards">
                            <?php if (empty($recent_enrollments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-book"></i>
                                    <p>No enrolled courses yet</p>
                                    <a href="../courses.php" class="btn btn-primary">Browse Courses</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_enrollments as $enrollment): ?>
                                    <div class="course-card">
                                        <div class="course-image">
                                            <img src="../assets/images/courses/<?php echo htmlspecialchars($enrollment['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($enrollment['title']); ?>">
                                            <div class="course-status status-<?php echo $enrollment['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $enrollment['status'])); ?>
                                            </div>
                                        </div>
                                        <div class="course-info">
                                            <h4><?php echo htmlspecialchars($enrollment['title']); ?></h4>
                                            <p><?php echo htmlspecialchars(substr($enrollment['description'], 0, 80)) . '...'; ?></p>
                                            <div class="course-progress">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $enrollment['progress']; ?>%"></div>
                                                </div>
                                                <span class="progress-text"><?php echo $enrollment['progress']; ?>% Complete</span>
                                            </div>
                                            <div class="course-actions">
                                                <a href="../course-details.php?id=<?php echo $enrollment['course_id']; ?>" class="btn btn-sm btn-primary">
                                                    Continue
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recommended Courses -->
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h3><i class="fas fa-lightbulb"></i> Recommended for You</h3>
                            <a href="../courses.php" class="section-link">View All</a>
                        </div>
                        <div class="recommended-courses">
                            <?php foreach ($recommended_courses as $course): ?>
                                <div class="recommended-course">
                                    <img src="../assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($course['title']); ?>">
                                    <div class="course-content">
                                        <h5><?php echo htmlspecialchars($course['title']); ?></h5>
                                        <p><?php echo htmlspecialchars(substr($course['description'], 0, 60)) . '...'; ?></p>
                                        <div class="course-meta">
                                            <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?></span>
                                            <span><i class="fas fa-dollar-sign"></i> <?php echo formatPrice($course['price']); ?></span>
                                        </div>
                                        <a href="../course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-outline">
                                            Learn More
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent Reviews -->
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h3><i class="fas fa-star"></i> Your Recent Reviews</h3>
                            <a href="../modules/reviews/index.php" class="section-link">View All</a>
                        </div>
                        <div class="recent-reviews">
                            <?php if (empty($recent_reviews)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-star"></i>
                                    <p>No reviews yet</p>
                                    <a href="../courses.php" class="btn btn-primary">Write Your First Review</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_reviews as $review): ?>
                                    <div class="review-item">
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="review-content">
                                            <h5><?php echo htmlspecialchars($review['course_title']); ?></h5>
                                            <p><?php echo htmlspecialchars(substr($review['comment'], 0, 100)) . '...'; ?></p>
                                            <span class="review-date"><?php echo formatDate($review['created_at']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
    <script src="assets/user.js"></script>
</body>
</html>
