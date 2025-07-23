<?php
// filepath: C:\xampp\htdocs\teachverse\modules\trainer_profiles\create.php
require_once '../../config/database.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

$error = '';
$success = '';

// Check user permissions
if (!hasRole('trainer') && !hasRole('admin')) {
    header('Location: ../../dashboard.php?error=Access denied');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize form data
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
            // Handle profile image upload
            $profile_image = 'default-trainer.jpg';
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/images/trainers/';
                
                // Create directory if it doesn't exist
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
            
            // Insert into database
            $sql = "INSERT INTO trainer_profiles (user_id, profile_title, bio, experience, certificates, profile_image, specializations, hourly_rate, availability, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$target_user_id, $profile_title, $bio, $experience, $certificates, $profile_image, $specializations, $hourly_rate, $availability]);
            
            if ($result) {
                $profile_id = $pdo->lastInsertId();
                
                // Success - redirect to trainers list with success message
                $success_message = urlencode("Trainer profile created successfully!");
                header("Location: index.php?success=" . $success_message);
                exit();
            } else {
                $error = 'Failed to create profile. Please try again.';
            }
        }
    } catch(PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    } catch(Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Trainer Profile | TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-group-full {
            grid-column: 1 / -1;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        .file-upload {
            position: relative;
            display: block;
            width: 100%;
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
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .file-upload:hover .file-upload-label {
            border-color: #667eea;
            color: #667eea;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5a6fd8;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .form-help {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }
        .back-nav {
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
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
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="back-nav">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Trainers
                </a>
            </div>

            <div class="header">
                <h1><i class="fas fa-plus-circle"></i> Create Trainer Profile</h1>
                <p>Set up your professional trainer profile to showcase your expertise</p>
            </div>

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

            <form method="POST" enctype="multipart/form-data" class="form-container" id="createProfileForm">
                <!-- Admin User Selection -->
                <?php if (hasRole('admin') && !empty($users)): ?>
                <div class="form-group">
                    <label for="user_id"><i class="fas fa-user"></i> Select User *</label>
                    <select name="user_id" id="user_id" class="form-control" required>
                        <option value="">Choose a user...</option>
                        <?php foreach ($users as $user_option): ?>
                            <option value="<?php echo $user_option['user_id']; ?>">
                                <?php echo htmlspecialchars($user_option['name'] . ' (' . $user_option['email'] . ') - ' . ucfirst($user_option['role'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-help">Select which user this profile belongs to</div>
                </div>
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="profile_title"><i class="fas fa-star"></i> Profile Title/Specialization *</label>
                        <input type="text" id="profile_title" name="profile_title" class="form-control" 
                               placeholder="e.g., Web Development Expert, Data Science Trainer" required>
                        <div class="form-help">What's your main area of expertise?</div>
                    </div>

                    <div class="form-group">
                        <label for="hourly_rate"><i class="fas fa-dollar-sign"></i> Hourly Rate (USD)</label>
                        <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" 
                               min="0" step="0.01" placeholder="50.00" value="0">
                        <div class="form-help">Your teaching rate per hour</div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="specializations"><i class="fas fa-tags"></i> Specializations</label>
                        <input type="text" id="specializations" name="specializations" class="form-control" 
                               placeholder="e.g., JavaScript, React, Node.js, Python">
                        <div class="form-help">Comma-separated list of your skills</div>
                    </div>

                    <div class="form-group">
                        <label for="availability"><i class="fas fa-clock"></i> Availability</label>
                        <select name="availability" id="availability" class="form-control">
                            <option value="Available">Available</option>
                            <option value="Busy">Busy</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                        <div class="form-help">Your current availability status</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="profile_image"><i class="fas fa-image"></i> Profile Image</label>
                    <div class="file-upload">
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <label for="profile_image" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i><br>
                            Click to upload profile image<br>
                            <small>JPG, PNG, GIF (Max 5MB)</small>
                        </label>
                    </div>
                </div>

                <div class="form-group-full">
                    <label for="bio"><i class="fas fa-user"></i> About You</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4" 
                              placeholder="Tell us about yourself, your teaching philosophy, and what makes you unique as an educator..."></textarea>
                    <div class="form-help">Share your background and what students can expect from you</div>
                </div>

                <div class="form-group-full">
                    <label for="experience"><i class="fas fa-briefcase"></i> Professional Experience</label>
                    <textarea id="experience" name="experience" class="form-control" rows="4" 
                              placeholder="Describe your work experience, notable positions, years in the industry, key projects..."></textarea>
                    <div class="form-help">Highlight your professional background and relevant experience</div>
                </div>

                <div class="form-group-full">
                    <label for="certificates"><i class="fas fa-certificate"></i> Certifications & Achievements</label>
                    <textarea id="certificates" name="certificates" class="form-control" rows="4" 
                              placeholder="List your certifications, awards, published work, degrees, and other achievements..."></textarea>
                    <div class="form-help">Include any credentials that validate your expertise</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-save"></i> Create Profile
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        // File upload preview
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const label = document.querySelector('.file-upload-label');
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                label.innerHTML = `<i class="fas fa-check"></i><br>Selected: ${fileName}`;
                label.style.borderColor = '#28a745';
                label.style.color = '#28a745';
            }
        });

        // Form submission handling
        document.getElementById('createProfileForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const profileTitle = document.getElementById('profile_title').value.trim();
            
            if (!profileTitle) {
                e.preventDefault();
                alert('Profile title is required!');
                document.getElementById('profile_title').focus();
                return false;
            }
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Profile...';
            submitBtn.disabled = true;
            
            // Re-enable button after 10 seconds (fallback)
            setTimeout(function() {
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Create Profile';
                submitBtn.disabled = false;
            }, 10000);
        });
    </script>
</body>
</html>