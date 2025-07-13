<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if job ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Job ID is required']);
    exit();
}

$jobId = intval($_POST['id']);
$recruiterId = $_SESSION['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // First verify that the job belongs to this recruiter
    $stmt = $conn->prepare("SELECT id, title FROM jobs WHERE id = ? AND recruiter_id = ?");
    $stmt->bind_param("ii", $jobId, $recruiterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Job not found or you do not have permission to delete it');
    }

    $job = $result->fetch_assoc();

    // Get all applications for this job
    $stmt = $conn->prepare("SELECT resume_path FROM applications WHERE job_id = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $applications = $stmt->get_result();

    // Delete applications for this job
    $stmt = $conn->prepare("DELETE FROM applications WHERE job_id = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();

    // Delete the job
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND recruiter_id = ?");
    $stmt->bind_param("ii", $jobId, $recruiterId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Clean up application files
        while ($application = $applications->fetch_assoc()) {
            if (!empty($application['resume_path']) && file_exists($application['resume_path'])) {
                unlink($application['resume_path']);
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Job "' . htmlspecialchars($job['title']) . '" deleted successfully']);
    } else {
        throw new Exception('Failed to delete job');
    }
} catch (Exception $e) {
    $conn->rollback();
    error_log("Job deletion error: " . $e->getMessage() . " for job ID: " . $jobId);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 