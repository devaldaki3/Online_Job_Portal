<?php
// Start session
session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header('Location: browse-jobs.php');
    exit();
}

// Page title
$pageTitle = 'Job Details';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get job ID
$job_id = (int)$_GET['id'];

// Get job details
$job = getJobById($conn, $job_id);

// Check if job exists
if (!$job) {
    header('Location: browse-jobs.php');
    exit();
}

// Get user profile
$user_id = $_SESSION['user_id'];
$profile = getProfileByUserId($conn, $user_id);

// Check if user has already applied
$has_applied = hasApplied($conn, $job_id, $user_id);

// Process application form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_applied) {
    $errors = [];
    
    // Check if resume exists
    if (empty($profile['resume_path'])) {
        $errors[] = 'Please upload your resume before applying for jobs.';
    } else {
        // Get cover letter
        $cover_letter = sanitize_input($conn, $_POST['cover_letter']);
        
        if (empty($cover_letter)) {
            $errors[] = 'Cover letter is required.';
        } elseif (strlen($cover_letter) < 100) {
            $errors[] = 'Cover letter must be at least 100 characters long.';
        } elseif (strlen($cover_letter) > 2000) {
            $errors[] = 'Cover letter must not exceed 2000 characters.';
        } else {
            try {
                // Check if job is still open
                $stmt = $conn->prepare("SELECT status FROM jobs WHERE id = ?");
                $stmt->bind_param("i", $job_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $job_status = $result->fetch_assoc()['status'];
                
                if ($job_status !== 'open') {
                    throw new Exception('This job is no longer accepting applications.');
                }
                
                // Submit application
                $sql = "INSERT INTO applications (job_id, jobseeker_id, resume_path, cover_letter, status) 
                        VALUES (?, ?, ?, ?, 'pending')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiss", $job_id, $user_id, $profile['resume_path'], $cover_letter);
                
                if ($stmt->execute()) {
                    $application_id = $conn->insert_id;
                    error_log("New application submitted. Application ID: " . $application_id);
                    
                    // Create notification for the recruiter
                    createNotification(
                        $conn,
                        $job['recruiter_id'],
                        'application',
                        'New Job Application',
                        "A new application has been received for the position: " . $job['title'],
                        $application_id
                    );
                    
                    // Create notification for the job seeker
                    createNotification(
                        $conn,
                        $user_id,
                        'application',
                        'Application Submitted',
                        "Your application for " . $job['title'] . " has been submitted successfully.",
                        $application_id
                    );
                    
                    $_SESSION['success'] = 'Application submitted successfully!';
                    header('Location: my-applications.php');
                    exit();
                } else {
                    throw new Exception('Failed to submit application: ' . $conn->error);
                }
            } catch (Exception $e) {
                error_log("Application submission error: " . $e->getMessage());
                $errors[] = $e->getMessage();
            }
        }
    }
}

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
                </div>
            </div>
        </div>
        
        <!-- Application Form -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Apply for this Job</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success'] ?>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($has_applied): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You have already applied for this job.
                            <a href="my-applications.php" class="alert-link">View your applications</a>
                        </div>
                    <?php else: ?>
                        <?php if (empty($profile['resume_path'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Please upload your resume before applying for jobs.
                                <a href="resume.php" class="alert-link">Upload Resume</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="job-details.php?id=<?= $job_id ?>" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="resume" class="form-label">Your Resume</label>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-pdf fa-2x text-danger me-2"></i>
                                        <div>
                                            <p class="mb-0"><?= basename($profile['resume_path']) ?></p>
                                            <small class="text-muted">Uploaded on <?= formatDate($profile['updated_at']) ?></small>
                                        </div>
                                        <a href="<?= htmlspecialchars($profile['resume_path']) ?>" class="btn btn-sm btn-outline-primary ms-auto" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cover_letter" class="form-label">Cover Letter</label>
                                    <textarea class="form-control" id="cover_letter" name="cover_letter" rows="6" required></textarea>
                                    <div class="invalid-feedback">
                                        Please write a cover letter.
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i> Submit Application
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Application Tips -->
            <div class="card shadow mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Application Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Customize your cover letter for each job application
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Highlight relevant skills and experience
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Keep your resume updated and professional
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Follow up with the employer if you don't hear back
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?> 