<?php
// Register Page (register.php)
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Page title
$pageTitle = 'Register';

// Include database connection
require_once 'includes/db.php';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($conn, $_POST['full_name']);
    $username = sanitize_input($conn, $_POST['username']);
    $email = sanitize_input($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize_input($conn, $_POST['role']);
    $errors = [];
    
    // Validate inputs
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    } else {
        // Check if username exists
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Username already exists';
        }
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } else {
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Email already exists';
        }
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!in_array($role, ['jobseeker', 'recruiter'])) {
        $errors[] = 'Invalid role selected';
    }
    
    // If no errors, create user account
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, role, full_name) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $full_name);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Create empty profile
            $sql = "INSERT INTO profiles (user_id) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Set success message and redirect to login
            $_SESSION['success'] = 'Registration successful! You can now log in.';
            header('Location: login.php');
            exit();
        } else {
            $errors[] = 'Registration failed. Please try again later.';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Create an Account</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="register.php" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= isset($full_name) ? htmlspecialchars($full_name) : '' ?>" required>
                        <div class="invalid-feedback">
                            Please enter your full name.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required>
                        <div class="invalid-feedback">
                            Please choose a username (at least 3 characters).
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                        <div class="invalid-feedback">
                            Please enter a valid email address.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <span class="input-group-text">
                                <i class="fas fa-eye toggle-password" toggle="#password"></i>
                            </span>
                            <div class="invalid-feedback">
                                Please enter a password (at least 6 characters).
                            </div>
                        </div>
                        <small class="form-text text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <span class="input-group-text">
                                <i class="fas fa-eye toggle-password" toggle="#confirm_password"></i>
                            </span>
                            <div class="invalid-feedback">
                                Please confirm your password.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Account Type</label>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <div class="form-check card p-3">
                                    <input class="form-check-input" type="radio" name="role" id="jobseeker" value="jobseeker" <?= (!isset($role) || $role === 'jobseeker') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="jobseeker">
                                        <i class="fas fa-user me-2"></i>Job Seeker
                                    </label>
                                    <small class="d-block text-muted mt-1">I'm looking for job opportunities</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check card p-3">
                                    <input class="form-check-input" type="radio" name="role" id="recruiter" value="recruiter" <?= (isset($role) && $role === 'recruiter') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="recruiter">
                                        <i class="fas fa-building me-2"></i>Recruiter
                                    </label>
                                    <small class="d-block text-muted mt-1">I'm hiring for my company</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">I agree to the Terms of Service and Privacy Policy</label>
                        <div class="invalid-feedback">
                            You must agree to the terms before registering.
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>