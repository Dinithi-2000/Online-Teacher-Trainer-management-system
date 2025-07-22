<?php
require_once '../config.php';
require_once '../database.php';

// Set JSON content type
header('Content-Type: application/json');

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

// Get the resource and action
$resource = $request[0] ?? '';
$action = $request[1] ?? '';
$id = $request[2] ?? '';

// Route requests to appropriate handlers
try {
    switch ($resource) {
        case 'courses':
            require_once 'courses.php';
            break;
        case 'trainers':
            require_once 'trainers.php';
            break;
        case 'blog':
            require_once 'blog.php';
            break;
        case 'resources':
            require_once 'resources.php';
            break;
        case 'users':
            require_once 'users.php';
            break;
        case 'auth':
            require_once 'auth.php';
            break;
        case 'dashboard':
            require_once 'dashboard.php';
            break;
        case 'search':
            require_once 'search.php';
            break;
        case 'newsletter':
            require_once 'newsletter.php';
            break;
        case 'progress':
            require_once 'progress.php';
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Resource not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}
?>
