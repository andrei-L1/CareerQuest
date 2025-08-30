<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Manila'); // Adjust as needed

require_once __DIR__ . '/config/dbcon.php'; // Use your PDO $conn

// Fetch existing role IDs
$roles = ['Admin', 'Moderator', 'Professional', 'Employer'];
$role_ids = [];
foreach ($roles as $role) {
    $stmt = $conn->prepare("SELECT role_id FROM role WHERE role_title = ?");
    $stmt->execute([$role]);
    $role_id = $stmt->fetchColumn();
    if ($role_id === false) {
        echo "❌ Error: Role '{$role}' not found in the database.<br>";
        exit;
    }
    $role_ids[$role] = $role_id;
}

// Default password
$rawPassword = 'Test1234!';
$hashedPassword = password_hash($rawPassword, PASSWORD_ARGON2ID);

// Users to insert into user table (Admin, Moderator, Employer)
$users = [
    [
        'first_name' => 'Andrei',
        'middle_name' => 'T',
        'last_name' => 'Lonsania',
        'email' => 'admin@example.com',
        'role_id' => $role_ids['Admin'],
        'user_type' => 'Admin',
        'status' => 'Active'
    ],
    [
        'first_name' => 'Jane',
        'middle_name' => 'Lee',
        'last_name' => 'Smith',
        'email' => 'moderator@example.com',
        'role_id' => $role_ids['Moderator'],
        'user_type' => 'Moderator',
        'status' => 'Active'
    ],
    [
        'first_name' => 'Emma',
        'middle_name' => 'Rose',
        'last_name' => 'Wilson',
        'email' => 'employer@example.com',
        'role_id' => $role_ids['Employer'],
        'user_type' => 'Employer',
        'status' => 'Active'
    ]
];

// Applicants to insert into student table (Student and Regular Applicant)
$applicants = [
    [
        'first_name' => 'John',
        'middle_name' => 'Michael',
        'last_name' => 'Doe',
        'email' => 'student@example.com',
        'gender' => 'Male',
        'date_of_birth' => '2000-01-15',
        'graduation_yr' => 2025,
        'course_id' => null, // Set to null if no course is assigned
        'bio' => 'College student seeking job opportunities',
        'institution' => 'Example University',
        'is_student' => true,
        'edu_background' => 'College Student',
        'status' => 'Active'
    ],
    [
        'first_name' => 'Sarah',
        'middle_name' => 'Anne',
        'last_name' => 'Johnson',
        'email' => 'applicant@example.com',
        'gender' => 'Female',
        'date_of_birth' => '1995-06-20',
        'graduation_yr' => null,
        'course_id' => null,
        'bio' => 'Professional seeking new career opportunities',
        'institution' => null,
        'is_student' => false,
        'edu_background' => 'Professional',
        'status' => 'Active'
    ]
];

// Insert users into user table
foreach ($users as $user) {
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM user WHERE user_email = ?");
    $stmt->execute([$user['email']]);
    if ($stmt->fetch()) {
        echo "❌ Skipping: Email '{$user['email']}' already exists in user table.<br>";
        continue;
    }

    // Insert into user table
    $stmt = $conn->prepare("
        INSERT INTO user (user_first_name, user_middle_name, user_last_name, user_email, user_password, role_id, user_type, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $success = $stmt->execute([
        $user['first_name'],
        $user['middle_name'],
        $user['last_name'],
        $user['email'],
        $hashedPassword,
        $user['role_id'],
        $user['user_type'],
        $user['status'],
        date('Y-m-d H:i:s')
    ]);

    if ($success) {
        echo "✅ User {$user['email']} inserted successfully.<br>";
        $user_id = $conn->lastInsertId();

        // Insert into actor table
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id, created_at) VALUES (?, ?, ?)");
        $stmt->execute(['user', $user_id, date('Y-m-d H:i:s')]);
        echo "✅ Actor record for {$user['email']} inserted successfully.<br>";

        // If employer, insert employer record
        if ($user['user_type'] === 'Employer') {
            $stmt = $conn->prepare("
                INSERT INTO employer (user_id, company_name, job_title, status, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $success = $stmt->execute([
                $user_id,
                'Example Corp',
                'HR Manager',
                'Verification',
                date('Y-m-d H:i:s')
            ]);
            if ($success) {
                echo "✅ Employer record for {$user['email']} inserted successfully.<br>";
            } else {
                echo "❌ Error inserting employer record for {$user['email']}.<br>";
            }
        }
    } else {
        echo "❌ Error inserting user {$user['email']}.<br>";
    }
}

// Insert applicants into student table
foreach ($applicants as $applicant) {
    // Check if email exists
    $stmt = $conn->prepare("SELECT stud_id FROM student WHERE stud_email = ?");
    $stmt->execute([$applicant['email']]);
    if ($stmt->fetch()) {
        echo "❌ Skipping: Email '{$applicant['email']}' already exists in student table.<br>";
        continue;
    }

    // Insert into student table
    $stmt = $conn->prepare("
        INSERT INTO student (stud_first_name, stud_middle_name, stud_last_name, stud_email, stud_password, stud_gender, stud_date_of_birth, 
                            graduation_yr, course_id, bio, institution, is_student, edu_background, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $success = $stmt->execute([
        $applicant['first_name'],
        $applicant['middle_name'],
        $applicant['last_name'],
        $applicant['email'],
        $hashedPassword,
        $applicant['gender'],
        $applicant['date_of_birth'],
        $applicant['graduation_yr'],
        $applicant['course_id'],
        $applicant['bio'],
        $applicant['institution'],
        $applicant['is_student'],
        $applicant['edu_background'],
        $applicant['status'],
        date('Y-m-d H:i:s')
    ]);

    if ($success) {
        echo "✅ Applicant {$applicant['email']} inserted successfully.<br>";
        $stud_id = $conn->lastInsertId();

        // Insert into actor table
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id, created_at) VALUES (?, ?, ?)");
        $stmt->execute(['student', $stud_id, date('Y-m-d H:i:s')]);
        echo "✅ Actor record for {$applicant['email']} inserted successfully.<br>";
    } else {
        echo "❌ Error inserting applicant {$applicant['email']}.<br>";
    }
}
?>