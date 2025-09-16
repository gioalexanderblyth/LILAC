/**
 * Scheduler Management JavaScript
 * Handles all scheduler-related UI interactions and functionality
 */

// Global variables
let currentDate = new Date();
let selectedDate = new Date(); // Initialize with current date
let eventsData = [];
let currentEvent = null;

// Initialize scheduler management
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing Scheduler Management...');
    
    // Initialize calendar
    generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    
    // Load events data
    loadEventsData();
    
    // Initialize event listeners
    initializeEventListeners();
    
    // Load upcoming events for reminders
    loadUpcomingEvents();
    
    console.log('✅ Scheduler Management initialized successfully');
});

// Calendar functionality
function generateCalendar(year, month) {
    const calendarBody = document.getElementById('calendar-body');
    if (!calendarBody) return;
    
    // Clear existing calendar
    calendarBody.innerHTML = '';
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay();
    
    // Create calendar header using Intl.DateTimeFormat
    const monthHeader = document.getElementById('month-header');
    if (monthHeader) {
        const dateFormatter = new Intl.DateTimeFormat('en-US', { 
            month: 'long', 
            year: 'numeric' 
        });
        const date = new Date(year, month, 1);
        monthHeader.textContent = dateFormatter.format(date);
    }
    
    // Create calendar grid
    let day = 1;
    for (let week = 0; week < 6; week++) {
        const row = document.createElement('tr');
        
        for (let dayOfWeek = 0; dayOfWeek < 7; dayOfWeek++) {
            const cell = document.createElement('td');
            cell.className = 'text-center p-2 border border-gray-200 cursor-pointer hover:bg-gray-100';
            
            if (week === 0 && dayOfWeek < startingDay) {
                // Empty cells before first day of month
                cell.innerHTML = '';
            } else if (day > daysInMonth) {
                // Empty cells after last day of month
                cell.innerHTML = '';
            } else {
                // Day cells
                cell.innerHTML = `<span class="day-number">${day}</span>`;
                cell.onclick = () => selectDate(year, month, day);
                
                // Highlight today
                const today = new Date();
                if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                    cell.classList.add('bg-blue-500', 'text-white');
                }
                
                // Highlight selected date
                if (selectedDate && 
                    year === selectedDate.getFullYear() && 
                    month === selectedDate.getMonth() && 
                    day === selectedDate.getDate()) {
                    cell.classList.add('bg-purple-500', 'text-white');
                }
                
                day++;
            }
            
            row.appendChild(cell);
        }
        
        calendarBody.appendChild(row);
    }
}

function selectDate(year, month, day) {
    selectedDate = new Date(year, month, day);
    generateCalendar(year, month);
    
    // Load events for selected date
    loadEventsForDate(selectedDate);
    
    // Show add event button
    showAddEventButton();
}

function navigateCalendar(direction) {
    if (direction === 'prev') {
        currentDate.setMonth(currentDate.getMonth() - 1);
    } else if (direction === 'next') {
        currentDate.setMonth(currentDate.getMonth() + 1);
    }
    
    generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
}

// Event management functions
function loadEventsData() {
    // Try main API first, then fallback to simple API
    let apiUrl = 'api/scheduler.php?action=get_events';
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(responseText => {
            if (!responseText || responseText.trim() === '') {
                console.log('Main API returned empty response, trying fallback...');
                // Try fallback API instead of throwing error
                return fetch('api/scheduler_simple.php?action=get_events')
                    .then(response => response.text())
                    .then(fallbackText => {
                        if (fallbackText && fallbackText.trim() !== '') {
                            return JSON.parse(fallbackText);
                        }
                        throw new Error('Both APIs returned empty responses');
                    });
            }
            return JSON.parse(responseText);
        })
        .then(result => {
            if (result.success) {
                eventsData = result.data;
                updateCalendarWithEvents();
            } else {
                showNotification('Failed to load events: ' + result.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading events:', error);
            showNotification('Error loading events', 'error');
        });
}

function loadEventsForDate(date) {
    const dateStr = date.toISOString().split('T')[0];
    
    fetch(`api/scheduler.php?action=get_events_for_date&date=${dateStr}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayEventsForDate(result.data);
            } else {
                showNotification('Failed to load events for date: ' + result.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading events for date:', error);
            showNotification('Error loading events for date', 'error');
        });
}

function displayEventsForDate(events) {
    const eventsContainer = document.getElementById('events-container');
    if (!eventsContainer) return;
    
    if (events.length === 0) {
        eventsContainer.innerHTML = '<p class="text-gray-500 text-center">No events scheduled for this date.</p>';
        return;
    }
    
    let eventsHTML = '';
    events.forEach(event => {
        const timeFormatter = new Intl.DateTimeFormat('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false 
        });
        const startTime = timeFormatter.format(new Date(event.start));
        const endTime = timeFormatter.format(new Date(event.end));
        
        eventsHTML += `
            <div class="event-item p-3 border border-gray-200 rounded-lg mb-2 cursor-pointer hover:bg-gray-50" 
                 onclick="viewEvent(${event.id})">
                <div class="font-medium text-gray-900">${event.title}</div>
                <div class="text-sm text-gray-600">${startTime} - ${endTime}</div>
                ${event.location ? `<div class="text-sm text-gray-500">📍 ${event.location}</div>` : ''}
            </div>
        `;
    });
    
    eventsContainer.innerHTML = eventsHTML;
}

function updateCalendarWithEvents() {
    // Update calendar with event indicators
    const dayCells = document.querySelectorAll('#calendar-body td');
    
    dayCells.forEach(cell => {
        const dayNumber = cell.querySelector('.day-number');
        if (dayNumber) {
            const day = parseInt(dayNumber.textContent);
            const cellDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
            
            // Check if there are events on this date
            const hasEvents = eventsData.some(event => {
                const eventDate = new Date(event.start);
                return eventDate.toDateString() === cellDate.toDateString();
            });
            
            if (hasEvents) {
                cell.classList.add('bg-green-100');
                if (!cell.querySelector('.event-indicator')) {
                    const indicator = document.createElement('div');
                    indicator.className = 'event-indicator w-2 h-2 bg-green-500 rounded-full mx-auto mt-1';
                    cell.appendChild(indicator);
                }
            }
        }
    });
}

function loadUpcomingEvents() {
    fetch('api/scheduler.php?action=get_upcoming_events')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(responseText => {
            if (!responseText || responseText.trim() === '') {
                console.log('Main API returned empty response, trying fallback...');
                // Try fallback API instead of throwing error
                return fetch('api/scheduler_simple.php?action=get_upcoming_events')
                    .then(response => response.text())
                    .then(fallbackText => {
                        if (fallbackText && fallbackText.trim() !== '') {
                            return JSON.parse(fallbackText);
                        }
                        throw new Error('Both APIs returned empty responses');
                    });
            }
            return JSON.parse(responseText);
        })
        .then(result => {
            if (result.success) {
                displayUpcomingEvents(result.data);
            } else {
                displayUpcomingEvents([]);
            }
        })
        .catch(error => {
            console.error('Error loading upcoming events:', error);
            displayUpcomingEvents([]);
        });
}

function displayUpcomingEvents(events) {
    const remindersContainer = document.getElementById('reminders-container');
    if (!remindersContainer) return;
    
    if (events.length === 0) {
        remindersContainer.innerHTML = '<p class="text-gray-500 text-sm">No upcoming events</p>';
        return;
    }
    
    let eventsHTML = '';
    events.forEach(event => {
        const eventDate = new Date(event.start);
        const dateFormatter = new Intl.DateTimeFormat('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: 'numeric'
        });
        const timeFormatter = new Intl.DateTimeFormat('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false 
        });
        const dateStr = dateFormatter.format(eventDate);
        const timeStr = timeFormatter.format(eventDate);
        
        eventsHTML += `
            <div class="reminder-item p-2 border border-gray-200 rounded mb-1 text-sm">
                <div class="font-medium">${event.title}</div>
                <div class="text-gray-600">${dateStr} at ${timeStr}</div>
                ${event.location ? `<div class="text-gray-500">📍 ${event.location}</div>` : ''}
            </div>
        `;
    });
    
    remindersContainer.innerHTML = eventsHTML;
}

// Modal functions
function showAddEventModal() {
    if (!selectedDate) {
        showNotification('Please select a date first', 'warning');
        return;
    }
    
    const modal = document.getElementById('add-event-modal');
    if (modal) {
        // Set default date and time
        const dateInput = document.getElementById('event-date');
        const timeInput = document.getElementById('event-time');
        
        if (dateInput) {
            dateInput.value = selectedDate.toISOString().split('T')[0];
        }
        if (timeInput) {
            timeInput.value = '09:00';
        }
        
        modal.classList.remove('hidden');
    }
}

function hideAddEventModal() {
    const modal = document.getElementById('add-event-modal');
    if (modal) {
        modal.classList.add('hidden');
        clearEventForm();
    }
}

function showAddEventButton() {
    const addButton = document.getElementById('add-event-btn');
    if (addButton) {
        addButton.classList.remove('hidden');
    }
}

function viewEvent(eventId) {
    fetch(`api/scheduler.php?action=get_event&id=${eventId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                currentEvent = result.event;
                showEventDetailsModal();
            } else {
                showNotification('Failed to load event details: ' + result.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading event details:', error);
            showNotification('Error loading event details', 'error');
        });
}

function showEventDetailsModal() {
    if (!currentEvent) return;
    
    const modal = document.getElementById('event-details-modal');
    if (modal) {
        // Populate event details using Intl.DateTimeFormat
        const dateFormatter = new Intl.DateTimeFormat('en-US', { 
            month: 'long', 
            day: 'numeric',
            year: 'numeric'
        });
        const timeFormatter = new Intl.DateTimeFormat('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false 
        });
        
        document.getElementById('event-title-display').textContent = currentEvent.title;
        document.getElementById('event-description-display').textContent = currentEvent.description || 'No description';
        document.getElementById('event-date-display').textContent = dateFormatter.format(new Date(currentEvent.start));
        document.getElementById('event-time-display').textContent = 
            timeFormatter.format(new Date(currentEvent.start)) + 
            ' - ' + 
            timeFormatter.format(new Date(currentEvent.end));
        document.getElementById('event-location-display').textContent = currentEvent.location || 'No location specified';
        
        modal.classList.remove('hidden');
    }
}

function hideEventDetailsModal() {
    const modal = document.getElementById('event-details-modal');
    if (modal) {
        modal.classList.add('hidden');
        currentEvent = null;
    }
}

function editEvent() {
    if (!currentEvent) return;
    
    // Populate edit form with current event data
    document.getElementById('edit-event-title').value = currentEvent.title;
    document.getElementById('edit-event-description').value = currentEvent.description || '';
    document.getElementById('edit-event-date').value = new Date(currentEvent.start).toISOString().split('T')[0];
    document.getElementById('edit-event-time').value = new Date(currentEvent.start).toTimeString().slice(0, 5);
    document.getElementById('edit-event-location').value = currentEvent.location || '';
    
    // Show edit modal
    const editModal = document.getElementById('edit-event-modal');
    if (editModal) {
        editModal.classList.remove('hidden');
    }
    
    // Hide details modal
    hideEventDetailsModal();
}

function hideEditEventModal() {
    const modal = document.getElementById('edit-event-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function deleteEvent() {
    if (!currentEvent) {
        showNotification('No event selected for deletion', 'error');
        return;
    }
    
    if (confirm('Are you sure you want to delete this event?')) {
        fetch('api/scheduler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_event&id=${currentEvent.id}`
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification('Event deleted successfully', 'success');
                hideEventDetailsModal();
                loadEventsData();
                if (selectedDate) {
                    loadEventsForDate(selectedDate);
                }
            } else {
                showNotification('Failed to delete event: ' + result.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting event:', error);
            showNotification('Error deleting event', 'error');
        });
    }
}

// Form handling
function clearEventForm() {
    document.getElementById('event-title').value = '';
    document.getElementById('event-description').value = '';
    document.getElementById('event-location').value = '';
}

function submitEventForm() {
    const formData = {
        title: document.getElementById('event-title').value.trim(),
        description: document.getElementById('event-description').value.trim(),
        location: document.getElementById('event-location').value.trim(),
        date: document.getElementById('event-date').value,
        time: document.getElementById('event-time').value
    };
    
    // Client-side validation
    if (!formData.title) {
        showNotification('Event title is required', 'error');
        return;
    }
    if (!formData.date) {
        showNotification('Event date is required', 'error');
        return;
    }
    if (!formData.time) {
        showNotification('Event time is required', 'error');
        return;
    }
    
    // Combine date and time
    const startDateTime = new Date(`${formData.date}T${formData.time}`);
    const endDateTime = new Date(startDateTime.getTime() + 2 * 60 * 60 * 1000); // Add 2 hours
    
    const eventData = {
        title: formData.title,
        description: formData.description,
        location: formData.location,
        start: startDateTime.toISOString(),
        end: endDateTime.toISOString()
    };
    
    // Submit to server
    fetch('api/scheduler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=create_event&${new URLSearchParams(eventData)}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Event created successfully', 'success');
            hideAddEventModal();
            loadEventsData();
            if (selectedDate) {
                loadEventsForDate(selectedDate);
            }
        } else {
            showNotification('Failed to create event: ' + result.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error creating event:', error);
        showNotification('Error creating event', 'error');
    });
}

function submitEditEventForm() {
    if (!currentEvent) {
        showNotification('No event selected for editing', 'error');
        return;
    }
    
    const formData = {
        title: document.getElementById('edit-event-title').value.trim(),
        description: document.getElementById('edit-event-description').value.trim(),
        location: document.getElementById('edit-event-location').value.trim(),
        date: document.getElementById('edit-event-date').value,
        time: document.getElementById('edit-event-time').value
    };
    
    // Client-side validation
    if (!formData.title) {
        showNotification('Event title is required', 'error');
        return;
    }
    if (!formData.date) {
        showNotification('Event date is required', 'error');
        return;
    }
    if (!formData.time) {
        showNotification('Event time is required', 'error');
        return;
    }
    
    // Combine date and time
    const startDateTime = new Date(`${formData.date}T${formData.time}`);
    const endDateTime = new Date(startDateTime.getTime() + 2 * 60 * 60 * 1000); // Add 2 hours
    
    const eventData = {
        title: formData.title,
        description: formData.description,
        location: formData.location,
        start: startDateTime.toISOString(),
        end: endDateTime.toISOString()
    };
    
    // Submit to server
    fetch('api/scheduler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_event&id=${currentEvent.id}&${new URLSearchParams(eventData)}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Event updated successfully', 'success');
            hideEditEventModal();
            loadEventsData();
            if (selectedDate) {
                loadEventsForDate(selectedDate);
            }
        } else {
            showNotification('Failed to update event: ' + result.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating event:', error);
        showNotification('Error updating event', 'error');
    });
}

// Utility functions
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-black' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}

function initializeEventListeners() {
    // Calendar navigation
    const prevBtn = document.getElementById('prev-month');
    const nextBtn = document.getElementById('next-month');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => navigateCalendar('prev'));
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => navigateCalendar('next'));
    }
    
    // Add event button
    const addBtn = document.getElementById('add-event-btn');
    if (addBtn) {
        addBtn.addEventListener('click', showAddEventModal);
    }
    
    // Modal close buttons
    const closeButtons = document.querySelectorAll('[data-modal-close]');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    });
}

// Make functions globally available
window.generateCalendar = generateCalendar;
window.navigateCalendar = navigateCalendar;
window.selectDate = selectDate;
window.showAddEventModal = showAddEventModal;
window.hideAddEventModal = hideAddEventModal;
window.viewEvent = viewEvent;
window.showEventDetailsModal = showEventDetailsModal;
window.hideEventDetailsModal = hideEventDetailsModal;
window.editEvent = editEvent;
window.hideEditEventModal = hideEditEventModal;
window.deleteEvent = deleteEvent;
window.submitEventForm = submitEventForm;
window.submitEditEventForm = submitEditEventForm;
