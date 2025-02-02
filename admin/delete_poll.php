<?php
require '../includes/access.php';

session_start();
require_once '../includes/connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$poll_id = isset($_GET['poll_id']) ? (int) $_GET['poll_id'] : 0;

if ($poll_id === 0) {
    die("Poll ID is required.");
}

// Start a transaction to delete poll and associated candidates
$conn->begin_transaction();

try {
   

    // Delete the poll
    $stmtPoll = $conn->prepare("DELETE FROM polls WHERE poll_id = ?");
    $stmtPoll->bind_param("i", $poll_id);
    $stmtPoll->execute();

    if ($stmtPoll->affected_rows > 0) {
        // Commit the transaction
        $conn->commit();
        echo "Poll and associated candidates deleted successfully.";
        header("Refresh:3;url= manage_polls.php");
        exit;

    } else {
        throw new Exception("Failed to delete poll.");
        header("Refresh:3;url= manage_polls.php");
        exit;
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
?>
