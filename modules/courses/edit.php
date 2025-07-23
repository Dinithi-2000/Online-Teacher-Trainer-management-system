<?php
require_once '../../config/database.php';
requireLogin();

$course_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

$pdo = getDBConnection();

// Get course data
$stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: index.php');
    exit();
}

// Check if user can edit this course (admin or course creator)
if (!hasRole('admin') && $course['created_by'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $duration = sanitize($_POST['duration']);
    $price = floatval($_POST['price']);
    $image = $course['image']; // Keep existing image by default
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/courses/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $new_image = uniqid() . '.' . $file_extension;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_image)) {
                // Delete old image if it's not the default
                if ($course['image'] !== 'default-course.jpg' && file_exists($upload_dir . $course['image'])) {
                    unlink($upload_dir . $course['image']);
                }
                $image = $new_image;
            } else {
                $error = 'Failed to upload image';
            }
        } else {
            $error = 'Invalid image format. Please use JPG, PNG, GIF, or WebP.';
        }
    }
    
    if (empty($title) || empty($description) || empty($duration) || $price < 0) {
        $error = 'Please fill in all required fields';
    } elseif (empty($error)) {
        $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, image = ?, duration = ?, price = ?, updated_at = CURRENT_TIMESTAMP WHERE course_id = ?");
        
        if ($stmt->execute([$title, $description, $image, $duration, $price, $course_id])) {
            $success = 'Course updated successfully!';
            // Refresh course data
            $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to update course. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - TeachVerse</title>
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
                <li><a href="index.php">Manage Courses</a></li>
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
                <i class="fas fa-edit"></i> Edit Course
            </h1>
            <p class="page-subtitle">Update course information</p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div style="max-width: 800px; margin: 0 auto;">
                <div class="card">
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" data-validate>
                        <div class="form-group">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading"></i> Course Title *
                            </label>
                            <input type="text" id="title" name="title" class="form-input" 
                                   value="<?php echo htmlspecialchars($course['title']); ?>" 
                                   placeholder="Enter course title"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left"></i> Course Description *
                            </label>
                            <textarea id="description" name="description" class="form-textarea" 
                                      placeholder="Describe what students will learn in this course"
                                      required><?php echo htmlspecialchars($course['description']); ?></textarea>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="duration" class="form-label">
                                    <i class="fas fa-clock"></i> Duration *
                                </label>
                                <input type="text" id="duration" name="duration" class="form-input" 
                                       value="<?php echo htmlspecialchars($course['duration']); ?>"
                                       placeholder="e.g., 8 weeks, 40 hours"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="price" class="form-label">
                                    <i class="fas fa-dollar-sign"></i> Price (USD) *
                                </label>
                                <input type="number" id="price" name="price" class="form-input" 
                                       value="<?php echo htmlspecialchars($course['price']); ?>"
                                       min="0" step="0.01"
                                       placeholder="0.00"
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="image" class="form-label">
                                <i class="fas fa-image"></i> Course Image
                            </label>
                            
                            <!-- Current Image Preview -->
                            <div style="margin-bottom: 1rem;">
                                <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Current Image:</p>
                                <img src="../../assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                                     alt="Current course image"
                                     style="max-width: 200px; height: 100px; object-fit: cover; border-radius: var(--border-radius); border: 2px solid var(--border-color);"
                                     onerror="this.src='../../assets/images/default-course.jpg'">
                            </div>
                            
                            <input type="file" id="image" name="image" class="form-input" 
                                   accept="image/*">
                            <small style="color: var(--text-secondary); font-size: 0.75rem;">
                                Leave empty to keep current image. Recommended size: 800x400px. Formats: JPG, PNG, GIF, WebP
                            </small>
                        </div>

                        <div class="form-group">
                            <div style="padding: 1rem; background: #f8fafc; border-radius: var(--border-radius); border-left: 4px solid var(--warning-color);">
                                <h4 style="margin-bottom: 0.5rem; color: var(--warning-color);">
                                    <i class="fas fa-exclamation-triangle"></i> Course Update Information
                                </h4>
                                <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary);">
                                    <li>Course ID: #<?php echo $course['course_id']; ?></li>
                                    <li>Created: <?php echo formatDate($course['created_at']); ?></li>
                                    <li>Last Updated: <?php echo formatDate($course['updated_at']); ?></li>
                                    <li>Changes will be visible to all students immediately</li>
                                </ul>
                            </div>
                        </div>

                        <div class="grid grid-3 gap-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Course
                            </button>
                            <a href="../../course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> View Course
                            </a>
                            <a href="index.php" class="btn btn-warning">
                                <i class="fas fa-arrow-left"></i> Back to Courses
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
