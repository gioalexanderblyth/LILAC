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
    <script>
        // Mobile nav handled below; removing unused duplicate toggleMenu()
        
        // Global variable to store current documents
        let currentEvents = [];
        const CATEGORY = 'Events & Activities';

        document.addEventListener('DOMContentLoaded', function() {
            loadEvents();
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
            
            // Filters and form listeners
            const searchInput = document.getElementById('event-search');
            const statusFilter = document.getElementById('event-status-filter');
            if (searchInput) searchInput.addEventListener('input', () => renderEventList());
            if (statusFilter) statusFilter.addEventListener('change', () => renderEventList());
            const addEventForm = document.getElementById('add-event-form');
            if (addEventForm) addEventForm.addEventListener('submit', handleSaveEvent);
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

        function getEventStartDate(event) {
            const dateStr = event.meeting_date || event.date || event.upload_date;
            const timeStr = event.meeting_time || event.time || '00:00';
            return new Date(`${dateStr}T${(timeStr || '00:00')}:00`);
        }

        function getEventEndDate(event) {
            const endDateStr = event.end_date || event.meeting_date || event.date;
            const endTimeStr = event.end_time || event.meeting_time || '23:59';
            return new Date(`${endDateStr}T${(endTimeStr || '23:59')}:00`);
        }

        function getEventStatus(event) {
            const now = new Date();
            const start = getEventStartDate(event);
            const end = getEventEndDate(event);
            if (end < now) return 'completed';
            if (start > now) return 'upcoming';
            return 'ongoing';
        }

        function loadEvents() {
            fetch('api/scheduler.php?action=get_all')
                .then(response => response.json())
                .then(data => {
                    if (data && data.success && Array.isArray(data.meetings)) {
                        currentEvents = data.meetings;
                    } else {
                        currentEvents = [];
                    }
                    if (!Array.isArray(currentEvents) || currentEvents.length === 0) {
                        // Demo data if none exists
                        const today = new Date();
                        const iso = today.toISOString().slice(0,10);
                        currentEvents = [
                            { id: 1, title: 'LILAC Orientation', meeting_date: iso, meeting_time: '09:00', end_date: iso, end_time: '10:00', organizer: 'LILAC', venue: 'Auditorium', description: 'Welcome and orientation.' },
                            { id: 2, title: 'Partner University Call', meeting_date: iso, meeting_time: '14:00', end_date: iso, end_time: '15:00', organizer: 'International Affairs', venue: 'Zoom', description: 'Collaboration discussion.' },
                            { id: 3, title: 'Research Symposium', meeting_date: iso, meeting_time: '16:00', end_date: iso, end_time: '18:00', organizer: 'R&D', venue: 'Hall B', description: 'Papers and posters.' }
                        ];
                    }
                    // Select first and render side list and counters
                    selectEvent(currentEvents[0]?.id || currentEvents[0]?.event_id);
                    updateEventCounters();
                    renderEventList();
                })
                .catch(() => {
                    currentEvents = [];
                    updateEventCounters();
                    renderEventList();
                });
        }

        function updateEventCounters() {
            const upcoming = currentEvents.filter(e => getEventStatus(e) === 'upcoming').length;
            const ongoing = currentEvents.filter(e => getEventStatus(e) === 'ongoing').length;
            const completed = currentEvents.filter(e => getEventStatus(e) === 'completed').length;
            const uEl = document.getElementById('stat-upcoming');
            const oEl = document.getElementById('stat-ongoing');
            const cEl = document.getElementById('stat-completed');
            if (uEl) uEl.textContent = upcoming;
            if (oEl) oEl.textContent = ongoing;
            if (cEl) cEl.textContent = completed;
        }

        function applyEventFilters(events) {
            const search = (document.getElementById('event-search')?.value || '').toLowerCase();
            const status = (document.getElementById('event-status-filter')?.value || 'all');
            return events.filter(e => {
                const matchesSearch = !search || (
                    (e.title || e.event_name || e.document_name || '').toLowerCase().includes(search) ||
                    (e.organizer || '').toLowerCase().includes(search) ||
                    (e.venue || '').toLowerCase().includes(search)
                );
                const matchesStatus = (status === 'all') || (getEventStatus(e) === status);
                return matchesSearch && matchesStatus;
            });
        }

        function renderEventList() {
            const filtered = applyEventFilters(currentEvents);
            displayCourseContent(filtered);
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
                                <button onclick="selectEvent(${doc.event_id || doc.id})" class="text-blue-600 hover:text-blue-900 font-medium flex items-center" title="View Details">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </button>
                                <button onclick="showEditEventModal(${doc.event_id || doc.id})" class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center" title="Edit">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </button>
                                <button onclick="showDeleteEventModal(${doc.event_id || doc.id}, '${(doc.event_name || doc.document_name || 'Untitled Event').replace(/'/g, "\\'")}')" class="text-red-600 hover:text-red-900 font-medium flex items-center" title="Delete">
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
                    loadEvents();
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

        function showDeleteEventModal(id, title) {
            document.getElementById('deleteEventName').textContent = title;
            const modal = document.getElementById('deleteEventModal');
            modal.classList.remove('hidden');
            modal.setAttribute('data-event-id', id);
        }

        function hideDeleteEventModal() {
            document.getElementById('deleteEventModal').classList.add('hidden');
        }

        function confirmDeleteEvent() {
            const modal = document.getElementById('deleteEventModal');
            const eventId = modal.getAttribute('data-event-id');
            if (eventId) deleteEvent(eventId);
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

        function deleteEvent(id) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage('Event deleted successfully!');
                    loadEvents();
                    hideDeleteEventModal();
                } else {
                    alert('Error deleting event: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(() => alert('Network error. Please try again.'));
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

            const upcoming = documents.filter(d => getEventStatus(d) === 'upcoming');
            const ongoing = documents.filter(d => getEventStatus(d) === 'ongoing');
            const completed = documents.filter(d => getEventStatus(d) === 'completed');
            const modules = [
                { title: 'Upcoming', items: upcoming },
                { title: 'Ongoing', items: ongoing },
                { title: 'Completed', items: completed }
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
                    const eventDate = getEventStartDate(item);
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
                                        <h4 class="text-sm font-medium text-gray-900">${item.title || item.event_name || item.document_name || 'Untitled Event'}</h4>
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
            const event = (currentEvents.find(e => (e.id == eventId || e.event_id == eventId)) || currentEvents[0] || currentDocuments?.find?.(d => (d.id == eventId || d.event_id == eventId)));
            if (!event) return;
            const container = document.getElementById('events-container');
            const startDate = getEventStartDate(event);
            const formattedDate = startDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            container.innerHTML = '';
        }
    </script>
</head>

<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between relative transition-all duration-300 ease-in-out">
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
        // Hamburger and desktop toggle buttons are now handled globally by LILACSidebar
    });
    
    // toggleSidebar function is now handled globally by LILACSidebar
    function toggleSidebar_DISABLED() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar) return;
        
        // Toggle hidden/visible by translating X
        sidebar.classList.toggle('-translate-x-full');
        
        // Toggle backdrop on mobile
        if (backdrop && window.innerWidth < 1024) {
            backdrop.classList.toggle('hidden');
        }
        
        // On mobile, adjust main content margin
        const mainContainer = document.getElementById('main-content');
        if (mainContainer) {
            // Only adjust margin on mobile (when sidebar is hidden by default)
            if (window.innerWidth < 1024) { // lg breakpoint
                mainContainer.classList.toggle('ml-0');
            }
        }
        
        // Adjust navbar left padding on desktop
        const nav = document.querySelector('nav.modern-nav');
        if (nav && window.innerWidth >= 1024) { // lg breakpoint
            nav.classList.toggle('pl-64');
        }
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
    <div id="main-content" class="p-4 pt-3 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out overflow-x-hidden min-w-0">

        <!-- Event Counters removed per request -->

        <!-- Compact Dashboard Section -->
        <div id="ea-dashboard-grid" class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6 mb-4">
            <!-- Left: Greeting + Overview -->
            <div class="lg:col-span-2 space-y-6 pr-0 lg:pr-2 min-w-0">
                <!-- Greeting + Quick actions -->
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center">
                                <h2 class="ea-greeting-title text-xl font-bold text-gray-900 leading-6 md:whitespace-nowrap">Good morning, Lesley</h2>
                                <span class="ml-2 text-xl align-middle" aria-hidden="true">!</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Here's a quick glance at your events</p>
                        </div>
                        <div class="hidden md:flex items-center gap-2 shrink-0">
                            <button class="px-3 py-1.5 text-sm rounded-lg border hover:bg-gray-50 text-transparent w-25" aria-label="Course" title="Course">Course</button>
                            <button class="px-3 py-1.5 text-sm rounded-lg border hover:bg-gray-50 text-transparent w-25" aria-label="Page" title="Page">Page</button>
                            <button class="px-3 py-1.5 text-sm rounded-lg border hover:bg-gray-50 text-transparent w-25" aria-label="Quiz" title="Quiz">Quiz</button>
                            <button class="px-3 py-1.5 text-sm rounded-lg border hover:bg-gray-50 text-transparent w-25" aria-label="Quiz" title="Quiz">Quiz</button>
                            <button class="px-3 py-1.5 text-sm rounded-lg border hover:bg-gray-50 text-transparent w-25" aria-label="Learning Path" title="Learning Path">Learning Path</button>
                        </div>
                    </div>
                </div>

                <!-- Overview cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <p class="text-xs text-gray-500">Upcoming</p>
                        <p id="dash-upcoming" class="mt-1 text-3xl font-bold">0</p>
                        <p class="text-xs text-green-600 mt-2">On your schedule</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <p class="text-xs text-gray-500">Ongoing</p>
                        <p id="dash-ongoing" class="mt-1 text-3xl font-bold">0</p>
                        <div class="mt-2 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div id="ongoing-bar" class="h-2 bg-indigo-600" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <p class="text-xs text-gray-500">Completed</p>
                        <p id="dash-completed" class="mt-1 text-3xl font-bold">0</p>
                        <p class="text-xs text-gray-500 mt-2">This period</p>
                    </div>
                </div>

                <!-- Ungraded-like table (Upcoming items) -->
                <div class="bg-white rounded-xl border border-gray-200 min-h-[450px]">
                    <div class="p-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Upcoming Events</h3>
                        <a href="#" onclick="document.getElementById('event-search').focus(); return false;" class="text-sm text-blue-600">Search events →</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Title</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">When</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Organizer</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody id="upcoming-table" class="divide-y">
                                <!-- Filled by JS from currentEvents -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

                         <!-- Right: To Do + Events + Upgrade -->
             <div id="ea-right-rail" class="space-y-6 lg:w-[350px] xl:w-[400px] 2xl:w-[430px]">
                 <!-- To Do List -->
                 <div class="bg-white rounded-xl border border-gray-200">
                    <div class="p-4 border-b flex items-center justify-between">
                        <h3 class="text-lg font-semibold">To Do List</h3>
                        <button id="todo-add" class="px-2 py-1 text-sm rounded-lg border hover:bg-gray-50">+ Add</button>
                    </div>
                    <div id="todo-list" class="p-4 space-y-3 text-sm">
                        <!-- Items injected via JS -->
                    </div>
                </div>

                 <!-- Events (moved here) -->
                 <div class="bg-white rounded-lg shadow-lg border border-gray-200">
                    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Events</h3>
                        <button onclick="showAddEventModal()" class="bg-black text-white text-sm px-3 py-1.5 rounded-lg hover:bg-gray-800 transition-colors">Add Event</button>
                    </div>
                    <div class="p-4 space-y-3">
                        <input id="event-search" type="text" placeholder="Search by title, organizer, venue" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <select id="event-status-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">All statuses</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                        <div id="course-content">
                            <!-- Course content will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Upgrade card -->
                <!-- Removed upgrade card per request -->
            </div>
        </div>

        <script>
        // Lightweight widgets populated from existing currentEvents
        function refreshMiniDashboard() {
            try {
                const upcoming = currentEvents.filter(e => getEventStatus(e) === 'upcoming');
                const ongoing = currentEvents.filter(e => getEventStatus(e) === 'ongoing');
                const completed = currentEvents.filter(e => getEventStatus(e) === 'completed');
                const u = document.getElementById('dash-upcoming');
                const o = document.getElementById('dash-ongoing');
                const c = document.getElementById('dash-completed');
                if (u) u.textContent = upcoming.length;
                if (o) o.textContent = ongoing.length;
                if (c) c.textContent = completed.length;
                const bar = document.getElementById('ongoing-bar');
                if (bar) {
                    const total = upcoming.length + ongoing.length + completed.length || 1;
                    bar.style.width = Math.min(100, Math.round((ongoing.length/total)*100)) + '%';
                }
                const tbody = document.getElementById('upcoming-table');
                if (tbody) {
                    const items = upcoming.slice(0, 5).map(ev => {
                        const dt = getEventStartDate(ev);
                        const time = dt.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                        const date = dt.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
                        const title = ev.title || ev.event_name || ev.document_name || 'Untitled Event';
                        const organizer = ev.organizer || '-';
                        return `<tr>
                            <td class=\"px-4 py-3\">${title}</td>
                            <td class=\"px-4 py-3 text-gray-600\">${date} • ${time}</td>
                            <td class=\"px-4 py-3 text-gray-600\">${organizer}</td>
                            <td class=\"px-4 py-3\"><button class=\"px-3 py-1 text-xs rounded-lg border hover:bg-gray-50\" onclick=\"selectEvent(${ev.id || ev.event_id})\">Grade Now</button></td>
                        </tr>`;
                    }).join('');
                    tbody.innerHTML = items || `<tr><td class=\"px-4 py-6 text-center text-gray-500\" colspan=\"4\">No upcoming items</td></tr>`;
                }
            } catch (e) { /* noop */ }
        }
        // Hook into existing loadEvents
        const __origLoadEvents = loadEvents;
        loadEvents = function() { __origLoadEvents(); setTimeout(refreshMiniDashboard, 50); };

        // Minimal localStorage-backed todo
        const TODO_KEY = 'lilac.events.todo.v1';
        function getTodos(){
            try { return JSON.parse(localStorage.getItem(TODO_KEY) || '[]'); } catch(e){ return []; }
        }
        function setTodos(items){
            localStorage.setItem(TODO_KEY, JSON.stringify(items));
            renderTodos();
        }
        function renderTodos(){
            const wrap = document.getElementById('todo-list');
            if (!wrap) return;
            const items = getTodos();
            if (!items.length) {
                wrap.innerHTML = `<div class=\"text-gray-500 text-sm\">Add new task to get started</div>`;
                return;
            }
            wrap.innerHTML = items.map((t, i) => `
                <label class=\"flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer\"> 
                    <input type=\"checkbox\" ${t.done?'checked':''} onchange=\"toggleTodo(${i})\" class=\"mt-1 rounded\"> 
                    <div>
                        <p class=\"${t.done?'line-through text-gray-400':'text-gray-800'}\">${t.text}</p>
                        ${t.due ? `<p class=\\"text-xs text-gray-500\\">${t.due}</p>` : ''}
                    </div>
                    <button onclick=\"deleteTodo(${i})\" class=\"ml-auto text-gray-400 hover:text-red-600\">✕</button>
                </label>`).join('');
        }
        function addTodo(){
            const text = prompt('Task');
            if (!text) return;
            const items = getTodos();
            items.unshift({ text, done:false, due:'Today' });
            setTodos(items);
        }
        function toggleTodo(i){
            const items = getTodos();
            if (!items[i]) return; items[i].done = !items[i].done; setTodos(items);
        }
        function deleteTodo(i){
            const items = getTodos(); items.splice(i,1); setTodos(items);
        }
        document.addEventListener('DOMContentLoaded', function(){
            const btn = document.getElementById('todo-add');
            if (btn) btn.addEventListener('click', addTodo);
            renderTodos();
        });
        </script>

        
        
        
        
        
        
        <!-- Course Platform Layout -->
        <div class="flex flex-col lg:flex-row gap-6">
             <!-- Main Content Area (Left) -->
             <div class="flex-1">
                 <div id="events-container">
                     <!-- Main event content will be loaded here -->
                 </div>
             </div>
                         
            <!-- Course Content Sidebar (Right) removed; Events moved to right widgets column -->
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

    <!-- Add/Edit Event Modal -->
    <div id="addEventModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-sm w-full max-h-[70vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <h3 id="addEventModalTitle" class="text-lg font-semibold text-gray-900">Add Event</h3>
                    <button onclick="hideAddEventModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form id="add-event-form" class="p-4 space-y-3">
                    <input type="hidden" id="event-id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title<span class="text-red-500">*</span></label>
                        <input id="event-title-input" type="text" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="event-description-input" rows="2" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input id="event-all-day" type="checkbox" class="rounded">
                        <label for="event-all-day" class="text-sm text-gray-700">All day</label>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date<span class="text-red-500">*</span></label>
                            <input id="event-date-start" type="date" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Time</label>
                            <input id="event-time-start" type="time" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input id="event-date-end" type="date" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Time</label>
                            <input id="event-time-end" type="time" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Organizer</label>
                            <input id="event-organizer-input" type="text" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="LILAC">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Venue</label>
                            <input id="event-venue-input" type="text" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Auditorium">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <div class="flex items-center space-x-2">
                            <label class="cursor-pointer flex items-center space-x-2"><input type="radio" name="event-color" value="blue" checked><span class="w-5 h-5 rounded-full bg-blue-500 inline-block"></span></label>
                            <label class="cursor-pointer flex items-center space-x-2"><input type="radio" name="event-color" value="orange"><span class="w-5 h-5 rounded-full bg-orange-500 inline-block"></span></label>
                            <label class="cursor-pointer flex items-center space-x-2"><input type="radio" name="event-color" value="teal"><span class="w-5 h-5 rounded-full bg-teal-500 inline-block"></span></label>
                            <label class="cursor-pointer flex items-center space-x-2"><input type="radio" name="event-color" value="brown"><span class="w-5 h-5 rounded-full bg-amber-700 inline-block"></span></label>
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end space-x-2 border-t border-gray-200">
                        <button type="button" onclick="hideAddEventModal()" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-black rounded-lg hover:bg-gray-800 transition-colors">Save</button>
                    </div>
                </form>
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
                            <h3 class="text-sm font-medium text-gray-900">Delete Event</h3>
                            <p class="text-sm text-gray-500">This action cannot be undone.</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700 mb-4">Are you sure you want to delete the event "<span id="deleteEventName" class="font-medium"></span>"?</p>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                    <button onclick="hideDeleteEventModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmDeleteEvent()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                        Delete Event
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

        function showAddEventModal() {
            document.getElementById('addEventModalTitle').textContent = 'Add Event';
            document.getElementById('event-id').value = '';
            document.getElementById('event-title-input').value = '';
            document.getElementById('event-description-input').value = '';
            document.getElementById('event-all-day').checked = false;
            const today = new Date();
            const iso = today.toISOString().slice(0,10);
            document.getElementById('event-date-start').value = iso;
            document.getElementById('event-time-start').value = '';
            document.getElementById('event-date-end').value = '';
            document.getElementById('event-time-end').value = '';
            document.getElementById('event-organizer-input').value = '';
            document.getElementById('event-venue-input').value = '';
            const colors = document.querySelectorAll('input[name="event-color"]');
            colors.forEach(c => { c.checked = c.value === 'blue'; });
            document.getElementById('addEventModal').classList.remove('hidden');
        }

        function showEditEventModal(id) {
            const e = currentEvents.find(x => x.id == id);
            if (!e) return;
            document.getElementById('addEventModalTitle').textContent = 'Edit Event';
            document.getElementById('event-id').value = e.id;
            document.getElementById('event-title-input').value = e.title || '';
            document.getElementById('event-description-input').value = e.description || '';
            const isAllDay = (e.is_all_day === '1' || e.is_all_day === 1 || e.is_all_day === true);
            document.getElementById('event-all-day').checked = isAllDay;
            document.getElementById('event-date-start').value = (e.meeting_date || e.date || '').slice(0,10);
            document.getElementById('event-time-start').value = (e.meeting_time || e.time || '');
            document.getElementById('event-date-end').value = (e.end_date || '').slice(0,10);
            document.getElementById('event-time-end').value = (e.end_time || '');
            document.getElementById('event-organizer-input').value = e.organizer || '';
            document.getElementById('event-venue-input').value = e.venue || '';
            const color = e.color || 'blue';
            const colors = document.querySelectorAll('input[name="event-color"]');
            colors.forEach(c => { c.checked = (c.value === color); });
            document.getElementById('addEventModal').classList.remove('hidden');
        }

        function hideAddEventModal() {
            document.getElementById('addEventModal').classList.add('hidden');
        }

        function handleSaveEvent(ev) {
            ev.preventDefault();
            const id = document.getElementById('event-id').value;
            const title = document.getElementById('event-title-input').value.trim();
            const description = document.getElementById('event-description-input').value.trim();
            const isAllDay = document.getElementById('event-all-day').checked ? '1' : '0';
            const dateStart = document.getElementById('event-date-start').value;
            const timeStart = document.getElementById('event-time-start').value;
            const dateEnd = document.getElementById('event-date-end').value;
            const timeEnd = document.getElementById('event-time-end').value;
            const organizer = document.getElementById('event-organizer-input').value.trim();
            const venue = document.getElementById('event-venue-input').value.trim();
            const color = (document.querySelector('input[name="event-color"]:checked')?.value) || 'blue';

            if (!title || !dateStart) {
                alert('Please provide at least title and start date.');
                return;
            }

            const formData = new FormData();
            formData.append('title', title);
            formData.append('description', description);
            formData.append('date', dateStart);
            if (!isAllDay) formData.append('time', timeStart || '');
            if (dateEnd) formData.append('end_date', dateEnd);
            if (!isAllDay && timeEnd) formData.append('end_time', timeEnd);
            formData.append('is_all_day', isAllDay);
            formData.append('color', color);
            formData.append('organizer', organizer);
            formData.append('venue', venue);

            if (id) {
                formData.append('action', 'update');
                formData.append('id', id);
            } else {
                formData.append('action', 'add');
            }

            fetch('api/scheduler.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        showSuccessMessage(id ? 'Event updated successfully!' : 'Event added successfully!');
                        hideAddEventModal();
                        loadEvents();
                    } else {
                        alert('Error saving event: ' + (data?.message || 'Unknown error'));
                    }
                })
                .catch(() => alert('Network error. Please try again.'));
        }

        // Sidebar layout is now handled globally by LILACSidebar
        // Custom layout adjustments for events page can be added via sidebar:state events if needed
        </script>

    </footer>

    <style>
    /* Compact layout helpers for events page to prevent hidden content on toggle */
    .ml-64{ margin-left:16rem; }
    .pl-64{ padding-left:16rem; }
    </style>

    <style>
    /* Extra compact mode when sidebar is open */
    #main-content.ea-compact { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
    @media (min-width: 1024px) {
      #main-content.ea-compact { padding-left: 1rem !important; padding-right: 1rem !important; }
    }
    #main-content.ea-compact .p-4 { padding: 0.75rem !important; }
    #main-content.ea-compact .px-4 { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    #main-content.ea-compact .py-4 { padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }
    #main-content.ea-compact .ea-greeting-title { font-size: 1.125rem !important; line-height: 1.5rem !important; }
    #main-content.ea-compact .md\:whitespace-nowrap { white-space: nowrap; }

    /* Right rail compact mode */
    @media (min-width: 1024px) {
      #ea-right-rail.ea-rail-compact { width: 280px !important; }
      #ea-right-rail.ea-rail-compact .p-4 { padding: 0.75rem !important; }
      #ea-right-rail.ea-rail-compact .text-lg { font-size: 1rem !important; }
      #ea-right-rail.ea-rail-compact .text-sm { font-size: 0.8125rem !important; }
      #ea-right-rail.ea-rail-compact .px-3 { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
      #ea-right-rail.ea-rail-compact .py-1\.5 { padding-top: 0.25rem !important; padding-bottom: 0.25rem !important; }
    }
    </style>
</body>

</html>
