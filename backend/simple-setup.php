<?php
// Super Simple Database Setup
echo "<h1>EduMentor Pro - Simple Database Setup</h1>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;} .success{color:green;background:#e8f5e8;padding:10px;margin:10px 0;} .error{color:red;background:#ffe8e8;padding:10px;margin:10px 0;} .info{color:blue;background:#e8f0ff;padding:10px;margin:10px 0;}</style>";

try {
    // Database connection
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'edumentor_pro';
    
    echo "<h2>Connecting to MySQL...</h2>";
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Connected to MySQL!</div>";
    
    echo "<h2>Creating Database...</h2>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "<div class='success'>✅ Database created!</div>";
    
    $pdo->exec("USE $dbname");
    echo "<div class='success'>✅ Using database $dbname</div>";
    
    echo "<h2>Creating Admin User Table...</h2>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        role ENUM('admin', 'trainer', 'student') DEFAULT 'student',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✅ Users table created!</div>";
    
    echo "<h2>Creating Admin Account...</h2>";
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "<div class='info'>ℹ️ Admin user already exists!</div>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@edumentor.com', $hashedPassword, 'System', 'Administrator', 'admin', 'active']);
        echo "<div class='success'>✅ Admin user created!</div>";
    }
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<div class='success'>";
    echo "<h3>Login Credentials:</h3>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><strong>Login URL:</strong> <a href='../admin-side/login.html'>Click here to login</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>💡 Make sure XAMPP MySQL is running!</div>";
}
?>
