<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Awards Progress</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Ensure proper spacing when sidebar is closed */
        .sidebar-closed .gap-6 {
            gap: 1.5rem !important;
        }
        
        /* Container width adjustments */
        .sidebar-closed .max-w-2xl,
        .sidebar-closed .max-w-md {
            max-width: none !important;
        }
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
            
            // Update weekly progress bars
            updateWeeklyProgress();
            
            // Update category counts
            updateCategoryCounts();
            
            // Update recent awards activity
            updateRecentAwardsActivity();
        }

        function updateWeeklyProgress() {
            // Simulate weekly data - in real implementation, fetch from API
            const weeklyData = [2, 1, 3, 2, 4, 1, 5]; // Mon-Sun
            const total = weeklyData.reduce((sum, val) => sum + val, 0);
            
            document.getElementById('weekly-total').textContent = total;
            
            // Update progress bar
            const progressBar = document.getElementById('weekly-progress');
            const progressPercentage = document.getElementById('weekly-percentage');
            if (progressBar && progressPercentage) {
                const percentage = Math.min((total / 5) * 100, 100); // 5 is the target
                progressBar.style.width = `${percentage}%`;
                progressPercentage.textContent = `${Math.round(percentage)}%`;
            }
            
            // Update bar heights with animation
            const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            days.forEach((day, index) => {
                const bar = document.getElementById(`${day}-bar`);
                if (bar) {
                    const height = (weeklyData[index] / 5) * 100; // 5 is max target
                    const finalHeight = Math.max(height, 8); // Minimum 8px height
                    
                    // Add animation class
                    bar.classList.add('transition-all', 'duration-500', 'ease-out');
                    
                    // Set final height
                    bar.style.height = `${finalHeight}px`;
                    
                    // Add hover effect
                    bar.classList.add('hover:opacity-80', 'cursor-pointer');
                    
                    // Add tooltip
                    bar.title = `${weeklyData[index]} awards on ${day === 'mon' ? 'Monday' : day === 'tue' ? 'Tuesday' : day === 'wed' ? 'Wednesday' : day === 'thu' ? 'Thursday' : day === 'fri' ? 'Friday' : day === 'sat' ? 'Saturday' : 'Sunday'}`;
                }
            });
        }

        function updateCategoryCounts() {
            // Simulate category data - in real implementation, fetch from API
            const categories = {
                academic: Math.floor(Math.random() * 15) + 5,
                research: Math.floor(Math.random() * 10) + 3,
                leadership: Math.floor(Math.random() * 8) + 2
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
            const colors = ['#3B82F6', '#10B981', '#8B5CF6'];
            
            window.categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Academic Excellence', 'Research & Innovation', 'Leadership & Service'],
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 0,
                        cutout: '60%'
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
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between pl-64 relative">
        <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
            <svg id="menu-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <div class="absolute left-1/2 transform -translate-x-1/2">
            <h1 class="text-xl font-bold text-gray-800">LILAC Awards Progress</h1>
        </div>
        <div class="absolute right-4 top-4 z-[90] text-sm flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span id="current-date"></span>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var hamburger = document.getElementById('hamburger-toggle');
        if (hamburger) {
            hamburger.addEventListener('click', function() {
                try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (e) {}
            });
        }
        
        // Update date in top-right
        function updateCurrentDate() {
            var el = document.getElementById('current-date');
            if (el) {
                var now = new Date();
                el.textContent = now.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
            }
        }
        updateCurrentDate();
        setInterval(updateCurrentDate, 60000);
    });
    </script>

    <!-- Main Content -->
    <div id="main-content" class="ml-0 md:ml-64 p-6 pt-20 min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 transition-all duration-300 ease-in-out">


        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Awards</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-awards">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">This Year</p>
                        <p class="text-2xl font-bold text-gray-900" id="recent-awards">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Latest Award</p>
                        <p class="text-sm font-bold text-gray-900" id="latest-award">None yet</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Awards Progress Dashboard -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Awards Guidelines Card -->
            <div class="bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-xl shadow-lg p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <h3 class="text-xl font-bold mb-3">Awards Guidelines</h3>
                    <p class="text-teal-100 mb-6">Learn about award criteria, submission guidelines, and recognition standards for academic excellence.</p>
                    <button onclick="showAwardsGuidelines()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition-colors font-medium">
                        Learn More
                    </button>
                </div>
                <div class="absolute bottom-0 right-0 w-24 h-24 opacity-20">
                    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
            </div>

            <!-- Awards by Category Card -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Awards by Category</h3>
                            <div class="flex items-center justify-center h-32 relative">
                <canvas id="awardsCategoryChart" width="200" height="200"></canvas>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900" id="category-total">0</div>
                        <div class="text-xs text-gray-500">Total Awards</div>
                    </div>
                </div>
            </div>
                <div class="mt-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Academic Excellence</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900" id="academic-count">0</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Research & Innovation</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900" id="research-count">0</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-purple-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Leadership & Service</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900" id="leadership-count">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Awards Progress Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Weekly Awards Progress -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Weekly Awards Progress</h3>
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600">Total: <span id="weekly-total" class="font-semibold">0</span> awards</span>
                        <span class="text-sm text-gray-600">Target: <span class="font-semibold">5</span> awards</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-teal-500 to-orange-500 h-2 rounded-full transition-all duration-500" id="weekly-progress" style="width: 0%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>0%</span>
                        <span id="weekly-percentage">0%</span>
                        <span>100%</span>
                    </div>
                </div>
                <div class="flex items-end justify-between h-32 mb-4">
                    <div class="flex flex-col items-center">
                        <div class="w-8 bg-gradient-to-t from-teal-500 to-teal-400 rounded-t-sm mb-2 shadow-sm hover:shadow-md transition-all duration-300" id="mon-bar" style="height: 20px;"></div>
                        <span class="text-xs text-gray-500 font-medium">M</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 bg-gradient-to-t from-teal-500 to-teal-400 rounded-t-sm mb-2 shadow-sm hover:shadow-md transition-all duration-300" id="tue-bar" style="height: 15px;"></div>
                        <span class="text-xs text-gray-500 font-medium">T</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 bg-gradient-to-t from-teal-500 to-teal-400 rounded-t-sm mb-2 shadow-sm hover:shadow-md transition-all duration-300" id="wed-bar" style="height: 25px;"></div>
                        <span class="text-xs text-gray-500 font-medium">W</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 bg-gradient-to-t from-teal-500 to-teal-400 rounded-t-sm mb-2 shadow-sm hover:shadow-md transition-all duration-300" id="thu-bar" style="height: 18px;"></div>
                        <span class="text-xs text-gray-500 font-medium">T</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 bg-gradient-to-t from-teal-500 to-teal-400 rounded-t-sm mb-2 shadow-sm hover:shadow-md transition-all duration-300" id="fri-bar" style="height: 22px;"></div>
                        <span class="text-xs text-gray-500 font-medium">F</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 bg-gradient-to-t from-teal-500 to-teal-400 rounded-t-sm mb-2 shadow-sm hover:shadow-md transition-all duration-300" id="sat-bar" style="height: 12px;"></div>
                        <span class="text-xs text-gray-500 font-medium">S</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 bg-gradient-to-t from-orange-500 to-orange-400 rounded-t-sm mb-2 shadow-sm hover:shadow-md transition-all duration-300" id="sun-bar" style="height: 30px;"></div>
                        <span class="text-xs text-gray-500 font-medium">S</span>
                    </div>
                </div>
            </div>

            <!-- Monthly Awards Trend -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Monthly Awards Trend</h3>
                    <button onclick="refreshMonthlyTrendChart()" class="text-teal-600 hover:text-teal-700 p-1 rounded-full hover:bg-teal-50 transition-colors" title="Refresh chart data">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm text-gray-600">Current Month: <span id="current-month" class="font-semibold">Jan</span></span>
                    <div class="flex items-center space-x-2">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-orange-500 rounded-full mr-1"></div>
                            <span class="text-xs text-gray-500">This Year</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-teal-500 rounded-full mr-1"></div>
                            <span class="text-xs text-gray-500">Last Year</span>
                        </div>
                    </div>
                </div>
                <div id="monthlyTrendChartContainer" class="relative">
                    <canvas id="monthlyTrendChart" height="120"></canvas>
                    <div id="monthlyTrendChartLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center hidden">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mx-auto mb-2"></div>
                            <p class="text-sm text-gray-600">Loading chart...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Awards Activity -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Recent Awards Activity</h3>
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm text-gray-500">Live updates</span>
                </div>
            </div>
            <div id="recent-awards-list" class="space-y-3">
                <!-- Recent awards will be loaded here -->
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="text-center">
                    <button onclick="loadDocuments()" class="text-sm text-teal-600 hover:text-teal-700 font-medium flex items-center justify-center w-full py-2 hover:bg-teal-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh Awards
                    </button>
                </div>
            </div>
        </div>

        <!-- Add New Award Section -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Add New Award</h2>
            <form id="award-form" class="space-y-6">
                <div>
                    <label for="award-title" class="block text-sm font-medium text-gray-700 mb-2">Award Title *</label>
                    <input type="text" id="award-title" name="award-title" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors"
                           placeholder="Enter award title">
                </div>
                <div>
                    <label for="date-received" class="block text-sm font-medium text-gray-700 mb-2">Date Received *</label>
                    <input type="date" id="date-received" name="date-received" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors">
                </div>
                <div>
                    <label for="award-description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="award-description" name="award-description" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors"
                              placeholder="Enter award description and details"></textarea>
                </div>
                <div>
                    <label for="award-file" class="block text-sm font-medium text-gray-700 mb-2">Upload Certificate</label>
                    <input type="file" id="award-file" name="award-file"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-black file:text-white hover:file:bg-gray-800">
                    <p class="mt-2 text-sm text-gray-500">Supported formats: PDF, JPG, PNG (Max 5MB)</p>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800 focus:ring-2 focus:ring-black focus:ring-offset-2 transition-colors font-medium">
                        Add Award
                    </button>
                </div>
            </form>
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

    <!-- Footer -->
    <footer class="ml-0 md:ml-64 bg-gray-800 text-white text-center p-4 mt-8">
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Chart.js to be available
        if (typeof Chart !== 'undefined') {
            // Initialize monthly trend chart
            renderMonthlyTrendChart();
            
            // Update current month display
            updateCurrentMonthDisplay();
        } else {
            // If Chart.js is not loaded yet, wait a bit and try again
            setTimeout(() => {
                if (typeof Chart !== 'undefined') {
                    renderMonthlyTrendChart();
                    updateCurrentMonthDisplay();
                } else {
                    console.error('Chart.js not loaded');
                    // Fallback: show a message or create a simple chart
                    createFallbackChart();
                }
            }, 1000);
        }
    });

    function renderMonthlyTrendChart() {
        const ctx = document.getElementById('monthlyTrendChart');
        if (!ctx) {
            console.error('Monthly trend chart canvas not found');
            return;
        }
        
        // Destroy existing chart if it exists
        if (window.monthlyTrendChart) {
            window.monthlyTrendChart.destroy();
        }
        
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Get current year and last year
        const currentYear = new Date().getFullYear();
        const lastYear = currentYear - 1;
        
        // Simulate data - in real implementation, fetch from API
        const thisYearData = [3, 5, 4, 6, 8, 7, 9, 6, 8, 7, 5, 4];
        const lastYearData = [2, 3, 4, 5, 6, 4, 7, 5, 6, 4, 3, 2];
        
        try {
            window.monthlyTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: `${currentYear}`,
                            data: thisYearData,
                            borderColor: '#F97316', // Orange
                            backgroundColor: 'rgba(249, 115, 22, 0.1)',
                            tension: 0.4,
                            pointBackgroundColor: '#F97316',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 2,
                            fill: true
                        },
                        {
                            label: `${lastYear}`,
                            data: lastYearData,
                            borderColor: '#14B8A6', // Teal
                            backgroundColor: 'rgba(20, 184, 166, 0.1)',
                            tension: 0.4,
                            pointBackgroundColor: '#14B8A6',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 2,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: false 
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#374151',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { 
                                color: '#f3f4f6',
                                drawBorder: false
                            },
                            ticks: { 
                                color: '#6b7280',
                                font: {
                                    size: 11
                                },
                                padding: 8
                            },
                            border: {
                                display: false
                            }
                        },
                        x: {
                            grid: { 
                                display: false 
                            },
                            ticks: { 
                                color: '#6b7280',
                                font: {
                                    size: 11
                                },
                                padding: 8
                            },
                            border: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    elements: {
                        point: {
                            hoverBackgroundColor: '#fff',
                            hoverBorderColor: '#F97316',
                            hoverBorderWidth: 2
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating monthly trend chart:', error);
            createFallbackChart();
        }
    }

    function createFallbackChart() {
        const ctx = document.getElementById('monthlyTrendChart');
        if (!ctx) return;
        
        // Create a simple fallback chart or message
        ctx.style.display = 'none';
        const container = ctx.parentElement;
        
        const fallbackHTML = `
            <div class="flex items-center justify-center h-32 text-center">
                <div>
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-gray-500 text-sm">Chart loading...</p>
                    <button onclick="renderMonthlyTrendChart()" class="mt-2 text-teal-600 hover:text-teal-700 text-xs font-medium">
                        Retry
                    </button>
                </div>
            </div>
        `;
        
        container.innerHTML = fallbackHTML;
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
</body>

</html>

