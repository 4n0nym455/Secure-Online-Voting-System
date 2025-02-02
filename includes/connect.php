<?php



// Database connection parameters
$host = 'localhost'; 
$username = 'root'; 
$password = '';
$dbase ='must_voting_system'; 

// Create a new MySQLi connection
$conn = new mysqli($host, $username, $password, $dbase);

// Check if the connection was successful
if ($conn->connect_error) {
    // Output the error message
    die("Database connection failed: " . $conn->connect_error);
}

?>