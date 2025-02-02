<?php
require '../includes/access.php';

date_default_timezone_set('Africa/Nairobi');

session_start();
require_once '../includes/connect.php';


if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// Get the poll ID from the URL parameter
$poll_id = isset($_GET['poll_id']) ? (int) $_GET['poll_id'] : 0;
if ($poll_id === 0) {
    die("Poll ID is required.");
}

// Fetch candidates for the specific poll
$query = $conn->prepare("SELECT c.candidate_id, c.candidate_name, p.position_name, s.school_name
                         FROM candidates c
                         LEFT JOIN positions p ON c.position_id = p.position_id
                         LEFT JOIN schools s ON c.school_id = s.school_id
                         WHERE c.poll_id = ?");
$query->bind_param("i", $poll_id);
$query->execute();
$result = $query->get_result();

// Fetch poll title
$pollQuery = $conn->prepare("SELECT title FROM polls WHERE poll_id = ?");
$pollQuery->bind_param("i", $poll_id);
$pollQuery->execute();
$pollResult = $pollQuery->get_result();
$poll = $pollResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>View Candidates - <?php echo htmlspecialchars($poll['title']); ?></title>
        <link rel="stylesheet" href="../styles/manage.css">
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
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_polls.php">Manage Polls</a></li>
                    <li><a href="create_ini_poll.php">Create Poll</a></li>
                    <li><a href="../report.php">Report</a></li>
                    <li><a href="../logout.php">Log Out</a></li>
                </ul>
            </div>
            
            <div id="overlay" onclick="toggleMenu()"></div>
        <div class = "container">
        <div class="logo-container">
            <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>
            <h2>View Candidates for Poll: <?php echo htmlspecialchars($poll['title']); ?></h2>

            <table>
                <thead>
                    <tr>
                        <th>Candidate Name</th>
                        <th>Position</th>
                        <th>School</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($candidate = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars(ucwords($candidate['candidate_name'])); ?></td>
                            <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $candidate['position_name']))); ?></td>
                            <td><?php echo htmlspecialchars($candidate['school_name'] ?: 'N/A'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <br>
            <a href="manage_polls.php">Back to Polls</a>
        </div>
            <script src = "../includes/functions.js" defer></script>


    </body>

</html>
