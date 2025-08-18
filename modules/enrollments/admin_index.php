<?php
require_once '../../config/database.php';
requireAdmin(); // Only admins can manage all enrollments

$pdo = getDBConnection();

// Handle search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$course_filter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Build query
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(u.name LIKE ? OR u.email LIKE ? OR c.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_clauses[] = "e.status = ?";
    $params[] = $status_filter;
}

if ($course_filter > 0) {
    $where_clauses[] = "e.course_id = ?";
    $params[] = $course_filter;
}

$where_clause = '';
if (!empty($where_clauses)) {
    $where_clause = "WHERE " . implode(' AND ', $where_clauses);
}

// Get enrollments with user and course information
$sql = "
    SELECT e.*, u.name as user_name, u.email as user_email, 
           c.title as course_title, c.price as course_price,
           t.name as trainer_name
    FROM enrollments e
    JOIN users u ON e.user_id = u.user_id
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN users t ON c.created_by = t.user_id
    $where_clause
    ORDER BY e.enrolled_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_enrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
$active_enrollments = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status IN ('enrolled', 'in_progress')")->fetchColumn();
$completed_enrollments = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'completed'")->fetchColumn();

// Get courses for filter dropdown
$courses_stmt = $pdo->query("SELECT course_id, title FROM courses ORDER BY title");
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Management - TeachVerse Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .header-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #718096;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .filters-section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
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

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .enrollments-table {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .table th {
            background-color: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-enrolled {
            background: #bee3f8;
            color: #2b6cb0;
        }

        .status-in_progress {
            background: #fbb6ce;
            color: #b83280;
        }

        .status-completed {
            background: #c6f6d5;
            color: #2f855a;
        }

        .progress-bar {
            width: 100px;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .no-enrollments {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .no-enrollments i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <h1 class="page-title">Enrollment Management</h1>
            <p class="page-subtitle">Manage all course enrollments across the platform</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_enrollments; ?></div>
                <div class="stat-label">Total Enrollments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_enrollments; ?></div>
                <div class="stat-label">Active Enrollments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $completed_enrollments; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Student name, email, or course...">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="enrolled" <?php echo $status_filter === 'enrolled' ? 'selected' : ''; ?>>Enrolled</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select id="course_id" name="course_id">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" 
                                    <?php echo $course_filter === $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
                <div class="form-group">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Enrollments Table -->
        <div class="enrollments-table">
            <?php if (!empty($enrollments)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Trainer</th>
                            <th>Enrolled Date</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($enrollment['user_name']); ?></div>
                                        <div style="font-size: 0.875rem; color: #718096;"><?php echo htmlspecialchars($enrollment['user_email']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($enrollment['course_title']); ?></div>
                                    <div style="font-size: 0.875rem; color: #718096;">$<?php echo number_format($enrollment['course_price'], 2); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($enrollment['trainer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($enrollment['enrolled_at'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $enrollment['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $enrollment['progress']; ?>%"></div>
                                    </div>
                                    <small><?php echo $enrollment['progress']; ?>%</small>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="update_progress.php?id=<?php echo $enrollment['enrollment_id']; ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="unenroll.php?id=<?php echo $enrollment['enrollment_id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to unenroll this student?')">
                                            <i class="fas fa-user-times"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-enrollments">
                    <i class="fas fa-user-graduate"></i>
                    <h3>No enrollments found</h3>
                    <p>No enrollments match your current filters.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Back to Admin -->
        <div style="margin-top: 2rem; text-align: center;">
            <a href="../../admin/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
            </a>
        </div>
    </div>
</body>
</html>
