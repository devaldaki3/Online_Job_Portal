<?php
// Get user information by ID
function getUserById($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get user profile by user ID
function getProfileByUserId($conn, $user_id) {
    $sql = "SELECT * FROM profiles WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Get the result metadata to fetch column names
    $meta = $stmt->result_metadata();
    $fields = array();
    $result = array();
    
    // Store column names
    while ($field = $meta->fetch_field()) {
        $fields[] = &$result[$field->name];
    }
    
    // Bind results to variables
    call_user_func_array(array($stmt, 'bind_result'), $fields);
    
    // Fetch the result
    if ($stmt->fetch()) {
        $profile = array();
        foreach ($result as $key => $value) {
            $profile[$key] = $value;
        }
        $stmt->close();
        return $profile;
    }
    
    $stmt->close();
    return null;
}

// Get job information by ID
function getJobById($conn, $job_id) {
    $sql = "SELECT j.*, u.full_name AS recruiter_name, p.company_name 
            FROM jobs j
            JOIN users u ON j.recruiter_id = u.id
            LEFT JOIN profiles p ON u.id = p.user_id
            WHERE j.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get featured jobs
function getFeaturedJobs($conn, $limit = 6) {
    $sql = "SELECT j.*, u.full_name AS recruiter_name, p.company_name 
            FROM jobs j
            JOIN users u ON j.recruiter_id = u.id
            LEFT JOIN profiles p ON u.id = p.user_id
            WHERE j.featured = 1 AND j.status = 'open'
            ORDER BY j.created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    return $jobs;
}

// Get recent jobs
function getRecentJobs($conn, $limit = 10) {
    $sql = "SELECT j.*, u.full_name AS recruiter_name, p.company_name 
            FROM jobs j
            JOIN users u ON j.recruiter_id = u.id
            LEFT JOIN profiles p ON u.id = p.user_id
            WHERE j.status = 'open'
            ORDER BY j.created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    return $jobs;
}

// Check if user has applied to a job
function hasApplied($conn, $job_id, $user_id) {
    $sql = "SELECT id FROM applications WHERE job_id = ? AND jobseeker_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $job_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Get job applications count
function getApplicationsCount($conn, $job_id) {
    $sql = "SELECT COUNT(*) as count FROM applications WHERE job_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

// Format date for display
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Get job types as array
function getJobTypes() {
    return [
        'full-time' => 'Full Time',
        'part-time' => 'Part Time',
        'contract' => 'Contract',
        'internship' => 'Internship',
        'remote' => 'Remote'
    ];
}

// Upload file with validation
function uploadFile($file, $destination, $allowedTypes = ['application/pdf'], $maxSize = 5242880) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
    
    // Check file size (5MB max)
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
    }
    
    // Check file type
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Please upload a PDF file.'];
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $destination . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}

// Search jobs based on criteria
function searchJobs($conn, $keyword = '', $location = '', $job_type = '') {
    $sql = "SELECT j.*, u.full_name AS recruiter_name, p.company_name 
            FROM jobs j
            JOIN users u ON j.recruiter_id = u.id
            LEFT JOIN profiles p ON u.id = p.user_id
            WHERE j.status = 'open'";
    
    $params = [];
    $types = "";
    
    if (!empty($keyword)) {
        $keyword = "%$keyword%";
        $sql .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ?)";
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $types .= "sss";
    }
    
    if (!empty($location)) {
        $location = "%$location%";
        $sql .= " AND j.location LIKE ?";
        $params[] = $location;
        $types .= "s";
    }
    
    if (!empty($job_type)) {
        $sql .= " AND j.job_type = ?";
        $params[] = $job_type;
        $types .= "s";
    }
    
    $sql .= " ORDER BY j.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    return $jobs;
}

// Count total jobs
function countTotalJobs($conn) {
    $sql = "SELECT COUNT(*) as total FROM jobs";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Count total users by role
function countUsersByRole($conn, $role) {
    $sql = "SELECT COUNT(*) as total FROM users WHERE role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Count total applications
function countTotalApplications($conn) {
    $sql = "SELECT COUNT(*) as total FROM applications";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'accepted':
            return 'success';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to check if username exists
function isUsernameExists($conn, $username, $exclude_user_id = null) {
    $sql = "SELECT id FROM users WHERE username = ?";
    if ($exclude_user_id) {
        $sql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($exclude_user_id) {
        $stmt->bind_param("si", $username, $exclude_user_id);
    } else {
        $stmt->bind_param("s", $username);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to check if email exists
function isEmailExists($conn, $email, $exclude_user_id = null) {
    $sql = "SELECT id FROM users WHERE email = ?";
    if ($exclude_user_id) {
        $sql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($exclude_user_id) {
        $stmt->bind_param("si", $email, $exclude_user_id);
    } else {
        $stmt->bind_param("s", $email);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to get user profile
function getUserProfile($conn, $user_id) {
    $sql = "SELECT * FROM profiles WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to update user profile
function updateUserProfile($conn, $user_id, $data) {
    $sql = "INSERT INTO profiles (user_id, phone, address, skills, experience, education, resume_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            phone = VALUES(phone),
            address = VALUES(address),
            skills = VALUES(skills),
            experience = VALUES(experience),
            education = VALUES(education),
            resume_path = VALUES(resume_path)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", 
        $user_id,
        $data['phone'],
        $data['address'],
        $data['skills'],
        $data['experience'],
        $data['education'],
        $data['resume_path']
    );
    
    return $stmt->execute();
}

/**
 * Get the count of applications for a job by status
 */
function getApplicationCountByStatus($conn, $job_id, $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = ? AND status = ?");
    $stmt->bind_param("is", $job_id, $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

// Create a new notification
function createNotification($conn, $user_id, $type, $title, $message, $related_id = null) {
    $sql = "INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $user_id, $type, $title, $message, $related_id);
    return $stmt->execute();
}

// Get unread notifications count
function getUnreadNotificationsCount($conn, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Get user notifications
function getUserNotifications($conn, $user_id, $limit = 10) {
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    return $notifications;
}

// Mark notification as read
function markNotificationAsRead($conn, $notification_id, $user_id) {
    $sql = "UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    return $stmt->execute();
}

// Mark all notifications as read
function markAllNotificationsAsRead($conn, $user_id) {
    $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}
?>