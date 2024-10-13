<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || $_SESSION['admin'] !== 'yes') {
    header('Location: wrongpath.php'); // Redirect to wrongpath.php if not an admin
    exit;
}

// Connect to the SQLite database
$db = new SQLite3(__DIR__ . '/database/bridge.db');

// Check if form was submitted for adding a location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['locationName'])) {
    $locationName = $_POST['locationName'];
    
    // Insert new location
    $insertQuery = 'INSERT INTO locations (name) VALUES (:name)';
    $stmt = $db->prepare($insertQuery);
    $stmt->bindValue(':name', $locationName, SQLITE3_TEXT);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Location '$locationName' added successfully!";
    } else {
        $_SESSION['message'] = "Error adding location: " . $db->lastErrorMsg();
    }
    header('Location: locations.php');
    exit;
}

// Fetch all locations
$locationsQuery = 'SELECT * FROM locations';
$locationsResult = $db->query($locationsQuery);
$locations = [];
while ($location = $locationsResult->fetchArray(SQLITE3_ASSOC)) {
    $locations[] = $location;
}

// Check for any session messages
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Locations - ZTX Panel</title>
    <link rel="icon" href="images/ztx.webp" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
        }
        .container {
            margin-top: 20px;
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 5px;
        }
        .location-card {
            background-color: #2a2a2a;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-danger {
            margin-left: 10px;
        }
        .navbar {
            background-color: #1e1e1e;
        }
        .nav-link {
            color: #ffffff !important;
        }
        .sidebar {
            background-color: #1e1e1e;
            padding: 20px;
            height: 100%;
            position: fixed;
            top: 0;
            left: 0;
            width: 200px;
            overflow-y: auto;
        }
        .sidebar a {
            color: #ffffff;
            text-decoration: none;
        }
        .sidebar a:hover {
            text-decoration: underline;
        }
        .content {
            margin-left: 220px; /* Leave space for sidebar */
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>ZTX Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="admin.php">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="locations.php">Manage Locations</a>
            </li>
            <!-- Add more links as needed -->
        </ul>
    </div>

    <div class="content">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Admin Panel</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </nav>

        <div class="container">
            <h2>Manage Locations</h2>
            <?php if ($message): ?>
                <div class="alert alert-info" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="locations.php" class="mb-4">
                <div class="mb-3">
                    <label for="locationName" class="form-label">New Location Name</label>
                    <input type="text" class="form-control" id="locationName" name="locationName" required>
                </div>
                <button type="submit" class="btn btn-success">Add Location</button>
            </form>

            <h4>Existing Locations</h4>
            <?php foreach ($locations as $location): ?>
                <div class="location-card">
                    <span><?php echo htmlspecialchars($location['name']); ?></span>
                    <a href="delete_location.php?id=<?php echo $location['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this location?');">Delete</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
