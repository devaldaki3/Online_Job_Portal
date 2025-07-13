<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$job_filter = isset($_GET['job_id']) ? $_GET['job_id'] : '';

// Build query
$sql = "SELECT a.*, 
        j.title as job_title, j.location, j.job_type,
        js.full_name as jobseeker_name, js.email as jobseeker_email,
        r.full_name as recruiter_name,
        p.phone as jobseeker_phone
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users js ON a.jobseeker_id = js.id
        JOIN users r ON j.recruiter_id = r.id
        LEFT JOIN profiles p ON js.id = p.user_id
        WHERE 1=1";

$params = [];
$types = "";

if ($status_filter) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($job_filter) {
    $sql .= " AND j.id = ?";
    $params[] = $job_filter;
    $types .= "i";
}

$sql .= " ORDER BY a.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Deduplicate applications to ensure only one entry per application ID
// This prevents duplicate displays that can occur during result processing
$unique_applications = [];
foreach ($applications as $app) {
    $unique_applications[$app['id']] = $app;
}
$applications = array_values($unique_applications);

// Get all jobs for filter dropdown
$jobs_sql = "SELECT id, title FROM jobs ORDER BY title ASC";
$jobs_result = $conn->query($jobs_sql);
$jobs = [];
while ($row = $jobs_result->fetch_assoc()) {
    $jobs[] = $row;
}

// Page title
$pageTitle = 'Manage Applications';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Manage Applications</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label for="job_id" class="form-label">Filter by Job</label>
                    <select name="job_id" id="job_id" class="form-select">
                        <option value="">All Jobs</option>
                        <?php foreach ($jobs as $job): ?>
                            <option value="<?= $job['id'] ?>" <?= ($job_filter == $job['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($job['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="reviewed" <?= ($status_filter === 'reviewed') ? 'selected' : '' ?>>Reviewed</option>
                        <option value="accepted" <?= ($status_filter === 'accepted') ? 'selected' : '' ?>>Accepted</option>
                        <option value="rejected" <?= ($status_filter === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Applications Table -->
    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Applications Found</h5>
                    <p class="mb-0">No applications match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Job Seeker</th>
                                <th>Job Details</th>
                                <th>Recruiter</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($application['jobseeker_name']) ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?= htmlspecialchars($application['jobseeker_email']) ?>
                                            </small>
                                            <?php if (!empty($application['jobseeker_phone'])): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone me-1"></i>
                                                    <?= htmlspecialchars($application['jobseeker_phone']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($application['job_title']) ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= htmlspecialchars($application['location']) ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-briefcase me-1"></i>
                                                <?= htmlspecialchars(ucfirst($application['job_type'])) ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($application['recruiter_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($application['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($application['status']) ?>">
                                            <?= ucfirst($application['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../admin/view-application.php?id=<?= $application['id'] ?>" 
                                               class="btn btn-outline-primary" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($application['status'] === 'pending'): ?>
                                                <button type="button" 
                                                        class="btn btn-outline-success"
                                                        onclick="updateStatus(<?= $application['id'] ?>, 'accepted')"
                                                        title="Accept Application">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-danger"
                                                        onclick="updateStatus(<?= $application['id'] ?>, 'rejected')"
                                                        title="Reject Application">
                                                    <i class="fas fa-times"></i>
                                                </button>
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

<script>
function updateStatus(applicationId, status) {
    if (confirm('Are you sure you want to ' + status + ' this application?')) {
        window.location.href = 'update-application-status.php?id=' + applicationId + '&status=' + status;
    }
}
</script>

<?php include '../includes/footer.php'; ?> 