<?php
session_start(); // Start the session

// Check if the user was logged in as an admin or voter
if (isset($_SESSION['admin_id'])) {
    // Redirect to the admin login page
    header("Refresh:3;url= ../admin/login.php");
} else {
    // Redirect to the voter login page
    header("Refresh:3;url= ../voter/login.php");
}
// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();


exit;
?>
