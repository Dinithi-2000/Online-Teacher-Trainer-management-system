<?php
require_once '../config.php';
require_once '../database.php';

header('Content-Type: application/json');

class AdminCRUD {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        // Check admin authentication
        if (!$this->isAdminAuthenticated()) {
            $this->sendResponse(false, 'Unauthorized access', null, 401);
            exit;
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $resource = $_GET['resource'] ?? '';
        $id = $_GET['id'] ?? null;
        
        try {
            switch ($resource) {
                case 'users':
                    return $this->handleUsers($method, $id);
                case 'courses':
                    return $this->handleCourses($method, $id);
                case 'trainers':
                    return $this->handleTrainers($method, $id);
                case 'enrollments':
                    return $this->handleEnrollments($method, $id);
                case 'analytics':
                    return $this->handleAnalytics($method);
                case 'admin-requests':
                    return $this->handleAdminRequests($method, $id);
                default:
                    return $this->sendResponse(false, 'Invalid resource');
            }
        } catch (Exception $e) {
            return $this->sendResponse(false, 'Server error: ' . $e->getMessage());
        }
    }
    
    // USERS CRUD OPERATIONS
    private function handleUsers($method, $id) {
        switch ($method) {
            case 'GET':
                return $id ? $this->getUser($id) : $this->getUsers();
            case 'POST':
                return $this->createUser();
            case 'PUT':
                return $this->updateUser($id);
            case 'DELETE':
                return $this->deleteUser($id);
            default:
                return $this->sendResponse(false, 'Method not allowed');
        }
    }
    
    private function getUsers() {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR username LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($role)) {
            $whereClause .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get users
        $query = "SELECT id, username, email, first_name, last_name, role, status, created_at, updated_at 
                 FROM users $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->sendResponse(true, 'Users retrieved successfully', [
            'users' => $users,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    private function getUser($id) {
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return $this->sendResponse(false, 'User not found', null, 404);
        }
        
        // Remove password from response
        unset($user['password']);
        
        return $this->sendResponse(true, 'User retrieved successfully', ['user' => $user]);
    }
    
    private function createUser() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['username', 'email', 'password', 'first_name', 'last_name', 'role'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return $this->sendResponse(false, "Field '$field' is required");
            }
        }
        
        // Check if username/email exists
        $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $data['username']);
        $checkStmt->bindParam(':email', $data['email']);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            return $this->sendResponse(false, 'Username or email already exists');
        }
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, first_name, last_name, role, status, bio) 
                 VALUES (:username, :email, :password, :first_name, :last_name, :role, :status, :bio)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':status', $data['status'] ?? 'active');
        $stmt->bindParam(':bio', $data['bio'] ?? '');
        
        if ($stmt->execute()) {
            return $this->sendResponse(true, 'User created successfully', ['id' => $this->db->lastInsertId()]);
        }
        
        return $this->sendResponse(false, 'Failed to create user');
    }
    
    private function updateUser($id) {
        if (!$id) {
            return $this->sendResponse(false, 'User ID is required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $updateFields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['username', 'email', 'first_name', 'last_name', 'role', 'status', 'bio'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $updateFields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updateFields)) {
            return $this->sendResponse(false, 'No fields to update');
        }
        
        $query = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute($params)) {
            return $this->sendResponse(true, 'User updated successfully');
        }
        
        return $this->sendResponse(false, 'Failed to update user');
    }
    
    private function deleteUser($id) {
        if (!$id) {
            return $this->sendResponse(false, 'User ID is required');
        }
        
        // Prevent deleting current admin
        if ($id == $_SESSION['admin_user_id']) {
            return $this->sendResponse(false, 'Cannot delete your own account');
        }
        
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return $this->sendResponse(true, 'User deleted successfully');
        }
        
        return $this->sendResponse(false, 'Failed to delete user');
    }
    
    // ANALYTICS
    private function handleAnalytics($method) {
        if ($method !== 'GET') {
            return $this->sendResponse(false, 'Method not allowed');
        }
        
        try {
            // Get user statistics
            $userStats = $this->db->query("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students,
                    SUM(CASE WHEN role = 'trainer' THEN 1 ELSE 0 END) as trainers,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users
                FROM users
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Get course statistics
            $courseStats = $this->db->query("
                SELECT 
                    COUNT(*) as total_courses,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_courses,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_courses
                FROM courses
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Get recent activity (last 30 days)
            $recentActivity = $this->db->query("
                SELECT DATE(created_at) as date, COUNT(*) as count
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
                LIMIT 30
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendResponse(true, 'Analytics retrieved successfully', [
                'user_stats' => $userStats,
                'course_stats' => $courseStats,
                'recent_activity' => $recentActivity
            ]);
            
        } catch (PDOException $e) {
            return $this->sendResponse(false, 'Failed to retrieve analytics');
        }
    }
    
    // ADMIN REQUESTS
    private function handleAdminRequests($method, $id) {
        switch ($method) {
            case 'GET':
                return $id ? $this->getAdminRequest($id) : $this->getAdminRequests();
            case 'PUT':
                return $this->updateAdminRequest($id);
            default:
                return $this->sendResponse(false, 'Method not allowed');
        }
    }
    
    private function getAdminRequests() {
        $query = "SELECT ar.*, u.username, u.email, u.first_name, u.last_name 
                 FROM admin_requests ar 
                 JOIN users u ON ar.user_id = u.id 
                 ORDER BY ar.requested_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->sendResponse(true, 'Admin requests retrieved successfully', ['requests' => $requests]);
    }
    
    private function getAdminRequest($id) {
        $query = "SELECT ar.*, u.username, u.email, u.first_name, u.last_name 
                 FROM admin_requests ar 
                 JOIN users u ON ar.user_id = u.id 
                 WHERE ar.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            return $this->sendResponse(false, 'Admin request not found', null, 404);
        }
        
        return $this->sendResponse(true, 'Admin request retrieved successfully', ['request' => $request]);
    }
    
    private function updateAdminRequest($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'] ?? '';
        $notes = $data['notes'] ?? '';
        
        if (!in_array($status, ['approved', 'rejected'])) {
            return $this->sendResponse(false, 'Invalid status');
        }
        
        // Update request
        $query = "UPDATE admin_requests 
                 SET status = :status, notes = :notes, reviewed_at = NOW(), reviewed_by = :reviewed_by 
                 WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':reviewed_by', $_SESSION['admin_user_id']);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            // If approved, activate the user
            if ($status === 'approved') {
                $getUserQuery = "SELECT user_id FROM admin_requests WHERE id = :id";
                $getUserStmt = $this->db->prepare($getUserQuery);
                $getUserStmt->bindParam(':id', $id);
                $getUserStmt->execute();
                $userId = $getUserStmt->fetch(PDO::FETCH_ASSOC)['user_id'];
                
                $activateQuery = "UPDATE users SET status = 'active' WHERE id = :user_id";
                $activateStmt = $this->db->prepare($activateQuery);
                $activateStmt->bindParam(':user_id', $userId);
                $activateStmt->execute();
            }
            
            return $this->sendResponse(true, 'Admin request updated successfully');
        }
        
        return $this->sendResponse(false, 'Failed to update admin request');
    }
    
    // Helper methods for other resources (courses, trainers, enrollments)
    private function handleCourses($method, $id) {
        // Implement course CRUD operations
        return $this->sendResponse(true, 'Course operations not yet implemented');
    }
    
    private function handleTrainers($method, $id) {
        // Implement trainer CRUD operations
        return $this->sendResponse(true, 'Trainer operations not yet implemented');
    }
    
    private function handleEnrollments($method, $id) {
        // Implement enrollment CRUD operations
        return $this->sendResponse(true, 'Enrollment operations not yet implemented');
    }
    
    private function isAdminAuthenticated() {
        return isset($_SESSION['admin_logged_in']) && 
               $_SESSION['admin_logged_in'] === true && 
               isset($_SESSION['admin_role']) && 
               $_SESSION['admin_role'] === 'admin';
    }
    
    private function sendResponse($success, $message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        
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
$crud = new AdminCRUD();
$crud->handleRequest();
?>
