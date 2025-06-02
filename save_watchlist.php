<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO watchlist (email, phone, name, instagram_link, boat_type, city, region) 
                              VALUES (:email, :phone, :name, :instagram_link, :boat_type, :city, :region)");
        
        $stmt->execute([
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':name' => $data['name'],
            ':instagram_link' => $data['instagramLink'],
            ':boat_type' => $data['boatType'],
            ':city' => $data['city'],
            ':region' => $data['region']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Entry saved successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error saving entry: ' . $e->getMessage()]);
    }
}
?> 