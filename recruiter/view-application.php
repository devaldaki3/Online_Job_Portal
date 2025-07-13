<?php
// Start session
session_start();

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Check if application ID is provided
if (!isset($_GET['id'])) {
    header('Location: my-jobs.php');
    exit();
}

// Page title
$pageTitle = 'View Application';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get application ID
$application_id = (int)$_GET['id'];

// Get application details with job and applicant information
$sql = "SELECT a.*, j.title as job_title, j.location, j.job_type, j.id as job_id,
        u.full_name, u.email, p.* 
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.jobseeker_id = u.id
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE a.id = ? AND j.recruiter_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $application_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Check if application exists and belongs to a job posted by the current recruiter
if (!$application = $result->fetch_assoc()) {
    header('Location: my-jobs.php');
    exit();
}

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = sanitize_input($conn, $_POST['status']);
    
    // Update status
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $application_id);
    
    if ($stmt->execute()) {
        // Create notification for the job seeker
        $message = "Your application for {$application['job_title']} has been marked as " . ucfirst($new_status);
        createNotification(
            $conn,
            $application['jobseeker_id'],
            'status_update',
            'Application Status Updated',
            $message,
            $application_id
        );
        
        $_SESSION['success'] = 'Application status updated successfully.';
        header("Location: view-application.php?id=$application_id");
        exit();
    } else {
        $_SESSION['error'] = 'Failed to update application status.';
    }
}

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Application Details</h2>
                <a href="applications.php?job_id=<?= $application['job_id'] ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Applications
                </a>
            </div>
        </div>
    </div>

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

    <div class="row">
        <!-- Application Details -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Application Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Job Details</h6>
                            <p class="mb-1">
                                <strong><?= htmlspecialchars($application['job_title']) ?></strong>
                            </p>
                            <p class="text-muted mb-1">
                                <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($application['location']) ?>
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-briefcase me-1"></i> <?= getJobTypes()[$application['job_type']] ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Application Status</h6>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?= getStatusBadgeClass($application['status']) ?> me-2">
                                    <?= ucfirst($application['status']) ?>
                                </span>
                                <small class="text-muted">
                                    Applied on <?= formatDate($application['created_at']) ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6>Cover Letter</h6>
                        <div class="border rounded p-3 bg-light">
                            <?= nl2br(htmlspecialchars($application['cover_letter'])) ?>
                        </div>
                    </div>

                    <?php if (!empty($application['resume_path'])): ?>
                        <div class="mb-4">
                            <h6>Resume</h6>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-pdf fa-2x text-danger me-2"></i>
                                <div>
                                    <p class="mb-0"><?= basename($application['resume_path']) ?></p>
                                    <small class="text-muted">Uploaded on <?= formatDate($application['updated_at']) ?></small>
                                </div>
                                <a href="<?= htmlspecialchars($application['resume_path']) ?>" class="btn btn-sm btn-primary ms-auto" target="_blank">
                                    <i class="fas fa-download me-1"></i> Download Resume
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Applicant Information -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Applicant Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if (!empty($application['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($application['profile_image']) ?>" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                        <?php endif; ?>
                        <h5 class="mb-1"><?= htmlspecialchars($application['full_name']) ?></h5>
                        <p class="text-muted mb-0">
                            <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($application['email']) ?>
                        </p>
                        <?php if (!empty($application['phone'])): ?>
                            <p class="text-muted mb-0">
                                <i class="fas fa-phone me-1"></i> <?= htmlspecialchars($application['phone']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($application['skills'])): ?>
                        <div class="mb-3">
                            <h6>Skills</h6>
                            <p class="mb-0"><?= htmlspecialchars($application['skills']) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($application['experience'])): ?>
                        <div class="mb-3">
                            <h6>Experience</h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($application['experience'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($application['education'])): ?>
                        <div class="mb-3">
                            <h6>Education</h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($application['education'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" onclick="updateStatus('accepted')">
                            <i class="fas fa-check me-1"></i> Accept Application
                        </button>
                        <button type="button" class="btn btn-danger" onclick="updateStatus('rejected')">
                            <i class="fas fa-times me-1"></i> Reject Application
                        </button>
                        <button type="button" class="btn btn-info" onclick="updateStatus('reviewed')">
                            <i class="fas fa-eye me-1"></i> Mark as Reviewed
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(status) {
    if (confirm('Are you sure you want to mark this application as ' + status + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const statusInput = document.createElement('input');
        statusInput.name = 'status';
        statusInput.value = status;

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