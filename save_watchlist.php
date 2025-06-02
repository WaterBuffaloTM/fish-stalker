<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Log received data
    error_log("Received data: " . print_r($data, true));
    
    if (!isset($data['email']) || !isset($data['instagram_link']) || !isset($data['name']) || 
        !isset($data['boat_type']) || !isset($data['city']) || !isset($data['region'])) {
        error_log("Missing required fields. Data received: " . print_r($data, true));
        throw new Exception('Missing required fields');
    }

    $stmt = $pdo->prepare("INSERT INTO watchlist (email, instagram_link, name, boat_type, city, region) 
                          VALUES (:email, :instagram_link, :name, :boat_type, :city, :region)");
    
    $params = [
        'email' => $data['email'],
        'instagram_link' => $data['instagram_link'],
        'name' => $data['name'],
        'boat_type' => $data['boat_type'],
        'city' => $data['city'],
        'region' => $data['region']
    ];
    
    // Log parameters
    error_log("SQL Parameters: " . print_r($params, true));
    
    $stmt->execute($params);
    
    // Log success
    error_log("Successfully inserted record with ID: " . $pdo->lastInsertId());

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error in save_watchlist.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 