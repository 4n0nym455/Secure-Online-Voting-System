<?php
date_default_timezone_set('Africa/Nairobi');


require_once '../includes/connect.php'; 
require_once '../includes/functions.php';
require_once '../includes/env_loader.php';
require '../includes/lib/phpmailer/src/Exception.php';
require '../includes/lib/phpmailer/src/PHPMailer.php';
require '../includes/lib/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    loadEnv(__DIR__ . '/../.env');

} catch (Exception $e) {
    die($e->getMessage());
}

session_start();

$errors = [];
$success = "";
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
    $_SESSION['csrf_token_time'] = time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'], $_POST['confirm_password'], $_GET['token'])) {

        # Validate CSRF token
 if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid CSRF token.');
}

    $npassword = $_POST['new_password'];
    $cpassword = $_POST['confirm_password'];
    $token = $_GET['token'];

    $current_time = date('Y-m-d H:i:s');

    // Check if the token exists and is not expired
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ? AND status = 0");
    $stmt->bind_param("ss", $token, $current_time);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();



    if (!$reset) {
        $errors[] = "Token expired or invalid.";
    } else {
        $email = $reset['email'];
    }
        if ($npassword !== $cpassword) {
            $errors[] = "Password do not match";
        }
        if (!is_strong_password($npassword)) {
            $errors[] = "Password Does not meet the Strength Standards";
        }

        if (empty($errors)) {

            $conn->begin_transaction();

            try{
          
            $hashed_password = password_hash($npassword, PASSWORD_BCRYPT);

            # Update the user's password in the users table
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            $stmt->execute();
            $stmt->close();

            $mail = new PHPMailer(true);

            
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = getenv('SMTP_USERNAME');
                $mail->Password = getenv('SMTP_PASSWORD');
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;


                $mail->setFrom(getenv('SMTP_USERNAME'), 'Must Secure Voting System');
                $mail->addAddress($email);
                $mail->Subject = 'Password Reset Successful';

                $mail->isHTML(true);
                $mail->Body = "
                                <h3>Password Reset Successful</h3>
                                <p> Your password has been reset Successfully.</p>
                                <p> You can now Log In with your new Password.</p>
                                <a href='". getenv('BASE_URL') . "/voter/login.php' style='color: blue;'>Log In </a>
                                <p>If you did not perform this Action, please contact support immediately.</p>
                                
                
                ";

                $mail->send();

                 # change the token status to complete
                $stmt = $conn->prepare("UPDATE password_resets SET status = 1 WHERE token = ?");
                $stmt->bind_param("s", $token);
                if (!$stmt->execute()) {
                  
                    throw new Exception ("Failed to update token status.");
                }
                $stmt->close();

                $conn->commit();


                $success = "Your password has been reset successfully. You will be redirected to login";
                    header("Refresh:3; url= /voter/login.php");
                    exit;

            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Error: ". $e->getMessage();
            }
           
        } 
    } 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../styles/styled.css">
</head>
<body>
    <div class="form-container">
        <div class="logo-container">
            <img src="../images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>

        <h2>Reset Password</h2>

        <?php 
        if (!empty ($success)) {
            echo "<p style='color:green;'>$success</p>";
        }
        ?>

        <!-- Display any errors -->
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <strong><?php echo htmlspecialchars($error); ?></strong>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form action="reset_password.php?token=<?php echo $_GET['token']; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class= "pass-wrapper">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
                <button type="button" onmouseover="showPass('new_password', this, true)" onmouseout="showPass('new_password', this, false)">👁️</button>

            </div>

            <div class= "pass-wrapper">

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="button" onmouseover="showPass('confirm_password', this, true)" onmouseout="showPass('confirm_password', this, false)">👁️</button>

            </div>
                <button type="submit">Reset Password</button>
        </form>
        <div class="confirmation-text">
            <p><strong>Note:</strong> Please Check the Email sent in your Spam Folder if you can't find it.</p>
        </div>
    </div>
    <script src = "../includes/functions.js" defer></script>

</body>
</html>
