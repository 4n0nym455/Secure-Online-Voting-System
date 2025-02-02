<?php
require '../includes/access.php';

session_start();
require_once '../includes/connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location:login.php');
    exit;
}

// Fetch poll data
$poll_id = isset($_GET['poll_id']) ? (int) $_GET['poll_id'] : 0;
if ($poll_id === 0) {
    die("Poll ID is required.");
}

$query = $conn->prepare("SELECT * FROM polls WHERE poll_id = ?");
$query->bind_param("i", $poll_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("Poll not found.");
}

$poll = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update poll details
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $stmt = $conn->prepare("UPDATE polls SET status = ?, start_date = ?, end_date = ? WHERE poll_id = ?");
    $stmt->bind_param("sssi", $status, $start_date, $end_date, $poll_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Poll updated successfully.";
        header("Refresh:2;url= edit_poll.php?poll_id=$poll_id;");
        exit;
    } else {
        echo "Failed to update poll.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Poll</title>
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
                <img src="../images/must e-voting.png" alt="Must E-Voting Logo" class="logo"><hr><hr>
            </div>

            <h2>Edit Poll - <?php echo htmlspecialchars($poll['title']); ?></h2>
            
            <form method="POST" action="edit_poll.php?poll_id=<?php echo $poll_id; ?>">
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="open" <?php echo ($poll['status'] === 'open') ? 'selected' : ''; ?>>Active</option>
                    <option value="closed" <?php echo ($poll['status'] === 'closed') ? 'selected' : ''; ?>>Inactive</option>
                </select>

                <label for="start_date">Start Date:</label>
                <input type="datetime-local" name="start_date" id="start_date" value="<?php echo $poll['start_date']; ?>" required>

                <label for="end_date">End Date:</label>
                <input type="datetime-local" name="end_date" id="end_date" value="<?php echo $poll['end_date']; ?>" required>

                <button type="submit">Update Poll</button>
            </form>
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
