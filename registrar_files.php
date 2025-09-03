<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Registrar Files</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script>
        // Initialize Registrar Files functionality
        let currentDocuments = [];
        let showExpiringOnly = false;
        const CATEGORY = 'Registrar Files';

        document.addEventListener('DOMContentLoaded', function() {
            loadDocuments();
            loadStats();
            initializeEventListeners();
            updateCurrentDate();
            
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



        function initializeEventListeners() {
            // Form submission
            const form = document.getElementById('registrar-form');
            if (form) {
                form.addEventListener('submit', handleFormSubmit);
            }

            // Clear form functionality
            const clearFormBtn = document.getElementById('clear-form');
            if (clearFormBtn) {
                clearFormBtn.addEventListener('click', function() {
                    document.getElementById('registrar-form').reset();
                    showNotification('Form cleared', 'info');
                });
            }

            // Search and filter functionality
            const searchInput = document.getElementById('search-registrar');
            if (searchInput) {
                searchInput.addEventListener('input', filterDocuments);
            }

            // Filter functionality
            const filterSelect = document.getElementById('filter-type');
            if (filterSelect) {
                filterSelect.addEventListener('change', filterDocuments);
            }

            // Sort functionality
            const sortSelect = document.getElementById('sort-files');
            if (sortSelect) {
                sortSelect.addEventListener('change', sortDocuments);
            }

            // Reset filters functionality
            const resetFiltersBtn = document.getElementById('reset-filters');
            if (resetFiltersBtn) {
                resetFiltersBtn.addEventListener('click', function() {
                    document.getElementById('search-registrar').value = '';
                    document.getElementById('filter-type').value = 'all';
                    document.getElementById('sort-files').value = 'date-new';
                    displayDocuments(currentDocuments);
                    showNotification('Filters reset', 'info');
                });
            }

            // Delete modal event listeners
            const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            if (closeDeleteModalBtn) {
                closeDeleteModalBtn.addEventListener('click', hideDeleteModal);
            }
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', hideDeleteModal);
            }
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', confirmDelete);
            }

            // View file modal event listener
            const closeViewFileModalBtn = document.getElementById('closeViewFileModalBtn');
            if (closeViewFileModalBtn) {
                closeViewFileModalBtn.addEventListener('click', closeViewFileModal);
            }
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'add');
            
            // Set category to "Registrar Files" as the main category
            formData.set('category', 'Registrar Files');
            
            // Use file_title as document_name for API consistency
            const fileTitle = formData.get('file_title');
            formData.set('document_name', fileTitle);
            
            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset form
                    e.target.reset();
                    
                    // Refresh display
                    loadDocuments();
                    loadStats();
                    showNotification('Registrar file uploaded successfully!', 'success');
                } else {
                    showNotification(data.message || 'Error uploading file', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
            });
        }

        function loadDocuments() {
            fetch(`api/documents.php?action=get_by_category&category=${encodeURIComponent(CATEGORY)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentDocuments = data.documents;
                        displayDocuments(data.documents);
                    }
                })
                .catch(error => console.error('Error loading documents:', error));
        }

        function loadStats() {
            fetch(`api/documents.php?action=get_stats_by_category&category=${encodeURIComponent(CATEGORY)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStats(data.stats);
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        }

        function updateStats(stats) {
            const totalFilesElement = document.getElementById('total-files');
            const recentFilesElement = document.getElementById('recent-files');
            const fileTypesElement = document.getElementById('file-types');
            
            if (totalFilesElement) {
                totalFilesElement.textContent = stats.total || 0;
            }
            
            if (recentFilesElement) {
                // Count files from last 30 days
                const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
                const recentCount = currentDocuments.filter(doc => 
                    new Date(doc.date_added) > thirtyDaysAgo
                ).length;
                recentFilesElement.textContent = recentCount;
            }
            
            if (fileTypesElement) {
                // Find most common document type
                const typeCounts = {};
                currentDocuments.forEach(doc => {
                    const category = doc.category || 'Other';
                    typeCounts[category] = (typeCounts[category] || 0) + 1;
                });
                
                const mostCommon = Object.keys(typeCounts).reduce((a, b) => 
                    typeCounts[a] > typeCounts[b] ? a : b, 'None'
                );
                
                fileTypesElement.textContent = mostCommon;
            }
        }

        function filterDocuments() {
            const searchTerm = document.getElementById('search-registrar').value.toLowerCase();
            const filterType = document.getElementById('filter-type').value;

            let filtered = currentDocuments.filter(file => {
                const matchesSearch = file.title.toLowerCase().includes(searchTerm) ||
                       (file.description && file.description.toLowerCase().includes(searchTerm)) ||
                       file.category.toLowerCase().includes(searchTerm) ||
                       file.file_name.toLowerCase().includes(searchTerm);
                       
                const matchesFilter = filterType === 'all' || file.category === filterType;
                
                return matchesSearch && matchesFilter;
            });

            displayDocuments(filtered);
        }

        function sortDocuments() {
            const sortBy = document.getElementById('sort-files').value;
            let sortedDocuments = [...currentDocuments]; // Use API data instead of localStorage

            sortedDocuments.sort((a, b) => {
                switch (sortBy) {
                    case 'title':
                        return a.title.localeCompare(b.title);
                    case 'type':
                        return a.category.localeCompare(b.category);
                    case 'size':
                        const sizeA = parseFloat(a.file_size) || 0;
                        const sizeB = parseFloat(b.file_size) || 0;
                        return sizeB - sizeA;
                    case 'accessed':
                        return (b.access_count || 0) - (a.access_count || 0);
                    case 'date-old':
                        return new Date(a.date_added) - new Date(b.date_added);
                    case 'date-new':
                    default:
                        return new Date(b.date_added) - new Date(a.date_added);
                }
            });

            displayDocuments(sortedDocuments);
        }

        function getTypeIcon(type) {
            const icons = {
                'Grade Report': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a2 2 0 002 2h4a2 2 0 002-2V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"></path></svg>',
                'Enrollment Form': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>',
                'Certificate': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2L3 7v11a1 1 0 001 1h12a1 1 0 001-1V7l-7-5zM8.5 13a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0z" clip-rule="evenodd"></path></svg>',
                'Transcript': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>',
                'Other': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm8 8v2h1v-2h-1zM6 7v1h1V7H6zm1 3v1h1v-1H7zm1 2H7v1h1v-1z" clip-rule="evenodd"></path></svg>'
            };
            return icons[type] || icons['Other'];
        }

        function getTypeColor(type) {
            const colors = {
                'Grade Report': 'bg-blue-100 text-blue-600',
                'Enrollment Form': 'bg-green-100 text-green-600',
                'Certificate': 'bg-yellow-100 text-yellow-600',
                'Transcript': 'bg-purple-100 text-purple-600',
                'Other': 'bg-gray-100 text-gray-600'
            };
            return colors[type] || colors['Other'];
        }

        function getTypeBadgeColor(type) {
            const colors = {
                'Grade Report': 'bg-blue-100 text-blue-800',
                'Enrollment Form': 'bg-green-100 text-green-800',
                'Certificate': 'bg-yellow-100 text-yellow-800',
                'Transcript': 'bg-purple-100 text-purple-800',
                'Other': 'bg-gray-100 text-gray-800'
            };
            return colors[type] || colors['Other'];
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

        function viewFile(id) {
            const file = currentDocuments.find(f => f.id == id);
            if (file) {
                // Populate modal with file details
                document.getElementById('viewFileTitle').textContent = file.title;
                document.getElementById('viewFileCategory').textContent = file.category;
                document.getElementById('viewFileSize').textContent = formatFileSize(file.file_size);
                document.getElementById('viewFileDateAdded').textContent = new Date(file.date_added).toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric'
                });
                document.getElementById('viewFileDescription').textContent = file.description || 'No description provided.';
                
                // Handle file download section
                const fileSection = document.getElementById('viewFileDownload');
                if (file.file_name) {
                    fileSection.innerHTML = `
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">${file.file_name}</p>
                                <p class="text-sm text-gray-500">Registrar file • ${formatFileSize(file.file_size)}</p>
                            </div>
                            <button onclick="downloadFileFromModal(${file.id}, '${file.file_name}')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                                Download
                            </button>
                        </div>
                    `;
                } else {
                    fileSection.innerHTML = `
                        <div class="text-center py-6 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <p>No file uploaded</p>
                        </div>
                    `;
                }
                
                // Show modal
                document.getElementById('viewFileModal').classList.remove('hidden');
            }
        }

        function closeViewFileModal() {
            document.getElementById('viewFileModal').classList.add('hidden');
        }

        function downloadFileFromModal(id, fileName) {
            showNotification(`Downloading ${fileName}...`, 'info');
            // In a real implementation, this would trigger the actual file download
            // window.open(`api/registrar_files.php?action=download&id=${id}`, '_blank');
        }

        function downloadFile(id) {
            const file = currentDocuments.find(f => f.id == id);
            if (file) {
                showNotification(`Downloading ${file.title}...`, 'info');
            }
        }

        // Delete modal functionality
        let fileToDelete = null;

        function showDeleteModal(id, fileName) {
            fileToDelete = id;
            document.getElementById('fileToDeleteName').textContent = `File: "${fileName}"`;
            document.getElementById('deleteConfirmModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            fileToDelete = null;
            document.getElementById('deleteConfirmModal').classList.add('hidden');
        }

        function confirmDelete() {
            if (fileToDelete) {
                deleteFile(fileToDelete);
            }
        }

        function deleteFile(id) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadDocuments();
                    loadStats();
                    hideDeleteModal(); // Close the modal after successful deletion
                    showNotification('File deleted successfully', 'success');
                } else {
                    showNotification(data.message || 'Error deleting file', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
            });
        }

        function showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Slide in
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            
            // Slide out and remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function formatFileSize(bytes) {
            if (!bytes || bytes === 0) return 'Unknown size';
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
        }

        function initializeModalEventListeners() {
            // Delete modal event listeners
            const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            if (closeDeleteModalBtn) {
                closeDeleteModalBtn.addEventListener('click', hideDeleteModal);
            }
            
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', hideDeleteModal);
            }
            
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', confirmDelete);
            }
        }

        function displayDocuments(documents) {
            const container = document.getElementById('files-container');
            
            // --- TABLE LAYOUT ONLY ---
            let tableHTML = `<div class="overflow-x-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        File Name
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        Type
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Description
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        File Size
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Date Added
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                        </svg>
                                        Actions
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">`;
            
            if (documents.length === 0) {
                tableHTML += `<tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No registrar files yet</h3>
                            <p class="text-gray-500 mb-4">Upload your first registrar file to get started</p>
                            <button onclick="document.getElementById('file-name').focus()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                Upload File
                            </button>
                        </div>
                    </td>
                </tr>`;
            } else {
                tableHTML += documents.map(doc => {
                    const addedDate = new Date(doc.date_added);
                    const formattedDate = addedDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                    
                    return `<tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${doc.title || doc.document_name || 'Untitled File'}</div>
                                    <div class="text-sm text-gray-500">${doc.file_name}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getTypeBadgeColor(doc.category)}">${doc.category}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate" title="${doc.description || ''}">${doc.description && doc.description.trim() && doc.description !== '' ? (doc.description.length > 50 ? doc.description.substring(0, 50) + '...' : doc.description) : 'No description available'}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">${formatFileSize(doc.file_size || 0)}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${formattedDate}</div>
                            <div class="text-xs text-gray-500">${getTimeAgo(addedDate)}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <button onclick="viewFile(${doc.id})" class="text-blue-600 hover:text-blue-900 font-medium flex items-center" title="View Details">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </button>
                                <button onclick="downloadFile(${doc.id})" class="text-green-600 hover:text-green-900 font-medium flex items-center" title="Download">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download
                                </button>
                                <button onclick="showDeleteModal(${doc.id}, '${(doc.title || doc.document_name || 'Untitled File').replace(/'/g, "\\'")}')" class="text-red-600 hover:text-red-900 font-medium flex items-center" title="Delete">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            }
            tableHTML += `</tbody></table></div></div>`;

            container.innerHTML = tableHTML;
        }

        function getTypeBadgeColor(category) {
            const colors = {
                'Grade Report': 'bg-blue-100 text-blue-800',
                'Enrollment Form': 'bg-green-100 text-green-800',
                'Certificate': 'bg-purple-100 text-purple-800',
                'Transcript': 'bg-yellow-100 text-yellow-800',
                'Other': 'bg-gray-100 text-gray-800'
            };
            return colors[category] || colors['Other'];
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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

        function viewFile(id) {
            showNotification('View file functionality - to be implemented', 'info');
        }

        function downloadFile(id) {
            showNotification('Download file functionality - to be implemented', 'info');
        }

        function showDeleteModal(id, fileName) {
            showNotification('Delete file functionality - to be implemented', 'info');
        }

        function closeViewFileModal() {
            document.getElementById('viewFileModal').classList.add('hidden');
        }

        function showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Slide in
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            
            // Slide out and remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</head>

<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between">
        <button id="menu-toggle" onclick="toggleMenu()" class="md:hidden p-2 rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        <h1 class="text-xl font-bold">LILAC Registrar Files</h1>
        <div class="text-sm flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span id="current-date"></span>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-0 md:ml-64 p-6 pt-20 min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <!-- Header Section -->
        <div class="mb-6">
            <div class="relative overflow-hidden bg-gradient-to-r from-red-600 via-rose-600 to-pink-600 text-white rounded-2xl p-4 shadow-xl">
                <div class="absolute inset-0 bg-black opacity-10"></div>
                <div class="absolute -top-2 -right-2 w-16 h-16 bg-white opacity-10 rounded-full"></div>
                <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-white opacity-5 rounded-full"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-black mb-2 bg-gradient-to-r from-white to-red-100 bg-clip-text text-transparent">
                                Registrar Center
                            </h1>
                            <p class="text-red-100 text-base font-medium">Upload • Archive • Manage</p>
                        </div>
                        <div class="hidden md:block">
                            <div class="w-16 h-16 bg-gradient-to-br from-white to-red-200 rounded-full opacity-20 animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600">Total Files</p>
                <p class="text-2xl font-bold text-gray-900" id="total-files">0</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600">Recent (30 days)</p>
                <p class="text-2xl font-bold text-gray-900" id="recent-files">0</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600">Most Common Type</p>
                <p class="text-sm font-bold text-gray-900" id="file-types">None</p>
            </div>
        </div>

        <!-- Upload File Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Upload Registrar File</h2>
            <form id="file-form" class="space-y-6">
                <div>
                    <label for="file-name" class="block text-sm font-medium text-gray-700 mb-2">File Name *</label>
                    <input type="text" id="file-name" name="file-name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors"
                           placeholder="Enter file name">
                </div>
                <div>
                    <label for="file-type" class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                    <select id="file-type" name="file-type" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors">
                        <option value="Grade Report">Grade Report</option>
                        <option value="Enrollment Form">Enrollment Form</option>
                        <option value="Certificate">Certificate</option>
                        <option value="Transcript">Transcript</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label for="file-description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="file-description" name="file-description" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors"
                              placeholder="Enter file description (optional)"></textarea>
                </div>
                <div>
                    <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">Upload File *</label>
                    <input type="file" id="file-upload" name="file-upload" required
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-black file:text-white hover:file:bg-gray-800">
                    <p class="mt-2 text-sm text-gray-500">Supported formats: PDF, DOC, DOCX, JPG, PNG (Max 10MB)</p>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800 focus:ring-2 focus:ring-black focus:ring-offset-2 transition-colors font-medium">
                        Upload File
                    </button>
                </div>
            </form>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-col lg:flex-row gap-4 items-end">
                <!-- Search Bar -->
                <div class="flex-1">
                    <label for="search-registrar" class="block text-sm font-medium text-gray-700 mb-2">Search Files</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-registrar" name="search-registrar"
                               class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="Search by name, type, or description...">
                    </div>
                </div>
                
                <!-- Type Filter -->
                <div class="md:w-56">
                    <label for="filter-type" class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                    <select id="filter-type" name="filter-type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        <option value="all">All Types</option>
                        <option value="Grade Report">Grade Report</option>
                        <option value="Enrollment Form">Enrollment Form</option>
                        <option value="Certificate">Certificate</option>
                        <option value="Transcript">Transcript</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <!-- Sort Options -->
                <div class="md:w-56">
                    <label for="sort-files" class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                    <select id="sort-files" name="sort-files"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        <option value="date-new">Newest First</option>
                        <option value="date-old">Oldest First</option>
                        <option value="title">Title A-Z</option>
                        <option value="type">Type A-Z</option>
                        <option value="size">File Size</option>
                    </select>
                </div>
                
                <!-- Reset Filters Button -->
                <div>
                    <button type="button" id="reset-filters"
                            class="px-4 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Files Grid -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Your Registrar Files</h2>
            </div>
            <div class="p-6">
                <div id="files-container" class="grid grid-cols-1 gap-4">
                    <!-- Files will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Footer -->
    <footer class="ml-0 md:ml-64 bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Confirm Deletion</h2>
                <button id="closeDeleteModalBtn" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-6">
                <p class="text-gray-700">Are you sure you want to delete this file? This action cannot be undone.</p>
                <p id="fileToDeleteName" class="font-semibold text-gray-700 mt-2"></p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelDeleteBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Delete</button>
            </div>
        </div>
    </div>

         <!-- View File Modal -->
     <div id="viewFileModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50">
         <div class="relative p-8 bg-white w-full max-w-2xl m-auto flex-col flex rounded-lg shadow-lg">
             <div class="flex justify-between items-center mb-6">
                 <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
                     <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                     </svg>
                     Registrar File Details
                 </h2>
                 <button onclick="closeViewFileModal()" class="text-gray-400 hover:text-gray-600">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                     </svg>
                 </button>
             </div>
             
             <div class="grid grid-cols-2 gap-6 mb-6">
                 <div>
                     <h3 class="text-sm font-medium text-gray-500 mb-1">File Title</h3>
                     <p id="viewFileTitle" class="text-lg font-semibold text-gray-900"></p>
                 </div>
                 <div>
                     <h3 class="text-sm font-medium text-gray-500 mb-1">Category</h3>
                     <p id="viewFileCategory" class="text-lg text-gray-700"></p>
                 </div>
                 <div>
                     <h3 class="text-sm font-medium text-gray-500 mb-1">File Size</h3>
                     <p id="viewFileSize" class="text-lg text-gray-700"></p>
                 </div>
                 <div>
                     <h3 class="text-sm font-medium text-gray-500 mb-1">Date Added</h3>
                     <p id="viewFileDateAdded" class="text-lg text-gray-700"></p>
                 </div>
             </div>
             
             <div class="mb-6">
                 <h3 class="text-sm font-medium text-gray-500 mb-2">Description</h3>
                 <p id="viewFileDescription" class="text-gray-700 leading-relaxed"></p>
             </div>
             
             <div class="mb-6">
                 <h3 class="text-lg font-semibold text-gray-800 mb-3">File Download</h3>
                 <div id="viewFileDownload">
                     <!-- File download section will be populated here -->
                 </div>
             </div>
             
             <div class="flex justify-end mt-6">
                 <button onclick="closeViewFileModal()" class="px-6 py-2 text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 rounded-lg">
                     Close
                 </button>
            </div>
        </div>
    </div>

    <script>
        // Mobile navigation function
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('menu-overlay');
            
            if (sidebar && overlay) {
                const isHidden = sidebar.classList.contains('-translate-x-full');
                
                if (isHidden) {
                    // Show menu
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                } else {
                    // Hide menu
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                }
            }
        }

        // Setup mobile navigation when page loads
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
</body>

</html>
