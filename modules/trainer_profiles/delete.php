<?php
require_once '../../config/database.php';
requireLogin();

$pdo = getDBConnection();

// Get profile ID from URL
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($profile_id <= 0) {
    $_SESSION['error_message'] = 'Invalid profile ID.';
    header('Location: index.php');
    exit;
}

// Fetch trainer profile data
try {
    $stmt = $pdo->prepare("
        SELECT tp.*, u.name, u.email 
        FROM trainer_profiles tp 
        JOIN users u ON tp.user_id = u.user_id 
        WHERE tp.profile_id = ?
    ");
    $stmt->execute([$profile_id]);
    $trainer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trainer) {
        $_SESSION['error_message'] = 'Trainer profile not found.';
        header('Location: index.php');
        exit;
    }

    // Check permissions - only owner or admin can delete
    if ($trainer['user_id'] != $_SESSION['user_id'] && !hasRole('admin')) {
        $_SESSION['error_message'] = 'You do not have permission to delete this profile.';
        header('Location: index.php');
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Check profile dependencies
$dependencies = [];
try {
    // Check courses created by this trainer
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE created_by = ?");
    $stmt->execute([$trainer_id]);
    $course_count = $stmt->fetchColumn();
    if ($course_count > 0) {
        $dependencies[] = "$course_count course(s) created";
    }
    
    // Check enrollments in trainer's courses
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.enroll_id) 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.course_id 
        WHERE c.created_by = ?
    ");
    $stmt->execute([$trainer['user_id']]);
    $enrollment_count = $stmt->fetchColumn();
    if ($enrollment_count > 0) {
        $dependencies[] = "$enrollment_count student enrollment(s) in your courses";
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
        $_SESSION['error_message'] = 'Please type "DELETE" to confirm profile deletion.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete profile image if it's not default
            if ($trainer['profile_image'] !== 'default-trainer.jpg') {
                $image_path = '../../assets/images/trainers/' . $trainer['profile_image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Delete trainer profile
            $stmt = $pdo->prepare("DELETE FROM trainer_profiles WHERE profile_id = ?");
            $stmt->execute([$profile_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Trainer profile for "' . $trainer['name'] . '" has been deleted successfully.';
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
    <title>Delete Trainer Profile - TeachVerse</title>
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
                <a href="index.php">Trainers</a>
                <a href="../../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Delete Trainer Profile</h1>
                <div class="header-actions">
                    <a href="view.php?id=<?php echo $profile_id; ?>" class="btn btn-secondary">View Profile</a>
                    <a href="index.php" class="btn btn-secondary">Back to Trainers</a>
                </div>
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

                <div class="profile-details-card">
                    <h3>Trainer Profile to Delete</h3>
                    <div class="profile-info">
                        <div class="profile-image-container">
                            <img src="../../assets/images/trainers/<?php echo htmlspecialchars($trainer['profile_image']); ?>" 
                                 alt="Profile" 
                                 onerror="this.src='../../assets/images/default-trainer.jpg'">
                        </div>
                        <div class="profile-meta">
                            <h4><?php echo htmlspecialchars($trainer['name']); ?></h4>
                            <p class="email"><?php echo htmlspecialchars($trainer['email']); ?></p>
                            
                            <?php if (!empty($trainer['bio'])): ?>
                                <div class="bio-preview">
                                    <strong>Bio:</strong>
                                    <p><?php echo htmlspecialchars(substr($trainer['bio'], 0, 200)); ?>
                                    <?php echo strlen($trainer['bio']) > 200 ? '...' : ''; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <strong>Profile ID:</strong> <?php echo $trainer['id']; ?>
                        </div>
                        <div class="stat-item">
                            <strong>Created:</strong> <?php echo date('M j, Y', strtotime($trainer['created_at'])); ?>
                        </div>
                        <div class="stat-item">
                            <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($trainer['updated_at'])); ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($dependencies)): ?>
                    <div class="dependencies-card">
                        <h3>⚠️ This trainer profile has the following dependencies:</h3>
                        <ul class="dependency-list">
                            <?php foreach ($dependencies as $dependency): ?>
                                <li><?php echo htmlspecialchars($dependency); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="warning-text">
                            <strong>Note:</strong> Deleting this trainer profile will not delete the associated courses or enrollments, but the trainer will no longer have a public profile. Students will still be able to access the courses, but the trainer information will be limited.
                        </p>
                    </div>
                <?php endif; ?>

                <div class="impact-info">
                    <h3>What will be deleted:</h3>
                    <ul class="impact-list">
                        <li><i class="fas fa-user-circle"></i> Trainer profile information</li>
                        <li><i class="fas fa-file-alt"></i> Bio and experience details</li>
                        <li><i class="fas fa-certificate"></i> Certificates and qualifications</li>
                        <li><i class="fas fa-image"></i> Profile image (if custom)</li>
                    </ul>
                    
                    <h3>What will NOT be deleted:</h3>
                    <ul class="preserve-list">
                        <li><i class="fas fa-user"></i> User account (can create new profile)</li>
                        <li><i class="fas fa-book"></i> Created courses (will remain active)</li>
                        <li><i class="fas fa-users"></i> Student enrollments</li>
                        <li><i class="fas fa-star"></i> Course reviews and ratings</li>
                    </ul>
                </div>

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
                        <button type="submit" class="btn btn-danger" id="delete-btn">
                            🗑️ Delete Trainer Profile
                        </button>
                        <a href="view.php?id=<?php echo $profile_id; ?>" class="btn btn-secondary">Cancel</a>
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
        
        return confirm('Are you absolutely sure you want to delete this trainer profile? This action cannot be undone.');
    }

    // Real-time validation
    document.getElementById('confirm_delete').addEventListener('input', function() {
        const deleteBtn = document.getElementById('delete-btn');
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
        const deleteBtn = document.getElementById('delete-btn');
        deleteBtn.disabled = true;
        deleteBtn.style.opacity = '0.5';
    });
    </script>

    <style>
    .profile-details-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .profile-details-card h3 {
        margin-bottom: 1.5rem;
        color: var(--text-primary);
    }

    .profile-info {
        display: flex;
        gap: 2rem;
        align-items: flex-start;
        margin-bottom: 2rem;
    }

    .profile-image-container {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        background: var(--gradient);
        flex-shrink: 0;
    }

    .profile-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-meta h4 {
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
        font-size: 1.5rem;
    }

    .profile-meta .email {
        color: var(--text-secondary);
        margin: 0 0 1rem 0;
        font-size: 1rem;
    }

    .bio-preview {
        background: var(--background-light);
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
    }

    .bio-preview strong {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .bio-preview p {
        margin: 0;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    .profile-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    .stat-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .stat-item strong {
        color: var(--text-primary);
        font-size: 0.9rem;
    }

    .impact-info {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .impact-info h3 {
        margin-bottom: 1rem;
        color: var(--text-primary);
    }

    .impact-list,
    .preserve-list {
        list-style: none;
        padding: 0;
        margin: 0 0 2rem 0;
    }

    .impact-list li,
    .preserve-list li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0;
        color: var(--text-secondary);
    }

    .impact-list i {
        color: var(--danger-color);
        width: 20px;
    }

    .preserve-list i {
        color: var(--success-color);
        width: 20px;
    }

    @media (max-width: 768px) {
        .profile-info {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .profile-stats {
            grid-template-columns: 1fr;
        }
        
        .header-actions {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .header-actions .btn {
            width: 100%;
        }
    }
    </style>
</body>
</html>
