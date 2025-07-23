<?php
require_once '../../config/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

// Get search and filter parameters
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.title LIKE ? OR u.name LIKE ? OR r.comment LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($course_filter > 0) {
    $where_conditions[] = "r.course_id = ?";
    $params[] = $course_filter;
}

if ($rating_filter > 0) {
    $where_conditions[] = "r.rating = ?";
    $params[] = $rating_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get reviews with course and user information
try {
    $query = "
        SELECT r.*, c.title as course_title, c.course_image, u.name as user_name,
               tp.profile_image as trainer_image, trainer.name as trainer_name
        FROM reviews r
        JOIN courses c ON r.course_id = c.course_id
        JOIN users u ON r.user_id = u.user_id
        LEFT JOIN users trainer ON c.created_by = trainer.user_id
        LEFT JOIN trainer_profiles tp ON trainer.user_id = tp.user_id
        $where_clause
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Check if we have reviews
    $debug_info = "Reviews found: " . count($reviews);
    if (empty($reviews)) {
        // Check if there are any reviews at all
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reviews");
        $count_stmt->execute();
        $total_reviews = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $debug_info .= " | Total reviews in DB: " . $total_reviews;
    }
    
} catch (PDOException $e) {
    $reviews = [];
    $debug_info = "Database error: " . $e->getMessage();
}

// Handle review form submission
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
    } elseif (empty($comment)) {
        $form_error = 'Please provide a comment for your review';
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
        SELECT DISTINCT c.course_id, c.title, c.course_image
        FROM courses c
        JOIN enrollments e ON c.course_id = e.course_id
        WHERE e.user_id = ?
        ORDER BY c.title
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all courses for filter dropdown
$courses_stmt = $pdo->prepare("SELECT course_id, title FROM courses ORDER BY title");
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as average_rating,
        COUNT(DISTINCT course_id) as courses_reviewed,
        COUNT(DISTINCT user_id) as unique_reviewers
    FROM reviews
");
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get rating distribution
$rating_dist_stmt = $pdo->prepare("
    SELECT rating, COUNT(*) as count 
    FROM reviews 
    GROUP BY rating 
    ORDER BY rating DESC
");
$rating_dist_stmt->execute();
$rating_distribution = $rating_dist_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Reviews - TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            background: #f8fafc;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header Styles */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            background: linear-gradient(45deg, #fff, #f0f8ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-content p {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 2rem;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .hero-stat p {
            opacity: 0.9;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Main Content */
        .main-content {
            margin: -2rem 0 0 0;
            position: relative;
            z-index: 2;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .filter-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .filter-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f7fafc;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        /* Reviews Grid */
        .reviews-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .review-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .review-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .review-card:hover::before {
            transform: scaleX(1);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .course-info {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex: 1;
        }

        .course-thumbnail {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            object-fit: cover;
            border: 3px solid #f7fafc;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .course-details h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .course-details h3 a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .course-details h3 a:hover {
            color: #667eea;
        }

        .trainer-info {
            color: #718096;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 12px;
        }

        .stars {
            display: flex;
            gap: 2px;
        }

        .star {
            color: #ffd700;
            font-size: 1.1rem;
        }

        .star.empty {
            color: #e2e8f0;
        }

        .rating-number {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .review-content {
            margin-bottom: 1.5rem;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
        }

        .reviewer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .reviewer-details h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .review-date {
            font-size: 0.8rem;
            color: #718096;
        }

        .review-comment {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 1.25rem;
            margin-top: 1rem;
        }

        .review-comment p {
            color: #4a5568;
            line-height: 1.6;
            margin: 0;
        }

        .review-actions {
            display: flex;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        /* Sidebar */
        .reviews-sidebar {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .sidebar-section {
            margin-bottom: 2rem;
        }

        .sidebar-section:last-child {
            margin-bottom: 0;
        }

        .sidebar-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .rating-breakdown {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .rating-label {
            font-size: 0.85rem;
            color: #4a5568;
            min-width: 60px;
        }

        .rating-bar {
            flex: 1;
            height: 8px;
            background: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.5s ease;
        }

        .rating-count {
            font-size: 0.8rem;
            color: #718096;
            min-width: 30px;
            text-align: right;
        }

        .overall-rating {
            text-align: center;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }

        .overall-number {
            font-size: 3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .overall-stars {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-bottom: 0.5rem;
        }

        .overall-text {
            color: #718096;
            font-size: 0.9rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .empty-icon {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #718096;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .reviews-container {
                grid-template-columns: 1fr;
            }
            
            .reviews-sidebar {
                order: -1;
                position: relative;
                top: 0;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .review-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .course-info {
                width: 100%;
            }
            
            .rating-display {
                align-self: flex-start;
            }
            
            .review-actions {
                flex-direction: column;
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        .slide-up {
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(40px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        /* Loading states */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            transform: translate(-50%, -50%);
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Modern Review Form Styles */
        .review-form-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 4rem 0;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }

        .review-form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(102,126,234,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            opacity: 0.4;
        }

        .form-container {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .form-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-header p {
            font-size: 1.1rem;
            color: #718096;
            max-width: 600px;
            margin: 0 auto;
        }

        .review-form {
            display: grid;
            gap: 2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        .form-field {
            display: flex;
            flex-direction: column;
        }

        .form-field.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.75rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: #667eea;
            font-size: 1.1rem;
        }

        .form-input, .form-select, .form-textarea {
            padding: 1rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
            color: #2d3748;
            font-family: inherit;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .rating-input-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .rating-stars {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 15px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .rating-stars:hover {
            border-color: #667eea;
            background: white;
        }

        .star-input {
            display: none;
        }

        .star-label {
            font-size: 2rem;
            color: #e2e8f0;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .star-label:hover,
        .star-input:checked ~ .star-label,
        .star-input:checked + .star-label {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .star-label:hover::after {
            content: attr(data-rating);
            position: absolute;
            top: -2.5rem;
            left: 50%;
            transform: translateX(-50%);
            background: #2d3748;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            pointer-events: none;
        }

        .rating-text {
            text-align: center;
            font-weight: 600;
            color: #4a5568;
            font-size: 1.1rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-width: 180px;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-reset {
            background: transparent;
            color: #718096;
            border: 2px solid #e2e8f0;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            border-color: #cbd5e0;
            color: #4a5568;
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            font-size: 1.2rem;
        }

        /* Course Selection Enhancement */
        .course-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .course-option:hover {
            border-color: #667eea;
            background: white;
            transform: translateX(5px);
        }

        .course-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
        }

        .course-image {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .course-info h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
        }

        /* Responsive Design for Form */
        @media (max-width: 768px) {
            .review-form-section {
                padding: 2rem 0;
            }
            
            .form-container {
                margin: 0 1rem;
                padding: 2rem 1.5rem;
            }
            
            .form-header h2 {
                font-size: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-submit, .btn-reset {
                width: 100%;
            }
            
            .rating-stars {
                gap: 0.25rem;
            }
            
            .star-label {
                font-size: 1.5rem;
            }
        }
        
        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }
        
        .alert-info {
            background-color: #e3f2fd;
            border-color: #1976d2;
            color: #0d47a1;
        }
        
        /* Review Actions */
        .review-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.2s;
        }
        
        .btn-warning {
            background-color: #f59e0b;
            color: white;
            border: 1px solid #f59e0b;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
            border-color: #d97706;
        }
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
            border: 1px solid #ef4444;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
            border-color: #dc2626;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../../index.php"><i class="fas fa-graduation-cap"></i> TeachVerse</a>
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
                        <li><a href="../../admin/">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="../../logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="../../auth.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content fade-in">
                <h1><i class="fas fa-star"></i> Course Reviews</h1>
                <p>Discover what our students think about our courses and make informed decisions about your learning journey</p>
                
                <div class="hero-stats">
                    <div class="hero-stat">
                        <h3><?php echo number_format($stats['total_reviews']); ?></h3>
                        <p>Total Reviews</p>
                    </div>
                    <div class="hero-stat">
                        <h3><?php echo number_format($stats['average_rating'], 1); ?></h3>
                        <p>Average Rating</p>
                    </div>
                    <div class="hero-stat">
                        <h3><?php echo number_format($stats['courses_reviewed']); ?></h3>
                        <p>Courses Reviewed</p>
                    </div>
                    <div class="hero-stat">
                        <h3><?php echo number_format($stats['unique_reviewers']); ?></h3>
                        <p>Happy Students</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modern Review Form Section -->
    <?php if (isset($_SESSION['user_id']) && !empty($user_courses)): ?>
    <section class="review-form-section">
        <div class="container">
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-star"></i> Share Your Experience</h2>
                    <p>Help other students make informed decisions by sharing your honest review of the courses you've completed</p>
                </div>

                <?php if (!empty($form_success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($form_success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($form_error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($form_error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="review-form" id="reviewForm">
                    <input type="hidden" name="submit_review" value="1">
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="course_id" class="form-label">
                                <i class="fas fa-book"></i>
                                Select Course
                            </label>
                            <select name="course_id" id="course_id" class="form-select" required>
                                <option value="">Choose a course to review...</option>
                                <?php foreach ($user_courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>" 
                                            data-image="<?php echo htmlspecialchars($course['course_image'] ?? ''); ?>"
                                            <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-field">
                            <label class="form-label">
                                <i class="fas fa-stars"></i>
                                Your Rating
                            </label>
                            <div class="rating-input-container">
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
                                <div class="rating-text" id="ratingText">Click to rate</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-field full-width">
                        <label for="comment" class="form-label">
                            <i class="fas fa-comment-dots"></i>
                            Your Review
                        </label>
                        <textarea name="comment" id="comment" class="form-textarea" 
                                  placeholder="Share your detailed experience with this course. What did you like? What could be improved? How would you recommend it to other students?"
                                  required><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i>
                            Submit Review
                        </button>
                        <button type="reset" class="btn-reset">
                            <i class="fas fa-undo"></i>
                            Reset Form
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Call to Action for Non-Logged-In Users -->
    <?php if (!isset($_SESSION['user_id'])): ?>
    <section class="review-form-section">
        <div class="container">
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-user-plus"></i> Join TeachVerse Community</h2>
                    <p>Sign up today to share your course experiences and help other students make informed decisions</p>
                </div>
                
                <div class="cta-content" style="text-align: center;">
                    <div class="cta-features" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin: 2rem 0;">
                        <div class="cta-feature">
                            <i class="fas fa-star" style="font-size: 2rem; color: #fbbf24; margin-bottom: 1rem;"></i>
                            <h4>Rate & Review Courses</h4>
                            <p>Share your honest feedback about courses you've completed</p>
                        </div>
                        <div class="cta-feature">
                            <i class="fas fa-users" style="font-size: 2rem; color: #667eea; margin-bottom: 1rem;"></i>
                            <h4>Help Other Students</h4>
                            <p>Your reviews help others make better learning decisions</p>
                        </div>
                        <div class="cta-feature">
                            <i class="fas fa-graduation-cap" style="font-size: 2rem; color: #10b981; margin-bottom: 1rem;"></i>
                            <h4>Access Premium Courses</h4>
                            <p>Enroll in courses and become part of our learning community</p>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="../../auth.php?mode=register&type=user" class="btn-submit" style="display: inline-block; text-decoration: none;">
                            <i class="fas fa-user-plus"></i>
                            Create Free Account
                        </a>
                        <a href="../../auth.php?mode=login&type=user" class="btn-reset" style="display: inline-block; text-decoration: none;">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <main class="main-content">
        <div class="container">
            <!-- Filter Section -->
            <div class="filter-section slide-up">
                <div class="filter-header">
                    <div class="filter-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h2>Filter & Search Reviews</h2>
                </div>
                
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="search">Search Reviews</label>
                        <input type="text" id="search" name="search" class="form-control"
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by course, student, or comment...">
                    </div>
                    
                    <div class="form-group">
                        <label for="course">Course</label>
                        <select id="course" name="course" class="form-control">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>" 
                                        <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="rating">Rating</label>
                        <select id="rating" name="rating" class="form-control">
                            <option value="">All Ratings</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?php echo $i; ?>" 
                                        <?php echo $rating_filter == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Debug Information (Remove in production) -->
            <?php /* Debug info commented out for production
            if (isset($debug_info)): ?>
                <div class="alert alert-info" style="margin-bottom: 20px;">
                    <strong>Debug Info:</strong> <?php echo $debug_info; ?>
                    <?php if (empty($reviews) && isset($total_reviews) && $total_reviews > 0): ?>
                        <br><small>There are reviews in the database but they're not being displayed. This might be due to JOIN issues or missing related data.</small>
                    <?php endif; ?>
                </div>
            <?php endif; */ ?>

            <!-- Reviews Container -->
            <div class="reviews-container">
                <!-- Reviews List -->
                <div class="reviews-list">
                    <?php if (empty($reviews)): ?>
                        <div class="empty-state fade-in">
                            <div class="empty-icon">
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <h3>No Reviews Found</h3>
                            <p>No reviews match your current filters. Try adjusting your search criteria or browse our courses to leave the first review!</p>
                            <?php if (isLoggedIn()): ?>
                                <a href="../../courses.php" class="btn btn-primary">
                                    <i class="fas fa-book"></i> Browse Courses
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="review-card fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                <div class="review-header">
                                    <div class="course-info">
                                        <img src="../../assets/images/courses/<?php echo htmlspecialchars($review['course_image'] ?: 'default-course.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($review['course_title']); ?>" 
                                             class="course-thumbnail"
                                             onerror="this.src='../../assets/images/courses/default-course.jpg'">
                                        <div class="course-details">
                                            <h3>
                                                <a href="../../course-details.php?id=<?php echo $review['course_id']; ?>">
                                                    <?php echo htmlspecialchars($review['course_title']); ?>
                                                </a>
                                            </h3>
                                            <?php if ($review['trainer_name']): ?>
                                                <div class="trainer-info">
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                    <span>by <?php echo htmlspecialchars($review['trainer_name']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="rating-display">
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star star <?php echo $i <= $review['rating'] ? '' : 'empty'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-number"><?php echo $review['rating']; ?>/5</span>
                                    </div>
                                </div>
                                
                                <div class="review-content">
                                    <div class="reviewer-info">
                                        <div class="reviewer-avatar">
                                            <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                                        </div>
                                        <div class="reviewer-details">
                                            <h4><?php echo htmlspecialchars($review['user_name']); ?></h4>
                                            <div class="review-date">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('M j, Y • g:i A', strtotime($review['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($review['comment'])): ?>
                                        <div class="review-comment">
                                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isLoggedIn() && (hasRole('admin') || $_SESSION['user_id'] == $review['user_id'])): ?>
                                    <div class="review-actions">
                                        <?php if ($_SESSION['user_id'] == $review['user_id']): ?>
                                            <a href="edit.php?id=<?php echo $review['review_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit Review
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasRole('admin') || $_SESSION['user_id'] == $review['user_id']): ?>
                                            <a href="delete.php?id=<?php echo $review['review_id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="reviews-sidebar slide-up">
                    <div class="sidebar-section">
                        <div class="overall-rating">
                            <div class="overall-number"><?php echo number_format($stats['average_rating'], 1); ?></div>
                            <div class="overall-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star star <?php echo $i <= round($stats['average_rating']) ? '' : 'empty'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="overall-text">Based on <?php echo number_format($stats['total_reviews']); ?> reviews</div>
                        </div>
                    </div>

                    <div class="sidebar-section">
                        <h3 class="sidebar-title">
                            <i class="fas fa-chart-bar"></i>
                            Rating Breakdown
                        </h3>
                        <div class="rating-breakdown">
                            <?php
                            $total_reviews = $stats['total_reviews'];
                            for ($i = 5; $i >= 1; $i--):
                                $count = 0;
                                foreach ($rating_distribution as $dist) {
                                    if ($dist['rating'] == $i) {
                                        $count = $dist['count'];
                                        break;
                                    }
                                }
                                $percentage = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
                            ?>
                                <div class="rating-row">
                                    <div class="rating-label"><?php echo $i; ?> stars</div>
                                    <div class="rating-bar">
                                        <div class="rating-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="rating-count"><?php echo $count; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="sidebar-section">
                        <h3 class="sidebar-title">
                            <i class="fas fa-plus-circle"></i>
                            Leave a Review
                        </h3>
                        <p style="color: #718096; font-size: 0.9rem; margin-bottom: 1rem;">
                            Share your experience with our courses and help other students make informed decisions.
                        </p>
                        <a href="../../courses.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-star"></i> Write a Review
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Smooth scroll animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Animate rating bars
            const ratingFills = document.querySelectorAll('.rating-fill');
            ratingFills.forEach((fill, index) => {
                setTimeout(() => {
                    fill.style.transform = 'scaleX(1)';
                }, index * 200);
            });

            // Add intersection observer for scroll animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all review cards
            document.querySelectorAll('.review-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });

        // Form submission with loading state
        document.querySelector('.filter-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
        });

        // Auto-submit form on filter change
        document.querySelectorAll('#course, #rating').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Search input debounced submission
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (e.target.value.length === 0 || e.target.value.length >= 3) {
                    // Auto-submit after 3 characters or when empty
                    e.target.form.submit();
                }
            }, 500);
        });

        // Smooth scroll to top when filters change
        if (window.location.search) {
            setTimeout(() => {
                document.querySelector('.filter-section').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }, 100);
        }

        // Review Form Functionality
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            // Rating Stars Functionality
            const starInputs = document.querySelectorAll('.star-input');
            const starLabels = document.querySelectorAll('.star-label');
            const ratingText = document.getElementById('ratingText');
            
            const ratingTexts = {
                1: 'Poor - Needs significant improvement',
                2: 'Fair - Below expectations',
                3: 'Good - Meets expectations',
                4: 'Very Good - Exceeds expectations',
                5: 'Excellent - Outstanding quality'
            };

            starInputs.forEach((input, index) => {
                input.addEventListener('change', function() {
                    const rating = parseInt(this.value);
                    ratingText.textContent = ratingTexts[rating];
                    ratingText.style.color = rating >= 4 ? '#10b981' : rating >= 3 ? '#f59e0b' : '#ef4444';
                    
                    // Update star colors
                    starLabels.forEach((label, labelIndex) => {
                        if (labelIndex < rating) {
                            label.style.color = '#fbbf24';
                        } else {
                            label.style.color = '#e2e8f0';
                        }
                    });
                });
            });

            // Form Validation
            reviewForm.addEventListener('submit', function(e) {
                const courseId = document.getElementById('course_id').value;
                const rating = document.querySelector('input[name="rating"]:checked');
                const comment = document.getElementById('comment').value.trim();

                if (!courseId) {
                    e.preventDefault();
                    alert('Please select a course to review.');
                    document.getElementById('course_id').focus();
                    return;
                }

                if (!rating) {
                    e.preventDefault();
                    alert('Please select a rating.');
                    document.querySelector('.rating-stars').scrollIntoView({ behavior: 'smooth' });
                    return;
                }

                if (!comment || comment.length < 10) {
                    e.preventDefault();
                    alert('Please provide a detailed review (at least 10 characters).');
                    document.getElementById('comment').focus();
                    return;
                }

                // Add loading state to submit button
                const submitBtn = reviewForm.querySelector('.btn-submit');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                submitBtn.disabled = true;
            });

            // Character counter for comment
            const commentTextarea = document.getElementById('comment');
            if (commentTextarea) {
                const charCountElement = document.createElement('div');
                charCountElement.className = 'char-count';
                charCountElement.style.cssText = 'text-align: right; font-size: 0.8rem; color: #718096; margin-top: 0.5rem;';
                commentTextarea.parentNode.appendChild(charCountElement);

                function updateCharCount() {
                    const count = commentTextarea.value.length;
                    charCountElement.textContent = `${count} characters`;
                    if (count < 10) {
                        charCountElement.style.color = '#ef4444';
                    } else if (count > 500) {
                        charCountElement.style.color = '#f59e0b';
                    } else {
                        charCountElement.style.color = '#10b981';
                    }
                }

                commentTextarea.addEventListener('input', updateCharCount);
                updateCharCount();
            }

            // Enhanced Course Selection
            const courseSelect = document.getElementById('course_id');
            if (courseSelect) {
                courseSelect.addEventListener('change', function() {
                    if (this.value) {
                        // Add visual feedback
                        this.style.borderColor = '#10b981';
                        this.style.background = 'linear-gradient(135deg, #f0fff4 0%, #dcfce7 100%)';
                    } else {
                        this.style.borderColor = '#e2e8f0';
                        this.style.background = '#f8fafc';
                    }
                });
            }

            // Form Reset Enhancement
            const resetBtn = reviewForm.querySelector('.btn-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    setTimeout(() => {
                        starLabels.forEach(label => {
                            label.style.color = '#e2e8f0';
                        });
                        ratingText.textContent = 'Click to rate';
                        ratingText.style.color = '#4a5568';
                        
                        if (courseSelect) {
                            courseSelect.style.borderColor = '#e2e8f0';
                            courseSelect.style.background = '#f8fafc';
                        }
                    }, 100);
                });
            }
        }

        // Auto-hide success messages
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 300);
            }, 5000);
        }
    </script>
</body>
</html>
