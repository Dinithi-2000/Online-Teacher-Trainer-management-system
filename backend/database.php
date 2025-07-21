<?php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // First, try to connect without specifying database to create it if needed
            $this->conn = new PDO("mysql:host=" . $this->host, 
                                $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS `" . $this->db_name . "`");
            
            // Now connect to the specific database
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Create database tables
function createTables() {
    $database = new Database();
    $db = $database->getConnection();
    
    // Users table
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('active', 'inactive') DEFAULT 'active',
        PRIMARY KEY (id)
    )";
    
    // Courses table
    $courses_table = "CREATE TABLE IF NOT EXISTS courses (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        short_description VARCHAR(500),
        trainer_id INT(11),
        category VARCHAR(100),
        level ENUM('beginner', 'intermediate', 'advanced'),
        duration INT(11) DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0.00,
        rating DECIMAL(3,2) DEFAULT 0.00,
        image VARCHAR(255),
        syllabus TEXT,
        objectives TEXT,
        requirements TEXT,
        status ENUM('active', 'inactive', 'draft') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    // Trainers table
    $trainers_table = "CREATE TABLE IF NOT EXISTS trainers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        expertise TEXT,
        experience_years INT(11) DEFAULT 0,
        certifications TEXT,
        rating DECIMAL(3,2) DEFAULT 0.00,
        total_students INT(11) DEFAULT 0,
        total_courses INT(11) DEFAULT 0,
        status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    // Blog posts table
    $blog_table = "CREATE TABLE IF NOT EXISTS blog_posts (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        content TEXT NOT NULL,
        excerpt VARCHAR(500),
        author_id INT(11),
        category VARCHAR(100),
        featured_image VARCHAR(255),
        status ENUM('published', 'draft', 'archived') DEFAULT 'draft',
        views INT(11) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    // Resources table
    $resources_table = "CREATE TABLE IF NOT EXISTS resources (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255) NOT NULL,
        file_type VARCHAR(50),
        category VARCHAR(100),
        grade_level VARCHAR(50),
        subject VARCHAR(100),
        downloads INT(11) DEFAULT 0,
        uploaded_by INT(11),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    // Enrollments table
    $enrollments_table = "CREATE TABLE IF NOT EXISTS enrollments (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        course_id INT(11) NOT NULL,
        enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completion_date TIMESTAMP NULL,
        progress DECIMAL(5,2) DEFAULT 0.00,
        status ENUM('enrolled', 'completed', 'dropped') DEFAULT 'enrolled',
        PRIMARY KEY (id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_enrollment (user_id, course_id)
    )";
    
    // Forum topics table
    $forum_topics_table = "CREATE TABLE IF NOT EXISTS forum_topics (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        category VARCHAR(100),
        created_by INT(11),
        views INT(11) DEFAULT 0,
        replies_count INT(11) DEFAULT 0,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'closed', 'pinned') DEFAULT 'active',
        PRIMARY KEY (id),
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    // Forum replies table
    $forum_replies_table = "CREATE TABLE IF NOT EXISTS forum_replies (
        id INT(11) NOT NULL AUTO_INCREMENT,
        topic_id INT(11) NOT NULL,
        user_id INT(11),
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    // Certifications table
    $certifications_table = "CREATE TABLE IF NOT EXISTS certifications (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        course_id INT(11) NOT NULL,
        certificate_code VARCHAR(100) NOT NULL UNIQUE,
        issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        valid_until DATE,
        status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
        PRIMARY KEY (id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )";
    
    // Webinars table
    $webinars_table = "CREATE TABLE IF NOT EXISTS webinars (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        presenter_id INT(11),
        scheduled_date DATETIME,
        duration INT(11) DEFAULT 60,
        video_url VARCHAR(255),
        thumbnail VARCHAR(255),
        max_participants INT(11) DEFAULT 100,
        registered_count INT(11) DEFAULT 0,
        status ENUM('scheduled', 'live', 'completed', 'cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (presenter_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    try {
        $db->exec($users_table);
        $db->exec($courses_table);
        $db->exec($trainers_table);
        $db->exec($blog_table);
        $db->exec($resources_table);
        $db->exec($enrollments_table);
        $db->exec($forum_topics_table);
        $db->exec($forum_replies_table);
        $db->exec($certifications_table);
        $db->exec($webinars_table);
        
        // Insert default admin user
        $admin_check = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $admin_check->execute();
        
        if ($admin_check->fetchColumn() == 0) {
            $admin_insert = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $admin_insert->execute(['admin', 'admin@edumentor.com', $admin_password, 'Admin', 'User', 'admin']);
        }
        
        return true;
    } catch(PDOException $exception) {
        echo "Error creating tables: " . $exception->getMessage();
        return false;
    }
}
?>
