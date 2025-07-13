<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required data is provided
if (!isset($_POST['job_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

$job_id = (int)$_POST['job_id'];
$status = $_POST['status'];
$recruiter_id = $_SESSION['user_id'];

// Validate status
if (!in_array($status, ['open', 'closed'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Update job status with security check for recruiter ownership
    $stmt = $conn->prepare("UPDATE jobs SET status = ? WHERE id = ? AND recruiter_id = ?");
    $stmt->bind_param("sii", $status, $job_id, $recruiter_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Log the status change
        error_log("Job ID: {$job_id} status updated to {$status} by recruiter ID: {$recruiter_id}");
        
        // Redirect back to job details page
        $_SESSION['success'] = 'Job status updated successfully';
        header('Location: job-details.php?id=' . $job_id);
        exit();
    } else {
        throw new Exception('Job not found or you do not have permission to update it');
    }
} catch (Exception $e) {
    error_log("Job status update error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to update job status';
    header('Location: job-details.php?id=' . $job_id);
    exit();
}
?> 