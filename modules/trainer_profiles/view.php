<?php
// filepath: C:\xampp\htdocs\teachverse\modules\trainer_profiles\view.php
require_once '../../config/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

// Get profile ID from URL
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$profile_id) {
    header('Location: index.php');
    exit();
}

// Fixed query - use profile_id instead of tp.id
try {
    $stmt = $pdo->prepare("
        SELECT tp.*, u.name as user_name, u.email as user_email 
        FROM trainer_profiles tp
        JOIN users u ON tp.user_id = u.user_id
        WHERE tp.profile_id = ?
    ");
    $stmt->execute([$profile_id]);
    $trainer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trainer) {
        header('Location: index.php?error=Profile not found');
        exit();
    }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Check permissions
if (!hasRole('admin') && $user['user_id'] != $trainer['user_id']) {
    header('Location: ../../dashboard.php?error=Access denied');
    exit();
}

// Get trainer's courses
try {
    $courses_stmt = $pdo->prepare("
        SELECT c.*, COUNT(e.enrollment_id) as enrollment_count
        FROM courses c 
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        WHERE c.created_by = ?
        GROUP BY c.course_id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $courses_stmt->execute([$trainer['user_id']]);
    $trainer_courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $trainer_courses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($trainer['user_name']); ?> - Trainer Profile | TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .trainer-profile-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255,255,255,0.3);
            flex-shrink: 0;
        }
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2.5rem;
        }
        .profile-title {
            color: rgba(255,255,255,0.9);
            font-size: 1.3rem;
            margin: 0 0 1rem 0;
            font-weight: 300;
        }
        .profile-email, .join-date {
            margin: 0.5rem 0;
            opacity: 0.9;
        }
        .profile-details {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: grid;
            gap: 2rem;
        }
        .detail-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .detail-section h3 {
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .detail-section h3 i {
            color: #667eea;
        }
        .profile-actions {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .courses-section {
            margin-top: 2rem;
        }
        .course-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .course-card h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        .course-meta {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .profile-info h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../../index.php">
                    <i class="fas fa-graduation-cap"></i>
                    TeachVerse
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="../../index.php">Home</a></li>
                <li><a href="../../courses.php">Courses</a></li>
                <li><a href="index.php" class="active">Trainers</a></li>
                <li><a href="../../modules/reviews/">Reviews</a></li>
                <li><a href="../../about.php">About Us</a></li>
                <li><a href="../../contact.php">Contact Us</a></li>
                <?php if (isLoggedIn()): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="../../dashboard.php">Dashboard</a></li>
                            <?php if (hasRole('admin')): ?>
                                <li><a href="../../admin/">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><a href="../../logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="../../auth.php" class="btn-login">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <!-- Profile Hero Section -->
        <div class="trainer-profile-hero">
            <div class="profile-header">
                <div class="profile-image">
                    <img src="../../assets/images/trainers/<?php echo htmlspecialchars($trainer['profile_image'] ?: 'default-trainer.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($trainer['user_name']); ?>">
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($trainer['user_name']); ?></h1>
                    <h2 class="profile-title"><?php echo htmlspecialchars($trainer['profile_title'] ?? 'Professional Trainer'); ?></h2>
                    <p class="profile-email">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($trainer['user_email']); ?>
                    </p>
                    <p class="join-date">
                        <i class="fas fa-calendar-alt"></i>
                        Joined <?php echo date('M Y', strtotime($trainer['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Profile Details -->
            <div class="profile-details">
                <div class="detail-section">
                    <h3><i class="fas fa-user"></i> About</h3>
                    <p><?php echo nl2br(htmlspecialchars($trainer['bio'] ?: 'No bio available')); ?></p>
                </div>

                <div class="detail-section">
                    <h3><i class="fas fa-briefcase"></i> Experience</h3>
                    <p><?php echo nl2br(htmlspecialchars($trainer['experience'] ?: 'No experience details available')); ?></p>
                </div>

                <div class="detail-section">
                    <h3><i class="fas fa-certificate"></i> Certifications & Achievements</h3>
                    <p><?php echo nl2br(htmlspecialchars($trainer['certificates'] ?: 'No certifications listed')); ?></p>
                </div>

                <?php if (!empty($trainer_courses)): ?>
                <div class="detail-section">
                    <h3><i class="fas fa-book"></i> Recent Courses</h3>
                    <div class="courses-section">
                        <?php foreach ($trainer_courses as $course): ?>
                        <div class="course-card">
                            <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                            <p><?php echo htmlspecialchars(substr($course['description'], 0, 150) . '...'); ?></p>
                            <div class="course-meta">
                                <span><i class="fas fa-users"></i> <?php echo $course['enrollment_count']; ?> students</span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($course['created_at'])); ?></span>
                                <span><i class="fas fa-tag"></i> $<?php echo number_format($course['price'], 2); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="profile-actions">
                <?php if (hasRole('admin') || $user['user_id'] == $trainer['user_id']): ?>
                    <a href="edit.php?id=<?php echo $trainer['profile_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <?php if (hasRole('admin')): ?>
                        <a href="delete.php?id=<?php echo $trainer['profile_id']; ?>" class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this profile?')">
                            <i class="fas fa-trash"></i> Delete Profile
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profiles
                </a>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
</body>
</html>