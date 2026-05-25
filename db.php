<?php
$host = "localhost";
$user = "root"; 
$pass = "";     // Default XAMPP password is empty
$dbname = "lspu_portal";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
?>