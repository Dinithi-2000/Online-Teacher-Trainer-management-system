<?php
require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Check if sample courses already exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM courses");
    $stmt->execute();
    $courseCount = $stmt->fetchColumn();
    
    if ($courseCount >= 5) {
        echo json_encode([
            'success' => true,
            'message' => "Already have $courseCount courses in database"
        ]);
        exit;
    }
    
    // Get trainer IDs (or create sample trainers if they don't exist)
    $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('trainer', 'admin') LIMIT 3");
    $stmt->execute();
    $trainers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($trainers)) {
        // Create sample trainers
        $sampleTrainers = [
            ['Dr. Sarah Johnson', 'sarah.johnson@edumentor.com', 'Digital Learning Expert'],
            ['Prof. Michael Chen', 'michael.chen@edumentor.com', 'Classroom Management Specialist'],
            ['Dr. Emma Wilson', 'emma.wilson@edumentor.com', 'Assessment & Evaluation Expert']
        ];
        
        foreach ($sampleTrainers as $trainer) {
            $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, bio) VALUES (?, ?, ?, ?, ?, 'trainer', ?)");
            $password = password_hash('trainer123', PASSWORD_DEFAULT);
            $nameParts = explode(' ', $trainer[0]);
            $firstName = $nameParts[1] ?? 'Teacher';
            $lastName = $nameParts[2] ?? 'Expert';
            $username = strtolower(str_replace([' ', '.'], ['', ''], $trainer[0]));
            
            $stmt->execute([$username, $trainer[1], $password, $firstName, $lastName, $trainer[2]]);
            $trainers[] = $db->lastInsertId();
        }
    }
    
    // Sample courses data
    $sampleCourses = [
        [
            'title' => 'Digital Teaching Tools Mastery',
            'description' => 'Master the latest digital tools and platforms for modern education. Learn to integrate technology seamlessly into your classroom to enhance student engagement and learning outcomes.',
            'short_description' => 'Master digital tools for modern education and enhance student engagement.',
            'category' => 'Digital Teaching',
            'level' => 'intermediate',
            'duration' => 40,
            'price' => 149.99,
            'syllabus' => '• Introduction to Educational Technology\n• Interactive Whiteboard Usage\n• Online Learning Platforms\n• Digital Assessment Tools\n• Student Engagement Strategies',
            'objectives' => '• Effectively use digital teaching tools\n• Create engaging online content\n• Implement blended learning strategies\n• Assess students digitally',
            'requirements' => 'Basic computer skills, internet connection, willingness to learn new technologies',
            'image' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=500&h=300&fit=crop',
            'status' => 'active'
        ],
        [
            'title' => 'Classroom Management Excellence',
            'description' => 'Develop effective classroom management strategies that create a positive learning environment. Learn techniques for behavior management, student motivation, and creating inclusive classrooms.',
            'short_description' => 'Create positive learning environments with proven management strategies.',
            'category' => 'Classroom Management',
            'level' => 'beginner',
            'duration' => 30,
            'price' => 99.99,
            'syllabus' => '• Establishing Classroom Rules\n• Behavior Management Techniques\n• Student Motivation Strategies\n• Conflict Resolution\n• Parent Communication',
            'objectives' => '• Establish effective classroom rules\n• Manage student behavior positively\n• Motivate students to learn\n• Handle conflicts professionally',
            'requirements' => 'Teaching experience helpful but not required',
            'image' => 'https://images.unsplash.com/photo-1573164713988-8665fc963095?w=500&h=300&fit=crop',
            'status' => 'active'
        ],
        [
            'title' => 'Modern Assessment Strategies',
            'description' => 'Transform your assessment practices with innovative evaluation methods. Learn formative and summative assessment techniques, rubric development, and providing meaningful feedback.',
            'short_description' => 'Innovative assessment methods for meaningful student evaluation.',
            'category' => 'Assessment',
            'level' => 'advanced',
            'duration' => 50,
            'price' => 199.99,
            'syllabus' => '• Assessment Planning\n• Formative vs Summative Assessment\n• Rubric Development\n• Feedback Strategies\n• Portfolio Assessment',
            'objectives' => '• Design effective assessments\n• Create comprehensive rubrics\n• Provide constructive feedback\n• Use assessment data for improvement',
            'requirements' => 'Teaching experience, familiarity with grading systems',
            'image' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=500&h=300&fit=crop',
            'status' => 'active'
        ],
        [
            'title' => 'Inclusive Education Fundamentals',
            'description' => 'Create inclusive learning environments that support all students. Learn about differentiated instruction, special needs accommodation, and culturally responsive teaching.',
            'short_description' => 'Support all learners with inclusive education strategies.',
            'category' => 'Special Education',
            'level' => 'intermediate',
            'duration' => 45,
            'price' => 169.99,
            'syllabus' => '• Understanding Diversity\n• Differentiated Instruction\n• Special Needs Support\n• Culturally Responsive Teaching\n• Accessibility in Education',
            'objectives' => '• Understand learning differences\n• Adapt instruction for all students\n• Support special needs learners\n• Create culturally inclusive classrooms',
            'requirements' => 'Basic teaching knowledge, open mindset',
            'image' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=500&h=300&fit=crop',
            'status' => 'active'
        ],
        [
            'title' => 'Educational Leadership Development',
            'description' => 'Develop leadership skills for educational settings. Learn team management, curriculum planning, staff development, and strategic planning for educational institutions.',
            'short_description' => 'Develop leadership skills for educational excellence.',
            'category' => 'Leadership',
            'level' => 'advanced',
            'duration' => 60,
            'price' => 249.99,
            'syllabus' => '• Leadership Principles\n• Team Management\n• Curriculum Development\n• Staff Training\n• Strategic Planning',
            'objectives' => '• Lead educational teams effectively\n• Develop comprehensive curricula\n• Train and mentor staff\n• Plan institutional growth',
            'requirements' => 'Teaching experience, leadership interest, management background helpful',
            'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=500&h=300&fit=crop',
            'status' => 'active'
        ],
        [
            'title' => 'STEM Education Innovation',
            'description' => 'Innovative approaches to teaching Science, Technology, Engineering, and Mathematics. Learn hands-on activities, project-based learning, and real-world applications.',
            'short_description' => 'Innovative STEM teaching with hands-on learning approaches.',
            'category' => 'STEM',
            'level' => 'intermediate',
            'duration' => 55,
            'price' => 189.99,
            'syllabus' => '• STEM Pedagogy\n• Hands-on Experiments\n• Project-Based Learning\n• Technology Integration\n• Real-World Applications',
            'objectives' => '• Teach STEM subjects effectively\n• Design engaging experiments\n• Implement project-based learning\n• Connect learning to real world',
            'requirements' => 'Basic science knowledge, interest in technology',
            'image' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=500&h=300&fit=crop',
            'status' => 'active'
        ]
    ];
    
    $insertedCount = 0;
    $stmt = $db->prepare("INSERT INTO courses (title, description, short_description, instructor_id, category, level, duration, price, syllabus, objectives, requirements, image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    foreach ($sampleCourses as $course) {
        // Assign random instructor
        $instructorId = $trainers[array_rand($trainers)];
        
        $result = $stmt->execute([
            $course['title'],
            $course['description'],
            $course['short_description'],
            $instructorId,
            $course['category'],
            $course['level'],
            $course['duration'],
            $course['price'],
            $course['syllabus'],
            $course['objectives'],
            $course['requirements'],
            $course['image'],
            $course['status']
        ]);
        
        if ($result) {
            $insertedCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully added $insertedCount sample courses to the database",
        'inserted' => $insertedCount,
        'total_courses' => $courseCount + $insertedCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
