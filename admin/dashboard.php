<?php
require '../includes/access.php';


session_start();
require_once '../includes/connect.php';


if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}


// Fetch all polls
$stmt = $conn->prepare("SELECT * FROM polls");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard</title>
        <link rel="stylesheet" href="../styles/dashad.css">
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
                <li><a href="#">Dashboard</a></li>
                <li><a href="create_ini_poll.php">Create Poll</a></li>
                <li><a href="manage_polls.php">Manage Polls</a></li>
                <li><a href="../report.php">Report</a></li>
                <li><a href="../logout.php">Log Out</a></li>
                </ul>
            </div>
            
            <div id="overlay" onclick="toggleMenu()"></div>
        <div class="container">
        <div class="logo-container">
            <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>
            <header>
                <h1>Welcome to the Admin Dashboard</h1>
                <p>ADMIN ID: <?php echo htmlspecialchars($_SESSION['admin_id']); ?></p>
            </header>

            <section>
                <h2>All Polls</h2>

                <?php if ($result->num_rows > 0) : ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Poll Title</th>
                                <th>Description</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($poll = $result->fetch_assoc()) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($poll['title']); ?></td>
                                    <td><?php echo htmlspecialchars($poll['description']); ?></td>
                                    <td><?php echo htmlspecialchars($poll['start_date']); ?></td>
                                    <td><?php echo htmlspecialchars($poll['end_date']); ?></td>
                                    <td><?php echo htmlspecialchars($poll['status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>No polls found.</p>
                <?php endif; ?>
            </section>

            
        </div>
        <script src = "../includes/functions.js" defer></script>

        <footer class="footer">
            <p>Copyright &copy; 2024 MUST Secure Voting System. </p>
                <p>All rights reserved.</p>
            <nav>
                <a href="#">About</a>
            </nav>
        </footer>
    </body>
</html>
