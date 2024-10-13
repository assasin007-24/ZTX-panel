<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || (isset($_SESSION['admin']) && $_SESSION['admin'] !== 'yes')) {
    header('Location: wrongpath.php'); // Redirect to wrongpath.php if not an admin
    exit;
}

// Connect to the SQLite database
$db = new SQLite3(__DIR__ . '/database/users.db');

// Check if user ID is provided
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Check if the user exists in the database before attempting to delete
    $checkQuery = 'SELECT * FROM users WHERE id = ?';
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $user = $checkStmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($user) {
        // Delete user from database
        $deleteQuery = 'DELETE FROM users WHERE id = ?';
        $stmt = $db->prepare($deleteQuery);
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);

        if ($stmt->execute()) {
            $_SESSION['message'] = "User deleted successfully!";
        } else {
            $_SESSION['message'] = "Error deleting user: " . $db->lastErrorMsg();
        }
    } else {
        $_SESSION['message'] = "User not found.";
    }
} else {
    $_SESSION['message'] = "User ID not specified.";
}

// Redirect back to admin page
header('Location: admin.php');
exit;
if ($stmt->execute()) {
    sendDiscordNotification("User with ID $userId deleted successfully!");
    $_SESSION['message'] = "User deleted successfully!";
}

?>
