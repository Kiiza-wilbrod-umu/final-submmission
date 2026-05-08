<?php
$servername = "localhost";
$username = "root";
$password = "";
$port = 3305;
$database = "course_db";

$conn = new mysqli($servername, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Set charset to utf8
$conn->set_charset("utf8");
?>