<?php
// Database Setup Script
require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>EduMentor Pro - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .login-info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>EduMentor Pro Database Setup</h1>";

try {
    echo "<h2>Creating Database Tables...</h2>";
    
    if (createTables()) {
        echo "<p class='success'>✓ Database tables created successfully!</p>";
        
        // Insert default admin user
        echo "<h2>Creating Default Admin User...</h2>";
        createDefaultAdmin();
        
        // Insert sample data
        echo "<h2>Inserting Sample Data...</h2>";
        insertSampleData();
        
        echo "<h2>Setup Complete!</h2>";
        echo "<p class='success'>Your EduMentor Pro database has been set up successfully.</p>";
        
        echo "<div class='login-info'>";
        echo "<h3>🔐 Default Admin Login Credentials:</h3>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Email:</strong> admin@edumentor.com</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><em>Please change the default password after first login for security.</em></p>";
        echo "</div>";
        
        echo "<h3>Quick Links:</h3>";
        echo "<ul>";
        echo "<li><a href='../admin-side/login.html' target='_blank'>Admin Login</a></li>";
        echo "<li><a href='../admin-side/signup.html' target='_blank'>Request Admin Access</a></li>";
        echo "<li><a href='../user-side/index.html' target='_blank'>Main Website</a></li>";
        echo "<li><a href='../admin-side/index.html' target='_blank'>Admin Dashboard</a></li>";
        echo "</ul>";
        
    } else {
        echo "<p class='error'>✗ Failed to create database tables.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

function createDefaultAdmin() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if admin already exists
        $checkQuery = "SELECT id FROM users WHERE email = 'admin@edumentor.com' OR username = 'admin'";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            echo "<p class='info'>ℹ Default admin user already exists.</p>";
            return;
        }
        
        // Create default admin user
        $adminQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, status, bio) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $adminStmt = $db->prepare($adminQuery);
        $success = $adminStmt->execute([
            'admin',
            'admin@edumentor.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            'System',
            'Administrator',
            'admin',
            'active',
            'Default system administrator account for EduMentor Pro platform.'
        ]);
        
        if ($success) {
            echo "<p class='success'>✓ Default admin user created successfully!</p>";
        } else {
            echo "<p class='error'>✗ Failed to create default admin user.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Error creating admin user: " . $e->getMessage() . "</p>";
    }
}

function insertSampleData() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Insert sample trainers
        echo "<p class='info'>Adding sample trainers...</p>";
        
        $trainers = [
            [
                'username' => 'sarah.johnson',
                'email' => 'sarah.johnson@edumentor.com',
                'password' => password_hash('trainer123', PASSWORD_DEFAULT),
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'bio' => 'Experienced mathematics educator with 15 years of classroom experience and expertise in digital teaching methods.',
                'expertise' => 'Digital Teaching, Mathematics Education',
                'experience_years' => 15,
                'certifications' => 'M.Ed. Mathematics Education, Google Certified Educator Level 2'
            ],
            [
                'username' => 'michael.chen',
                'email' => 'michael.chen@edumentor.com',
                'password' => password_hash('trainer123', PASSWORD_DEFAULT),
                'first_name' => 'Michael',
                'last_name' => 'Chen',
                'bio' => 'Elementary education specialist focusing on classroom management and inclusive teaching strategies.',
                'expertise' => 'Classroom Management, Elementary Education',
                'experience_years' => 12,
                'certifications' => 'M.Ed. Elementary Education, Positive Behavior Support Certification'
            ],
            [
                'username' => 'emily.rodriguez',
                'email' => 'emily.rodriguez@edumentor.com',
                'password' => password_hash('trainer123', PASSWORD_DEFAULT),
                'first_name' => 'Emily',
                'last_name' => 'Rodriguez',
                'bio' => 'Language arts teacher and curriculum developer with expertise in assessment strategies.',
                'expertise' => 'Assessment, Language Arts, Curriculum Development',
                'experience_years' => 10,
                'certifications' => 'M.A. English Education, Assessment Design Certification'
            ]
        ];
        
        foreach ($trainers as $trainer) {
            // Insert user
            $userQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, bio, status) 
                         VALUES (?, ?, ?, ?, ?, 'trainer', ?, 'active')";
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute([
                $trainer['username'], $trainer['email'], $trainer['password'],
                $trainer['first_name'], $trainer['last_name'], $trainer['bio']
            ]);
            
            $userId = $db->lastInsertId();
            
            // Insert trainer record
            $trainerQuery = "INSERT INTO trainers (user_id, expertise, experience_years, certifications, rating, total_students, status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'active')";
            $trainerStmt = $db->prepare($trainerQuery);
            $trainerStmt->execute([
                $userId, $trainer['expertise'], $trainer['experience_years'],
                $trainer['certifications'], 4.5 + (rand(0, 5) / 10), rand(50, 500)
            ]);
        }
        
        // Insert sample courses
        echo "<p class='info'>Adding sample courses...</p>";
        
        $courses = [
            [
                'title' => 'Digital Teaching Methods',
                'description' => 'Comprehensive course on modern digital tools and techniques for effective online and hybrid teaching. Learn to engage students in virtual environments.',
                'short_description' => 'Master modern digital tools and techniques for effective online and hybrid teaching.',
                'category' => 'Digital Teaching',
                'level' => 'intermediate',
                'duration' => 12,
                'price' => 199.99,
                'rating' => 4.8,
                'syllabus' => 'Module 1: Introduction to Digital Teaching\nModule 2: Essential Digital Tools\nModule 3: Virtual Classroom Management\nModule 4: Student Engagement Strategies',
                'objectives' => 'Master digital teaching platforms, Create engaging online content, Manage virtual classrooms effectively',
                'requirements' => 'Basic computer skills, Internet connection, Teaching experience preferred'
            ],
            [
                'title' => 'Classroom Management Excellence',
                'description' => 'Develop effective strategies for managing diverse classrooms and student behavior. Create positive learning environments that promote student success.',
                'short_description' => 'Develop effective strategies for managing diverse classrooms and student behavior.',
                'category' => 'Classroom Management',
                'level' => 'beginner',
                'duration' => 8,
                'price' => 149.99,
                'rating' => 4.9,
                'syllabus' => 'Module 1: Classroom Environment Setup\nModule 2: Behavior Management Strategies\nModule 3: Building Positive Relationships\nModule 4: Conflict Resolution',
                'objectives' => 'Create positive classroom environments, Implement effective behavior management, Build strong student relationships',
                'requirements' => 'Teaching experience helpful but not required'
            ],
            [
                'title' => 'Modern Assessment Strategies',
                'description' => 'Learn innovative approaches to student assessment and performance evaluation. Design fair and effective assessment tools.',
                'short_description' => 'Learn innovative approaches to student assessment and performance evaluation.',
                'category' => 'Assessment',
                'level' => 'advanced',
                'duration' => 10,
                'price' => 179.99,
                'rating' => 4.7,
                'syllabus' => 'Module 1: Assessment Theory\nModule 2: Formative Assessment Techniques\nModule 3: Summative Assessment Design\nModule 4: Digital Assessment Tools',
                'objectives' => 'Design effective assessments, Use digital assessment tools, Provide meaningful feedback',
                'requirements' => 'Teaching experience required, Basic understanding of assessment principles'
            ]
        ];
        
        // Get trainer IDs for assigning courses
        $trainerQuery = "SELECT u.id FROM users u JOIN trainers t ON u.id = t.user_id WHERE u.role = 'trainer' LIMIT 3";
        $trainerStmt = $db->prepare($trainerQuery);
        $trainerStmt->execute();
        $trainerIds = $trainerStmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($courses as $index => $course) {
            $trainerId = $trainerIds[$index % count($trainerIds)];
            
            $courseQuery = "INSERT INTO courses (title, description, short_description, trainer_id, category, level, duration, price, rating, syllabus, objectives, requirements, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            $courseStmt = $db->prepare($courseQuery);
            $courseStmt->execute([
                $course['title'], $course['description'], $course['short_description'],
                $trainerId, $course['category'], $course['level'], $course['duration'],
                $course['price'], $course['rating'], $course['syllabus'],
                $course['objectives'], $course['requirements']
            ]);
        }
        
        // Insert sample blog posts
        echo "<p class='info'>Adding sample blog posts...</p>";
        
        $blogPosts = [
            [
                'title' => '5 Essential Digital Tools Every Teacher Should Know',
                'slug' => '5-essential-digital-tools-every-teacher-should-know',
                'content' => '<p>In today\'s digital age, teachers need to be equipped with the right tools to engage students effectively. Here are five essential digital tools that every educator should master:</p><h3>1. Interactive Whiteboards</h3><p>Interactive whiteboards transform traditional lessons into dynamic, engaging experiences...</p>',
                'excerpt' => 'Discover the top 5 digital tools that can transform your teaching and boost student engagement.',
                'category' => 'Digital Teaching',
                'status' => 'published'
            ],
            [
                'title' => 'Building Positive Classroom Culture: A Teacher\'s Guide',
                'slug' => 'building-positive-classroom-culture-teachers-guide',
                'content' => '<p>Creating a positive classroom culture is fundamental to student success. It sets the foundation for learning, growth, and personal development...</p>',
                'excerpt' => 'Learn proven strategies for creating a positive, inclusive classroom environment.',
                'category' => 'Classroom Management',
                'status' => 'published'
            ]
        ];
        
        // Get admin user ID for blog posts
        $adminQuery = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute();
        $adminId = $adminStmt->fetchColumn();
        
        foreach ($blogPosts as $post) {
            $blogQuery = "INSERT INTO blog_posts (title, slug, content, excerpt, author_id, category, status, views) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $blogStmt = $db->prepare($blogQuery);
            $blogStmt->execute([
                $post['title'], $post['slug'], $post['content'], $post['excerpt'],
                $adminId, $post['category'], $post['status'], rand(100, 1000)
            ]);
        }
        
        // Insert sample resources
        echo "<p class='info'>Adding sample resources...</p>";
        
        $resources = [
            [
                'title' => 'Lesson Plan Template - Elementary Math',
                'description' => 'Comprehensive lesson plan template designed specifically for elementary mathematics instruction.',
                'file_path' => '/uploads/resources/elementary-math-lesson-template.pdf',
                'file_type' => 'PDF',
                'category' => 'Lesson Plans',
                'grade_level' => 'Elementary',
                'subject' => 'Mathematics'
            ],
            [
                'title' => 'Digital Classroom Setup Guide',
                'description' => 'Step-by-step guide for setting up a digital classroom with essential tools and platforms.',
                'file_path' => '/uploads/resources/digital-classroom-setup-guide.pdf',
                'file_type' => 'PDF',
                'category' => 'Guides',
                'grade_level' => 'All Levels',
                'subject' => 'Technology'
            ]
        ];
        
        foreach ($resources as $resource) {
            $resourceQuery = "INSERT INTO resources (title, description, file_path, file_type, category, grade_level, subject, uploaded_by, downloads, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            $resourceStmt = $db->prepare($resourceQuery);
            $resourceStmt->execute([
                $resource['title'], $resource['description'], $resource['file_path'],
                $resource['file_type'], $resource['category'], $resource['grade_level'],
                $resource['subject'], $adminId, rand(10, 200)
            ]);
        }
        
        echo "<p class='success'>✓ Sample data inserted successfully!</p>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>Error inserting sample data: " . $e->getMessage() . "</p>";
    }
}

echo "</body></html>";
?>
