<?php
// Start session
session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit();
}

// Page title
$pageTitle = 'Resume Management';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get user profile
$user_id = $_SESSION['user_id'];
$profile = getProfileByUserId($conn, $user_id);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Handle resume upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['resume'], '../uploads/resumes', ['application/pdf'], 5242880); // 5MB max
        if ($upload['success']) {
            // Update profile with resume path
            $resume_path = $upload['filepath'];
            $sql = "UPDATE profiles SET resume_path = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $resume_path, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Resume uploaded successfully!';
                header('Location: resume.php');
                exit();
            } else {
                $errors[] = 'Failed to update resume. Please try again.';
            }
        } else {
            $errors[] = $upload['message'];
        }
    } else {
        $errors[] = 'Please select a resume file to upload.';
    }
}

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Resume Management</h4>
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
                    
                    <!-- Current Resume -->
                    <div class="mb-4">
                        <h5>Current Resume</h5>
                        <?php if (!empty($profile['resume_path'])): ?>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                                <div>
                                    <p class="mb-0"><?= basename($profile['resume_path']) ?></p>
                                    <small class="text-muted">Uploaded on <?= formatDate($profile['updated_at']) ?></small>
                                </div>
                                <div class="ms-auto">
                                    <a href="<?= htmlspecialchars($profile['resume_path']) ?>" class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <a href="<?= htmlspecialchars($profile['resume_path']) ?>" class="btn btn-sm btn-outline-secondary" download>
                                        <i class="fas fa-download me-1"></i> Download
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No resume uploaded yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Upload New Resume -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Upload New Resume</h5>
                            <p class="text-muted">Upload your resume in PDF format (max 5MB)</p>
                            
                            <form method="POST" action="resume.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="resume" class="form-label">Select Resume</label>
                                    <input type="file" class="form-control" id="resume" name="resume" accept=".pdf" required>
                                    <div class="invalid-feedback">
                                        Please select a PDF file to upload.
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-1"></i> Upload Resume
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Resume Tips -->
                    <div class="mt-4">
                        <h5>Resume Tips</h5>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Keep your resume updated with your latest experience and skills
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Use a professional format and clear headings
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Highlight your achievements and relevant experience
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Include keywords from job descriptions to improve visibility
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Keep your resume concise (1-2 pages recommended)
                            </li>
                        </ul>
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
