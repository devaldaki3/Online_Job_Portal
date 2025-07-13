<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../includes/db.php';

// Check if ID and status are provided
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = "Missing required parameters.";
    header('Location: manage-applications.php');
    exit();
}

$application_id = (int)$_GET['id'];
$status = $_GET['status'];

// Validate status
$valid_statuses = ['pending', 'reviewed', 'accepted', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    $_SESSION['error'] = "Invalid status provided.";
    header('Location: manage-applications.php');
    exit();
}

// Update application status
$sql = "UPDATE applications SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $application_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Application status updated successfully.";
} else {
    $_SESSION['error'] = "Failed to update application status.";
}

// Redirect back to manage applications page
header('Location: manage-applications.php');
exit();
?> 