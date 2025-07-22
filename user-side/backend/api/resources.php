<?php
// Resources API - CRUD Operations for downloadable resources

require_once '../database.php';

class ResourcesAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // CREATE - Add new resource
    public function create($data) {
        try {
            $query = "INSERT INTO resources (title, description, file_path, file_type, category, grade_level, subject, uploaded_by, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $data['file_path'],
                $data['file_type'] ?? '',
                $data['category'] ?? '',
                $data['grade_level'] ?? '',
                $data['subject'] ?? '',
                $data['uploaded_by'] ?? null,
                $data['status'] ?? 'active'
            ]);
            
            if ($result) {
                $resourceId = $this->db->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Resource created successfully',
                    'data' => ['id' => $resourceId]
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create resource'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // READ - Get resources with filters
    public function read($filters = []) {
        try {
            $query = "SELECT r.*, u.first_name, u.last_name 
                     FROM resources r 
                     LEFT JOIN users u ON r.uploaded_by = u.id 
                     WHERE r.status = 'active'";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['category'])) {
                $query .= " AND r.category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['grade_level'])) {
                $query .= " AND r.grade_level = ?";
                $params[] = $filters['grade_level'];
            }
            
            if (!empty($filters['subject'])) {
                $query .= " AND r.subject = ?";
                $params[] = $filters['subject'];
            }
            
            if (!empty($filters['file_type'])) {
                $query .= " AND r.file_type = ?";
                $params[] = $filters['file_type'];
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (r.title LIKE ? OR r.description LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'DESC';
            $query .= " ORDER BY r.{$sortBy} {$sortOrder}";
            
            // Pagination
            $limit = $filters['limit'] ?? 12;
            $offset = ($filters['page'] ?? 0) * $limit;
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format uploader names
            foreach ($resources as &$resource) {
                $resource['uploader_name'] = $resource['first_name'] . ' ' . $resource['last_name'];
                unset($resource['first_name'], $resource['last_name']);
                
                // Format file size if available
                if (file_exists($resource['file_path'])) {
                    $resource['file_size'] = $this->formatFileSize(filesize($resource['file_path']));
                }
            }
            
            return [
                'success' => true,
                'data' => $resources,
                'total' => $this->getTotalResources($filters)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // READ - Get single resource
    public function readOne($id) {
        try {
            $query = "SELECT r.*, u.first_name, u.last_name, u.profile_image 
                     FROM resources r 
                     LEFT JOIN users u ON r.uploaded_by = u.id 
                     WHERE r.id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resource) {
                $resource['uploader_name'] = $resource['first_name'] . ' ' . $resource['last_name'];
                unset($resource['first_name'], $resource['last_name']);
                
                return [
                    'success' => true,
                    'data' => $resource
                ];
            }
            
            return ['success' => false, 'message' => 'Resource not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // UPDATE - Update resource
    public function update($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['title', 'description', 'category', 'grade_level', 'subject', 'status'];
            
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
            $query = "UPDATE resources SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Resource updated successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'No changes made or resource not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // DELETE - Delete resource
    public function delete($id) {
        try {
            // Get file path before deletion
            $fileQuery = "SELECT file_path FROM resources WHERE id = ?";
            $fileStmt = $this->db->prepare($fileQuery);
            $fileStmt->execute([$id]);
            $filePath = $fileStmt->fetchColumn();
            
            // Delete from database
            $query = "DELETE FROM resources WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Delete physical file if it exists
                if ($filePath && file_exists($filePath)) {
                    unlink($filePath);
                }
                
                return [
                    'success' => true,
                    'message' => 'Resource deleted successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Resource not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Download resource
    public function download($id) {
        try {
            // Update download count
            $updateQuery = "UPDATE resources SET downloads = downloads + 1 WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$id]);
            
            // Get file info
            $query = "SELECT title, file_path, file_type FROM resources WHERE id = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resource && file_exists($resource['file_path'])) {
                return [
                    'success' => true,
                    'data' => $resource
                ];
            }
            
            return ['success' => false, 'message' => 'File not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get resource categories
    public function getCategories() {
        try {
            $query = "SELECT category, COUNT(*) as count 
                     FROM resources 
                     WHERE status = 'active' AND category != '' 
                     GROUP BY category 
                     ORDER BY category";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get grade levels
    public function getGradeLevels() {
        try {
            $query = "SELECT grade_level, COUNT(*) as count 
                     FROM resources 
                     WHERE status = 'active' AND grade_level != '' 
                     GROUP BY grade_level 
                     ORDER BY grade_level";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Format file size
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    // Get total resources count
    private function getTotalResources($filters = []) {
        try {
            $query = "SELECT COUNT(*) FROM resources WHERE status = 'active'";
            $params = [];
            
            if (!empty($filters['category'])) {
                $query .= " AND category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['grade_level'])) {
                $query .= " AND grade_level = ?";
                $params[] = $filters['grade_level'];
            }
            
            if (!empty($filters['subject'])) {
                $query .= " AND subject = ?";
                $params[] = $filters['subject'];
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
$resourcesAPI = new ResourcesAPI();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if ($action === 'categories') {
            $response = $resourcesAPI->getCategories();
        } elseif ($action === 'grade-levels') {
            $response = $resourcesAPI->getGradeLevels();
        } elseif ($action === 'download' && !empty($id)) {
            $response = $resourcesAPI->download($id);
        } elseif (!empty($id)) {
            $response = $resourcesAPI->readOne($id);
        } else {
            $filters = $_GET;
            $response = $resourcesAPI->read($filters);
        }
        break;
        
    case 'POST':
        $response = $resourcesAPI->create($input);
        break;
        
    case 'PUT':
        if (empty($id)) {
            $response = ['success' => false, 'message' => 'Resource ID required for update'];
        } else {
            $response = $resourcesAPI->update($id, $input);
        }
        break;
        
    case 'DELETE':
        if (empty($id)) {
            $response = ['success' => false, 'message' => 'Resource ID required for deletion'];
        } else {
            $response = $resourcesAPI->delete($id);
        }
        break;
        
    default:
        http_response_code(405);
        $response = ['success' => false, 'message' => 'Method not allowed'];
}

echo json_encode($response);
?>
