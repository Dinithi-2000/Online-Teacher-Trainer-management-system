<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'teachverse');

// Create connection
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $current_dir = dirname($_SERVER['PHP_SELF']);
        $root_path = str_repeat('../', substr_count($current_dir, '/') - 1);
        header('Location: ' . $root_path . 'auth.php?mode=login');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        $current_dir = dirname($_SERVER['PHP_SELF']);
        $root_path = str_repeat('../', substr_count($current_dir, '/') - 1);
        header('Location: ' . $root_path . 'dashboard.php');
        exit();
    }
}

// Redirect if not trainer
function requireTrainer() {
    requireLogin();
    if (!hasRole('trainer') && !hasRole('admin')) {
        $current_dir = dirname($_SERVER['PHP_SELF']);
        $root_path = str_repeat('../', substr_count($current_dir, '/') - 1);
        header('Location: ' . $root_path . 'dashboard.php');
        exit();
    }
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Format date
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}
?>
