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
$user_id = $_SESSION['user_id'];

// Get job details
$sql = "SELECT j.*, u.full_name as recruiter_name, p.company_name 
        FROM jobs j
        JOIN users u ON j.recruiter_id = u.id
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE j.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();

// Get the result metadata to fetch column names
$meta = $stmt->result_metadata();
$fields = array();
$result = array();

// Store column names
while ($field = $meta->fetch_field()) {
    $fields[] = &$result[$field->name];
}

// Bind results to variables
call_user_func_array(array($stmt, 'bind_result'), $fields);

// Fetch the result
if ($stmt->fetch()) {
    $job = array();
    foreach ($result as $key => $value) {
        $job[$key] = $value;
    }
} else {
    header('Location: browse-jobs.php');
    exit();
}

$stmt->close();

// Check if job exists
if (!$job) {
    header('Location: browse-jobs.php');
    exit();
}

// Check if user has already applied
$has_applied = hasApplied($conn, $job_id, $user_id);

// Get user profile
$profile = getProfileByUserId($conn, $user_id);

// Process application form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_applied) {
    $errors = [];
    
    // Check if resume exists
    if (empty($profile['resume_path'])) {
        $errors[] = 'Please upload your resume before applying for jobs.';
    }
    
    // Get cover letter
    $cover_letter = sanitize_input($conn, $_POST['cover_letter']);
    
    if (empty($cover_letter)) {
        $errors[] = 'Cover letter is required.';
    }
    
    // If no errors, submit application
    if (empty($errors)) {
        $sql = "INSERT INTO applications (job_id, jobseeker_id, resume_path, cover_letter, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $job_id, $user_id, $profile['resume_path'], $cover_letter);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Application submitted successfully!';
            header('Location: my-applications.php');
            exit();
        } else {
            $errors[] = 'Failed to submit application. Please try again.';
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
                <div class="card-header bg-white">
                    <h5 class="mb-0">Apply for this Job</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($errors)): ?>
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
                            <form method="POST" action="view-job.php?id=<?= $job_id ?>" class="needs-validation" novalidate>
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
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?> 