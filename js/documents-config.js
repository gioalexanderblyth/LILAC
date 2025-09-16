/**
 * Documents Configuration
 * Centralized configuration for documents page
 */
const DocumentsConfig = {
    // Pagination settings
    pagination: {
        defaultLimit: 10,
        limits: [5, 10, 20, 50, 100],
        maxLimit: 100
    },
    
    // File upload settings
    upload: {
        maxFileSize: 50 * 1024 * 1024, // 50MB
        allowedTypes: [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ],
        maxFiles: 10
    },
    
    // API endpoints
    api: {
        upload: 'api/documents.php',
        list: 'api/documents.php',
        delete: 'api/documents.php',
        search: 'api/documents.php'
    },
    
    // UI settings
    ui: {
        autoRefresh: true,
        refreshInterval: 30000, // 30 seconds
        showNotifications: true,
        animationDuration: 300
    },
    
    // Categories
    categories: [
        'MOUs & MOAs',
        'Registrar Files',
        'Templates',
        'Awards',
        'Events',
        'Other'
    ],
    
    // File type icons and colors
    fileTypes: {
        pdf: { icon: '📄', color: 'text-red-500' },
        doc: { icon: '📝', color: 'text-blue-500' },
        docx: { icon: '📝', color: 'text-blue-500' },
        xls: { icon: '📊', color: 'text-green-500' },
        xlsx: { icon: '📊', color: 'text-green-500' },
        jpg: { icon: '🖼️', color: 'text-purple-500' },
        jpeg: { icon: '🖼️', color: 'text-purple-500' },
        png: { icon: '🖼️', color: 'text-purple-500' },
        gif: { icon: '🖼️', color: 'text-purple-500' },
        txt: { icon: '📄', color: 'text-gray-500' },
        default: { icon: '📁', color: 'text-gray-500' }
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DocumentsConfig;
}
