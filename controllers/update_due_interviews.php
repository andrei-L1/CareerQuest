<?php
require '../config/dbcon.php'; // adjust as needed
/** @var PDO $conn */
// Set timezone to Philippine Time (UTC+8)
date_default_timezone_set('Asia/Manila');
$now = date('Y-m-d H:i:s');

$sql = "
    UPDATE application_tracking at
    JOIN interviews i ON at.application_id = i.application_id
    SET at.application_status = 'Interview', i.status = 'Completed'
    WHERE i.interview_date <= ?
      AND at.application_status = 'Interview Scheduled'
      AND i.status = 'Scheduled'
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$now]);

    $updated = $stmt->rowCount();
    if ($updated > 0) {
        error_log("[Interview Status Update] $updated updated at $now");
        echo "<script>console.log('✅ Interview status auto-updated for $updated application(s) at $now');</script>";
    } else {
        echo "<script>console.log('ℹ️ No interviews due yet as of $now');</script>";
    }
} catch (PDOException $e) {
    error_log("[Interview Status Update Error] " . $e->getMessage());
    echo "<script>console.error('❌ Interview status update failed: " . addslashes($e->getMessage()) . "');</script>";
}
?>
