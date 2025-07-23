<?php
require_once '../auth.php';
requireAdmin();

// Handle backup actions
if ($_POST) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (isset($_POST['create_backup'])) {
            $backup_type = $_POST['backup_type'];
            $backup_name = 'teachverse_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backup_path = '../backups/';
            
            // Create backups directory if it doesn't exist
            if (!is_dir($backup_path)) {
                mkdir($backup_path, 0755, true);
            }
            
            $full_path = $backup_path . $backup_name;
            
            // Get all tables
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $backup_content = "-- TeachVerse Database Backup\n";
            $backup_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
            $backup_content .= "-- Database: teachverse\n\n";
            
            foreach ($tables as $table) {
                if ($backup_type === 'structure_only') {
                    // Get table structure only
                    $create_table = $db->query("SHOW CREATE TABLE `$table`")->fetch();
                    $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
                    $backup_content .= $create_table[1] . ";\n\n";
                } else {
                    // Get table structure
                    $create_table = $db->query("SHOW CREATE TABLE `$table`")->fetch();
                    $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
                    $backup_content .= $create_table[1] . ";\n\n";
                    
                    // Get table data
                    $result = $db->query("SELECT * FROM `$table`");
                    if ($result->rowCount() > 0) {
                        $backup_content .= "INSERT INTO `$table` VALUES\n";
                        $rows = [];
                        while ($row = $result->fetch(PDO::FETCH_NUM)) {
                            $row = array_map(function($value) use ($db) {
                                return $value === null ? 'NULL' : $db->quote($value);
                            }, $row);
                            $rows[] = '(' . implode(',', $row) . ')';
                        }
                        $backup_content .= implode(",\n", $rows) . ";\n\n";
                    }
                }
            }
            
            file_put_contents($full_path, $backup_content);
            $success = "Backup created successfully: $backup_name";
        }
        
        if (isset($_POST['delete_backup'])) {
            $backup_file = $_POST['backup_file'];
            $backup_path = '../backups/' . basename($backup_file);
            
            if (file_exists($backup_path) && unlink($backup_path)) {
                $success = "Backup deleted successfully.";
            } else {
                $error = "Failed to delete backup file.";
            }
        }
        
    } catch(Exception $e) {
        $error = "Backup operation failed: " . $e->getMessage();
    }
}

// Get existing backups
$backup_files = [];
$backup_dir = '../backups/';
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (substr($file, -4) === '.sql') {
            $filepath = $backup_dir . $file;
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'date' => date('Y-m-d H:i:s', filemtime($filepath))
            ];
        }
    }
    // Sort by date (newest first)
    usort($backup_files, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

// Get database statistics
try {
    $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stats = [];
    $tables = ['users', 'courses', 'trainer_profiles', 'enrollments', 'reviews', 'contacts'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $stats[$table] = $stmt->fetchColumn();
    }
    
    // Get database size
    $stmt = $db->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'db_size' FROM information_schema.tables WHERE table_schema='teachverse'");
    $db_size = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    $stats = [];
    $db_size = 'Unknown';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - TeachVerse Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/admin-style.css" rel="stylesheet">
    <style>
        .backup-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .backup-form {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .backup-list {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .backup-list-header {
            padding: 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .backup-list-header h3 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--admin-primary);
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .backup-item:last-child {
            border-bottom: none;
        }
        
        .backup-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--admin-primary);
        }
        
        .backup-info p {
            margin: 0;
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .backup-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-download {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-download:hover {
            background: #e5e7eb;
        }
        
        .btn-delete {
            background: #fecaca;
            color: #dc2626;
        }
        
        .btn-delete:hover {
            background: #fca5a5;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--admin-primary);
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-radio {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }
        
        .form-radio input {
            width: auto;
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
            width: 100%;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .database-stats {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--admin-primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f3f4f6;
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--admin-accent);
            width: 0%;
            transition: width 0.3s ease;
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
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item active">
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
                <h1 class="page-title">Database Backup</h1>
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
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Database Statistics -->
            <div class="database-stats">
                <h3>Database Overview</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $db_size; ?> MB</div>
                        <div class="stat-label">Database Size</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo isset($stats['users']) ? $stats['users'] : 0; ?></div>
                        <div class="stat-label">Users</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo isset($stats['courses']) ? $stats['courses'] : 0; ?></div>
                        <div class="stat-label">Courses</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo isset($stats['trainer_profiles']) ? $stats['trainer_profiles'] : 0; ?></div>
                        <div class="stat-label">Trainers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo isset($stats['enrollments']) ? $stats['enrollments'] : 0; ?></div>
                        <div class="stat-label">Enrollments</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo isset($stats['reviews']) ? $stats['reviews'] : 0; ?></div>
                        <div class="stat-label">Reviews</div>
                    </div>
                </div>
            </div>

            <!-- Backup Grid -->
            <div class="backup-grid">
                <!-- Create Backup Form -->
                <div class="backup-form">
                    <h3>Create New Backup</h3>
                    <form method="POST" id="backupForm">
                        <div class="form-group">
                            <label class="form-label">Backup Type</label>
                            <div class="form-radio">
                                <input type="radio" id="full_backup" name="backup_type" value="full" checked>
                                <label for="full_backup">Full Backup (Structure + Data)</label>
                            </div>
                            <div class="form-radio">
                                <input type="radio" id="structure_only" name="backup_type" value="structure_only">
                                <label for="structure_only">Structure Only</label>
                            </div>
                        </div>
                        
                        <div class="progress-bar" id="progressBar" style="display: none;">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        
                        <button type="submit" name="create_backup" class="btn-primary" id="backupBtn">
                            <i class="fas fa-download"></i>
                            Create Backup
                        </button>
                    </form>
                </div>

                <!-- Backup List -->
                <div class="backup-list">
                    <div class="backup-list-header">
                        <h3>Existing Backups (<?php echo count($backup_files); ?>)</h3>
                    </div>
                    
                    <?php if (!empty($backup_files)): ?>
                        <?php foreach ($backup_files as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <h4><?php echo htmlspecialchars($backup['name']); ?></h4>
                                    <p>
                                        Size: <?php echo number_format($backup['size'] / 1024, 2); ?> KB | 
                                        Created: <?php echo $backup['date']; ?>
                                    </p>
                                </div>
                                <div class="backup-actions">
                                    <a href="../backups/<?php echo urlencode($backup['name']); ?>" class="btn-small btn-download" download>
                                        <i class="fas fa-download"></i>
                                        Download
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this backup?')">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['name']); ?>">
                                        <button type="submit" name="delete_backup" class="btn-small btn-delete">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-database"></i>
                            <p>No backups found. Create your first backup to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Backup Tips -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-lightbulb"></i> Backup Best Practices</h3>
                </div>
                <div class="card-body">
                    <ul style="margin: 0; padding-left: 1.5rem; color: #6b7280;">
                        <li>Create regular backups, especially before major updates or changes</li>
                        <li>Store backups in multiple locations (local and cloud storage)</li>
                        <li>Test backup restoration periodically to ensure data integrity</li>
                        <li>Keep multiple backup versions to protect against corruption</li>
                        <li>Document your backup and restoration procedures</li>
                        <li>Consider automated backup scheduling for production environments</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/admin.js"></script>
    <script>
        document.getElementById('backupForm').addEventListener('submit', function(e) {
            const progressBar = document.getElementById('progressBar');
            const progressFill = document.getElementById('progressFill');
            const backupBtn = document.getElementById('backupBtn');
            
            // Show progress bar
            progressBar.style.display = 'block';
            backupBtn.disabled = true;
            backupBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Backup...';
            
            // Simulate progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress > 90) progress = 90;
                progressFill.style.width = progress + '%';
            }, 100);
            
            // The form will submit normally, and the page will reload
            // In a real application, you might use AJAX for better UX
        });
        
        // Auto-refresh backup list every 30 seconds
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                // In a real application, you would refresh the backup list via AJAX
                console.log('Checking for new backups...');
            }
        }, 30000);
    </script>
</body>
</html>
