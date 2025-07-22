<?php
require_once '../config.php';
require_once '../database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class UserLogin {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendResponse(false, 'Only POST method allowed');
        }
        
        try {
            return $this->login();
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return $this->sendResponse(false, 'Login failed. Please try again.');
        }
    }
    
    private function login() {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Input validation
        if (empty($email) || empty($password)) {
            return $this->sendResponse(false, 'Email and password are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->sendResponse(false, 'Please enter a valid email address');
        }
        
        try {
            // Check if user exists
            $query = "SELECT id, username, email, password, first_name, last_name, role, status 
                     FROM users 
                     WHERE email = :email AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Log failed login attempt
                error_log("Failed login attempt for: " . $email);
                return $this->sendResponse(false, 'Invalid email or password');
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                // Log failed password attempt
                error_log("Failed password verification for: " . $email);
                return $this->sendResponse(false, 'Invalid email or password');
            }
            
            // Create session
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                setcookie('user_remember_token', $token, $expires, '/', '', true, true);
                
                // Store hashed token in database
                $hashedToken = password_hash($token, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET remember_token = :token WHERE id = :id";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindParam(':token', $hashedToken);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
            }
            
            // Update last login time
            $updateLoginQuery = "UPDATE users SET updated_at = NOW() WHERE id = :id";
            $updateLoginStmt = $this->db->prepare($updateLoginQuery);
            $updateLoginStmt->bindParam(':id', $user['id']);
            $updateLoginStmt->execute();
            
            // Log successful login
            error_log("Successful login for: " . $email . " (ID: " . $user['id'] . ")");
            
            // Determine redirect based on role
            $redirectUrl = 'courses.html';
            if ($user['role'] === 'admin') {
                $redirectUrl = '../admin-side/index.html';
            } elseif ($user['role'] === 'trainer') {
                $redirectUrl = 'trainers.html';
            }
            
            return $this->sendResponse(true, 'Login successful! Welcome back!', [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => $user['role'],
                'redirect' => $redirectUrl
            ]);
            
        } catch (PDOException $e) {
            error_log("Database error during login: " . $e->getMessage());
            return $this->sendResponse(false, 'Login failed. Please try again.');
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
        exit;
    }
}

// Handle the request
$login = new UserLogin();
$login->handleRequest();
?>
