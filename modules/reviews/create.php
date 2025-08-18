<?php
require_once '../../config/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

// Get course ID from URL parameter
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Handle form submission
$form_error = '';
$form_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $course_id = intval($_POST['course_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');
    
    // Validate form data
    if ($course_id <= 0) {
        $form_error = 'Please select a course to review';
    } elseif ($rating < 1 || $rating > 5) {
        $form_error = 'Please select a rating between 1 and 5 stars';
    } elseif (empty($comment) || strlen($comment) < 10) {
        $form_error = 'Please provide a detailed review (at least 10 characters)';
    } else {
        // Check if user is enrolled in the course
        $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $course_id]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$enrollment) {
            $form_error = 'You must be enrolled in the course to leave a review';
        } else {
            // Check if user already reviewed this course
            $stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$_SESSION['user_id'], $course_id]);
            $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_review) {
                // Update existing review
                $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, updated_at = CURRENT_TIMESTAMP WHERE review_id = ?");
                $result = $stmt->execute([$rating, $comment, $existing_review['review_id']]);
                
                if ($result) {
                    $form_success = 'Your review has been updated successfully!';
                } else {
                    $form_error = 'Error updating review. Please try again.';
                }
            } else {
                // Create new review
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, course_id, rating, comment, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $result = $stmt->execute([$_SESSION['user_id'], $course_id, $rating, $comment]);
                
                if ($result) {
                    $form_success = 'Your review has been submitted successfully!';
                } else {
                    $form_error = 'Error submitting review. Please try again.';
                }
            }
        }
    }
}

// Get user's enrolled courses for the review form
$user_courses = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.course_id, c.title, c.image as course_image, c.description
        FROM courses c
        JOIN enrollments e ON c.course_id = e.course_id
        WHERE e.user_id = ?
        ORDER BY c.title
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If course_id is provided, get course details
$selected_course = null;
if ($course_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $selected_course = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write a Review - TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1a202c;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-menu a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Main Form Container */
        .review-form-container {
            background: white;
            border-radius: 30px;
            padding: 3rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }

        .review-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            pointer-events: none;
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }

        .form-header h1 {
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .form-header p {
            font-size: 1.1rem;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .form-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .form-header .icon i {
            font-size: 2rem;
            color: white;
        }

        /* Alert Styles */
        .alert {
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #10b981;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .alert i {
            font-size: 1.5rem;
        }

        /* Form Styles */
        .review-form {
            position: relative;
            z-index: 1;
        }

        .form-grid {
            display: grid;
            gap: 2.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 700;
            color: #374151;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-label i {
            color: #667eea;
            font-size: 1.2rem;
            width: 20px;
        }

        .form-control {
            padding: 1.25rem 1.5rem;
            border: 3px solid #e5e7eb;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f9fafb;
            color: #374151;
            font-family: inherit;
            position: relative;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 5px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-control:hover {
            border-color: #9ca3af;
            background: white;
        }

        .form-textarea {
            resize: vertical;
            min-height: 150px;
            font-family: inherit;
            line-height: 1.6;
        }

        /* Course Selection */
        .course-selector {
            background: #f8fafc;
            border: 3px solid #e5e7eb;
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .course-selector:hover {
            border-color: #667eea;
            background: white;
        }

        .course-options {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .course-option {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .course-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .course-option:hover {
            border-color: #667eea;
            transform: translateX(8px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }

        .course-option:hover::before {
            transform: scaleY(1);
        }

        .course-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
            transform: translateX(8px);
        }

        .course-option.selected::before {
            transform: scaleY(1);
        }

        .course-option input[type="radio"] {
            display: none;
        }

        .course-image {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid #e5e7eb;
            flex-shrink: 0;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }

        .course-info h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .course-info p {
            margin: 0;
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Rating System */
        .rating-container {
            background: #f8fafc;
            border: 3px solid #e5e7eb;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .rating-container:hover {
            border-color: #667eea;
            background: white;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .star-input {
            display: none;
        }

        .star-label {
            font-size: 3rem;
            color: #e5e7eb;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            transform-origin: center;
        }

        .star-label:hover {
            transform: scale(1.2) rotate(15deg);
            color: #fbbf24;
        }

        .star-input:checked ~ .star-label,
        .star-input:checked + .star-label {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .star-label:hover::after {
            content: attr(data-rating);
            position: absolute;
            top: -3rem;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            white-space: nowrap;
            pointer-events: none;
            z-index: 10;
        }

        .rating-text {
            font-size: 1.3rem;
            font-weight: 600;
            color: #4b5563;
            margin-top: 1rem;
            min-height: 2rem;
            transition: all 0.3s ease;
        }

        .rating-text.filled {
            color: #667eea;
            transform: scale(1.05);
        }

        /* Character Counter */
        .char-counter {
            text-align: right;
            font-size: 0.9rem;
            margin-top: 0.75rem;
            padding: 0.5rem 0;
            border-top: 2px solid #f3f4f6;
            transition: all 0.3s ease;
        }

        .char-counter.valid {
            color: #10b981;
            border-color: #10b981;
        }

        .char-counter.invalid {
            color: #ef4444;
            border-color: #ef4444;
        }

        .char-counter.warning {
            color: #f59e0b;
            border-color: #f59e0b;
        }

        /* Submit Button */
        .submit-container {
            margin-top: 3rem;
            text-align: center;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1.25rem 3rem;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            min-width: 200px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s ease;
        }

        .btn-submit:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:active {
            transform: translateY(-1px) scale(1.02);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-cancel {
            background: transparent;
            color: #6b7280;
            border: 2px solid #e5e7eb;
            padding: 1rem 2.5rem;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 1rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cancel:hover {
            border-color: #9ca3af;
            color: #374151;
            transform: translateY(-2px);
        }

        /* Loading State */
        .loading {
            pointer-events: none;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            transform: translate(-50%, -50%);
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 0.5rem;
            }
            
            .review-form-container {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }
            
            .form-header h1 {
                font-size: 2.5rem;
            }
            
            .course-option {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .rating-stars {
                gap: 0.5rem;
            }
            
            .star-label {
                font-size: 2.5rem;
            }
            
            .nav-menu {
                display: none;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .pulse {
            animation: pulse 0.6s ease-in-out;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="container">
        <nav class="navbar fade-in-up">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="../../index.php">
                        <i class="fas fa-graduation-cap"></i> TeachVerse
                    </a>
                </div>
                <ul class="nav-menu">
                    <li><a href="../../index.php">Home</a></li>
                    <li><a href="../../courses.php">Courses</a></li>
                    <li><a href="../trainer_profiles/index.php">Trainers</a></li>
                    <li><a href="index.php" class="active">Reviews</a></li>
                    <li><a href="../../about.php">About</a></li>
                    <li><a href="../../contact.php">Contact</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="../../dashboard.php">Dashboard</a></li>
                        <?php if (hasRole('admin')): ?>
                            <li><a href="../../admin/">Admin</a></li>
                        <?php endif; ?>
                        <li><a href="../../logout.php">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Main Form Container -->
        <div class="review-form-container fade-in-up" style="animation-delay: 0.2s;">
            <!-- Header -->
            <div class="form-header">
                <div class="icon">
                    <i class="fas fa-star"></i>
                </div>
                <h1>Share Your Experience</h1>
                <p>Your honest review helps other students make informed decisions about their learning journey. Share your thoughts about the course content, instructor quality, and overall experience.</p>
            </div>

            <!-- Alerts -->
            <?php if (!empty($form_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($form_success); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($form_error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($form_error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Review Form -->
            <form method="POST" class="review-form" id="reviewForm">
                <input type="hidden" name="submit_review" value="1">
                
                <div class="form-grid">
                    <!-- Course Selection -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-book"></i>
                            Select Course to Review
                        </label>
                        <div class="course-selector">
                            <?php if (empty($user_courses)): ?>
                                <div style="text-align: center; padding: 2rem; color: #6b7280;">
                                    <i class="fas fa-book" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                    <h3>No Enrolled Courses</h3>
                                    <p>You need to be enrolled in a course to write a review.</p>
                                    <a href="../../courses.php" class="btn-submit" style="margin-top: 1rem; display: inline-block; text-decoration: none;">
                                        <i class="fas fa-search"></i> Browse Courses
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="course-options">
                                    <?php foreach ($user_courses as $course): ?>
                                        <label class="course-option">
                                            <input type="radio" name="course_id" value="<?php echo $course['course_id']; ?>" 
                                                   <?php echo ($course_id == $course['course_id']) ? 'checked' : ''; ?> required>
                                            <img src="../../assets/images/courses/<?php echo htmlspecialchars($course['course_image'] ?: 'default-course.jpg'); ?>" 
                                                 alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                                 class="course-image"
                                                 onerror="this.src='../../assets/images/courses/default-course.jpg'">
                                            <div class="course-info">
                                                <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                                <p><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($user_courses)): ?>
                    <!-- Rating Selection -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-stars"></i>
                            Rate Your Experience
                        </label>
                        <div class="rating-container">
                            <div class="rating-stars" id="ratingStars">
                                <input type="radio" name="rating" value="1" id="star1" class="star-input" required>
                                <label for="star1" class="star-label" data-rating="Poor">★</label>
                                
                                <input type="radio" name="rating" value="2" id="star2" class="star-input">
                                <label for="star2" class="star-label" data-rating="Fair">★</label>
                                
                                <input type="radio" name="rating" value="3" id="star3" class="star-input">
                                <label for="star3" class="star-label" data-rating="Good">★</label>
                                
                                <input type="radio" name="rating" value="4" id="star4" class="star-input">
                                <label for="star4" class="star-label" data-rating="Very Good">★</label>
                                
                                <input type="radio" name="rating" value="5" id="star5" class="star-input">
                                <label for="star5" class="star-label" data-rating="Excellent">★</label>
                            </div>
                            <div class="rating-text" id="ratingText">Click to rate your experience</div>
                        </div>
                    </div>

                    <!-- Comment -->
                    <div class="form-group">
                        <label for="comment" class="form-label">
                            <i class="fas fa-comment-dots"></i>
                            Your Detailed Review
                        </label>
                        <textarea name="comment" id="comment" class="form-control form-textarea" 
                                  placeholder="Share your detailed experience with this course. What did you like most? What could be improved? How would you recommend it to other students? Be specific and honest to help others make informed decisions."
                                  required><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                        <div class="char-counter" id="charCounter">0/500 characters (minimum 10 required)</div>
                    </div>

                    <!-- Submit Button -->
                    <div class="submit-container">
                        <a href="index.php" class="btn-cancel">
                            <i class="fas fa-arrow-left"></i> Back to Reviews
                        </a>
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-paper-plane"></i> Submit Review
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reviewForm = document.getElementById('reviewForm');
            const starInputs = document.querySelectorAll('.star-input');
            const starLabels = document.querySelectorAll('.star-label');
            const ratingText = document.getElementById('ratingText');
            const commentTextarea = document.getElementById('comment');
            const charCounter = document.getElementById('charCounter');
            const submitBtn = document.getElementById('submitBtn');
            const courseOptions = document.querySelectorAll('.course-option');

            // Rating texts
            const ratingTexts = {
                1: 'Poor - Needs significant improvement',
                2: 'Fair - Below expectations',
                3: 'Good - Meets expectations',
                4: 'Very Good - Exceeds expectations',
                5: 'Excellent - Outstanding quality!'
            };

            // Star rating functionality
            starInputs.forEach((input, index) => {
                input.addEventListener('change', function() {
                    const rating = parseInt(this.value);
                    ratingText.textContent = ratingTexts[rating];
                    ratingText.classList.add('filled');
                    
                    // Add pulse animation
                    ratingText.classList.add('pulse');
                    setTimeout(() => {
                        ratingText.classList.remove('pulse');
                    }, 600);
                    
                    // Update star colors with animation
                    starLabels.forEach((label, labelIndex) => {
                        setTimeout(() => {
                            if (labelIndex < rating) {
                                label.style.color = '#fbbf24';
                                label.style.transform = 'scale(1.1)';
                            } else {
                                label.style.color = '#e5e7eb';
                                label.style.transform = 'scale(1)';
                            }
                        }, labelIndex * 100);
                    });
                });
            });

            // Course selection functionality
            courseOptions.forEach(option => {
                option.addEventListener('click', function() {
                    courseOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    // Add pulse animation
                    this.classList.add('pulse');
                    setTimeout(() => {
                        this.classList.remove('pulse');
                    }, 600);
                });
            });

            // Character counter
            function updateCharCounter() {
                const length = commentTextarea.value.length;
                const maxLength = 500;
                const minLength = 10;
                
                charCounter.textContent = `${length}/${maxLength} characters`;
                
                if (length < minLength) {
                    charCounter.textContent += ` (minimum ${minLength} required)`;
                    charCounter.className = 'char-counter invalid';
                } else if (length > maxLength) {
                    charCounter.textContent = `${maxLength}/${maxLength} characters (maximum reached)`;
                    charCounter.className = 'char-counter warning';
                    commentTextarea.value = commentTextarea.value.substring(0, maxLength);
                } else {
                    charCounter.className = 'char-counter valid';
                }
            }

            if (commentTextarea && charCounter) {
                commentTextarea.addEventListener('input', updateCharCounter);
                updateCharCounter();
            }

            // Form validation and submission
            if (reviewForm) {
                reviewForm.addEventListener('submit', function(e) {
                    const courseId = document.querySelector('input[name="course_id"]:checked');
                    const rating = document.querySelector('input[name="rating"]:checked');
                    const comment = commentTextarea.value.trim();

                    let errors = [];

                    if (!courseId) {
                        errors.push('Please select a course to review');
                    }

                    if (!rating) {
                        errors.push('Please select a rating');
                    }

                    if (!comment || comment.length < 10) {
                        errors.push('Please provide a detailed review (at least 10 characters)');
                    }

                    if (errors.length > 0) {
                        e.preventDefault();
                        
                        // Show error message
                        let existingAlert = document.querySelector('.alert-error');
                        if (existingAlert) {
                            existingAlert.remove();
                        }
                        
                        const errorAlert = document.createElement('div');
                        errorAlert.className = 'alert alert-error';
                        errorAlert.innerHTML = `
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Please fix the following errors:</strong>
                                <ul style="margin: 0.5rem 0 0 1rem;">${errors.map(error => `<li>${error}</li>`).join('')}</ul>
                            </div>
                        `;
                        
                        reviewForm.insertBefore(errorAlert, reviewForm.firstChild);
                        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }

                    // Add loading state
                    submitBtn.classList.add('loading');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Review...';
                    submitBtn.disabled = true;
                });
            }

            // Auto-hide success messages
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    successAlert.style.opacity = '0';
                    successAlert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        successAlert.style.display = 'none';
                    }, 500);
                }, 6000);
            }

            // Add hover effects to form elements
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.parentNode.style.transform = 'translateY(-2px)';
                });
                
                control.addEventListener('blur', function() {
                    this.parentNode.style.transform = 'translateY(0)';
                });
            });

            // Initialize pre-selected course if provided
            const preSelectedCourse = document.querySelector('input[name="course_id"]:checked');
            if (preSelectedCourse) {
                preSelectedCourse.closest('.course-option').classList.add('selected');
            }
        });
    </script>
</body>
</html>
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
