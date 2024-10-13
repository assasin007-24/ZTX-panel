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
$usernameOrEmail = $_SESSION['username']; 
$query = $userDb->prepare('SELECT * FROM users WHERE (username = :usernameOrEmail OR email = :usernameOrEmail)');
$query->bindValue(':usernameOrEmail', $usernameOrEmail, SQLITE3_TEXT);
$result = $query->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

// Check if the user is an admin
$isAdmin = ($user['admin'] === 'yes');

if (!$isAdmin) {
    die("You are not authorized to manage containers.");
}

// Connect to the SQLite database for containers
$containerDb = new SQLite3(__DIR__ . '/database/containers.db');

// Fetch all containers
$query = 'SELECT * FROM containers';
$containers = $containerDb->query($query);

// Handle container deletion
if (isset($_POST['deleteContainer'])) {
    $containerId = $_POST['container_id'];
    
    // Delete container from the database
    $stmt = $containerDb->prepare('DELETE FROM containers WHERE container_id = :container_id');
    $stmt->bindValue(':container_id', $containerId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Here you can also add the code to stop and remove the container using the Python backend
    $ch = curl_init("http://localhost:5000/delete-container");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'container_id' => $containerId
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    header('Location: manage_containers.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Containers - ZTX Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .btn-delete {
            background-color: red;
            color: white;
        }

        .btn-manage {
            background-color: lime;
            color: black;
        }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="container mt-5">
        <h1>Manage Containers</h1>
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Container Name</th>
                    <th>Email</th>
                    <th>CPU</th>
                    <th>RAM</th>
                    <th>Storage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    <?php while ($container = $containers->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><?php echo htmlspecialchars($container['container_id'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($container['container_name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($container['email'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($container['cpu'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($container['ram'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($container['storage'] ?? ''); ?></td>
            <td>
                <form method="POST" action="" style="display:inline;">
                    <input type="hidden" name="container_id" value="<?php echo htmlspecialchars($container['container_id'] ?? ''); ?>">
                    <button type="submit" name="deleteContainer" class="btn btn-delete">Delete Container</button>
                </form>
                <a href="server.php?container_id=<?php echo htmlspecialchars($container['container_id'] ?? ''); ?>" class="btn btn-manage">Go to server</a>
            </td>
        </tr>
    <?php endwhile; ?>
</tbody>

        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
