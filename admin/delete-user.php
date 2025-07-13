<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if ID is provided
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$userId = intval($_POST['id']);

// Prevent admin from deleting their own account
if ($userId === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete user's profile first (due to foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM profiles WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Delete user's applications if they are a jobseeker
    $stmt = $conn->prepare("DELETE FROM applications WHERE jobseeker_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Delete jobs if they are a recruiter
    $stmt = $conn->prepare("DELETE FROM jobs WHERE recruiter_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Finally delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User has been successfully deleted']);
    } else {
        throw new Exception('User not found');
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 