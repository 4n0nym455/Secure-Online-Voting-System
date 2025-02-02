<?php
date_default_timezone_set('Africa/Nairobi');

session_start();
require_once '../includes/connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../voter/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$poll_id = $_POST['poll_id'] ?? null;

if (!$poll_id) {
    echo "No poll selected.";
    exit;
}

// Check if the poll exists and retrieve the start and end dates
$stmt = $conn->prepare("SELECT start_date, end_date FROM polls WHERE poll_id = ?");
$stmt->bind_param("i", $poll_id);
$stmt->execute();
$poll_result = $stmt->get_result();

if ($poll_result->num_rows === 0) {
    echo "Poll not found.";
    exit;
}


// Check if the user has already voted in this poll
$stmt = $conn->prepare("SELECT * FROM votes WHERE user_id = ? AND poll_id = ?");
$stmt->bind_param("ii", $user_id, $poll_id);
$stmt->execute();
$vote_check = $stmt->get_result();

if ($vote_check->num_rows > 0) {
    echo "You have already voted in this poll.";
    exit;
}

// Loop through each position and check if a candidate was selected
$positions_query = "SELECT * FROM positions";
$positions_result = $conn->query($positions_query);

while ($position = $positions_result->fetch_assoc()) {
    $position_id = $position['position_id'];
    $candidate_id = $_POST["candidate_id_$position_id"] ?? null;

    if ($candidate_id) {
        // Insert the vote into the database
        $stmt = $conn->prepare("INSERT INTO votes (user_id, poll_id, candidate_id, position_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $user_id, $poll_id, $candidate_id, $position_id);
        $stmt->execute();
    }
}

echo "Voted Successfully<script>window.location.href = '/voter/view_results.php';</script>";  // Redirect to the dashboard after voting
exit;
?>
