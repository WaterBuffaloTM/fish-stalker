<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    error_log("get_reports.php called.");

    if (!isset($_GET['email']) || empty($_GET['email'])) {
        error_log("Email parameter is missing or empty.");
        throw new Exception('Email parameter is required');
    }

    $email = $_GET['email'];
    error_log("Fetching fishing reports for email: " . $email);

    // Fetch the most recent reports for the user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            email,
            report_content,
            created_at
        FROM fishing_reports 
        WHERE email = :email 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute(['email' => $email]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($reports) . " reports for email: " . $email);

    // Format the reports for the response
    $formattedReports = array_map(function($report) {
        return [
            'id' => $report['id'],
            'content' => $report['report_content'],
            'created_at' => $report['created_at']
        ];
    }, $reports);

    echo json_encode([
        'success' => true,
        'reports' => $formattedReports
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    error_log("Database error in get_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while fetching reports'
    ]);
} catch (Exception $e) {
    error_log("Error in get_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 