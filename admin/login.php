<?php
require '../includes/access.php';

date_default_timezone_set('Africa/Nairobi');

session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST"){

    # Validate CSRF token
 if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid CSRF token.');
}


    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)){
        $errors[] = "Please fill in both email and password.";

    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors[] = "Please enter a valid email address.";
    }
    if (empty($errors)){

        $stmt = $conn->prepare("SELECT admin_id, password FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0){
            $admin = $result->fetch_assoc();

            if(password_verify($password, $admin['password'])){

                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['email'] = $email;

                header("Location: dashboard.php");
                exit;

            }else{
                $errors[] = "Incorrect Password.";
            }
            
        }else{
            $errors[] = "No admin account found with that email.";

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

        <title>Admin Login</title>
        <link rel="stylesheet" href="../styles/style.css">
    </head>
    <body>
        <div class="container">
        <div class="logo-container">
            <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>
            <h2>Admin Login</h2>

            <!-- Error Handling -->
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <strong><?php echo htmlspecialchars($error); ?></strong>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                <!-- Admin Login Form -->
                <form action ="login.php" method ="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="email">Email:</label>
                    <input type ="email" id ="email" name ="email" placeholder ="example@gmail.com" required><br><br>

                    
                    <label for="password">Password:</label>
                    <div class = "pass-wrapper">
                    <input type ="password" id ="password" name ="password"  required>
                    <button type="button" onmouseover="showPass('password', this, true)" onmouseout="showPass('password', this, false)">👁️</button>
                    </div>
                    <button type ="submit">Login</button>
                </form>

                <p><a href="../index.php">Homepage </a></p>
            </div>

            <script src = "../includes/functions.js" defer></script>

    </body>
</html>