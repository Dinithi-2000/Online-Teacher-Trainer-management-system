<?php
require_once '../../config/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

// Get user's enrollments
$stmt = $pdo->prepare("
    SELECT e.*, c.title, c.description, c.image, c.duration, c.price
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.course_id 
    WHERE e.user_id = ? 
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get enrollment statistics
$total_enrolled = count($enrollments);
$completed = array_filter($enrollments, fn($e) => $e['status'] === 'completed');
$in_progress = array_filter($enrollments, fn($e) => $e['status'] === 'in_progress');
$not_started = array_filter($enrollments, fn($e) => $e['status'] === 'enrolled');

$total_completed = count($completed);
$total_in_progress = count($in_progress);
$total_not_started = count($not_started);

// Calculate average progress
$avg_progress = $total_enrolled > 0 ? array_sum(array_column($enrollments, 'progress')) / $total_enrolled : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Enrollments - TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <a href="../../index.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-graduation-cap"></i> TeachVerse
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="../../index.php">Home</a></li>
                <li><a href="../../courses.php">Courses</a></li>
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <?php if (isLoggedIn()): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="../../profile.php"><i class="fas fa-user-edit"></i> Profile</a>
                            <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">
                <i class="fas fa-graduation-cap"></i> My Enrollments
            </h1>
            <p class="page-subtitle">Track your learning progress</p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Statistics Cards -->
            <div class="grid grid-4 mb-4">
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem; color: var(--primary-color);">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem; color: var(--primary-color);"><?php echo $total_enrolled; ?></h3>
                            <p style="margin: 0; color: var(--text-secondary);">Total Enrolled</p>
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
                            <h3 style="margin: 0; font-size: 2rem; color: var(--secondary-color);"><?php echo round($avg_progress); ?>%</h3>
                            <p style="margin: 0; color: var(--text-secondary);">Avg Progress</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrollments List -->
            <?php if (!empty($enrollments)): ?>
                <div class="grid grid-2">
                    <?php foreach ($enrollments as $enrollment): ?>
                        <div class="course-card">
                            <img src="../../assets/images/courses/<?php echo htmlspecialchars($enrollment['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($enrollment['title']); ?>" 
                                 class="course-image"
                                 onerror="this.src='../../assets/images/default-course.jpg'">
                            <div class="course-content">
                                <h3 class="course-title"><?php echo htmlspecialchars($enrollment['title']); ?></h3>
                                <p class="course-description" style="color: var(--text-secondary); margin-bottom: 1rem;">
                                    <?php echo htmlspecialchars(substr($enrollment['description'], 0, 100)) . '...'; ?>
                                </p>
                                
                                <!-- Progress Bar -->
                                <div style="margin-bottom: 1rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                        <span style="font-size: 0.875rem; font-weight: 600;">Progress</span>
                                        <span style="font-size: 0.875rem; color: var(--primary-color);"><?php echo $enrollment['progress']; ?>%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" 
                                             data-width="<?php echo $enrollment['progress']; ?>%" 
                                             style="width: <?php echo $enrollment['progress']; ?>%;"></div>
                                    </div>
                                </div>
                                
                                <!-- Status and Meta -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <span class="badge badge-<?php echo $enrollment['status'] === 'completed' ? 'success' : ($enrollment['status'] === 'in_progress' ? 'warning' : 'primary'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $enrollment['status'])); ?>
                                    </span>
                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($enrollment['duration']); ?>
                                    </span>
                                </div>
                                
                                <!-- Progress Update -->
                                <div style="margin-bottom: 1rem;">
                                    <label style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; display: block;">
                                        Update Progress:
                                    </label>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <input type="range" 
                                               id="progress-<?php echo $enrollment['enroll_id']; ?>"
                                               min="0" max="100" 
                                               value="<?php echo $enrollment['progress']; ?>"
                                               style="flex: 1;"
                                               onchange="updateProgress(<?php echo $enrollment['enroll_id']; ?>, this.value)">
                                        <span id="progress-display-<?php echo $enrollment['enroll_id']; ?>" 
                                              style="min-width: 40px; font-size: 0.875rem; color: var(--primary-color);">
                                            <?php echo $enrollment['progress']; ?>%
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="grid grid-2 gap-2">
                                    <a href="../../course-details.php?id=<?php echo $enrollment['course_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-play"></i> Continue
                                    </a>
                                    <button onclick="unenrollFromCourse(<?php echo $enrollment['enroll_id']; ?>)" 
                                            class="btn btn-danger btn-sm">
                                        <i class="fas fa-times"></i> Unenroll
                                    </button>
                                </div>
                                
                                <!-- Enrollment Date -->
                                <p style="margin-top: 1rem; font-size: 0.75rem; color: var(--text-secondary); text-align: center;">
                                    Enrolled: <?php echo formatDate($enrollment['enrolled_at']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                        <i class="fas fa-graduation-cap" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <h3>No Enrollments Yet</h3>
                        <p style="margin-bottom: 2rem;">You haven't enrolled in any courses yet. Start your learning journey today!</p>
                        <a href="../../courses.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Courses
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Update progress function
        function updateProgress(enrollId, progress) {
            // Update display immediately
            document.getElementById(`progress-display-${enrollId}`).textContent = progress + '%';
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('enroll_id', enrollId);
            formData.append('progress', progress);
            
            fetch('update_progress.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Progress updated successfully!', 'success');
                    // Update progress bar
                    const progressBar = document.querySelector(`#progress-${enrollId}`).closest('.course-card').querySelector('.progress-bar');
                    progressBar.style.width = progress + '%';
                    
                    // Update status badge if needed
                    const badge = document.querySelector(`#progress-${enrollId}`).closest('.course-card').querySelector('.badge');
                    if (progress == 100) {
                        badge.className = 'badge badge-success';
                        badge.textContent = 'Completed';
                    } else if (progress > 0) {
                        badge.className = 'badge badge-warning';
                        badge.textContent = 'In Progress';
                    } else {
                        badge.className = 'badge badge-primary';
                        badge.textContent = 'Enrolled';
                    }
                } else {
                    showAlert(data.message || 'Failed to update progress', 'error');
                }
            })
            .catch(error => {
                showAlert('An error occurred', 'error');
                console.error('Error:', error);
            });
        }
        
        // Unenroll function
        function unenrollFromCourse(enrollId) {
            if (confirm('Are you sure you want to unenroll from this course? This action cannot be undone.')) {
                window.location.href = `unenroll.php?id=${enrollId}`;
            }
        }
        
        // Update progress display on slider change
        document.addEventListener('DOMContentLoaded', function() {
            const progressSliders = document.querySelectorAll('input[type="range"]');
            progressSliders.forEach(slider => {
                slider.addEventListener('input', function() {
                    const enrollId = this.id.split('-')[1];
                    document.getElementById(`progress-display-${enrollId}`).textContent = this.value + '%';
                });
            });
        });
    </script>
</body>
</html>
