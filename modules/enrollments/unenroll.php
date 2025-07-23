<?php
require_once '../../config/database.php';
requireLogin();

$enroll_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

$pdo = getDBConnection();

// Verify enrollment belongs to current user
$stmt = $pdo->prepare("SELECT e.*, c.title FROM enrollments e JOIN courses c ON e.course_id = c.course_id WHERE e.enroll_id = ? AND e.user_id = ?");
$stmt->execute([$enroll_id, $user_id]);
$enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$enrollment) {
    header('Location: index.php?error=' . urlencode('Enrollment not found'));
    exit();
}

// Delete enrollment
$stmt = $pdo->prepare("DELETE FROM enrollments WHERE enroll_id = ?");
if ($stmt->execute([$enroll_id])) {
    header('Location: index.php?success=' . urlencode('Successfully unenrolled from ' . $enrollment['title']));
} else {
    header('Location: index.php?error=' . urlencode('Failed to unenroll from course'));
}
exit();
?>
