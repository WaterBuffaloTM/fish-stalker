document.addEventListener('DOMContentLoaded', function() {
    const emailForm = document.getElementById('emailForm');
    const watchlistForm = document.getElementById('watchlistForm');
    const watchlistSection = document.getElementById('watchlistSection');
    const watchlistDisplay = document.getElementById('watchlistDisplay');
    const watchlistResults = document.getElementById('watchlistResults');
    const generateReportBtn = document.getElementById('generateReportBtn');
    const reportSection = document.getElementById('fishingReport');
    
    let currentEmail = '';

    // Email Form Submit
    emailForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        currentEmail = document.getElementById('email').value;
        
        // Show watchlist section
        watchlistSection.style.display = 'block';
        watchlistDisplay.style.display = 'block';
        
        // Load existing watchlist
        await loadWatchlist();
    });

    // Add to Watchlist
    watchlistForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = {
            email: currentEmail,
            name: document.getElementById('name').value,
            instagram_link: document.getElementById('instagramLink').value,
            boat_type: document.getElementById('boatType').value,
            city: document.getElementById('city').value,
            region: document.getElementById('region').value
        };

        try {
            const response = await fetch('save_watchlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            if (data.success) {
                watchlistForm.reset();
                await loadWatchlist();
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while saving to watchlist.');
        }
    });

    // Load Watchlist
    async function loadWatchlist() {
        try {
            const response = await fetch(`get_reports.php?email=${encodeURIComponent(currentEmail)}`);
            const data = await response.json();
            
            if (data.success) {
                displayWatchlist(data.captains);
            } else {
                watchlistResults.innerHTML = `<p>${data.message}</p>`;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while loading the watchlist.');
        }
    }

    // Display Watchlist
    function displayWatchlist(captains) {
        if (!captains || captains.length === 0) {
            watchlistResults.innerHTML = '<p>No captains in your watchlist. Add some above!</p>';
            generateReportBtn.style.display = 'none';
            return;
        }

        const html = captains.map(captain => `
            <div class="captain-card">
                <h3>${captain.name}</h3>
                <p>Instagram: <a href="${captain.instagram_link}" target="_blank">${captain.instagram_link}</a></p>
                <p>Boat Type: ${captain.boat_type}</p>
                <p>Location: ${captain.city}, ${captain.region}</p>
                <button onclick="removeFromWatchlist('${captain.instagram_link}')" class="remove-btn">Remove</button>
            </div>
        `).join('');

        watchlistResults.innerHTML = html;
        generateReportBtn.style.display = 'block';
    }

    // Generate Report
    generateReportBtn.addEventListener('click', async function() {
        reportSection.innerHTML = '<p>Generating fishing report...</p>';
        reportSection.style.display = 'block';

        try {
            const response = await fetch('generate_fishing_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: currentEmail })
            });

            const data = await response.json();
            
            if (data.success) {
                const reportsHtml = data.reports.map(report => `
                    <div class="report-section">
                        <h3>Report for ${report.instagram_url}</h3>
                        <pre>${report.report || report.error}</pre>
                    </div>
                `).join('');
                
                reportSection.innerHTML = reportsHtml;
            } else {
                reportSection.innerHTML = `<p>Error: ${data.message}</p>`;
            }
        } catch (error) {
            console.error('Error:', error);
            reportSection.innerHTML = '<p>An error occurred while generating the fishing report.</p>';
        }
    });
});

// Remove from Watchlist
async function removeFromWatchlist(instagramLink) {
    if (!confirm('Remove this captain from your watchlist?')) return;
    
    try {
        const response = await fetch('remove_watchlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: document.getElementById('email').value,
                instagram_link: instagramLink
            })
        });

        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while removing from watchlist.');
    }
} 