<?php
require_once '../../config/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

$error = '';
$success = '';

// Handle form submission for adding new trainer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    $profile_title = trim($_POST['profile_title'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $certificates = trim($_POST['certificates'] ?? '');
    $specializations = trim($_POST['specializations'] ?? '');
    $hourly_rate = floatval($_POST['hourly_rate'] ?? 0);
    $availability = trim($_POST['availability'] ?? 'Available');
    
    // For admins, allow selecting user; for trainers, use their own ID
    $target_user_id = (hasRole('admin') && !empty($_POST['user_id'])) ? (int)$_POST['user_id'] : $user['user_id'];
    
    // Validation
    if (empty($profile_title)) {
        $error = 'Profile title is required.';
    } else {
        try {
            // Handle profile image upload
            $profile_image = 'default-trainer.jpg';
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/images/trainers/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions) && $_FILES['profile_image']['size'] <= 5000000) {
                    $profile_image = 'trainer_' . $target_user_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $profile_image;
                    
                    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        $profile_image = 'default-trainer.jpg';
                    }
                }
            }
            
            // Insert new trainer profile
            $sql = "INSERT INTO trainer_profiles (user_id, profile_title, bio, experience, certificates, profile_image, specializations, hourly_rate, availability, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$target_user_id, $profile_title, $bio, $experience, $certificates, $profile_image, $specializations, $hourly_rate, $availability]);
            
            if ($result) {
                $success = "Trainer profile created successfully!";
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Failed to create profile. Please try again.';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialization = isset($_GET['specialization']) ? trim($_GET['specialization']) : '';
$availability_filter = isset($_GET['availability']) ? trim($_GET['availability']) : '';

// Build WHERE conditions
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR tp.profile_title LIKE ? OR tp.bio LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($specialization) {
    $where_conditions[] = "tp.specializations LIKE ?";
    $params[] = "%$specialization%";
}

if ($availability_filter) {
    $where_conditions[] = "tp.availability = ?";
    $params[] = $availability_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get trainer profiles with user information
try {
    $query = "
        SELECT 
            tp.profile_id,
            tp.user_id,
            tp.profile_title,
            tp.bio,
            tp.experience,
            tp.certificates,
            tp.profile_image,
            tp.specializations,
            tp.hourly_rate,
            tp.availability,
            tp.created_at,
            tp.updated_at,
            u.name as user_name, 
            u.email as user_email, 
            u.created_at as user_joined,
            COALESCE(course_count.total_courses, 0) as total_courses,
            COALESCE(review_stats.avg_rating, 0) as avg_rating,
            COALESCE(review_stats.total_reviews, 0) as total_reviews
        FROM trainer_profiles tp
        INNER JOIN users u ON tp.user_id = u.user_id
        LEFT JOIN (
            SELECT created_by, COUNT(*) as total_courses 
            FROM courses 
            GROUP BY created_by
        ) course_count ON tp.user_id = course_count.created_by
        LEFT JOIN (
            SELECT c.created_by, AVG(r.rating) as avg_rating, COUNT(r.review_id) as total_reviews
            FROM reviews r
            JOIN courses c ON r.course_id = c.course_id
            GROUP BY c.created_by
        ) review_stats ON tp.user_id = review_stats.created_by
        $where_clause
        ORDER BY tp.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $trainers = [];
    $db_error = "Error fetching trainers: " . $e->getMessage();
}

// Get statistics
try {
    $stats_query = "
        SELECT 
            COUNT(DISTINCT tp.profile_id) as total_profiles,
            COUNT(DISTINCT tp.user_id) as unique_trainers,
            COUNT(CASE WHEN tp.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_profiles,
            COALESCE(AVG(tp.hourly_rate), 0) as avg_hourly_rate
        FROM trainer_profiles tp
        INNER JOIN users u ON tp.user_id = u.user_id
    ";
    $stats_stmt = $pdo->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $stats = ['total_profiles' => 0, 'unique_trainers' => 0, 'new_profiles' => 0, 'avg_hourly_rate' => 0];
}

// Get users for admin dropdown
$users = [];
if (hasRole('admin')) {
    try {
        $stmt = $pdo->query("SELECT user_id, name, email, role FROM users WHERE role IN ('trainer', 'admin') ORDER BY name");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        // Continue without users
    }
}

// Get distinct specializations for filter
$specializations = [];
try {
    $spec_query = "SELECT DISTINCT specializations FROM trainer_profiles WHERE specializations IS NOT NULL AND specializations != ''";
    $spec_stmt = $pdo->query($spec_query);
    $all_specializations = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach($all_specializations as $spec_list) {
        if ($spec_list) {
            $specs = explode(',', $spec_list);
            foreach($specs as $spec) {
                $spec = trim($spec);
                if($spec && !in_array($spec, $specializations)) {
                    $specializations[] = $spec;
                }
            }
        }
    }
    sort($specializations);
} catch(PDOException $e) {
    $specializations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Profiles Management | TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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
            background: #f7fafc;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header Styles */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .header-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .header-content h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header-content p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Statistics Dashboard */
        .stats-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .stat-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            color: #718096;
            font-weight: 500;
        }

        /* Main Content Layout */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }

        .content-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .section-header {
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-bottom: 1px solid #e2e8f0;
        }

        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .section-header p {
            color: #718096;
        }

        /* Add Trainer Form */
        .add-trainer-form {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            position: sticky;
            top: 2rem;
        }

        .form-header {
            padding: 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-align: center;
        }

        .form-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-body {
            padding: 2rem;
            max-height: 600px;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }

        .file-upload {
            position: relative;
            display: block;
        }

        .file-upload input[type=file] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: block;
            padding: 1rem;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            text-align: center;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload:hover .file-upload-label {
            border-color: #667eea;
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .form-actions {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
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
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        /* Filters Section */
        .filters-section {
            padding: 2rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        /* Trainers Grid */
        .trainers-content {
            padding: 2rem;
        }

        .trainers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .trainer-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .trainer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .trainer-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border-color: #667eea;
        }

        .trainer-card:hover::before {
            transform: scaleX(1);
        }

        .trainer-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .trainer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .trainer-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .trainer-title {
            color: #667eea;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .trainer-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #718096;
        }

        .meta-item i {
            color: #667eea;
            width: 14px;
        }

        .trainer-bio {
            color: #4a5568;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 1rem 0;
            max-height: 3.6em;
            overflow: hidden;
        }

        .trainer-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .availability-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .availability-available {
            background: #c6f6d5;
            color: #22543d;
        }

        .availability-busy {
            background: #fef5e7;
            color: #744210;
        }

        .availability-unavailable {
            background: #fed7d7;
            color: #742a2a;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #cbd5e0;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
            
            .add-trainer-form {
                position: relative;
                top: 0;
                order: -1;
            }
            
            .form-body {
                max-height: none;
            }
        }

        @media (max-width: 768px) {
            .header-content h1 {
                font-size: 2rem;
            }
            
            .stats-dashboard {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .stat-content {
                flex-direction: column;
                text-align: center;
            }
            
            .trainers-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .trainer-actions {
                flex-direction: column;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Helper Text */
        .form-help {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 0.25rem;
        }

        /* Toggle Button for Mobile Form */
        .mobile-form-toggle {
            display: none;
            width: 100%;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .mobile-form-toggle {
                display: block;
            }
            
            .add-trainer-form {
                display: none;
            }
            
            .add-trainer-form.active {
                display: block;
            }
        }

        /* Loading State */
        .btn-loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .btn-loading::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
                <li><a href="index.php" class="active">Trainers</a></li>
                <li><a href="../reviews/">Reviews</a></li>
                <li><a href="../../about.php">About</a></li>
                <li><a href="../../contact.php">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="../../dashboard.php">Dashboard</a></li>
                    <?php if (hasRole('admin')): ?>
                        <li><a href="../../admin/">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="../../logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="header-content">
                <h1><i class="fas fa-chalkboard-teacher"></i> Trainer Profiles</h1>
                <p>Discover and manage our talented educators and industry experts</p>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="container">
            <!-- Statistics Dashboard -->
            <div class="stats-dashboard">
                <div class="stat-card fade-in">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_profiles']); ?></h3>
                            <p>Total Profiles</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['unique_trainers']); ?></h3>
                            <p>Active Trainers</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['new_profiles']); ?></h3>
                            <p>New This Month</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$<?php echo number_format($stats['avg_hourly_rate'], 0); ?></h3>
                            <p>Avg. Hourly Rate</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Form Toggle -->
            <?php if (hasRole('admin') || hasRole('trainer')): ?>
                <button class="btn btn-primary mobile-form-toggle" onclick="toggleMobileForm()">
                    <i class="fas fa-plus"></i> Add New Trainer Profile
                </button>
            <?php endif; ?>

            <!-- Main Layout -->
            <div class="main-layout">
                <!-- Trainers List Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2><i class="fas fa-list"></i> Trainer Profiles</h2>
                        <p>Browse and manage trainer profiles in your organization</p>
                    </div>

                    <!-- Filters Section -->
                    <div class="filters-section">
                        <form method="GET" class="filters-form">
                            <div class="form-group">
                                <label for="search">Search</label>
                                <input type="text" name="search" id="search" class="form-control" 
                                       placeholder="Search trainers..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="specialization">Specialization</label>
                                <select name="specialization" id="specialization" class="form-control">
                                    <option value="">All Specializations</option>
                                    <?php foreach ($specializations as $spec): ?>
                                        <option value="<?php echo htmlspecialchars($spec); ?>" 
                                                <?php echo $specialization == $spec ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($spec); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="availability">Availability</label>
                                <select name="availability" id="availability" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Available" <?php echo $availability_filter == 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="Busy" <?php echo $availability_filter == 'Busy' ? 'selected' : ''; ?>>Busy</option>
                                    <option value="Unavailable" <?php echo $availability_filter == 'Unavailable' ? 'selected' : ''; ?>>Unavailable</option>
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

                    <!-- Success/Error Messages -->
                    <div class="trainers-content">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Trainers Grid -->
                        <?php if (empty($trainers)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <h3>No Trainers Found</h3>
                                <p>No trainer profiles match your current filters. Try adjusting your search criteria.</p>
                            </div>
                        <?php else: ?>
                            <div class="trainers-grid">
                                <?php foreach ($trainers as $trainer): ?>
                                    <div class="trainer-card fade-in">
                                        <div class="trainer-header">
                                            <img src="../../assets/images/trainers/<?php echo htmlspecialchars($trainer['profile_image'] ?: 'default-trainer.jpg'); ?>" 
                                                 alt="<?php echo htmlspecialchars($trainer['user_name']); ?>" 
                                                 class="trainer-avatar"
                                                 onerror="this.src='../../assets/images/trainers/default-trainer.jpg'">
                                            <div class="trainer-info">
                                                <h4><?php echo htmlspecialchars($trainer['user_name']); ?></h4>
                                                <div class="trainer-title"><?php echo htmlspecialchars($trainer['profile_title']); ?></div>
                                            </div>
                                        </div>

                                        <div class="trainer-meta">
                                            <div class="meta-item">
                                                <i class="fas fa-book"></i>
                                                <span><?php echo $trainer['total_courses']; ?> courses</span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo number_format($trainer['avg_rating'], 1); ?> rating</span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-dollar-sign"></i>
                                                <span>$<?php echo number_format($trainer['hourly_rate'], 0); ?>/hr</span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="availability-badge availability-<?php echo strtolower($trainer['availability']); ?>">
                                                    <?php echo $trainer['availability']; ?>
                                                </span>
                                            </div>
                                        </div>

                                        <?php if ($trainer['bio']): ?>
                                            <div class="trainer-bio">
                                                <?php echo htmlspecialchars(substr($trainer['bio'], 0, 120)) . '...'; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="trainer-actions">
                                            <a href="view.php?id=<?php echo $trainer['profile_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            
                                            <?php if (hasRole('admin') || $user['user_id'] == $trainer['user_id']): ?>
                                                <a href="edit.php?id=<?php echo $trainer['profile_id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (hasRole('admin')): ?>
                                                <a href="delete.php?id=<?php echo $trainer['profile_id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this trainer profile?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Trainer Form -->
                <?php if (hasRole('admin') || hasRole('trainer')): ?>
                    <div class="add-trainer-form" id="addTrainerForm">
                        <div class="form-header">
                            <h3><i class="fas fa-plus-circle"></i> Add New Trainer</h3>
                            <p>Create a professional trainer profile</p>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="trainer-form" id="trainerForm">
                            <div class="form-body">
                                <!-- Admin User Selection -->
                                <?php if (hasRole('admin') && !empty($users)): ?>
                                <div class="form-group">
                                    <label for="user_id"><i class="fas fa-user"></i> Select User</label>
                                    <select name="user_id" id="user_id" class="form-control">
                                        <option value="">Choose a user...</option>
                                        <?php foreach ($users as $user_option): ?>
                                            <option value="<?php echo $user_option['user_id']; ?>">
                                                <?php echo htmlspecialchars($user_option['name'] . ' (' . $user_option['email'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-help">Select which user this profile belongs to</div>
                                </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="profile_title"><i class="fas fa-star"></i> Profile Title *</label>
                                    <input type="text" id="profile_title" name="profile_title" class="form-control" 
                                           placeholder="e.g., Web Development Expert" required>
                                    <div class="form-help">Your area of expertise</div>
                                </div>

                                <div class="form-group">
                                    <label for="hourly_rate"><i class="fas fa-dollar-sign"></i> Hourly Rate (USD)</label>
                                    <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" 
                                           min="0" step="0.01" placeholder="50.00" value="0">
                                    <div class="form-help">Your teaching rate per hour</div>
                                </div>

                                <div class="form-group">
                                    <label for="specializations"><i class="fas fa-tags"></i> Specializations</label>
                                    <input type="text" id="specializations" name="specializations" class="form-control" 
                                           placeholder="JavaScript, React, Node.js">
                                    <div class="form-help">Comma-separated list of skills</div>
                                </div>

                                <div class="form-group">
                                    <label for="availability_form"><i class="fas fa-clock"></i> Availability</label>
                                    <select name="availability" id="availability_form" class="form-control">
                                        <option value="Available">Available</option>
                                        <option value="Busy">Busy</option>
                                        <option value="Unavailable">Unavailable</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="profile_image"><i class="fas fa-image"></i> Profile Image</label>
                                    <div class="file-upload">
                                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                                        <label for="profile_image" class="file-upload-label">
                                            <i class="fas fa-cloud-upload-alt"></i><br>
                                            Click to upload image<br>
                                            <small>JPG, PNG, GIF (Max 5MB)</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="bio"><i class="fas fa-user"></i> About You</label>
                                    <textarea id="bio" name="bio" class="form-control" rows="3" 
                                              placeholder="Tell us about yourself and your teaching philosophy..."></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="experience"><i class="fas fa-briefcase"></i> Experience</label>
                                    <textarea id="experience" name="experience" class="form-control" rows="3" 
                                              placeholder="Describe your professional experience..."></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="certificates"><i class="fas fa-certificate"></i> Certifications</label>
                                    <textarea id="certificates" name="certificates" class="form-control" rows="3" 
                                              placeholder="List your certifications and achievements..."></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="add_trainer" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Create Profile
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Mobile form toggle
        function toggleMobileForm() {
            const form = document.getElementById('addTrainerForm');
            form.classList.toggle('active');
        }

        // File upload preview
        document.getElementById('profile_image')?.addEventListener('change', function(e) {
            const label = document.querySelector('.file-upload-label');
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                label.innerHTML = `<i class="fas fa-check"></i><br>Selected: ${fileName}`;
                label.style.borderColor = '#48bb78';
                label.style.color = '#48bb78';
            }
        });

        // Form submission with loading state
        document.getElementById('trainerForm')?.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const profileTitle = document.getElementById('profile_title').value.trim();
            
            if (!profileTitle) {
                e.preventDefault();
                alert('Profile title is required!');
                document.getElementById('profile_title').focus();
                return false;
            }
            
            // Show loading state
            submitBtn.classList.add('btn-loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            submitBtn.disabled = true;
        });

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });

        // Smooth scroll to form after submit (if errors)
        <?php if ($error): ?>
            setTimeout(() => {
                document.getElementById('addTrainerForm')?.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }, 100);
        <?php endif; ?>

        // Add fade-in animation to cards on scroll
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

        document.querySelectorAll('.trainer-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // Real-time search (optional enhancement)
        let searchTimeout;
        document.getElementById('search')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (e.target.value.length === 0 || e.target.value.length >= 3) {
                    // Could implement AJAX search here
                    console.log('Search for:', e.target.value);
                }
            }, 300);
        });
    </script>
</body>
</html>
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem; color: var(--primary-color);">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem; color: var(--primary-color);"><?php echo $stats['total_trainers']; ?></h3>
                            <p style="margin: 0; color: var(--text-secondary);">Total Profiles</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem; color: var(--accent-color);">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem; color: var(--accent-color);"><?php echo $stats['unique_trainers']; ?></h3>
                            <p style="margin: 0; color: var(--text-secondary);">Unique Trainers</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem; color: var(--success-color);">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem; color: var(--success-color);"><?php echo $stats['new_profiles']; ?></h3>
                            <p style="margin: 0; color: var(--text-secondary);">New This Month</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User's Own Profiles Section -->
            <?php if ((hasRole('trainer') || hasRole('admin')) && !empty($user_profiles)): ?>
                <div class="card mb-4">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="margin: 0; color: var(--primary-color);">
                            <i class="fas fa-user-circle"></i> My Profiles (<?php echo count($user_profiles); ?>)
                        </h2>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create New Profile
                        </a>
                    </div>
                    
                    <div class="grid grid-2 gap-4">
                        <?php foreach ($user_profiles as $profile): ?>
                            <div class="card-secondary" style="border-left: 4px solid var(--primary-color);">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <img src="../../assets/images/trainers/<?php echo htmlspecialchars($profile['profile_image']); ?>" 
                                         alt="Profile Image"
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%;"
                                         onerror="this.src='../../assets/images/trainers/default-trainer.jpg'">
                                    <div>
                                        <h4 style="margin: 0; color: var(--primary-color);">
                                            <?php echo htmlspecialchars($profile['profile_title'] ?? 'My Profile'); ?>
                                        </h4>
                                        <p style="margin: 0.25rem 0; color: var(--text-secondary); font-size: 0.875rem;">
                                            Created: <?php echo date('M j, Y', strtotime($profile['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars(substr($profile['bio'], 0, 150)) . (strlen($profile['bio']) > 150 ? '...' : ''); ?>
                                </p>
                                
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="view.php?id=<?php echo $profile['profile_id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit.php?id=<?php echo $profile['profile_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif (hasRole('trainer') || hasRole('admin')): ?>
                <div class="card mb-4" style="text-align: center; padding: 2rem; background: linear-gradient(135deg, #f8fafc 0%, #e5e7eb 100%);">
                    <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Create Your First Trainer Profile</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                        Start building your professional presence by creating your trainer profile. You can create multiple profiles for different specializations.
                    </p>
                    <a href="create.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Create My First Profile
                    </a>
                </div>
            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions and Search -->
            <div class="card mb-4">
                <div style="display: flex; justify-content: between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <div style="flex: 1;">
                        <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                            <div class="search-container" style="flex: 1; max-width: 400px;">
                                <input type="text" 
                                       name="search" 
                                       placeholder="Search trainers..." 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       class="form-input">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <?php if (!empty($search)): ?>
                                <a href="index.php" class="btn btn-secondary">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div>
                        <?php if (hasRole('admin')): ?>
                            <!-- Admin can create profiles for any user -->
                            <a href="create_new.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create New Trainer
                            </a>
                            <div style="margin-top: 10px; display: flex; gap: 10px;">
                                <a href="../users/create.php" class="btn btn-sm btn-outline">
                                    <i class="fas fa-user-plus"></i> Add User
                                </a>
                                <a href="../../add_sample_users.php" class="btn btn-sm btn-outline" 
                                   onclick="return confirm('This will add 5 sample users for testing. Continue?')">
                                    <i class="fas fa-users"></i> Add Sample Users
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (hasRole('trainer')): ?>
                            <!-- Trainers can manage their own profile -->
                            <a href="create.php" class="btn btn-secondary">
                                <i class="fas fa-user-edit"></i> Manage My Profile
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Trainers Grid -->
            <div class="trainers-grid">
                <?php if (!empty($trainers)): ?>
                    <?php foreach ($trainers as $trainer): ?>
                        <div class="trainer-card">
                            <div class="trainer-image">
                                <img src="../../assets/images/trainers/<?php echo htmlspecialchars($trainer['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($trainer['name']); ?>"
                                     onerror="this.src='../../assets/images/default-trainer.jpg'">
                            </div>
                            
                            <div class="trainer-info">
                                <h3><?php echo htmlspecialchars($trainer['name']); ?></h3>
                                <?php if (!empty($trainer['profile_title'])): ?>
                                    <p class="profile-title"><?php echo htmlspecialchars($trainer['profile_title']); ?></p>
                                <?php endif; ?>
                                <p class="trainer-email"><?php echo htmlspecialchars($trainer['email']); ?></p>
                                
                                <?php if (!empty($trainer['bio'])): ?>
                                    <p class="trainer-bio">
                                        <?php echo htmlspecialchars(substr($trainer['bio'], 0, 120)); ?>
                                        <?php echo strlen($trainer['bio']) > 120 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($trainer['experience'])): ?>
                                    <div class="trainer-experience">
                                        <strong>Experience:</strong>
                                        <p><?php echo htmlspecialchars(substr($trainer['experience'], 0, 100)); ?>
                                        <?php echo strlen($trainer['experience']) > 100 ? '...' : ''; ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="trainer-meta">
                                    <span class="join-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        Joined <?php echo date('M Y', strtotime($trainer['user_created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="trainer-actions">
                                <a href="view.php?id=<?php echo $trainer['profile_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Profile
                                </a>
                                
                                <?php if ($trainer['user_id'] == $_SESSION['user_id'] || hasRole('admin')): ?>
                                    <a href="edit.php?id=<?php echo $trainer['profile_id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <button onclick="deleteTrainerProfile(<?php echo $trainer['profile_id']; ?>)" 
                                            class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h3>No Trainers Found</h3>
                        <p>
                            <?php if (!empty($search)): ?>
                                No trainers match your search criteria.
                            <?php else: ?>
                                No trainer profiles have been created yet.
                            <?php endif; ?>
                        </p>
                        <?php if (hasRole('trainer') || hasRole('admin')): ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create First Profile
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
    function deleteTrainerProfile(profileId) {
        if (confirm('Are you sure you want to delete this trainer profile? This action cannot be undone.')) {
            window.location.href = 'delete.php?id=' + profileId;
        }
    }
    </script>

    <style>
    .trainers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .trainer-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .trainer-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .trainer-image {
        height: 200px;
        overflow: hidden;
        background: var(--gradient);
        position: relative;
    }

    .trainer-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .trainer-card:hover .trainer-image img {
        transform: scale(1.05);
    }

    .trainer-info {
        padding: 1.5rem;
    }

    .trainer-info h3 {
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
        font-size: 1.25rem;
    }

    .trainer-email {
        color: var(--text-secondary);
        margin: 0 0 1rem 0;
        font-size: 0.9rem;
    }

    .profile-title {
        color: var(--accent-color);
        margin: 0.25rem 0 0.75rem 0;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .trainer-bio {
        color: var(--text-secondary);
        margin: 1rem 0;
        line-height: 1.5;
    }

    .trainer-experience {
        margin: 1rem 0;
    }

    .trainer-experience strong {
        color: var(--text-primary);
        display: block;
        margin-bottom: 0.5rem;
    }

    .trainer-experience p {
        color: var(--text-secondary);
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .trainer-meta {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    .join-date {
        color: var(--text-secondary);
        font-size: 0.85rem;
    }

    .join-date i {
        margin-right: 0.5rem;
    }

    .trainer-actions {
        padding: 1rem 1.5rem;
        background: var(--background-light);
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-secondary);
        grid-column: 1 / -1;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state h3 {
        margin: 1rem 0;
        color: var(--text-primary);
    }

    .btn-sm {
        padding: 8px 16px;
        font-size: 0.875rem;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
    }

    .btn-outline {
        background: transparent;
        border: 1.5px solid var(--border-color);
        color: var(--text-secondary);
    }

    .btn-outline:hover {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .trainers-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .trainer-actions {
            flex-direction: column;
        }
        
        .trainer-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
</body>
</html>
