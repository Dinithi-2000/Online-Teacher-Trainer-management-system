<?php
require_once 'config/database.php';

$course_id = intval($_GET['id'] ?? 0);
$pdo = getDBConnection();

// Get course details
$stmt = $pdo->prepare("
    SELECT c.*, u.name as creator_name 
    FROM courses c 
    LEFT JOIN users u ON c.created_by = u.user_id 
    WHERE                                 <a href="auth.php?mode=login&type=user" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login to Enroll
                                </a>
                                <a href="auth.php?mode=register&type=user" class="btn btn-secondary">urse_id = ?
");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: courses.php');
    exit();
}

// Check if user is enrolled
$is_enrolled = false;
$user_enrollment = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $user_enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    $is_enrolled = !empty($user_enrollment);
}

// Get course reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.name as reviewer_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.user_id 
    WHERE r.course_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$course_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$total_reviews = count($reviews);
$avg_rating = $total_reviews > 0 ? array_sum(array_column($reviews, 'rating')) / $total_reviews : 0;

// Get enrollment count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
$stmt->execute([$course_id]);
$enrollment_count = $stmt->fetchColumn();

// Check if user can review (enrolled and hasn't reviewed yet)
$can_review = false;
$user_review = null;
if ($is_enrolled) {
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $user_review = $stmt->fetch(PDO::FETCH_ASSOC);
    $can_review = empty($user_review);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - TeachVerse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <a href="index.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-graduation-cap"></i> TeachVerse
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php">Courses</a></li>
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="grid grid-2" style="gap: 3rem;">
                <!-- Left Column -->
                <div>
                    <!-- Course Header -->
                    <div class="card mb-4">
                        <img src="assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                             alt="<?php echo htmlspecialchars($course['title']); ?>"
                             style="width: 100%; height: 300px; object-fit: cover; border-radius: var(--border-radius); margin-bottom: 1.5rem;"
                             onerror="this.src='assets/images/default-course.jpg'">
                        
                        <h1 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </h1>
                        
                        <!-- Course Meta -->
                        <div class="grid grid-3" style="margin-bottom: 1.5rem;">
                            <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: var(--border-radius);">
                                <i class="fas fa-clock" style="font-size: 1.5rem; color: var(--primary-color); margin-bottom: 0.5rem;"></i>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">Duration</p>
                                <p style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($course['duration']); ?></p>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: var(--border-radius);">
                                <i class="fas fa-users" style="font-size: 1.5rem; color: var(--success-color); margin-bottom: 0.5rem;"></i>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">Students</p>
                                <p style="margin: 0; font-weight: 600;"><?php echo $enrollment_count; ?></p>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: var(--border-radius);">
                                <i class="fas fa-star" style="font-size: 1.5rem; color: var(--warning-color); margin-bottom: 0.5rem;"></i>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">Rating</p>
                                <p style="margin: 0; font-weight: 600;"><?php echo round($avg_rating, 1); ?> (<?php echo $total_reviews; ?>)</p>
                            </div>
                        </div>
                        
                        <!-- Instructor -->
                        <?php if ($course['creator_name']): ?>
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: var(--border-radius);">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--gradient); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.25rem;">
                                    <?php echo strtoupper(substr($course['creator_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p style="margin: 0; font-weight: 600;">Instructor</p>
                                    <p style="margin: 0; color: var(--primary-color);">
                                        <a href="modules/trainer_profiles/view.php?id=<?php echo $course['created_by']; ?>">
                                            <?php echo htmlspecialchars($course['creator_name']); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Description -->
                        <h3 style="margin-bottom: 1rem;">About This Course</h3>
                        <p style="line-height: 1.6; color: var(--text-primary);">
                            <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                        </p>
                    </div>

                    <!-- Reviews Section -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-star"></i> Student Reviews (<?php echo $total_reviews; ?>)
                            </h3>
                        </div>
                        
                        <?php if ($total_reviews > 0): ?>
                            <!-- Average Rating Display -->
                            <div style="text-align: center; padding: 2rem; border-bottom: 1px solid var(--border-color); margin-bottom: 2rem;">
                                <div style="font-size: 3rem; font-weight: 700; color: var(--primary-color); margin-bottom: 0.5rem;">
                                    <?php echo round($avg_rating, 1); ?>
                                </div>
                                <div class="star-rating" style="justify-content: center; margin-bottom: 0.5rem;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= round($avg_rating) ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <p style="color: var(--text-secondary);">Based on <?php echo $total_reviews; ?> review<?php echo $total_reviews !== 1 ? 's' : ''; ?></p>
                            </div>
                            
                            <!-- Individual Reviews -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <?php foreach (array_slice($reviews, 0, 5) as $review): ?>
                                    <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                            <h5 style="margin: 0;"><?php echo htmlspecialchars($review['reviewer_name']); ?></h5>
                                            <div class="star-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">
                                            <?php echo formatDate($review['created_at']); ?>
                                        </p>
                                        <p style="margin: 0; line-height: 1.6;">
                                            <?php echo htmlspecialchars($review['comment']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                <i class="fas fa-star" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <h4>No Reviews Yet</h4>
                                <p>Be the first to review this course!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column - Enrollment Card -->
                <div>
                    <div class="card" style="position: sticky; top: 2rem;">
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <div style="font-size: 3rem; font-weight: 700; color: var(--primary-color); margin-bottom: 0.5rem;">
                                <?php echo formatPrice($course['price']); ?>
                            </div>
                            <p style="color: var(--text-secondary);">One-time payment</p>
                        </div>

                        <?php if (isLoggedIn()): ?>
                            <?php if ($is_enrolled): ?>
                                <!-- Already Enrolled -->
                                <div style="text-align: center; margin-bottom: 2rem;">
                                    <div class="alert alert-success" style="margin-bottom: 1rem;">
                                        <i class="fas fa-check-circle"></i> You are enrolled in this course!
                                    </div>
                                    
                                    <?php if ($user_enrollment): ?>
                                        <!-- Progress Display -->
                                        <div style="margin-bottom: 1.5rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                                <span style="font-weight: 600;">Your Progress</span>
                                                <span style="color: var(--primary-color); font-weight: 600;"><?php echo $user_enrollment['progress']; ?>%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo $user_enrollment['progress']; ?>%;"></div>
                                            </div>
                                        </div>
                                        
                                        <div style="margin-bottom: 1.5rem;">
                                            <span class="badge badge-<?php echo $user_enrollment['status'] === 'completed' ? 'success' : ($user_enrollment['status'] === 'in_progress' ? 'warning' : 'primary'); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $user_enrollment['status'])); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="grid grid-1 gap-2">
                                    <a href="modules/enrollments/index.php" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Continue Learning
                                    </a>
                                    
                                    <?php if ($can_review): ?>
                                        <a href="modules/reviews/create.php?course_id=<?php echo $course_id; ?>" class="btn btn-success">
                                            <i class="fas fa-star"></i> Write Review
                                        </a>
                                    <?php elseif ($user_review): ?>
                                        <a href="modules/reviews/create.php?course_id=<?php echo $course_id; ?>" class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Edit Review
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Not Enrolled - Show Enroll Button -->
                                <button onclick="enrollInCourse(<?php echo $course_id; ?>, <?php echo $_SESSION['user_id']; ?>)" 
                                        class="btn btn-primary w-full" style="margin-bottom: 1rem;">
                                    <i class="fas fa-graduation-cap"></i> Enroll Now
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Not Logged In -->
                            <div class="alert alert-info" style="margin-bottom: 1rem;">
                                Please log in to enroll in this course
                            </div>
                            <div class="grid grid-2 gap-2">
                                <a href="auth.php?mode=login&type=user" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                                <a href="auth.php?mode=register&type=user" class="btn btn-secondary">
                                    <i class="fas fa-user-plus"></i> Register
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Reviews Section -->
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                            <h4 style="margin-bottom: 1rem;">
                                <i class="fas fa-star"></i> Course Reviews 
                                <?php if ($total_reviews > 0): ?>
                                    <span style="font-size: 0.9rem; color: var(--text-muted);">
                                        (<?php echo $total_reviews; ?> review<?php echo $total_reviews > 1 ? 's' : ''; ?>)
                                    </span>
                                <?php endif; ?>
                            </h4>
                            
                            <?php if ($total_reviews > 0): ?>
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                    <div class="rating" style="display: flex; align-items: center; gap: 0.25rem;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="color: <?php echo $i <= round($avg_rating) ? '#fbbf24' : '#e2e8f0'; ?>; font-size: 1.1rem;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span style="font-weight: 600; color: var(--text-primary);">
                                        <?php echo number_format($avg_rating, 1); ?>/5
                                    </span>
                                </div>
                                
                                <!-- Recent Reviews -->
                                <div style="margin-bottom: 1rem;">
                                    <?php foreach (array_slice($reviews, 0, 2) as $review): ?>
                                        <div style="background: var(--bg-light); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                                                    <div class="rating" style="display: flex; gap: 0.125rem;">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? '#fbbf24' : '#e2e8f0'; ?>; font-size: 0.9rem;"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <small style="color: var(--text-muted);">
                                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                                </small>
                                            </div>
                                            <?php if (!empty($review['comment'])): ?>
                                                <p style="margin: 0; color: var(--text-secondary); line-height: 1.5;">
                                                    <?php echo htmlspecialchars($review['comment']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--text-muted); margin-bottom: 1rem;">No reviews yet. Be the first to review this course!</p>
                            <?php endif; ?>
                            
                            <!-- Review Actions -->
                            <?php if (isLoggedIn()): ?>
                                <?php if ($can_review): ?>
                                    <a href="modules/reviews/create.php?course_id=<?php echo $course_id; ?>" 
                                       class="btn btn-secondary" style="width: 100%; margin-bottom: 1rem;">
                                        <i class="fas fa-star"></i> Write a Review
                                    </a>
                                <?php elseif ($user_review): ?>
                                    <div style="background: var(--success-light); color: var(--success-dark); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                                        <i class="fas fa-check-circle"></i> You have reviewed this course
                                    </div>
                                    <a href="modules/reviews/edit.php?id=<?php echo $user_review['review_id']; ?>" 
                                       class="btn btn-secondary" style="width: 100%; margin-bottom: 1rem;">
                                        <i class="fas fa-edit"></i> Edit My Review
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($total_reviews > 2): ?>
                                <a href="modules/reviews/index.php?course_id=<?php echo $course_id; ?>" 
                                   class="btn btn-outline" style="width: 100%; margin-bottom: 1rem;">
                                    <i class="fas fa-eye"></i> View All Reviews (<?php echo $total_reviews; ?>)
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Course Features -->
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                            <h4 style="margin-bottom: 1rem;">This course includes:</h4>
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    Comprehensive curriculum
                                </li>
                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    Expert instruction
                                </li>
                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    Progress tracking
                                </li>
                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    Certificate of completion
                                </li>
                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    Lifetime access
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>
