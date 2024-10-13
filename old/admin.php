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
$isAdmin = ($user['admin'] === 'yes'); // Adjust this line based on your actual column name and expected value

if (!$isAdmin) {
    header('Location: wrongpath.php'); // Redirect to wrongpath.php if not an admin
    exit;
}

// Retrieve all users for admin view
$adminQuery = 'SELECT * FROM users'; // Fetch all users for admin view
$adminResult = $db->query($adminQuery);
$adminUsers = [];
while ($adminUser = $adminResult->fetchArray(SQLITE3_ASSOC)) {
    $adminUsers[] = $adminUser;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - ZTX Panel</title>
    <link rel="icon" href="images/ztx.webp" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .nav-separator {
            margin: 15px 0;
            border-bottom: 1px solid #555;
            padding: 5px 0;
            text-align: center;
            font-weight: bold;
        }
        .user-card {
            border: 1px solid #555;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #1e1e1e;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-card h5 {
            margin: 0;
        }
        .form-container {
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #555;
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
        <a href="#" class="nav-link" onclick="showManageUsers()"><i class="fas fa-user"></i> Manage Users</a>
        <a href="bridges.php" class="nav-link" onclick=""><i class="fas fa-server"></i> Manage Bridges</a>
        <a href="api_management.php" class="nav-link" onclick=""><i class="fas fa-key"></i> API Management</a>
    </div>


    <div class="content">
        <h2>Admin Panel</h2>
        <div id="userManagement" style="display: none;">
            <h4>User List</h4>
            <div class="user-list">
                <?php foreach ($adminUsers as $adminUser): ?>
                    <div class="user-card">
                        <div>
                            <h5><?php echo htmlspecialchars($adminUser['username']); ?></h5>
                            <p>Email: <?php echo htmlspecialchars($adminUser['email']); ?></p>
                            <p>Status: <?php echo $adminUser['admin'] === 'yes' ? 'Admin' : 'Normal User'; ?></p>
                        </div>
                        <div>
                            <a href="#" class="btn btn-primary btn-sm" onclick="showUserDetails(<?php echo htmlspecialchars(json_encode($adminUser)); ?>)">Edit</a>
                            <a href="delete_user.php?id=<?php echo $adminUser['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-container">
                <h4>Create New User</h4>
                <form id="createUserForm" method="POST" action="create_user.php">
                    <div class="mb-3">
                        <label for="newUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" name="newUsername" required>
                    </div>
                    <div class="mb-3">
                        <label for="newEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" name="newEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" name="newPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="newIsAdmin" class="form-label">Admin Status</label>
                        <select class="form-select" name="newIsAdmin">
                            <option value="no">Normal User</option>
                            <option value="yes">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Create User</button>
                </form>
            </div>
        </div>

        <div id="userDetails">
            <h4>User Details</h4>
            <div id="detailsContainer">
                <p>Select a user from the user list to view and modify their details.</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showManageUsers() {
            $('#userManagement').show(); // Show user management container
            $('#userDetails').hide(); // Hide user details container
        }

        function showUserDetails(user) {
            $('#detailsContainer').html(`
                <h5>Modify User: ${user.username}</h5>
                <form method="POST" action="update_user.php">
                    <input type="hidden" name="user_id" value="${user.id}">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" value="${user.username}" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="${user.email}" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Leave blank if not changing">
                    </div>
                    <div class="mb-3">
                        <label for="isAdmin" class="form-label">Admin Status</label>
                        <select class="form-select" name="isAdmin">
                            <option value="no" ${user.admin === 'no' ? 'selected' : ''}>Normal User</option>
                            <option value="yes" ${user.admin === 'yes' ? 'selected' : ''}>Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </form>
            `);
            $('#userManagement').hide(); // Hide user management
            $('#userDetails').show(); // Show user details
        }
    </script>
</body>
</html>
