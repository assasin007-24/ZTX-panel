<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

// Connect to the SQLite database for users
$db = new SQLite3(__DIR__ . '/database/users.db');

// Retrieve user information
$usernameOrEmail = $_SESSION['username'];
$query = $db->prepare('SELECT * FROM users WHERE (username = :usernameOrEmail OR email = :usernameOrEmail)');
$query->bindValue(':usernameOrEmail', $usernameOrEmail, SQLITE3_TEXT);
$result = $query->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

// Check if the user is an admin
$isAdmin = ($user['admin'] === 'yes');
if (!$isAdmin) {
    header('Location: wrongpath.php'); // Redirect to wrongpath.php if not an admin
    exit;
}

// Connect to the SQLite database for bridges
$bridgeDb = new SQLite3(__DIR__ . '/database/bridge.db');

// Function to validate FQDN or IP address
function isValidFQDN($fqdn) {
    return filter_var($fqdn, FILTER_VALIDATE_IP) || 
           (filter_var($fqdn, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && preg_match('/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $fqdn));
}

// Handle bridge creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createBridge'])) {
    $fqdn = trim($_POST['fqdn']);
    $use_ssl = $_POST['use_ssl'];
    $memory = $_POST['memory'];
    $storage = $_POST['storage'];
    $cpu = $_POST['cpu'];
    $location = $_POST['location'];

    // Validate FQDN
    if (!isValidFQDN($fqdn)) {
        $errorMessage = "Invalid FQDN. Please enter a valid IP address or domain.";
    } else {
        // Save bridge info to bridge.db
        $stmt = $bridgeDb->prepare("INSERT INTO bridges (fqdn, use_ssl, memory, storage, cpu, location) VALUES (:fqdn, :use_ssl, :memory, :storage, :cpu, :location)");
        $stmt->bindValue(':fqdn', $fqdn, SQLITE3_TEXT);
        $stmt->bindValue(':use_ssl', $use_ssl, SQLITE3_INTEGER);
        $stmt->bindValue(':memory', $memory, SQLITE3_INTEGER);
        $stmt->bindValue(':storage', $storage, SQLITE3_INTEGER);
        $stmt->bindValue(':cpu', $cpu, SQLITE3_INTEGER);
        $stmt->bindValue(':location', $location, SQLITE3_TEXT);
        $stmt->execute();

        $successMessage = "Bridge created successfully.";
    }
}

// Handle bridge deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $bridgeId = $_GET['delete'];
    $deleteStmt = $bridgeDb->prepare("DELETE FROM bridges WHERE id = :id");
    $deleteStmt->bindValue(':id', $bridgeId, SQLITE3_INTEGER);
    $deleteStmt->execute();
    $successMessage = "Bridge deleted successfully.";
}

// Handle bridge editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editBridge'])) {
    $bridgeId = $_POST['id'];
    $fqdn = trim($_POST['fqdn']);
    $use_ssl = $_POST['use_ssl'];
    $memory = $_POST['memory'];
    $storage = $_POST['storage'];
    $cpu = $_POST['cpu'];
    $location = $_POST['location'];

    // Validate FQDN
    if (!isValidFQDN($fqdn)) {
        $errorMessage = "Invalid FQDN. Please enter a valid IP address or domain.";
    } else {
        // Update bridge info in bridge.db
        $updateStmt = $bridgeDb->prepare("UPDATE bridges SET fqdn = :fqdn, use_ssl = :use_ssl, memory = :memory, storage = :storage, cpu = :cpu, location = :location WHERE id = :id");
        $updateStmt->bindValue(':fqdn', $fqdn, SQLITE3_TEXT);
        $updateStmt->bindValue(':use_ssl', $use_ssl, SQLITE3_INTEGER);
        $updateStmt->bindValue(':memory', $memory, SQLITE3_INTEGER);
        $updateStmt->bindValue(':storage', $storage, SQLITE3_INTEGER);
        $updateStmt->bindValue(':cpu', $cpu, SQLITE3_INTEGER);
        $updateStmt->bindValue(':location', $location, SQLITE3_TEXT);
        $updateStmt->bindValue(':id', $bridgeId, SQLITE3_INTEGER);
        $updateStmt->execute();
        $successMessage = "Bridge updated successfully.";
    }
}

// Retrieve all existing bridges
$bridgesQuery = 'SELECT * FROM bridges';
$bridgesResult = $bridgeDb->query($bridgesQuery);
$bridges = [];

// Retrieve all locations for bridge creation
$locationsQuery = 'SELECT * FROM locations'; // Ensure you have a locations table
$locationsResult = $bridgeDb->query($locationsQuery);
$locations = [];

while ($location = $locationsResult->fetchArray(SQLITE3_ASSOC)) {
    $locations[] = $location;
}

while ($bridge = $bridgesResult->fetchArray(SQLITE3_ASSOC)) {
    $bridges[] = $bridge;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bridges - ZTX Panel</title>
    <link rel="icon" href="images/ztx.webp" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            background-color: #121212;
            color: #ffffff;
        }
        .sidebar {
            min-width: 200px;
            background-color: #1e1e1e;
            padding: 20px;
            position: fixed;
            height: 100vh;
        }
        .content {
            margin-left: 220px; /* Space for sidebar */
            padding: 20px;
            width: calc(100% - 220px);
        }
        .sidebar h5 {
            margin-bottom: 10px;
        }
        .sidebar .nav-link {
            color: #ffffff;
            display: flex;
            align-items: center;
            padding: 10px;
            transition: background 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: #343a40;
        }
        .form-container {
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #555;
        }
        /* Additional style for text color */
        th, td {
            color: #ffffff; /* Change table text color to white for visibility */
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-3">
            <img src="images/ztx.webp" alt="ZTX Logo" style="height: 50px;">
            <p>ZTX Panel</p>
        </div>
        <div class="nav-separator">---- Basic Utilities ----</div>
        <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>

        <div class="nav-separator">---- Admin Utilities ----</div>
        <a href="admin.php" class="nav-link"><i class="fas fa-user"></i> Manage Users</a>
        <a href="locations.php" class="nav-link"><i class="fas fa-globe"></i> Locations</a>
        <a href="bridges.php" class="nav-link"><i class="fas fa-server"></i> Bridges</a>
    </div>

    <div class="content">
        <h2>Manage Bridges</h2>
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <h4>Create Bridge</h4>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="fqdn" class="form-label">FQDN (IP/Domain)</label>
                    <input type="text" class="form-control" id="fqdn" name="fqdn" required>
                </div>
                <div class="mb-3">
                    <label for="use_ssl" class="form-label">Use SSL</label>
                    <select class="form-select" id="use_ssl" name="use_ssl" required>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="memory" class="form-label">Memory (MB)</label>
                    <input type="number" class="form-control" id="memory" name="memory" required>
                </div>
                <div class="mb-3">
                    <label for="storage" class="form-label">Storage (GB)</label>
                    <input type="number" class="form-control" id="storage" name="storage" required>
                </div>
                <div class="mb-3">
                    <label for="cpu" class="form-label">CPU Cores</label>
                    <input type="number" class="form-control" id="cpu" name="cpu" required>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <select class="form-select" id="location" name="location" required>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo htmlspecialchars($location['name']); ?>"><?php echo htmlspecialchars($location['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="createBridge" class="btn btn-primary">Create Bridge</button>
            </form>
        </div>

        <h4>Existing Bridges</h4>
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>FQDN</th>
                    <th>Use SSL</th>
                    <th>Memory</th>
                    <th>Storage</th>
                    <th>CPU</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bridges as $bridge): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bridge['id']); ?></td>
                        <td><?php echo htmlspecialchars($bridge['fqdn']); ?></td>
                        <td><?php echo htmlspecialchars($bridge['use_ssl'] ? 'Yes' : 'No'); ?></td>
                        <td><?php echo htmlspecialchars($bridge['memory']); ?></td>
                        <td><?php echo htmlspecialchars($bridge['storage']); ?></td>
                        <td><?php echo htmlspecialchars($bridge['cpu']); ?></td>
                        <td><?php echo htmlspecialchars($bridge['location']); ?></td>
                        <td>
                            <a href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editBridgeModal-<?php echo $bridge['id']; ?>">Edit</a>
                            <a href="?delete=<?php echo $bridge['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this bridge?');">Delete</a>
                        </td>
                    </tr>

                    <!-- Edit Bridge Modal -->
                    <div class="modal fade" id="editBridgeModal-<?php echo $bridge['id']; ?>" tabindex="-1" aria-labelledby="editBridgeLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editBridgeLabel">Edit Bridge</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="id" value="<?php echo $bridge['id']; ?>">
                                        <div class="mb-3">
                                            <label for="fqdn" class="form-label">FQDN (IP/Domain)</label>
                                            <input type="text" class="form-control" id="fqdn" name="fqdn" value="<?php echo htmlspecialchars($bridge['fqdn']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="use_ssl" class="form-label">Use SSL</label>
                                            <select class="form-select" id="use_ssl" name="use_ssl" required>
                                                <option value="1" <?php echo $bridge['use_ssl'] ? 'selected' : ''; ?>>Yes</option>
                                                <option value="0" <?php echo !$bridge['use_ssl'] ? 'selected' : ''; ?>>No</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="memory" class="form-label">Memory (MB)</label>
                                            <input type="number" class="form-control" id="memory" name="memory" value="<?php echo htmlspecialchars($bridge['memory']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="storage" class="form-label">Storage (GB)</label>
                                            <input type="number" class="form-control" id="storage" name="storage" value="<?php echo htmlspecialchars($bridge['storage']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="cpu" class="form-label">CPU Cores</label>
                                            <input type="number" class="form-control" id="cpu" name="cpu" value="<?php echo htmlspecialchars($bridge['cpu']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="location" class="form-label">Location</label>
                                            <select class="form-select" id="location" name="location" required>
                                                <?php foreach ($locations as $location): ?>
                                                    <option value="<?php echo htmlspecialchars($location['name']); ?>" <?php echo $bridge['location'] === $location['name'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($location['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="editBridge" class="btn btn-primary">Update Bridge</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
