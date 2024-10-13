<?php
// api_management.php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

// Connect to the SQLite database
$db = new SQLite3(__DIR__ . '/database/users.db');

// Retrieve user information
$usernameOrEmail = $_SESSION['username']; // Ensure this is correct
$query = $db->prepare('SELECT * FROM users WHERE (username = :usernameOrEmail OR email = :usernameOrEmail)');
$query->bindValue(':usernameOrEmail', $usernameOrEmail, SQLITE3_TEXT);
$result = $query->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

// Debugging output
if (!$user) {
    echo "No user found for: $usernameOrEmail";
    exit; // Stop execution to prevent further errors
}

// Check if the user is an admin
$isAdmin = ($user['admin'] === 'yes'); // Adjust this line based on your actual column name and expected value

if (!$isAdmin) {
    header('Location: wrongpath.php'); // Redirect to wrongpath.php if not an admin
    exit;
}

// Use absolute paths for the database files
$bridgesDbPath = __DIR__ . '/database/bridges.db';
$apiDbPath = __DIR__ . '/database/api.db';

// Connect to API database
try {
    $pdoApi = new PDO("sqlite:$apiDbPath");
} catch (PDOException $e) {
    echo "Error connecting to API database: " . $e->getMessage();
    exit();
}

// Fetch existing API keys
$apiKeys = $pdoApi->query("SELECT * FROM api_keys")->fetchAll(PDO::FETCH_ASSOC);

// Generate a unique API key
function generateApiKey() {
    return bin2hex(random_bytes(16)); // Generates a random 32-character API key
}

// Handle API key generation
if (isset($_POST['generateApiKey'])) {
    $newApiKey = generateApiKey();
    $stmt = $pdoApi->prepare("INSERT INTO api_keys (api_key) VALUES (:api_key)");
    $stmt->execute([':api_key' => $newApiKey]);
    header("Location: api_management.php"); // Refresh the page to see new key
    exit();
}

// Handle API key deletion
if (isset($_POST['deleteApiKey'])) {
    $apiKeyId = $_POST['api_key_id'];
    $stmt = $pdoApi->prepare("DELETE FROM api_keys WHERE id = :id");
    $stmt->execute([':id' => $apiKeyId]);
    header("Location: api_management.php"); // Refresh the page to see updated keys
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Management - ZTX Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div class="container mt-5">
        <h1>API Management</h1>
        <form method="POST" action="">
            <button type="submit" name="generateApiKey" class="btn btn-primary">Generate API Key</button>
        </form>

        <h4 class="mt-4">Existing API Keys</h4>
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>API Key</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apiKeys as $apiKey): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($apiKey['id']); ?></td>
                        <td><?php echo htmlspecialchars($apiKey['api_key']); ?></td>
                        <td><?php echo htmlspecialchars($apiKey['created_at']); ?></td>
                        <td>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="api_key_id" value="<?php echo htmlspecialchars($apiKey['id']); ?>">
                                <button type="submit" name="deleteApiKey" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
