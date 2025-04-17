<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config/dbcon.php"; 
require "../auth/auth_check.php"; 
include '../includes/employer_navbar.php';
require_once '../auth/employer_auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // === 1. FETCH EMPLOYER, USER, ROLE DATA ===
    $stmt = $conn->prepare("
        SELECT 
            u.user_id,
            u.user_first_name, 
            u.user_middle_name,
            u.user_last_name, 
            u.user_email,
            u.user_type,
            u.status AS user_status,
            u.picture_file,
            r.role_title, 
            e.employer_id,
            e.company_name, 
            e.job_title, 
            e.status AS employer_status
        FROM user u
        JOIN role r ON u.role_id = r.role_id
        JOIN employer e ON u.user_id = e.user_id
        WHERE u.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $employer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employer) {
        throw new Exception("Employer not found.");
    }

    // Sanitize user display data
    $full_name = htmlspecialchars($employer['user_first_name'] . ' ' . $employer['user_middle_name'] . ' ' . $employer['user_last_name']);
    $email = htmlspecialchars($employer['user_email']);
    $user_type = htmlspecialchars($employer['user_type']);
    $user_status = htmlspecialchars($employer['user_status']);
    $role_title = htmlspecialchars($employer['role_title']);
    $company_name = htmlspecialchars($employer['company_name']);
    $job_title = htmlspecialchars($employer['job_title']);
    $employer_status = htmlspecialchars($employer['employer_status']);
    $profile_picture = htmlspecialchars($employer['picture_file']);
    $employer_id = $employer['employer_id'];

    // === 2. FETCH JOB POSTINGS LINKED TO EMPLOYER ===
    $stmt = $conn->prepare("
        SELECT 
            j.job_id,
            j.title,
            j.description,
            j.location,
            j.salary,
            j.img_url,
            j.posted_at,
            j.expires_at,
            j.moderation_status,
            j.flagged,
            jt.job_type_title,
            (
                SELECT COUNT(*) FROM application_tracking a WHERE a.job_id = j.job_id
            ) AS total_applications
        FROM job_posting j
        LEFT JOIN job_type jt ON j.job_type_id = jt.job_type_id
        WHERE j.employer_id = :employer_id AND j.deleted_at IS NULL
        ORDER BY j.posted_at DESC
    ");
    $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $stmt->execute();
    $job_postings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Employer Dashboard Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Employer Dashboard</title>
    <style>
     
        img.profile-pic { max-width: 120px; border-radius: 10px; }
        .job-box { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 10px; background: #f9f9f9; }
        .job-box img { max-width: 100%; height: auto; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>Welcome, <?php echo $full_name; ?>!</h1>

    <h3>Your Profile</h3>
    <p><strong>Role:</strong> <?php echo $role_title; ?></p>
    <p><strong>Email:</strong> <?php echo $email; ?></p>
    <p><strong>User Type:</strong> <?php echo $user_type; ?></p>
    <p><strong>Status:</strong> <?php echo $user_status; ?></p>

    <?php if (!empty($profile_picture)): ?>
        <p><img src="../uploads/<?php echo $profile_picture; ?>" class="profile-pic" alt="Profile Picture"></p>
    <?php endif; ?>

    <h3>Company Info</h3>
    <p><strong>Company Name:</strong> <?php echo $company_name; ?></p>
    <p><strong>Job Title:</strong> <?php echo $job_title; ?></p>
    <p><strong>Employer Status:</strong> <?php echo $employer_status; ?></p>

    <hr>

    <h2>Your Job Postings</h2>
    <?php if (!empty($job_postings)): ?>
        <?php foreach ($job_postings as $job): ?>
            <div class="job-box">
                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                <p><strong>Job Type:</strong> <?php echo htmlspecialchars($job['job_type_title'] ?? 'N/A'); ?></p>
                <p><strong>Salary:</strong> ₱<?php echo number_format($job['salary'], 2); ?></p>
                <p><strong>Posted:</strong> <?php echo htmlspecialchars($job['posted_at']); ?></p>
                <p><strong>Expires:</strong> <?php echo $job['expires_at'] ?? 'Not set'; ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($job['moderation_status']); ?> <?php if ($job['flagged']) echo '(Flagged)'; ?></p>
                <p><strong>Applications:</strong> <?php echo $job['total_applications']; ?></p>

                <?php if (!empty($job['img_url'])): ?>
                    <p><img src="../uploads/<?php echo htmlspecialchars($job['img_url']); ?>" alt="Job Image"></p>
                <?php endif; ?>

                <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>You have not posted any jobs yet.</p>
    <?php endif; ?>

    <hr>
    <a href="../auth/logout.php">Logout</a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
