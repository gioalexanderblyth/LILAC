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
    <!-- Tesseract.js for OCR functionality -->
    <script src="https://unpkg.com/tesseract.js@4.1.1/dist/tesseract.min.js"></script>
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
            <!-- Upload Button -->
            <button id="upload-btn" class="p-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors bg-purple-50 border-purple-200 hover:bg-purple-100" title="Upload Files">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
            </button>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Upload Modal -->
    <div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Upload Files</h3>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6">
                <!-- Drag and Drop Area -->
                <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-purple-400 transition-colors cursor-pointer">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Drop files here</h4>
                        <p class="text-gray-500 mb-4">or click to browse files</p>
                        <button id="browse-files" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                            Choose Files
                        </button>
                    </div>
                </div>
                
                <!-- File List -->
                <div id="file-list" class="mt-4 hidden">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Selected Files:</h4>
                    <div id="selected-files" class="space-y-2 max-h-32 overflow-y-auto">
                        <!-- Files will be listed here -->
                    </div>
                </div>
                
                <!-- OCR Progress -->
                <div id="ocr-progress" class="mt-4 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900">Scanning content...</span>
                        <span id="ocr-status" class="text-sm text-gray-500">Processing</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="ocr-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Upload Progress -->
                <div id="upload-progress" class="mt-4 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900">Uploading...</span>
                        <span id="progress-text" class="text-sm text-gray-500">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-purple-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <!-- OCR Results -->
                <div id="ocr-results" class="mt-4 hidden">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Content Analysis:</h4>
                    <div id="detected-content" class="bg-gray-50 rounded p-3 text-sm">
                        <!-- OCR results will be displayed here -->
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
                <button id="cancel-upload" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="start-upload" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Upload Files
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden File Input -->
    <input type="file" id="file-input" class="hidden" multiple accept="*/*">

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
                        <h2 class="text-lg font-bold text-gray-900">Your Events and Activities</h2>
                        <div class="flex items-center gap-2">
                            <!-- Filter Dropdown -->
                            <div class="relative">
                                <button id="filter-btn" class="flex items-center gap-1 px-3 py-1 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v4.586a1 1 0 01-.293.707l-2 2A1 1 0 0110 20.586V14.414a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                    </svg>
                                    <span id="filter-text">All</span>
                                    <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <!-- Filter Dropdown Menu -->
                                <div id="filter-menu" class="absolute top-full left-0 mt-1 w-32 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden">
                                    <div class="p-1">
                                        <button class="filter-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50 rounded" data-filter="all">All</button>
                                        <button class="filter-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50 rounded" data-filter="activities">Activities</button>
                                        <button class="filter-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50 rounded" data-filter="events">Events</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Navigation Arrows -->
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
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <!-- Course Card 1 -->
                        <div class="event-card bg-white rounded-lg border border-gray-200 overflow-hidden" data-type="activities">
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
                        <div class="event-card bg-white rounded-lg border border-gray-200 overflow-hidden" data-type="events">
                            <div class="h-32 bg-gradient-to-br from-gray-800 to-gray-900 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">CODE EDITOR INTERFACE</p>
                                </div>
                            </div>
                            <div class="p-3">
                                <span class="text-purple-600 text-xs font-medium">EVENTS</span>
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
                        <div class="event-card bg-white rounded-lg border border-gray-200 overflow-hidden" data-type="activities">
                            <div class="h-32 bg-gradient-to-br from-blue-500 to-blue-600 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">How To Create Your Online Course Step 3</p>
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
                        <h3 class="text-base font-bold text-gray-900 mb-1">Good Morning Lesley</h3>
                        <p class="text-gray-600 text-xs">Let's reach the goal to achieve the better future of Central Philippine University!</p>
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
            
            // Initialize upload modal
            initializeUploadModal();
            
            // Initialize filter functionality
            initializeFilter();
        });

        let selectedFiles = [];
        
        function initializeUploadModal() {
            const uploadBtn = document.getElementById('upload-btn');
            const uploadModal = document.getElementById('upload-modal');
            const closeModal = document.getElementById('close-modal');
            const cancelUpload = document.getElementById('cancel-upload');
            const fileInput = document.getElementById('file-input');
            const browseFiles = document.getElementById('browse-files');
            const dropZone = document.getElementById('drop-zone');
            const startUpload = document.getElementById('start-upload');
            
            // Open modal when upload button is clicked
            if (uploadBtn && uploadModal) {
                uploadBtn.addEventListener('click', function() {
                    uploadModal.classList.remove('hidden');
                });
            }
            
            // Close modal functions
            window.closeUploadModal = function() {
                uploadModal.classList.add('hidden');
                resetModal();
            }
            
            if (closeModal) {
                closeModal.addEventListener('click', window.closeUploadModal);
            }
            
            if (cancelUpload) {
                cancelUpload.addEventListener('click', window.closeUploadModal);
            }
            
            // Close modal when clicking outside
            if (uploadModal) {
                uploadModal.addEventListener('click', function(e) {
                    if (e.target === uploadModal) {
                        window.closeUploadModal();
                    }
                });
            }
            
            // Browse files button
            if (browseFiles && fileInput) {
                browseFiles.addEventListener('click', function() {
                    fileInput.click();
                });
            }
            
            // Drop zone click
            if (dropZone && fileInput) {
                dropZone.addEventListener('click', function() {
                    fileInput.click();
                });
            }
            
            // File input change
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files);
                    if (files.length > 0) {
                        selectedFiles = files;
                        displaySelectedFiles(files);
                        enableUploadButton();
                    }
                });
            }
            
            // Drag and drop functionality
            if (dropZone) {
                setupModalDragAndDrop(dropZone);
            }
            
            // Start upload button
            if (startUpload) {
                startUpload.addEventListener('click', function() {
                    if (selectedFiles.length > 0) {
                        handleFileUpload(selectedFiles);
                    }
                });
            }
        }
        
        function setupModalDragAndDrop(dropZone) {
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            // Highlight drop area
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, function() {
                    dropZone.classList.add('border-purple-400', 'bg-purple-50');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, function() {
                    dropZone.classList.remove('border-purple-400', 'bg-purple-50');
                }, false);
            });
            
            // Handle dropped files
            dropZone.addEventListener('drop', function(e) {
                const files = Array.from(e.dataTransfer.files);
                if (files.length > 0) {
                    selectedFiles = files;
                    displaySelectedFiles(files);
                    enableUploadButton();
                }
            }, false);
        }
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        function displaySelectedFiles(files) {
            const fileList = document.getElementById('file-list');
            const selectedFilesContainer = document.getElementById('selected-files');
            
            if (fileList && selectedFilesContainer) {
                fileList.classList.remove('hidden');
                selectedFilesContainer.innerHTML = '';
                
                files.forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded text-sm';
                    fileItem.innerHTML = `
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-gray-900">${file.name}</span>
                            <span class="text-gray-500">(${formatFileSize(file.size)})</span>
                        </div>
                        <button class="text-red-500 hover:text-red-700" onclick="removeFile(${index})">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    `;
                    selectedFilesContainer.appendChild(fileItem);
                });
            }
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            if (selectedFiles.length > 0) {
                displaySelectedFiles(selectedFiles);
            } else {
                document.getElementById('file-list').classList.add('hidden');
                disableUploadButton();
            }
        }
        
        function enableUploadButton() {
            const startUpload = document.getElementById('start-upload');
            if (startUpload) {
                startUpload.disabled = false;
            }
        }
        
        function disableUploadButton() {
            const startUpload = document.getElementById('start-upload');
            if (startUpload) {
                startUpload.disabled = true;
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function handleFileUpload(files) {
            const uploadProgress = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const startUpload = document.getElementById('start-upload');
            
            // Show progress
            if (uploadProgress) {
                uploadProgress.classList.remove('hidden');
            }
            
            // Disable upload button
            if (startUpload) {
                startUpload.disabled = true;
                startUpload.textContent = 'Uploading...';
            }
            
            // Simulate upload progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 100) progress = 100;
                
                if (progressBar) {
                    progressBar.style.width = progress + '%';
                }
                if (progressText) {
                    progressText.textContent = Math.round(progress) + '%';
                }
                
                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        showUploadSuccess(files.length);
                        setTimeout(window.closeUploadModal, 1500);
                    }, 500);
                }
            }, 200);
        }
        
        function showUploadSuccess(fileCount) {
            const uploadProgress = document.getElementById('upload-progress');
            const startUpload = document.getElementById('start-upload');
            
            if (uploadProgress) {
                uploadProgress.innerHTML = `
                    <div class="flex items-center justify-center text-green-600">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        ${fileCount} file(s) uploaded successfully!
                    </div>
                `;
            }
            
            if (startUpload) {
                startUpload.textContent = 'Complete!';
                startUpload.classList.add('bg-green-600', 'hover:bg-green-700');
                startUpload.classList.remove('bg-purple-600', 'hover:bg-purple-700');
            }
        }
        
        function resetModal() {
            const fileList = document.getElementById('file-list');
            const uploadProgress = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const startUpload = document.getElementById('start-upload');
            const fileInput = document.getElementById('file-input');
            
            selectedFiles = [];
            
            if (fileList) fileList.classList.add('hidden');
            if (uploadProgress) {
                uploadProgress.classList.add('hidden');
                uploadProgress.innerHTML = `
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900">Uploading...</span>
                        <span id="progress-text" class="text-sm text-gray-500">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-purple-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                `;
            }
            if (startUpload) {
                startUpload.disabled = true;
                startUpload.textContent = 'Upload Files';
                startUpload.classList.remove('bg-green-600', 'hover:bg-green-700');
                startUpload.classList.add('bg-purple-600', 'hover:bg-purple-700');
            }
            if (fileInput) {
                fileInput.value = '';
            }
        }
        
        function initializeFilter() {
            const filterBtn = document.getElementById('filter-btn');
            const filterMenu = document.getElementById('filter-menu');
            const filterText = document.getElementById('filter-text');
            const filterOptions = document.querySelectorAll('.filter-option');
            
            // Toggle filter dropdown
            if (filterBtn && filterMenu) {
                filterBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    filterMenu.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    filterMenu.classList.add('hidden');
                });
                
                // Prevent dropdown from closing when clicking inside
                filterMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Handle filter option clicks
            filterOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const filterValue = this.getAttribute('data-filter');
                    const filterLabel = this.textContent;
                    
                    // Update filter button text
                    if (filterText) {
                        filterText.textContent = filterLabel;
                    }
                    
                    // Apply filter
                    applyFilter(filterValue);
                    
                    // Close dropdown
                    filterMenu.classList.add('hidden');
                });
            });
        }
        
        function applyFilter(filterValue) {
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                if (filterValue === 'all') {
                    card.style.display = 'block';
                } else {
                    const cardType = card.getAttribute('data-type');
                    if (cardType === filterValue) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }
    </script>

    <style>
    .ml-64{ margin-left:16rem; }
    .pl-64{ padding-left:16rem; }
    </style>

</body>

</html>
