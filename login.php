<?php
// Login Page (login.php)
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Page title
$pageTitle = 'Login';

// Include database connection
require_once 'includes/db.php';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($conn, $_POST['username']);
    $password = $_POST['password'];
    $errors = [];
    
    // Validate inputs
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        header('Location: admin/index.php');
                        break;
                    case 'recruiter':
                        header('Location: recruiter/index.php');
                        break;
                    case 'jobseeker':
                        header('Location: jobseeker/index.php');
                        break;
                    default:
                        header('Location: index.php');
                }
                exit();
            } else {
                $errors[] = 'Invalid username or password';
            }
        } else {
            $errors[] = 'Invalid username or password';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Login</h4>
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
                
                <form method="POST" action="login.php" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">
                            Please enter your username or email.
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
                                Please enter your password.
                            </div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>