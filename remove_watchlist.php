<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['instagram_link'])) {
        throw new Exception('Missing required fields');
    }

    $stmt = $pdo->prepare("DELETE FROM watchlist WHERE email = :email AND instagram_link = :instagram_link");
    
    $result = $stmt->execute([
        'email' => $data['email'],
        'instagram_link' => $data['instagram_link']
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('No matching record found to delete');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 