<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = 'Deval@7148';
$database = 'job_portal';

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($database);

// Create users table if not exists
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'recruiter', 'jobseeker') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating users table: " . $conn->error);
}

// Create profiles table if not exists
$sql = "CREATE TABLE IF NOT EXISTS profiles (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    bio TEXT,
    skills TEXT,
    experience TEXT,
    education TEXT,
    resume_path VARCHAR(255),
    profile_image VARCHAR(255),
    company_name VARCHAR(100),
    company_description TEXT,
    company_website VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating profiles table: " . $conn->error);
}

// Create jobs table if not exists
$sql = "CREATE TABLE IF NOT EXISTS jobs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    recruiter_id INT(11) NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    location VARCHAR(100) NOT NULL,
    job_type ENUM('full-time', 'part-time', 'contract', 'internship', 'remote') NOT NULL,
    salary VARCHAR(50),
    company_name VARCHAR(100),
    deadline DATE,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating jobs table: " . $conn->error);
}

// Add deadline and company_name columns if they don't exist
$sql = "SELECT COLUMN_NAME FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = '$database' 
        AND TABLE_NAME = 'jobs'";
$result = $conn->query($sql);
$existing_columns = [];
while ($row = $result->fetch_assoc()) {
    $existing_columns[] = $row['COLUMN_NAME'];
}

// Add deadline column if it doesn't exist
if (!in_array('deadline', $existing_columns)) {
    $sql = "ALTER TABLE jobs ADD COLUMN deadline DATE AFTER salary";
    if ($conn->query($sql) !== TRUE) {
        die("Error adding deadline column: " . $conn->error);
    }
}

// Add company_name column if it doesn't exist
if (!in_array('company_name', $existing_columns)) {
    $sql = "ALTER TABLE jobs ADD COLUMN company_name VARCHAR(100) AFTER salary";
    if ($conn->query($sql) !== TRUE) {
        die("Error adding company_name column: " . $conn->error);
    }
}

// Create applications table if not exists
$sql = "CREATE TABLE IF NOT EXISTS applications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    job_id INT(11) NOT NULL,
    jobseeker_id INT(11) NOT NULL,
    resume_path VARCHAR(255) NOT NULL,
    cover_letter TEXT,
    status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (jobseeker_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY job_user (job_id, jobseeker_id)
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating applications table: " . $conn->error);
}

// Create notifications table if not exists
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    type ENUM('application', 'status_update', 'message') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating notifications table: " . $conn->error);
}

// Check if admin exists, if not create default admin account
$sql = "SELECT * FROM users WHERE role = 'admin' LIMIT 1";
$result = $conn->query($sql);

if($result->num_rows == 0) {
    // Create default admin
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, role, full_name) 
            VALUES ('admin', 'admin@jobportal.com', '$admin_password', 'admin', 'System Administrator')";
    
    if ($conn->query($sql) !== TRUE) {
        echo "Error creating default admin: " . $conn->error;
    }
}

// Function to sanitize user inputs
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// Check for missing columns in profiles table
$sql = "SELECT COLUMN_NAME FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = '$database' 
        AND TABLE_NAME = 'profiles'";
$result = $conn->query($sql);
$existing_columns = [];
while ($row = $result->fetch_assoc()) {
    $existing_columns[] = $row['COLUMN_NAME'];
}

// Add company_description column if it doesn't exist
if (!in_array('company_description', $existing_columns)) {
    $sql = "ALTER TABLE profiles ADD COLUMN company_description TEXT AFTER company_name";
    if ($conn->query($sql) !== TRUE) {
        die("Error adding company_description column: " . $conn->error);
    }
}

// Add company_website column if it doesn't exist
if (!in_array('company_website', $existing_columns)) {
    $sql = "ALTER TABLE profiles ADD COLUMN company_website VARCHAR(255) AFTER company_description";
    if ($conn->query($sql) !== TRUE) {
        die("Error adding company_website column: " . $conn->error);
    }
}
?>