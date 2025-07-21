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

class UserRegistration {
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
            return $this->register();
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return $this->sendResponse(false, 'Registration failed. Please try again.');
        }
    }
    
    private function register() {
        // Get and sanitize input data
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = $this->generateUsername($firstName, $lastName);
        
        // Additional fields from multi-step form
        $experience = trim($_POST['experience'] ?? 'beginner');
        $subjects = trim($_POST['subjects'] ?? '');
        $goals = trim($_POST['goals'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        // Validate required fields
        $validation = $this->validateInput($firstName, $lastName, $email, $password);
        if (!$validation['valid']) {
            return $this->sendResponse(false, $validation['message'], $validation['errors']);
        }
        
        // Check if user already exists
        if ($this->userExists($email, $username)) {
            return $this->sendResponse(false, 'An account with this email already exists');
        }
        
        // Create user account
        $userId = $this->createUser([
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => 'student',
            'status' => 'active',
            'bio' => $this->generateBio($firstName, $experience, $subjects, $goals, $bio)
        ]);
        
        if ($userId) {
            // Log the registration
            error_log("New user registered: $email (ID: $userId)");
            
            // Create session for auto-login
            session_regenerate_id(true);
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            $_SESSION['user_role'] = 'student';
            $_SESSION['login_time'] = time();
            
            return $this->sendResponse(true, 'Registration successful! Welcome to EduMentor Pro!', [
                'user_id' => $userId,
                'username' => $username,
                'email' => $email,
                'name' => $firstName . ' ' . $lastName,
                'redirect' => '../user-side/courses.html'
            ]);
        } else {
            return $this->sendResponse(false, 'Registration failed. Please try again.');
        }
    }
    
    private function validateInput($firstName, $lastName, $email, $password) {
        $errors = [];
        
        // First name validation
        if (empty($firstName)) {
            $errors['firstName'] = 'First name is required';
        } elseif (strlen($firstName) < 2 || strlen($firstName) > 50) {
            $errors['firstName'] = 'First name must be between 2 and 50 characters';
        } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $firstName)) {
            $errors['firstName'] = 'First name contains invalid characters';
        }
        
        // Last name validation
        if (empty($lastName)) {
            $errors['lastName'] = 'Last name is required';
        } elseif (strlen($lastName) < 2 || strlen($lastName) > 50) {
            $errors['lastName'] = 'Last name must be between 2 and 50 characters';
        } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $lastName)) {
            $errors['lastName'] = 'Last name contains invalid characters';
        }
        
        // Email validation
        if (empty($email)) {
            $errors['email'] = 'Email address is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } elseif (strlen($email) > 100) {
            $errors['email'] = 'Email address is too long';
        }
        
        // Password validation
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
        }
        
        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Valid' : 'Please correct the errors below',
            'errors' => $errors
        ];
    }
    
    private function userExists($email, $username) {
        $query = "SELECT id FROM users WHERE email = :email OR username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }
    
    private function generateUsername($firstName, $lastName) {
        $baseUsername = strtolower(substr($firstName, 0, 1) . $lastName);
        $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
        
        // Check if username exists and add number if needed
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->usernameExists($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    private function usernameExists($username) {
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }
    
    private function generateBio($firstName, $experience, $subjects, $goals, $customBio) {
        if (!empty($customBio)) {
            return $customBio;
        }
        
        $bio = "Hi, I'm $firstName! ";
        
        if (!empty($experience) && $experience !== 'beginner') {
            $bio .= "I have $experience level experience in education. ";
        } else {
            $bio .= "I'm excited to start my teaching journey. ";
        }
        
        if (!empty($subjects)) {
            $bio .= "I'm interested in $subjects. ";
        }
        
        if (!empty($goals)) {
            $bio .= "My goal is to $goals. ";
        }
        
        $bio .= "I'm looking forward to learning and growing with EduMentor Pro!";
        
        return $bio;
    }
    
    private function createUser($userData) {
        $query = "INSERT INTO users (username, email, password, first_name, last_name, role, status, bio, created_at) 
                  VALUES (:username, :email, :password, :first_name, :last_name, :role, :status, :bio, NOW())";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $userData['username']);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->bindParam(':password', $userData['password']);
        $stmt->bindParam(':first_name', $userData['first_name']);
        $stmt->bindParam(':last_name', $userData['last_name']);
        $stmt->bindParam(':role', $userData['role']);
        $stmt->bindParam(':status', $userData['status']);
        $stmt->bindParam(':bio', $userData['bio']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
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
$registration = new UserRegistration();
$registration->handleRequest();
?>
