<?php
// Start session
session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit();
}

// Page title
$pageTitle = 'Browse Jobs';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get search parameters
$keyword = isset($_GET['keyword']) ? sanitize_input($conn, $_GET['keyword']) : '';
$location = isset($_GET['location']) ? sanitize_input($conn, $_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? sanitize_input($conn, $_GET['job_type']) : '';

// Get job types
$jobTypes = getJobTypes();

// Search jobs
$jobs = searchJobs($conn, $keyword, $location, $job_type);

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Search Filters -->
        <div class="col-md-3">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Search Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="browse-jobs.php" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="keyword" class="form-label">Keywords</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Job title or keywords">
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($location) ?>" placeholder="City or country">
                        </div>
                        
                        <div class="mb-3">
                            <label for="job_type" class="form-label">Job Type</label>
                            <select class="form-select" id="job_type" name="job_type">
                                <option value="">All Types</option>
                                <?php foreach ($jobTypes as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $job_type === $value ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Search Jobs
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Job Search Tips -->
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Search Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Use specific keywords related to your skills
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Try different variations of job titles
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Filter by location to find nearby opportunities
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Use job type filters to find your preferred work arrangement
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Job Listings -->
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Job Listings</h5>
                        <span class="badge bg-primary"><?= count($jobs) ?> Jobs Found</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($jobs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4>No jobs found</h4>
                            <p class="text-muted">Try adjusting your search criteria</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($jobs as $job): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?= htmlspecialchars($job['title']) ?></h5>
                                        <small class="text-muted">Posted <?= formatDate($job['created_at']) ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-primary me-2"><?= $jobTypes[$job['job_type']] ?></span>
                                        <span class="text-muted">
                                            <i class="fas fa-building me-1"></i> <?= htmlspecialchars($job['company_name']) ?>
                                            <i class="fas fa-map-marker-alt ms-2 me-1"></i> <?= htmlspecialchars($job['location']) ?>
                                        </span>
                                    </div>
                                    <p class="mb-1"><?= substr(htmlspecialchars($job['description']), 0, 200) ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <?php if (!empty($job['salary'])): ?>
                                                <span class="text-primary">
                                                    <i class="fas fa-money-bill-wave me-1"></i> <?= htmlspecialchars($job['salary']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if (hasApplied($conn, $job['id'], $_SESSION['user_id'])): ?>
                                                <span class="badge bg-success">Already Applied</span>
                                            <?php else: ?>
                                                <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">
                                                    View Details
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
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
