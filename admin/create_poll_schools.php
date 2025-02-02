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



# Hii ni kufetch initial school yenye ikona school_id ya 1 
$school_id = isset($_GET['school_id']) ? (int) $_GET['school_id'] : 1;

// Check if school_id matches the session state
if (!isset($_SESSION['school_id'])) {
    header('Location: dashboard.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    # Validate CSRF token
     if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token.');
    }
    

    # kuprepare school-specific data for db insertion
    $_SESSION['school_' . $school_id] = [
        'school_rep' => array_filter($_POST['school_rep'], 'trim'),  // Join non-empty entries
        'mens_rep' => array_filter($_POST['mens_rep'], 'trim'),
        'womens_rep' => array_filter($_POST['womens_rep'], 'trim')
    ];

    if ($school_id == 8) { 
        header('Location: submit_poll.php');

    } else {
        $nextPage = $school_id + 1;
        $_SESSION['school_id'] = $nextPage;
        header("Location: create_poll_schools.php?school_id={$nextPage}");
    }
    exit;
}

$query = $conn->prepare("SELECT school_name FROM schools WHERE school_id = ?");
$query->bind_param("i", $school_id);
$query->execute();
$school_result = $query->get_result();

if ($school_result->num_rows > 0) {
    $school = $school_result->fetch_assoc();
} else {
    die("ERROR 404: PAGE NOT FOUND.");
}


?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Poll - <?= htmlspecialchars($school['school_name']) ?></title>
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
                    <li><a href="create_ini_poll.php">Start Over</a></li>
                    <li><a href="manage_polls.php">Manage Polls</a></li>
                    <li><a href="../report.php">Report</a></li>
                    <li><a href="../logout.php">Log Out</a></li>
                </ul>
            </div>
            
            <div id="overlay" onclick="toggleMenu()"></div>
        
        <div class = "container">
        <div class="logo-container">
                <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo"><hr>
            </div>
            <h2><?= htmlspecialchars($school['school_name']) ?></h2><hr>
            <form method="POST"  action="create_poll_schools.php?school_id=<?= urlencode($school_id) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <!-- School Representative Candidates -->
                <label for="school_rep_1">School Representatives:</label>
                <input type="text" name="school_rep[]" id="school_rep_1" placeholder="Enter School Rep Candidate(1)" required>
                <input type="text" name="school_rep[]" id="school_rep_2" placeholder="Enter School Rep Candidate(2)" required>
                <input type="text" name="school_rep[]" id="school_rep_3" placeholder="Enter School Rep Candidate(3)">
                <input type="text" name="school_rep[]" id="school_rep_4" placeholder="Enter School Rep Candidate(4)">

                <!-- Men's Representative Candidates -->
                <label for="mens_rep_1">Men's Representatives:</label>
                <input type="text" name="mens_rep[]" id="mens_rep_1" placeholder="Enter Men's Rep Candidate(1)" required>
                <input type="text" name="mens_rep[]" id="mens_rep_2" placeholder="Enter Men's Rep Candidate(2)" required>
                <input type="text" name="mens_rep[]" id="mens_rep_3" placeholder="Enter Men's Rep Candidate(3)">
                <input type="text" name="mens_rep[]" id="mens_rep_4" placeholder="Enter Men's Rep Candidate(4)">

                <!-- Women's Representative Candidates -->
                <label for="womens_rep_1">Women's Representatives:</label>
                <input type="text" name="womens_rep[]" id="womens_rep_1" placeholder="Enter Women's Rep Candidate(1)" required>
                <input type="text" name="womens_rep[]" id="womens_rep_2" placeholder="Enter Women's Rep Candidate(2)" required>
                <input type="text" name="womens_rep[]" id="womens_rep_3" placeholder="Enter Women's Rep Candidate(3)">
                <input type="text" name="womens_rep[]" id="womens_rep_4" placeholder="Enter Women's Rep Candidate(4)">

                <button type="submit"><?=$school_id == 8 ?'Finish': 'Next' ?></button>
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
