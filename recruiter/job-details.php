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
$pageTitle = 'Job Details';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get job ID
$job_id = (int)$_GET['id'];
$recruiter_id = $_SESSION['user_id'];

// Get job details with security check for recruiter ownership
$sql = "SELECT j.*, p.company_name, COUNT(a.id) as application_count 
        FROM jobs j 
        LEFT JOIN users u ON j.recruiter_id = u.id 
        LEFT JOIN profiles p ON u.id = p.user_id 
        LEFT JOIN applications a ON j.id = a.job_id 
        WHERE j.id = ? AND j.recruiter_id = ? 
        GROUP BY j.id, p.company_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $recruiter_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

// Check if job exists and belongs to the recruiter
if (!$job) {
    $_SESSION['error'] = 'Job not found or you do not have permission to view it.';
    header('Location: my-jobs.php');
    exit();
}

// Get applications for this job
$sql = "SELECT a.*, u.full_name, u.email 
        FROM applications a 
        JOIN users u ON a.jobseeker_id = u.id 
        WHERE a.job_id = ? 
        ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$applications = $stmt->get_result();

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
                        <div>
                            <span class="badge bg-primary me-2"><?= getJobTypes()[$job['job_type']] ?></span>
                            <span class="badge bg-<?= $job['status'] === 'open' ? 'success' : 'danger' ?>"><?= ucfirst($job['status']) ?></span>
                        </div>
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
                            <span class="ms-3">
                                <i class="fas fa-calendar-alt me-1"></i> Deadline: <?= formatDate($job['deadline']) ?>
                            </span>
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

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="edit-job.php?id=<?= $job_id ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Edit Job
                        </a>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $job_id ?>)">
                            <i class="fas fa-trash-alt me-1"></i> Delete Job
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Applications Summary -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Applications Summary</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h2 class="mb-0"><?= $job['application_count'] ?></h2>
                        <p class="text-muted">Total Applications</p>
                    </div>
                    
                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Pending
                            <span class="badge bg-warning rounded-pill">
                                <?= getApplicationCountByStatus($conn, $job_id, 'pending') ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Accepted
                            <span class="badge bg-success rounded-pill">
                                <?= getApplicationCountByStatus($conn, $job_id, 'accepted') ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Rejected
                            <span class="badge bg-danger rounded-pill">
                                <?= getApplicationCountByStatus($conn, $job_id, 'rejected') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Status -->
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Job Status</h5>
                </div>
                <div class="card-body">
                    <form id="statusForm" method="POST" action="update-job-status.php">
                        <input type="hidden" name="job_id" value="<?= $job_id ?>">
                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <select class="form-select" name="status" onchange="updateJobStatus(this)">
                                <option value="open" <?= $job['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="closed" <?= $job['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Applications List -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Applications</h5>
                </div>
                <div class="card-body">
                    <?php if ($applications->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Email</th>
                                        <th>Applied On</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($application = $applications->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($application['full_name']) ?></td>
                                            <td><?= htmlspecialchars($application['email']) ?></td>
                                            <td><?= formatDate($application['created_at']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadgeClass($application['status']) ?>">
                                                    <?= ucfirst($application['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view-application.php?id=<?= $application['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No applications received yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(jobId) {
    if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
        fetch('delete-job.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + jobId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'my-jobs.php';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the job.');
        });
    }
}

function updateJobStatus(select) {
    document.getElementById('statusForm').submit();
}
</script>

<?php
// Include footer
include '../includes/footer.php';
?> 