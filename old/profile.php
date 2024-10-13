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
$username = $_SESSION['username'];
$query = $db->prepare('SELECT * FROM users WHERE username = :username');
$query->bindValue(':username', $username, SQLITE3_TEXT);
$result = $query->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newUsername = $_POST['username'];
    $newEmail = $_POST['email'];
    $newPassword = $_POST['password'];

    // Update user in the database
    $updateQuery = $db->prepare('UPDATE users SET username = :username, email = :email, password = :password WHERE id = :id');
    $updateQuery->bindValue(':username', $newUsername, SQLITE3_TEXT);
    $updateQuery->bindValue(':email', $newEmail, SQLITE3_TEXT);
    $updateQuery->bindValue(':password', $newPassword, SQLITE3_TEXT);
    $updateQuery->bindValue(':id', $user['id'], SQLITE3_INTEGER);
    $updateQuery->execute();

    // Logout and redirect to login page
    session_destroy(); // Log the user out
    header('Location: login.php'); // Redirect to login after update
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ZTX Panel</title>
    <link rel="icon" href="images/ztx.webp" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
        }
        .container {
            margin-top: 20px;
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
        <h2>Profile Settings</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <small class="form-text text-muted">Leave blank if you don't want to change the password.</small>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
