/**
 * Modal Handlers JavaScript
 * Centralized event handling for modals across all pages
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all modal event listeners
    initializeModalHandlers();
});

function initializeModalHandlers() {
    // Document viewer close buttons
    const documentViewerCloseButtons = document.querySelectorAll('[data-modal-close="document-viewer"], [data-modal-close="document-viewer-overlay"]');
    documentViewerCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const overlay = document.getElementById('document-viewer-overlay');
            if (overlay) {
                overlay.classList.add('hidden');
            }
        });
    });

    // Event creation modal close buttons
    const eventModalCloseButtons = document.querySelectorAll('[data-modal-close="event-creation"]');
    eventModalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('event-creation-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    });

    // Upload modal close buttons
    const uploadModalCloseButtons = document.querySelectorAll('[data-modal-close="upload"]');
    uploadModalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('upload-modal');
            if (modal) {
                modal.remove();
            }
        });
    });

    // Generic modal close buttons
    const genericCloseButtons = document.querySelectorAll('[data-modal-close]');
    genericCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = button.getAttribute('data-modal-close');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    });

    // File input triggers
    const fileInputTriggers = document.querySelectorAll('[data-file-input]');
    fileInputTriggers.forEach(button => {
        button.addEventListener('click', function() {
            const inputId = button.getAttribute('data-file-input');
            const input = document.getElementById(inputId);
            if (input) {
                input.click();
            }
        });
    });

    // Focus triggers
    const focusTriggers = document.querySelectorAll('[data-focus]');
    focusTriggers.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = button.getAttribute('data-focus');
            const target = document.getElementById(targetId);
            if (target) {
                target.focus();
            }
        });
    });
}

// Utility function to close any modal by ID
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Utility function to show any modal by ID
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
    }
}

// Make functions globally available
window.closeModal = closeModal;
window.showModal = showModal;
