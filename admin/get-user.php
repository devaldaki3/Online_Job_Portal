<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in and is an admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Check if ID is provided
    if (!isset($_GET['id'])) {
        throw new Exception('User ID is required');
    }

    $userId = intval($_GET['id']);

    // Prepare and execute query
    $sql = "SELECT u.*, p.company_name, p.phone, p.address 
            FROM users u 
            LEFT JOIN profiles p ON u.id = p.user_id 
            WHERE u.id = ?";

    if (!$stmt = $conn->prepare($sql)) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    if (!$stmt->bind_param("i", $userId)) {
        throw new Exception('Failed to bind parameters: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception('Failed to get result: ' . $stmt->error);
    }

    if ($user = $result->fetch_assoc()) {
        // Remove sensitive information
        unset($user['password']);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        throw new Exception('User not found');
    }

} catch (Exception $e) {
    // Log the error
    error_log('Error in get-user.php: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching user details: ' . $e->getMessage()
    ]);
} finally {
    // Clean up
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 