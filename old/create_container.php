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

// Connect to the SQLite database for users
$userDb = new SQLite3(__DIR__ . '/database/users.db');

// Retrieve user information
$usernameOrEmail = $_SESSION['username']; // Ensure this is correct
$query = $userDb->prepare('SELECT * FROM users WHERE (username = :usernameOrEmail OR email = :usernameOrEmail)');
$query->bindValue(':usernameOrEmail', $usernameOrEmail, SQLITE3_TEXT);
$result = $query->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

// Check if the user is an admin
$isAdmin = ($user['admin'] === 'yes');

if (!$isAdmin) {
    die("You are not authorized to create containers.");
}

// Load available images from the JSON configuration
$images = json_decode(file_get_contents('images.json'), true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form input data
    $containerName = trim($_POST['container_name']);
    $email = trim($_POST['email']);
    $cpu = (int)$_POST['cpu'];
    $ram = (int)$_POST['ram'];
    $storage = (int)$_POST['storage'];
    $selectedImage = $_POST['image']; // Get the selected image
    $port = rand(10000, 60000); // Random port assignment (adjust as needed)

    // Validate input
    if (empty($containerName) || empty($email) || empty($selectedImage) || $cpu <= 0 || $ram <= 0 || $storage <= 0) {
        echo "All fields are required and must be valid!";
    } else {
        // Generate a unique container ID
        $containerId = bin2hex(random_bytes(16));

        // Create a directory for the container
        $containerDir = __DIR__ . "/servers/$containerId";
        if (!mkdir($containerDir, 0755, true) && !is_dir($containerDir)) {
            die("Failed to create directories...");
        }

        // Connect to the SQLite database for containers
        $containerDb = new SQLite3(__DIR__ . '/database/containers.db');

        // Insert container data into the database
        $stmt = $containerDb->prepare('INSERT INTO containers (container_id, container_name, email, cpu, ram, storage, port, image, directory) VALUES (:container_id, :container_name, :email, :cpu, :ram, :storage, :port, :image, :directory)');
        $stmt->bindValue(':container_id', $containerId, SQLITE3_TEXT);
        $stmt->bindValue(':container_name', $containerName, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':cpu', $cpu, SQLITE3_INTEGER);
        $stmt->bindValue(':ram', $ram, SQLITE3_INTEGER);
        $stmt->bindValue(':storage', $storage, SQLITE3_INTEGER);
        $stmt->bindValue(':port', $port, SQLITE3_INTEGER); // Bind the port value
        $stmt->bindValue(':image', $selectedImage, SQLITE3_TEXT); // Save the selected image
        $stmt->bindValue(':directory', $containerDir, SQLITE3_TEXT); // Save the directory path

        if (!$stmt->execute()) {
            echo "Error saving container data: " . $containerDb->lastErrorMsg();
        } else {
            // Trigger the Python backend to create the Docker container
            $ch = curl_init("http://localhost:5000/create-container");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'container_id' => $containerId,
                'container_name' => $containerName,
                'cpu' => $cpu,
                'ram' => $ram,
                'storage' => $storage,
                'image' => $selectedImage, // Send the selected image
                'port' => $port, // Pass the random port assigned
                'directory' => $containerDir // Pass the container directory
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $response = curl_exec($ch);
            curl_close($ch);

            // Check response from Python API
            $responseData = json_decode($response, true);
            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                echo "Container created successfully!";
            } else {
                echo "Failed to create the container: " . (isset($responseData['message']) ? $responseData['message'] : 'Unknown error.');
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
    <title>Create Container - ZTX Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div class="container mt-5">
        <h1>Create New Container</h1>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="containerName" class="form-label">Container Name</label>
                <input type="text" name="container_name" id="containerName" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">User Email (who will manage the container)</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Select Docker Image</label>
                <select name="image" id="image" class="form-select" required>
                    <option value="">-- Select an Image --</option>
                    <?php foreach ($images as $image): ?>
                        <option value="<?php echo htmlspecialchars($image['name']); ?>">
                            <?php echo htmlspecialchars($image['name']) . " - " . htmlspecialchars($image['description']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="cpu" class="form-label">CPU (Number of Cores)</label>
                <input type="number" name="cpu" id="cpu" class="form-control" required min="1">
            </div>

            <div class="mb-3">
                <label for="ram" class="form-label">RAM (in MB)</label>
                <input type="number" name="ram" id="ram" class="form-control" required min="1">
            </div>

            <div class="mb-3">
                <label for="storage" class="form-label">Storage (in MB)</label>
                <input type="number" name="storage" id="storage" class="form-control" required min="1">
            </div>

            <button type="submit" class="btn btn-primary">Create Container</button>
        </form>
    </div>
</body>
</html>
