<?php
// Start session
session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit();
}

// Check if application ID is provided
if (!isset($_GET['id'])) {
    header('Location: my-applications.php');
    exit();
}

// Include database connection
require_once '../includes/db.php';

// Get application ID and user ID
$application_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if application exists and belongs to the user
    $sql = "SELECT status FROM applications WHERE id = ? AND jobseeker_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $application_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Application not found or cannot be withdrawn.');
    }

    // Delete the application
    $sql = "DELETE FROM applications WHERE id = ? AND jobseeker_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $application_id, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to withdraw application.');
    }

    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = 'Application withdrawn successfully.';
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

// Redirect back to applications page
header('Location: my-applications.php');
exit(); 