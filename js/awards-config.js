/**
 * Awards Configuration
 * Centralized configuration for awards page
 */
const AwardsConfig = {
    // API endpoints
    api: {
        awards: 'api/awards.php',
        stats: 'api/awards.php',
        upload: 'api/awards.php'
    },
    
    // Award categories
    categories: [
        { value: 'academic', label: 'Academic Excellence', color: '#DC2626' },
        { value: 'research', label: 'Research & Innovation', color: '#3B82F6' },
        { value: 'leadership', label: 'Leadership & Service', color: '#F9A8D4' }
    ],
    
    // Award types
    awardTypes: [
        'Scholarship',
        'Grant',
        'Fellowship',
        'Prize',
        'Recognition',
        'Certificate',
        'Medal',
        'Trophy',
        'Other'
    ],
    
    // Status options
    statusOptions: [
        { value: 'active', label: 'Active', color: 'text-green-600' },
        { value: 'pending', label: 'Pending', color: 'text-yellow-600' },
        { value: 'expired', label: 'Expired', color: 'text-red-600' },
        { value: 'cancelled', label: 'Cancelled', color: 'text-gray-600' }
    ],
    
    // Chart settings
    charts: {
        donut: {
            colors: ['#DC2626', '#3B82F6', '#F9A8D4'], // Red, Blue, Pink
            labels: ['Operation', 'Utilities', 'Transportation'],
            responsive: true,
            maintainAspectRatio: false
        },
        line: {
            colors: {
                thisYear: '#3B82F6',
                lastYear: '#6B7280'
            },
            responsive: true,
            maintainAspectRatio: false
        }
    },
    
    // Time periods for filtering
    timePeriods: [
        { value: 'This Month', label: 'This Month' },
        { value: 'Last Month', label: 'Last Month' },
        { value: 'This Quarter', label: 'This Quarter' },
        { value: 'Last Quarter', label: 'Last Quarter' },
        { value: 'This Year', label: 'This Year' },
        { value: 'Last Year', label: 'Last Year' }
    ],
    
    // File upload settings
    upload: {
        maxFileSize: 15 * 1024 * 1024, // 15MB
        allowedTypes: [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ],
        maxFiles: 5
    },
    
    // Validation rules
    validation: {
        required: {
            title: 'Award title is required',
            category: 'Award category is required',
            recipient: 'Recipient name is required',
            date: 'Award date is required'
        },
        patterns: {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            phone: /^[\+]?[1-9][\d]{0,15}$/,
            amount: /^\d+(\.\d{1,2})?$/
        }
    },
    
    // UI settings
    ui: {
        autoRefresh: true,
        refreshInterval: 60000, // 1 minute
        showNotifications: true,
        animationDuration: 300,
        itemsPerPage: 20
    },
    
    // Date formats
    formats: {
        date: 'YYYY-MM-DD',
        displayDate: 'MMM DD, YYYY',
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
    module.exports = AwardsConfig;
}
