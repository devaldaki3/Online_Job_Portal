<?php
// Start session and error reporting
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../includes/config.php';

// Get search parameters
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build base query
$sql = "SELECT u.id, u.full_name, u.email, u.role, u.created_at, u.updated_at, 
        MAX(p.company_name) as company_name 
        FROM users u 
        LEFT JOIN profiles p ON u.id = p.user_id 
        WHERE 1=1";
$params = [];
$types = "";

// Add search conditions
if (!empty($keyword)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $keyword_param = "%$keyword%";
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $types .= "ss";
}

// Add role condition - fixed to properly handle 'all' or empty role
if (!empty($role) && $role !== 'all') {
    $sql .= " AND u.role = ?";
    $params[] = $role;
    $types .= "s";
}

$sql .= " GROUP BY u.id, u.full_name, u.email, u.role, u.created_at, u.updated_at ORDER BY u.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// Page title
$pageTitle = 'Manage Users';

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Manage Users</h4>
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search Filters -->
                    <form method="GET" action="manage-users.php" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Search users...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="role">
                                    <option value="all">All Roles</option>
                                    <option value="jobseeker" <?= $role === 'jobseeker' ? 'selected' : '' ?>>Job Seeker</option>
                                    <option value="recruiter" <?= $role === 'recruiter' ? 'selected' : '' ?>>Recruiter</option>
                                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Company</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                            <h5>No users found</h5>
                                            <p class="text-muted">Try adjusting your search criteria</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getRoleBadgeClass($user['role']) ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= $user['role'] === 'recruiter' ? htmlspecialchars($user['company_name'] ?? '-') : '-' ?>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewUser(<?= $user['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="editUser(<?= $user['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
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

<!-- User Details Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userDetails"></div>
            </div>
        </div>
    </div>
</div>

<?php
function getRoleBadgeClass($role) {
    switch ($role) {
        case 'admin':
            return 'danger';
        case 'recruiter':
            return 'primary';
        case 'jobseeker':
            return 'success';
        default:
            return 'secondary';
    }
}
?>

<!-- JavaScript for handling user actions -->
<script>
// Add the getRoleBadgeClass function in JavaScript
function getRoleBadgeClass(role) {
    switch (role) {
        case 'admin':
            return 'danger';
        case 'recruiter':
            return 'primary';
        case 'jobseeker':
            return 'success';
        default:
            return 'secondary';
    }
}

function viewUser(userId) {
    // Show loading state
    document.getElementById('userDetails').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading user details...</p>
        </div>
    `;

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();

    // Fetch user details
    fetch(`get-user.php?id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug log
            if (data.success) {
                const user = data.user;
                document.getElementById('userDetails').innerHTML = `
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-4">
                                <h4 class="text-primary">${user.full_name}</h4>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-envelope me-2"></i>${user.email}
                                </p>
                                <span class="badge bg-${getRoleBadgeClass(user.role)}">
                                    ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h5 class="text-secondary">Contact Information</h5>
                                <p class="mb-1">
                                    <i class="fas fa-phone me-2"></i>
                                    ${user.phone || 'Not provided'}
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    ${user.address || 'Not provided'}
                                </p>
                            </div>

                            ${user.role === 'recruiter' && user.company_name ? `
                                <div class="mb-4">
                                    <h5 class="text-secondary">Company Information</h5>
                                    <p class="mb-1">
                                        <i class="fas fa-building me-2"></i>
                                        ${user.company_name}
                                    </p>
                                </div>
                            ` : ''}

                            <div>
                                <h5 class="text-secondary">Account Information</h5>
                                <p class="mb-1">
                                    <i class="fas fa-calendar me-2"></i>
                                    Joined: ${new Date(user.created_at).toLocaleDateString()}
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                throw new Error(data.message || 'Failed to fetch user details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('userDetails').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${error.message || 'An error occurred while fetching user details.'}
                </div>
            `;
        });
}

function editUser(userId) {
    window.location.href = `edit-user.php?id=${userId}`;
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch('delete-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the user.');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
