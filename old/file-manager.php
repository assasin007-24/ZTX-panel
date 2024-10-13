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

// Get the container ID and the current directory from the query parameter
if (!isset($_GET['container_id'])) {
    die("Container ID not provided");
}

$containerId = $_GET['container_id'];
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : '';

// Define the base container directory
$baseDir = __DIR__ . "/servers/$containerId";

// Define the current directory to work with
$containerDir = $baseDir . '/' . $currentDir;

// Check if the container directory exists
if (!is_dir($baseDir) || !is_dir($containerDir)) {
    die("Container or directory does not exist.");
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $targetFile = $containerDir . '/' . basename($_FILES['fileToUpload']['name']);
    if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $targetFile)) {
        echo "<script>alert('File uploaded successfully.');</script>";
    } else {
        echo "<script>alert('Error uploading file.');</script>";
    }
}

// Handle file deletion
if (isset($_POST['deleteFile'])) {
    $fileToDelete = $containerDir . '/' . $_POST['filename'];
    if (file_exists($fileToDelete)) {
        if (is_dir($fileToDelete)) {
            rmdir($fileToDelete); // Remove directory if it is a directory
        } else {
            unlink($fileToDelete); // Remove file
        }
        echo "<script>alert('File or directory deleted successfully.');</script>";
    } else {
        echo "<script>alert('File or directory not found.');</script>";
    }
}

// Handle creating a new folder
if (isset($_POST['createFolder'])) {
    $newFolderName = $_POST['folderName'];
    $newFolderPath = $containerDir . '/' . $newFolderName;

    if (!is_dir($newFolderPath)) {
        mkdir($newFolderPath, 0755, true);
        echo "<script>alert('Folder created successfully.');</script>";
    } else {
        echo "<script>alert('Folder already exists.');</script>";
    }
}

// List files in the current directory
$files = scandir($containerDir);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager for Container <?= htmlspecialchars($containerId) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #2f343f;
            color: #ffffff;
        }
        .navbar {
            margin-bottom: 20px;
        }
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #444;
            transition: background-color 0.2s;
        }
        .file-item:hover {
            background-color: #444;
        }
        .file-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">File Manager</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="server.php?container_id=<?= urlencode($containerId) ?>">
                            <i class="fas fa-console"></i> Console
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="file-manager.php?container_id=<?= urlencode($containerId) ?>">
                            <i class="fas fa-folder"></i> File Manager
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1 class="text-center mb-4">File Manager for Container <?= htmlspecialchars($containerId) ?></h1>

        <!-- File upload form -->
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="input-group">
                <input type="file" name="fileToUpload" class="form-control" required>
                <button type="submit" class="btn btn-custom">Upload</button>
            </div>
        </form>

        <!-- Create new folder form -->
        <form method="POST" class="mb-4">
            <div class="input-group">
                <input type="text" name="folderName" class="form-control" placeholder="New Folder Name" required>
                <button type="submit" name="createFolder" class="btn btn-success">Create Folder</button>
            </div>
        </form>

        <!-- List files -->
        <h2 class="mt-4">Files</h2>
        <div class="list-group">
            <?php foreach ($files as $file): ?>
                <?php if ($file !== '.' && $file !== '..'): ?>
                    <div class="file-item">
                        <?php if (is_dir($containerDir . '/' . $file)): ?>
                            <a href="file-manager.php?container_id=<?= urlencode($containerId) ?>&dir=<?= urlencode($currentDir . '/' . $file) ?>" class="text-success">
                                <i class="fas fa-folder file-icon"></i><?= htmlspecialchars($file) ?>
                            </a>
                        <?php else: ?>
                            <span>
                                <i class="fas fa-file file-icon"></i><?= htmlspecialchars($file) ?>
                            </span>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="filename" value="<?= htmlspecialchars($file) ?>">
                            <button type="submit" name="deleteFile" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Back to previous directory link -->
        <?php if ($currentDir): ?>
            <a href="file-manager.php?container_id=<?= urlencode($containerId) ?>&dir=<?= urlencode(dirname($currentDir)) ?>" class="btn btn-secondary mt-3">Back</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    
</body>
</html>
