<?php
// Load data using the new SchedulerManager class
require_once 'classes/SchedulerManager.php';
require_once 'classes/DateTimeUtility.php';

// Initialize scheduler manager
$schedulerManager = new SchedulerManager();

// Load meetings data (single source of truth - database only)
$meetingsResult = $schedulerManager->loadMeetingsData();
$meetings = $meetingsResult['success'] ? $meetingsResult['data'] : [];

// Load upcoming events for reminders
$upcomingResult = $schedulerManager->getUpcomingEvents(5);
$upcomingEvents = $upcomingResult['success'] ? $upcomingResult['data'] : [];

// No trash meetings - using database with proper deletion
// Format meetings data for display (single source - database only)
$allItems = [];
foreach ($meetings as $meeting) {
    $allItems[] = [
        'id' => $meeting['id'],
        'title' => $meeting['title'],
        'meeting_date' => DateTimeUtility::formatDate($meeting['start']),
        'meeting_time' => DateTimeUtility::formatTime($meeting['start']),
        'end_date' => DateTimeUtility::formatDate($meeting['end']),
        'end_time' => DateTimeUtility::formatTime($meeting['end']),
        'description' => $meeting['description'] ?? '',
        'is_all_day' => '0',
        'color' => 'text-blue-600',
        'organizer' => 'LILAC',
        'location' => $meeting['location'] ?? ''
    ];
}
// Events are already included in meetings data (single source of truth)

// Data is already loaded above using SchedulerManager
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Scheduler</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>(function(){ try { document.documentElement.classList.add('sidebar-prep'); } catch(e){} })();</script>
    <style id="sidebar-prep-style">.sidebar-prep #sidebar, .sidebar-prep nav.modern-nav, .sidebar-prep #main-content{ transition: none !important; }</style>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {}
            }
        }
    </script>
    <style>
        /* Remove all focus styling from scheduler link */
        .scheduler-link:focus,
        .scheduler-link:focus-visible,
        .scheduler-link:active {
            outline: none !important;
            box-shadow: none !important;
            border: none !important;
            ring: none !important;
        }

        /* Typography */
        body { font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", sans-serif; }

        /* Calendar day cells */
        .calendar-day {
            border: 1px solid rgba(17, 24, 39, 0.06);
            background-image: linear-gradient(to bottom, rgba(255, 255, 255, 0.0), rgba(17, 24, 39, 0.02));
        }
        .dark .calendar-day {
            border-color: rgba(255,255,255,0.08);
            background-image: linear-gradient(to bottom, rgba(34, 40, 49, 0.0), rgba(255,255,255,0.02));
        }

        /* Event chips inside month calendar */
        .event-chip {
            border-radius: 0.375rem;
            font-size: 10px;
            padding: 2px 6px;
            line-height: 1;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Weekly grid subtle lines */
        .day-column-bg {
            background-image: repeating-linear-gradient(to bottom, rgba(17,24,39,0.04), rgba(17,24,39,0.04) 1px, transparent 1px, transparent 40px);
        }
        .dark .day-column-bg {
            background-image: repeating-linear-gradient(to bottom, rgba(255,255,255,0.06), rgba(255,255,255,0.06) 1px, transparent 1px, transparent 40px);
        }

        /* FAB label hidden (disable reveal) */
        #view-switch-btn span { display: none !important; }
        #view-switch-btn:hover span, #view-switch-btn:focus span { display: none !important; }

        /* Toggle switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 42px;
            height: 22px;
        }
        .switch input { display: none; }
        .slider {
            position: absolute; cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #d1d5db; transition: .2s; border-radius: 9999px;
        }
        .slider:before {
            position: absolute; content: "";
            height: 18px; width: 18px; left: 2px; top: 2px;
            background-color: white; transition: .2s; border-radius: 9999px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        input:checked + .slider { background-color: #7c3aed; }
        input:checked + .slider:before { transform: translateX(20px); }
    </style>
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js?v=1.1"></script>
    <script src="js/date-time-utility.js"></script>
    <script src="js/scheduler-management.js"></script>
    <script>
        // Force cache refresh - version 1.1
        // Scheduler script loaded
        
        // Ensure LILAC enhancements are loaded
        if (typeof window.lilacNotifications === 'undefined') {
            console.warn('LILAC notifications not loaded, waiting for initialization...');
            // Wait for the enhancement system to initialize
            const checkLILAC = setInterval(() => {
                if (typeof window.lilacNotifications !== 'undefined') {
                    // LILAC notifications loaded
                    clearInterval(checkLILAC);
                }
            }, 100);
        }
        // Initialize Scheduler functionality
        let currentMeetings = [];
        let currentWeek = new Date();
        // selectedDate is now managed by scheduler-management.js
        let currentView = 'week'; // 'day', 'week', 'month'
        let clickedDateString = null; // Track the exact clicked date string
        
        // Event type filters
        let eventFilters = {
            'meeting': true,
            'holiday': true,
            'ui-ux': true,
            'developer': true,
            'data-science': true,
            'marketing': true
        };
        
        console.log('Event filters:', eventFilters);

        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar state is now handled globally by LILACSidebar
            loadMeetings();
            initializeCalendar();
            renderMiniCalendar();
            initializeEventListeners();
            updateCurrentDate();
            
            // Prefetch table data so the meetings table is ready on refresh
            try { loadDocuments(); } catch (e) { /* ignore */ }
            
            // Set default view to calendar
            setActiveView('calendar');
            // Show month date grid by default
            setView('month');
            
            // Load trash count
            loadTrashCount();
            
            // Set today as the initially selected date
            const today = new Date().toISOString().split('T')[0];
            setTimeout(() => selectDate(today), 100);
            
            // Update date every minute
            setInterval(updateCurrentDate, 60000);
            
            // Ensure LILAC notifications appear below navbar
            setTimeout(function() {
                if (window.lilacNotifications && window.lilacNotifications.container) {
                    window.lilacNotifications.container.style.top = '80px';
                    window.lilacNotifications.container.style.zIndex = '99999';
                }
            }, 500);
            
            // Debug LILAC notifications initialization
            // DOM loaded - LILAC notifications initialized
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
            // View toggle buttons
            const viewButtons = document.querySelectorAll('[data-view]');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const view = this.dataset.view;
                    setView(view);
                });
            });

            // Navigation buttons
            const prevBtn = document.getElementById('prev-week');
            const nextBtn = document.getElementById('next-week');
            if (prevBtn) prevBtn.addEventListener('click', () => navigateWeek(-1));
            if (nextBtn) nextBtn.addEventListener('click', () => navigateWeek(1));

            // Event type filters
            const filterToggles = document.querySelectorAll('[data-filter]');
            filterToggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const filterType = this.dataset.filter;
                    eventFilters[filterType] = this.checked;
                    renderSchedule();
                });
            });

            // Add event button
            const addEventBtn = document.getElementById('add-event-btn');
            if (addEventBtn) {
                addEventBtn.addEventListener('click', showAddEventModal);
            }

            // Responsive floating button on scroll (only if button exists)
            let lastScrollTop = 0;
            const floatingBtn = document.getElementById('view-switch-btn');
            const floatingBtnContainer = floatingBtn?.parentElement;
            
            if (floatingBtn && floatingBtnContainer) {
                window.addEventListener('scroll', function() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    if (scrollTop > lastScrollTop && scrollTop > 100) {
                        // Scrolling down - move button up (current position above footer)
                        floatingBtnContainer.style.bottom = '80px'; // bottom-20 equivalent
                        floatingBtnContainer.style.transition = 'bottom 0.3s ease';
                    } else {
                        // Scrolling up - move button down (old position at bottom)
                        floatingBtnContainer.style.bottom = '16px'; // bottom-4 equivalent
                        floatingBtnContainer.style.transition = 'bottom 0.3s ease';
                    }
                    
                    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
                });
            }

            // Calendar day clicks
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('calendar-day')) {
                    clickedDateString = e.target.dataset.date; // Set global variable
                    const clickedDay = e.target.textContent.trim();
                    
                    // Date selection logic
                    
                    // Create date without timezone issues by parsing the date string directly
                    const [year, month, day] = clickedDateString.split('-').map(Number);
                    const date = new Date(year, month - 1, day); // month is 0-indexed
                    
                    // Date object created
                    
                    selectedDate = date;
                    
                    // Navigate to the month of the clicked date if it's different from current month
                    const currentMonth = new Date().getMonth();
                    const currentYear = new Date().getFullYear();
                    const clickedMonth = date.getMonth();
                    const clickedYear = date.getFullYear();
                    
                    if (clickedMonth !== currentMonth || clickedYear !== currentYear) {
                        // Navigating to different month/year
                        // Update the calendar to show the clicked date's month
                        selectedDate = date;
                    }
                    
                    // Force immediate re-render to show selection
                    setTimeout(() => {
                        renderCalendar(); // Re-render calendar to update selection
                        // Calendar re-rendered
                    }, 10);
                    
                    // Also update the weekly view date selection
                    selectDate(clickedDateString);
                    
                    // Always update the weekly view to show the week containing the clicked date
                    currentWeek = new Date(date);
                    // Updated currentWeek
                    
                    // Force the calendar view to be active and update the weekly view
                    setActiveView('calendar');
                    
                    // Force the schedule to re-render with the new week immediately
                    setTimeout(() => {
                        renderSchedule();
                        // Schedule re-rendered
                        
                        // Force update the date range display manually
                        const dateRangeElement = document.getElementById('date-range');
                        if (dateRangeElement) {
                            const weekStart = getWeekStart(currentWeek);
                            const weekEnd = new Date(weekStart);
                            weekEnd.setDate(weekStart.getDate() + 6);
                            
                            dateRangeElement.textContent = `${weekStart.toLocaleDateString('en-US', {
                                month: 'long',
                                day: 'numeric',
                                year: 'numeric'
                            })} - ${weekEnd.toLocaleDateString('en-US', {
                                month: 'long',
                                day: 'numeric',
                                year: 'numeric'
                            })}`;
                            // Date range updated
                        }
                        
                        // Also re-render after a short delay to ensure DOM updates
                        setTimeout(() => {
                            renderSchedule();
                            // Schedule re-rendered after delay
                        }, 100);
                    }, 10);
                }
            });

            // Floating view switch button
            const viewSwitchBtn = document.getElementById('view-switch-btn');
            const calendarView = document.getElementById('calendar-view');
            const meetingsView = document.getElementById('meetings-view');

            if (viewSwitchBtn) {
                viewSwitchBtn.addEventListener('click', function() {
                    // Convert FAB to open Add Event modal
                    showAddEventModal();
                });
                viewSwitchBtn.setAttribute('title', 'Schedule a Meeting');
            }





            // Form submission
            const meetingForm = document.getElementById('meeting-form');
            if (meetingForm) {
                meetingForm.addEventListener('submit', handleFormSubmit);
            }

            // Set default date to today
            const meetingDate = document.getElementById('meeting-date');
            if (meetingDate) {
                const today = new Date().toISOString().split('T')[0];
                meetingDate.value = today;
            }

            // Set default time to 12:00 PM
            const meetingTime = document.getElementById('meeting-time');
            if (meetingTime) {
                meetingTime.value = '12:00';
            }

            // Quick reminder button
            const quickReminderBtn = document.getElementById('quick-reminder-btn');
            if (quickReminderBtn) {
                quickReminderBtn.addEventListener('click', handleQuickReminder);
            }

            // Jump to date picker
            const jumpDate = document.getElementById('jump-date');
            if (jumpDate) {
                jumpDate.addEventListener('change', function() {
                    if (!this.value) return;
                    const d = new Date(this.value);
                    selectedDate = new Date(d);
                    currentWeek = new Date(d);
                    clickedDateString = this.value;
                    renderCalendar();
                    renderMiniCalendar();
                    renderSchedule();
                });
            }

            // Dark mode disabled: Always use light theme
            try { localStorage.setItem('darkMode', 'false'); } catch (e) {}
            document.documentElement.classList.remove('dark');
        }

        // Function to handle date selection
        function selectDate(dateString) {
            // Remove previous selection
            const allDateHeaders = document.querySelectorAll('[data-date]');
            allDateHeaders.forEach(header => {
                header.classList.remove('bg-blue-100', 'dark:bg-blue-900', 'border-blue-300', 'dark:border-blue-600');
                header.classList.add('bg-gray-50', 'dark:bg-gray-700');
            });

            // Highlight selected date
            const selectedHeader = document.querySelector(`[data-date="${dateString}"]`);
            if (selectedHeader) {
                selectedHeader.classList.remove('bg-gray-50', 'dark:bg-gray-700');
                selectedHeader.classList.add('bg-blue-100', 'dark:bg-blue-900', 'border-blue-300', 'dark:border-blue-600');
            }

            // Store selected date for potential use (like adding events to specific date)
            window.selectedDate = dateString;
            
            // Date selected
        }

        // Function to show event details modal
        function showEventDetails(id, title, description, date, startTime, endTime, isAllDay, color) {
            // Showing event details
            // Store event data for edit/delete operations
            window.currentEventData = { id, title, description, date, startTime, endTime, isAllDay, color };
            
            // Format date
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Format time
            let timeDisplay = '';
            if (isAllDay === '1' || isAllDay === true) {
                timeDisplay = 'All Day Event';
            } else if (startTime && endTime) {
                const start = formatTime(startTime);
                const end = formatTime(endTime);
                timeDisplay = `${start} - ${end}`;
            } else if (startTime) {
                timeDisplay = formatTime(startTime);
            } else {
                timeDisplay = 'No time specified';
            }
            
            // Update modal content
            document.getElementById('event-title').textContent = title;
            console.log('Setting description in modal:', description);
            document.getElementById('event-description').textContent = description || 'No description provided';
            document.getElementById('event-date').textContent = formattedDate;
            document.getElementById('event-time').textContent = timeDisplay;
            

            
            // Show modal
            document.getElementById('event-details-modal').classList.remove('hidden');
        }

        // Function to close event details modal
        function closeEventDetails() {
            document.getElementById('event-details-modal').classList.add('hidden');
            window.currentEventData = null;
        }

        // Function to show custom confirmation dialog
        function showCustomConfirmDialog(eventTitle, eventId) {
            const dialog = document.getElementById('custom-confirm-dialog');
            const message = document.getElementById('custom-confirm-message');
            const confirmBtn = document.getElementById('custom-confirm-btn');
            const cancelBtn = document.getElementById('custom-confirm-cancel-btn');
            
            // Set the message
            message.textContent = `Are you sure you want to move "${eventTitle}" to trash?`;
            
            // Show the dialog
            dialog.classList.remove('hidden');
            
            // Handle confirm button click
            confirmBtn.onclick = function() {
                console.log('User confirmed moving to trash');
                // Hide the custom dialog
                dialog.classList.add('hidden');
                // Force close the event details modal
                document.getElementById('event-details-modal').classList.add('hidden');
                
                // Remove from currentMeetings array first
                const meetingIndex = currentMeetings.findIndex(meeting => meeting.id == eventId);
                if (meetingIndex !== -1) {
                    console.log('Removing event from currentMeetings array:', eventId);
                    currentMeetings.splice(meetingIndex, 1);
                }
                
                // Immediately update the calendar view
                renderCalendar();
                renderSchedule();
                updateReminders();
                loadStats();
                
                // Show success message
                if (window.lilacNotifications) {
                    window.lilacNotifications.success('Event moved to trash successfully!');
                } else {
                    alert('Event moved to trash successfully!');
                }
                
                // Then try to delete from backend
                deleteMeeting(eventId);
            };
            
            // Handle cancel button click
            cancelBtn.onclick = function() {
                console.log('Move to trash cancelled');
                dialog.classList.add('hidden');
            };
            
            // Handle clicking outside the dialog
            dialog.onclick = function(e) {
                if (e.target === dialog) {
                    dialog.classList.add('hidden');
                }
            };
        }

        // Function to edit event
        function editEvent() {
            console.log('editEvent function called');
            console.log('window.currentEventData:', window.currentEventData);
            
            if (window.currentEventData) {
                console.log('Edit event:', window.currentEventData);
                showEditEventModal();
            } else {
                console.error('No current event data available');
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('No event data available for editing');
                } else {
                    alert('No event data available for editing');
                }
            }
        }

        // Function to show edit event modal
        function showEditEventModal() {
            console.log('showEditEventModal function called');
            const eventData = window.currentEventData;
            console.log('eventData:', eventData);
            if (!eventData) {
                console.error('No event data available in showEditEventModal');
                return;
            }

            // Pre-fill the add event modal with current event data
            console.log('Attempting to pre-fill form fields...');
            
            const eventNameField = document.getElementById('event-name');
            const eventDescriptionField = document.getElementById('event-description-input');
            const eventDateStartField = document.getElementById('event-date-start');
            const eventDateEndField = document.getElementById('event-date-end');
            
            console.log('Form fields found:', {
                eventNameField: !!eventNameField,
                eventDescriptionField: !!eventDescriptionField,
                eventDateStartField: !!eventDateStartField,
                eventDateEndField: !!eventDateEndField
            });
            
            if (eventNameField) eventNameField.value = eventData.title;
            if (eventDescriptionField) eventDescriptionField.value = eventData.description || '';
            if (eventDateStartField) eventDateStartField.value = eventData.date;
            if (eventDateEndField) eventDateEndField.value = eventData.date; // Use same date for end date
            
            // Handle time fields
            if (eventData.isAllDay === '1' || eventData.isAllDay === true) {
                document.getElementById('event-all-day').checked = true;
                document.getElementById('event-time-start').value = '';
                document.getElementById('event-time-end').value = '';
            } else {
                document.getElementById('event-all-day').checked = false;
                if (eventData.startTime) {
                    document.getElementById('event-time-start').value = eventData.startTime;
                }
                if (eventData.endTime) {
                    document.getElementById('event-time-end').value = eventData.endTime;
                }
            }

                            // Set the color
                const colorInput = document.querySelector(`input[name="event-color"][value="${eventData.color || 'blue'}"]`);
                console.log('Setting color for edit:', {
                    eventDataColor: eventData.color,
                    colorInput: colorInput,
                    colorInputValue: colorInput?.value,
                    allColorInputs: document.querySelectorAll('input[name="event-color"]').length
                });
                
                // Log all available color options
                document.querySelectorAll('input[name="event-color"]').forEach((input, index) => {
                    console.log(`Available color ${index}: value="${input.value}"`);
                });
                
                if (colorInput) {
                    colorInput.checked = true;
                    // Update visual selection using the proper function
                    updateColorSelection();
                    console.log('Color set successfully');
                } else {
                    // If the color is not found in the default options, it might be a custom color
                    // Check if it's a color variant (like red-500, blue-600, etc.)
                    if (eventData.color && eventData.color.includes('-')) {
                        console.log('Custom color detected, creating custom color option:', eventData.color);
                        // Get the hex value for this color
                        const hexValue = getColorHex(eventData.color);
                        // Create a custom color option
                        selectCustomColor(eventData.color, hexValue);
                    } else {
                        console.log('Color input not found, using default blue');
                        // Set default blue if color not found
                        const blueInput = document.querySelector('input[name="event-color"][value="blue"]');
                        if (blueInput) {
                            blueInput.checked = true;
                            updateColorSelection();
                        }
                    }
                }

            // Set edit mode variables
            window.isEditMode = true;
            window.editEventId = eventData.id;
            console.log('Set edit mode:', { isEditMode: window.isEditMode, editEventId: window.editEventId });
            
            // Change modal title and button
            const modalTitle = document.getElementById('add-event-modal-title');
            const submitBtn = document.getElementById('add-event-submit-btn');
            const addEventModal = document.getElementById('add-event-modal');
            
            console.log('Modal elements found:', {
                modalTitle: !!modalTitle,
                submitBtn: !!submitBtn,
                addEventModal: !!addEventModal
            });
            
            if (modalTitle) modalTitle.textContent = 'Edit Event';
            if (submitBtn) submitBtn.textContent = 'Update Event';
            
            // Store that we're in edit mode
            window.isEditMode = true;
            window.editEventId = eventData.id;
            console.log('Edit mode set:', { isEditMode: window.isEditMode, editEventId: window.editEventId });

            // Show the modal
            if (addEventModal) {
                addEventModal.classList.remove('hidden');
                console.log('Add event modal shown');
            } else {
                console.error('Add event modal not found');
            }
            
            // Close the event details modal
            closeEventDetails();
        }

        // Function to handle edit event submission
        function handleEditEventSubmit() {
            // Edit event submission
            const eventData = window.currentEventData;
            console.log('Event data:', eventData);
            console.log('Edit mode:', window.isEditMode);
            console.log('Edit event ID:', window.editEventId);
            
            if (!eventData || !window.isEditMode) {
                console.error('Missing event data or not in edit mode');
                return;
            }

            const title = document.getElementById('event-name').value.trim();
            const description = document.getElementById('event-description-input').value.trim();
            const startDate = document.getElementById('event-date-start').value;
            const endDate = document.getElementById('event-date-end').value;
            const isAllDay = document.getElementById('event-all-day').checked;
            const startTime = isAllDay ? null : document.getElementById('event-time-start').value;
            const endTime = isAllDay ? null : document.getElementById('event-time-end').value;
            const selectedColorInput = document.querySelector('input[name="event-color"]:checked');
            const color = selectedColorInput?.value || 'blue';

            console.log('Validation check:', { title, startDate, startTime, endTime, isAllDay });
            
            if (!title || !startDate) {
                console.error('Validation failed: missing title or start date');
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Please fill in required fields (title and date)');
                } else {
                    alert('Please fill in required fields (title and date)');
                }
                return;
            }

            if (!isAllDay && (!startTime || !endTime)) {
                console.error('Validation failed: missing start or end time');
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Please fill in start and end times for timed events');
                } else {
                    alert('Please fill in start and end times for timed events');
                }
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', window.editEventId);
            formData.append('title', title);
            formData.append('description', description);
            formData.append('date', startDate);
            formData.append('time', isAllDay ? '00:00' : startTime);
            formData.append('end_date', endDate);
            formData.append('end_time', isAllDay ? '23:59' : endTime);
            formData.append('is_all_day', isAllDay ? '1' : '0');
            formData.append('color', color);
            
            console.log('Form data being sent:', {
                action: 'update',
                id: window.editEventId,
                title: title,
                description: description,
                descriptionLength: description ? description.length : 0,
                date: startDate,
                time: isAllDay ? '00:00' : startTime,
                end_date: endDate,
                end_time: isAllDay ? '23:59' : endTime,
                is_all_day: isAllDay ? '1' : '0',
                color: color
            });
            console.log('FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.json();
            })
            .then(data => {
                console.log('Edit event response:', data);
                if (data.success) {
                    closeAddEventModal();
                    loadMeetings();
                    updateReminders();
                    loadStats();
                    // Event updated successfully - no notification needed
                    // Reset edit mode
                    window.isEditMode = false;
                    window.editEventId = null;
                } else {
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error updating event:', error);
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Error updating event');
                } else {
                    alert('Error updating event');
                }
            });
        }

        // Function to delete event from details modal
        function deleteEventFromDetails() {
            console.log('deleteEventFromDetails called');
            console.log('window.currentEventData:', window.currentEventData);
            
            if (window.currentEventData) {
                const eventId = window.currentEventData.id;
                const eventTitle = window.currentEventData.title;
                
                console.log('Attempting to move event to trash:', eventId, eventTitle);
                console.log('Event ID type:', typeof eventId);
                console.log('Event ID value:', eventId);
                
                // Show custom confirmation dialog
                showCustomConfirmDialog(eventTitle, eventId);
            } else {
                console.error('No current event data available');
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('No event data available for deletion');
                } else {
                    alert('No event data available for deletion');
                }
            }
        }

        // Helper function to get color hex value
        function getColorHex(colorName) {
            const colorMap = {
                // Basic colors
                'red': '#ef4444', 'red-500': '#ef4444', 'red-600': '#dc2626', 'red-700': '#b91c1c', 'red-800': '#991b1b',
                'pink': '#ec4899', 'pink-500': '#ec4899', 'pink-600': '#db2777', 'pink-700': '#be185d', 'pink-800': '#9d174d',
                'purple': '#a855f7', 'purple-500': '#a855f7', 'purple-600': '#9333ea', 'purple-700': '#7c3aed', 'purple-800': '#6b21a8',
                'blue': '#3b82f6', 'blue-500': '#3b82f6', 'blue-600': '#2563eb', 'blue-700': '#1d4ed8', 'blue-800': '#1e40af',
                'cyan': '#06b6d4', 'cyan-500': '#06b6d4', 'cyan-600': '#0891b2',
                'teal': '#14b8a6', 'teal-500': '#14b8a6', 'teal-600': '#0d9488',
                'green': '#22c55e', 'green-500': '#22c55e', 'green-600': '#16a34a', 'green-700': '#15803d', 'green-800': '#166534',
                'yellow': '#eab308', 'yellow-500': '#eab308', 'yellow-600': '#ca8a04',
                'amber': '#f59e0b', 'amber-500': '#f59e0b', 'amber-600': '#d97706', 'amber-700': '#b45309', 'amber-800': '#92400e',
                'orange': '#f97316', 'orange-500': '#f97316', 'orange-600': '#ea580c', 'orange-700': '#c2410c', 'orange-800': '#9a3412',
                'brown': '#b45309', 'brown-500': '#b45309', 'brown-600': '#d97706', 'brown-700': '#b45309', 'brown-800': '#92400e',
                'gray': '#6b7280', 'gray-500': '#6b7280', 'gray-600': '#4b5563',
                // Additional color variants for completeness
                'indigo': '#6366f1', 'indigo-500': '#6366f1', 'indigo-600': '#4f46e5',
                'lime': '#84cc16', 'lime-500': '#84cc16', 'lime-600': '#65a30d',
                'emerald': '#10b981', 'emerald-500': '#10b981', 'emerald-600': '#059669',
                'rose': '#f43f5e', 'rose-500': '#f43f5e', 'rose-600': '#e11d48',
                'violet': '#8b5cf6', 'violet-500': '#8b5cf6', 'violet-600': '#7c3aed',
                'fuchsia': '#d946ef', 'fuchsia-500': '#d946ef', 'fuchsia-600': '#c026d3',
                'sky': '#0ea5e9', 'sky-500': '#0ea5e9', 'sky-600': '#0284c7',
                'slate': '#64748b', 'slate-500': '#64748b', 'slate-600': '#475569',
                'zinc': '#71717a', 'zinc-500': '#71717a', 'zinc-600': '#52525b',
                'neutral': '#737373', 'neutral-500': '#737373', 'neutral-600': '#525252',
                'stone': '#78716c', 'stone-500': '#78716c', 'stone-600': '#57534e'
            };
            
            return colorMap[colorName] || '#3b82f6'; // Default to blue if color not found
        }

        function showNotification(message, type = 'info') {
            // Simple notification function
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 4px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                max-width: 300px;
                word-wrap: break-word;
                ${type === 'error' ? 'background-color: #ef4444;' : 'background-color: #3b82f6;'}
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }

        function loadMeetings() {
            console.log('Loading meetings and events...');
            
            // For now, use the fallback method which works reliably
            // TODO: Fix API call issues later
            loadMeetingsFallback();
        }

        function loadMeetingsFallback() {
            console.log('Using fallback PHP meetings data...');
            try {
                // Use PHP data as fallback
                const meetingsData = <?php echo json_encode($meetingsData); ?>;
                console.log('Loaded fallback meetings data:', meetingsData);
                
                let allItems = [];
                
                // Process meetings and events
                if (Array.isArray(meetingsData)) {
                    allItems = meetingsData.map(item => ({
                        ...item,
                        type: String(item.id).startsWith('event_') ? 'event' : 'meeting',
                        color: item.color || (String(item.id).startsWith('event_') ? 'green' : 'blue'),
                        startTime: item.meeting_time,
                        endTime: item.end_time || calculateEndTime(item.meeting_time, String(item.id).startsWith('event_') ? 120 : 60),
                        date: item.meeting_date,
                        dateEnd: item.end_date || item.meeting_date
                    }));
                }
                
                currentMeetings = allItems;
                console.log('Combined meetings and events:', currentMeetings);
                renderSchedule();
                renderMiniCalendar();
                updateReminders();
                loadStats();
                
            } catch (error) {
                console.error('Error loading meetings and events:', error);
                currentMeetings = [];
                showNotification('Failed to load schedule data. Please refresh the page.', 'error');
            }
        }

        function displayMeetings(meetingsData) {
            console.log('Displaying meetings data:', meetingsData);
            
            let allItems = [];
            
            // Process meetings and events
            if (Array.isArray(meetingsData)) {
                allItems = meetingsData.map(item => ({
                    ...item,
                    type: String(item.id).startsWith('event_') ? 'event' : 'meeting',
                    color: item.color || (String(item.id).startsWith('event_') ? 'green' : 'blue'),
                    startTime: item.meeting_time,
                    endTime: item.end_time || calculateEndTime(item.meeting_time, String(item.id).startsWith('event_') ? 120 : 60),
                    date: item.meeting_date,
                    dateEnd: item.end_date || item.meeting_date
                }));
            }
            
            currentMeetings = allItems;
            console.log('Combined meetings and events:', currentMeetings);
            renderSchedule();
            renderMiniCalendar();
            updateReminders();
            loadStats();
        }

        function calculateEndTime(startTime, durationMinutes) {
            if (!startTime) return '';
            const [hours, minutes] = startTime.split(':').map(Number);
            const endDate = new Date();
            endDate.setHours(hours, minutes + durationMinutes, 0, 0);
            return endDate.toTimeString().slice(0, 5);
        }

        function initializeCalendar() {
            renderCalendar();
            initializeCalendarSwipe();
            initializeCalendarKeyboard();
            renderMiniCalendar();
        }

        function renderCalendar() {
            const calendarContainer = document.getElementById('calendar-grid');
            if (!calendarContainer) return;

            const year = selectedDate.getFullYear();
            const month = selectedDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            // Get meetings for the current month to show event indicators
            const startDateString = firstDay.toISOString().split('T')[0];
            const endDateString = lastDay.toISOString().split('T')[0];
            
            // Use PHP data directly for calendar
            try {
                const meetingsData = <?php echo json_encode($meetingsData); ?>;
                console.log('Calendar data:', meetingsData);
                
                // Filter meetings for the current month
                const meetings = Array.isArray(meetingsData) ? meetingsData.filter(meeting => {
                    if (!meeting.meeting_date) return false;
                    const meetingDate = new Date(meeting.meeting_date);
                    return meetingDate >= startDate && meetingDate <= endDate;
                }) : [];
                
                const daysWithEvents = new Map(); // Use Map to store date -> count
                
                // Create a map of dates to event counts
                meetings.forEach(meeting => {
                    if (meeting.meeting_date) {
                        const currentCount = daysWithEvents.get(meeting.meeting_date) || 0;
                        daysWithEvents.set(meeting.meeting_date, currentCount + 1);
                        console.log('Added event for date:', meeting.meeting_date, 'Total events for this date:', currentCount + 1);
                    }
                });
                
                console.log('Days with events:', Object.fromEntries(daysWithEvents));
                renderCalendarDays(daysWithEvents, meetings);
                
            } catch (error) {
                console.error('Error fetching meetings for calendar:', error);
                // Render calendar with no event markers when API/database is unavailable
                renderCalendarDays(new Map(), []);
            }
        }

        function renderCalendarDays(daysWithEvents, meetings = []) {
            const calendarContainer = document.getElementById('calendar-grid');
            if (!calendarContainer) return;

            const year = selectedDate.getFullYear();
            const month = selectedDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            let calendarHTML = '';
            
            // Days of week header
            const daysOfWeek = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
            daysOfWeek.forEach(day => {
                calendarHTML += `<div class=\"text-[11px] font-medium text-gray-500 dark:text-gray-300 text-center py-0.5\">${day}</div>`;
            });

            // Calendar days
            for (let i = 0; i < 42; i++) {
                const currentDate = new Date(startDate);
                currentDate.setDate(startDate.getDate() + i);
                
                const isCurrentMonth = currentDate.getMonth() === month;
                
                // Compare dates using YYYY-MM-DD format to avoid timezone issues
                const currentDateString = currentDate.toISOString().split('T')[0];
                const todayString = new Date().toISOString().split('T')[0];
                
                // Use the global clickedDateString for precise comparison
                const isToday = currentDateString === todayString;
                const isSelected = clickedDateString ? currentDateString === clickedDateString : false;
                const eventCount = daysWithEvents.get(currentDateString) || 0;
                const hasEvent = eventCount > 0;
                
                // Debug logging for event detection
                if (hasEvent) {
                    console.log('Day has event:', currentDateString, 'Date:', currentDate.getDate(), 'Count:', eventCount);
                }
                
                let dayClass = 'calendar-day text-xs p-1 h-20 flex flex-col items-start justify-start cursor-pointer hover:bg-red-100 dark:hover:bg-red-900 hover:text-red-700 dark:hover:text-red-300 rounded text-blue-600 dark:text-blue-300 font-medium relative';
                if (!isCurrentMonth) dayClass += ' text-gray-300 dark:text-gray-500';
                if (isSelected) {
                    dayClass += ' bg-red-600 text-white font-bold shadow-lg border-2 border-red-700';
                } else if (isToday) {
                    dayClass += ' bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 font-semibold ring-1 ring-purple-400';
                }
                
                // Add event indicator dot in the top-right corner
                let eventDot = '';
                if (hasEvent) {
                    eventDot = `<div class=\"absolute top-0.5 right-0.5 w-[5px] h-[5px] bg-red-500 rounded-full\" title=\"${eventCount} event${eventCount > 1 ? 's' : ''} scheduled\"></div>`;
                }

                // Build event chips for this date (limit to 2)
                const meetingsForDay = (meetings || []).filter(m => {
                    const start = m.meeting_date;
                    const end = m.end_date || m.meeting_date;
                    return start <= currentDateString && end >= currentDateString;
                });
                let chipsHTML = '';
                if (meetingsForDay.length > 0) {
                    const toShow = meetingsForDay.slice(0, 2);
                    toShow.forEach(m => {
                        const classes = getEventColorClasses(m.color || 'blue');
                        const title = (m.title || m.meeting_title || 'Event').replace(/'/g, "\\'").replace(/\"/g, '&quot;');
                        chipsHTML += `
                            <div class=\"event-chip ${classes.background} ${classes.hover || ''} text-white flex items-center mt-0.5\" title=\"${title}\">
                                <svg class=\"w-3 h-3 mr-1 opacity-90\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M8 7V3m8 4V3M5 8h14M5 21h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z\"/></svg>
                                <span class=\"truncate\">${title}</span>
                            </div>`;
                    });
                    if (meetingsForDay.length > 2) {
                        chipsHTML += `<div class=\"text-[10px] text-purple-600 dark:text-purple-300 mt-0.5\">+${meetingsForDay.length - 2} more</div>`;
                    }
                }
                
                calendarHTML += `
                    <div class=\"${dayClass}\" data-date=\"${currentDate.toISOString().split('T')[0]}\" onclick=\"selectCalendarDate('${currentDate.toISOString().split('T')[0]}')\">
                        <div class=\"w-full flex items-center justify-between\"> 
                            <span>${currentDate.getDate()}</span>
                            ${eventDot}
                        </div>
                        <div class=\"w-full space-y-0.5 mt-0.5\">${chipsHTML}</div>
                    </div>
                `;
            }

            calendarContainer.innerHTML = calendarHTML;
            
            // Update month/year display
            const monthYearElement = document.getElementById('calendar-month-year');
            if (monthYearElement) {
                monthYearElement.textContent = selectedDate.toLocaleDateString('en-US', {
                    month: 'long',
                    year: 'numeric'
                });
            }
        }



        function setView(view) {
            currentView = view;
            
            // Update active button
            document.querySelectorAll('[data-view]').forEach(btn => {
                btn.classList.remove('bg-purple-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });
            
            const activeBtn = document.querySelector(`[data-view="${view}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('bg-gray-200', 'text-gray-700');
                activeBtn.classList.add('bg-purple-600', 'text-white');
            }
            
            // Toggle containers
            const monthContainer = document.getElementById('month-container');
            const scheduleContainer = document.getElementById('schedule-grid');
            if (monthContainer && scheduleContainer) {
                if (view === 'month') {
                    monthContainer.style.display = 'block';
                    scheduleContainer.style.display = 'none';
                    renderCalendar();
                } else {
                    monthContainer.style.display = 'none';
                    scheduleContainer.style.display = 'block';
                }
            }
            
            renderSchedule();
        }

        function navigateWeek(direction) {
            // Always advance by one full week relative to the currently displayed week,
            // regardless of the selected view, so the header moves from e.g. Sep 6 → Sep 7-13.
            const start = getWeekStart(currentWeek);
            const nextStart = new Date(start);
            nextStart.setDate(start.getDate() + (direction * 7));
            currentWeek = nextStart;
            renderSchedule();
        }

        // Jump to today's date in both the calendar and schedule views
        function goToToday() {
            const today = new Date();
            selectedDate = new Date(today);
            currentWeek = new Date(today);
            clickedDateString = today.toISOString().split('T')[0];
            renderCalendar();
            renderSchedule();
        }
        
        function renderSchedule() {
            const scheduleContainer = document.getElementById('schedule-grid');
            if (!scheduleContainer) return;

            const weekStart = getWeekStart(currentWeek);
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 6);

            // Update date range display
            const dateRangeElement = document.getElementById('date-range');
            if (dateRangeElement) {
                dateRangeElement.textContent = `${weekStart.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                })} - ${weekEnd.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                })}`;
            }

            // Filter meetings based on event type filters
            const filteredMeetings = currentMeetings.filter(meeting => {
                const passes = eventFilters[meeting.type] || false;
                console.log('Event filter check:', meeting.title, 'type:', meeting.type, 'passes:', passes, 'eventFilters:', eventFilters);
                return passes;
            });
            
            // Calendar rendering
            console.log('Total meetings loaded:', currentMeetings.length);
            console.log('All meetings:', currentMeetings);
            console.log('Filtered meetings:', filteredMeetings);
            console.log('Event filters:', eventFilters);
            console.log('Schedule container exists:', !!scheduleContainer);

            let scheduleHTML = `
                <div class="grid grid-cols-7 gap-1 bg-white dark:bg-[#2a2f3a] rounded-lg border border-gray-200 dark:border-gray-600">
            `;

            // Day headers
            for (let i = 0; i < 7; i++) {
                const dayDate = new Date(weekStart);
                dayDate.setDate(weekStart.getDate() + i);
                const isToday = dayDate.toDateString() === new Date().toDateString();
                const dayString = dayDate.toISOString().split('T')[0];
                
                scheduleHTML += `
                    <div class="p-2 border-b bg-gray-50 dark:bg-gray-700 text-center ${isToday ? 'bg-purple-50 dark:bg-purple-900 border-purple-200 dark:border-purple-600' : ''} cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" 
                         onclick="selectDate('${dayString}')" 
                         data-date="${dayString}">
                        <div class="text-xs font-medium text-gray-900 dark:text-white">
                            ${dayDate.toLocaleDateString('en-US', { weekday: 'short' })}
                        </div>
                        <div class="text-[10px] text-gray-500 dark:text-gray-300 ${isToday ? 'text-purple-600 dark:text-purple-300 font-semibold' : ''}">
                            ${dayDate.getDate()}
                        </div>
                    </div>
                `;
            }

            // Events for each day
            for (let day = 0; day < 7; day++) {
                const dayDate = new Date(weekStart);
                dayDate.setDate(weekStart.getDate() + day);
                const dayString = dayDate.toISOString().split('T')[0];
                
                const dayEvents = filteredMeetings.filter(meeting => {
                    const start = meeting.date;
                    const end = meeting.dateEnd || meeting.date;
                    const match = start <= dayString && end >= dayString;
                    console.log('Comparing range:', {start, end, dayString, match});
                    return match;
                });
                
                console.log('Events for day', dayString, ':', dayEvents.length, dayEvents.map(e => e.title));

                scheduleHTML += `
                    <div class="day-column-bg p-2 border-r border-gray-200 dark:border-gray-600 min-h-[600px] relative">
                `;

                dayEvents.forEach(meetingEvent => {
                    // Get the color for this event using a proper mapping function
                    const eventColor = meetingEvent.color || 'blue';
                    const colorClasses = getEventColorClasses(eventColor);
                    
                    console.log('Event color mapping:', {
                        eventColor: eventColor,
                        colorClasses: colorClasses,
                        title: meetingEvent.title
                    });
                    
                    // Handle all-day events display
                    const isAllDay = !meetingEvent.startTime || meetingEvent.startTime === '00:00:00' || meetingEvent.is_all_day;
                    const timeDisplay = isAllDay ? 'All Day' : `${formatTime(meetingEvent.startTime)} - ${formatTime(meetingEvent.endTime)}`;
                    
                    // Fallback to ensure visibility
                    const fallbackClasses = 'bg-blue-500 hover:bg-blue-600 text-blue-100';
                    const finalClasses = colorClasses.background ? 
                        `${colorClasses.background} text-white ${colorClasses.hover} transition-colors` : 
                        fallbackClasses;
                    
                    console.log('Description before onclick:', meetingEvent.description);
                    scheduleHTML += `
                        <div class="${finalClasses} text-xs p-2 rounded mb-2 cursor-pointer" 
                             data-meeting-id="${meetingEvent.id}"
                             onclick="showEventDetails('${meetingEvent.id}', '${(meetingEvent.title || meetingEvent.meeting_title || 'Meeting').replace(/'/g, "\\'")}', '${(meetingEvent.description || '').replace(/'/g, "\\'")}', '${meetingEvent.date}', '${meetingEvent.startTime || ''}', '${meetingEvent.endTime || ''}', '${meetingEvent.is_all_day || '0'}', '${meetingEvent.color || 'blue'}')">
                            <div class="font-medium">${meetingEvent.title || meetingEvent.meeting_title || 'Meeting'}</div>
                            <div class="${colorClasses.text || 'text-blue-100'}">${timeDisplay}</div>
                        </div>
                    `;
                });

                scheduleHTML += `</div>`;
            }

            scheduleHTML += `</div>`;
            scheduleContainer.innerHTML = scheduleHTML;
        }

        function getWeekStart(date) {
            const d = new Date(date);
            const day = d.getDay();
            const diff = d.getDate() - day;
            return new Date(d.setDate(diff));
        }

        // Month navigation functions
        function navigateMonth(direction) {
            const newDate = new Date(selectedDate);
            newDate.setMonth(newDate.getMonth() + direction);
            selectedDate = newDate;
            // Sync weekly view with the newly selected month and refresh date range
            currentWeek = new Date(newDate);
            renderCalendar();
            renderSchedule();
            
            // Add visual feedback for button press
            const buttonId = direction > 0 ? 'next-month' : 'prev-month';
            const button = document.getElementById(buttonId);
            if (button) {
                button.classList.add('bg-gray-300', 'dark:bg-gray-600');
                setTimeout(() => {
                    button.classList.remove('bg-gray-300', 'dark:bg-gray-600');
                }, 150);
            }
        }

        // Swipe gesture detection for calendar
        function initializeCalendarSwipe() {
            const calendarContainer = document.getElementById('calendar-grid');
            if (!calendarContainer) return;

            let startX = 0;
            let startY = 0;
            let endX = 0;
            let endY = 0;
            let isSwiping = false;
            const minSwipeDistance = 30; // Reduced minimum distance for smoother swipes

            // Add visual feedback for swipe
            calendarContainer.style.userSelect = 'none';
            calendarContainer.style.webkitUserSelect = 'none';
            calendarContainer.style.mozUserSelect = 'none';
            calendarContainer.style.msUserSelect = 'none';

            // Touch events for mobile
            calendarContainer.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                isSwiping = true;
                calendarContainer.style.transform = 'scale(0.99)';
                calendarContainer.style.transition = 'transform 0.15s ease';
            }, { passive: true });

            calendarContainer.addEventListener('touchmove', function(e) {
                if (!isSwiping) return;
                e.preventDefault(); // Prevent scrolling while swiping
            }, { passive: false });

            calendarContainer.addEventListener('touchend', function(e) {
                if (!isSwiping) return;
                
                endX = e.changedTouches[0].clientX;
                endY = e.changedTouches[0].clientY;
                
                const deltaX = endX - startX;
                const deltaY = endY - startY;
                
                // Reset visual feedback
                calendarContainer.style.transform = 'scale(1)';
                
                // Check if it's a horizontal swipe (more horizontal than vertical)
                if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
                    if (deltaX > 0) {
                        // Right swipe - go to previous month
                        navigateMonth(-1);
                    } else {
                        // Left swipe - go to next month
                        navigateMonth(1);
                    }
                }
                
                isSwiping = false;
            }, { passive: true });

            // Mouse events for desktop trackpad
            calendarContainer.addEventListener('mousedown', function(e) {
                startX = e.clientX;
                startY = e.clientY;
                isSwiping = true;
                calendarContainer.style.transform = 'scale(0.99)';
                calendarContainer.style.transition = 'transform 0.15s ease';
            }, { passive: true });

            calendarContainer.addEventListener('mousemove', function(e) {
                if (!isSwiping) return;
                
                endX = e.clientX;
                endY = e.clientY;
            }, { passive: true });

            calendarContainer.addEventListener('mouseup', function(e) {
                if (!isSwiping) return;
                
                const deltaX = endX - startX;
                const deltaY = endY - startY;
                
                // Reset visual feedback
                calendarContainer.style.transform = 'scale(1)';
                
                // Check if it's a horizontal swipe (more horizontal than vertical)
                if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
                    if (deltaX > 0) {
                        // Right swipe - go to previous month
                        navigateMonth(-1);
                    } else {
                        // Left swipe - go to next month
                        navigateMonth(1);
                    }
                }
                
                isSwiping = false;
            }, { passive: true });

            // Prevent text selection during swipe
            calendarContainer.addEventListener('selectstart', function(e) {
                if (isSwiping) {
                    e.preventDefault();
                }
            }, { passive: false });
        }

        // Keyboard navigation for calendar
        function initializeCalendarKeyboard() {
            document.addEventListener('keydown', function(e) {
                // Only handle arrow keys when calendar is visible
                const calendarContainer = document.getElementById('calendar-grid');
                if (!calendarContainer || calendarContainer.closest('.hidden')) return;
                
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        navigateMonth(-1);
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        navigateMonth(1);
                        break;
                }
            });
        }

        // Mini calendar and quick reminder helpers
        function renderMiniCalendar() {
            const container = document.getElementById('mini-calendar-grid');
            if (!container) return;

            const year = selectedDate.getFullYear();
            const month = selectedDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            let html = '';
            const days = ['Su','Mo','Tu','We','Th','Fr','Sa'];
            days.forEach(d => html += `<div class="text-[10px] text-center text-gray-500 dark:text-gray-300">${d}</div>`);

            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                const isCurrMonth = date.getMonth() === month;
                const dateStr = date.toISOString().split('T')[0];
                const isToday = dateStr === new Date().toISOString().split('T')[0];
                const isSelected = clickedDateString ? dateStr === clickedDateString : false;
                const eventCount = currentMeetings.filter(m => {
                    const start = m.date || m.meeting_date;
                    const end = m.dateEnd || m.end_date || start;
                    return start && end && start <= dateStr && end >= dateStr;
                }).length;
                const hasEvent = eventCount > 0;
                let cls = 'text-[11px] h-6 flex items-center justify-center rounded cursor-pointer hover:bg-purple-100 dark:hover:bg-gray-700';
                if (!isCurrMonth) cls += ' text-gray-300 dark:text-gray-500';
                if (isSelected) cls += ' bg-purple-600 text-white';
                else if (isToday) cls += ' ring-1 ring-purple-500';
                const dot = hasEvent ? '<span class="ml-1 w-1.5 h-1.5 rounded-full bg-purple-500 inline-block"></span>' : '';
                html += `<div class="${cls}" data-date="${dateStr}" onclick="selectCalendarDate('${dateStr}'); renderMiniCalendar();">${date.getDate()}${dot}</div>`;
            }

            container.innerHTML = html;
            const label = document.getElementById('mini-calendar-month-year');
            if (label) label.textContent = selectedDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            const prev = document.getElementById('mini-prev-month');
            const next = document.getElementById('mini-next-month');
            if (prev) prev.onclick = function(){ selectedDate = new Date(year, month - 1, 1); currentWeek = new Date(selectedDate); renderCalendar(); renderMiniCalendar(); renderSchedule(); };
            if (next) next.onclick = function(){ selectedDate = new Date(year, month + 1, 1); currentWeek = new Date(selectedDate); renderCalendar(); renderMiniCalendar(); renderSchedule(); };
        }

        function handleQuickReminder() {
            showAddEventModal();
            setTimeout(() => {
                try {
                    const dateStr = clickedDateString || new Date().toISOString().split('T')[0];
                    document.getElementById('event-all-day').checked = true;
                    toggleAllDay();
                    document.getElementById('event-date-start').value = dateStr;
                    document.getElementById('event-date-end').value = dateStr;
                } catch (e) {}
            }, 0);
        }

        function formatTime(timeString) {
            if (!timeString) return '';
            const [hours, minutes] = timeString.split(':').map(Number);
            const date = new Date();
            date.setHours(hours, minutes, 0, 0);
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        function getEventColorClasses(color) {
            const colorMap = {
                // Basic colors
                'blue': {
                    background: 'bg-blue-500',
                    hover: 'hover:bg-blue-600',
                    text: 'text-blue-100'
                },
                'red': {
                    background: 'bg-red-500',
                    hover: 'hover:bg-red-600',
                    text: 'text-red-100'
                },
                'green': {
                    background: 'bg-green-500',
                    hover: 'hover:bg-green-600',
                    text: 'text-green-100'
                },
                'yellow': {
                    background: 'bg-yellow-500',
                    hover: 'hover:bg-yellow-600',
                    text: 'text-yellow-100'
                },
                'purple': {
                    background: 'bg-purple-500',
                    hover: 'hover:bg-purple-600',
                    text: 'text-purple-100'
                },
                'pink': {
                    background: 'bg-pink-500',
                    hover: 'hover:bg-pink-600',
                    text: 'text-pink-100'
                },
                'indigo': {
                    background: 'bg-indigo-500',
                    hover: 'hover:bg-indigo-600',
                    text: 'text-indigo-100'
                },
                'teal': {
                    background: 'bg-teal-500',
                    hover: 'hover:bg-teal-600',
                    text: 'text-teal-100'
                },
                'orange': {
                    background: 'bg-orange-500',
                    hover: 'hover:bg-orange-600',
                    text: 'text-orange-100'
                },
                'brown': {
                    background: 'bg-amber-700',
                    hover: 'hover:bg-amber-800',
                    text: 'text-amber-100'
                },
                'cyan': {
                    background: 'bg-cyan-500',
                    hover: 'hover:bg-cyan-600',
                    text: 'text-cyan-100'
                },
                'lime': {
                    background: 'bg-lime-500',
                    hover: 'hover:bg-lime-600',
                    text: 'text-lime-100'
                },
                'emerald': {
                    background: 'bg-emerald-500',
                    hover: 'hover:bg-emerald-600',
                    text: 'text-emerald-100'
                },
                'rose': {
                    background: 'bg-rose-500',
                    hover: 'hover:bg-rose-600',
                    text: 'text-rose-100'
                },
                'violet': {
                    background: 'bg-violet-500',
                    hover: 'hover:bg-violet-600',
                    text: 'text-violet-100'
                },
                'fuchsia': {
                    background: 'bg-fuchsia-500',
                    hover: 'hover:bg-fuchsia-600',
                    text: 'text-fuchsia-100'
                },
                'sky': {
                    background: 'bg-sky-500',
                    hover: 'hover:bg-sky-600',
                    text: 'text-sky-100'
                },
                'slate': {
                    background: 'bg-slate-500',
                    hover: 'hover:bg-slate-600',
                    text: 'text-slate-100'
                },
                'gray': {
                    background: 'bg-gray-500',
                    hover: 'hover:bg-gray-600',
                    text: 'text-gray-100'
                },
                'zinc': {
                    background: 'bg-zinc-500',
                    hover: 'hover:bg-zinc-600',
                    text: 'text-zinc-100'
                },
                'neutral': {
                    background: 'bg-neutral-500',
                    hover: 'hover:bg-neutral-600',
                    text: 'text-neutral-100'
                },
                'stone': {
                    background: 'bg-stone-500',
                    hover: 'hover:bg-stone-600',
                    text: 'text-stone-100'
                },
                // Color variants from color picker
                'red-500': {
                    background: 'bg-red-500',
                    hover: 'hover:bg-red-600',
                    text: 'text-red-100'
                },
                'red-600': {
                    background: 'bg-red-600',
                    hover: 'hover:bg-red-700',
                    text: 'text-red-100'
                },
                'red-700': {
                    background: 'bg-red-700',
                    hover: 'hover:bg-red-800',
                    text: 'text-red-100'
                },
                'red-800': {
                    background: 'bg-red-800',
                    hover: 'hover:bg-red-900',
                    text: 'text-red-100'
                },
                'pink-500': {
                    background: 'bg-pink-500',
                    hover: 'hover:bg-pink-600',
                    text: 'text-pink-100'
                },
                'pink-600': {
                    background: 'bg-pink-600',
                    hover: 'hover:bg-pink-700',
                    text: 'text-pink-100'
                },
                'pink-700': {
                    background: 'bg-pink-700',
                    hover: 'hover:bg-pink-800',
                    text: 'text-pink-100'
                },
                'pink-800': {
                    background: 'bg-pink-800',
                    hover: 'hover:bg-pink-900',
                    text: 'text-pink-100'
                },
                'purple-500': {
                    background: 'bg-purple-500',
                    hover: 'hover:bg-purple-600',
                    text: 'text-purple-100'
                },
                'purple-600': {
                    background: 'bg-purple-600',
                    hover: 'hover:bg-purple-700',
                    text: 'text-purple-100'
                },
                'purple-700': {
                    background: 'bg-purple-700',
                    hover: 'hover:bg-purple-800',
                    text: 'text-purple-100'
                },
                'purple-800': {
                    background: 'bg-purple-800',
                    hover: 'hover:bg-purple-900',
                    text: 'text-purple-100'
                },
                'blue-500': {
                    background: 'bg-blue-500',
                    hover: 'hover:bg-blue-600',
                    text: 'text-blue-100'
                },
                'blue-600': {
                    background: 'bg-blue-600',
                    hover: 'hover:bg-blue-700',
                    text: 'text-blue-100'
                },
                'blue-700': {
                    background: 'bg-blue-700',
                    hover: 'hover:bg-blue-800',
                    text: 'text-blue-100'
                },
                'blue-800': {
                    background: 'bg-blue-800',
                    hover: 'hover:bg-blue-900',
                    text: 'text-blue-100'
                },
                'cyan-500': {
                    background: 'bg-cyan-500',
                    hover: 'hover:bg-cyan-600',
                    text: 'text-cyan-100'
                },
                'cyan-600': {
                    background: 'bg-cyan-600',
                    hover: 'hover:bg-cyan-700',
                    text: 'text-cyan-100'
                },
                'teal-500': {
                    background: 'bg-teal-500',
                    hover: 'hover:bg-teal-600',
                    text: 'text-teal-100'
                },
                'teal-600': {
                    background: 'bg-teal-600',
                    hover: 'hover:bg-teal-700',
                    text: 'text-teal-100'
                },
                'green-500': {
                    background: 'bg-green-500',
                    hover: 'hover:bg-green-600',
                    text: 'text-green-100'
                },
                'green-600': {
                    background: 'bg-green-600',
                    hover: 'hover:bg-green-700',
                    text: 'text-green-100'
                },
                'green-700': {
                    background: 'bg-green-700',
                    hover: 'hover:bg-green-800',
                    text: 'text-green-100'
                },
                'green-800': {
                    background: 'bg-green-800',
                    hover: 'hover:bg-green-900',
                    text: 'text-green-100'
                },
                'yellow-500': {
                    background: 'bg-yellow-500',
                    hover: 'hover:bg-yellow-600',
                    text: 'text-yellow-100'
                },
                'yellow-600': {
                    background: 'bg-yellow-600',
                    hover: 'hover:bg-yellow-700',
                    text: 'text-yellow-100'
                },
                'amber-500': {
                    background: 'bg-amber-500',
                    hover: 'hover:bg-amber-600',
                    text: 'text-amber-100'
                },
                'amber-600': {
                    background: 'bg-amber-600',
                    hover: 'hover:bg-amber-700',
                    text: 'text-amber-100'
                },
                'amber-700': {
                    background: 'bg-amber-700',
                    hover: 'hover:bg-amber-800',
                    text: 'text-amber-100'
                },
                'amber-800': {
                    background: 'bg-amber-800',
                    hover: 'hover:bg-amber-900',
                    text: 'text-amber-100'
                },
                'orange-500': {
                    background: 'bg-orange-500',
                    hover: 'hover:bg-orange-600',
                    text: 'text-orange-100'
                },
                'orange-600': {
                    background: 'bg-orange-600',
                    hover: 'hover:bg-orange-700',
                    text: 'text-orange-100'
                },
                'orange-700': {
                    background: 'bg-orange-700',
                    hover: 'hover:bg-orange-800',
                    text: 'text-orange-100'
                },
                'orange-800': {
                    background: 'bg-orange-800',
                    hover: 'hover:bg-orange-900',
                    text: 'text-orange-100'
                },
                'gray-500': {
                    background: 'bg-gray-500',
                    hover: 'hover:bg-gray-600',
                    text: 'text-gray-100'
                },
                'gray-600': {
                    background: 'bg-gray-600',
                    hover: 'hover:bg-gray-700',
                    text: 'text-gray-100'
                }
            };
            
            return colorMap[color] || colorMap['blue']; // Default to blue if color not found
        }

        function updateReminders() {
            const remindersContainer = document.getElementById('reminders-list');
            if (!remindersContainer) return;

            // Reminders processing
            
            const now = new Date();
            console.log('Current time:', now);
            console.log('Current date string:', now.toISOString().split('T')[0]);
            const upcomingMeetings = currentMeetings
                .filter(meeting => {
                    console.log('Checking meeting for reminders:', meeting.title, meeting.date, meeting.startTime, meeting.is_all_day);
                    
                    if (!meeting.date) {
                        console.log('Meeting has no date, skipping:', meeting.title);
                        return false;
                    }
                    
                    // Handle both regular meetings and all-day events
                    if (meeting.is_all_day === '1' || meeting.is_all_day === true) {
                        // For all-day events, show them on their date
                        const meetingDate = new Date(meeting.date);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        meetingDate.setHours(0, 0, 0, 0);
                        const isUpcoming = meetingDate >= today;
                        console.log('All-day event check:', meeting.title, 'meetingDate:', meetingDate, 'today:', today, 'isUpcoming:', isUpcoming);
                        return isUpcoming;
                    } else if (meeting.startTime) {
                        // For timed events, check if they're in the future
                        const meetingDateTime = new Date(meeting.date + 'T' + meeting.startTime);
                        const isUpcoming = meetingDateTime > now;
                        console.log('Timed event check:', meeting.title, 'meetingDateTime:', meetingDateTime, 'now:', now, 'isUpcoming:', isUpcoming);
                        return isUpcoming;
                    } else {
                        // For events without time, show them on their date
                        const meetingDate = new Date(meeting.date);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        meetingDate.setHours(0, 0, 0, 0);
                        const isUpcoming = meetingDate >= today;
                        console.log('No-time event check:', meeting.title, 'meetingDate:', meetingDate, 'today:', today, 'isUpcoming:', isUpcoming);
                        return isUpcoming;
                    }
                })
                .sort((a, b) => {
                    // Sort by date first, then by time
                    const dateA = new Date(a.date);
                    const dateB = new Date(b.date);
                    
                    if (dateA.getTime() !== dateB.getTime()) {
                        return dateA - dateB;
                    }
                    
                    // If same date, sort by time
                    const timeA = a.startTime ? new Date(a.date + 'T' + a.startTime) : new Date(a.date + 'T00:00:00');
                    const timeB = b.startTime ? new Date(b.date + 'T' + b.startTime) : new Date(b.date + 'T00:00:00');
                    return timeA - timeB;
                })
                .slice(0, 5); // Show up to 5 upcoming events

            console.log('Filtered upcoming meetings:', upcomingMeetings);

            let remindersHTML = '';
            
            if (upcomingMeetings.length === 0) {
                remindersHTML = `
                    <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                        No upcoming events
                    </div>
                `;
            } else {
                upcomingMeetings.forEach(meeting => {
                    console.log('Processing reminder for meeting:', meeting.title, 'date:', meeting.date, 'startTime:', meeting.startTime, 'is_all_day:', meeting.is_all_day);
                    const meetingDate = new Date(meeting.date);
                    const isToday = meetingDate.toDateString() === now.toDateString();
                    const isAllDay = meeting.is_all_day === '1' || meeting.is_all_day === true;
                    
                    let statusBadge = '';
                    let timeDisplay = '';
                    let eventType = '';
                    
                    if (isAllDay) {
                        statusBadge = '<span class="inline-block w-2 h-2 bg-purple-500 rounded-full mr-2"></span>';
                        timeDisplay = 'All Day';
                        eventType = 'All Day';
                    } else if (meeting.startTime) {
                        const meetingDateTime = new Date(meeting.date + 'T' + meeting.startTime);
                        const isOngoing = meetingDateTime <= now && 
                                        new Date(meetingDateTime.getTime() + 60 * 60 * 1000) > now;
                        
                        if (isOngoing) {
                            statusBadge = '<span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2"></span>';
                            eventType = 'Ongoing';
                        } else if (isToday) {
                            statusBadge = '<span class="inline-block w-2 h-2 bg-blue-500 rounded-full mr-2"></span>';
                            eventType = 'Today';
                        } else {
                            statusBadge = '<span class="inline-block w-2 h-2 bg-gray-400 rounded-full mr-2"></span>';
                            eventType = 'Event';
                        }
                        
                        timeDisplay = formatTime(meeting.startTime);
                        if (meeting.endTime) {
                            timeDisplay += ` - ${formatTime(meeting.endTime)}`;
                        }
                    } else {
                        statusBadge = '<span class="inline-block w-2 h-2 bg-gray-400 rounded-full mr-2"></span>';
                        timeDisplay = 'No time specified';
                        eventType = 'Event';
                    }

                    remindersHTML += `
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors cursor-pointer" 
                             onclick="showEventDetails('${meeting.id}', '${(meeting.title || meeting.meeting_title || 'Meeting').replace(/'/g, "\\'")}', '${(meeting.description || '').replace(/'/g, "\\'")}', '${meeting.date}', '${meeting.startTime || ''}', '${meeting.endTime || ''}', '${meeting.is_all_day || '0'}', '${meeting.color || 'blue'}')">
                            <div class="flex items-center">
                                ${statusBadge}
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        ${meeting.title || meeting.meeting_title || 'Meeting'}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        ${meetingDate.toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric'
                                        })} • ${timeDisplay}
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full">
                                ${eventType}
                            </span>
                        </div>
                    `;
                });
            }

            remindersContainer.innerHTML = remindersHTML;
        }

        function goToCalendarView() {
            console.log('Going to calendar view - NO NOTIFICATION');
            setActiveView('calendar');
            // Explicitly prevent any notifications
            return false;
        }

        function setActiveView(view) {
            const viewSwitchBtn = document.getElementById('view-switch-btn');
            const calendarIcon = document.getElementById('calendar-icon');
            const meetingsIcon = document.getElementById('meetings-icon');
            const viewSwitchText = document.getElementById('view-switch-text');
            const calendarView = document.getElementById('calendar-view');
            const meetingsView = document.getElementById('meetings-view');

            if (view === 'calendar') {
                // Update floating button (if it exists)
                if (viewSwitchBtn) {
                    viewSwitchBtn.setAttribute('data-current-view', 'calendar');
                }
                if (calendarIcon) {
                    calendarIcon.classList.remove('hidden');
                }
                if (meetingsIcon) {
                    meetingsIcon.classList.add('hidden');
                }
                if (viewSwitchText) {
                    viewSwitchText.textContent = 'Schedule a Meeting';
                }
                
                // Show calendar view
                if (calendarView) {
                    calendarView.style.display = 'block';
                }
                if (meetingsView) {
                    meetingsView.style.display = 'none';
                }
            } else if (view === 'meetings') {
                // Update floating button (if it exists)
                if (viewSwitchBtn) {
                    viewSwitchBtn.setAttribute('data-current-view', 'meetings');
                }
                if (meetingsIcon) {
                    meetingsIcon.classList.remove('hidden');
                }
                if (calendarIcon) {
                    calendarIcon.classList.add('hidden');
                }
                if (viewSwitchText) {
                    viewSwitchText.textContent = 'Calendar View';
                }
                
                // Show meetings view
                if (meetingsView) {
                    meetingsView.style.display = 'block';
                }
                if (calendarView) {
                    calendarView.style.display = 'none';
                }
                loadDocuments(); // Load meetings for the table view
            }
        }

        function openTrashBin() {
            showTrashModal();
        }
        
        function showTrashModal() {
            const modal = document.getElementById('trash-modal');
            if (modal) {
                modal.classList.remove('hidden');
                // Load trash meetings and update count when opening modal
                loadTrashMeetings();
                loadTrashCount();
            }
        }
        
        function hideTrashModal() {
            const modal = document.getElementById('trash-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function showAddEventModal() {
            const modal = document.getElementById('add-event-modal');
            if (modal) {
                modal.classList.remove('hidden');
                // Set default date to today
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('event-date-start').value = today;
                document.getElementById('event-date-end').value = today;
                // Set default time to 12:00 PM
                document.getElementById('event-time-start').value = '12:00';
                document.getElementById('event-time-end').value = '13:00'; // End time 1 hour later
            }
        }

        function closeAddEventModal() {
            const modal = document.getElementById('add-event-modal');
            if (modal) {
                modal.classList.add('hidden');
                // Reset form
                document.getElementById('add-event-form').reset();
                
                // Remove any custom color options
                const existingCustom = document.querySelector('.custom-color-option');
                if (existingCustom) {
                    existingCustom.remove();
                }
                
                // Reset to default blue color
                const blueRadio = document.querySelector('input[name="event-color"][value="blue"]');
                if (blueRadio) {
                    blueRadio.checked = true;
                    updateColorSelection();
                }
                
                // Reset edit mode
                window.isEditMode = false;
                window.editEventId = null;
                
                // Reset modal title and button
                document.getElementById('add-event-modal-title').textContent = 'Add Event';
                document.getElementById('add-event-submit-btn').textContent = 'Add Event';
                
                // Reset time to 12:00 PM for next use
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('event-date-start').value = today;
                document.getElementById('event-date-end').value = today;
                document.getElementById('event-time-start').value = '12:00';
                document.getElementById('event-time-end').value = '13:00';
            }
        }

        function handleSubmitButtonClick(e) {
            e.preventDefault();
            e.stopPropagation();
            // Submit button clicked
            console.log('Button text:', e.target.textContent);
            console.log('Edit mode:', window.isEditMode);
            console.log('Edit event ID:', window.editEventId);
            console.log('Current event data:', window.currentEventData);
            
            // Force the edit function to run
            if (window.isEditMode && window.editEventId) {
                console.log('FORCING EDIT FUNCTION TO RUN');
                // Get form values directly
                const title = document.getElementById('event-name').value.trim();
                const description = document.getElementById('event-description-input').value.trim();
                const startDate = document.getElementById('event-date-start').value;
                const endDate = document.getElementById('event-date-end').value;
                const isAllDay = document.getElementById('event-all-day').checked;
                const startTime = isAllDay ? '00:00' : document.getElementById('event-time-start').value;
                const endTime = isAllDay ? '23:59' : document.getElementById('event-time-end').value;
                const selectedColorInput = document.querySelector('input[name="event-color"]:checked');
                const color = selectedColorInput?.value || 'blue';
                
                console.log('Color selection debug:', {
                    selectedColorInput: selectedColorInput,
                    colorValue: color,
                    allColorInputs: document.querySelectorAll('input[name="event-color"]').length,
                    checkedInputs: document.querySelectorAll('input[name="event-color"]:checked').length
                });
                
                // Log all color inputs and their checked status
                document.querySelectorAll('input[name="event-color"]').forEach((input, index) => {
                    console.log(`Color input ${index}: value="${input.value}", checked=${input.checked}`);
                });
                
                console.log('Form values:', { title, description, startDate, endDate, isAllDay, startTime, endTime, color });
                
                // Create form data and send directly
                const formData = new FormData();
                formData.append('action', 'update');
                formData.append('id', window.editEventId);
                formData.append('title', title);
                formData.append('description', description);
                formData.append('date', startDate);
                formData.append('time', startTime);
                formData.append('end_date', endDate);
                formData.append('end_time', endTime);
                formData.append('is_all_day', isAllDay ? '1' : '0');
                formData.append('color', color);
                
                console.log('Sending update request...');
                
                fetch('api/scheduler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                                    console.log('Update response:', data);
                if (data.success) {
                    console.log('Event updated successfully, reloading meetings...');
                    closeAddEventModal();
                    loadMeetings();
                    updateReminders();
                    loadStats();
                        // Event updated successfully - no notification needed
                        // Reset edit mode
                        window.isEditMode = false;
                        window.editEventId = null;
                    } else {
                        if (window.lilacNotifications) {
                            window.lilacNotifications.error('Error: ' + data.message);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating event:', error);
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error updating event');
                    } else {
                        alert('Error updating event');
                    }
                });
            } else {
                console.log('Calling handleAddEventSubmit from button click');
                handleAddEventSubmit(e);
            }
        }

        function handleAddEventSubmit(e) {
            e.preventDefault();
            
            // Form submission
            console.log('Edit mode check:', { isEditMode: window.isEditMode, editEventId: window.editEventId });
            console.log('Form elements:', {
                eventName: document.getElementById('event-name')?.value,
                description: document.getElementById('event-description-input')?.value,
                startDate: document.getElementById('event-date-start')?.value,
                endDate: document.getElementById('event-date-end')?.value,
                isAllDay: document.getElementById('event-all-day')?.checked
            });
            
            // Check if we're in edit mode
            if (window.isEditMode && window.editEventId) {
                console.log('In edit mode, calling handleEditEventSubmit');
                handleEditEventSubmit();
                return;
            }
            
            const eventName = document.getElementById('event-name').value.trim();
            const description = document.getElementById('event-description-input').value.trim();
            const isAllDay = document.getElementById('event-all-day').checked;
            const startDate = document.getElementById('event-date-start').value;
            const startTime = document.getElementById('event-time-start').value;
            const endDate = document.getElementById('event-date-end').value;
            const endTime = document.getElementById('event-time-end').value;
            const selectedColorInput = document.querySelector('input[name="event-color"]:checked');
            const selectedColor = selectedColorInput?.value || 'blue';
            
            if (!eventName || !startDate || (!isAllDay && !startTime)) {
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Please fill in all required fields');
                } else {
                    alert('Please fill in all required fields');
                }
                return;
            }
            
            // Validate dates
            const startDateTime = new Date(startDate + 'T' + (isAllDay ? '00:00' : startTime));
            const endDateTime = new Date(endDate + 'T' + (isAllDay ? '23:59' : endTime));
            
            if (endDateTime <= startDateTime) {
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('End time must be after start time');
                } else {
                    alert('End time must be after start time');
                }
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('title', eventName);
            formData.append('date', startDate);
            formData.append('time', isAllDay ? '00:00' : startTime);
            formData.append('end_date', endDate);
            formData.append('end_time', isAllDay ? '23:59' : endTime);
            formData.append('description', description);
            formData.append('is_all_day', isAllDay ? '1' : '0');
            formData.append('color', selectedColor);

            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Add event response:', data);
                if (data.success) {
                    closeAddEventModal();
                    loadMeetings();
                    updateReminders(); // Ensure reminders are updated immediately
                    loadStats(); // Update statistics
                    renderCalendar(); // Refresh calendar to show red dots
                    if (window.lilacNotifications) {
                        window.lilacNotifications.success('Event added successfully!');
                    } else {
                        alert('Event added successfully!');
                    }
                } else {
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error adding event:', error);
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Error adding event');
                } else {
                    alert('Error adding event');
                }
            });
        }

        function toggleAllDay() {
            const isAllDay = document.getElementById('event-all-day').checked;
            const timeInputs = document.querySelectorAll('.time-input');
            
            timeInputs.forEach(input => {
                input.disabled = isAllDay;
                if (isAllDay) {
                    input.value = '';
                }
            });
        }

        // Initialize color selection functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle color selection
            const colorInputs = document.querySelectorAll('input[name="event-color"]');
            const colorDivs = document.querySelectorAll('input[name="event-color"] + div');
            
            colorInputs.forEach((input, index) => {
                const div = colorDivs[index];
                if (div) {
                    div.addEventListener('click', function() {
                        console.log('Color div clicked:', input.value);
                        // Uncheck all other radio buttons
                        colorInputs.forEach(radio => radio.checked = false);
                        // Check this one
                        input.checked = true;
                        // Update visual selection
                        updateColorSelection();
                    });
                }
            });
            
            // Initial color selection
            updateColorSelection();
        });

        function updateColorSelection() {
            const colorInputs = document.querySelectorAll('input[name="event-color"]');
            const colorDivs = document.querySelectorAll('input[name="event-color"] + div');
            
            console.log('updateColorSelection called');
            console.log('Color inputs found:', colorInputs.length);
            console.log('Color divs found:', colorDivs.length);
            
            // Log all color inputs and their checked status
            colorInputs.forEach((input, index) => {
                console.log(`Color input ${index}: value="${input.value}", checked=${input.checked}`);
            });
            
            colorDivs.forEach((div, index) => {
                if (colorInputs[index] && colorInputs[index].checked) {
                    div.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
                    console.log('Added ring to color div', index, 'value:', colorInputs[index].value);
                } else {
                    div.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
                }
            });
            
            // Also handle custom color options
            const customColorDivs = document.querySelectorAll('.custom-color-option div');
            customColorDivs.forEach((div, index) => {
                const customInput = div.parentElement.querySelector('input');
                if (customInput && customInput.checked) {
                    div.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
                    console.log('Added ring to custom color div', index, 'value:', customInput.value);
                } else {
                    div.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
                }
            });
        }

        // Color picker functions
        function showColorPicker() {
            const popup = document.getElementById('color-picker-popup');
            if (popup) {
                popup.classList.remove('hidden');
            }
        }

        function closeColorPicker() {
            const popup = document.getElementById('color-picker-popup');
            if (popup) {
                popup.classList.add('hidden');
            }
        }

        function selectCustomColor(colorClass, hexValue) {
            // Uncheck all existing radio buttons
            const existingRadios = document.querySelectorAll('input[name="event-color"]');
            existingRadios.forEach(radio => radio.checked = false);
            
            // Remove existing custom color div if it exists
            const existingCustom = document.querySelector('.custom-color-option');
            if (existingCustom) {
                existingCustom.remove();
            }
            
            // Create new custom color option
            const colorContainer = document.querySelector('.flex.items-center.space-x-3');
            const customColorDiv = document.createElement('label');
            customColorDiv.className = 'flex items-center custom-color-option';
            customColorDiv.innerHTML = `
                <input type="radio" name="event-color" value="${colorClass}" checked class="sr-only">
                <div class="w-6 h-6 rounded-full cursor-pointer border-2 border-transparent hover:border-gray-300 ring-2 ring-blue-500 ring-offset-2" style="background-color: ${hexValue}"></div>
            `;
            
            // Insert before the plus button
            const plusButton = colorContainer.querySelector('button');
            colorContainer.insertBefore(customColorDiv, plusButton);
            
            // Add click handler to the new custom color div
            const customColorInput = customColorDiv.querySelector('input');
            const customColorDivElement = customColorDiv.querySelector('div');
            customColorDivElement.addEventListener('click', function() {
                // Uncheck all other radio buttons
                document.querySelectorAll('input[name="event-color"]').forEach(radio => radio.checked = false);
                // Check this one
                customColorInput.checked = true;
                // Update visual selection
                updateColorSelection();
            });
            
            // Close the color picker
            closeColorPicker();
            
            // Update visual selection
            updateColorSelection();
        }

        // Close color picker when clicking outside
        document.addEventListener('click', function(e) {
            const popup = document.getElementById('color-picker-popup');
            const popupContent = popup?.querySelector('.bg-white, .dark\\:bg-\\[\\#2a2f3a\\]');
            
            if (popup && !popupContent?.contains(e.target) && !e.target.closest('button[onclick="showColorPicker()"]')) {
                closeColorPicker();
            }
        });

        // Close color picker on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeColorPicker();
            }
        });

        // Original meeting management functions
        function loadDocuments() {
            try {
                // Use PHP data directly instead of HTTP requests
                const meetingsData = <?php echo json_encode($meetingsData ?? []); ?>;
                
                if (Array.isArray(meetingsData)) {
                    currentDocuments = meetingsData;
                    displayDocuments(meetingsData);
                    loadStats();
                } else {
                    console.log('Meetings data is not an array, using empty array');
                    currentDocuments = [];
                    displayDocuments([]);
                    loadStats();
                }
            } catch (error) {
                console.error('Error loading meetings:', error);
                displayDocuments([]);
            }
        }

        function loadStats() {
            console.log('Loading stats from currentMeetings:', currentMeetings);
            
            if (currentMeetings && currentMeetings.length > 0) {
                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const weekFromNow = new Date(today);
                weekFromNow.setDate(today.getDate() + 7);
                
                const upcomingCount = currentMeetings.filter(meeting => {
                    if (!meeting.date || !meeting.startTime) return false;
                    try {
                        const meetingDateTime = new Date(meeting.date + 'T' + meeting.startTime);
                        return meetingDateTime >= now;
                    } catch (e) {
                        return false;
                    }
                }).length;
                
                const todayCount = currentMeetings.filter(meeting => {
                    if (!meeting.date) return false;
                    try {
                        const meetingDate = new Date(meeting.date);
                        return meetingDate.toDateString() === today.toDateString();
                    } catch (e) {
                        return false;
                    }
                }).length;
                
                const weekCount = currentMeetings.filter(meeting => {
                    if (!meeting.date) return false;
                    try {
                        const meetingDate = new Date(meeting.date);
                        const meetingDay = new Date(meetingDate.getFullYear(), meetingDate.getMonth(), meetingDate.getDate());
                        return meetingDay >= today && meetingDay < weekFromNow;
                    } catch (e) {
                        return false;
                    }
                }).length;
                
                const stats = {
                    total: currentMeetings.length,
                    upcoming: upcomingCount,
                    today: todayCount,
                    week: weekCount
                };
                
                console.log('Calculated stats:', stats);
                updateStats(stats);
            } else {
                console.log('No meetings found, setting stats to 0');
                updateStats({
                    total: 0,
                    upcoming: 0,
                    today: 0,
                    week: 0
                });
            }
        }

        function updateStats(stats) {
            const totalMeetingsElement = document.getElementById('total-meetings');
            const upcomingMeetingsElement = document.getElementById('upcoming-meetings');
            const todayMeetingsElement = document.getElementById('today-meetings');
            const weekMeetingsElement = document.getElementById('week-meetings');
            
            if (totalMeetingsElement) totalMeetingsElement.textContent = stats.total || 0;
            if (upcomingMeetingsElement) upcomingMeetingsElement.textContent = stats.upcoming || 0;
            if (todayMeetingsElement) todayMeetingsElement.textContent = stats.today || 0;
            if (weekMeetingsElement) weekMeetingsElement.textContent = stats.week || 0;
        }

        function displayDocuments(documents) {
            const container = document.getElementById('meetings-container');
            
            let tableHTML = `<div class="overflow-x-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Meeting Title
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Date & Time
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Location
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
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                            </svg>
                                            Actions
                                        </div>
                                        <button id="trash-bin-btn" onclick="openTrashBin()" class="bg-red-600 text-white p-1.5 rounded hover:bg-red-700 transition-colors flex items-center justify-center relative">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <!-- Trash Count Badge -->
                                            <div id="trash-count-badge" class="absolute -top-1 -right-1 bg-yellow-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold hidden">
                                                0
                                            </div>
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">`;
            
            if (documents.length === 0) {
                tableHTML += `<tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No meetings yet</h3>
                            <p class="text-gray-500 mb-4">Schedule your first meeting to get started</p>
                            <button onclick="document.getElementById('meeting-title').focus()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                Schedule Meeting
                            </button>
                        </div>
                    </td>
                </tr>`;
            } else {
                tableHTML += documents.map(doc => {
                    let formattedDate = 'Not specified';
                    let formattedTime = 'Not specified';
                    
                    try {
                        if (doc.meeting_date) {
                            const meetingDate = new Date(doc.meeting_date);
                            if (!isNaN(meetingDate.getTime())) {
                                formattedDate = meetingDate.toLocaleDateString('en-US', { 
                                    year: 'numeric', 
                                    month: 'short', 
                                    day: 'numeric' 
                                });
                            }
                        }
                        
                        if (doc.meeting_time) {
                            const timeStr = doc.meeting_time;
                            const timeParts = timeStr.split(':');
                            if (timeParts.length >= 2) {
                                const hours = parseInt(timeParts[0]);
                                const minutes = parseInt(timeParts[1]);
                                const timeDate = new Date();
                                timeDate.setHours(hours, minutes, 0, 0);
                                formattedTime = timeDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            }
                        }
                    } catch (e) {
                        console.error('Error formatting date/time for meeting:', doc.id, e);
                    }
                    
                    return `<tr class="hover:bg-gray-50 transition-colors" data-meeting-id="${doc.id}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${doc.title || doc.meeting_title || doc.document_name || 'Untitled Meeting'}</div>
                                    <div class="text-sm text-gray-500">Meeting ID: ${doc.id}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">${formattedDate}</div>
                            <div class="text-sm text-gray-500">${formattedTime}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${doc.location || 'No location specified'}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate" title="${doc.description || ''}">${doc.description && doc.description.trim() && doc.description !== '' ? (doc.description.length > 50 ? doc.description.substring(0, 50) + '...' : doc.description) : 'No description available'}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <button onclick="viewMeeting(${doc.id})" class="text-blue-600 hover:text-blue-900 font-medium flex items-center" title="View Meeting Details">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </button>
                                <button onclick="simpleDeleteMeeting(${doc.id})" class="text-red-600 hover:text-red-900 font-medium flex items-center" title="Move to Trash">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Move to Trash
                                </button>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            }
            tableHTML += `</tbody></table></div></div>`;

            container.innerHTML = tableHTML;
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            
            const title = document.getElementById('meeting-title').value.trim();
            const date = document.getElementById('meeting-date').value;
            const time = document.getElementById('meeting-time').value;
            const location = document.getElementById('meeting-location').value.trim();
            const description = document.getElementById('meeting-description').value.trim();
            
            if (!title || !date || !time) {
                alert('Please fill in required fields (title, date, and time)');
                return;
            }
            
            const meetingDateTime = new Date(date + 'T' + time);
            const now = new Date();
            if (meetingDateTime < now) {
                alert('Cannot schedule a meeting in the past');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('title', title);
            formData.append('date', date);
            formData.append('time', time);
            formData.append('end_date', date); // Use same date as end date
            formData.append('end_time', time); // Use same time as end time
            formData.append('location', location);
            formData.append('description', description);
            formData.append('is_all_day', '0'); // Default to not all-day
            formData.append('color', 'blue'); // Default color

            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Meeting form response:', data);
                if (data.success) {
                    document.getElementById('meeting-form').reset();
                    document.getElementById('meeting-date').value = new Date().toISOString().split('T')[0];
                    document.getElementById('meeting-time').value = '12:00'; // Reset time to 12:00 PM
                    loadMeetings(); // Load meetings for calendar view
                    updateReminders(); // Update reminders immediately
                    loadStats(); // Update statistics immediately
                    if (window.lilacNotifications) {
                        window.lilacNotifications.success('Meeting scheduled successfully!');
                    } else {
                        alert('Meeting scheduled successfully!');
                    }
                } else {
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error scheduling meeting:', error);
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Error scheduling meeting');
                } else {
                    alert('Error scheduling meeting');
                }
            });
        }

        // Initialize variables for meeting management
        let currentDocuments = [];
        let currentTrashMeetings = [];
        let meetingIndexToCancel = null;

        function showDeleteModal(id, title) {
            console.log('showDeleteModal called with id:', id, 'title:', title);
            console.log('window.lilacNotifications:', window.lilacNotifications);
            console.log('window.lilacNotifications.confirm:', window.lilacNotifications?.confirm);
            console.log('typeof window.lilacNotifications.confirm:', typeof window.lilacNotifications?.confirm);
            
            meetingIndexToCancel = id;
            
            // Wait a bit to ensure lilac-enhancements.js is loaded
            setTimeout(() => {
                // Always try to use custom confirmation first
                if (window.lilacNotifications && typeof window.lilacNotifications.confirm === 'function') {
                    console.log('Using lilacNotifications.confirm');
                    window.lilacNotifications.confirm(
                        `Are you sure you want to delete "${title}"? This action cannot be undone.`,
                        () => deleteMeeting(id),
                        () => { meetingIndexToCancel = null; }
                    );
                } else {
                    console.log('lilacNotifications not available, using native confirm');
                    // Fallback to native confirm only if custom modal is not available
                    if (confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
                        deleteMeeting(id);
                    }
                }
            }, 100);
        }

        function removeMeetingFromUI(id) {
            console.log('Removing meeting from UI:', id);
            console.log('ID type in removeMeetingFromUI:', typeof id);
            
            // Remove from currentMeetings array
            const meetingIndex = currentMeetings.findIndex(meeting => meeting.id == id);
            console.log('Meeting index found:', meetingIndex);
            if (meetingIndex !== -1) {
                const removedMeeting = currentMeetings.splice(meetingIndex, 1)[0];
                console.log('Removed meeting from array:', removedMeeting);
            } else {
                console.log('Meeting not found in currentMeetings array');
            }
            
            // Remove from calendar view
            const scheduleContainer = document.getElementById('schedule-grid');
            if (scheduleContainer) {
                // Find and remove the meeting element from the calendar
                const meetingElements = scheduleContainer.querySelectorAll(`[data-meeting-id="${id}"]`);
                meetingElements.forEach(element => {
                    element.style.transition = 'all 0.3s ease';
                    element.style.opacity = '0';
                    element.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        if (element.parentNode) {
                            element.parentNode.removeChild(element);
                        }
                    }, 300);
                });
            }
            
            // Remove from meetings list (if in meetings view) - Enhanced removal
            const meetingsContainer = document.getElementById('meetings-container');
            if (meetingsContainer) {
                // Try multiple selectors to find the meeting row
                let meetingRow = meetingsContainer.querySelector(`[data-meeting-id="${id}"]`);
                if (!meetingRow) {
                    // Try finding by text content that contains the meeting ID
                    const rows = meetingsContainer.querySelectorAll('tr');
                    for (let row of rows) {
                        if (row.textContent.includes(`Meeting ID: ${id}`)) {
                            meetingRow = row;
                            break;
                        }
                    }
                }
                
                if (meetingRow) {
                    meetingRow.style.transition = 'all 0.3s ease';
                    meetingRow.style.opacity = '0';
                    meetingRow.style.transform = 'translateX(-100%)';
                    setTimeout(() => {
                        if (meetingRow.parentNode) {
                            meetingRow.parentNode.removeChild(meetingRow);
                        }
                    }, 300);
                } else {
                    console.log('Meeting row not found in meetings container, reloading meetings');
                    // If we can't find the specific row, reload the meetings list
                    loadDocuments();
                }
            }
            
            // Update reminders section
            updateReminders();
            
            // Re-render schedule to update the view
            renderSchedule();
        }

        function simpleDeleteMeeting(id) {
            console.log('SIMPLE DELETE CALLED FOR ID:', id);
            
            if (confirm('Are you sure you want to move this meeting to trash?')) {
                console.log('User confirmed moving to trash');
                
                // Create form data
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                // Send request
                fetch('api/scheduler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    console.log('Response length:', text.length);
                    console.log('Response type:', typeof text);
                    
                    // Check if response is empty or whitespace
                    if (!text || text.trim() === '') {
                        console.log('Empty response received');
                        alert('Meeting deleted successfully!');
                        loadMeetings();
                        return;
                    }
                    
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed response:', data);
                        
                        if (data.success) {
                            // Remove the meeting from currentMeetings array
                            const meetingIndex = currentMeetings.findIndex(m => m.id == id);
                            if (meetingIndex !== -1) {
                                currentMeetings.splice(meetingIndex, 1);
                                console.log('Removed meeting from currentMeetings array');
                            }
                            
                            // Refresh the calendar display
                            renderSchedule();
                            renderMiniCalendar();
                            
                            alert('Meeting moved to trash successfully!');
                            // Immediately remove the meeting row from the table
                            const meetingRow = document.querySelector(`tr[data-meeting-id="${id}"]`);
                            if (meetingRow) {
                                console.log('Found meeting row, removing with animation');
                                meetingRow.style.transition = 'all 0.3s ease';
                                meetingRow.style.opacity = '0';
                                meetingRow.style.transform = 'translateX(-100%)';
                                setTimeout(() => {
                                    if (meetingRow.parentNode) {
                                        meetingRow.parentNode.removeChild(meetingRow);
                                    }
                                }, 300);
                            }
                            
                            // Update reminders
                            updateReminders();
                        } else {
                            alert('Error: ' + (data.message || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Failed to parse response:', text);
                        
                        // If we can't parse JSON but the meeting was actually moved to trash, 
                        // check if the response contains success indicators
                        if (text.includes('success') || text.includes('moved to trash')) {
                            console.log('Response suggests success, treating as successful');
                            alert('Meeting moved to trash successfully!');
                            loadMeetings();
                        } else {
                            alert('Error: Invalid response from server');
                        }
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error: ' + error.message);
                });
            } else {
                console.log('User cancelled moving to trash');
            }
        }

        function deleteMeeting(id) {
            console.log('=== DELETE MEETING FUNCTION CALLED ===');
            console.log('deleteMeeting called with id:', id);
            console.log('ID type:', typeof id);
            console.log('Current time:', new Date().toISOString());
            
            // UI is already updated, just handle backend
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            console.log('Sending delete request for meeting ID:', id);
            console.log('FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            console.log('Sending API request to api/scheduler.php');
            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Delete response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        console.error('Raw text was:', text);
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Delete response data:', data);
                console.log('Delete response message:', data.message);
                if (data.success) {
                    // Remove the meeting from currentMeetings array
                    const meetingIndex = currentMeetings.findIndex(m => m.id == id);
                    if (meetingIndex !== -1) {
                        currentMeetings.splice(meetingIndex, 1);
                        console.log('Removed meeting from currentMeetings array');
                    }
                    
                    // Refresh the calendar display
                    renderSchedule();
                    renderMiniCalendar();
                    
                    // Update trash count and reminders
                    loadTrashCount(); // Update trash count
                    updateReminders(); // Update reminders after deletion
                    loadStats(); // Update statistics after deletion
                    console.log('Success response:', data);
                } else {
                    // If backend failed, restore the meeting in UI
                    console.error('Move to trash failed:', data.message);
                    console.error('Full response:', data);
                    loadMeetings(); // Reload to restore the meeting
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error moving event to trash: ' + data.message);
                    } else {
                        alert('Error moving event to trash: ' + data.message);
                    }
                }
                meetingIndexToCancel = null;
            })
            .catch(error => {
                console.error('Error moving meeting to trash:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack,
                    type: error.constructor.name
                });
                // If network error, restore the meeting in UI
                loadMeetings(); // Reload to restore the meeting
                meetingIndexToCancel = null;
            });
        }

        function viewMeeting(id) {
            const meeting = currentDocuments.find(m => m.id == id);
            if (meeting) {
                const meetingTitle = meeting.meeting_title || meeting.title || meeting.document_name || 'Untitled Meeting';
                alert(`Meeting Details:\n\nTitle: ${meetingTitle}\nDate: ${meeting.meeting_date}\nTime: ${meeting.meeting_time}\nLocation: ${meeting.location || 'No location'}\nDescription: ${meeting.description || 'No description'}`);
            }
        }

        // Trash bin functions
        function loadTrashMeetings() {
            console.log('Loading trash meetings from API...');
            fetch('api/scheduler.php?action=get_trash')
                .then(response => {
                    console.log('Trash API response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Trash API response data:', data);
                    if (data.success && Array.isArray(data.meetings)) {
                        currentTrashMeetings = data.meetings;
                        displayTrashMeetings(data.meetings);
                        updateTrashCount(data.meetings.length);
                        console.log(`Successfully loaded ${data.meetings.length} trash meetings`);
                    } else {
                        console.error('Trash API returned invalid data:', data);
                        displayTrashMeetings([]);
                        updateTrashCount(0);
                    }
                })
                .catch(error => {
                    console.error('Error loading trash meetings from API:', error);
                    // Fallback to PHP data if API fails
                    try {
                const trashData = <?php echo json_encode($trashData); ?>;
                        console.log('Using fallback PHP trash data:', trashData);
                
                if (Array.isArray(trashData)) {
                    currentTrashMeetings = trashData;
                    displayTrashMeetings(trashData);
                    updateTrashCount(trashData.length);
                            console.log(`Fallback: Loaded ${trashData.length} trash meetings from PHP data`);
                } else {
                            console.log('Fallback: No valid trash data found');
                    displayTrashMeetings([]);
                    updateTrashCount(0);
                }
                    } catch (fallbackError) {
                        console.error('Fallback also failed:', fallbackError);
                displayTrashMeetings([]);
                updateTrashCount(0);
            }
                });
        }

        function updateTrashCount(count) {
            // Update the original badge (in actions dropdown)
            const badge = document.getElementById('trash-count-badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
            
            // Update the main trash bin button badge
            const mainBadge = document.getElementById('trash-count-badge-main');
            if (mainBadge) {
                if (count > 0) {
                    mainBadge.textContent = count;
                    mainBadge.classList.remove('hidden');
                } else {
                    mainBadge.classList.add('hidden');
                }
            }
        }

        function loadTrashCount() {
            console.log('Loading trash count from API...');
            fetch('api/scheduler.php?action=get_trash')
                .then(response => {
                    console.log('Trash count API response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(responseText => {
                    if (!responseText || responseText.trim() === '') {
                        console.log('Main API returned empty response, trying fallback...');
                        // Try fallback API instead of throwing error
                        return fetch('api/scheduler_simple.php?action=get_trash')
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
                .then(data => {
                    console.log('Trash count API response data:', data);
                    if (data.success && Array.isArray(data.meetings)) {
                        updateTrashCount(data.meetings.length);
                    } else {
                        console.log('Trash count API returned invalid data, using fallback');
                        // Fallback to PHP data
                        try {
                            const trashData = <?php echo json_encode($trashData ?? []); ?>;
                            if (Array.isArray(trashData)) {
                                updateTrashCount(trashData.length);
                            } else {
                                updateTrashCount(0);
                            }
                        } catch (fallbackError) {
                            console.error('PHP fallback also failed:', fallbackError);
                            updateTrashCount(0);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading trash count from API:', error);
                    // Final fallback to PHP data
                    try {
                        const trashData = <?php echo json_encode($trashData ?? []); ?>;
                        if (Array.isArray(trashData)) {
                            updateTrashCount(trashData.length);
                        } else {
                            updateTrashCount(0);
                        }
                    } catch (fallbackError) {
                        console.error('All fallbacks failed:', fallbackError);
                        updateTrashCount(0);
                    }
                });
        }

        function displayTrashMeetings(meetings) {
            const container = document.getElementById('trash-container');
            
            if (!container) {
                console.error('Trash container element not found');
                return;
            }
            
            // Ensure meetings is an array
            if (!Array.isArray(meetings)) {
                console.warn('displayTrashMeetings received non-array data:', meetings);
                meetings = [];
            }
            
            console.log(`Displaying ${meetings.length} trash meetings`);
            
            
            // Add search, filter, and sort controls
            let controlsHTML = `
                <div class="mb-6 space-y-4">
                    <!-- Search and Filter Row -->
                    <div class="flex flex-col lg:flex-row gap-4">
                        <!-- Search Bar -->
                        <div class="flex-1">
                            <div class="relative">
                                <input type="text" id="trash-search" placeholder="Search by meeting title..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       onkeyup="filterTrashMeetings()">
                                <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Filter by Month/Year -->
                        <div class="flex gap-2">
                            <select id="trash-month-filter" onchange="filterTrashMeetings()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                            <select id="trash-year-filter" onchange="filterTrashMeetings()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">All Years</option>
                                <option value="2023">2023</option>
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                            </select>
                        </div>
                        
                        <!-- Sort Options -->
                        <div class="flex gap-2">
                            <select id="trash-sort" onchange="sortTrashMeetings()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="deleted_at_desc">Deleted At (Latest First)</option>
                                <option value="deleted_at_asc">Deleted At (Oldest First)</option>
                                <option value="meeting_date_desc">Event Date (Latest First)</option>
                                <option value="meeting_date_asc">Event Date (Oldest First)</option>
                                <option value="title_asc">Title (A-Z)</option>
                                <option value="title_desc">Title (Z-A)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Results Count -->
                    <div class="text-sm text-gray-600">
                        Showing <span id="trash-results-count">${meetings.length}</span> of ${meetings.length} deleted meetings
                    </div>
                </div>
            `;
            
            let tableHTML = `<div class="overflow-x-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll()" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mr-2">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Meeting Title</span>
                                        <span class="sm:hidden">Title</span>
                                    </div>
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Date & Time
                                    </div>
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Deleted At
                                    </div>
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Deleted By
                                    </div>
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        Type
                                    </div>
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
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
            
            if (meetings.length === 0) {
                tableHTML += `<tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Trash is empty</h3>
                            <p class="text-gray-500">No deleted meetings found</p>
                        </div>
                    </td>
                </tr>`;
            } else {
                tableHTML += meetings.map(meeting => {
                    let formattedDate = 'Not specified';
                    let formattedTime = 'Not specified';
                    let formattedDeletedAt = 'Unknown';
                    let timeUntilPermanent = '';
                    let eventType = 'Meeting';
                    let colorIndicator = '';
                    
                    try {
                        // Handle both meeting_date/meeting_time and date/time field names
                        const meetingDate = meeting.meeting_date || meeting.date;
                        const meetingTime = meeting.meeting_time || meeting.time;
                        
                        if (meetingDate) {
                            const dateObj = new Date(meetingDate);
                            if (!isNaN(dateObj.getTime())) {
                                formattedDate = dateObj.toLocaleDateString('en-US', { 
                                    year: 'numeric', 
                                    month: 'short', 
                                    day: 'numeric' 
                                });
                            }
                        }
                        
                        if (meetingTime) {
                            const timeStr = meetingTime;
                            const timeParts = timeStr.split(':');
                            if (timeParts.length >= 2) {
                                const hours = parseInt(timeParts[0]);
                                const minutes = parseInt(timeParts[1]);
                                const timeDate = new Date();
                                timeDate.setHours(hours, minutes, 0, 0);
                                formattedTime = timeDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            }
                        }

                        if (meeting.deleted_at) {
                            const deletedDate = new Date(meeting.deleted_at);
                            if (!isNaN(deletedDate.getTime())) {
                                formattedDeletedAt = deletedDate.toLocaleDateString('en-US', { 
                                    year: 'numeric', 
                                    month: 'short', 
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                                
                                // Calculate time until permanent deletion (30 days)
                                const permanentDate = new Date(deletedDate.getTime() + (30 * 24 * 60 * 60 * 1000));
                                const now = new Date();
                                const timeLeft = permanentDate.getTime() - now.getTime();
                                
                                if (timeLeft > 0) {
                                    const daysLeft = Math.ceil(timeLeft / (24 * 60 * 60 * 1000));
                                    timeUntilPermanent = `${daysLeft} day${daysLeft !== 1 ? 's' : ''} until permanent deletion`;
                                } else {
                                    timeUntilPermanent = 'Ready for permanent deletion';
                                }
                            }
                        }
                        
                        // Determine event type
                        if (meeting.is_all_day == '1') {
                            eventType = 'All-Day Event';
                        } else if (meeting.description && meeting.description.length > 100) {
                            eventType = 'Detailed Meeting';
                        } else {
                            eventType = 'Meeting';
                        }
                        
                        // Color indicator
                        if (meeting.color) {
                            const colorMap = {
                                'blue': 'bg-blue-500',
                                'red': 'bg-red-500',
                                'green': 'bg-green-500',
                                'yellow': 'bg-yellow-500',
                                'purple': 'bg-purple-500',
                                'pink': 'bg-pink-500',
                                'indigo': 'bg-indigo-500',
                                'gray': 'bg-gray-500'
                            };
                            const colorClass = colorMap[meeting.color] || 'bg-blue-500';
                            colorIndicator = `<div class="w-3 h-3 rounded-full ${colorClass} mr-2"></div>`;
                        }
                        
                    } catch (e) {
                        console.error('Error formatting date/time for trash meeting:', meeting.id, e);
                    }
                    
                    const description = meeting.description || 'No description available';
                    const truncatedDescription = description.length > 50 ? description.substring(0, 50) + '...' : description;
                    
                    return `<tr class="hover:bg-gray-50 transition-colors" data-meeting-id="${meeting.id}" data-meeting-title="${meeting.title || ''}" data-meeting-date="${meeting.meeting_date || meeting.date || ''}" data-meeting-month="${new Date(meeting.meeting_date || meeting.date || new Date()).getMonth() + 1}" data-meeting-year="${new Date(meeting.meeting_date || meeting.date || new Date()).getFullYear()}">
                        <td class="px-3 sm:px-6 py-4">
                            <div class="flex items-center">
                                <input type="checkbox" class="meeting-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mr-3" data-meeting-id="${meeting.id}" onchange="updateBulkActionButtons()">
                                <div class="flex-shrink-0 h-8 w-8 sm:h-10 sm:w-10">
                                    <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-full bg-red-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-2 sm:ml-4 flex-1 min-w-0">
                                    <div class="flex items-center">
                                        ${colorIndicator}
                                        <button onclick="showTrashMeetingDetails(${meeting.id})" class="text-sm font-medium text-gray-900 hover:text-blue-600 transition-colors text-left truncate">
                                            ${meeting.title || 'Untitled Meeting'}
                                        </button>
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-500">ID: ${meeting.original_id}</div>
                                    <div class="text-xs text-gray-400 mt-1 truncate" title="${description}">${truncatedDescription}</div>
                                    <div class="sm:hidden text-xs text-gray-500 mt-1">${formattedDate} ${formattedTime}</div>
                                    ${timeUntilPermanent ? `<div class="text-xs text-orange-600 mt-1">${timeUntilPermanent}</div>` : ''}
                                </div>
                            </div>
                        </td>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                            <div class="text-sm text-gray-900 font-medium">${formattedDate}</div>
                            <div class="text-sm text-gray-500">${formattedTime}</div>
                            ${meeting.is_all_day == '1' ? '<div class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded-full inline-block mt-1">All Day</div>' : ''}
                        </td>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                            <div class="text-sm text-gray-900">${formattedDeletedAt}</div>
                        </td>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden xl:table-cell">
                            <div class="text-sm text-gray-900">${meeting.deleted_by || 'System'}</div>
                        </td>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                            <div class="text-sm text-gray-900">${eventType}</div>
                        </td>
                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-1 sm:space-x-3">
                                <button onclick="showRestoreConfirmModal(${meeting.id}, '${(meeting.title || 'Untitled Meeting').replace(/'/g, "\\'")}')" class="text-green-600 hover:text-green-900 font-medium flex items-center text-xs sm:text-sm" title="Restore Meeting">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Restore</span>
                                </button>
                                <button onclick="showDeleteConfirmModal(${meeting.id}, '${(meeting.title || 'Untitled Meeting').replace(/'/g, "\\'")}')" class="text-red-600 hover:text-red-900 font-medium flex items-center text-xs sm:text-sm" title="Permanently Delete">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            }
            tableHTML += `</tbody></table></div></div>`;
            
            container.innerHTML = controlsHTML + tableHTML;
            
            // Store meetings data for filtering/sorting
            window.currentTrashMeetings = meetings;
        }

        function restoreMeeting(trashId) {
            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('trash_id', trashId);

            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Find the restored meeting in trash and add it back to currentMeetings
                    const restoredMeeting = currentTrashMeetings.find(m => m.id == trashId);
                    if (restoredMeeting) {
                        // Remove from trash array
                        currentTrashMeetings = currentTrashMeetings.filter(m => m.id != trashId);
                        
                        // Add back to currentMeetings
                        const meetingToRestore = {
                            ...restoredMeeting,
                            type: 'meeting',
                            color: restoredMeeting.color || 'blue',
                            startTime: restoredMeeting.meeting_time || restoredMeeting.time,
                            endTime: restoredMeeting.end_time || calculateEndTime(restoredMeeting.meeting_time || restoredMeeting.time, 60),
                            date: restoredMeeting.meeting_date || restoredMeeting.date,
                            dateEnd: restoredMeeting.end_date || restoredMeeting.meeting_date || restoredMeeting.date
                        };
                        currentMeetings.push(meetingToRestore);
                        
                        // Refresh displays
                        renderSchedule();
                        renderMiniCalendar();
                        updateReminders();
                    }
                    
                    loadTrashMeetings();
                    loadDocuments();
                    loadStats();
                    loadTrashCount(); // Update trash count
                    if (window.lilacNotifications) {
                        window.lilacNotifications.success('Meeting restored successfully!');
                    } else {
                        alert('Meeting restored successfully!');
                    }
                } else {
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error restoring meeting:', error);
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Error restoring meeting');
                } else {
                    alert('Error restoring meeting');
                }
            });
        }

        function permanentlyDeleteMeeting(trashId, title) {
            const formData = new FormData();
            formData.append('action', 'permanently_delete');
            formData.append('trash_id', trashId);

            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadTrashMeetings();
                    loadTrashCount(); // Update trash count
                    if (window.lilacNotifications) {
                        window.lilacNotifications.success('Meeting permanently deleted!');
                    } else {
                        alert('Meeting permanently deleted!');
                    }
                } else {
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error permanently deleting meeting:', error);
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Error permanently deleting meeting');
                } else {
                    alert('Error permanently deleting meeting');
                }
            });
        }

        function emptyTrash() {
            const message = 'Are you sure you want to empty the trash? This will permanently delete all meetings in the trash and cannot be undone.';
            
            showBulkConfirmDialog('Empty Trash', message, function() {
                performEmptyTrash();
            });
        }

        function performEmptyTrash() {
            const formData = new FormData();
            formData.append('action', 'empty_trash');

            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadTrashMeetings();
                    loadTrashCount(); // Update trash count
                    if (window.lilacNotifications) {
                        window.lilacNotifications.success('Trash emptied successfully!');
                    } else {
                        alert('Trash emptied successfully!');
                    }
                } else {
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error emptying trash:', error);
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Error emptying trash');
                } else {
                    alert('Error emptying trash');
                }
            });
        }

        // Bulk Actions Functions
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const meetingCheckboxes = document.querySelectorAll('.meeting-checkbox');
            
            meetingCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateBulkActionButtons();
        }

        function updateBulkActionButtons() {
            const meetingCheckboxes = document.querySelectorAll('.meeting-checkbox');
            const selectedCheckboxes = document.querySelectorAll('.meeting-checkbox:checked');
            const restoreAllBtn = document.getElementById('restore-all-btn');
            const deleteAllBtn = document.getElementById('delete-all-btn');
            
            if (selectedCheckboxes.length > 0) {
                restoreAllBtn.style.display = 'flex';
                deleteAllBtn.style.display = 'flex';
            } else {
                restoreAllBtn.style.display = 'none';
                deleteAllBtn.style.display = 'none';
            }
        }

        function getSelectedMeetingIds() {
            const selectedCheckboxes = document.querySelectorAll('.meeting-checkbox:checked');
            const meetingIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.getAttribute('data-meeting-id'));
            console.log('getSelectedMeetingIds found:', selectedCheckboxes.length, 'selected checkboxes');
            console.log('Meeting IDs:', meetingIds);
            return meetingIds;
        }

        function restoreAllSelected() {
            const selectedIds = getSelectedMeetingIds();
            if (selectedIds.length === 0) {
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('No meetings selected');
                } else {
                    alert('No meetings selected');
                }
                return;
            }

            const message = `Are you sure you want to restore ${selectedIds.length} meeting${selectedIds.length > 1 ? 's' : ''}?`;
            showBulkConfirmDialog('Restore Meetings', message, function() {
                performBulkRestore(selectedIds);
            });
        }

        function performBulkRestore(meetingIds) {
            const formData = new FormData();
            formData.append('action', 'bulk_restore');
            formData.append('meeting_ids', JSON.stringify(meetingIds));

            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Find and restore all selected meetings
                    const restoredMeetings = currentTrashMeetings.filter(m => meetingIds.includes(m.id));
                    restoredMeetings.forEach(restoredMeeting => {
                        // Remove from trash array
                        currentTrashMeetings = currentTrashMeetings.filter(m => m.id != restoredMeeting.id);
                        
                        // Add back to currentMeetings
                        const meetingToRestore = {
                            ...restoredMeeting,
                            type: 'meeting',
                            color: restoredMeeting.color || 'blue',
                            startTime: restoredMeeting.meeting_time || restoredMeeting.time,
                            endTime: restoredMeeting.end_time || calculateEndTime(restoredMeeting.meeting_time || restoredMeeting.time, 60),
                            date: restoredMeeting.meeting_date || restoredMeeting.date,
                            dateEnd: restoredMeeting.end_date || restoredMeeting.meeting_date || restoredMeeting.date
                        };
                        currentMeetings.push(meetingToRestore);
                    });
                    
                    // Refresh displays
                    renderSchedule();
                    renderMiniCalendar();
                    updateReminders();
                    
                    loadTrashMeetings();
                    loadDocuments();
                    loadStats();
                    loadTrashCount();
                    if (window.lilacNotifications) {
                        window.lilacNotifications.success(`${meetingIds.length} meeting${meetingIds.length > 1 ? 's' : ''} restored successfully!`);
                    } else {
                        alert(`${meetingIds.length} meeting${meetingIds.length > 1 ? 's' : ''} restored successfully!`);
                    }
                } else {
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error performing bulk restore:', error);
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Error restoring meetings');
                } else {
                    alert('Error restoring meetings');
                }
            });
        }

        function deleteAllSelected() {
            console.log('deleteAllSelected function called');
            const selectedIds = getSelectedMeetingIds();
            console.log('Selected IDs for deletion:', selectedIds);
            
            if (selectedIds.length === 0) {
                console.log('No meetings selected for deletion');
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('No meetings selected');
                } else {
                    alert('No meetings selected');
                }
                return;
            }

            const message = `Are you sure you want to permanently delete ${selectedIds.length} meeting${selectedIds.length > 1 ? 's' : ''}? This action cannot be undone.`;
            showBulkConfirmDialog('Delete Meetings', message, function() {
                console.log('User confirmed bulk delete');
                performBulkDelete(selectedIds);
            });
        }

        function showBulkConfirmDialog(title, message, onConfirm) {
            const dialog = document.getElementById('bulk-confirm-dialog');
            const titleElement = document.getElementById('bulk-confirm-title');
            const messageElement = document.getElementById('bulk-confirm-message');
            const confirmBtn = document.getElementById('bulk-confirm-btn');
            const cancelBtn = document.getElementById('bulk-confirm-cancel-btn');
            
            // Set the title and message
            titleElement.textContent = title;
            messageElement.textContent = message;
            
            // Store the callback globally to avoid security issues
            window.bulkConfirmCallback = onConfirm;
            
            // Show the dialog
            dialog.classList.remove('hidden');
            
            // Handle confirm button click - use direct function call
            confirmBtn.onclick = handleBulkConfirm;
            
            // Handle cancel button click
            cancelBtn.onclick = handleBulkCancel;
            
            // Handle clicking outside the dialog
            dialog.onclick = function(e) {
                if (e.target === dialog) {
                    handleBulkCancel();
                }
            };
        }

        function handleBulkConfirm() {
            console.log('Bulk confirm button clicked');
            const dialog = document.getElementById('bulk-confirm-dialog');
            dialog.classList.add('hidden');
            
            if (window.bulkConfirmCallback) {
                window.bulkConfirmCallback();
                window.bulkConfirmCallback = null;
            }
        }

        function handleBulkCancel() {
            console.log('Bulk cancel button clicked');
            const dialog = document.getElementById('bulk-confirm-dialog');
            dialog.classList.add('hidden');
            window.bulkConfirmCallback = null;
        }

        function performBulkDelete(meetingIds) {
            console.log('performBulkDelete called with IDs:', meetingIds);
            
            const formData = new FormData();
            formData.append('action', 'bulk_delete');
            formData.append('meeting_ids', JSON.stringify(meetingIds));

            console.log('Sending bulk delete request to api/scheduler.php');
            console.log('Meeting IDs being sent:', JSON.stringify(meetingIds));

            fetch('api/scheduler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Bulk delete response status:', response.status);
                return response.text().then(text => {
                    console.log('Bulk delete raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON response:', e);
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Bulk delete parsed response:', data);
                if (data.success) {
                    console.log('Bulk delete successful, reloading trash meetings');
                    loadTrashMeetings();
                    loadTrashCount();
                    if (window.lilacNotifications) {
                        window.lilacNotifications.success(`${meetingIds.length} meeting${meetingIds.length > 1 ? 's' : ''} permanently deleted!`);
                    } else {
                        alert(`${meetingIds.length} meeting${meetingIds.length > 1 ? 's' : ''} permanently deleted!`);
                    }
                } else {
                    console.error('Bulk delete failed:', data.message);
                    if (window.lilacNotifications) {
                        window.lilacNotifications.error('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error performing bulk delete:', error);
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Error deleting meetings: ' + error.message);
                } else {
                    alert('Error deleting meetings: ' + error.message);
                }
            });
        }

        // Trash Bin Enhanced Functions
        function filterTrashMeetings() {
            const searchTerm = document.getElementById('trash-search').value.toLowerCase();
            const monthFilter = document.getElementById('trash-month-filter').value;
            const yearFilter = document.getElementById('trash-year-filter').value;
            
            const rows = document.querySelectorAll('#trash-container tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const title = row.getAttribute('data-meeting-title').toLowerCase();
                const meetingDate = row.getAttribute('data-meeting-date');
                const meetingMonth = row.getAttribute('data-meeting-month');
                const meetingYear = row.getAttribute('data-meeting-year');
                
                let showRow = true;
                
                // Search filter
                if (searchTerm && !title.includes(searchTerm)) {
                    showRow = false;
                }
                
                // Month filter
                if (monthFilter && meetingMonth !== monthFilter) {
                    showRow = false;
                }
                
                // Year filter
                if (yearFilter && meetingYear !== yearFilter) {
                    showRow = false;
                }
                
                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update results count
            const resultsCountElement = document.getElementById('trash-results-count');
            if (resultsCountElement) {
                resultsCountElement.textContent = visibleCount;
            }
        }

        function sortTrashMeetings() {
            const sortOption = document.getElementById('trash-sort').value;
            const tbody = document.querySelector('#trash-container tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                switch (sortOption) {
                    case 'deleted_at_desc':
                        return new Date(b.querySelector('td:nth-child(3)').textContent) - new Date(a.querySelector('td:nth-child(3)').textContent);
                    case 'deleted_at_asc':
                        return new Date(a.querySelector('td:nth-child(3)').textContent) - new Date(b.querySelector('td:nth-child(3)').textContent);
                    case 'meeting_date_desc':
                        return new Date(b.querySelector('td:nth-child(2)').textContent) - new Date(a.querySelector('td:nth-child(2)').textContent);
                    case 'meeting_date_asc':
                        return new Date(a.querySelector('td:nth-child(2)').textContent) - new Date(b.querySelector('td:nth-child(2)').textContent);
                    case 'title_asc':
                        return a.querySelector('td:nth-child(1) button').textContent.trim().localeCompare(b.querySelector('td:nth-child(1) button').textContent.trim());
                    case 'title_desc':
                        return b.querySelector('td:nth-child(1) button').textContent.trim().localeCompare(a.querySelector('td:nth-child(1) button').textContent.trim());
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        function showTrashMeetingDetails(meetingId) {
            const meeting = window.currentTrashMeetings.find(m => m.id == meetingId);
            if (!meeting) {
                if (window.lilacNotifications) {
                    window.lilacNotifications.error('Meeting not found');
                } else {
                    alert('Meeting not found');
                }
                return;
            }
            
            let formattedDate = 'Not specified';
            let formattedTime = 'Not specified';
            let formattedDeletedAt = 'Unknown';
            
            try {
                if (meeting.meeting_date) {
                    const meetingDate = new Date(meeting.meeting_date);
                    if (!isNaN(meetingDate.getTime())) {
                        formattedDate = meetingDate.toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                    }
                }
                
                if (meeting.meeting_time) {
                    const timeStr = meeting.meeting_time;
                    const timeParts = timeStr.split(':');
                    if (timeParts.length >= 2) {
                        const hours = parseInt(timeParts[0]);
                        const minutes = parseInt(timeParts[1]);
                        const timeDate = new Date();
                        timeDate.setHours(hours, minutes, 0, 0);
                        formattedTime = timeDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    }
                }

                if (meeting.deleted_at) {
                    const deletedDate = new Date(meeting.deleted_at);
                    if (!isNaN(deletedDate.getTime())) {
                        formattedDeletedAt = deletedDate.toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                }
            } catch (e) {
                console.error('Error formatting date/time for trash meeting details:', meeting.id, e);
            }
            
            const detailsHTML = `
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">${meeting.title || 'Untitled Meeting'}</h3>
                        <p class="text-sm text-gray-600">Original ID: ${meeting.original_id}</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Event Details</h4>
                            <p class="text-sm text-gray-600">Date: ${formattedDate}</p>
                            <p class="text-sm text-gray-600">Time: ${formattedTime}</p>
                            <p class="text-sm text-gray-600">Location: ${meeting.location || 'No location specified'}</p>
                            <p class="text-sm text-gray-600">Type: ${meeting.is_all_day == '1' ? 'All-Day Event' : 'Timed Event'}</p>
                            ${meeting.color ? `<p class="text-sm text-gray-600">Color: ${meeting.color}</p>` : ''}
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Deletion Info</h4>
                            <p class="text-sm text-gray-600">Deleted At: ${formattedDeletedAt}</p>
                            <p class="text-sm text-gray-600">Deleted By: ${meeting.deleted_by || 'System'}</p>
                        </div>
                    </div>
                    
                    ${meeting.description ? `
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Description</h4>
                            <p class="text-sm text-gray-600 whitespace-pre-wrap">${meeting.description}</p>
                        </div>
                    ` : ''}
                </div>
            `;
            
            if (window.lilacNotifications && window.lilacNotifications.showModal) {
                window.lilacNotifications.showModal('Meeting Details', detailsHTML, [
                    {
                        text: 'Restore',
                        class: 'bg-green-600 hover:bg-green-700',
                        onClick: function() {
                            showRestoreConfirmModal(meeting.id, meeting.title || 'Untitled Meeting');
                        }
                    },
                    {
                        text: 'Close',
                        class: 'bg-gray-600 hover:bg-gray-700'
                    }
                ]);
            } else {
                alert(detailsHTML.replace(/<[^>]*>/g, ''));
            }
        }

        function showRestoreConfirmModal(meetingId, meetingTitle) {
            const message = `Are you sure you want to restore "${meetingTitle}"? This will move it back to your calendar.`;
            
            // Store the meeting data globally to avoid callback issues
            window.pendingRestoreMeeting = {
                id: meetingId,
                title: meetingTitle
            };
            
            showBulkConfirmDialog('Restore Meeting', message, function() {
                if (window.pendingRestoreMeeting) {
                    restoreMeeting(window.pendingRestoreMeeting.id);
                    window.pendingRestoreMeeting = null;
                }
            });
        }

        function showDeleteConfirmModal(meetingId, meetingTitle) {
            const message = `Are you sure you want to permanently delete "${meetingTitle}"? This action cannot be undone.`;
            
            // Store the meeting data globally to avoid callback issues
            window.pendingDeleteMeeting = {
                id: meetingId,
                title: meetingTitle
            };
            
            showBulkConfirmDialog('Delete Meeting', message, function() {
                if (window.pendingDeleteMeeting) {
                    permanentlyDeleteMeeting(window.pendingDeleteMeeting.id, window.pendingDeleteMeeting.title);
                    window.pendingDeleteMeeting = null;
                }
            });
        }

        // Calendar date selection function
        function selectCalendarDate(dateString) {
            console.log('Calendar date clicked:', dateString);
            
            // Update the clicked date
            clickedDateString = dateString;
            selectedDate = new Date(dateString);
            
            // Re-render calendar to update selection
            renderCalendar();
            
            // If we're in calendar view, navigate to that date
            if (currentView === 'week' || currentView === 'day') {
                currentWeek = new Date(dateString);
                renderSchedule();
            }
            
            // Show events for this date if any exist
            const eventsForDate = currentMeetings.filter(meeting => {
                const meetingDate = meeting.meeting_date || meeting.date;
                return meetingDate === dateString;
            });
            
            if (eventsForDate.length > 0) {
                console.log('Events for selected date:', eventsForDate);
                // You can add logic here to show events in a modal or filter the main view
            }
        }

        // Mobile navigation function
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

        // Dark mode functions
        function initializeDarkMode() {
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.documentElement.classList.add('dark');
            }
        }





        // Setup mobile navigation when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const overlay = document.getElementById('menu-overlay');
            const hamburgerToggle = document.getElementById('hamburger-toggle');
             
            if (menuToggle) {
                menuToggle.addEventListener('click', toggleMenu);
            }
            
            // Hamburger button is now handled globally by LILACSidebar

            // Overlay click is now handled globally by LILACSidebar
        });
        
        // toggleSidebar function is now handled globally by LILACSidebar
    </script>
</head>
<body class="bg-gray-50 dark:bg-[#222831] transition-colors duration-200">
	<!-- Navigation Bar -->
	<nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between relative transition-all duration-300 ease-in-out">
		<div class="flex items-center space-x-4 pl-16">
			<button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
				<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
				</svg>
			</button>
			
			<h1 class="text-xl font-bold text-gray-800 cursor-pointer" onclick="location.reload()">Scheduler</h1>
			
			<a href="dashboard.php" class="flex items-center space-x-3 hover:opacity-80 transition-opacity cursor-pointer">
			</a>
		</div>
		
		<!-- Trash Bin Button in Navbar -->
		<div class="flex items-center pr-4">
			<button id="trash-bin-btn-navbar" onclick="openTrashBin()" class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center space-x-2 relative" title="Trash Bin">
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
				</svg>
				<span class="hidden sm:inline">Trash Bin</span>
				<div id="trash-count-badge-navbar" class="absolute -top-1 -right-1 bg-yellow-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold hidden">0</div>
			</button>
		</div>
	</nav>

	<!-- Sidebar -->
	<?php include 'includes/sidebar.php'; ?>

    <!-- Custom Scrollbar Styles -->
    <style>
        .trash-modal-scroll {
            scrollbar-width: thin;
            scrollbar-color: #64748b #f1f5f9;
        }
        .trash-modal-scroll::-webkit-scrollbar {
            width: 14px;
        }
        .trash-modal-scroll::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin: 4px;
        }
        .trash-modal-scroll::-webkit-scrollbar-thumb {
            background: #64748b;
            border-radius: 8px;
            border: 2px solid #f8fafc;
            min-height: 40px;
        }
        .trash-modal-scroll::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
        .trash-modal-scroll::-webkit-scrollbar-thumb:active {
            background: #334155;
        }
        .trash-modal-scroll::-webkit-scrollbar-corner {
            background: #f8fafc;
        }
    </style>

    <!-- Main Content -->
    <div id="main-content" class="p-4 pt-3 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out">
        
        <!-- Trash Bin Button -->
        <div class="mb-4 flex justify-end">
            <button id="trash-bin-btn-main" onclick="openTrashBin()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center space-x-2 relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <span>Trash Bin</span>
                <!-- Trash Count Badge -->
                <div id="trash-count-badge-main" class="absolute -top-1 -right-1 bg-yellow-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold hidden">
                    0
                </div>
            </button>
        </div>




        <!-- Calendar View -->
        <div id="calendar-view" class="space-y-6">
            <!-- Calendar Header removed to align Quick Reminder to top -->

            <!-- Month Calendar Header + Grid -->
            <div id="month-container-hidden" class="hidden bg-white dark:bg-[#2a2f3a] rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 overflow-hidden">
                 <div class="flex items-center justify-between px-2 py-1 border-b border-gray-200 dark:border-gray-600">
                     <div class="flex items-center space-x-2">
                         <button id="prev-month" onclick="navigateMonth(-1)" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                             <svg class="w-5 h-5 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                             </svg>
                         </button>
                         <button id="next-month" onclick="navigateMonth(1)" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                             <svg class="w-5 h-5 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                             </svg>
                         </button>
                         <span id="calendar-month-year" class="ml-2 text-sm text-gray-700 dark:text-gray-300"></span>
                     </div>
                 </div>
                 <div id="calendar-grid-hidden" class="grid grid-cols-7 gap-0.5 p-1"></div>
             </div>
            
            <!-- Schedule Grid -->
            <div class="flex space-x-6">
                <!-- Left column: Reminders -->
                <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 p-4 w-72 flex-shrink-0 -mt-4">
                    <button id="quick-reminder-btn" class="w-full mb-3 px-3 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition-colors">+ Quick Reminder</button>
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span id="mini-calendar-month-year" class="text-xs font-medium text-gray-700 dark:text-gray-300"></span>
                            <div class="flex space-x-1">
                                <button id="mini-prev-month" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700" title="Previous">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button id="mini-next-month" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700" title="Next">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                        <div id="mini-calendar-grid" class="grid grid-cols-7 gap-1 text-[10px]"></div>
                    </div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reminders</h4>
                    <div id="reminders-list" class="space-y-1">
                        <!-- Reminder items will be inserted here by JavaScript -->
                    </div>
                </div>

                <!-- Right column: Month grid + Schedule stacked -->
                <div class="flex-1 space-y-4 -mt-4">

                    <!-- Date range sub-header -->
                    <div class="w-full flex items-center justify-between px-2">
                        <div class="flex items-center space-x-1">
                            <button id="prev-week-small" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700" title="Previous week" onclick="navigateWeek(-1)">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button id="next-week-small" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700" title="Next week" onclick="navigateWeek(1)">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                        <span id="date-range" class="text-base font-semibold text-gray-700 dark:text-gray-200"></span>
                    </div>

                    <!-- Month Calendar Header + Grid -->
                    <div id="month-container-hidden" class="hidden bg-white dark:bg-[#2a2f3a] rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 overflow-hidden">
                        <div class="flex items-center justify-between px-2 py-1 border-b border-gray-200 dark:border-gray-600">
                            <div class="flex items-center space-x-2">
                                <button id="prev-month" onclick="navigateMonth(-1)" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <button id="next-month" onclick="navigateMonth(1)" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                                <span id="calendar-month-year" class="ml-2 text-sm text-gray-700 dark:text-gray-300"></span>
                            </div>
                        </div>
                        <div id="calendar-grid-hidden" class="grid grid-cols-7 gap-0.5 p-1"></div>
                    </div>

                    <!-- Schedule Grid -->
                    <div id="schedule-grid" class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                        <!-- Schedule will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Meetings Management View -->
        <div id="meetings-view" class="space-y-6" style="display: none;">
            <!-- Meeting Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Meetings</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-meetings">0</p>
                </div>
                <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Upcoming</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="upcoming-meetings">0</p>
                </div>
                <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Today</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="today-meetings">0</p>
                </div>
                <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">This Week</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="week-meetings">0</p>
                </div>
            </div>

            <!-- Schedule Meeting Section -->
            <div class="bg-white dark:bg-[#2a2f3a] p-6 rounded-lg shadow mb-6">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Schedule a Meeting
                </h3>
                
                <form id="meeting-form" class="space-y-6">
                    <!-- Basic Meeting Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="meeting-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meeting Title*</label>
                            <input type="text" id="meeting-title" name="meeting-title" placeholder="Enter meeting title" class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
                        </div>
                        <div>
                            <label for="meeting-location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                            <input type="text" id="meeting-location" name="meeting-location" placeholder="Enter location" class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
                        </div>
                        <div>
                            <label for="meeting-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date*</label>
                            <input type="date" id="meeting-date" name="meeting-date" class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label for="meeting-time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time*</label>
                            <input type="time" id="meeting-time" name="meeting-time" class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="meeting-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea id="meeting-description" name="meeting-description" placeholder="Enter meeting description" class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent h-20 bg-white dark:bg-[#222831] text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="border-t pt-4">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium">
                            Schedule Meeting
                        </button>
                        <p class="text-sm text-gray-500 mt-2">* Required fields</p>
                    </div>
                </form>
            </div>
            
            <!-- Upcoming Meetings Section -->
            <div class="bg-white dark:bg-[#2a2f3a] p-6 rounded-lg shadow">
                <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Upcoming Meetings</h3>
                <div id="meetings-container" class="space-y-4">
                    <!-- Meeting cards will be inserted here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Trash Bin Modal -->
        <div id="trash-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden">
            <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[95vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Modal Content -->
                    <div class="flex-1 overflow-y-auto trash-modal-scroll" style="scroll-behavior: smooth;">
                        <div class="p-4 sm:p-6">
                            <!-- Trash Meetings Section -->
                            <div class="bg-white p-6 rounded-lg shadow">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xl font-bold text-gray-900">Deleted Meetings</h3>
                                    <div class="flex items-center space-x-3">
                                        <button onclick="emptyTrash()" class="bg-red-800 text-white px-4 py-2 rounded-lg hover:bg-red-900 transition-colors font-medium flex items-center space-x-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <span>Empty Trash</span>
                                        </button>
                                        <button onclick="hideTrashModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Bulk Actions Buttons -->
                                <div class="mb-4">
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                        <button id="restore-all-btn" onclick="restoreAllSelected()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center space-x-2" style="display: none;">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                            </svg>
                                            <span>Restore All</span>
                                        </button>
                                        
                                        <button id="delete-all-btn" onclick="deleteAllSelected()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors font-medium flex items-center space-x-2" style="display: none;">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="trash-container" class="space-y-4">
                                    <!-- Trash meetings will be inserted here by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    <!-- Mobile Menu Overlay -->
    <div id="menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Floating View Switch Button Removed per request -->

    <!-- Add Event Modal -->
    <div id="add-event-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" onclick="if(event.target===this){closeAddEventModal()}">
        <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow-xl w-full max-w-3xl mx-4" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-600">
                <h3 id="add-event-modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">Add Event</h3>
                <button onclick="closeAddEventModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <form id="add-event-form" onsubmit="handleAddEventSubmit(event)" class="p-4 grid grid-cols-2 gap-4">
                <!-- Event Name -->
                <div class="col-span-2">
                    <label for="event-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Event Name*
                    </label>
                    <input type="text" id="event-name" name="event-name" required
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                           placeholder="Enter event name">
                </div>

                <!-- Description -->
                <div class="col-span-2">
                    <label for="event-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea id="event-description-input" name="event-description" rows="2"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                              placeholder="Enter event description"></textarea>
                </div>

                <!-- All Day Checkbox -->
                <div class="flex items-center">
                    <input type="checkbox" id="event-all-day" name="event-all-day" onchange="toggleAllDay()"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="event-all-day" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        All day
                    </label>
                </div>

                <!-- Start Date/Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Starts*
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="date" id="event-date-start" name="event-date-start" required
                               class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white">
                        <input type="time" id="event-time-start" name="event-time-start" required
                               class="time-input border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white">
                    </div>
                </div>

                <!-- End Date/Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Ends*
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="date" id="event-date-end" name="event-date-end" required
                               class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white">
                        <input type="time" id="event-time-end" name="event-time-end" required
                               class="time-input border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#222831] text-gray-900 dark:text-white">
                    </div>
                </div>

                <!-- Color Selection -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Color*
                    </label>
                    <div class="flex items-center space-x-3">
                        <label class="flex items-center">
                            <input type="radio" name="event-color" value="blue" checked
                                   class="sr-only">
                            <div class="w-6 h-6 bg-blue-500 rounded-full cursor-pointer border-2 border-transparent hover:border-gray-300"></div>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="event-color" value="orange"
                                   class="sr-only">
                            <div class="w-6 h-6 bg-orange-500 rounded-full cursor-pointer border-2 border-transparent hover:border-gray-300"></div>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="event-color" value="teal"
                                   class="sr-only">
                            <div class="w-6 h-6 bg-teal-500 rounded-full cursor-pointer border-2 border-transparent hover:border-gray-300"></div>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="event-color" value="brown"
                                   class="sr-only">
                            <div class="w-6 h-6 bg-amber-700 rounded-full cursor-pointer border-2 border-transparent hover:border-gray-300"></div>
                        </label>
                        <button type="button" onclick="showColorPicker()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="col-span-2 flex justify-center pt-2">
                    <button type="submit" id="add-event-submit-btn" onclick="handleSubmitButtonClick(event)"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Color Picker Popup -->
    <div id="color-picker-popup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] hidden">
        <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow-xl max-w-sm w-full mx-4">
            <!-- Color Picker Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Choose Color</h3>
                <button onclick="closeColorPicker()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Color Grid -->
            <div class="p-4">
                <div class="grid grid-cols-8 gap-3">
                    <!-- Red shades -->
                    <div class="w-8 h-8 bg-red-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('red-500', '#ef4444')"></div>
                    <div class="w-8 h-8 bg-red-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('red-600', '#dc2626')"></div>
                    <div class="w-8 h-8 bg-red-700 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('red-700', '#b91c1c')"></div>
                    <div class="w-8 h-8 bg-red-800 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('red-800', '#991b1b')"></div>
                    
                    <!-- Pink shades -->
                    <div class="w-8 h-8 bg-pink-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('pink-500', '#ec4899')"></div>
                    <div class="w-8 h-8 bg-pink-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('pink-600', '#db2777')"></div>
                    <div class="w-8 h-8 bg-pink-700 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('pink-700', '#be185d')"></div>
                    <div class="w-8 h-8 bg-pink-800 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('pink-800', '#9d174d')"></div>
                    
                    <!-- Purple shades -->
                    <div class="w-8 h-8 bg-purple-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('purple-500', '#a855f7')"></div>
                    <div class="w-8 h-8 bg-purple-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('purple-600', '#9333ea')"></div>
                    <div class="w-8 h-8 bg-purple-700 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('purple-700', '#7c3aed')"></div>
                    <div class="w-8 h-8 bg-purple-800 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('purple-800', '#6b21a8')"></div>
                    
                    <!-- Blue shades -->
                    <div class="w-8 h-8 bg-blue-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('blue-500', '#3b82f6')"></div>
                    <div class="w-8 h-8 bg-blue-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('blue-600', '#2563eb')"></div>
                    <div class="w-8 h-8 bg-blue-700 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('blue-700', '#1d4ed8')"></div>
                    <div class="w-8 h-8 bg-blue-800 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('blue-800', '#1e40af')"></div>
                    
                    <!-- Cyan/Teal shades -->
                    <div class="w-8 h-8 bg-cyan-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('cyan-500', '#06b6d4')"></div>
                    <div class="w-8 h-8 bg-cyan-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('cyan-600', '#0891b2')"></div>
                    <div class="w-8 h-8 bg-teal-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('teal-500', '#14b8a6')"></div>
                    <div class="w-8 h-8 bg-teal-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('teal-600', '#0d9488')"></div>
                    
                    <!-- Green shades -->
                    <div class="w-8 h-8 bg-green-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('green-500', '#22c55e')"></div>
                    <div class="w-8 h-8 bg-green-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('green-600', '#16a34a')"></div>
                    <div class="w-8 h-8 bg-green-700 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('green-700', '#15803d')"></div>
                    <div class="w-8 h-8 bg-green-800 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('green-800', '#166534')"></div>
                    
                    <!-- Yellow/Amber shades -->
                    <div class="w-8 h-8 bg-yellow-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('yellow-500', '#eab308')"></div>
                    <div class="w-8 h-8 bg-yellow-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('yellow-600', '#ca8a04')"></div>
                    <div class="w-8 h-8 bg-amber-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('amber-500', '#f59e0b')"></div>
                    <div class="w-8 h-8 bg-amber-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('amber-600', '#d97706')"></div>
                    
                    <!-- Orange shades -->
                    <div class="w-8 h-8 bg-orange-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('orange-500', '#f97316')"></div>
                    <div class="w-8 h-8 bg-orange-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('orange-600', '#ea580c')"></div>
                    <div class="w-8 h-8 bg-orange-700 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('orange-700', '#c2410c')"></div>
                    <div class="w-8 h-8 bg-orange-800 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('orange-800', '#9a3412')"></div>
                    
                    <!-- Brown/Gray shades -->
                    <div class="w-8 h-8 bg-amber-700 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('amber-700', '#b45309')"></div>
                    <div class="w-8 h-8 bg-amber-800 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('amber-800', '#92400e')"></div>
                    <div class="w-8 h-8 bg-gray-500 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('gray-500', '#6b7280')"></div>
                    <div class="w-8 h-8 bg-gray-600 rounded-full cursor-pointer hover:scale-110 transition-transform" onclick="selectCustomColor('gray-600', '#4b5563')"></div>
                </div>
            </div>

            <!-- Color Picker Footer -->
            <div class="flex justify-end p-4 border-t border-gray-200 dark:border-gray-600">
                <button onclick="closeColorPicker()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Dialog for Event Details -->
    <div id="custom-confirm-dialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <!-- Dialog Header -->
            <div class="flex items-center p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Move to Trash</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                </div>
            </div>
            
            <!-- Dialog Content -->
            <div class="p-6">
                <p id="custom-confirm-message" class="text-gray-700 dark:text-gray-300"></p>
            </div>
            
            <!-- Dialog Footer -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
                <button id="custom-confirm-cancel-btn" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
                <button id="custom-confirm-btn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                    Move to Trash
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Action Confirmation Dialog -->
    <div id="bulk-confirm-dialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <!-- Dialog Header -->
            <div class="flex items-center p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <h3 id="bulk-confirm-title" class="text-lg font-semibold text-gray-900 dark:text-white">Confirm Action</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                </div>
            </div>
            
            <!-- Dialog Content -->
            <div class="p-6">
                <p id="bulk-confirm-message" class="text-gray-700 dark:text-gray-300"></p>
            </div>
            
            <!-- Dialog Footer -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
                <button id="bulk-confirm-cancel-btn" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
                <button id="bulk-confirm-btn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div id="event-details-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[70] hidden">
        <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Event Details</h3>
                <button onclick="closeEventDetails()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6">
                <!-- Event Title -->
                <div class="mb-4">
                    <div id="event-title" class="text-lg font-semibold text-gray-900 dark:text-white"></div>
                </div>

                <!-- Event Description -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <div id="event-description" class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg min-h-[60px]"></div>
                </div>

                <!-- Event Date -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date</label>
                    <div id="event-date" class="text-gray-900 dark:text-white"></div>
                </div>

                <!-- Event Time -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time</label>
                    <div id="event-time" class="text-gray-900 dark:text-white"></div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-600">
                <button onclick="editEvent()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium">
                    Edit Event
                </button>
                <button onclick="deleteEventFromDetails()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors font-medium">
                    Move to Trash
                </button>
            </div>
        </div>
    </div>
    </div>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

</body>
</html>