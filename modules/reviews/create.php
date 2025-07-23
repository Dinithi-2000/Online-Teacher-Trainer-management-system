<?php
require_once '../../config/database.php';
requireLogin();

$course_id = intval($_GET['course_id'] ?? 0);
$error = '';
$success = '';

$pdo = getDBConnection();

// Get course info
$stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: ../../courses.php');
    exit();
}

// Check if user is enrolled in the course
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$enrollment) {
    header('Location: ../../course-details.php?id=' . $course_id . '&error=' . urlencode('You must be enrolled to review this course'));
    exit();
}

// Check if user already reviewed this course
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND course_id = ?");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$existing_review = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5 stars';
    } elseif (empty($comment)) {
        $error = 'Please provide a comment';
    } else {
        if ($existing_review) {
            // Update existing review
            $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, updated_at = CURRENT_TIMESTAMP WHERE review_id = ?");
            $params = [$rating, $comment, $existing_review['review_id']];
            $success_message = 'Review updated successfully!';
        } else {
            // Create new review
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, course_id, rating, comment) VALUES (?, ?, ?, ?)");
            $params = [$_SESSION['user_id'], $course_id, $rating, $comment];
            $success_message = 'Review submitted successfully!';
        }
        
        if ($stmt->execute($params)) {
            $success = $success_message;
            // Refresh review data
            $stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$_SESSION['user_id'], $course_id]);
            $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to save review. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Course - TeachVerse</title>
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
                <i class="fas fa-star"></i> <?php echo $existing_review ? 'Edit Review' : 'Write Review'; ?>
            </h1>
            <p class="page-subtitle">Share your experience with this course</p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div style="max-width: 800px; margin: 0 auto;">
                <!-- Course Info -->
                <div class="card mb-4">
                    <div style="display: flex; align-items: center; gap: 1.5rem;">
                        <img src="../../assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                             alt="<?php echo htmlspecialchars($course['title']); ?>"
                             style="width: 120px; height: 80px; object-fit: cover; border-radius: var(--border-radius);"
                             onerror="this.src='../../assets/images/default-course.jpg'">
                        <div>
                            <h3 style="margin-bottom: 0.5rem; color: var(--primary-color);">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </h3>
                            <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars(substr($course['description'], 0, 150)) . '...'; ?>
                            </p>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?>
                                </span>
                                <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                    Your Progress: <?php echo $enrollment['progress']; ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review Form -->
                <div class="card">
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" data-validate>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-star"></i> Rating *
                            </label>
                            <div class="star-rating" style="font-size: 2rem; margin: 1rem 0;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo ($existing_review && $i <= $existing_review['rating']) ? 'filled' : ''; ?>" 
                                          data-rating="<?php echo $i; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="rating" value="<?php echo $existing_review['rating'] ?? ''; ?>" required>
                            <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                Click on the stars to rate this course
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="comment" class="form-label">
                                <i class="fas fa-comment"></i> Your Review *
                            </label>
                            <textarea id="comment" name="comment" class="form-textarea" 
                                      placeholder="Share your thoughts about this course. What did you like? What could be improved? Would you recommend it to others?"
                                      required><?php echo htmlspecialchars($existing_review['comment'] ?? ''); ?></textarea>
                            <small style="color: var(--text-secondary); font-size: 0.75rem;">
                                Your honest feedback helps other students and improves the course quality.
                            </small>
                        </div>

                        <div class="form-group">
                            <div style="padding: 1rem; background: #f8fafc; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                                <h4 style="margin-bottom: 0.5rem; color: var(--primary-color);">
                                    <i class="fas fa-info-circle"></i> Review Guidelines
                                </h4>
                                <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary); font-size: 0.875rem;">
                                    <li>Be honest and constructive in your feedback</li>
                                    <li>Focus on the course content, instruction quality, and learning experience</li>
                                    <li>Avoid personal attacks or inappropriate language</li>
                                    <li>Your review will help other students make informed decisions</li>
                                    <li>You can edit your review anytime after submission</li>
                                </ul>
                            </div>
                        </div>

                        <div class="grid grid-2 gap-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-star"></i> <?php echo $existing_review ? 'Update Review' : 'Submit Review'; ?>
                            </button>
                            <a href="../../course-details.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Course
                            </a>
                        </div>
                    </form>
                </div>

                <?php if ($existing_review): ?>
                    <!-- Delete Review Option -->
                    <div class="card" style="border-left: 4px solid var(--error-color);">
                        <h4 style="color: var(--error-color); margin-bottom: 1rem;">
                            <i class="fas fa-trash"></i> Delete Review
                        </h4>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            If you want to remove your review completely, you can delete it. This action cannot be undone.
                        </p>
                        <button onclick="deleteReview(<?php echo $existing_review['review_id']; ?>)" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete My Review
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Star rating functionality
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('rating');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    ratingInput.value = rating;
                    
                    // Update star display
                    stars.forEach((s, i) => {
                        if (i < rating) {
                            s.classList.add('filled');
                        } else {
                            s.classList.remove('filled');
                        }
                    });
                });
                
                star.addEventListener('mouseover', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    
                    // Temporarily highlight stars
                    stars.forEach((s, i) => {
                        if (i < rating) {
                            s.style.color = '#fbbf24';
                        } else {
                            s.style.color = '#d1d5db';
                        }
                    });
                });
            });
            
            // Reset hover effect
            document.querySelector('.star-rating').addEventListener('mouseleave', function() {
                const currentRating = parseInt(ratingInput.value) || 0;
                stars.forEach((s, i) => {
                    if (i < currentRating) {
                        s.style.color = '#fbbf24';
                    } else {
                        s.style.color = '#d1d5db';
                    }
                });
            });
        });
        
        // Delete review function
        function deleteReview(reviewId) {
            if (confirm('Are you sure you want to delete your review? This action cannot be undone.')) {
                window.location.href = `delete.php?id=${reviewId}`;
            }
        }
    </script>
</body>
</html>
