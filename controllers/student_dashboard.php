<?php
require '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the student is logged in
if (!isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

$stud_id = $_SESSION['stud_id'];

try {
    // Fetch student details and join with course title
    $stmt = $conn->prepare("
        SELECT s.stud_first_name, s.stud_middle_name, s.stud_last_name,
               s.stud_email, s.profile_picture, s.bio,
               s.institution, s.status, c.course_title
        FROM student s
        LEFT JOIN course c ON s.course_id = c.course_id
        WHERE s.stud_id = :stud_id AND s.deleted_at IS NULL
    ");
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student not found.");
    }

    // Define inactive statuses
    $inactive_statuses = ['Deleted'];

    if (in_array($student['status'], $inactive_statuses)) {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php?account_deleted=1");
        exit();
    }

    // Fetch actor ID for features like forum/messages/notifications
    $actor_stmt = $conn->prepare("
        SELECT actor_id
        FROM actor
        WHERE entity_type = 'student' AND entity_id = :stud_id AND deleted_at IS NULL
        LIMIT 1
    ");
    $actor_stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $actor_stmt->execute();
    $actor = $actor_stmt->fetch(PDO::FETCH_ASSOC);

    $actor_id = $actor['actor_id'] ?? null;
    $_SESSION['actor_id'] = $actor_id;

    // Fetch skills associated with the student
    $skills_stmt = $conn->prepare("
        SELECT sm.skill_name
        FROM stud_skill ss
        JOIN skill_masterlist sm ON ss.skill_id = sm.skill_id
        WHERE ss.stud_id = :stud_id AND ss.deleted_at IS NULL
    ");
    
    $skills_stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $skills_stmt->execute();
    $skills = $skills_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch current courses for the student
    $courses_stmt = $conn->prepare("
        SELECT c.course_title, c.course_id
        FROM course c
        WHERE c.deleted_at IS NULL
        AND c.course_id IN (SELECT course_id FROM student WHERE stud_id = :stud_id)
    ");
    $courses_stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Assign data for UI usage
    $full_name = htmlspecialchars($student['stud_first_name'] . ' ' . ($student['stud_middle_name'] ?? '') . ' ' . $student['stud_last_name']);
    // $stud_no = htmlspecialchars($student['stud_no']);
    $email = htmlspecialchars($student['stud_email']);
    $bio = htmlspecialchars($student['bio'] ?? 'No bio added yet.');
    $course_title = htmlspecialchars($student['course_title'] ?? 'No course assigned');
    $institution = htmlspecialchars($student['institution']);
    $profile_picture = !empty($student['profile_picture']) ? $student['profile_picture'] : 'default.png';

    // Display skills as an array
    $skills_list = array_map(function($skill) {
        return htmlspecialchars($skill['skill_name']);
    }, $skills);

    // Display courses as an array
    $courses_list = array_map(function($course) {
        return htmlspecialchars($course['course_title']);
    }, $courses);

    // These values can now be echoed in the HTML
} catch (Exception $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}
// After your existing code, add this section to fetch forum activity
try {
    // Fetch recent forum posts with comment counts
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
            CASE 
                WHEN fp.forum_id = 1 THEN 'question' -- Assuming forum_id 1 is for questions
                WHEN fp.forum_id = 2 THEN 'idea'     -- Assuming forum_id 2 is for ideas
                ELSE 'discussion'
            END AS post_type
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

    // Format the forum posts for display
    $recent_forum_activity = array_map(function($post) {
        return [
            'title' => strlen($post['content']) > 50 
                       ? substr($post['content'], 0, 50) . '...' 
                       : $post['content'],
            'full_content' => $post['content'],
            'posted_at' => $post['posted_at'],
            'comment_count' => $post['comment_count'],
            'author' => $post['author_name'],
            'forum_title' => $post['forum_title'],
            'type' => $post['post_type'],
            'time_ago' => getTimeAgo($post['posted_at'])
        ];
    }, $forum_posts);

} catch (Exception $e) {
    error_log("Forum Activity Error: " . $e->getMessage());
    $recent_forum_activity = [];
}

function getTimeAgo($timestamp, $referenceTime = null) {
    // Create a DateTime object in the Manila time zone
    $timezone = new DateTimeZone('Asia/Manila');
    $time = new DateTime($timestamp, $timezone);
    
    if ($referenceTime) {
        // Use provided reference time or default to 'now' in Manila time zone
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

try {
    // Define profile fields to check and their weights
    $profile_fields = [
        'stud_first_name' => 5,
        'stud_last_name' => 5,
        'stud_gender' => 5,
        'stud_date_of_birth' => 5,
        'graduation_yr' => 5,
        'course_id' => 10,
        'bio' => 10,
        'resume_file' => 15,
        'profile_picture' => 10,
        'institution' => 10,
        'skills' => 20
    ];

    // Build dynamic SELECT
    $columns = implode(', ', array_diff(array_keys($profile_fields), ['skills']));
    $details_stmt = $conn->prepare("
        SELECT $columns
        FROM student
        WHERE stud_id = :stud_id AND deleted_at IS NULL
    ");
    $details_stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $details_stmt->execute();
    $student = $details_stmt->fetch(PDO::FETCH_ASSOC);

    $total_weight = array_sum($profile_fields);
    $completed_weight = 0;
    $missing_fields = [];

    foreach ($profile_fields as $field => $weight) {
        if ($field === 'skills') {
            if (!empty($skills) && count($skills) > 0) {
                $completed_weight += $weight;
            } else {
                $missing_fields[] = 'Skills';
            }
        } elseif ($field === 'course_id') {
            // If course_id is empty or 0, add it to missing_fields and don't count its weight
            if (empty($student[$field]) || $student[$field] == 1) {
                $missing_fields[] = 'Course';
            } else {
                $completed_weight += $weight;
            }
        } elseif (!empty($student[$field])) {
            $completed_weight += $weight;
        } else {
            switch ($field) {
                case 'stud_first_name': $missing_fields[] = 'First Name'; break;
                case 'stud_last_name': $missing_fields[] = 'Last Name'; break;
                case 'stud_gender': $missing_fields[] = 'Gender'; break;
                case 'stud_date_of_birth': $missing_fields[] = 'Date of Birth'; break;
                case 'graduation_yr': $missing_fields[] = 'Graduation Year'; break;
                case 'bio': $missing_fields[] = 'Bio'; break;
                case 'resume_file': $missing_fields[] = 'Resume'; break;
                case 'profile_picture': $missing_fields[] = 'Profile Picture'; break;
                case 'institution': $missing_fields[] = 'Institution'; break;
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
    error_log("Profile Completion Error: " . $e->getMessage());
    $completion_percentage = 0;
    $progress_class = 'bg-danger';
}



// Fetch applications for the logged-in student
try {
    $applications_stmt = $conn->prepare("
        SELECT at.application_id, at.application_status, at.applied_at, jp.title AS job_title
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        WHERE at.stud_id = :stud_id AND at.deleted_at IS NULL
        ORDER BY at.applied_at DESC
    ");
    $applications_stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $applications_stmt->execute();
    $applications = $applications_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the application data for display
    $application_data = array_map(function($app) {
        return [
            'job_title' => htmlspecialchars($app['job_title']),
            'applied_at' => getTimeAgo($app['applied_at']),
            'application_status' => htmlspecialchars($app['application_status']),
            'status_class' => getStatusClass($app['application_status']),
        ];
    }, $applications);
} catch (Exception $e) {
    error_log("Application Status Error: " . $e->getMessage());
    $application_data = [];
}

// Helper function to return CSS class based on application status
function getStatusClass($status) {
    switch ($status) {
        case 'Accepted':
            return 'status-accepted';
        case 'Rejected':
            return 'status-rejected';
        case 'Pending':
        default:
            return 'status-pending';
    }
}

?>
