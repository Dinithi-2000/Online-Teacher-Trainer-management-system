<?php
require_once '../../config/database.php';

$pdo = getDBConnection();

// Handle search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) ? sanitize($_GET['order']) : 'DESC';

// Build query
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE title LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$sql = "SELECT c.*, u.name as creator_name 
        FROM courses c 
        LEFT JOIN users u ON c.created_by = u.user_id 
        $where_clause 
        ORDER BY $sort $order";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - TeachVerse</title>
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
                <li><a href="../../courses.php">Courses</a></li>
                <li><a href="../../dashboard.php">Dashboard</a></li>
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
                <i class="fas fa-book"></i> Course Management
            </h1>
            <p class="page-subtitle">Manage all training courses</p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Search and Filter -->
            <div class="search-filter">
                <div class="grid grid-3">
                    <div class="form-group">
                        <label class="form-label">Search Courses</label>
                        <input type="text" 
                               class="form-input search-input" 
                               data-target=".course-row"
                               placeholder="Search by title or description..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sort By</label>
                        <select class="form-select" onchange="updateSort(this.value)">
                            <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date Created</option>
                            <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title</option>
                            <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Actions</label>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Course
                        </a>
                    </div>
                </div>
            </div>

            <!-- Courses Table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Creator</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr class="course-row">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <img src="../../assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($course['title']); ?>"
                                             style="width: 60px; height: 40px; object-fit: cover; border-radius: 0.5rem;"
                                             onerror="this.src='../../assets/images/default-course.jpg'">
                                        <div>
                                            <h4 style="margin: 0; font-size: 1rem;"><?php echo htmlspecialchars($course['title']); ?></h4>
                                            <p style="margin: 0; font-size: 0.75rem; color: var(--text-secondary);">
                                                <?php echo htmlspecialchars(substr($course['description'], 0, 60)) . '...'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($course['creator_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($course['duration']); ?></td>
                                <td><?php echo formatPrice($course['price']); ?></td>
                                <td><?php echo formatDate($course['created_at']); ?></td>
                                <td>
                                    <div class="flex gap-2">
                                        <a href="../../course-details.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-sm btn-secondary"
                                           data-tooltip="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-sm btn-warning"
                                           data-tooltip="Edit Course">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteItem('delete.php?id=<?php echo $course['course_id']; ?>', 'course')" 
                                                class="btn btn-sm btn-danger"
                                                data-tooltip="Delete Course">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($courses)): ?>
                    <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                        <i class="fas fa-book" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>No courses found</h3>
                        <p>No courses match your search criteria.</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create First Course
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        function updateSort(sortBy) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', sortBy);
            window.location.search = urlParams.toString();
        }
    </script>
</body>
</html>
