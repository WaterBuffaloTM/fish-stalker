<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $email = $_GET['email'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM watchlist WHERE email = :email ORDER BY created_at DESC");
        $stmt->execute([':email' => $email]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'reports' => $reports]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error retrieving reports: ' . $e->getMessage()]);
    }
}
?> 