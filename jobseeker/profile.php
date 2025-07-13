<?php
// Start session
session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit();
}

// Page title
$pageTitle = 'Profile Management';

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
    $phone = sanitize_input($conn, $_POST['phone']);
    $address = sanitize_input($conn, $_POST['address']);
    $bio = sanitize_input($conn, $_POST['bio']);
    $skills = sanitize_input($conn, $_POST['skills']);
    $experience = sanitize_input($conn, $_POST['experience']);
    $education = sanitize_input($conn, $_POST['education']);
    
    // Validate inputs
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    }
    
    if (empty($bio)) {
        $errors[] = 'Bio is required';
    }
    
    if (empty($skills)) {
        $errors[] = 'Skills are required';
    }
    
    if (empty($experience)) {
        $errors[] = 'Experience is required';
    }
    
    if (empty($education)) {
        $errors[] = 'Education is required';
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
    
    // If no errors, update profile
    if (empty($errors)) {
        if ($profile) {
            // Update existing profile
            $sql = "UPDATE profiles SET 
                    phone = ?, 
                    address = ?, 
                    bio = ?, 
                    skills = ?, 
                    experience = ?, 
                    education = ?, 
                    profile_image = ?
                    WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $phone, $address, $bio, $skills, $experience, $education, $profile_image, $user_id);
        } else {
            // Create new profile
            $sql = "INSERT INTO profiles (user_id, phone, address, bio, skills, experience, education, profile_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssss", $user_id, $phone, $address, $bio, $skills, $experience, $education, $profile_image);
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
                    <h4 class="mb-0">Profile Management</h4>
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
                        
                        <!-- Personal Information -->
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
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" required><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                            <div class="invalid-feedback">
                                Please enter your bio.
                            </div>
                        </div>
                        
                        <!-- Skills -->
                        <div class="mb-3">
                            <label for="skills" class="form-label">Skills</label>
                            <textarea class="form-control" id="skills" name="skills" rows="3" required><?= htmlspecialchars($profile['skills'] ?? '') ?></textarea>
                            <div class="form-text">Enter your skills separated by commas (e.g., PHP, MySQL, JavaScript)</div>
                            <div class="invalid-feedback">
                                Please enter your skills.
                            </div>
                        </div>
                        
                        <!-- Experience -->
                        <div class="mb-3">
                            <label for="experience" class="form-label">Work Experience</label>
                            <textarea class="form-control" id="experience" name="experience" rows="5" required><?= htmlspecialchars($profile['experience'] ?? '') ?></textarea>
                            <div class="form-text">Describe your work experience in detail</div>
                            <div class="invalid-feedback">
                                Please enter your work experience.
                            </div>
                        </div>
                        
                        <!-- Education -->
                        <div class="mb-3">
                            <label for="education" class="form-label">Education</label>
                            <textarea class="form-control" id="education" name="education" rows="5" required><?= htmlspecialchars($profile['education'] ?? '') ?></textarea>
                            <div class="form-text">List your educational qualifications</div>
                            <div class="invalid-feedback">
                                Please enter your education details.
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
