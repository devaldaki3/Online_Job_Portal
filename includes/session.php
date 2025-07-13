<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if current user is an admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Check if current user is a recruiter
function isRecruiter() {
    return isLoggedIn() && $_SESSION['role'] === 'recruiter';
}

// Check if current user is a job seeker
function isJobSeeker() {
    return isLoggedIn() && $_SESSION['role'] === 'jobseeker';
}

// Redirect to login page if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'You must log in to access this page';
        header('Location: ../login.php');
        exit();
    }
}

// Redirect to appropriate page based on role
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'You do not have permission to access this page';
        redirectToRoleDashboard();
        exit();
    }
}

// Redirect to appropriate page based on role
function requireRecruiter() {
    requireLogin();
    if (!isRecruiter()) {
        $_SESSION['error'] = 'You do not have permission to access this page';
        redirectToRoleDashboard();
        exit();
    }
}

// Redirect to appropriate page based on role
function requireJobSeeker() {
    requireLogin();
    if (!isJobSeeker()) {
        $_SESSION['error'] = 'You do not have permission to access this page';
        redirectToRoleDashboard();
        exit();
    }
}

// Redirect user to their role-specific dashboard
function redirectToRoleDashboard() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
    
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ../admin/index.php');
            break;
        case 'recruiter':
            header('Location: ../recruiter/index.php');
            break;
        case 'jobseeker':
            header('Location: ../jobseeker/index.php');
            break;
        default:
            header('Location: ../index.php');
    }
    exit();
}

// Display session messages
function displayMessages() {
    $output = '';
    
    if (isset($_SESSION['success'])) {
        $output .= '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        $output .= '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['info'])) {
        $output .= '<div class="alert alert-info">' . $_SESSION['info'] . '</div>';
        unset($_SESSION['info']);
    }
    
    return $output;
}
?>