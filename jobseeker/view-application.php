<?php
// Start session
session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit();
}

// Check if application ID is provided
if (!isset($_GET['id'])) {
    header('Location: my-applications.php');
    exit();
}

// Page title
$pageTitle = 'View Application';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get application ID and user ID
$application_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get application details with security check for ownership
$sql = "SELECT a.*, j.title, j.description, j.requirements, j.location, j.job_type, j.salary,
        u.full_name as recruiter_name, p.company_name, p.company_website, p.company_description
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON j.recruiter_id = u.id
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE a.id = ? AND a.jobseeker_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $application_id, $user_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();

// Check if application exists and belongs to the user
if (!$application) {
    $_SESSION['error'] = 'Application not found or you do not have permission to view it.';
    header('Location: my-applications.php');
    exit();
}

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Application Details</h5>
                    <a href="my-applications.php" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Applications
                    </a>
                </div>
                <div class="card-body">
                    <!-- Application Status -->
                    <div class="alert alert-<?= getStatusBadgeClass($application['status']) ?> d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Application Status:</strong> <?= ucfirst($application['status']) ?>
                        </div>
                        <?php if ($application['status'] === 'pending'): ?>
                            <a href="withdraw-application.php?id=<?= $application_id ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to withdraw this application?')">
                                Withdraw Application
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Job Details -->
                    <div class="mb-4">
                        <h5 class="card-title"><?= htmlspecialchars($application['title']) ?></h5>
                        <p class="text-muted mb-2">
                            <i class="fas fa-building me-1"></i> <?= htmlspecialchars($application['company_name'] ?? 'Company Name Not Available') ?>
                            <?php if (!empty($application['company_website'])): ?>
                                <a href="<?= htmlspecialchars($application['company_website']) ?>" 
                                   target="_blank" 
                                   class="ms-2 text-decoration-none">
                                    <i class="fas fa-external-link-alt"></i> Visit Website
                                </a>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($application['company_description'])): ?>
                        <p class="text-muted small mb-2">
                            <?= nl2br(htmlspecialchars($application['company_description'])) ?>
                        </p>
                        <?php endif; ?>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($application['location']) ?>
                            <span class="ms-3">
                                <i class="fas fa-briefcase me-1"></i> <?= htmlspecialchars(ucfirst($application['job_type'])) ?>
                            </span>
                            <?php if (!empty($application['salary'])): ?>
                                <span class="ms-3">
                                    <i class="fas fa-money-bill-wave me-1"></i> <?= htmlspecialchars($application['salary']) ?>
                                </span>
                            <?php endif; ?>
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-clock me-1"></i> Applied on <?= date('M d, Y', strtotime($application['created_at'])) ?>
                        </p>
                    </div>

                    <!-- Job Description -->
                    <div class="mb-4">
                        <h6 class="fw-bold">Job Description</h6>
                        <p><?= nl2br(htmlspecialchars($application['description'])) ?></p>
                    </div>

                    <!-- Job Requirements -->
                    <div class="mb-4">
                        <h6 class="fw-bold">Requirements</h6>
                        <p><?= nl2br(htmlspecialchars($application['requirements'])) ?></p>
                    </div>

                    <!-- Application Details -->
                    <div class="mb-4">
                        <h6 class="fw-bold">Your Cover Letter</h6>
                        <p><?= nl2br(htmlspecialchars($application['cover_letter'])) ?></p>
                    </div>

                    <!-- Attached Resume -->
                    <div>
                        <h6 class="fw-bold">Your Resume</h6>
                        <a href="<?= htmlspecialchars($application['resume_path']) ?>" 
                           class="btn btn-outline-primary" 
                           target="_blank">
                            <i class="fas fa-file-pdf me-1"></i> View Resume
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?> 