<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Awards</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        /* Sidebar transition styles */
        #main-content {
            transition: all 0.3s ease-in-out;
        }
        
        /* Ensure sidebar is properly positioned */
        #sidebar {
            transition: transform 0.3s ease-in-out;
        }
        
        /* Content expansion when sidebar is closed */
        #main-content.sidebar-closed {
            margin-left: 0 !important;
            max-width: 100vw !important;
            width: 100% !important;
        }
        
        #main-content.sidebar-open {
            margin-left: 16rem; /* 256px */
            max-width: calc(100vw - 16rem);
        }
        
        /* Grid layout adjustments for sidebar closed state */
        .sidebar-closed .grid {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
        }
        
        .sidebar-closed .lg\\:grid-cols-2 {
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)) !important;
        }
        
        .sidebar-closed .md\\:grid-cols-3 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 767px) {
            #main-content {
                margin-left: 0 !important;
                max-width: 100vw !important;
            }
        }
        
        /* Smooth transitions for all interactive elements */
        .transition-all {
            transition: all 0.3s ease-in-out;
        }
        
        /* Menu icon rotation animation */
        #menu-icon {
            transition: transform 0.3s ease-in-out;
        }
        
        /* Chart container adjustments */
        .sidebar-closed .chart-container {
            width: 100% !important;
            max-width: none !important;
        }
        
        /* Card width adjustments */
        .sidebar-closed .bg-white {
            min-width: 0 !important;
        }
        
        /* Canvas and chart adjustments */
        .sidebar-closed canvas {
            max-width: 100% !important;
            width: 100% !important;
        }
        
        /* Grid container adjustments */
        .sidebar-closed .grid {
            width: 100% !important;
            max-width: 100% !important;
        }
        
        /* Ensure content takes full width when sidebar is closed */
        .sidebar-closed > * {
            max-width: 100% !important;
            width: 100% !important;
        }
        
        /* Specific adjustments for chart cards */
        .sidebar-closed .lg\\:grid-cols-2 > div {
            min-width: 0 !important;
            flex: 1 !important;
        }
        
        .sidebar-closed .md\\:grid-cols-3 > div {
            min-width: 0 !important;
            flex: 1 !important;
        }
        
        /* Force content expansion */
        .sidebar-closed .p-6 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }
        
        /* Chart responsiveness */
        .sidebar-closed #monthlyTrendChartContainer,
        .sidebar-closed #awardsCategoryChart {
            width: 100% !important;
            max-width: none !important;
        }
        
        /* Awards Breakdown chart container */
        .sidebar-closed .w-48.h-48 {
            width: 12rem !important;
            height: 12rem !important;
        }
        
        /* Ensure proper spacing in sidebar-closed state */
        .sidebar-closed .space-x-8 > div:first-child {
            margin-right: 2rem !important;
        }
        
        /* Tab Navigation Styles */
        .tab-button {
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }
        
        .tab-button:hover {
            color: #374151;
        }
        
        .tab-button.active {
            
            color: #000;
        }
        
        .tab-content {
            transition: opacity 0.3s ease-in-out;
        }
        
        .tab-content.hidden {
            display: none;
        }
        
        /* Footer section alignment */
        .sidebar-closed .grid.grid-cols-3 {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 1rem !important;
        }
        
        .sidebar-closed .grid.grid-cols-3 > div {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
        }
        
        /* Awards Breakdown responsive behavior */
        .sidebar-closed .flex.items-start.space-x-8 {
            flex-direction: row !important;
            gap: 2rem !important;
        }
        
        .sidebar-closed .flex.items-start.space-x-8 > div:first-child {
            flex-shrink: 0 !important;
        }
        
        .sidebar-closed .flex.items-start.space-x-8 > div:last-child {
            flex: 1 !important;
            min-width: 0 !important;
        }
        
        /* Ensure proper spacing when sidebar is closed */
        .sidebar-closed .gap-6 {
            gap: 1.5rem !important;
        }
        
        /* Container width adjustments */
        .sidebar-closed .max-w-2xl,
        .sidebar-closed .max-w-md {
            max-width: none !important;
        }
        
        /* Responsive improvements for smaller screens */
        @media (max-width: 768px) {
            .flex.items-start.space-x-8 {
                flex-direction: column !important;
                gap: 2rem !important;
            }
            
            .flex.items-start.space-x-8 > div:first-child {
                align-self: center !important;
            }
        }
        /* Awards Breakdown custom donut chart */
        .awards-donut .donut-chart {
          position: relative;
          width: 192px;
          height: 192px;
          margin: 0 auto 0;
          border-radius: 100%;
        }
        .awards-donut p.center {
          background: #ffffff;
          position: absolute;
          text-align: center;
          font-size: 16px;
          top:0;left:0;bottom:0;right:0;
          width: 124px;
          height: 124px;
          margin: auto;
          border-radius: 50%;
          line-height: 20px;
          padding: 36px 0 0;
          color: #374151;
        }
        .awards-donut .portion-block {
          border-radius: 50%;
          clip: rect(0px, 192px, 192px, 96px);
          height: 100%;
          position: absolute;
          width: 100%;
        }
        .awards-donut .circle {
          border-radius: 50%;
          clip: rect(0px, 96px, 192px, 0px);
          height: 100%;
          position: absolute;
          width: 100%;
          font-family: monospace;
          font-size: 1.5rem;
        }
        .awards-donut #part1 { transform: rotate(0deg); }
        .awards-donut #part1 .circle { background-color: #DC2626; animation: first 1s 1 forwards; }
        .awards-donut #part2 { transform: rotate(0deg); }
        .awards-donut #part2 .circle { background-color: #3B82F6; animation: second 1s 1 forwards 1s; }
        .awards-donut #part3 { transform: rotate(0deg); }
        .awards-donut #part3 .circle { background-color: #F9A8D4; animation: third 0.5s 1 forwards 2s; }

        @keyframes first {
          from { transform: rotate(0deg); }
          to { transform: rotate(100deg); }
        }
        @keyframes second {
          from { transform: rotate(0deg); }
          to { transform: rotate(150deg); }
        }
        @keyframes third {
          from { transform: rotate(0deg); }
          to { transform: rotate(111deg); }
        }
        /* Seamless donut via conic-gradient (no gaps) */
        .awards-donut .donut-chart.gradient {
          background: conic-gradient(
            #DC2626 0deg var(--deg-red),
            #3B82F6 var(--deg-red) calc(var(--deg-red) + var(--deg-blue)),
            #F9A8D4 calc(var(--deg-red) + var(--deg-blue)) 360deg
          );
        }
        .awards-donut .donut-chart.gradient .portion-block { display: none;         }
        /* Chart container styles (match provided design but scoped) */
        .awards-chart { background-color: #273241; border-radius: 8px; padding: 16px; }
        .awards-chart canvas { display: block; width: 100% !important; height: 300px !important; }
        /* Ensure chart canvas is visible and sized in the Average Awards Statistic card */
        #monthlyTrendChart { display: none; }
    </style>
    <script>
        // Initialize awards functionality
        let currentDocuments = [];
        const CATEGORY = 'Awards';

        document.addEventListener('DOMContentLoaded', function() {
            loadDocuments();
            loadStats();
            initializeEventListeners();
            updateCurrentDate();
            
            // Initialize tabs - ensure Awards Progress is active by default
            const periodSelect = document.getElementById('awards-breakdown-period');
            if (periodSelect) {
                periodSelect.addEventListener('change', function() {
                    updateAwardsDonutForPeriod(this.value);
                });
            }
            switchTab('overview');
            
            // Update date every minute
            setInterval(updateCurrentDate, 60000);
            
            // Ensure LILAC notifications appear below navbar
            setTimeout(function() {
                if (window.lilacNotifications && window.lilacNotifications.container) {
                    // Force reposition the LILAC container itself - navbar is 64px tall
                    window.lilacNotifications.container.style.top = '80px'; // 64px navbar + 16px gap
                    window.lilacNotifications.container.style.zIndex = '99999';
                }
            }, 500);
        });

        function updateCurrentDate() {
            const now = new Date();
            const dateElement = document.getElementById('current-date');
            if (dateElement) {
                dateElement.textContent = now.toLocaleDateString('en-US', {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }
        }



        function initializeEventListeners() {
            // Form submission
            const form = document.getElementById('award-form');
            if (form) {
                form.addEventListener('submit', handleFormSubmit);
            }
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            
            const title = document.getElementById('award-title').value.trim();
            const dateReceived = document.getElementById('date-received').value;
            const description = document.getElementById('award-description').value.trim();
            const fileInput = document.getElementById('award-file');

            // Enhanced validation
            if (!title) {
                showNotification('Please enter award title', 'error');
                document.getElementById('award-title').focus();
                return;
            }

            if (title.length < 2) {
                showNotification('Award title must be at least 2 characters long', 'error');
                document.getElementById('award-title').focus();
                return;
            }

            if (title.length > 150) {
                showNotification('Award title must be less than 150 characters', 'error');
                document.getElementById('award-title').focus();
                return;
            }

            if (!dateReceived) {
                showNotification('Please select the date received', 'error');
                document.getElementById('date-received').focus();
                return;
            }

            // Validate date received is not in the future
            const received = new Date(dateReceived);
            const today = new Date();
            today.setHours(23, 59, 59, 999); // End of today
            if (received > today) {
                showNotification('Date received cannot be in the future', 'error');
                document.getElementById('date-received').focus();
                return;
            }

            // Validate file if uploaded
            if (fileInput.files[0]) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (fileInput.files[0].size > maxSize) {
                    showNotification('Certificate file must be less than 10MB', 'error');
                    return;
                }

                // Validate file type
                const allowedTypes = [
                    'application/pdf', 
                    'image/jpeg', 
                    'image/png', 
                    'image/jpg'
                ];
                
                if (!allowedTypes.includes(fileInput.files[0].type)) {
                    showNotification('Only PDF, JPG, JPEG, and PNG files are allowed for certificates', 'error');
                    return;
                }
            }

            // Show confirmation modal
            showAddAwardConfirmModal(title, dateReceived, description, fileInput.files[0]);
        }

        function showAddAwardConfirmModal(title, dateReceived, description, file) {
            // Populate modal with award details
            document.getElementById('confirmAwardTitle').textContent = title;
            document.getElementById('confirmAwardDate').textContent = new Date(dateReceived).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric', 
                month: 'long', 
                day: 'numeric'
            });
            document.getElementById('confirmAwardDescription').textContent = description || 'No description provided.';
            document.getElementById('confirmAwardFile').textContent = file ? file.name : 'No file uploaded';
            
            // Show modal
            document.getElementById('addAwardConfirmModal').classList.remove('hidden');
        }

        function hideAddAwardConfirmModal() {
            document.getElementById('addAwardConfirmModal').classList.add('hidden');
        }

        function confirmAddAward() {
            const title = document.getElementById('award-title').value.trim();
            const dateReceived = document.getElementById('date-received').value;
            const description = document.getElementById('award-description').value.trim();
            const fileInput = document.getElementById('award-file');

            // Show loading state
            const submitBtn = document.querySelector('#addAwardConfirmModal button[onclick="confirmAddAward()"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding Award...';

            // Create full description with award details
            let fullDescription = description;
            if (dateReceived) fullDescription += `\nDate Received: ${dateReceived}`;

            // Add document via API
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('document_name', title);
            formData.append('category', CATEGORY);
            formData.append('description', fullDescription);
            if (fileInput.files[0]) {
                formData.append('file_name', fileInput.files[0].name);
                formData.append('file_size', fileInput.files[0].size);
            } else {
                // Add fallback file_name if no file is uploaded
                formData.append('file_name', `award_${Date.now()}.txt`);
            }

            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear form
                    document.getElementById('award-form').reset();
                    
                    // Refresh display
                    loadDocuments();
                    loadStats();
                    showNotification('Award added successfully!', 'success');
                    hideAddAwardConfirmModal();
                } else {
                    showNotification('Error: ' + (data.message || 'Unknown error occurred'), 'error');
                }
            })
            .catch(error => {
                console.error('Error adding award:', error);
                showNotification('Network error. Please check your connection and try again.', 'error');
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        }

        function loadDocuments() {
            fetch(`api/documents.php?action=get_by_category&category=${encodeURIComponent(CATEGORY)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentDocuments = data.documents;
                        displayDocuments(data.documents);
                    }
                })
                .catch(error => console.error('Error loading documents:', error));
        }

        function loadStats() {
            fetch(`api/documents.php?action=get_stats_by_category&category=${encodeURIComponent(CATEGORY)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStats(data.stats);
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
            
            // Also load monthly trend data
            loadMonthlyTrendData();
            // initialize donut values
            updateAwardsDonutForPeriod('This Month');
            const monthEl = document.getElementById('awards-donut-month');
            if (monthEl) {
                const now = new Date();
                monthEl.textContent = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            }
        }

        function loadMonthlyTrendData() {
            // Show loading state
            const loadingElement = document.getElementById('monthlyTrendChartLoading');
            if (loadingElement) {
                loadingElement.classList.remove('hidden');
            }
            
            // Try to fetch from awards API first
            fetch('api/awards.php?action=get_awards_by_month')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        updateMonthlyTrendChart(result.data);
                    } else {
                        // Fallback to simulated data
                        updateMonthlyTrendChart();
                    }
                })
                .catch(error => {
                    console.log('Using simulated monthly trend data');
                    // Fallback to simulated data
                    updateMonthlyTrendChart();
                })
                .finally(() => {
                    // Hide loading state
                    if (loadingElement) {
                        loadingElement.classList.add('hidden');
                    }
                });
                }
        
        // Awards Breakdown donut updater
        function updateAwardsDonutForPeriod(period) {
            // simple preset data per period
            const presets = {
                'This Month': { red: 52, blue: 32, pink: 16 },
                'Last 7 Days': { red: 40, blue: 45, pink: 15 },
                'Today': { red: 60, blue: 25, pink: 15 },
                'Last Month': { red: 48, blue: 36, pink: 16 }
            };
            const data = presets[period] || presets['This Month'];
            const total = data.red + data.blue + data.pink;
            // convert to degrees with decimals to avoid gaps between slices
            const redDeg = 360 * (data.red / total);
            const blueDeg = 360 * (data.blue / total);
            const pinkDeg = Math.max(0, 360 - (redDeg + blueDeg)); // exact remainder
            const donut = document.getElementById('awardsDonut');
            if (donut) {
                donut.style.setProperty('--deg-red', redDeg + 'deg');
                donut.style.setProperty('--deg-blue', blueDeg + 'deg');
            }
            // update labels on right
            const pctElems = document.querySelectorAll('#awards-breakdown-percentages .pct');
            if (pctElems.length >= 3) {
                pctElems[0].textContent = data.red + '%';
                pctElems[1].textContent = data.blue + '%';
                pctElems[2].textContent = data.pink + '%';
            }
            // update bar widths
            const barRed = document.getElementById('bar-red');
            const barBlue = document.getElementById('bar-blue');
            const barPink = document.getElementById('bar-pink');
            if (barRed) barRed.style.width = data.red + '%';
            if (barBlue) barBlue.style.width = data.blue + '%';
            if (barPink) barPink.style.width = data.pink + '%';
        }
        
        function updateMonthlyTrendChart(apiData = null) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            let thisYearData, lastYearData;
            
            if (apiData) {
                // Use real API data
                thisYearData = months.map((_, i) => apiData[i+1] || 0);
                // For last year, we could fetch from a different endpoint or use a percentage of this year
                lastYearData = thisYearData.map(val => Math.max(0, Math.floor(val * 0.8 + Math.random() * 2)));
            } else {
                // Use simulated data
                thisYearData = [3, 5, 4, 6, 8, 7, 9, 6, 8, 7, 5, 4];
                lastYearData = [2, 3, 4, 5, 6, 4, 7, 5, 6, 4, 3, 2];
            }
            
            // Update the chart with new data
            if (window.monthlyTrendChart && typeof Chart !== 'undefined') {
                window.monthlyTrendChart.data.datasets[0].data = thisYearData;
                window.monthlyTrendChart.data.datasets[1].data = lastYearData;
                window.monthlyTrendChart.update('active');
            }
        }

        function updateStats(stats) {
            const totalAwardsElement = document.getElementById('total-awards');
            const recentAwardsElement = document.getElementById('recent-awards');
            
            if (totalAwardsElement) {
                totalAwardsElement.textContent = stats.total;
            }
            if (recentAwardsElement) {
                recentAwardsElement.textContent = stats.recent;
            }
            
            // Update category counts
            updateCategoryCounts();
            
            // Update recent awards activity
            updateRecentAwardsActivity();
        }



        function updateCategoryCounts() {
            // Use fixed data to match the design
            const categories = {
                academic: 23,
                research: 14,
                leadership: 7
            };
            
            document.getElementById('academic-count').textContent = categories.academic;
            document.getElementById('research-count').textContent = categories.research;
            document.getElementById('leadership-count').textContent = categories.leadership;
            
            // Update total count
            const total = categories.academic + categories.research + categories.leadership;
            document.getElementById('category-total').textContent = total;
            
            // Update category chart
            renderCategoryChart(categories);
        }

        function renderCategoryChart(categories) {
            const ctx = document.getElementById('awardsCategoryChart');
            if (!ctx) return;
            
            // Destroy existing chart if it exists
            if (window.categoryChart) {
                window.categoryChart.destroy();
            }
            
            const data = [categories.academic, categories.research, categories.leadership];
            const colors = ['#DC2626', '#3B82F6', '#F9A8D4']; // Red, Blue, Pink to match design
            
            window.categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Operation', 'Utilities', 'Transportation'],
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateRecentAwardsActivity() {
            const recentList = document.getElementById('recent-awards-list');
            if (!recentList || currentDocuments.length === 0) return;
            
            // Get 5 most recent awards
            const recentAwards = currentDocuments
                .sort((a, b) => new Date(b.upload_date) - new Date(a.upload_date))
                .slice(0, 5);
            
            const activityHTML = recentAwards.map(award => {
                const date = new Date(award.upload_date);
                const timeAgo = getTimeAgo(date);
                const amount = `+ ${Math.floor(Math.random() * 500) + 100}`; // Simulate points
                
                return `
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">${award.document_name || 'Untitled Award'}</p>
                            <p class="text-sm text-gray-500">${timeAgo}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="text-sm font-medium text-green-600">${amount}</span>
                        </div>
                    </div>
                `;
            }).join('');
            
            recentList.innerHTML = activityHTML;
        }

        function showAwardsGuidelines() {
            const guidelinesContent = `
                <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full mx-4">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold text-gray-900">Awards Guidelines & Criteria</h3>
                            <button onclick="closeGuidelinesModal()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Academic Excellence Awards</h4>
                            <p class="text-gray-600 text-sm">Recognizes outstanding academic performance, research contributions, and scholarly achievements.</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Research & Innovation Awards</h4>
                            <p class="text-gray-600 text-sm">Honors groundbreaking research, innovative projects, and significant contributions to knowledge.</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Leadership & Service Awards</h4>
                            <p class="text-gray-600 text-sm">Acknowledges exceptional leadership, community service, and positive impact on society.</p>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h5 class="font-medium text-blue-900 mb-2">Submission Requirements</h5>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• Complete application form with supporting documents</li>
                                <li>• Academic transcripts and achievements</li>
                                <li>• Letters of recommendation</li>
                                <li>• Portfolio of work or research</li>
                            </ul>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-200 flex justify-end">
                        <button onclick="closeGuidelinesModal()" class="px-4 py-2 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 rounded-lg transition-colors">
                            Got it
                        </button>
                    </div>
                </div>
            `;
            
            // Create and show modal
            const modal = document.createElement('div');
            modal.id = 'guidelinesModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
            modal.innerHTML = guidelinesContent;
            document.body.appendChild(modal);
        }

        function closeGuidelinesModal() {
            const modal = document.getElementById('guidelinesModal');
            if (modal) {
                modal.remove();
            }
        }

        function displayDocuments(documents) {
            const container = document.getElementById('awards-container');
            
            // --- TABLE LAYOUT ONLY ---
            let tableHTML = `<div class="overflow-x-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                        </svg>
                                        Award Title
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Recipient
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Date Awarded
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Description
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        File Size
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                        </svg>
                                        Actions
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">`;
            
            if (documents.length === 0) {
                tableHTML += `<tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No awards yet</h3>
                            <p class="text-gray-500 mb-4">Add your first award to get started</p>
                            <button onclick="document.getElementById('award-title').focus()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                Add Award
                            </button>
                        </div>
                    </td>
                </tr>`;
            } else {
                tableHTML += documents.map(doc => {
                    const awardedDate = new Date(doc.date_awarded);
                    const formattedDate = awardedDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric'
                    });
                    
                    return `<tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${doc.award_title || doc.document_name || 'Untitled Award'}</div>
                                    <div class="text-sm text-gray-500">Award ID: ${doc.award_id || doc.id}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">${doc.recipient_name || 'No recipient specified'}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">${formattedDate}</div>
                            <div class="text-sm text-gray-500">${getTimeAgo(awardedDate)}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate" title="${doc.description || ''}">${doc.description && doc.description.trim() && doc.description !== '' ? (doc.description.length > 50 ? doc.description.substring(0, 50) + '...' : doc.description) : 'No description available'}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">${formatFileSize(doc.file_size || 0)}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <button onclick="viewAward(${doc.award_id || doc.id})" class="text-blue-600 hover:text-blue-900 font-medium flex items-center" title="View Details">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </button>
                                <button onclick="editAward(${doc.award_id || doc.id})" class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center" title="Edit">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </button>
                                <button onclick="showDeleteModal(${doc.award_id || doc.id}, '${(doc.award_title || doc.document_name || 'Untitled Award').replace(/'/g, "\\'")}')" class="text-red-600 hover:text-red-900 font-medium flex items-center" title="Delete">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            }
            tableHTML += `</tbody></table></div></div>`;

            container.innerHTML = tableHTML;
        }

        function getTimeAgo(date) {
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'just now';
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
            if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)}d ago`;
            if (diffInSeconds < 31536000) return `${Math.floor(diffInSeconds / 2592000)}mo ago`;
            return `${Math.floor(diffInSeconds / 31536000)}y ago`;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function viewAward(id) {
            const award = currentDocuments.find(d => d.id == id);
            if (award) {
                // Populate modal with award details
                document.getElementById('viewAwardTitle').textContent = award.document_name;
                document.getElementById('viewAwardDateReceived').textContent = new Date(award.upload_date).toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric'
                });
                document.getElementById('viewAwardDateAdded').textContent = getTimeAgo(new Date(award.upload_date));
                document.getElementById('viewAwardDescription').textContent = award.description || 'No description provided.';
                
                // Handle certificate file
                const certificateSection = document.getElementById('viewAwardCertificate');
                if (award.filename) {
                    certificateSection.innerHTML = `
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">${award.filename}</p>
                                <p class="text-sm text-gray-500">Certificate file</p>
                            </div>
                            <button onclick="downloadAwardFile(${award.id}, '${award.filename}')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                                Download
                            </button>
                        </div>
                    `;
                } else {
                    certificateSection.innerHTML = `
                        <div class="text-center py-6 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <p>No certificate file uploaded</p>
                        </div>
                    `;
                }
                
                // Show modal
                document.getElementById('viewAwardModal').classList.remove('hidden');
            }
        }

        function closeViewAwardModal() {
            document.getElementById('viewAwardModal').classList.add('hidden');
        }

        function downloadDocument(id) {
            const doc = currentDocuments.find(d => d.id == id);
            if (doc) {
                showNotification(`Downloading ${doc.filename}...`, 'info');
                // In a real implementation, this would trigger the actual file download
                // window.open(`api/documents.php?action=download&id=${id}`, '_blank');
            }
        }

        function downloadAwardFile(id, fileName) {
            showNotification(`Downloading ${fileName}...`, 'info');
            // In a real implementation, this would trigger the actual file download
            // window.open(`api/documents.php?action=download&id=${id}`, '_blank');
        }

        // Delete modal functionality
        let awardToDelete = null;

        function showDeleteModal(id, title) {
            awardToDelete = id;
            document.getElementById('awardToDeleteName').textContent = `Award: "${title}"`;
            document.getElementById('deleteConfirmModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            awardToDelete = null;
            document.getElementById('deleteConfirmModal').classList.add('hidden');
        }

        function confirmDelete() {
            if (awardToDelete !== null) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', awardToDelete);

                fetch('api/documents.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadDocuments();
                        loadStats();
                        hideDeleteModal();
                        showNotification('Award deleted successfully', 'success');
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting award:', error);
                    showNotification('Error deleting award', 'error');
                });
            }
        }

        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active', 'border-black', 'text-black');
                button.classList.add('border-transparent', 'text-gray-500');
            });

            // Show selected tab content
            const selectedContent = document.getElementById(`tab-${tabName}-content`);
            if (selectedContent) {
                selectedContent.classList.remove('hidden');
            }

            // Activate selected tab button
            const selectedButton = document.getElementById(`tab-${tabName}`);
            if (selectedButton) {
                selectedButton.classList.add('active', 'border-black', 'text-black');
                selectedButton.classList.remove('border-transparent', 'text-gray-500');
            }

            // Resize charts if switching to overview tab
            if (tabName === 'overview') {
                setTimeout(() => {
                    if (window.monthlyTrendChart && typeof Chart !== 'undefined') {
                        window.monthlyTrendChart.resize();
                    }
                    if (window.categoryChart && typeof Chart !== 'undefined') {
                        window.categoryChart.resize();
                    }
                }, 100);
            }
        }

        // AwardMatch Algorithm - Jaccard Similarity
        function calculateJaccardSimilarity(setA, setB) {
            if (setA.size === 0 && setB.size === 0) return 1;
            if (setA.size === 0 || setB.size === 0) return 0;
            
            const intersection = new Set([...setA].filter(x => setB.has(x)));
            const union = new Set([...setA, ...setB]);
            
            return intersection.size / union.size;
        }

        // Award criteria keywords for matching
        const awardCriteria = {
            leadership: new Set(['leadership', 'partnership', 'exchange', 'global', 'international', 'collaboration', 'initiative', 'management', 'coordination']),
            education: new Set(['education', 'curriculum', 'research', 'academic', 'program', 'course', 'study', 'learning', 'teaching', 'scholarship']),
            emerging: new Set(['emerging', 'innovation', 'new', 'creative', 'pioneering', 'breakthrough', 'advancement', 'development', 'growth', 'future']),
            regional: new Set(['regional', 'local', 'community', 'area', 'district', 'province', 'coordination', 'management', 'office', 'administration']),
            citizenship: new Set(['citizenship', 'global', 'cultural', 'exchange', 'community', 'awareness', 'engagement', 'social', 'responsibility', 'diversity'])
        };

        // Run AwardMatch analysis
        function runAwardMatch() {
            // Show loading state
            const button = document.querySelector('button[onclick="runAwardMatch()"]');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Analyzing...';

            // Simulate analysis delay for better UX
            setTimeout(() => {
                // Analyze university activities (this would normally come from database)
                const universityActivities = analyzeUniversityActivities();
                
                // Calculate scores for each award
                const scores = calculateAwardScores(universityActivities);
                
                // Update UI with results
                updateAwardMatchResults(scores, universityActivities);
                
                // Restore button
                button.disabled = false;
                button.textContent = originalText;
            }, 1500);
        }

        // Analyze university activities (simulated data for now)
        function analyzeUniversityActivities() {
            // This would normally fetch from database
            // For now, using simulated data based on typical university activities
            return {
                internationalPartnerships: 8,
                studentExchanges: 12,
                researchCollaborations: 15,
                internationalConferences: 6,
                culturalPrograms: 4,
                regionalInitiatives: 7,
                globalProjects: 9,
                academicPrograms: 18,
                leadershipPrograms: 5,
                communityEngagement: 11
            };
        }

        // Calculate award scores using Jaccard Similarity
        function calculateAwardScores(activities) {
            const scores = {};
            
            // Convert activities to keyword sets for each category
            const activityKeywords = {
                leadership: new Set(['partnership', 'exchange', 'global', 'international', 'collaboration', 'initiative', 'management', 'coordination']),
                education: new Set(['education', 'curriculum', 'research', 'academic', 'program', 'course', 'study', 'learning', 'teaching', 'scholarship']),
                emerging: new Set(['emerging', 'innovation', 'new', 'creative', 'pioneering', 'breakthrough', 'advancement', 'development', 'growth', 'future']),
                regional: new Set(['regional', 'local', 'community', 'area', 'district', 'province', 'coordination', 'management', 'office', 'administration']),
                citizenship: new Set(['citizenship', 'global', 'cultural', 'exchange', 'community', 'awareness', 'engagement', 'social', 'responsibility', 'diversity'])
            };

            // Calculate scores based on activity strengths
            scores.leadership = Math.min(100, Math.round(
                (activities.internationalPartnerships * 0.3 + 
                 activities.studentExchanges * 0.25 + 
                 activities.globalProjects * 0.25 + 
                 activities.leadershipPrograms * 0.2) * 2.5
            ));
            
            scores.education = Math.min(100, Math.round(
                (activities.academicPrograms * 0.4 + 
                 activities.researchCollaborations * 0.3 + 
                 activities.internationalConferences * 0.2 + 
                 activities.studentExchanges * 0.1) * 2.2
            ));
            
            scores.emerging = Math.min(100, Math.round(
                (activities.globalProjects * 0.3 + 
                 activities.internationalPartnerships * 0.25 + 
                 activities.researchCollaborations * 0.25 + 
                 activities.culturalPrograms * 0.2) * 2.8
            ));
            
            scores.regional = Math.min(100, Math.round(
                (activities.regionalInitiatives * 0.4 + 
                 activities.communityEngagement * 0.3 + 
                 activities.internationalPartnerships * 0.2 + 
                 activities.culturalPrograms * 0.1) * 2.5
            ));
            
            scores.citizenship = Math.min(100, Math.round(
                (activities.culturalPrograms * 0.35 + 
                 activities.communityEngagement * 0.3 + 
                 activities.studentExchanges * 0.2 + 
                 activities.globalProjects * 0.15) * 2.6
            ));

            return scores;
        }

        // Update AwardMatch results in UI
        function updateAwardMatchResults(scores, activities) {
            // Update score displays
            document.getElementById('leadership-score').textContent = scores.leadership + '%';
            document.getElementById('education-score').textContent = scores.education + '%';
            document.getElementById('emerging-score').textContent = scores.emerging + '%';
            document.getElementById('regional-score').textContent = scores.regional + '%';
            document.getElementById('citizenship-score').textContent = scores.citizenship + '%';

            // Update analysis metrics
            document.getElementById('activities-count').textContent = Object.values(activities).reduce((a, b) => a + b, 0);
            
            // Find best match
            const bestMatch = Object.entries(scores).reduce((a, b) => scores[a[0]] > scores[b[0]] ? a : b);
            document.getElementById('best-match').textContent = bestMatch[0].charAt(0).toUpperCase() + bestMatch[0].slice(1) + ' Award';
            
            // Calculate overall score
            const overallScore = Math.round(Object.values(scores).reduce((a, b) => a + b, 0) / 5);
            document.getElementById('overall-score').textContent = overallScore + '%';

            // Generate strategic recommendations
            generateRecommendations(scores, activities);
        }

        // Generate strategic recommendations
        function generateRecommendations(scores, activities) {
            const recommendations = [];
            
            // Find areas for improvement
            const areas = Object.entries(scores).sort((a, b) => a[1] - b[1]);
            const lowestArea = areas[0];
            
            if (lowestArea[1] < 30) {
                recommendations.push({
                    type: 'critical',
                    title: `Focus on ${lowestArea[0].charAt(0).toUpperCase() + lowestArea[0].slice(1)} Development`,
                    description: `Your ${lowestArea[0]} score is low. Consider developing more programs in this area.`,
                    priority: 'High'
                });
            }
            
            if (scores.leadership < 50) {
                recommendations.push({
                    type: 'improvement',
                    title: 'Enhance International Leadership',
                    description: 'Increase international partnerships and student exchange programs.',
                    priority: 'Medium'
                });
            }
            
            if (scores.education < 50) {
                recommendations.push({
                    type: 'improvement',
                    title: 'Strengthen Academic Programs',
                    description: 'Develop more international curriculum and research collaborations.',
                    priority: 'Medium'
                });
            }
            
            if (scores.citizenship < 40) {
                recommendations.push({
                    type: 'opportunity',
                    title: 'Expand Cultural Programs',
                    description: 'Increase cultural exchange and community engagement initiatives.',
                    priority: 'Low'
                });
            }

            // Display recommendations
            displayRecommendations(recommendations);
        }

        // Display recommendations in UI
        function displayRecommendations(recommendations) {
            const container = document.getElementById('recommendations-container');
            
            if (recommendations.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-green-600">
                        <svg class="w-12 h-12 mx-auto mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="font-medium">Excellent! Your university is well-positioned for all CHED awards.</p>
                    </div>
                `;
                return;
            }

            const recommendationsHTML = recommendations.map(rec => {
                const colorClass = rec.type === 'critical' ? 'red' : rec.type === 'improvement' ? 'yellow' : 'blue';
                const priorityColor = rec.priority === 'High' ? 'text-red-600' : rec.priority === 'Medium' ? 'text-yellow-600' : 'text-blue-600';
                
                return `
                    <div class="border-l-4 border-${colorClass}-500 pl-4 py-3 bg-${colorClass}-50 rounded-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">${rec.title}</h4>
                                <p class="text-sm text-gray-600 mt-1">${rec.description}</p>
                            </div>
                            <span class="text-xs font-medium ${priorityColor} bg-white px-2 py-1 rounded-full border border-${colorClass}-200">
                                ${rec.priority} Priority
                            </span>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = recommendationsHTML;
        }

        function showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Slide in
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            
            // Slide out and remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Initialize modal event listeners when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize variables for delete modal
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');
            const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const awardToDeleteName = document.getElementById('awardToDeleteName');

            // Add event listeners for delete modal buttons
            if (closeDeleteModalBtn) {
                closeDeleteModalBtn.addEventListener('click', hideDeleteModal);
            }
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', hideDeleteModal);
            }
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', confirmDelete);
            }

            // Add event listener for view award modal close button
            const closeViewModalBtn = document.getElementById('closeViewAwardModalBtn');
            if (closeViewModalBtn) {
                closeViewModalBtn.addEventListener('click', closeViewAwardModal);
            }

            // Keyboard shortcuts for power users
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + N = Focus on new award title field
                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                    e.preventDefault();
                    document.getElementById('award-title').focus();
                    document.getElementById('award-title').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                // Escape key to close modals
                if (e.key === 'Escape') {
                    if (!document.getElementById('viewAwardModal').classList.contains('hidden')) {
                        closeViewAwardModal();
                    }
                    if (!document.getElementById('deleteConfirmModal').classList.contains('hidden')) {
                        hideDeleteModal();
                    }
                }

                // Enter key in delete modal to confirm
                if (e.key === 'Enter' && !document.getElementById('deleteConfirmModal').classList.contains('hidden')) {
                    e.preventDefault();
                    confirmDelete();
                }

                // F1 or ? key to show help
                if (e.key === 'F1' || e.key === '?') {
                    e.preventDefault();
                    showKeyboardShortcuts();
                }
            });

            // Show keyboard shortcuts help
            function showKeyboardShortcuts() {
                const helpContent = `
LILAC Awards - Keyboard Shortcuts:

⌨️ Navigation:
• Ctrl/Cmd + N - Focus on new award title field
• Escape - Close any open modal
• Enter - Confirm deletion (when delete modal is open)
• F1 or ? - Show this help

🔍 View Features:
• Click "View" button to see award details
• Download certificates directly from view modal

💡 Tips:
• All file uploads are validated automatically
• Large images are optimized for better performance
• Use date picker or type YYYY-MM-DD format for dates

✨ Enhanced Features:
• Form validation with helpful error messages
• Loading states for all operations
• Automatic focus management for accessibility
                `;

                alert(helpContent);
            }

            // Make functions globally accessible
            window.showKeyboardShortcuts = showKeyboardShortcuts;
            window.showAwardsGuidelines = showAwardsGuidelines;
            window.closeGuidelinesModal = closeGuidelinesModal;

            // Add tooltips and accessibility hints
            const helpTooltips = [
                { id: 'award-title', hint: 'Press Ctrl+N to quickly focus here' },
                { id: 'date-received', hint: 'Use date picker or type YYYY-MM-DD format' },
                { id: 'award-file', hint: 'Accepts PDF, JPG, PNG files up to 10MB' }
            ];

            helpTooltips.forEach(tooltip => {
                const element = document.getElementById(tooltip.id);
                if (element) {
                    element.setAttribute('title', tooltip.hint);
                    element.setAttribute('aria-describedby', `${tooltip.id}-hint`);
                }
            });
        });
    </script>
</head>

<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] bg-white border-b border-gray-200 p-4 h-14 flex items-center relative">
        <!-- Left Side - Menu Buttons -->
        <div class="flex items-center space-x-4">
            <button id="menu-toggle" onclick="openSidebar()" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-700" title="Open Sidebar">
                <svg id="menu-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <button id="desktop-menu-toggle" onclick="openSidebar()" class="hidden md:flex items-center p-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-700" title="Open sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        
        <!-- Center - Title -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="text-center text-gray-900">
                <div class="text-lg font-bold">Awards Progress</div>
            </div>
        </div>
        
        <!-- Right Side - Date and Actions -->
        <div class="flex items-center space-x-4 ml-auto">
            <div class="flex items-center space-x-2 text-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span id="current-date" class="text-sm"></span>
            </div>
            
        </div>
    </nav>

    

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div id="main-content" class="ml-0 md:ml-64 p-4 pt-4 min-h-screen bg-white transition-all duration-300 ease-in-out">

        



        <!-- Tab Navigation -->
        <div class="mb-2">
            <div class="border-b border-gray-200 px-4">
                <div class="flex items-center justify-between">
                    <nav class="flex space-x-8" aria-label="Tabs">
                        <button id="tab-overview" onclick="switchTab('overview')" class="tab-button active py-4 px-1  font-medium text-sm text-black">
                            Awards Progress
                        </button>
                        <button id="tab-awardmatch" onclick="switchTab('awardmatch')" class="tab-button py-4 px-1  font-medium text-sm text-gray-500 hover:text-gray-700 ">
                            Award Match Analysis
                        </button>
                    </nav>
                    
                </div>
            </div>
        </div>

        <!-- Tab Content Container -->
        <div id="tab-overview-content" class="tab-content">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-600">Total Awards</p>
                        <p class="text-xl font-bold text-gray-900" id="total-awards">44</p>
                    </div>
                    <div class="p-3 rounded-full bg-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-600">Academic Excellence</p>
                        <p class="text-xl font-bold text-gray-900" id="academic-count">23</p>
                    </div>
                    <div class="p-3 rounded-full bg-green-100">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-600">Research Awards</p>
                        <p class="text-xl font-bold text-gray-900" id="research-count">14</p>
                    </div>
                    <div class="p-3 rounded-full bg-red-100">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-600">Leadership Awards</p>
                        <p class="text-xl font-bold text-gray-900" id="leadership-count">7</p>
                    </div>
                    <div class="p-3 rounded-full bg-purple-100">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Awards Progress Dashboard -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Average Awards Statistic -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Average Awards Statistic</h3>
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <select id="avg-awards-period" class="text-sm text-gray-600 bg-transparent border-none focus:ring-0">
                            <option value="This Year" selected>This Year</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                                            <div class="text-2xl font-bold text-gray-900"></div>
                </div>
                <div class="w-full">
                    <div class="chart__container awards-chart w-full">
                        <canvas id="awardsLineChartCanvas" width="600" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Awards Breakdown -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Awards Breakdown</h3>
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <select id="awards-breakdown-period" class="text-sm text-gray-600 bg-transparent border-none focus:ring-0">
                            <option value="This Month" selected>This Month</option>
                            <option value="Last 7 Days">Last 7 Days</option>
                            <option value="Today">Today</option>
                            <option value="Last Month">Last Month</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-start space-x-8">
                    <div class="flex-shrink-0">
                        <div class="awards-donut">
                            <div id="awardsDonut" class="donut-chart gradient" style="--deg-red: 187deg; --deg-blue: 115deg;">
                                <div id="part1" class="portion-block"><div class="circle"></div></div>
                                <div id="part2" class="portion-block"><div class="circle"></div></div>
                                <div id="part3" class="portion-block"><div class="circle"></div></div>
                                <p class="center"></p>
                            </div>
                        </div>
                        <div id="awards-donut-month" class="text-sm text-gray-600 text-center mt-2">August 2023</div>
                    </div>
                    <div id="awards-breakdown-percentages" class="flex-1 space-y-4 min-w-0">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-600 rounded-full mr-2"></div>
                                <span class="text-sm text-gray-600"></span>
                            </div>
                            <span class="text-sm font-medium text-red-600 pct">52%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="bar-red" class="bg-red-600 h-2 rounded-full" style="width: 52%"></div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                                <span class="text-sm text-gray-600"></span>
                            </div>
                            <span class="text-sm font-medium text-blue-600 pct">32%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="bar-blue" class="bg-blue-500 h-2 rounded-full" style="width: 32%"></div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-pink-300 rounded-full mr-2"></div>
                                <span class="text-sm text-gray-600"></span>
                            </div>
                            <span class="text-sm font-medium text-pink-500 pct">16%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="bar-pink" class="bg-pink-300 h-2 rounded-full" style="width: 16%"></div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="flex flex-col items-center">
                            <div class="text-sm text-gray-500 mb-1">Today</div>
                            <div class="text-lg font-semibold text-gray-900"></div>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="text-sm text-gray-500 mb-1">Last 7 Days</div>
                            <div class="text-lg font-semibold text-gray-900"></div>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="text-sm text-gray-500 mb-1">Last Month</div>
                            <div class="text-lg font-semibold text-gray-900"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Bottom Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Latest Awards -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Latest Awards</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138-3.138z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Academic Excellence Award</p>
                                <p class="text-xs text-gray-500">28 August 2023</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-green-600 font-medium">Success</span>
                            <div class="w-2 h-2 bg-green-500 rounded-full ml-2 inline-block"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Research Innovation Prize</p>
                                <p class="text-xs text-gray-500">25 August 2023</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-green-600 font-medium">Success</span>
                            <div class="w-2 h-2 bg-green-500 rounded-full ml-2 inline-block"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Leadership Service Award</p>
                                <p class="text-xs text-gray-500">22 August 2023</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-green-600 font-medium">Success</span>
                            <div class="w-2 h-2 bg-green-500 rounded-full ml-2 inline-block"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-medium">
                            RV
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900"><span class="font-medium">Ruby Vetrovs</span> 5 minutes ago</p>
                            <p class="text-sm text-gray-600">Added 3 new awards data with certificates.</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center text-white font-medium">
                            MJ
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900"><span class="font-medium">Maria Johnson</span> 2 hours ago</p>
                            <p class="text-sm text-gray-600">Updated award status for Research Excellence.</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center text-white font-medium">
                            AS
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900"><span class="font-medium">Alex Smith</span> 1 day ago</p>
                            <p class="text-sm text-gray-600">Uploaded certificate for Academic Achievement.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        

        <!-- Awards Grid -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Your Awards</h2>
            </div>
            <div class="p-6">
                <div id="awards-container" class="grid grid-cols-1 gap-4">
                    <!-- Awards will be loaded here -->
                </div>
            </div>
        </div>
        </div> <!-- End of tab-overview-content -->

        <!-- AwardMatch Tab Content -->
        <div id="tab-awardmatch-content" class="tab-content hidden">
            <!-- CHED Awards Progress -->
            <div class="grid grid-cols-5 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-600 truncate">International Leadership Award</p>
                            <p class="text-xl font-bold text-gray-900" id="leadership-score">0%</p>
                            <p class="text-xs text-blue-600">Qualification Score</p>
                        </div>
                        <div class="p-2 rounded-full bg-blue-100 ml-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-600 truncate">Outstanding International Education Program</p>
                            <p class="text-xl font-bold text-gray-900" id="education-score">0%</p>
                            <p class="text-xs text-green-600">Qualification Score</p>
                        </div>
                        <div class="p-2 rounded-full bg-green-100 ml-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-600 truncate">Emerging Leadership Award</p>
                            <p class="text-xl font-bold text-gray-900" id="emerging-score">0%</p>
                            <p class="text-xs text-purple-600">Qualification Score</p>
                        </div>
                        <div class="p-2 rounded-full bg-purple-100 ml-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-600 truncate">Best Regional Office for International</p>
                            <p class="text-xl font-bold text-gray-900" id="regional-score">0%</p>
                            <p class="text-sm text-orange-600">Qualification Score</p>
                        </div>
                        <div class="p-2 rounded-full bg-orange-100 ml-2">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-600 truncate">Global Citizenship Award</p>
                            <p class="text-xl font-bold text-gray-900" id="citizenship-score">0%</p>
                            <p class="text-sm text-red-600">Qualification Score</p>
                        </div>
                        <div class="p-2 rounded-full bg-red-100 ml-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 5 0 012 2 2.5 2.5 0 002.5 2.5.5.5 0 01.5.5v.5M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 5 0 012 2 2.5 2.5 0 002.5 2.5.5.5 0 01.5.5v.5"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Award Criteria and Matching Analysis -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Award Criteria -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">CHED Award Criteria</h3>
                    <div class="space-y-4">
                        <div class="border-l-4 border-blue-500 pl-4">
                            <h4 class="font-medium text-gray-900">International Leadership Award</h4>
                            <p class="text-sm text-gray-600">Demonstrated leadership in international partnerships, student exchanges, and global initiatives</p>
                        </div>
                        <div class="border-l-4 border-green-500 pl-4">
                            <h4 class="font-medium text-gray-900">Outstanding International Education Program</h4>
                            <p class="text-sm text-gray-600">Excellence in international curriculum, research collaborations, and academic partnerships</p>
                        </div>
                        <div class="border-l-4 border-purple-500 pl-4">
                            <h4 class="font-medium text-gray-900">Emerging Leadership Award</h4>
                            <p class="text-sm text-gray-600">Innovative approaches to international education and emerging global opportunities</p>
                        </div>
                        <div class="border-l-4 border-orange-500 pl-4">
                            <h4 class="font-medium text-gray-900">Best Regional Office for International</h4>
                            <p class="text-sm text-gray-600">Effective regional coordination and management of international programs</p>
                        </div>
                        <div class="border-l-4 border-red-500 pl-4">
                            <h4 class="font-medium text-gray-900">Global Citizenship Award</h4>
                            <p class="text-sm text-gray-600">Promotion of global awareness, cultural exchange, and international community engagement</p>
                        </div>
                    </div>
                </div>

                <!-- Matching Analysis -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Qualification Analysis</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Activities Analyzed</span>
                            <span class="text-sm text-gray-900" id="activities-count">0</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Best Match</span>
                            <span class="text-sm text-green-600 font-medium" id="best-match">None</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Overall Score</span>
                            <span class="text-sm text-blue-600 font-medium" id="overall-score">0%</span>
                        </div>
                    </div>
                    <button onclick="runAwardMatch()" class="w-full mt-4 bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors">
                        Run AwardMatch Analysis
                    </button>
                </div>
            </div>

            <!-- Strategic Recommendations -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Strategic Recommendations</h3>
                <div id="recommendations-container" class="space-y-3">
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        <p>Run AwardMatch Analysis to see personalized recommendations</p>
                    </div>
                </div>
            </div>
        </div> <!-- End of tab-awardmatch-content -->
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Add Award Confirmation Modal -->
    <div id="addAwardConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Confirm Award Details</h3>
                        <button onclick="hideAddAwardConfirmModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Award Title</label>
                        <p id="confirmAwardTitle" class="mt-1 text-sm text-gray-900 font-medium"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date Received</label>
                        <p id="confirmAwardDate" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <p id="confirmAwardDescription" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Certificate File</label>
                        <p id="confirmAwardFile" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                    <button onclick="hideAddAwardConfirmModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmAddAward()" class="px-4 py-2 text-sm font-medium text-white bg-black rounded-lg hover:bg-gray-800 transition-colors">
                        Confirm & Add Award
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Floating Add Award Button Above Footer -->
    <div class="fixed bottom-20 right-4 z-50">
        <button id="view-switch-btn" aria-label="Add Award" class="bg-purple-600 text-white w-12 h-12 rounded-full shadow-lg hover:bg-purple-700 transition-all duration-300 transform hover:scale-105 flex items-center justify-center" onclick="showAddAwardModal()">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
            </svg>
        </button>
    </div>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Confirm Deletion</h2>
                <button id="closeDeleteModalBtn" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-6">
                <p class="text-gray-700">Are you sure you want to delete this award? This action cannot be undone.</p>
                <p id="awardToDeleteName" class="font-semibold text-gray-700 mt-2"></p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelDeleteBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Delete</button>
            </div>
        </div>
    </div>

    <!-- View Award Modal -->
    <div id="viewAwardModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative p-8 bg-white w-full max-w-2xl m-auto flex-col flex rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
                    <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    Award Details
                </h2>
                <button onclick="closeViewAwardModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-6">
                <!-- Award Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Award Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Award Title</label>
                            <p id="viewAwardTitle" class="text-gray-900 font-medium"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Date Received</label>
                            <p id="viewAwardDateReceived" class="text-gray-900"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Added to System</label>
                            <p id="viewAwardDateAdded" class="text-gray-900"></p>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">Description</label>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p id="viewAwardDescription" class="text-gray-900"></p>
                    </div>
                </div>

                <!-- Certificate File -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">Certificate File</label>
                    <div id="viewAwardCertificate">
                        <!-- Certificate content will be dynamically inserted here -->
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button onclick="closeViewAwardModal()" class="px-6 py-2 text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 rounded-lg">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Add New Award Modal -->
    <div id="add-award-modal" class="fixed inset-0 bg-black/50 z-[70] hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Add New Award</h3>
                    <button class="text-gray-400 hover:text-gray-600" onclick="closeAddAwardModal()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="award-modal-form" class="p-6 space-y-4">
                    <div>
                        <label for="m-award-title" class="block text-sm font-medium text-gray-700 mb-2">Award Title *</label>
                        <input id="m-award-title" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent" />
                    </div>
                    <div>
                        <label for="m-date-received" class="block text-sm font-medium text-gray-700 mb-2">Date Received *</label>
                        <input id="m-date-received" type="date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent" />
                    </div>
                    <div>
                        <label for="m-award-description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="m-award-description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"></textarea>
                    </div>
                    <div>
                        <label for="m-award-file" class="block text-sm font-medium text-gray-700 mb-2">Upload Certificate</label>
                        <input id="m-award-file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent" />
                        <p class="mt-2 text-sm text-gray-500">Supported formats: PDF, JPG, PNG (Max 5MB)</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeAddAwardModal()" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-black text-white hover:bg-gray-800">Add Award</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Data Modal -->
    <div id="import-data-modal" class="fixed inset-0 bg-black/50 z-[70] hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl">
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Import Awards Data</h3>
                    <button class="text-gray-400 hover:text-gray-600" onclick="closeImportDataModal()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-sm text-gray-700">Upload a CSV or Excel file (.csv, .xlsx) with award data. You can download a sample template below.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button class="px-3 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="downloadImportTemplate()">Download Template</button>
                            <label class="px-3 py-2 rounded-lg bg-black text-white hover:bg-gray-800 cursor-pointer">
                                <input id="import-file" type="file" accept=".csv,.xlsx" class="hidden" onchange="handleImportFileChange(event)" />
                                Choose File
                            </label>
                            <span id="import-file-name" class="text-sm text-gray-600"></span>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeImportDataModal()" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</button>
                        <button type="button" onclick="processImport()" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">Import</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Simple chart initialization
    function initializeCharts() {
        if (typeof Chart === 'undefined') {
            setTimeout(initializeCharts, 100);
            return;
        }
        renderAwardsLineChart();
    }
    
    // Helper: render when canvas has a layout size
    function renderWhenReady(attempts = 10) {
        const canvas = document.getElementById('monthlyTrendChart');
        if (!canvas) return initializeCharts();
        const ready = canvas.offsetWidth > 0 && canvas.offsetHeight > 0;
        if (ready) {
            hideChartFallback();
            initializeCharts();
        } else if (attempts > 0) {
            setTimeout(() => renderWhenReady(attempts - 1), 150);
        } else {
            initializeCharts();
        }
    }

    // Start initialization when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('awardsLineChartCanvas')) {
            renderAwardsLineChart();
            const period = document.getElementById('avg-awards-period');
            if (period) {
                period.addEventListener('change', function(){
                    // For now only 'This Year' is available; re-render to simulate filter
                    renderAwardsLineChart();
                });
            }
        } else {
            renderWhenReady();
        }
    });
    
    // Also try on window load
    window.addEventListener('load', function() {
        if (typeof Chart !== 'undefined' && !window.monthlyTrendChart) {
            renderWhenReady();
        }
    });

    function renderMonthlyTrendChart() {
        console.log('renderMonthlyTrendChart called');
        const canvasEl = document.getElementById('monthlyTrendChart');
        if (!canvasEl) {
            createFallbackChart();
            return;
        }
        // Ensure canvas has dimensions
        canvasEl.style.display = 'block';
        canvasEl.width = canvasEl.parentElement.clientWidth;
        canvasEl.height = 160;
        
        console.log('Canvas found, destroying existing chart if any...');
        const ctx = canvasEl.getContext('2d');
        // build gradients similar to provided snippet
        const gradientStroke = ctx.createLinearGradient(500, 0, 100, 0);
        gradientStroke.addColorStop(0, '#ff6c00');
        gradientStroke.addColorStop(1, '#ff3b74');
        const gradientBkgrd = ctx.createLinearGradient(0, 0, 0, canvasEl.height);
        gradientBkgrd.addColorStop(0, 'rgba(244,94,132,0.2)');
        gradientBkgrd.addColorStop(1, 'rgba(249,135,94,0)');
        // Destroy existing chart if it exists
        if (window.monthlyTrendChart) {
            window.monthlyTrendChart.destroy();
        }
        
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Get current year and last year
        const currentYear = new Date().getFullYear();
        const lastYear = currentYear - 1;
        
        // Use data to match the financial dashboard design with Rp values
        const thisYearData = [1200, 1800, 1500, 2200, 2800, 2500, 3200, 7200, 3800, 4200, 3500, 4000];
        const lastYearData = [1000, 1400, 1600, 1900, 2100, 1800, 2400, 2800, 2200, 2600, 2000, 2400];
        
        try {
            console.log('Creating new Chart.js instance...');
            
            const lineShadow = { id: 'lineShadow', beforeDatasetsDraw(chart){ const {ctx}=chart; ctx.save(); ctx.shadowBlur=8; ctx.shadowOffsetX=0; ctx.shadowOffsetY=6; ctx.shadowColor='rgba(244,94,132,0.35)'; }, afterDatasetsDraw(chart){ chart.ctx.restore(); } };
            if (typeof Chart !== 'undefined') { Chart.register(lineShadow); }
            
            window.monthlyTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ["Sep", "Oct", "Nov", "Dec", "Jan", "Feb", "Mar", "Apr"],
                    datasets: [{
                        label: "Income",
                        data: [5500, 2500, 10000, 6000, 14000, 1500, 7000, 20000],
                        borderColor: gradientStroke,
                        backgroundColor: gradientBkgrd,
                        pointBorderColor: 'rgba(255,255,255,0)',
                        pointBackgroundColor: 'rgba(255,255,255,0)',
                        pointBorderWidth: 0,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: '#ff6c00',
                        pointHoverBorderColor: 'rgba(220,220,220,1)',
                        pointHoverBorderWidth: 4,
                        pointRadius: 1,
                        borderWidth: 5,
                        pointHitRadius: 16,
                        tension: 0.35,
                        fill: 'start'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#fff',
                            titleColor: '#000',
                            bodyColor: '#000',
                            borderColor: '#E5E7EB',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6', drawBorder: false },
                            ticks: {
                                color: '#6b7280',
                                font: { size: 11 },
                                padding: 8,
                                callback: function(value){ return (value/1000) + 'K'; }
                            },
                            border: { display: false }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6b7280', font: { size: 11 }, padding: 8 },
                            border: { display: false }
                        }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
            console.log('Chart created successfully!');
        } catch (error) {
            console.error('Error creating monthly trend chart:', error);
            createFallbackChart();
        }
    }

    function createFallbackChart() {
        const canvas = document.getElementById('monthlyTrendChart');
        const fallback = document.getElementById('monthlyTrendChartFallback');
        
        if (canvas && fallback) {
            canvas.style.display = 'none';
            fallback.classList.remove('hidden');
        }
    }
    
    // Manual function to show fallback
    function showChartFallback() {
        createFallbackChart();
    }
    
    // Manual function to hide fallback and show chart
    function hideChartFallback() {
        const canvas = document.getElementById('monthlyTrendChart');
        const fallback = document.getElementById('monthlyTrendChartFallback');
        
        if (canvas && fallback) {
            canvas.style.display = 'block';
            canvas.width = canvas.parentElement.clientWidth;
            canvas.height = 160;
            fallback.classList.add('hidden');
        }
    }

    function updateCurrentMonthDisplay() {
        const currentMonth = new Date().toLocaleDateString('en-US', { month: 'short' });
        const monthElement = document.getElementById('current-month');
        if (monthElement) {
            monthElement.textContent = currentMonth;
        }
    }

    // Function to refresh chart data (can be called from other parts of the code)
    function refreshMonthlyTrendChart() {
        if (typeof Chart !== 'undefined') {
            renderMonthlyTrendChart();
        }
    }

    // Handle window resize for better chart responsiveness
    window.addEventListener('resize', function() {
        if (window.monthlyTrendChart && typeof Chart !== 'undefined') {
            window.monthlyTrendChart.resize();
        }
    });

                // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + R to refresh chart
                if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                    e.preventDefault();
                    refreshMonthlyTrendChart();
                }
                
                // Ctrl/Cmd + B to toggle sidebar
                if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                    e.preventDefault();
                    toggleMenu();
                }
            });
    </script>

    <script>
        // Sidebar state management
        let sidebarOpen = window.innerWidth >= 768; // Default open on desktop
        
        // Sidebar toggle function
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('menu-overlay');
            const mainContent = document.getElementById('main-content');
            
            if (sidebar && overlay && mainContent) {
                const isHidden = sidebar.classList.contains('-translate-x-full');
                
                if (isHidden) {
                    // Show sidebar
                    openSidebar();
                } else {
                    // Hide sidebar
                    closeSidebar();
                }
            }
        }

        // Function to close sidebar
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('menu-overlay');
            const mainContent = document.getElementById('main-content');
            const menuIcon = document.getElementById('menu-icon');
            
            if (sidebar && overlay && mainContent) {
                // Force off-canvas state
                sidebar.classList.add('-translate-x-full');
                sidebar.style.transform = 'translateX(-100%)';
                overlay.classList.add('hidden');
                mainContent.classList.remove('ml-64', 'sidebar-open');
                mainContent.classList.add('ml-0', 'sidebar-closed');
                sidebarOpen = false;
                
                // Update menu icon
                if (menuIcon) {
                    menuIcon.style.transform = 'rotate(0deg)';
                }
                
                // Resize charts after sidebar change
                resizeChartsAfterSidebarChange();
            }
        }

        // Function to open sidebar
        function openSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('menu-overlay');
            const mainContent = document.getElementById('main-content');
            const menuIcon = document.getElementById('menu-icon');
            
            if (sidebar && overlay && mainContent) {
                // Force visible state
                sidebar.classList.remove('-translate-x-full');
                sidebar.style.transform = '';
                overlay.classList.remove('hidden');
                mainContent.classList.remove('ml-0', 'sidebar-closed');
                mainContent.classList.add('ml-64', 'sidebar-open');
                sidebarOpen = true;
                
                // Update menu icon
                if (menuIcon) {
                    menuIcon.style.transform = 'rotate(90deg)';
                }
                
                // Resize charts after sidebar change
                resizeChartsAfterSidebarChange();
            }
        }

        // Function to force chart resizing after sidebar state change
        function resizeChartsAfterSidebarChange() {
            setTimeout(() => {
                if (window.monthlyTrendChart && typeof Chart !== 'undefined') {
                    window.monthlyTrendChart.resize();
                }
                if (window.categoryChart && typeof Chart !== 'undefined') {
                    window.categoryChart.resize();
                }
                
                // Force re-render of category chart to ensure proper sizing
                if (window.categoryChart && typeof Chart !== 'undefined') {
                    const ctx = document.getElementById('awardsCategoryChart');
                    if (ctx) {
                        const rect = ctx.getBoundingClientRect();
                        ctx.width = rect.width;
                        ctx.height = rect.height;
                        window.categoryChart.resize();
                    }
                }
            }, 350); // Wait for CSS transition to complete
        }

        // Setup sidebar functionality when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const overlay = document.getElementById('menu-overlay');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', toggleMenu);
            }
            
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            // Responsive floating button on scroll
            let lastScrollTop = 0;
            const floatingBtn = document.getElementById('view-switch-btn');
            const floatingBtnContainer = floatingBtn?.parentElement;
            
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (floatingBtnContainer) {
                    if (scrollTop > lastScrollTop && scrollTop > 100) {
                        // Scrolling down - move button up (current position above footer)
                        floatingBtnContainer.style.bottom = '80px'; // bottom-20 equivalent
                        floatingBtnContainer.style.transition = 'bottom 0.3s ease';
                    } else {
                        // Scrolling up - move button down (old position at bottom)
                        floatingBtnContainer.style.bottom = '16px'; // bottom-4 equivalent
                        floatingBtnContainer.style.transition = 'bottom 0.3s ease';
                    }
                }
                
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            });

            // Listen for sidebar toggle events from other components
            window.addEventListener('sidebar:toggle', function() {
                if (sidebarOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            // Initialize state per viewport
            if (window.innerWidth >= 768) {
                openSidebar();
            } else {
                closeSidebar();
            }

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    // Desktop: ensure sidebar is visible and content is properly positioned
                    if (!sidebarOpen) {
                        const mainContent = document.getElementById('main-content');
                        if (mainContent) {
                            mainContent.classList.remove('ml-0', 'sidebar-closed');
                            mainContent.classList.add('ml-64', 'sidebar-open');
                        }
                    }
                } else {
                    // Mobile: ensure sidebar is hidden and content takes full width
                    if (sidebarOpen) {
                        closeSidebar();
                    }
                }
            });

            // Initialize sidebar state based on screen size
            if (window.innerWidth < 768) {
                closeSidebar();
            } else {
                // Desktop: ensure proper initial state
                const mainContent = document.getElementById('main-content');
                if (mainContent) {
                    mainContent.classList.remove('sidebar-closed');
                    mainContent.classList.add('sidebar-open');
                }
            }
            
        });
    </script>

    <script>
        function showAddAwardModal(){ openAddAwardModal(); }
        function openAddAwardModal(){
            document.getElementById('add-award-modal').classList.remove('hidden');
        }
        function closeAddAwardModal(){
            document.getElementById('add-award-modal').classList.add('hidden');
        }
        function openImportDataModal(){
            document.getElementById('import-data-modal').classList.remove('hidden');
        }
        function closeImportDataModal(){
            document.getElementById('import-data-modal').classList.add('hidden');
        }
        function handleImportFileChange(e){
            const file = e.target.files[0];
            document.getElementById('import-file-name').textContent = file ? file.name : '';
        }
        function downloadImportTemplate(){
            const csv = 'Title,Date,Description\nSample Award,2025-01-15,Description here';
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'awards_template.csv'; a.click();
            URL.revokeObjectURL(url);
        }
        function processImport(){
            const input = document.getElementById('import-file');
            if (!input.files || !input.files[0]) { alert('Please choose a file first'); return; }
            closeImportDataModal();
        }
        document.getElementById('award-modal-form')?.addEventListener('submit', function(e){
            e.preventDefault();
            closeAddAwardModal();
        });
    </script>

    <script>
    // ... existing code ...
    function renderAwardsLineChart() {
        const canvas = document.getElementById('awardsLineChartCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        // Gradients per provided design
        const gradientStroke = ctx.createLinearGradient(500, 0, 100, 0);
        gradientStroke.addColorStop(0, '#ff6c00');
        gradientStroke.addColorStop(1, '#ff3b74');

        const gradientBkgrd = ctx.createLinearGradient(0, 0, 0, canvas.height);
        gradientBkgrd.addColorStop(0, 'rgba(244,94,132,0.2)');
        gradientBkgrd.addColorStop(1, 'rgba(249,135,94,0)');

        // Shadow plugin (Chart.js v4)
        const lineShadow = {
            id: 'lineShadowAwards',
            beforeDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.shadowBlur = 8;
                ctx.shadowOffsetX = 0;
                ctx.shadowOffsetY = 6;
                ctx.shadowColor = 'rgba(244,94,132,0.35)';
            },
            afterDatasetsDraw(chart) {
                chart.ctx.restore();
            }
        };
        if (typeof Chart !== 'undefined') Chart.register(lineShadow);

        if (window.awardsLineChart) {
            window.awardsLineChart.destroy();
        }

        window.awardsLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
                datasets: [{
                    label: 'Income',
                    data: [5500, 2500, 10000, 6000, 14000, 1500, 7000, 20000],
                    backgroundColor: gradientBkgrd,
                    borderColor: gradientStroke,
                    pointBorderColor: 'rgba(255,255,255,0)',
                    pointBackgroundColor: 'rgba(255,255,255,0)',
                    pointBorderWidth: 0,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: gradientStroke,
                    pointHoverBorderColor: 'rgba(220,220,220,1)',
                    pointHoverBorderWidth: 4,
                    pointRadius: 1,
                    borderWidth: 5,
                    pointHitRadius: 16,
                    tension: 0.35,
                    fill: 'start'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff',
                        displayColors: false,
                        titleColor: '#000',
                        bodyColor: '#000'
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        ticks: {
                            callback: function(value) { return (value / 1000) + 'K'; }
                        }
                    }
                }
            }
        });
    }
    // ... existing code ...
    </script>

</body>

</html>

