<?php
require_once '../auth.php';
requireAdmin();

// Handle log actions
if ($_POST) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create logs table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level ENUM('INFO', 'WARNING', 'ERROR', 'DEBUG') DEFAULT 'INFO',
            category VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            user_id INT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");
        
        if (isset($_POST['clear_logs'])) {
            $days = (int)$_POST['days'];
            if ($days > 0) {
                $stmt = $db->prepare("DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$days]);
                $success = "Logs older than $days days have been cleared.";
            }
        }
        
        if (isset($_POST['add_log'])) {
            $stmt = $db->prepare("INSERT INTO system_logs (level, category, message, user_id, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['level'],
                $_POST['category'],
                $_POST['message'],
                $_SESSION['user_id'],
                $_SERVER['REMOTE_ADDR']
            ]);
            $success = "Log entry added successfully.";
        }
        
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Add some sample logs if table is empty
try {
    $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->query("SELECT COUNT(*) FROM system_logs");
    if ($stmt->fetchColumn() == 0) {
        $sample_logs = [
            ['INFO', 'Authentication', 'User logged in successfully', 'LOGIN'],
            ['INFO', 'Course', 'New course created: "Advanced Teaching Methods"', 'CREATE'],
            ['WARNING', 'Security', 'Failed login attempt detected', 'SECURITY'],
            ['INFO', 'User', 'New user registration completed', 'REGISTRATION'],
            ['INFO', 'System', 'Database backup completed successfully', 'BACKUP'],
            ['ERROR', 'Email', 'Failed to send email notification', 'EMAIL'],
            ['INFO', 'Review', 'New review submitted for course', 'REVIEW'],
            ['DEBUG', 'System', 'Memory usage: 64MB', 'PERFORMANCE'],
            ['INFO', 'Enrollment', 'Student enrolled in course', 'ENROLLMENT'],
            ['WARNING', 'System', 'High CPU usage detected', 'PERFORMANCE']
        ];
        
        foreach ($sample_logs as $log) {
            $stmt = $db->prepare("INSERT INTO system_logs (level, category, message, ip_address, created_at) VALUES (?, ?, ?, '127.0.0.1', DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY))");
            $stmt->execute($log);
        }
    }
} catch(PDOException $e) {
    // Ignore errors for sample data
}

// Get filters
$level_filter = isset($_GET['level']) ? $_GET['level'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build query with filters
    $whereConditions = [];
    $params = [];
    
    if ($level_filter) {
        $whereConditions[] = "level = ?";
        $params[] = $level_filter;
    }
    
    if ($category_filter) {
        $whereConditions[] = "category = ?";
        $params[] = $category_filter;
    }
    
    if ($search) {
        $whereConditions[] = "(message LIKE ? OR category LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm]);
    }
    
    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM system_logs $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalLogs = $stmt->fetchColumn();
    $totalPages = ceil($totalLogs / $limit);
    
    // Get logs
    $query = "SELECT sl.*, u.username 
              FROM system_logs sl 
              LEFT JOIN users u ON sl.user_id = u.id 
              $whereClause 
              ORDER BY sl.created_at DESC 
              LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN level = 'ERROR' THEN 1 ELSE 0 END) as errors,
        SUM(CASE WHEN level = 'WARNING' THEN 1 ELSE 0 END) as warnings,
        SUM(CASE WHEN level = 'INFO' THEN 1 ELSE 0 END) as info,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as today
    FROM system_logs");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get categories
    $stmt = $db->query("SELECT DISTINCT category FROM system_logs ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
    $logs = [];
    $stats = ['total' => 0, 'errors' => 0, 'warnings' => 0, 'info' => 0, 'today' => 0];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - TeachVerse Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/admin-style.css" rel="stylesheet">
    <style>
        .logs-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 1rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-select, .search-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .search-input {
            min-width: 250px;
        }
        
        .logs-table {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 80px 100px 150px 1fr 150px 150px 80px;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: var(--admin-primary);
            font-size: 0.875rem;
        }
        
        .log-row {
            display: grid;
            grid-template-columns: 80px 100px 150px 1fr 150px 150px 80px;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.3s ease;
            font-size: 0.875rem;
        }
        
        .log-row:hover {
            background: #f9fafb;
        }
        
        .log-level {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
            text-transform: uppercase;
        }
        
        .level-info {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .level-warning {
            background: #fef3c7;
            color: #d97706;
        }
        
        .level-error {
            background: #fecaca;
            color: #dc2626;
        }
        
        .level-debug {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .log-message {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .log-user {
            font-weight: 500;
            color: var(--admin-accent);
        }
        
        .log-time {
            color: #6b7280;
            font-size: 0.75rem;
        }
        
        .view-log-btn {
            padding: 0.25rem 0.5rem;
            background: #f3f4f6;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            color: #374151;
            font-size: 0.75rem;
        }
        
        .view-log-btn:hover {
            background: #e5e7eb;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .pagination a, .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            text-decoration: none;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .pagination .current {
            background: var(--admin-accent);
            color: white;
            border-color: var(--admin-accent);
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .quick-action {
            padding: 0.75rem 1rem;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .quick-action:hover {
            background: #f3f4f6;
        }
        
        .quick-action.danger {
            border-color: #fecaca;
            color: #dc2626;
        }
        
        .quick-action.danger:hover {
            background: #fef2f2;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }
        
        .btn-primary {
            background: var(--admin-accent);
            color: white;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
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
                <li class="nav-item">
                    <a href="backup.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        Backup
                    </a>
                </li>
                <li class="nav-item active">
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
                <h1 class="page-title">System Logs</h1>
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

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Logs</p>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['errors']; ?></h3>
                        <p>Errors</p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['warnings']; ?></h3>
                        <p>Warnings</p>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['today']; ?></h3>
                        <p>Today's Logs</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <button class="quick-action" onclick="openAddLogModal()">
                    <i class="fas fa-plus"></i>
                    Add Log Entry
                </button>
                <button class="quick-action danger" onclick="openClearLogsModal()">
                    <i class="fas fa-trash"></i>
                    Clear Old Logs
                </button>
                <button class="quick-action" onclick="exportLogs()">
                    <i class="fas fa-download"></i>
                    Export Logs
                </button>
            </div>

            <!-- Filters -->
            <form method="GET" class="logs-filters">
                <div class="filter-group">
                    <label>Level:</label>
                    <select name="level" class="filter-select">
                        <option value="">All Levels</option>
                        <option value="INFO" <?php echo $level_filter === 'INFO' ? 'selected' : ''; ?>>Info</option>
                        <option value="WARNING" <?php echo $level_filter === 'WARNING' ? 'selected' : ''; ?>>Warning</option>
                        <option value="ERROR" <?php echo $level_filter === 'ERROR' ? 'selected' : ''; ?>>Error</option>
                        <option value="DEBUG" <?php echo $level_filter === 'DEBUG' ? 'selected' : ''; ?>>Debug</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Category:</label>
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search logs..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <button type="submit" class="quick-action">Filter</button>
                <a href="logs.php" class="quick-action">Clear</a>
            </form>

            <!-- Logs Table -->
            <div class="logs-table">
                <div class="table-header">
                    <div>Level</div>
                    <div>Category</div>
                    <div>User</div>
                    <div>Message</div>
                    <div>IP Address</div>
                    <div>Time</div>
                    <div>Action</div>
                </div>
                
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-row">
                            <div>
                                <span class="log-level level-<?php echo strtolower($log['level']); ?>">
                                    <?php echo $log['level']; ?>
                                </span>
                            </div>
                            
                            <div><?php echo htmlspecialchars($log['category']); ?></div>
                            
                            <div class="log-user">
                                <?php echo $log['username'] ? htmlspecialchars($log['username']) : 'System'; ?>
                            </div>
                            
                            <div class="log-message" title="<?php echo htmlspecialchars($log['message']); ?>">
                                <?php echo htmlspecialchars(substr($log['message'], 0, 100)) . (strlen($log['message']) > 100 ? '...' : ''); ?>
                            </div>
                            
                            <div><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></div>
                            
                            <div class="log-time">
                                <?php echo date('M j, Y H:i', strtotime($log['created_at'])); ?>
                            </div>
                            
                            <div>
                                <button class="view-log-btn" onclick="viewLog(<?php echo $log['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; color: #6b7280;">
                        <i class="fas fa-list-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No logs found.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&level=<?php echo $level_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&level=<?php echo $level_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page+1; ?>&level=<?php echo $level_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Log Modal -->
    <div id="addLogModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Log Entry</h3>
                <button class="close-modal" onclick="closeModal('addLogModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-select" required>
                        <option value="INFO">Info</option>
                        <option value="WARNING">Warning</option>
                        <option value="ERROR">Error</option>
                        <option value="DEBUG">Debug</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-textarea" rows="4" required></textarea>
                </div>
                <button type="submit" name="add_log" class="btn-primary">Add Log Entry</button>
            </form>
        </div>
    </div>

    <!-- Clear Logs Modal -->
    <div id="clearLogsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Clear Old Logs</h3>
                <button class="close-modal" onclick="closeModal('clearLogsModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Clear logs older than (days)</label>
                    <input type="number" name="days" class="form-input" min="1" value="30" required>
                    <small style="color: #6b7280;">This action cannot be undone</small>
                </div>
                <button type="submit" name="clear_logs" class="btn-primary" onclick="return confirm('Are you sure you want to clear old logs?')">Clear Logs</button>
            </form>
        </div>
    </div>

    <script src="assets/admin.js"></script>
    <script>
        function openAddLogModal() {
            document.getElementById('addLogModal').classList.add('active');
        }
        
        function openClearLogsModal() {
            document.getElementById('clearLogsModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function viewLog(logId) {
            // In a real application, this would show detailed log information
            alert('Viewing log ID: ' + logId);
        }
        
        function exportLogs() {
            // In a real application, this would export logs to CSV or JSON
            alert('Export functionality would be implemented here');
        }
        
        // Close modals when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Auto-refresh logs every 30 seconds
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                // In a real application, you would refresh the logs via AJAX
                console.log('Checking for new logs...');
            }
        }, 30000);
    </script>
</body>
</html>
