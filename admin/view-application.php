<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage-applications.php');
    exit();
}

$application_id = (int)$_GET['id'];

// Get application details
$sql = "SELECT a.*, 
        j.title as job_title, j.description as job_description, j.requirements as job_requirements,
        j.location, j.job_type, j.salary,
        js.full_name as jobseeker_name, js.email as jobseeker_email,
        r.full_name as recruiter_name, r.email as recruiter_email,
        p.phone as jobseeker_phone, p.address as jobseeker_address,
        p.skills as jobseeker_skills, p.experience as jobseeker_experience,
        p.education as jobseeker_education
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users js ON a.jobseeker_id = js.id
        JOIN users r ON j.recruiter_id = r.id
        LEFT JOIN profiles p ON js.id = p.user_id
        WHERE a.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: manage-applications.php');
    exit();
}

$application = $result->fetch_assoc();

// Page title
$pageTitle = 'View Application';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Application Details</h1>
                <a href="manage-applications.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Applications
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Job Seeker Information -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Job Seeker Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Full Name</h6>
                            <p><?= htmlspecialchars($application['jobseeker_name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Email</h6>
                            <p><?= htmlspecialchars($application['jobseeker_email']) ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Phone</h6>
                            <p><?= htmlspecialchars($application['jobseeker_phone'] ?? 'Not provided') ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Address</h6>
                            <p><?= htmlspecialchars($application['jobseeker_address'] ?? 'Not provided') ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Skills</h6>
                            <p><?= nl2br(htmlspecialchars($application['jobseeker_skills'] ?? 'Not provided')) ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Experience</h6>
                            <p><?= nl2br(htmlspecialchars($application['jobseeker_experience'] ?? 'Not provided')) ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Education</h6>
                            <p><?= nl2br(htmlspecialchars($application['jobseeker_education'] ?? 'Not provided')) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Details -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Job Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Job Title</h6>
                            <p><?= htmlspecialchars($application['job_title']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Location</h6>
                            <p><?= htmlspecialchars($application['location']) ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Job Type</h6>
                            <p><?= htmlspecialchars(ucfirst($application['job_type'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Salary</h6>
                            <p><?= htmlspecialchars($application['salary'] ?? 'Not specified') ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h6>Job Description</h6>
                            <p><?= nl2br(htmlspecialchars($application['job_description'])) ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Requirements</h6>
                            <p><?= nl2br(htmlspecialchars($application['job_requirements'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Application Status -->
            <div class="card shadow mb-4">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">Application Status</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="badge bg-<?= getStatusBadgeClass($application['status']) ?> fs-6">
                            <?= ucfirst($application['status']) ?>
                        </span>
                    </div>
                    <p class="mb-2">
                        <strong>Applied On:</strong><br>
                        <?= date('F j, Y g:i A', strtotime($application['created_at'])) ?>
                    </p>
                    <?php if ($application['status'] === 'pending'): ?>
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="updateStatus(<?= $application['id'] ?>, 'accepted')">
                                <i class="fas fa-check me-2"></i>Accept Application
                            </button>
                            <button class="btn btn-danger" onclick="updateStatus(<?= $application['id'] ?>, 'rejected')">
                                <i class="fas fa-times me-2"></i>Reject Application
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recruiter Information -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Recruiter Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Name:</strong><br>
                        <?= htmlspecialchars($application['recruiter_name']) ?>
                    </p>
                    <p class="mb-0">
                        <strong>Email:</strong><br>
                        <?= htmlspecialchars($application['recruiter_email']) ?>
                    </p>
                </div>
            </div>

            <!-- Application Documents -->
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">Documents</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($application['resume_path'])): ?>
                        <a href="<?= htmlspecialchars($application['resume_path']) ?>" 
                           class="btn btn-primary w-100 mb-3" 
                           target="_blank">
                            <i class="fas fa-download me-2"></i>Download Resume
                        </a>
                    <?php else: ?>
                        <p class="text-muted mb-3">No resume uploaded</p>
                    <?php endif; ?>

                    <?php if (!empty($application['cover_letter'])): ?>
                        <h6>Cover Letter</h6>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($application['cover_letter'])) ?></p>
                    <?php else: ?>
                        <p class="text-muted mb-0">No cover letter provided</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(applicationId, status) {
    if (confirm('Are you sure you want to ' + status + ' this application?')) {
        window.location.href = 'update-application-status.php?id=' + applicationId + '&status=' + status;
    }
}
</script>

<?php include '../includes/footer.php'; ?> 