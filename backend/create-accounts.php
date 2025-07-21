<?php
// Create Admin Account Script
require_once 'config.php';
require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create Admin Account - EduMentor Pro</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: linear-gradient(135deg, #1e3a8a, #f97316); color: white; }
        .container { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 30px; }
        .success { color: #22c55e; background: rgba(34, 197, 94, 0.2); padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { color: #ef4444; background: rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { color: #3b82f6; background: rgba(59, 130, 246, 0.2); padding: 15px; border-radius: 8px; margin: 10px 0; }
        .credentials { background: rgba(245, 158, 11, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0; border: 2px solid #f59e0b; }
        h1 { text-align: center; color: white; }
        h2 { color: #f97316; }
        code { background: rgba(0, 0, 0, 0.3); padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Create Admin Account</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Step 1: Creating Admin Account...</h2>";
    
    // Admin credentials
    $adminUsername = 'adminuser';
    $adminEmail = 'admin@edumentor.pro';
    $adminPassword = 'AdminPass123!';
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    // Check if admin already exists
    $checkQuery = "SELECT id FROM users WHERE email = :email OR username = :username";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $adminEmail);
    $checkStmt->bindParam(':username', $adminUsername);
    $checkStmt->execute();
    
    if ($checkStmt->fetch()) {
        echo "<div class='info'>ℹ️ Admin account already exists, updating password...</div>";
        
        // Update existing admin
        $updateQuery = "UPDATE users SET password = :password, status = 'active', role = 'admin' WHERE email = :email OR username = :username";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':password', $hashedPassword);
        $updateStmt->bindParam(':email', $adminEmail);
        $updateStmt->bindParam(':username', $adminUsername);
        $updateStmt->execute();
        
        echo "<div class='success'>✅ Admin account updated successfully!</div>";
    } else {
        // Create new admin account
        $insertQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, status, bio, created_at) 
                       VALUES (:username, :email, :password, :first_name, :last_name, :role, :status, :bio, NOW())";
        
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':username', $adminUsername);
        $insertStmt->bindParam(':email', $adminEmail);
        $insertStmt->bindParam(':password', $hashedPassword);
        $insertStmt->bindValue(':first_name', 'Admin');
        $insertStmt->bindValue(':last_name', 'User');
        $insertStmt->bindValue(':role', 'admin');
        $insertStmt->bindValue(':status', 'active');
        $insertStmt->bindValue(':bio', 'Main administrator account for EduMentor Pro platform.');
        
        if ($insertStmt->execute()) {
            echo "<div class='success'>✅ New admin account created successfully!</div>";
        } else {
            echo "<div class='error'>❌ Failed to create admin account!</div>";
        }
    }
    
    echo "<h2>Step 2: Creating Sample User Account...</h2>";
    
    // Sample user credentials
    $userUsername = 'testuser';
    $userEmail = 'user@edumentor.pro';
    $userPassword = 'UserPass123!';
    $userHashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
    
    // Check if user already exists
    $checkUserQuery = "SELECT id FROM users WHERE email = :email OR username = :username";
    $checkUserStmt = $db->prepare($checkUserQuery);
    $checkUserStmt->bindParam(':email', $userEmail);
    $checkUserStmt->bindParam(':username', $userUsername);
    $checkUserStmt->execute();
    
    if ($checkUserStmt->fetch()) {
        echo "<div class='info'>ℹ️ Sample user account already exists, updating password...</div>";
        
        // Update existing user
        $updateUserQuery = "UPDATE users SET password = :password, status = 'active', role = 'student' WHERE email = :email OR username = :username";
        $updateUserStmt = $db->prepare($updateUserQuery);
        $updateUserStmt->bindParam(':password', $userHashedPassword);
        $updateUserStmt->bindParam(':email', $userEmail);
        $updateUserStmt->bindParam(':username', $userUsername);
        $updateUserStmt->execute();
        
        echo "<div class='success'>✅ Sample user account updated successfully!</div>";
    } else {
        // Create new user account
        $insertUserQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, status, bio, created_at) 
                           VALUES (:username, :email, :password, :first_name, :last_name, :role, :status, :bio, NOW())";
        
        $insertUserStmt = $db->prepare($insertUserQuery);
        $insertUserStmt->bindParam(':username', $userUsername);
        $insertUserStmt->bindParam(':email', $userEmail);
        $insertUserStmt->bindParam(':password', $userHashedPassword);
        $insertUserStmt->bindValue(':first_name', 'Test');
        $insertUserStmt->bindValue(':last_name', 'User');
        $insertUserStmt->bindValue(':role', 'student');
        $insertUserStmt->bindValue(':status', 'active');
        $insertUserStmt->bindValue(':bio', 'Sample user account for testing EduMentor Pro platform.');
        
        if ($insertUserStmt->execute()) {
            echo "<div class='success'>✅ Sample user account created successfully!</div>";
        } else {
            echo "<div class='error'>❌ Failed to create sample user account!</div>";
        }
    }
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<div class='success'>Both admin and user accounts have been set up successfully!</div>";
    
    echo "<div class='credentials'>";
    echo "<h3>🔐 Admin Login Credentials (Admin Side):</h3>";
    echo "<p><strong>Username:</strong> <code>$adminUsername</code></p>";
    echo "<p><strong>Email:</strong> <code>$adminEmail</code></p>";
    echo "<p><strong>Password:</strong> <code>$adminPassword</code></p>";
    echo "<p><strong>Login URL:</strong> <a href='../admin-side/login.html' style='color: #f59e0b;'>Admin Login Page</a></p>";
    echo "</div>";
    
    echo "<div class='credentials'>";
    echo "<h3>👤 User Login Credentials (User Side):</h3>";
    echo "<p><strong>Username:</strong> <code>$userUsername</code></p>";
    echo "<p><strong>Email:</strong> <code>$userEmail</code></p>";
    echo "<p><strong>Password:</strong> <code>$userPassword</code></p>";
    echo "<p><strong>Login URL:</strong> <a href='../user-side/login.html' style='color: #f59e0b;'>User Login Page</a></p>";
    echo "</div>";
    
    echo "<h3>📋 Test Instructions:</h3>";
    echo "<ol style='text-align: left;'>";
    echo "<li><strong>Test Admin Login:</strong> Go to admin-side/login.html and use admin credentials</li>";
    echo "<li><strong>Test User Login:</strong> Go to user-side/login.html and use user credentials</li>";
    echo "<li><strong>Test User Registration:</strong> Go to user-side/register.html and create a new account</li>";
    echo "<li><strong>Verify Database:</strong> Check phpMyAdmin to see all accounts</li>";
    echo "</ol>";
    
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='../admin-side/login.html' style='display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; text-decoration: none; border-radius: 8px; margin: 5px;'>🔐 Test Admin Login</a>";
    echo "<a href='../user-side/login.html' style='display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #f97316, #fb923c); color: white; text-decoration: none; border-radius: 8px; margin: 5px;'>👤 Test User Login</a>";
    echo "<a href='../user-side/register.html' style='display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; text-decoration: none; border-radius: 8px; margin: 5px;'>📝 Test Registration</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>💡 Make sure XAMPP MySQL is running and database is set up!</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "    </div>
</body>
</html>";
?>
