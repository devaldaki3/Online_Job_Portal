<?php
// Start session
session_start();

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header('Location: my-jobs.php');
    exit();
}

// Page title
$pageTitle = 'View Job';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get job ID
$job_id = (int)$_GET['id'];

// Get job details
$job = getJobById($conn, $job_id);

// Check if job exists and belongs to the current recruiter
if (!$job || $job['recruiter_id'] != $_SESSION['user_id']) {
    header('Location: my-jobs.php');
    exit();
}

// Get application statistics
$total_applications = getApplicationsCount($conn, $job_id);
$pending_count = getApplicationCountByStatus($conn, $job_id, 'pending');
$reviewed_count = getApplicationCountByStatus($conn, $job_id, 'reviewed');
$accepted_count = getApplicationCountByStatus($conn, $job_id, 'accepted');
$rejected_count = getApplicationCountByStatus($conn, $job_id, 'rejected');

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Job Details -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?= htmlspecialchars($job['title']) ?></h4>
                        <span class="badge bg-primary"><?= getJobTypes()[$job['job_type']] ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5 class="text-primary"><?= htmlspecialchars($job['company_name']) ?></h5>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['location']) ?>
                            <?php if (!empty($job['salary'])): ?>
                                <span class="ms-3">
                                    <i class="fas fa-money-bill-wave me-1"></i> <?= htmlspecialchars($job['salary']) ?>
                                </span>
                            <?php endif; ?>
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-clock me-1"></i> Posted <?= formatDate($job['created_at']) ?>
                            <?php if (!empty($job['deadline'])): ?>
                                <span class="ms-3">
                                    <i class="fas fa-calendar-alt me-1"></i> Deadline: <?= formatDate($job['deadline']) ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Job Description</h5>
                        <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Requirements</h5>
                        <p><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="edit-job.php?id=<?= $job_id ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit"></i> Edit Job
                        </a>
                        <a href="applications.php?job_id=<?= $job_id ?>" class="btn btn-success">
                            <i class="fas fa-users"></i> View Applications
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Statistics -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Application Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted">Total Applications</h6>
                        <h2 class="mb-0"><?= $total_applications ?></h2>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Pending</span>
                            <span class="badge bg-warning"><?= $pending_count ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Reviewed</span>
                            <span class="badge bg-info"><?= $reviewed_count ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Accepted</span>
                            <span class="badge bg-success"><?= $accepted_count ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Rejected</span>
                            <span class="badge bg-danger"><?= $rejected_count ?></span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid">
                        <a href="applications.php?job_id=<?= $job_id ?>" class="btn btn-outline-primary">
                            <i class="fas fa-list me-1"></i> Manage Applications
                        </a>
                    </div>
                </div>
            </div>

            <!-- Job Status -->
            <div class="card shadow mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Job Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Current Status:</span>
                        <span class="badge bg-<?= $job['status'] === 'open' ? 'success' : 'danger' ?>">
                            <?= ucfirst($job['status']) ?>
                        </span>
                    </div>
                    <?php if ($job['featured']): ?>
                        <div class="mt-3">
                            <span class="badge bg-warning">Featured Job</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?> 