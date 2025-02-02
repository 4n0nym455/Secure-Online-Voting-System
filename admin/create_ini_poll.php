<?php
date_default_timezone_set('Africa/Nairobi');

session_start();
require_once '../includes/connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    # Validate CSRF token
 if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token.');
}

    // Save the general poll info in session
    $_SESSION['poll_info'] = [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'presidential_candidates' => $_POST['presidential_candidates']
    ];

    // Redirect to the first school-specific form (school_id=1)
    header('Location: create_poll_schools.php?school_id=1');
    exit;


}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Poll</title>
        <link rel="stylesheet" href="../styles/styles.css">
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
                    <h1 >Must Secure Voting System</h1>

                    <div class="date-time">
                      <span id="current-date"></span> ⏰ <span id="current-time"></span>
                    </div>
                    
                </nav>
            </header>

            <!-- Side Panel -->
            <div id="side-panel">
                <button class="close-btn" onclick="toggleMenu()">X</button>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_polls.php">Manage Polls</a></li>
                    <li><a href="../report.php">Report</a></li>
                    <li><a href="#">Create Poll</a></li>
                    <li><a href="../logout.php">Log Out</a></li>
                </ul>
            </div>
            
            <div id="overlay" onclick="toggleMenu()"></div>

        <div class = "container">
            <div class="logo-container">
                <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
            </div>
            <h2>Create Poll</h2>
            <form method="POST" action="create_ini_poll.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <!-- Poll Title -->
                <label for="title">Poll Title:</label>
                <input type="text" name="title" id="title" placeholder="Enter Poll Title" required>
                
                <!-- Poll Description -->
                <label for="description">Poll Description:</label>
                <textarea name="description" id="description" placeholder="Enter Poll Description" required></textarea>
                
                <!-- Poll Start Date -->
                <label for="start_date">Start Date:</label>
                <input type="datetime-local" name="start_date" id="start_date" required>
                
                <!-- Poll End Date -->
                <label for="end_date">End Date:</label>
                <input type="datetime-local" name="end_date" id="end_date" required>
                
                <!-- Presidential Candidates -->
                <label for="presidential_candidates_1">Presidential Candidates:</label>
                <input type="text" name="presidential_candidates[]" id="presidential_candidates_1" placeholder="Enter Presidential Candidate(1)" required>
                <input type="text" name="presidential_candidates[]" id="presidential_candidates_2" placeholder="Enter Presidential Candidate(2)" required>
                <input type="text" name="presidential_candidates[]" id="presidential_candidates_3" placeholder="Enter Presidential Candidate(3)">
                <input type="text" name="presidential_candidates[]" id="presidential_candidates_4" placeholder="Enter Presidential Candidate(4)">
                
                <button type="submit">Next</button>
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