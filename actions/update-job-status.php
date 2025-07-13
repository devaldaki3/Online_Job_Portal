<?php
// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../manage-jobs.php');
    exit();
}

// Include database connection
require_once '../includes/db.php';

// Get job ID and status from POST data
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validate status
if (!in_array($status, ['open', 'closed'])) {
    $_SESSION['error'] = 'Invalid status provided.';
    header('Location: ../job-details.php?id=' . $job_id);
    exit();
}

// Update job status
$sql = "UPDATE jobs SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $job_id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Job status updated successfully.';
} else {
    $_SESSION['error'] = 'Failed to update job status.';
}

// Redirect back to job details
header('Location: ../job-details.php?id=' . $job_id);
exit();
?> 