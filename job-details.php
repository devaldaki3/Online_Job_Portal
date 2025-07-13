<?php
// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage-jobs.php');
    exit();
}

// Page title
$pageTitle = 'Job Details';

// Include database connection and functions
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get job ID
$job_id = (int)$_GET['id'];

// Get job details with recruiter and company information
$sql = "SELECT j.*, u.full_name as recruiter_name, u.email as recruiter_email, 
        p.company_name, 
        (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as total_applications
        FROM jobs j
        JOIN users u ON j.recruiter_id = u.id
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE j.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

// Check if job exists
if (!$job) {
    header('Location: manage-jobs.php');
    exit();
}

// Get applications for this job
$stmt = $conn->prepare("SELECT a.*, u.full_name, u.email, p.phone 
                       FROM applications a 
                       JOIN users u ON a.jobseeker_id = u.id 
                       LEFT JOIN profiles p ON u.id = p.user_id 
                       WHERE a.job_id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Deduplicate applications to ensure only one entry per application ID
// This prevents duplicate displays that can occur during result processing
$unique_applications = [];
foreach ($applications as $app) {
    $unique_applications[$app['id']] = $app;
}
$applications = array_values($unique_applications);

// Get application counts by status
$total_applications = count($applications);
$status_counts = [
    'pending' => 0,
    'reviewed' => 0,
    'accepted' => 0,
    'rejected' => 0
];

foreach ($applications as $app) {
    $status_counts[$app['status']]++;
}

// Include header
include 'includes/header.php';
?>

<div class="container py-5">
    <!-- Back button -->
    <div class="mb-4">
        <a href="manage-jobs.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Manage Jobs
        </a>
    </div>

    <div class="row">
        <!-- Job Details -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?= htmlspecialchars($job['title']) ?></h4>
                        <span class="badge bg-<?= $job['status'] === 'open' ? 'success' : 'danger' ?>">
                            <?= ucfirst($job['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Company and Recruiter Info -->
                    <div class="mb-4">
                        <h5 class="text-primary"><?= htmlspecialchars($job['company_name']) ?></h5>
                        <p class="mb-2">
                            <strong>Recruiter:</strong> 
                            <?= htmlspecialchars($job['recruiter_name']) ?> 
                            (<?= htmlspecialchars($job['recruiter_email']) ?>)
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['location']) ?>
                            <?php if (!empty($job['salary'])): ?>
                                <span class="ms-3">
                                    <i class="fas fa-money-bill-wave me-1"></i> <?= htmlspecialchars($job['salary']) ?>
                                </span>
                            <?php endif; ?>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-briefcase me-1"></i> <?= getJobTypes()[$job['job_type']] ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-clock me-1"></i> Posted <?= formatDate($job['created_at']) ?>
                        </p>
                    </div>

                    <!-- Job Description -->
                    <div class="mb-4">
                        <h5>Job Description</h5>
                        <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                    </div>

                    <!-- Requirements -->
                    <div class="mb-4">
                        <h5>Requirements</h5>
                        <p><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- Applications -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Applications (<?= count($applications) ?>)</h5>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($applications)): ?>
                        <p class="text-muted mb-0">No applications received yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Applied On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $application): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($application['full_name']) ?></td>
                                            <td>
                                                <small>
                                                    <div><?= htmlspecialchars($application['email']) ?></div>
                                                    <?php if (!empty($application['phone'])): ?>
                                                        <div><?= htmlspecialchars($application['phone']) ?></div>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadgeClass($application['status']) ?>">
                                                    <?= ucfirst($application['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($application['created_at']) ?></td>
                                            <td>
                                                <a href="admin/view-application.php?id=<?= $application['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>Total Applications</div>
                        <h4 class="mb-0"><?= count($applications) ?></h4>
                    </div>
                    <?php
                    $status_counts = array_count_values(array_column($applications, 'status'));
                    $statuses = ['pending', 'reviewed', 'accepted', 'rejected'];
                    foreach ($statuses as $status):
                        $count = $status_counts[$status] ?? 0;
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div><?= ucfirst($status) ?></div>
                            <span class="badge bg-<?= getStatusBadgeClass($status) ?>"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="edit-job.php?id=<?= $job_id ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Edit Job
                        </a>
                        <?php if ($job['status'] === 'open'): ?>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#closeJobModal">
                                <i class="fas fa-times-circle me-1"></i> Close Job
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reopenJobModal">
                                <i class="fas fa-check-circle me-1"></i> Reopen Job
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Close Job Modal -->
<div class="modal fade" id="closeJobModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Close Job Posting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to close this job posting? This will prevent new applications from being submitted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="actions/update-job-status.php" method="POST" class="d-inline">
                    <input type="hidden" name="job_id" value="<?= $job_id ?>">
                    <input type="hidden" name="status" value="closed">
                    <button type="submit" class="btn btn-danger">Close Job</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reopen Job Modal -->
<div class="modal fade" id="reopenJobModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reopen Job Posting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reopen this job posting? This will allow new applications to be submitted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="actions/update-job-status.php" method="POST" class="d-inline">
                    <input type="hidden" name="job_id" value="<?= $job_id ?>">
                    <input type="hidden" name="status" value="open">
                    <button type="submit" class="btn btn-success">Reopen Job</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?> 