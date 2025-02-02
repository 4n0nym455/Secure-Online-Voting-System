<?php
require '../includes/access.php';

session_start();
require_once '../includes/connect.php';


if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// Fetch all polls
$query = "SELECT * FROM polls";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Polls</title>
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
                    <li><a href="#">Manage Polls</a></li>
                    <li><a href="create_ini_poll.php">Create Poll</a></li>
                    <li><a href="../report.php">Report</a></li>
                    <li><a href="../logout.php">Log Out</a></li>
                </ul>
            </div>
            
            <div id="overlay" onclick="toggleMenu()"></div>
    <div class="container">
<div class="logo-container">
            <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>
    <h2>Manage Polls</h2>
    
    <table>
        <thead>
            <tr>
                <th>Poll Title</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($poll = $result->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($poll['title']); ?></td>
                    <td><?php echo htmlspecialchars($poll['status']); ?></td>
                    <td><?php echo htmlspecialchars($poll['start_date']); ?></td>
                    <td><?php echo htmlspecialchars($poll['end_date']); ?></td>
                    <td>
                        <a href="edit_poll.php?poll_id=<?php echo $poll['poll_id']; ?>">Edit</a>
                        <a href="view_candidates.php?poll_id=<?php echo $poll['poll_id']; ?>">View Candidates</a>
                        <a href="delete_poll.php?poll_id=<?php echo $poll['poll_id']; ?>" onclick="return confirm('Are you sure you want to delete this poll?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <script src = "../includes/functions.js" defer></script>


</body>
</html>
