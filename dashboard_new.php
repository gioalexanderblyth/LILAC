<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LILAC Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize LILAC notifications
            window.lilacNotifications = new LILACNotifications();
            
            // Hamburger menu toggle functionality
            const hamburgerToggle = document.getElementById("hamburger-toggle");
            const sidebar = document.getElementById("sidebar");
            const mainContent = document.getElementById("main-content");
            
            if (hamburgerToggle && sidebar) {
                hamburgerToggle.addEventListener("click", function() {
                    sidebar.classList.toggle("hidden");
                    if (mainContent) {
                        mainContent.classList.toggle("ml-64");
                    }
                });
            }
            
            // Notification bell functionality
            const notificationBell = document.getElementById("notification-bell");
            const notificationDropdown = document.getElementById("notification-dropdown");
            const notificationBadge = document.getElementById("notification-badge");
            
            if (notificationBell && notificationDropdown) {
                notificationBell.addEventListener("click", function(e) {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle("hidden");
                    // Hide user dropdown if open
                    const userDropdown = document.getElementById("user-dropdown");
                    if (userDropdown) {
                        userDropdown.classList.add("hidden");
                    }
                });
                
                // Update notification badge
                updateNotificationBadge();
            }
            
            // User profile dropdown functionality
            const userProfileBtn = document.getElementById("user-profile-btn");
            const userDropdown = document.getElementById("user-dropdown");
            
            if (userProfileBtn && userDropdown) {
                userProfileBtn.addEventListener("click", function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle("hidden");
                    // Hide notification dropdown if open
                    if (notificationDropdown) {
                        notificationDropdown.classList.add("hidden");
                    }
                });
            }
            
            // Close dropdowns when clicking outside
            document.addEventListener("click", function(e) {
                if (notificationDropdown && !notificationDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
                    notificationDropdown.classList.add("hidden");
                }
                if (userDropdown && !userDropdown.contains(e.target) && !userProfileBtn.contains(e.target)) {
                    userDropdown.classList.add("hidden");
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
                    notificationBadge.classList.remove("hidden");
                }
                
                // Populate notification dropdown
                if (notificationDropdown) {
                    const notificationList = notificationDropdown.querySelector("#notification-list");
                    if (notificationList) {
                        notificationList.innerHTML = notifications.map(notif => `
                            <div class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 rounded-full ${notif.type === "success" ? "bg-green-500" : notif.type === "warning" ? "bg-yellow-500" : "bg-blue-500"}"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">${notif.title}</p>
                                        <p class="text-sm text-gray-500 truncate">${notif.message}</p>
                                        <p class="text-xs text-gray-400 mt-1">${notif.time}</p>
                                    </div>
                                </div>
                            </div>
                        `).join("");
                    }
                }
            }
            
            // User profile actions
            function handleLogout() {
                if (confirm("Are you sure you want to logout?")) {
                    window.lilacNotifications.success("Logged out successfully");
                    // Add logout logic here
                }
            }
            
            function handleProfile() {
                window.lilacNotifications.info("Profile page - to be implemented");
                // Add profile navigation logic here
            }
            
            function handleSettings() {
                window.lilacNotifications.info("Settings page - to be implemented");
                // Add settings navigation logic here
            }
            
            // Make functions globally available
            window.handleLogout = handleLogout;
            window.handleProfile = handleProfile;
            window.handleSettings = handleSettings;
        });
    </script>
</head>
