<?php
require_once '../auth.php';
requireAdmin();

// Handle contact actions
if ($_POST) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'mark_read':
                    $stmt = $db->prepare("UPDATE contacts SET status = 'read' WHERE id = ?");
                    $stmt->execute([$_POST['contact_id']]);
                    $success = "Contact marked as read.";
                    break;
                    
                case 'mark_responded':
                    $stmt = $db->prepare("UPDATE contacts SET status = 'responded' WHERE id = ?");
                    $stmt->execute([$_POST['contact_id']]);
                    $success = "Contact marked as responded.";
                    break;
                    
                case 'delete':
                    $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
                    $stmt->execute([$_POST['contact_id']]);
                    $success = "Contact deleted successfully.";
                    break;
                    
                case 'bulk_action':
                    if (isset($_POST['contact_ids']) && is_array($_POST['contact_ids'])) {
                        $ids = $_POST['contact_ids'];
                        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                        
                        if ($_POST['bulk_action_type'] === 'mark_read') {
                            $stmt = $db->prepare("UPDATE contacts SET status = 'read' WHERE id IN ($placeholders)");
                            $stmt->execute($ids);
                            $success = count($ids) . " contacts marked as read.";
                        } elseif ($_POST['bulk_action_type'] === 'delete') {
                            $stmt = $db->prepare("DELETE FROM contacts WHERE id IN ($placeholders)");
                            $stmt->execute($ids);
                            $success = count($ids) . " contacts deleted.";
                        }
                    }
                    break;
            }
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get contacts with filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $db = new PDO("mysql:host=localhost;dbname=teachverse", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build query with filters
    $whereConditions = [];
    $params = [];
    
    if ($status_filter) {
        $whereConditions[] = "status = ?";
        $params[] = $status_filter;
    }
    
    if ($priority_filter) {
        $whereConditions[] = "priority = ?";
        $params[] = $priority_filter;
    }
    
    if ($search) {
        $whereConditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM contacts $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalContacts = $stmt->fetchColumn();
    $totalPages = ceil($totalContacts / $limit);
    
    // Get contacts
    $query = "SELECT * FROM contacts $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded_count,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority
    FROM contacts");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - TeachVerse Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/admin-style.css" rel="stylesheet">
    <style>
        .contact-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
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
        
        .contacts-table {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table-header {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .bulk-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .contact-row {
            display: grid;
            grid-template-columns: 40px 200px 200px 300px 100px 120px 80px 80px;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.3s ease;
        }
        
        .contact-row:hover {
            background: #f9fafb;
        }
        
        .contact-row.new {
            background: #eff6ff;
        }
        
        .contact-row.high-priority {
            border-left: 4px solid #ef4444;
        }
        
        .priority-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .priority-high {
            background: #fecaca;
            color: #dc2626;
        }
        
        .priority-medium {
            background: #fef3c7;
            color: #d97706;
        }
        
        .priority-low {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-new {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .status-read {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .status-responded {
            background: #d1fae5;
            color: #059669;
        }
        
        .contact-actions {
            display: flex;
            gap: 0.25rem;
        }
        
        .action-btn {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .action-btn.read {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .action-btn.respond {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .action-btn.delete {
            background: #fecaca;
            color: #dc2626;
        }
        
        .action-btn:hover {
            opacity: 0.8;
        }
        
        .contact-message {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
        }
        
        .pagination .current {
            background: var(--admin-accent);
            color: white;
            border-color: var(--admin-accent);
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
            justify-content: between;
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
                <li class="nav-item active">
                    <a href="contacts.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        Contact Messages
                        <?php if ($stats['new_count'] > 0): ?>
                            <span class="nav-badge"><?php echo $stats['new_count']; ?></span>
                        <?php endif; ?>
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
                <h1 class="page-title">Contact Messages</h1>
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
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Messages</p>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['new_count']; ?></h3>
                        <p>New Messages</p>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['responded_count']; ?></h3>
                        <p>Responded</p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['high_priority']; ?></h3>
                        <p>High Priority</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" class="contact-filters">
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status" class="filter-select">
                        <option value="">All</option>
                        <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="responded" <?php echo $status_filter === 'responded' ? 'selected' : ''; ?>>Responded</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Priority:</label>
                    <select name="priority" class="filter-select">
                        <option value="">All</option>
                        <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search messages..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <button type="submit" class="action-btn read">Filter</button>
                <a href="contacts.php" class="action-btn">Clear</a>
            </form>

            <!-- Contacts Table -->
            <div class="contacts-table">
                <div class="table-header">
                    <h3>Contact Messages (<?php echo $totalContacts; ?>)</h3>
                    <form method="POST" class="bulk-actions" id="bulkForm">
                        <select name="bulk_action_type">
                            <option value="">Bulk Actions</option>
                            <option value="mark_read">Mark as Read</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" name="action" value="bulk_action" onclick="return confirmBulkAction()">Apply</button>
                    </form>
                </div>
                
                <form method="POST" id="contactsForm">
                    <?php if (!empty($contacts)): ?>
                        <?php foreach ($contacts as $contact): ?>
                            <div class="contact-row <?php echo $contact['status']; ?> <?php echo $contact['priority'] === 'high' ? 'high-priority' : ''; ?>">
                                <input type="checkbox" name="contact_ids[]" value="<?php echo $contact['id']; ?>" form="bulkForm">
                                
                                <div>
                                    <strong><?php echo htmlspecialchars($contact['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($contact['email']); ?></small>
                                </div>
                                
                                <div>
                                    <strong><?php echo htmlspecialchars($contact['subject']); ?></strong>
                                </div>
                                
                                <div class="contact-message" title="<?php echo htmlspecialchars($contact['message']); ?>">
                                    <?php echo htmlspecialchars(substr($contact['message'], 0, 100)) . (strlen($contact['message']) > 100 ? '...' : ''); ?>
                                </div>
                                
                                <div>
                                    <span class="priority-badge priority-<?php echo $contact['priority']; ?>">
                                        <?php echo $contact['priority']; ?>
                                    </span>
                                </div>
                                
                                <div>
                                    <span class="status-badge status-<?php echo $contact['status']; ?>">
                                        <?php echo ucfirst($contact['status']); ?>
                                    </span>
                                </div>
                                
                                <div>
                                    <small><?php echo date('M j, Y', strtotime($contact['created_at'])); ?></small>
                                </div>
                                
                                <div class="contact-actions">
                                    <button type="button" onclick="viewContact(<?php echo $contact['id']; ?>)" class="action-btn read" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($contact['status'] === 'new'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                            <button type="submit" class="action-btn read" title="Mark as Read">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($contact['status'] !== 'responded'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_responded">
                                            <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                            <button type="submit" class="action-btn respond" title="Mark as Responded">
                                                <i class="fas fa-reply"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this contact?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                        <button type="submit" class="action-btn delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: #6b7280;">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No contact messages found.</p>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Contact View Modal -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Contact Details</h3>
                <button class="close-modal" onclick="closeContactModal()">&times;</button>
            </div>
            <div id="contactDetails">
                <!-- Contact details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="assets/admin.js"></script>
    <script>
        function viewContact(contactId) {
            // In a real application, this would fetch contact details via AJAX
            document.getElementById('contactModal').classList.add('active');
        }
        
        function closeContactModal() {
            document.getElementById('contactModal').classList.remove('active');
        }
        
        function confirmBulkAction() {
            const selected = document.querySelectorAll('input[name="contact_ids[]"]:checked');
            if (selected.length === 0) {
                alert('Please select contacts to perform bulk action.');
                return false;
            }
            return confirm(`Are you sure you want to perform this action on ${selected.length} contacts?`);
        }
        
        // Close modal when clicking outside
        document.getElementById('contactModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeContactModal();
            }
        });
    </script>
</body>
</html>
