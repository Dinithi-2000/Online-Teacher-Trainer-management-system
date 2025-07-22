<?php
/**
 * Contact Management API
 * Handles all contact-related operations for EduMentor Pro
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
$request = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $request);
            break;
        case 'POST':
            handlePostRequest($conn, $request);
            break;
        case 'PUT':
            handlePutRequest($conn, $request);
            break;
        case 'DELETE':
            handleDeleteRequest($conn, $request);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($conn, $action) {
    switch ($action) {
        case 'list':
            getAllContacts($conn);
            break;
        case 'get':
            getContactById($conn);
            break;
        case 'stats':
            getContactStats($conn);
            break;
        case 'search':
            searchContacts($conn);
            break;
        default:
            getAllContacts($conn);
            break;
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($conn, $action) {
    switch ($action) {
        case 'create':
        case 'submit':
            createContact($conn);
            break;
        case 'bulk-action':
            handleBulkAction($conn);
            break;
        default:
            createContact($conn);
            break;
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($conn, $action) {
    switch ($action) {
        case 'update':
            updateContact($conn);
            break;
        case 'status':
            updateContactStatus($conn);
            break;
        case 'assign':
            updateContact($conn); // Use general update function for assignment
            break;
        default:
            updateContact($conn);
            break;
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($conn, $action) {
    switch ($action) {
        case 'delete':
            deleteContact($conn);
            break;
        case 'bulk-delete':
            handleBulkAction($conn); // Use bulk action function for deletion
            break;
        default:
            deleteContact($conn);
            break;
    }
}

/**
 * Get all contacts with optional filtering
 */
function getAllContacts($conn) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $priority = isset($_GET['priority']) ? $_GET['priority'] : '';
    $message_type = isset($_GET['message_type']) ? $_GET['message_type'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($status)) {
        $where_conditions[] = "status = ?";
        $params[] = $status;
    }
    
    if (!empty($priority)) {
        $where_conditions[] = "priority = ?";
        $params[] = $priority;
    }
    
    if (!empty($message_type)) {
        $where_conditions[] = "message_type = ?";
        $params[] = $message_type;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM contacts $where_clause";
    $count_stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        $count_stmt->execute($params);
    } else {
        $count_stmt->execute();
    }
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get contacts
    $query = "SELECT c.*, a.first_name as assigned_first_name, a.last_name as assigned_last_name 
              FROM contacts c 
              LEFT JOIN admins a ON c.assigned_to = a.id 
              $where_clause 
              ORDER BY c.created_at DESC 
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    foreach ($contacts as &$contact) {
        $contact['assigned_admin'] = null;
        if ($contact['assigned_first_name']) {
            $contact['assigned_admin'] = $contact['assigned_first_name'] . ' ' . $contact['assigned_last_name'];
        }
        unset($contact['assigned_first_name'], $contact['assigned_last_name']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $contacts,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => intval($total),
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get contact by ID
 */
function getContactById($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Contact ID is required']);
        return;
    }
    
    $query = "SELECT c.*, a.first_name as assigned_first_name, a.last_name as assigned_last_name 
              FROM contacts c 
              LEFT JOIN admins a ON c.assigned_to = a.id 
              WHERE c.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        http_response_code(404);
        echo json_encode(['error' => 'Contact not found']);
        return;
    }
    
    // Format response
    $contact['assigned_admin'] = null;
    if ($contact['assigned_first_name']) {
        $contact['assigned_admin'] = $contact['assigned_first_name'] . ' ' . $contact['assigned_last_name'];
    }
    unset($contact['assigned_first_name'], $contact['assigned_last_name']);
    
    echo json_encode([
        'success' => true,
        'data' => $contact
    ]);
}

/**
 * Get contact statistics
 */
function getContactStats($conn) {
    $stats_query = "
        SELECT 
            COUNT(*) as total_messages,
            SUM(CASE WHEN status IN ('New', 'In Progress') THEN 1 ELSE 0 END) as pending_messages,
            SUM(CASE WHEN status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) as resolved_messages,
            SUM(CASE WHEN priority = 'Urgent' THEN 1 ELSE 0 END) as urgent_messages,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_messages,
            AVG(CASE 
                WHEN resolved_at IS NOT NULL AND created_at IS NOT NULL 
                THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
                ELSE NULL 
            END) as avg_response_time_hours
        FROM contacts
    ";
    
    $stmt = $conn->prepare($stats_query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format average response time
    $avg_hours = $stats['avg_response_time_hours'];
    if ($avg_hours !== null) {
        if ($avg_hours < 24) {
            $stats['avg_response_time'] = round($avg_hours, 1) . 'h';
        } else {
            $stats['avg_response_time'] = round($avg_hours / 24, 1) . 'd';
        }
    } else {
        $stats['avg_response_time'] = 'N/A';
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

/**
 * Create new contact
 */
function createContact($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'email', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    // Validate email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }
    
    // Set default values
    $message_type = $input['message_type'] ?? 'General Inquiry';
    $priority = $input['priority'] ?? 'Medium';
    $phone = $input['phone'] ?? null;
    $newsletter = isset($input['newsletter_subscription']) ? 1 : 0;
    
    $query = "INSERT INTO contacts 
              (first_name, last_name, email, phone, message_type, priority, subject, message, newsletter_subscription) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        $input['first_name'],
        $input['last_name'],
        $input['email'],
        $phone,
        $message_type,
        $priority,
        $input['subject'],
        $input['message'],
        $newsletter
    ]);
    
    if ($result) {
        $contact_id = $conn->lastInsertId();
        
        // Get the created contact
        $get_query = "SELECT * FROM contacts WHERE id = ?";
        $get_stmt = $conn->prepare($get_query);
        $get_stmt->execute([$contact_id]);
        $contact = $get_stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Contact created successfully',
            'data' => $contact
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create contact']);
    }
}

/**
 * Update contact
 */
function updateContact($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? intval($_GET['id']) : ($input['id'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Contact ID is required']);
        return;
    }
    
    // Check if contact exists
    $check_query = "SELECT id FROM contacts WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$id]);
    
    if (!$check_stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Contact not found']);
        return;
    }
    
    // Build update query dynamically
    $update_fields = [];
    $params = [];
    
    $allowed_fields = ['first_name', 'last_name', 'email', 'phone', 'message_type', 'priority', 'subject', 'message', 'status', 'is_read', 'assigned_to'];
    
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $update_fields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($update_fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update']);
        return;
    }
    
    // Add resolved_at if status is being changed to resolved/closed
    if (isset($input['status']) && in_array($input['status'], ['Resolved', 'Closed'])) {
        $update_fields[] = "resolved_at = NOW()";
    }
    
    $update_fields[] = "updated_at = NOW()";
    $params[] = $id;
    
    $query = "UPDATE contacts SET " . implode(', ', $update_fields) . " WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Get updated contact
        $get_query = "SELECT * FROM contacts WHERE id = ?";
        $get_stmt = $conn->prepare($get_query);
        $get_stmt->execute([$id]);
        $contact = $get_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Contact updated successfully',
            'data' => $contact
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update contact']);
    }
}

/**
 * Update contact status
 */
function updateContactStatus($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? intval($_GET['id']) : ($input['id'] ?? 0);
    $status = $input['status'] ?? '';
    
    if (!$id || !$status) {
        http_response_code(400);
        echo json_encode(['error' => 'Contact ID and status are required']);
        return;
    }
    
    $valid_statuses = ['New', 'In Progress', 'Resolved', 'Closed'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        return;
    }
    
    $resolved_at_update = in_array($status, ['Resolved', 'Closed']) ? ", resolved_at = NOW()" : ", resolved_at = NULL";
    
    $query = "UPDATE contacts SET status = ?, updated_at = NOW() $resolved_at_update WHERE id = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$status, $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update status']);
    }
}

/**
 * Delete contact
 */
function deleteContact($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Contact ID is required']);
        return;
    }
    
    $query = "DELETE FROM contacts WHERE id = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$id]);
    
    if ($result) {
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Contact deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Contact not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete contact']);
    }
}

/**
 * Handle bulk actions
 */
function handleBulkAction($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $ids = $input['ids'] ?? [];
    
    if (empty($ids) || !is_array($ids)) {
        http_response_code(400);
        echo json_encode(['error' => 'No contact IDs provided']);
        return;
    }
    
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    switch ($action) {
        case 'mark_read':
            $query = "UPDATE contacts SET is_read = 1, updated_at = NOW() WHERE id IN ($placeholders)";
            break;
        case 'mark_unread':
            $query = "UPDATE contacts SET is_read = 0, updated_at = NOW() WHERE id IN ($placeholders)";
            break;
        case 'delete':
            $query = "DELETE FROM contacts WHERE id IN ($placeholders)";
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid bulk action']);
            return;
    }
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute($ids);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Bulk action completed successfully',
            'affected_rows' => $stmt->rowCount()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to perform bulk action']);
    }
}

/**
 * Search contacts
 */
function searchContacts($conn) {
    $search = isset($_GET['q']) ? $_GET['q'] : '';
    
    if (empty($search)) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        return;
    }
    
    $query = "SELECT id, first_name, last_name, email, subject, status, priority, created_at 
              FROM contacts 
              WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?
              ORDER BY created_at DESC 
              LIMIT 20";
    
    $search_param = "%$search%";
    $stmt = $conn->prepare($query);
    $stmt->execute([$search_param, $search_param, $search_param, $search_param, $search_param]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $contacts
    ]);
}

?>
