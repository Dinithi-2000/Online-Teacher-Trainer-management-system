<?php
// Courses API - CRUD Operations

require_once '../database.php';

class CoursesAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // CREATE - Add new course
    public function create($data) {
        try {
            $query = "INSERT INTO courses (title, description, short_description, trainer_id, category, level, duration, price, syllabus, objectives, requirements, image, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $data['short_description'] ?? '',
                $data['trainer_id'] ?? null,
                $data['category'] ?? '',
                $data['level'] ?? 'beginner',
                $data['duration'] ?? 0,
                $data['price'] ?? 0.00,
                $data['syllabus'] ?? '',
                $data['objectives'] ?? '',
                $data['requirements'] ?? '',
                $data['image'] ?? '',
                $data['status'] ?? 'draft'
            ]);
            
            if ($result) {
                $courseId = $this->db->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Course created successfully',
                    'data' => ['id' => $courseId]
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create course'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // READ - Get courses (with filters)
    public function read($filters = []) {
        try {
            $query = "SELECT c.*, u.first_name, u.last_name, u.profile_image as trainer_image 
                     FROM courses c 
                     LEFT JOIN users u ON c.trainer_id = u.id 
                     WHERE c.status = 'active'";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['category'])) {
                $query .= " AND c.category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['level'])) {
                $query .= " AND c.level = ?";
                $params[] = $filters['level'];
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['trainer_id'])) {
                $query .= " AND c.trainer_id = ?";
                $params[] = $filters['trainer_id'];
            }
            
            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'DESC';
            $query .= " ORDER BY c.{$sortBy} {$sortOrder}";
            
            // Pagination
            $limit = $filters['limit'] ?? 10;
            $offset = ($filters['page'] ?? 0) * $limit;
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format trainer names
            foreach ($courses as &$course) {
                $course['trainer_name'] = $course['first_name'] . ' ' . $course['last_name'];
                unset($course['first_name'], $course['last_name']);
            }
            
            return [
                'success' => true,
                'data' => $courses,
                'total' => $this->getTotalCourses($filters)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // READ - Get single course by ID
    public function readOne($id) {
        try {
            $query = "SELECT c.*, u.first_name, u.last_name, u.profile_image as trainer_image, u.bio as trainer_bio 
                     FROM courses c 
                     LEFT JOIN users u ON c.trainer_id = u.id 
                     WHERE c.id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($course) {
                $course['trainer_name'] = $course['first_name'] . ' ' . $course['last_name'];
                unset($course['first_name'], $course['last_name']);
                
                return [
                    'success' => true,
                    'data' => $course
                ];
            }
            
            return ['success' => false, 'message' => 'Course not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // UPDATE - Update course
    public function update($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['title', 'description', 'short_description', 'trainer_id', 'category', 'level', 'duration', 'price', 'syllabus', 'objectives', 'requirements', 'image', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            $params[] = $id;
            $query = "UPDATE courses SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Course updated successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'No changes made or course not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // DELETE - Delete course
    public function delete($id) {
        try {
            // First check if course exists
            $checkQuery = "SELECT id FROM courses WHERE id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if (!$checkStmt->fetch()) {
                return ['success' => false, 'message' => 'Course not found'];
            }
            
            // Delete course (this will also handle enrollments due to foreign key constraints)
            $query = "DELETE FROM courses WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Course deleted successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to delete course'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get featured courses
    public function getFeatured() {
        try {
            $query = "SELECT c.*, u.first_name, u.last_name 
                     FROM courses c 
                     LEFT JOIN users u ON c.trainer_id = u.id 
                     WHERE c.status = 'active' 
                     ORDER BY c.rating DESC, c.created_at DESC 
                     LIMIT 6";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($courses as &$course) {
                $course['trainer_name'] = $course['first_name'] . ' ' . $course['last_name'];
                unset($course['first_name'], $course['last_name']);
            }
            
            return [
                'success' => true,
                'data' => $courses
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get total courses count for pagination
    private function getTotalCourses($filters = []) {
        try {
            $query = "SELECT COUNT(*) FROM courses WHERE status = 'active'";
            $params = [];
            
            if (!empty($filters['category'])) {
                $query .= " AND category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['level'])) {
                $query .= " AND level = ?";
                $params[] = $filters['level'];
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (title LIKE ? OR description LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
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
$coursesAPI = new CoursesAPI();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if ($action === 'featured') {
            $response = $coursesAPI->getFeatured();
        } elseif (!empty($id)) {
            $response = $coursesAPI->readOne($id);
        } else {
            $filters = $_GET;
            $response = $coursesAPI->read($filters);
        }
        break;
        
    case 'POST':
        $response = $coursesAPI->create($input);
        break;
        
    case 'PUT':
        if (empty($id)) {
            $response = ['success' => false, 'message' => 'Course ID required for update'];
        } else {
            $response = $coursesAPI->update($id, $input);
        }
        break;
        
    case 'DELETE':
        if (empty($id)) {
            $response = ['success' => false, 'message' => 'Course ID required for deletion'];
        } else {
            $response = $coursesAPI->delete($id);
        }
        break;
        
    default:
        http_response_code(405);
        $response = ['success' => false, 'message' => 'Method not allowed'];
}

echo json_encode($response);
?>
