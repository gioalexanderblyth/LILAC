/**
 * Global Error Handler
 * Centralized error handling and user notifications
 */

class ErrorHandler {
    constructor() {
        this.notifications = [];
        this.maxNotifications = 5;
        this.notificationDuration = 5000;
        
        this.initializeGlobalErrorHandling();
        this.createNotificationContainer();
    }
    
    /**
     * Initialize global error handling
     */
    initializeGlobalErrorHandling() {
        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
            this.showError('An unexpected error occurred. Please try again.');
            event.preventDefault();
        });
        
        // Handle global JavaScript errors
        window.addEventListener('error', (event) => {
            console.error('Global error:', event.error);
            this.showError('A system error occurred. Please refresh the page.');
        });
        
        // Handle fetch errors globally
        this.interceptFetch();
    }
    
    /**
     * Intercept fetch requests for error handling
     */
    interceptFetch() {
        const originalFetch = window.fetch;
        
        window.fetch = async (...args) => {
            try {
                const response = await originalFetch(...args);
                
                // Check for HTTP errors
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return response;
            } catch (error) {
                console.error('Fetch error:', error);
                this.showError('Network error. Please check your connection.');
                throw error;
            }
        };
    }
    
    /**
     * Create notification container
     */
    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
    
    /**
     * Show success notification
     */
    showSuccess(message, duration = this.notificationDuration) {
        this.showNotification(message, 'success', duration);
    }
    
    /**
     * Show error notification
     */
    showError(message, duration = this.notificationDuration) {
        this.showNotification(message, 'error', duration);
    }
    
    /**
     * Show warning notification
     */
    showWarning(message, duration = this.notificationDuration) {
        this.showNotification(message, 'warning', duration);
    }
    
    /**
     * Show info notification
     */
    showInfo(message, duration = this.notificationDuration) {
        this.showNotification(message, 'info', duration);
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = this.notificationDuration) {
        const notification = this.createNotificationElement(message, type);
        const container = document.getElementById('notification-container');
        
        if (!container) return;
        
        // Remove oldest notification if at max capacity
        if (this.notifications.length >= this.maxNotifications) {
            const oldest = this.notifications.shift();
            if (oldest && oldest.parentNode) {
                oldest.parentNode.removeChild(oldest);
            }
        }
        
        // Add new notification
        container.appendChild(notification);
        this.notifications.push(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.add('animate-in');
        }, 10);
        
        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }
    }
    
    /**
     * Create notification element
     */
    createNotificationElement(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} transform translate-x-full transition-all duration-300 ease-in-out`;
        
        const config = this.getNotificationConfig(type);
        
        notification.innerHTML = `
            <div class="flex items-center p-4 rounded-lg shadow-lg max-w-sm ${config.bgColor} ${config.textColor}">
                <span class="mr-3 text-xl">${config.icon}</span>
                <div class="flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <button class="ml-3 text-gray-400 hover:text-gray-600" onclick="errorHandler.removeNotification(this.parentElement.parentElement)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        return notification;
    }
    
    /**
     * Get notification configuration
     */
    getNotificationConfig(type) {
        const configs = {
            success: {
                icon: '✅',
                bgColor: 'bg-green-50 border border-green-200',
                textColor: 'text-green-800'
            },
            error: {
                icon: '❌',
                bgColor: 'bg-red-50 border border-red-200',
                textColor: 'text-red-800'
            },
            warning: {
                icon: '⚠️',
                bgColor: 'bg-yellow-50 border border-yellow-200',
                textColor: 'text-yellow-800'
            },
            info: {
                icon: 'ℹ️',
                bgColor: 'bg-blue-50 border border-blue-200',
                textColor: 'text-blue-800'
            }
        };
        
        return configs[type] || configs.info;
    }
    
    /**
     * Remove notification
     */
    removeNotification(notification) {
        if (!notification || !notification.parentNode) return;
        
        // Animate out
        notification.classList.add('animate-out');
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
            
            // Remove from notifications array
            const index = this.notifications.indexOf(notification);
            if (index > -1) {
                this.notifications.splice(index, 1);
            }
        }, 300);
    }
    
    /**
     * Clear all notifications
     */
    clearAllNotifications() {
        this.notifications.forEach(notification => {
            this.removeNotification(notification);
        });
    }
    
    /**
     * Handle API errors
     */
    handleApiError(error, context = '') {
        console.error(`API Error${context ? ` in ${context}` : ''}:`, error);
        
        let message = 'An error occurred while processing your request.';
        
        if (error.message) {
            if (error.message.includes('Network')) {
                message = 'Network error. Please check your internet connection.';
            } else if (error.message.includes('404')) {
                message = 'The requested resource was not found.';
            } else if (error.message.includes('500')) {
                message = 'Server error. Please try again later.';
            } else if (error.message.includes('403')) {
                message = 'Access denied. You do not have permission to perform this action.';
            } else {
                message = error.message;
            }
        }
        
        this.showError(message);
    }
    
    /**
     * Handle validation errors
     */
    handleValidationError(errors) {
        if (Array.isArray(errors)) {
            this.showError(errors.join(', '));
        } else if (typeof errors === 'string') {
            this.showError(errors);
        } else {
            this.showError('Please check your input and try again.');
        }
    }
    
    /**
     * Handle file upload errors
     */
    handleFileUploadError(error) {
        console.error('File upload error:', error);
        
        let message = 'File upload failed.';
        
        if (error.message) {
            if (error.message.includes('too large')) {
                message = 'File is too large. Please choose a smaller file.';
            } else if (error.message.includes('type')) {
                message = 'Invalid file type. Please choose a supported file format.';
            } else {
                message = error.message;
            }
        }
        
        this.showError(message);
    }
}

// Initialize global error handler
document.addEventListener('DOMContentLoaded', function() {
    window.errorHandler = new ErrorHandler();
    
    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            transform: translateX(100%);
        }
        
        .notification.animate-in {
            transform: translateX(0);
        }
        
        .notification.animate-out {
            transform: translateX(100%);
            opacity: 0;
        }
        
        .notification:hover {
            transform: translateX(0) scale(1.02);
        }
    `;
    document.head.appendChild(style);
});

// Global error handling functions for backward compatibility
window.showNotification = function(message, type = 'info') {
    if (window.errorHandler) {
        window.errorHandler.showNotification(message, type);
    }
};

window.showError = function(message) {
    if (window.errorHandler) {
        window.errorHandler.showError(message);
    }
};

window.showSuccess = function(message) {
    if (window.errorHandler) {
        window.errorHandler.showSuccess(message);
    }
};

window.showWarning = function(message) {
    if (window.errorHandler) {
        window.errorHandler.showWarning(message);
    }
};
