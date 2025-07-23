<?php
require_once '../../config/database.php';

// Handle AJAX progress update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please log in']);
        exit();
    }
    
    $enroll_id = intval($_POST['enroll_id'] ?? 0);
    $progress = intval($_POST['progress'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Validate progress
    if ($progress < 0 || $progress > 100) {
        echo json_encode(['success' => false, 'message' => 'Invalid progress value']);
        exit();
    }
    
    $pdo = getDBConnection();
    
    // Verify enrollment belongs to current user
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE enroll_id = ? AND user_id = ?");
    $stmt->execute([$enroll_id, $user_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        echo json_encode(['success' => false, 'message' => 'Enrollment not found']);
        exit();
    }
    
    // Determine status based on progress
    $status = 'enrolled';
    if ($progress > 0 && $progress < 100) {
        $status = 'in_progress';
    } elseif ($progress == 100) {
        $status = 'completed';
    }
    
    // Update progress and status
    try {
        $stmt = $pdo->prepare("UPDATE enrollments SET progress = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE enroll_id = ?");
        if ($stmt->execute([$progress, $status, $enroll_id])) {
            echo json_encode([
                'success' => true, 
                'message' => 'Progress updated successfully!',
                'progress' => $progress,
                'status' => $status
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    exit();
}

// If not POST request, redirect to dashboard
header('Location: ../../dashboard.php');
exit();
?>
