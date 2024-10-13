<?php
// Include database connection file
include 'database/bridge.db'; // Adjust the path as necessary

// Check if the bridge ID is provided
if (isset($_GET['id'])) {
    $bridgeId = intval($_GET['id']); // Sanitize input

    // Prepare and execute the SQL statement to fetch bridge details
    $stmt = $pdo->prepare("SELECT * FROM bridges WHERE id = :id");
    $stmt->bindParam(':id', $bridgeId, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch the bridge data
    $bridge = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bridge) {
        // Return the bridge details as JSON
        echo json_encode($bridge);
    } else {
        // Return an error message if the bridge is not found
        echo json_encode(['error' => 'Bridge not found.']);
    }
} else {
    // Return an error message if the ID is not provided
    echo json_encode(['error' => 'No bridge ID provided.']);
}
?>
