<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Events & Activities</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="dashboard-theme.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
</head>

<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between relative transition-all duration-300 ease-in-out">
        <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        

        <div class="absolute right-4 top-1/2 transform -translate-y-1/2 z-[90] flex items-center gap-3">
            <div class="relative max-w-md">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" placeholder="Search your course here..." class="w-64 pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <button class="p-2 border border-gray-200 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <button class="p-2 border border-gray-200 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                </svg>
            </button>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        updateCurrentDate();
        setInterval(updateCurrentDate, 60000);
        setTimeout(function() {
            if (window.lilacNotifications && window.lilacNotifications.container) {
                window.lilacNotifications.container.style.top = '80px';
                window.lilacNotifications.container.style.zIndex = '99999';
            }
        }, 500);
    });

    function updateCurrentDate() {
        var el = document.getElementById('current-date');
        if (el) {
            var now = new Date();
            el.textContent = now.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
        }
    }
    </script>

    <!-- Main Content -->
    <div id="main-content" class="p-3 pt-2 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out overflow-x-hidden min-w-0">
        


        <!-- Hero Banner -->
        <div class="relative bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-4 mb-4 overflow-hidden">
            <div class="relative z-10">
                <h1 class="text-2xl font-bold text-white">Continue Your Journey And Achieve Your Target</h1>
            </div>
            <div class="absolute right-0 top-0 w-32 h-32 opacity-20">
                <div class="w-full h-full bg-white rounded-full"></div>
            </div>
        </div>

        <!-- Course Progress Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div class="bg-white rounded-lg p-3 border border-gray-200 flex items-center gap-2">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-gray-600">8/15 Watched</p>
                    <p class="font-medium text-gray-900 text-sm">Front-end</p>
                </div>
                <button class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="bg-white rounded-lg p-3 border border-gray-200 flex items-center gap-2">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-gray-600">3/14 Watched</p>
                    <p class="font-medium text-gray-900 text-sm">Back-end</p>
                </div>
                <button class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="bg-white rounded-lg p-3 border border-gray-200 flex items-center gap-2">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-gray-600">2/6 Watched</p>
                    <p class="font-medium text-gray-900 text-sm">Product Design</p>
                </div>
                <button class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="bg-white rounded-lg p-3 border border-gray-200 flex items-center gap-2">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-gray-600">9/10 Watched</p>
                    <p class="font-medium text-gray-900 text-sm">Project Manager</p>
                </div>
                <button class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex gap-4">
            <!-- Left Content -->
            <div class="flex-1">
                <!-- Continue Watching -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold text-gray-900">Continue Watching</h2>
                        <div class="flex gap-1">
                            <button class="w-6 h-6 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50">
                                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button class="w-6 h-6 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50">
                                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <!-- Course Card 1 -->
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <div class="h-32 bg-gradient-to-br from-blue-400 to-purple-500 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">LEARN SOFTWARE DEVELOPMENT WITH US!</p>
                                </div>
                            </div>
                            <div class="p-3">
                                <span class="text-purple-600 text-xs font-medium">ACTIVITIES</span>
                                <h3 class="font-semibold text-gray-900 mt-1 mb-2 text-sm">Beginner's Guide To Becoming A Professional Frontend Developer</h3>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <div class="flex -space-x-1">
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">+124</span>
                                    </div>
                                    <button class="w-6 h-6 bg-purple-600 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Course Card 2 -->
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <div class="h-32 bg-gradient-to-br from-gray-800 to-gray-900 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">CODE EDITOR INTERFACE</p>
                                </div>
                            </div>
                            <div class="p-3">
                                <span class="text-purple-600 text-xs font-medium">BACKEND</span>
                                <h3 class="font-semibold text-gray-900 mt-1 mb-2 text-sm">Beginner's Guide To Becoming A Professional Backend Developer</h3>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <div class="flex -space-x-1">
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">+27</span>
                                    </div>
                                    <button class="w-6 h-6 bg-purple-600 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Course Card 3 -->
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <div class="h-32 bg-gradient-to-br from-blue-500 to-blue-600 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">How To Create Your Online Course Step 3</p>
                                </div>
                            </div>
                            <div class="p-3">
                                <span class="text-purple-600 text-xs font-medium">FRONTEND</span>
                                <h3 class="font-semibold text-gray-900 mt-1 mb-2 text-sm">Beginner's Guide To Becoming A Professional Frontend Developer</h3>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <div class="flex -space-x-1">
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">+87</span>
                                    </div>
                                    <button class="w-6 h-6 bg-purple-600 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Your Mentor Table -->
                <div class="bg-white rounded-lg border border-gray-200">
                    <div class="p-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-gray-900">Your Mentor</h2>
                            <a href="#" class="text-purple-600 hover:text-purple-700 font-medium text-sm">See All</a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">INSTRUCTOR NAME & DATE</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">COURSE TYPE</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">COURSE TITLE</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 bg-gray-300 rounded-full"></div>
                                            <div>
                                                <p class="font-medium text-gray-900 text-sm">John Doe</p>
                                                <p class="text-xs text-gray-500">Jan 15, 2024</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="text-purple-600 font-medium text-xs">FRONTEND</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Understanding Concept Of React</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <button class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 bg-gray-300 rounded-full"></div>
                                            <div>
                                                <p class="font-medium text-gray-900 text-sm">Jane Smith</p>
                                                <p class="text-xs text-gray-500">Jan 12, 2024</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="text-purple-600 font-medium text-xs">BACKEND</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Concept Of The Data Base</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <button class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 bg-gray-300 rounded-full"></div>
                                            <div>
                                                <p class="font-medium text-gray-900 text-sm">Mike Johnson</p>
                                                <p class="text-xs text-gray-500">Jan 10, 2024</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="text-purple-600 font-medium text-xs">FRONTEND</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Core Development Approaches</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <button class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="w-64 space-y-4">
                <!-- Your Profile -->
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold text-gray-900">Your Profile</h2>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full mx-auto mb-3 flex items-center justify-center">
                            <span class="text-white text-xl font-bold">A</span>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Good Morning Alex</h3>
                        <p class="text-gray-600 text-xs">Continue Your Journey And Achieve Your Target</p>
                    </div>

                    <div class="flex justify-center gap-3 mb-4">
                        <button class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12.828 7H4.828z"></path>
                            </svg>
                        </button>
                        <button class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                        </button>
                        <button class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Progress Chart -->
                    <div class="mb-4">
                        <div class="flex items-end justify-between h-16 gap-1">
                            <div class="w-4 bg-purple-200 rounded-t"></div>
                            <div class="w-4 bg-purple-300 rounded-t" style="height: 60%"></div>
                            <div class="w-4 bg-purple-400 rounded-t" style="height: 80%"></div>
                            <div class="w-4 bg-purple-500 rounded-t" style="height: 100%"></div>
                            <div class="w-4 bg-purple-600 rounded-t" style="height: 70%"></div>
                        </div>
                    </div>

                    <!-- Your Mentor List -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-900 text-sm">Your Mentor</h3>
                            <button class="w-5 h-5 bg-purple-600 rounded-full flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-xs">Kilam Rosvelt</p>
                                    <p class="text-xs text-gray-500">Software Developer</p>
                                </div>
                                <button class="text-purple-600 text-xs font-medium hover:text-purple-700">Follow</button>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-xs">Teodor Maskevich</p>
                                    <p class="text-xs text-gray-500">Product Owner</p>
                                </div>
                                <button class="text-purple-600 text-xs font-medium hover:text-purple-700">Follow</button>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-xs">Andrew Kooller</p>
                                    <p class="text-xs text-gray-500">Frontend Developer</p>
                                </div>
                                <button class="text-purple-600 text-xs font-medium hover:text-purple-700">Follow</button>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-xs">Adam Chekish</p>
                                    <p class="text-xs text-gray-500">Backend Developer</p>
                                </div>
                                <button class="text-purple-600 text-xs font-medium hover:text-purple-700">Follow</button>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-xs">Anton Peterson</p>
                                    <p class="text-xs text-gray-500">Software Developer</p>
                                </div>
                                <button class="text-purple-600 text-xs font-medium hover:text-purple-700">Follow</button>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-xs">Matew Jackson</p>
                                    <p class="text-xs text-gray-500">Product Designer</p>
                                </div>
                                <button class="text-purple-600 text-xs font-medium hover:text-purple-700">Follow</button>
                            </div>
                        </div>
                        
                        <button class="w-full mt-3 text-purple-600 hover:text-purple-700 font-medium text-xs">See All</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <script>
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('menu-overlay');
            
            if (sidebar && overlay) {
                const isHidden = sidebar.classList.contains('-translate-x-full');
                
                if (isHidden) {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                } else {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                }
            }
        }

        function desktopToggleSidebar() {
            try {
                window.dispatchEvent(new CustomEvent('sidebar:toggle'));
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const overlay = document.getElementById('menu-overlay');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', toggleMenu);
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        sidebar.classList.add('-translate-x-full');
                        overlay.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    <style>
    .ml-64{ margin-left:16rem; }
    .pl-64{ padding-left:16rem; }
    </style>

</body>

</html>
