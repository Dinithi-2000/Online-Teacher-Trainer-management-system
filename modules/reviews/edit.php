<?php
require_once '../../config/database.php';
requireLogin();

$pdo = getDBConnection();

// Get review ID from URL
$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($review_id <= 0) {
    $_SESSION['error_message'] = 'Invalid review ID.';
    header('Location: index.php');
    exit;
}

// Fetch review data
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.title as course_title, c.course_id, u.name as reviewer_name
        FROM reviews r 
        JOIN courses c ON r.course_id = c.course_id 
        JOIN users u ON r.user_id = u.user_id 
        WHERE r.review_id = ?
    ");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        $_SESSION['error_message'] = 'Review not found.';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Check permissions - only review owner or admin can edit
if ($review['user_id'] != $_SESSION['user_id'] && !hasRole('admin')) {
    $_SESSION['error_message'] = 'You do not have permission to edit this review.';
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = sanitize($_POST['comment']);
    
    // Validation
    $errors = [];
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating must be between 1 and 5 stars.';
    }
    
    if (empty($comment)) {
        $errors[] = 'Review comment is required.';
    }
    
    if (strlen($comment) < 10) {
        $errors[] = 'Review comment must be at least 10 characters long.';
    }
    
    // Update review if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE reviews 
                SET rating = ?, comment = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE review_id = ?
            ");
            $stmt->execute([$rating, $comment, $review_id]);
            
            $_SESSION['success_message'] = 'Review updated successfully!';
            header('Location: ../../course-details.php?id=' . $review['course_id']);
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review - TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="../../index.php">
                    <i class="fas fa-graduation-cap"></i> TeachVerse
                </a>
            </div>
            <div class="nav-menu">
                <a href="../../index.php">Home</a>
                <a href="../../courses.php">Courses</a>
                <a href="../../dashboard.php">Dashboard</a>
                <a href="index.php">Reviews</a>
                <div class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['name']); ?> <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="nav-dropdown-content">
                        <a href="../../profile.php"><i class="fas fa-user-edit"></i> Profile</a>
                        <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="page-header-content">
                <div class="page-title-section">
                    <h1 class="page-title">
                        <i class="fas fa-edit"></i> Edit Review
                    </h1>
                    <p class="page-subtitle">Update your review for "<?php echo htmlspecialchars($course['title']); ?>"</p>
                </div>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Reviews
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="review-edit-layout">
                <!-- Course Information -->
                <div class="course-info-section">
                    <div class="card">
                        <div class="course-header">
                            <img src="../../assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                                 alt="Course Image" class="course-image">
                            <div class="course-details">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 150)) . '...'; ?></p>
                                <div class="course-meta">
                                    <span class="duration"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?></span>
                                    <span class="price"><i class="fas fa-dollar-sign"></i> <?php echo formatPrice($course['price']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review Form -->
                <div class="review-form-section">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-star"></i> Your Review</h2>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-error">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="review-form" id="reviewForm">
                                <div class="form-group">
                                    <label for="rating">Your Rating *</label>
                                    <div class="rating-input">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" 
                                                   <?php echo (isset($review['rating']) && $review['rating'] == $i) ? 'checked' : ''; ?> required>
                                            <label for="star<?php echo $i; ?>" class="star-label">
                                                <i class="fas fa-star"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="rating-text">
                                        <span id="ratingText">
                                            <?php 
                                            if (isset($review['rating'])) {
                                                $ratings = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very Good', 5 => 'Excellent'];
                                                echo $ratings[$review['rating']];
                                            } else {
                                                echo 'Select a rating';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="comment">Your Review *</label>
                                    <textarea id="comment" name="comment" rows="6" required 
                                              placeholder="Share your experience with this course..."><?php echo isset($review['comment']) ? htmlspecialchars($review['comment']) : ''; ?></textarea>
                                    <div class="character-count">
                                        <span id="charCount"><?php echo isset($review['comment']) ? strlen($review['comment']) : 0; ?></span>/1000 characters
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Update Review
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>

    <style>
    .page-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .review-edit-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .course-header {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .course-image {
        width: 120px;
        height: 80px;
        border-radius: 8px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .course-details h3 {
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
        font-size: 1.3rem;
    }

    .course-description {
        margin: 0.5rem 0;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    .course-meta {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .course-meta span {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .rating-input {
        display: flex;
        flex-direction: row-reverse;
        gap: 0.25rem;
        margin-bottom: 0.5rem;
    }

    .rating-input input[type="radio"] {
        display: none;
    }

    .star-label {
        font-size: 2rem;
        color: #ddd;
        cursor: pointer;
        transition: color 0.3s ease, transform 0.2s ease;
    }

    .star-label:hover,
    .star-label:hover ~ .star-label {
        color: #ffc107;
        transform: scale(1.1);
    }

    .rating-input input[type="radio"]:checked ~ .star-label {
        color: #ffc107;
    }

    .rating-text {
        text-align: center;
        margin-top: 0.5rem;
    }

    #ratingText {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--primary-color);
    }

    .character-count {
        text-align: right;
        margin-top: 0.5rem;
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn-lg {
        padding: 1rem 2rem;
        font-size: 1.1rem;
    }

    .nav-dropdown {
        position: relative;
        display: inline-block;
    }

    .nav-dropdown-toggle {
        text-decoration: none;
        color: white;
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .nav-dropdown-content {
        display: none;
        position: absolute;
        background: white;
        min-width: 200px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        z-index: 1000;
        right: 0;
        border-radius: 8px;
        overflow: hidden;
    }

    .nav-dropdown:hover .nav-dropdown-content {
        display: block;
    }

    .nav-dropdown-content a {
        color: var(--text-primary);
        padding: 0.75rem 1rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: background-color 0.3s ease;
    }

    .nav-dropdown-content a:hover {
        background-color: var(--background-light);
    }

    @media (max-width: 768px) {
        .review-edit-layout {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .course-header {
            flex-direction: column;
            text-align: center;
        }
        
        .course-image {
            width: 100%;
            height: 200px;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .page-header-content {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        const ratingText = document.getElementById('ratingText');
        const commentTextarea = document.getElementById('comment');
        const charCount = document.getElementById('charCount');
        const maxLength = 1000;
        
        const ratingTexts = {
            1: 'Poor',
            2: 'Fair', 
            3: 'Good',
            4: 'Very Good',
            5: 'Excellent'
        };
        
        // Rating selection handler
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                ratingText.textContent = ratingTexts[this.value];
            });
        });
        
        // Character counter
        commentTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            charCount.textContent = currentLength;
            
            if (currentLength > maxLength * 0.9) {
                charCount.style.color = '#dc3545';
            } else if (currentLength > maxLength * 0.7) {
                charCount.style.color = '#ffc107';
            } else {
                charCount.style.color = '#6c757d';
            }
        });
        
        // Form validation
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            const rating = document.querySelector('input[name="rating"]:checked');
            const comment = commentTextarea.value.trim();
            
            if (!rating) {
                e.preventDefault();
                alert('Please select a rating.');
                return;
            }
            
            if (!comment) {
                e.preventDefault();
                alert('Please write a review comment.');
                return;
            }
            
            if (comment.length > maxLength) {
                e.preventDefault();
                alert(`Review comment cannot exceed ${maxLength} characters.`);
                return;
            }
        });
    });
    </script>
</body>
</html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review - TeachVerse</title>
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
                <a href="../../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Edit Review</h1>
                <div class="header-actions">
                    <a href="../../course-details.php?id=<?php echo $review['course_id']; ?>" class="btn btn-secondary">
                        Back to Course
                    </a>
                    <?php if (hasRole('admin')): ?>
                        <a href="index.php" class="btn btn-secondary">All Reviews</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="edit-review-layout">
                <!-- Course Information -->
                <div class="course-info-card">
                    <h3>Course Being Reviewed</h3>
                    <div class="course-details">
                        <h4><?php echo htmlspecialchars($review['course_title']); ?></h4>
                        <p>Your review will help other students make informed decisions about this course.</p>
                        
                        <div class="review-info">
                            <div class="info-item">
                                <strong>Original Review Date:</strong>
                                <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                            </div>
                            <?php if ($review['created_at'] != $review['updated_at']): ?>
                                <div class="info-item">
                                    <strong>Last Updated:</strong>
                                    <?php echo date('M j, Y g:i A', strtotime($review['updated_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Edit Review Form -->
                <div class="form-container">
                    <form method="POST" class="review-form">
                        <div class="form-group">
                            <label>Rating *</label>
                            <div class="star-rating-input">
                                <input type="hidden" id="rating" name="rating" value="<?php echo $review['rating']; ?>" required>
                                <div class="stars" id="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="star fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>" 
                                           data-rating="<?php echo $i; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-text" id="rating-text">
                                    <?php echo $review['rating']; ?> out of 5 stars
                                </span>
                            </div>
                            <small class="form-text">Click on the stars to rate this course</small>
                        </div>

                        <div class="form-group">
                            <label for="comment">Your Review *</label>
                            <textarea 
                                id="comment" 
                                name="comment" 
                                rows="8" 
                                placeholder="Share your experience with this course. What did you like? What could be improved? How would you recommend it to others?"
                                required
                                minlength="10"
                            ><?php echo htmlspecialchars($review['comment']); ?></textarea>
                            <small class="form-text">
                                <span id="char-count"><?php echo strlen($review['comment']); ?></span> characters (minimum 10)
                            </small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Review
                            </button>
                            <a href="../../course-details.php?id=<?php echo $review['course_id']; ?>" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Review Preview -->
            <div class="review-preview-card">
                <h3>Preview</h3>
                <div class="review-preview" id="review-preview">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-avatar">
                                <?php echo strtoupper(substr($review['reviewer_name'], 0, 2)); ?>
                            </div>
                            <div class="reviewer-details">
                                <h4><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                <div class="preview-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <div class="review-date">
                            Updated now
                        </div>
                    </div>
                    <div class="review-content">
                        <p id="preview-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');
        const ratingText = document.getElementById('rating-text');
        const commentTextarea = document.getElementById('comment');
        const charCount = document.getElementById('char-count');
        const previewComment = document.getElementById('preview-comment');
        const previewStars = document.querySelectorAll('.preview-stars .fa-star');

        // Star rating functionality
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                
                // Update star display
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                // Update preview stars
                previewStars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                // Update rating text
                const ratingTexts = {
                    1: '1 out of 5 stars - Poor',
                    2: '2 out of 5 stars - Fair',
                    3: '3 out of 5 stars - Good',
                    4: '4 out of 5 stars - Very Good',
                    5: '5 out of 5 stars - Excellent'
                };
                ratingText.textContent = ratingTexts[rating];
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
        
        document.getElementById('star-rating').addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingInput.value);
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });

        // Character count and live preview
        commentTextarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            // Update character count color
            if (length < 10) {
                charCount.style.color = '#dc3545';
            } else {
                charCount.style.color = '#28a745';
            }
            
            // Update preview
            previewComment.textContent = this.value || 'Your review will appear here...';
        });
        
        // Initialize character count
        commentTextarea.dispatchEvent(new Event('input'));
    });
    </script>

    <style>
    .edit-review-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .course-info-card,
    .review-preview-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        height: fit-content;
    }

    .course-info-card h3,
    .review-preview-card h3 {
        margin-bottom: 1rem;
        color: var(--text-primary);
    }

    .course-details h4 {
        margin: 0 0 0.5rem 0;
        color: var(--primary-color);
    }

    .course-details p {
        color: var(--text-secondary);
        margin-bottom: 1rem;
    }

    .review-info {
        background: var(--background-light);
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
    }

    .info-item {
        margin-bottom: 0.5rem;
    }

    .info-item:last-child {
        margin-bottom: 0;
    }

    .info-item strong {
        color: var(--text-primary);
    }

    .star-rating-input {
        margin-bottom: 1rem;
    }

    .stars {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .star {
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s ease;
        margin-right: 0.25rem;
    }

    .star.active {
        color: #ffc107;
    }

    .star:hover {
        color: #ffc107;
    }

    .rating-text {
        color: var(--text-secondary);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .review-preview {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1rem;
        background: var(--background-light);
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .reviewer-info {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .reviewer-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .reviewer-details h4 {
        margin: 0 0 0.25rem 0;
        font-size: 1rem;
        color: var(--text-primary);
    }

    .preview-stars {
        font-size: 0.9rem;
    }

    .preview-stars .fa-star {
        color: #ddd;
        margin-right: 0.1rem;
    }

    .preview-stars .fa-star.active {
        color: #ffc107;
    }

    .review-date {
        color: var(--text-secondary);
        font-size: 0.8rem;
    }

    .review-content p {
        margin: 0;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    #char-count {
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .edit-review-layout {
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
        
        .review-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .reviewer-info {
            align-self: flex-start;
        }
    }
    </style>
</body>
</html>
