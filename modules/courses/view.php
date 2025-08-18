<?php
require_once '../../config/database.php';
requireLogin();

$pdo = getDBConnection();

// Get course ID from URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    $_SESSION['error_message'] = 'Invalid course ID.';
    header('Location: index.php');
    exit;
}

// Fetch course data with creator information
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as creator_name, u.email as creator_email
        FROM courses c 
        JOIN users u ON c.created_by = u.user_id 
        WHERE c.course_id = ?
    ");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        $_SESSION['error_message'] = 'Course not found.';
        header('Location: index.php');
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Get enrollment statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as enrollment_count FROM enrollments WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $enrollment_count = $stmt->fetch()['enrollment_count'];
    
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $review_stats = $stmt->fetch();
    
} catch (PDOException $e) {
    $enrollment_count = 0;
    $review_stats = ['avg_rating' => 0, 'review_count' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Course - <?php echo htmlspecialchars($course['title']); ?></title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .course-header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .course-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .course-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #718096;
        }

        .course-image {
            width: 100%;
            max-width: 400px;
            height: 250px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .course-description {
            font-size: 1.1rem;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .course-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .course-details {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .detail-section {
            margin-bottom: 2rem;
        }

        .detail-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-content {
            color: #4a5568;
            line-height: 1.6;
        }

        .creator-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f7fafc;
            border-radius: 12px;
            margin-top: 1rem;
        }

        .creator-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        @media (max-width: 768px) {
            .course-meta {
                flex-direction: column;
                gap: 1rem;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="course-header">
            <h1 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h1>
            
            <div class="course-meta">
                <div class="meta-item">
                    <i class="fas fa-user"></i>
                    <span>Created by <?php echo htmlspecialchars($course['creator_name']); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo date('M j, Y', strtotime($course['created_at'])); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-dollar-sign"></i>
                    <span>$<?php echo number_format($course['price'], 2); ?></span>
                </div>
            </div>

            <?php if ($course['image']): ?>
                <img src="../../assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                     alt="<?php echo htmlspecialchars($course['title']); ?>" 
                     class="course-image"
                     onerror="this.src='../../assets/images/courses/default-course.jpg'">
            <?php endif; ?>

            <div class="course-description">
                <?php echo nl2br(htmlspecialchars($course['description'])); ?>
            </div>
        </div>

        <div class="course-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $enrollment_count; ?></div>
                <div class="stat-label">Enrolled Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($review_stats['avg_rating'], 1); ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $review_stats['review_count']; ?></div>
                <div class="stat-label">Total Reviews</div>
            </div>
        </div>

        <div class="course-details">
            <div class="detail-section">
                <h3 class="detail-title">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Course Creator
                </h3>
                <div class="creator-info">
                    <div class="creator-avatar">
                        <?php echo strtoupper(substr($course['creator_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($course['creator_name']); ?></div>
                        <div style="color: #718096; font-size: 0.9rem;"><?php echo htmlspecialchars($course['creator_email']); ?></div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h3 class="detail-title">
                    <i class="fas fa-info-circle"></i>
                    Course Information
                </h3>
                <div class="detail-content">
                    <p><strong>Course ID:</strong> <?php echo $course['course_id']; ?></p>
                    <p><strong>Created:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($course['created_at'])); ?></p>
                    <?php if ($course['updated_at']): ?>
                        <p><strong>Last Updated:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($course['updated_at'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
            
            <?php if (hasRole('admin') || $_SESSION['user_id'] == $course['created_by']): ?>
                <a href="edit.php?id=<?php echo $course['course_id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Course
                </a>
            <?php endif; ?>
            
            <?php if (hasRole('admin')): ?>
                <a href="delete.php?id=<?php echo $course['course_id']; ?>" class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this course? This action cannot be undone.')">
                    <i class="fas fa-trash"></i> Delete Course
                </a>
            <?php endif; ?>
            
            <a href="../../course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary">
                <i class="fas fa-eye"></i> Public View
            </a>
        </div>
    </div>
</body>
</html>
