<?php
require_once 'config/database.php';

$pdo = getDBConnection();

echo "<h2>Complete Database Setup for Reviews Testing</h2>";

try {
    // 1. Add sample users if they don't exist
    echo "<h3>1. Setting up Users:</h3>";
    $user_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $user_count_stmt->execute();
    $user_count = $user_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($user_count == 0) {
        $sample_users = [
            ['john_doe', 'John Doe', 'john@example.com', password_hash('password123', PASSWORD_DEFAULT), 'student'],
            ['jane_smith', 'Jane Smith', 'jane@example.com', password_hash('password123', PASSWORD_DEFAULT), 'trainer'],
            ['mike_wilson', 'Mike Wilson', 'mike@example.com', password_hash('password123', PASSWORD_DEFAULT), 'student'],
            ['admin_user', 'Admin User', 'admin@example.com', password_hash('password123', PASSWORD_DEFAULT), 'admin']
        ];
        
        $user_stmt = $pdo->prepare("INSERT INTO users (username, name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        foreach ($sample_users as $user) {
            $user_stmt->execute($user);
        }
        echo "✅ Added " . count($sample_users) . " sample users<br>";
    } else {
        echo "ℹ️ Found $user_count existing users<br>";
    }
    
    // 2. Add sample courses if they don't exist
    echo "<h3>2. Setting up Courses:</h3>";
    $course_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses");
    $course_count_stmt->execute();
    $course_count = $course_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($course_count == 0) {
        // Get trainer user
        $trainer_stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'trainer' LIMIT 1");
        $trainer_stmt->execute();
        $trainer = $trainer_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trainer) {
            $sample_courses = [
                ['Introduction to Web Development', 'Learn the basics of HTML, CSS, and JavaScript', 'default-course.jpg', 99.99, $trainer['user_id']],
                ['Advanced PHP Programming', 'Master PHP for backend development', 'default-course.jpg', 149.99, $trainer['user_id']],
                ['Database Design Fundamentals', 'Learn to design efficient databases', 'default-course.jpg', 129.99, $trainer['user_id']]
            ];
            
            $course_stmt = $pdo->prepare("INSERT INTO courses (title, description, course_image, price, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            
            foreach ($sample_courses as $course) {
                $course_stmt->execute($course);
            }
            echo "✅ Added " . count($sample_courses) . " sample courses<br>";
        } else {
            echo "❌ No trainer found to create courses<br>";
        }
    } else {
        echo "ℹ️ Found $course_count existing courses<br>";
    }
    
    // 3. Add sample enrollments
    echo "<h3>3. Setting up Enrollments:</h3>";
    $enrollment_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments");
    $enrollment_count_stmt->execute();
    $enrollment_count = $enrollment_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($enrollment_count == 0) {
        // Get students and courses
        $students_stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'student' LIMIT 3");
        $students_stmt->execute();
        $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $courses_stmt = $pdo->prepare("SELECT course_id FROM courses LIMIT 3");
        $courses_stmt->execute();
        $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($students) && !empty($courses)) {
            $enrollment_stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())");
            
            $enrollments_added = 0;
            foreach ($students as $student) {
                foreach ($courses as $course) {
                    $enrollment_stmt->execute([$student['user_id'], $course['course_id']]);
                    $enrollments_added++;
                }
            }
            echo "✅ Added $enrollments_added sample enrollments<br>";
        } else {
            echo "❌ No students or courses found for enrollments<br>";
        }
    } else {
        echo "ℹ️ Found $enrollment_count existing enrollments<br>";
    }
    
    // 4. Add sample reviews
    echo "<h3>4. Setting up Reviews:</h3>";
    $review_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews");
    $review_count_stmt->execute();
    $review_count = $review_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Clear existing reviews and add fresh ones
    $pdo->prepare("DELETE FROM reviews")->execute();
    
    // Get enrolled students and courses
    $enrolled_stmt = $pdo->prepare("
        SELECT DISTINCT e.user_id, e.course_id, u.name as user_name, c.title as course_title
        FROM enrollments e
        JOIN users u ON e.user_id = u.user_id
        JOIN courses c ON e.course_id = c.course_id
        LIMIT 5
    ");
    $enrolled_stmt->execute();
    $enrolled_data = $enrolled_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($enrolled_data)) {
        $sample_reviews = [
            ['rating' => 5, 'comment' => 'Excellent course! Very comprehensive and well-structured. Learned a lot!'],
            ['rating' => 4, 'comment' => 'Great content and good instructor. Would recommend to others.'],
            ['rating' => 5, 'comment' => 'Outstanding material and presentation. Worth every penny!'],
            ['rating' => 4, 'comment' => 'Very informative course. Some sections could be more detailed.'],
            ['rating' => 5, 'comment' => 'Perfect for beginners and advanced learners alike. Highly recommend!']
        ];
        
        $review_stmt = $pdo->prepare("INSERT INTO reviews (user_id, course_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        $reviews_added = 0;
        foreach ($enrolled_data as $index => $enrollment) {
            $review_data = $sample_reviews[$index % count($sample_reviews)];
            $review_stmt->execute([
                $enrollment['user_id'],
                $enrollment['course_id'],
                $review_data['rating'],
                $review_data['comment']
            ]);
            $reviews_added++;
        }
        echo "✅ Added $reviews_added sample reviews<br>";
    } else {
        echo "❌ No enrollments found to create reviews<br>";
    }
    
    // 5. Verify setup
    echo "<h3>5. Setup Verification:</h3>";
    
    // Test the main query used in reviews/index.php
    $main_query_stmt = $pdo->prepare("
        SELECT r.*, c.title as course_title, c.course_image, u.name as user_name,
               tp.profile_image as trainer_image, trainer.name as trainer_name
        FROM reviews r
        JOIN courses c ON r.course_id = c.course_id
        JOIN users u ON r.user_id = u.user_id
        LEFT JOIN users trainer ON c.created_by = trainer.user_id
        LEFT JOIN trainer_profiles tp ON trainer.user_id = tp.user_id
        ORDER BY r.created_at DESC
    ");
    $main_query_stmt->execute();
    $main_query_results = $main_query_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Main reviews query returns " . count($main_query_results) . " reviews<br>";
    
    if (!empty($main_query_results)) {
        echo "<h4>Sample review data:</h4>";
        $sample = $main_query_results[0];
        echo "<ul>";
        echo "<li>Review ID: {$sample['review_id']}</li>";
        echo "<li>User: {$sample['user_name']}</li>";
        echo "<li>Course: {$sample['course_title']}</li>";
        echo "<li>Rating: {$sample['rating']}/5</li>";
        echo "<li>Comment: " . substr($sample['comment'], 0, 50) . "...</li>";
        echo "</ul>";
    }
    
    echo "<br><h3>✅ Database setup complete!</h3>";
    echo "<p><a href='modules/reviews/index.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Reviews Page</a></p>";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
?>
