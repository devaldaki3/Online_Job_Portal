<?php
// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../includes/config.php';

// Get user ID
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id === 0) {
    header('Location: manage-users.php');
    exit();
}

// Get user details
$sql = "SELECT u.*, p.phone, p.address, p.company_name 
        FROM users u
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: manage-users.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $company_name = isset($_POST['company_name']) ? trim($_POST['company_name']) : null;

    $errors = [];

    // Validate required fields
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($role)) $errors[] = "Role is required";

    // Check if email exists (excluding current user)
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already exists";
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Update user table
            $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $full_name, $email, $role, $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user information");
            }

            // Check if profile exists
            $check_sql = "SELECT user_id FROM profiles WHERE user_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $profile_exists = $check_stmt->get_result()->num_rows > 0;

            if ($profile_exists) {
                // Update existing profile
                $profile_sql = "UPDATE profiles SET 
                              phone = ?, 
                              address = ?, 
                              company_name = ?,
                              updated_at = CURRENT_TIMESTAMP 
                              WHERE user_id = ?";
                $profile_stmt = $conn->prepare($profile_sql);
                $profile_stmt->bind_param("sssi", $phone, $address, $company_name, $user_id);
            } else {
                // Insert new profile
                $profile_sql = "INSERT INTO profiles (user_id, phone, address, company_name, created_at, updated_at) 
                              VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
                $profile_stmt = $conn->prepare($profile_sql);
                $profile_stmt->bind_param("isss", $user_id, $phone, $address, $company_name);
            }

            if (!$profile_stmt->execute()) {
                throw new Exception("Failed to update profile information");
            }

            $conn->commit();
            $_SESSION['success'] = "User updated successfully";
            header('Location: manage-users.php');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error updating user: " . $e->getMessage());
            $errors[] = "Failed to update user: " . $e->getMessage();
        }
    }
}

// Page title
$pageTitle = 'Edit User';

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit User</h4>
                        <a href="manage-users.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Back to Users
                        </a>
                    </div>
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

                    <form method="POST" action="edit-user.php?id=<?= $user_id ?>">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="jobseeker" <?= $user['role'] === 'jobseeker' ? 'selected' : '' ?>>Job Seeker</option>
                                <option value="recruiter" <?= $user['role'] === 'recruiter' ? 'selected' : '' ?>>Recruiter</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <?php if ($user['role'] === 'recruiter'): ?>
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?= htmlspecialchars($user['company_name'] ?? '') ?>">
                            </div>
                        <?php endif; ?>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 