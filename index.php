<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fish Stalker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Fish Stalker</h1>
        
        <!-- Email Login -->
        <div class="section">
            <h2>Enter Your Email</h2>
            <form id="emailForm">
                <input type="email" id="email" placeholder="Your Email" required>
                <button type="submit">Continue</button>
            </form>
        </div>

        <!-- Add Instagram Links (hidden until email is entered) -->
        <div class="section" id="watchlistSection" style="display: none;">
            <h2>Add Captain to Watchlist</h2>
            <form id="watchlistForm">
                <input type="text" id="name" placeholder="Captain Name" required>
                <input type="text" id="instagramLink" placeholder="Instagram Profile URL" required>
                <input type="text" id="boatType" placeholder="Boat Type" required>
                <input type="text" id="city" placeholder="City" required>
                <input type="text" id="region" placeholder="Region" required>
                <button type="submit">Add to Watchlist</button>
            </form>
        </div>

        <!-- Watchlist Display -->
        <div class="section" id="watchlistDisplay" style="display: none;">
            <h2>Your Watchlist</h2>
            <div id="watchlistResults"></div>
            <button id="generateReportBtn" style="display: none;">Generate Fishing Report</button>
        </div>

        <!-- Report Display -->
        <div class="section" id="reportSection" style="display: none;">
            <h2>Fishing Report</h2>
            <div id="reportsList"></div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html> 