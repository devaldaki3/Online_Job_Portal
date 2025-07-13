<?php
// Admin Dashboard (admin/index.php)
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get statistics
$total_jobs = countTotalJobs($conn);
$total_recruiters = countUsersByRole($conn, 'recruiter');
$total_jobseekers = countUsersByRole($conn, 'jobseeker');
$total_applications = countTotalApplications($conn);

// Page title
$pageTitle = 'Admin Dashboard';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Admin Dashboard</h1>
                <div>
                    <a href="manage-users.php" class="btn btn-primary">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                    <a href="manage-jobs.php" class="btn btn-success ms-2">
                        <i class="fas fa-briefcase me-2"></i>Manage Jobs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Total Jobs Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Jobs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_jobs; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Recruiters Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Recruiters</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_recruiters; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Job Seekers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Job Seekers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_jobseekers; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Applications Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Applications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_applications; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">Recent Activity</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Recent Jobs</th>
                                    <th>Recent Applications</th>
                                    <th>Recent Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <a href="manage-jobs.php" class="btn btn-sm btn-primary">
                                            View All Jobs
                                        </a>
                                    </td>
                                    <td>
                                        <a href="manage-applications.php" class="btn btn-sm btn-primary">
                                            View All Applications
                                        </a>
                                    </td>
                                    <td>
                                        <a href="manage-users.php" class="btn btn-sm btn-primary">
                                            View All Users
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df;
}
.border-left-success {
    border-left: 4px solid #1cc88a;
}
.border-left-info {
    border-left: 4px solid #36b9cc;
}
.border-left-warning {
    border-left: 4px solid #f6c23e;
}
.text-gray-300 {
    color: #dddfeb;
}
.text-gray-800 {
    color: #5a5c69;
}
.card {
    position: relative;
}
.card .card-body {
    padding: 1.25rem;
}
</style>

<?php include '../includes/footer.php'; ?>