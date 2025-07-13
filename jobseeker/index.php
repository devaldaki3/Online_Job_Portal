<?php
// Start session
session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit();
}

// Page title
$pageTitle = 'Job Seeker Dashboard';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get jobseeker's applications
$jobseeker_id = $_SESSION['user_id'];
$sql = "SELECT a.*, j.title, j.location, j.job_type, j.salary, 
        u.full_name as recruiter_name, p.company_name 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN users u ON j.recruiter_id = u.id 
        LEFT JOIN profiles p ON u.id = p.user_id 
        WHERE a.jobseeker_id = ? 
        ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $jobseeker_id);
$stmt->execute();
$result = $stmt->get_result();
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}

// Get recommended jobs
$sql = "SELECT j.*, u.full_name as recruiter_name, p.company_name 
        FROM jobs j 
        JOIN users u ON j.recruiter_id = u.id 
        LEFT JOIN profiles p ON u.id = p.user_id 
        WHERE j.status = 'open' 
        AND j.id NOT IN (SELECT job_id FROM applications WHERE jobseeker_id = ?) 
        ORDER BY j.created_at DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $jobseeker_id);
$stmt->execute();
$result = $stmt->get_result();
$recommended_jobs = [];
while ($row = $result->fetch_assoc()) {
    $recommended_jobs[] = $row;
}

// Get jobseeker's profile
$sql = "SELECT * FROM profiles WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $jobseeker_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Calculate profile completion percentage
function calculateProfileCompletion($profile) {
    if (!$profile) return 0;
    
    $fields = ['phone', 'address', 'bio', 'skills', 'experience', 'education', 'resume_path'];
    $completed = 0;
    
    foreach ($fields as $field) {
        if (!empty($profile[$field])) {
            $completed++;
        }
    }
    
    return round(($completed / count($fields)) * 100);
}

$profile_completion = calculateProfileCompletion($profile);

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Profile Summary -->
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if (!empty($profile['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($profile['profile_image']) ?>" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        <?php endif; ?>
                    </div>
                    <h4><?= htmlspecialchars($_SESSION['full_name']) ?></h4>
                    <p class="text-muted">Job Seeker</p>
                    <div class="d-grid gap-2">
                        <a href="profile.php" class="btn btn-outline-primary">Edit Profile</a>
                        <a href="resume.php" class="btn btn-outline-secondary">Manage Resume</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-8">
            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Applications</h6>
                            <h3><?= count($applications) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Active Applications</h6>
                            <h3><?= count(array_filter($applications, function($app) { return $app['status'] === 'pending'; })) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Profile Completion</h6>
                            <h3><?= $profile_completion ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Applications</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($applications)): ?>
                        <p class="text-muted">You haven't applied to any jobs yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Company</th>
                                        <th>Status</th>
                                        <th>Applied On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $application): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($application['title']) ?></td>
                                            <td><?= htmlspecialchars($application['company_name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadgeClass($application['status']) ?>">
                                                    <?= ucfirst($application['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($application['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end">
                            <a href="my-applications.php" class="btn btn-outline-primary">View All Applications</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recommended Jobs -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recommended Jobs</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recommended_jobs)): ?>
                        <p class="text-muted">No recommended jobs available at the moment.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recommended_jobs as $job): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($job['title']) ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-building me-1"></i> <?= htmlspecialchars($job['company_name']) ?>
                                                <i class="fas fa-map-marker-alt ms-2 me-1"></i> <?= htmlspecialchars($job['location']) ?>
                                                <?php if ($job['salary']): ?>
                                                    <i class="fas fa-money-bill-wave ms-2 me-1"></i> <?= htmlspecialchars($job['salary']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <a href="view-job.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-end mt-3">
                            <a href="browse-jobs.php" class="btn btn-outline-primary">Browse All Jobs</a>
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
