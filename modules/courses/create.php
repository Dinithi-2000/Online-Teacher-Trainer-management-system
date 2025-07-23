<?php
require_once '../../config/database.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $duration = sanitize($_POST['duration']);
    $price = floatval($_POST['price']);
    $image = 'default-course.jpg';
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/courses/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $image = uniqid() . '.' . $file_extension;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image)) {
                $error = 'Failed to upload image';
                $image = 'default-course.jpg';
            }
        } else {
            $error = 'Invalid image format. Please use JPG, PNG, GIF, or WebP.';
        }
    }
    
    if (empty($title) || empty($description) || empty($duration) || $price < 0) {
        $error = 'Please fill in all required fields';
    } elseif (empty($error)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO courses (title, description, image, duration, price, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$title, $description, $image, $duration, $price, $_SESSION['user_id']])) {
            $success = 'Course created successfully!';
            // Clear form data
            $title = $description = $duration = '';
            $price = 0;
        } else {
            $error = 'Failed to create course. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course - TeachVerse</title>
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
                <i class="fas fa-plus"></i> Create New Course
            </h1>
            <p class="page-subtitle">Add a new training course to the platform</p>
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
                                   value="<?php echo htmlspecialchars($title ?? ''); ?>" 
                                   placeholder="Enter course title"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left"></i> Course Description *
                            </label>
                            <textarea id="description" name="description" class="form-textarea" 
                                      placeholder="Describe what students will learn in this course"
                                      required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="duration" class="form-label">
                                    <i class="fas fa-clock"></i> Duration *
                                </label>
                                <input type="text" id="duration" name="duration" class="form-input" 
                                       value="<?php echo htmlspecialchars($duration ?? ''); ?>"
                                       placeholder="e.g., 8 weeks, 40 hours"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="price" class="form-label">
                                    <i class="fas fa-dollar-sign"></i> Price (USD) *
                                </label>
                                <input type="number" id="price" name="price" class="form-input" 
                                       value="<?php echo htmlspecialchars($price ?? ''); ?>"
                                       min="0" step="0.01"
                                       placeholder="0.00"
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="image" class="form-label">
                                <i class="fas fa-image"></i> Course Image
                            </label>
                            <input type="file" id="image" name="image" class="form-input" 
                                   accept="image/*">
                            <small style="color: var(--text-secondary); font-size: 0.75rem;">
                                Recommended size: 800x400px. Formats: JPG, PNG, GIF, WebP
                            </small>
                        </div>

                        <div class="form-group">
                            <div style="padding: 1rem; background: #f8fafc; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                                <h4 style="margin-bottom: 0.5rem; color: var(--primary-color);">
                                    <i class="fas fa-info-circle"></i> Course Creation Tips
                                </h4>
                                <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary);">
                                    <li>Write a clear, descriptive title that highlights the main benefit</li>
                                    <li>Include key learning outcomes in the description</li>
                                    <li>Set a realistic duration based on content complexity</li>
                                    <li>Price competitively based on course value and market standards</li>
                                    <li>Use an engaging, high-quality course image</li>
                                </ul>
                            </div>
                        </div>

                        <div class="grid grid-2 gap-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Course
                            </button>
                            <a href="index.php" class="btn btn-secondary">
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
