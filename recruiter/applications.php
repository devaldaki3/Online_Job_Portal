<?php
// Start session
session_start();

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Check if job ID is provided
if (!isset($_GET['job_id'])) {
    header('Location: my-jobs.php');
    exit();
}

// Page title
$pageTitle = 'Job Applications';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get job ID
$job_id = (int)$_GET['job_id'];

// Get job details
$job = getJobById($conn, $job_id);

// Check if job exists and belongs to the current recruiter
if (!$job || $job['recruiter_id'] != $_SESSION['user_id']) {
    header('Location: my-jobs.php');
    exit();
}

// Get status filter
$status_filter = isset($_GET['status']) ? sanitize_input($conn, $_GET['status']) : '';

// Build query for applications
$sql = "SELECT a.*, u.full_name, u.email, p.phone 
        FROM applications a 
        JOIN users u ON a.jobseeker_id = u.id 
        LEFT JOIN profiles p ON u.id = p.user_id 
        WHERE a.job_id = ?";

$params = [$job_id];
$types = "i";

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY a.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Deduplicate applications to ensure only one entry per application ID
// This prevents duplicate displays that can occur during result processing
$unique_applications = [];
foreach ($applications as $app) {
    $unique_applications[$app['id']] = $app;
}
$applications = array_values($unique_applications);

// Add debug information
echo "<!-- Debug Info: " . count($applications) . " applications found after deduplication -->";

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id']) && isset($_POST['status'])) {
    $application_id = (int)$_POST['application_id'];
    $new_status = sanitize_input($conn, $_POST['status']);
    
    // Verify application belongs to this job
    $stmt = $conn->prepare("SELECT jobseeker_id FROM applications WHERE id = ? AND job_id = ?");
    $stmt->bind_param("ii", $application_id, $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Update status
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $application_id);
        
        if ($stmt->execute()) {
            // Create notification for the job seeker
            $message = "Your application for {$job['title']} has been marked as " . ucfirst($new_status);
            createNotification(
                $conn,
                $row['jobseeker_id'],
                'status_update',
                'Application Status Updated',
                $message,
                $application_id
            );
            
            $_SESSION['success'] = 'Application status updated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update application status.';
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: applications.php?job_id=$job_id" . (!empty($status_filter) ? "&status=$status_filter" : ""));
    exit();
}

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Applications for <?= htmlspecialchars($job['title']) ?></h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['location']) ?>
                        <span class="mx-2">•</span>
                        <span class="badge bg-primary"><?= getJobTypes()[$job['job_type']] ?></span>
                    </p>
                </div>
                <a href="view-job.php?id=<?= $job_id ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Job
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Applications</h5>
                        <div class="btn-group">
                            <a href="applications.php?job_id=<?= $job_id ?>" class="btn btn-outline-primary <?= empty($status_filter) ? 'active' : '' ?>">
                                All
                            </a>
                            <a href="applications.php?job_id=<?= $job_id ?>&status=pending" class="btn btn-outline-primary <?= $status_filter === 'pending' ? 'active' : '' ?>">
                                Pending
                            </a>
                            <a href="applications.php?job_id=<?= $job_id ?>&status=reviewed" class="btn btn-outline-primary <?= $status_filter === 'reviewed' ? 'active' : '' ?>">
                                Reviewed
                            </a>
                            <a href="applications.php?job_id=<?= $job_id ?>&status=accepted" class="btn btn-outline-primary <?= $status_filter === 'accepted' ? 'active' : '' ?>">
                                Accepted
                            </a>
                            <a href="applications.php?job_id=<?= $job_id ?>&status=rejected" class="btn btn-outline-primary <?= $status_filter === 'rejected' ? 'active' : '' ?>">
                                Rejected
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <?php if (empty($applications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No applications found</h5>
                            <p class="text-muted">
                                <?= empty($status_filter) ? 'No one has applied for this job yet.' : 'No applications with the selected status.' ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Contact</th>
                                        <th>Applied On</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $application): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($application['full_name']) ?></h6>
                                                        <small class="text-muted">ID: <?= $application['id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($application['email']) ?></div>
                                                    <?php if (!empty($application['phone'])): ?>
                                                        <div><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($application['phone']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?= formatDate($application['created_at']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadgeClass($application['status']) ?>">
                                                    <?= ucfirst($application['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view-application.php?id=<?= $application['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="updateStatus(<?= $application['id'] ?>, 'accepted')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="updateStatus(<?= $application['id'] ?>, 'rejected')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
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
    </div>
</div>

<script>
function updateStatus(applicationId, status) {
    if (confirm('Are you sure you want to mark this application as ' + status + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const applicationIdInput = document.createElement('input');
        applicationIdInput.name = 'application_id';
        applicationIdInput.value = applicationId;

        const statusInput = document.createElement('input');
        statusInput.name = 'status';
        statusInput.value = status;

        form.appendChild(applicationIdInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Include footer
include '../includes/footer.php';
?>
