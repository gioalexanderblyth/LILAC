<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    
    <title>LILAC Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            generateCalendar();
            updateCurrentDate();
            // Function to adjust layout
function applySidebarLayout(isOpen) {
    var main = document.getElementById('main-content');
    if (isOpen) {
        if (main) main.classList.add('ml-64');
    } else {
        if (main) main.classList.remove('ml-64');
    }
}

            // Desktop-only: start with spacing applied
            applySidebarLayout(true);
            
            // Listen to sidebar state changes
window.addEventListener('sidebar:state', function (e) {
    applySidebarLayout(!!(e && e.detail && e.detail.open));
});
            // On resize keep spacing applied (desktop-only)
            window.addEventListener('resize', function () {
                applySidebarLayout(true);
            });
            
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

        // Main dashboard data loading function
        function loadDashboardData() {
            // Show loading skeletons first
            showLoadingSkeletons();
            
            // Fetch data from multiple sources
            Promise.all([
                fetch('api/dashboard.php?action=get_overview'),
                fetch('api/scheduler.php?action=get_upcoming'),
                fetch('api/events_activities.php?action=get_upcoming')
            ])
            .then(responses => Promise.all(responses.map(r => r.json())))
            .then(([dashboardData, meetingsData, eventsData]) => {
                if (dashboardData.success) {
                    // Merge meetings and events data
                    const mergedData = {
                        ...dashboardData.data,
                        meetings: {
                            ...dashboardData.data.meetings,
                            upcoming: meetingsData.success ? meetingsData.meetings : []
                        },
                        events: eventsData.success ? eventsData.events : []
                    };
                    updateDashboard(mergedData);
                } else {
                    console.error('API Error:', dashboardData.message);
                    showDashboardError();
                }
            })
            .catch(error => {
                console.error('Error loading dashboard data:', error);
                showDashboardError();
            });
        }

        function updateDashboard(data) {
            updateMeetings(data.meetings?.upcoming || []);
            updateMOUsAndAwards(data);
            updateRecentDocuments(data.documents?.recent || []);
            updateTemplates(data.templates || []);
            updateCardCounts(data);
        }

        function updateMeetings(meetings) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Find the Upcoming Meetings section by looking for the container with the specific heading
            const meetingsSection = Array.from(document.querySelectorAll('h3')).find(h3 => 
                h3.textContent.includes('Upcoming Meetings')
            );
            if (!meetingsSection) return;
            
            const meetingsContainer = meetingsSection.closest('.relative').querySelector('.space-y-3');
            if (!meetingsContainer) return;

            if (meetings.length === 0) {
                meetingsContainer.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <div class="w-16 h-16 border-2 border-black rounded-2xl mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <p class="font-semibold text-gray-700 text-lg mb-2">No meetings scheduled</p>
                        <p class="text-sm text-gray-500">Schedule a meeting to get started</p>
                    </div>
                `;
                return;
            }

            const upcomingMeetings = meetings.slice(0, 3);
            meetingsContainer.innerHTML = upcomingMeetings.map(meeting => {
                const meetingDate = new Date(meeting.meeting_date + 'T' + meeting.meeting_time);
                const isToday = meetingDate.toDateString() === today.toDateString();
                const tomorrow = new Date(today);
                tomorrow.setDate(tomorrow.getDate() + 1);
                const isTomorrow = meetingDate.toDateString() === tomorrow.toDateString();
                
                // Format date and time nicely
                let dateText = '';
                if (isToday) {
                    dateText = 'Today';
                } else if (isTomorrow) {
                    dateText = 'Tomorrow';
                } else {
                    dateText = meetingDate.toLocaleDateString('en-US', { 
                        weekday: 'short', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                }
                
                const timeText = meetingDate.toLocaleTimeString([], {
                    hour: '2-digit', 
                    minute: '2-digit'
                });
                
                return `
                    <div class="p-5 ${isToday ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'} rounded-xl border hover:shadow-md transition-all duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-700 text-base mb-2">${meeting.title}</h4>
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-600 font-medium flex items-center">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-2"></span>
                                        ${dateText}, ${timeText}
                                    </p>
                                    ${meeting.location ? `<p class="text-sm text-gray-600 flex items-center">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-2"></span>
                                        ${meeting.location}
                                    </p>` : ''}
                                </div>
                            </div>
                            ${isToday ? `<span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-semibold ml-3">Today</span>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateMOUsAndAwards(data) {
            const mous = data.mous?.active || [];
            const awards = data.awards?.recent || [];
            
            // Update MOUs count
            const activeMOUs = mous.filter(mou => mou.status === 'Active');
            const mouCountElement = document.getElementById('active-mou-count');
            if (mouCountElement) {
                mouCountElement.textContent = activeMOUs.length;
            }

            // Update Awards count
            const awardCountElement = document.getElementById('awards-received-count');
            if (awardCountElement) {
                awardCountElement.textContent = awards.length;
            }

            // Update Latest MOU
            const latestMOUContainer = document.getElementById('latest-mou-details');
            if (latestMOUContainer) {
                if (mous.length > 0) {
                    const latestMOU = mous.sort((a, b) => new Date(b.signed_date) - new Date(a.signed_date))[0];
                    latestMOUContainer.innerHTML = `
                        <p class="text-sm text-green-700 font-medium text-center">Latest: ${latestMOU.organization}</p>
                    `;
                } else {
                    latestMOUContainer.innerHTML = `
                        <p class="text-sm text-green-700 font-medium text-center">Create an MOU to get started</p>
                    `;
                }
            }

            // Update Recent Award
            const latestAwardContainer = document.getElementById('latest-award-details');
            if (latestAwardContainer) {
                if (awards.length > 0) {
                    const latestAward = awards.sort((a, b) => new Date(b.date_received) - new Date(a.date_received))[0];
                    latestAwardContainer.innerHTML = `
                        <p class="text-sm text-purple-700 font-medium text-center">Recent: ${latestAward.title}</p>
                    `;
                } else {
                    latestAwardContainer.innerHTML = `
                        <p class="text-sm text-purple-700 font-medium text-center">Add an award to get started</p>
                    `;
                }
            }
        }

        function updateRecentDocuments(documents) {
            // Find the Recent Documents section by looking for the container with the specific heading
            const documentsSection = Array.from(document.querySelectorAll('h3')).find(h3 => 
                h3.textContent.includes('Recent Documents')
            );
            if (!documentsSection) return;

            const documentsContainer = documentsSection.closest('.relative').querySelector('.space-y-3');
            if (!documentsContainer) return;

            if (documents.length === 0) {
                documentsContainer.innerHTML = `
                    <div class="text-center py-6 text-gray-500">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-2xl mx-auto mb-3 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        </div>
                        <p class="font-medium text-gray-600">No documents yet</p>
                        <p class="text-sm mt-1">Upload a document to get started</p>
                    </div>
                `;
                return;
            }

            documentsContainer.innerHTML = documents.slice(0, 3).map(doc => {
                const dateAdded = new Date(doc.upload_date);
                const timeAgo = getTimeAgo(dateAdded);
                const isRecent = (new Date() - dateAdded) < (24 * 60 * 60 * 1000); // Less than 24 hours
                
                return `
                    <div class="p-4 ${isRecent ? 'bg-purple-50 border-purple-200' : 'bg-gray-50 border-gray-200'} rounded-xl border">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-semibold ${isRecent ? 'text-purple-800' : 'text-gray-800'} text-sm">${doc.document_name || doc.title}</h4>
                                <p class="text-xs ${isRecent ? 'text-purple-600' : 'text-gray-600'} mt-1">
                                    📄 ${doc.category || 'Document'} • ${timeAgo}
                                </p>
                            </div>
                            ${isRecent ? `<span class="bg-purple-100 text-purple-700 text-xs px-2 py-1 rounded-full font-medium">New</span>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateTemplates(templatesData) {
            // Update templates count in the dashboard
            const templatesCount = templatesData.total || 0;
            
            // Update any template count displays
            const templateCountElements = document.querySelectorAll('[data-template-count]');
            templateCountElements.forEach(el => {
                el.textContent = templatesCount;
            });
            
            // Update templates section if it exists
            const templatesSection = Array.from(document.querySelectorAll('.bg-white.rounded-xl.shadow-md.p-6'))
                .find(section => {
                    const header = section.querySelector('h3');
                    return header && header.textContent.includes('Templates');
                });

            if (templatesSection) {
            const templatesContainer = templatesSection.querySelector('.space-y-3');
                if (templatesContainer) {
                templatesContainer.innerHTML = `
                        <div class="text-center py-4 text-gray-500">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                            <p class="font-medium text-gray-600">${templatesCount} Templates Available</p>
                            <a href="templates.php" class="text-sm text-blue-600 hover:text-blue-800">View all templates →</a>
                    </div>
                }
            }
        }

        function updateCardCounts(data) {
            // Update Documents count and status with animation
            const documentsCount = data.documents?.recent?.length || 0;
            const documentCountElement = document.getElementById('total-documents-count');
            const recentUploadsElement = document.getElementById('recent-uploads');
            
            if (documentCountElement) {
                animateNumber(documentCountElement, documentsCount);
            }
            
            if (recentUploadsElement) {
                if (documentsCount > 0) {
                    const latestDoc = data.documents.recent[0];
                    recentUploadsElement.textContent = `Latest: ${latestDoc.document_name || latestDoc.title}`;
            } else {
                    recentUploadsElement.textContent = 'Upload a document to get started';
                }
            }

            // Update Meetings count and status with animation
            const meetingsCount = data.meetings?.upcoming?.length || 0;
            const meetingCountElement = document.getElementById('upcoming-meetings-count');
            const nextMeetingElement = document.getElementById('next-meeting');
            
            if (meetingCountElement) {
                animateNumber(meetingCountElement, meetingsCount);
            }
            
            if (nextMeetingElement) {
                if (meetingsCount > 0) {
                    const nextMeeting = data.meetings.upcoming[0];
                    nextMeetingElement.textContent = `Next: ${nextMeeting.title}`;
                } else {
                    nextMeetingElement.textContent = 'Schedule a meeting to get started';
                }
            }

            // Update progress bars
            updateProgressBars(data);
        }

        function updateProgressBars(data) {
            // Calculate progress percentages (example targets)
            const targets = {
                mous: 10,
                awards: 15,
                documents: 50,
                meetings: 8
            };

            const mousCount = data.mous?.active?.length || 0;
            const awardsCount = data.awards?.recent?.length || 0;
            const documentsCount = data.documents?.recent?.length || 0;
            const meetingsCount = data.meetings?.upcoming?.length || 0;

            // Update MOUs progress bar
            const mousProgress = Math.min((mousCount / targets.mous) * 100, 100);
            const mousProgressBar = document.querySelector('.bg-gradient-to-r.from-green-400.to-emerald-500');
            if (mousProgressBar) {
                mousProgressBar.style.width = `${mousProgress}%`;
            }

            // Update Awards progress bar
            const awardsProgress = Math.min((awardsCount / targets.awards) * 100, 100);
            const awardsProgressBar = document.querySelector('.bg-gradient-to-r.from-purple-400.to-pink-500');
            if (awardsProgressBar) {
                awardsProgressBar.style.width = `${awardsProgress}%`;
            }

            // Update Documents progress bar
            const documentsProgress = Math.min((documentsCount / targets.documents) * 100, 100);
            const documentsProgressBar = document.querySelector('.bg-gradient-to-r.from-blue-400.to-cyan-500');
            if (documentsProgressBar) {
                documentsProgressBar.style.width = `${documentsProgress}%`;
            }

            // Update Meetings progress bar
            const meetingsProgress = Math.min((meetingsCount / targets.meetings) * 100, 100);
            const meetingsProgressBar = document.querySelector('.bg-gradient-to-r.from-orange-400.to-red-500');
            if (meetingsProgressBar) {
                meetingsProgressBar.style.width = `${meetingsProgress}%`;
            }
        }

        // Animate number counting up
        function animateNumber(element, targetNumber) {
            const startNumber = 0;
            const duration = 1000; // 1 second
            const startTime = performance.now();
            
            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function for smooth animation
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const currentNumber = Math.floor(startNumber + (targetNumber - startNumber) * easeOutQuart);
                
                element.textContent = currentNumber;
                
                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                } else {
                    element.textContent = targetNumber;
                    // Add a subtle pulse effect when animation completes
                    element.classList.add('animate-pulse');
                    setTimeout(() => element.classList.remove('animate-pulse'), 500);
                }
            }
            
            requestAnimationFrame(updateNumber);
        }

        // Helper Functions
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        }

        function showDashboardError() {
            console.error('Dashboard data loading failed');
        }

        // Loading Skeleton Functions
        function showLoadingSkeletons() {
            // Stats cards skeletons
            const statElements = ['active-mou-count', 'awards-received-count', 'total-documents-count', 'upcoming-meetings-count'];
            statElements.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.innerHTML = '<div class="animate-pulse bg-gray-300 h-8 w-12 rounded"></div>';
                }
            });

            // Status text skeletons
            const statusElements = ['recent-uploads', 'next-meeting', 'latest-mou-details', 'latest-award-details'];
            statusElements.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.innerHTML = '<div class="animate-pulse bg-gray-300 h-4 w-32 rounded"></div>';
                }
            });

            // Meetings list skeleton
            const meetingsList = document.getElementById('meetings-list');
            if (meetingsList) {
                meetingsList.innerHTML = `
                    <div class="space-y-3">
                        ${Array(3).fill().map(() => `
                            <div class="p-4 bg-gray-50 rounded-xl border animate-pulse">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
                                        <div class="h-3 bg-gray-300 rounded w-1/2 mb-1"></div>
                                        <div class="h-3 bg-gray-300 rounded w-1/3"></div>
                                    </div>
                                    <div class="h-6 bg-gray-300 rounded w-12"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            // Documents list skeleton
            const documentsList = document.getElementById('documents-list');
            if (documentsList) {
                documentsList.innerHTML = `
                    <div class="space-y-3">
                        ${Array(3).fill().map(() => `
                            <div class="p-4 bg-gray-50 rounded-xl border animate-pulse">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
                                        <div class="h-3 bg-gray-300 rounded w-1/2"></div>
                                    </div>
                                    <div class="h-6 bg-gray-300 rounded w-12"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }
        }

        function getTimeAgo(date) {
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            const diffInMinutes = Math.floor(diffInSeconds / 60);
            const diffInHours = Math.floor(diffInMinutes / 60);
            const diffInDays = Math.floor(diffInHours / 24);

            if (diffInSeconds < 60) return 'just now';
            if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
            if (diffInHours < 24) return `${diffInHours}h ago`;
            if (diffInDays < 7) return `${diffInDays}d ago`;
            return date.toLocaleDateString();
        }

        // Calendar Functions
        let currentCalendarDate = new Date();
        let selectedDate = new Date();

        function generateCalendar(date = currentCalendarDate) {
            const currentMonthYearEl = document.getElementById('current-month-year');
            const calendarDaysEl = document.getElementById('calendar-days');
            
            if (!currentMonthYearEl || !calendarDaysEl) return;

            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];
            currentMonthYearEl.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;

            calendarDaysEl.innerHTML = '';
            const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
            const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            for (let i = 0; i < 42; i++) {
                const currentDate = new Date(startDate);
                currentDate.setDate(startDate.getDate() + i);
                
                const dayEl = document.createElement('div');
                dayEl.className = 'h-10 flex items-center justify-center text-sm cursor-pointer rounded-xl transition-all duration-200 font-medium';
                dayEl.textContent = currentDate.getDate();
                
                if (currentDate.getMonth() === date.getMonth()) {
                    dayEl.className += ' text-gray-800 hover:bg-gray-100 hover:shadow-md';
                } else {
                    dayEl.className += ' text-gray-400';
                }
                
                const today = new Date();
                if (currentDate.toDateString() === today.toDateString()) {
                    dayEl.className += ' bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold shadow-lg hover:shadow-xl transform hover:scale-105';
                }
                
                if (currentDate.toDateString() === selectedDate.toDateString() && currentDate.toDateString() !== today.toDateString()) {
                    dayEl.className += ' bg-indigo-100 border-2 border-indigo-300 text-indigo-800';
                }
                
                dayEl.addEventListener('click', () => {
                    selectedDate = new Date(currentDate);
                    generateCalendar(date);
                    loadTodayEvents(selectedDate);
                });
                
                calendarDaysEl.appendChild(dayEl);
            }

            loadTodayEvents(selectedDate);
        }

        function loadTodayEvents(date = selectedDate) {
            // This function can be enhanced to load events from the API
            const selectedDateEl = document.getElementById('selected-date');
            const dayEventsEl = document.getElementById('day-events');
            
            if (!selectedDateEl || !dayEventsEl) return;

            const formattedDate = date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            selectedDateEl.textContent = `Events for ${formattedDate}`;
            dayEventsEl.innerHTML = '<p class="text-gray-500 text-xs">No events scheduled</p>';
        }

        function changeMonth(direction) {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + direction);
            generateCalendar(currentCalendarDate);
        }

        // Global notifications array to manage notifications
        let currentNotifications = [];
        let selectedNotifications = new Set();
        let isSelectMode = false;

        // Notification System Functions
        function toggleNotifications() {
            const dropdown = document.getElementById('notification-dropdown');
            const isHidden = dropdown.classList.contains('hidden');
            
            if (isHidden) {
                dropdown.classList.remove('hidden');
                dropdown.classList.add('scale-100', 'opacity-100');
                dropdown.classList.remove('scale-95', 'opacity-0');
                loadNotifications();
            } else {
                dropdown.classList.add('hidden');
                dropdown.classList.remove('scale-100', 'opacity-100');
                dropdown.classList.add('scale-95', 'opacity-0');
            }
        }

        function loadNotifications() {
            // Only initialize test notifications if currentNotifications is empty
            if (currentNotifications.length === 0) {
                const testNotifications = [
                    {
                        type: 'meeting',
                        title: 'Team Meeting',
                        message: 'Weekly team sync in 2 hours',
                        time: new Date(Date.now() + 2 * 60 * 60 * 1000),
                        priority: 'high',
                        location: 'Conference Room A',
                        description: 'Discuss project progress, upcoming deadlines, and team coordination for the week ahead.',
                        attendees: ['John Doe', 'Jane Smith', 'Mike Johnson']
                    },
                    {
                        type: 'event',
                        title: 'Conference Call',
                        message: 'Client presentation tomorrow',
                        time: new Date(Date.now() + 24 * 60 * 60 * 1000),
                        priority: 'medium',
                        location: 'Virtual Meeting',
                        description: 'Present quarterly results to key stakeholders and discuss future collaboration opportunities.',
                        attendees: ['Client Team', 'Management', 'Sales Team']
                    },
                    {
                        type: 'reminder',
                        title: 'Document Review',
                        message: 'Review quarterly reports',
                        time: new Date(Date.now() + 4 * 60 * 60 * 1000),
                        priority: 'medium',
                        description: 'Review and approve quarterly financial reports before submission deadline.',
                        documents: ['Q4_Financial_Report.pdf', 'Budget_Analysis.xlsx']
                    }
                ];
                
                // Store notifications globally
                currentNotifications = testNotifications;
            }
            
            // Display the current notifications (preserving read status)
            displayNotifications(currentNotifications);
            
            // Also try to fetch real notifications from API (but don't replace test ones for now)
            Promise.all([
                fetch('api/scheduler.php?action=get_upcoming'),
                fetch('api/events_activities.php?action=get_upcoming')
            ])
            .then(responses => Promise.all(responses.map(r => r.json())))
            .then(([meetingsData, eventsData]) => {
                const realNotifications = [];
                
                // Process meetings
                if (meetingsData.success && meetingsData.meetings) {
                    meetingsData.meetings.forEach(meeting => {
                        const meetingDate = new Date(meeting.meeting_date + 'T' + meeting.meeting_time);
                        const now = new Date();
                        const timeDiff = meetingDate - now;
                        const hoursUntil = timeDiff / (1000 * 60 * 60);
                        
                        if (hoursUntil > 0 && hoursUntil <= 24) {
                            realNotifications.push({
                                type: 'meeting',
                                title: meeting.title,
                                message: `Meeting in ${Math.round(hoursUntil)} hours`,
                                time: meetingDate,
                                priority: hoursUntil <= 2 ? 'high' : 'medium'
                            });
                        }
                    });
                }
                
                // Process events
                if (eventsData.success && eventsData.events) {
                    eventsData.events.forEach(event => {
                        const eventDate = new Date(event.event_date + 'T' + event.event_time);
                        const now = new Date();
                        const timeDiff = eventDate - now;
                        const hoursUntil = timeDiff / (1000 * 60 * 60);
                        
                        if (hoursUntil > 0 && hoursUntil <= 48) {
                            realNotifications.push({
                                type: 'event',
                                title: event.title,
                                message: `Event in ${Math.round(hoursUntil)} hours`,
                                time: eventDate,
                                priority: hoursUntil <= 4 ? 'high' : 'medium'
                            });
                        }
                    });
                }
                
                // If we have real notifications, merge them with existing ones (preserving read status)
                if (realNotifications.length > 0) {
                    // Merge real notifications with existing ones, avoiding duplicates
                    const existingIds = new Set(currentNotifications.map(n => n.title + n.time.getTime()));
                    realNotifications.forEach(notification => {
                        const notificationId = notification.title + notification.time.getTime();
                        if (!existingIds.has(notificationId)) {
                            currentNotifications.push(notification);
                        }
                    });
                    displayNotifications(currentNotifications);
                }
            })
            .catch(error => {
                console.error('Error loading real notifications:', error);
                // Keep the test notifications that are already displayed
            });
        }

        function displayNotifications(notifications) {
            const notificationList = document.getElementById('notification-list');
            const badge = document.getElementById('notification-badge');
            
            if (notifications.length === 0) {
                notificationList.innerHTML = `
                    <div class="p-4 text-center text-gray-500">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM10.5 3.75a6 6 0 0 1 8.25 8.25l-8.25-8.25zM3.75 10.5a6 6 0 0 1 8.25-8.25l-8.25 8.25z"></path>
                        </svg>
                        <p>No notifications</p>
                    </div>
                `;
                badge.classList.add('hidden');
                
                // Hide select all button when no notifications
                const selectAllBtn = document.getElementById('select-all-btn');
                if (selectAllBtn) {
                    selectAllBtn.classList.add('hidden');
                }
                return;
            } else {
                // Show select all button when there are notifications
                const selectAllBtn = document.getElementById('select-all-btn');
                if (selectAllBtn) {
                    selectAllBtn.classList.remove('hidden');
                }
            }
            
            // Sort by priority and time
            notifications.sort((a, b) => {
                if (a.priority === 'high' && b.priority !== 'high') return -1;
                if (b.priority === 'high' && a.priority !== 'high') return 1;
                return a.time - b.time;
            });
            
            notificationList.innerHTML = notifications.map((notification, index) => {
                let priorityColor, priorityText, priorityClass;
                
                if (notification.priority === 'high') {
                    priorityColor = 'border-l-red-500';
                    priorityText = 'Urgent';
                    priorityClass = 'bg-red-100 text-red-800';
                } else if (notification.priority === 'medium') {
                    priorityColor = 'border-l-blue-500';
                    priorityText = 'Info';
                    priorityClass = 'bg-blue-100 text-blue-800';
                } else if (notification.priority === 'read') {
                    priorityColor = 'border-l-gray-300';
                    priorityText = 'Read';
                    priorityClass = 'bg-gray-100 text-gray-600';
                }
                
                const timeAgo = getTimeAgo(notification.time);
                const isSelected = selectedNotifications.has(index);
                
                return `
                    <div class="p-4 border-l-4 ${priorityColor} hover:bg-gray-50 transition-colors relative group ${notification.priority === 'read' ? 'opacity-75' : ''}" 
                         data-notification-id="${index}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3 flex-1">
                                ${isSelectMode ? `
                                    <input type="checkbox" 
                                           class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" 
                                           ${isSelected ? 'checked' : ''}
                                           onchange="toggleNotificationSelection(${index}, this.checked)"
                                           onclick="event.stopPropagation()">
                                ` : ''}
                                <div class="flex-1 cursor-pointer" onclick="showNotificationDetails(${index})">
                                    <h4 class="font-semibold ${notification.priority === 'read' ? 'text-gray-600' : 'text-gray-900'} text-sm">${notification.title}</h4>
                                    <p class="text-sm ${notification.priority === 'read' ? 'text-gray-500' : 'text-gray-600'} mt-1">${notification.message}</p>
                                    <p class="text-xs text-gray-400 mt-1">${timeAgo}</p>
                                </div>
                            </div>
                            <div class="ml-2 flex items-center space-x-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${priorityClass}">
                                    ${priorityText}
                                </span>
                                <button onclick="event.stopPropagation(); deleteNotification(${index})" 
                                        class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-gray-200 hover:border-red-200"
                                        title="Delete notification">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Update badge - only show for unread (high priority) notifications that haven't been marked as read
            const unreadCount = notifications.filter(n => n.priority === 'high' && n.priority !== 'read').length;
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        function deleteNotification(index) {
            // Remove the notification from the global array
            if (currentNotifications[index]) {
                currentNotifications.splice(index, 1);
                
                // Re-display notifications with updated array
                displayNotifications(currentNotifications);
                
                // Show a brief confirmation
                showDeleteConfirmation();
            }
        }

        function showDeleteConfirmation() {
            // Create a temporary confirmation message
            const confirmation = document.createElement('div');
            confirmation.className = 'fixed top-20 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-[80] transform translate-x-full transition-transform duration-300';
            confirmation.textContent = 'Notification deleted';
            
            document.body.appendChild(confirmation);
            
            // Slide in
            setTimeout(() => confirmation.classList.remove('translate-x-full'), 100);
            
            // Slide out and remove
            setTimeout(() => {
                confirmation.classList.add('translate-x-full');
                setTimeout(() => confirmation.remove(), 300);
            }, 2000);
        }



                function toggleSelectAll() {
            isSelectMode = !isSelectMode;
            const selectAllBtn = document.getElementById('select-all-btn');
            const bulkActionsFooter = document.getElementById('bulk-actions-footer');
            
            if (isSelectMode) {
                // Enter select mode - toggle ON
                selectAllBtn.className = 'p-2 bg-red-100 hover:bg-red-200 rounded-lg transition-colors border border-red-200 hover:border-red-300';
                selectAllBtn.innerHTML = `
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                `;
                selectAllBtn.title = 'Cancel Selection';
                bulkActionsFooter.classList.remove('hidden');
                selectedNotifications.clear();
            } else {
                // Exit select mode - toggle OFF
                selectAllBtn.className = 'p-2 hover:bg-gray-100 rounded-lg transition-colors border border-gray-200 hover:border-gray-300';
                selectAllBtn.innerHTML = `
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                </svg>
            `;
                selectAllBtn.title = 'Select All';
                bulkActionsFooter.classList.add('hidden');
                selectedNotifications.clear();
            }
            
            // Re-render notifications to show/hide checkboxes
            displayNotifications(currentNotifications);
            updateSelectedCount();
            
            return false; // Prevent any default behavior
        }

        function toggleNotificationSelection(index, isChecked) {
            if (isChecked) {
                selectedNotifications.add(index);
            } else {
                selectedNotifications.delete(index);
            }
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const selectedCount = document.getElementById('selected-count');
            if (selectedCount) {
                selectedCount.textContent = `${selectedNotifications.size} selected`;
            }
        }

        function updateNotificationBadge() {
            const badge = document.getElementById('notification-badge');
            if (!badge) return;
            
            // Only count notifications that are truly unread (high priority and not marked as read)
            const unreadCount = currentNotifications.filter(n => n.priority === 'high' && n.priority !== 'read').length;
            
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        function selectAllNotifications() {
            // Check if all notifications are currently selected
            const allSelected = selectedNotifications.size === currentNotifications.length;
            
            if (allSelected) {
                // Unselect all notifications
                selectedNotifications.clear();
                showUnselectAllConfirmation();
            } else {
                // Select all notifications
                selectedNotifications.clear();
                for (let i = 0; i < currentNotifications.length; i++) {
                    selectedNotifications.add(i);
                }
                showSelectAllConfirmation();
            }
            
            // Update the display and count without affecting notification priority
            displayNotifications(currentNotifications);
            updateSelectedCount();
            
            // Ensure badge state is preserved based on original notification priorities
            updateNotificationBadge();
        }

        function showSelectAllConfirmation() {
            // Create a temporary confirmation message
            const confirmation = document.createElement('div');
            confirmation.className = 'fixed top-20 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg z-[80] transform translate-x-full transition-transform duration-300';
            confirmation.textContent = 'All notifications selected';
            
            document.body.appendChild(confirmation);
            
            // Slide in
            setTimeout(() => confirmation.classList.remove('translate-x-full'), 100);
            
            // Slide out and remove
            setTimeout(() => {
                confirmation.classList.add('translate-x-full');
                setTimeout(() => confirmation.remove(), 300);
            }, 2000);
        }

        function showUnselectAllConfirmation() {
            // Create a temporary confirmation message
            const confirmation = document.createElement('div');
            confirmation.className = 'fixed top-20 right-4 bg-gray-500 text-white px-4 py-2 rounded-lg shadow-lg z-[80] transform translate-x-full transition-transform duration-300';
            confirmation.textContent = 'All notifications unselected';
            
            document.body.appendChild(confirmation);
            
            // Slide in
            setTimeout(() => confirmation.classList.remove('translate-x-full'), 100);
            
            // Slide out and remove
            setTimeout(() => {
                confirmation.classList.add('translate-x-full');
                setTimeout(() => confirmation.remove(), 300);
            }, 2000);
        }

        function deleteSelectedNotifications() {
            if (selectedNotifications.size === 0) {
                alert('Please select notifications to delete');
                return;
            }
            
            // Convert Set to Array and sort in descending order to avoid index shifting issues
            const indicesToDelete = Array.from(selectedNotifications).sort((a, b) => b - a);
            
            // Delete notifications from highest index to lowest
            indicesToDelete.forEach(index => {
                if (currentNotifications[index]) {
                    currentNotifications.splice(index, 1);
                }
            });
            
            // Clear selection and exit select mode
            selectedNotifications.clear();
            isSelectMode = false;
            
            // Update UI
            const selectAllBtn = document.getElementById('select-all-btn');
            const bulkActionsFooter = document.getElementById('bulk-actions-footer');
            
            selectAllBtn.className = 'p-2 hover:bg-gray-100 rounded-lg transition-colors border border-gray-200 hover:border-gray-300';
            selectAllBtn.innerHTML = `
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                </svg>
            `;
            selectAllBtn.title = 'Select All';
            bulkActionsFooter.classList.add('hidden');
            
            // Re-render notifications
            displayNotifications(currentNotifications);
            updateSelectedCount();
            
            // Show confirmation
            showDeleteConfirmation();
        }



        function showNotificationDetails(index) {
            const notification = currentNotifications[index];
            if (!notification) return;

            // Close notification dropdown
            document.getElementById('notification-dropdown').classList.add('hidden');

            // Create modal HTML
            const modalHTML = `
                <div id="notification-details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[2000] flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 max-h-[90vh] transform transition-all duration-300 scale-95 opacity-0 flex flex-col" id="notification-modal-content">
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200 flex-shrink-0">
                                                            <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center ${
                                        notification.priority === 'high' ? 'bg-red-100' : 
                                        notification.priority === 'medium' ? 'bg-blue-100' : 'bg-gray-100'
                                    }">
                                        <svg class="w-5 h-5 ${
                                            notification.priority === 'high' ? 'text-red-600' : 
                                            notification.priority === 'medium' ? 'text-blue-600' : 'text-gray-600'
                                        }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM10.5 3.75a6 6 0 0 1 8.25 8.25l-8.25-8.25zM3.75 10.5a6 6 0 0 1 8.25-8.25l-8.25 8.25z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">${notification.title}</h3>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                            notification.priority === 'high' ? 'bg-red-100 text-red-800' : 
                                            notification.priority === 'medium' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'
                                        }">
                                            ${notification.priority === 'high' ? 'Urgent' : 
                                             notification.priority === 'medium' ? 'Info' : 'Read'}
                                        </span>
                                    </div>
                            </div>
                            <button onclick="closeNotificationDetails()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Scrollable Content -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <div class="space-y-4">
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-2">Message</h4>
                                    <p class="text-gray-700 leading-relaxed">${notification.message}</p>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 mb-2">Type</h4>
                                        <p class="text-gray-700 capitalize">${notification.type}</p>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 mb-2">Priority</h4>
                                        <p class="text-gray-700 capitalize">${notification.priority}</p>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-2">Time</h4>
                                    <p class="text-gray-700">${notification.time.toLocaleString()}</p>
                                    <p class="text-sm text-gray-500 mt-1">${getTimeAgo(notification.time)}</p>
                                </div>

                                ${notification.location ? `
                                    <div>
                                        <h4 class="font-semibold text-gray-900 mb-2">Location</h4>
                                        <p class="text-gray-700">${notification.location}</p>
                                    </div>
                                ` : ''}

                                ${notification.description ? `
                                    <div>
                                        <h4 class="font-semibold text-gray-900 mb-2">Description</h4>
                                        <p class="text-gray-700">${notification.description}</p>
                                    </div>
                                ` : ''}

                                ${notification.attendees ? `
                                    <div>
                                        <h4 class="font-semibold text-gray-900 mb-2">Attendees</h4>
                                        <div class="flex flex-wrap gap-2">
                                            ${notification.attendees.map(attendee => `
                                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">${attendee}</span>
                                            `).join('')}
                                        </div>
                                    </div>
                                ` : ''}

                                ${notification.documents ? `
                                    <div>
                                        <h4 class="font-semibold text-gray-900 mb-2">Related Documents</h4>
                                        <div class="space-y-2">
                                            ${notification.documents.map(doc => `
                                                <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded-lg">
                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    <span class="text-sm text-gray-700">${doc}</span>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between p-6 border-t border-gray-200 flex-shrink-0">
                            <button onclick="deleteNotification(${index}); closeNotificationDetails();" 
                                    class="px-4 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors border border-red-200">
                                Delete
                            </button>
                            <button onclick="closeNotificationDetails()" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Animate in
            setTimeout(() => {
                const modalContent = document.getElementById('notification-modal-content');
                if (modalContent) {
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }
            }, 10);
        }

        function closeNotificationDetails() {
            const modal = document.getElementById('notification-details-modal');
            if (modal) {
                const modalContent = document.getElementById('notification-modal-content');
                if (modalContent) {
                    modalContent.classList.add('scale-95', 'opacity-0');
                    modalContent.classList.remove('scale-100', 'opacity-100');
                }
                
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const notificationBell = document.getElementById('notification-bell');
            const notificationDropdown = document.getElementById('notification-dropdown');
            
            if (!notificationBell.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });

        // Close notification details modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('notification-details-modal');
            if (modal && event.target === modal) {
                closeNotificationDetails();
            }
        });

        // Quick Actions Panel Functions
        function toggleQuickActions() {
            const menu = document.getElementById('quick-actions-menu');
            const fab = document.getElementById('quick-actions-fab');
            const icon = document.getElementById('fab-icon');
            
            const isHidden = menu.classList.contains('hidden');
            
            if (isHidden) {
                // Show menu
                menu.classList.remove('hidden');
                setTimeout(() => {
                    menu.classList.remove('scale-95', 'opacity-0');
                    menu.classList.add('scale-100', 'opacity-100');
                }, 10);
                
                // Rotate icon
                icon.style.transform = 'rotate(45deg)';
                fab.classList.add('bg-gradient-to-r', 'from-red-600', 'to-pink-600');
                fab.classList.remove('bg-gradient-to-r', 'from-purple-600', 'to-blue-600');
            } else {
                // Hide menu
                menu.classList.add('scale-95', 'opacity-0');
                menu.classList.remove('scale-100', 'opacity-100');
                setTimeout(() => {
                    menu.classList.add('hidden');
                }, 300);
                
                // Reset icon
                icon.style.transform = 'rotate(0deg)';
                fab.classList.remove('bg-gradient-to-r', 'from-red-600', 'to-pink-600');
                fab.classList.add('bg-gradient-to-r', 'from-purple-600', 'to-blue-600');
            }
        }

        // Close quick actions when clicking outside
        document.addEventListener('click', function(event) {
            const panel = document.getElementById('quick-actions-panel');
            const menu = document.getElementById('quick-actions-menu');
            
            if (panel && !panel.contains(event.target) && !menu.classList.contains('hidden')) {
                toggleQuickActions();
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (!sidebar) return;
            // Toggle hidden/visible by translating X
            sidebar.classList.toggle('-translate-x-full');
            // Adjust navbar left padding and main content margin to reclaim space
            const nav = document.querySelector('nav.modern-nav');
            if (nav) nav.classList.toggle('pl-64');
            const mainContainer = document.getElementById('main-content');
            if (mainContainer) mainContainer.classList.toggle('ml-64');
        }
    </script>
</head>

<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between pl-64 relative transition-all duration-300 ease-in-out">
        <div class="flex items-center space-x-4">
            <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <a href="dashboard.php" class="flex items-center space-x-3 hover:opacity-80 transition-opacity cursor-pointer">

         </div>

         <div class="absolute left-1/2 transform -translate-x-1/2">
            <h1 class="text-xl font-bold text-gray-800 cursor-pointer" onclick="location.reload()">LILAC System</h1>
         </div>

         <div class="text-sm flex items-center space-x-4">
            <!-- Notification Bell -->
            <div class="relative">
                <button id="notification-bell" onclick="toggleNotifications()" class="btn btn-sm relative">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <!-- Notification bell icon -->
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25-2.84 9.74-2.84 9.74A1 1 0 0 0 3 20h18a1 1 0 0 0 .84-1.26S19 14.25 19 9c0-3.87-3.13-7-7-7z"/>
                        <path d="M9 20a3 3 0 0 0 6 0"/>
                        <circle cx="12" cy="9" r="1"/>
                        <path d="M12 6v3"/>
                    </svg>
                    <!-- Notification Badge -->
                    <span id="notification-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                </button>
                
                <!-- Notification Dropdown -->
                <div id="notification-dropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-[1000] hidden transform origin-top-right transition-all duration-200 ease-out">
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                            <div class="flex items-center space-x-3">
                                <button onclick="event.preventDefault(); event.stopPropagation(); toggleSelectAll();" id="select-all-btn" class="p-2 hover:bg-gray-100 rounded-lg transition-colors border border-gray-200 hover:border-gray-300" title="Select All">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                                    </svg>
                                </button>

                            </div>
                        </div>
                    </div>
                    <div id="notification-list" class="max-h-96 overflow-y-auto">
                        <!-- Notifications will be loaded here -->
                        <div class="p-4 text-center text-gray-500">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM10.5 3.75a6 6 0 0 1 8.25 8.25l-8.25-8.25zM3.75 10.5a6 6 0 0 1 8.25-8.25l-8.25 8.25z"></path>
                            </svg>
                            <p>No notifications</p>
                        </div>
                    </div>
                    <!-- Bulk Actions Footer -->
                    <div id="bulk-actions-footer" class="p-4 border-t border-gray-200 hidden">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <button onclick="event.preventDefault(); event.stopPropagation(); selectAllNotifications();" class="p-1 hover:bg-gray-100 rounded transition-colors" title="Select All">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                <span id="selected-count" class="text-sm text-gray-600">0 selected</span>
                            </div>
                            <button onclick="deleteSelectedNotifications()" class="px-3 py-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors border border-red-200 text-sm">
                                Delete Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            

            
            <!-- User Profile -->
            <div class="relative">
                <button id="user-profile-btn" class="flex items-center space-x-2 btn my-1 btn-sm">
                    <div class="w-9 h-9 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <span class="text-white text-sm font-bold">LD</span>
                    </div>
                </button>
                
                <!-- User Profile Dropdown -->
                <div id="user-dropdown" class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-[1000]">
                    <!-- User Info Section -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-lg font-medium">LD</span>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-gray-900">Lesley Dignadice</div>
                                <div class="text-xs text-gray-500">lesley.dignadice@cpu.edu.ph</div>
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Dark Mode Toggle -->
                    <div class="p-3 border-b border-gray-200">
                        <button id="dark-mode-toggle" class="w-full flex items-center hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg p-2 transition-colors">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                </svg>
                                <span class="text-sm text-black">Dark Mode</span>
                            </div>
                        </button>
                    </div>
                    
                    <!-- Menu Items -->
                    <div class="py-2">
                        <a href="#" class="flex items-center space-x-3 px-4 py-2 text-sm text-black hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Account Settings</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 px-4 py-2 text-sm text-black hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span>Privacy</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 px-4 py-2 text-sm text-black hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>Reset Password</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 px-4 py-2 text-sm text-black hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM10.5 3.75a6 6 0 0 1 8.25 8.25l-8.25-8.25zM3.75 10.5a6 6 0 0 1 8.25-8.25l-8.25 8.25z"></path>
                            </svg>
                            <span>Notifications</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div id="main-content" class="ml-64 p-4 pt-16 min-h-screen bg-muted transition-all duration-300 ease-in-out">
                <!-- Welcome Section -->
        <!-- Stats Cards -->
        <div class="grid grid-cols-4 gap-4 mb-6 mt-4">
            <!-- MOUs Card -->
            <a href="mou-moa.php" class="stats-card group cursor-pointer" style="text-decoration: none;">
                <div class="flex items-center justify-between mb-4"></div>
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <p id="active-mou-count" class="stats-number">0</p>
                        <p class="stats-label">Active MOUs</p>
                        <p class="text-secondary text-sm mb-4">Partnership agreements</p>
                    </div>
                    <div id="latest-mou-details" class="p-3 bg-muted rounded-lg border border-border flex items-center justify-center">
                        <p class="text-sm text-secondary font-medium text-center">No MOUs yet</p>
                    </div>
                </div>
            </a>

            <!-- Awards Card -->
            <a href="awards.php" class="stats-card group cursor-pointer" style="text-decoration: none;">
                <div class="flex items-center justify-between mb-4"></div>
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <p id="awards-received-count" class="stats-number">0</p>
                        <p class="stats-label">Awards</p>
                        <p class="text-secondary text-sm mb-4">Recognition earned</p>
                    </div>
                    <div id="latest-award-details" class="p-3 bg-muted rounded-lg border border-border flex items-center justify-center">
                        <p class="text-sm text-secondary font-medium text-center">No awards yet</p>
                    </div>
                </div>
            </a>

            <!-- Documents Card -->
            <a href="documents.php" class="stats-card group cursor-pointer" style="text-decoration: none;">
                <div class="flex items-center justify-between mb-4"></div>
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <p id="total-documents-count" class="stats-number">0</p>
                        <p class="stats-label">Documents</p>
                        <p class="text-secondary text-sm mb-4">Files managed</p>
                    </div>
                    <div class="p-3 bg-muted rounded-lg border border-border flex items-center justify-center">
                        <p class="text-sm text-secondary font-medium text-center" id="recent-uploads">No documents yet</p>
                    </div>
                </div>
            </a>

            <!-- Meetings Card -->
            <a href="scheduler.php" class="stats-card group cursor-pointer" style="text-decoration: none;">
                <div class="flex items-center justify-between mb-4"></div>
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <p id="upcoming-meetings-count" class="stats-number">0</p>
                        <p class="stats-label">Meetings</p>
                        <p class="text-secondary text-sm mb-4">Scheduled upcoming</p>
                    </div>
                    <div class="p-3 bg-muted rounded-lg border border-border flex items-center justify-center">
                        <p class="text-sm text-secondary font-medium text-center" id="next-meeting">No meetings yet</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-3 gap-4" style="margin-top: 28px;">
            <!-- Left Column -->
            <div class="col-span-2 space-y-6">
                <!-- Upcoming Meetings -->
                <div class="group relative">
                    <div class="absolute -inset-0.5 border border-black rounded-3xl opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                    <div class="relative bg-white bg-opacity-80 backdrop-blur-xl rounded-3xl p-6 shadow-2xl border border-white border-opacity-30">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 border-2 border-black rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-gray-800">Upcoming Meetings</h3>
                                    <p class="text-sm text-gray-500">Your scheduled appointments</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="scheduler.php" class="inline-flex items-center space-x-2 border-2 border-black text-black px-4 py-2 rounded-xl text-sm font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span>Schedule Meeting</span>
                                </a>
                                <a href="scheduler.php" class="text-black hover:text-gray-600 text-sm font-semibold border border-black px-3 py-2 rounded-xl transition-colors">View All</a>
                            </div>
                        </div>
                        <div id="meetings-list" class="space-y-4">
                            <div class="text-center py-8 text-gray-500">
                                <div class="w-16 h-16 border-2 border-black rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <p class="font-semibold text-gray-700 text-lg mb-2">No meetings scheduled</p>
                                <p class="text-sm text-gray-500 mb-4">Schedule a meeting to get started</p>
                                <a href="scheduler.php" class="inline-flex items-center space-x-2 border-2 border-black text-black px-6 py-3 rounded-xl text-sm font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span>+ Schedule a Meeting</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Documents -->
                <div class="group relative">
                    <div class="absolute -inset-0.5 border border-black rounded-3xl opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                    <div class="relative bg-white bg-opacity-80 backdrop-blur-xl rounded-3xl p-6 shadow-2xl border border-white border-opacity-30">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 border-2 border-black rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-gray-800">Recent Documents</h3>
                                    <p class="text-sm text-gray-500">Your latest file uploads</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="documents.php" class="inline-flex items-center space-x-2 border-2 border-black text-black px-4 py-2 rounded-xl text-sm font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span>Upload Document</span>
                                </a>
                                <a href="documents.php" class="text-black hover:text-gray-600 text-sm font-semibold border border-black px-3 py-2 rounded-xl transition-colors">View All</a>
                            </div>
                        </div>
                        <div id="documents-list" class="space-y-4">
                            <div class="text-center py-8 text-gray-500">
                                <div class="w-16 h-16 border-2 border-black rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <p class="font-semibold text-gray-700 text-lg mb-2">No documents yet</p>
                                <p class="text-sm text-gray-500 mb-4">Upload a document to get started</p>
                                <a href="documents.php" class="inline-flex items-center space-x-2 border-2 border-black text-black px-6 py-3 rounded-xl text-sm font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span>+ Upload Document</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Calendar -->
            <div class="lg:col-span-1">
                <div class="group relative">
                    <div class="absolute -inset-0.5 border border-black rounded-3xl opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                    <div class="relative bg-white bg-opacity-80 backdrop-blur-xl rounded-3xl p-6 shadow-2xl border border-white border-opacity-30">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 border-2 border-black rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-gray-800">Calendar</h3>
                                <p class="text-sm text-gray-500">Your schedule overview</p>
                            </div>
                        </div>
                        
                        <div class="text-center mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <button onclick="changeMonth(-1)" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <h4 id="current-month-year" class="font-bold text-lg text-gray-800"></h4>
                                <button onclick="changeMonth(1)" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-xs text-gray-600 mb-3 font-semibold">
                                <div class="p-2 text-center">Su</div>
                                <div class="p-2 text-center">Mo</div>
                                <div class="p-2 text-center">Tu</div>
                                <div class="p-2 text-center">We</div>
                                <div class="p-2 text-center">Th</div>
                                <div class="p-2 text-center">Fr</div>
                                <div class="p-2 text-center">Sa</div>
                            </div>
                            <div id="calendar-days" class="grid grid-cols-7 gap-1 text-sm">
                                <!-- Calendar days will be generated here -->
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h4 id="selected-date" class="font-semibold text-gray-800 mb-3">Today's Events</h4>
                            <div id="day-events" class="space-y-2">
                                <div class="text-center py-4 text-gray-500">
                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-sm">No events scheduled</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    




    <!-- Quick Actions Panel -->
    <div id="quick-actions-panel" class="fixed bottom-6 right-6 z-50">
        <!-- Main FAB -->
        <button id="quick-actions-fab" onclick="toggleQuickActions()" 
                class="w-14 h-14 border-2 border-black text-black rounded-full shadow-2xl hover:shadow-3xl transform hover:scale-110 transition-all duration-300 flex items-center justify-center group">
            <svg id="fab-icon" class="w-6 h-6 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        </button>
        
        <!-- Quick Actions Menu -->
        <div id="quick-actions-menu" class="absolute bottom-16 right-0 bg-white rounded-2xl shadow-2xl border border-gray-200 p-4 hidden transform scale-95 opacity-0 transition-all duration-300 origin-bottom-right">
            <div class="space-y-3 min-w-48">
                <h3 class="text-sm font-bold text-gray-700 mb-3 text-center">Quick Actions</h3>
                
                <a href="documents.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-50 transition-colors group">
                    <div class="w-10 h-10 border border-black rounded-xl flex items-center justify-center group-hover:border-gray-600 transition-colors">
                        <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Upload Document</p>
                        <p class="text-xs text-gray-500">Add new files</p>
                    </div>
                </a>
                
                <a href="scheduler.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-50 transition-colors group">
                    <div class="w-10 h-10 border border-black rounded-xl flex items-center justify-center group-hover:border-gray-600 transition-colors">
                        <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Schedule Meeting</p>
                        <p class="text-xs text-gray-500">Book appointment</p>
                    </div>
                </a>
                
                <a href="mou-moa.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-50 transition-colors group">
                    <div class="w-10 h-10 border border-black rounded-xl flex items-center justify-center group-hover:border-gray-600 transition-colors">
                        <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">New MOU/MOA</p>
                        <p class="text-xs text-gray-500">Create partnership</p>
                    </div>
                </a>
                
                <a href="awards.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-50 transition-colors group">
                    <div class="w-10 h-10 border border-black rounded-xl flex items-center justify-center group-hover:border-gray-600 transition-colors">
                        <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Add Award</p>
                        <p class="text-xs text-gray-500">Record achievement</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

         <!-- Footer -->
     <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
         <p>&copy; 2025 Central Philippine University | LILAC System</p>
     </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // User profile dropdown functionality
            const userProfileBtn = document.getElementById('user-profile-btn');
            const userDropdown = document.getElementById('user-dropdown');
            
            if (userProfileBtn && userDropdown) {
                userProfileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userProfileBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.add('hidden');
                    }
                });
            }

            // Calendar navigation functions
            window.changeMonth = function(direction) {
                currentCalendarDate.setMonth(currentCalendarDate.getMonth() + direction);
                generateCalendar(currentCalendarDate);
            };

            // Hamburger button toggles sidebar
            var hamburger = document.getElementById('hamburger-toggle');
            if (hamburger) {
                hamburger.addEventListener('click', function() {
                    try {
                        window.dispatchEvent(new CustomEvent('sidebar:toggle'));
                    } catch (e) {}
                });
            }
        });
    </script>
</body>

</html>


