<?php
/**
 * Users Management API
 * Handles all user-related CRUD operations for EduMentor Pro
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';
require_once '../database.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $action, $id);
            break;
        case 'POST':
            handlePostRequest($conn, $action);
            break;
        case 'PUT':
            handlePutRequest($conn, $action, $id);
            break;
        case 'DELETE':
            handleDeleteRequest($conn, $action, $id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($conn, $action, $id) {
    switch ($action) {
        case 'list':
            getAllUsers($conn);
            break;
        case 'get':
            if ($id) {
                getUserById($conn, $id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
            }
            break;
        case 'stats':
            getUserStats($conn);
            break;
        case 'search':
            searchUsers($conn);
            break;
        default:
            getAllUsers($conn);
            break;
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($conn, $action) {
    switch ($action) {
        case 'create':
            createUser($conn);
            break;
        case 'bulk-action':
            handleBulkAction($conn);
            break;
        default:
            createUser($conn);
            break;
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($conn, $action, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    switch ($action) {
        case 'update':
            updateUser($conn, $id);
            break;
        case 'status':
            updateUserStatus($conn, $id);
            break;
        case 'role':
            updateUserRole($conn, $id);
            break;
        default:
            updateUser($conn, $id);
            break;
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($conn, $action, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    switch ($action) {
        case 'delete':
            deleteUser($conn, $id);
            break;
        default:
            deleteUser($conn, $id);
            break;
    }
}

/**
 * Get all users with filtering and pagination
 */
function getAllUsers($conn) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    $role = isset($_GET['role']) ? $_GET['role'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($role)) {
        $where_conditions[] = "role = ?";
        $params[] = $role;
    }
    
    if (!empty($status)) {
        $where_conditions[] = "status = ?";
        $params[] = $status;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM users $where_clause";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get users
    $query = "SELECT id, username, email, first_name, last_name, role, profile_image, status, created_at, updated_at 
              FROM users $where_clause 
              ORDER BY created_at DESC 
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $users,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get user by ID
 */
function getUserById($conn, $id) {
    $query = "SELECT id, username, email, first_name, last_name, role, profile_image, bio, status, created_at, updated_at 
              FROM users WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

/**
 * Create new user
 */
function createUser($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['username', 'email', 'password', 'first_name', 'last_name'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    // Validate email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Check if username or email already exists
    $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$input['username'], $input['email']]);
    
    if ($check_stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        return;
    }
    
    // Set default values
    $role = $input['role'] ?? 'student';
    $status = $input['status'] ?? 'active';
    $bio = $input['bio'] ?? '';
    $profile_image = $input['profile_image'] ?? '';
    
    // Hash password
    $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (username, email, password, first_name, last_name, role, bio, profile_image, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        $input['username'],
        $input['email'],
        $hashed_password,
        $input['first_name'],
        $input['last_name'],
        $role,
        $bio,
        $profile_image,
        $status
    ]);
    
    if ($result) {
        $user_id = $conn->lastInsertId();
        
        // Get the created user
        $get_query = "SELECT id, username, email, first_name, last_name, role, status, created_at FROM users WHERE id = ?";
        $get_stmt = $conn->prepare($get_query);
        $get_stmt->execute([$user_id]);
        $user = $get_stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create user']);
    }
}

/**
 * Update user
 */
function updateUser($conn, $id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if user exists
    $check_query = "SELECT id FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$id]);
    
    if (!$check_stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    // Build update query dynamically
    $fields = [];
    $params = [];
    
    $allowed_fields = ['username', 'email', 'first_name', 'last_name', 'role', 'bio', 'profile_image', 'status'];
    
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    // Handle password update
    if (!empty($input['password'])) {
        $fields[] = "password = ?";
        $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
    }
    
    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        return;
    }
    
    // Check for duplicate username/email (excluding current user)
    if (isset($input['username']) || isset($input['email'])) {
        $duplicate_conditions = [];
        $duplicate_params = [];
        
        if (isset($input['username'])) {
            $duplicate_conditions[] = "username = ?";
            $duplicate_params[] = $input['username'];
        }
        
        if (isset($input['email'])) {
            $duplicate_conditions[] = "email = ?";
            $duplicate_params[] = $input['email'];
        }
        
        $duplicate_params[] = $id;
        
        $duplicate_query = "SELECT id FROM users WHERE (" . implode(' OR ', $duplicate_conditions) . ") AND id != ?";
        $duplicate_stmt = $conn->prepare($duplicate_query);
        $duplicate_stmt->execute($duplicate_params);
        
        if ($duplicate_stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            return;
        }
    }
    
    $params[] = $id;
    $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Get updated user
        $get_query = "SELECT id, username, email, first_name, last_name, role, status, updated_at FROM users WHERE id = ?";
        $get_stmt = $conn->prepare($get_query);
        $get_stmt->execute([$id]);
        $user = $get_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
}

/**
 * Update user status
 */
function updateUserStatus($conn, $id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Status is required']);
        return;
    }
    
    $allowed_statuses = ['active', 'inactive', 'pending'];
    if (!in_array($input['status'], $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        return;
    }
    
    $query = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$input['status'], $id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'User status updated successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found or no changes made']);
    }
}

/**
 * Delete user
 */
function deleteUser($conn, $id) {
    // Check if user exists
    $check_query = "SELECT id, role FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$id]);
    $user = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    // Prevent deletion of admin users (optional safety check)
    if ($user['role'] === 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Cannot delete admin users']);
        return;
    }
    
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
}

/**
 * Get user statistics
 */
function getUserStats($conn) {
    $stats = [];
    
    // Total users
    $total_query = "SELECT COUNT(*) as total FROM users";
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->execute();
    $stats['total_users'] = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Users by role
    $role_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
    $role_stmt = $conn->prepare($role_query);
    $role_stmt->execute();
    $roles = $role_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats['by_role'] = [];
    foreach ($roles as $role) {
        $stats['by_role'][$role['role']] = $role['count'];
    }
    
    // Users by status
    $status_query = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->execute();
    $statuses = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats['by_status'] = [];
    foreach ($statuses as $status) {
        $stats['by_status'][$status['status']] = $status['count'];
    }
    
    // Recent registrations (last 30 days)
    $recent_query = "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $recent_stmt = $conn->prepare($recent_query);
    $recent_stmt->execute();
    $stats['recent_registrations'] = $recent_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

/**
 * Search users
 */
function searchUsers($conn) {
    $search = $_GET['q'] ?? '';
    
    if (empty($search)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Search query is required']);
        return;
    }
    
    $query = "SELECT id, username, email, first_name, last_name, role, status 
              FROM users 
              WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?
              ORDER BY first_name, last_name
              LIMIT 20";
    
    $search_term = "%$search%";
    $stmt = $conn->prepare($query);
    $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
}

/**
 * Handle bulk actions
 */
function handleBulkAction($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action']) || !isset($input['user_ids']) || !is_array($input['user_ids'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid bulk action request']);
        return;
    }
    
    $action = $input['action'];
    $user_ids = $input['user_ids'];
    $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
    
    switch ($action) {
        case 'activate':
            $query = "UPDATE users SET status = 'active' WHERE id IN ($placeholders)";
            break;
        case 'deactivate':
            $query = "UPDATE users SET status = 'inactive' WHERE id IN ($placeholders)";
            break;
        case 'delete':
            $query = "DELETE FROM users WHERE id IN ($placeholders) AND role != 'admin'";
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid bulk action']);
            return;
    }
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute($user_ids);
    
    if ($result) {
        $affected_rows = $stmt->rowCount();
        echo json_encode([
            'success' => true,
            'message' => "Bulk action completed. $affected_rows users affected."
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to execute bulk action']);
    }
}
?>
