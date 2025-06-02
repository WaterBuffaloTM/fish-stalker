<?php
require_once 'db_config.php';

// Get email from URL parameter
$email = isset($_GET['email']) ? $_GET['email'] : '';

if (empty($email)) {
    die("Please provide an email address");
}

try {
    // Get all entries for this email
    $stmt = $pdo->prepare("SELECT * FROM watchlist WHERE email = ? ORDER BY created_at DESC");
    $stmt->execute([$email]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Fishing Watchlist - Fish Stalker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>My Fishing Watchlist</h1>
        
        <?php if (empty($entries)): ?>
            <div class="no-entries">
                <p>You haven't added any boats to your watchlist yet.</p>
                <a href="index.html" class="add-boat-btn">Add Your First Boat</a>
            </div>
        <?php else: ?>
            <div class="watchlist-summary">
                <p>You're watching <?php echo count($entries); ?> boat<?php echo count($entries) > 1 ? 's' : ''; ?></p>
            </div>
            
            <div class="watchlist-entries">
                <?php foreach ($entries as $entry): ?>
                    <div class="entry-card">
                        <div class="boat-info">
                            <h3><?php echo htmlspecialchars($entry['name']); ?></h3>
                            <p class="boat-type"><?php echo htmlspecialchars($entry['boat_type']); ?></p>
                        </div>
                        
                        <div class="location-info">
                            <p><i class="location-icon">📍</i> <?php echo htmlspecialchars($entry['city']); ?>, <?php echo htmlspecialchars($entry['region']); ?></p>
                        </div>
                        
                        <div class="social-info">
                            <a href="<?php echo htmlspecialchars($entry['instagram_link']); ?>" target="_blank" class="instagram-link">
                                <i class="instagram-icon">📸</i> @<?php echo htmlspecialchars($entry['instagram_handle']); ?>
                            </a>
                        </div>
                        
                        <div class="entry-footer">
                            <span class="added-date">Added: <?php echo date('F j, Y', strtotime($entry['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="index.html" class="back-btn">Back to Home</a>
            <a href="index.html" class="add-btn">Add Another Boat</a>
        </div>
    </div>
</body>
</html> 