document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('watchlistForm');
    const searchForm = document.getElementById('searchForm');
    
    // Form validation and submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        const formData = {
            email: document.getElementById('email').value,
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
                // Add to table
                const tableBody = document.getElementById('watchlistTableBody');
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${formData.name}</td>
                    <td><a href="${formData.instagram_link}" target="_blank">${formData.instagram_link}</a></td>
                    <td>${formData.boat_type}</td>
                    <td>${formData.city}</td>
                    <td>${formData.region}</td>
                    <td><button class="delete-btn" onclick="deleteRow(this)">Delete</button></td>
                `;
                tableBody.appendChild(row);
                
                // Clear form
                form.reset();
                document.getElementById('region').value = 'Palm Beach County';
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error saving entry: ' + error.message);
        }
    });
    
    // Search functionality
    searchForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = document.getElementById('searchEmail').value;
        
        try {
            const response = await fetch(`get_reports.php?email=${encodeURIComponent(email)}`);
            const result = await response.json();
            
            if (result.success) {
                displayResults(result.reports);
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error retrieving reports: ' + error.message);
        }
    });
    
    function displayResults(reports) {
        const reportsList = document.getElementById('reportsList');
        
        if (!reportsList) {
            console.error('Could not find reportsList element');
            return;
        }
        
        if (reports.length === 0) {
            reportsList.innerHTML = '<p>No reports found for this email.</p>';
            return;
        }
        
        const html = `
            <div class="report-actions">
                <button onclick="requestFishingReport()" class="request-report-btn">Request Fishing Report</button>
            </div>
            ${reports.map(report => `
                <div class="report-card">
                    <h3>${report.name}</h3>
                    <p><strong>Instagram:</strong> <a href="${report.instagram_link}" target="_blank">${report.instagram_link}</a></p>
                    <p><strong>Boat Type:</strong> ${report.boat_type}</p>
                    <p><strong>Location:</strong> ${report.city}, ${report.region}</p>
                    <p><strong>Added:</strong> ${new Date(report.created_at).toLocaleDateString()}</p>
                </div>
            `).join('')}
        `;
        
        reportsList.innerHTML = html;
    }
    
    function validateForm() {
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const name = document.getElementById('name').value;
        const instagramLink = document.getElementById('instagramLink').value;
        const boatType = document.getElementById('boatType').value;
        const city = document.getElementById('city').value;
        const region = document.getElementById('region').value;
        
        if (!email || !phone || !name || !instagramLink || !boatType || !city || !region) {
            alert('Please fill in all fields');
            return false;
        }
        
        if (!isValidEmail(email)) {
            alert('Please enter a valid email address');
            return false;
        }
        
        if (!isValidPhone(phone)) {
            alert('Please enter a valid phone number');
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
    
    function isValidPhone(phone) {
        return /^\+?[\d\s-]{10,}$/.test(phone);
    }
    
    function isValidInstagramLink(link) {
        // More permissive Instagram link validation
        return /^https?:\/\/(www\.)?instagram\.com\/[a-zA-Z0-9_.]+\/?.*$/.test(link);
    }
});

function deleteRow(button) {
    const row = button.parentNode.parentNode;
    row.parentNode.removeChild(row);
}

// Validate Instagram link on input
document.getElementById('instagramLink').addEventListener('input', function(e) {
    const instagramPattern = /^https?:\/\/(www\.)?instagram\.com\/[a-zA-Z0-9_.]+\/?.*$/;
    const errorElement = document.getElementById('instagramError');
    
    if (!instagramPattern.test(this.value)) {
        errorElement.textContent = 'Please enter a valid Instagram URL';
        errorElement.style.display = 'block';
    } else {
        errorElement.style.display = 'none';
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

// Move this function outside of any other function to make it globally accessible
function requestFishingReport() {
    // Show the report section
    const reportSection = document.getElementById('reportSection');
    if (reportSection) {
        reportSection.style.display = 'block';
    }

    const reportsList = document.getElementById('reportsList');
    if (!reportsList) {
        console.error('Could not find reportsList element');
        return;
    }

    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading';
    loadingDiv.innerHTML = `
        <div class="loading-spinner"></div>
        <p>Generating fishing reports... This may take a few minutes.</p>
        <p>We're analyzing Instagram posts and creating detailed reports.</p>
    `;
    reportsList.innerHTML = '';
    reportsList.appendChild(loadingDiv);

    fetch('generate_fishing_report.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success) {
                displayReports(data.reports);
            } else {
                throw new Error(data.message || 'Failed to generate reports');
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            reportsList.innerHTML = `
                <div class="error">
                    <p>Error generating reports: ${error.message}</p>
                    <p>Please try again in a few minutes.</p>
                </div>
            `;
        });
}

function displayReports(reports) {
    const reportsList = document.getElementById('reportsList');
    if (!reportsList) return;

    if (reports.length === 0) {
        reportsList.innerHTML = '<p>No reports available.</p>';
        return;
    }

    const html = reports.map(report => `
        <div class="report-card">
            <h3>${report.instagram_url}</h3>
            <div class="report-content">${report.report}</div>
            <p class="report-meta">Posts analyzed: ${report.posts_found}</p>
        </div>
    `).join('');

    reportsList.innerHTML = html;
} 