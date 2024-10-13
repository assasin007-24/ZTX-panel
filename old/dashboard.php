<?php
session_start();

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
$isAdmin = ($user['admin'] === 'yes');

// Retrieve all users for admin view
if ($isAdmin) {
    $adminQuery = 'SELECT * FROM users WHERE admin = "yes"';
    $adminResult = $db->query($adminQuery);
    $adminUsers = [];
    while ($adminUser = $adminResult->fetchArray(SQLITE3_ASSOC)) {
        $adminUsers[] = $adminUser;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ZTX Panel</title>
    <link rel="icon" href="images/ztx.webp" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
        }
        .navbar {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="https://ztxpanel.netlify.app">
        <img src="images/ztx.webp" alt="ZTX Logo" style="height: 30px; margin-left: 10px; margin-right: 10px;">
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
            <li class="nav-item active">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
            </li>
            <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin.php"><i class="fas fa-cog"></i> Admin Settings</a>
                </li>
            <?php endif; ?>
        </ul>
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="profile.php">Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>
</nav>




    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?></h2>
        <?php if ($isAdmin): ?>
            <h3>Admin Dashboard</h3>
            <h4>Admin Users:</h4>
            <ul>
                <?php foreach ($adminUsers as $adminUser): ?>
                    <li><?php echo htmlspecialchars($adminUser['username']); ?> (<?php echo htmlspecialchars($adminUser['email']); ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <h3>User Dashboard</h3>
            <p>This is your dashboard as a normal user.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
