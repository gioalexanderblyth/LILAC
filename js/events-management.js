/**
 * Events Management JavaScript
 * Handles all events-related functionality
 */

class EventsManager {
    constructor() {
        this.eventsData = [];
        this.currentFilters = {
            status: 'all',
            category: 'all',
            search: '',
            page: 1,
            limit: EventsConfig.ui.itemsPerPage
        };
        
        this.initializeEventListeners();
        this.loadEvents();
    }
    
    /**
     * Initialize event listeners
     */
    initializeEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('events-search');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.currentFilters.search = searchInput.value;
                this.currentFilters.page = 1;
                this.loadEvents();
            }, 300));
        }
        
        // Status filter
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                this.currentFilters.status = statusFilter.value;
                this.currentFilters.page = 1;
                this.loadEvents();
            });
        }
        
        // Category filter
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => {
                this.currentFilters.category = categoryFilter.value;
                this.currentFilters.page = 1;
                this.loadEvents();
            });
        }
        
        // Create event button
        const createBtn = document.getElementById('create-event-btn');
        if (createBtn) {
            createBtn.addEventListener('click', () => this.showCreateEventModal());
        }
        
        // Event form submission
        const eventForm = document.getElementById('event-form');
        if (eventForm) {
            eventForm.addEventListener('submit', (e) => this.handleEventSubmit(e));
        }
    }
    
    /**
     * Load events from API
     */
    async loadEvents() {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                action: 'get_events_by_status',
                ...this.currentFilters
            });
            
            const response = await fetch(`${EventsConfig.api.events}?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.eventsData = data.events || [];
                this.renderEvents();
                this.updateEventCounters();
            } else {
                throw new Error(data.error || 'Failed to load events');
            }
        } catch (error) {
            console.error('Error loading events:', error);
            this.showNotification('Failed to load events', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Render events in the table
     */
    renderEvents() {
        const tbody = document.getElementById('events-table-body');
        if (!tbody) return;
        
        if (this.eventsData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-8 text-gray-500">
                        No events found
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.eventsData.map(event => this.createEventRow(event)).join('');
    }
    
    /**
     * Create an event table row
     */
    createEventRow(event) {
        const status = EventsConfig.statusOptions.find(s => s.value === event.status) || EventsConfig.statusOptions[0];
        const startDate = this.formatDate(event.start);
        const endDate = this.formatDate(event.end);
        
        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        ${event.title}
                    </div>
                    <div class="text-sm text-gray-500">
                        ${event.description || 'No description'}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-white">${startDate}</div>
                    <div class="text-sm text-gray-500">${endDate}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${event.location || 'No location'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${status.color}">
                        ${status.label}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(event.created_at)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="eventsManager.viewEvent('${event.id}')" 
                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                        View
                    </button>
                    <button onclick="eventsManager.editEvent('${event.id}')" 
                            class="text-blue-600 hover:text-blue-900 mr-3">
                        Edit
                    </button>
                    <button onclick="eventsManager.deleteEvent('${event.id}')" 
                            class="text-red-600 hover:text-red-900">
                        Delete
                    </button>
                </td>
            </tr>
        `;
    }
    
    /**
     * Update event counters
     */
    updateEventCounters() {
        const upcoming = this.eventsData.filter(e => e.status === 'upcoming').length;
        const completed = this.eventsData.filter(e => e.status === 'completed').length;
        const total = this.eventsData.length;
        
        const upcomingEl = document.getElementById('upcoming-count');
        const completedEl = document.getElementById('completed-count');
        const totalEl = document.getElementById('total-count');
        
        if (upcomingEl) upcomingEl.textContent = upcoming;
        if (completedEl) completedEl.textContent = completed;
        if (totalEl) totalEl.textContent = total;
    }
    
    /**
     * Show create event modal
     */
    showCreateEventModal() {
        const modal = document.getElementById('create-event-modal');
        if (modal) {
            modal.classList.remove('hidden');
            this.resetEventForm();
        }
    }
    
    /**
     * Hide create event modal
     */
    hideCreateEventModal() {
        const modal = document.getElementById('create-event-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    /**
     * Reset event form
     */
    resetEventForm() {
        const form = document.getElementById('event-form');
        if (form) {
            form.reset();
        }
    }
    
    /**
     * Handle event form submission
     */
    async handleEventSubmit(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        formData.append('action', 'create');
        
        try {
            this.showLoading(true);
            
            const response = await fetch(EventsConfig.api.upload, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Event created successfully', 'success');
                this.hideCreateEventModal();
                this.loadEvents();
                
                // Check for awards earned after successful event creation
                if (window.checkAwardCriteria) {
                    window.checkAwardCriteria('event', result.event_id || result.data?.event_id);
                }
            } else {
                throw new Error(result.error || 'Failed to create event');
            }
        } catch (error) {
            console.error('Error creating event:', error);
            this.showNotification('Failed to create event', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * View event details
     */
    viewEvent(eventId) {
        const event = this.eventsData.find(e => e.id === eventId);
        if (!event) return;
        
        // Show event details in modal or navigate to details page
        this.showEventDetailsModal(event);
    }
    
    /**
     * Show event details modal
     */
    showEventDetailsModal(event) {
        const modal = document.getElementById('event-details-modal');
        if (!modal) return;
        
        // Populate modal with event data
        const titleEl = modal.querySelector('#event-details-title');
        const descriptionEl = modal.querySelector('#event-details-description');
        const dateEl = modal.querySelector('#event-details-date');
        const locationEl = modal.querySelector('#event-details-location');
        const statusEl = modal.querySelector('#event-details-status');
        
        if (titleEl) titleEl.textContent = event.title;
        if (descriptionEl) descriptionEl.textContent = event.description || 'No description';
        if (dateEl) dateEl.textContent = `${this.formatDate(event.start)} - ${this.formatDate(event.end)}`;
        if (locationEl) locationEl.textContent = event.location || 'No location';
        if (statusEl) {
            const status = EventsConfig.statusOptions.find(s => s.value === event.status);
            statusEl.textContent = status ? status.label : event.status;
        }
        
        modal.classList.remove('hidden');
    }
    
    /**
     * Edit event
     */
    editEvent(eventId) {
        const event = this.eventsData.find(e => e.id === eventId);
        if (!event) return;
        
        // Populate form with event data and show modal
        this.populateEventForm(event);
        this.showCreateEventModal();
    }
    
    /**
     * Populate event form with data
     */
    populateEventForm(event) {
        const form = document.getElementById('event-form');
        if (!form) return;
        
        form.querySelector('#event-title').value = event.title || '';
        form.querySelector('#event-description').value = event.description || '';
        form.querySelector('#event-start').value = event.start || '';
        form.querySelector('#event-end').value = event.end || '';
        form.querySelector('#event-location').value = event.location || '';
        form.querySelector('#event-status').value = event.status || 'upcoming';
    }
    
    /**
     * Delete event
     */
    async deleteEvent(eventId) {
        if (!confirm('Are you sure you want to delete this event?')) return;
        
        try {
            this.showLoading(true);
            
            const response = await fetch(EventsConfig.api.events, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete_event',
                    id: eventId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Event deleted successfully', 'success');
                this.loadEvents();
            } else {
                throw new Error(result.error || 'Failed to delete event');
            }
        } catch (error) {
            console.error('Error deleting event:', error);
            this.showNotification('Failed to delete event', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Show loading state
     */
    showLoading(show) {
        const loadingEl = document.getElementById('events-loading');
        if (loadingEl) {
            loadingEl.style.display = show ? 'block' : 'none';
        }
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = EventsConfig.notifications[type] || EventsConfig.notifications.info;
        
        // Create notification element
        const notificationEl = document.createElement('div');
        notificationEl.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${notification.bgColor} ${notification.color}`;
        notificationEl.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">${notification.icon}</span>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notificationEl);
        
        // Remove after 3 seconds
        setTimeout(() => {
            if (notificationEl.parentNode) {
                notificationEl.parentNode.removeChild(notificationEl);
            }
        }, 3000);
    }
    
    /**
     * Format date for display
     */
    formatDate(dateString) {
        if (!dateString) return 'Unknown';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.eventsManager = new EventsManager();
});