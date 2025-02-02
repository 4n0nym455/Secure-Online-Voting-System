<?php
date_default_timezone_set('Africa/Nairobi');

session_start();
require_once "../includes/connect.php"; # Database connection

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

$errors = [];
# Initialize an empty array for error messages


# Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

            # Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo 'Invalid CSRF token.';
        header('Refresh:1;url= login.php');
        exit;
    }


    # Get email, registration number and password from POST data
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $reg_num = mysqli_real_escape_string($conn, $_POST['registration']);
    $password = trim($_POST['password']);

    # Validate input
    if (empty($email) || empty($reg_num) || empty($password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    # If no validation errors, proceed with login
    if (empty($errors)) {
        # Prepare and execute SQL query to get user data
        $stmt = $conn->prepare("SELECT user_id, username, school_name, school_id, password, registration_number FROM users WHERE email = ? AND registration_number = ?");
        $stmt->bind_param("ss", $email, $reg_num);
        $stmt->execute();
        $result = $stmt->get_result();

        # Check if the email exists in the database
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            # Verify the password
            if (password_verify($password, $user['password'])) {
                // Successful login, set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['school_id'] = $user['school_id'];
                $_SESSION['school_name'] = $user['school_name'];
                $_SESSION['registration_number'] = $user['registration_number'];
                $_SESSION['email'] = $email;
               
                # Redirect to the dashboard or home page
                echo "<script>window.location.href='/voter/dashboard.php'</script>";
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No account found with that email and registration number.";
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="../images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>
        <h2>Login</h2>
        
        <!-- Display any errors -->
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <strong><?php echo htmlspecialchars($error); ?></strong>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required autocomplete="off"><br><br>

            <label for="reg_num">Registration Number:</label>
            <input type="text" id="reg_num" name="registration" required autocomplete="on"><br><br>
           
            <div class = "pass-wrapper">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="button" onmouseover="showPass('password', this, true)" onmouseout="showPass('password', this, false)">👁️</button>
            </div>
            <button type="submit">Login</button>
        </form>

        <p><a href="forgot_password.php">Forgot Password?</a></p>
        <p>Don't have an account? <a href="register.php">Register here</a>.</p>
    </div>
    <script src = "../includes/functions.js" defer></script>
    

</body>
</html>
