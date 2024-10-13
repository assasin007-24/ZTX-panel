<?php
// Connect to the SQLite database
$db = new SQLite3(__DIR__ . '/database/bridge.db');

// Check if an ID is provided
if (isset($_GET['id'])) {
    $locationId = $_GET['id'];
    
    // Prepare the DELETE statement
    $stmt = $db->prepare("DELETE FROM locations WHERE id = :id");
    
    if ($stmt) {
        $stmt->bindValue(':id', $locationId, SQLITE3_INTEGER);
        $stmt->execute();
        // Redirect or output success message
        header("Location: locations.php?success=Location deleted successfully.");
        exit;
    } else {
        // Handle the case where the statement preparation fails
        echo "Error preparing statement: " . $db->lastErrorMsg();
    }
} else {
    // Handle the case where no ID is provided
    echo "No location ID specified.";
}
