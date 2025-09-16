/**
 * MOU-MOA Configuration
 * Centralized configuration for MOU-MOA page
 */
const MouMoaConfig = {
    // API endpoints
    api: {
        upload: 'api/mous.php',
        list: 'api/mous.php',
        delete: 'api/mous.php',
        search: 'api/mous.php'
    },
    
    // Document types
    documentTypes: [
        { value: 'MOU', label: 'Memorandum of Understanding', color: 'text-blue-600' },
        { value: 'MOA', label: 'Memorandum of Agreement', color: 'text-green-600' },
        { value: 'KUMA-MOU', label: 'KUMA-MOU', color: 'text-purple-600' }
    ],
    
    // Partner types
    partnerTypes: [
        'Government Agency',
        'Educational Institution',
        'Private Company',
        'NGO',
        'International Organization',
        'Other'
    ],
    
    // Status options
    statusOptions: [
        { value: 'active', label: 'Active', color: 'text-green-600' },
        { value: 'expired', label: 'Expired', color: 'text-red-600' },
        { value: 'expiring', label: 'Expiring Soon', color: 'text-yellow-600' },
        { value: 'terminated', label: 'Terminated', color: 'text-gray-600' }
    ],
    
    // File upload settings
    upload: {
        maxFileSize: 20 * 1024 * 1024, // 20MB
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
            partnerName: 'Partner name is required',
            documentType: 'Document type is required',
            startDate: 'Start date is required',
            endDate: 'End date is required'
        },
        patterns: {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            phone: /^[\+]?[1-9][\d]{0,15}$/,
            url: /^https?:\/\/.+\..+/
        }
    },
    
    // Expiration settings
    expiration: {
        warningDays: 30, // Days before expiration to show warning
        criticalDays: 7  // Days before expiration to show critical warning
    },
    
    // UI settings
    ui: {
        autoRefresh: true,
        refreshInterval: 60000, // 1 minute
        showNotifications: true,
        animationDuration: 300,
        itemsPerPage: 15
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
    module.exports = MouMoaConfig;
}
