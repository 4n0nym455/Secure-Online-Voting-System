<?php
require '../includes/access.php';

session_start();
require_once '../includes/connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// Check if the poll info is in session
if (!isset($_SESSION['poll_info'])) {
    die('Poll info not found.');
}

// Poll info from the session
$poll_info = $_SESSION['poll_info'];
$createdBy = $_SESSION['admin_id'];
$presidential_candidates = $poll_info['presidential_candidates'];

// Start a transaction
// Start a transaction
$conn->begin_transaction();

try {
    // Insert poll data into the database
    $stmt = $conn->prepare("INSERT INTO polls (title, description, start_date, end_date, created_at, created_by) VALUES (?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("ssssi", $poll_info['title'], $poll_info['description'], $poll_info['start_date'], $poll_info['end_date'], $createdBy);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to insert poll data.");
    }
    $pollId = $stmt->insert_id;

    // Insert the presidential candidates
    foreach ($presidential_candidates as $candidate) {
        $candidate = trim($candidate);
        if (!empty($candidate)) {
            $stmtCandidate = $conn->prepare("INSERT INTO candidates (poll_id, candidate_name, position_id) VALUES (?, ?, ?)");
            $prezPositionId = 1; // Assuming 1 is the position_id for "President"
            $stmtCandidate->bind_param("isi", $pollId, $candidate, $prezPositionId);
            $stmtCandidate->execute();
            if ($stmtCandidate->affected_rows === 0) {
                throw new Exception("Failed to insert presidential candidate: " . $candidate);
            }
        }
    }

    // Insert school-specific candidates for each school
    for ($school_id = 1; $school_id <= 8; $school_id++) {
        if (isset($_SESSION['school_' . $school_id])) {
            $school_data = $_SESSION['school_' . $school_id];
            
            // Define position IDs
            $positions = [
                'school_rep' => 2,
                'mens_rep' => 3,
                'womens_rep' => 4
            ];

            // Insert each candidate for School Rep, Men’s Rep, and Women’s Rep
            foreach ($positions as $repType => $positionId) {
                if (isset($school_data[$repType]) && is_array($school_data[$repType])) {
                    foreach ($school_data[$repType] as $candidate) {
                        $candidate = trim($candidate);
                        if (!empty($candidate)) {
                            $stmtCandidate = $conn->prepare("INSERT INTO candidates (poll_id, candidate_name, position_id, school_id) VALUES (?, ?, ?, ?)");
                            $stmtCandidate->bind_param("isii", $pollId, $candidate, $positionId, $school_id);
                            $stmtCandidate->execute();
                            if ($stmtCandidate->affected_rows === 0) {
                                throw new Exception("Failed to insert candidate: " . $candidate . " for position ID: " . $positionId . " in School ID: " . $school_id);
                            }
                        }
                    }
                }
            }
        }
    }

    // Commit the transaction
    $conn->commit();

    // Clear session data after successful submission
    unset($_SESSION['poll_info']);
    for ($school_id = 1; $school_id <= 8; $school_id++) {
        unset($_SESSION['school_' . $school_id]);
    }

} catch (Exception $e) {
    // Rollback transaction in case of an error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
    error_log("Error during poll creation: " . $e->getMessage() . " on " . date('Y-m-d H:i:s'));
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Poll Submission</title>
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
                    <h1>Must Secure Voting System</h1>
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
            <h2>Poll Submition</h2>
            <h2>Poll created successfully!</h2>
        </div>
        <script src = "../includes/functions.js" defer></script>

        <?php include "../includes/footer.php"?>
    </body>
</html>
