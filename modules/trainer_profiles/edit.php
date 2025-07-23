<?php
require_once '../../config/da      // Check permissions - only owner or admin can edit
    if ($trainer['user_id'] != $_SESSION['user_id'] && !hasRole('admin')) {
        $_SESSION['error_message'] = 'You do not have permission to edit this profile.';
        header('Location: index.php');
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error occurred.';
    header('Location: index.php');
    exit;
}

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error occurred.';
    header('Location: index.php');
    exit;
}equireLogin();

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
        WHERE tp.id = ?
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profile_title = sanitize($_POST['profile_title']);
    $bio = sanitize($_POST['bio']);
    $experience = sanitize($_POST['experience']);
    $certificates = sanitize($_POST['certificates']);
    $profile_image = $trainer['profile_image'];
    
    // Validation
    $errors = [];
    
    if (empty($profile_title)) {
        $errors[] = 'Profile title is required.';
    }
    
    if (empty($bio)) {
        $errors[] = 'Bio is required.';
    }
    
    if (empty($experience)) {
        $errors[] = 'Experience is required.';
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
                $new_image = uniqid() . '.' . $file_extension;
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
            $errors[] = 'Invalid image format. Please use JPG, PNG, GIF, or WebP.';
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE trainer_profiles 
                SET profile_title = ?, bio = ?, experience = ?, certificates = ?, profile_image = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$profile_title, $bio, $experience, $certificates, $profile_image, $profile_id]);
            
            $_SESSION['success_message'] = 'Trainer profile updated successfully!';
            header('Location: view.php?id=' . $profile_id);
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
    <title>Edit Trainer Profile - TeachVerse</title>
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
                <h1>Edit Trainer Profile</h1>
                <div class="header-actions">
                    <a href="view.php?id=<?php echo $trainer_id; ?>" class="btn btn-secondary">View Profile</a>
                    <a href="index.php" class="btn btn-secondary">Back to Trainers</a>
                </div>
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

            <div class="form-layout">
                <!-- Current Profile Preview -->
                <div class="profile-preview">
                    <h3>Current Profile</h3>
                    <div class="current-profile-card">
                        <div class="profile-image-container">
                            <img src="../../assets/images/trainers/<?php echo htmlspecialchars($trainer['profile_image']); ?>" 
                                 alt="Current Profile" 
                                 id="current-image"
                                 onerror="this.src='../../assets/images/default-trainer.jpg'">
                        </div>
                        <div class="profile-info">
                            <h4><?php echo htmlspecialchars($trainer['name']); ?></h4>
                            <p class="email"><?php echo htmlspecialchars($trainer['email']); ?></p>
                            <p class="last-updated">
                                Last updated: <?php echo date('M j, Y g:i A', strtotime($trainer['updated_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data" class="form">
                        <div class="form-group">
                            <label for="profile_image">Profile Image</label>
                            <div class="file-upload-area">
                                <input type="file" 
                                       id="profile_image" 
                                       name="profile_image" 
                                       accept="image/*"
                                       onchange="previewImage(this)">
                                <div class="file-upload-text">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Choose new image or drag & drop</span>
                                    <small>JPG, PNG, GIF, WebP (Max 5MB)</small>
                                </div>
                            </div>
                            <div id="image-preview" style="display: none;">
                                <img id="preview-img" alt="Preview">
                                <button type="button" onclick="clearImagePreview()" class="btn btn-sm btn-secondary">
                                    Remove Preview
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="profile_title">Profile Title / Specialization *</label>
                            <input 
                                type="text" 
                                id="profile_title" 
                                name="profile_title" 
                                value="<?php echo htmlspecialchars($trainer['profile_title'] ?? ''); ?>"
                                placeholder="e.g., Web Development Expert, Data Science Instructor, UI/UX Designer..."
                                required
                                maxlength="255">
                            <small class="form-text">Choose a title that represents your specialization or expertise area.</small>
                        </div>

                        <div class="form-group">
                            <label for="bio">Bio / About Me *</label>
                            <textarea 
                                id="bio" 
                                name="bio" 
                                rows="6" 
                                placeholder="Tell us about yourself, your passion for teaching, and your background..."
                                required
                            ><?php echo htmlspecialchars($trainer['bio']); ?></textarea>
                            <small class="form-text">Describe your background, teaching philosophy, and what makes you unique.</small>
                        </div>

                        <div class="form-group">
                            <label for="experience">Professional Experience *</label>
                            <textarea 
                                id="experience" 
                                name="experience" 
                                rows="6" 
                                placeholder="Detail your professional experience, skills, and areas of expertise..."
                                required
                            ><?php echo htmlspecialchars($trainer['experience']); ?></textarea>
                            <small class="form-text">Include your work history, technical skills, and years of experience.</small>
                        </div>

                        <div class="form-group">
                            <label for="certificates">Certifications & Qualifications</label>
                            <textarea 
                                id="certificates" 
                                name="certificates" 
                                rows="4" 
                                placeholder="List your certifications, degrees, and professional qualifications..."
                            ><?php echo htmlspecialchars($trainer['certificates']); ?></textarea>
                            <small class="form-text">Include relevant certifications, degrees, and training programs.</small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                            <a href="view.php?id=<?php echo $trainer_id; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
    function previewImage(input) {
        const preview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearImagePreview() {
        const input = document.getElementById('profile_image');
        const preview = document.getElementById('image-preview');
        
        input.value = '';
        preview.style.display = 'none';
    }

    // Drag and drop functionality
    const fileUploadArea = document.querySelector('.file-upload-area');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        fileUploadArea.classList.add('highlight');
    }

    function unhighlight(e) {
        fileUploadArea.classList.remove('highlight');
    }

    fileUploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            document.getElementById('profile_image').files = files;
            previewImage(document.getElementById('profile_image'));
        }
    }
    </script>

    <style>
    .form-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .profile-preview h3 {
        margin-bottom: 1rem;
        color: var(--text-primary);
    }

    .current-profile-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
    }

    .profile-image-container {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto 1rem;
        background: var(--gradient);
    }

    .profile-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-info h4 {
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
    }

    .profile-info .email {
        color: var(--text-secondary);
        margin: 0 0 1rem 0;
        font-size: 0.9rem;
    }

    .profile-info .last-updated {
        color: var(--text-secondary);
        font-size: 0.8rem;
        margin: 0;
    }

    .file-upload-area {
        border: 2px dashed var(--border-color);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.3s ease, background-color 0.3s ease;
        position: relative;
    }

    .file-upload-area:hover,
    .file-upload-area.highlight {
        border-color: var(--primary-color);
        background-color: rgba(var(--primary-rgb), 0.05);
    }

    .file-upload-area input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .file-upload-text i {
        font-size: 2rem;
        color: var(--primary-color);
        display: block;
        margin-bottom: 0.5rem;
    }

    .file-upload-text span {
        display: block;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .file-upload-text small {
        color: var(--text-secondary);
        font-size: 0.8rem;
    }

    #image-preview {
        margin-top: 1rem;
        text-align: center;
    }

    #image-preview img {
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .form-layout {
            grid-template-columns: 1fr;
            gap: 1rem;
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
