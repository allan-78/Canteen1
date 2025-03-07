<?php
session_start(); // Start the session

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Admin') {
    // If not logged in or not an admin, redirect to the login page
    header("Location: ../auth/login.php");
    exit(); // Prevent further execution of the page
}

// If the user is logged in and is an admin, continue displaying the page
?>
