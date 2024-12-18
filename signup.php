<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mysqli = require __DIR__ . "/database.php";

// Input validation
if (empty($_POST["name"])) {
    die("Name is required!");
}

if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
    die("Valid Email is required");
}

if (strlen($_POST["password"]) < 8) {
    die("Password must be at least 8 characters");
}

if (!preg_match("/[a-z]/i", $_POST["password"])) {
    die("Password must contain at least one letter");
}

if (!preg_match("/[0-9]/", $_POST["password"])) {
    die("Password must contain at least one number");
}

if ($_POST["password"] !== $_POST["confirm-password"]) {
    die("Passwords must match");
}

// Hash the password
$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

$verification_code = bin2hex(random_bytes(16));

// SQL query to insert user
$sql = "INSERT INTO users (fullname, email, password_hash, verification_code)
        VALUES (?, ?, ?, ?)";

$stmt = $mysqli->stmt_init();

if (!$stmt->prepare($sql)) {
    die("SQL error: " . $mysqli->error);
}

// Bind the parameters
$stmt->bind_param("ssss",
                  $_POST["name"],
                  $_POST["email"],
                  $password_hash,
                  $verification_code);

try {
    $stmt->execute();
    send_verification_email($_POST["email"], $verification_code);
    header("Location: signup-success.html");
    exit;
} catch (mysqli_sql_exception) {
    if ($mysqli->errno === 1062) {
        die("Email already taken");
    } else {
        die("Database error: " . $mysqli->error);
    }
}

// Function to send the verification email
function send_verification_email($email, $verification_code) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mailernimike6@gmail.com'; // Your Gmail address
        $mail->Password   = 'aoad ikwg wqcj rxxb';     // Your app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('mailernimike6@gmail.com', 'Mailer');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'EggWatch - Account Email Verification';
        $mail->Body    = "Please click the link below to verify your email address: <a href='https://antiquewhite-bear-449942.hostingersite.com/verify-email.php?code=$verification_code'>Verify Email</a>";

        $mail->send();
    } catch (Exception $e) {
        die("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>