<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config/dbcon.php"; 

// Check if the user is logged in
if (!isset($_SESSION['stud_id'])) {
    // Redirect unauthorized users to the login page
    header("Location: ../index.php");
    exit();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<a href="../auth/logout.php">Logout</a>
</body>
</html>