<?php
require_once '../../config/database.php';
requireAdmin(); // Only admins can view user details

$pdo = getDBConnection();

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    $_SESSION['error_message'] = 'Invalid user ID.';
    header('Location: index.php');
    exit;
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = 'User not found.';
        header('Location: index.php');
        exit;
    }
    
    // Get user statistics
    $stats = [];
    
    // Courses created (if trainer)
    if ($user['role'] === 'trainer') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE created_by = ?");
        $stmt->execute([$user_id]);
        $stats['courses_created'] = $stmt->fetchColumn();
    }
    
    // Enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_enrollments'] = $stmt->fetchColumn();
    
    // Completed courses
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $stats['completed_courses'] = $stmt->fetchColumn();
    
    // Reviews written
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['reviews_written'] = $stmt->fetchColumn();
    
    // Trainer profile
    $trainer_profile = null;
    if ($user['role'] === 'trainer') {
        $stmt = $pdo->prepare("SELECT * FROM trainer_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $trainer_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Recent activity - enrollments
    $stmt = $pdo->prepare("
        SELECT e.*, c.title as course_title, c.image as course_image 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.course_id 
        WHERE e.user_id = ? 
        ORDER BY e.enrolled_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent reviews
    $stmt = $pdo->prepare("
        SELECT r.*, c.title as course_title 
        FROM reviews r 
        JOIN courses c ON r.course_id = c.course_id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo htmlspecialchars($user['name']); ?> - TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="../../index.php">TeachVerse</a>
            </div>
            <div class="nav-menu">
                <a href="../../dashboard.php">Dashboard</a>
                <a href="../../courses.php">Courses</a>
                <a href="index.php">Users</a>
                <a href="../../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>User Details</h1>
                <div class="header-actions">
                    <a href="edit.php?id=<?php echo $user_id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit User
                    </a>
                    <a href="index.php" class="btn btn-secondary">Back to Users</a>
                </div>
            </div>

            <div class="user-details-layout">
                <!-- User Information Card -->
                <div class="user-info-card">
                    <div class="user-avatar">
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                        </div>
                    </div>
                    
                    <div class="user-basic-info">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="role-badge role-<?php echo $user['role']; ?>">
                            <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'crown' : ($user['role'] === 'trainer' ? 'chalkboard-teacher' : 'user-graduate'); ?>"></i>
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                    
                    <div class="user-meta">
                        <div class="meta-item">
                            <strong>User ID:</strong> <?php echo $user['user_id']; ?>
                        </div>
                        <div class="meta-item">
                            <strong>Joined:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </div>
                        <div class="meta-item">
                            <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?>
                        </div>
                        <div class="meta-item">
                            <strong>Status:</strong> 
                            <span class="status-badge active">Active</span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <?php if ($user['role'] === 'trainer' && isset($stats['courses_created'])): ?>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['courses_created']; ?></h3>
                                <p>Courses Created</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_enrollments']; ?></h3>
                            <p>Total Enrollments</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['completed_courses']; ?></h3>
                            <p>Completed Courses</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['reviews_written']; ?></h3>
                            <p>Reviews Written</p>
                        </div>
                    </div>
                </div>

                <!-- Trainer Profile (if applicable) -->
                <?php if ($user['role'] === 'trainer'): ?>
                    <div class="trainer-profile-section">
                        <h3>Trainer Profile</h3>
                        <?php if ($trainer_profile): ?>
                            <div class="trainer-profile-card">
                                <div class="profile-image">
                                    <img src="../../assets/images/trainers/<?php echo htmlspecialchars($trainer_profile['profile_image']); ?>" 
                                         alt="Trainer Profile" 
                                         onerror="this.src='../../assets/images/default-trainer.jpg'">
                                </div>
                                <div class="profile-content">
                                    <div class="profile-section">
                                        <h4>Bio</h4>
                                        <p><?php echo nl2br(htmlspecialchars($trainer_profile['bio'])); ?></p>
                                    </div>
                                    <div class="profile-section">
                                        <h4>Experience</h4>
                                        <p><?php echo nl2br(htmlspecialchars($trainer_profile['experience'])); ?></p>
                                    </div>
                                    <?php if (!empty($trainer_profile['certificates'])): ?>
                                        <div class="profile-section">
                                            <h4>Certificates</h4>
                                            <p><?php echo nl2br(htmlspecialchars($trainer_profile['certificates'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="profile-actions">
                                    <a href="../trainer_profiles/view.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                                        View Full Profile
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-tie"></i>
                                <p>This trainer hasn't created a profile yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Recent Activity -->
                <div class="activity-section">
                    <h3>Recent Activity</h3>
                    
                    <!-- Recent Enrollments -->
                    <?php if (!empty($recent_enrollments)): ?>
                        <div class="activity-group">
                            <h4>Recent Enrollments</h4>
                            <div class="activity-list">
                                <?php foreach ($recent_enrollments as $enrollment): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <img src="../../assets/images/courses/<?php echo htmlspecialchars($enrollment['course_image']); ?>" 
                                                 alt="Course" 
                                                 onerror="this.src='../../assets/images/default-course.jpg'">
                                        </div>
                                        <div class="activity-content">
                                            <h5><?php echo htmlspecialchars($enrollment['course_title']); ?></h5>
                                            <p>Progress: <?php echo $enrollment['progress']; ?>%</p>
                                            <p>Status: <span class="status-<?php echo $enrollment['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $enrollment['status'])); ?>
                                            </span></p>
                                            <small>Enrolled: <?php echo date('M j, Y', strtotime($enrollment['enrolled_at'])); ?></small>
                                        </div>
                                        <div class="activity-progress">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $enrollment['progress']; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Recent Reviews -->
                    <?php if (!empty($recent_reviews)): ?>
                        <div class="activity-group">
                            <h4>Recent Reviews</h4>
                            <div class="activity-list">
                                <?php foreach ($recent_reviews as $review): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon review-icon">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h5><?php echo htmlspecialchars($review['course_title']); ?></h5>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                                <?php endfor; ?>
                                                <span><?php echo $review['rating']; ?>/5</span>
                                            </div>
                                            <p><?php echo htmlspecialchars(substr($review['comment'], 0, 100)); ?>
                                            <?php echo strlen($review['comment']) > 100 ? '...' : ''; ?></p>
                                            <small>Reviewed: <?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($recent_enrollments) && empty($recent_reviews)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>No recent activity found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>

    <style>
    .user-details-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .user-info-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        height: fit-content;
        text-align: center;
    }

    .user-avatar {
        margin-bottom: 1.5rem;
    }

    .avatar-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: var(--gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
        font-weight: 600;
        margin: 0 auto;
    }

    .user-basic-info h2 {
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
    }

    .user-email {
        color: var(--text-secondary);
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .role-admin { background: var(--danger-color); color: white; }
    .role-trainer { background: var(--warning-color); color: white; }
    .role-student { background: var(--primary-color); color: white; }

    .user-meta {
        margin-top: 2rem;
        text-align: left;
    }

    .meta-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--border-color);
    }

    .meta-item:last-child {
        border-bottom: none;
    }

    .status-badge.active {
        color: var(--success-color);
        font-weight: 600;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .stat-info h3 {
        margin: 0;
        font-size: 2rem;
        color: var(--primary-color);
    }

    .stat-info p {
        margin: 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .trainer-profile-section,
    .activity-section {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .trainer-profile-section h3,
    .activity-section h3 {
        margin-bottom: 1.5rem;
        color: var(--text-primary);
    }

    .trainer-profile-card {
        display: flex;
        gap: 1.5rem;
        align-items: flex-start;
    }

    .profile-image {
        width: 120px;
        height: 120px;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .profile-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-content {
        flex: 1;
    }

    .profile-section {
        margin-bottom: 1.5rem;
    }

    .profile-section h4 {
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
        font-size: 1rem;
    }

    .profile-section p {
        margin: 0;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    .profile-actions {
        display: flex;
        align-items: center;
    }

    .activity-group {
        margin-bottom: 2rem;
    }

    .activity-group h4 {
        margin-bottom: 1rem;
        color: var(--text-primary);
        font-size: 1.1rem;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .activity-item {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        background: var(--background-light);
        border-radius: 8px;
        align-items: center;
    }

    .activity-icon {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .activity-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .activity-icon.review-icon {
        background: var(--gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }

    .activity-content {
        flex: 1;
    }

    .activity-content h5 {
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
        font-size: 1rem;
    }

    .activity-content p {
        margin: 0.25rem 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .activity-content small {
        color: var(--text-secondary);
        font-size: 0.8rem;
    }

    .review-rating {
        margin: 0.5rem 0;
    }

    .review-rating .fa-star {
        color: #ddd;
        font-size: 0.8rem;
        margin-right: 0.1rem;
    }

    .review-rating .fa-star.active {
        color: #ffc107;
    }

    .review-rating span {
        color: var(--text-secondary);
        font-size: 0.8rem;
        margin-left: 0.5rem;
    }

    .activity-progress {
        width: 100px;
        flex-shrink: 0;
    }

    .progress-bar {
        height: 8px;
        background: var(--border-color);
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: var(--gradient);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .status-enrolled { color: var(--primary-color); }
    .status-in_progress { color: var(--warning-color); }
    .status-completed { color: var(--success-color); }
    .status-dropped { color: var(--danger-color); }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .user-details-layout {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .header-actions {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .header-actions .btn {
            width: 100%;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .trainer-profile-card {
            flex-direction: column;
        }
        
        .activity-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .activity-progress {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>
