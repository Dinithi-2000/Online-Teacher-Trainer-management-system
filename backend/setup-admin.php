<?php
// Dedicated Admin Setup Script
require_once 'config.php';
require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Setup - EduMentor Pro</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: linear-gradient(135deg, #1e3a8a, #f97316); color: white; }
        .container { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 30px; }
        .success { color: #22c55e; background: rgba(34, 197, 94, 0.2); padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { color: #ef4444; background: rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 8px; margin: 10px 0; }
        .admin-creds { background: rgba(30, 58, 138, 0.3); padding: 20px; border-radius: 10px; margin: 20px 0; border: 2px solid #3b82f6; }
        h1 { text-align: center; color: white; }
        code { background: rgba(0, 0, 0, 0.3); padding: 4px 8px; border-radius: 4px; }
        .btn { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Admin Account Setup</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Dedicated Admin Credentials
    $adminUsername = 'edumentor_admin';
    $adminEmail = 'admin@edumentor.com';
    $adminPassword = 'Admin2025@Pro';
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    echo "<h2>Creating Dedicated Admin Account...</h2>";
    
    // Remove any existing admin accounts with this username/email
    $deleteQuery = "DELETE FROM users WHERE email = :email OR username = :username";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':email', $adminEmail);
    $deleteStmt->bindParam(':username', $adminUsername);
    $deleteStmt->execute();
    
    // Create the dedicated admin account
    $insertQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, status, bio, created_at) 
                   VALUES (:username, :email, :password, :first_name, :last_name, :role, :status, :bio, NOW())";
    
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':username', $adminUsername);
    $insertStmt->bindParam(':email', $adminEmail);
    $insertStmt->bindParam(':password', $hashedPassword);
    $insertStmt->bindValue(':first_name', 'EduMentor');
    $insertStmt->bindValue(':last_name', 'Admin');
    $insertStmt->bindValue(':role', 'admin');
    $insertStmt->bindValue(':status', 'active');
    $insertStmt->bindValue(':bio', 'Dedicated administrator account for EduMentor Pro platform management.');
    
    if ($insertStmt->execute()) {
        echo "<div class='success'>✅ Admin account created successfully!</div>";
        
        echo "<div class='admin-creds'>";
        echo "<h3>🔐 ADMIN LOGIN CREDENTIALS</h3>";
        echo "<p><strong>Username:</strong> <code>$adminUsername</code></p>";
        echo "<p><strong>Email:</strong> <code>$adminEmail</code></p>";
        echo "<p><strong>Password:</strong> <code>$adminPassword</code></p>";
        echo "<p><strong>Role:</strong> Administrator</p>";
        echo "<p><strong>Status:</strong> Active</p>";
        echo "</div>";
        
        echo "<h3>🎯 Admin Login Instructions:</h3>";
        echo "<ol>";
        echo "<li>Go to the admin login page</li>";
        echo "<li>Enter the username: <code>$adminUsername</code></li>";
        echo "<li>Enter the password: <code>$adminPassword</code></li>";
        echo "<li>Access the admin dashboard</li>";
        echo "</ol>";
        
        echo "<div style='text-align: center; margin-top: 30px;'>";
        echo "<a href='../admin-side/login.html' class='btn'>🔐 Go to Admin Login</a>";
        echo "</div>";
        
    } else {
        echo "<div class='error'>❌ Failed to create admin account!</div>";
    }
    
    // Also create a sample user for testing
    echo "<h2>Creating Sample User Account...</h2>";
    
    $userUsername = 'demo_student';
    $userEmail = 'student@demo.com';
    $userPassword = 'Student123';
    $userHashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
    
    // Remove existing demo user
    $deleteUserQuery = "DELETE FROM users WHERE email = :email OR username = :username";
    $deleteUserStmt = $db->prepare($deleteUserQuery);
    $deleteUserStmt->bindParam(':email', $userEmail);
    $deleteUserStmt->bindParam(':username', $userUsername);
    $deleteUserStmt->execute();
    
    // Create demo user
    $insertUserQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, status, bio, created_at) 
                       VALUES (:username, :email, :password, :first_name, :last_name, :role, :status, :bio, NOW())";
    
    $insertUserStmt = $db->prepare($insertUserQuery);
    $insertUserStmt->bindParam(':username', $userUsername);
    $insertUserStmt->bindParam(':email', $userEmail);
    $insertUserStmt->bindParam(':password', $userHashedPassword);
    $insertUserStmt->bindValue(':first_name', 'Demo');
    $insertUserStmt->bindValue(':last_name', 'Student');
    $insertUserStmt->bindValue(':role', 'student');
    $insertUserStmt->bindValue(':status', 'active');
    $insertUserStmt->bindValue(':bio', 'Demo student account for testing the EduMentor Pro platform.');
    
    if ($insertUserStmt->execute()) {
        echo "<div class='success'>✅ Demo student account created!</div>";
        echo "<p><strong>Demo Student Login:</strong> $userEmail / $userPassword</p>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "    </div>
</body>
</html>";
?>
