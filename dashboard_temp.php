<?php
// Suppress any output before JSON
ob_start();

// Load dashboard data directly from database
require_once 'config/database.php';

function loadDashboardData() {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get events count
        $upcomingStmt = $conn->query("SELECT COUNT(*) as count FROM central_events WHERE status = 'upcoming'");
        $upcomingEvents = $upcomingStmt->fetch()['count'];
        
        $completedStmt = $conn->query("SELECT COUNT(*) as count FROM central_events WHERE status = 'completed'");
        $completedEvents = $completedStmt->fetch()['count'];
        
        // Get documents count
        $documentsStmt = $conn->query("SELECT COUNT(*) as count FROM enhanced_documents");
        $documents = $documentsStmt->fetch()['count'];
        
        // Get awards count (using award_readiness table)
        $awardsStmt = $conn->query("SELECT COUNT(*) as count FROM award_readiness");
        $awards = $awardsStmt->fetch()['count'];
        
        return [
            'success' => true,
            'data' => [
                'upcoming_events' => $upcomingEvents,
                'completed_events' => $completedEvents,
                'documents' => $documents,
                'awards' => $awards
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'data' => [
                'upcoming_events' => 0,
                'completed_events' => 0,
                'documents' => 0,
                'awards' => 0
            ],
            'error' => $e->getMessage()
        ];
    }
}

$dashboardData = loadDashboardData();

// Clean any output buffer
ob_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize LILAC notifications
            window.lilacNotifications = new LILACNotifications();
            
            // Function to adjust layout
            function adjustLayout() {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('main-content');
                
                if (sidebar && mainContent) {
                    if (window.innerWidth <= 1024) {
                        // sidebar.classList.add('hidden');
                        mainContent.classList.remove('ml-64');
                    } else {
                        sidebar.classList.remove('hidden');
                        mainContent.classList.add('ml-64');
                    }
                }
            }
            
            // Initial layout adjustment
            adjustLayout();
            
            // Adjust layout on window resize
            window.addEventListener('resize', adjustLayout);
            
            // Hamburger menu toggle is now handled globally by LILACSidebar
            
            // Notification bell functionality
            const notificationBell = document.getElementById('notification-bell');
            const notificationDropdown = document.getElementById('notification-dropdown');
            const notificationBadge = document.getElementById('notification-badge');
            
            if (notificationBell && notificationDropdown) {
                notificationBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('hidden');
                    const userDropdown = document.getElementById('user-dropdown');
                    if (userDropdown) {
                        userDropdown.classList.add('hidden');
                    }
                });
                
                updateNotificationBadge();
            }
            
            // User profile dropdown functionality
            const userProfileBtn = document.getElementById('user-profile-btn');
            const userDropdown = document.getElementById('user-dropdown');
            
            if (userProfileBtn && userDropdown) {
                userProfileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('hidden');
                    if (notificationDropdown) {
                        notificationDropdown.classList.add('hidden');
                    }
                });
            }
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (notificationDropdown && !notificationDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
                    notificationDropdown.classList.add('hidden');
                }
                if (userDropdown && !userDropdown.contains(e.target) && !userProfileBtn.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
            
            // Sample notification data
            function updateNotificationBadge() {
                const notifications = [
                    { id: 1, title: "New event created", message: "Annual Conference 2024 has been scheduled", time: "2 min ago", type: "info" },
                    { id: 2, title: "Document uploaded", message: "Meeting minutes for Q4 review", time: "15 min ago", type: "success" },
                    { id: 3, title: "Award deadline", message: "Employee of the Year nominations due tomorrow", time: "1 hour ago", type: "warning" }
                ];
                
                if (notificationBadge) {
                    notificationBadge.textContent = notifications.length;
                    notificationBadge.classList.remove('hidden');
                }
                
                if (notificationDropdown) {
                    const notificationList = notificationDropdown.querySelector('#notification-list');
                    if (notificationList) {
                        notificationList.innerHTML = notifications.map(notif => 
                            `<div class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 rounded-full ${notif.type === 'success' ? 'bg-green-500' : notif.type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'}"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">${notif.title}</p>
                                        <p class="text-sm text-gray-500 truncate">${notif.message}</p>
                                        <p class="text-xs text-gray-400 mt-1">${notif.time}</p>
                                    </div>
                                </div>
                            </div>`
                        ).join('');
                    }
                }
            }
            
            // User profile actions
            function handleLogout() {
                if (confirm('Are you sure you want to logout?')) {
                    window.lilacNotifications.success('Logged out successfully');
                    // Redirect to index.html immediately
                    window.location.href = 'http://localhost/LILAC/index.html';
                }
            }
            
            function handleProfile() {
                window.lilacNotifications.info('Profile page - to be implemented');
            }
            
            function handleSettings() {
                window.lilacNotifications.info('Settings page - to be implemented');
            }
            
            window.handleLogout = handleLogout;
            window.handleProfile = handleProfile;
            window.handleSettings = handleSettings;
            
            // Load dashboard data
            loadDashboardData();
        });
        
        function loadDashboardData() {
            // Update counters with PHP data
            const data = <?php 
                $data = $dashboardData['data'] ?? ['upcoming_events' => 0, 'completed_events' => 0, 'documents' => 0, 'awards' => 0];
                echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            ?>;
            
            const upcomingElement = document.getElementById('upcoming-events-count');
            const completedElement = document.getElementById('completed-events-count');
            const documentsElement = document.getElementById('documents-count');
            const awardsElement = document.getElementById('awards-count');
            
            if (upcomingElement) upcomingElement.textContent = data.upcoming_events;
            if (completedElement) completedElement.textContent = data.completed_events;
            if (documentsElement) documentsElement.textContent = data.documents;
            if (awardsElement) awardsElement.textContent = data.awards;
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between relative transition-all duration-300 ease-in-out">
        <div class="flex items-center space-x-4">
            <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        <div class="absolute left-1/2 transform -translate-x-1/2">
            <h1 class="text-xl font-bold text-gray-800 cursor-pointer" onclick="location.reload()">LILAC System</h1>
        </div>
        <div class="text-sm flex items-center space-x-4">
            <!-- Notification Bell -->
            <div class="relative">
                <button type="button" id="notification-bell" class="btn btn-sm relative">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25-2.84 9.74-2.84 9.74A1 1 0 0 0 3 20h18a1 1 0 0 0 .84-1.26S19 14.25 19 9c0-3.87-3.13-7-7-7z"/>
                        <path d="M9 20a3 3 0 0 0 6 0"/>
                        <circle cx="12" cy="9" r="1"/>
                        <path d="M12 6v3"/>
                    </svg>
                    <span id="notification-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                </button>
                
                <!-- Notification Dropdown -->
                <div id="notification-dropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                    </div>
                    <div id="notification-list" class="max-h-64 overflow-y-auto">
                        <!-- Notifications will be populated by JavaScript -->
                    </div>
                    <div class="p-3 border-t border-gray-200">
                        <button class="w-full text-center text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View All Notifications
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- User Profile -->
            <div id="user-profile-container" class="relative">
                <button type="button" id="user-profile-btn" class="flex items-center space-x-2 btn my-1 btn-sm">
                    <div class="w-9 h-9 bg-gradient-to-r from-amber-600 to-amber-800 rounded-xl flex items-center justify-center shadow-lg border border-amber-700">
                        <span class="text-white text-sm font-bold">LD</span>
                    </div>
                </button>
                
                <!-- User Dropdown -->
                <div id="user-dropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-r from-amber-600 to-amber-800 rounded-lg flex items-center justify-center">
                                <span class="text-white text-sm font-bold">LD</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Lesley</p>
                                <p class="text-xs text-gray-500">Administrator</p>
                            </div>
                        </div>
                    </div>
                    <div class="py-2">
                        <button onclick="handleProfile()" class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Profile</span>
                        </button>
                        <button onclick="handleSettings()" class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0l1.403 5.777c.1.41.4.75.8.9l5.5 2.4c1.6.7 1.6 3.1 0 3.8l-5.5 2.4c-.4.15-.7.49-.8.9l-1.403 5.777c-.426 1.756-2.924 1.756-3.35 0l-1.403-5.777c-.1-.41-.4-.75-.8-.9l-5.5-2.4c-1.6-.7-1.6-3.1 0-3.8l5.5-2.4c.4-.15.7-.49.8-.9l1.403-5.777z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Settings</span>
                        </button>
                        <div class="border-t border-gray-200 my-2"></div>
                        <button onclick="handleLogout()" class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span>Logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar -->
    <?php include "includes/sidebar.php"; ?>
    
    <!-- Main Content -->
    <div id="main-content" class="p-4 pt-3 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out ml-64">
        <!-- Welcome Section -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome back, Lesley!</h1>
            <p class="text-gray-600">Here's what's happening with your LILAC system today.</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Events & Activities -->
            <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Events & Activities</p>
                        <div class="flex items-center mt-2">
                            <span id="upcoming-events-count" class="text-2xl font-bold text-blue-600">0</span>
                            <span class="text-sm text-gray-500 ml-2">upcoming</span>
                        </div>
                        <div class="flex items-center">
                            <span id="completed-events-count" class="text-lg font-semibold text-green-600">0</span>
                            <span class="text-sm text-gray-500 ml-2">completed</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Awards Progress -->
            <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Awards Progress</p>
                        <div class="mt-2">
                            <span id="awards-count" class="text-2xl font-bold text-purple-600">0</span>
                            <span class="text-sm text-gray-500 ml-2">total awards</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: 75%"></div>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Meetings -->
            <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Meetings</p>
                        <div class="mt-2">
                            <span class="text-2xl font-bold text-green-600">12</span>
                            <span class="text-sm text-gray-500 ml-2">this month</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">3 scheduled today</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Documents -->
            <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Documents</p>
                        <div class="mt-2">
                            <span id="documents-count" class="text-2xl font-bold text-orange-600">0</span>
                            <span class="text-sm text-gray-500 ml-2">total files</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">5 uploaded today</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Recent Activities -->
            <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activities</h3>
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">New event created</p>
                            <p class="text-xs text-gray-500">Annual Conference 2024</p>
                        </div>
                        <span class="text-xs text-gray-400">2 min ago</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">Document uploaded</p>
                            <p class="text-xs text-gray-500">Meeting minutes Q4</p>
                        </div>
                        <span class="text-xs text-gray-400">15 min ago</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">Award nomination</p>
                            <p class="text-xs text-gray-500">Employee of the Year</p>
                        </div>
                        <span class="text-xs text-gray-400">1 hour ago</span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-4">
                    <a href="events_activities.php" class="p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <div class="text-center">
                            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Events</p>
                        </div>
                    </a>
                    <a href="documents.php" class="p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <div class="text-center">
                            <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Documents</p>
                        </div>
                    </a>
                    <a href="awards.php" class="p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                        <div class="text-center">
                            <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Awards</p>
                        </div>
                    </a>
                    <a href="mou-moa.php" class="p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                        <div class="text-center">
                            <div class="w-8 h-8 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">MOU/MOA</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
