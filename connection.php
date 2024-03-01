<?php

// Connection 1 - $conn
$servername = 'localhost';
$username = 'root';
$password = '';
$database = 'transaction';

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    // Handle connection errors for connection 1
    die("Connection failed for conn: " . $conn->connect_error);
}

// Connection 2 - $conn2
$servername2 = 'localhost';
$username2 = 'root';
$password2 = '';
$database2 = 'moodle';

$conn2 = new mysqli($servername2, $username2, $password2, $database2);
if ($conn2->connect_error) {
    // Handle connection errors for connection 2
    die("Connection failed for conn2: " . $conn2->connect_error);
}
