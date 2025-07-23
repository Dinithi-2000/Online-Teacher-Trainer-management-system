<?php
require_once '../../config/database.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $pdo = getDBConnection();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Email already exists';
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $hashed_password, $role])) {
                $success = 'User created successfully!';
                // Clear form data
                $name = $email = $role = '';
            } else {
                $error = 'Failed to create user. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - TeachVerse</title>
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
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <li><a href="index.php">Manage Users</a></li>
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
                <i class="fas fa-user-plus"></i> Create New User
            </h1>
            <p class="page-subtitle">Add a new user to the platform</p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="card">
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" data-validate>
                        <div class="form-group">
                            <label for="name" class="form-label">
                                <i class="fas fa-user"></i> Full Name *
                            </label>
                            <input type="text" id="name" name="name" class="form-input" 
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                                   placeholder="Enter full name"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email Address *
                            </label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                   placeholder="Enter email address"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag"></i> User Role *
                            </label>
                            <select id="role" name="role" class="form-select" required>
                                <option value="">Select user role</option>
                                <option value="student" <?php echo (isset($role) && $role === 'student') ? 'selected' : ''; ?>>
                                    Student - Can enroll in courses and track progress
                                </option>
                                <option value="trainer" <?php echo (isset($role) && $role === 'trainer') ? 'selected' : ''; ?>>
                                    Trainer - Can create and manage courses
                                </option>
                                <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>
                                    Admin - Full system access and management
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Password *
                            </label>
                            <input type="password" id="password" name="password" class="form-input" 
                                   minlength="6" 
                                   placeholder="Enter password (minimum 6 characters)"
                                   required>
                            <small style="color: var(--text-secondary); font-size: 0.75rem;">
                                Password must be at least 6 characters long
                            </small>
                        </div>

                        <div class="form-group">
                            <div style="padding: 1rem; background: #f8fafc; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                                <h4 style="margin-bottom: 0.5rem; color: var(--primary-color);">
                                    <i class="fas fa-info-circle"></i> User Role Permissions
                                </h4>
                                <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary); font-size: 0.875rem;">
                                    <li><strong>Student:</strong> Enroll in courses, track progress, submit reviews</li>
                                    <li><strong>Trainer:</strong> Create courses, manage student enrollments, view analytics</li>
                                    <li><strong>Admin:</strong> Full system access, user management, platform configuration</li>
                                </ul>
                            </div>
                        </div>

                        <div class="grid grid-2 gap-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Create User
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Users
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
