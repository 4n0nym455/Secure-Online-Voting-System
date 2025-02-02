<?php

date_default_timezone_set('Africa/Nairobi');

session_start();

require_once "../includes/connect.php";
require_once "../includes/functions.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

$user_id = $_SESSION['user_id'];
$errors = [];


if ($_SERVER["REQUEST_METHOD"] == "POST") {

       # Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }



    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $cpassword = trim($_POST['cpassword']);


    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if  (!$user || !password_verify($current_password, $user['password'])) {
        $errors[] = "Current password is incorrect.";
    }

    if ($new_password === $current_password) {
        $errors[] = "Password Reuse not Accepted";
    }

    if ($new_password !== $cpassword) {
        $errors[] = "Passwords Do not Match.";
    }

    if (!is_strong_password($new_password)) {
        $errors[] = "Enter a strong password";
    }

    if (empty($errors)) {
        $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_new_password, $user_id);

        if ($stmt->execute()) {
            echo "<script>alert('Password updated successfully');</script>";
            header("Refresh: 4; url=../voter/login.php");
            exit;

        } else {
            echo "<script>alert('Failed to update password');</script>";
        }

        $stmt->close();

    }
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Change Password</title>
        <link rel="stylesheet" href="../styles/style.css">
        <link rel="stylesheet" href="../styles/ham.css">

    </head>
    <body>
        <header>
                <nav>
                    <div class="hamburger-menu" onclick="toggleMenu()">
                        <div class="bar"></div>
                        <div class="bar"></div>
                        <div class="bar"></div>
                    </div>
                    <div class="logo-container1">
                    <img src="../images/must e-voting.png" alt="Must E-Voting Logo" class="logo1">
                </div>
                    <h1>Must Secure Voting System</h1>

                    <div class="date-time">
                      <span id="current-date"></span> ⏰ <span id="current-time"></span>
                    </div>
                    
                </nav>
            </header>

            <!-- Side Panel -->
            <div id="side-panel">
                <button class="close-btn" onclick="toggleMenu()">X</button>
                <ul>
                    <li><a href="dashboard.php">Home</a></li>
                    <li><a href="view_results.php">My Votes</a></li>
                    <li><a href="../report.php">Report</a></li>
                    <li><a href="#">Change Password</a></li>
                    <li><a href="../logout.php">Log Out</a></li>
                </ul>
            </div>
            
            <div id="overlay" onclick="toggleMenu()"></div>

            <div class="container">
                <div class="logo-container">
                    <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
                </div>
                <h2>Change Password</h2>

                <?php if (!empty($errors)): ?>
                    <div class="errors">
                        <?php foreach ($errors as $error): ?>
                            <strong><?php echo htmlspecialchars($error); ?></strong>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="profile.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">


                    <label for="current_password">Current Password:</label>
                    <div class = "pass-wrapper">
                    <input type="password" id="current_password" name="current_password" required>
                    <button type="button" onmouseover="showPass('current_password', this, true)" onmouseout="showPass('current_password', this, false)">👁️</button>
                    </div>

                    <label for="new_password">New Password:</label>
                    <div class = "pass-wrapper">
                    <input type="password" id="new_password" name="new_password" required>
                    <button type="button" onmouseover="showPass('new_password', this, true)" onmouseout="showPass('new_password', this, false)">👁️</button>
                    </div>

                    <label for="confirm_password">Confirm New Password:</label>
                    <div class = "pass-wrapper">
                    <input type="password" id="cpassword" name="cpassword" required>
                    <button type="button" onmouseover="showPass('cpassword', this, true)" onmouseout="showPass('cpassword', this, false)">👁️</button>
                    </div>

                    <button type="submit">Change Password</button>
                </form>
            </div>
            <script src = "../includes/functions.js" defer></script>
            <footer class="footer">
            <p>Copyright &copy; 2024 MUST Secure Voting System. </p>
                <p>All rights reserved.</p>
            <nav>
                <a href="#">Contact Us</a>
            </nav>
        </footer>
    </body>
</html>