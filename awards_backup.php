<?php
require_once 'classes/DateTimeUtility.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Awards</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/error-handler.js"></script>
    <script src="js/security-utils.js"></script>
    <script src="js/awards-check.js"></script>
    <script src="js/awards-config.js"></script>
    <script src="js/awards-management.js"></script>
    <script src="js/text-config.js"></script>
    <script src="js/date-time-utility.js"></script>
    <script src="js/lazy-loader.js"></script>
    <script src="js/modal-handlers.js"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="dashboard-theme.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        // Cache buster: 2024-12-19
        // Define category constant for awards
        const CATEGORY = 'Awards';
        
        document.addEventListener('DOMContentLoaded', function() {
            loadDocuments();
            loadStats();
            initializeEventListeners();
            updateCurrentDate();
            
            // Load award document counts on page load
            loadAwardDocumentCounts();
            
            // Load readiness summary on page load
            loadReadinessSummary();
            
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

            // Add document via API (no redundant description concatenation)
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('document_name', title);
            formData.append('category', CATEGORY);
            formData.append('description', description);
            formData.append('date_received', dateReceived); // Add date as separate field
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
                    // Check for awards earned after successful upload
                    if (window.checkAwardCriteria) {
                        window.checkAwardCriteria('award', data.newAwardId || data.document_id);
                    }
                    
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
                    // Using simulated monthly trend data
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
        
        // Awards Breakdown donut updater (zero by default)
        function updateAwardsDonutForPeriod(period) {
            // Try to get real data from API instead of defaulting to zeros
            fetch(`api/awards.php?action=get_awards_by_period&period=${encodeURIComponent(period)}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        // Use real API data
                        const data = result.data;
                        const total = data.red + data.blue + data.pink;
                        
                        if (total > 0) {
                            // Convert to degrees safely
                            const redDeg = 360 * (data.red / total);
                            const blueDeg = 360 * (data.blue / total);
                            const pinkDeg = Math.max(0, 360 - (redDeg + blueDeg));
                            
                            const donut = document.getElementById('awardsDonut');
                            if (donut) {
                                donut.style.setProperty('--deg-red', redDeg + 'deg');
                                donut.style.setProperty('--deg-blue', blueDeg + 'deg');
                            }
                            
                            // Update labels with real percentages
                            const pctElems = document.querySelectorAll('#awards-breakdown-percentages .pct');
                            if (pctElems.length >= 3) {
                                pctElems[0].textContent = Math.round((data.red / total) * 100) + '%';
                                pctElems[1].textContent = Math.round((data.blue / total) * 100) + '%';
                                pctElems[2].textContent = Math.round((data.pink / total) * 100) + '%';
                            }
                            
                            // Update bar widths
                            const barRed = document.getElementById('bar-red');
                            const barBlue = document.getElementById('bar-blue');
                            const barPink = document.getElementById('bar-pink');
                            if (barRed) barRed.style.width = (data.red / total) * 100 + '%';
                            if (barBlue) barBlue.style.width = (data.blue / total) * 100 + '%';
                            if (barPink) barPink.style.width = (data.pink / total) * 100 + '%';
                        } else {
                            // Show "No data" state
                            showDonutNoData();
                        }
                    } else {
                        // Show error state instead of fake zeros
                        showDonutError('Unable to load chart data');
                    }
                })
                .catch(error => {
                    console.error('Error loading period data:', error);
                    showDonutError('Network error loading chart data');
                });
        }
        
        function showDonutError(message) {
            const donut = document.getElementById('awardsDonut');
            if (donut) {
                donut.innerHTML = `
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <div class="text-2xl mb-2">📊</div>
                            <div class="text-sm">${message}</div>
                        </div>
                    </div>
                `;
            }
            
            // Update labels to show error
            const pctElems = document.querySelectorAll('#awards-breakdown-percentages .pct');
            if (pctElems.length >= 3) {
                pctElems[0].textContent = 'N/A';
                pctElems[1].textContent = 'N/A';
                pctElems[2].textContent = 'N/A';
            }
        }
        
        function showDonutNoData() {
            const donut = document.getElementById('awardsDonut');
            if (donut) {
                donut.innerHTML = `
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <div class="text-2xl mb-2">📊</div>
                            <div class="text-sm">No data available</div>
                        </div>
                    </div>
                `;
            }
            
            // Update labels to show no data
            const pctElems = document.querySelectorAll('#awards-breakdown-percentages .pct');
            if (pctElems.length >= 3) {
                pctElems[0].textContent = '0%';
                pctElems[1].textContent = '0%';
                pctElems[2].textContent = '0%';
            }
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
                // Default to zeros when no data is uploaded
                thisYearData = new Array(12).fill(0);
                lastYearData = new Array(12).fill(0);
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
            // Default to zeros when no data is uploaded
            const categories = {
                academic: 0,
                research: 0,
                leadership: 0
            };
            
            const academicCountEl = document.getElementById('academic-count');
            const researchCountEl = document.getElementById('research-count');
            const leadershipCountEl = document.getElementById('leadership-count');
            
            if (academicCountEl) academicCountEl.textContent = categories.academic;
            if (researchCountEl) researchCountEl.textContent = categories.research;
            if (leadershipCountEl) leadershipCountEl.textContent = categories.leadership;
            
            // Update total count if element is present
            const total = categories.academic + categories.research + categories.leadership;
            const totalEl = document.getElementById('category-total');
            if (totalEl) totalEl.textContent = total;
            
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

        // Activity keyword sets derived from university activities (static for now)
        const activityKeywords = {
            leadership: new Set(['partnership', 'exchange', 'global', 'international', 'collaboration', 'initiative', 'management', 'coordination']),
            education: new Set(['education', 'curriculum', 'research', 'academic', 'program', 'course', 'study', 'learning', 'teaching', 'scholarship']),
            emerging: new Set(['emerging', 'innovation', 'new', 'creative', 'pioneering', 'breakthrough', 'advancement', 'development', 'growth', 'future']),
            regional: new Set(['regional', 'local', 'community', 'area', 'district', 'province', 'coordination', 'management', 'office', 'administration']),
            citizenship: new Set(['citizenship', 'global', 'cultural', 'exchange', 'community', 'awareness', 'engagement', 'social', 'responsibility', 'diversity'])
        };

        function setsToArrays(mapOfSets) {
            const out = {};
            Object.keys(mapOfSets).forEach(k => {
                out[k] = Array.from(mapOfSets[k]);
            });
            return out;
        }

        

        // Modal helpers for missing data notice
        function showAwardMatchMissingDataModal() {
            const modal = document.getElementById('awardMatchMissingDataModal');
            if (modal) modal.classList.remove('hidden');
        }

        function hideAwardMatchMissingDataModal() {
            const modal = document.getElementById('awardMatchMissingDataModal');
            if (modal) modal.classList.add('hidden');
        }

        // Run AwardMatch analysis
        async function runAwardMatch() {
            // Show document/event selection modal first
            showDocumentSelectionModal();
        }

        function showDocumentSelectionModal() {
            const modal = document.createElement('div');
            modal.id = 'document-selection-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Select Document or Event for Analysis</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('document-selection-modal').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <h4 class="text-md font-medium text-gray-900 mb-3">Available Documents and Events</h4>
                            <div id="available-content-list" class="space-y-2 max-h-96 overflow-y-auto">
                                <div class="text-center text-gray-500 py-8">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                                    Loading content...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            loadAvailableContentForAnalysis();
        }

        async function loadAvailableContentForAnalysis() {
            try {
                // Get all documents
                const documentsResponse = await fetch('api/documents.php?action=get_all');
                const documentsData = await documentsResponse.json();
                
                // Get all events from central system
                const eventsResponse = await fetch('api/central_events_api.php?action=get_events_for_awards');
                const eventsData = await eventsResponse.json();
                
                const contentList = document.getElementById('available-content-list');
                contentList.innerHTML = '';
                
                if (documentsData.success && documentsData.documents.length > 0) {
                    documentsData.documents.forEach(doc => {
                        if (doc.status === 'Active') {
                            const item = document.createElement('div');
                            item.className = 'flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer';
                            item.onclick = () => analyzeSingleContent('document', doc.id, doc.document_name);
                            item.innerHTML = `
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">${doc.document_name}</div>
                                        <div class="text-sm text-gray-500">Document • ${doc.award_type || 'Unclassified'}</div>
                                    </div>
                                </div>
                                <div class="text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            `;
                            contentList.appendChild(item);
                        }
                    });
                }
                
                if (eventsData.success && eventsData.events.length > 0) {
                    eventsData.events.forEach(event => {
                        if (event.status === 'Active') {
                            const item = document.createElement('div');
                            item.className = 'flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer';
                            item.onclick = () => analyzeSingleContent('event', event.id, event.title);
                            item.innerHTML = `
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">${event.title}</div>
                                        <div class="text-sm text-gray-500">Event • ${event.award_type || 'Unclassified'}</div>
                                    </div>
                                </div>
                                <div class="text-green-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            `;
                            contentList.appendChild(item);
                        }
                    });
                }
                
                if (contentList.children.length === 0) {
                    contentList.innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p>No documents or events available for analysis</p>
                        </div>
                    `;
                }
                
            } catch (error) {
                console.error('Error loading content:', error);
                document.getElementById('available-content-list').innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <p>Error loading content. Please try again.</p>
                    </div>
                `;
            }
        }

        async function analyzeSingleContent(contentType, contentId, contentTitle) {
            // Close selection modal
            document.getElementById('document-selection-modal').remove();
            
            // Show loading state
            const button = document.querySelector('button[onclick="runAwardMatch()"]');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Analyzing...';

            try {
                showNotification(`Analyzing ${contentType}: ${contentTitle}`, 'info');
                
                // Perform analysis via API
                const response = await fetch('api/checklist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=analyze_single_content&content_type=${encodeURIComponent(contentType)}&content_id=${encodeURIComponent(contentId)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displaySingleAnalysisResults(result.analysis, contentType, contentTitle);
                    showNotification('Analysis completed successfully!', 'success');
                    
                    // Refresh the page data
                    if (typeof loadAwardDocumentCounts === 'function') {
                        loadAwardDocumentCounts();
                    }
                    if (typeof loadReadinessSummary === 'function') {
                        loadReadinessSummary();
                    }
                    if (typeof updateChecklistStatusAutomatically === 'function') {
                        updateChecklistStatusAutomatically();
                    }
                } else {
                    throw new Error(result.message || 'Analysis failed');
                }

            } catch (error) {
                console.error('Error analyzing content:', error);
                showNotification(`Error analyzing ${contentType}: ${error.message}`, 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
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

        // Legacy heuristic scoring removed; scoring now computed by backend Jaccard or client fallback

        // Load award document counts from API
        async function loadAwardDocumentCounts() {
            try {
                const response = await fetch('api/documents.php?action=get_award_counters');
                const result = await response.json();
                
                if (result.success && result.counters) {
                    updateAwardCounters(result.counters);
                }
            } catch (error) {
                console.error('Error loading award document counts:', error);
            }
        }

        // Update award counters in UI
        function updateAwardCounters(counters) {
            const awardTypes = ['leadership', 'education', 'emerging', 'regional', 'global'];
            
            awardTypes.forEach(awardType => {
                const counter = counters[awardType];
                if (counter) {
                    // Update document count displays
                    const scoreElement = document.getElementById(`${awardType}-score`);
                    if (scoreElement) {
                        scoreElement.textContent = counter.total_content || 0;
                    }
                    
                    // Update readiness status
                    updateAwardReadiness(awardType, counter);
                }
            });

            // Update analysis metrics
            const totalDocuments = Object.values(counters).reduce((a, b) => a + (b.total_content || 0), 0);
            const activitiesCountElement = document.getElementById('activities-count');
            const overallScoreElement = document.getElementById('overall-score');
            
            if (activitiesCountElement) activitiesCountElement.textContent = totalDocuments;
            if (overallScoreElement) overallScoreElement.textContent = totalDocuments;
            
            // Find best match (based on total content)
            const bestMatch = Object.entries(counters).reduce((a, b) => 
                (counters[a[0]].total_content || 0) > (counters[b[0]].total_content || 0) ? a : b
            );
            
            const bestMatchElement = document.getElementById('best-match');
            if (bestMatchElement) {
                if (counters[bestMatch[0]].total_content > 0) {
                    const awardNames = {
                        'leadership': 'Internationalization (IZN) Leadership',
                        'education': 'Outstanding International Education Program',
                        'emerging': 'Emerging Leadership',
                        'regional': 'Best Regional Office for Internationalization',
                        'global': 'Global Citizenship'
                    };
                    bestMatchElement.textContent = awardNames[bestMatch[0]] + ' Award';
                } else {
                    bestMatchElement.textContent = 'None';
                }
            }
        }

        // Update award readiness status
        function updateAwardReadiness(awardType, counter) {
            const readinessElement = document.getElementById(`${awardType}-readiness`);
            if (readinessElement) {
                const isReady = counter.readiness === 'Ready to Apply';
                readinessElement.className = `px-3 py-1 rounded-full text-sm font-medium ${
                    isReady ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`;
                readinessElement.textContent = counter.readiness;
            }
            
            // Update progress bar if it exists
            const progressElement = document.getElementById(`${awardType}-progress`);
            if (progressElement) {
                const percentage = Math.min(100, (counter.total_content / counter.threshold) * 100);
                progressElement.style.width = `${percentage}%`;
                progressElement.className = `h-2 rounded-full ${
                    isReady ? 'bg-green-500' : 'bg-blue-500'
                }`;
            }
        }

        // Update AwardMatch results in UI (legacy function for compatibility)
        function updateAwardMatchResults(scores, activities) {
            // This function is kept for compatibility but now just loads from API
            loadAwardDocumentCounts();
        }

        // Analyze all documents and events and show detailed breakdown
        async function analyzeAllDocuments() {
            // Show loading state
            const button = document.querySelector('button[onclick="analyzeAllDocuments()"]');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Analyzing All...';

            try {
                showNotification('Starting comprehensive analysis of all documents and events...', 'info');
                
                // Perform batch analysis via API
                const response = await fetch('api/checklist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=analyze_all_content'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayBatchAnalysisResults(result.analysis);
                    showNotification('Comprehensive analysis completed successfully!', 'success');
                    
                    // Refresh the page data
                    if (typeof loadAwardDocumentCounts === 'function') {
                        loadAwardDocumentCounts();
                    }
                    if (typeof loadReadinessSummary === 'function') {
                        loadReadinessSummary();
                    }
                    if (typeof updateChecklistStatusAutomatically === 'function') {
                        updateChecklistStatusAutomatically();
                    }
                } else {
                    throw new Error(result.message || 'Batch analysis failed');
                }
                
            } catch (error) {
                console.error('Error analyzing all documents and events:', error);
                showNotification(`Error in batch analysis: ${error.message}`, 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        }

        // Display single content analysis results
        function displaySingleAnalysisResults(analysis, contentType, contentTitle) {
            const modal = document.createElement('div');
            modal.id = 'single-analysis-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Analysis Results: ${contentTitle}</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('single-analysis-modal').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6">
                        ${generateSingleAnalysisContent(analysis, contentType, contentTitle)}
                    </div>
                    <div class="p-6 border-t border-gray-200 flex justify-end">
                        <button onclick="document.getElementById('single-analysis-modal').remove()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        function generateSingleAnalysisContent(analysis, contentType, contentTitle) {
            const awardNames = {
                'leadership': 'Internationalization (IZN) Leadership Award',
                'education': 'Outstanding International Education Program Award',
                'emerging': 'Emerging Leadership Award',
                'regional': 'Best Regional Office for Internationalization Award',
                'global': 'Global Citizenship Award'
            };

            let content = `
                <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Analysis Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">${analysis.supported_awards?.length || 0}</div>
                            <div class="text-sm text-blue-800">Awards Supported</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">${analysis.satisfied_criteria?.length || 0}</div>
                            <div class="text-sm text-green-800">Criteria Satisfied</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">${analysis.confidence_score || 0}%</div>
                            <div class="text-sm text-purple-800">Confidence Score</div>
                        </div>
                    </div>
                </div>
            `;

            if (analysis.supported_awards && analysis.supported_awards.length > 0) {
                content += `
                    <div class="mb-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Supported Awards</h4>
                        <div class="space-y-2">
                `;
                
                analysis.supported_awards.forEach(award => {
                    content += `
                        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-green-900">${awardNames[award.award_type] || award.award_type}</div>
                                    <div class="text-sm text-green-700">Confidence: ${award.confidence}%</div>
                                </div>
                            </div>
                            <div class="text-green-600 font-medium">${award.confidence}%</div>
                        </div>
                    `;
                });
                
                content += `
                        </div>
                    </div>
                `;
            }

            if (analysis.satisfied_criteria && analysis.satisfied_criteria.length > 0) {
                content += `
                    <div class="mb-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Satisfied Criteria</h4>
                        <div class="space-y-2">
                `;
                
                analysis.satisfied_criteria.forEach(criterion => {
                    content += `
                        <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-blue-900">${criterion.criterion}</div>
                                    <div class="text-sm text-blue-700">${awardNames[criterion.award_type] || criterion.award_type}</div>
                                </div>
                            </div>
                            <div class="text-blue-600 font-medium">${criterion.confidence}%</div>
                        </div>
                    `;
                });
                
                content += `
                        </div>
                    </div>
                `;
            }

            if (analysis.keywords_found && analysis.keywords_found.length > 0) {
                content += `
                    <div class="mb-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Keywords Found</h4>
                        <div class="flex flex-wrap gap-2">
                `;
                
                analysis.keywords_found.forEach(keyword => {
                    content += `
                        <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                            ${keyword}
                        </span>
                    `;
                });
                
                content += `
                        </div>
                    </div>
                `;
            }

            if (analysis.recommendations && analysis.recommendations.length > 0) {
                content += `
                    <div class="mb-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Recommendations</h4>
                        <div class="space-y-2">
                `;
                
                analysis.recommendations.forEach(rec => {
                    content += `
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="font-medium text-yellow-900">${rec.title}</div>
                            <div class="text-sm text-yellow-700">${rec.description}</div>
                        </div>
                    `;
                });
                
                content += `
                        </div>
                    </div>
                `;
            }

            return content;
        }

        // Display batch analysis results
        function displayBatchAnalysisResults(analysis) {
            const modal = document.createElement('div');
            modal.id = 'batch-analysis-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Comprehensive Analysis Results</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('batch-analysis-modal').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6">
                        ${generateBatchAnalysisContent(analysis)}
                    </div>
                    <div class="p-6 border-t border-gray-200 flex justify-end">
                        <button onclick="document.getElementById('batch-analysis-modal').remove()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        function generateBatchAnalysisContent(analysis) {
            const awardNames = {
                'leadership': 'Internationalization (IZN) Leadership Award',
                'education': 'Outstanding International Education Program Award',
                'emerging': 'Emerging Leadership Award',
                'regional': 'Best Regional Office for Internationalization Award',
                'global': 'Global Citizenship Award'
            };

            let content = `
                <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Analysis Summary</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">${analysis.total_documents || 0}</div>
                            <div class="text-sm text-blue-800">Documents Analyzed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">${analysis.total_events || 0}</div>
                            <div class="text-sm text-green-800">Events Analyzed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">${analysis.total_criteria_satisfied || 0}</div>
                            <div class="text-sm text-purple-800">Criteria Satisfied</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">${analysis.awards_ready || 0}</div>
                            <div class="text-sm text-orange-800">Awards Ready</div>
                        </div>
                    </div>
                </div>
            `;

            if (analysis.award_breakdown && analysis.award_breakdown.length > 0) {
                content += `
                    <div class="mb-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Award Breakdown</h4>
                        <div class="space-y-4">
                `;
                
                analysis.award_breakdown.forEach(award => {
                    const readinessColor = award.readiness === 'Ready to Apply' ? 'green' : 
                                         award.readiness === 'Nearly Ready' ? 'yellow' : 'red';
                    const readinessIcon = award.readiness === 'Ready to Apply' ? '✅' : 
                                        award.readiness === 'Nearly Ready' ? '⚠️' : '❌';
                    
                    content += `
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h5 class="font-semibold text-gray-900">${awardNames[award.award_type] || award.award_type}</h5>
                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-${readinessColor}-100 text-${readinessColor}-800">
                                    ${readinessIcon} ${award.readiness}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-4 mb-3">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-blue-600">${award.documents_count}</div>
                                    <div class="text-xs text-blue-800">Documents</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600">${award.events_count}</div>
                                    <div class="text-xs text-green-800">Events</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-purple-600">${award.satisfied_criteria}/${award.total_criteria}</div>
                                    <div class="text-xs text-purple-800">Criteria</div>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-${readinessColor}-500 h-2 rounded-full" style="width: ${(award.satisfied_criteria / award.total_criteria) * 100}%"></div>
                            </div>
                        </div>
                    `;
                });
                
                content += `
                        </div>
                    </div>
                `;
            }

            if (analysis.missing_criteria && analysis.missing_criteria.length > 0) {
                content += `
                    <div class="mb-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Missing Criteria</h4>
                        <div class="space-y-2">
                `;
                
                analysis.missing_criteria.forEach(missing => {
                    content += `
                        <div class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-red-900">${missing.criterion}</div>
                                    <div class="text-sm text-red-700">${awardNames[missing.award_type] || missing.award_type}</div>
                                </div>
                            </div>
                            <div class="text-red-600 font-medium">Missing</div>
                        </div>
                    `;
                });
                
                content += `
                        </div>
                    </div>
                `;
            }

            return content;
        }

        // Display detailed analysis results
        function displayAnalysisResults(results) {
            const modal = document.createElement('div');
            modal.id = 'analysis-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Document Analysis Results</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('analysis-modal').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                        <div class="space-y-6">
                            ${results.map(result => `
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">${result.awardType}</h4>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div class="bg-blue-50 p-3 rounded-lg">
                                            <div class="text-sm text-blue-600 font-medium">Documents</div>
                                            <div class="text-2xl font-bold text-blue-900">${result.document_count || 0}</div>
                                        </div>
                                        <div class="bg-purple-50 p-3 rounded-lg">
                                            <div class="text-sm text-purple-600 font-medium">Events</div>
                                            <div class="text-2xl font-bold text-purple-900">${result.event_count || 0}</div>
                                        </div>
                                        <div class="bg-green-50 p-3 rounded-lg">
                                            <div class="text-sm text-green-600 font-medium">Satisfaction Rate</div>
                                            <div class="text-2xl font-bold text-green-900">${Math.round(result.satisfaction_rate * 100)}%</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <h5 class="font-medium text-gray-900 mb-2">✅ Satisfied Criteria (${result.satisfied_criteria.length})</h5>
                                            <ul class="space-y-1">
                                                ${result.satisfied_criteria.map(criterion => `
                                                    <li class="text-sm text-green-700 flex items-center">
                                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        ${criterion}
                                                    </li>
                                                `).join('')}
                                            </ul>
                                        </div>
                                        <div>
                                            <h5 class="font-medium text-gray-900 mb-2">❌ Missing Criteria (${result.unsatisfied_criteria.length})</h5>
                                            <ul class="space-y-1">
                                                ${result.unsatisfied_criteria.map(criterion => `
                                                    <li class="text-sm text-red-700 flex items-center">
                                                        <svg class="w-4 h-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        ${criterion}
                                                    </li>
                                                `).join('')}
                                            </ul>
                                        </div>
                                    </div>

                                    ${(result.documents && result.documents.length > 0) || (result.events && result.events.length > 0) ? `
                                        <div class="mt-4">
                                            <h5 class="font-medium text-gray-900 mb-2">📄 Content for this Award</h5>
                                            <div class="space-y-2">
                                                ${result.documents ? result.documents.map(doc => `
                                                    <div class="flex items-center justify-between p-2 bg-blue-50 rounded border-l-4 border-blue-400">
                                                        <div class="flex items-center">
                                                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded mr-2">DOC</span>
                                                            <span class="text-sm text-gray-700">${doc.document_name}</span>
                                                        </div>
                                                        <span class="text-xs text-gray-500">${doc.upload_date}</span>
                                                    </div>
                                                `).join('') : ''}
                                                ${result.events ? result.events.map(event => `
                                                    <div class="flex items-center justify-between p-2 bg-purple-50 rounded border-l-4 border-purple-400">
                                                        <div class="flex items-center">
                                                            <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded mr-2">EVENT</span>
                                                            <span class="text-sm text-gray-700">${event.title}</span>
                                                        </div>
                                                        <span class="text-xs text-gray-500">${event.created_date}</span>
                                                    </div>
                                                `).join('') : ''}
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-white ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Show award checklists
        async function showAwardChecklists() {
            try {
                showNotification('Loading award checklists...', 'info');
                
                const response = await fetch('api/checklist.php?action=get_all_checklists');
                const result = await response.json();
                
                if (result.success) {
                    displayAwardChecklists(result.checklists);
                    showNotification('Award checklists loaded!', 'success');
                } else {
                    showNotification('Failed to load checklists', 'error');
                }
            } catch (error) {
                console.error('Error loading checklists:', error);
                showNotification('Error loading checklists', 'error');
            }
        }

        // Display award checklists
        function displayAwardChecklists(checklists) {
            const modal = document.createElement('div');
            modal.id = 'checklist-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-7xl w-full mx-4 max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Award Application Checklists</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('checklist-modal').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            ${checklists.map(checklist => `
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-lg font-semibold text-gray-900">${checklist.award_type}</h4>
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl">${checklist.readiness.icon}</span>
                                            <span class="px-3 py-1 rounded-full text-sm font-medium ${
                                                checklist.readiness.color === 'green' ? 'bg-green-100 text-green-800' :
                                                checklist.readiness.color === 'yellow' ? 'bg-yellow-100 text-yellow-800' :
                                                'bg-red-100 text-red-800'
                                            }">
                                                ${checklist.readiness.status}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-4 mb-4">
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-blue-600">${checklist.document_count}</div>
                                            <div class="text-xs text-gray-500">Documents</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-purple-600">${checklist.event_count}</div>
                                            <div class="text-xs text-gray-500">Events</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-green-600">${checklist.satisfied_criteria.length}/${checklist.checklist.length}</div>
                                            <div class="text-xs text-gray-500">Criteria</div>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <h5 class="font-medium text-gray-900 mb-2">Checklist Progress</h5>
                                        ${checklist.checklist.map(item => `
                                            <div class="flex items-center justify-between p-2 rounded ${
                                                item.satisfied ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
                                            }">
                                                <div class="flex items-center">
                                                    <span class="text-lg mr-2">${item.satisfied ? '✅' : '❌'}</span>
                                                    <span class="text-sm ${item.satisfied ? 'text-green-800' : 'text-red-800'}">${item.criterion}</span>
                                                </div>
                                                <button onclick="toggleCriterionStatus('${checklist.award_type}', '${item.criterion}', ${!item.satisfied})" 
                                                        class="text-xs px-2 py-1 rounded ${
                                                            item.satisfied ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'
                                                        }">
                                                    ${item.satisfied ? 'Mark Unsatisfied' : 'Mark Satisfied'}
                                                </button>
                                            </div>
                                        `).join('')}
                                    </div>

                                    ${checklist.unsatisfied_criteria.length > 0 ? `
                                        <div class="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                            <h6 class="font-medium text-yellow-800 mb-2">💡 Suggestions for Missing Criteria</h6>
                                            <ul class="text-sm text-yellow-700 space-y-1">
                                                ${checklist.checklist.filter(item => !item.satisfied).slice(0, 2).map(item => `
                                                    <li>• ${item.suggestions[0] || 'Create content that demonstrates ' + item.criterion}</li>
                                                `).join('')}
                                            </ul>
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Toggle criterion status
        async function toggleCriterionStatus(awardType, criterion, newStatus) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_criterion_status');
                formData.append('award_type', awardType);
                formData.append('criterion', criterion);
                formData.append('satisfied', newStatus);

                const response = await fetch('api/checklist.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification('Criterion status updated!', 'success');
                    // Refresh the checklist display
                    showAwardChecklists();
                } else {
                    showNotification('Failed to update criterion status', 'error');
                }
            } catch (error) {
                console.error('Error updating criterion status:', error);
                showNotification('Error updating criterion status', 'error');
            }
        }

        // Load readiness summary
        async function loadReadinessSummary() {
            try {
                showNotification('Loading readiness summary...', 'info');
                
                const response = await fetch('api/checklist.php?action=get_readiness_summary');
                const result = await response.json();
                
                if (result.success) {
                    displayReadinessSummary(result.summary);
                    showNotification('Readiness summary loaded!', 'success');
                } else {
                    showNotification('Failed to load readiness summary', 'error');
                }
            } catch (error) {
                console.error('Error loading readiness summary:', error);
                showNotification('Error loading readiness summary', 'error');
            }
        }

        // Display readiness summary
        function displayReadinessSummary(summary) {
            const container = document.getElementById('readiness-summary');
            
            if (!summary || summary.length === 0) {
                container.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500">No readiness data available</div>';
                return;
            }
            
            container.innerHTML = summary.map(item => `
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900 text-sm">${item.award_type}</h4>
                        <div class="flex items-center gap-1">
                            <span class="text-lg">${item.readiness.icon}</span>
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${
                                item.readiness.color === 'green' ? 'bg-green-100 text-green-800' :
                                item.readiness.color === 'yellow' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-red-100 text-red-800'
                            }">
                                ${item.readiness.status}
                            </span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Documents:</span>
                            <span class="font-medium text-blue-600">${item.document_count}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Events:</span>
                            <span class="font-medium text-purple-600">${item.event_count}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Criteria:</span>
                            <span class="font-medium text-green-600">${item.satisfied_count}/${item.total_count}</span>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full ${
                                item.readiness.color === 'green' ? 'bg-green-500' :
                                item.readiness.color === 'yellow' ? 'bg-yellow-500' :
                                'bg-red-500'
                            }" style="width: ${Math.round(item.readiness.satisfaction_rate * 100)}%"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 text-center">
                            ${Math.round(item.readiness.satisfaction_rate * 100)}% Complete
                        </div>
                    </div>
                    
                    <button onclick="showDetailedChecklist('${item.award_type}')" class="w-full mt-3 px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors">
                        View Details
                    </button>
                </div>
            `).join('');
        }

        // Show detailed checklist for a specific award
        async function showDetailedChecklist(awardType) {
            try {
                showNotification('Loading detailed checklist...', 'info');
                
                const response = await fetch(`api/checklist.php?action=get_award_checklist&award_type=${encodeURIComponent(awardType)}`);
                const result = await response.json();
                
                if (result.success) {
                    displayDetailedChecklist(result);
                    showNotification('Detailed checklist loaded!', 'success');
                } else {
                    showNotification('Failed to load detailed checklist', 'error');
                }
            } catch (error) {
                console.error('Error loading detailed checklist:', error);
                showNotification('Error loading detailed checklist', 'error');
            }
        }

        // Display detailed checklist for a specific award
        function displayDetailedChecklist(checklist) {
            const modal = document.createElement('div');
            modal.id = 'detailed-checklist-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">${checklist.award_type} - Detailed Checklist</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('detailed-checklist-modal').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                        <!-- Readiness Status -->
                        <div class="mb-6 p-4 rounded-lg ${
                            checklist.readiness.color === 'green' ? 'bg-green-50 border border-green-200' :
                            checklist.readiness.color === 'yellow' ? 'bg-yellow-50 border border-yellow-200' :
                            'bg-red-50 border border-red-200'
                        }">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-3xl">${checklist.readiness.icon}</span>
                                    <div>
                                        <h4 class="font-semibold text-lg ${
                                            checklist.readiness.color === 'green' ? 'text-green-800' :
                                            checklist.readiness.color === 'yellow' ? 'text-yellow-800' :
                                            'text-red-800'
                                        }">${checklist.readiness.status}</h4>
                                        <p class="text-sm ${
                                            checklist.readiness.color === 'green' ? 'text-green-600' :
                                            checklist.readiness.color === 'yellow' ? 'text-yellow-600' :
                                            'text-red-600'
                                        }">${Math.round(checklist.readiness.satisfaction_rate * 100)}% Complete</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold ${
                                        checklist.readiness.color === 'green' ? 'text-green-600' :
                                        checklist.readiness.color === 'yellow' ? 'text-yellow-600' :
                                        'text-red-600'
                                    }">${checklist.satisfied_criteria.length}/${checklist.checklist.length}</div>
                                    <div class="text-sm text-gray-500">Criteria Met</div>
                                </div>
                            </div>
                        </div>

                        <!-- Content Summary -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">${checklist.document_count}</div>
                                <div class="text-sm text-gray-600">Documents</div>
                            </div>
                            <div class="text-center p-4 bg-purple-50 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600">${checklist.event_count}</div>
                                <div class="text-sm text-gray-600">Events</div>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">${checklist.total_content}</div>
                                <div class="text-sm text-gray-600">Total Content</div>
                            </div>
                        </div>

                        <!-- Detailed Checklist -->
                        <div class="space-y-4">
                            <h5 class="font-semibold text-gray-900">Criteria Checklist</h5>
                            ${checklist.checklist.map(item => `
                                <div class="border border-gray-200 rounded-lg p-4 ${
                                    item.satisfied ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'
                                }">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="text-2xl">${item.satisfied ? '✅' : '❌'}</span>
                                                <h6 class="font-medium ${
                                                    item.satisfied ? 'text-green-800' : 'text-red-800'
                                                }">${item.criterion}</h6>
                                            </div>
                                            
                                            ${item.supporting_content.length > 0 ? `
                                                <div class="ml-8 mb-3">
                                                    <p class="text-sm text-gray-600 mb-2">Supporting Content:</p>
                                                    <div class="space-y-1">
                                                        ${item.supporting_content.map(content => `
                                                            <div class="flex items-center gap-2 text-sm">
                                                                <span class="px-2 py-1 rounded text-xs ${
                                                                    content.type === 'document' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
                                                                }">${content.type.toUpperCase()}</span>
                                                                <span class="text-gray-700">${content.title}</span>
                                                            </div>
                                                        `).join('')}
                                                    </div>
                                                </div>
                                            ` : ''}
                                            
                                            ${!item.satisfied && item.suggestions.length > 0 ? `
                                                <div class="ml-8">
                                                    <p class="text-sm text-gray-600 mb-2">💡 Suggestions:</p>
                                                    <ul class="text-sm text-gray-700 space-y-1">
                                                        ${item.suggestions.slice(0, 3).map(suggestion => `
                                                            <li>• ${suggestion}</li>
                                                        `).join('')}
                                                    </ul>
                                                </div>
                                            ` : ''}
                                        </div>
                                        
                                        <button onclick="toggleCriterionStatus('${checklist.award_type}', '${item.criterion}', ${!item.satisfied})" 
                                                class="ml-4 px-3 py-1 text-xs rounded ${
                                                    item.satisfied ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'
                                                }">
                                            ${item.satisfied ? 'Mark Unsatisfied' : 'Mark Satisfied'}
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
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
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between relative transition-all duration-300 ease-in-out">
        <div class="flex items-center space-x-4 pl-16">
            <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <h1 class="text-xl font-bold text-gray-800 cursor-pointer" onclick="location.reload()">Awards Progress</h1>
            
            <a href="dashboard.php" class="flex items-center space-x-3 hover:opacity-80 transition-opacity cursor-pointer">
            </a>
        </div>
    </nav>

    

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div id="main-content" class="p-4 pt-3 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out">

        



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
                    <div class="flex items-center gap-2">
                        <button id="add-award-btn" aria-label="Upload" class="px-3 py-1.5 bg-purple-600 text-white rounded-md shadow hover:bg-purple-700 transition-colors text-sm" onclick="showAddAwardModal()">Upload</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content Container -->
        <div id="tab-overview-content" class="tab-content">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-2 mb-3 bg-gray-50 p-2 rounded-lg">
            <div class="bg-white rounded-xl p-3 border border-gray-200 shadow-sm hover:shadow-md transition transform hover:scale-[1.01]">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xl font-extrabold text-gray-900" id="total-awards">0</div>
                        <div class="text-xs text-gray-600" data-text="awards.totalAwards">Total Awards</div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-200 shadow-sm hover:shadow-md transition transform hover:scale-[1.01]">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xl font-extrabold text-gray-900" id="leadership-score">0</div>
                        <div class="text-xs text-gray-600">International Leadership</div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-200 shadow-sm hover:shadow-md transition transform hover:scale-[1.01]">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xl font-extrabold text-gray-900" id="education-score">0</div>
                        <div class="text-xs text-gray-600">Outstanding International Education</div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-200 shadow-sm hover:shadow-md transition transform hover:scale-[1.01]">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xl font-extrabold text-gray-900" id="emerging-score">0</div>
                        <div class="text-xs text-gray-600">Emerging Leadership Award</div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-200 shadow-sm hover:shadow-md transition transform hover:scale-[1.01]">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xl font-extrabold text-gray-900" id="regional-score">0</div>
                        <div class="text-xs text-gray-600">Best Regional Office for International</div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-200 shadow-sm hover:shadow-md transition transform hover:scale-[1.01]">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xl font-extrabold text-gray-900" id="global-score">0</div>
                        <div class="text-xs text-gray-600">Global Citizenship Award</div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
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
                            <p class="text-xl font-bold text-gray-900" id="leadership-score">0</p>
                            <p class="text-xs text-blue-600">Documents Count</p>
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
                            <p class="text-xl font-bold text-gray-900" id="education-score">0</p>
                            <p class="text-xs text-green-600">Documents Count</p>
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
                            <p class="text-xl font-bold text-gray-900" id="emerging-score">0</p>
                            <p class="text-xs text-purple-600">Documents Count</p>
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
                            <p class="text-xl font-bold text-gray-900" id="regional-score">0</p>
                            <p class="text-sm text-orange-600">Documents Count</p>
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
                            <p class="text-xl font-bold text-gray-900" id="citizenship-score">0</p>
                            <p class="text-sm text-red-600">Documents Count</p>
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
                        <!-- International Leadership Award -->
                        <div class="border-l-4 border-blue-500 pl-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                            <h4 class="font-medium text-gray-900">International Leadership Award</h4>
                            <p class="text-sm text-gray-600">Demonstrated leadership in international partnerships, student exchanges, and global initiatives</p>
                        </div>
                                <button onclick="toggleAwardDetails('leadership')" class="ml-4 p-2 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors">
                                    <svg id="leadership-arrow" class="w-5 h-5 text-blue-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="leadership-details" class="hidden mt-3 p-4 bg-blue-50 rounded-lg">
                                <div class="space-y-2">
                                    <div class="flex items-center text-sm text-blue-800">
                                        <input type="checkbox" class="mr-3 rounded border-blue-300 text-blue-600 focus:ring-blue-500" 
                                               data-award="leadership" data-criterion="Champion Bold Innovation"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Champion Bold Innovation</span>
                                        <span class="text-xs text-blue-600 ml-2" id="leadership-Champion Bold Innovation-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-blue-800">
                                        <input type="checkbox" class="mr-3 rounded border-blue-300 text-blue-600 focus:ring-blue-500" 
                                               data-award="leadership" data-criterion="Cultivate Global Citizens"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Cultivate Global Citizens</span>
                                        <span class="text-xs text-blue-600 ml-2" id="leadership-Cultivate Global Citizens-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-blue-800">
                                        <input type="checkbox" class="mr-3 rounded border-blue-300 text-blue-600 focus:ring-blue-500" 
                                               data-award="leadership" data-criterion="Nurture Lifelong Learning"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Nurture Lifelong Learning</span>
                                        <span class="text-xs text-blue-600 ml-2" id="leadership-Nurture Lifelong Learning-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-blue-800">
                                        <input type="checkbox" class="mr-3 rounded border-blue-300 text-blue-600 focus:ring-blue-500" 
                                               data-award="leadership" data-criterion="Lead with Purpose"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Lead with Purpose</span>
                                        <span class="text-xs text-blue-600 ml-2" id="leadership-Lead with Purpose-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-blue-800">
                                        <input type="checkbox" class="mr-3 rounded border-blue-300 text-blue-600 focus:ring-blue-500" 
                                               data-award="leadership" data-criterion="Ethical and Inclusive Leadership"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Ethical and Inclusive Leadership</span>
                                        <span class="text-xs text-blue-600 ml-2" id="leadership-Ethical and Inclusive Leadership-status">Auto-assigned</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Outstanding International Education Program -->
                        <div class="border-l-4 border-green-500 pl-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Outstanding International Education Program</h4>
                            <p class="text-sm text-gray-600">Excellence in international curriculum, research collaborations, and academic partnerships</p>
                        </div>
                                <button onclick="toggleAwardDetails('education')" class="ml-4 p-2 rounded-lg bg-green-50 hover:bg-green-100 transition-colors">
                                    <svg id="education-arrow" class="w-5 h-5 text-green-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="education-details" class="hidden mt-3 p-4 bg-green-50 rounded-lg">
                                <div class="space-y-2">
                                    <div class="flex items-center text-sm text-green-800">
                                        <input type="checkbox" class="mr-3 rounded border-green-300 text-green-600 focus:ring-green-500" 
                                               data-award="education" data-criterion="Expand Access to Global Opportunities"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Expand Access to Global Opportunities</span>
                                        <span class="text-xs text-green-600 ml-2" id="education-Expand Access to Global Opportunities-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-green-800">
                                        <input type="checkbox" class="mr-3 rounded border-green-300 text-green-600 focus:ring-green-500" 
                                               data-award="education" data-criterion="Foster Collaborative Innovation"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Foster Collaborative Innovation</span>
                                        <span class="text-xs text-green-600 ml-2" id="education-Foster Collaborative Innovation-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-green-800">
                                        <input type="checkbox" class="mr-3 rounded border-green-300 text-green-600 focus:ring-green-500" 
                                               data-award="education" data-criterion="Embrace Inclusivity and Beyond"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Embrace Inclusivity and Beyond</span>
                                        <span class="text-xs text-green-600 ml-2" id="education-Embrace Inclusivity and Beyond-status">Auto-assigned</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Emerging Leadership Award -->
                        <div class="border-l-4 border-purple-500 pl-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Emerging Leadership Award</h4>
                            <p class="text-sm text-gray-600">Innovative approaches to international education and emerging global opportunities</p>
                        </div>
                                <button onclick="toggleAwardDetails('emerging')" class="ml-4 p-2 rounded-lg bg-purple-50 hover:bg-purple-100 transition-colors">
                                    <svg id="emerging-arrow" class="w-5 h-5 text-purple-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="emerging-details" class="hidden mt-3 p-4 bg-purple-50 rounded-lg">
                                <div class="space-y-2">
                                    <div class="flex items-center text-sm text-purple-800">
                                        <input type="checkbox" class="mr-3 rounded border-purple-300 text-purple-600 focus:ring-purple-500" 
                                               data-award="emerging" data-criterion="Innovation"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Innovation</span>
                                        <span class="text-xs text-purple-600 ml-2" id="emerging-Innovation-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-purple-800">
                                        <input type="checkbox" class="mr-3 rounded border-purple-300 text-purple-600 focus:ring-purple-500" 
                                               data-award="emerging" data-criterion="Strategic and Inclusive Growth"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Strategic and Inclusive Growth</span>
                                        <span class="text-xs text-purple-600 ml-2" id="emerging-Strategic and Inclusive Growth-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-purple-800">
                                        <input type="checkbox" class="mr-3 rounded border-purple-300 text-purple-600 focus:ring-purple-500" 
                                               data-award="emerging" data-criterion="Empowerment of Others"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Empowerment of Others</span>
                                        <span class="text-xs text-purple-600 ml-2" id="emerging-Empowerment of Others-status">Auto-assigned</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Best Regional Office for International -->
                        <div class="border-l-4 border-orange-500 pl-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Best Regional Office for International</h4>
                            <p class="text-sm text-gray-600">Effective regional coordination and management of international programs</p>
                        </div>
                                <button onclick="toggleAwardDetails('regional')" class="ml-4 p-2 rounded-lg bg-orange-50 hover:bg-orange-100 transition-colors">
                                    <svg id="regional-arrow" class="w-5 h-5 text-orange-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="regional-details" class="hidden mt-3 p-4 bg-orange-50 rounded-lg">
                                <div class="space-y-2">
                                    <div class="flex items-center text-sm text-orange-800">
                                        <input type="checkbox" class="mr-3 rounded border-orange-300 text-orange-600 focus:ring-orange-500" 
                                               data-award="regional" data-criterion="Comprehensive Internationalization Efforts"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Comprehensive Internationalization Efforts</span>
                                        <span class="text-xs text-orange-600 ml-2" id="regional-Comprehensive Internationalization Efforts-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-orange-800">
                                        <input type="checkbox" class="mr-3 rounded border-orange-300 text-orange-600 focus:ring-orange-500" 
                                               data-award="regional" data-criterion="Cooperation and Collaboration"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Cooperation and Collaboration</span>
                                        <span class="text-xs text-orange-600 ml-2" id="regional-Cooperation and Collaboration-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-orange-800">
                                        <input type="checkbox" class="mr-3 rounded border-orange-300 text-orange-600 focus:ring-orange-500" 
                                               data-award="regional" data-criterion="Measurable Impact"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Measurable Impact</span>
                                        <span class="text-xs text-orange-600 ml-2" id="regional-Measurable Impact-status">Auto-assigned</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Global Citizenship Award -->
                        <div class="border-l-4 border-red-500 pl-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Global Citizenship Award</h4>
                            <p class="text-sm text-gray-600">Promotion of global awareness, cultural exchange, and international community engagement</p>
                        </div>
                                <button onclick="toggleAwardDetails('citizenship')" class="ml-4 p-2 rounded-lg bg-red-50 hover:bg-red-100 transition-colors">
                                    <svg id="citizenship-arrow" class="w-5 h-5 text-red-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="citizenship-details" class="hidden mt-3 p-4 bg-red-50 rounded-lg">
                                <div class="space-y-2">
                                    <div class="flex items-center text-sm text-red-800">
                                        <input type="checkbox" class="mr-3 rounded border-red-300 text-red-600 focus:ring-red-500" 
                                               data-award="global" data-criterion="Ignite Intercultural Understanding"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Ignite Intercultural Understanding</span>
                                        <span class="text-xs text-red-600 ml-2" id="global-Ignite Intercultural Understanding-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-red-800">
                                        <input type="checkbox" class="mr-3 rounded border-red-300 text-red-600 focus:ring-red-500" 
                                               data-award="global" data-criterion="Empower Changemakers"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Empower Changemakers</span>
                                        <span class="text-xs text-red-600 ml-2" id="global-Empower Changemakers-status">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center text-sm text-red-800">
                                        <input type="checkbox" class="mr-3 rounded border-red-300 text-red-600 focus:ring-red-500" 
                                               data-award="global" data-criterion="Cultivate Active Engagement"
                                               onchange="updateCriterionStatus(this)" disabled>
                                        <span class="flex-1">Cultivate Active Engagement</span>
                                        <span class="text-xs text-red-600 ml-2" id="global-Cultivate Active Engagement-status">Auto-assigned</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Award Readiness Summary -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Award Readiness Summary</h3>
                        <button onclick="loadReadinessSummary()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            Refresh Status
                        </button>
                    </div>
                    
                    <div id="readiness-summary" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Readiness cards will be loaded here -->
                        <div class="col-span-full text-center py-8 text-gray-500">
                            Click "Refresh Status" to load award readiness information
                        </div>
                    </div>
                </div>

                <!-- Matching Analysis -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Document Analysis</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Documents Analyzed</span>
                            <span class="text-sm text-gray-900" id="activities-count">0</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Best Match</span>
                            <span class="text-sm text-green-600 font-medium" id="best-match">None</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Total Documents</span>
                            <span class="text-sm text-blue-600 font-medium" id="overall-score">0</span>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-4">
                        <button onclick="runAwardMatch()" class="flex-1 bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors">
                        Run AwardMatch Analysis
                    </button>
                        <button onclick="analyzeAllDocuments()" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Analyze All Content
                        </button>
                        <button onclick="generateAwardReport()" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors ml-2">
                            Generate Report
                        </button>
                    </div>
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

    <!-- Missing Data Modal -->
    <div id="awardMatchMissingDataModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" onclick="hideAwardMatchMissingDataModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full" onclick="event.stopPropagation()">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Action Required</h3>
                    </div>
                </div>
                <div class="p-6 space-y-3">
                    <p class="text-sm text-gray-700">Please upload both award and record data before running analysis.</p>
                </div>
                <div class="p-4 border-t border-gray-200 flex justify-end">
                    <button onclick="hideAwardMatchMissingDataModal()" class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800">Close</button>
                </div>
            </div>
        </div>
    </div>

    

    <!-- Add Award Confirmation Modal -->
    <div id="addAwardConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" onclick="this.classList.add('hidden')">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full" onclick="event.stopPropagation()">
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

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50" onclick="this.classList.add('hidden')">
        <div class="relative p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-lg shadow-lg" onclick="event.stopPropagation()">
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
    <div id="viewAwardModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50" onclick="this.classList.add('hidden')">
        <div class="relative p-8 bg-white w-full max-w-2xl m-auto flex-col flex rounded-lg shadow-lg" onclick="event.stopPropagation()">
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
    <div id="add-award-modal" class="fixed inset-0 bg-black/50 z-[70] hidden" onclick="this.classList.add('hidden')">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg" onclick="event.stopPropagation()">
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
    <div id="import-data-modal" class="fixed inset-0 bg-black/50 z-[70] hidden" onclick="this.classList.add('hidden')">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl" onclick="event.stopPropagation()">
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
        // Zero Awards Progress counters until data is uploaded
        (function(){
            const ids = ['total-awards','academic-count','research-count','leadership-count'];
            ids.forEach(function(id){ var el = document.getElementById(id); if (el) el.textContent = '0'; });
        })();
    });
    
    // Also try on window load
    window.addEventListener('load', function() {
        if (typeof Chart !== 'undefined' && !window.monthlyTrendChart) {
            renderWhenReady();
        }
    });

    function renderMonthlyTrendChart() {
        // Rendering monthly trend chart
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
        // Fetch real data from API instead of using hardcoded values
        fetch('api/awards.php?action=get_monthly_trends')
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    const thisYearData = result.data.thisYear || [1200, 1800, 1500, 2200, 2800, 2500, 3200, 7200, 3800, 4200, 3500, 4000];
                    const lastYearData = result.data.lastYear || [1000, 1400, 1600, 1900, 2100, 1800, 2400, 2800, 2200, 2600, 2000, 2400];
                    
                    createMonthlyTrendChart(thisYearData, lastYearData);
                } else {
                    // Fallback to default data if API fails
                    const thisYearData = [1200, 1800, 1500, 2200, 2800, 2500, 3200, 7200, 3800, 4200, 3500, 4000];
                    const lastYearData = [1000, 1400, 1600, 1900, 2100, 1800, 2400, 2800, 2200, 2600, 2000, 2400];
                    createMonthlyTrendChart(thisYearData, lastYearData);
                }
            })
            .catch(error => {
                console.error('Error loading monthly trends:', error);
                // Fallback to default data
                const thisYearData = [1200, 1800, 1500, 2200, 2800, 2500, 3200, 7200, 3800, 4200, 3500, 4000];
                const lastYearData = [1000, 1400, 1600, 1900, 2100, 1800, 2400, 2800, 2200, 2600, 2000, 2400];
                createMonthlyTrendChart(thisYearData, lastYearData);
            });
        
        function createMonthlyTrendChart(thisYearData, lastYearData) {
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
                            data: thisYearData.slice(0, 8), // Use first 8 months of this year data
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
            if (window.toggleSidebar) {
                window.toggleSidebar();
            } else {
                try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
            }
        }
    });
    </script>

    

    <script>
        // Clean script block - 2024-12-19-3
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
        
        // Toggle award details dropdown
        function toggleAwardDetails(awardType) {
            const detailsElement = document.getElementById(`${awardType}-details`);
            const arrowElement = document.getElementById(`${awardType}-arrow`);
            
            if (detailsElement && arrowElement) {
                const isHidden = detailsElement.classList.contains('hidden');
                
                if (isHidden) {
                    // Show details
                    detailsElement.classList.remove('hidden');
                    arrowElement.style.transform = 'rotate(180deg)';
                } else {
                    // Hide details
                    detailsElement.classList.add('hidden');
                    arrowElement.style.transform = 'rotate(0deg)';
                }
            }
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
                        display: false
                    }
                }
            }
        });
    }

    // Award Criteria Dropdown and Linking Functions
    function updateCriterionStatus(checkbox) {
        const award = checkbox.dataset.award;
        const criterion = checkbox.dataset.criterion;
        const isChecked = checkbox.checked;
        
        // Update the criterion status via API
        fetch('api/checklist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_criterion_status&award_type=${encodeURIComponent(award)}&criterion=${encodeURIComponent(criterion)}&satisfied=${isChecked ? '1' : '0'}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Criterion ${criterion} status updated to ${isChecked ? 'satisfied' : 'not satisfied'}`);
                // Refresh the readiness summary if it exists
                if (typeof loadReadinessSummary === 'function') {
                    loadReadinessSummary();
                }
            } else {
                console.error('Failed to update criterion status:', data.message);
                // Revert checkbox state
                checkbox.checked = !isChecked;
            }
        })
        .catch(error => {
            console.error('Error updating criterion status:', error);
            // Revert checkbox state
            checkbox.checked = !isChecked;
        });
    }

    // Function to update checklist status automatically based on counters
    function updateChecklistStatusAutomatically() {
        const awardTypes = ['leadership', 'education', 'emerging', 'regional', 'global'];
        
        awardTypes.forEach(awardType => {
            fetch(`api/checklist.php?action=get_checklist_status&award_type=${encodeURIComponent(awardType)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.status.forEach(criterionStatus => {
                        const checkbox = document.querySelector(`input[data-award="${awardType}"][data-criterion="${criterionStatus.criterion}"]`);
                        const statusElement = document.getElementById(`${awardType}-${criterionStatus.criterion}-status`);
                        
                        if (checkbox) {
                            checkbox.checked = criterionStatus.satisfied;
                        }
                        
                        if (statusElement) {
                            if (criterionStatus.satisfied) {
                                statusElement.textContent = 'Auto-assigned';
                                statusElement.className = 'text-xs text-green-600 ml-2';
                            } else {
                                statusElement.textContent = 'Not assigned';
                                statusElement.className = 'text-xs text-gray-500 ml-2';
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error(`Error loading checklist status for ${awardType}:`, error);
            });
        });
    }

    // Load available content when page loads
    document.addEventListener('DOMContentLoaded', function() {
        updateChecklistStatusAutomatically();
        // Set up periodic refresh of checklist data
        setInterval(refreshChecklistData, 30000); // Refresh every 30 seconds
    });

    // Function to refresh checklist data
    function refreshChecklistData() {
        if (typeof loadAwardDocumentCounts === 'function') {
            loadAwardDocumentCounts();
        }
        if (typeof loadReadinessSummary === 'function') {
            loadReadinessSummary();
        }
        if (typeof updateChecklistStatusAutomatically === 'function') {
            updateChecklistStatusAutomatically();
        }
    }

    function generateAwardReport() {
        // Show loading state
        const reportButton = event.target;
        const originalText = reportButton.textContent;
        reportButton.textContent = 'Generating...';
        reportButton.disabled = true;

        // Fetch readiness summary for all awards
        fetch('api/checklist.php?action=get_readiness_summary')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAwardReport(data.summary);
            } else {
                console.error('Failed to generate report:', data.message);
                alert('Failed to generate report. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error generating report:', error);
            alert('Error generating report. Please try again.');
        })
        .finally(() => {
            // Restore button state
            reportButton.textContent = originalText;
            reportButton.disabled = false;
        });
    }

    function displayAwardReport(summary) {
        // Create modal for the report
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-900">Award Application Report</h2>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-gray-600 mt-2">Comprehensive overview of award readiness and missing requirements</p>
                </div>
                <div class="p-6">
                    ${generateReportContent(summary)}
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end">
                    <button onclick="this.closest('.fixed').remove()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition-colors">
                        Close
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }

    function generateReportContent(summary) {
        const awardNames = {
            'leadership': 'Internationalization (IZN) Leadership Award',
            'education': 'Outstanding International Education Program Award',
            'emerging': 'Emerging Leadership Award',
            'regional': 'Best Regional Office for Internationalization Award',
            'global': 'Global Citizenship Award'
        };

        let content = '<div class="space-y-6">';
        
        summary.forEach(award => {
            const readinessColor = award.readiness === 'Ready to Apply' ? 'green' : 
                                 award.readiness === 'Nearly Ready' ? 'yellow' : 'red';
            const readinessIcon = award.readiness === 'Ready to Apply' ? '✅' : 
                                award.readiness === 'Nearly Ready' ? '⚠️' : '❌';
            
            content += `
                <div class="border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-gray-900">${awardNames[award.award_type] || award.award_type}</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-${readinessColor}-100 text-${readinessColor}-800">
                            ${readinessIcon} ${award.readiness}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">${award.satisfied_count}/${award.total_count}</div>
                            <div class="text-sm text-blue-800">Criteria Satisfied</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">${award.document_count}</div>
                            <div class="text-sm text-green-800">Documents Uploaded</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">${award.event_count}</div>
                            <div class="text-sm text-purple-800">Events Created</div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Progress</h4>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-${readinessColor}-500 h-2 rounded-full" style="width: ${(award.satisfied_count / award.total_count) * 100}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">
                            ${award.total_count - award.satisfied_count} criteria remaining to complete this award
                        </p>
                    </div>
                </div>
            `;
        });
        
        content += '</div>';
        
        // Add summary statistics
        const totalAwards = summary.length;
        const readyAwards = summary.filter(a => a.readiness === 'Ready to Apply').length;
        const nearlyReadyAwards = summary.filter(a => a.readiness === 'Nearly Ready').length;
        const incompleteAwards = summary.filter(a => a.readiness === 'Incomplete').length;
        
        content = `
            <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Overall Summary</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">${totalAwards}</div>
                        <div class="text-sm text-blue-800" data-text="awards.totalAwards">Total Awards</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${readyAwards}</div>
                        <div class="text-sm text-green-800">Ready to Apply</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">${nearlyReadyAwards}</div>
                        <div class="text-sm text-yellow-800">Nearly Ready</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">${incompleteAwards}</div>
                        <div class="text-sm text-red-800">Incomplete</div>
                    </div>
                </div>
            </div>
        ` + content;
        
        return content;
    }

    // End of JavaScript - Cache buster: 2024-12-19-2
    </script>

</body>

</html>

