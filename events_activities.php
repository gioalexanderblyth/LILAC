<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Events & Activities</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script>
        // Mobile nav handled below; removing unused duplicate toggleMenu()
        
        // Global variable to store current documents
        let currentDocuments = [];
        const CATEGORY = 'Events & Activities';

        document.addEventListener('DOMContentLoaded', function() {
            loadDocuments();
            loadStats();
            // Data loading disabled per request
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
            
            // Set today's date as default in the form
            const today = new Date();
            const eventDateInput = document.getElementById('event-date');
            if (eventDateInput) {
                eventDateInput.valueAsDate = today;
            }
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

        function loadDocuments() {
            fetch(`api/documents.php?action=get_by_category&category=${encodeURIComponent(CATEGORY)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentDocuments = data.documents;
                        if (!Array.isArray(currentDocuments) || currentDocuments.length === 0) {
                            currentDocuments = [
                                { id: 1, document_name: 'Learn The Alphabets', upload_date: new Date().toISOString(), description: '60 minutes', organizer: 'Language Dept' },
                                { id: 2, document_name: 'Touch The Grass', upload_date: new Date().toISOString(), description: '23 minutes', organizer: 'Env Club' },
                                { id: 3, document_name: 'Practice, Practice, Practice', upload_date: new Date().toISOString(), description: '112 minutes' },
                                { id: 4, document_name: 'Just Do It', upload_date: new Date().toISOString(), description: '99 minutes' },
                            ];
                        }
                        selectEvent(currentDocuments[0]?.id || currentDocuments[0]?.event_id);
                        displayCourseContent(currentDocuments);
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
                    } else {
                        // Show demo stats if no real stats available
                        updateStats({ total: currentDocuments.length || 4, recent: 2, month: 3 });
                    }
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                    // Show demo stats on error
                    updateStats({ total: currentDocuments.length || 4, recent: 2, month: 3 });
                });
        }

        function updateStats(stats) {
            const totalEventsElement = document.getElementById('total-events');
            const recentEventsElement = document.getElementById('recent-events');
            const monthEventsElement = document.getElementById('month-events');
            
            if (totalEventsElement) {
                totalEventsElement.textContent = stats.total;
            }
            if (recentEventsElement) {
                recentEventsElement.textContent = stats.recent;
            }
            if (monthEventsElement) {
                monthEventsElement.textContent = stats.month || 0;
            }
        }

        function displayDocuments(documents) {
            const container = document.getElementById('events-container');
            
            if (documents.length === 0) {
                container.innerHTML = `<div class="text-center py-12">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No events yet</h3>
                        <p class="text-gray-500 mb-4">Add your first event to get started</p>
                        <button onclick="document.getElementById('event-title').focus()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                            Add Event
                        </button>
                    </div>
                </div>`;
            } else {
                // Display events in a simple table format
                let tableHTML = `<div class="overflow-x-auto">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            Event Name
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Event Date
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            Organizer
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Venue
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                            </svg>
                                            Actions
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">`;
                
                tableHTML += documents.map(doc => {
                    const eventDate = new Date(doc.event_date || doc.upload_date);
                    const formattedDate = eventDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                    
                    return `<tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${doc.event_name || doc.document_name || 'Untitled Event'}</div>
                                    <div class="text-sm text-gray-500">Event ID: ${doc.event_id || doc.id}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">${formattedDate}</div>
                            <div class="text-sm text-gray-500">${getTimeAgo(eventDate)}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${doc.organizer || 'No organizer specified'}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${doc.venue || 'No venue specified'}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate" title="${doc.description || ''}">${doc.description && doc.description.trim() && doc.description !== '' ? (doc.description.length > 50 ? doc.description.substring(0, 50) + '...' : doc.description) : 'No description available'}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <button onclick="viewDocument(${doc.event_id || doc.id})" class="text-blue-600 hover:text-blue-900 font-medium flex items-center" title="View Details">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </button>
                                <button onclick="downloadDocument(${doc.event_id || doc.id})" class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center" title="Download">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Download
                                </button>
                                <button onclick="showDeleteDocumentModal(${doc.event_id || doc.id}, '${(doc.event_name || doc.document_name || 'Untitled Event').replace(/'/g, "\\'")}')" class="text-red-600 hover:text-red-900 font-medium flex items-center" title="Delete">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
                
                tableHTML += `</tbody></table></div></div>`;
                container.innerHTML = tableHTML;
            }
        }

        function addDocument() {
            const documentName = document.getElementById('event-title').value.trim();
            const eventDate = document.getElementById('event-date').value;
            const eventTime = document.getElementById('event-time').value;
            const organizer = document.getElementById('event-organizer') ? document.getElementById('event-organizer').value.trim() : '';
            const venue = document.getElementById('event-location').value.trim();
            const description = document.getElementById('event-description').value.trim();
            const fileInput = document.getElementById('event-file');

            if (!documentName || !eventDate || !fileInput.files[0]) {
                alert('Please fill in required fields (title, date, and file)');
                return;
            }

            // Show confirmation modal
            showAddDocumentConfirmModal(documentName, eventDate, eventTime, organizer, venue, description, fileInput.files[0]);
        }

        function showAddDocumentConfirmModal(title, date, time, organizer, venue, description, file) {
            // Populate modal with document details
            document.getElementById('confirmEventTitle').textContent = title;
            document.getElementById('confirmEventDate').textContent = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric', 
                month: 'long', 
                day: 'numeric'
            });
            document.getElementById('confirmEventTime').textContent = time;
            document.getElementById('confirmEventLocation').textContent = venue || 'No location specified';
            document.getElementById('confirmEventOrganizer').textContent = organizer || 'No organizer specified';
            document.getElementById('confirmEventDescription').textContent = description || 'No description provided.';
            document.getElementById('confirmEventFile').textContent = file.name;
            
            // Show modal
            document.getElementById('addEventConfirmModal').classList.remove('hidden');
        }

        function hideAddEventConfirmModal() {
            document.getElementById('addEventConfirmModal').classList.add('hidden');
        }

        function confirmAddDocument() {
            const documentName = document.getElementById('event-title').value.trim();
            const eventDate = document.getElementById('event-date').value;
            const eventTime = document.getElementById('event-time').value;
            const organizer = document.getElementById('event-organizer') ? document.getElementById('event-organizer').value.trim() : '';
            const venue = document.getElementById('event-location').value.trim();
            const description = document.getElementById('event-description').value.trim();
            const fileInput = document.getElementById('event-file');
            const file = fileInput.files[0];

            // Create full description with event details
            let fullDescription = description;
            if (eventTime) fullDescription += `\nTime: ${eventTime}`;
            if (organizer) fullDescription += `\nOrganizer: ${organizer}`;
            if (venue) fullDescription += `\nVenue: ${venue}`;
            if (eventDate) fullDescription += `\nDate: ${eventDate}`;

            // Add document via API
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('document_name', documentName);
            formData.append('category', CATEGORY);
            formData.append('description', fullDescription);
            formData.append('file_name', file.name);
            formData.append('file_size', file.size);

            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage('Event/Activity document added successfully!');
                    document.getElementById('event-form').reset();
                    loadDocuments();
                    loadStats();
                    hideAddEventConfirmModal();
                } else {
                    alert('Error adding document: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Network error. Please try again.');
                console.error(error);
            });
        }

        function showSuccessMessage(message) {
            // Create a temporary success message
            const successDiv = document.createElement('div');
            successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2';
            successDiv.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>${message}</span>
            `;
            document.body.appendChild(successDiv);
            
            // Remove after 3 seconds
            setTimeout(() => {
                if (successDiv.parentNode) {
                    successDiv.parentNode.removeChild(successDiv);
                }
            }, 3000);
        }

        function viewDocument(id) {
            const doc = currentDocuments.find(d => d.id == id);
            if (doc) {
                // Populate modal with document details
                document.getElementById('viewEventTitle').textContent = doc.document_name;
                document.getElementById('viewEventDate').textContent = new Date(doc.upload_date).toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric'
                });
                document.getElementById('viewEventLocation').textContent = doc.filename;
                document.getElementById('viewEventOrganizer').textContent = doc.category;
                document.getElementById('viewEventDescription').textContent = doc.description || 'No description provided.';
                
                // Show modal
                document.getElementById('viewEventModal').classList.remove('hidden');
            } else {
                alert('Document not found.');
            }
        }

        function closeViewEventModal() {
            document.getElementById('viewEventModal').classList.add('hidden');
        }

        function downloadDocument(id) {
            const doc = currentDocuments.find(d => d.id == id);
            if (doc) {
                showSuccessMessage(`Downloading ${doc.filename}...`);
                // In a real implementation, this would trigger the actual file download
                // window.open(`api/documents.php?action=download&id=${id}`, '_blank');
            }
        }

        function showDeleteDocumentModal(id, documentName) {
            document.getElementById('deleteEventName').textContent = documentName;
            document.getElementById('deleteEventModal').classList.remove('hidden');
            // Store the document ID for deletion
            document.getElementById('deleteEventModal').setAttribute('data-document-id', id);
        }

        function hideDeleteEventModal() {
            document.getElementById('deleteEventModal').classList.add('hidden');
        }

        function confirmDeleteDocument() {
            const documentId = document.getElementById('deleteEventModal').getAttribute('data-document-id');
            if (documentId) {
                deleteDocument(documentId);
            }
        }

        function deleteDocument(id) {
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
                    showSuccessMessage('Document deleted successfully!');
                    loadDocuments();
                    loadStats();
                    hideDeleteEventModal();
                } else {
                    alert('Error deleting document: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Network error. Please try again.');
                console.error(error);
            });
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

        function displayCourseContent(documents) {
            const container = document.getElementById('course-content');
            
            if (!container) return;
            if (documents.length === 0) {
                container.innerHTML = `<div class="text-center py-8">
                    <p class="text-gray-500">No events available</p>
                </div>`;
                return;
            }

            const modules = [
                { title: "Course Intro", items: documents.slice(0, 4) },
                { title: "History of Cringe", items: documents.slice(4, 7) },
                { title: "Role Of Technology", items: documents.slice(7, 15) },
                { title: "Age OF AI/ML", items: documents.slice(15, 17) },
                { title: "Final Quiz & Transformation", items: documents.slice(17, 29) }
            ];

            let contentHTML = `<div class="space-y-6">`;
            
            modules.forEach((module, moduleIndex) => {
                if (!module.items || module.items.length === 0) return;

                contentHTML += `
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors" onclick="toggleModule(${moduleIndex})">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">${module.title}</h3>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500">${module.items.length} Items</span>
                                    <svg class="w-4 h-4 text-gray-400 transform transition-transform ${moduleIndex === 0 ? 'rotate-180' : ''}" id="module-icon-${moduleIndex}" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="module-content-${moduleIndex}" style="display: ${moduleIndex === 0 ? 'block' : 'none'};">
                            <div class="divide-y divide-gray-200">
                `;

                module.items.forEach((item) => {
                    const eventDate = new Date(item.event_date || item.upload_date || Date.now());
                    const timeAgo = getTimeAgo(eventDate);
                    contentHTML += `
                        <div class="px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer" onclick="selectEvent(${item.id || item.event_id})">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">${item.event_name || item.document_name || 'Untitled Event'}</h4>
                                        <p class="text-xs text-gray-500">${timeAgo}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-gray-500">${eventDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                contentHTML += `
                            </div>
                        </div>
                    </div>
                `;
            });

            contentHTML += `</div>`;
            container.innerHTML = contentHTML;
        }

        function toggleModule(moduleIndex) {
            const content = document.querySelector(`.module-content-${moduleIndex}`);
            const icon = document.getElementById(`module-icon-${moduleIndex}`);
            if (!content || !icon) return;
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        function selectEvent(eventId) {
            const event = currentDocuments.find(doc => (doc.id == eventId || doc.event_id == eventId)) || currentDocuments[0];
            if (!event) return;
            const container = document.getElementById('events-container');
            const eventDate = new Date(event.event_date || event.upload_date || Date.now());
            const formattedDate = eventDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            container.innerHTML = `
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="aspect-video bg-gradient-to-br from-blue-600 to-purple-700 flex items-center justify-center relative">
                        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                        <div class="relative z-10 text-center text-white">
                            <div class="w-24 h-24 mx-auto mb-4 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-2">${event.event_name || event.document_name || 'Untitled Event'}</h3>
                            <p class="text-lg opacity-90">Event Preview</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-2xl font-bold text-gray-900">${event.event_name || event.document_name || 'Untitled Event'}</h2>
                            <div class="flex space-x-2">
                                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Save Note
                                </button>
                                <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Download
                                </button>
                                <button class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684z"></path>
                                    </svg>
                                    Share
                                </button>
                            </div>
                        </div>
                        <div class="border-b border-gray-200 mb-6">
                            <nav class="flex space-x-8">
                                <button class="border-b-2 border-blue-600 py-2 px-1 text-sm font-medium text-blue-600">Overview</button>
                                <button class="border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">Notes</button>
                                <button class="border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">Announcements</button>
                                <button class="border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">Reviews</button>
                            </nav>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Event Details</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-gray-700">${formattedDate}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="text-gray-700">${event.venue || 'No venue specified'}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <span class="text-gray-700">${event.organizer || 'No organizer specified'}</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Description</h4>
                                <p class="text-gray-700 leading-relaxed">${event.description && event.description.trim() ? event.description : 'No description available for this event.'}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    </script>
</head>

<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between pl-64 relative transition-all duration-300 ease-in-out">
        <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        
        <div class="absolute left-1/2 transform -translate-x-1/2">
            <h1 class="text-xl font-bold text-gray-800">Events & Activities</h1>
        </div>
        <div class="absolute right-4 top-4 z-[90] text-sm flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span id="current-date"></span>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var hamburger = document.getElementById('hamburger-toggle');
        if (hamburger) {
            hamburger.addEventListener('click', function() {
                try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (e) {}
            });
        }
        var desktopToggle = document.getElementById('desktop-menu-toggle');
        if (desktopToggle) {
            desktopToggle.addEventListener('click', function() {
                try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (e) {}
            });
        }


        // Update date in top-right
        function updateCurrentDate() {
            var el = document.getElementById('current-date');
            if (el) {
                var now = new Date();
                el.textContent = now.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
            }
        }
        updateCurrentDate();
        setInterval(updateCurrentDate, 60000);
    });
    </script>

    <!-- Main Content -->
    <div id="main-content" class="ml-64 p-6 pt-20 min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 transition-all duration-300 ease-in-out">

        




        <!-- Course Platform Layout -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Main Content Area (Left) -->
            <div class="flex-1">
                <div id="events-container">
                    <!-- Main event content will be loaded here -->
                </div>
            </div>
            
            <!-- Course Content Sidebar (Right) -->
            <div class="lg:w-80">
                <div class="bg-white rounded-lg shadow-lg border border-gray-200">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Course Content</h3>
                    </div>
                    <div class="p-4">
                        <div id="course-content">
                            <!-- Course content will be loaded here -->
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

    <!-- Add Event Confirmation Modal -->
    <div id="addEventConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Confirm Document Details</h3>
                        <button onclick="hideAddEventConfirmModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Document Title</label>
                        <p id="confirmEventTitle" class="mt-1 text-sm text-gray-900 font-medium"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Event Date</label>
                            <p id="confirmEventDate" class="mt-1 text-sm text-gray-900"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Event Time</label>
                            <p id="confirmEventTime" class="mt-1 text-sm text-gray-900"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <p id="confirmEventLocation" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Organizer</label>
                        <p id="confirmEventOrganizer" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">File</label>
                        <p id="confirmEventFile" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <p id="confirmEventDescription" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                    <button onclick="hideAddEventConfirmModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmAddDocument()" class="px-4 py-2 text-sm font-medium text-white bg-black rounded-lg hover:bg-gray-800 transition-colors">
                        Confirm & Add Document
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Event Confirmation Modal -->
    <div id="deleteEventModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Confirm Deletion</h3>
                        <button onclick="hideDeleteEventModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-gray-900">Delete Document</h3>
                            <p class="text-sm text-gray-500">This action cannot be undone.</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700 mb-4">
                        Are you sure you want to delete the document "<span id="deleteEventName" class="font-medium"></span>"?
                    </p>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                    <button onclick="hideDeleteEventModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmDeleteDocument()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                        Delete Document
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Event Modal -->
    <div id="viewEventModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Document Details</h3>
                        <button onclick="closeViewEventModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Document Title</label>
                        <p id="viewEventTitle" class="mt-1 text-lg font-medium text-gray-900"></p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Upload Date</label>
                            <p id="viewEventDate" class="mt-1 text-sm text-gray-900"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <p id="viewEventOrganizer" class="mt-1 text-sm text-gray-900"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Filename</label>
                        <p id="viewEventLocation" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <p id="viewEventDescription" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end">
                    <button onclick="closeViewEventModal()" class="px-6 py-2 text-sm font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700 transition-colors">
                        Close
                    </button>
                </div>
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

        // Desktop toggle relays to sidebar controller
        function desktopToggleSidebar() {
            try {
                window.dispatchEvent(new CustomEvent('sidebar:toggle'));
            } catch (e) {}
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
