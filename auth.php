<?php
require_once 'config/database.php';

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // login or register
$userType = $_GET['type'] ?? 'user'; // user or admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userType = $_POST['user_type'] ?? 'user';
    
    if ($action === 'login') {
        // Login Logic
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields';
        } else {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if user type matches
                if ($userType === 'admin' && $user['role'] !== 'admin') {
                    $error = 'Access denied. Admin credentials required.';
                } elseif ($userType === 'user' && $user['role'] === 'admin') {
                    $error = 'Please use the admin login portal.';
                } else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: admin/index.php');
                    } else {
                        header('Location: user/index.php');
                    }
                    exit();
                }
            } else {
                $error = 'Invalid email or password';
            }
        }
    } elseif ($action === 'register') {
        // Registration Logic
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $userType === 'admin' ? 'admin' : sanitize($_POST['role']);
        
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields';
        } elseif ($userType === 'user' && empty($role)) {
            $error = 'Please select a role';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
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
                // Admin registration requires special validation
                if ($userType === 'admin') {
                    $admin_key = $_POST['admin_key'] ?? '';
                    if ($admin_key !== 'TEACHVERSE_ADMIN_2024') {
                        $error = 'Invalid admin registration key';
                    } else {
                        $role = 'admin';
                    }
                }
                
                if (empty($error)) {
                    // Create new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                    
                    if ($stmt->execute([$name, $email, $hashed_password, $role])) {
                        $success = 'Account created successfully! You can now log in.';
                        
                        // Auto login
                        $user_id = $pdo->lastInsertId();
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $role;
                        
                        // Redirect based on role
                        if ($role === 'admin') {
                            header("refresh:2;url=admin/index.php");
                        } else {
                            header("refresh:2;url=user/index.php");
                        }
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
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
    <title><?php echo ucfirst($mode); ?> - TeachVerse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Authentication Page Styles */
        .auth-body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }
        
        .auth-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }
        
        .auth-sidebar {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .auth-sidebar::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="30" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="30" cy="80" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="70" cy="70" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: float 20s linear infinite;
            opacity: 0.3;
        }
        
        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .auth-logo {
            font-size: 48px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .auth-brand {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }
        
        .auth-tagline {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .auth-features {
            list-style: none;
            padding: 0;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .auth-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .auth-features i {
            width: 20px;
            text-align: center;
        }
        
        .auth-main {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }
        
        .auth-subtitle {
            color: #718096;
            font-size: 16px;
        }
        
        .user-type-selector {
            display: flex;
            background: #f7fafc;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 30px;
            gap: 4px;
        }
        
        .user-type-option {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #718096;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .user-type-option.active {
            background: white;
            color: #4299e1;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .auth-mode-toggle {
            display: flex;
            background: #edf2f7;
            border-radius: 8px;
            margin-bottom: 24px;
            padding: 2px;
        }
        
        .mode-option {
            flex: 1;
            padding: 8px 16px;
            text-align: center;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #718096;
            font-weight: 500;
            font-size: 14px;
        }
        
        .mode-option.active {
            background: white;
            color: #4299e1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-weight: 500;
            color: #2d3748;
            font-size: 14px;
        }
        
        .form-input {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s ease;
            background: #fafafa;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4299e1;
            background: white;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        
        .form-select {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            background: #fafafa;
            cursor: pointer;
        }
        
        .auth-button {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .auth-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.4);
        }
        
        .auth-button:active {
            transform: translateY(0);
        }
        
        .admin-key-notice {
            background: #fef5e7;
            border: 1px solid #f6e05e;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            color: #744210;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fed7d7;
            border: 1px solid #fc8181;
            color: #c53030;
        }
        
        .alert-success {
            background: #c6f6d5;
            border: 1px solid #68d391;
            color: #2f855a;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .auth-link {
            color: #4299e1;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }
            
            .auth-sidebar {
                display: none;
            }
            
            .auth-main {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body class="auth-body">
    <div class="auth-container">
        <!-- Sidebar -->
        <div class="auth-sidebar">
            <div class="auth-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="auth-brand">TeachVerse</h1>
            <p class="auth-tagline">Your Gateway to Professional Teacher Training</p>
            <ul class="auth-features">
                <li><i class="fas fa-check"></i> Interactive Learning Experience</li>
                <li><i class="fas fa-check"></i> Expert-Led Courses</li>
                <li><i class="fas fa-check"></i> Progress Tracking</li>
                <li><i class="fas fa-check"></i> Community Support</li>
                <li><i class="fas fa-check"></i> Certification Programs</li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="auth-main">
            <div class="auth-header">
                <h2 class="auth-title">
                    <?php if ($mode === 'login'): ?>
                        Welcome Back!
                    <?php else: ?>
                        Join TeachVerse
                    <?php endif; ?>
                </h2>
                <p class="auth-subtitle">
                    <?php if ($mode === 'login'): ?>
                        Sign in to continue your learning journey
                    <?php else: ?>
                        Create your account and start learning
                    <?php endif; ?>
                </p>
            </div>

            <!-- User Type Selector -->
            <div class="user-type-selector">
                <a href="?mode=<?php echo $mode; ?>&type=user" 
                   class="user-type-option <?php echo $userType === 'user' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Student/Trainer</span>
                </a>
                <a href="?mode=<?php echo $mode; ?>&type=admin" 
                   class="user-type-option <?php echo $userType === 'admin' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>Administrator</span>
                </a>
            </div>

            <!-- Mode Toggle -->
            <div class="auth-mode-toggle">
                <a href="?mode=login&type=<?php echo $userType; ?>" 
                   class="mode-option <?php echo $mode === 'login' ? 'active' : ''; ?>">
                    Sign In
                </a>
                <a href="?mode=register&type=<?php echo $userType; ?>" 
                   class="mode-option <?php echo $mode === 'register' ? 'active' : ''; ?>">
                    Sign Up
                </a>
            </div>

            <!-- Error/Success Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Authentication Form -->
            <form method="POST" class="auth-form">
                <input type="hidden" name="action" value="<?php echo $mode; ?>">
                <input type="hidden" name="user_type" value="<?php echo $userType; ?>">

                <?php if ($mode === 'register'): ?>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input" placeholder="Enter your full name" required>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>

                <?php if ($mode === 'register'): ?>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                    </div>

                    <?php if ($userType === 'user'): ?>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">Select your role</option>
                                <option value="student">Student</option>
                                <option value="trainer">Trainer</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ($userType === 'admin'): ?>
                        <div class="admin-key-notice">
                            <i class="fas fa-key"></i>
                            <strong>Admin Registration:</strong> Requires a special registration key provided by the system administrator.
                        </div>
                        <div class="form-group">
                            <label class="form-label">Admin Registration Key</label>
                            <input type="password" name="admin_key" class="form-input" placeholder="Enter admin key" required>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <button type="submit" class="auth-button">
                    <?php if ($mode === 'login'): ?>
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In as <?php echo ucfirst($userType); ?>
                    <?php else: ?>
                        <i class="fas fa-user-plus"></i>
                        Create <?php echo ucfirst($userType); ?> Account
                    <?php endif; ?>
                </button>
            </form>

            <!-- Additional Links -->
            <div class="auth-links">
                <?php if ($mode === 'login'): ?>
                    <p>Don't have an account? 
                        <a href="?mode=register&type=<?php echo $userType; ?>" class="auth-link">Sign up here</a>
                    </p>
                <?php else: ?>
                    <p>Already have an account? 
                        <a href="?mode=login&type=<?php echo $userType; ?>" class="auth-link">Sign in here</a>
                    </p>
                <?php endif; ?>
                <p><a href="index.php" class="auth-link">← Back to Home</a></p>
            </div>
        </div>
    </div>

    <script>
        // Add smooth transitions and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const form = document.querySelector('.auth-form');
            const inputs = form.querySelectorAll('input, select');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() !== '') {
                        this.style.borderColor = '#48bb78';
                    }
                });
                
                input.addEventListener('focus', function() {
                    this.style.borderColor = '#4299e1';
                });
            });
            
            // Password confirmation validation
            const password = form.querySelector('input[name="password"]');
            const confirmPassword = form.querySelector('input[name="confirm_password"]');
            
            if (confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    if (this.value !== password.value) {
                        this.style.borderColor = '#f56565';
                    } else {
                        this.style.borderColor = '#48bb78';
                    }
                });
            }
            
            // Form submission with loading state
            form.addEventListener('submit', function() {
                const button = this.querySelector('.auth-button');
                const originalText = button.innerHTML;
                
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                button.disabled = true;
                
                // Reset after 5 seconds in case of issues
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 5000);
            });
        });
    </script>
</body>
</html>
