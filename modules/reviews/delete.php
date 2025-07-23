<?php
require_once '../../config/database.php';
requireLogin();

$review_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

$pdo = getDBConnection();

// Verify review belongs to current user
$stmt = $pdo->prepare("SELECT r.*, c.title FROM reviews r JOIN courses c ON r.course_id = c.course_id WHERE r.review_id = ? AND r.user_id = ?");
$stmt->execute([$review_id, $user_id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review) {
    header('Location: ../../courses.php?error=' . urlencode('Review not found'));
    exit();
}

// Delete review
$stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
if ($stmt->execute([$review_id])) {
    header('Location: ../../course-details.php?id=' . $review['course_id'] . '&success=' . urlencode('Review deleted successfully'));
} else {
    header('Location: ../../course-details.php?id=' . $review['course_id'] . '&error=' . urlencode('Failed to delete review'));
}
exit();
?>
