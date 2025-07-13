<?php
session_start();

// Check if user is logged in and is jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../includes/db.php';

// Get jobseeker's applications
$jobseeker_id = $_SESSION['user_id'];
$sql = "SELECT a.*, j.title, j.company_name, j.location, j.job_type 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE a.jobseeker_id = ? 
        ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $jobseeker_id);
$stmt->execute();
$result = $stmt->get_result();
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}

// Get recommended jobs
$sql = "SELECT * FROM jobs WHERE status = 'open' ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);
$recommended_jobs = [];
while ($row = $result->fetch_assoc()) {
    $recommended_jobs[] = $row;
}

// Page title
$pageTitle = 'Job Seeker Dashboard';
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
        <h1>Job Seeker Dashboard</h1>
        
        <div class="dashboard-sections">
            <div class="section">
                <h2>Your Applications</h2>
                <?php if (empty($applications)): ?>
                    <p>You haven't applied to any jobs yet.</p>
                <?php else: ?>
                    <div class="applications-list">
                        <?php foreach ($applications as $application): ?>
                            <div class="application-card">
                                <h3><?php echo htmlspecialchars($application['title']); ?></h3>
                                <p><strong>Company:</strong> <?php echo htmlspecialchars($application['company_name']); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($application['location']); ?></p>
                                <p><strong>Type:</strong> <?php echo htmlspecialchars($application['job_type']); ?></p>
                                <p><strong>Status:</strong> <?php echo htmlspecialchars($application['status']); ?></p>
                                <p><strong>Applied on:</strong> <?php echo formatDate($application['created_at']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Recommended Jobs</h2>
                <?php if (empty($recommended_jobs)): ?>
                    <p>No jobs available at the moment.</p>
                <?php else: ?>
                    <div class="jobs-list">
                        <?php foreach ($recommended_jobs as $job): ?>
                            <div class="job-card">
                                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company_name']); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                                <p><strong>Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?></p>
                                <a href="../view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html> 