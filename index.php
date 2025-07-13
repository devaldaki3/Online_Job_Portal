<?php
// Start session
session_start();

// Page title
$pageTitle = 'Home';

// Include database connection
require_once 'includes/db.php';

// Include functions
require_once 'includes/functions.php';

// Get featured jobs
$featuredJobs = getFeaturedJobs($conn, 6);

// Get recent jobs
$recentJobs = getRecentJobs($conn, 10);

// Get job types for search form
$jobTypes = getJobTypes();

// Include header
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero text-center text-white">
    <div class="container">
        <h1>Find Your Dream Job Today</h1>
        <p class="lead">Browse thousands of job listings and find the perfect match for your career.</p>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <form action="<?php 
                    if (isset($_SESSION['role'])) {
                        switch ($_SESSION['role']) {
                            case 'admin':
                                echo 'admin/manage-jobs.php';
                                break;
                            case 'recruiter':
                                echo 'recruiter/my-jobs.php';
                                break;
                            default:
                                echo 'jobseeker/browse-jobs.php';
                        }
                    } else {
                        echo 'jobseeker/browse-jobs.php';
                    }
                ?>" method="GET" id="jobSearchForm" class="bg-white p-4 rounded shadow">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="keyword" id="keyword" placeholder="Job title or keyword">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="location" id="location" placeholder="Location">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Featured Jobs Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Featured Jobs</h2>
        <div class="row g-4">
            <?php if (empty($featuredJobs)): ?>
                <div class="col-12 text-center">
                    <p>No featured jobs available at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($featuredJobs as $job): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card job-card h-100">
                            <div class="card-body">
                                <span class="badge bg-warning text-dark featured-badge">Featured</span>
                                <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <i class="fas fa-building me-1"></i> <?= htmlspecialchars($job['company_name'] ?? $job['recruiter_name']) ?>
                                </h6>
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['location']) ?><br>
                                    <i class="fas fa-clock me-1"></i> <?= htmlspecialchars(ucfirst($job['job_type'])) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="text-primary">
                                        <i class="fas fa-money-bill-wave me-1"></i> 
                                        <?= !empty($job['salary']) ? htmlspecialchars($job['salary']) : 'Not specified' ?>
                                    </span>
                                    <a href="jobseeker/job-details.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <small>Posted <?= formatDate($job['created_at']) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="jobseeker/browse-jobs.php" class="btn btn-outline-primary">View All Jobs</a>
        </div>
    </div>
</section>

<!-- Job Categories Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Job Categories</h2>
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <i class="fas fa-laptop-code fa-3x text-primary mb-3"></i>
                        <h5>Technology</h5>
                        <p class="text-muted">Software, IT, Data</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                        <h5>Finance</h5>
                        <p class="text-muted">Banking, Accounting</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <i class="fas fa-stethoscope fa-3x text-danger mb-3"></i>
                        <h5>Healthcare</h5>
                        <p class="text-muted">Medical, Nursing</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <i class="fas fa-chalkboard-teacher fa-3x text-warning mb-3"></i>
                        <h5>Education</h5>
                        <p class="text-muted">Teaching, Training</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-user-plus fa-2x"></i>
                </div>
                <h4>Create an Account</h4>
                <p>Register as a job seeker or recruiter to access all features.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-search fa-2x"></i>
                </div>
                <h4>Find or Post Jobs</h4>
                <p>Search for your dream job or post job openings for your company.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
                <h4>Apply or Hire</h4>
                <p>Apply to jobs with your resume or hire talented professionals.</p>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 mb-4 mb-md-0">
                <h2><?= countTotalJobs($conn) ?></h2>
                <p>Jobs Available</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h2><?= countUsersByRole($conn, 'jobseeker') ?></h2>
                <p>Job Seekers</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h2><?= countUsersByRole($conn, 'recruiter') ?></h2>
                <p>Recruiters</p>
            </div>
            <div class="col-md-3">
                <h2><?= countTotalApplications($conn) ?></h2>
                <p>Applications</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2>Ready to Start Your Career Journey?</h2>
                <p class="lead">Join thousands of job seekers and recruiters on our platform.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="register.php" class="btn btn-primary btn-lg">Register Now</a>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>