<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Log received data
    error_log("Received data for removal: " . print_r($data, true));

    if (!isset($data['email']) || !isset($data['instagram_link'])) {
        error_log("Missing required fields for removal. Data received: " . print_r($data, true));
        throw new Exception('Missing required fields');
    }

    $stmt = $pdo->prepare("DELETE FROM watchlist WHERE email = :email AND instagram_link = :instagram_link");
    
    $params = [
        'email' => $data['email'],
        'instagram_link' => $data['instagram_link']
    ];

    // Log parameters
    error_log("SQL Parameters for removal: " . print_r($params, true));

    $result = $stmt->execute($params);

    if ($result === false) {
         error_log("Database execute failed for removal.");
         throw new Exception('Database operation failed.');
    }

    if ($stmt->rowCount() === 0) {
        error_log("No matching record found to delete for email: " . $data['email'] . " and instagram: " . $data['instagram_link']);
        // Decide if this should be an error or success depending on desired behavior
        // Currently throwing an exception as originally written
        throw new Exception('No matching record found to delete');
    }

    error_log("Successfully deleted record for email: " . $data['email'] . " and instagram: " . $data['instagram_link']);
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Database error in remove_watchlist.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in remove_watchlist.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 