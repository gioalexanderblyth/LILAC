/**
 * Events Configuration
 * Centralized configuration for events page
 */
const EventsConfig = {
    // API endpoints
    api: {
        events: 'api/central_events_api.php',
        upload: 'create_event.php'
    },
    
    // Event status options
    statusOptions: [
        { value: 'upcoming', label: 'Upcoming', color: 'text-blue-600' },
        { value: 'completed', label: 'Completed', color: 'text-green-600' },
        { value: 'cancelled', label: 'Cancelled', color: 'text-red-600' }
    ],
    
    // Event categories
    categories: [
        'Meeting',
        'Conference',
        'Workshop',
        'Seminar',
        'Training',
        'Social Event',
        'Academic',
        'Administrative',
        'Other'
    ],
    
    // File upload settings
    upload: {
        maxFileSize: 10 * 1024 * 1024, // 10MB
        allowedTypes: [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        maxFiles: 1
    },
    
    // Calendar settings
    calendar: {
        defaultView: 'month',
        firstDayOfWeek: 1, // Monday
        timeFormat: '12h', // 12h or 24h
        dateFormat: 'MM/DD/YYYY'
    },
    
    // UI settings
    ui: {
        autoRefresh: true,
        refreshInterval: 30000, // 30 seconds
        showNotifications: true,
        animationDuration: 300,
        itemsPerPage: 10
    },
    
    // Date/time formats
    formats: {
        date: 'YYYY-MM-DD',
        time: 'HH:mm',
        datetime: 'YYYY-MM-DD HH:mm',
        displayDate: 'MMM DD, YYYY',
        displayTime: 'h:mm A',
        displayDateTime: 'MMM DD, YYYY h:mm A'
    },
    
    // Notification settings
    notifications: {
        success: {
            icon: '✅',
            color: 'text-green-600',
            bgColor: 'bg-green-50'
        },
        error: {
            icon: '❌',
            color: 'text-red-600',
            bgColor: 'bg-red-50'
        },
        warning: {
            icon: '⚠️',
            color: 'text-yellow-600',
            bgColor: 'bg-yellow-50'
        },
        info: {
            icon: 'ℹ️',
            color: 'text-blue-600',
            bgColor: 'bg-blue-50'
        }
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EventsConfig;
}
