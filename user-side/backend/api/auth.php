<?php
require_once '../config.php';
require_once '../database.php';

header('Content-Type: application/json');

class AdminAuth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            switch ($method) {
                case 'POST':
                    if (isset($_POST['username']) && isset($_POST['password']) && !isset($_POST['firstName'])) {
                        return $this->login();
                    } elseif (isset($_POST['firstName'])) {
                        return $this->signup();
                    } elseif ($action === 'logout') {
                        return $this->logout();
                    }
                    break;
                case 'GET':
                    if ($action === 'check-session') {
                        return $this->checkSession();
                    }
                    break;
            }
            
            return $this->sendResponse(false, 'Invalid request');
            
        } catch (Exception $e) {
            return $this->sendResponse(false, 'Server error: ' . $e->getMessage());
        }
    }
    
    private function login() {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);
        
        // Input validation
        if (empty($username) || empty($password)) {
            return $this->sendResponse(false, 'Username and password are required');
        }
        
        try {
            // Check if user exists and is admin
            $query = "SELECT id, username, email, password, first_name, last_name, role, status 
                     FROM users 
                     WHERE (username = :username OR email = :username) 
                     AND role = 'admin' AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Log failed login attempt
                error_log("Failed admin login attempt for: " . $username);
                return $this->sendResponse(false, 'Invalid credentials or access denied');
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                // Log failed password attempt
                error_log("Failed password verification for admin: " . $username);
                return $this->sendResponse(false, 'Invalid credentials');
            }
            
            // Create session
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['admin_login_time'] = time();
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                setcookie('admin_remember_token', $token, $expires, '/', '', true, true);
                
                // Store hashed token in database
                $hashedToken = password_hash($token, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET remember_token = :token WHERE id = :id";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindParam(':token', $hashedToken);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
            }
            
            // Update last login timestamp
            $updateLastLogin = "UPDATE users SET updated_at = NOW() WHERE id = :id";
            $updateStmt = $this->db->prepare($updateLastLogin);
            $updateStmt->bindParam(':id', $user['id']);
            $updateStmt->execute();
            
            // Log successful login
            error_log("Successful admin login for: " . $user['username']);
            
            return $this->sendResponse(true, 'Login successful', [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'role' => $user['role']
                ]
            ]);
            
        } catch (PDOException $e) {
            error_log("Database error in admin login: " . $e->getMessage());
            return $this->sendResponse(false, 'Database error occurred');
        }
    }
    
    private function signup() {
        $firstName = trim($_POST['firstName']);
        $lastName = trim($_POST['lastName']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $department = $_POST['department'];
        $justification = trim($_POST['justification']);
        
        // Comprehensive validation
        if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password) || empty($department) || empty($justification)) {
            return $this->sendResponse(false, 'All fields are required');
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->sendResponse(false, 'Invalid email format');
        }
        
        // Validate username format (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            return $this->sendResponse(false, 'Username must be 3-50 characters and contain only letters, numbers, and underscores');
        }
        
        // Validate password strength
        if (strlen($password) < 8) {
            return $this->sendResponse(false, 'Password must be at least 8 characters long');
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            return $this->sendResponse(false, 'Password must contain at least one uppercase letter, one lowercase letter, and one number');
        }
        
        // Validate justification length
        if (strlen($justification) < 50) {
            return $this->sendResponse(false, 'Justification must be at least 50 characters long');
        }
        
        try {
            // Check if username or email already exists
            $checkQuery = "SELECT id, username, email, status FROM users WHERE username = :username OR email = :email";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':username', $username);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();
            
            $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingUser) {
                if ($existingUser['username'] === $username) {
                    return $this->sendResponse(false, 'Username already exists');
                } else {
                    return $this->sendResponse(false, 'Email address already exists');
                }
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with 'inactive' status for admin approval
            $insertQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, status, bio, created_at) 
                           VALUES (:username, :email, :password, :firstName, :lastName, 'admin', 'inactive', :justification, NOW())";
            
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bindParam(':username', $username);
            $insertStmt->bindParam(':email', $email);
            $insertStmt->bindParam(':password', $hashedPassword);
            $insertStmt->bindParam(':firstName', $firstName);
            $insertStmt->bindParam(':lastName', $lastName);
            $insertStmt->bindParam(':justification', $justification);
            
            if ($insertStmt->execute()) {
                $userId = $this->db->lastInsertId();
                
                // Log the admin access request
                $this->logAdminRequest($userId, $department, $justification);
                
                // Log successful signup
                error_log("New admin access request from: " . $email);
                
                return $this->sendResponse(true, 'Admin access request submitted successfully. You will be notified via email once approved.');
            } else {
                error_log("Failed to insert new admin request for: " . $email);
                return $this->sendResponse(false, 'Failed to submit request. Please try again.');
            }
            
        } catch (PDOException $e) {
            error_log("Database error in admin signup: " . $e->getMessage());
            return $this->sendResponse(false, 'Database error occurred');
        }
    }
    
    private function logout() {
        // Clear session
        session_unset();
        session_destroy();
        
        // Clear remember me cookie
        setcookie('admin_remember_token', '', time() - 3600, '/', '', true, true);
        
        return $this->sendResponse(true, 'Logged out successfully');
    }
    
    private function checkSession() {
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            return $this->sendResponse(true, 'Session active', [
                'user' => [
                    'id' => $_SESSION['admin_user_id'],
                    'username' => $_SESSION['admin_username'],
                    'email' => $_SESSION['admin_email'],
                    'name' => $_SESSION['admin_name'],
                    'role' => $_SESSION['admin_role']
                ]
            ]);
        }
        
        return $this->sendResponse(false, 'No active session');
    }
    
    private function logAdminRequest($userId, $department, $justification) {
        try {
            // Create admin_requests table if it doesn't exist
            $createTable = "CREATE TABLE IF NOT EXISTS admin_requests (
                id INT(11) NOT NULL AUTO_INCREMENT,
                user_id INT(11) NOT NULL,
                department VARCHAR(100) NOT NULL,
                justification TEXT NOT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reviewed_at TIMESTAMP NULL,
                reviewed_by INT(11) NULL,
                notes TEXT,
                PRIMARY KEY (id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            
            $this->db->exec($createTable);
            
            // Insert request
            $insertRequest = "INSERT INTO admin_requests (user_id, department, justification) 
                             VALUES (:userId, :department, :justification)";
            
            $stmt = $this->db->prepare($insertRequest);
            $stmt->bindParam(':userId', $userId);
            $stmt->bindParam(':department', $department);
            $stmt->bindParam(':justification', $justification);
            $stmt->execute();
            
        } catch (PDOException $e) {
            // Log error but don't fail the signup process
            error_log("Failed to log admin request: " . $e->getMessage());
        }
    }
    
    private function sendResponse($success, $message, $data = null) {
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        return $response;
    }
}

// Initialize and handle request
$auth = new AdminAuth();
$auth->handleRequest();
?>
