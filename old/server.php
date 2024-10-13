<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

// Get the container ID from the query parameter
if (!isset($_GET['container_id'])) {
    die("Container ID not provided");
}
$containerId = $_GET['container_id'];

// Connect to the containers database
$db = new SQLite3(__DIR__ . '/database/containers.db');

// Retrieve the container information
$query = $db->prepare('SELECT * FROM containers WHERE container_id = :container_id');
$query->bindValue(':container_id', $containerId, SQLITE3_TEXT);
$result = $query->execute();
$container = $result->fetchArray(SQLITE3_ASSOC);

if (!$container) {
    die("Container not found");
}

// Retrieve user information
$userDb = new SQLite3(__DIR__ . '/database/users.db');
$usernameOrEmail = $_SESSION['username'];
$query = $userDb->prepare('SELECT * FROM users WHERE (username = :usernameOrEmail OR email = :usernameOrEmail)');
$query->bindValue(':usernameOrEmail', $usernameOrEmail, SQLITE3_TEXT);
$result = $query->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

// Check if the user is an admin or the owner of the container
$isAdmin = ($user['admin'] === 'yes');
if (!$isAdmin && $container['email'] !== $user['email']) {
    die("Access denied. You are not allowed to manage this container.");
}

// Here you can add functionality to interact with Docker through the Python API (start, stop, restart, etc.)
$ch = curl_init("http://localhost:5000/container-status");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['container_id' => $containerId]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
$status = json_decode($response, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Container</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #2b2b2b;
            color: #fff;
        }
        .card {
            background-color: #343a40;
        }
        .navbar {
            background-color: #212529;
        }
        .console-area {
            height: 300px;
            background-color: #1e1e1e;
            color: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            overflow-y: auto;
        }
        .command-input {
            background-color: #1e1e1e;
            border: 1px solid #ccc;
            color: #fff;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/socket.io@4.0.0/dist/socket.io.js"></script>
    <script>
        let socket;

        function initSocket() {
            socket = io("ws://192.168.100.51:5000"); // WebSocket for Python API

            // Listen for console output
            socket.on('console_output', function(data) {
                const consoleArea = document.getElementById('console');
                consoleArea.value += data + "\n";
                consoleArea.scrollTop = consoleArea.scrollHeight; // Auto scroll to bottom
            });

            // Send command to container
            function sendCommand() {
                const command = document.getElementById('commandInput').value;
                if (command) {
                    socket.emit('send_command', {
                        container_id: "<?= $containerId ?>",
                        command: command
                    });
                    document.getElementById('commandInput').value = ''; // Clear input field
                }
            }

            // Handle enter key for command input
            document.getElementById('commandInput').addEventListener('keyup', function(event) {
                if (event.keyCode === 13) {
                    sendCommand();
                }
            });
        }

        // Start container with flags
        function startContainer() {
            const flags = document.getElementById('startupFlags').value;
            fetch(`http://192.168.100.51:5000/start-container`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    container_id: "<?= $containerId ?>",
                    flags: flags
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                location.reload(); // Refresh the page to get updated status
            })
            .catch(error => console.error('Error:', error));
        }

        // Stop container
        function stopContainer() {
            fetch(`http://192.168.100.51:5000/stop-container`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ container_id: "<?= $containerId ?>" })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                location.reload(); // Refresh the page to get updated status
            })
            .catch(error => console.error('Error:', error));
        }

        window.onload = initSocket;
    </script>
</head>
<body>
    <div class="container mt-4">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Container Management</a>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="server.php?container_id=<?= urlencode($containerId) ?>">
                                <i class="fas fa-console"></i> Console
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="file-manager.php?container_id=<?= urlencode($containerId) ?>">
                                <i class="fas fa-folder"></i> File Manager
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <h1 class="text-center">Managing Container: <?= htmlspecialchars($container['container_name'] ?? 'Unknown') ?></h1>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Container Status</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> <?= htmlspecialchars($status['status'] ?? 'Unknown') ?></p>
                        <p><strong>CPU:</strong> <?= htmlspecialchars($container['cpu'] ?? 'N/A') ?> cores</p>
                        <p><strong>RAM:</strong> <?= htmlspecialchars($container['ram'] ?? 'N/A') ?> MB</p>
                        <p><strong>Storage:</strong> <?= htmlspecialchars($container['storage'] ?? 'N/A') ?> GB</p>
                        <div class="mt-3">
                            <input type="text" id="startupFlags" class="form-control" placeholder="Enter startup flags here">
                            <button class="btn btn-success mt-2" onclick="startContainer()">Start Container</button>
                            <button class="btn btn-danger mt-2" onclick="stopContainer()">Stop Container</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Console Output</h5>
                    </div>
                    <div class="card-body">
                        <div class="console-area" id="console" readonly></div>
                        <input type="text" id="commandInput" class="form-control command-input mt-3" placeholder="Type command here and press Enter">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.0.0/socket.io.min.js"></script>

</body>
</html>
