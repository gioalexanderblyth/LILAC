# LILAC System - Online/Offline Implementation Guide

## Overview
Transform your LILAC system into a Progressive Web App (PWA) that works seamlessly both online and offline.

## Key Components

### 1. Service Worker (sw.js) ✅ Already created
- Caches static resources and API responses
- Handles offline requests
- Provides background synchronization

### 2. Offline Manager (offline-manager.js) ✅ Already created  
- Monitors online/offline status
- Manages data synchronization
- Provides user feedback

### 3. PWA Manifest (manifest.json) ✅ Already created
- Enables app installation
- Configures app appearance and behavior

## Implementation Steps

### Step 1: Add PWA Support to HTML Pages
Add to `<head>` section of all pages:

```html
<link rel="manifest" href="/LILAC/manifest.json">
<meta name="theme-color" content="#000000">
<link rel="apple-touch-icon" href="/LILAC/icons/icon-192x192.png">
<script src="/LILAC/offline-manager.js"></script>
```

### Step 2: Create Icon Assets
Create `/LILAC/icons/` folder with PWA icons:
- icon-72x72.png through icon-512x512.png

### Step 3: Enhanced API Error Handling
Update your existing JavaScript to handle offline responses:

```javascript
// Example for awards.php API calls
async function submitForm(formData) {
    try {
        const response = await fetch('/LILAC/api/awards.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.offline) {
            showMessage('Data saved offline - will sync when online', 'info');
        } else if (result.success) {
            showMessage('Data saved successfully', 'success');
        }
        
    } catch (error) {
        showMessage('Error saving data', 'error');
    }
}
```

## Features You'll Get

### Offline Capabilities
- ✅ Pages load without internet
- ✅ Forms work offline (saved locally)
- ✅ Data syncs automatically when back online
- ✅ Visual indicators for connection status

### PWA Features
- ✅ Install as native app
- ✅ Works on mobile and desktop
- ✅ Fast loading from cache
- ✅ App-like experience

### User Experience
- ✅ Offline banner notifications
- ✅ Pending changes counter
- ✅ Automatic background sync
- ✅ Clear status messages

## Quick Test
1. Open your LILAC system
2. Disconnect internet
3. Try submitting a form - should work offline
4. Reconnect internet - data should sync automatically

## Benefits
- **Users**: Works without internet, faster loading, app-like experience
- **System**: Reduced server load, better reliability, modern web standards
- **Data**: No data loss, automatic synchronization, conflict resolution

This implementation provides a robust offline-first experience while maintaining all existing functionality! 