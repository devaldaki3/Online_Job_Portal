<?php
// Start session
session_start();

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Page title
$pageTitle = 'Recruiter Profile';

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get user profile
$user_id = $_SESSION['user_id'];
$profile = getProfileByUserId($conn, $user_id);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $company_name = sanitize_input($conn, $_POST['company_name']);
    $phone = sanitize_input($conn, $_POST['phone']);
    $address = sanitize_input($conn, $_POST['address']);
    $website = sanitize_input($conn, $_POST['website']);
    $description = sanitize_input($conn, $_POST['description']);
    
    // Validate inputs
    if (empty($company_name)) {
        $errors[] = 'Company name is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    }
    
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid website URL';
    }
    
    // Handle profile image upload
    $profile_image = $profile['profile_image'] ?? '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['profile_image'], '../uploads/profile_images', ['image/jpeg', 'image/png', 'image/gif']);
        if ($upload['success']) {
            $profile_image = $upload['filepath'];
        } else {
            $errors[] = $upload['message'];
        }
    }
    
    // Handle company logo upload
    $company_logo = $profile['company_logo'] ?? '';
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['company_logo'], '../uploads/company_logos', ['image/jpeg', 'image/png', 'image/gif']);
        if ($upload['success']) {
            $company_logo = $upload['filepath'];
        } else {
            $errors[] = $upload['message'];
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        if ($profile) {
            // Update existing profile
            $sql = "UPDATE profiles SET 
                    company_name = ?, 
                    phone = ?, 
                    address = ?, 
                    website = ?, 
                    description = ?, 
                    profile_image = ?, 
                    company_logo = ?
                    WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $company_name, $phone, $address, $website, $description, $profile_image, $company_logo, $user_id);
        } else {
            // Create new profile
            $sql = "INSERT INTO profiles (user_id, company_name, phone, address, website, description, profile_image, company_logo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssss", $user_id, $company_name, $phone, $address, $website, $description, $profile_image, $company_logo);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Profile updated successfully!';
            header('Location: profile.php');
            exit();
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Recruiter Profile</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success'] ?>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="profile.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <!-- Profile Image -->
                        <div class="mb-4 text-center">
                            <div class="mb-3">
                                <?php if (!empty($profile['profile_image'])): ?>
                                    <img src="<?= htmlspecialchars($profile['profile_image']) ?>" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Change Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            </div>
                        </div>
                        
                        <!-- Company Logo -->
                        <div class="mb-4 text-center">
                            <div class="mb-3">
                                <?php if (!empty($profile['company_logo'])): ?>
                                    <img src="<?= htmlspecialchars($profile['company_logo']) ?>" class="img-fluid" style="max-height: 100px;">
                                <?php else: ?>
                                    <i class="fas fa-building fa-5x text-primary"></i>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="company_logo" class="form-label">Change Company Logo</label>
                                <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                            </div>
                        </div>
                        
                        <!-- Company Information -->
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?= htmlspecialchars($profile['company_name'] ?? '') ?>" required>
                            <div class="invalid-feedback">
                                Please enter your company name.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="website" class="form-label">Company Website</label>
                            <input type="url" class="form-control" id="website" name="website" value="<?= htmlspecialchars($profile['website'] ?? '') ?>" placeholder="https://example.com">
                            <div class="invalid-feedback">
                                Please enter a valid website URL.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Company Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($profile['description'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" required>
                            <div class="invalid-feedback">
                                Please enter your phone number.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
                            <div class="invalid-feedback">
                                Please enter your address.
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>
