<?php
// Include database connection
require_once 'includes/db.php';

// Function to insert a job
function insertJob($conn, $recruiter_id, $title, $description, $requirements, $job_type, $location, $salary, $is_featured = 0) {
    $sql = "INSERT INTO jobs (recruiter_id, title, description, requirements, job_type, location, salary, featured, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssi", $recruiter_id, $title, $description, $requirements, $job_type, $location, $salary, $is_featured);
    return $stmt->execute();
}

// Example jobs data
$jobs = [
    [
        'title' => 'Senior PHP Developer',
        'description' => 'We are looking for an experienced PHP Developer to join our team in Ahmedabad. You will be responsible for developing and maintaining web applications, working with MySQL databases, and implementing RESTful APIs. The ideal candidate should have strong problem-solving skills and be able to work independently.',
        'requirements' => '3+ years of experience in PHP development
Strong knowledge of MySQL and database design
Experience with Laravel or CodeIgniter framework
Understanding of RESTful API development
Good knowledge of HTML, CSS, and JavaScript
Experience with version control systems (Git)',
        'job_type' => 'full-time',
        'location' => 'Ahmedabad, Gujarat',
        'salary' => '₹8,00,000 - ₹12,00,000 per annum',
        'is_featured' => 1
    ],
    [
        'title' => 'Digital Marketing Executive',
        'description' => 'We are seeking a creative and analytical Digital Marketing Executive to help grow our online presence. You will be responsible for managing social media accounts, creating content, running digital campaigns, and analyzing performance metrics.',
        'requirements' => '1-2 years of experience in digital marketing
Strong knowledge of social media platforms
Experience with Google Analytics and SEO
Good content writing skills
Basic knowledge of graphic design tools
Ability to work independently and meet deadlines',
        'job_type' => 'part-time',
        'location' => 'Ahmedabad, Gujarat',
        'salary' => '₹15,000 - ₹25,000 per month',
        'is_featured' => 0
    ],
    [
        'title' => 'Customer Support Executive',
        'description' => 'We are looking for a Customer Support Executive to handle customer inquiries, provide product information, and resolve issues. The role requires excellent communication skills and the ability to work in a fast-paced environment.',
        'requirements' => 'Excellent communication skills in English and Hindi
1+ year of experience in customer service
Basic computer knowledge
Ability to handle customer complaints professionally
Good problem-solving skills
Willingness to work in shifts',
        'job_type' => 'full-time',
        'location' => 'Ahmedabad, Gujarat',
        'salary' => '₹20,000 - ₹30,000 per month',
        'is_featured' => 0
    ],
    [
        'title' => 'Web Development Intern',
        'description' => 'We are offering an internship opportunity for aspiring web developers. You will work on real projects, learn modern web technologies, and get hands-on experience in a professional environment.',
        'requirements' => 'Basic knowledge of HTML, CSS, and JavaScript
Eagerness to learn new technologies
Good problem-solving skills
Currently pursuing or recently completed a degree in Computer Science or related field
Portfolio or GitHub profile (preferred)',
        'job_type' => 'internship',
        'location' => 'Ahmedabad, Gujarat',
        'salary' => '₹5,000 - ₹10,000 per month',
        'is_featured' => 0
    ]
];

// Get a recruiter ID (assuming there's at least one recruiter in the database)
$sql = "SELECT id FROM users WHERE role = 'recruiter' LIMIT 1";
$result = $conn->query($sql);
$recruiter = $result->fetch_assoc();

if ($recruiter) {
    $recruiter_id = $recruiter['id'];
    
    // Insert each job
    foreach ($jobs as $job) {
        if (insertJob($conn, $recruiter_id, $job['title'], $job['description'], $job['requirements'], 
            $job['job_type'], $job['location'], $job['salary'], $job['is_featured'])) {
            echo "Successfully inserted job: " . $job['title'] . "\n";
        } else {
            echo "Failed to insert job: " . $job['title'] . "\n";
            echo "Error: " . $conn->error . "\n";
        }
    }
} else {
    echo "No recruiter found in the database. Please create a recruiter account first.\n";
}

// Close connection
$conn->close();
?> 