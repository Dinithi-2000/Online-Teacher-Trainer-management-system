<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';
require_once '../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback to form data
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
    } else {
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
    }
    
    // Validate input
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Username and password are required'
        ]);
        exit();
    }
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database connection failed'
        ]);
        exit();
    }
    
    // Find admin user from admins table
    $query = "SELECT id, username, email, password, first_name, last_name, role, status 
              FROM admins 
              WHERE (username = :username OR email = :username) 
              AND status = 'active'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid admin credentials'
        ]);
        exit();
    }
    
    // Verify password
    if (!password_verify($password, $admin['password'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid admin credentials'
        ]);
        exit();
    }
    
    // Start session and store admin data
    session_start();
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['last_activity'] = time();
    
    // Update last login
    $updateQuery = "UPDATE admins SET last_login = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':id', $admin['id']);
    $updateStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Admin login successful',
        'admin' => [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'name' => $admin['first_name'] . ' ' . $admin['last_name'],
            'role' => $admin['role']
        ],
        'redirect' => '../admin-side/index.html'
    ]);
    
} catch (PDOException $e) {
    error_log("Admin Auth Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Admin Auth Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Authentication error occurred'
    ]);
}
?>
