<?php
// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Page title
$pageTitle = 'Admin Profile';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get user profile
$user_id = $_SESSION['user_id'];

// Get admin user data
$sql = "SELECT u.*, p.* 
        FROM users u 
        LEFT JOIN profiles p ON u.id = p.user_id 
        WHERE u.id = ? AND u.role = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    
    // Update users table
    $update_user = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($update_user);
    $stmt->bind_param("ssi", $full_name, $email, $user_id);
    
    if ($stmt->execute()) {
        // Check if profile exists
        $check_profile = "SELECT * FROM profiles WHERE user_id = ?";
        $stmt = $conn->prepare($check_profile);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $profile_exists = $stmt->get_result()->num_rows > 0;
        
        if ($profile_exists) {
            // Update existing profile
            $update_profile = "UPDATE profiles SET phone = ?, address = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_profile);
            $stmt->bind_param("ssi", $phone, $address, $user_id);
        } else {
            // Create new profile
            $create_profile = "INSERT INTO profiles (user_id, phone, address) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($create_profile);
            $stmt->bind_param("iss", $user_id, $phone, $address);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating profile details.";
        }
    } else {
        $_SESSION['error'] = "Error updating user information.";
    }
    
    // Redirect to refresh the page
    header('Location: profile.php');
    exit();
}

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">My Profile</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

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

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" 
                                      id="address" 
                                      name="address" 
                                      rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php
// Include footer
include '../includes/footer.php';
?>
