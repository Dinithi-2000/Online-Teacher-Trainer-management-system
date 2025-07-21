<?php
// Blog API - CRUD Operations

require_once '../database.php';

class BlogAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // CREATE - Add new blog post
    public function create($data) {
        try {
            // Generate slug from title
            $slug = $this->generateSlug($data['title']);
            
            $query = "INSERT INTO blog_posts (title, slug, content, excerpt, author_id, category, featured_image, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['title'],
                $slug,
                $data['content'],
                $data['excerpt'] ?? $this->generateExcerpt($data['content']),
                $data['author_id'] ?? null,
                $data['category'] ?? 'General',
                $data['featured_image'] ?? '',
                $data['status'] ?? 'draft'
            ]);
            
            if ($result) {
                $postId = $this->db->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Blog post created successfully',
                    'data' => ['id' => $postId, 'slug' => $slug]
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create blog post'];
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate slug
                $slug = $this->generateSlug($data['title'], true);
                return $this->create(array_merge($data, ['slug' => $slug]));
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // READ - Get blog posts (with filters)
    public function read($filters = []) {
        try {
            $query = "SELECT b.*, u.first_name, u.last_name, u.profile_image as author_image 
                     FROM blog_posts b 
                     LEFT JOIN users u ON b.author_id = u.id 
                     WHERE b.status = 'published'";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['category'])) {
                $query .= " AND b.category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (b.title LIKE ? OR b.content LIKE ? OR b.excerpt LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['author_id'])) {
                $query .= " AND b.author_id = ?";
                $params[] = $filters['author_id'];
            }
            
            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'DESC';
            $query .= " ORDER BY b.{$sortBy} {$sortOrder}";
            
            // Pagination
            $limit = $filters['limit'] ?? 10;
            $offset = ($filters['page'] ?? 0) * $limit;
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format author names
            foreach ($posts as &$post) {
                $post['author_name'] = $post['first_name'] . ' ' . $post['last_name'];
                unset($post['first_name'], $post['last_name']);
                
                // Format dates
                $post['formatted_date'] = date('F j, Y', strtotime($post['created_at']));
                
                // Add reading time estimate
                $post['reading_time'] = $this->estimateReadingTime($post['content']);
            }
            
            return [
                'success' => true,
                'data' => $posts,
                'total' => $this->getTotalPosts($filters)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // READ - Get single blog post by ID or slug
    public function readOne($identifier) {
        try {
            // Check if identifier is numeric (ID) or string (slug)
            $isId = is_numeric($identifier);
            $field = $isId ? 'id' : 'slug';
            
            $query = "SELECT b.*, u.first_name, u.last_name, u.profile_image as author_image, u.bio as author_bio 
                     FROM blog_posts b 
                     LEFT JOIN users u ON b.author_id = u.id 
                     WHERE b.{$field} = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$identifier]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post) {
                $post['author_name'] = $post['first_name'] . ' ' . $post['last_name'];
                unset($post['first_name'], $post['last_name']);
                
                $post['formatted_date'] = date('F j, Y', strtotime($post['created_at']));
                $post['reading_time'] = $this->estimateReadingTime($post['content']);
                
                // Increment view count
                $this->incrementViews($post['id']);
                
                // Get related posts
                $post['related_posts'] = $this->getRelatedPosts($post['id'], $post['category']);
                
                return [
                    'success' => true,
                    'data' => $post
                ];
            }
            
            return ['success' => false, 'message' => 'Blog post not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // UPDATE - Update blog post
    public function update($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['title', 'content', 'excerpt', 'category', 'featured_image', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
            
            // Update slug if title is changed
            if (isset($data['title'])) {
                $fields[] = "slug = ?";
                $params[] = $this->generateSlug($data['title']);
            }
            
            if (empty($fields)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            $params[] = $id;
            $query = "UPDATE blog_posts SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Blog post updated successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'No changes made or post not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // DELETE - Delete blog post
    public function delete($id) {
        try {
            $query = "DELETE FROM blog_posts WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Blog post deleted successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Blog post not found'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get blog categories
    public function getCategories() {
        try {
            $query = "SELECT category, COUNT(*) as count 
                     FROM blog_posts 
                     WHERE status = 'published' 
                     GROUP BY category 
                     ORDER BY count DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $categories
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get featured/latest posts
    public function getFeatured($limit = 5) {
        try {
            $query = "SELECT b.*, u.first_name, u.last_name 
                     FROM blog_posts b 
                     LEFT JOIN users u ON b.author_id = u.id 
                     WHERE b.status = 'published' 
                     ORDER BY b.views DESC, b.created_at DESC 
                     LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($posts as &$post) {
                $post['author_name'] = $post['first_name'] . ' ' . $post['last_name'];
                unset($post['first_name'], $post['last_name']);
                $post['formatted_date'] = date('F j, Y', strtotime($post['created_at']));
            }
            
            return [
                'success' => true,
                'data' => $posts
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Generate URL-friendly slug
    private function generateSlug($title, $unique = false) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = trim($slug, '-');
        
        if ($unique) {
            $slug .= '-' . time();
        }
        
        return $slug;
    }
    
    // Generate excerpt from content
    private function generateExcerpt($content, $length = 150) {
        $content = strip_tags($content);
        if (strlen($content) <= $length) {
            return $content;
        }
        return substr($content, 0, $length) . '...';
    }
    
    // Estimate reading time
    private function estimateReadingTime($content) {
        $wordCount = str_word_count(strip_tags($content));
        $wordsPerMinute = 200; // Average reading speed
        $minutes = ceil($wordCount / $wordsPerMinute);
        return $minutes . ' min read';
    }
    
    // Increment view count
    private function incrementViews($postId) {
        try {
            $query = "UPDATE blog_posts SET views = views + 1 WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$postId]);
        } catch (PDOException $e) {
            // Silently fail - view counting is not critical
        }
    }
    
    // Get related posts
    private function getRelatedPosts($postId, $category, $limit = 3) {
        try {
            $query = "SELECT id, title, slug, excerpt, featured_image, created_at 
                     FROM blog_posts 
                     WHERE id != ? AND category = ? AND status = 'published' 
                     ORDER BY created_at DESC 
                     LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$postId, $category, $limit]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($posts as &$post) {
                $post['formatted_date'] = date('F j, Y', strtotime($post['created_at']));
            }
            
            return $posts;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get total posts count for pagination
    private function getTotalPosts($filters = []) {
        try {
            $query = "SELECT COUNT(*) FROM blog_posts WHERE status = 'published'";
            $params = [];
            
            if (!empty($filters['category'])) {
                $query .= " AND category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?)";
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
$blogAPI = new BlogAPI();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if ($action === 'categories') {
            $response = $blogAPI->getCategories();
        } elseif ($action === 'featured') {
            $limit = $_GET['limit'] ?? 5;
            $response = $blogAPI->getFeatured($limit);
        } elseif (!empty($id)) {
            $response = $blogAPI->readOne($id);
        } else {
            $filters = $_GET;
            $response = $blogAPI->read($filters);
        }
        break;
        
    case 'POST':
        $response = $blogAPI->create($input);
        break;
        
    case 'PUT':
        if (empty($id)) {
            $response = ['success' => false, 'message' => 'Post ID required for update'];
        } else {
            $response = $blogAPI->update($id, $input);
        }
        break;
        
    case 'DELETE':
        if (empty($id)) {
            $response = ['success' => false, 'message' => 'Post ID required for deletion'];
        } else {
            $response = $blogAPI->delete($id);
        }
        break;
        
    default:
        http_response_code(405);
        $response = ['success' => false, 'message' => 'Method not allowed'];
}

echo json_encode($response);
?>
