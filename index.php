<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Must Secure Online Voting System</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="../styles/style.css">
</head>

<body>

<div class="container">
    <h1 style="color:green;">Must Secure Online Voting System </h1>

    <p style="text-align:center;">Please Login or Register for an account:</p>
    
    <div class="buttons">
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/voter/dashboard.php">Go to Dashboard</a>
            <a href="logout.php">Logout</a>

        <?php else: ?>
            <a href="/voter/login.php">Login</a>
            <a href="/voter/register.php">Register</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
