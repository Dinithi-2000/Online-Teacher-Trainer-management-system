<?php
require_once '../../config/database.php';
requireLogin();

$course_id = intval($_GET['id'] ?? 0);

$pdo = getDBConnection();

// Get course data
$stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: index.php?error=' . urlencode('Course not found'));
    exit();
}

// Check if user can delete this course (admin or course creator)
if (!hasRole('admin') && $course['created_by'] != $_SESSION['user_id']) {
    header('Location: index.php?error=' . urlencode('You do not have permission to delete this course'));
    exit();
}

// Check if course has enrollments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
$stmt->execute([$course_id]);
$enrollment_count = $stmt->fetchColumn();

if ($enrollment_count > 0) {
    header('Location: index.php?error=' . urlencode('Cannot delete course with active enrollments'));
    exit();
}

// Delete course image if it's not the default
if ($course['image'] !== 'default-course.jpg') {
    $image_path = '../../assets/images/courses/' . $course['image'];
    if (file_exists($image_path)) {
        unlink($image_path);
    }
}

// Delete course
$stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
if ($stmt->execute([$course_id])) {
    header('Location: index.php?success=' . urlencode('Course deleted successfully'));
} else {
    header('Location: index.php?error=' . urlencode('Failed to delete course'));
}
exit();
?>
