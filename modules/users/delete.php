<?php
require_once '../../config/database.php';
requireAdmin(); // Only admins can delete users

$pdo = getDBConnection();

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    $_SESSION['error_message'] = 'Invalid user ID.';
    header('Location: index.php');
    exit;
}

// Prevent self-deletion
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You cannot delete your own account.';
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

// Check user dependencies
$dependencies = [];
try {
    // Check courses created by this user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE created_by = ?");
    $stmt->execute([$user_id]);
    $course_count = $stmt->fetchColumn();
    if ($course_count > 0) {
        $dependencies[] = "$course_count course(s) created";
    }
    
    // Check enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $enrollment_count = $stmt->fetchColumn();
    if ($enrollment_count > 0) {
        $dependencies[] = "$enrollment_count enrollment(s)";
    }
    
    // Check reviews
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $review_count = $stmt->fetchColumn();
    if ($review_count > 0) {
        $dependencies[] = "$review_count review(s)";
    }
    
    // Check trainer profile
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trainer_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile_count = $stmt->fetchColumn();
    if ($profile_count > 0) {
        $dependencies[] = "trainer profile";
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error while checking dependencies: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm_delete = isset($_POST['confirm_delete']) ? $_POST['confirm_delete'] : '';
    
    if ($confirm_delete !== 'DELETE') {
        $_SESSION['error_message'] = 'Please type "DELETE" to confirm user deletion.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete user (this will cascade delete related records due to foreign key constraints)
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['success_message'] = 'User "' . $user['name'] . '" has been deleted successfully.';
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaction
            $pdo->rollback();
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Delete User</h1>
                <a href="index.php" class="btn btn-secondary">Back to Users</a>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="danger-zone">
                <div class="danger-header">
                    <h2>⚠️ Danger Zone</h2>
                    <p>This action cannot be undone. Please be certain.</p>
                </div>

                <div class="user-details-card">
                    <h3>User to Delete</h3>
                    <div class="user-info">
                        <div class="user-avatar">
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                            </div>
                        </div>
                        <div class="user-meta">
                            <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="user-stats">
                        <div class="stat-item">
                            <strong>User ID:</strong> <?php echo $user['user_id']; ?>
                        </div>
                        <div class="stat-item">
                            <strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </div>
                        <div class="stat-item">
                            <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($dependencies)): ?>
                    <div class="dependencies-card">
                        <h3>⚠️ This user has the following dependencies:</h3>
                        <ul class="dependency-list">
                            <?php foreach ($dependencies as $dependency): ?>
                                <li><?php echo htmlspecialchars($dependency); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="warning-text">
                            <strong>Warning:</strong> Deleting this user will also remove all associated data including courses, enrollments, reviews, and trainer profiles. This action cannot be undone.
                        </p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="delete-form" onsubmit="return confirmDeletion()">
                    <div class="form-group">
                        <label for="confirm_delete">
                            To confirm deletion, type <strong>"DELETE"</strong> in the field below:
                        </label>
                        <input 
                            type="text" 
                            id="confirm_delete" 
                            name="confirm_delete" 
                            placeholder="Type DELETE to confirm"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger">
                            🗑️ Delete User Permanently
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
    function confirmDeletion() {
        const userInput = document.getElementById('confirm_delete').value;
        if (userInput !== 'DELETE') {
            alert('Please type "DELETE" exactly to confirm deletion.');
            return false;
        }
        
        return confirm('Are you absolutely sure you want to delete this user? This action cannot be undone and will remove all associated data.');
    }

    // Real-time validation
    document.getElementById('confirm_delete').addEventListener('input', function() {
        const deleteBtn = document.querySelector('.btn-danger');
        if (this.value === 'DELETE') {
            deleteBtn.disabled = false;
            deleteBtn.style.opacity = '1';
        } else {
            deleteBtn.disabled = true;
            deleteBtn.style.opacity = '0.5';
        }
    });

    // Initialize button state
    document.addEventListener('DOMContentLoaded', function() {
        const deleteBtn = document.querySelector('.btn-danger');
        deleteBtn.disabled = true;
        deleteBtn.style.opacity = '0.5';
    });
    </script>
</body>
</html>
