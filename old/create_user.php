<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || $_SESSION['admin'] !== 'yes') {
    header('Location: wrongpath.php'); // Redirect to wrongpath.php if not an admin
    exit;
}

// Connect to the SQLite database
$db = new SQLite3(__DIR__ . '/database/users.db');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['newUsername']);
    $email = trim($_POST['newEmail']);
    $password = password_hash(trim($_POST['newPassword']), PASSWORD_DEFAULT); // Hash the password
    $isAdmin = isset($_POST['newIsAdmin']) ? 'yes' : 'no'; // Handle admin status

    // Check if the username or email already exists
    $checkQuery = 'SELECT COUNT(*) as count FROM users WHERE username = :username OR email = :email';
    $stmt = $db->prepare($checkQuery);
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row['count'] > 0) {
        $_SESSION['message'] = "Error: Username or email already exists.";
    } else {
        // Insert new user
        $insertQuery = 'INSERT INTO users (username, email, password, admin) VALUES (?, ?, ?, ?)';
        $stmt = $db->prepare($insertQuery);
        $stmt->bindValue(1, $username, SQLITE3_TEXT);
        $stmt->bindValue(2, $email, SQLITE3_TEXT);
        $stmt->bindValue(3, $password, SQLITE3_TEXT); // Store hashed password
        $stmt->bindValue(4, $isAdmin, SQLITE3_TEXT);

        if ($stmt->execute()) {
            $_SESSION['message'] = "User $username created successfully!";
        } else {
            $_SESSION['message'] = "Error creating user: " . $db->lastErrorMsg();
        }
    }

    header('Location: admin.php');
    exit;
}
if ($stmt->execute()) {
    sendDiscordNotification("User $username created successfully!");
    $_SESSION['message'] = "User $username created successfully!";
}

?>
