<?php
session_start();

$mysqli = require __DIR__ . "/database.php";

if (!$mysqli) {
    die("Database connection failed.");
}

// Initialize variables
$user = null;
$temperature = "N/A";
$humidity = "N/A";
$tempStatus = "Unknown";
$humidStatus = "Unknown";

// Fetch user details if logged in
if (isset($_SESSION["user_id"])) {
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}

// Set proper database connection encoding
$mysqli->set_charset("utf8mb4");

// Fetch the latest temperature and humidity readings
$sql = "SELECT temperature, humidity FROM dht11 ORDER BY datetime DESC LIMIT 1";
$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $temperature = $row["temperature"];
    $humidity = $row["humidity"];
}

// Function to determine status
function getStatus($value, $min, $max) {
    if (!is_numeric($value)) return "Invalid";
    if ($value < $min) return "Below range";
    if ($value > $max) return "Above range";
    return "Optimal range";
}

$tempStatus = getStatus($temperature, 36.5, 37.5);
$humidStatus = getStatus($humidity, 50, 60);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EggWatch: Temperature and Humidity Monitor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        .container {
            display: flex;
            gap: 20px;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            width: 200px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .card-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .card-content {
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }
        .card-status {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .user-info {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 16px;
            color: #333;
        }
    </style>
</head>
<body>
    <?php if ($user): ?>
        <div class="user-info">Welcome, <?= htmlspecialchars($user["fullname"]) ?></div>
    <?php endif; ?>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Temperature</span>
            </div>
            <div class="card-content"><?= htmlspecialchars($temperature) ?> &deg;C</div>
            <div class="card-status"><?= htmlspecialchars($tempStatus) ?></div>
        </div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Humidity</span>
            </div>
            <div class="card-content"><?= htmlspecialchars($humidity) ?>%</div>
            <div class="card-status"><?= htmlspecialchars($humidStatus) ?></div>
        </div>
    </div>

    <script>
        // Refresh the page every 5 seconds to get new data
        setTimeout(function() {
            location.reload();
        }, 5000);
    </script>
</body>
</html>
