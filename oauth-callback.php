<?php
require_once 'vendor/autoload.php';

use Google_Service_Oauth2; // Correct namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$client = new Google_Client();
$client->setClientId('10700573976-a9t9s8oc5iidm6kop2e1rdomtmbb1q49.apps.googleusercontent.com'); // Replace with your new Google Client ID
$client->setClientSecret('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'); // Replace with your new Google Client Secret
$client->setRedirectUri('https://antiquewhite-bear-449942.hostingersite.com/oauth-callback.php'); // Ensure this matches the URI in Google API Console

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        die('Error fetching access token: ' . $token['error']);
    }
    $client->setAccessToken($token);

    $oauth2 = new Google_Service_Oauth2($client);
    $google_user_info = $oauth2->userinfo->get();

    // Use $google_user_info to get user details and handle login/signup
    $email = $google_user_info->email;
    $name = $google_user_info->name;

    // Check if user exists in the database
    $mysqli = require __DIR__ . "/database.php";
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $mysqli->stmt_init();

    if (!$stmt->prepare($sql)) {
        die("SQL error: " . $mysqli->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // User exists, log them in
        session_start();
        session_regenerate_id();
        $_SESSION["user_id"] = $user["id"];
        if ($user["is_verified"]) {
            header("Location: index.php");
        } else {
            header("Location: unverified.php");
        }
    } else {
        // User does not exist, create a new user
        $verification_code = bin2hex(random_bytes(16));
        $sql = "INSERT INTO users (fullname, email, is_verified, verification_code) VALUES (?, ?, TRUE, ?)";
        $stmt = $mysqli->stmt_init();

        if (!$stmt->prepare($sql)) {
            die("SQL error: " . $mysqli->error);
        }

        $stmt->bind_param("sss", $name, $email, $verification_code);
        $stmt->execute();

        send_verification_email($email, $verification_code);

        session_start();
        session_regenerate_id();
        $_SESSION["user_id"] = $mysqli->insert_id;
        header("Location: https://antiquewhite-bear-449942.hostingersite.com/verify-email.php?code=$verification_code");
        header("Location: index.php");
    }
    exit;
} else {
    die('No code parameter found in the URL.');
}

function send_verification_email($email, $verification_code) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Use Gmail's SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gianjbsasha1430@gmail.com'; // Your Gmail address
        $mail->Password   = 'dyid ogmq coex kjgn'; // Your app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('gianjbsasha1430@gmail.com', 'Mailer');
        $mail->addAddress($email); // Sends the email to the user's email address

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification';
        $mail->Body    = "Please click the link below to verify your email address: <a href='https://antiquewhite-bear-449942.hostingersite.com/verify-email.php?code=$verification_code'>Verify Email</a>"; // Update to localhost

        $mail->send();
    } catch (Exception $e) {
        die("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }

    header("Location: https://antiquewhite-bear-449942.hostingersite.com/verify-email.php?code=$verification_code");
}
?>