<?php
date_default_timezone_set('Africa/Nairobi');

session_start();
require 'includes/connect.php';

// Check for user session
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])){
    header('Location: logout.php');
    exit;
}
 $isAdmin = isset($_SESSION['admin_id']);
 $dashboard = $isAdmin ? 'admin/dashboard.php' : 'voter/dashboard.php';



$stmt_election_dates = $conn->prepare("
    SELECT start_date, end_date FROM polls WHERE status = 'open' LIMIT 1;
");

$stmt_election_dates->execute();
$election_dates = $stmt_election_dates->get_result()->fetch_assoc();

$current_date = date('Y-m-d H:i:s'); // Current date and time

// Check if the election is over
if ($current_date < $election_dates['start_date']) {
    echo "<h2 style='text-align:center; color:red;'>The election has not started yet. Please check back later.</h2>";
    header("Refresh:3;url= $dashboard");
    exit;

} elseif ($current_date <= $election_dates['end_date']) {
    echo "<h2 style='text-align:center; color:red;'>The election is still ongoing. Reports will be available after {$election_dates['end_date']}.</h2>";
    header("Refresh:3;url= $dashboard");
    exit;
}

// Query to get total registered and voted voters
$stmt_voter_stats = $conn->prepare("
    SELECT COUNT(*) AS total_registered FROM users;
");
$stmt_voter_stats->execute();
$total_registered = $stmt_voter_stats->get_result()->fetch_assoc()['total_registered'];

# voted voters
$stmt_voted = $conn->prepare("
    SELECT COUNT(DISTINCT user_id) AS total_voted FROM votes;
");
$stmt_voted->execute();
$total_voted = $stmt_voted->get_result()->fetch_assoc()['total_voted'];

$total_non_voters = $total_registered - $total_voted;

// Query for President
$stmt_president = $conn->prepare("
    SELECT c.candidate_name, COUNT(v.vote_id) AS vote_count
    FROM candidates c
    JOIN votes v ON c.candidate_id = v.candidate_id
    WHERE c.position_id = 1
    GROUP BY c.candidate_id
    ORDER BY vote_count DESC
");
$stmt_president->execute();
$president_results = $stmt_president->get_result();
$president_data = [];
while ($row = $president_results->fetch_assoc()) {
    $president_data[] = $row;
}

// Query for Other Positions
$positions = [
    'school_rep' => 2,
    'men_rep' => 3,
    'women_rep' => 4
];

$election_summary = [];
foreach ($positions as $position_key => $position_id) {
    $stmt_position = $conn->prepare("
        SELECT c.candidate_name, c.position_id, s.school_name, COUNT(v.vote_id) AS vote_count
        FROM candidates c
        JOIN votes v ON c.candidate_id = v.candidate_id
        JOIN schools s ON s.school_id = c.school_id
        WHERE c.position_id = ?
        GROUP BY s.school_name, c.candidate_id
        ORDER BY vote_count DESC
    ");
    $stmt_position->bind_param("i", $position_id);
    $stmt_position->execute();
    $result_position = $stmt_position->get_result();

    while ($row = $result_position->fetch_assoc()) {
        $election_summary[$row['school_name']][$position_key][] = [
            'candidate_name' => $row['candidate_name'],
            'vote_count' => $row['vote_count'],
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Report</title>
    <link rel="stylesheet" href="../styles/report.css">
    <link rel="stylesheet" href="../styles/ham.css">

    <script src="includes/chart.js" defer></script>
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
        <li><a href="<?php echo $dashboard ?>">Home</a></li>
        <li><a href="#">Report</a></li>
        <li><a href="logout.php">Log Out</a></li>
    </ul>
</div>

<!-- Overlay -->
<div id="overlay" onclick="toggleMenu()"></div>

    <section class="summary-report">
        <!-- Total Voter Statistics Section -->
        <hr><h2>Election Voter Statistics</h2><hr>
        <div class="statistics">
            <p>Total Registered Voters: <?php echo $total_registered; ?></p>
            <p>Total Voted Voters: <?php echo $total_voted; ?></p>
            <p>Not Voted: <?php echo $total_non_voters; ?></p>
        </div>
        <canvas id="votersChart"></canvas>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var ctx = document.getElementById('votersChart').getContext('2d');
                var votersChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Registered Voters', 'Voted Voters', 'Not Voted'],
                        datasets: [{
                            data: [<?php echo $total_registered; ?>, <?php echo $total_voted; ?>, <?php echo $total_non_voters; ?>],
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
            });
        </script>

        <!-- President Winner Section -->
        <hr><h2>President Election Results</h2><hr>
        <?php if (!empty($president_data)): ?>
            <div class="president-result">
                <table>
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Winner</th>
                            <th>Winning Votes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($president_data )): ?>
                            <tr>
                                <td>President</td>
                                <td><?php echo htmlspecialchars($president_data[0]['candidate_name']); ?></td>
                                <td><?php echo $president_data[0]['vote_count']; ?></td>
                            </tr>
                
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <canvas id="presidentChart"></canvas>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var ctx = document.getElementById('presidentChart').getContext('2d');
                    var presidentChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode(array_column($president_data, 'candidate_name')); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_column($president_data, 'vote_count')); ?>,
                                backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#FF9F40', '#9966FF'],
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(tooltipItem) {
                                            return tooltipItem.raw + ' votes';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Votes'
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
        <?php else: ?>

            <p>No data available for the President position.</p>
            
        <?php endif; ?>

        <!-- Other Positions Section -->
        <hr><h2>Election Winners for School-Specific Positions</h2><hr>
        <table>
            <thead>
                <tr>
                    <th>School</th>
                    <th>Position</th>
                    <th>Winner</th>
                    <th>Winning Votes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($election_summary as $school_name => $positions_results): ?>
                    <?php foreach ($positions as $position_key => $position_id): ?>
                        <?php if (isset($positions_results[$position_key])): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($school_name); ?></td>
                                <td>
                                    <?php
                                    switch ($position_key) {
                                        case 'school_rep': echo 'School Representative'; break;
                                        case 'men_rep': echo "Men's Representative"; break;
                                        case 'women_rep': echo "Women's Representative"; break;
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($positions_results[$position_key][0]['candidate_name']); ?></td>
                                <td><?php echo $positions_results[$position_key][0]['vote_count']; ?></td>
                            </tr>
                            <tr>
                                <td colspan="4">
                                    <canvas id="chart-<?php echo $school_name . '-' . $position_key; ?>"></canvas>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            var ctx = document.getElementById('chart-<?php echo $school_name . '-' . $position_key; ?>').getContext('2d');
                                            var data = <?php echo json_encode($positions_results[$position_key]); ?>;
                                            var labels = data.map(d => d.candidate_name);
                                            var votes = data.map(d => d.vote_count);

                                            var chart = new Chart(ctx, {
                                                type: 'bar',
                                                data: {
                                                    labels: labels,
                                                    datasets: [{
                                                        label: 'Votes',
                                                        data: votes,
                                                        backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#FF9F40', '#9966FF'],
                                                        hoverOffset: 4
                                                    }]
                                                },
                                                options: {
                                                    responsive: true,
                                                    plugins: {
                                                        legend: {
                                                            position: 'top',
                                                        },
                                                        tooltip: {
                                                            callbacks: {
                                                                label: function(tooltipItem) {
                                                                    return tooltipItem.raw + ' votes';
                                                                }
                                                            }
                                                        }
                                                    },
                                                    scales: {
                                                        y: {
                                                            beginAtZero: true,
                                                            title: {
                                                                display: true,
                                                                text: 'Votes'
                                                            }
                                                        }
                                                    }
                                                }
                                            });
                                        });
                                    </script>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

   <section class="summary-report">
    <!-- Election Report Content -->
    <div class="export-container">
        <button id="export-button">Download Report</button>
        <div id="export-options" class="dropdown">
            <a href="#" id="export-pdf">PDF</a>
            <a href="#" id="export-image">Image</a>
        </div>
    </div>
</section>


  
<script src="../includes/functions.js" defer></script>
<script src="../includes/html2canvas.min.js"></script>
<script src="../includes/jspdf.umd.min.js"></script>

    <script>
             // Export as PDF
             document.getElementById('export-pdf').addEventListener('click', function (e) {
    e.preventDefault(); // Prevent navigation
    const { jsPDF } = window.jspdf;

    html2canvas(document.querySelector('.summary-report'), { scale: 2 }).then(function (canvas) {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');

        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();

        const canvasWidth = canvas.width;
        const canvasHeight = canvas.height;

        const ratio = canvasWidth / pdfWidth;
        const scaledHeight = canvasHeight / ratio;

        let yPosition = 0;

        // Add multiple pages if content exceeds one page
        while (yPosition < scaledHeight) {
            const sliceCanvas = document.createElement('canvas');
            sliceCanvas.width = canvas.width;
            sliceCanvas.height = Math.min(canvas.height, (pdfHeight * ratio));

            const context = sliceCanvas.getContext('2d');
            context.drawImage(canvas, 0, yPosition * ratio, canvas.width, sliceCanvas.height, 0, 0, sliceCanvas.width, sliceCanvas.height);

            const sliceImgData = sliceCanvas.toDataURL('image/png');
            pdf.addImage(sliceImgData, 'PNG', 0, 0, pdfWidth, pdfHeight);

            yPosition += pdfHeight;
            if (yPosition < scaledHeight) {
                pdf.addPage();
            }
        }

        pdf.save('Must_2024_Elections_Report.pdf');
    });
});




            // Export as Image
            document.getElementById('export-image').addEventListener('click', function (e) {
                e.preventDefault(); // Prevent navigation
                html2canvas(document.querySelector('.summary-report')).then(function (canvas) {
                    const link = document.createElement('a');
                    link.download = 'Must_2024_Elections_Report.png';
                    link.href = canvas.toDataURL();
                    link.click();
                });
            });
    </script>


<footer class="footer">
    <p>Copyright &copy; 2024 MUST Secure Voting System. </p>
    <p>All rights reserved.</p>
    <nav>
        <a href="#">Contact Us</a>
    </nav>
</footer>
</body>
</html>
