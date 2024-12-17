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
$tempMin = null;
$tempMax = null;
$humidMin = null;
$humidMax = null;
$updateSuccess = false; // Success flag

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

// Create thresholds table if not exists
$mysqli->query("CREATE TABLE IF NOT EXISTS thresholds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    egg_type VARCHAR(50) NOT NULL UNIQUE,
    temp_min FLOAT,
    temp_max FLOAT,
    humid_min FLOAT,
    humid_max FLOAT
)");

// Default egg type
$eggType = $_POST['egg_type'] ?? 'Chicken Egg';

// Fetch thresholds for the selected egg type
$stmt = $mysqli->prepare("SELECT * FROM thresholds WHERE egg_type = ? LIMIT 1");
$stmt->bind_param("s", $eggType);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $thresholds = $result->fetch_assoc();
    $tempMin = $thresholds['temp_min'];
    $tempMax = $thresholds['temp_max'];
    $humidMin = $thresholds['humid_min'];
    $humidMax = $thresholds['humid_max'];
} else {
    // Insert a blank row for the egg type if not present
    $stmt = $mysqli->prepare("INSERT INTO thresholds (egg_type, temp_min, temp_max, humid_min, humid_max) 
                              VALUES (?, NULL, NULL, NULL, NULL)");
    $stmt->bind_param("s", $eggType);
    $stmt->execute();
    $stmt->close();
}
$stmt->close();

// Handle POST request to update thresholds
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_thresholds"])) {
    $tempMin = (float) $_POST["temp_min"];
    $tempMax = (float) $_POST["temp_max"];
    $humidMin = (float) $_POST["humid_min"];
    $humidMax = (float) $_POST["humid_max"];

    $stmt = $mysqli->prepare("UPDATE thresholds SET 
                                temp_min = ?, 
                                temp_max = ?, 
                                humid_min = ?, 
                                humid_max = ? 
                              WHERE egg_type = ?");
    $stmt->bind_param("dddss", $tempMin, $tempMax, $humidMin, $humidMax, $eggType);
    $stmt->execute();
    $stmt->close();

    // Set success flag
    $updateSuccess = true;
}

// Fetch the latest temperature and humidity readings
$sql = "SELECT temperature, humidity, datetime FROM dht11 ORDER BY datetime DESC LIMIT 1";
$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $temperature = $row["temperature"];
    $humidity = $row["humidity"];
    $datetime = $row["datetime"];

    // Insert the latest temperature and humidity into historical_data
    $insertData = $mysqli->prepare("INSERT INTO historical_data (temperature, humidity, recorded_at, datetime) 
                                    VALUES (?, ?, NOW(), ?)");
    $insertData->bind_param("dds", $temperature, $humidity, $datetime);
    $insertData->execute();
    $insertData->close();
}

// Function to determine status
function getStatus($value, $min, $max) {
    if (!is_numeric($value) || $min === null || $max === null) return "Unknown";
    if ($value < $min) return "Below range";
    if ($value > $max) return "Above range";
    return "Optimal range";
}

$tempStatus = getStatus($temperature, $tempMin, $tempMax);
$humidStatus = getStatus($humidity, $humidMin, $humidMax);

// Fetch historical data for the chart
$sql = "SELECT temperature, humidity, recorded_at FROM historical_data ORDER BY recorded_at DESC LIMIT 10";
$result = $mysqli->query($sql);

$historicalData = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $historicalData[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EggWatch: Temperature and Humidity Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
            padding-top: 30px;
        }
        .container {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 25px;
            width: 250px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #dcdcdc;
            transition: all 0.3s ease-in-out;
            margin: 10px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #343a40;
        }
        .card-content {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
        }
        .card-status {
            font-size: 14px;
            color: #6c757d;
            margin-top: 10px;
        }
        .threshold-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 25px;
            width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #dcdcdc;
            margin: 10px;
            text-align: center;
        }
        .threshold-card-header {
            font-size: 20px;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 15px;
        }
        .thresholds-content {
            font-size: 16px;
            color: #212529;
        }
        form {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 350px;
        }
        label {
            display: block;
            margin: 12px 0 6px;
            font-weight: 600;
            color: #495057;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            font-size: 16px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.5);
        }
        button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #218838;
        }
        .success-message {
            color: #28a745;
            font-weight: 500;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Temperature</span>
            </div>
            <div class="card-content" id="temperature"><?= htmlspecialchars($temperature) ?> &deg;C</div>
            <div class="card-status" id="tempStatus"><?= htmlspecialchars($tempStatus) ?></div>
        </div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Humidity</span>
            </div>
            <div class="card-content" id="humidity"><?= htmlspecialchars($humidity) ?>%</div>
            <div class="card-status" id="humidStatus"><?= htmlspecialchars($humidStatus) ?></div>
        </div>
    </div>

    <!-- Chart for historical data -->
    <div style="width: 80%; max-width: 800px; margin-bottom: 30px;">
        <canvas id="temperatureHumidityChart"></canvas>
    </div>

    <!-- Current Thresholds Card -->
    <div class="threshold-card">
        <div class="threshold-card-header">
            Current Threshold for <?= htmlspecialchars($eggType) ?>
        </div>
        <div class="thresholds-content">
            <p><strong>Temperature Min:</strong> <?= htmlspecialchars($tempMin ?? 'N/A') ?> &deg;C</p>
            <p><strong>Temperature Max:</strong> <?= htmlspecialchars($tempMax ?? 'N/A') ?> &deg;C</p>
            <p><strong>Humidity Min:</strong> <?= htmlspecialchars($humidMin ?? 'N/A') ?>%</p>
            <p><strong>Humidity Max:</strong> <?= htmlspecialchars($humidMax ?? 'N/A') ?>%</p>
        </div>
    </div>

    <!-- Threshold Form -->
    <form method="POST" action="">
        <h3>Set Thresholds for <?= htmlspecialchars($eggType) ?></h3>
        <label>Egg Type:</label>
        <select name="egg_type" onchange="this.form.submit()">
            <option value="Chicken Egg" <?= $eggType == 'Chicken Egg' ? 'selected' : '' ?>>Chicken Egg</option>
            <option value="Duck Egg" <?= $eggType == 'Duck Egg' ? 'selected' : '' ?>>Duck Egg</option>
        </select>
        <label>Temperature Min:</label>
        <input type="number" step="0.1" name="temp_min" value="<?= htmlspecialchars($tempMin ?? '') ?>" required>
        <label>Temperature Max:</label>
        <input type="number" step="0.1" name="temp_max" value="<?= htmlspecialchars($tempMax ?? '') ?>" required>
        <label>Humidity Min:</label>
        <input type="number" step="0.1" name="humid_min" value="<?= htmlspecialchars($humidMin ?? '') ?>" required>
        <label>Humidity Max:</label>
        <input type="number" step="0.1" name="humid_max" value="<?= htmlspecialchars($humidMax ?? '') ?>" required>
        <button type="submit" name="update_thresholds">Update Thresholds</button>
        <?php if ($updateSuccess): ?>
            <div class="success-message">Thresholds updated successfully!</div>
        <?php endif; ?>
    </form>

    <script>
        // Automatically refresh the entire page every 30 seconds
        setInterval(function() {
            location.reload(); // Reload the page
        }, 30000); // 30 seconds
        
        // Historical data chart
        const ctx = document.getElementById('temperatureHumidityChart').getContext('2d');
        const data = {
            labels: [<?php echo implode(',', array_map(function($row) { return '"' . $row['recorded_at'] . '"'; }, $historicalData)); ?>],
            datasets: [{
                label: 'Temperature (°C)',
                data: [<?php echo implode(',', array_map(function($row) { return $row['temperature']; }, $historicalData)); ?>],
                borderColor: 'rgba(255, 99, 132, 1)',
                fill: false,
                tension: 0.1
            }, {
                label: 'Humidity (%)',
                data: [<?php echo implode(',', array_map(function($row) { return $row['humidity']; }, $historicalData)); ?>],
                borderColor: 'rgba(54, 162, 235, 1)',
                fill: false,
                tension: 0.1
            }]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Values'
                        },
                        min: 0
                    }
                }
            }
        };
        new Chart(ctx, config);
    </script>
</body>
</html>