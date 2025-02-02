<?php
date_default_timezone_set('Africa/Nairobi');

session_start();
require_once '../includes/connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}



// Fetch the logged-in user's school name
$userId = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'];
$pollId = $_GET['poll_id']?? null;

if (!$pollId) {
    echo "Invalid Poll ID";
    exit;
}


// Get the candidates for the user’s school and the presidency position
$stmt = $conn->prepare("
    SELECT c.candidate_name, p.position_name 
    FROM candidates AS c
    JOIN positions AS p ON c.position_id = p.position_id
    WHERE c.poll_id = ? AND (c.position_id = 1 OR c.school_id = ?)
");
$stmt->bind_param("ii", $pollId, $schoolId);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Candidates</title>
    <link rel="stylesheet" href="../styles/styling.css">
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
            <li><a href="profile.php">Change Password</a></li>
            <li><a href="../logout.php">Log Out</a></li>
        </ul>
    </div>
    <div id="overlay" onclick="toggleMenu()"></div>

    <div class="container">
        <div class="logo-container">
            <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>
        <h2>Candidates for Poll</h2>
        
        <?php if ($result->num_rows > 0) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Candidate Name</th>
                        <th>Position</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($candidate = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars(ucwords($candidate['candidate_name'])); ?></td>
                            <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $candidate['position_name']))); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No candidates available for your school.</p>
        <?php endif; ?>
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
