<?php
// Start session
session_start();

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Page title
$pageTitle = 'Post New Job';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get user profile
$user_id = $_SESSION['user_id'];
$profile = getProfileByUserId($conn, $user_id);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $title = sanitize_input($conn, $_POST['title']);
    $description = sanitize_input($conn, $_POST['description']);
    $requirements = sanitize_input($conn, $_POST['requirements']);
    $job_type = sanitize_input($conn, $_POST['job_type']);
    $location = sanitize_input($conn, $_POST['location']);
    $salary = sanitize_input($conn, $_POST['salary']);
    $deadline = sanitize_input($conn, $_POST['deadline']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
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
    } elseif (!preg_match('/^₹?\s*\d+(?:,\d{3})*(?:\.\d{2})?\s*(?:–|-)\s*₹?\s*\d+(?:,\d{3})*(?:\.\d{2})?\s*(?:per\s+month|per\s+annum|PA|PM)?$/', $salary)) {
        $errors[] = 'Please enter a valid salary format (e.g., ₹12,000 – ₹15,000 per month)';
    }
    
    if (empty($deadline)) {
        $errors[] = 'Application deadline is required';
    } elseif (strtotime($deadline) < strtotime('today')) {
        $errors[] = 'Application deadline must be in the future';
    } elseif (strtotime($deadline) > strtotime('+1 year')) {
        $errors[] = 'Application deadline cannot be more than 1 year in the future';
    }
    
    // If no errors, insert job
    if (empty($errors)) {
        try {
            // Get company name from profile
            $company_name = $profile['company_name'] ?? '';
            
            $sql = "INSERT INTO jobs (recruiter_id, title, description, requirements, job_type, location, salary, deadline, featured, company_name, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            $stmt->bind_param("isssssssis", 
                $user_id, 
                $title, 
                $description, 
                $requirements, 
                $job_type, 
                $location, 
                $salary, 
                $deadline, 
                $is_featured,
                $company_name
            );
            
            if ($stmt->execute()) {
                $job_id = $conn->insert_id;
                error_log("New job posted successfully. Job ID: " . $job_id);
                $_SESSION['success'] = 'Job posted successfully!';
                header('Location: my-jobs.php');
                exit();
            } else {
                throw new Exception('Failed to execute statement: ' . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Job posting error: " . $e->getMessage());
            $errors[] = 'Failed to post job: ' . $e->getMessage();
        }
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
                        <h4 class="mb-0">Post New Job</h4>
                        <a href="my-jobs.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Back to Jobs
                        </a>
                    </div>
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
                    
                    <form method="POST" action="post-job.php" class="needs-validation" novalidate>
                        <!-- Job Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Job Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                            <div class="invalid-feedback">
                                Please enter a job title.
                            </div>
                        </div>
                        
                        <!-- Job Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Job Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            <div class="invalid-feedback">
                                Please enter a job description.
                            </div>
                        </div>
                        
                        <!-- Job Requirements -->
                        <div class="mb-3">
                            <label for="requirements" class="form-label">Job Requirements</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="4" required><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
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
                                    <option value="<?= $value ?>" <?= isset($_POST['job_type']) && $_POST['job_type'] === $value ? 'selected' : '' ?>>
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
                            <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
                            <div class="invalid-feedback">
                                Please enter a location.
                            </div>
                        </div>
                        
                        <!-- Salary -->
                        <div class="mb-3">
                            <label for="salary" class="form-label">Salary</label>
                            <input type="text" class="form-control" id="salary" name="salary" value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>" 
                                placeholder="e.g., ₹12,000 – ₹15,000 per month" required>
                            <div class="invalid-feedback">
                                Please enter a valid salary format (e.g., ₹12,000 – ₹15,000 per month)
                            </div>
                            <small class="text-muted">
                                Valid formats: ₹12,000 – ₹15,000 per month, 5-7 Lakhs PA, 50K-70K per month
                            </small>
                        </div>
                        
                        <!-- Application Deadline -->
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Application Deadline</label>
                            <input type="date" class="form-control" id="deadline" name="deadline" value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>" required>
                            <div class="invalid-feedback">
                                Please select an application deadline.
                            </div>
                        </div>
                        
                        <!-- Featured Job -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_featured">
                                    Feature this job (Additional charges may apply)
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Post Job</button>
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
