<?php
session_start();
$mysqli = require __DIR__ . "/database.php";

$error = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Fetch user data from the "users" table
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify password against password_hash
        if (password_verify($password, $user["password_hash"])) {
            if ($user["is_verified"]) {
                // Start session and redirect to dashboard
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["fullname"] = $user["fullname"];
                header("Location: index.php");
                exit;
            } else {
                $error = "Account not verified!";
            }
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "No user found with that email!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f9;
        }

        /* Container Styling */
        .form-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 350px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #333333;
            font-size: 1.5em;
        }

        /* Input Fields */
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        input:focus {
            border-color: #007BFF;
        }

        /* Button Styling */
        button {
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        /* Error Message */
        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* Sign-Up Link */
        .signup-link {
            margin-top: 15px;
            font-size: 14px;
            color: #666;
        }

        .signup-link a {
            color: #007BFF;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: #0056b3;
        }

        /* Google Sign-In Button */
        .google-signin {
            margin-top: 20px;
        }

        .google-signin button {
            background-color: #4285F4;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .google-signin button img {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .google-signin button:hover {
            background-color: #357ae8;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Login</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="google-signin">
            <form action="oauth-login.php" method="post">
                <button type="submit">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/768px-Google_%22G%22_logo.svg.png" alt="Google logo">
                    Sign in with Google
                </button>
            </form>
        </div>
        <div class="signup-link">
            <p>Don't have an account? <a href="signup.html">Sign up</a></p>
        </div>
    </div>
</body>
</html>