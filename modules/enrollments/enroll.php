<?php
require_once '../../config/database.php';

// Handle AJAX enrollment request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please log in to enroll']);
        exit();
    }
    
    $course_id = intval($_POST['course_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($course_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid course']);
        exit();
    }
    
    $pdo = getDBConnection();
    
    // Check if course exists
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit();
    }
    
    // Check if user is already enrolled
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    $existing_enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_enrollment) {
        echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course']);
        exit();
    }
    
    // Create enrollment
    try {
        $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, progress, status) VALUES (?, ?, 0, 'enrolled')");
        if ($stmt->execute([$user_id, $course_id])) {
            echo json_encode(['success' => true, 'message' => 'Successfully enrolled in course!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to enroll. Please try again.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Enrollment failed']);
    }
    exit();
}

// If not POST request, redirect to courses page
header('Location: ../../courses.php');
exit();
?>
