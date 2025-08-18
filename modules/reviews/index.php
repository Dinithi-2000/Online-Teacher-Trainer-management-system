<?php
require_once '../../config/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

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

// Get user's enrolled courses
$user_courses = [];
$stmt = $pdo->prepare("
    SELECT DISTINCT c.course_id, c.title, c.image as course_image, c.description, c.price,
           u.name as instructor_name
    FROM courses c
    JOIN enrollments e ON c.course_id = e.course_id
    LEFT JOIN users u ON c.created_by = u.user_id
    WHERE e.user_id = ?
    ORDER BY c.title
");
$stmt->execute([$_SESSION['user_id']]);
$user_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get course for pre-selection
$selected_course_id = intval($_GET['course_id'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write a Review - TeachVerse</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
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
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Main Container */
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem 2rem;
            position: relative;
            z-index: 1;
        }

        .nav-menu a:hover, .nav-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Profile Dropdown Styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 200px;
            border-radius: 15px;
            padding: 1rem 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(20px);
        }

        .dropdown:hover .dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .dropdown-content a:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1f2937;
            transform: translateX(5px);
        }

        .dropdown-content a i {
            width: 16px;
            color: #667eea;
        }

        /* Auth Buttons */
        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-left: auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-login {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .btn-register {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            border: 2px solid rgba(255, 255, 255, 0.9);
            font-weight: 700;
        }

        .btn-register:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            color: #5a67d8;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background: transparent;
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        /* Main Container */
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem 2rem;
            position: relative;
            z-index: 1;
        }

        /* Form Container */
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 4rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: slideInUp 0.8s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .form-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .form-header h1 {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-header p {
            font-size: 1.2rem;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* Alerts */
        .alert {
            padding: 1.5rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            display: grid;
            gap: 2.5rem;
        }

        .form-section {
            background: #f8fafc;
            padding: 2.5rem;
            border-radius: 20px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-section:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: #667eea;
            font-size: 1.25rem;
        }

        /* Course Selection */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .course-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            border: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .course-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }

        .course-card:hover::before {
            opacity: 1;
        }

        .course-card.selected {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        }

        .course-card input[type="radio"] {
            display: none;
        }

        .course-header {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .course-image {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }

        .course-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .course-instructor {
            font-size: 0.9rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .course-description {
            color: #4b5563;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .course-price {
            font-weight: 700;
            color: #059669;
            font-size: 1.1rem;
        }

        /* Rating Section */
        .rating-container {
            text-align: center;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 2rem 0;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            border: 3px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .rating-stars:hover {
            border-color: #667eea;
            transform: scale(1.02);
        }

        .star-input {
            display: none;
        }

        .star-label {
            font-size: 3rem;
            color: #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: block;
        }

        .star-label:hover {
            transform: scale(1.2);
        }

        .star-input:checked ~ .star-label,
        .star-input:checked + .star-label {
            color: #fbbf24;
            animation: starPulse 0.3s ease;
        }

        @keyframes starPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
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
            font-weight: 600;
            white-space: nowrap;
            pointer-events: none;
            z-index: 10;
        }

        .rating-description {
            font-size: 1.3rem;
            font-weight: 600;
            color: #374151;
            margin-top: 1rem;
            min-height: 2rem;
        }

        /* Comment Section */
        .comment-field {
            position: relative;
        }

        .comment-textarea {
            width: 100%;
            min-height: 150px;
            padding: 1.5rem;
            border: 3px solid #e2e8f0;
            border-radius: 20px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            transition: all 0.3s ease;
            background: white;
        }

        .comment-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: scale(1.01);
        }

        .comment-counter {
            text-align: right;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #6b7280;
            transition: color 0.3s ease;
        }

        .comment-counter.valid {
            color: #059669;
        }

        .comment-counter.invalid {
            color: #dc2626;
        }

        /* Submit Section */
        .submit-section {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 20px;
            border: 2px dashed #cbd5e0;
        }

        .submit-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2.5rem;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 180px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        /* Loading State */
        .btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #374151;
        }

        .empty-state p {
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .nav-left {
                gap: 2rem;
            }
            
            .nav-menu {
                gap: 1.5rem;
            }
            
            .auth-buttons {
                gap: 0.75rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 2rem 1rem;
            }

            .form-container {
                padding: 2rem;
                border-radius: 20px;
            }

            .form-header h1 {
                font-size: 2.5rem;
            }

            .course-grid {
                grid-template-columns: 1fr;
            }

            .rating-stars {
                gap: 0.5rem;
            }

            .star-label {
                font-size: 2.5rem;
            }

            .submit-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }

            .nav-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-left {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }

            .nav-menu {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-menu a {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .auth-buttons {
                width: 100%;
                justify-content: center;
                gap: 1rem;
            }

            .auth-buttons .btn {
                flex: 1;
                max-width: 120px;
                padding: 0.75rem 1rem;
                justify-content: center;
            }

            .dropdown-content {
                right: 0;
                left: auto;
                min-width: 180px;
            }

            .nav-container {
                padding: 0 1rem;
            }

            .nav-logo a {
                font-size: 1.5rem;
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <div class="form-container">
            <!-- Header -->
            <div class="form-header">
                <h1>Share Your Experience</h1>
                <p>Help other students by sharing your honest review about the courses you've taken. Your feedback makes a difference!</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Review Form -->
            <form method="POST" class="review-form" id="reviewForm">
                <!-- Course Selection Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-book-open"></i>
                        Select Course
                    </h3>
                    
                    <?php if (empty($user_courses)): ?>
                        <div class="empty-state">
                            <i class="fas fa-graduation-cap"></i>
                            <h3>No Enrolled Courses</h3>
                            <p>You need to be enrolled in at least one course to write a review.</p>
                            <a href="../../courses.php" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Browse Courses
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($user_courses as $course): ?>
                                <label class="course-card" for="course_<?php echo $course['course_id']; ?>">
                                    <input type="radio" 
                                           name="course_id" 
                                           id="course_<?php echo $course['course_id']; ?>" 
                                           value="<?php echo $course['course_id']; ?>" 
                                           required>
                                    
                                    <div class="course-header">
                                        <img src="../../assets/images/courses/<?php echo htmlspecialchars($course['course_image'] ?: 'default-course.jpg'); ?>" 
                                             alt="Course Image" 
                                             class="course-image"
                                             onerror="this.src='../../assets/images/courses/default-course.jpg'">
                                        <div class="course-info">
                                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                            <div class="course-instructor">
                                                <i class="fas fa-user-tie"></i>
                                                <span><?php echo htmlspecialchars($course['instructor_name'] ?: 'TBD'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="course-description">
                                        <?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...
                                    </div>
                                    
                                    <div class="course-price">
                                        $<?php echo number_format($course['price'], 2); ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($user_courses)): ?>
                    <!-- Rating Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-star"></i>
                            Rate Your Experience
                        </h3>
                        
                        <div class="rating-container">
                            <div class="rating-stars">
                                <input type="radio" name="rating" id="star5" value="5" class="star-input" required>
                                <label for="star5" class="star-label" data-rating="Excellent">★</label>
                                
                                <input type="radio" name="rating" id="star4" value="4" class="star-input">
                                <label for="star4" class="star-label" data-rating="Very Good">★</label>
                                
                                <input type="radio" name="rating" id="star3" value="3" class="star-input">
                                <label for="star3" class="star-label" data-rating="Good">★</label>
                                
                                <input type="radio" name="rating" id="star2" value="2" class="star-input">
                                <label for="star2" class="star-label" data-rating="Fair">★</label>
                                
                                <input type="radio" name="rating" id="star1" value="1" class="star-input">
                                <label for="star1" class="star-label" data-rating="Poor">★</label>
                            </div>
                            
                            <div class="rating-description" id="ratingDescription">
                                Click on stars to rate your experience
                            </div>
                        </div>
                    </div>

                    <!-- Comment Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-comment-alt"></i>
                            Share Your Thoughts
                        </h3>
                        
                        <div class="comment-field">
                            <textarea name="comment" 
                                      id="comment" 
                                      class="comment-textarea" 
                                      placeholder="Tell us about your learning experience. What did you like most? What could be improved? Your detailed feedback helps other students make informed decisions..." 
                                      required
                                      minlength="10" 
                                      maxlength="1000"></textarea>
                            <div class="comment-counter">
                                <span id="charCount">0</span> / 1000 characters
                            </div>
                        </div>
                    </div>

                    <!-- Submit Section -->
                    <div class="submit-section">
                        <div class="submit-buttons">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-paper-plane"></i>
                                Submit Review
                            </button>
                            <a href="../../courses.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back to Courses
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Course Selection
            const courseCards = document.querySelectorAll('.course-card');
            courseCards.forEach(card => {
                card.addEventListener('click', function() {
                    courseCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });

            // Rating System
            const stars = document.querySelectorAll('.star-label');
            const ratingDescription = document.getElementById('ratingDescription');
            const ratingDescriptions = {
                1: '⭐ Poor - Not recommended',
                2: '⭐⭐ Fair - Below expectations', 
                3: '⭐⭐⭐ Good - Meets expectations',
                4: '⭐⭐⭐⭐ Very Good - Exceeds expectations',
                5: '⭐⭐⭐⭐⭐ Excellent - Outstanding experience!'
            };

            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    const value = this.previousElementSibling.value;
                    ratingDescription.textContent = ratingDescriptions[value];
                    ratingDescription.style.color = value >= 4 ? '#059669' : value >= 3 ? '#d97706' : '#dc2626';
                });
                
                star.addEventListener('mouseover', function() {
                    const value = this.previousElementSibling.value;
                    ratingDescription.textContent = ratingDescriptions[value];
                    ratingDescription.style.opacity = '0.7';
                });
                
                star.addEventListener('mouseout', function() {
                    const selectedRating = document.querySelector('input[name="rating"]:checked');
                    if (selectedRating) {
                        ratingDescription.textContent = ratingDescriptions[selectedRating.value];
                        ratingDescription.style.opacity = '1';
                    } else {
                        ratingDescription.textContent = 'Click on stars to rate your experience';
                        ratingDescription.style.opacity = '1';
                    }
                });
            });

            // Character Counter
            const commentTextarea = document.getElementById('comment');
            const charCount = document.getElementById('charCount');
            const commentCounter = document.querySelector('.comment-counter');

            commentTextarea.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length;
                
                if (length < 10) {
                    commentCounter.className = 'comment-counter invalid';
                } else if (length > 950) {
                    commentCounter.className = 'comment-counter invalid';
                } else {
                    commentCounter.className = 'comment-counter valid';
                }
            });

            // Form Submission
            const form = document.getElementById('reviewForm');
            const submitBtn = document.getElementById('submitBtn');

            form.addEventListener('submit', function(e) {
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Submitting...';
                submitBtn.disabled = true;
            });

            // Smooth animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            });

            document.querySelectorAll('.form-section').forEach(section => {
                observer.observe(section);
            });

            // Profile Dropdown Enhancement
            const dropdown = document.querySelector('.dropdown');
            const dropdownToggle = document.querySelector('.dropdown-toggle');
            const dropdownContent = document.querySelector('.dropdown-content');

            if (dropdown && dropdownToggle && dropdownContent) {
                // Add click functionality for mobile
                dropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdown.classList.toggle('active');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });

                // Add active class styles for mobile
                const style = document.createElement('style');
                style.textContent = `
                    .dropdown.active .dropdown-content {
                        opacity: 1;
                        visibility: visible;
                        transform: translateY(0);
                    }
                    
                    @media (max-width: 768px) {
                        .dropdown-content {
                            position: fixed;
                            top: 80px;
                            right: 1rem;
                            left: 1rem;
                            width: auto;
                            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                            z-index: 9999;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        });
    </script>
</body>
</html>
