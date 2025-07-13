<?php
// Start session
session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit();
}

// Page title
$pageTitle = 'My Applications';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Get all applications for the jobseeker
$sql = "SELECT a.*, j.title, j.location, j.job_type, j.salary,
        u.full_name as recruiter_name, p.company_name
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON j.recruiter_id = u.id
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE a.jobseeker_id = ?
        ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Applications</h5>
                    <a href="browse-jobs.php" class="btn btn-light btn-sm">Browse Jobs</a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <?php if (empty($applications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No applications found</h5>
                            <p class="mb-3">You haven't applied to any jobs yet.</p>
                            <a href="browse-jobs.php" class="btn btn-primary">Browse Available Jobs</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Company</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Applied On</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $application): ?>
                                        <tr>
                                            <td>
                                                <a href="view-job.php?id=<?= $application['job_id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($application['title']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($application['company_name']) ?></td>
                                            <td><i class="fas fa-map-marker-alt text-muted me-1"></i><?= htmlspecialchars($application['location']) ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars(ucfirst($application['job_type'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($application['created_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadgeClass($application['status']) ?>">
                                                    <?= ucfirst($application['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view-application.php?id=<?= $application['id'] ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($application['status'] === 'pending'): ?>
                                                        <a href="withdraw-application.php?id=<?= $application['id'] ?>" 
                                                           class="btn btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to withdraw this application?')">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
