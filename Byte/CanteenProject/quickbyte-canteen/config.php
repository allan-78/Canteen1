<?php
$host = "localhost";
$user = "root";     // your database username
$pass = "";         // your database password
$db   = "canteen_database";  // your database name

// Create connection
$con = new mysqli($host, $user, $pass, $db);

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

  // Function to sanitize user inputs (using prepared statements)

?>