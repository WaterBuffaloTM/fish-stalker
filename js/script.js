document.addEventListener('DOMContentLoaded', function() {
    const emailForm = document.getElementById('emailForm');
    const emailSection = document.getElementById('emailSection');
    const watchlistSection = document.getElementById('watchlistSection');
    const watchlistDisplay = document.getElementById('watchlistDisplay');
    const reportSection = document.getElementById('reportSection');
    const watchlistForm = document.getElementById('watchlistForm');
    const greetingHeader = document.getElementById('greetingHeader');
    const addToWatchlistBtn = document.getElementById('addToWatchlistBtn');
    let currentUserEmail = '';

    // Hide Add Captain to Watchlist section by default
    if (watchlistSection) watchlistSection.style.display = 'none';

    // Main top buttons logic
    const genReportBtn = document.getElementById('generateReportBtnMain');
    const genWatchlistReportBtn = document.getElementById('generateWatchlistReportBtn');
    const viewPastReportsBtn = document.getElementById('viewPastReportsBtnMain');

    if (genReportBtn) {
        genReportBtn.onclick = function() {
            alert('This feature will be available soon!');
        };
    }
    if (addToWatchlistBtn) {
        addToWatchlistBtn.onclick = function() {
            if (watchlistSection) watchlistSection.style.display = 'block';
            if (watchlistSection) watchlistSection.scrollIntoView({ behavior: 'smooth' });
            const nameInput = document.getElementById('name');
            if (nameInput) nameInput.focus();
        };
    }
    if (genWatchlistReportBtn) {
        genWatchlistReportBtn.onclick = function() {
            if (typeof requestFishingReport === 'function') {
                requestFishingReport();
            } else {
                alert('Report generation logic not implemented yet.');
            }
        };
    }
    if (viewPastReportsBtn) {
        viewPastReportsBtn.onclick = function() {
            if (reportSection) reportSection.style.display = 'block';
            if (typeof loadPastReports === 'function') {
                loadPastReports(currentUserEmail);
            } else {
                alert('Past reports logic not implemented yet.');
            }
        };
    }

    // Auto-login if email is cached in localStorage
    const cachedEmail = localStorage.getItem('fishstalker_email');
    if (cachedEmail) {
        autoLoginWithEmail(cachedEmail);
    }

    // Email form submission
    emailForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const emailInput = document.getElementById('email');
        const email = emailInput.value.trim();
        if (!email) {
            alert('Please enter your email address.');
            return;
        }
        if (!isValidEmail(email)) {
            alert('Please enter a valid email address');
            return;
        }
        // Cache email in localStorage
        localStorage.setItem('fishstalker_email', email);
        // Set greeting header
        currentUserEmail = email;
        if (greetingHeader) {
            greetingHeader.textContent = `Hey ${email}, ready to fish today?`;
            greetingHeader.style.display = 'block';
        }
        // Hide email form and show other sections
        if (emailSection) emailSection.style.display = 'none';
        if (watchlistSection) watchlistSection.style.display = 'none';
        if (watchlistDisplay) watchlistDisplay.style.display = 'block';
        if (reportSection) reportSection.style.display = 'block';
        // Fetch and display existing watchlist entries for this email
        await fetchWatchlistEntries(email);
        // Load today's and past reports
        loadTodaysReport(email);
        loadPastReports(email);
    });

    function autoLoginWithEmail(email) {
        currentUserEmail = email;
        if (greetingHeader) {
            greetingHeader.textContent = `Hey ${email}, ready to fish today?`;
            greetingHeader.style.display = 'block';
        }
        if (emailSection) emailSection.style.display = 'none';
        if (watchlistSection) watchlistSection.style.display = 'none';
        if (watchlistDisplay) watchlistDisplay.style.display = 'block';
        if (reportSection) reportSection.style.display = 'block';
        fetchWatchlistEntries(email);
        loadTodaysReport(email);
        loadPastReports(email);
    }

    // Function to fetch and display watchlist entries
    async function fetchWatchlistEntries(email) {
        console.log('Attempting to fetch watchlist for email:', email);
        const watchlistResultsDiv = document.getElementById('watchlistResults');
        if (!watchlistResultsDiv) {
             console.error('Could not find watchlistResults element');
             return;
        }

        // Show a loading indicator in the watchlistResults area
        watchlistResultsDiv.innerHTML = '<p>Loading your watchlist...</p>';

        try {
            console.log('Fetching reports from get_reports.php...');
            const response = await fetch(`get_reports.php?email=${encodeURIComponent(email)}`);
            console.log('Received response from get_reports.php', response);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('HTTP error fetching watchlist:', response.status, errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Parsed JSON response:', result);

            if (result.success) {
                // The API returns 'captains' which contains watchlist entries
                displayWatchlist(result.captains);
            } else {
                // Display message if no entries found or an error occurred
                 watchlistResultsDiv.innerHTML = `<p>${result.message || 'Could not load watchlist.'}</p>`;
                 // Hide the generate report button if there are no captains
                 const generateReportBtn = document.getElementById('generateReportBtn');
                 if(generateReportBtn) { generateReportBtn.style.display = 'none'; }
            }
        } catch (error) {
            console.error('Error fetching watchlist:', error);
            watchlistResultsDiv.innerHTML = '<p>Error loading watchlist. Please try again.</p>';
             // Hide the generate report button on error
            const generateReportBtn = document.getElementById('generateReportBtn');
            if(generateReportBtn) { generateReportBtn.style.display = 'none'; }
        }
    }

    // Form validation and submission for adding to watchlist
    watchlistForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        const formData = {
            email: document.getElementById('email').value, // Get email from the hidden or previously entered field
            instagram_link: document.getElementById('instagramLink').value,
            name: document.getElementById('name').value,
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

            const result = await response.json();

            if (result.success) {
                alert('Entry saved successfully!');
                 // After saving, re-fetch and display updated watchlist
                const userEmail = document.getElementById('email').value; // Assuming email field still holds the value
                await fetchWatchlistEntries(userEmail);

                // Clear form
                this.reset();
                document.getElementById('region').value = 'Palm Beach County'; // Reset region to default
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving entry: ' + error.message);
        }
    });

    // Function to display watchlist entries
    function displayWatchlist(captains) {
        const watchlistResultsDiv = document.getElementById('watchlistResults');
        if (!watchlistResultsDiv) {
            console.error('Could not find watchlistResults element');
            return;
        }
        if (!captains || captains.length === 0) {
            watchlistResultsDiv.innerHTML = '<p>No captains added to watchlist yet.</p>';
            return;
        }
        // Responsive grid of expandable cards
        let html = '<div class="watchlist-grid">';
        captains.forEach((captain, idx) => {
            html += `
                <div class="watchlist-card" data-idx="${idx}">
                    <div class="card-header">
                        <span class="captain-name">${captain.name}</span>
                        <span class="expand-icon">&#9660;</span>
                    </div>
                    <div class="card-details" style="display:none;">
                        <p><strong>Instagram:</strong> <a href="${captain.instagram_link}" target="_blank">${captain.instagram_link}</a></p>
                        <p><strong>Boat Type:</strong> ${captain.boat_type}</p>
                        <p><strong>Location:</strong> ${captain.city}, ${captain.region}</p>
                        <button class="delete-btn" data-email="${document.getElementById('email').value}" data-instagram="${captain.instagram_link}">Remove</button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        watchlistResultsDiv.innerHTML = html;
        // Add expand/collapse logic
        document.querySelectorAll('.watchlist-card').forEach(card => {
            card.querySelector('.card-header').onclick = function() {
                const details = card.querySelector('.card-details');
                const icon = card.querySelector('.expand-icon');
                if (details.style.display === 'none') {
                    details.style.display = 'block';
                    icon.innerHTML = '&#9650;';
                } else {
                    details.style.display = 'none';
                    icon.innerHTML = '&#9660;';
                }
            };
        });
        // Add remove logic
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.onclick = async function(e) {
                e.stopPropagation();
                const email = this.getAttribute('data-email');
                const instagramLink = this.getAttribute('data-instagram');
                await removeWatchlistItem(email, instagramLink);
            };
        });
    }

     // Function to remove a watchlist item
    async function removeWatchlistItem(email, instagramLink) {
        try {
            const response = await fetch('remove_watchlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: email, instagram_link: instagramLink })
            });

            const result = await response.json();

            if (result.success) {
                alert('Watchlist item removed successfully!');
                // Re-fetch and display the updated watchlist
                const userEmail = document.getElementById('email').value; // Assuming email field still holds the value
                await fetchWatchlistEntries(userEmail);
            } else {
                alert('Error removing item: ' + result.message);
            }
        } catch (error) {
            console.error('Error removing watchlist item:', error);
            alert('Error removing watchlist item: ' + error.message);
        }
    }

    function validateForm() {
        const email = document.getElementById('email').value;
        const name = document.getElementById('name').value;
        const instagramLink = document.getElementById('instagramLink').value;
        const boatType = document.getElementById('boatType').value;
        const city = document.getElementById('city').value;
        const region = document.getElementById('region').value;
        
        if (!email || !name || !instagramLink || !boatType || !city || !region) {
            alert('Please fill in all fields');
            return false;
        }
        
        if (!isValidEmail(email)) {
            alert('Please enter a valid email address');
            return false;
        }
        
        if (!isValidInstagramLink(instagramLink)) {
            alert('Please enter a valid Instagram link (e.g., https://instagram.com/username)');
            return false;
        }
        
        return true;
    }
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function isValidInstagramLink(link) {
        return /^https?:\/\/(www\.)?instagram\.com\/[a-zA-Z0-9_.]+\/?.*$/.test(link);
    }

    // --- Fishing Report Section Logic ---
    function displayTodaysReport(report) {
        const todaysReportContent = document.getElementById('todaysReportContent');
        if (!todaysReportContent) return;
        if (!report) {
            const today = new Date();
            const dateStr = today.toLocaleDateString();
            todaysReportContent.innerHTML = `<p>No fishing report generated yet for today (${dateStr}). Hit the button above to generate one!</p>`;
            return;
        }
        const today = new Date();
        const dateStr = today.toLocaleDateString();
        todaysReportContent.innerHTML = `
            <div class="report-card">
                <div class="report-content">${report.report_content}</div>
                <div class="report-meta">Generated on ${dateStr}</div>
            </div>
        `;
    }

    function displayPastReportsList(reports) {
        const pastReportsList = document.getElementById('pastReportsList');
        if (!pastReportsList) return;
        pastReportsList.innerHTML = '';
        if (!reports || reports.length === 0) {
            pastReportsList.innerHTML = '<p>No past reports available.</p>';
            return;
        }
        reports.forEach(report => {
            const reportCard = document.createElement('div');
            reportCard.className = 'report-card';
            reportCard.innerHTML = `
                <div class="report-content">${report.report_content}</div>
                <div class="report-meta">Generated on ${report.created_at}</div>
            `;
            pastReportsList.appendChild(reportCard);
        });
    }

    // Load today's report (most recent for today)
    function loadTodaysReport(email) {
        fetch(`get_past_reports.php?email=${encodeURIComponent(email)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.reports && data.reports.length > 0) {
                    // Find the most recent report for today
                    const todayStr = new Date().toISOString().slice(0, 10);
                    const todaysReport = data.reports.find(r => r.created_at && r.created_at.slice(0, 10) === todayStr);
                    displayTodaysReport(todaysReport);
                } else {
                    displayTodaysReport(null);
                }
            })
            .catch(() => displayTodaysReport(null));
    }

    // Load all past reports (excluding today)
    function loadPastReports(email) {
        fetch(`get_past_reports.php?email=${encodeURIComponent(email)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.reports && data.reports.length > 0) {
                    const todayStr = new Date().toISOString().slice(0, 10);
                    const pastReports = data.reports.filter(r => !r.created_at || r.created_at.slice(0, 10) !== todayStr);
                    displayPastReportsList(pastReports);
                } else {
                    displayPastReportsList([]);
                }
            })
            .catch(() => displayPastReportsList([]));
    }

    // When generating a new report, update today's report and reload past reports
    window.requestFishingReport = function() {
        console.log('Generate Fishing Report button clicked. Starting report generation...');
        // Show the report section
        if (reportSection) {
            reportSection.style.display = 'block';
        }
        const todaysReportContent = document.getElementById('todaysReportContent');
        if (todaysReportContent) {
            todaysReportContent.innerHTML = `<div class="loading"><div class="loading-spinner"></div><p>Generating fishing report... This may take a few minutes.</p></div>`;
        }
        const userEmail = currentUserEmail || document.getElementById('email').value;
        if (!userEmail) {
            if (todaysReportContent) {
                todaysReportContent.innerHTML = '<p>Error: User email not available to generate report.</p>';
            }
            return;
        }
        fetch('generate_fishing_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: userEmail })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.report) {
                    // Save today's report to the section
                    displayTodaysReport({ report_content: data.report });
                    // Reload past reports
                    loadPastReports(userEmail);
                } else {
                    if (todaysReportContent) {
                        todaysReportContent.innerHTML = `<p>Error generating report: ${data.message || 'Unknown error'}</p>`;
                    }
                }
            })
            .catch(error => {
                if (todaysReportContent) {
                    todaysReportContent.innerHTML = '<p>Error generating report. Please try again.</p>';
                }
            });
    };

    // Expose these functions globally so the top buttons work
    window.requestFishingReport = requestFishingReport;
    window.loadPastReports = loadPastReports;

    // Video overlay logic
    const playHeroVideoBtn = document.getElementById('playHeroVideoBtn');
    const videoOverlay = document.getElementById('videoOverlay');
    const closeVideoBtn = document.getElementById('closeVideoBtn');
    const heroVideo = document.getElementById('heroVideo');

    if (playHeroVideoBtn && videoOverlay && closeVideoBtn && heroVideo) {
        playHeroVideoBtn.onclick = function() {
            videoOverlay.style.display = 'flex';
            heroVideo.currentTime = 0;
            heroVideo.play();
        };
        closeVideoBtn.onclick = function() {
            videoOverlay.style.display = 'none';
            heroVideo.pause();
        };
        videoOverlay.onclick = function(e) {
            if (e.target === videoOverlay) {
                videoOverlay.style.display = 'none';
                heroVideo.pause();
            }
        };
    }

    // Hamburger menu logic for all pages
    const navbarHamburger = document.getElementById('navbarHamburger');
    const navbarLinks = document.getElementById('navbarLinks');
    if (navbarHamburger && navbarLinks) {
        navbarHamburger.onclick = function(e) {
            e.stopPropagation();
            navbarLinks.classList.toggle('open');
        };
        // Close menu when clicking a link (on mobile)
        navbarLinks.querySelectorAll('a').forEach(link => {
            link.onclick = function() {
                navbarLinks.classList.remove('open');
            };
        });
        // Close menu when clicking outside
        document.body.onclick = function(e) {
            if (navbarLinks.classList.contains('open') && !navbarLinks.contains(e.target) && e.target !== navbarHamburger) {
                navbarLinks.classList.remove('open');
            }
        };
    }

    // Main card new button logic
    const addFishBtn = document.getElementById('addFishBtn');
    const generateReportByFishBtn = document.getElementById('generateReportByFishBtn');
    const chooseLocationBtn = document.getElementById('chooseLocationBtn');

    if (addFishBtn) {
        addFishBtn.onclick = function() {
            alert('Add Fish functionality coming soon!');
        };
    }
    if (generateReportByFishBtn) {
        generateReportByFishBtn.onclick = function() {
            if (fishingReportTypeSpan && reportSection) {
                fishingReportTypeSpan.textContent = 'Fish';
                reportSection.style.display = 'block';
            }
            alert('Generate Fishing Report by Fish coming soon!');
        };
    }
    if (chooseLocationBtn) {
        chooseLocationBtn.onclick = function() {
            if (fishingReportTypeSpan && reportSection) {
                fishingReportTypeSpan.textContent = 'Location';
                reportSection.style.display = 'block';
            }
            alert('Choose Your Location functionality coming soon!');
        };
    }

    // Show watchlist only when View Watchlist is clicked
    const viewWatchlistBtn = document.getElementById('viewWatchlistBtn');
    if (viewWatchlistBtn && watchlistDisplay) {
        viewWatchlistBtn.onclick = function() {
            watchlistDisplay.style.display = 'block';
            viewWatchlistBtn.style.display = 'none';
        };
    }

    // Update fishing report section title by type
    const fishingReportTypeSpan = document.getElementById('fishingReportType');

    // Generate Fishing Report from Watchlist (main logic)
    const generateWatchlistReportBtn = document.getElementById('generateWatchlistReportBtn');
    if (generateWatchlistReportBtn) {
        generateWatchlistReportBtn.onclick = function() {
            if (fishingReportTypeSpan && reportSection) {
                fishingReportTypeSpan.textContent = 'Fisherman';
                reportSection.style.display = 'block';
            }
            if (typeof requestFishingReport === 'function') {
                requestFishingReport();
            } else {
                alert('Report generation logic not implemented yet.');
            }
        };
    }
});

// Validate Instagram link on input
document.getElementById('instagramLink').addEventListener('input', function(e) {
    const instagramPattern = /^https?:\/\/(www\.)?instagram\.com\/[a-zA-Z0-9_.]+\/?.*$/;
    // Make sure you have an element with this ID in your HTML for displaying errors
    // const errorElement = document.getElementById('instagramError'); 

    // Find the nearest parent form element to correctly attach the error message if needed
    const formElement = this.closest('form');
    if (!formElement) return; // Exit if somehow not inside a form

    // Create or find an error message element near the instagramLink input
    let instagramErrorElement = formElement.querySelector('#instagramLinkError');
    if (!instagramErrorElement) {
        instagramErrorElement = document.createElement('div');
        instagramErrorElement.id = 'instagramLinkError';
        instagramErrorElement.style.color = 'red';
        instagramErrorElement.style.fontSize = '0.9em';
        instagramErrorElement.style.marginTop = '5px';
        this.parentNode.insertBefore(instagramErrorElement, this.nextSibling);
    }

    if (!instagramPattern.test(this.value)) {
        instagramErrorElement.textContent = 'Please enter a valid Instagram URL';
        instagramErrorElement.style.display = 'block';
    } else {
        instagramErrorElement.style.display = 'none';
        instagramErrorElement.textContent = ''; // Clear the message when valid
    }
});

// Save to localStorage
function saveToLocalStorage() {
    const tableBody = document.getElementById('watchlistTableBody');
    const rows = tableBody.getElementsByTagName('tr');
    const data = [];
    
    for (let row of rows) {
        const cells = row.getElementsByTagName('td');
        data.push({
            name: cells[0].textContent,
            instagram_link: cells[1].querySelector('a').href,
            type: cells[2].textContent,
            city: cells[3].textContent,
            region: cells[4].textContent
        });
    }
    
    localStorage.setItem('watchlistData', JSON.stringify(data));
}

// Load from localStorage
function loadFromLocalStorage() {
    const data = JSON.parse(localStorage.getItem('watchlistData') || '[]');
    const tableBody = document.getElementById('watchlistTableBody');
    
    data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td><a href="${item.instagram_link}" target="_blank">${item.instagram_link}</a></td>
            <td>${item.type}</td>
            <td>${item.city}</td>
            <td>${item.region}</td>
            <td><button class="delete-btn" onclick="deleteRow(this)">Delete</button></td>
        `;
        tableBody.appendChild(row);
    });
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', loadFromLocalStorage); 