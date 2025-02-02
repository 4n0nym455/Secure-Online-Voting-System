<?php
date_default_timezone_set('Africa/Nairobi');

require_once '../includes/connect.php';
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

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_email'])) {
    $reset_email = $_POST['reset_email'];
        # Validate CSRF token
 if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid CSRF token.');
}

    # Email format validation
    if (empty($errors) && !filter_var($reset_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";

    } else {
    # Validate the email exists
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $reset_email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $errors[] = "No account found with this email.";
    }

   

    if ($user) {
        # Check for recent reset request
        $stmt = $conn->prepare("SELECT last_requested_at FROM password_resets WHERE email = ? AND status = 0 ORDER BY last_requested_at DESC LIMIT 1");
        $stmt->bind_param("s", $reset_email);
        $stmt->execute();
        $reset_request = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($reset_request && strtotime($reset_request['last_requested_at']) > strtotime('-120 minutes')) {
            $errors[] = "You can only request a password reset every 2 hour.";
            
        } 
        
        if (empty($errors)) {
            # Generate reset token
            $token = bin2hex(random_bytes(20));
            $expires_at =  date('Y-m-d H:i:s',strtotime('+5 Minutes'));

            # Storing token and expiration time in the database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at, last_requested_at, status) VALUES (?, ?, ?, NOW(), 0)");
            $stmt->bind_param("sss", $reset_email, $token, $expires_at);
            $stmt->execute();
            $stmt->close();

            #cleaning older records
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND id NOT IN (SELECT id FROM (SELECT id FROM password_resets WHERE email = ? ORDER BY last_requested_at DESC LIMIT 1) AS recent)");
            $stmt->bind_param("ss", $reset_email, $reset_email);
            $stmt->execute();
            $stmt->close();

            # Send reset link to email
            $reset_link = getenv('BASE_URL') . "/voter/reset_password.php?token=$token";
            
            $mail = new PHPMailer(true);

            try {

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = getenv('SMTP_USERNAME');
                $mail->Password = getenv('SMTP_PASSWORD');
                $mail->SMTPSecure = PHPMailer ::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom(getenv('SMTP_USERNAME'), 'Must Secure Voting System');
                $mail->addAddress($reset_email);
                $mail->Subject = 'Password Reset Request';

                $mail->isHTML(true);
                $mail->Body = "
                                <h3>Password Reset Request</h3>
                                <p> Click this link to reset your password. <strong> This link is will expire in 5 minutes</strong>:</p>
                                <a href = '$reset_link' style='color:blue;'>Reset Your Password</a>
                                <p>If you did not request this reset, please ignore this email.</p>
                ";

                $mail->send();
                echo "<p style='color:green;'>A password reset link has been sent to your email.</p> 
                       <p style='color:green;'>Please Check it in the spam folder if you can't find it in your email</p>";
                header("Refresh:4;url=../voter/login.php");
                exit;
                
            } catch (Exception $e) {
                $errors[] = "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
            }
        }
    } 
}

}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../styles/styled.css">
</head>
<body>
    <div class="form-container">
    <div class="logo-container">
            <img src="../images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>
        <h2>Forgot Password</h2>
       <!-- Display any errors -->
       <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <strong><?php echo htmlspecialchars($error); ?></strong>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form action="forgot_password.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"> 
            
            <button type="submit">Send Reset Link</button>
            <input type="email" name="reset_email" placeholder="Enter your email" required>

      
        </form>
    </div>
    
</body>
</html>