<?php
date_default_timezone_set('Africa/Nairobi');



require_once "../includes/connect.php";
require_once "../includes/functions.php";


$schools=[];
$school_query= $conn->query("SELECT school_id, school_name, prefix FROM schools");

if ($school_query){
while ($row = $school_query->fetch_assoc()) {
    $schools[]=$row;
}
}else{
    echo "Error fetching schools:" . $conn->error;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {


   

    # Gets input Values
    $username=trim($_POST['username']);
    $reg_num=trim($_POST['registration_number']);
    $email=trim($_POST['email']);
    $password=trim($_POST['password']);
    $cpassword=trim($_POST['cpassword']);
    $school_name=trim($_POST['school_name']);
    $school_prefix = strtoupper(substr($reg_num, 0, 2));# to extract prefix from table


$errors=[];

# Validation user input
if (empty($username) || empty($reg_num) || empty($email) || empty($password) || empty($school_name)){
$errors[]="Fields Required";
}

if ($password!==$cpassword){
    $errors[]= "Passwords Do Not Match";
}

if (!is_strong_password($password)){
    $errors[]= "Please enter a strong password. Must be atleast 8 characters long, include an uppercase letter, lowercase letter, a number and a special character.";
}


# Registration number Validation
$reg_num_error = validRegNo($reg_num);
if ($reg_num_error) {
    $errors[] = $reg_num_error;
}


# Email Validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
    $errors[]="Enter a valid email format eg example@gmail.com";
}

# Prepared statement to check if user already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0){
    $errors[]="Username or Email already exists.";

}
 $stmt->close();

# Prepared Statement to check if registration number exists
$stmt = $conn->prepare("SELECT * FROM users WHERE registration_number = ? ");
$stmt->bind_param("s", $reg_num);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0){
    $errors[]="Registration number belongs to an already registered user .";

}
 $stmt->close();


# prepared statement to check if  Regno and school selected match
$stmt = $conn->prepare("SELECT school_id FROM schools WHERE school_name = ? AND prefix = ?");
$stmt->bind_param("ss", $school_name, $school_prefix);
$stmt->execute();
$school_result = $stmt->get_result()->fetch_assoc();


if (!$school_result){
    $errors[] = "Selected school Don't Match the Registation Number Prefix provided!";

}else {
    $school_id=$school_result['school_id'];
    
}
 $stmt->close();

 $stmt = $conn->prepare("SELECT * FROM verify WHERE admission_no = ?");
 $stmt->bind_param("s", $reg_num);
 $stmt->execute();
 $result = $stmt->get_result();

 if ($result->num_rows === 0) {
    $errors[] = "Please Use your Real Admission Number";
 } else {
    $user = $result->fetch_assoc();
    if (!password_verify($password, $user['hashed_password'])) {
        $errors[] = "Default Password Failure";
    }
 }
 $stmt->close();

if (empty($errors)){
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);


    $stmt = $conn->prepare("INSERT INTO users(username, email, registration_number, password, school_name, school_id) VALUES(?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $username, $email, $reg_num, $hashed_password, $school_name, $school_id);
    $stmt->execute();

    
    header('Refresh:3;url= login.php');
    
    $stmt->close();

 }else{
    foreach($errors as $error){
        echo "<script>alert('$error');</script>";
    }
}



}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registation</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="/images/must e-voting.png" alt="Must E-Voting Logo" class="logo">
        </div>
    <h2>Register</h2>
  
      
    <form action="register.php" method ="POST">

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" placeholder="username" required autocomplete="off"><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="example@gmail.com" required autocomplete="off"><br><br>

        <label for="reg_num">Registration Number:</label>
        <input type="text" id="reg_number" name="registration_number" placeholder="AA999/1234567/YY" required autocomplete="off"><br><br>

        <select id="school_name" name="school_name" required>

            <!-- Hii ni dropdown list ya Schools -->
            <option value ="">Select your School</option>

            <?php foreach($schools as $school): ?>

            <option><?= htmlspecialchars ($school['school_name']); ?></option>
            <?php endforeach; ?>
            </select><br><br>

        <label for="password">Password:</label>
        <div class = "pass-wrapper">
        <input type="password" id="password" name="password" required autocomplete="off">
        <button type="button" onmouseover="showPass('password', this, true)" onmouseout="showPass('password', this, false)">👁️</button>
        </div>

        <label for="password">Confirm Password:</label>
        <div class = "pass-wrapper">
        <input type="password" id="cpassword" name="cpassword" required autocomplete="off">
        <button type="button" onmouseover="showPass('cpassword', this, true)" onmouseout="showPass('cpassword', this, false)">👁️</button>
        </div>
        <button type="submit">Register</button>

    </form>
    <div>
    <p>Already have an account? <a href="../voter/login.php">Login here</a>.</p>
    </div>
    </div>

    <script src = "../includes/functions.js" defer></script>

</body>
</html>