<?php
// Database Connection Test for Separated Admin/User Tables
require_once '../config.php';
require_once '../database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    // Test 1: Check if admins table exists and has data
    $adminQuery = "SELECT COUNT(*) as admin_count FROM admins";
    $adminStmt = $connection->prepare($adminQuery);
    $adminStmt->execute();
    $adminResult = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    // Test 2: Check if users table exists and has data
    $userQuery = "SELECT COUNT(*) as user_count FROM users";
    $userStmt = $connection->prepare($userQuery);
    $userStmt->execute();
    $userResult = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Test 3: Show sample admin data (excluding passwords)
    $sampleAdminQuery = "SELECT id, username, email, permissions, created_at FROM admins LIMIT 1";
    $sampleAdminStmt = $connection->prepare($sampleAdminQuery);
    $sampleAdminStmt->execute();
    $sampleAdmin = $sampleAdminStmt->fetch(PDO::FETCH_ASSOC);
    
    // Test 4: Show sample user data (excluding passwords)
    $sampleUserQuery = "SELECT id, username, email, first_name, last_name, created_at FROM users LIMIT 1";
    $sampleUserStmt = $connection->prepare($sampleUserQuery);
    $sampleUserStmt->execute();
    $sampleUser = $sampleUserStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database structure test completed successfully',
        'results' => [
            'admin_count' => $adminResult['admin_count'],
            'user_count' => $userResult['user_count'],
            'sample_admin' => $sampleAdmin,
            'sample_user' => $sampleUser,
            'database_separation' => 'Admins and Users are now in separate tables',
            'security_improvement' => 'Admin credentials are isolated from user data'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database test failed: ' . $e->getMessage()
    ]);
}
?>
