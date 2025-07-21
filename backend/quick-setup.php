<?php
// Simple Database Setup Script
echo "<!DOCTYPE html>
<html>
<head>
    <title>EduMentor Pro - Quick Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f0f8ff; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe8e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e8f0ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .login-box { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin: 20px 0; }
        h1 { color: #1e3a8a; text-align: center; }
        h2 { color: #f97316; }
        .btn { display: inline-block; padding: 10px 20px; background: #1e3a8a; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #3b82f6; }
    </style>
</head>
<body>
    <h1>🚀 EduMentor Pro - Quick Setup</h1>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'edumentor_pro';

try {
    echo "<h2>Step 1: Connecting to MySQL...</h2>";
    
    // Connect to MySQL server without specifying database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Successfully connected to MySQL server!</div>";
    
    echo "<h2>Step 2: Creating Database...</h2>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
    echo "<div class='success'>✅ Database '$database' created successfully!</div>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Connected to database '$database'!</div>";
    
    echo "<h2>Step 3: Creating Tables...</h2>";
    
    // Create users table
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        role ENUM('admin', 'trainer', 'student') DEFAULT 'student',
        profile_image VARCHAR(255) DEFAULT NULL,
        bio TEXT,
        status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
        remember_token VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )";
    
    $pdo->exec($users_table);
    echo "<div class='success'>✅ Users table created!</div>";
    
    // Create courses table
    $courses_table = "CREATE TABLE IF NOT EXISTS courses (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        short_description VARCHAR(500),
        instructor_id INT(11),
        category VARCHAR(100),
        level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
        duration INT(11) DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0.00,
        rating DECIMAL(3,2) DEFAULT 0.00,
        image VARCHAR(255),
        syllabus TEXT,
        objectives TEXT,
        requirements TEXT,
        status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $pdo->exec($courses_table);
    echo "<div class='success'>✅ Courses table created!</div>";
    
    // Create enrollments table
    $enrollments_table = "CREATE TABLE IF NOT EXISTS enrollments (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        course_id INT(11) NOT NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        progress DECIMAL(5,2) DEFAULT 0.00,
        status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
        completion_date TIMESTAMP NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_enrollment (user_id, course_id)
    )";
    
    $pdo->exec($enrollments_table);
    echo "<div class='success'>✅ Enrollments table created!</div>";
    
    echo "<h2>Step 4: Creating Admin User...</h2>";
    
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@edumentor.com' OR username = 'admin'");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "<div class='info'>ℹ️ Admin user already exists!</div>";
    } else {
        // Create admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, status, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([
            'admin',
            'admin@edumentor.com',
            $adminPassword,
            'System',
            'Administrator',
            'admin',
            'active',
            'Default system administrator account for EduMentor Pro platform.'
        ]);
        
        if ($success) {
            echo "<div class='success'>✅ Admin user created successfully!</div>";
        } else {
            echo "<div class='error'>❌ Failed to create admin user!</div>";
        }
    }
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<div class='success'>Your EduMentor Pro database has been set up successfully!</div>";
    
    echo "<div class='login-box'>";
    echo "<h3>🔐 Admin Login Credentials:</h3>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Email:</strong> admin@edumentor.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><em>⚠️ Please change the default password after first login for security.</em></p>";
    echo "</div>";
    
    echo "<h3>Quick Links:</h3>";
    echo "<a href='../admin-side/login.html' class='btn'>🔐 Admin Login</a>";
    echo "<a href='../user-side/index.html' class='btn'>🌐 Main Website</a>";
    echo "<a href='../admin-side/index.html' class='btn'>📊 Admin Dashboard</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>💡 Make sure XAMPP MySQL service is running!</div>";
    echo "<div class='info'>💡 Check your database credentials in config.php</div>";
}

echo "</body></html>";
?>
