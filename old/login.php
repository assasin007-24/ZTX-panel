<?php
session_start();

// Connect to the SQLite database
$db = new SQLite3(__DIR__ . '/database/users.db');

// Handle login logic here (e.g., checking username/password)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usernameOrEmail = $_POST['username_or_email'];
    $password = $_POST['password'];

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $db->prepare('SELECT * FROM users WHERE (username = :usernameOrEmail OR email = :usernameOrEmail)');
    $stmt->bindValue(':usernameOrEmail', $usernameOrEmail, SQLITE3_TEXT);

    // Execute the query
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    // Check if the user exists in the database and verify the password
    if ($user && password_verify($password, $user['password'])) {
        // Password is correct, start session
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $user['username']; // Store username in session
        $_SESSION['admin'] = $user['admin']; // Store admin status if needed
        header('Location: dashboard.php'); // Redirect to dashboard after successful login
        exit;
    } else {
        $error = "Invalid username/email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ZTX Panel</title>
    <link rel="icon" href="images/ztx.webp" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212; /* Dark background */
            color: #ffffff; /* White text */
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Ensure footer stays at the bottom */
        }
        .login-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-card {
            background-color: #1e1e1e; /* Dark card background */
            border-radius: 8px;
            padding: 20px;
            width: 400px; /* Fixed width */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .logo-img {
            max-width: 80%; /* Adjusted width for better centering */
            height: auto; /* Responsive image */
            margin: 0 auto; /* Center the image horizontally */
            display: block; /* Display as block element for centering */
        }
        footer {
            text-align: center;
            padding: 10px;
            background-color: #1e1e1e;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card text-center"> <!-- Centered text -->
            <img src="images/ztx.webp" alt="Logo" class="logo-img mb-4"> <!-- Centered image -->
            <h4>Login</h4>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="username_or_email" class="form-label">Username or Email</label>
                    <input type="text" class="form-control" id="username_or_email" name="username_or_email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>

    <footer>
        &copy; 2024 <a href="https://ztxpanel.netlify.app" class="text-white">ZTX Panel</a>. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
