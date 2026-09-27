<?php
// Database connection for XAMPP (default: localhost, root, no password)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'eavs_homestay';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error . '<br>Please import <b>database.sql</b> in phpMyAdmin or run <b>install.php</b>.');
}
$conn->set_charset('utf8mb4');
