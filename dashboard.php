<?php
require_once 'config/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

// Get user statistics based on role
if ($user['role'] === 'student') {
    // Student statistics
    $enrolled_courses = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ?");
    $enrolled_courses->execute([$user['user_id']]);
    $total_enrolled = $enrolled_courses->fetchColumn();
    
    $completed_courses = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND status = 'completed'");
    $completed_courses->execute([$user['user_id']]);
    $total_completed = $completed_courses->fetchColumn();
    
    $in_progress = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND status IN ('enrolled', 'in_progress')");
    $in_progress->execute([$user['user_id']]);
    $total_in_progress = $in_progress->fetchColumn();
    
    // Recent enrollments
    $recent_enrollments = $pdo->prepare("
        SELECT e.*, c.title, c.image, c.duration 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.course_id 
        WHERE e.user_id = ? 
        ORDER BY e.enrolled_at DESC 
        LIMIT 5
    ");
    $recent_enrollments->execute([$user['user_id']]);
    $recent_courses = $recent_enrollments->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($user['role'] === 'trainer') {
    // Trainer statistics
    $my_courses = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE created_by = ?");
    $my_courses->execute([$user['user_id']]);
    $total_courses = $my_courses->fetchColumn();
    
    $total_students = $pdo->prepare("
        SELECT COUNT(DISTINCT e.user_id) 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.course_id 
        WHERE c.created_by = ?
    ");
    $total_students->execute([$user['user_id']]);
    $total_enrolled_students = $total_students->fetchColumn();
    
    $total_revenue = $pdo->prepare("
        SELECT SUM(c.price) 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.course_id 
        WHERE c.created_by = ?
    ");
    $total_revenue->execute([$user['user_id']]);
    $revenue = $total_revenue->fetchColumn() ?? 0;
    
    // Recent enrollments in my courses
    $recent_enrollments = $pdo->prepare("
        SELECT e.*, c.title, u.name as student_name
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.course_id 
        JOIN users u ON e.user_id = u.user_id
        WHERE c.created_by = ? 
        ORDER BY e.enrolled_at DESC 
        LIMIT 5
    ");
    $recent_enrollments->execute([$user['user_id']]);
    $recent_student_enrollments = $recent_enrollments->fetchAll(PDO::FETCH_ASSOC);
    
} else {
    // Admin statistics
    $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
    $total_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $total_enrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
    $total_revenue = $pdo->query("SELECT SUM(c.price) FROM enrollments e JOIN courses c ON e.course_id = c.course_id")->fetchColumn() ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TeachVerse</title>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
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
            </ul>
        </nav>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Statistics Cards -->
            <div class="grid grid-4 mb-4">
                <?php if ($user['role'] === 'student'): ?>
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--primary-color);">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--primary-color);"><?php echo $total_enrolled; ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">Enrolled Courses</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--success-color);">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--success-color);"><?php echo $total_completed; ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">Completed</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--warning-color);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--warning-color);"><?php echo $total_in_progress; ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">In Progress</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--secondary-color);">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--secondary-color);">
                                    <?php echo $total_enrolled > 0 ? round(($total_completed / $total_enrolled) * 100) : 0; ?>%
                                </h3>
                                <p style="margin: 0; color: var(--text-secondary);">Completion Rate</p>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($user['role'] === 'trainer'): ?>
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--primary-color);">
                                <i class="fas fa-book"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--primary-color);"><?php echo $total_courses; ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">My Courses</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--success-color);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--success-color);"><?php echo $total_enrolled_students; ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">Total Students</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--warning-color);">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--warning-color);"><?php echo formatPrice($revenue); ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">Total Revenue</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--secondary-color);">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--secondary-color);">4.8</h3>
                                <p style="margin: 0; color: var(--text-secondary);">Avg Rating</p>
                            </div>
                        </div>
                    </div>
                    
                <?php else: // Admin ?>
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--primary-color);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--primary-color);"><?php echo $total_users; ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">Total Users</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--success-color);">
                                <i class="fas fa-book"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--success-color);"><?php echo $total_courses; ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">Total Courses</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--warning-color);">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--warning-color);"><?php echo $total_enrollments; ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">Total Enrollments</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem; color: var(--secondary-color);">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 2rem; color: var(--secondary-color);"><?php echo formatPrice($total_revenue); ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">Total Revenue</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h3>
                </div>
                <div class="grid grid-4">
                    <?php if ($user['role'] === 'student'): ?>
                        <a href="courses.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Courses
                        </a>
                        <a href="my-courses.php" class="btn btn-secondary">
                            <i class="fas fa-book"></i> My Courses
                        </a>
                        <a href="certificates.php" class="btn btn-success">
                            <i class="fas fa-certificate"></i> Certificates
                        </a>
                        <a href="profile.php" class="btn btn-warning">
                            <i class="fas fa-user-edit"></i> Edit Profile
                        </a>
                    <?php elseif ($user['role'] === 'trainer'): ?>
                        <a href="modules/courses/create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Course
                        </a>
                        <a href="modules/courses/manage.php" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> Manage Courses
                        </a>
                        <a href="modules/trainer_profiles/create.php" class="btn btn-success">
                            <i class="fas fa-user-tie"></i> Update Profile
                        </a>
                        <a href="earnings.php" class="btn btn-warning">
                            <i class="fas fa-chart-line"></i> View Earnings
                        </a>
                    <?php else: ?>
                        <a href="admin/index.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                        <a href="modules/users/manage.php" class="btn btn-secondary">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                        <a href="modules/courses/manage.php" class="btn btn-success">
                            <i class="fas fa-book"></i> Manage Courses
                        </a>
                        <a href="reports.php" class="btn btn-warning">
                            <i class="fas fa-chart-bar"></i> View Reports
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <?php if ($user['role'] === 'student' && !empty($recent_courses)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i> Recent Courses
                        </h3>
                    </div>
                    <div class="grid grid-2">
                        <?php foreach ($recent_courses as $course): ?>
                            <div class="course-card">
                                <img src="assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                     class="course-image"
                                     onerror="this.src='assets/images/default-course.jpg'">
                                <div class="course-content">
                                    <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <div class="progress">
                                        <div class="progress-bar" data-width="<?php echo $course['progress']; ?>%" 
                                             style="width: <?php echo $course['progress']; ?>%;"></div>
                                    </div>
                                    <p style="margin: 0.5rem 0; color: var(--text-secondary);">
                                        Progress: <?php echo $course['progress']; ?>%
                                    </p>
                                    <div class="flex justify-between items-center">
                                        <span class="badge badge-<?php echo $course['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($course['status']); ?>
                                        </span>
                                        <a href="course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-primary">
                                            Continue
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($user['role'] === 'trainer' && !empty($recent_student_enrollments)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-users"></i> Recent Student Enrollments
                        </h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>Enrolled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_student_enrollments as $enrollment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['title']); ?></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo $enrollment['progress']; ?>%;"></div>
                                            </div>
                                            <?php echo $enrollment['progress']; ?>%
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $enrollment['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($enrollment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($enrollment['enrolled_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>
