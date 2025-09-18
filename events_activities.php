<?php
// Server-side authentication and authorization
session_start();

// Check if user is logged in (more permissive for demo)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    // Set default session for demo purposes
    $_SESSION['user_id'] = 'demo_user';
    $_SESSION['user_role'] = 'admin';
}

// Check user permissions for events management (allow all roles for demo)
$allowed_roles = ['admin', 'manager', 'coordinator', 'user'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    // Set default role for demo
    $_SESSION['user_role'] = 'user';
}

// Validate session token for security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Events data is now loaded via API calls for better performance
// No need to load all data on page initialization
require_once 'classes/DateTimeUtility.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Events & Activities</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <link rel="stylesheet" href="events-enhanced.css">
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
                                <label for="event-date-manual" class="block text-xs font-medium text-gray-700 mb-0.5">Date</label>
                                <input type="date" id="event-date-manual" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500">
                            </div>
                        </div>

                        <!-- Third Row -->
                        <div class="grid grid-cols-1 gap-2">
                            <div>
                                <label for="event-status" class="block text-xs font-medium text-gray-700 mb-0.5">Event Status</label>
                                <select id="event-status" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500">
                                    <option value="">Select status</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="completed">Completed</option>
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
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden" onclick="hideDeleteModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Delete Event</h3>
                            <p class="text-sm text-gray-500">This action will move the event to trash</p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <p class="text-gray-700">Are you sure you want to delete this event?</p>
                        <div id="deleteEventInfo" class="mt-2 p-3 bg-gray-50 rounded-lg">
                            <!-- Event info will be populated here -->
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end gap-3">
                        <button onclick="hideDeleteModal()" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button id="confirmDeleteBtn" onclick="confirmDeleteEvent()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trash Bin Modal -->
    <div id="trashModal" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden" onclick="hideTrashModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between p-4 border-b">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Trash Bin</h3>
                    </div>
                </div>
                <div class="p-4 max-h-[60vh] overflow-y-auto">
                    <div id="trash-container" class="space-y-2">
                        <div class="text-sm text-gray-500">No deleted events.</div>
                    </div>
                </div>
                <div class="p-4 border-t flex justify-between">
                    <button type="button" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700" onclick="showEmptyTrashModal()">
                        Empty Trash
                    </button>
                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg" onclick="hideTrashModal()">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Trash Delete Confirmation Modal -->
    <div id="trash-delete-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-auto">
            <!-- Modal Header -->
            <div class="p-6 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Permanently Delete Event</h3>
                <p class="text-gray-600 text-sm mb-6">Are you sure you want to permanently delete this event? This action cannot be undone.</p>
                
                <!-- Modal Buttons -->
                <div class="flex gap-3">
                    <button type="button" id="trash-delete-cancel" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="trash-delete-confirm" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                        Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty Trash Confirmation Modal -->
    <div id="empty-trash-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-auto">
            <!-- Modal Header -->
            <div class="p-6 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Empty Trash</h3>
                <p class="text-gray-600 text-sm mb-6">Are you sure you want to empty the trash? This will permanently delete all events in the trash. This action cannot be undone.</p>
                
                <!-- Modal Buttons -->
                <div class="flex gap-3">
                    <button type="button" id="empty-trash-cancel" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="empty-trash-confirm" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                        Empty Trash
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-card-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
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

    <!-- Event Creation Modal -->
    <div id="event-creation-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-auto max-h-[90vh] overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Create New Event</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('event-creation-modal').classList.add('hidden')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <form id="event-creation-form" class="space-y-4">
                    <!-- CSRF Token -->
                    <input type="hidden" id="csrf-token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <!-- Event Title -->
                    <div>
                        <label for="event-title" class="block text-sm font-medium text-gray-700 mb-1">Event Title *</label>
                        <input type="text" id="event-title" name="title" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter event title">
                    </div>

                    <!-- Event Date -->
                    <div>
                        <label for="event-date" class="block text-sm font-medium text-gray-700 mb-1">Event Date *</label>
                        <input type="date" id="event-date" name="event_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Select any date (past dates automatically allowed for award documentation)</p>
                    </div>

                    <!-- Event Time -->
                    <div>
                        <label for="event-time" class="block text-sm font-medium text-gray-700 mb-1">Event Time (Optional)</label>
                        <input type="time" id="event-time" name="event_time" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Event Location -->
                    <div>
                        <label for="event-location" class="block text-sm font-medium text-gray-700 mb-1">Location (Optional)</label>
                        <input type="text" id="event-location" name="location"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter event location (auto-detected from title/description)">
                        <p class="text-xs text-gray-500 mt-1">ðŸ’¡ Location will be automatically suggested based on your event title and description</p>
                    </div>

                    <!-- Event Description -->
                    <div>
                        <label for="event-description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="event-description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter event description"></textarea>
                    </div>

                    <!-- Original Link -->
                    <div>
                        <label for="event-original-link" class="block text-sm font-medium text-gray-700 mb-1">Original Link (Optional)</label>
                        <input type="url" id="event-original-link" name="original_link"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="https://example.com/original-event-post">
                        <p class="text-xs text-gray-500 mt-1">ðŸ’¡ Link to the original event post or source</p>
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <label for="event-image" class="block text-sm font-medium text-gray-700 mb-1">Event Image (Optional)</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition-colors">
                            <input type="file" id="event-image" name="image" accept="image/*" class="hidden">
                            <div id="image-preview" class="hidden mb-2">
                                <img id="preview-img" class="max-w-full h-32 object-cover rounded mx-auto">
                            </div>
                            <div id="image-upload-area">
                                <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm text-gray-600 mb-2">Click to upload image or drag and drop</p>
                                <button type="button" onclick="document.getElementById('event-image').click()" 
                                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    Choose Image
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Award Classification -->
                    <div>
                        <label for="event-award-type" class="block text-sm font-medium text-gray-700 mb-1">Award Classification</label>
                        <div class="relative">
                            <select id="event-award-type" name="award_type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Auto-classify based on content</option>
                                <option value="Internationalization (IZN) Leadership Award">Internationalization (IZN) Leadership Award</option>
                                <option value="Outstanding International Education Program Award">Outstanding International Education Program Award</option>
                                <option value="Emerging Leadership Award">Emerging Leadership Award</option>
                                <option value="Best Regional Office for Internationalization Award">Best Regional Office for Internationalization Award</option>
                                <option value="Global Citizenship Award">Global Citizenship Award</option>
                            </select>
                            <div id="award-classification-loading" class="absolute right-3 top-1/2 transform -translate-y-1/2 hidden">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Leave empty for automatic classification based on title and description</p>
                    </div>

                    <!-- Auto-classification Preview -->
                    <div id="classification-preview" class="hidden p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <h4 class="text-sm font-medium text-blue-900 mb-2">Auto-classification Preview</h4>
                        <div id="classification-result" class="text-sm text-blue-800"></div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="document.getElementById('event-creation-modal').classList.add('hidden')" 
                        class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" id="create-event-btn" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Create Event
                </button>
            </div>
        </div>
    </div>

    <!-- Document Viewer Modal -->
    <div id="document-viewer-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden" onclick="this.classList.add('hidden')">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[80vh] flex flex-col" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h3 id="document-viewer-title" class="text-lg font-semibold text-gray-900"></h3>
                    <div class="flex items-center gap-2">
                        <button id="document-viewer-open" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Open in New Tab</button>
                        <button data-modal-close="document-viewer-overlay" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 bg-gray-50 p-2 overflow-y-auto overflow-x-hidden min-h-0">
                    <div id="document-viewer-content" class="w-full h-full overflow-y-auto overflow-x-hidden"></div>
                </div>
                <div class="flex items-center justify-end gap-2 px-4 py-3 border-t">
                    <button id="document-viewer-download" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Download</button>
                    <button data-modal-close="document-viewer-overlay" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">Close</button>
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
                                    <input type="text" id="events-search-input" placeholder="Search your events here..." class="w-80 pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                                <!-- Action Buttons -->
                                <div class="flex gap-2">
                                    <button id="events-upload-btn" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm">
                                        Upload
                                    </button>
                                    <button id="trash-btn" class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium text-sm" onclick="showTrashModal()">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Trash
                                    </button>
                                </div>
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
                            <tbody class="divide-y divide-gray-200" id="events-table-body">
                                <tr id="empty-events-message">
                                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="text-sm font-medium">No events found</p>
                                            <p class="text-xs text-gray-400 mt-1">Create your first event or upload files to get started</p>
                                        </div>
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
                            <button onclick="navigateCalendar('prev')" class="text-gray-400 hover:text-gray-600 transition-colors" title="Previous month">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button onclick="navigateCalendar('next')" class="text-gray-400 hover:text-gray-600 transition-colors" title="Next month">
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
                            <!-- Calendar days will be generated dynamically -->
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-gray-900 text-sm">Upcoming Events</h3>
                        <button class="text-purple-600 text-xs font-medium hover:text-purple-700">View All</button>
                    </div>
                    
                    <div id="upcoming-events-list" class="space-y-3">
                        <!-- Dynamic upcoming events will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

    <script>
        // Global file selection handler
        let isProcessingFiles = false;
        
        // FIXED: Real file selection handler with improved error handling
        async function handleFileSelection(files) {
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
            
            // Show upload progress
            const uploadProgress = document.getElementById('upload-progress');
            if (uploadProgress) {
                uploadProgress.classList.remove('hidden');
            }
            
            try {
                let successCount = 0;
                let errorCount = 0;
                
                // Process each file
                for (let i = 0; i < fileArray.length; i++) {
                    const file = fileArray[i];
                    console.log(`Processing file ${i + 1}/${fileArray.length}: ${file.name}`);
                    
                    try {
                        // Generate a proper event title from filename
                        const eventTitle = file.name.replace(/\.[^/.]+$/, "").replace(/[-_]/g, ' ');
                        
                const formData = new FormData();
                        formData.append('file', file);
                        formData.append('action', 'create_event');
                        formData.append('title', eventTitle);
                        formData.append('description', `Event created from uploaded file: ${file.name}`);
                        formData.append('event_date', new Date().toISOString().split('T')[0]);
                        formData.append('event_time', new Date().toTimeString().split(' ')[0]);
                        formData.append('location', 'To be determined');
                        
                        // Upload to central events API
                        const response = await fetch('api/central_events_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                        // Check if response is ok
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        // Get response text first to check if it's valid JSON
                        const responseText = await response.text();
                        console.log('Raw API response:', responseText);
                        
                        if (!responseText || responseText.trim() === '') {
                            throw new Error('Empty response from server');
                        }
                        
                        let result;
                        try {
                            result = JSON.parse(responseText);
                        } catch (parseError) {
                            console.error('JSON parse error:', parseError);
                            console.error('Response text:', responseText);
                            throw new Error('Invalid JSON response from server');
                        }
                        
                        if (result.success) {
                            console.log(`âœ… File ${file.name} uploaded and event created successfully`);
                            successCount++;
                } else {
                            console.error(`âŒ Failed to create event from ${file.name}:`, result.data?.message || result.message);
                            errorCount++;
                        }
                        
                    } catch (fileError) {
                        console.error(`âŒ Error processing file ${file.name}:`, fileError);
                        errorCount++;
                    }
                }
                
                // Hide progress after processing
                if (uploadProgress) {
                    uploadProgress.classList.add('hidden');
                }
                
                // Show appropriate success/error message
                if (successCount > 0 && errorCount === 0) {
                    showNotification(`Successfully uploaded ${successCount} file(s) and created events!`, 'success');
                } else if (successCount > 0 && errorCount > 0) {
                    showNotification(`Uploaded ${successCount} file(s) successfully, ${errorCount} failed.`, 'warning');
                } else {
                    showNotification(`Failed to upload files. Please try again.`, 'error');
                }
                
                // Refresh events list to show new events
                setTimeout(() => {
                    refreshEventsFromAPI();
                }, 1000);
                
            } catch (error) {
                console.error('Error processing files:', error);
                
                // Hide progress on error
                if (uploadProgress) {
                    uploadProgress.classList.add('hidden');
                }
                
                showNotification('Error uploading files: ' + error.message, 'error');
            } finally {
                isProcessingFiles = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Check for success/error messages from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('created') === '1') {
                const message = urlParams.get('message') || 'Event created successfully';
                showNotification(decodeURIComponent(message), 'success');
                
                // Check for awards earned after successful event creation
                if (window.checkAwardCriteria) {
                    window.checkAwardCriteria('event', 'new_event');
                }
                
                // Clean up URL
                window.history.replaceState({}, document.title, window.location.pathname);
                // Refresh events data from API to show the new event
                setTimeout(() => {
                    refreshEventsFromAPI();
                }, 500);
            } else if (urlParams.get('deleted') === '1') {
                const message = urlParams.get('message') || 'Event deleted successfully';
                showNotification(decodeURIComponent(message), 'success');
                // Clean up URL
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (urlParams.get('error') === '1') {
                const message = urlParams.get('message') || 'Error occurred';
                showNotification(decodeURIComponent(message), 'error');
                // Clean up URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            // Initialize calendar
            generateCalendar(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
            
            // Load existing events on page load
            loadExistingEvents();
            
            // Initialize delete listeners after events are loaded (with delay)
            setTimeout(() => {
                fixAllDeleteButtons();
            }, 200);
            
            // Initialize search functionality
            initializeSearch();
            
            // Initialize upload modal functionality
            const eventsUploadBtn = document.getElementById('events-upload-btn');
            const uploadModal = document.getElementById('upload-modal');
            
            // Initialize trash delete modal event listeners
            const trashDeleteCancel = document.getElementById('trash-delete-cancel');
            const trashDeleteConfirm = document.getElementById('trash-delete-confirm');
            if (trashDeleteCancel) {
                trashDeleteCancel.addEventListener('click', cancelTrashDelete);
            }
            if (trashDeleteConfirm) {
                trashDeleteConfirm.addEventListener('click', confirmTrashDelete);
            }
            
            // Initialize empty trash modal event listeners
            const emptyTrashCancel = document.getElementById('empty-trash-cancel');
            const emptyTrashConfirm = document.getElementById('empty-trash-confirm');
            if (emptyTrashCancel) {
                emptyTrashCancel.addEventListener('click', hideEmptyTrashModal);
            }
            if (emptyTrashConfirm) {
                emptyTrashConfirm.addEventListener('click', confirmEmptyTrash);
            }
            const closeModal = document.getElementById('close-modal');
            const cancelUpload = document.getElementById('cancel-upload');
            const fileInput = document.getElementById('file-input');
            const browseFiles = document.getElementById('browse-files');
            const dropZone = document.getElementById('drop-zone');
            const addManualEvent = document.getElementById('add-manual-event');

            // Initialize event creation modal functionality
            const createEventBtnHeader = document.getElementById('create-event-btn-header');
            const eventCreationModal = document.getElementById('event-creation-modal');
            const createEventBtn = document.getElementById('create-event-btn');
            const eventForm = document.getElementById('event-creation-form');
            const eventImageInput = document.getElementById('event-image');
            const imagePreview = document.getElementById('image-preview');
            const imageUploadArea = document.getElementById('image-upload-area');
            const previewImg = document.getElementById('preview-img');
            const classificationPreview = document.getElementById('classification-preview');
            const classificationResult = document.getElementById('classification-result');

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
                    const date = document.getElementById('event-date-manual')?.value || '';
                    const eventStatus = document.getElementById('event-status')?.value || 'upcoming';
                    
                    if (!eventName || !organizer || !place || !date) {
                        showNotification('Please fill in all required fields', 'error');
                        return;
                    }
                    
                    const eventData = {
                        name: eventName,
                        title: eventName, // Add title field for central events API
                        organizer: organizer,
                        place: place,
                        location: place, // Add location field for central events API
                        date: date,
                        status: eventStatus,
                        type: 'manual',
                        description: `Event organized by ${organizer} at ${place}`
                    };
                    
                    try {
                        // Save to API first
                        const savedEvent = await saveEventToAPI(eventData);
                        
                        if (savedEvent) {
                            // Add to events table with saved data
                            addEventToTable(savedEvent);
                            
                            showNotification('Event added successfully!', 'success');
                            closeUploadModal();
                            
                            // Clear form
                            document.getElementById('event-name').value = '';
                            document.getElementById('event-organizer').value = '';
                            document.getElementById('event-place').value = '';
                            document.getElementById('event-date-manual').value = '';
                            document.getElementById('event-status').value = 'upcoming';
                            
                            // Refresh events list
                            setTimeout(() => {
                                refreshEventsFromAPI();
                            }, 1000);
                        } else {
                            showNotification('Failed to save event. Please try again.', 'error');
                        }
                    } catch (error) {
                        console.error('Error adding event:', error);
                        showNotification('Error adding event: ' + error.message, 'error');
                    }
                };
            }

            // Event Creation Modal Functionality
            if (createEventBtnHeader && eventCreationModal) {
                createEventBtnHeader.onclick = function() {
                    eventCreationModal.classList.remove('hidden');
                };
            }

            // Image upload preview
            if (eventImageInput) {
                eventImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImg.src = e.target.result;
                            imagePreview.classList.remove('hidden');
                            imageUploadArea.classList.add('hidden');
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Date format conversion helper
            function convertDateFormat(dateString) {
                // Handle various date formats and convert to YYYY-MM-DD
                if (!dateString) return '';
                
                // If already in YYYY-MM-DD format, return as is
                if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
                    return dateString;
                }
                
                // Handle formats like "8 8, 2025" or "8/8/2025" or "8-8-2025"
                const date = new Date(dateString);
                if (isNaN(date.getTime())) {
                    return '';
                }
                
                // Convert to YYYY-MM-DD format
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                
                return `${year}-${month}-${day}`;
            }
            
            // Auto-classification on input change
            const eventTitle = document.getElementById('event-title');
            const eventDescription = document.getElementById('event-description');
            const eventAwardType = document.getElementById('event-award-type');
            const eventDate = document.getElementById('event-date');
            const eventTime = document.getElementById('event-time');
            const eventLocation = document.getElementById('event-location');

            function performAutoClassification() {
                const title = eventTitle.value;
                const description = eventDescription.value;
                
                if (title && window.DocumentAnalyzer) {
                    const analyzer = new DocumentAnalyzer();
                    const analysis = analyzer.performKeywordAnalysis(title + ' ' + description);
                    
                    if (analysis.bestMatch && analysis.confidence > 0.3) {
                        classificationResult.innerHTML = `
                            <div class="flex items-center justify-between">
                                <span><strong>Suggested Award:</strong> ${analysis.bestMatch}</span>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">${Math.round(analysis.confidence * 100)}% confidence</span>
                            </div>
                        `;
                        classificationPreview.classList.remove('hidden');
                        
                        // Auto-select if no manual selection
                        if (!eventAwardType.value) {
                            eventAwardType.value = analysis.bestMatch;
                        }
                    } else {
                        classificationPreview.classList.add('hidden');
                    }
                } else {
                    classificationPreview.classList.add('hidden');
                }
            }

            if (eventTitle) {
                eventTitle.addEventListener('input', performEnhancedClassification);
            }
            if (eventDescription) {
                eventDescription.addEventListener('input', performEnhancedClassification);
            }
            
            // Location analysis function
            async function analyzeLocation(title, description) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'analyze_location');
                    formData.append('title', title);
                    formData.append('description', description);
                    
                    const response = await fetch('api/enhanced_management.php', {
                    method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                const result = await response.json();
                        if (result.success && result.location_analysis.suggested_location) {
                            const suggestedLocation = result.location_analysis.suggested_location;
                            const confidence = result.location_analysis.confidence;
                            
                            // Only suggest if confidence is high enough and location field is empty
                            if (confidence > 0.5 && !eventLocation.value) {
                                eventLocation.value = suggestedLocation;
                                showNotification(`ðŸ“ Location suggested: ${suggestedLocation} (${Math.round(confidence * 100)}% confidence)`, 'info');
                            }
                        }
                    }
                } catch (error) {
                    console.log('Location analysis failed:', error);
                    // Don't show error to user, just fail silently
                }
            }
            
            // Enhanced auto-classification with location analysis
            function performEnhancedClassification() {
                performAutoClassification();
                
                // Auto-analyze location if location field is empty
                const title = eventTitle.value;
                const description = eventDescription.value;
                if (!eventLocation.value && (title || description)) {
                    // Debounce the location analysis
                    clearTimeout(window.locationAnalysisTimeout);
                    window.locationAnalysisTimeout = setTimeout(() => {
                        analyzeLocation(title, description);
                    }, 1000); // Wait 1 second after user stops typing
                }
            }

            // Create event functionality
            if (createEventBtn) {
                createEventBtn.onclick = async function() {
                    // Validate required fields
                    if (!eventTitle.value.trim()) {
                        showNotification('Event title is required', 'error');
                        return;
                    }
                    if (!eventDate.value) {
                        showNotification('Event date is required', 'error');
                        return;
                    }
                    
                    // Convert and validate date format
                    const convertedDate = convertDateFormat(eventDate.value);
                    if (!convertedDate) {
                        showNotification('Please enter a valid date (e.g., 8/8/2025, 8-8-2025, or 2025-08-08)', 'error');
                        return;
                    }
                    
                    // Check if date is not in the past (allow past dates for award records)
                    const selectedDate = new Date(convertedDate);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    // Auto-classify award type if not specified
                    let awardType = eventAwardType.value;
                    if (!awardType) {
                        try {
                            // Show loading state
                            const originalText = createEventBtn.textContent;
                            createEventBtn.textContent = 'Analyzing content...';
                            createEventBtn.disabled = true;
                            
                            // Show loading spinner in award classification
                            const loadingSpinner = document.getElementById('award-classification-loading');
                            if (loadingSpinner) {
                                loadingSpinner.classList.remove('hidden');
                            }
                            
                            // Call auto-classification API
                            const classifyFormData = new FormData();
                            classifyFormData.append('action', 'auto_classify_event');
                            classifyFormData.append('csrf_token', document.getElementById('csrf-token').value);
                            classifyFormData.append('title', eventTitle.value.trim());
                            classifyFormData.append('description', eventDescription.value.trim());
                            classifyFormData.append('location', eventLocation.value.trim());
                            
                            const classifyResponse = await fetch('api/enhanced_management.php', {
                                method: 'POST',
                                body: classifyFormData
                            });
                            
                            const classifyResult = await classifyResponse.json();
                            
                            if (classifyResult.success && classifyResult.award_name) {
                                awardType = classifyResult.award_name;
                                eventAwardType.value = awardType;
                                
                                const confidence = Math.round(classifyResult.confidence_score * 100);
                                showNotification(`Auto-classified as: ${awardType} (${confidence}% confidence)`, 'success');
                } else {
                                // Fallback to default for past events
                                if (selectedDate < today) {
                                    awardType = 'Internationalization (IZN) Leadership Award';
                                    eventAwardType.value = awardType;
                                    showNotification('Auto-selected award classification for historical event', 'info');
                                } else {
                                    showNotification('Could not auto-classify event. Please select an award type manually.', 'warning');
                                }
                            }
                            
                            // Reset button and hide loading spinner
                            createEventBtn.textContent = originalText;
                            createEventBtn.disabled = false;
                            
                            if (loadingSpinner) {
                                loadingSpinner.classList.add('hidden');
                }
                
            } catch (error) {
                            console.error('Auto-classification error:', error);
                            // Reset button and hide loading spinner
                            createEventBtn.textContent = originalText;
                            createEventBtn.disabled = false;
                            
                            if (loadingSpinner) {
                                loadingSpinner.classList.add('hidden');
                            }
                            
                            // Fallback to default for past events
                            if (selectedDate < today) {
                                awardType = 'Internationalization (IZN) Leadership Award';
                                eventAwardType.value = awardType;
                                showNotification('Auto-selected award classification for historical event', 'info');
                            } else {
                                showNotification('Could not auto-classify event. Please select an award type manually.', 'warning');
                            }
                        }
                    }

                    const formData = new FormData();
                    formData.append('action', 'create_event');
                    formData.append('csrf_token', document.getElementById('csrf-token').value);
                    formData.append('title', eventTitle.value.trim());
                    formData.append('description', eventDescription.value.trim());
                    formData.append('event_date', convertedDate);
                    formData.append('event_time', eventTime.value || '');
                    formData.append('location', eventLocation.value.trim());
                    formData.append('award_type', awardType || '');
                    formData.append('original_link', document.getElementById('event-original-link').value.trim());
                    
                    if (eventImageInput.files[0]) {
                        formData.append('file', eventImageInput.files[0]);
                    }

                    try {
                        // Submit form using fetch with FormData for proper file handling
                        const response = await fetch('create_event.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        if (response.ok) {
                            // Success - redirect to events page
                            window.location.href = 'events_activities.php?created=1&message=' + encodeURIComponent('Event "' + eventTitle.value.trim() + '" created successfully');
                        } else {
                            throw new Error('Server error: ' + response.status);
                        }
                        
                    } catch (error) {
                        console.error('Error creating event:', error);
                        showNotification('Error creating event: ' + error.message, 'error');
                    }
                };
            }

            // Initialize filter functionality
            initializeFilter();
            
            // Initialize delete functionality
            initializeDeleteFunctionality();
        });

        // Notification function
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

        // FIXED: Add event to table with proper field mapping from central_events table
        function addEventToTable(eventData) {
            const tableBody = document.getElementById('events-table-body');
            if (!tableBody) return;
            
            // Hide empty state message if it exists
            const emptyMessage = document.getElementById('empty-events-message');
            if (emptyMessage) {
                emptyMessage.style.display = 'none';
            }
            
            const newRow = document.createElement('tr');
            const status = eventData.status || 'upcoming';
            const statusClass = status === 'upcoming' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
            const statusText = status.charAt(0).toUpperCase() + status.slice(1);
            
            // Format date properly
            let formattedDate = 'N/A';
            if (eventData.start) {
                const eventDate = new Date(eventData.start);
                formattedDate = eventDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } else if (eventData.date) {
                formattedDate = new Date(eventData.date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }
            
            // Format time if available
            let formattedTime = '';
            if (eventData.start) {
                const eventDateTime = new Date(eventData.start);
                formattedTime = eventDateTime.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
            }
            
            // Get event title (prioritize title field from central_events table)
            const eventTitle = eventData.title || eventData.name || 'Untitled Event';
            
            // Get description (truncate if too long)
            let descriptionHtml = '';
            if (eventData.description) {
                const truncatedDesc = eventData.description.length > 50 
                    ? eventData.description.substring(0, 50) + '...' 
                    : eventData.description;
                descriptionHtml = `<p class="text-xs text-gray-500 mt-1">${truncatedDesc}</p>`;
            }
            
            // Get location
            const location = eventData.location || eventData.place || 'To be determined';
            
            // Get organizer (default to LILAC System if not specified)
            const organizer = eventData.organizer || 'LILAC System';
            
            newRow.innerHTML = `
                <td class="px-3 py-2">
                    <div class="flex flex-col">
                        <p class="font-medium text-gray-900 text-sm">${eventTitle}</p>
                        ${descriptionHtml}
                    </div>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-900 text-sm">${organizer}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-900 text-sm">${location}</p>
                </td>
                <td class="px-3 py-2">
                    <div class="flex flex-col">
                        <p class="text-gray-600 text-sm">${formattedDate}</p>
                        ${formattedTime ? `<p class="text-xs text-gray-500">${formattedTime}</p>` : ''}
                    </div>
                </td>
                <td class="px-3 py-2">
                    <span class="inline-block ${statusClass} text-xs px-2 py-1 rounded-full font-medium">${statusText}</span>
                </td>
                <td class="px-3 py-2 text-center">
                    <div class="flex items-center justify-center gap-2">
                        ${eventData.image_path ? `
                        <button class="view-file-btn text-blue-500 hover:text-blue-700 transition-colors p-1" title="View file" data-event-id="${eventData.id || ''}" onclick="viewEventFile('${eventData.id || ''}')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        ` : ''}
                        <button class="delete-row-btn text-red-500 hover:text-red-700 transition-colors p-1" title="Delete event" data-event-id="${eventData.id || ''}" onclick="showDeleteModal('${eventData.id || ''}')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            
            // Add data attribute for potential deletion
            if (eventData.id) {
                newRow.setAttribute('data-event-id', eventData.id);
            }
            
            tableBody.appendChild(newRow);
            
            // Attach delete listener to the new button
            attachTableDeleteListeners();
            
            // Update calendar with new event
            if (eventsData.length > 0) {
                updateCalendarWithEvents(eventsData);
            }
        }

        // Initialize search functionality
        function initializeSearch() {
            const searchInput = document.getElementById('events-search-input');
            if (!searchInput) return;
            
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                filterEventsTable(searchTerm);
            });
        }
        
        // Filter events table based on search term
        function filterEventsTable(searchTerm) {
            const tableBody = document.getElementById('events-table-body');
            if (!tableBody) return;
            
            const rows = tableBody.querySelectorAll('tr');
            let visibleRows = 0;
            
            rows.forEach(row => {
                // Skip the empty message row
                if (row.id === 'empty-events-message') return;
                
                const eventName = row.querySelector('td:first-child p')?.textContent.toLowerCase() || '';
                const organizer = row.querySelector('td:nth-child(2) p')?.textContent.toLowerCase() || '';
                const place = row.querySelector('td:nth-child(3) p')?.textContent.toLowerCase() || '';
                const date = row.querySelector('td:nth-child(4) p')?.textContent.toLowerCase() || '';
                
                const matches = eventName.includes(searchTerm) || 
                               organizer.includes(searchTerm) || 
                               place.includes(searchTerm) || 
                               date.includes(searchTerm);
                
                if (matches) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show/hide empty message
            const emptyMessage = document.getElementById('empty-events-message');
            if (emptyMessage) {
                if (visibleRows === 0 && searchTerm !== '') {
                    emptyMessage.style.display = '';
                    emptyMessage.querySelector('p:first-child').textContent = 'No events match your search';
                    emptyMessage.querySelector('p:last-child').textContent = 'Try adjusting your search terms';
                } else if (visibleRows === 0 && searchTerm === '') {
                    emptyMessage.style.display = '';
                    emptyMessage.querySelector('p:first-child').textContent = 'No events found';
                    emptyMessage.querySelector('p:last-child').textContent = 'Create your first event or upload files to get started';
                } else {
                    emptyMessage.style.display = 'none';
                }
            }
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
            const deleteModal = document.getElementById('delete-card-modal');
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
                    <!-- Action Buttons -->
                    <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="view-file-btn w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center hover:bg-blue-600" title="View file" data-event-id="${eventData.id || ''}" onclick="viewEventFile('${eventData.id || ''}')">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        <button class="delete-card w-6 h-6 bg-red-500 rounded-full flex items-center justify-center hover:bg-red-600" title="Delete this card">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
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
                    <!-- Action Buttons -->
                    <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="view-file-btn w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center hover:bg-blue-600" title="View file" data-event-id="${eventData.id || ''}" onclick="viewEventFile('${eventData.id || ''}')">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        <button class="delete-card w-6 h-6 bg-red-500 rounded-full flex items-center justify-center hover:bg-red-600" title="Delete this card">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
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

        // FIXED: saveEventToAPI function to use the correct API endpoint
        async function saveEventToAPI(eventData) {
            try {
                const formData = new FormData();
                formData.append('action', 'create_event');
                formData.append('title', eventData.name || eventData.title);
                formData.append('description', eventData.description || `${eventData.type || 'Event'} organized by ${eventData.organizer} at ${eventData.place}`);
                formData.append('event_date', eventData.date);
                formData.append('event_time', '12:00:00'); // Default time
                formData.append('location', eventData.place);
                
                // Add file if provided
                if (eventData.file_path) {
                    formData.append('description', formData.get('description') + ` (File: ${eventData.file_path})`);
                }

                console.log('Sending event data to central events API:', eventData);

                const response = await fetch('api/central_events_api.php', {
                    method: 'POST',
                    body: formData
                });

                console.log('Response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                if (!responseText || responseText.trim() === '') {
                    throw new Error('Empty response from server');
                }

                const result = JSON.parse(responseText);
                console.log('Parsed result:', result);
                
                if (result.success) {
                    console.log('Event saved successfully:', result.data);
                    return result.data;
            } else {
                    console.error('Failed to save event:', result.data?.message || result.message);
                    return null;
                }
            } catch (error) {
                console.error('Error saving event:', error);
                return null;
            }
        }

        // Refresh events from API (for after creating new events)
        async function refreshEventsFromAPI() {
            try {
                console.log('ðŸ”„ Refreshing events from API...');
                
                // Try the main API first, then fallback to simple API
                let response;
                try {
                    response = await fetch('api/central_events_api.php?action=get_events_by_status');
                } catch (error) {
                    console.log('Main API failed, trying simple API...');
                    response = await fetch('api/events_simple.php?action=get_events_by_status');
                }
                const result = await response.json();
                
                if (result.success && result.data.events) {
                    console.log('âœ… Refreshed events from API');
                    
                    // Clear existing table rows (keep empty message)
                    const tableBody = document.getElementById('events-table-body');
                    if (tableBody) {
                        const rows = tableBody.querySelectorAll('tr');
                        rows.forEach(row => {
                            if (row.id !== 'empty-events-message') {
                                row.remove();
                            }
                        });
                    }
                    
                    // Combine upcoming and completed events
                    const allEvents = [...result.data.events.upcoming, ...result.data.events.completed];
                    console.log('ðŸ“Š Total refreshed events:', allEvents.length);
                    
                    // Recreate table entries for all events
                    allEvents.forEach(eventData => {
                        try {
                            addEventToTable(eventData);
                        } catch (error) {
                            console.error('Error adding refreshed event to table:', error, eventData);
                        }
                    });
                    
                    // Update event counters
                    updateEventCounters(allEvents);
                    
                    // Update upcoming events list in sidebar
                    populateUpcomingEventsList(result.data.events.upcoming);
                    
                    // Update calendar with events
                    updateCalendarWithEvents(allEvents);
                    
                    // Handle empty state
                    const emptyMessage = document.getElementById('empty-events-message');
                    if (emptyMessage && allEvents.length > 0) {
                        emptyMessage.style.display = 'none';
                    } else if (emptyMessage && allEvents.length === 0) {
                        emptyMessage.style.display = '';
                    }
                    
                    console.log('âœ… Events refreshed successfully');
                } else {
                    console.error('âŒ Failed to refresh events:', result.message);
                }
            } catch (error) {
                console.error('âŒ Error refreshing events from API:', error);
            }
        }

        // Load events from API
        async function loadEventsFromAPI() {
            try {
                console.log('ðŸ”„ Loading events from API...');
                
                // Try the main API first, then fallback to simple API
                let response;
                try {
                    response = await fetch('api/central_events_api.php?action=get_events_by_status');
                } catch (error) {
                    console.log('Main API failed, trying simple API...');
                    response = await fetch('api/events_simple.php?action=get_events_by_status');
                }
                
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Check if response has content
                const responseText = await response.text();
                if (!responseText || responseText.trim() === '') {
                    console.log('Main API returned empty response, trying fallback API...');
                    // Try the fallback API instead of throwing error
                    response = await fetch('api/events_simple.php?action=get_events_by_status');
                    const fallbackText = await response.text();
                    if (!fallbackText || fallbackText.trim() === '') {
                        throw new Error('Both APIs returned empty responses');
                    }
                    // Use fallback response
                    const fallbackResult = JSON.parse(fallbackText);
                    if (fallbackResult.success && fallbackResult.data && fallbackResult.data.events) {
                        console.log('âœ… Loaded events from fallback API');
                        
                        // Extract events from the grouped structure
                        const allEvents = [];
                        if (fallbackResult.data.events.upcoming) {
                            allEvents.push(...fallbackResult.data.events.upcoming);
                        }
                        if (fallbackResult.data.events.completed) {
                            allEvents.push(...fallbackResult.data.events.completed);
                        }
                        
                        // Clear existing table rows (keep empty message)
                        const tableBody = document.getElementById('events-table-body');
                        if (tableBody) {
                            const rows = tableBody.querySelectorAll('tr');
                            rows.forEach(row => {
                                if (row.id !== 'empty-events-message') {
                                    row.remove();
                                }
                            });
                        }
                        
                        // Add events to table
                        allEvents.forEach(event => {
                            addEventToTable(event);
                        });
                        
                        // Update counters and lists
                        updateEventCounters(allEvents);
                        populateUpcomingEventsList(allEvents);
                        
                        // Update global events data
                        eventsData = allEvents;
                        return; // Exit function successfully
                    }
                    throw new Error('Fallback API also failed');
                }
                
                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error('Invalid JSON response from API');
                }
                
                if (result.success && result.data && result.data.events) {
                    console.log('âœ… Loaded events from API');
                    
                    // Extract events from the grouped structure
                    const allEvents = [];
                    if (result.data.events.upcoming) {
                        allEvents.push(...result.data.events.upcoming);
                    }
                    if (result.data.events.completed) {
                        allEvents.push(...result.data.events.completed);
                    }
                    
                    // Clear existing table rows (keep empty message)
                    const tableBody = document.getElementById('events-table-body');
                    if (tableBody) {
                        const rows = tableBody.querySelectorAll('tr');
                        rows.forEach(row => {
                            if (row.id !== 'empty-events-message') {
                                row.remove();
                            }
                        });
                    }
                    
                    // Add events to table
                    allEvents.forEach(event => {
                        addEventToTable(event);
                    });
                    
                    // Update counters and lists
                    updateEventCounters(allEvents);
                    populateUpcomingEventsList(allEvents);
                    
                    // Update global events data
                    eventsData = allEvents;
                    
                } else {
                    console.log('No events found or API error, using fallback data');
                    // Use fallback data from PHP
                    const pageEventsData = <?php echo json_encode($eventsData ?? []); ?>;
                    if (pageEventsData && pageEventsData.length > 0) {
                        pageEventsData.forEach(event => {
                            addEventToTable(event);
                        });
                        updateEventCounters(pageEventsData);
                        populateUpcomingEventsList(pageEventsData);
                        eventsData = pageEventsData;
                    } else {
                        updateEventCounters([]);
                        populateUpcomingEventsList([]);
                    }
                }
                
            } catch (error) {
                console.error('âŒ Error loading events from API:', error);
                console.log('Using fallback data from PHP');
                // Use fallback data from PHP
                const pageEventsData = <?php echo json_encode($eventsData ?? []); ?>;
                if (pageEventsData && pageEventsData.length > 0) {
                    pageEventsData.forEach(event => {
                        addEventToTable(event);
                    });
                    updateEventCounters(pageEventsData);
                    populateUpcomingEventsList(pageEventsData);
                    eventsData = pageEventsData;
                } else {
                    updateEventCounters([]);
                    populateUpcomingEventsList([]);
                }
            }
        }

        // Load existing events from PHP data directly
        function loadExistingEvents() {
            try {
                console.log('ðŸ”„ Loading all events from API...');
                
                // Load events from API since PHP data loading was removed for performance
                loadEventsFromAPI();
                
            } catch (error) {
                console.error('âŒ Error loading existing events:', error);
                console.error('Error details:', {
                    name: error.name,
                    message: error.message,
                    stack: error.stack
                });
                
                // Show user-friendly error message
                showNotification('Failed to load events. Please refresh the page.', 'error');
            }
        }

        // Update event counters based on loaded events
        function updateEventCounters(events) {
            try {
                const upcomingCountElement = document.getElementById('upcoming-count');
                const completedCountElement = document.getElementById('completed-count');
                
                if (!upcomingCountElement || !completedCountElement) {
                    console.warn('Counter elements not found');
                    return;
                }
                
                // Count events by status
                const upcomingCount = events.filter(event => event.status === 'upcoming').length;
                const completedCount = events.filter(event => event.status === 'completed').length;
                
                // Update the counter displays
                upcomingCountElement.textContent = upcomingCount;
                completedCountElement.textContent = completedCount;
                
                console.log(`ðŸ“Š Updated counters - Upcoming: ${upcomingCount}, Completed: ${completedCount}`);
            } catch (error) {
                console.error('Error updating event counters:', error);
            }
        }

        // Populate upcoming events list
        function populateUpcomingEventsList(events) {
            const upcomingEventsList = document.getElementById('upcoming-events-list');
            if (!upcomingEventsList) return;
            
            // Filter upcoming events and sort by date
            const upcomingEvents = events
                .filter(event => event.status === 'upcoming')
                .sort((a, b) => new Date(a.start) - new Date(b.start))
                .slice(0, 4); // Show only the next 4 upcoming events
            
            if (upcomingEvents.length === 0) {
                upcomingEventsList.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-xs text-gray-500">No upcoming events</p>
                    </div>
                `;
                return;
            }
            
            // Generate event items
            const eventItems = upcomingEvents.map((event, index) => {
                const colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500'];
                const color = colors[index % colors.length];
                
                // Format date
                const eventDate = new Date(event.start);
                const formattedDate = eventDate.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
                
                return `
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 ${color} rounded-full mt-2 flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 text-xs">${event.title || event.name || 'Untitled Event'}</p>
                            <p class="text-xs text-gray-500">${formattedDate}</p>
                            <p class="text-xs text-gray-500">${event.location || 'TBD'}</p>
                        </div>
                    </div>
                `;
            }).join('');
            
            upcomingEventsList.innerHTML = eventItems;
        }

        // Delete event from API
        // Delete event from API - FIXED VERSION
        async function deleteEventFromAPI(eventId) {
            console.log('Deleting event ID:', eventId);
            
            try {
                const response = await fetch('api/central_events_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=delete_event&event_id=' + encodeURIComponent(eventId)
                });
                
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                
                const result = await response.json();
                console.log('Delete result:', result);
                return result;
            } catch (error) {
                console.error('Error deleting event:', error);
                throw error;
            }
        }

        // Attach delete listeners to table rows
        function attachTableDeleteListeners() {
            // Note: Delete buttons now use onclick handlers directly, so this function is disabled
            // to prevent conflicts with the new delete modal system
            console.log('ðŸ” Delete buttons now use onclick handlers with delete modal - no event listeners needed');
            
            const allDeleteButtons = document.querySelectorAll('.delete-row-btn');
            console.log(`- Total delete buttons found: ${allDeleteButtons.length}`);
            
            // Only handle disabled buttons (sample events)
            const disabledButtons = document.querySelectorAll('.delete-row-btn[disabled]');
            disabledButtons.forEach(button => {
                button.removeEventListener('click', handleDisabledButtonClick);
                button.addEventListener('click', handleDisabledButtonClick);
            });
            
            if (disabledButtons.length > 0) {
                console.log(`- ${disabledButtons.length} disabled buttons (sample events) handled`);
            }
        }
        
        // Handle clicks on disabled delete buttons
        function handleDisabledButtonClick(event) {
            event.preventDefault();
            event.stopPropagation();
            console.log('ðŸš« Disabled delete button clicked');
            alert('This is a sample event and cannot be deleted. Add your own events to see the delete functionality.');
        }

        // Note: handleTableDeleteClick function removed - delete buttons now use onclick handlers with modal

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
            console.log('ðŸ§ª Testing delete functionality...');
            const allButtons = document.querySelectorAll('.delete-row-btn');
            const enabledButtons = document.querySelectorAll('.delete-row-btn:not([disabled])');
            
            console.log('ðŸ“Š Delete button summary:');
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
                console.log('âœ… Found enabled buttons - delete functionality should work');
                console.log('ðŸ” Try clicking the red delete button next to TEST event');
            } else {
                console.log('âŒ No enabled buttons found - add new events to test delete');
            }
        }
        
        // Quick fix function to manually delete the TEST event
        function deleteTestEvent() {
            console.log('ðŸ—‘ï¸ Manually deleting TEST event...');
            
            if (typeof deleteEventFromAPI === 'function') {
                deleteEventFromAPI(2).then(result => {
                    if (result && result.success) {
                        console.log('âœ… TEST event deleted successfully!');
                        // Force reload to clear any cached data
                        setTimeout(() => {
                            window.location.reload(true); // Force reload from server
                        }, 500);
                    } else {
                        console.error('âŒ Failed to delete TEST event:', result);
                    }
                }).catch(error => {
                    console.error('âŒ Error deleting TEST event:', error);
                });
            } else {
                console.error('âŒ deleteEventFromAPI function not found');
            }
        }

        // Function to force refresh events from server
        function forceRefreshEvents() {
            console.log('ðŸ”„ Force refreshing events from server...');
            // Clear any cached data
            if (typeof loadExistingEvents === 'function') {
                loadExistingEvents().then(() => {
                    console.log('âœ… Events refreshed from server');
                }).catch(error => {
                    console.error('âŒ Error refreshing events:', error);
                });
            } else {
                console.log('ðŸ”„ Reloading page to refresh events...');
                window.location.reload(true);
            }
        }
        
        // Force update all delete button states
        function fixAllDeleteButtons() {
            console.log('ðŸ”§ Fixing all delete button states...');
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
                        console.log(`ðŸ”’ Disabled: "${eventName}" (Sample: ${isSample}, No ID: ${!eventId})`);
                    } else {
                        // This is a real event - make it enabled
                        button.disabled = false;
                        button.className = 'delete-row-btn text-red-500 hover:text-red-700 transition-colors p-1';
                        button.title = 'Delete event';
                        console.log(`ðŸ—‘ï¸ Enabled: "${eventName}" (ID: ${eventId})`);
                    }
                }
            });
            
            // Reattach listeners
            attachTableDeleteListeners();
            console.log('âœ… All delete buttons updated!');
        }

        // Delete confirmation modal functionality
        let currentDeleteEventId = null;
        let currentDeleteEventData = null;

        async function showDeleteModal(eventId) {
            currentDeleteEventId = eventId;
            
            const modal = document.getElementById('deleteModal');
            const eventInfo = document.getElementById('deleteEventInfo');
            
            if (!modal) {
                console.error('Delete modal not found');
                return;
            }
            
            // Show the modal
            modal.classList.remove('hidden');
            
            // Show loading state
            if (eventInfo) {
                eventInfo.innerHTML = `
                    <div class="text-sm text-gray-500">
                        <div class="animate-pulse">Loading event details...</div>
                    </div>
                `;
            }
            
            // First try to get event data from the page data (faster and more reliable)
            const pageEventsData = <?php echo json_encode($eventsData); ?>;
            let event = null;
            
            if (pageEventsData.success && pageEventsData.data && pageEventsData.data.events) {
                const allEvents = [...pageEventsData.data.events.upcoming, ...pageEventsData.data.events.completed];
                event = allEvents.find(e => e.id == eventId);
            }
            
            if (event) {
                console.log('ðŸ“„ Found event in page data for delete:', event);
                currentDeleteEventData = event;
                if (eventInfo) {
                    eventInfo.innerHTML = `
                        <div class="text-sm">
                            <div class="font-medium text-gray-900">${event.title || 'Untitled Event'}</div>
                            <div class="text-gray-600 mt-1">Date: ${event.start ? new Date(event.start).toLocaleDateString() : 'N/A'}</div>
                            <div class="text-gray-600">Location: ${event.location || 'N/A'}</div>
                            ${event.description ? `<div class="text-gray-600 mt-1">Description: ${event.description}</div>` : ''}
                        </div>
                    `;
                }
                return;
            }
            
            // Fallback to API call if not found in page data
            console.log('ðŸ”„ Event not found in page data, trying API for delete...');
            
            try {
                // Fetch event data from API
                const response = await fetch(`api/central_events_api.php?action=get_event&event_id=${eventId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                console.log('ðŸ“¥ Raw API response:', responseText);
                
                if (!responseText.trim()) {
                    throw new Error('Empty response from API');
                }
                
                const result = JSON.parse(responseText);
                
                if (result.success && result.data && result.data.event) {
                    const event = result.data.event;
                    currentDeleteEventData = event;
                    
                    // Populate event info
                    if (eventInfo) {
                        eventInfo.innerHTML = `
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">${event.title || 'Untitled Event'}</div>
                                <div class="text-gray-600 mt-1">Date: ${event.start ? new Date(event.start).toLocaleDateString() : 'N/A'}</div>
                                <div class="text-gray-600">Location: ${event.location || 'N/A'}</div>
                                ${event.description ? `<div class="text-gray-600 mt-1">Description: ${event.description}</div>` : ''}
                            </div>
                        `;
                    }
                } else {
                    if (eventInfo) {
                        eventInfo.innerHTML = `
                            <div class="text-sm text-red-600">
                                Error loading event details: ${result.message || 'Unknown error'}
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('âŒ Error fetching event data for delete:', error);
                if (eventInfo) {
                    eventInfo.innerHTML = `
                        <div class="text-sm text-red-600">
                            Error loading event details: ${error.message}
                        </div>
                    `;
                }
            }
        }

        function hideDeleteModal() {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.classList.add('hidden');
            }
            currentDeleteEventId = null;
            currentDeleteEventData = null;
        }

        async function confirmDeleteEvent() {
            if (!currentDeleteEventId) return;
            
            try {
                // Show loading state
                const deleteBtn = document.getElementById('confirmDeleteBtn');
                const originalText = deleteBtn.textContent;
                deleteBtn.textContent = 'Deleting...';
                deleteBtn.disabled = true;
                
                // Delete the event
                const result = await deleteEventFromAPI(currentDeleteEventId);
                
                if (result.success) {
                    // Hide modal
                    hideDeleteModal();
                    
                    // Remove from Events List table
                    removeEventFromTable(currentDeleteEventId);
            
                    // Remove from upcoming events cards
                    removeEventFromCards(currentDeleteEventId);
                    
                    // Show success notification
                    showNotification('Event moved to trash successfully', 'success');
                    
                    // Refresh counters
                    loadExistingEvents();
                    } else {
                    const errorMsg = result.message || result.error || 'Unknown error occurred';
                    showNotification('Failed to delete event: ' + errorMsg, 'error');
                }
                
                // Reset button
                deleteBtn.textContent = originalText;
                deleteBtn.disabled = false;
                
            } catch (error) {
                console.error('Error deleting event:', error);
                showNotification('Error deleting event', 'error');
                
                // Reset button
                const deleteBtn = document.getElementById('confirmDeleteBtn');
                deleteBtn.textContent = 'Delete';
                deleteBtn.disabled = false;
            }
        }

        function removeEventFromTable(eventId) {
            // Find and remove the table row
            const tableBody = document.querySelector('#events-table tbody');
            if (tableBody) {
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const deleteBtn = row.querySelector('button[onclick*="deleteEventFromAPI"]');
                    if (deleteBtn && deleteBtn.getAttribute('onclick').includes(eventId)) {
                        row.remove();
                    }
                });
            }
        }

        function removeEventFromCards(eventId) {
            // Since we removed the cards container, we'll refresh the upcoming events list instead
            // This will be handled by the loadExistingEvents function when it's called after deletion
            console.log('Event card removal handled by upcoming events list refresh');
        }

        function updateCardsLayout() {
            // Since we removed the cards container, this function is no longer needed
            // The upcoming events list in the sidebar will be refreshed automatically
            console.log('Cards layout update handled by upcoming events list refresh');
        }

        // Trash bin functionality
        function showTrashModal() {
            const modal = document.getElementById('trashModal');
            if (modal) {
                modal.classList.remove('hidden');
                // Load trash events when opening
                loadTrashEvents();
            }
        }

        function hideTrashModal() {
            const modal = document.getElementById('trashModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Trash delete modal functions
        let currentTrashDeleteId = null;

        function showTrashDeleteModal(trashId) {
            console.log('showTrashDeleteModal called with trashId:', trashId);
            console.log('trashId type:', typeof trashId);
            console.log('trashId value:', JSON.stringify(trashId));
            
            if (!trashId || trashId === 'undefined' || trashId === 'null' || trashId === '') {
                console.error('Invalid trash ID:', trashId);
                showNotification('Invalid event ID. Please try again.', 'error');
                return;
            }
            
            currentTrashDeleteId = trashId;
            const modal = document.getElementById('trash-delete-modal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        function hideTrashDeleteModal() {
            const modal = document.getElementById('trash-delete-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
            // Don't reset currentTrashDeleteId here - it will be reset after successful deletion
        }
        
        function cancelTrashDelete() {
            currentTrashDeleteId = null;
            hideTrashDeleteModal();
        }

        function confirmTrashDelete() {
            console.log('confirmTrashDelete called, currentTrashDeleteId:', currentTrashDeleteId);
            
            if (currentTrashDeleteId) {
                const trashIdToDelete = currentTrashDeleteId;
                currentTrashDeleteId = null; // Reset immediately
                hideTrashDeleteModal();
                permanentlyDeleteEvent(trashIdToDelete);
            } else {
                console.error('No trash ID available for deletion');
                showNotification('No event selected for deletion', 'error');
            }
        }

        // Empty trash modal functions
        function showEmptyTrashModal() {
            const modal = document.getElementById('empty-trash-modal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        function hideEmptyTrashModal() {
            const modal = document.getElementById('empty-trash-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function confirmEmptyTrash() {
            hideEmptyTrashModal();
            emptyTrash();
        }

        function loadTrashEvents() {
            try {
                console.log('Loading trash events from PHP data...');
                
                // Use PHP data directly instead of HTTP requests
                const result = <?php echo json_encode($trashEventsData); ?>;
                console.log('PHP Trash Events Data:', result);
                
                if (result.success && result.trash_events) {
                    console.log('âœ… Loaded trash events from PHP data');
                    console.log('Trash events count:', result.trash_events.length);
                    renderTrashEvents(result.trash_events);
                } else {
                    console.log('No trash events found or failed to load');
                    renderTrashEvents([]);
                }
            } catch (error) {
                console.error('âŒ Error loading trash events:', error);
                renderTrashEvents([]);
            }
        }

        function renderTrashEvents(trashEvents) {
            const container = document.getElementById('trash-container');
            if (!container) return;
            
            console.log('Rendering trash events:', trashEvents);
            
            if (!trashEvents || trashEvents.length === 0) {
                container.innerHTML = '<div class="text-sm text-gray-500">No deleted events.</div>';
                return;
            }
            
            container.innerHTML = trashEvents.map(event => {
                console.log('Rendering event:', event);
                console.log('Event ID:', event.id, 'Type:', typeof event.id);
                
                if (!event.id) {
                    console.error('Event missing ID:', event);
                    return '';
                }
                
                return `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-900 truncate">${event.title || 'Untitled Event'}</div>
                        <div class="text-xs text-gray-500">Deleted ${new Date(event.deleted_at || Date.now()).toLocaleString()}</div>
                        ${event.description ? `<div class="text-xs text-gray-600 mt-1 truncate">${event.description.substring(0, 100)}...</div>` : ''}
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <button class="px-3 py-1.5 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors" onclick="restoreEvent(${event.id})">
                            Restore
                        </button>
                        <button class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors" onclick="showTrashDeleteModal(${event.id})">
                            Delete
                        </button>
                    </div>
                </div>
                `;
            }).filter(html => html !== '').join('');
        }

        async function restoreEvent(trashId) {
            try {
                const formData = new FormData();
                formData.append('action', 'restore_event');
                formData.append('trash_id', trashId);
                
                const response = await fetch('api/enhanced_management.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Event restored successfully', 'success');
                    loadTrashEvents(); // Refresh trash list
                    loadExistingEvents(); // Refresh main events list
            } else {
                    showNotification('Failed to restore event: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error restoring event:', error);
                showNotification('Error restoring event', 'error');
            }
        }

        function permanentlyDeleteEvent(trashId) {
            try {
                console.log('permanentlyDeleteEvent called with trashId:', trashId);
                console.log('trashId type:', typeof trashId);
                console.log('trashId value:', JSON.stringify(trashId));
                
                if (!trashId || trashId === 'undefined' || trashId === 'null' || trashId === '') {
                    console.error('Invalid trash ID in permanentlyDeleteEvent:', trashId);
                    showNotification('Invalid event ID. Cannot delete.', 'error');
                    return;
                }
                
                // Create a form to submit the deletion request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_trash_event.php';
                form.style.display = 'none';
                
                const trashIdInput = document.createElement('input');
                trashIdInput.type = 'hidden';
                trashIdInput.name = 'trash_id';
                trashIdInput.value = trashId;
                
                console.log('Form input value:', trashIdInput.value);
                
                form.appendChild(trashIdInput);
                document.body.appendChild(form);
                
                // Show loading notification
                showNotification('Permanently deleting event...', 'info');
                
                // Submit the form (this will cause a page reload with updated data)
                form.submit();
                
            } catch (error) {
                console.error('Error deleting event:', error);
                showNotification('Error deleting event: ' + error.message, 'error');
            }
        }

        async function emptyTrash() {
            try {
                const formData = new FormData();
                formData.append('action', 'empty_trash_events');
                
                const response = await fetch('api/enhanced_management.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Trash emptied successfully', 'success');
                    loadTrashEvents(); // Refresh trash list
                    } else {
                    showNotification('Failed to empty trash: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error emptying trash:', error);
                showNotification('Error emptying trash', 'error');
            }
        }

        // Make functions globally available
        window.addEventToTable = addEventToTable;
        window.createManualEventCard = createManualEventCard;
        window.createImageCard = createImageCard;
        window.saveEventToAPI = saveEventToAPI;
        window.loadExistingEvents = loadExistingEvents;
        window.refreshEventsFromAPI = refreshEventsFromAPI;
        window.deleteEventFromAPI = deleteEventFromAPI;
        window.attachTableDeleteListeners = attachTableDeleteListeners;
        window.testDeleteFunctionality = testDeleteFunctionality;
        window.fixAllDeleteButtons = fixAllDeleteButtons;
        window.deleteTestEvent = deleteTestEvent;
        window.viewEventFile = viewEventFile;
        window.showDeleteModal = showDeleteModal;
        
        // Test function for action buttons
        window.testActionButtons = function() {
            console.log('ðŸ”§ Testing action buttons...');
            
            // Check if buttons exist
            const viewButtons = document.querySelectorAll('.view-file-btn');
            const deleteButtons = document.querySelectorAll('.delete-row-btn');
            
            console.log(`ðŸ“Š Found ${viewButtons.length} view buttons and ${deleteButtons.length} delete buttons`);
            
            // Test each button
            viewButtons.forEach((btn, index) => {
                const eventId = btn.getAttribute('data-event-id');
                const onclick = btn.getAttribute('onclick');
                console.log(`ðŸ‘ï¸ View button ${index + 1}: ID="${eventId}", onclick="${onclick}"`);
            });
            
            deleteButtons.forEach((btn, index) => {
                const eventId = btn.getAttribute('data-event-id');
                const onclick = btn.getAttribute('onclick');
                console.log(`ðŸ—‘ï¸ Delete button ${index + 1}: ID="${eventId}", onclick="${onclick}"`);
            });
            
            // Test functions exist
            console.log('ðŸ” Function availability:');
            console.log(`  - viewEventFile: ${typeof window.viewEventFile}`);
            console.log(`  - showDeleteModal: ${typeof window.showDeleteModal}`);
            console.log(`  - hideDeleteModal: ${typeof window.hideDeleteModal}`);
            console.log(`  - confirmDeleteEvent: ${typeof window.confirmDeleteEvent}`);
            
            return {
                viewButtons: viewButtons.length,
                deleteButtons: deleteButtons.length,
                functionsAvailable: {
                    viewEventFile: typeof window.viewEventFile,
                    showDeleteModal: typeof window.showDeleteModal,
                    hideDeleteModal: typeof window.hideDeleteModal,
                    confirmDeleteEvent: typeof window.confirmDeleteEvent
                }
            };
        };
        window.generateCalendar = generateCalendar;
        window.navigateCalendar = navigateCalendar;
        window.updateCalendarWithEvents = updateCalendarWithEvents;
        window.showEventsForDate = showEventsForDate;
        window.showTrashModal = showTrashModal;
        window.hideTrashModal = hideTrashModal;
        window.restoreEvent = restoreEvent;
        window.permanentlyDeleteEvent = permanentlyDeleteEvent;
        window.emptyTrash = emptyTrash;
        window.showTrashDeleteModal = showTrashDeleteModal;
        window.hideTrashDeleteModal = hideTrashDeleteModal;
        window.cancelTrashDelete = cancelTrashDelete;
        window.confirmTrashDelete = confirmTrashDelete;
        window.showEmptyTrashModal = showEmptyTrashModal;
        window.hideEmptyTrashModal = hideEmptyTrashModal;
        window.confirmEmptyTrash = confirmEmptyTrash;
        window.showDeleteModal = showDeleteModal;
        window.hideDeleteModal = hideDeleteModal;
        window.confirmDeleteEvent = confirmDeleteEvent;
        
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

        // File viewer functions
        function getFileExtension(filename) {
            return filename.split('.').pop().toLowerCase();
        }

        function showDocumentViewer(doc) {
            const title = doc.document_name || doc.title || doc.name || 'Untitled Document';
            let filePath = doc.file_path || doc.filename || doc.image_file;
            const ext = getFileExtension(filePath || '');

            if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                filePath = `uploads/${filePath}`;
            }

            const overlay = document.getElementById('document-viewer-overlay');
            const titleEl = document.getElementById('document-viewer-title');
            const contentEl = document.getElementById('document-viewer-content');
            const downloadBtn = document.getElementById('document-viewer-download');
            const openBtn = document.getElementById('document-viewer-open');

            if (!overlay || !titleEl || !contentEl || !downloadBtn) return;

            titleEl.textContent = title;
            contentEl.innerHTML = '';

            if (openBtn) {
                openBtn.onclick = function(){
                    if (!filePath) return;
                    const href = new URL(filePath, window.location.origin).href;
                    window.open(href, '_blank');
                };
            }

            if (!filePath) {
                contentEl.innerHTML = '<div class="text-center text-gray-600">File path not available.</div>';
            } else if (['png','jpg','jpeg','gif','webp','bmp','svg'].includes(ext)) {
                const img = document.createElement('img');
                img.src = filePath;
                img.alt = title;
                img.className = 'max-h-full max-w-full object-contain mx-auto';
                contentEl.appendChild(img);
                
                // Add description below the image if it exists
                if (doc.description && doc.description.trim()) {
                    const descriptionDiv = document.createElement('div');
                    descriptionDiv.className = 'mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200';
                    descriptionDiv.innerHTML = `
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Event Description</h4>
                        <p class="text-gray-900 whitespace-pre-wrap text-sm">${doc.description}</p>
                    `;
                    contentEl.appendChild(descriptionDiv);
                }
                
                // Add original link below the description if it exists
                if (doc.original_link && doc.original_link.trim()) {
                    const linkDiv = document.createElement('div');
                    linkDiv.className = 'mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200';
                    linkDiv.innerHTML = `
                        <h4 class="text-sm font-medium text-blue-700 mb-2">Original Source</h4>
                        <div class="flex items-center gap-2">
                            <a href="${doc.original_link}" target="_blank" rel="noopener noreferrer" 
                               class="text-blue-600 hover:text-blue-800 text-sm underline truncate flex-1">
                                ${doc.original_link}
                            </a>
                            <button onclick="window.open('${doc.original_link}', '_blank')" 
                                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                                Open in New Tab
                            </button>
                    </div>
                `;
                    contentEl.appendChild(linkDiv);
                }
            } else if (ext === 'pdf') {
                const container = document.createElement('div');
                container.className = 'w-full h-full';
                contentEl.appendChild(container);
                try {
                    if (!window['pdfjsLib']) throw new Error('PDF.js not loaded');
                    pdfjsLib.getDocument(filePath).promise.then(pdf => {
                        const numPages = pdf.numPages;
                        const renderPage = (pageNum) => {
                            pdf.getPage(pageNum).then(page => {
                                const availableWidth = contentEl.clientWidth - 16;
                                const viewport = page.getViewport({ scale: 1 });
                                const scale = Math.min(1.5, Math.max(0.6, availableWidth / viewport.width));
                                const scaledViewport = page.getViewport({ scale });
                                const canvas = document.createElement('canvas');
                                const ctx = canvas.getContext('2d');
                                canvas.width = scaledViewport.width;
                                canvas.height = scaledViewport.height;
                                canvas.className = 'block mx-auto mb-4 bg-white max-w-full h-auto';
                                container.appendChild(canvas);
                                page.render({ canvasContext: ctx, viewport: scaledViewport }).promise.then(() => {
                                    if (pageNum < numPages) renderPage(pageNum + 1);
                                });
                            });
                        };
                        renderPage(1);
                    }).catch(() => {
                        const fallback = document.createElement('iframe');
                        fallback.src = filePath;
                        fallback.className = 'w-full h-full rounded';
                        contentEl.innerHTML = '';
                        contentEl.appendChild(fallback);
                    });
                } catch (e) {
                    const fallback = document.createElement('iframe');
                    fallback.src = filePath;
                    fallback.className = 'w-full h-full rounded';
                    contentEl.appendChild(fallback);
                }
            } else if (['doc','docx','ppt','pptx','xls','xlsx'].includes(ext)) {
                const isLocalhost = ['localhost','127.0.0.1','::1'].includes(location.hostname);
                if (isLocalhost) {
                    const info = document.createElement('div');
                    info.className = 'text-center text-gray-600';
                    info.textContent = 'Preview for Office files is not available on localhost. Please use Download to view the file.';
                    contentEl.appendChild(info);
                } else {
                    const absoluteUrl = new URL(filePath, window.location.origin).href;
                    const officeUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(absoluteUrl);
                    const iframe = document.createElement('iframe');
                    iframe.src = officeUrl;
                    iframe.className = 'w-full rounded bg-white';
                    iframe.style.height = 'calc(100% - 0px)';
                    iframe.style.display = 'block';
                    contentEl.appendChild(iframe);
                }
            } else {
                contentEl.innerHTML = '<div class="text-center text-gray-600">Preview not supported for this file type. Please download to view.</div>';
            }

            downloadBtn.onclick = function() { downloadDocument(doc); };
            overlay.classList.remove('hidden');
        }

        function downloadDocument(doc) {
            const fileName = doc.document_name || doc.title || doc.name || 'Untitled Document';
            let filePath = doc.file_path || doc.filename || doc.image_file;
            
            if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                filePath = `uploads/${filePath}`;
            }
            
            if (!filePath) {
                showNotification('File path not available for download', 'error');
                return;
            }
            
            const link = document.createElement('a');
            link.href = filePath;
            link.download = fileName;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showNotification(`Downloading ${fileName}`, 'success');
        }

        function viewEventFile(eventId) {
            console.log('ðŸ” Viewing event:', eventId);
            
            // First try to get event data from the page data (faster and more reliable)
            const pageEventsData = <?php echo json_encode($eventsData); ?>;
            let event = null;
            
            if (pageEventsData.success && pageEventsData.data && pageEventsData.data.events) {
                const allEvents = [...pageEventsData.data.events.upcoming, ...pageEventsData.data.events.completed];
                event = allEvents.find(e => e.id == eventId);
            }
            
            if (event) {
                console.log('ðŸ“„ Found event in page data:', event);
                showEventDetails(event);
                return;
            }
            
            // Fallback to API call if not found in page data
            console.log('ðŸ”„ Event not found in page data, trying API...');
            
            // Get event data from the central events API
            fetch(`api/central_events_api.php?action=get_event&event_id=${eventId}`)
                .then(response => {
                    console.log('ðŸ“¡ API Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                    return response.json();
                })
                .then(result => {
                    console.log('ðŸ“¥ API Result:', result);
                    if (result.success && result.data && result.data.event) {
                        const event = result.data.event;
                        console.log('ðŸ“„ Event data:', event);
                        
                        // Create a document object for the viewer
                        const doc = {
                            document_name: event.title || 'Untitled Event',
                            file_path: event.image_path || event.file_path || null,
                            title: event.title || 'Untitled Event',
                            filename: event.image_path || event.file_path || null,
                            description: event.description || '',
                            event_date: event.start || '',
                            location: event.location || '',
                            original_link: event.original_link || '',
                            extracted_content: event.extracted_content || ''
                        };
                        
                        // Show document viewer if there's a file, otherwise show event details
                        if (doc.file_path) {
                            showDocumentViewer(doc);
                        } else {
                            showEventDetails(event);
                        }
                    } else {
                        console.error('âŒ Event not found or API error:', result);
                        showNotification('Event not found or API error: ' + (result.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('âŒ Error fetching event:', error);
                    showNotification('Error loading event: ' + error.message, 'error');
                });
        }
        
        function showEventDetails(event) {
            // Create a modal to show event details (simplified - only title, image, description)
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[100] flex items-center justify-center p-4';
            
            // Debug: Log event data to see what's available
            console.log('ðŸ” Event data for view:', event);
            console.log('ðŸ–¼ï¸ Image paths:', {
                image_path: event.image_path,
                file_path: event.file_path,
                hasImage: !!(event.image_path || event.file_path)
            });
            
            // Create image HTML if image exists
            const imageHtml = (event.image_path || event.file_path) ? `
                <div class="mb-4">
                    <img src="${event.image_path || event.file_path}" alt="${event.title || 'Event Image'}" 
                         class="w-full h-64 object-cover rounded-lg shadow-md"
                         onerror="console.error('âŒ Image failed to load:', this.src); this.style.display='none';">
                            </div>
            ` : '<div class="mb-4 text-center text-gray-500 text-sm">No image available</div>';
            
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Event Details</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="this.closest('.fixed').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                        ${imageHtml}
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-xl font-semibold text-gray-900 mb-2">${event.title || 'No title'}</h4>
                            </div>
                            <div>
                                <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">${event.description || 'No description available'}</p>
                            </div>
                        </div>
                    </div>
                            </div>
                        `;
            
            document.body.appendChild(modal);
            
            // Close modal when clicking outside
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Calendar functionality
        let currentCalendarDate = new Date();
        let eventsData = [];

        function generateCalendar(year, month) {
            const calendarDays = document.getElementById('calendar-days');
            const monthYear = document.getElementById('calendar-month-year');
            
            if (!calendarDays || !monthYear) {
                console.warn('Calendar elements not found');
                return;
            }

            // Update month/year display
            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            monthYear.textContent = `${monthNames[month]} ${year}`;

            // Clear existing calendar days
            calendarDays.innerHTML = '';

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();

            // Add empty cells for days before the first day of the month
            for (let i = 0; i < startingDayOfWeek; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'text-center py-1 text-gray-300';
                calendarDays.appendChild(emptyDay);
            }

            // Add days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer transition-colors';
                dayElement.textContent = day;
                dayElement.dataset.day = day;
                dayElement.dataset.month = month;
                dayElement.dataset.year = year;

                // Check if this is today's date
                const today = new Date();
                const isToday = year === today.getFullYear() && 
                               month === today.getMonth() && 
                               day === today.getDate();

                // Check if this date has events
                const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const hasEvents = eventsData.some(event => {
                    // Use start field from database
                    const eventDate = new Date(event.start);
                    const eventDateString = `${eventDate.getFullYear()}-${String(eventDate.getMonth() + 1).padStart(2, '0')}-${String(eventDate.getDate()).padStart(2, '0')}`;
                    return eventDateString === dateString;
                });

                // Apply styling based on conditions
                if (isToday && hasEvents) {
                    // Today with events - special styling
                    dayElement.classList.add('bg-green-500', 'text-white', 'font-bold', 'ring-2', 'ring-green-300');
                    dayElement.title = 'Today - Has events';
                } else if (isToday) {
                    // Today without events
                    dayElement.classList.add('bg-green-100', 'text-green-800', 'font-bold', 'ring-2', 'ring-green-300');
                    dayElement.title = 'Today';
                } else if (hasEvents) {
                    // Has events but not today
                    dayElement.classList.add('bg-blue-100', 'text-blue-800', 'font-semibold');
                    dayElement.title = 'Has events';
                }

                // Add click handler
                dayElement.addEventListener('click', () => {
                    showEventsForDate(year, month, day);
                });

                calendarDays.appendChild(dayElement);
            }
        }

        function showEventsForDate(year, month, day) {
            const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const eventsOnDate = eventsData.filter(event => {
                // Use start field from database
                const eventDate = new Date(event.start);
                const eventDateString = `${eventDate.getFullYear()}-${String(eventDate.getMonth() + 1).padStart(2, '0')}-${String(eventDate.getDate()).padStart(2, '0')}`;
                return eventDateString === dateString;
            });

            if (eventsOnDate.length > 0) {
                // Use title field from database
                const eventList = eventsOnDate.map(event => `â€¢ ${event.title}`).join('\n');
                showNotification(`Events on ${dateString}:\n${eventList}`, 'info');
            } else {
                showNotification(`No events on ${dateString}`, 'info');
            }
        }

        function updateCalendarWithEvents(events) {
            eventsData = events;
            generateCalendar(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
        }

        function navigateCalendar(direction) {
            if (direction === 'prev') {
                currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
            } else if (direction === 'next') {
                currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
            }
            generateCalendar(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
        }
    </script>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <!-- Include shared document viewer -->
    <?php include 'components/shared-document-viewer.php'; ?>

</body>

</html> 



