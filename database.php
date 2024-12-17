<?php
$hostname = "auth-db1630.hstgr.io";
$username = "u779691448_EggWatch";
$password = "Namias99";
$database = "u779691448_EggWatchDB"; 

$mysqli = new mysqli($hostname, $username, $password, $database);

if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Check if the 'is_verified' column exists
$result = $mysqli->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
if ($result->num_rows == 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE");
}

// Check if the 'verification_code' column exists
$result = $mysqli->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
if ($result->num_rows == 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN verification_code VARCHAR(32)");
}

return $mysqli;
?>