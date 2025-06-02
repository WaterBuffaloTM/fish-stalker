<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    if (!isset($_GET['email'])) {
        throw new Exception('Email parameter is required');
    }

    $stmt = $pdo->prepare("SELECT name, instagram_link, boat_type, city, region FROM watchlist WHERE email = ?");
    $stmt->execute([$_GET['email']]);
    $captains = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'captains' => $captains
    ]);
} catch (Exception $e) {
    error_log("Error in get_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 