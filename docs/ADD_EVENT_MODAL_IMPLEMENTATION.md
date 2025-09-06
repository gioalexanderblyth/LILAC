# Add Event Modal Implementation for LILAC Scheduler

## Overview
The Add Event modal has been implemented to provide a comprehensive interface for creating new events/meetings with advanced features including all-day events, color coding, and flexible time ranges.

## Features

### 1. Event Information
- **Event Name***: Required field for the event title
- **Description**: Optional detailed description of the event
- **All Day**: Checkbox to mark events as all-day events

### 2. Date and Time Management
- **Start Date/Time***: Required start date and time
- **End Date/Time***: Required end date and time
- **All Day Toggle**: Automatically disables time inputs when checked
- **Validation**: Ensures end time is after start time

### 3. Color Coding
- **Color Selection**: Choose from 4 predefined colors (blue, orange, teal, brown)
- **Visual Indicators**: Color circles with hover effects
- **Default Selection**: Blue is selected by default

### 4. User Experience
- **Modal Interface**: Clean, modern modal design
- **Form Validation**: Client-side and server-side validation
- **Responsive Design**: Works on desktop and mobile devices
- **Dark Mode Support**: Compatible with dark theme

## Database Changes

### New Fields Added to `meetings` Table:
```sql
-- End date and time for events
ALTER TABLE `meetings` ADD COLUMN `end_date` date NULL AFTER `meeting_time`;
ALTER TABLE `meetings` ADD COLUMN `end_time` time NULL AFTER `end_date`;

-- All-day event support
ALTER TABLE `meetings` ADD COLUMN `is_all_day` tinyint(1) NOT NULL DEFAULT 0 AFTER `end_time`;

-- Color coding support
ALTER TABLE `meetings` ADD COLUMN `color` varchar(20) NOT NULL DEFAULT 'blue' AFTER `is_all_day`;

-- Performance indexes
ALTER TABLE `meetings` ADD INDEX `idx_end_date` (`end_date`);
ALTER TABLE `meetings` ADD INDEX `idx_is_all_day` (`is_all_day`);
ALTER TABLE `meetings` ADD INDEX `idx_color` (`color`);
```

## API Updates

### Modified Endpoint: `POST api/scheduler.php?action=add`

**New Parameters:**
- `end_date`: End date of the event
- `end_time`: End time of the event
- `is_all_day`: Whether the event is all-day (0 or 1)
- `color`: Event color (blue, orange, teal, brown)

**Example Request:**
```javascript
const formData = new FormData();
formData.append('action', 'add');
formData.append('title', 'Team Meeting');
formData.append('date', '2025-02-15');
formData.append('time', '09:00');
formData.append('end_date', '2025-02-15');
formData.append('end_time', '10:00');
formData.append('description', 'Weekly team sync');
formData.append('is_all_day', '0');
formData.append('color', 'blue');
```

## User Interface

### Modal Structure:
```html
<div id="add-event-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow-xl max-w-md w-full mx-4">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b">
            <h3>Add Event</h3>
            <button onclick="closeAddEventModal()">×</button>
        </div>
        
        <!-- Form -->
        <form id="add-event-form" onsubmit="handleAddEventSubmit(event)">
            <!-- Event Name -->
            <input type="text" id="event-name" required placeholder="Enter event name">
            
            <!-- Description -->
            <textarea id="event-description" rows="3" placeholder="Enter event description"></textarea>
            
            <!-- All Day Checkbox -->
            <input type="checkbox" id="event-all-day" onchange="toggleAllDay()">
            
            <!-- Start Date/Time -->
            <div class="grid grid-cols-2 gap-3">
                <input type="date" id="event-date-start" required>
                <input type="time" id="event-time-start" required class="time-input">
            </div>
            
            <!-- End Date/Time -->
            <div class="grid grid-cols-2 gap-3">
                <input type="date" id="event-date-end" required>
                <input type="time" id="event-time-end" required class="time-input">
            </div>
            
            <!-- Color Selection -->
            <div class="flex items-center space-x-3">
                <label><input type="radio" name="event-color" value="blue" checked><div class="w-6 h-6 bg-blue-500 rounded-full"></div></label>
                <label><input type="radio" name="event-color" value="orange"><div class="w-6 h-6 bg-orange-500 rounded-full"></div></label>
                <label><input type="radio" name="event-color" value="teal"><div class="w-6 h-6 bg-teal-500 rounded-full"></div></label>
                <label><input type="radio" name="event-color" value="brown"><div class="w-6 h-6 bg-amber-700 rounded-full"></div></label>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg">Save</button>
        </form>
    </div>
</div>
```

## JavaScript Functions

### Core Functions:
1. **`showAddEventModal()`**: Opens the modal and sets default values
2. **`closeAddEventModal()`**: Closes the modal and resets the form
3. **`handleAddEventSubmit(e)`**: Handles form submission and validation
4. **`toggleAllDay()`**: Toggles time input fields based on all-day checkbox
5. **`updateColorSelection()`**: Updates visual selection of color circles

### Form Validation:
- Event name is required
- Start date is required
- Time is required for non-all-day events
- End time must be after start time
- Color selection is optional (defaults to blue)

## Installation

### 1. Run Database Migration
```bash
php apply_event_fields_migration.php
```

### 2. Verify Installation
- Check that new columns were added to the `meetings` table
- Test the Add Event modal functionality
- Verify color selection works correctly

## Usage

### For Users:
1. **Open Modal**: Click the "Add Event" button in the sidebar
2. **Fill Form**: Enter event details including name, description, dates, and times
3. **Set All Day**: Check "All day" for events that span the entire day
4. **Choose Color**: Select a color to categorize the event
5. **Save Event**: Click "Save" to create the event

### For Developers:
- The modal uses Tailwind CSS for styling
- Form validation is handled both client-side and server-side
- Color selection uses radio buttons with custom styling
- All form data is sent via FormData to the API

## Color System

### Available Colors:
- **Blue** (`blue`): Default color for general events
- **Orange** (`orange`): For important or urgent events
- **Teal** (`teal`): For meetings or collaborative events
- **Brown** (`brown`): For personal or private events

### Color Usage:
- Colors are stored in the database as string values
- Can be used for filtering and categorization
- Visual distinction in calendar views
- Future enhancement: Custom color picker

## Error Handling

### Client-Side Validation:
- Required field validation
- Date/time validation
- Form submission prevention on errors

### Server-Side Validation:
- Database constraint validation
- Business logic validation
- Error response handling

### User Feedback:
- Success notifications
- Error messages
- Form reset on successful submission

## Future Enhancements

1. **Custom Colors**: Allow users to pick custom colors
2. **Recurring Events**: Support for recurring event patterns
3. **Event Categories**: Predefined event categories
4. **File Attachments**: Attach files to events
5. **Event Templates**: Save and reuse event templates
6. **Advanced Scheduling**: Conflict detection and suggestions

## Troubleshooting

### Common Issues:

1. **Modal Not Opening**: Check JavaScript console for errors
2. **Form Not Submitting**: Verify all required fields are filled
3. **Color Not Saving**: Check database migration was successful
4. **Time Validation Errors**: Ensure end time is after start time

### Debug Mode:
- Check browser console for JavaScript errors
- Verify API responses in Network tab
- Check database for new columns

## Browser Compatibility

- **Modern Browsers**: Full support (Chrome, Firefox, Safari, Edge)
- **Mobile Browsers**: Responsive design support
- **Older Browsers**: May have limited CSS Grid support

## Performance Considerations

- Modal is loaded with the page (no AJAX loading)
- Form validation is lightweight
- Color selection uses efficient event delegation
- Database queries are optimized with indexes 