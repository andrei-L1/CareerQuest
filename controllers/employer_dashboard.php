<?php
require '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the employer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch employer details with company information - updated query for new schema
    $stmt = $conn->prepare("
        SELECT 
            u.user_first_name, u.user_middle_name, u.user_last_name,
            u.user_email, u.picture_file, u.status as user_status,
            e.company_name, e.job_title, e.employer_id, e.company_logo,
            e.company_website, e.contact_number, e.company_description
        FROM user u
        JOIN employer e ON u.user_id = e.user_id
        WHERE u.user_id = :user_id AND u.deleted_at IS NULL
    ");

    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $employer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employer) {
        throw new Exception("Employer not found.");
    }

    // Define inactive statuses (now checking user.status instead of employer.status)
    $inactive_statuses = ['Suspended', 'Banned'];

    if (in_array($employer['user_status'], $inactive_statuses)) {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php?account_inactive=1");
        exit();
    }

    // Fetch or create actor ID for features like forum/messages/notifications
    $actor_stmt = $conn->prepare("
        SELECT actor_id
        FROM actor
        WHERE entity_type = 'user' AND entity_id = :user_id AND deleted_at IS NULL
        LIMIT 1
    ");
    $actor_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $actor_stmt->execute();
    $actor = $actor_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$actor) {
        // Create actor record if it doesn't exist
        $create_actor_stmt = $conn->prepare("
            INSERT INTO actor (entity_type, entity_id, created_at)
            VALUES ('user', :user_id, NOW())
        ");
        $create_actor_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $create_actor_stmt->execute();
        $actor_id = $conn->lastInsertId();
    } else {
        $actor_id = $actor['actor_id'];
    }

    $_SESSION['actor_id'] = $actor_id;
    $_SESSION['employer_id'] = $employer['employer_id'];

    // Assign data for UI usage
    $full_name = htmlspecialchars(trim($employer['user_first_name'] . ' ' . 
                  ($employer['user_middle_name'] ? $employer['user_middle_name'] . ' ' : '') . 
                  $employer['user_last_name']));
    $email = htmlspecialchars($employer['user_email']);
    $company_name = htmlspecialchars($employer['company_name'] ?? 'No company specified');
    $job_title = htmlspecialchars($employer['job_title'] ?? 'No job title specified');
    $company_website = htmlspecialchars($employer['company_website'] ?? '');
    $contact_number = htmlspecialchars($employer['contact_number'] ?? '');
    $company_description = htmlspecialchars($employer['company_description'] ?? '');
    
    // Use company logo if available, otherwise fall back to user profile picture
    $profile_picture = !empty($employer['picture_file']) ? htmlspecialchars($employer['picture_file']) 
                     : (!empty($employer['picture_file']) ? htmlspecialchars($employer['picture_file']) 
                     : 'default.png');

} catch (Exception $e) {
    error_log("Employer Dashboard Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}

// Fetch job postings statistics (unchanged)
try {
    // Get total jobs posted
    $jobs_stmt = $conn->prepare("
        SELECT 
            COUNT(*) AS total_jobs,
            SUM(CASE WHEN (expires_at IS NULL OR expires_at > NOW()) 
                 AND moderation_status = 'Approved' THEN 1 ELSE 0 END) AS active_jobs
        FROM job_posting
        WHERE employer_id = :employer_id
        AND deleted_at IS NULL
    ");
    $jobs_stmt->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
    $jobs_stmt->execute();
    $job_stats = $jobs_stmt->fetch(PDO::FETCH_ASSOC);

    $total_jobs = $job_stats['total_jobs'] ?? 0;
    $active_jobs = $job_stats['active_jobs'] ?? 0;

    // Get total applicants across all jobs
    $applicants_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT at.stud_id) AS total_applicants
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        WHERE jp.employer_id = :employer_id
        AND at.deleted_at IS NULL
        AND at.application_status != 'Withdrawn'
    ");
    $applicants_stmt->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
    $applicants_stmt->execute();
    $applicants = $applicants_stmt->fetch(PDO::FETCH_ASSOC);

    $total_applicants = $applicants['total_applicants'] ?? 0;

    // Get recent job postings with applicant counts
    $recent_jobs_stmt = $conn->prepare("
        SELECT 
            jp.job_id,
            jp.title,
            jp.posted_at,
            jp.expires_at,
            jp.moderation_status,
            COUNT(DISTINCT at.application_id) AS applicant_count,
            CASE 
                WHEN jp.expires_at IS NOT NULL AND jp.expires_at < NOW() THEN 'Expired'
                WHEN jp.moderation_status = 'Pending' THEN 'Pending Approval'
                WHEN jp.moderation_status = 'Rejected' THEN 'Rejected'
                ELSE 'Active'
            END AS status,
            CASE 
                WHEN jp.expires_at IS NOT NULL AND jp.expires_at < NOW() THEN 'status-rejected'
                WHEN jp.moderation_status = 'Pending' THEN 'status-pending'
                WHEN jp.moderation_status = 'Rejected' THEN 'status-rejected'
                ELSE 'status-accepted'
            END AS status_class
        FROM job_posting jp
        LEFT JOIN application_tracking at ON jp.job_id = at.job_id AND at.deleted_at IS NULL
        WHERE jp.employer_id = :employer_id
        AND jp.deleted_at IS NULL
        GROUP BY jp.job_id
        ORDER BY jp.posted_at DESC
        LIMIT 3
    ");
    $recent_jobs_stmt->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
    $recent_jobs_stmt->execute();
    $recent_jobs = $recent_jobs_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Job Postings Error: " . $e->getMessage());
    $total_jobs = 0;
    $active_jobs = 0;
    $total_applicants = 0;
    $recent_jobs = [];
}

// Fetch recent applicants (unchanged)
try {
    $recent_applicants_stmt = $conn->prepare("
        SELECT 
            at.application_id,
            at.application_status,
            at.applied_at,
            jp.title AS job_title,
            CONCAT(s.stud_first_name, ' ', s.stud_last_name) AS full_name,
            s.profile_picture,
            CASE 
                WHEN at.application_status = 'Accepted' THEN 'status-accepted'
                WHEN at.application_status = 'Rejected' THEN 'status-rejected'
                WHEN at.application_status = 'Withdrawn' THEN 'status-rejected'
                ELSE 'status-pending'
            END AS status_class
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        JOIN student s ON at.stud_id = s.stud_id
        WHERE jp.employer_id = :employer_id
        AND at.deleted_at IS NULL
        AND at.application_status != 'Withdrawn'
        ORDER BY at.applied_at DESC
        LIMIT 5
    ");
    $recent_applicants_stmt->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
    $recent_applicants_stmt->execute();
    $recent_applicants = $recent_applicants_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Recent Applicants Error: " . $e->getMessage());
    $recent_applicants = [];
}

// Fetch forum activity (unchanged)
try {
    $forum_stmt = $conn->prepare("
        SELECT 
            fp.post_id,
            fp.content,
            fp.posted_at,
            (SELECT COUNT(*) FROM forum_comment fc WHERE fc.post_id = fp.post_id AND fc.deleted_at IS NULL) AS comment_count,
            f.title AS forum_title,
            CASE 
                WHEN s.stud_id IS NOT NULL THEN CONCAT(s.stud_first_name, ' ', s.stud_last_name)
                WHEN u.user_id IS NOT NULL THEN CONCAT(u.user_first_name, ' ', u.user_last_name)
                ELSE 'Anonymous'
            END AS author_name,
            f.forum_id
        FROM forum_post fp
        JOIN forum f ON fp.forum_id = f.forum_id AND f.deleted_at IS NULL
        LEFT JOIN actor a ON fp.poster_id = a.actor_id AND a.deleted_at IS NULL
        LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id AND s.deleted_at IS NULL
        LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id AND u.deleted_at IS NULL
        WHERE fp.deleted_at IS NULL
        ORDER BY fp.posted_at DESC
        LIMIT 2
    ");
    $forum_stmt->execute();
    $forum_posts = $forum_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get forum types for categorization
    $forum_types_stmt = $conn->prepare("SELECT forum_id, title FROM forum WHERE deleted_at IS NULL");
    $forum_types_stmt->execute();
    $forum_types = $forum_types_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $recent_forum_activity = array_map(function($post) use ($forum_types) {
        return [
            'title' => strlen($post['content']) > 50 
                       ? substr($post['content'], 0, 50) . '...' 
                       : $post['content'],
            'full_content' => $post['content'],
            'posted_at' => $post['posted_at'],
            'comment_count' => $post['comment_count'],
            'author' => $post['author_name'],
            'forum_title' => $post['forum_title'],
            'type' => $forum_types[$post['forum_id']] ?? 'discussion',
            'time_ago' => getTimeAgo($post['posted_at'])
        ];
    }, $forum_posts);

} catch (Exception $e) {
    error_log("Forum Activity Error: " . $e->getMessage());
    $recent_forum_activity = [];
}

// Profile completion calculation - updated for new fields
try {
    // Define profile fields to check and their weights
    $profile_fields = [
        'user_first_name' => 10,
        'user_last_name' => 10,
        'company_name' => 20,
        'job_title' => 15,
        'company_logo' => 15,
        'company_website' => 10,
        'contact_number' => 10,
        'company_description' => 10
    ];

    // Get employer profile details from both user and employer tables
    $profile_stmt = $conn->prepare("
        SELECT 
            u.user_first_name, 
            u.user_last_name, 
            u.picture_file, 
            e.company_name, 
            e.job_title,
            e.company_logo,
            e.company_website,
            e.contact_number,
            e.company_description
        FROM user u
        LEFT JOIN employer e ON u.user_id = e.user_id
        WHERE u.user_id = :user_id
    ");
    $profile_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $profile_stmt->execute();
    $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);

    $total_weight = array_sum($profile_fields);
    $completed_weight = 0;
    $missing_fields = [];

    foreach ($profile_fields as $field => $weight) {
        if ($field === 'company_logo' || $field === 'picture_file') {
            if (!empty($profile[$field])) {
                $completed_weight += $weight;
            } else {
                $missing_fields[] = ($field === 'company_logo') ? 'Company Logo' : 'Profile Picture';
            }
        } elseif (!empty($profile[$field])) {
            $completed_weight += $weight;
        } else {
            switch ($field) {
                case 'user_first_name': $missing_fields[] = 'First Name'; break;
                case 'user_last_name': $missing_fields[] = 'Last Name'; break;
                case 'company_name': $missing_fields[] = 'Company Name'; break;
                case 'job_title': $missing_fields[] = 'Job Title'; break;
                case 'company_website': $missing_fields[] = 'Company Website'; break;
                case 'contact_number': $missing_fields[] = 'Contact Number'; break;
                case 'company_description': $missing_fields[] = 'Company Description'; break;
            }
        }
    }

    $completion_percentage = min(100, max(0, round(($completed_weight / $total_weight) * 100)));

    $progress_class = match (true) {
        $completion_percentage < 30 => 'bg-danger',
        $completion_percentage < 70 => 'bg-warning',
        default => 'bg-success'
    };

} catch (Exception $e) {
    error_log("Employer Profile Completion Error: " . $e->getMessage());
    $completion_percentage = 0;
    $progress_class = 'bg-danger';
    $missing_fields = ['All profile fields'];
}

function getTimeAgo($timestamp, $referenceTime = null) {
    $timezone = new DateTimeZone('Asia/Manila');
    $time = new DateTime($timestamp, $timezone);
    
    if ($referenceTime) {
        $now = new DateTime($referenceTime, $timezone);
    } else {
        $now = new DateTime('now', $timezone);
    }
    
    $diff = $now->diff($time);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return $diff->s . ' second' . ($diff->s > 1 ? 's' : '') . ' ago';
}