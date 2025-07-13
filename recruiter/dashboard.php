<?php
session_start();

// Check if user is logged in and is recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../includes/db.php';

// Get recruiter's jobs
$recruiter_id = $_SESSION['user_id'];
$sql = "SELECT * FROM jobs WHERE recruiter_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recruiter_id);
$stmt->execute();
$result = $stmt->get_result();
$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

// Page title
$pageTitle = 'Recruiter Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Job Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Recruiter Dashboard</h1>
        
        <div class="dashboard-actions">
            <a href="post-job.php" class="btn btn-primary">Post New Job</a>
        </div>
        
        <h2>Your Posted Jobs</h2>
        <?php if (empty($jobs)): ?>
            <p>You haven't posted any jobs yet.</p>
        <?php else: ?>
            <div class="jobs-list">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($job['status']); ?></p>
                        <p><strong>Applications:</strong> <?php echo getApplicationsCount($conn, $job['id']); ?></p>
                        <div class="job-actions">
                            <a href="view-application.php?job_id=<?php echo $job['id']; ?>" class="btn btn-secondary">View Applications</a>
                            <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary">Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html> 