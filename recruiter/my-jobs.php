<?php
// Start session
session_start();

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Page title
$pageTitle = 'My Jobs';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get search parameters
$keyword = isset($_GET['keyword']) ? sanitize_input($conn, $_GET['keyword']) : '';
$status = isset($_GET['status']) ? sanitize_input($conn, $_GET['status']) : '';
$job_type = isset($_GET['job_type']) ? sanitize_input($conn, $_GET['job_type']) : '';

// Build query
$sql = "SELECT * FROM jobs WHERE recruiter_id = ?";
$params = [$_SESSION['user_id']];
$types = "i";

if (!empty($keyword)) {
    $sql .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $keyword_param = "%$keyword%";
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $types .= "sss";
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($job_type)) {
    $sql .= " AND job_type = ?";
    $params[] = $job_type;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">My Jobs</h4>
                        <a href="post-job.php" class="btn btn-light">
                            <i class="fas fa-plus me-1"></i> Post New Job
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search Filters -->
                    <form method="GET" action="my-jobs.php" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Search jobs...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="job_type">
                                    <option value="">All Types</option>
                                    <?php foreach (getJobTypes() as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $job_type === $value ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Jobs Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Applications</th>
                                    <th>Status</th>
                                    <th>Posted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jobs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                            <h5>No jobs found</h5>
                                            <p class="text-muted">Try adjusting your search criteria</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td>
                                                <a href="job-details.php?id=<?= $job['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($job['title']) ?>
                                                    <?php if ($job['featured']): ?>
                                                        <span class="badge bg-warning ms-1">Featured</span>
                                                    <?php endif; ?>
                                                </a>
                                            </td>
                                            <td><?= getJobTypes()[$job['job_type']] ?></td>
                                            <td><?= htmlspecialchars($job['location']) ?></td>
                                            <td>
                                                <?php
                                                $stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
                                                $stmt->bind_param("i", $job['id']);
                                                $stmt->execute();
                                                $count = $stmt->get_result()->fetch_row()[0];
                                                ?>
                                                <a href="applications.php?job_id=<?= $job['id'] ?>" class="text-decoration-none">
                                                    <?= $count ?> Applications
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $job['status'] === 'open' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($job['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($job['created_at']) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit-job.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteJob(<?= $job['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteJob(jobId) {
    if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
        fetch('delete-job.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + jobId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete job: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the job.');
        });
    }
}
</script>

<?php
// Include footer
include '../includes/footer.php';
?>
