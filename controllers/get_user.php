<?php
require '../config/dbcon.php';
/** @var PDO $conn */
require 'admin_user_management.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    $userData = fetchUserById($user_id);

    if ($userData) {
        echo json_encode($userData);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} else {
    echo json_encode(['error' => 'User ID not provided']);
}
?>
