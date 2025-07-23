<?php
require_once '../../config/database.php';
requireAdmin(); // Only admins can manage users

$pdo = getDBConnection();

// Handle search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';

// Build query
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = '';
if (!empty($where_clauses)) {
    $where_clause = "WHERE " . implode(' AND ', $where_clauses);
}

$sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_trainers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainer'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - TeachVerse</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <a href="../../index.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-graduation-cap"></i> TeachVerse
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="../../index.php">Home</a></li>
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <li><a href="../../admin/index.php">Admin Panel</a></li>
                <?php if (isLoggedIn()): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="../../profile.php"><i class="fas fa-user-edit"></i> Profile</a>
                            <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">
                <i class="fas fa-users"></i> User Management
            </h1>
            <p class="page-subtitle">Manage all platform users</p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Statistics Cards -->
            <div class="grid grid-3 mb-4">
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem; color: var(--primary-color);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem; color: var(--primary-color);"><?php echo $total_users; ?></h3>
                            <p style="margin: 0; color: var(--text-secondary);">Total Users</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem; color: var(--success-color);">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem; color: var(--success-color);"><?php echo $total_students; ?></h3>
                            <p style="margin: 0; color: var(--text-secondary);">Students</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem; color: var(--warning-color);">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem; color: var(--warning-color);"><?php echo $total_trainers; ?></h3>
                            <p style="margin: 0; color: var(--text-secondary);">Trainers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="search-filter">
                <form method="GET" class="grid grid-4">
                    <div class="form-group">
                        <label class="form-label">Search Users</label>
                        <input type="text" name="search" class="form-input" 
                               placeholder="Search by name or email..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Filter by Role</label>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                            <option value="trainer" <?php echo $role_filter === 'trainer' ? 'selected' : ''; ?>>Trainers</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <a href="create.php" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Add User
                        </a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Last Active</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--gradient); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h4 style="margin: 0; font-size: 1rem;"><?php echo htmlspecialchars($user['name']); ?></h4>
                                            <p style="margin: 0; font-size: 0.75rem; color: var(--text-secondary);">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'trainer' ? 'warning' : 'primary'); ?>">
                                        <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'crown' : ($user['role'] === 'trainer' ? 'chalkboard-teacher' : 'user-graduate'); ?>"></i>
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <span style="color: var(--text-secondary);">
                                        <?php echo formatDate($user['updated_at']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <a href="view.php?id=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-secondary"
                                           data-tooltip="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-warning"
                                           data-tooltip="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <button onclick="deleteItem('delete.php?id=<?php echo $user['user_id']; ?>', 'user')" 
                                                    class="btn btn-sm btn-danger"
                                                    data-tooltip="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($users)): ?>
                    <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>No users found</h3>
                        <p>No users match your search criteria.</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add First User
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
