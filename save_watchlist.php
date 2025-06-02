<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['instagram_link']) || !isset($data['name']) || 
        !isset($data['boat_type']) || !isset($data['city']) || !isset($data['region'])) {
        throw new Exception('Missing required fields');
    }

    $stmt = $pdo->prepare("INSERT INTO watchlist (email, instagram_link, name, boat_type, city, region) 
                          VALUES (:email, :instagram_link, :name, :boat_type, :city, :region)");
    
    $stmt->execute([
        'email' => $data['email'],
        'instagram_link' => $data['instagram_link'],
        'name' => $data['name'],
        'boat_type' => $data['boat_type'],
        'city' => $data['city'],
        'region' => $data['region']
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 