<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

// Connect to the SQLite database
$db = new SQLite3(__DIR__ . '/database/users.db');

// Check if form data is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $userId = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password']; // Can be empty if not changing
    $isAdmin = $_POST['isAdmin'] === '1' ? 'yes' : 'no'; // Convert to 'yes'/'no'

    // Prepare the update query
    $updateQuery = $db->prepare('UPDATE users SET username = :username, email = :email, admin = :admin' . ($password ? ', password = :password' : '') . ' WHERE id = :id');

    // Bind values
    $updateQuery->bindValue(':username', $username, SQLITE3_TEXT);
    $updateQuery->bindValue(':email', $email, SQLITE3_TEXT);
    $updateQuery->bindValue(':admin', $isAdmin, SQLITE3_TEXT);
    $updateQuery->bindValue(':id', $userId, SQLITE3_INTEGER);

    // Bind password only if it's provided
    if ($password) {
        // You should hash the password before storing it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateQuery->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
    }

    // Execute the update query
    if ($updateQuery->execute()) {
        // Check if the updated user is the logged-in user
        if ($_SESSION['user_id'] == $userId) {
            // Unset the session and redirect to login
            session_unset(); // Remove all session variables
            session_destroy(); // Destroy the session
            header('Location: login.php?message=updated'); // Redirect to login page
            exit;
        } else {
            // Set success message
            $_SESSION['message'] = "User details updated successfully.";
            header('Location: admin.php'); // Redirect to admin page
            exit;
        }
    } else {
        // Set error message
        $_SESSION['message'] = "Error updating user: " . $db->lastErrorMsg();
        header('Location: admin.php'); // Redirect to admin page
        exit;
    }
} else {
    // Redirect if the request method is not POST
    header('Location: admin.php');
    exit;
}
if ($stmt->execute()) {
    sendDiscordNotification("User $username updated successfully!");
    $_SESSION['message'] = "User $username updated successfully!";
}

?>
