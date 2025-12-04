<?php
/**
 * Database connection file
 * 
 * @global PDO $conn
 */

$host = 'sites.local';
$dbname = 'career_platform';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Type guard for static analysis - ensures $conn is PDO
if (!($conn instanceof PDO)) {
    die("Database connection not available");
}


/*
$host = 'sql104.byethost9.com';
$dbname = 'b9_39771656_career_platform';
$username = 'b9_39771656';
$password = 'FreeDomainYey123';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
    
*/
?>