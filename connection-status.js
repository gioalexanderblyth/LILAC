/**
 * LILAC Connection Status Manager
 * Shared script for online/offline indicator across all pages
 */

// Initialize connection status when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('LILAC Connection Status: DOM loaded, initializing...');
    initializeConnectionStatus();
});

// Connection Status Management
function initializeConnectionStatus() {
    console.log('LILAC Connection Status: Initializing connection status');
    const onlineIndicator = document.getElementById('online-indicator');
    const offlineIndicator = document.getElementById('offline-indicator');
    
    console.log('LILAC Connection Status: Online indicator found:', !!onlineIndicator);
    console.log('LILAC Connection Status: Offline indicator found:', !!offlineIndicator);
    
    updateConnectionStatus();
    
    // Listen for online/offline events
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
}

function updateConnectionStatus() {
    const isOnline = navigator.onLine;
    const onlineIndicator = document.getElementById('online-indicator');
    const offlineIndicator = document.getElementById('offline-indicator');
    
    console.log('LILAC Connection Status: Updating status. Online:', isOnline);
    console.log('LILAC Connection Status: Elements found - Online:', !!onlineIndicator, 'Offline:', !!offlineIndicator);
    
    if (onlineIndicator && offlineIndicator) {
        if (isOnline) {
            onlineIndicator.style.display = 'flex';
            offlineIndicator.style.display = 'none';
            console.log('LILAC Connection Status: Set to online mode');
        } else {
            onlineIndicator.style.display = 'none';
            offlineIndicator.style.display = 'flex';
            console.log('LILAC Connection Status: Set to offline mode');
        }
    } else {
        console.log('LILAC Connection Status: ERROR - Could not find indicator elements!');
    }
}

function handleOnline() {
    console.log('LILAC: Connection restored');
    updateConnectionStatus();
    showConnectionNotification('Connection restored! 🟢', 'success');
}

function handleOffline() {
    console.log('LILAC: Connection lost');
    updateConnectionStatus();
    showConnectionNotification('You are now offline 🟠', 'info');
}

function showConnectionNotification(message, type = 'info') {
    // Check if there's already a showNotification function on the page
    if (typeof showNotification === 'function') {
        showNotification(message, type);
        return;
    }
    
    // Fallback notification if showNotification doesn't exist
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-orange-500'
    };

    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Slide in
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    
    // Slide out and remove
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Get connection status info
function getConnectionStatus() {
    return {
        isOnline: navigator.onLine,
        timestamp: new Date().toISOString()
    };
}

console.log('LILAC Connection Status Manager loaded'); 