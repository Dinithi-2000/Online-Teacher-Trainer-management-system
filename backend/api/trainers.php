<?php
// Trainers API - CRUD Operations

require_once '../database.php';

class TrainersAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // CREATE - Add new trainer
    public function create($data) {
        try {
            // First create/update user record
            $userQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, profile_image, bio) 
                         VALUES (?, ?, ?, ?, ?, 'trainer', ?, ?)
                         ON DUPLICATE KEY UPDATE 
                         first_name = VALUES(first_name),
                         last_name = VALUES(last_name),
                         profile_image = VALUES(profile_image),
                         bio = VALUES(bio)";
            
            $userStmt = $this->db->prepare($userQuery);
            $hashedPassword = password_hash($data['password'] ?? 'defaultpass123', PASSWORD_DEFAULT);
            
            $userResult = $userStmt->execute([
                $data['username'] ?? $data['email'],
                $data['email'],
                $hashedPassword,
                $data['first_name'],
                $data['last_name'],
                $data['profile_image'] ?? '',
                $data['bio'] ?? ''
            ]);
            
            if (!$userResult) {
                return ['success' => false, 'message' => 'Failed to create user record'];
            }
            
            $userId = $this->db->lastInsertId();
            
            // If user already exists, get the existing user ID
            if ($userId == 0) {
                $userCheckQuery = "SELECT id FROM users WHERE email = ?";
                $userCheckStmt = $this->db->prepare($userCheckQuery);
                $userCheckStmt->execute([$data['email']]);
                $userId = $userCheckStmt->fetchColumn();
            }
            
            // Create trainer record
            $trainerQuery = "INSERT INTO trainers (user_id, expertise, experience_years, certifications, status) 
                           VALUES (?, ?, ?, ?, ?)";
            
            $trainerStmt = $this->db->prepare($trainerQuery);
            $trainerResult = $trainerStmt->execute([
                $userId,
                $data['expertise'] ?? '',
                $data['experience_years'] ?? 0,
                $data['certifications'] ?? '',
                $data['status'] ?? 'pending'
            ]);
            
            if ($trainerResult) {
                return [
                    'success' => true,
                    'message' => 'Trainer created successfully',
                    'data' => ['id' => $this->db->lastInsertId(), 'user_id' => $userId]
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create trainer record'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // READ - Get trainers (with filters)
    public function read($filters = []) {
        try {
            $query = "SELECT t.*, u.first_name, u.last_name, u.email, u.profile_image, u.bio, u.created_at as user_created
                     FROM trainers t 
                     INNER JOIN users u ON t.user_id = u.id 
                     WHERE t.status = 'active'";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['expertise'])) {
                $query .= " AND t.expertise LIKE ?";
                $params[] = "%{$filters['expertise']}%";
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR t.expertise LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['min_rating'])) {
                $query .= " AND t.rating >= ?";
                $params[] = $filters['min_rating'];
            }
            
            // Sorting
            $sortBy = $filters['sort_by'] ?? 'rating';
            $sortOrder = $filters['sort_order'] ?? 'DESC';
            
            if ($sortBy === 'name') {
                $query .= " ORDER BY u.first_name {$sortOrder}";
            } else {
                $query .= " ORDER BY t.{$sortBy} {$sortOrder}";
            }
            
            // Pagination
            $limit = $filters['limit'] ?? 12;
            $offset = ($filters['page'] ?? 0) * $limit;
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add course count for each trainer
            foreach ($trainers as &$trainer) {
                $trainer['courses_count'] = $this->getTrainerCoursesCount($trainer['user_id']);
            }
            
            return [
                'success' => true,
                'data' => $trainers,
                'total' => $this->getTotalTrainers($filters)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // READ - Get single trainer by ID
    public function readOne($id) {
        try {
            $query = "SELECT t.*, u.first_name, u.last_name, u.email, u.profile_image, u.bio, u.created_at as user_created
                     FROM trainers t 
                     INNER JOIN users u ON t.user_id = u.id 
                     WHERE t.id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $trainer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($trainer) {
                // Get trainer's courses
                $trainer['courses'] = $this->getTrainerCourses($trainer['user_id']);
                $trainer['courses_count'] = count($trainer['courses']);
                
                // Get trainer reviews/testimonials (simplified - would need a reviews table in real app)
                $trainer['reviews'] = $this->getTrainerReviews($trainer['id']);
                
                return [
                    'success' => true,
                    'data' => $trainer
                ];
            }
            
            return ['success' => false, 'message' => 'Trainer not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // UPDATE - Update trainer
    public function update($id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Update user table
            if (isset($data['first_name']) || isset($data['last_name']) || isset($data['bio']) || isset($data['profile_image'])) {
                $userFields = [];
                $userParams = [];
                
                $userFieldMap = ['first_name', 'last_name', 'bio', 'profile_image'];
                
                foreach ($userFieldMap as $field) {
                    if (isset($data[$field])) {
                        $userFields[] = "{$field} = ?";
                        $userParams[] = $data[$field];
                    }
                }
                
                if (!empty($userFields)) {
                    // Get user_id first
                    $getUserQuery = "SELECT user_id FROM trainers WHERE id = ?";
                    $getUserStmt = $this->db->prepare($getUserQuery);
                    $getUserStmt->execute([$id]);
                    $userId = $getUserStmt->fetchColumn();
                    
                    if ($userId) {
                        $userParams[] = $userId;
                        $userUpdateQuery = "UPDATE users SET " . implode(', ', $userFields) . " WHERE id = ?";
                        $userStmt = $this->db->prepare($userUpdateQuery);
                        $userStmt->execute($userParams);
                    }
                }
            }
            
            // Update trainer table
            $trainerFields = [];
            $trainerParams = [];
            
            $trainerFieldMap = ['expertise', 'experience_years', 'certifications', 'status'];
            
            foreach ($trainerFieldMap as $field) {
                if (isset($data[$field])) {
                    $trainerFields[] = "{$field} = ?";
                    $trainerParams[] = $data[$field];
                }
            }
            
            if (!empty($trainerFields)) {
                $trainerParams[] = $id;
                $trainerUpdateQuery = "UPDATE trainers SET " . implode(', ', $trainerFields) . " WHERE id = ?";
                $trainerStmt = $this->db->prepare($trainerUpdateQuery);
                $trainerStmt->execute($trainerParams);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Trainer updated successfully'
            ];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // DELETE - Delete trainer
    public function delete($id) {
        try {
            // Check if trainer has active courses
            $checkQuery = "SELECT COUNT(*) FROM courses WHERE trainer_id = (SELECT user_id FROM trainers WHERE id = ?) AND status = 'active'";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$id]);
            $activeCourses = $checkStmt->fetchColumn();
            
            if ($activeCourses > 0) {
                return ['success' => false, 'message' => 'Cannot delete trainer with active courses'];
            }
            
            // Delete trainer record (user record will remain)
            $query = "DELETE FROM trainers WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Trainer deleted successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Trainer not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get trainer's courses
    private function getTrainerCourses($userId) {
        try {
            $query = "SELECT id, title, short_description, level, duration, rating, status 
                     FROM courses 
                     WHERE trainer_id = ? 
                     ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get trainer's courses count
    private function getTrainerCoursesCount($userId) {
        try {
            $query = "SELECT COUNT(*) FROM courses WHERE trainer_id = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Get trainer reviews (simplified)
    private function getTrainerReviews($trainerId) {
        // In a real application, you would have a reviews table
        // For now, return dummy data
        return [
            [
                'reviewer' => 'Anonymous',
                'rating' => 5,
                'comment' => 'Excellent trainer with great expertise!',
                'date' => date('Y-m-d')
            ]
        ];
    }
    
    // Get total trainers count for pagination
    private function getTotalTrainers($filters = []) {
        try {
            $query = "SELECT COUNT(*) FROM trainers t INNER JOIN users u ON t.user_id = u.id WHERE t.status = 'active'";
            $params = [];
            
            if (!empty($filters['expertise'])) {
                $query .= " AND t.expertise LIKE ?";
                $params[] = "%{$filters['expertise']}%";
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR t.expertise LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            return 0;
        }
    }
}

// Handle API requests
$trainersAPI = new TrainersAPI();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if (!empty($id)) {
            $response = $trainersAPI->readOne($id);
        } else {
            $filters = $_GET;
            $response = $trainersAPI->read($filters);
        }
        break;
        
    case 'POST':
        $response = $trainersAPI->create($input);
        break;
        
    case 'PUT':
        if (empty($id)) {
            $response = ['success' => false, 'message' => 'Trainer ID required for update'];
        } else {
            $response = $trainersAPI->update($id, $input);
        }
        break;
        
    case 'DELETE':
        if (empty($id)) {
            $response = ['success' => false, 'message' => 'Trainer ID required for deletion'];
        } else {
            $response = $trainersAPI->delete($id);
        }
        break;
        
    default:
        http_response_code(405);
        $response = ['success' => false, 'message' => 'Method not allowed'];
}

echo json_encode($response);
?>
