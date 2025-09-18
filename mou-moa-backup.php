<?php
require_once 'classes/DateTimeUtility.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOUs & MOAs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script src="js/error-handler.js"></script>
    <script src="js/security-utils.js"></script>
    <script src="js/awards-check.js"></script>
    <script src="js/lazy-loader.js"></script>
    <script src="js/mou-moa-config.js"></script>
    <script src="js/mou-moa-management.js?v=<?php echo time() . rand(10000, 99999); ?>"></script>
    <script src="js/modal-handlers.js"></script>
    
    <script>
        // Initialize MOU/MOA system with enhanced functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('MOU page DOM loaded');
            console.log('MouMoaManager available:', typeof MouMoaManager !== 'undefined');
            
            // Initialize MOU/MOA Manager
            if (typeof MouMoaManager !== 'undefined') {
                window.mouMoaManager = new MouMoaManager();
                console.log('MOU Manager initialized:', window.mouMoaManager);
            } else {
                console.log('MouMoaManager not available');
            }
            
            // Ensure LILAC notifications appear below navbar
            setTimeout(function() {
                if (window.lilacNotifications && window.lilacNotifications.container) {
                    window.lilacNotifications.container.style.top = '80px';
                    window.lilacNotifications.container.style.zIndex = '99999';
                }
            }, 500);
            
            // Update current date display
            updateCurrentDate();
            setInterval(updateCurrentDate, 60000);
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
        
        // Upload modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const uploadBtn = document.getElementById('upload-mou-btn');
            const uploadModal = document.getElementById('upload-modal');
            const closeModal = document.getElementById('close-upload-modal');
            const cancelBtn = document.getElementById('cancel-upload');
            const uploadForm = document.getElementById('upload-mou-form');
            
            // Open modal
            if (uploadBtn) {
                uploadBtn.addEventListener('click', function() {
                    uploadModal.classList.remove('hidden');
                });
            }
            
            // Close modal
            function closeUploadModal() {
                uploadModal.classList.add('hidden');
                uploadForm.reset();
            }
            
            if (closeModal) {
                closeModal.addEventListener('click', closeUploadModal);
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeUploadModal);
            }
            
            // Close modal when clicking outside
            uploadModal.addEventListener('click', function(e) {
                if (e.target === uploadModal) {
                    closeUploadModal();
                }
            });
            
            // Handle form submission
            if (uploadForm) {
                uploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(uploadForm);
                    formData.append('action', 'add');
                    
                    // Show loading state
                    const submitBtn = uploadForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.textContent = 'Uploading...';
                    submitBtn.disabled = true;
                    
                    fetch('api/mous.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            if (window.lilacNotifications) {
                                window.lilacNotifications.show('MOU/MOA uploaded successfully!', 'success');
                            }
                            
                            // Close modal and refresh data
                            closeUploadModal();
                            
                            // Refresh the documents table
                            setTimeout(() => {
                                if (window.mouMoaManager && window.mouMoaManager.loadDocuments) {
                                    window.mouMoaManager.loadDocuments();
                                } else {
                                    // Fallback: reload the page if manager not available
                                    window.location.reload();
                                }
                            }, 100);
                        } else {
                            // Show error message
                            if (window.lilacNotifications) {
                                window.lilacNotifications.show(data.message || 'Upload failed', 'error');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Upload error:', error);
                        if (window.lilacNotifications) {
                            window.lilacNotifications.show('Upload failed. Please try again.', 'error');
                        }
                    })
                    .finally(() => {
                        // Reset button state
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
        });
    </script>
</head>

<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between relative transition-all duration-300 ease-in-out">
        <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <div class="flex-1 flex items-center justify-start ml-16">
            <h1 id="page-title" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-blue-600 transition-colors">
                MOUs & MOAs
            </h1>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div id="main-content" class="p-4 pt-4 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out text-sm">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total MOUs</p>
                        <p id="total-mous" class="text-2xl font-bold text-gray-900">0</p>
                    </div>
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active MOUs</p>
                        <p id="active-mous" class="text-2xl font-bold text-green-600">0</p>
                    </div>
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Expiring Soon</p>
                        <p id="expiring-mous" class="text-2xl font-bold text-yellow-600">0</p>
                    </div>
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4 border border-gray-200">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="search-input" placeholder="Search MOUs/MOAs..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex gap-2">
                    <select id="status-filter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Expired">Expired</option>
                        <option value="Pending">Pending</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <select id="partner-filter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Partners</option>
                        <option value="University">University</option>
                        <option value="Private">Private</option>
                    </select>
                    <button id="sync-mous-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Sync from Documents
                    </button>
                </div>
            </div>
        </div>

        <!-- Documents Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">MOU/MOA Documents</h2>
                    <div class="flex items-center gap-2">
                        <button id="upload-mou-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Upload MOU/MOA
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <!-- Checkbox column header -->
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Institution</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Upload Date</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="documents-table-body" class="bg-white divide-y divide-gray-200">
                        <!-- Documents will be loaded here by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Loading Indicator -->
            <div id="loading-indicator" class="hidden p-8 text-center">
                <div class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading documents...
                </div>
            </div>
            
            <!-- Pagination -->
            <div id="pagination" class="p-4 border-t border-gray-200">
                <!-- Pagination controls will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <!-- Shared Document Viewer Modal -->
    <?php include 'components/shared-document-viewer.php'; ?>

    <!-- Delete Confirmation Modal -->
    <div id="delete-mou-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[80] flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Delete MOU/MOA</h3>
                <button onclick="closeDeleteModal()" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                    Close
                </button>
            </div>
            <div class="mb-4">
                <p class="text-gray-700">Are you sure you want to delete this MOU/MOA?</p>
                <p class="text-sm text-gray-500 mt-2">This action cannot be undone.</p>
                
                <!-- File attachment info and view button -->
                <div id="delete-modal-file-info" class="mt-3 p-3 bg-gray-50 rounded-lg hidden">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">Attached File:</p>
                            <p id="delete-modal-filename" class="text-sm text-gray-600 truncate"></p>
                        </div>
                        <button id="delete-modal-view-btn" onclick="viewDocumentFromDeleteModal()" 
                                class="ml-3 bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-colors">
                            View
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="confirmDeleteMou()" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                    Delete
                </button>
                <button onclick="closeDeleteModal()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Upload MOU/MOA Modal -->
    <div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4 py-8">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-y-auto">
                <div class="p-3">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-gray-900">Upload MOU/MOA</h3>
                        <button id="close-upload-modal" class="px-2 py-1 text-xs text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                            Close
                        </button>
                    </div>
                    
                    <form id="upload-mou-form" enctype="multipart/form-data">
                        <div class="space-y-2">
                            <div>
                                <label for="institution" class="block text-xs font-medium text-gray-700 mb-1">Institution</label>
                                <input type="text" id="institution" name="institution" 
                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent" 
                                       placeholder="Enter institution name" required>
                            </div>
                            
                            <div>
                                <label for="location" class="block text-xs font-medium text-gray-700 mb-1">Location of Institution</label>
                                <input type="text" id="location" name="location" 
                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent" 
                                       placeholder="Enter institution location" required>
                            </div>
                            
                            <div>
                                <label for="contact-details" class="block text-xs font-medium text-gray-700 mb-1">Contact Details</label>
                                <input type="text" id="contact-details" name="contact_details" 
                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter email or phone number" required>
                            </div>
                            
                            <div>
                                <label for="term" class="block text-xs font-medium text-gray-700 mb-1">Term</label>
                                <input type="text" id="term" name="term" 
                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent" 
                                       placeholder="Enter agreement term (e.g., 3 years, 5 years)" required>
                            </div>
                            
                            <div>
                                <label for="sign-date" class="block text-xs font-medium text-gray-700 mb-1">Date of Sign</label>
                                <input type="date" id="sign-date" name="sign_date" 
                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent" required>
                            </div>
                            
                            <div>
                                <label for="start-date" class="block text-xs font-medium text-gray-700 mb-1">Start Date (Optional)</label>
                                <input type="date" id="start-date" name="start_date" 
                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="end-date" class="block text-xs font-medium text-gray-700 mb-1">End Date (Optional)</label>
                                <input type="date" id="end-date" name="end_date" 
                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="mou-file" class="block text-xs font-medium text-gray-700 mb-1">Select File (Optional)</label>
                                <input type="file" id="mou-file" name="mou-file" accept=".pdf,.doc,.docx" 
                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-2 mt-3">
                            <button type="button" id="cancel-upload" class="px-3 py-1 text-sm text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-3 py-1 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                Upload MOU/MOA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
