<?php
// Set error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Set maximum execution time to 5 minutes
set_time_limit(300);
ini_set('max_execution_time', 300);

require 'vendor/autoload.php';
require 'config/config.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

header('Content-Type: application/json');

try {
    error_log("Starting report generation process...");
    
    // Get all Instagram links from watchlist
    $stmt = $pdo->prepare("SELECT * FROM watchlist");
    $stmt->execute();
    $captains = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($captains) . " captains in watchlist");

    if (empty($captains)) {
        throw new Exception('No captains found in watchlist');
    }

    // Initialize Apify client with increased timeout settings
    $client = new Client([
        'base_uri' => 'https://api.apify.com/v2/',
        'headers' => [
            'Authorization' => 'Bearer ' . APIFY_API_KEY,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 120,
        'connect_timeout' => 60
    ]);

    // Collect all Instagram URLs
    $instagram_urls = array_map(function($captain) {
        return $captain['instagram_link'];
    }, $captains);

    error_log("Processing Instagram URLs: " . implode(", ", $instagram_urls));

    // Start single Apify actor run for all URLs
    try {
        error_log("Sending request to Apify...");
        $response = $client->post('actor-tasks/oldschoolmatt~fishing-report/run-sync-get-dataset-items', [
            'json' => [
                'directUrls' => $instagram_urls,
                'resultsLimit' => 10
            ]
        ]);
        error_log("Received response from Apify");
    } catch (RequestException $e) {
        error_log("Apify API Error: " . $e->getMessage());
        if ($e->hasResponse()) {
            error_log("Apify Error Response: " . $e->getResponse()->getBody()->getContents());
        }
        throw new Exception('Error accessing Apify API: ' . $e->getMessage());
    }

    $results = json_decode($response->getBody(), true);
    error_log("Apify Results: " . json_encode($results));

    if (empty($results)) {
        throw new Exception('No posts found for any Instagram accounts');
    }

    // Group results by Instagram URL
    $results_by_url = [];
    foreach ($results as $result) {
        $url = $result['inputUrl'];
        if (!isset($results_by_url[$url])) {
            $results_by_url[$url] = [];
        }
        $results_by_url[$url][] = $result;
    }

    $all_reports = [];
    foreach ($captains as $captain) {
        $instagram_url = $captain['instagram_link'];
        $posts = [];

        if (isset($results_by_url[$instagram_url])) {
            foreach ($results_by_url[$instagram_url] as $post) {
                // Download the image and convert to base64
                $imageUrl = $post['displayUrl'] ?? '';
                $imageBase64 = '';
                if (!empty($imageUrl)) {
                    try {
                        $imageContent = file_get_contents($imageUrl);
                        if ($imageContent !== false) {
                            $imageBase64 = base64_encode($imageContent);
                        }
                    } catch (Exception $e) {
                        error_log("Error downloading image: " . $e->getMessage());
                    }
                }

                $posts[] = [
                    'caption' => $post['caption'] ?? '',
                    'timestamp' => $post['timestamp'] ?? '',
                    'likes' => $post['likes'] ?? 0,
                    'comments' => $post['comments'] ?? 0,
                    'imageUrl' => $imageUrl,
                    'imageBase64' => $imageBase64,
                    'location' => $post['locationName'] ?? ''
                ];
            }
        }

        if (empty($posts)) {
            $all_reports[] = [
                'instagram_url' => $instagram_url,
                'error' => 'No posts found for this Instagram account'
            ];
            continue;
        }

        // Call ChatGPT API with image analysis
        error_log("Sending data to ChatGPT for {$captain['name']}...");
        $chatgptClient = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30
        ]);

        // Create a message array that includes both text and image URLs
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a fishing report expert. Analyze these Instagram posts and their images to create detailed, engaging fishing reports. Include information about the catches, fishing conditions, and any notable patterns you observe in both the text and images.'
            ]
        ];

        // Add each post as a separate message with its image
        foreach ($posts as $post) {
            $messageContent = [
                [
                    'type' => 'text',
                    'text' => "Post from {$captain['name']} in {$post['location']}:\nCaption: {$post['caption']}\nTimestamp: {$post['timestamp']}\nLikes: {$post['likes']}\nComments: {$post['comments']}"
                ]
            ];

            // Add image if we have it in base64
            if (!empty($post['imageBase64'])) {
                $messageContent[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => "data:image/jpeg;base64," . $post['imageBase64']
                    ]
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $messageContent
            ];
        }

        // Add the final instruction
        $messages[] = [
            'role' => 'user',
            'content' => "Based on these posts and their images, create a detailed fishing report for {$captain['name']} in {$captain['city']}, {$captain['region']}. Include information about recent catches, fishing conditions, and any notable patterns you observe in both the text and images."
        ];

        error_log("Sending request to ChatGPT with " . count($messages) . " messages...");
        $chatgptResponse = $chatgptClient->post('chat/completions', [
            'json' => [
                'model' => 'gpt-4o',
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.7
            ]
        ]);

        $chatgptData = json_decode($chatgptResponse->getBody(), true);
        error_log("ChatGPT Response: " . json_encode($chatgptData));
        
        if (!isset($chatgptData['choices'][0]['message']['content'])) {
            error_log("ChatGPT API Error Response: " . json_encode($chatgptData));
            throw new Exception('Invalid response from ChatGPT API');
        }
        
        $report = $chatgptData['choices'][0]['message']['content'];
        error_log("Generated report for {$captain['name']}: " . $report);

        $all_reports[] = [
            'instagram_url' => $instagram_url,
            'report' => $report,
            'posts_found' => count($posts),
            'posts' => $posts
        ];
    }

    // Ensure we have a valid response before sending
    if (empty($all_reports)) {
        throw new Exception('No reports were generated');
    }

    $response = [
        'success' => true,
        'message' => 'Reports generated successfully',
        'reports' => $all_reports
    ];

    // Ensure proper JSON encoding
    $json_response = json_encode($response);
    if ($json_response === false) {
        throw new Exception('Error encoding response: ' . json_last_error_msg());
    }

    error_log("Sending successful response");
    echo $json_response;

} catch (Exception $e) {
    error_log("Error in generate_fishing_report.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating report: ' . $e->getMessage(),
        'error_details' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} 