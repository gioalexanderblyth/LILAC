<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script>
        if (window['pdfjsLib']) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>
    <title>LILAC Documents</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <style>
        /* Use Inter font from modern-design-system.css */
        /* Font family is now consistent across all pages */
        
        /* Main content styles with sidebar */
        .main-content {
            margin-left: 0;
            width: 100%;
        }
        
        /* Empty state styling */
        .empty-state-container {
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .empty-state-content {
            text-align: center;
            padding: 2rem;
        }
        
        /* Table layout styling */
        .table-fixed {
            table-layout: fixed;
        }
        
        .table-fixed th,
        .table-fixed td {
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Table height styling */
        .table-fixed th {
            height: 60px;
        }
        
        .table-fixed td {
            height: 70px;
        }
        
        .table-fixed tbody {
            min-height: 2000px;
        }
        

        
        /* Remove focus ring/box on Documents link */
        .documents-link:focus,
        .documents-link:focus-visible,
        .documents-link:active {
            outline: none;
            box-shadow: none;
            border: none;
        }
        
        /* Smooth transitions */
        .nav-item {
            transition: all 0.3s ease-in-out;
        }
        
        .nav-text {
            transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out;
        }
    </style>
    <script>


        // Global variables for pagination and filtering
        let currentFilters = {
            page: 1,
            limit: 10,
            search: '',
            category: '',
            sort_by: 'upload_date',
            sort_order: 'DESC',
            view: 'all' // all, my, favorites, sharing, deleted
        };
        
        // Track file names to handle duplicates
        let existingFileNames = new Set();
        
        // View mode state
        let currentViewMode = 'list'; // 'list' or 'grid'
        
        // Local state for recent uploads (no API dependency)
        let recentUploads = [];
        
        // Bulk selection state
        let selectedDocuments = new Set();
        let selectAllChecked = false;
        
        // Document editor state
        let currentDocumentContent = '';
        let isEditorOpen = false;
        
        let debounceTimer;
        let availableCategories = [];
        let currentDocuments = [];

        // Initialize documents functionality
        document.addEventListener('DOMContentLoaded', function() {
            initializeEventListeners();

            // Load initial data
            loadCategories();
            loadDocuments();
            loadStats();

            updateCurrentDate();
            

            
            // Update date every minute
            setInterval(updateCurrentDate, 60000);
            
            // Ensure LILAC notifications appear below navbar
            setTimeout(function() {
                if (window.lilacNotifications && window.lilacNotifications.container) {
                    window.lilacNotifications.container.style.top = '80px';
                    window.lilacNotifications.container.style.zIndex = '99999';
                }
            }, 500);
            
            // Disable all notifications permanently
            window.suppressNotifications();
            
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
            // Tab navigation
            const tabButtons = document.querySelectorAll('[data-tab]');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tab = this.getAttribute('data-tab');
                    switchTab(tab);
                });
            });

            // View toggle buttons
            const viewButtons = document.querySelectorAll('[data-view]');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const view = this.getAttribute('data-view');
                    switchView(view);
                });
            });



            // Search functionality with debounce
            const searchInput = document.getElementById('search-documents');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    const searchValue = this.value.trim();
                    
                    // Show/hide clear button
                    const clearBtn = document.getElementById('clear-search');
                    if (searchValue) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }
                    
                    // Debounce search to avoid too many API calls
                    debounceTimer = setTimeout(() => {
                        currentFilters.search = searchValue;
                        currentFilters.page = 1;
                        filterAndDisplayDocuments(searchValue);
                    }, 300);
                });
            }

            // Clear search button
            const clearSearchBtn = document.getElementById('clear-search');
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    this.classList.add('hidden');
                    currentFilters.search = '';
                    currentFilters.page = 1;
                    filterAndDisplayDocuments('');
                });
            }

            // Filter dropdowns
            const filterDropdowns = document.querySelectorAll('[data-filter]');
            filterDropdowns.forEach(dropdown => {
                dropdown.addEventListener('change', function() {
                    const filterType = this.getAttribute('data-filter');
                    const value = this.value;
                    
                    switch(filterType) {
                        case 'group':
                            currentFilters.groupBy = value;
                            break;
                        case 'sort':
                            const [sortBy, sortOrder] = value.split('-');
                            currentFilters.sort_by = sortBy;
                            currentFilters.sort_order = sortOrder;
                            break;
                        case 'view':
                            currentFilters.viewType = value;
                            break;
                    }
                    
                    currentFilters.page = 1;
                    loadDocuments();
                });
            });

            // Initialize additional functionality
            initializeDropdownFilters();
            initializeDragAndDrop();
            initializeRecentUploads();
            initializeKeyboardNavigation();
        }

        function switchTab(tab) {
            // Update active tab
            document.querySelectorAll('[data-tab]').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('text-gray-600', 'hover:text-gray-900');
            });
            
            document.querySelector(`[data-tab="${tab}"]`).classList.add('bg-blue-600', 'text-white');
            document.querySelector(`[data-tab="${tab}"]`).classList.remove('text-gray-600', 'hover:text-gray-900');
            
            // Update current view
            currentFilters.view = tab;
            loadDocuments();
        }

        function switchView(view) {
            console.log('Switching to view:', view);
            
            // Update active view button
            document.querySelectorAll('[data-view]').forEach(btn => {
                btn.classList.remove('bg-gray-200', 'text-gray-700');
                btn.classList.add('text-gray-400', 'hover:text-gray-600');
            });
            
            document.querySelector(`[data-view="${view}"]`).classList.add('bg-gray-200', 'text-gray-700');
            document.querySelector(`[data-view="${view}"]`).classList.remove('text-gray-400', 'hover:text-gray-600');
            
            // Update view mode based on the selected view
            currentViewMode = view;
            console.log('Current view mode set to:', currentViewMode);
            
            // Update view type
            currentFilters.viewType = view;
            
            // Preserve selection state when switching views
            const currentSelection = new Set(selectedDocuments);
            const currentSelectAllState = selectAllChecked;
            
            // Instead of reloading documents, just switch the view immediately
            displayDocumentsByTime(currentDocuments);
            
            // Restore selection state
            selectedDocuments = currentSelection;
            selectAllChecked = currentSelectAllState;
            updateBulkDeleteUI();
            updateSelectAllCheckbox();
        }

        function filterAndDisplayDocuments(searchTerm) {
            // If no search term, show all documents
            if (!searchTerm || searchTerm.trim() === '') {
                if (window.allDocuments) {
                    currentDocuments = window.allDocuments;
                    displayDocumentsByTime(window.allDocuments);
                    updateDocumentsCount({ total_documents: window.allDocuments.length, current_page: 1, limit: currentFilters.limit });
                }
                return;
            }

            // Filter documents by filename (case-insensitive)
            const filteredDocuments = window.allDocuments.filter(doc => {
                const filename = doc.document_name || doc.title || doc.filename || '';
                return filename.toLowerCase().includes(searchTerm.toLowerCase());
            });

            // Update current documents and display
            currentDocuments = filteredDocuments;
            displayDocumentsByTime(filteredDocuments);
            updateDocumentsCount({ total_documents: filteredDocuments.length, current_page: 1, limit: currentFilters.limit });
        }

        function loadCategories() {
            fetch('api/documents.php?action=get_categories')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        availableCategories = data.categories;
                    }
                })
                .catch(error => console.error('Error loading categories:', error));
        }

        function loadDocuments() {
            showLoadingState();
            
            // Build query parameters
            const params = new URLSearchParams({
                action: 'get_all',
                page: currentFilters.page,
                limit: currentFilters.limit,
                sort_by: currentFilters.sort_by,
                sort_order: currentFilters.sort_order
            });
            
            if (currentFilters.search) {
                params.append('search', currentFilters.search);
            }
            
            if (currentFilters.category) {
                params.append('category', currentFilters.category);
            }
            
            fetch('api/documents.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    hideLoadingState();
                    if (data.success) {
                        currentDocuments = data.documents;
                        // Store all documents for client-side filtering
                        window.allDocuments = data.documents;
                        
                        // Update existing file names set
                        existingFileNames.clear();
                        data.documents.forEach(doc => {
                            const fileName = doc.document_name || doc.title || 'Untitled Document';
                            existingFileNames.add(fileName.toLowerCase());
                        });
                        
                        displayDocumentsByTime(data.documents);
                        updateDocumentsCount(data.pagination);
                        
                        // Reset selection state when documents are loaded
                        selectedDocuments.clear();
                        selectAllChecked = false;
                        updateSelectAllCheckbox();
                        
                        // Keep recent uploads separate from main documents
                        // displayRecentUploads(); // This will be called separately
                    } else {
                        showErrorMessage('Failed to load documents: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    hideLoadingState();
                    console.error('Error loading documents:', error);
                    showErrorMessage('Error loading documents. Please try again.');
                });
        }

        function showLoadingState() {
            const loadingElement = document.getElementById('loading-state');
            if (loadingElement) {
                loadingElement.classList.remove('hidden');
            }
        }

        function hideLoadingState() {
            const loadingElement = document.getElementById('loading-state');
            if (loadingElement) {
                loadingElement.classList.add('hidden');
            }
        }

        function loadStats() {
            fetch('api/documents.php?action=get_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-documents').textContent = data.stats.total;
                        document.getElementById('recent-documents').textContent = data.stats.recent;
                        document.getElementById('document-types').textContent = data.stats.categories;
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        }

        function displayDocumentsByTime(documents) {
            const listContainer = document.getElementById('documents-table-body');
            const gridContainer = document.getElementById('documents-grid-body');
            const listView = document.getElementById('list-view');
            const gridView = document.getElementById('grid-view');
            
            // Show/hide appropriate view
            if (currentViewMode === 'list') {
                listView.classList.remove('hidden');
                gridView.classList.add('hidden');
            } else {
                listView.classList.add('hidden');
                gridView.classList.remove('hidden');
            }
            
            if (documents.length === 0) {
                if (currentViewMode === 'list') {
                    listContainer.innerHTML = `
                        <tr>
                            <td colspan="7" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-20 h-20 text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <h3 class="text-xl font-medium text-gray-900 mb-3">No documents found</h3>
                                    <p class="text-gray-500 mb-6">${currentFilters.search ? 'Try adjusting your search criteria' : 'Upload your first document to get started'}</p>
                                    ${!currentFilters.search ? `
                                        <button onclick="showUploadModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                            Upload First Document
                                        </button>
                                    ` : `
                                        <button onclick="clearSearch()" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                                            Clear Search
                                        </button>
                                    `}
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    gridContainer.innerHTML = `
                        <div class="col-span-full px-6 py-24 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-20 h-20 text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="text-xl font-medium text-gray-900 mb-3">No documents found</h3>
                                <p class="text-gray-500 mb-6">${currentFilters.search ? 'Try adjusting your search criteria' : 'Upload your first document to get started'}</p>
                                ${!currentFilters.search ? `
                                    <button onclick="showUploadModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                        Upload First Document
                                    </button>
                                ` : `
                                    <button onclick="clearSearch()" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                                        Clear Search
                                    </button>
                                `}
                            </div>
                        </div>
                    `;
                }
                return;
            }

            // Display documents based on current view mode
            if (currentViewMode === 'list') {
                const html = documents.map((doc) => createDocumentTableRow(doc)).join('');
                listContainer.innerHTML = html;
                
                // Update bulk selection UI
                updateBulkDeleteUI();
                updateSelectAllCheckbox();
            } else {
                const html = documents.map((doc) => createDocumentGridCard(doc)).join('');
                gridContainer.innerHTML = html;
            }
        }

        function createDocumentTableRow(doc, isSelected = false) {
            const fileExtension = getFileExtension(doc.filename || '');
            const fileIcon = getFileIcon(fileExtension);
            const fileColor = getFileColor(fileExtension);
            const addedDate = new Date(doc.upload_date || doc.date_added);
            const formattedDate = addedDate.toLocaleDateString('en-US', { 
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            }).replace(',', ' ·');

            // Generate random file size and shared members for demo
            const fileSize = Math.floor(Math.random() * 10000) + 100;
            const sharedMembers = Math.floor(Math.random() * 5) + 1;
            
            // Check if this document is selected
            const isDocSelected = selectedDocuments.has(doc.id);

            return `
                <tr class="${isDocSelected ? 'bg-blue-50' : 'hover:bg-gray-50'}" 
                    data-document-id="${doc.id}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center justify-center">
                            <input type="checkbox" 
                                   onclick="event.stopPropagation(); toggleDocumentSelection(${doc.id})" 
                                   ${isDocSelected ? 'checked' : ''}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap cursor-pointer" onclick="viewDocument(${doc.id})">
                        <div class="flex items-center">
                            <div class="w-8 h-8 ${fileColor} rounded-lg flex items-center justify-center mr-3 flex-shrink-0 shadow-sm ring-1 ring-black/5">
                                ${fileIcon.replace('w-4 h-4', 'w-5 h-5')}
                            </div>
                            <div class="text-sm font-medium text-gray-900">
                                ${doc.document_name || doc.title || 'Untitled Document'}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer w-[12%] text-center" onclick="viewDocument(${doc.id})">
                        ${fileSize.toLocaleString()} KB
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer w-[12%] text-center" onclick="viewDocument(${doc.id})">
                        ${fileExtension.toUpperCase()}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer w-[24%] text-center" onclick="viewDocument(${doc.id})">
                        ${formattedDate}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium w-[12%] text-center">
                        <button onclick="event.stopPropagation(); deleteDocumentWithConfirmation(${doc.id})" 
                                class="text-gray-400 hover:text-red-600 transition-colors inline-flex items-center justify-center" 
                                aria-label="Delete document">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
            `;
        }

        function createDocumentCard(doc) {
            const fileExtension = getFileExtension(doc.filename || '');
            const fileIcon = getFileIcon(fileExtension);
            const fileColor = getFileColor(fileExtension);
            const addedDate = new Date(doc.upload_date || doc.date_added);
            const formattedDate = addedDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });

            // Generate random colored circles for tags (in real app, these would be actual tags)
            const tagColors = ['bg-orange-400', 'bg-red-400', 'bg-yellow-400', 'bg-blue-400', 'bg-green-400', 'bg-purple-400', 'bg-pink-400'];
            const tags = tagColors.slice(0, 3).map(color => `<div class="w-2 h-2 ${color} rounded-full"></div>`).join('');

            return `
                <div class="bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow cursor-pointer group" onclick="viewDocument(${doc.id})">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-8 h-8 ${fileColor} rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm ring-1 ring-black/5">
                                ${fileIcon.replace('w-4 h-4', 'w-5 h-5')}
                            </div>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="event.stopPropagation(); showDocumentMenu(${doc.id})" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h4 class="font-medium text-gray-900 text-sm leading-tight mb-2" title="${doc.document_name || doc.title || 'Untitled Document'}">
                                ${(doc.document_name || doc.title || 'Untitled Document').length > 30 ? 
                                    (doc.document_name || doc.title || 'Untitled Document').substring(0, 30) + '...' : 
                                    (doc.document_name || doc.title || 'Untitled Document')}
                            </h4>
                            <div class="flex items-center gap-1">
                                ${tags}
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            ${formattedDate}
                        </div>
                    </div>
                </div>
            `;
        }

        function createDocumentGridCard(doc, isSelected = false) {
            const fileExtension = getFileExtension(doc.filename || '');
            const fileIcon = getFileIcon(fileExtension);
            const fileColor = getFileColor(fileExtension);
            const addedDate = new Date(doc.upload_date || doc.date_added);
            const formattedDate = addedDate.toLocaleDateString('en-US', { 
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            }).replace(',', ' ·');

            // Generate random file size and shared members for demo
            const fileSize = Math.floor(Math.random() * 10000) + 100;
            const sharedMembers = Math.floor(Math.random() * 5) + 1;
            
            // Check if this document is selected
            const isDocSelected = selectedDocuments.has(doc.id);

            return `
                <div class="bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-all duration-200 group ${isDocSelected ? 'ring-2 ring-blue-500 bg-blue-50' : ''}" 
                     data-document-id="${doc.id}">
                    <div class="p-4">
                        <!-- Checkbox and File Icon -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" 
                                       onclick="event.stopPropagation(); toggleDocumentSelection(${doc.id})" 
                                       ${isDocSelected ? 'checked' : ''}
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <div class="w-12 h-12 ${fileColor} rounded-lg flex items-center justify-center shadow-sm">
                                    ${fileIcon.replace('w-4 h-4', 'w-6 h-6')}
                                </div>
                            </div>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="event.stopPropagation(); deleteDocumentWithConfirmation(${doc.id})" 
                                        class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded-full hover:bg-red-50" 
                                        aria-label="Delete document">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- File Name -->
                        <div class="mb-3 cursor-pointer" onclick="viewDocument(${doc.id})">
                            <h4 class="font-semibold text-gray-900 text-sm leading-tight mb-1" title="${doc.document_name || doc.title || 'Untitled Document'}">
                                ${(doc.document_name || doc.title || 'Untitled Document').length > 25 ? 
                                    (doc.document_name || doc.title || 'Untitled Document').substring(0, 25) + '...' : 
                                    (doc.document_name || doc.title || 'Untitled Document')}
                            </h4>
                            <p class="text-xs text-gray-500">${fileExtension.toUpperCase()} File</p>
                        </div>
                        
                        <!-- Metadata -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Size</span>
                                <span class="font-medium">${fileSize.toLocaleString()} KB</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Shared</span>
                                <span class="font-medium">${sharedMembers} ${sharedMembers === 1 ? 'Member' : 'Members'}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Date</span>
                                <span class="font-medium">${formattedDate}</span>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mt-4 pt-3 border-t border-gray-100 opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="flex gap-2">
                                <button onclick="event.stopPropagation(); viewDocument(${doc.id})" 
                                        class="flex-1 bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-medium hover:bg-blue-100 transition-colors">
                                    View
                                </button>
                                <button onclick="event.stopPropagation(); downloadDocument(${doc.id})" 
                                        class="flex-1 bg-gray-50 text-gray-600 px-2 py-1 rounded text-xs font-medium hover:bg-gray-100 transition-colors">
                                    Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function updateDocumentsCount(pagination) {
            const countElement = document.getElementById('documents-count');
            if (countElement && pagination) {
                const start = (pagination.current_page - 1) * pagination.limit + 1;
                const end = Math.min(pagination.current_page * pagination.limit, pagination.total_documents);
                countElement.textContent = `Showing ${start}-${end} of ${pagination.total_documents} documents`;
            }
        }

        // Bulk selection functions
        function toggleSelectAll() {
            selectAllChecked = !selectAllChecked;
            
            if (selectAllChecked) {
                // Select all visible documents
                currentDocuments.forEach(doc => {
                    selectedDocuments.add(doc.id);
                });
            } else {
                // Unselect all documents
                selectedDocuments.clear();
            }
            
            // Update the display to reflect the new selection state
            displayDocumentsByTime(currentDocuments);
            updateBulkDeleteUI();
            updateSelectAllCheckbox();
        }

        function toggleDocumentSelection(docId) {
            if (selectedDocuments.has(docId)) {
                selectedDocuments.delete(docId);
            } else {
                selectedDocuments.add(docId);
            }
            
            // Update select all state based on current selection
            selectAllChecked = selectedDocuments.size === currentDocuments.length;
            
            // Update the display to reflect the new selection state
            displayDocumentsByTime(currentDocuments);
            updateBulkDeleteUI();
            updateSelectAllCheckbox();
        }

        function updateBulkDeleteUI() {
            const bulkActionsBar = document.getElementById('bulk-actions-bar');
            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
            const selectedCount = document.getElementById('selected-count');
            const selectedPlural = document.getElementById('selected-plural');
            const gridSelectedCount = document.getElementById('grid-selected-count');
            
            if (bulkActionsBar && bulkDeleteBtn && selectedCount && selectedPlural) {
                if (selectedDocuments.size > 0) {
                    bulkActionsBar.classList.remove('hidden');
                    selectedCount.textContent = selectedDocuments.size;
                    selectedPlural.textContent = selectedDocuments.size === 1 ? '' : 's';
                } else {
                    bulkActionsBar.classList.add('hidden');
                }
            }
            
            // Update grid view selected count
            if (gridSelectedCount) {
                gridSelectedCount.textContent = selectedDocuments.size;
            }
        }

        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const gridSelectAllCheckbox = document.getElementById('grid-select-all-checkbox');
            
            // Update list view checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = selectAllChecked;
                selectAllCheckbox.indeterminate = selectedDocuments.size > 0 && selectedDocuments.size < currentDocuments.length;
                
                // Ensure the checkbox state is properly reflected
                if (selectedDocuments.size === 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (selectedDocuments.size === currentDocuments.length) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                }
            }
            
            // Update grid view checkbox
            if (gridSelectAllCheckbox) {
                gridSelectAllCheckbox.checked = selectAllChecked;
                gridSelectAllCheckbox.indeterminate = selectedDocuments.size > 0 && selectedDocuments.size < currentDocuments.length;
                
                // Ensure the checkbox state is properly reflected
                if (selectedDocuments.size === 0) {
                    gridSelectAllCheckbox.checked = false;
                    gridSelectAllCheckbox.indeterminate = false;
                } else if (selectedDocuments.size === currentDocuments.length) {
                    gridSelectAllCheckbox.checked = true;
                    gridSelectAllCheckbox.indeterminate = false;
                } else {
                    gridSelectAllCheckbox.checked = false;
                    gridSelectAllCheckbox.indeterminate = true;
                }
                
                // Show/hide "Select All" text based on checkbox state
                const gridSelectAllText = document.getElementById('grid-select-all-text');
                if (gridSelectAllText) {
                    if (gridSelectAllCheckbox.checked) {
                        gridSelectAllText.classList.remove('hidden');
                    } else {
                        gridSelectAllText.classList.add('hidden');
                    }
                }
            }
        }

        function clearSelection() {
            selectedDocuments.clear();
            selectAllChecked = false;
            updateBulkDeleteUI();
            updateSelectAllCheckbox();
            displayDocumentsByTime(currentDocuments);
        }

        function bulkDeleteSelected() {
            if (selectedDocuments.size === 0) return;
            
            const selectedDocs = Array.from(selectedDocuments).map(id => 
                currentDocuments.find(doc => doc.id == id)
            ).filter(Boolean);
            
            if (selectedDocs.length === 0) return;
            
            // Show confirmation modal
            showBulkDeleteConfirmation(selectedDocs);
        }

        function showBulkDeleteConfirmation(docs) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Delete Selected Documents</h3>
                        <button type="button" onclick="closeBulkDeleteModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mb-4">
                        <p class="text-gray-700">Are you sure you want to delete ${docs.length} selected document${docs.length > 1 ? 's' : ''}?</p>
                        <p class="text-sm text-gray-500 mt-2">This action cannot be undone.</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="bulk-delete-confirm-btn" type="button" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                        <button type="button" onclick="closeBulkDeleteModal()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add event listeners
            const confirmBtn = document.getElementById('bulk-delete-confirm-btn');
            confirmBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                confirmBulkDelete(docs);
            });
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeBulkDeleteModal();
                }
            });
        }

        function closeBulkDeleteModal() {
            const modals = document.querySelectorAll('.fixed');
            modals.forEach(modal => {
                if (modal.innerHTML.includes('Delete Selected Documents')) {
                    modal.remove();
                }
            });
        }

        function confirmBulkDelete(docs) {
            const confirmBtn = document.getElementById('bulk-delete-confirm-btn');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Deleting...';
            }
            
            let deletedCount = 0;
            let failedCount = 0;
            
            // Delete each document
            const deletePromises = docs.map(doc => {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', doc.id);
                
                return fetch('api/documents.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        deletedCount++;
                        // Remove from current documents
                        const index = currentDocuments.findIndex(d => d.id == doc.id);
                        if (index !== -1) {
                            currentDocuments.splice(index, 1);
                        }
                        // Remove from selected documents
                        selectedDocuments.delete(doc.id);
                    } else {
                        failedCount++;
                    }
                })
                .catch(error => {
                    console.error('Error deleting document:', error);
                    failedCount++;
                });
            });
            
            Promise.all(deletePromises).then(() => {
                // Close modal
                closeBulkDeleteModal();
                
                // Reset selection
                selectedDocuments.clear();
                selectAllChecked = false;
                
                // Update UI
                displayDocumentsByTime(currentDocuments);
                updateBulkDeleteUI();
                updateSelectAllCheckbox();
                
                console.log(`Bulk delete completed: ${deletedCount} deleted, ${failedCount} failed`);
            });
        }

        // Document Editor Functions
        function openDocumentEditor() {
            if (isEditorOpen) return;
            
            isEditorOpen = true;
            currentDocumentContent = '';
            
            const modal = document.createElement('div');
            modal.id = 'document-editor-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl h-5/6 flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Create New Document</h2>
                        <button type="button" onclick="closeDocumentEditor()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Editor Content -->
                    <div class="flex-1 p-6 overflow-hidden">
                        <div class="h-full flex flex-col">
                            <div class="mb-4">
                                <label for="document-title" class="block text-sm font-medium text-gray-700 mb-2">Document Title</label>
                                <input type="text" id="document-title" placeholder="Enter document title..." 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="flex-1">
                                <label for="document-content" class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                                <textarea id="document-content" placeholder="Start typing your document content here..." 
                                          class="w-full h-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                          style="min-height: 300px;"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
                        <button type="button" onclick="closeDocumentEditor()" 
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="button" onclick="saveDocument()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Save Document
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Focus on title input
            setTimeout(() => {
                const titleInput = document.getElementById('document-title');
                if (titleInput) titleInput.focus();
            }, 100);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeDocumentEditor();
                }
            });
        }

        function closeDocumentEditor() {
            const modal = document.getElementById('document-editor-modal');
            if (modal) {
                modal.remove();
                isEditorOpen = false;
                currentDocumentContent = '';
            }
        }

        function saveDocument() {
            const titleInput = document.getElementById('document-title');
            const contentInput = document.getElementById('document-content');
            
            if (!titleInput || !contentInput) return;
            
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
            
            if (!title) {
                alert('Please enter a document title.');
                titleInput.focus();
                return;
            }
            
            if (!content) {
                alert('Please enter some content for your document.');
                contentInput.focus();
                return;
            }
        }
        
        function openDocumentUpload() {
            // Open the new advanced upload modal
            try {
                showUploadModal();
            } catch (e) {
                console.error('Failed to open upload modal:', e);
            }
        }
        function clearSearch() {
            const searchInput = document.getElementById('search-documents');
            if (searchInput) {
                searchInput.value = '';
                currentFilters.search = '';
                filterAndDisplayDocuments('');
            }
        }

        function showDocumentMenu(docId) {
            // This would show a context menu for document actions
            showNotification('Document menu coming soon!', 'info');
        }

        // File type and icon functions
        function getFileExtension(filename) {
            return filename.split('.').pop().toLowerCase();
        }

        function getFileIcon(extension) {
            const icons = {
                'pdf': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm0 2h4v4H4V5zm6 0h6v4h-6V5zM4 11h4v4H4v-4zm6 0h6v4h-6v-4z"></path></svg>',
                'doc': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'docx': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'xls': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'xlsx': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'ppt': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'pptx': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'txt': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v2H6V5zm0 4h8v2H6V9zm0 4h6v2H6v-2z"></path></svg>',
                'jpg': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                'jpeg': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                'png': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                'gif': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                'zip': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'rar': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>'
            };
            return icons[extension] || '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm8 2v4H8V5h4zM6 7v2H4V7h2zm0 4v2H4v-2h2zm8-4v2h2V7h-2zm0 4v2h2v-2h-2z"></path></svg>';
        }

        function getFileColor(extension) {
            const colors = {
                'pdf': 'bg-red-500',
                'doc': 'bg-blue-500',
                'docx': 'bg-blue-500',
                'xls': 'bg-green-600',
                'xlsx': 'bg-green-600',
                'ppt': 'bg-orange-500',
                'pptx': 'bg-orange-500',
                'txt': 'bg-gray-500',
                'jpg': 'bg-green-500',
                'jpeg': 'bg-green-500',
                'png': 'bg-green-500',
                'gif': 'bg-purple-500',
                'zip': 'bg-yellow-500',
                'rar': 'bg-yellow-500'
            };
            return colors[extension] || 'bg-gray-400';
        }

        function viewDocument(id) {
            const doc = currentDocuments.find(d => d.id == id);
            if (doc) {
                // For now, just show a notification
                showNotification(`Viewing ${doc.document_name || doc.title || 'Untitled Document'}`, 'info');
                // In a real implementation, this would open a detailed view modal
            }
        }

        function showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300`;
            notification.style.top = '160px';
            notification.style.zIndex = '99999';
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function showErrorMessage(message) {
            showNotification(message, 'error');
        }

        // Recent uploads functionality - Local state based (no API dependency)
        function displayRecentUploads() {
            console.log('=== DISPLAY RECENT UPLOADS CALLED ===');
            console.log('Current recentUploads array:', recentUploads);
            console.log('Array length:', recentUploads.length);
            
            const container = document.getElementById('recent-uploads');
            if (!container) {
                console.error('Recent uploads container not found!');
                return;
            }
            
            console.log('Found container:', container);
            console.log('Container current HTML:', container.innerHTML.substring(0, 200) + '...');
            
            if (recentUploads.length === 0) {
                console.log('No recent uploads, showing empty state');
                container.innerHTML = `
                    <div class="text-center py-6 text-gray-500">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-600">No recent uploads yet</p>
                        <p class="text-xs text-gray-500 mt-1">Upload files to see them here</p>
                    </div>
                `;
                console.log('Empty state displayed');
                return;
            }
            
            console.log('Found', recentUploads.length, 'recent uploads to display');
            console.log('Clearing container...');
            container.innerHTML = '';
            
            // Display recent uploads (newest first)
            recentUploads.forEach((upload, index) => {
                console.log('Creating upload item', index + 1, ':', upload);
                try {
                    const uploadItem = createUploadItem(upload);
                    console.log('Created upload item HTML:', uploadItem.substring(0, 100) + '...');
                    container.appendChild(uploadItem);
                    console.log('Added upload item to container');
                } catch (error) {
                    console.error('Error creating upload item:', error);
                }
            });
            
            console.log('Final container HTML length:', container.innerHTML.length);
            console.log('=== DISPLAY RECENT UPLOADS COMPLETED ===');
        }

        function createUploadItem(upload) {
            try {
                console.log('Creating upload item for:', upload);
                
                const fileIcon = getFileIcon(upload.type);
                const fileColor = getFileColor(upload.type);
                const isCompleted = upload.progress === 100;
                
                // Format file size
                let fileSize = 'Unknown size';
                if (upload.fileSize && typeof formatFileSize === 'function') {
                    fileSize = formatFileSize(upload.fileSize);
                } else if (upload.fileSize) {
                    fileSize = Math.round(upload.fileSize / 1024) + ' KB';
                }
                
                // Format upload date
                let uploadDate = 'Just now';
                if (upload.uploadDate) {
                    try {
                        uploadDate = new Date(upload.uploadDate).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    } catch (e) {
                        console.log('Error formatting date:', e);
                        uploadDate = 'Just now';
                    }
                }

                const uploadItem = `
                    <div class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors cursor-pointer" onclick="viewDocumentByName('${upload.name}')">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center flex-1 min-w-0">
                                <div class="w-6 h-6 ${fileColor} rounded flex items-center justify-center mr-2 flex-shrink-0">
                                    ${fileIcon.replace('w-4 h-4', 'w-3 h-3')}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 truncate" title="${upload.name}">${upload.name}</div>
                                    <div class="text-xs text-gray-500">${fileSize} • ${upload.type.toUpperCase()}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1 ml-2">
                                <span class="text-xs text-gray-400">${uploadDate}</span>
                                <button onclick="event.stopPropagation(); removeFromRecentUploads('${upload.name}')" 
                                        class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded-full hover:bg-red-50" 
                                        aria-label="Remove from recent uploads">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        ${!isCompleted ? `
                            <div class="w-full bg-gray-200 rounded-full h-1">
                                <div class="bg-blue-600 h-1 rounded-full transition-all duration-300" style="width: ${upload.progress}%"></div>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                console.log('Created upload item successfully');
                return uploadItem;
            } catch (error) {
                console.error('Error creating upload item:', error);
                // Return a simple fallback item
                return `
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-gray-400 rounded flex items-center justify-center mr-2">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm8 2v4H8V5h4zM6 7v2H4V7h2zm0 4v2H4v-2h2zm8-4v2h2V7h-2zm0 4v2h2v-2h-2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">${upload.name || 'Unknown File'}</div>
                                <div class="text-xs text-gray-500">File uploaded</div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        function showUploadModal() {
            const modal = document.createElement('div');
            modal.id = 'upload-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[80vh] overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">File Upload</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('upload-modal').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-5 overflow-y-auto">
                        <div id="drop-zone" class="border-2 border-dashed rounded-xl p-8 text-center bg-purple-50/50 border-purple-300">
                                                         <div class="w-12 h-12 rounded-full mx-auto mb-3 flex items-center justify-center bg-white shadow">
                                 <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8"/></svg>
                             </div>
                                                          <p class="text-sm font-medium text-purple-700 cursor-pointer" onclick="document.getElementById('single-file-input').click()">Click to Upload</p>
                             <p class="text-xs text-purple-600">or drag and drop</p>
                             
                             <p class="text-[11px] text-gray-400 mt-1">Supports any kinds of document</p>
                             <input id="single-file-input" type="file" multiple class="hidden" />
                        </div>

                        <div id="upload-list" class="space-y-3"></div>
                    </div>

                    <div class="px-6 pb-6">
                        <button id="begin-upload-btn" class="w-full bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white py-3 rounded-xl font-semibold disabled:opacity-50" disabled>Upload</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            const input = modal.querySelector('#single-file-input');
            const dropZone = modal.querySelector('#drop-zone');
            const list = modal.querySelector('#upload-list');
            const uploadBtn = modal.querySelector('#begin-upload-btn');

            let selectedFiles = [];

            function resetList() {
                list.innerHTML = '';
                uploadBtn.disabled = selectedFiles.length === 0;
            }

            function makeRow(file, state) {
                const id = 'row-' + Date.now();
                const isUploading = state === 'uploading';
                const isFailed = state === 'failed';
                const isDone = state === 'done';
                const row = document.createElement('div');
                row.id = id;
                row.className = 'bg-gray-50 rounded-xl px-4 py-3';
                row.innerHTML = `
                    <div class="flex items-start justify-between">
                                                 <div class="flex items-center gap-3 min-w-0 flex-1">
                             <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                                <div class="mt-1">
                                                                         <div class="w-[420px] max-w-full h-1.5 bg-gray-200 rounded-full overflow-hidden"><div class="progress-bar h-full bg-purple-500 rounded-full transition-all duration-700 ease-out" style="width:${isDone?100:isFailed?0:0}%"></div></div>
                                    <div class="flex items-center gap-2 mt-1 text-xs">
                                        <span class="text-gray-500">${Math.round(file.size/1024)}kb</span>
                                        <span class="text-gray-400">${isUploading? 'Uploading...' : isFailed? '<span class=\'text-red-600\'>Upload Failed</span>' : isDone? '<span class=\'text-green-600\'>Completed</span>' : ''}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                                                 <div class="flex items-center gap-2 ml-3">
                             <button class="text-gray-400 hover:text-red-600 transition-colors" title="Delete" data-act="delete">
                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                 </svg>
                             </button>
                         </div>
                    </div>`;
                row.addEventListener('click', (e)=>{
                    const act = e.target.closest('[data-act]')?.getAttribute('data-act');
                    if (!act) return;
                    if (act === 'delete') {
                        // Remove the specific file from the list
                        const fileName = e.target.closest('.bg-gray-50').querySelector('p').textContent;
                        selectedFiles = selectedFiles.filter(f => f.name !== fileName);
                        
                        // Remove just this specific row instead of resetting the entire list
                        const rowToRemove = e.target.closest('.bg-gray-50');
                        if (rowToRemove) {
                            rowToRemove.remove();
                        }
                        
                        // Update upload button state
                        uploadBtn.disabled = selectedFiles.length === 0;
                    }
                });
                return row;
            }

            function setFiles(files) {
                selectedFiles = Array.from(files);
                resetList();
                if (selectedFiles.length === 0) return;
                selectedFiles.forEach((file, index) => {
                    const row = makeRow(file, 'idle');
                    list.appendChild(row);
                    const bar = row.querySelector('.progress-bar');
                    if (bar) {
                        bar.style.width = '0%';
                        requestAnimationFrame(() => {
                            bar.style.width = '100%';
                        });
                    }
                });
                uploadBtn.disabled = false;
            }

                        input.addEventListener('change', () => {
                if (!input.files || input.files.length === 0) return;
                setFiles(input.files);
            });

            ;['dragover','dragleave','drop'].forEach(evt => {
                dropZone.addEventListener(evt, e => {
                    e.preventDefault();
                    if (evt==='dragover') dropZone.classList.add('border-purple-500','bg-purple-50');
                    if (evt!=='dragover') dropZone.classList.remove('border-purple-500','bg-purple-50');
                    if (evt==='drop') {
                        const files = e.dataTransfer.files;
                        if (files && files.length > 0) {
                            setFiles(files);
                        }
                    }
                });
            });

            uploadBtn.addEventListener('click', () => {
                if (selectedFiles.length === 0) return;
                
                // Show uploading state for all files
                list.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const row = makeRow(file, 'uploading');
                    row.id = `row-${index}`;
                    list.appendChild(row);
                    // Animate progress bar while uploading
                    const bar = row.querySelector('.progress-bar');
                    if (bar) {
                        bar.style.width = '0%';
                        let progress = 0;
                        const step = () => {
                            if (!document.getElementById(`row-${index}`)) return; // row removed
                            progress = Math.min(progress + 4, 80);
                            bar.style.width = progress + '%';
                            if (progress < 80) {
                                requestAnimationFrame(step);
                            }
                        };
                        requestAnimationFrame(step);
                    }
                });

                // Upload all files
                let completedCount = 0;
                let failedCount = 0;
                
                selectedFiles.forEach((file, index) => {
                    uploadSingleFile(file, index, () => {
                        // success
                        const row = document.getElementById(`row-${index}`);
                        if (row) {
                            // Animate the violet progress bar to full
                            const bar = row.querySelector('.progress-bar');
                            if (bar) {
                                requestAnimationFrame(() => {
                                    bar.style.width = '100%';
                                });
                            }
                            // Then switch row to completed state after animation
                            bar.addEventListener('transitionend', () => {
                                const currentRow = document.getElementById(`row-${index}`);
                                if (currentRow) {
                                    currentRow.innerHTML = makeRow(file, 'done').innerHTML;
                                }
                            }, { once: true });
                        }
                        completedCount++;
                        
                        if (completedCount + failedCount === selectedFiles.length) {
                            // All uploads completed
                            uploadBtn.disabled = true;
                            
                            // Immediately add newly uploaded files to the display
                            if (completedCount > 0) {
                                // Add uploaded files directly to current documents list
                                selectedFiles.forEach((file, index) => {
                                    // Create a new document object for the uploaded file
                                    const newDoc = {
                                        id: Date.now() + index, // Temporary ID
                                        document_name: file.name,
                                        filename: file.name,
                                        title: file.name,
                                        file_size: file.size,
                                        upload_date: new Date().toISOString(),
                                        file_path: 'uploads/' + file.name,
                                        type: file.type || 'application/octet-stream'
                                    };
                                    
                                    // Add to current documents list
                                    currentDocuments.unshift(newDoc); // Add to beginning
                                    window.allDocuments.unshift(newDoc); // Add to global list
                                    
                                    // Add to existing file names set
                                    existingFileNames.add(file.name.toLowerCase());
                                });
                                
                                // Update the display immediately
                                displayDocumentsByTime(currentDocuments);
                                
                                // Also refresh from server in background
                                setTimeout(() => {
                                    loadDocuments();
                                    loadStats();
                                }, 1000);
                            }
                            
                            // Close modal after all uploads complete (regardless of success/failure)
                            setTimeout(() => {
                                const modal = document.getElementById('upload-modal');
                                if (modal) {
                                    modal.remove();
                                }
                            }, 1000);
                        }
                    }, () => {
                        // failed
                        const row = document.getElementById(`row-${index}`);
                        if (row) {
                            row.innerHTML = makeRow(file, 'failed').innerHTML;
                        }
                        failedCount++;
                        
                        if (completedCount + failedCount === selectedFiles.length) {
                            // All uploads completed
                            uploadBtn.disabled = false;
                            
                            // Immediately add newly uploaded files to the display
                            if (completedCount > 0) {
                                // Add uploaded files directly to current documents list
                                selectedFiles.forEach((file, index) => {
                                    // Create a new document object for the uploaded file
                                    const newDoc = {
                                        id: Date.now() + index, // Temporary ID
                                        document_name: file.name,
                                        filename: file.name,
                                        title: file.name,
                                        file_size: file.size,
                                        upload_date: new Date().toISOString(),
                                        file_path: 'uploads/' + file.name,
                                        type: file.type || 'application/octet-stream'
                                    };
                                    
                                    // Add to current documents list
                                    currentDocuments.unshift(newDoc); // Add to beginning
                                    window.allDocuments.unshift(newDoc); // Add to global list
                                    
                                    // Add to existing file names set
                                    existingFileNames.add(file.name.toLowerCase());
                                });
                                
                                // Update the display immediately
                                displayDocumentsByTime(currentDocuments);
                                
                                // Also refresh from server in background
                                setTimeout(() => {
                                    loadDocuments();
                                    loadStats();
                                }, 1000);
                            }
                            
                            // Close modal after all uploads complete (regardless of success/failure)
                            setTimeout(() => {
                                const modal = document.getElementById('upload-modal');
                                if (modal) {
                                    modal.remove();
                                }
                            }, 1000);
                        }
                    });
                });
            });
        }

        function displaySelectedFiles(files) {
            const filesList = document.getElementById('selected-files-list');
            const filesContainer = document.getElementById('files-container');
            
            if (files.length === 0) {
                filesList.classList.add('hidden');
                return;
            }
            
            filesList.classList.remove('hidden');
            filesContainer.innerHTML = '';
            
            Array.from(files).forEach((file, index) => {
                            // Check for duplicate file names (including versioned files)
            const fileName = file.name;
            const baseFileName = fileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers like "(1)", "(2)"
            
            const isDuplicate = currentDocuments.some(doc => {
                const docFileName = doc.filename || doc.document_name || doc.title || '';
                const docBaseFileName = docFileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers
                return docBaseFileName.toLowerCase() === baseFileName.toLowerCase();
            });
                
                const fileItem = document.createElement('div');
                fileItem.className = `flex items-center justify-between p-2 rounded-lg ${isDuplicate ? 'bg-red-50 border border-red-200' : 'bg-gray-50'}`;
                fileItem.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 ${isDuplicate ? 'text-red-500' : 'text-gray-500'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm ${isDuplicate ? 'text-red-700' : 'text-gray-700'} truncate">${file.name}</span>
                        <span class="text-xs ${isDuplicate ? 'text-red-500' : 'text-gray-500'}">(${formatFileSize(file.size)})</span>
                        ${isDuplicate ? '<span class="text-xs text-red-600 font-medium">(Duplicate)</span>' : ''}
                    </div>
                    <button onclick="removeFile(${index})" class="text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                `;
                filesContainer.appendChild(fileItem);
            });
        }

        function removeFile(index) {
            const fileInput = document.getElementById('file-upload');
            const dt = new DataTransfer();
            const files = Array.from(fileInput.files);
            
            files.splice(index, 1);
            files.forEach(file => dt.items.add(file));
            
            fileInput.files = dt.files;
            displaySelectedFiles(fileInput.files);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function uploadFiles() {
            const fileInput = document.getElementById('file-upload');
            const files = fileInput.files;
            
            if (files.length === 0) {
                showNotification('Please select files to upload', 'error');
                return;
            }
            
            // Check for duplicate files before uploading (including versioned files)
            const duplicateFiles = [];
            Array.from(files).forEach(file => {
                const fileName = file.name;
                const baseFileName = fileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers like "(1)", "(2)"
                
                const isDuplicate = currentDocuments.some(doc => {
                    const docFileName = doc.filename || doc.document_name || doc.title || '';
                    const docBaseFileName = docFileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers
                    return docBaseFileName.toLowerCase() === baseFileName.toLowerCase();
                });
                if (isDuplicate) {
                    duplicateFiles.push(fileName);
                }
            });
            
            if (duplicateFiles.length > 0) {
                const duplicateList = duplicateFiles.join(', ');
                showNotification(`Cannot upload duplicate files: ${duplicateList}. Please rename or remove them.`, 'error');
                return;
            }
            
            // Show upload progress modal
            showUploadProgressModal(files);
            
            // Upload each file
            let uploadedCount = 0;
            let failedCount = 0;
            
            Array.from(files).forEach((file, index) => {
                uploadSingleFile(file, index, () => {
                    uploadedCount++;
                    updateUploadProgress(index, true);
                    
                    if (uploadedCount + failedCount === files.length) {
                        // All uploads completed
                        setTimeout(() => {
                            closeUploadProgressModal();
                            
                            // Remove upload modal
                            const uploadModal = document.getElementById('upload-modal');
                            if (uploadModal) {
                                uploadModal.remove();
                            }
                            
                            // Suppress all notifications during upload completion
                            window.suppressNotifications(1000);
                            
                            // Reload documents but preserve recent uploads
                            loadDocuments();
                            // Don't call displayRecentUploads() here as it might clear the recent uploads
                            console.log('Upload completion - recent uploads should already be updated');
                        }, 1000);
                    }
                }, () => {
                    failedCount++;
                    updateUploadProgress(index, false);
                    
                    if (uploadedCount + failedCount === files.length) {
                        // All uploads completed
                        setTimeout(() => {
                            closeUploadProgressModal();
                            
                            // Remove upload modal
                            const uploadModal = document.getElementById('upload-modal');
                            if (uploadModal) {
                                uploadModal.remove();
                            }
                            
                            // Suppress all notifications during upload completion
                            window.suppressNotifications(1000);
                            
                            // Reload documents but preserve recent uploads
                            loadDocuments();
                            // Don't call displayRecentUploads() here as it might clear the recent uploads
                            console.log('Upload completion - recent uploads should already be updated');
                        }, 1000);
                    }
                });
            });
        }

        function uploadSingleFile(file, index, onSuccess, onError) {
            // No file size limit - removed 10MB restriction
            // No file type restrictions - accept any file type
            
            // Check for duplicate file names (including versioned files)
            const fileName = file.name;
            const baseFileName = fileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers like "(1)", "(2)"
            
            const isDuplicate = currentDocuments.some(doc => {
                const docFileName = doc.filename || doc.document_name || doc.title || '';
                const docBaseFileName = docFileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers
                return docBaseFileName.toLowerCase() === baseFileName.toLowerCase();
            });
            
            if (isDuplicate) {
                showNotification(`File "${baseFileName}" already exists (including any versions). Please rename the file or choose a different one.`, 'error');
                onError();
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('file', file);
            
            // Auto-generate document name from filename with duplicate handling
            let documentName = file.name.replace(/\.[^/.]+$/, ""); // Remove extension
            let counter = 1;
            const originalName = documentName;
            
            // Check for duplicates and add version number if needed
            while (existingFileNames.has(documentName.toLowerCase())) {
                documentName = `${originalName} (${counter})`;
                counter++;
            }
            
            // Add to existing file names set
            existingFileNames.add(documentName.toLowerCase());
            
            formData.append('document_name', documentName);
            try {
                classifyFileClientSide(file).then(category => {
                    if (category) formData.append('category', category);
                }).catch(() => {});
            } catch(e) {}
            
            console.log('Starting upload for file:', file.name, 'Size:', file.size, 'Type:', file.type);
            console.log('Form data prepared:', {
                action: 'add',
                document_name: documentName,
                file_name: file.name
            });
            
            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON response:', e);
                        console.error('Raw response was:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                console.log('Upload response data:', data);
                console.log('Response type:', typeof data);
                console.log('Success property:', data.success);
                
                // ALWAYS add to recent uploads regardless of API response
                console.log('File upload attempt completed for:', file.name);
                
                // Force add to recent uploads immediately
                const uploadData = {
                    name: file.name,
                    type: file.name.split('.').pop().toLowerCase(),
                    progress: 100,
                    file: file,
                    fileSize: file.size,
                    uploadDate: new Date().toISOString()
                };
                
                console.log('About to add to recent uploads:', uploadData);
                
                // Add to recent uploads when file is successfully uploaded
                addToRecentUploads(uploadData);
                
                console.log('Successfully called addToRecentUploads for:', file.name);
                
                if (data && data.success) {
                    console.log('File uploaded successfully:', file.name);
                    onSuccess();
                } else {
                    console.log('Upload failed:', data ? data.message : 'Unknown error');
                    onError();
                }
            })
            .catch(error => {
                console.error('Upload error for file:', file.name, error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack
                });
                onError();
            });
        }

        async function classifyFileClientSide(file) {
            try {
                const name = (file.name || '').toLowerCase();
                if (/\b(mou|moa|memorandum|agreement|kuma-mou)\b/i.test(name)) return 'MOUs & MOAs';
                if (/\b(template|form|admission|application|registration|checklist|request)\b/i.test(name)) return 'Templates';
                const isImage = /^image\//.test(file.type);
                if (isImage && file.size < 5 * 1024 * 1024 && window.Tesseract) {
                    const text = await Tesseract.recognize(file, 'eng').then(r => (r && r.data && r.data.text) ? r.data.text : '').catch(() => '');
                    if (/\b(MOU|MOA|Memorandum of Understanding|Agreement)\b/i.test(text)) return 'MOUs & MOAs';
                    if (/\b(Template|Form|Admission|Application|Registration|Checklist|Request)\b/i.test(text)) return 'Templates';
                }
            } catch(e) {}
            return '';
        }

        function showUploadProgressModal(files) {
            const modal = document.createElement('div');
            modal.id = 'upload-progress-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Uploading Files</h3>
                    </div>
                    <div class="space-y-3">
                        ${Array.from(files).map((file, index) => `
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-700 truncate">${file.name}</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin mr-2"></div>
                                    <span class="text-xs text-gray-500">Uploading...</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function updateUploadProgress(index, success) {
            const progressElements = document.querySelectorAll('#upload-progress-modal .animate-spin');
            if (progressElements[index]) {
                const parent = progressElements[index].parentElement;
                if (success) {
                    parent.innerHTML = `
                        <div class="w-4 h-4 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-xs text-green-600">Complete</span>
                    `;
                } else {
                    parent.innerHTML = `
                        <div class="w-4 h-4 bg-red-500 rounded-full mr-2"></div>
                        <span class="text-xs text-red-600">Failed</span>
                    `;
                }
            }
        }

        function closeUploadProgressModal() {
            const modal = document.getElementById('upload-progress-modal');
            if (modal) {
                modal.remove();
            }
        }

        // Initialize dropdown filters functionality
        function initializeDropdownFilters() {
            // All Documents dropdown
            const allDocumentsDropdown = document.querySelector('select:first-of-type');
            if (allDocumentsDropdown) {
                allDocumentsDropdown.addEventListener('change', function() {
                    const filterValue = this.value;
                    currentFilters.documentStatus = filterValue;
                    currentFilters.page = 1;
                    loadDocuments();
                });
            }

            // Documents Type dropdown
            const documentTypeDropdown = document.querySelector('select:last-of-type');
            if (documentTypeDropdown) {
                documentTypeDropdown.addEventListener('change', function() {
                    const filterValue = this.value;
                    currentFilters.fileType = filterValue;
                    currentFilters.page = 1;
                    loadDocuments();
                });
            }
        }

        // Initialize drag and drop functionality
        function initializeDragAndDrop() {
            const uploadArea = document.querySelector('.border-dashed');
            if (uploadArea) {
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('border-blue-500', 'bg-blue-50');
                });

                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-blue-500', 'bg-blue-50');
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-blue-500', 'bg-blue-50');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        handleFileUpload(files);
                    }
                });
            }
        }

        // Handle file upload
        function handleFileUpload(files) {
            const fileArray = Array.from(files);
            
            // Show upload progress
            showUploadProgress(fileArray);
            
            // Simulate upload process
            fileArray.forEach((file, index) => {
                setTimeout(() => {
                    uploadFile(file, index);
                }, index * 500);
            });
        }

        // Show upload progress
        function showUploadProgress(files) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Uploading Files</h3>
                    </div>
                    <div class="space-y-3">
                        ${files.map((file, index) => `
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">${file.name}</span>
                                <div class="flex items-center">
                                    <div class="w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin mr-2"></div>
                                    <span class="text-xs text-gray-500">Uploading...</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Upload individual file
        function uploadFile(file, index) {
            // Simulate upload progress
            const progress = Math.random() * 100;
            
            // Add to recent uploads
            addToRecentUploads({
                name: file.name,
                type: getFileExtension(file.name),
                progress: progress,
                file: file
            });
            
            // Update progress in modal
            updateUploadProgress(index, progress);
            
            if (progress >= 100) {
                setTimeout(() => {
                    document.querySelector('.fixed').remove();
                    
                    // Suppress all notifications during individual file upload completion
                    window.suppressNotifications(1000);
                    
                    loadDocuments();
                }, 1000);
            }
        }

        // Update upload progress
        function updateUploadProgress(index, progress) {
            const progressElements = document.querySelectorAll('.animate-spin');
            if (progressElements[index]) {
                const parent = progressElements[index].parentElement;
                parent.innerHTML = `
                    <div class="w-4 h-4 bg-green-500 rounded-full mr-2"></div>
                    <span class="text-xs text-green-600">Complete</span>
                `;
            }
        }

        // Initialize recent uploads interactions
        function initializeRecentUploads() {
            // Add event delegation for recent uploads
            document.addEventListener('click', function(e) {
                const uploadItem = e.target.closest('.bg-gray-50');
                if (uploadItem && uploadItem.closest('#recent-uploads')) {
                    const fileName = uploadItem.querySelector('span').textContent;
                    viewDocumentByName(fileName);
                }
                
                // Handle cancel/remove buttons
                const cancelBtn = e.target.closest('button[aria-label="Cancel upload"]');
                if (cancelBtn) {
                    const uploadItem = cancelBtn.closest('.bg-gray-50');
                    const fileName = uploadItem.querySelector('span').textContent;
                    removeFromRecentUploads(fileName);
                }
            });
        }

        // Add to recent uploads - Persistent storage based
        function addToRecentUploads(upload) {
            console.log('=== ADD TO RECENT UPLOADS CALLED ===');
            console.log('Upload data received:', upload);
            
            // Handle duplicate names in recent uploads
            let uploadName = upload.name;
            let counter = 1;
            const originalName = uploadName;
            
            // Check for duplicates in recent uploads
            while (recentUploads.some(u => u.name === uploadName)) {
                uploadName = `${originalName} (${counter})`;
                counter++;
            }
            
            // Enhance upload object with additional details
            const enhancedUpload = {
                ...upload,
                name: uploadName, // Use potentially modified name
                fileSize: upload.file ? upload.file.size : null,
                uploadDate: new Date().toISOString(),
                type: upload.type || (upload.file ? upload.file.name.split('.').pop().toLowerCase() : 'file')
            };
            
            console.log('Enhanced upload object:', enhancedUpload);
            
            // Add to local state (at the beginning for newest first)
            recentUploads.unshift(enhancedUpload);
            
            console.log('Updated recentUploads array:', recentUploads);
            console.log('Array length:', recentUploads.length);
            
            // Save to localStorage for persistence
            try {
                localStorage.setItem('lilac_recent_uploads', JSON.stringify(recentUploads));
                console.log('Saved to localStorage');
            } catch (error) {
                console.error('Failed to save to localStorage:', error);
            }
            
            console.log('About to call displayRecentUploads()...');
            
            // Update the display
            displayRecentUploads();
            
            console.log('displayRecentUploads() completed');
            
            console.log('=== ADD TO RECENT UPLOADS COMPLETED ===');
        }

        // Remove from recent uploads - Persistent storage based
        function removeFromRecentUploads(fileName) {
            // Remove from local state
            const index = recentUploads.findIndex(upload => upload.name === fileName);
            if (index !== -1) {
                recentUploads.splice(index, 1);
                
                // Save to localStorage
                try {
                    localStorage.setItem('lilac_recent_uploads', JSON.stringify(recentUploads));
                } catch (error) {
                    console.error('Failed to save to localStorage:', error);
                }
                
                // Update the display
                displayRecentUploads();
            }
        }
        
        // Load recent uploads from localStorage
        function loadRecentUploadsFromStorage() {
            try {
                const stored = localStorage.getItem('lilac_recent_uploads');
                if (stored) {
                    recentUploads = JSON.parse(stored);
                    console.log('Loaded recent uploads from localStorage:', recentUploads.length, 'items');
                }
            } catch (error) {
                console.error('Failed to load from localStorage:', error);
                recentUploads = [];
            }
        }

        // View document by name
        function viewDocumentByName(fileName) {
            const doc = currentDocuments.find(d => 
                (d.document_name || d.title || '').toLowerCase().includes(fileName.toLowerCase())
            );
            
            if (doc) {
                viewDocument(doc.id);
            } else {
                showNotification(`Opening ${fileName}`, 'info');
            }
        }

        // Initialize keyboard navigation
        function initializeKeyboardNavigation() {
            document.addEventListener('keydown', function(e) {
                // Tab navigation for dropdowns
                if (e.key === 'Tab') {
                    const dropdowns = document.querySelectorAll('select');
                    dropdowns.forEach(dropdown => {
                        if (document.activeElement === dropdown) {
                            dropdown.addEventListener('keydown', function(e) {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    this.focus();
                                }
                            });
                        }
                    });
                }

                // Escape key to close modals
                if (e.key === 'Escape') {
                    const modals = document.querySelectorAll('.fixed');
                    modals.forEach(modal => {
                        if (modal.style.display !== 'none') {
                            modal.remove();
                        }
                    });
                }
            });
        }

        // Enhanced view document function
        function viewDocument(docId) {
            const doc = currentDocuments.find(d => d.id == docId);
            if (doc) {
                // Show document viewer modal
                showDocumentViewer(doc);
            } else {
                showNotification('Document not found', 'error');
            }
        }

        // Modal-based document viewer
        function showDocumentViewer(doc) {
            const title = doc.document_name || doc.title || 'Untitled Document';
            let filePath = doc.file_path || doc.filename || doc.filename;
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
                                const availableWidth = contentEl.clientWidth - 16; // account for padding
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

            downloadBtn.onclick = function() { downloadDocument(doc.id); };
            overlay.classList.remove('hidden');
        }

        // Download document
        function downloadDocument(docId) {
            const doc = currentDocuments.find(d => d.id == docId);
            if (doc) {
                const fileName = doc.document_name || doc.title || 'Untitled Document';
                // Ensure proper file path with uploads directory
                let filePath = doc.file_path || doc.filename || doc.filename;
                
                // If the path doesn't start with uploads/, add it
                if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                    filePath = `uploads/${filePath}`;
                }
                
                // If no filename is available, show error
                if (!filePath) {
                    showNotification('File path not available', 'error');
                    return;
                }
                
                console.log('Download attempt:', { doc, fileName, filePath });
                
                showNotification(`Downloading ${fileName}...`, 'info');
                
                // First check if the file exists
                fetch(filePath, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            // File exists, proceed with download
                            const link = document.createElement('a');
                            link.href = filePath;
                            link.download = fileName;
                            link.target = '_blank';
                            
                            // Add to DOM, click, and remove
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            showNotification(`${fileName} download started!`, 'success');
                        } else {
                            throw new Error(`File not found (${response.status})`);
                        }
                    })
                    .catch(error => {
                        console.error('Download error:', error);
                        showNotification(`Download failed: ${fileName} wasn't available on site`, 'error');
                        
                        // Show detailed error modal
                        showDownloadErrorModal(doc, fileName, filePath, error);
                    });
            } else {
                showNotification('Document not found', 'error');
            }
        }

        // Show download error modal with details
        function showDownloadErrorModal(doc, fileName, filePath, error) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Download Error</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-800 font-medium">${fileName} wasn't available on site</p>
                            <p class="text-xs text-red-600 mt-1">The file could not be found at the specified location.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File Details</label>
                            <div class="space-y-2 text-sm">
                                <div><strong>Name:</strong> ${fileName}</div>
                                <div><strong>Attempted Path:</strong> ${filePath}</div>
                                <div><strong>File Type:</strong> ${getFileExtension(doc.filename || '')}</div>
                                <div><strong>Document ID:</strong> ${doc.id}</div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Possible Solutions</label>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Check if the file exists in the uploads folder</li>
                                <li>• Verify the file path in the database</li>
                                <li>• Try re-uploading the file</li>
                                <li>• Contact administrator if issue persists</li>
                            </ul>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="retryDownload(${doc.id})" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Retry Download
                            </button>
                            <button onclick="this.closest('.fixed').remove()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Retry download function
        function retryDownload(docId) {
            // Close any existing error modals
            const modals = document.querySelectorAll('.fixed');
            modals.forEach(modal => {
                if (modal.innerHTML.includes('Download Error')) {
                    modal.remove();
                }
            });
            
            // Retry the download
            downloadDocument(docId);
        }

        // Share document
        function shareDocument(docId) {
            console.log('Share document called with ID:', docId);
            const doc = currentDocuments.find(d => d.id == docId);
            console.log('Found document:', doc);
            
            if (doc) {
                const fileName = doc.document_name || doc.title || 'Untitled Document';
                // Ensure proper file path with uploads directory
                let filePath = doc.file_path || doc.filename || doc.filename;
                
                // If the path doesn't start with uploads/, add it
                if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                    filePath = `uploads/${filePath}`;
                }
                
                // If no filename is available, show error
                if (!filePath) {
                    showNotification('File path not available', 'error');
                    return;
                }
                
                console.log('Sharing document:', { fileName, filePath });
                
                // Always show the custom share modal for now (more reliable)
                showShareModal(doc);
                
                // Optional: Try Web Share API first on mobile devices
                if (navigator.share && /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    navigator.share({
                        title: fileName,
                        text: `Check out this document: ${fileName}`,
                        url: window.location.origin + '/' + filePath
                    })
                    .then(() => {
                        showNotification(`${fileName} shared successfully!`, 'success');
                        // Close any open share modal
                        const shareModals = document.querySelectorAll('.fixed');
                        shareModals.forEach(modal => {
                            if (modal.innerHTML.includes('Share Document')) {
                                modal.remove();
                            }
                        });
                    })
                    .catch((error) => {
                        console.log('Web Share API failed, using custom modal:', error);
                        // Custom modal is already shown above
                    });
                }
            } else {
                showNotification('Document not found', 'error');
            }
        }

        // Show share modal for document
        function showShareModal(doc) {
            const fileName = doc.document_name || doc.title || 'Untitled Document';
            // Ensure proper file path with uploads directory
            let filePath = doc.file_path || doc.filename || doc.filename;
            
            // If the path doesn't start with uploads/, add it
            if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                filePath = `uploads/${filePath}`;
            }
            
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Share Document</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document</label>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm font-medium text-gray-900">${fileName}</p>
                                <p class="text-xs text-gray-500">${filePath}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Share Link</label>
                            <div class="flex">
                                <input type="text" value="${window.location.origin}/${filePath}" 
                                       class="flex-1 border border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                       readonly>
                                <button onclick="copyToClipboard(this.previousElementSibling)" 
                                        class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700 transition-colors text-sm">
                                    Copy
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Share via Email</label>
                            <input type="email" placeholder="Enter email address" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="flex gap-2">
                            <button onclick="sendEmailShare('${fileName}', '${filePath}')" 
                                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Send Email
                            </button>
                            <button onclick="this.closest('.fixed').remove()" 
                                    class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Copy to clipboard function
        function copyToClipboard(inputElement) {
            inputElement.select();
            inputElement.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                document.execCommand('copy');
                showNotification('Link copied to clipboard!', 'success');
            } catch (err) {
                // Fallback for modern browsers
                navigator.clipboard.writeText(inputElement.value).then(() => {
                    showNotification('Link copied to clipboard!', 'success');
                }).catch(() => {
                    showNotification('Failed to copy link', 'error');
                });
            }
        }

        // Send email share
        function sendEmailShare(fileName, filePath) {
            const emailInput = document.querySelector('input[type="email"]');
            const email = emailInput ? emailInput.value.trim() : '';
            
            if (!email) {
                showNotification('Please enter an email address', 'error');
                return;
            }
            
            if (!email.includes('@')) {
                showNotification('Please enter a valid email address', 'error');
                return;
            }
            
            // Validate email format more thoroughly
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('Please enter a valid email address', 'error');
                return;
            }
            
            showNotification(`Sharing ${fileName} with ${email}...`, 'info');
            
            // Create form data for email sending
            const formData = new FormData();
            formData.append('action', 'send_email_share');
            formData.append('email', email);
            formData.append('fileName', fileName);
            formData.append('filePath', filePath);
            formData.append('subject', `Document Shared: ${fileName}`);
            formData.append('message', `Hello,\n\nA document has been shared with you: ${fileName}\n\nYou can access it at: ${window.location.origin}/${filePath}\n\nBest regards,\nLILAC System`);
            
            // Send email via API
            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(`${fileName} shared successfully with ${email}!`, 'success');
                    // Close the modal
                    const modal = document.querySelector('.fixed');
                    if (modal) {
                        modal.remove();
                    }
                } else {
                    showNotification(`Failed to send email: ${data.message || 'Unknown error'}`, 'error');
                }
            })
            .catch(error => {
                console.error('Email sending error:', error);
                showNotification('Failed to send email. Please try again.', 'error');
            });
        }

        // Enhanced delete document with confirmation
        function deleteDocumentWithConfirmation(docId) {
            const doc = currentDocuments.find(d => d.id == docId);
            if (!doc) return;
            
            // Create modal with form to prevent default submission
            const modal = document.createElement('div');
            modal.id = 'delete-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Delete Document</h3>
                    </div>
                    <div class="mb-4">
                        <p class="text-gray-700">Are you sure you want to delete "${doc.document_name || doc.title || 'Untitled Document'}"?</p>
                        <p class="text-sm text-gray-500 mt-2">This action cannot be undone.</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="delete-confirm-btn-${docId}" type="button" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                        <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Get button references
            const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
            const cancelBtn = modal.querySelector('button:last-child');
            
            // Add event listeners
            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                confirmDeleteDocument(docId);
            });
            
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeDeleteModal();
            });
            
            // Keyboard event handling
            const handleKeyDown = function(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    e.stopPropagation();
                    closeDeleteModal();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    confirmDeleteDocument(docId);
                }
            };
            
            document.addEventListener('keydown', handleKeyDown);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeDeleteModal();
                }
            });
            
            // Store cleanup function on modal
            modal.cleanup = function() {
                document.removeEventListener('keydown', handleKeyDown);
            };
            
            // Focus the delete button for better accessibility
            setTimeout(() => {
                if (deleteBtn) {
                    deleteBtn.focus();
                }
            }, 100);
        }

        // Helper function to close delete modal
        function closeDeleteModal() {
            // Prefer targeted close by id, fallback to legacy content check
            const overlay = document.getElementById('delete-modal');
            if (overlay) {
                if (overlay.cleanup && typeof overlay.cleanup === 'function') {
                    overlay.cleanup();
                }
                overlay.remove();
                return;
            }
            const allModals = document.querySelectorAll('.fixed');
            allModals.forEach(modal => {
                if (modal.innerHTML.includes('Delete Document')) {
                    if (modal.cleanup && typeof modal.cleanup === 'function') {
                        modal.cleanup();
                    }
                    modal.remove();
                }
            });
        }

        // Global function to force close any stuck modals (can be called from browser console)
        window.forceCloseModals = function() {
            console.log('Force closing all modals...');
            const allModals = document.querySelectorAll('.fixed');
            console.log('Found', allModals.length, 'modals');
            allModals.forEach((modal, index) => {
                console.log('Removing modal', index, ':', modal.innerHTML.substring(0, 100) + '...');
                modal.remove();
            });
            console.log('All modals closed');
        };

        // Global function to reset any stuck delete buttons
        window.resetDeleteButtons = function() {
            console.log('Resetting all delete buttons...');
            const deleteButtons = document.querySelectorAll('button[id^="delete-confirm-btn-"]');
            deleteButtons.forEach(btn => {
                btn.disabled = false;
                btn.textContent = 'Delete';
            });
            console.log('All delete buttons reset');
        };

        // Global function to suppress all notifications temporarily
        window.suppressNotifications = function(duration = 1000) {
            // Completely disable all notifications
            if (window.lilacNotifications) {
                window.lilacNotifications.success = function() {};
                window.lilacNotifications.show = function() {};
                window.lilacNotifications.error = function() {};
                window.lilacNotifications.info = function() {};
            }
        };



        // Confirm delete document
        function confirmDeleteDocument(docId) {
            const doc = currentDocuments.find(d => d.id == docId);
            if (!doc) return;
            
            // Show loading state
            const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.textContent = 'Deleting...';
            }
            
            // Create form data for API call
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', docId);
            
            // Add timeout to prevent infinite loading
            const timeoutId = setTimeout(() => {
                console.log('Delete request timed out, forcing modal close');
                showNotification('Request timed out. Please try again.', 'error');
                
                // Force close modal
                const allModals = document.querySelectorAll('.fixed');
                allModals.forEach(modal => {
                    if (modal.innerHTML.includes('Delete Document')) {
                        modal.remove();
                    }
                });
                
                // Reset button state
                const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.textContent = 'Delete';
                }
            }, 10000); // 10 second timeout
            
            // Call the API to delete the document
            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                clearTimeout(timeoutId); // Clear timeout on response
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text().then(text => {
                    console.log('Response text:', text);
                    
                    if (!text || text.trim() === '') {
                        throw new Error('Empty response from server');
                    }
                    
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                console.log('Delete response data:', data);
                console.log('Response type:', typeof data);
                console.log('Success property:', data.success);
                
                if (data && data.success) {
                    console.log('Delete successful, closing modal...');
                    
                    // Force close the modal immediately
                    closeDeleteModal();
                    
                    // Remove the deleted document from selection if it was selected
                    selectedDocuments.delete(docId);
                    
                    // Update select all state
                    selectAllChecked = selectedDocuments.size === currentDocuments.length;
                    
                    // Reload documents to reflect changes (silently)
                    loadDocuments();
                    loadStats();
                    
                    // Completely suppress any notifications for delete operations
                    if (window.lilacNotifications) {
                        // Store original methods
                        const originalSuccess = window.lilacNotifications.success;
                        const originalShow = window.lilacNotifications.show;
                        
                        // Disable all notification methods
                        window.lilacNotifications.success = function() {};
                        window.lilacNotifications.show = function() {};
                        
                        // Re-enable after a longer delay to ensure no notifications show
                        setTimeout(() => {
                            window.lilacNotifications.success = originalSuccess;
                            window.lilacNotifications.show = originalShow;
                        }, 500);
                    }
                } else {
                    console.log('Delete failed:', data);
                    showNotification(data.message || 'Error deleting document', 'error');
                    
                    // Reset button state
                    const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
                    if (deleteBtn) {
                        deleteBtn.disabled = false;
                        deleteBtn.textContent = 'Delete';
                    }
                }
            })
            .catch(error => {
                clearTimeout(timeoutId); // Clear timeout on error
                console.error('Error deleting document:', error);
                showNotification('Network error. Please try again.', 'error');
                
                // Reset button state
                const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.textContent = 'Delete';
                }
            });
        }

        // Show share modal
        function showShareModal() {
            const modal = document.createElement('div');
            modal.id = 'share-documents-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Share Documents</h3>
                        <button id="close-share-modal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Documents</label>
                            <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-2">
                                ${currentDocuments.slice(0, 5).map(doc => `
                                    <label class="flex items-center p-2 hover:bg-gray-50 rounded">
                                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="${doc.id}">
                                        <span class="ml-2 text-sm text-gray-700">${doc.document_name || doc.title || 'Untitled Document'}</span>
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Share with</label>
                            <input type="email" placeholder="Enter email addresses" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message (optional)</label>
                            <textarea rows="3" placeholder="Add a message..." class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button id="share-button" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Share
                            </button>
                            <button id="cancel-share-button" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add event listeners after modal is added to DOM
            setTimeout(() => {
                const shareButton = document.getElementById('share-button');
                const cancelButton = document.getElementById('cancel-share-button');
                const closeButton = document.getElementById('close-share-modal');
                
                if (shareButton) {
                    console.log('Adding click listener to share button');
                    shareButton.addEventListener('click', function(e) {
                        console.log('Share button clicked!');
                        e.preventDefault();
                        e.stopPropagation();
                        shareSelectedDocuments();
                    });
                } else {
                    console.error('Share button not found');
                }
                
                if (cancelButton) {
                    cancelButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        modal.remove();
                    });
                }
                
                if (closeButton) {
                    closeButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        modal.remove();
                    });
                }
            }, 100);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Share selected documents
        function shareSelectedDocuments() {
            console.log('=== SHARE SELECTED DOCUMENTS FUNCTION CALLED ===');
            
            const modal = document.getElementById('share-documents-modal');
            if (!modal) {
                console.error('Share modal not found');
                showNotification('Share modal not found', 'error');
                return;
            }
            
            const selectedCheckboxes = modal.querySelectorAll('input[type="checkbox"]:checked');
            const emailInput = modal.querySelector('input[type="email"]');
            const messageInput = modal.querySelector('textarea');
            const shareButton = document.getElementById('share-button');
            
            console.log('Found elements:', {
                modal: !!modal,
                selectedCheckboxes: selectedCheckboxes.length,
                emailInput: !!emailInput,
                messageInput: !!messageInput
            });
            
            // Validate that at least one document is selected
            if (selectedCheckboxes.length === 0) {
                showNotification('Please select at least one document to share', 'error');
                return;
            }
            
            // Validate email address
            const email = emailInput.value.trim();
            if (!email) {
                showNotification('Please enter an email address', 'error');
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('Please enter a valid email address', 'error');
                return;
            }
            
            // Get selected document IDs
            const selectedDocumentIds = Array.from(selectedCheckboxes).map(cb => cb.value);
            
            // Get optional message
            const message = messageInput.value.trim();
            
            // Show loading state
            if (shareButton) {
                shareButton.disabled = true;
                shareButton.textContent = 'Sending...';
                shareButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
            
            // Prepare request payload
            const payload = {
                documents: selectedDocumentIds,
                email: email,
                message: message
            };
            
            console.log('Sending share request with payload:', payload);
            
            // Call backend API
            fetch('api/documents.php?action=send_email_share', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                console.log('API response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('API response data:', data);
                
                // Reset button state
                if (shareButton) {
                    shareButton.disabled = false;
                    shareButton.textContent = 'Share';
                    shareButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                if (data.success) {
                    if (data.local_dev) {
                        showNotification(`Documents prepared for sharing! Email content saved to ${data.log_file} (local development mode)`, 'success');
                        modal.remove();
                    } else {
                        showNotification(`Documents shared successfully! Email sent to ${email}`, 'success');
                        modal.remove();
                    }
                } else {
                    showNotification(`Failed to send email: ${data.message || 'Unknown error'}`, 'error');
                }
            })
            .catch(error => {
                console.error('Share error:', error);
                
                // Reset button state
                if (shareButton) {
                    shareButton.disabled = false;
                    shareButton.textContent = 'Share';
                    shareButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                showNotification('Failed to send email. Please try again.', 'error');
            });
        }


    </script>
</head>

<body class="bg-[#F8F8FF]">
    <!-- Document Viewer Modal -->
    <div id="document-viewer-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[80vh] flex flex-col">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h3 id="document-viewer-title" class="text-lg font-semibold text-gray-900"></h3>
                    <div class="flex items-center gap-2">
                        <button id="document-viewer-open" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Open in New Tab</button>
                        <button onclick="document.getElementById('document-viewer-overlay').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 bg-gray-50 p-2 overflow-y-auto overflow-x-hidden min-h-0">
                    <div id="document-viewer-content" class="w-full h-full overflow-y-auto overflow-x-hidden"></div>
                </div>
                <div class="flex items-center justify-end gap-2 px-4 py-3 border-t">
                    <button id="document-viewer-download" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Download</button>
                    <button onclick="document.getElementById('document-viewer-overlay').classList.add('hidden')" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between pl-64 relative transition-all duration-300 ease-in-out">
        <div class="flex items-center space-x-4">
            <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <a href="dashboard.php" class="flex items-center space-x-3 hover:opacity-80 transition-opacity cursor-pointer">
            </a>
        </div>

        <div class="absolute left-1/2 transform -translate-x-1/2">
            <h1 class="text-xl font-bold text-gray-800 cursor-pointer" onclick="location.reload()">Documents</h1>
        </div>

        <div class="text-sm flex items-center space-x-4">
            <!-- View Toggle Buttons -->
            <div class="flex items-center bg-gray-100 rounded-lg p-1">
                <button data-view="grid" class="p-2 rounded-md text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </button>
                <button data-view="list" class="p-2 rounded-md bg-gray-200 text-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-documents" name="search-documents"
                       class="block w-64 pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm"
                       placeholder="Search">
                <button type="button" id="clear-search" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 hidden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Create Button -->
            <button onclick="openDocumentEditor()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
                Create
            </button>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

        	<!-- Main Content -->
	<div id="main-content" class="ml-64 p-4 pt-3 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out">
		<!-- Content Area -->
		<div class="">
            <!-- Documents Container -->
            <div id="documents-container" class="bg-white bg-opacity-80 backdrop-blur-xl rounded-3xl border border-white border-opacity-30 overflow-hidden shadow-2xl">
                <!-- Bulk Actions Bar -->
                <div id="bulk-actions-bar" class="hidden bg-blue-50 border-b border-blue-200 px-6 py-3 rounded-t-3xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm text-blue-700 font-medium">
                                <span id="selected-count">0</span> document<span id="selected-plural">s</span> selected
                            </span>
                        </div>
                        <div class="flex items-center">
                            <button id="bulk-delete-btn" onclick="bulkDeleteSelected()" class="bg-red-600 text-white px-3 py-1.5 rounded-md hover:bg-red-700 transition-colors text-sm font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- List View -->
                                    <div id="list-view" class="overflow-x-auto" style="min-height: 550px;">
                        <table class="w-full table-fixed">
                        <thead class="bg-gray-50 border-b border-gray-200 rounded-t-3xl">
                            <tr>
                                <th class="w-16 px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all-checkbox" onclick="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="w-[40%] px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="w-[12%] px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="w-[12%] px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="w-[24%] px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="w-[12%] px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="documents-table-body" class="bg-white divide-y divide-gray-200 min-h-[400px]">
                            <!-- Documents will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Grid View -->
                <div id="grid-view" class="hidden rounded-b-3xl min-h-[400px]">
                    <!-- Grid View Header with Select All -->
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="grid-select-all-checkbox" onclick="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span id="grid-select-all-text" class="text-sm font-medium text-gray-700 hidden">Select All</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span id="grid-selected-count">0</span> selected
                            </div>
                        </div>
                    </div>
                    <!-- Grid Content -->
                    <div class="p-6">
                        <div id="documents-grid-body" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <!-- Documents will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
    </div>


    <!-- Floating Upload Documents Button Above Footer -->
    <div class="fixed bottom-20 right-4 z-50">
        <button id="view-switch-btn" aria-label="Upload Documents" class="bg-purple-600 text-white w-12 h-12 rounded-full shadow-lg hover:bg-purple-700 transition-all duration-300 transform hover:scale-105 flex items-center justify-center" onclick="showUploadModal()">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
        </button>
    </div>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hamburger button toggles sidebar
            var hamburger = document.getElementById('hamburger-toggle');
            if (hamburger) {
                hamburger.addEventListener('click', function() {
                    try {
                        window.dispatchEvent(new CustomEvent('sidebar:toggle'));
                    } catch (e) {}
                });
            }

            // Responsive floating button on scroll
            let lastScrollTop = 0;
            const floatingBtn = document.getElementById('view-switch-btn');
            const floatingBtnContainer = floatingBtn?.parentElement;
            
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (floatingBtnContainer) {
                    if (scrollTop > lastScrollTop && scrollTop > 100) {
                        // Scrolling down - move button up (current position above footer)
                        floatingBtnContainer.style.bottom = '80px'; // bottom-20 equivalent
                        floatingBtnContainer.style.transition = 'bottom 0.3s ease';
                    } else {
                        // Scrolling up - move button down (old position at bottom)
                        floatingBtnContainer.style.bottom = '16px'; // bottom-4 equivalent
                        floatingBtnContainer.style.transition = 'bottom 0.3s ease';
                    }
                }
                
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            });
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

</body>

</html>
