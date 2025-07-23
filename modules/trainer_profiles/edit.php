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

    // Check permissions - only owner or admin can edit
    if ($trainer['user_id'] != $_SESSION['user_id'] && !hasRole('admin')) {
        $_SESSION['error_message'] = 'You do not have permission to edit this profile.';
        header('Location: index.php');
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profile_title = sanitize($_POST['profile_title']);
    $bio = sanitize($_POST['bio']);
    $experience = sanitize($_POST['experience']);
    $certificates = sanitize($_POST['certificates']);
    $specializations = sanitize($_POST['specializations']);
    $hourly_rate = floatval($_POST['hourly_rate']);
    $availability = sanitize($_POST['availability']);
    $profile_image = $trainer['profile_image'];
    
    // Validation
    if (empty($profile_title)) {
        $errors[] = 'Profile title is required.';
    }
    
    if (empty($bio)) {
        $errors[] = 'Bio is required.';
    }
    
    if (empty($experience)) {
        $errors[] = 'Experience is required.';
    }
    
    if ($hourly_rate < 0) {
        $errors[] = 'Hourly rate cannot be negative.';
    }
    
    // Handle file upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/trainers/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            // Check file size (max 5MB)
            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Profile image must be less than 5MB.';
            } else {
                $new_image = 'trainer_' . $trainer['user_id'] . '_' . time() . '.' . $file_extension;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_image)) {
                    // Delete old image if it's not the default
                    if ($trainer['profile_image'] !== 'default-trainer.jpg' && file_exists($upload_dir . $trainer['profile_image'])) {
                        unlink($upload_dir . $trainer['profile_image']);
                    }
                    $profile_image = $new_image;
                } else {
                    $errors[] = 'Failed to upload profile image.';
                }
            }
        } else {
            $errors[] = 'Invalid file type. Please upload JPG, PNG, GIF, or WebP files only.';
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE trainer_profiles 
                SET profile_title = ?, bio = ?, experience = ?, certificates = ?, 
                    profile_image = ?, specializations = ?, hourly_rate = ?, 
                    availability = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE profile_id = ?
            ");
            
            $result = $stmt->execute([
                $profile_title, $bio, $experience, $certificates, 
                $profile_image, $specializations, $hourly_rate, 
                $availability, $profile_id
            ]);
            
            if ($result) {
                $_SESSION['success_message'] = 'Trainer profile updated successfully!';
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to update profile. Please try again.';
            }
            
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
    <title>Edit Trainer Profile - TeachVerse</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .edit-form {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background-color: #fed7d7;
            border: 1px solid #feb2b2;
            color: #c53030;
        }

        .alert-success {
            background-color: #c6f6d5;
            border: 1px solid #9ae6b4;
            color: #2f855a;
        }

        .current-image {
            display: block;
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="edit-form">
            <div class="form-header">
                <h1><i class="fas fa-edit"></i> Edit Trainer Profile</h1>
                <p>Update your trainer profile information</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profile_title">Profile Title *</label>
                    <input type="text" id="profile_title" name="profile_title" 
                           value="<?php echo htmlspecialchars($trainer['profile_title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="bio">Bio *</label>
                    <textarea id="bio" name="bio" required><?php echo htmlspecialchars($trainer['bio']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="experience">Experience *</label>
                    <textarea id="experience" name="experience" required><?php echo htmlspecialchars($trainer['experience']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="certificates">Certificates</label>
                    <textarea id="certificates" name="certificates"><?php echo htmlspecialchars($trainer['certificates']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="specializations">Specializations</label>
                    <input type="text" id="specializations" name="specializations" 
                           value="<?php echo htmlspecialchars($trainer['specializations']); ?>"
                           placeholder="e.g., Web Development, Data Science, Mobile Apps">
                </div>

                <div class="form-group">
                    <label for="hourly_rate">Hourly Rate ($)</label>
                    <input type="number" id="hourly_rate" name="hourly_rate" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($trainer['hourly_rate']); ?>">
                </div>

                <div class="form-group">
                    <label for="availability">Availability</label>
                    <select id="availability" name="availability">
                        <option value="Available" <?php echo ($trainer['availability'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                        <option value="Busy" <?php echo ($trainer['availability'] === 'Busy') ? 'selected' : ''; ?>>Busy</option>
                        <option value="Unavailable" <?php echo ($trainer['availability'] === 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="profile_image">Profile Image</label>
                    <?php if ($trainer['profile_image']): ?>
                        <img src="../../assets/images/trainers/<?php echo htmlspecialchars($trainer['profile_image']); ?>" 
                             alt="Current Profile" class="current-image"
                             onerror="this.src='../../assets/images/trainers/default-trainer.jpg'">
                    <?php endif; ?>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                    <small>Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
