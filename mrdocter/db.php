<?php
// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');      // Change this to your DB username
define('DB_PASSWORD', '');          // Change this to your DB password
define('DB_NAME', 'dbmedi');        // Change this to your actual DB name

// Connect to MySQL server (without specifying DB)
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Make $conn global for included files
$GLOBALS['conn'] = $conn;
?>