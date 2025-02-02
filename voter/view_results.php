<?php
date_default_timezone_set('Africa/Nairobi');

session_start();
require '../includes/connect.php';

// Check for user session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../voter/login.php');
    exit;
}

// Retrieve current school from session
$current_school = isset($_SESSION['school_name']) ? $_SESSION['school_name'] : '';


// Get the school_id associated with the current_school
$stmt_school = $conn->prepare("SELECT school_id FROM schools WHERE school_name = ?");
$stmt_school->bind_param("s", $current_school);
$stmt_school->execute();
$result_school = $stmt_school->get_result();
$school_id = $result_school->fetch_assoc()['school_id'];

// Total Registered Voters
$stmt_registered = $conn->prepare("SELECT COUNT(*) AS total_registered FROM users");
$stmt_registered->execute();
$result_registered = $stmt_registered->get_result();
$total_registered = $result_registered->fetch_assoc()['total_registered'];

// Total Voted Voters
$stmt_voted = $conn->prepare("SELECT COUNT(DISTINCT user_id) AS total_voted FROM votes");
$stmt_voted->execute();
$result_voted = $stmt_voted->get_result();
$total_voted = $result_voted->fetch_assoc()['total_voted'];

// Total Not-voted
$total_not_voted = $total_registered - $total_voted;

// Query for Presidential Candidates
$stmt_presidential = $conn->prepare("
    SELECT c.candidate_id, c.candidate_name, c.position_id, COUNT(v.vote_id) AS vote_count
    FROM candidates AS c
    LEFT JOIN votes  AS v ON c.candidate_id = v.candidate_id
    WHERE c.position_id = 1
    GROUP BY c.candidate_id
    ORDER BY vote_count DESC
");
$stmt_presidential->execute();
$result_presidential = $stmt_presidential->get_result();

// Array to store results for each position
$position_results = [
    'president' => [],
    'school_rep' => [],
    'men_rep' => [],
    'women_rep' => []
];

// Store presidential candidates
while ($row = $result_presidential->fetch_assoc()) {
    $position_results['president'][] = $row;
}

// Query for other positions by school (School Representative, Men's, Women's Representatives)
$positions = [
    'school_rep' => 2,
    'men_rep' => 3,
    'women_rep' => 4
];

$stmt_position = $conn->prepare("
    SELECT c.candidate_id, c.candidate_name, c.position_id, COUNT(v.vote_id) AS vote_count
    FROM candidates c
    LEFT JOIN votes v ON c.candidate_id = v.candidate_id
    WHERE c.position_id = ? AND c.school_id = ?
    GROUP BY c.candidate_id
    ORDER BY vote_count DESC
");

foreach ($positions as $key => $position_id) {
    $stmt_position->bind_param("ii", $position_id, $school_id);
    $stmt_position->execute();
    $result_position = $stmt_position->get_result();

    // Store each position's candidates in the respective array
    while ($row = $result_position->fetch_assoc()) {
        $position_results[$key][] = $row;
    }
}

// Find the presidential winner
$presidential_winner = $position_results['president'][0]; // First one is the winner

// Get how each school voted for the presidential winner
$school_votes = [];
$stmt_school_votes = $conn->prepare("
    SELECT s.school_name, COUNT(v.vote_id) AS vote_count
    FROM votes v
    JOIN users u ON v.user_id = u.user_id
    JOIN schools s ON u.school_id = s.school_id
    WHERE v.candidate_id = ?
    GROUP BY s.school_name
");
$stmt_school_votes->bind_param("i", $presidential_winner['candidate_id']);
$stmt_school_votes->execute();
$result_school_votes = $stmt_school_votes->get_result();

while ($row = $result_school_votes->fetch_assoc()) {
    $school_votes[] = $row;
}


// Function to calculate percentage
function calculate_percentage($votes, $total_votes) {
    return ($total_votes > 0) ? round(($votes / $total_votes) * 100, 2) : 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vote Results</title>
    <link rel="stylesheet" href="../styles/votes.css">
    <link rel="stylesheet" href="../styles/ham.css">

    <script src="../includes/chart.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('votersChart').getContext('2d');
            var votersChart = new Chart(ctx, {
                type: 'bar', 
                data: {
                    labels: ['Registered Voters', 'Voted Voters', 'Not Voted'],
                    datasets: [{
                        data: [<?php echo $total_registered; ?>, <?php echo $total_voted; ?>, <?php echo $total_not_voted; ?>],
                        backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.label + ': ' + tooltipItem.raw;
                                }
                            }
                        }
                    }
                }
            });

            // Presidential Voting by School Chart
            var ctx2 = document.getElementById('presidentialVotesBySchool').getContext('2d');
            var presidentialVotesBySchoolChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($school_votes, 'school_name')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($school_votes, 'vote_count')); ?>,
                        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#FF9F40', '#9966FF', '#FFCD56'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.label + ': ' + tooltipItem.raw;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
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

    <!-- Overlay -->
    <div id="overlay" onclick="toggleMenu()"></div>


    

    <section class="statistics">
        <hr><h2>Statistics</h2><hr>
        <div class="statistics-content">
            <div class="statistics-text">
                <p>Total Registered Voters: <?php echo $total_registered; ?></p>
                <p>Total Voted Voters: <?php echo $total_voted; ?></p>
                <p>Not Voted: <?php echo $total_not_voted; ?></p>
            </div>
            <div class="statistics-chart">
                <canvas id="votersChart"></canvas>
            </div>
        </div>
    </section>

    <section class="results">
        <hr><h2>Presidential Candidates</h2><hr>
        <table>
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Votes</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $total_presidential_votes = array_sum(array_column($position_results['president'], 'vote_count'));
                    foreach ($position_results['president'] as $candidate):
                        $percentage = calculate_percentage($candidate['vote_count'], $total_presidential_votes);
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td>
                        <td><?php echo $candidate['vote_count']; ?></td>
                        <td>
                                    <?php 
                                        $percentage = ($total_presidential_votes > 0) ? ($candidate['vote_count'] / $total_presidential_votes) * 100 : 0;
                                        echo round($percentage, 2) . '%'; 
                                    ?>
                                    <div class="percentage-bar">
                                        <span style="width: <?php echo $percentage; ?>%;"><?php echo round($percentage, 1); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Results by School -->
        <h2>Results by School</h2><hr>
        <?php foreach (['school_rep' => 'School Representative', 'men_rep' => "Men's Representative", 'women_rep' => "Women's Representative"] as $key => $title): ?>
            <hr><h3><?php echo $title; ?></h3><hr>
            <table>
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Votes</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $total_position_votes = array_sum(array_column($position_results[$key], 'vote_count'));
                        if (!empty($position_results[$key])):
                            foreach ($position_results[$key] as $candidate):
                                $percentage = calculate_percentage($candidate['vote_count'], $total_position_votes);
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td>
                            <td><?php echo $candidate['vote_count']; ?></td>
                            <td>
                                <?php 
                                        $percentage = ($total_position_votes > 0) ? ($candidate['vote_count'] / $total_position_votes) * 100 : 0;
                                        echo round($percentage, 2) . '%'; 
                                    ?>
                                    <div class="percentage-bar">
                                        <span style="width: <?php echo $percentage; ?>%;"><?php echo round($percentage, 1); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">No candidates yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <!-- Presidential Votes by School Chart -->
        <hr><h3>Presidency  Winner Votes by School</h3>
        <canvas id="presidentialVotesBySchool"></canvas>
    </section>
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
