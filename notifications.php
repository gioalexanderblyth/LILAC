<?php
require_once 'classes/DateTimeUtility.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/error-handler.js"></script>
    <script src="js/security-utils.js"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script>
        // Cache buster: 2024-12-19
        // Define category constant for notifications
        const CATEGORY = 'Notifications';
        
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            initializeEventListeners();
            updateCurrentDate();
        });

        function initializeEventListeners() {
            // Mark all as read button
            const markAllReadBtn = document.getElementById('mark-all-read');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', markAllAsRead);
            }

            // Clear all button
            const clearAllBtn = document.getElementById('clear-all');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', clearAllNotifications);
            }

            // Filter buttons
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    filterNotifications(filter);
                });
            });
        }

        function loadNotifications() {
            const container = document.getElementById('notifications-container');
            
            // Sample notification data - in real implementation, this would come from API
            const notifications = [
                {
                    id: 1,
                    title: "New Event Created",
                    message: "Annual Conference 2024 has been scheduled for March 15-17, 2024",
                    type: "info",
                    category: "events",
                    timestamp: new Date(Date.now() - 2 * 60 * 1000), // 2 minutes ago
                    read: false,
                    priority: "medium"
                },
                {
                    id: 2,
                    title: "Document Uploaded",
                    message: "Meeting minutes for Q4 review have been uploaded successfully",
                    type: "success",
                    category: "documents",
                    timestamp: new Date(Date.now() - 15 * 60 * 1000), // 15 minutes ago
                    read: false,
                    priority: "low"
                },
                {
                    id: 3,
                    title: "Award Deadline Reminder",
                    message: "Employee of the Year nominations are due tomorrow at 5:00 PM",
                    type: "warning",
                    category: "awards",
                    timestamp: new Date(Date.now() - 60 * 60 * 1000), // 1 hour ago
                    read: true,
                    priority: "high"
                },
                {
                    id: 4,
                    title: "System Maintenance",
                    message: "Scheduled maintenance will occur tonight from 11:00 PM to 1:00 AM",
                    type: "info",
                    category: "system",
                    timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000), // 2 hours ago
                    read: true,
                    priority: "medium"
                },
                {
                    id: 5,
                    title: "MOU Expiring Soon",
                    message: "Partnership agreement with University of Tokyo expires in 30 days",
                    type: "warning",
                    category: "mou",
                    timestamp: new Date(Date.now() - 4 * 60 * 60 * 1000), // 4 hours ago
                    read: false,
                    priority: "high"
                },
                {
                    id: 6,
                    title: "New User Registration",
                    message: "Dr. Sarah Johnson has been added to the system",
                    type: "success",
                    category: "users",
                    timestamp: new Date(Date.now() - 6 * 60 * 60 * 1000), // 6 hours ago
                    read: true,
                    priority: "low"
                }
            ];

            displayNotifications(notifications);
        }

        function displayNotifications(notifications) {
            const container = document.getElementById('notifications-container');
            
            if (notifications.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12.828 7H4.828zM4.828 17h8l-2.586-2.586a2 2 0 00-2.828 0L4.828 17zM15 7h5l-5-5v5z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
                        <p class="text-gray-500">You're all caught up! Check back later for new updates.</p>
                    </div>
                `;
                return;
            }

            const notificationsHTML = notifications.map(notification => {
                const timeAgo = getTimeAgo(notification.timestamp);
                const typeIcon = getTypeIcon(notification.type);
                const typeColor = getTypeColor(notification.type);
                const priorityColor = getPriorityColor(notification.priority);
                
                return `
                    <div class="notification-item bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all duration-200 ${!notification.read ? 'border-l-4 border-l-blue-500' : ''}" data-id="${notification.id}" data-type="${notification.type}" data-category="${notification.category}">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full ${typeColor.bg} flex items-center justify-center">
                                    ${typeIcon}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-gray-900 ${!notification.read ? 'font-semibold' : ''}">${notification.title}</h4>
                                    <div class="flex items-center space-x-2">
                                        ${notification.priority === 'high' ? `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${priorityColor}">High Priority</span>` : ''}
                                        <span class="text-xs text-gray-500">${timeAgo}</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">${notification.message}</p>
                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex items-center space-x-4">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">${notification.category}</span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${typeColor.text}">${notification.type}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        ${!notification.read ? `<button onclick="markAsRead(${notification.id})" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Mark as read</button>` : ''}
                                        <button onclick="deleteNotification(${notification.id})" class="text-xs text-red-600 hover:text-red-800 font-medium">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = notificationsHTML;
        }

        function getTypeIcon(type) {
            const icons = {
                'success': '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                'warning': '<svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
                'error': '<svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                'info': '<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };
            return icons[type] || icons['info'];
        }

        function getTypeColor(type) {
            const colors = {
                'success': { bg: 'bg-green-100', text: 'bg-green-100 text-green-800' },
                'warning': { bg: 'bg-yellow-100', text: 'bg-yellow-100 text-yellow-800' },
                'error': { bg: 'bg-red-100', text: 'bg-red-100 text-red-800' },
                'info': { bg: 'bg-blue-100', text: 'bg-blue-100 text-blue-800' }
            };
            return colors[type] || colors['info'];
        }

        function getPriorityColor(priority) {
            const colors = {
                'high': 'bg-red-100 text-red-800',
                'medium': 'bg-yellow-100 text-yellow-800',
                'low': 'bg-green-100 text-green-800'
            };
            return colors[priority] || colors['low'];
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

        function markAsRead(id) {
            const notification = document.querySelector(`[data-id="${id}"]`);
            if (notification) {
                notification.classList.remove('border-l-4', 'border-l-blue-500');
                const title = notification.querySelector('h4');
                if (title) {
                    title.classList.remove('font-semibold');
                }
                const markReadBtn = notification.querySelector('button[onclick*="markAsRead"]');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
            }
            showNotification('Notification marked as read', 'success');
        }

        function markAllAsRead() {
            const unreadNotifications = document.querySelectorAll('.notification-item[class*="border-l-4"]');
            unreadNotifications.forEach(notification => {
                notification.classList.remove('border-l-4', 'border-l-blue-500');
                const title = notification.querySelector('h4');
                if (title) {
                    title.classList.remove('font-semibold');
                }
                const markReadBtn = notification.querySelector('button[onclick*="markAsRead"]');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
            });
            showNotification('All notifications marked as read', 'success');
        }

        function deleteNotification(id) {
            if (confirm('Are you sure you want to delete this notification?')) {
                const notification = document.querySelector(`[data-id="${id}"]`);
                if (notification) {
                    notification.remove();
                }
                showNotification('Notification deleted', 'success');
            }
        }

        function clearAllNotifications() {
            if (confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
                const container = document.getElementById('notifications-container');
                container.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12.828 7H4.828zM4.828 17h8l-2.586-2.586a2 2 0 00-2.828 0L4.828 17zM15 7h5l-5-5v5z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
                        <p class="text-gray-500">You're all caught up! Check back later for new updates.</p>
                    </div>
                `;
                showNotification('All notifications cleared', 'success');
            }
        }

        function filterNotifications(filter) {
            const notifications = document.querySelectorAll('.notification-item');
            const filterButtons = document.querySelectorAll('.filter-btn');
            
            // Update active filter button
            filterButtons.forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-100', 'text-gray-700');
            });
            
            const activeBtn = document.querySelector(`[data-filter="${filter}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('bg-gray-100', 'text-gray-700');
                activeBtn.classList.add('bg-blue-600', 'text-white');
            }
            
            // Filter notifications
            notifications.forEach(notification => {
                if (filter === 'all' || notification.dataset.type === filter) {
                    notification.style.display = 'block';
                } else {
                    notification.style.display = 'none';
                }
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                type === 'warning' ? 'bg-yellow-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function updateCurrentDate() {
            const dateElement = document.getElementById('current-date');
            if (dateElement) {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                };
                dateElement.textContent = now.toLocaleDateString('en-US', options);
            }
        }
    </script>
</head>

<body class="bg-gray-50">
    <!-- Main Content -->
    <div id="main-content" class="min-h-screen">
        <!-- Top Navigation -->
        <nav class="modern-nav bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button onclick="window.location.href='dashboard.php'" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" title="Back to Dashboard">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                        <p class="text-sm text-gray-600">Stay updated with system alerts and important information</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        <span id="current-date"></span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <div class="p-6">
            <!-- Header with Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">All Notifications</h2>
                        <p class="text-sm text-gray-600 mt-1">Manage your notifications and stay informed</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button id="mark-all-read" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                            Mark All as Read
                        </button>
                        <button id="clear-all" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                            Clear All
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <div class="flex items-center space-x-3">
                    <span class="text-sm font-medium text-gray-700">Filter by type:</span>
                    <button class="filter-btn px-3 py-1 rounded-full text-sm font-medium bg-blue-600 text-white" data-filter="all">All</button>
                    <button class="filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700" data-filter="info">Info</button>
                    <button class="filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700" data-filter="success">Success</button>
                    <button class="filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700" data-filter="warning">Warning</button>
                    <button class="filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700" data-filter="error">Error</button>
                </div>
            </div>

            <!-- Notifications Container -->
            <div id="notifications-container" class="space-y-4">
                <!-- Notifications will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>
</body>

</html>
