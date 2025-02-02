<?php

date_default_timezone_set('Africa/Nairobi');

session_start();
require_once '../includes/connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../voter/login.php');
    exit;
}


$userId = $_SESSION['user_id'];
$pollId = $_GET['poll_id'] ?? null;

if (!$pollId) {
    echo "No poll selected.";
    exit;
}

    $stmt = $conn->prepare("SELECT COUNT(*) FROM votes WHERE user_id = ? AND poll_id = ?");
    $stmt->bind_param("ii", $userId, $pollId);
    $stmt->execute();
    $stmt->bind_result($vote_count);
    $stmt->fetch();
    $stmt->close();

    if ($vote_count > 0) {
        echo "<h1 style=color:red;> <strong>You have already voted in this poll.</strong></h1>";
        header("Refresh:2;url= dashboard.php");
        exit;
    }

    // Fetch poll details
    $stmt = $conn->prepare("SELECT * FROM polls WHERE poll_id = ?");
    $stmt->bind_param("i", $pollId);
    $stmt->execute();
    $poll = $stmt->get_result()->fetch_assoc();

    if (!$poll) {
        echo "Poll not found.";
        exit;
    }


    // Fetch all positions (president and school-specific)
    $positions_query = "SELECT * FROM positions";
    $positions_result = $conn->query($positions_query);

    function fetch_candidates_for_position($pollId, $position_id, $school_id) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT c.candidate_id, c.candidate_name, p.position_name
            FROM candidates AS c
            JOIN positions AS p ON c.position_id = p.position_id
            WHERE c.poll_id = ?
            AND p.position_id = ?
            AND (c.school_id = ? OR p.position_name = 'president')
        ");
        $stmt->bind_param("iii", $pollId, $position_id, $school_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    $start_time = new DateTime($poll['start_date']);
    $end_time = new DateTime($poll['end_date']);
    $current_time = new DateTime();

    $can_vote = ($current_time >= $start_time && $current_time <= $end_time);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote in Poll: <?php echo htmlspecialchars($poll['title']); ?></title>
    <link rel="stylesheet" href="../styles/stylin.css">
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
            <li><a href="view_candidates.php?poll_id=<?php echo $default_poll_id; ?>">Candidates</a></li>
            <li><a href="vote.php?poll_id=<?php echo $default_poll_id; ?>">Vote</a></li>
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
            <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>
        <header>
            <h1>Vote in Poll: <?php echo htmlspecialchars($poll['title']); ?></h1>
            <p class="instructions">Please select a candidate for each position below:</p>
        </header>

        <form action="submit_votes.php" method="post">
            
            <input type="hidden" name="poll_id" value="<?php echo $pollId; ?>">
            
            <?php while ($position = $positions_result->fetch_assoc()) : ?>
                <?php
                $candidates_result = fetch_candidates_for_position($pollId, $position['position_id'], $_SESSION['school_id']);
                ?>
                
                <section class="position-section">
                    <h3><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $position['position_name']))); ?></h3>
                    
                    <?php if ($candidates_result->num_rows > 0) : ?>
                        <?php while ($candidate = $candidates_result->fetch_assoc()) : ?>
                            <label class="candidate-option">
                                <input type="radio" name="candidate_id_<?php echo $position['position_id']; ?>" value="<?php echo $candidate['candidate_id']; ?>" required>
                               <span class="candidate-name"><?php echo htmlspecialchars(ucwords($candidate['candidate_name'])); ?></span>
                            </label><br>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <p class="no-candidates">No candidates available for this position.</p>
                    <?php endif; ?>
                </section>
                
            <?php endwhile; ?>
        
            <?php if ($can_vote) : ?>
                <button type="submit" class="submit-button" onclick="return confirmVote()">Submit Your Vote</button>
            <?php else : ?>
                <div class="voting-closed">
                    
                    <?php if ($current_time < $start_time) : ?>
                        <button disabled="disabled" class="decoy">Voting starts on: <?php echo $poll['start_date']; ?></button>
                    <?php elseif ($current_time > $end_time) : ?>
                        <button disabled="disabled" class="decoy"> Voting ended on: <?php echo $poll['end_date']; ?></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
        
        <div class="confirmation-text">
            <p><strong>Note:</strong> Once you confirm, your votes cannot be changed.</p>
        </div>
    </div>

    <script src = "../includes/functions.js" defer></script>

    <?php include "../includes/footer.php"?>
</body>
</html>
