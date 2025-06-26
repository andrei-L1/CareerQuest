<?php
session_start();
header('Content-Type: application/json');

// Update the session flag
$_SESSION['profile_modal_shown'] = true;
$_SESSION['show_profile_modal'] = false;

// Return success response
echo json_encode(['success' => true]); 