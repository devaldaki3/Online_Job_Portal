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
$pageTitle = 'Edit Job';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get job ID and recruiter ID
$job_id = (int)$_GET['id'];
$recruiter_id = $_SESSION['user_id'];

// Get job details with security check for recruiter ownership
$sql = "SELECT j.* FROM jobs j WHERE j.id = ? AND j.recruiter_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $recruiter_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

// Check if job exists and belongs to the recruiter
if (!$job) {
    $_SESSION['error'] = 'Job not found or you do not have permission to edit it.';
    header('Location: my-jobs.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    try {
        // Get form data
        $title = sanitize_input($conn, $_POST['title']);
        $description = sanitize_input($conn, $_POST['description']);
        $requirements = sanitize_input($conn, $_POST['requirements']);
        $job_type = sanitize_input($conn, $_POST['job_type']);
        $location = sanitize_input($conn, $_POST['location']);
        $salary = sanitize_input($conn, $_POST['salary']);
        
        // Validate inputs
        if (empty($title)) {
            $errors[] = 'Job title is required';
        } elseif (strlen($title) > 100) {
            $errors[] = 'Job title must be less than 100 characters';
        }
        
        if (empty($description)) {
            $errors[] = 'Job description is required';
        } elseif (strlen($description) < 50) {
            $errors[] = 'Job description must be at least 50 characters long';
        }
        
        if (empty($requirements)) {
            $errors[] = 'Job requirements are required';
        } elseif (strlen($requirements) < 30) {
            $errors[] = 'Job requirements must be at least 30 characters long';
        }
        
        if (empty($job_type) || !in_array($job_type, array_keys(getJobTypes()))) {
            $errors[] = 'Please select a valid job type';
        }
        
        if (empty($location)) {
            $errors[] = 'Location is required';
        } elseif (strlen($location) > 100) {
            $errors[] = 'Location must be less than 100 characters';
        }
        
        if (empty($salary)) {
            $errors[] = 'Salary is required';
        }
        
        // If no errors, update job
        if (empty($errors)) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                $sql = "UPDATE jobs SET 
                        title = ?, 
                        description = ?, 
                        requirements = ?, 
                        job_type = ?, 
                        location = ?, 
                        salary = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND recruiter_id = ?";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare statement: ' . $conn->error);
                }
                
                $stmt->bind_param("ssssssii", 
                    $title, 
                    $description, 
                    $requirements, 
                    $job_type, 
                    $location, 
                    $salary,
                    $job_id,
                    $recruiter_id
                );
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to execute statement: ' . $stmt->error);
                }
                
                if ($stmt->affected_rows === 0 && $stmt->errno === 0) {
                    throw new Exception('No changes were made to the job posting');
                }
                
                // Commit transaction
                $conn->commit();
                
                error_log("Job ID: {$job_id} updated successfully by recruiter ID: {$recruiter_id}");
                $_SESSION['success'] = 'Job updated successfully!';
                header('Location: job-details.php?id=' . $job_id);
                exit();
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                error_log("Job update error: " . $e->getMessage() . " for Job ID: {$job_id}");
                $errors[] = 'Database Error: ' . $e->getMessage();
            }
        }
    } catch (Exception $e) {
        error_log("Form processing error: " . $e->getMessage());
        $errors[] = 'Error processing form: ' . $e->getMessage();
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
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Job</h4>
                        <a href="job-details.php?id=<?= $job_id ?>" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Back to Job Details
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error'] ?>
                            <?php unset($_SESSION['error']); ?>
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
                    
                    <form method="POST" action="edit-job.php?id=<?= $job_id ?>" class="needs-validation" novalidate>
                        <!-- Job Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Job Title</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                value="<?= htmlspecialchars($job['title']) ?>" required>
                            <div class="invalid-feedback">
                                Please enter a job title.
                            </div>
                        </div>
                        
                        <!-- Job Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Job Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                rows="4" required><?= htmlspecialchars($job['description']) ?></textarea>
                            <div class="invalid-feedback">
                                Please enter a job description.
                            </div>
                        </div>
                        
                        <!-- Job Requirements -->
                        <div class="mb-3">
                            <label for="requirements" class="form-label">Job Requirements</label>
                            <textarea class="form-control" id="requirements" name="requirements" 
                                rows="4" required><?= htmlspecialchars($job['requirements']) ?></textarea>
                            <div class="invalid-feedback">
                                Please enter job requirements.
                            </div>
                        </div>
                        
                        <!-- Job Type -->
                        <div class="mb-3">
                            <label for="job_type" class="form-label">Job Type</label>
                            <select class="form-select" id="job_type" name="job_type" required>
                                <option value="">Select job type</option>
                                <?php foreach (getJobTypes() as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $job['job_type'] === $value ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a job type.
                            </div>
                        </div>
                        
                        <!-- Location -->
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                value="<?= htmlspecialchars($job['location']) ?>" required>
                            <div class="invalid-feedback">
                                Please enter a location.
                            </div>
                        </div>
                        
                        <!-- Salary -->
                        <div class="mb-3">
                            <label for="salary" class="form-label">Salary</label>
                            <input type="text" class="form-control" id="salary" name="salary" 
                                value="<?= htmlspecialchars($job['salary']) ?>" 
                                placeholder="e.g., ₹50,000 per month, 5 Lakhs PA" required>
                            <div class="invalid-feedback">
                                Please enter a valid salary.
                            </div>
                            <small class="text-muted">
                                Valid formats: ₹50,000, 5 Lakhs PA, 50K per month, 1.2 Cr per annum
                            </small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                            <a href="job-details.php?id=<?= $job_id ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?> 