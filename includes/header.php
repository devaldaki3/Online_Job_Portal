<?php
// Get the base URL
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/Online_Job_Portal/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Job Portal' : 'Job Portal'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
</head>
<body></body>
    <!-- Navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary" href="<?php echo $base_url; ?>">
                <i class="fas fa-briefcase"></i> Job Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>">Home</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'jobseeker'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>jobseeker/browse-jobs.php">Browse Jobs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>jobseeker/my-applications.php">My Applications</a>
                        </li>
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'recruiter'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>recruiter/post-job.php">Post Job</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>recruiter/my-jobs.php">My Jobs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>recruiter/applications.php">Applications</a>
                        </li>
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>admin/manage-users.php">Manage Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>admin/manage-jobs.php">Manage Jobs</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>admin/index.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>admin/profile.php">Profile</a></li>
                                <?php elseif ($_SESSION['role'] === 'recruiter'): ?>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>recruiter/index.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>recruiter/profile.php">Profile</a></li>
                                <?php elseif ($_SESSION['role'] === 'jobseeker'): ?>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>jobseeker/index.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>jobseeker/profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $base_url; ?>jobseeker/resume.php">Resume</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page content container -->
    <div class="container mt-4">
        <?php 
        if (function_exists('displayMessages')) {
            echo displayMessages();
        } elseif (isset($_SESSION['success']) || isset($_SESSION['error']) || isset($_SESSION['info'])) {
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            if (isset($_SESSION['info'])) {
                echo '<div class="alert alert-info">' . $_SESSION['info'] . '</div>';
                unset($_SESSION['info']);
            }
        }
        ?>