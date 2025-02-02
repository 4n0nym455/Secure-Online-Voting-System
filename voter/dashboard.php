<?php

date_default_timezone_set('Africa/Nairobi');

session_start();
require_once '../includes/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../voter/login.php');
    exit;
}

// Fetch voter details (assuming the user's school is stored in the session)
$user_id = $_SESSION['user_id'];
$userName = $_SESSION['username'];
$schoolId = $_SESSION['school_id'];
$schoolName = $_SESSION['school_name'];

// Fetch all polls
$polls_query = "SELECT * FROM polls WHERE status = 'open'";
$stmt = $conn->prepare($polls_query);
$stmt->execute();
$polls_result = $stmt->get_result();

function getTimeRemaining($date) {
    $now = new DateTime();
    $poll_date = new DateTime($date);

    if ($now < $poll_date) {
        $interval = $now->diff($poll_date);
        return [
            'status' => 'upcoming',
            'time' => $interval->format('%a days, %h hours, %i minutes')
        ];
    } elseif ($now >= $poll_date) {
        return [
            'status' => 'started',
            'time' => 'Poll has started!'
        ];
    }

    return [
        'status' => 'ended',
        'time' => 'Poll has ended!'
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
        <li><a href="#">Home</a></li>
        <li><a href="view_results.php">My Votes</a></li>
        <li><a href="../report.php">Report</a></li>
        <li><a href="profile.php">Change Password</a></li>
        <li><a href="../logout.php">Log Out</a></li>
    </ul>
</div>

<!-- Overlay -->
<div id="overlay" onclick="toggleMenu()"></div>
<div class="container">
    <div class="logo-container">
        <img src="../images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
    </div>
    <header>
        <h1 style="color:green;">Welcome <?php echo htmlspecialchars(ucwords($userName)); ?>, to your Dashboard</h1>
        <p><?php echo htmlspecialchars($schoolName); ?></p>
        <p>School ID:<?php echo htmlspecialchars($_SESSION['school_id']); ?></p>
    </header>

    <section>
        <h3>Available Polls</h3>

        <?php if ($polls_result->num_rows > 0) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Poll Title</th>
                        <th>Description</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>CountDown</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($poll = $polls_result->fetch_assoc()) : ?>
                        <?php
                        // Check if the poll is specific to the voter's school
                        $poll_is_school_specific = in_array($schoolName, ['School of Computing and Informatics', 'School of Business and Economics', 'School of Education', 'School of Agriculture and Food Science', 'School of Pure and Applied Science', 'School of Engineering and Architecture', 'School of Health Sciences', 'School of Nursing']) && ($poll['title'] != 'Presidency');
                        
                        // Show polls that are either general (presidency) or specific to the voter’s school
                        if ($poll['title'] == 'Presidency' || $poll_is_school_specific) :
                            $start_time = new DateTime($poll['start_date']);
                            $end_time = new DateTime($poll['end_date']);
                            $current_time = new DateTime();
                            $time_remaining_start = getTimeRemaining($poll['start_date']);
                            $time_remaining_end = getTimeRemaining($poll['end_date']);
                            $can_vote = ($current_time >= $start_time && $current_time <= $end_time);
                        ?>
                            <tr data-end-time="<?php echo htmlspecialchars($poll['end_date']); ?>">
                                <td><?php echo htmlspecialchars($poll['title']); ?></td>
                                <td><?php echo htmlspecialchars($poll['description']); ?></td>
                                <td><?php echo htmlspecialchars($poll['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($poll['end_date']); ?></td>
                                <td>
                                    <?php
                                    if ($current_time > $end_time) {
                                        echo "Poll has ended!";
                                    } elseif ($current_time < $start_time) {
                                        echo $time_remaining_start['time'];
                                    } else {
                                        echo "Poll has started! <br><span class='countdown'></span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($current_time <= $end_time && $current_time >= $start_time) : ?>
                                        <a href="vote.php?poll_id=<?php echo $poll['poll_id']; ?>">Vote</a><hr><hr>
                                        <a href="view_candidates.php?poll_id=<?php echo $poll['poll_id']; ?>">View Candidates</a>
                                    <?php else : ?>
                                        <span>Voting unavailable</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No active polls at the moment.</p>
        <?php endif; ?>
    </section>
</div>

<script src="../includes/functions.js" defer></script>

<footer class="footer">
    <p>Copyright &copy; 2024 MUST Secure Voting System. </p>
    <p>All rights reserved.</p>
    <nav>
        <a href="#">Contact Us</a>
    </nav>
</footer>

</body>
</html>


