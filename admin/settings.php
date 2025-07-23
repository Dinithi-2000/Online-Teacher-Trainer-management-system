<?php
require_once '../auth.php';
requireAdmin();

// Handle settings updates
if ($_POST) {
    $success = [];
    $errors = [];
    
    try {
        $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create settings table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('text', 'number', 'boolean', 'email', 'url', 'textarea') DEFAULT 'text',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        if (isset($_POST['update_general'])) {
            $settings = [
                'site_name' => $_POST['site_name'],
                'site_description' => $_POST['site_description'],
                'admin_email' => $_POST['admin_email'],
                'contact_email' => $_POST['contact_email'],
                'site_url' => $_POST['site_url'],
                'timezone' => $_POST['timezone']
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success[] = "General settings updated successfully.";
        }
        
        if (isset($_POST['update_security'])) {
            $settings = [
                'session_timeout' => $_POST['session_timeout'],
                'max_login_attempts' => $_POST['max_login_attempts'],
                'password_min_length' => $_POST['password_min_length'],
                'require_email_verification' => isset($_POST['require_email_verification']) ? '1' : '0',
                'enable_two_factor' => isset($_POST['enable_two_factor']) ? '1' : '0'
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success[] = "Security settings updated successfully.";
        }
        
        if (isset($_POST['update_email'])) {
            $settings = [
                'smtp_host' => $_POST['smtp_host'],
                'smtp_port' => $_POST['smtp_port'],
                'smtp_username' => $_POST['smtp_username'],
                'smtp_password' => $_POST['smtp_password'],
                'smtp_encryption' => $_POST['smtp_encryption'],
                'mail_from_name' => $_POST['mail_from_name'],
                'mail_from_address' => $_POST['mail_from_address']
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success[] = "Email settings updated successfully.";
        }
        
        if (isset($_POST['update_appearance'])) {
            $settings = [
                'theme_color' => $_POST['theme_color'],
                'logo_url' => $_POST['logo_url'],
                'favicon_url' => $_POST['favicon_url'],
                'footer_text' => $_POST['footer_text'],
                'custom_css' => $_POST['custom_css']
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success[] = "Appearance settings updated successfully.";
        }
        
    } catch(PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// Load current settings
try {
    $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
} catch(PDOException $e) {
    $settings = [];
}

// Default values
$defaults = [
    'site_name' => 'TeachVerse',
    'site_description' => 'Professional Online Teacher Training Platform',
    'admin_email' => 'admin@teachverse.com',
    'contact_email' => 'contact@teachverse.com',
    'site_url' => 'http://localhost',
    'timezone' => 'UTC',
    'session_timeout' => '3600',
    'max_login_attempts' => '5',
    'password_min_length' => '8',
    'require_email_verification' => '0',
    'enable_two_factor' => '0',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'mail_from_name' => 'TeachVerse',
    'mail_from_address' => 'noreply@teachverse.com',
    'theme_color' => '#667eea',
    'logo_url' => '',
    'favicon_url' => '',
    'footer_text' => '© 2024 TeachVerse. All rights reserved.',
    'custom_css' => ''
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - TeachVerse Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/admin-style.css" rel="stylesheet">
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
        }
        
        .settings-nav {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            height: fit-content;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .settings-nav h3 {
            margin: 0 0 1rem 0;
            font-size: 1.125rem;
            color: var(--admin-primary);
        }
        
        .settings-nav-item {
            display: block;
            padding: 0.75rem 1rem;
            color: #6b7280;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            margin-bottom: 0.25rem;
        }
        
        .settings-nav-item:hover {
            background: #f3f4f6;
            color: var(--admin-primary);
        }
        
        .settings-nav-item.active {
            background: var(--admin-accent);
            color: white;
        }
        
        .settings-nav-item i {
            width: 20px;
            margin-right: 0.5rem;
        }
        
        .settings-content {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .settings-section {
            display: none;
            padding: 2rem;
        }
        
        .settings-section.active {
            display: block;
        }
        
        .settings-section h2 {
            margin: 0 0 1.5rem 0;
            font-size: 1.5rem;
            color: var(--admin-primary);
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 0.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--admin-primary);
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--admin-accent);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }
        
        .form-checkbox input {
            width: auto;
        }
        
        .form-help {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .settings-actions {
            border-top: 1px solid #f3f4f6;
            padding: 1.5rem 2rem;
            background: #f8fafc;
            border-radius: 0 0 0.75rem 0.75rem;
        }
        
        .btn-primary {
            background: var(--admin-accent);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            margin-left: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-graduation-cap"></i>
                <h3>TeachVerse Admin</h3>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="analytics.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../modules/users/index.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../modules/courses/index.php" class="nav-link">
                        <i class="fas fa-book"></i>
                        Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../modules/trainer_profiles/index.php" class="nav-link">
                        <i class="fas fa-user-tie"></i>
                        Trainers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../modules/reviews/index.php" class="nav-link">
                        <i class="fas fa-star"></i>
                        Reviews
                    </a>
                </li>
                <li class="nav-item">
                    <a href="contacts.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        Contact Messages
                    </a>
                </li>
                <div class="nav-divider"></div>
                <li class="nav-item active">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="backup.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        Backup
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logs.php" class="nav-link">
                        <i class="fas fa-list-alt"></i>
                        System Logs
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="admin-info">
                    <h4><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></h4>
                    <p>Administrator</p>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">System Settings</h1>
            </div>
            
            <div class="topbar-right">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </button>
                
                <div class="admin-user-menu">
                    <img src="../assets/images/default-avatar.jpg" alt="Admin" class="user-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="admin-avatar" style="display: none;">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="admin-content">
            <?php if (!empty($success)): ?>
                <?php foreach ($success as $msg): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $msg; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="settings-container">
                <!-- Settings Navigation -->
                <div class="settings-nav">
                    <h3>Settings</h3>
                    <a href="#general" class="settings-nav-item active" onclick="showSection('general', this)">
                        <i class="fas fa-cog"></i>
                        General
                    </a>
                    <a href="#security" class="settings-nav-item" onclick="showSection('security', this)">
                        <i class="fas fa-shield-alt"></i>
                        Security
                    </a>
                    <a href="#email" class="settings-nav-item" onclick="showSection('email', this)">
                        <i class="fas fa-envelope"></i>
                        Email
                    </a>
                    <a href="#appearance" class="settings-nav-item" onclick="showSection('appearance', this)">
                        <i class="fas fa-palette"></i>
                        Appearance
                    </a>
                </div>

                <!-- Settings Content -->
                <div class="settings-content">
                    <!-- General Settings -->
                    <div id="general" class="settings-section active">
                        <h2>General Settings</h2>
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Site Name</label>
                                    <input type="text" name="site_name" class="form-input" value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                                    <div class="form-help">The name of your website</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Site URL</label>
                                    <input type="url" name="site_url" class="form-input" value="<?php echo htmlspecialchars($settings['site_url']); ?>">
                                    <div class="form-help">Full URL of your website</div>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Site Description</label>
                                    <textarea name="site_description" class="form-textarea"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                                    <div class="form-help">Brief description of your website</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Admin Email</label>
                                    <input type="email" name="admin_email" class="form-input" value="<?php echo htmlspecialchars($settings['admin_email']); ?>">
                                    <div class="form-help">Primary administrator email</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" name="contact_email" class="form-input" value="<?php echo htmlspecialchars($settings['contact_email']); ?>">
                                    <div class="form-help">Public contact email address</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Timezone</label>
                                    <select name="timezone" class="form-select">
                                        <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                        <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                        <option value="America/Chicago" <?php echo $settings['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                        <option value="America/Denver" <?php echo $settings['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                        <option value="America/Los_Angeles" <?php echo $settings['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                        <option value="Europe/London" <?php echo $settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                        <option value="Europe/Paris" <?php echo $settings['timezone'] === 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                        <option value="Asia/Tokyo" <?php echo $settings['timezone'] === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="settings-actions">
                                <button type="submit" name="update_general" class="btn-primary">Save General Settings</button>
                                <button type="button" class="btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Settings -->
                    <div id="security" class="settings-section">
                        <h2>Security Settings</h2>
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Session Timeout (seconds)</label>
                                    <input type="number" name="session_timeout" class="form-input" value="<?php echo htmlspecialchars($settings['session_timeout']); ?>">
                                    <div class="form-help">User session timeout in seconds</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Max Login Attempts</label>
                                    <input type="number" name="max_login_attempts" class="form-input" value="<?php echo htmlspecialchars($settings['max_login_attempts']); ?>">
                                    <div class="form-help">Maximum failed login attempts before lockout</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Minimum Password Length</label>
                                    <input type="number" name="password_min_length" class="form-input" value="<?php echo htmlspecialchars($settings['password_min_length']); ?>">
                                    <div class="form-help">Minimum required password length</div>
                                </div>
                                
                                <div class="form-group full-width">
                                    <div class="form-checkbox">
                                        <input type="checkbox" id="require_email_verification" name="require_email_verification" <?php echo $settings['require_email_verification'] ? 'checked' : ''; ?>>
                                        <label for="require_email_verification">Require Email Verification</label>
                                    </div>
                                    <div class="form-help">Require users to verify their email address</div>
                                </div>
                                
                                <div class="form-group full-width">
                                    <div class="form-checkbox">
                                        <input type="checkbox" id="enable_two_factor" name="enable_two_factor" <?php echo $settings['enable_two_factor'] ? 'checked' : ''; ?>>
                                        <label for="enable_two_factor">Enable Two-Factor Authentication</label>
                                    </div>
                                    <div class="form-help">Enable 2FA for enhanced security</div>
                                </div>
                            </div>
                            
                            <div class="settings-actions">
                                <button type="submit" name="update_security" class="btn-primary">Save Security Settings</button>
                                <button type="button" class="btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Email Settings -->
                    <div id="email" class="settings-section">
                        <h2>Email Settings</h2>
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-input" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>">
                                    <div class="form-help">SMTP server hostname</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="number" name="smtp_port" class="form-input" value="<?php echo htmlspecialchars($settings['smtp_port']); ?>">
                                    <div class="form-help">SMTP server port (usually 587 or 465)</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" name="smtp_username" class="form-input" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                                    <div class="form-help">SMTP authentication username</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" name="smtp_password" class="form-input" value="<?php echo htmlspecialchars($settings['smtp_password']); ?>">
                                    <div class="form-help">SMTP authentication password</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">SMTP Encryption</label>
                                    <select name="smtp_encryption" class="form-select">
                                        <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="none" <?php echo $settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">From Name</label>
                                    <input type="text" name="mail_from_name" class="form-input" value="<?php echo htmlspecialchars($settings['mail_from_name']); ?>">
                                    <div class="form-help">Name used in outgoing emails</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">From Address</label>
                                    <input type="email" name="mail_from_address" class="form-input" value="<?php echo htmlspecialchars($settings['mail_from_address']); ?>">
                                    <div class="form-help">Email address used in outgoing emails</div>
                                </div>
                            </div>
                            
                            <div class="settings-actions">
                                <button type="submit" name="update_email" class="btn-primary">Save Email Settings</button>
                                <button type="button" class="btn-secondary">Test Email</button>
                            </div>
                        </form>
                    </div>

                    <!-- Appearance Settings -->
                    <div id="appearance" class="settings-section">
                        <h2>Appearance Settings</h2>
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Theme Color</label>
                                    <input type="color" name="theme_color" class="form-input" value="<?php echo htmlspecialchars($settings['theme_color']); ?>">
                                    <div class="form-help">Primary theme color for the website</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Logo URL</label>
                                    <input type="url" name="logo_url" class="form-input" value="<?php echo htmlspecialchars($settings['logo_url']); ?>">
                                    <div class="form-help">URL to your logo image</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Favicon URL</label>
                                    <input type="url" name="favicon_url" class="form-input" value="<?php echo htmlspecialchars($settings['favicon_url']); ?>">
                                    <div class="form-help">URL to your favicon</div>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Footer Text</label>
                                    <input type="text" name="footer_text" class="form-input" value="<?php echo htmlspecialchars($settings['footer_text']); ?>">
                                    <div class="form-help">Text displayed in the website footer</div>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Custom CSS</label>
                                    <textarea name="custom_css" class="form-textarea" rows="10"><?php echo htmlspecialchars($settings['custom_css']); ?></textarea>
                                    <div class="form-help">Custom CSS styles for your website</div>
                                </div>
                            </div>
                            
                            <div class="settings-actions">
                                <button type="submit" name="update_appearance" class="btn-primary">Save Appearance Settings</button>
                                <button type="button" class="btn-secondary">Preview Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/admin.js"></script>
    <script>
        function showSection(sectionId, navItem) {
            // Hide all sections
            document.querySelectorAll('.settings-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav items
            document.querySelectorAll('.settings-nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to selected nav item
            navItem.classList.add('active');
        }
        
        // Auto-save drafts
        let saveTimeout;
        document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(element => {
            element.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    // Auto-save draft (implement as needed)
                    console.log('Auto-saving draft...');
                }, 2000);
            });
        });
    </script>
</body>
</html>
