/**
 * Text Configuration JavaScript
 * Centralized text content for internationalization and maintainability
 */

const TEXT_CONFIG = {
    // Awards
    awards: {
        totalAwards: 'Total Awards',
        pendingAwards: 'Pending Awards',
        approvedAwards: 'Approved Awards',
        rejectedAwards: 'Rejected Awards',
        addAward: 'Add Award',
        noAwardsMessage: 'Add your first award to get started'
    },
    
    // Events
    events: {
        createEvent: 'Create New Event',
        eventName: 'Event Name',
        description: 'Description',
        location: 'Location',
        startDate: 'Start Date',
        endDate: 'End Date',
        allDay: 'All Day',
        save: 'Save',
        cancel: 'Cancel',
        close: 'Close'
    },
    
    // Documents
    documents: {
        upload: 'Upload',
        download: 'Download',
        view: 'View',
        delete: 'Delete',
        clickToUpload: 'Click to Upload',
        dragAndDrop: 'or drag and drop',
        fileUpload: 'File Upload',
        documentViewer: 'Document Viewer'
    },
    
    // Common
    common: {
        loading: 'Loading...',
        error: 'Error',
        success: 'Success',
        warning: 'Warning',
        info: 'Information',
        confirm: 'Confirm',
        yes: 'Yes',
        no: 'No',
        close: 'Close',
        cancel: 'Cancel',
        save: 'Save',
        edit: 'Edit',
        delete: 'Delete',
        view: 'View',
        search: 'Search',
        filter: 'Filter',
        sort: 'Sort',
        refresh: 'Refresh'
    },
    
    // Messages
    messages: {
        noDataFound: 'No data found',
        loadingData: 'Loading data...',
        operationSuccessful: 'Operation completed successfully',
        operationFailed: 'Operation failed',
        confirmDelete: 'Are you sure you want to delete this item?',
        unsavedChanges: 'You have unsaved changes. Are you sure you want to leave?'
    }
};

// Make configuration globally available
window.TEXT_CONFIG = TEXT_CONFIG;
