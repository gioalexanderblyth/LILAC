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
    <link rel="stylesheet" href="events-enhanced.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <!-- Tesseract.js for OCR functionality -->
    <script src="https://unpkg.com/tesseract.js@4.1.1/dist/tesseract.min.js"></script>
    <!-- Enhanced OCR Processing -->
    <script src="events-ocr-enhanced.js"></script>
</head>

<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between relative transition-all duration-300 ease-in-out">
        <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
    </nav>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Upload Modal -->
    <div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900">Upload Files</h3>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="p-3">
                <!-- Manual Entry Section -->
                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-900 mb-2">Enter Event Details</h4>
                    <div class="space-y-2">
                        <!-- First Row -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="event-name" class="block text-xs font-medium text-gray-700 mb-0.5">Name of Event</label>
                                <input type="text" id="event-name" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500" placeholder="Enter event name">
                            </div>
                            <div>
                                <label for="event-organizer" class="block text-xs font-medium text-gray-700 mb-0.5">Organizer</label>
                                <input type="text" id="event-organizer" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500" placeholder="Enter organizer name">
                            </div>
                        </div>

                        <!-- Second Row -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="event-place" class="block text-xs font-medium text-gray-700 mb-0.5">Place</label>
                                <input type="text" id="event-place" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500" placeholder="Enter venue/location">
                            </div>
                            <div>
                                <label for="event-date" class="block text-xs font-medium text-gray-700 mb-0.5">Date</label>
                                <input type="date" id="event-date" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500">
                            </div>
                        </div>

                        <!-- Third Row -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="event-status" class="block text-xs font-medium text-gray-700 mb-0.5">Status</label>
                                <select id="event-status" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500">
                                    <option value="">Select status</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div>
                                <label for="event-type" class="block text-xs font-medium text-gray-700 mb-0.5">Event Type</label>
                                <select id="event-type" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500">
                                    <option value="">Select type</option>
                                    <option value="events">Events</option>
                                    <option value="activities">Activities</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Section -->
                <div>
                    <h4 class="text-xs font-semibold text-gray-900 mb-2">Upload Files</h4>
                    <!-- Drag and Drop Area -->
                    <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded p-3 text-center hover:border-purple-400 transition-colors cursor-pointer">
                        <div class="flex flex-col items-center">
                            <svg class="w-6 h-6 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <h4 class="text-xs font-medium text-gray-900 mb-1">Drop files here or click to browse</h4>
                            <button id="browse-files" class="bg-purple-600 text-white px-2 py-1 text-xs rounded hover:bg-purple-700 transition-colors" onclick="event.stopPropagation(); document.getElementById('file-input').click(); console.log('Choose Files clicked!');">
                                Choose Files
                            </button>
                        </div>
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
            <div class="flex items-center justify-between px-3 py-2 border-t border-gray-200">
                <button id="cancel-upload" class="px-2 py-1 text-xs text-gray-700 border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="add-manual-event" class="px-3 py-1.5 text-xs bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                    Add Event
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden File Input -->
    <input type="file" id="file-input" class="hidden" multiple accept="*/*" onchange="handleFileSelection(this.files)">

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-auto">
            <!-- Modal Header -->
            <div class="p-6 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Card</h3>
                <p class="text-gray-600 text-sm mb-6">Are you sure you want to delete this card? This action cannot be undone.</p>
                
                <!-- Modal Buttons -->
                <div class="flex gap-3 justify-center">
                    <button id="cancel-delete" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button id="confirm-delete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="main-content" class="p-3 pt-2 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out overflow-x-hidden min-w-0">
        <div class="flex gap-4">
            <!-- Left Content -->
            <div class="flex-1">
                <!-- Event Counter Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <!-- Upcoming Events Counter -->
                    <div class="bg-white rounded-lg p-3 border border-gray-200 flex items-center justify-between shadow-md hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-700">Upcoming Events</p>
                        </div>
                        <div id="upcoming-count" class="text-2xl font-bold text-gray-900">0</div>
                    </div>
                    
                    <!-- Completed Events Counter -->
                    <div class="bg-white rounded-lg p-3 border border-gray-200 flex items-center justify-between shadow-md hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-700">Completed Events</p>
                        </div>
                        <div id="completed-count" class="text-2xl font-bold text-gray-900">0</div>
                    </div>
                </div>

                <!-- Your Events and Activities Section -->
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
                        <div class="event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-shadow duration-300" data-type="activities">
                            <div class="h-32 bg-cover bg-center bg-no-repeat relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">SEA-TEACHER 10th BATCH EVALUATION MEETING</p>
                                </div>
                                <!-- Delete Button -->
                                <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
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
                        <div class="event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-shadow duration-300" data-type="events">
                            <div class="h-32 bg-gradient-to-br from-gray-800 to-gray-900 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">CODE EDITOR INTERFACE</p>
                                </div>
                                <!-- Delete Button -->
                                <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
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
                        <div class="event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-shadow duration-300" data-type="activities">
                            <div class="h-32 bg-gradient-to-br from-blue-500 to-blue-600 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">How To Create Your Online Course Step 3</p>
                                </div>
                                <!-- Delete Button -->
                                <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
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

                <!-- Events List Table -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="p-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-gray-900">Events List</h2>
                            <div class="flex items-center gap-3">
                                <!-- Search Bar -->
                                <div class="relative">
                                    <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <input type="text" placeholder="Search your events here..." class="w-48 pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                                <!-- Upload Button -->
                                <button id="events-upload-btn" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm">
                                    Upload
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">NAME OF EVENT</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">ORGANIZER</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">PLACE</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">DATE</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">STATUS</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr data-sample-row="true">
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-gray-900 text-sm">Annual Tech Conference 2024</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">CPU IT Department</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">CPU Auditorium</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-600 text-sm">Jan 25, 2024</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">Upcoming</span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button class="delete-row-btn text-gray-400 cursor-not-allowed p-1" title="Sample event - cannot be deleted" disabled>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr data-sample-row="true">
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-gray-900 text-sm">Student Orientation Program</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Student Affairs</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Main Campus</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-600 text-sm">Jan 12, 2024</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">Completed</span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button class="delete-row-btn text-gray-400 cursor-not-allowed p-1" title="Sample event - cannot be deleted" disabled>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr data-sample-row="true">
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-gray-900 text-sm">Research Symposium</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Research Office</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Library Hall</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-600 text-sm">Feb 15, 2024</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">Upcoming</span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button class="delete-row-btn text-gray-400 cursor-not-allowed p-1" title="Sample event - cannot be deleted" disabled>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
                <!-- Calendar -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold text-gray-900">Calendar</h2>
                        <div class="flex gap-1">
                            <button class="text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button class="text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Month/Year -->
                    <div class="text-center mb-3">
                        <h3 id="calendar-month-year" class="font-semibold text-gray-900">September 2025</h3>
                    </div>
                    
                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-7 gap-1 text-xs">
                        <!-- Days of week -->
                        <div class="text-center font-medium text-gray-500 py-1">S</div>
                        <div class="text-center font-medium text-gray-500 py-1">M</div>
                        <div class="text-center font-medium text-gray-500 py-1">T</div>
                        <div class="text-center font-medium text-gray-500 py-1">W</div>
                        <div class="text-center font-medium text-gray-500 py-1">T</div>
                        <div class="text-center font-medium text-gray-500 py-1">F</div>
                        <div class="text-center font-medium text-gray-500 py-1">S</div>
                        
                        <!-- Calendar days -->
                        <div id="calendar-days" class="contents">
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">1</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">2</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">3</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">4</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">5</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">6</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">7</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">8</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">9</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">10</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">11</div>
                            <div class="text-center py-1 bg-purple-600 text-white rounded font-medium">13</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">14</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">15</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">16</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">17</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">18</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">19</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">20</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">21</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">22</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">23</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">24</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">25</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">26</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">27</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">28</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">29</div>
                            <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">30</div>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-gray-900 text-sm">Upcoming Events</h3>
                        <button class="text-purple-600 text-xs font-medium hover:text-purple-700">View All</button>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-xs">Tech Conference 2024</p>
                                <p class="text-xs text-gray-500">Jan 25, 2024</p>
                                <p class="text-xs text-gray-500">CPU Auditorium</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-xs">Research Symposium</p>
                                <p class="text-xs text-gray-500">Feb 15, 2024</p>
                                <p class="text-xs text-gray-500">Library Hall</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-purple-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-xs">Student Activities Fair</p>
                                <p class="text-xs text-gray-500">Mar 10, 2024</p>
                                <p class="text-xs text-gray-500">Main Campus</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-orange-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-xs">Graduation Ceremony</p>
                                <p class="text-xs text-gray-500">Mar 25, 2024</p>
                                <p class="text-xs text-gray-500">Grand Auditorium</p>
                            </div>
                        </div>
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
        // Global file selection handler
        let isProcessingFiles = false;
        
        function handleFileSelection(files) {
            console.log('handleFileSelection called with', files.length, 'files');
            
            if (isProcessingFiles) {
                console.log('Already processing files, ignoring duplicate call');
                return;
            }
            
            isProcessingFiles = true;
            
            if (!files || files.length === 0) {
                console.log('No files selected');
                isProcessingFiles = false;
                return;
            }
            
            const fileArray = Array.from(files);
            console.log('Processing files:', fileArray.map(f => f.name));
            
            // Show selected files in the modal
            const fileList = document.getElementById('file-list');
            const selectedFilesContainer = document.getElementById('selected-files');
            
            if (fileList && selectedFilesContainer) {
                fileList.classList.remove('hidden');
                selectedFilesContainer.innerHTML = '';
                
                fileArray.forEach(file => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded text-sm';
                    fileItem.innerHTML = `
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-gray-900">${file.name}</span>
                        </div>
                        <span class="text-gray-500 text-xs">${(file.size / 1024).toFixed(1)} KB</span>
                    `;
                    selectedFilesContainer.appendChild(fileItem);
                });
            }
            
            // Start OCR processing
            setTimeout(() => {
                console.log('Checking OCR function availability...');
                console.log('window.processFilesWithOCR exists:', typeof window.processFilesWithOCR);
                console.log('Tesseract available:', typeof window.Tesseract);
                
                if (window.processFilesWithOCR) {
                    console.log('Starting enhanced OCR processing with', fileArray.length, 'files...');
                    processFilesWithOCR(fileArray);
                } else {
                    console.error('Enhanced OCR function not found - falling back to basic processing');
                    // Fallback: create basic cards without OCR
                    fileArray.forEach(file => {
                        if (file.type.startsWith('image/')) {
                            const basicEventData = {
                                name: file.name.replace(/\.[^/.]+$/, ''),
                                organizer: 'File Upload',
                                place: 'Not specified',
                                date: new Date().toISOString().split('T')[0],
                                status: 'upcoming',
                                type: 'activities'
                            };
                            
                            // Create a basic OCR-style card
                            createImageCard(file, basicEventData);
                            addEventToTable(basicEventData);
                        }
                    });
                }
                
                setTimeout(() => {
                    isProcessingFiles = false;
                }, 2000);
            }, 500);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Load existing events on page load
            loadExistingEvents();
            
            // Initialize delete listeners for existing table rows
            fixAllDeleteButtons();
            
            // Initialize upload modal functionality
            const eventsUploadBtn = document.getElementById('events-upload-btn');
            const uploadModal = document.getElementById('upload-modal');
            const closeModal = document.getElementById('close-modal');
            const cancelUpload = document.getElementById('cancel-upload');
            const fileInput = document.getElementById('file-input');
            const browseFiles = document.getElementById('browse-files');
            const dropZone = document.getElementById('drop-zone');
            const addManualEvent = document.getElementById('add-manual-event');

            // Open modal
            if (eventsUploadBtn && uploadModal) {
                eventsUploadBtn.onclick = function() {
                    console.log('Upload button clicked, opening modal');
                    uploadModal.classList.remove('hidden');
                    uploadModal.classList.add('flex');
                };
            }

            // Close modal
            function closeUploadModal() {
                if (uploadModal) {
                    uploadModal.classList.add('hidden');
                    uploadModal.classList.remove('flex');
                }
            }

            if (closeModal) closeModal.onclick = closeUploadModal;
            if (cancelUpload) cancelUpload.onclick = closeUploadModal;

            // File selection
            if (browseFiles && fileInput) {
                browseFiles.onclick = function(e) {
                    e.preventDefault();
                    fileInput.click();
                };
            }

            if (dropZone && fileInput) {
                dropZone.onclick = function(e) {
                    e.preventDefault();
                    fileInput.click();
                };
            }

            // Manual event addition
            if (addManualEvent) {
                addManualEvent.onclick = async function() {
                    const eventName = document.getElementById('event-name')?.value || '';
                    const organizer = document.getElementById('event-organizer')?.value || '';
                    const place = document.getElementById('event-place')?.value || '';
                    const date = document.getElementById('event-date')?.value || '';
                    const status = document.getElementById('event-status')?.value || '';
                    const type = document.getElementById('event-type')?.value || '';
                    
                    if (!eventName || !organizer || !place || !date || !status || !type) {
                        alert('Please fill in all fields');
                        return;
                    }
                    
                    const eventData = {
                        name: eventName,
                        organizer: organizer,
                        place: place,
                        date: date,
                        status: status,
                        type: type,
                        description: `${type.charAt(0).toUpperCase() + type.slice(1)} organized by ${organizer} at ${place}`
                    };
                    
                    // Save to API first
                    const savedEvent = await saveEventToAPI(eventData);
                    
                    if (savedEvent) {
                        // Create visual card with saved data (includes ID)
                        createManualEventCard(savedEvent);
                        
                        // Add to events table with saved data
                        addEventToTable(savedEvent);
                        
                        alert('Event added successfully!');
                        closeUploadModal();
                    } else {
                        alert('Failed to save event. Please try again.');
                        return;
                    }
                    
                    // Clear form
                    document.getElementById('event-name').value = '';
                    document.getElementById('event-organizer').value = '';
                    document.getElementById('event-place').value = '';
                    document.getElementById('event-date').value = '';
                    document.getElementById('event-status').value = '';
                    document.getElementById('event-type').value = '';
                };
            }

            // Initialize filter functionality
            initializeFilter();
            
            // Initialize delete functionality
            initializeDeleteFunctionality();
        });

        // Add event to table
        function addEventToTable(eventData) {
            const tableBody = document.querySelector('tbody.divide-y.divide-gray-200');
            if (!tableBody) return;
            
            const newRow = document.createElement('tr');
            const statusClass = eventData.status === 'upcoming' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
            const statusText = eventData.status.charAt(0).toUpperCase() + eventData.status.slice(1);
            
            newRow.innerHTML = `
                <td class="px-3 py-2">
                    <p class="font-medium text-gray-900 text-sm">${eventData.name}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-900 text-sm">${eventData.organizer}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-900 text-sm">${eventData.place}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-600 text-sm">${eventData.date}</p>
                </td>
                <td class="px-3 py-2">
                    <span class="inline-block ${statusClass} text-xs px-2 py-1 rounded-full font-medium">${statusText}</span>
                </td>
            `;
            
            tableBody.appendChild(newRow);
        }

        // Initialize filter functionality
        function initializeFilter() {
            const filterBtn = document.getElementById('filter-btn');
            const filterMenu = document.getElementById('filter-menu');
            const filterText = document.getElementById('filter-text');
            const filterOptions = document.querySelectorAll('.filter-option');
            
            if (filterBtn && filterMenu) {
                filterBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    filterMenu.classList.toggle('hidden');
                });
                
                document.addEventListener('click', function() {
                    filterMenu.classList.add('hidden');
                });
                
                filterMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            filterOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const filterValue = this.getAttribute('data-filter');
                    const filterLabel = this.textContent;
                    
                    if (filterText) {
                        filterText.textContent = filterLabel;
                    }
                    
                    applyFilter(filterValue);
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

        // Initialize delete functionality
        function initializeDeleteFunctionality() {
            let cardToDelete = null;
            const deleteModal = document.getElementById('delete-modal');
            const cancelDelete = document.getElementById('cancel-delete');
            const confirmDelete = document.getElementById('confirm-delete');
            
            function attachCardDeleteListeners() {
                const deleteButtons = document.querySelectorAll('.delete-card');
                deleteButtons.forEach(button => {
                    button.removeEventListener('click', handleCardDeleteClick);
                    button.addEventListener('click', handleCardDeleteClick);
                });
            }
            
            function handleCardDeleteClick(event) {
                event.stopPropagation();
                event.preventDefault();
                
                cardToDelete = event.target.closest('.event-card');
                
                if (cardToDelete && deleteModal) {
                    deleteModal.classList.remove('hidden');
                    deleteModal.classList.add('flex');
                }
            }
            
            if (cancelDelete) {
                cancelDelete.addEventListener('click', function() {
                    if (deleteModal) {
                        deleteModal.classList.add('hidden');
                        deleteModal.classList.remove('flex');
                    }
                    cardToDelete = null;
                });
            }
            
            if (confirmDelete) {
                confirmDelete.addEventListener('click', function() {
                    if (cardToDelete) {
                        cardToDelete.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        cardToDelete.style.opacity = '0';
                        cardToDelete.style.transform = 'scale(0.95)';
                        
                        setTimeout(() => {
                            if (cardToDelete && cardToDelete.parentNode) {
                                cardToDelete.remove();
                            }
                        }, 300);
                        
                        if (deleteModal) {
                            deleteModal.classList.add('hidden');
                            deleteModal.classList.remove('flex');
                        }
                        
                        cardToDelete = null;
                    }
                });
            }
            
            if (deleteModal) {
                deleteModal.addEventListener('click', function(event) {
                    if (event.target === deleteModal) {
                        deleteModal.classList.add('hidden');
                        deleteModal.classList.remove('flex');
                        cardToDelete = null;
                    }
                });
            }
            
            attachCardDeleteListeners();
            window.attachCardDeleteListeners = attachCardDeleteListeners;
        }

        // Create manual event card
        function createManualEventCard(eventData) {
            const cardsContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-3.gap-3');
            if (!cardsContainer) return;
            
            // Create new card element
            const newCard = document.createElement('div');
            newCard.className = `event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-all duration-300`;
            newCard.setAttribute('data-type', eventData.type);
            if (eventData.id) {
                newCard.setAttribute('data-event-id', eventData.id);
            }
            
            // Generate random gradient colors based on type
            const categoryGradients = {
                'events': [
                    'from-blue-400 to-purple-500',
                    'from-purple-500 to-pink-500',
                    'from-indigo-400 to-purple-600'
                ],
                'activities': [
                    'from-green-400 to-blue-500',
                    'from-yellow-400 to-orange-500',
                    'from-red-400 to-pink-500'
                ]
            };
            
            const gradients = categoryGradients[eventData.type] || categoryGradients['activities'];
            const randomGradient = gradients[Math.floor(Math.random() * gradients.length)];
            
            newCard.innerHTML = `
                <div class="h-32 bg-gradient-to-br ${randomGradient} relative">
                    <!-- Delete Button -->
                    <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    <!-- Event Title in Header - Bottom Left like original cards -->
                    <div class="absolute bottom-2 left-2 text-white">
                        <p class="text-xs font-medium uppercase" style="color: white !important; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">${eventData.name}</p>
                    </div>
                </div>
                <div class="p-3 bg-white">
                    <div class="mb-2">
                        <span class="text-purple-600 text-xs font-medium uppercase">${eventData.type}</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm mb-2">${eventData.name}</h3>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <div class="flex -space-x-1">
                                <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                            </div>
                            <span class="text-xs text-gray-600">+124</span>
                        </div>
                        <button class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center hover:bg-purple-700 transition-colors">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            
            // Add the card to the container with animation
            newCard.style.opacity = '0';
            newCard.style.transform = 'scale(0.95)';
            cardsContainer.appendChild(newCard);
            
            // Animate card in
            setTimeout(() => {
                newCard.style.transition = 'all 0.3s ease-out';
                newCard.style.opacity = '1';
                newCard.style.transform = 'scale(1)';
            }, 100);
            
            // Refresh delete listeners
            if (window.attachCardDeleteListeners) {
                window.attachCardDeleteListeners();
            }
            
            return newCard;
        }

        // Create image card (fallback for when OCR isn't available)
        function createImageCard(file, eventData) {
            const cardsContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-3.gap-3');
            if (!cardsContainer) return;
            
            // Create new card element
            const newCard = document.createElement('div');
            newCard.className = `event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-all duration-300`;
            newCard.setAttribute('data-type', eventData.type);
            if (eventData.id) {
                newCard.setAttribute('data-event-id', eventData.id);
            }
            
            // Create image URL
            const imageUrl = URL.createObjectURL(file);
            
            newCard.innerHTML = `
                <div class="h-32 relative overflow-hidden">
                    <img src="${imageUrl}" alt="${eventData.name}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <div class="absolute inset-0 bg-black bg-opacity-40"></div>
                    <!-- Delete Button -->
                    <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    <!-- Event Title in Header - Bottom Left like original cards -->
                    <div class="absolute bottom-2 left-2 text-white">
                        <p class="text-xs font-medium uppercase" style="color: white !important; text-shadow: 0 1px 3px rgba(0,0,0,0.7);">${eventData.name}</p>
                    </div>
                </div>
                <div class="p-3 bg-white">
                    <div class="mb-2">
                        <span class="text-purple-600 text-xs font-medium uppercase">${eventData.type}</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm mb-2">${eventData.name}</h3>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <div class="flex -space-x-1">
                                <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                            </div>
                            <span class="text-xs text-gray-600">+124</span>
                        </div>
                        <button class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center hover:bg-purple-700 transition-colors">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            
            // Add the card to the container with animation
            newCard.style.opacity = '0';
            newCard.style.transform = 'scale(0.95)';
            cardsContainer.appendChild(newCard);
            
            // Animate card in
            setTimeout(() => {
                newCard.style.transition = 'all 0.3s ease-out';
                newCard.style.opacity = '1';
                newCard.style.transform = 'scale(1)';
            }, 100);
            
            // Refresh delete listeners
            if (window.attachCardDeleteListeners) {
                window.attachCardDeleteListeners();
            }
            
            return newCard;
        }

        // Save event to API
        async function saveEventToAPI(eventData) {
            try {
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('name', eventData.name);
                formData.append('organizer', eventData.organizer);
                formData.append('place', eventData.place);
                formData.append('date', eventData.date);
                formData.append('status', eventData.status);
                formData.append('type', eventData.type);
                formData.append('description', eventData.description || '');
                formData.append('image_file', eventData.image_file || '');
                formData.append('ocr_text', eventData.ocr_text || '');
                formData.append('confidence', eventData.confidence || 0);

                const response = await fetch('api/events.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Save event result:', result);
                
                if (result.success) {
                    console.log('Event saved successfully:', result.event);
                    return result.event;
                } else {
                    console.error('Failed to save event:', result.message);
                    return null;
                }
            } catch (error) {
                console.error('Error saving event:', error);
                return null;
            }
        }

        // Load existing events from API
        async function loadExistingEvents() {
            try {
                console.log('🔄 Loading existing events from API...');
                const response = await fetch('api/events.php?action=get_all', {
                    cache: 'no-cache', // Prevent browser caching
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });
                const result = await response.json();
                
                console.log('📥 API Response:', result);
                
                if (result.success && result.events) {
                    console.log('✅ Loaded', result.events.length, 'existing events:', result.events);
                    
                    // Clear existing dynamic cards (keep the original 3 sample cards)
                    const cardsContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-3.gap-3');
                    if (cardsContainer) {
                        // Remove cards that have the "NEW" badge (dynamically created ones)
                        const allCards = cardsContainer.querySelectorAll('.event-card');
                        allCards.forEach(card => {
                            if (card) {
                                // Check if this card has a NEW badge (dynamically created)
                                const newBadge = card.querySelector('.bg-green-500');
                                if (newBadge && newBadge.textContent.trim() === 'NEW') {
                                    card.remove();
                                }
                            }
                        });
                    }
                    
                    // Clear existing table rows (keep header)
                    const tableBody = document.querySelector('tbody.divide-y.divide-gray-200');
                    if (tableBody) {
                        // Keep only the first 3 sample rows, remove the rest
                        const rows = tableBody.querySelectorAll('tr');
                        for (let i = 3; i < rows.length; i++) {
                            if (rows[i]) {
                                rows[i].remove();
                            }
                        }
                    }
                    
                    // Recreate cards and table entries for each saved event
                    result.events.forEach(eventData => {
                        console.log('Loading event with ID:', eventData.id, 'Name:', eventData.name);
                        
                        // Create visual card
                        if (eventData.image_file) {
                            // If it has an image file, create image card
                            // Note: We'd need to handle image URL properly here
                            createManualEventCard(eventData);
                        } else {
                            // Create regular manual card
                            createManualEventCard(eventData);
                        }
                        
                        // Add to table
                        addEventToTable(eventData);
                    });
                    
                    // Update event counters
                    updateEventCounters(result.events);
                    
                    // Attach delete listeners after all events are loaded
                    setTimeout(() => {
                        fixAllDeleteButtons();
                    }, 100);
                    
                } else {
                    console.log('No existing events found or failed to load');
                }
            } catch (error) {
                console.error('Error loading existing events:', error);
            }
        }

        // Enhanced addEventToTable to work with saved events
        function addEventToTable(eventData) {
            const tableBody = document.querySelector('tbody.divide-y.divide-gray-200');
            if (!tableBody) return;
            
            const newRow = document.createElement('tr');
            const statusClass = eventData.status === 'upcoming' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
            const statusText = eventData.status.charAt(0).toUpperCase() + eventData.status.slice(1);
            
            newRow.innerHTML = `
                <td class="px-3 py-2">
                    <p class="font-medium text-gray-900 text-sm">${eventData.name}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-900 text-sm">${eventData.organizer}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-900 text-sm">${eventData.place}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-600 text-sm">${eventData.date}</p>
                </td>
                <td class="px-3 py-2">
                    <span class="inline-block ${statusClass} text-xs px-2 py-1 rounded-full font-medium">${statusText}</span>
                </td>
                <td class="px-3 py-2 text-center">
                    <button class="delete-row-btn text-red-500 hover:text-red-700 transition-colors p-1" title="Delete event" data-event-id="${eventData.id || ''}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </td>
            `;
            
            // Add data attribute for potential deletion
            if (eventData.id) {
                newRow.setAttribute('data-event-id', eventData.id);
            }
            
            tableBody.appendChild(newRow);
            
            // Attach delete listener to the new button
            attachTableDeleteListeners();
        }

        // Delete event from API
        async function deleteEventFromAPI(eventId) {
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', eventId);

                const response = await fetch('api/events.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Delete event result:', result);
                
                if (result.success) {
                    console.log('Event deleted successfully');
                    
                    // Log file deletion status
                    if (result.file_deleted === true) {
                        console.log('✅ Associated file also deleted from server');
                    } else if (result.file_deleted === false) {
                        console.warn('⚠️ Event deleted but file could not be removed:', result.file_warning);
                    } else {
                        console.log('ℹ️ Event had no associated file to delete');
                    }
                    
                    return result;
                } else {
                    console.error('Failed to delete event:', result.message);
                    return false;
                }
            } catch (error) {
                console.error('Error deleting event:', error);
                return false;
            }
        }

        // Attach delete listeners to table rows
        function attachTableDeleteListeners() {
            const allDeleteButtons = document.querySelectorAll('.delete-row-btn');
            const enabledButtons = document.querySelectorAll('.delete-row-btn:not([disabled])');
            const disabledButtons = document.querySelectorAll('.delete-row-btn[disabled]');
            
            console.log('🔍 Delete button analysis:');
            console.log('- Total buttons:', allDeleteButtons.length);
            console.log('- Enabled buttons:', enabledButtons.length);
            console.log('- Disabled buttons:', disabledButtons.length);
            
            // Attach listeners to enabled buttons
            enabledButtons.forEach((button, index) => {
                // Remove existing listeners to prevent duplicates
                button.removeEventListener('click', handleTableDeleteClick);
                button.addEventListener('click', handleTableDeleteClick);
                const eventId = button.getAttribute('data-event-id');
                const row = button.closest('tr');
                const eventName = row ? row.querySelector('td:first-child p')?.textContent : 'Unknown';
                console.log(`✅ Button ${index + 1} - ID: "${eventId}", Name: "${eventName}"`);
            });
            
            // Add click handlers to disabled buttons to show message
            disabledButtons.forEach(button => {
                button.removeEventListener('click', handleDisabledButtonClick);
                button.addEventListener('click', handleDisabledButtonClick);
            });
            
            if (enabledButtons.length === 0) {
                console.warn('⚠️ No enabled delete buttons found! Only sample events exist.');
                console.log('💡 Add a new event to see working delete buttons.');
            } else {
                console.log('🎉 Delete functionality is ready!');
            }
        }
        
        // Handle clicks on disabled delete buttons
        function handleDisabledButtonClick(event) {
            event.preventDefault();
            event.stopPropagation();
            console.log('🚫 Disabled delete button clicked');
            alert('This is a sample event and cannot be deleted. Add your own events to see the delete functionality.');
        }

        // Handle delete button click
        async function handleTableDeleteClick(event) {
            console.log('🗑️ Delete button clicked!', event.target);
            event.preventDefault();
            event.stopPropagation();
            
            const button = event.currentTarget;
            const eventId = button.getAttribute('data-event-id');
            const row = button.closest('tr');
            const eventName = row ? row.querySelector('td:first-child p')?.textContent || 'this event' : 'Unknown event';
            
            console.log('🔍 Delete button data:', { 
                eventId, 
                eventName, 
                buttonElement: button,
                hasRow: !!row,
                buttonClasses: button.className,
                isDisabled: button.disabled
            });
            
            if (!eventId || eventId === '') {
                console.error('❌ No event ID found on button:', button);
                alert('Cannot delete this event - no ID found. This might be a sample event.');
                return;
            }
            
            if (button.disabled) {
                console.log('⚠️ Button is disabled - this is likely a sample event');
                alert('Cannot delete sample events.');
                return;
            }
            
            // Confirm deletion
            if (!confirm(`Are you sure you want to delete "${eventName}"? This action cannot be undone.`)) {
                return;
            }
            
            // Show loading state
            button.disabled = true;
            button.innerHTML = `
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;
            
            // Delete from API
            const result = await deleteEventFromAPI(eventId);
            
            if (result && result.success) {
                // Remove the table row with animation
                if (row) {
                    row.style.transition = 'all 0.3s ease-out';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    
                    setTimeout(() => {
                        if (row && row.parentNode) {
                            row.remove();
                        }
                    }, 300);
                }
                
                // Also remove the corresponding card if it exists
                const cards = document.querySelectorAll('.event-card');
                cards.forEach(card => {
                    if (card) {
                        const cardEventId = card.getAttribute('data-event-id');
                        if (cardEventId === eventId) {
                            card.style.transition = 'all 0.3s ease-out';
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.95)';
                            setTimeout(() => {
                                if (card && card.parentNode) {
                                    card.remove();
                                }
                            }, 300);
                        }
                    }
                });
                
                // Show success message with file deletion status
                let message = 'Event deleted successfully!';
                if (result.file_deleted === true) {
                    message += ' (File also removed)';
                } else if (result.file_deleted === false) {
                    message += ' (File could not be removed)';
                }
                showTemporaryMessage(message, 'success');
            } else {
                // Reset button on failure
                button.disabled = false;
                button.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                `;
                alert('Failed to delete event. Please try again.');
            }
        }

        // Show temporary success/error message
        function showTemporaryMessage(message, type = 'success') {
            const messageDiv = document.createElement('div');
            messageDiv.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white font-medium z-50 transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            messageDiv.textContent = message;
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(-20px)';
            
            document.body.appendChild(messageDiv);
            
            // Animate in
            setTimeout(() => {
                messageDiv.style.opacity = '1';
                messageDiv.style.transform = 'translateY(0)';
            }, 100);
            
            // Remove after 3 seconds
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                messageDiv.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    document.body.removeChild(messageDiv);
                }, 300);
            }, 3000);
        }

        // Debug function to test delete functionality
        function testDeleteFunctionality() {
            console.log('🧪 Testing delete functionality...');
            const allButtons = document.querySelectorAll('.delete-row-btn');
            const enabledButtons = document.querySelectorAll('.delete-row-btn:not([disabled])');
            
            console.log('📊 Delete button summary:');
            console.log('- Total buttons found:', allButtons.length);
            console.log('- Enabled buttons found:', enabledButtons.length);
            console.log('- Disabled buttons found:', allButtons.length - enabledButtons.length);
            
            allButtons.forEach((button, index) => {
                const eventId = button.getAttribute('data-event-id');
                const row = button.closest('tr');
                const eventName = row ? row.querySelector('td:first-child p')?.textContent : 'Unknown';
                const isDisabled = button.disabled;
                const isSample = row ? row.hasAttribute('data-sample-row') : false;
                console.log(`- Button ${index + 1}: "${eventName}" | ID="${eventId}" | Disabled=${isDisabled} | Sample=${isSample}`);
            });
            
            if (enabledButtons.length > 0) {
                console.log('✅ Found enabled buttons - delete functionality should work');
                console.log('🔍 Try clicking the red delete button next to TEST event');
            } else {
                console.log('❌ No enabled buttons found - add new events to test delete');
            }
        }
        
        // Quick fix function to manually delete the TEST event
        function deleteTestEvent() {
            console.log('🗑️ Manually deleting TEST event...');
            
            if (typeof deleteEventFromAPI === 'function') {
                deleteEventFromAPI(2).then(result => {
                    if (result && result.success) {
                        console.log('✅ TEST event deleted successfully!');
                        // Force reload to clear any cached data
                        setTimeout(() => {
                            window.location.reload(true); // Force reload from server
                        }, 500);
                    } else {
                        console.error('❌ Failed to delete TEST event:', result);
                    }
                }).catch(error => {
                    console.error('❌ Error deleting TEST event:', error);
                });
            } else {
                console.error('❌ deleteEventFromAPI function not found');
            }
        }

        // Function to force refresh events from server
        function forceRefreshEvents() {
            console.log('🔄 Force refreshing events from server...');
            // Clear any cached data
            if (typeof loadExistingEvents === 'function') {
                loadExistingEvents().then(() => {
                    console.log('✅ Events refreshed from server');
                }).catch(error => {
                    console.error('❌ Error refreshing events:', error);
                });
            } else {
                console.log('🔄 Reloading page to refresh events...');
                window.location.reload(true);
            }
        }
        
        // Force update all delete button states
        function fixAllDeleteButtons() {
            console.log('🔧 Fixing all delete button states...');
            const allRows = document.querySelectorAll('tbody tr');
            
            allRows.forEach((row, index) => {
                const button = row.querySelector('.delete-row-btn');
                const isSample = row.hasAttribute('data-sample-row');
                const eventId = row.getAttribute('data-event-id');
                const eventName = row.querySelector('td:first-child p')?.textContent || 'Unknown';
                
                if (button) {
                    if (isSample || !eventId) {
                        // This is a sample event - make it clearly disabled
                        button.disabled = true;
                        button.className = 'delete-row-btn text-gray-400 cursor-not-allowed p-1';
                        button.title = 'Sample event - cannot be deleted';
                        console.log(`🔒 Disabled: "${eventName}" (Sample: ${isSample}, No ID: ${!eventId})`);
                    } else {
                        // This is a real event - make it enabled
                        button.disabled = false;
                        button.className = 'delete-row-btn text-red-500 hover:text-red-700 transition-colors p-1';
                        button.title = 'Delete event';
                        console.log(`🗑️ Enabled: "${eventName}" (ID: ${eventId})`);
                    }
                }
            });
            
            // Reattach listeners
            attachTableDeleteListeners();
            console.log('✅ All delete buttons updated!');
        }

        // Make functions globally available
        window.addEventToTable = addEventToTable;
        window.createManualEventCard = createManualEventCard;
        window.createImageCard = createImageCard;
        window.saveEventToAPI = saveEventToAPI;
        window.loadExistingEvents = loadExistingEvents;
        window.deleteEventFromAPI = deleteEventFromAPI;
        window.attachTableDeleteListeners = attachTableDeleteListeners;
        window.testDeleteFunctionality = testDeleteFunctionality;
        window.fixAllDeleteButtons = fixAllDeleteButtons;
        window.deleteTestEvent = deleteTestEvent;
        
        // Helper functions for OCR processing
        function extractEventName(text) {
            const lines = text.split('\n').map(line => line.trim()).filter(line => line.length > 0);
            
            for (let i = 0; i < Math.min(lines.length, 3); i++) {
                const line = lines[i];
                if (line.length > 5 && line.length < 100) {
                    return line.replace(/[^\w\s]/g, ' ').replace(/\s+/g, ' ').trim();
                }
            }
            
            return 'Untitled Event';
        }

        function extractEventDetails(text) {
            const lowerText = text.toLowerCase();
            
            let organizer = 'System Generated';
            let place = 'Not specified';
            let date = new Date().toISOString().split('T')[0];
            
            // Simple extraction logic
            const lines = text.split('\n').map(line => line.trim()).filter(line => line.length > 0);
            
            lines.forEach(line => {
                const lowerLine = line.toLowerCase();
                if (lowerLine.includes('organizer') || lowerLine.includes('by')) {
                    organizer = line.substring(0, 50);
                }
                if (lowerLine.includes('venue') || lowerLine.includes('location') || lowerLine.includes('at')) {
                    place = line.substring(0, 50);
                }
            });
            
            return { organizer, place, date };
        }

        function generateTitle(extractedText, filename) {
            if (extractedText && extractedText.trim()) {
                const lines = extractedText.split('\n').filter(line => line.trim().length > 0);
                if (lines.length > 0) {
                    return lines[0].trim().substring(0, 50) + '...';
                }
            }
            
            const nameWithoutExt = filename.replace(/\.[^/.]+$/, '');
            return nameWithoutExt.charAt(0).toUpperCase() + nameWithoutExt.slice(1).replace(/[-_]/g, ' ');
        }
    </script>

</body>

</html> 


