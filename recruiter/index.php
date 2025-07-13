<?php
// Start session
session_start();

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get search parameters
$keyword = isset($_GET['keyword']) ? sanitize_input($conn, $_GET['keyword']) : '';
$status = isset($_GET['status']) ? sanitize_input($conn, $_GET['status']) : '';
$job_type = isset($_GET['job_type']) ? sanitize_input($conn, $_GET['job_type']) : '';

// Get recruiter's jobs with search filters
$recruiter_id = $_SESSION['user_id'];
$sql = "SELECT * FROM jobs WHERE recruiter_id = ?";
$params = [$recruiter_id];
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
$result = $stmt->get_result();
$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

// Page title
$pageTitle = 'My Jobs';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My Jobs</h1>
        <a href="post-job.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Post New Job
        </a>
    </div>

    <!-- Search Filters -->
    <form method="GET" action="index.php" class="row g-3 mb-4">
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
    </form>

    <!-- Jobs Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
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
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($job['title']) ?>
                                    <?php if ($job['featured']): ?>
                                        <span class="badge bg-warning text-dark ms-1">Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($job['job_type']) ?></td>
                                <td><?= htmlspecialchars($job['location']) ?></td>
                                <td>
                                    <a href="applications.php?job_id=<?= $job['id'] ?>" class="text-primary text-decoration-none">
                                        <?= getApplicationsCount($conn, $job['id']) ?> Applications
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $job['status'] === 'open' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst(htmlspecialchars($job['status'])) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view-job.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Job">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-job.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-info" title="Edit Job">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteJob(<?= $job['id'] ?>)" title="Delete Job">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function deleteJob(jobId) {
    if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
        console.log('Deleting job with ID:', jobId);
        const formData = new FormData();
        formData.append('id', jobId);
        
        fetch('delete-job.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Server response:', data);
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the job.');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
