<?php
require_once '../../config/database.php';
requireAdmin(); // Only admins can edit users

$pdo = getDBConnection();

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    $_SESSION['error_message'] = 'Invalid user ID.';
    header('Location: index.php');
    exit;
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error_message'] = 'User not found.';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $new_password = sanitize($_POST['new_password']);
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    
    if (!in_array($role, ['admin', 'trainer', 'student'])) {
        $errors[] = 'Invalid role selected.';
    }
    
    // Check if email already exists (excluding current user)
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Update user if no errors
    if (empty($errors)) {
        try {
            // Prepare update query
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->execute([$name, $email, $role, $hashed_password, $user_id]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->execute([$name, $email, $role, $user_id]);
            }
            
            $_SESSION['success_message'] = 'User updated successfully!';
            header('Location: index.php');
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
    <title>Edit User - TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Edit User</h1>
                <a href="index.php" class="btn btn-secondary">Back to Users</a>
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

            <div class="form-container">
                <form method="POST" class="form">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?php echo htmlspecialchars($user['name']); ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($user['email']); ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="trainer" <?php echo $user['role'] === 'trainer' ? 'selected' : ''; ?>>Trainer</option>
                            <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password (leave blank to keep current)</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            placeholder="Enter new password or leave blank"
                        >
                        <small class="form-text">Only enter a new password if you want to change it</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <div class="user-info-card">
                <h3>User Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>User ID:</strong> <?php echo $user['user_id']; ?>
                    </div>
                    <div class="info-item">
                        <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?>
                    </div>
                    <div class="info-item">
                        <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?>
                    </div>
                    <div class="info-item">
                        <strong>Current Role:</strong> 
                        <span class="role-badge role-<?php echo $user['role']; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
