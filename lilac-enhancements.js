/**
 * LILAC System Enhancements
 * Unified notification system, form validation, loading states, and accessibility features
 */

// Notification System
class LILACNotifications {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Create notification container - positioned well below static navbar
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.className = 'fixed top-32 right-4 z-[9999] space-y-2 max-w-sm';
        document.body.appendChild(this.container);
    }

    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        const id = 'notification-' + Date.now();
        notification.id = id;
        
        const typeStyles = {
            success: 'bg-green-500 border-green-600',
            error: 'bg-red-500 border-red-600',
            warning: 'bg-yellow-500 border-yellow-600',
            info: 'bg-blue-500 border-blue-600'
        };

        const icons = {
            success: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            error: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            warning: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
            info: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        };

        notification.className = `${typeStyles[type]} border-l-4 text-white p-4 rounded-lg shadow-lg transform translate-x-full transition-all duration-300 ease-in-out`;
        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-3">
                    ${icons[type]}
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <button onclick="window.lilacNotifications.dismiss('${id}')" class="ml-3 flex-shrink-0 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        `;

        this.container.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(id);
            }, duration);
        }

        return id;
    }

    dismiss(id) {
        const notification = document.getElementById(id);
        if (notification) {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }

    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 7000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 6000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }

    confirm(message, onConfirm, onCancel) {
        // Create confirmation modal
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]';
        modal.id = 'confirm-modal-' + Date.now();
        
        modal.innerHTML = `
            <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow-xl max-w-md w-full mx-4" role="dialog" aria-labelledby="confirm-title-${modal.id}" aria-describedby="confirm-message-${modal.id}">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 mr-3">
                            <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 id="confirm-title-${modal.id}" class="text-lg font-medium text-gray-900 dark:text-white">Confirm Action</h3>
                    </div>
                    <p id="confirm-message-${modal.id}" class="text-sm text-gray-600 dark:text-gray-300 mb-6">${message}</p>
                    <div class="flex justify-end space-x-3">
                        <button id="cancel-btn-${modal.id}" onclick="window.lilacNotifications.cancelConfirm('${modal.id}')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button id="confirm-btn-${modal.id}" onclick="window.lilacNotifications.executeConfirm('${modal.id}')" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Store callbacks
        modal.dataset.onConfirm = onConfirm ? onConfirm.toString() : '';
        modal.dataset.onCancel = onCancel ? onCancel.toString() : '';
        
        document.body.appendChild(modal);
        
        // Store modal reference
        this.currentConfirmModal = modal;
        
        // Add keyboard event listeners
        const handleKeyDown = (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                this.executeConfirm(modal.id);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                this.cancelConfirm(modal.id);
            }
        };
        
        // Add event listener to the modal
        modal.addEventListener('keydown', handleKeyDown);
        
        // Store the event listener reference for cleanup
        modal._keydownHandler = handleKeyDown;
        
        // Focus the confirm button for better accessibility
        setTimeout(() => {
            const confirmBtn = document.getElementById(`confirm-btn-${modal.id}`);
            if (confirmBtn) {
                confirmBtn.focus();
            }
        }, 100);
        
        return modal.id;
    }

    executeConfirm(modalId) {
        const modal = document.getElementById(modalId);
        if (modal && this.currentConfirmModal === modal) {
            const onConfirm = modal.dataset.onConfirm;
            if (onConfirm) {
                try {
                    // Execute the callback function safely
                    const callback = this.getCallbackFunction(onConfirm);
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                } catch (e) {
                    console.error('Error executing confirm callback:', e);
                }
            }
            this.dismissConfirm(modalId);
        }
    }

    cancelConfirm(modalId) {
        const modal = document.getElementById(modalId);
        if (modal && this.currentConfirmModal === modal) {
            const onCancel = modal.dataset.onCancel;
            if (onCancel) {
                try {
                    // Execute the callback function safely
                    const callback = this.getCallbackFunction(onCancel);
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                } catch (e) {
                    console.error('Error executing cancel callback:', e);
                }
            }
            this.dismissConfirm(modalId);
        }
    }

    getCallbackFunction(callbackString) {
        // Safely parse callback functions without using eval
        try {
            // Check if it's a function reference (e.g., "functionName")
            if (typeof window[callbackString] === 'function') {
                return window[callbackString];
            }
            
            // Check if it's a method call (e.g., "object.method")
            const parts = callbackString.split('.');
            if (parts.length === 2) {
                const obj = window[parts[0]];
                if (obj && typeof obj[parts[1]] === 'function') {
                    return obj[parts[1]].bind(obj);
                }
            }
            
            // For security, we don't support arbitrary code execution
            console.warn('Unsafe callback function detected:', callbackString);
            return null;
        } catch (e) {
            console.error('Error parsing callback function:', e);
            return null;
        }
    }

    dismissConfirm(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Remove event listeners before removing the modal
            if (modal._keydownHandler) {
                modal.removeEventListener('keydown', modal._keydownHandler);
            }
            
            modal.remove();
            if (this.currentConfirmModal === modal) {
                this.currentConfirmModal = null;
            }
        }
    }
}

// Form Validation System
class LILACFormValidator {
    constructor() {
        this.rules = {
            required: (value) => value.trim() !== '',
            email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
            minLength: (value, min) => value.length >= min,
            maxLength: (value, max) => value.length <= max,
            numeric: (value) => !isNaN(value) && value !== '',
            positiveNumber: (value) => !isNaN(value) && parseFloat(value) > 0,
            date: (value) => !isNaN(Date.parse(value)),
            futureDate: (value) => new Date(value) > new Date(),
            pastDate: (value) => new Date(value) <= new Date()
        };
    }

    validateField(field, rules) {
        const value = field.value.trim();
        const errors = [];

        for (const rule of rules) {
            const [ruleName, ...params] = rule.split(':');
            if (this.rules[ruleName] && !this.rules[ruleName](value, ...params)) {
                errors.push(this.getErrorMessage(ruleName, params));
            }
        }

        this.showFieldValidation(field, errors);
        return errors.length === 0;
    }

    getErrorMessage(rule, params) {
        const messages = {
            required: 'This field is required',
            email: 'Please enter a valid email address',
            minLength: `Minimum length is ${params[0]} characters`,
            maxLength: `Maximum length is ${params[0]} characters`,
            numeric: 'Please enter a valid number',
            positiveNumber: 'Please enter a positive number',
            date: 'Please enter a valid date',
            futureDate: 'Date must be in the future',
            pastDate: 'Date cannot be in the future'
        };
        return messages[rule] || 'Invalid input';
    }

    showFieldValidation(field, errors) {
        // Remove existing validation
        this.clearFieldValidation(field);

        if (errors.length > 0) {
            field.classList.add('border-red-500', 'bg-red-50');
            field.classList.remove('border-green-500', 'bg-green-50');

            const errorDiv = document.createElement('div');
            errorDiv.className = 'validation-error mt-1 text-sm text-red-600';
            errorDiv.innerHTML = errors.map(error => `<div class="flex items-center"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>${error}</div>`).join('');
            field.parentNode.appendChild(errorDiv);
        } else if (field.value.trim() !== '') {
            field.classList.add('border-green-500', 'bg-green-50');
            field.classList.remove('border-red-500', 'bg-red-50');

            const successDiv = document.createElement('div');
            successDiv.className = 'validation-success mt-1 text-sm text-green-600';
            successDiv.innerHTML = '<div class="flex items-center"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Valid</div>';
            field.parentNode.appendChild(successDiv);
        }
    }

    clearFieldValidation(field) {
        field.classList.remove('border-red-500', 'bg-red-50', 'border-green-500', 'bg-green-50');
        const existing = field.parentNode.querySelectorAll('.validation-error, .validation-success');
        existing.forEach(el => el.remove());
    }

    validateForm(form, validationRules) {
        let isValid = true;
        for (const [fieldName, rules] of Object.entries(validationRules)) {
            const field = form.querySelector(`[name="${fieldName}"], #${fieldName}`);
            if (field && !this.validateField(field, rules)) {
                isValid = false;
            }
        }
        return isValid;
    }
}

// Loading States Manager
class LILACLoadingManager {
    setButtonLoading(button, isLoading, loadingText = 'Loading...') {
        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                ${loadingText}
            `;
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText || button.textContent;
        }
    }

    showPageLoading() {
        const overlay = document.createElement('div');
        overlay.id = 'page-loading-overlay';
        overlay.className = 'fixed inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50';
        overlay.innerHTML = `
            <div class="text-center">
                <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-black mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-600">Loading...</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    hidePageLoading() {
        const overlay = document.getElementById('page-loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
}

// Mobile Navigation Enhancement
class LILACMobileNav {
    constructor() {
        this.init();
    }

    init() {
        this.addMobileToggle();
        this.addOverlay();
        this.setupEventListeners();
    }

    addMobileToggle() {
        const nav = document.querySelector('nav');
        // Only add toggle if none exists and no toggleMenu function is already defined
        if (nav && !nav.querySelector('#menu-toggle') && typeof window.toggleMenu === 'undefined') {
            const toggleButton = document.createElement('button');
            toggleButton.id = 'menu-toggle';
            toggleButton.className = 'md:hidden p-2 rounded-lg hover:bg-gray-800 transition-colors';
            toggleButton.setAttribute('aria-label', 'Toggle mobile menu');
            toggleButton.innerHTML = `
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            `;
            toggleButton.addEventListener('click', () => this.toggleMenu());
            nav.insertBefore(toggleButton, nav.firstChild);
        }
    }

    addOverlay() {
        if (!document.getElementById('menu-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'menu-overlay';
            overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden';
            document.body.appendChild(overlay);
        }
    }

    setupEventListeners() {
        const toggle = document.getElementById('menu-toggle');
        const overlay = document.getElementById('menu-overlay');
        const sidebar = document.querySelector('#sidebar, #side-menu');

        // Only setup if no existing toggleMenu function exists
        if (toggle && sidebar && typeof window.toggleMenu === 'undefined') {
            toggle.addEventListener('click', () => this.toggleMenu());
        }

        if (overlay && typeof window.toggleMenu === 'undefined') {
            overlay.addEventListener('click', () => this.closeMenu());
        }

        // Close menu on route change only on small screens
        if (typeof window.toggleMenu === 'undefined') {
            document.addEventListener('click', (e) => {
                const anchor = e.target.closest('a[href]');
                if (!anchor) return;
                if (window.innerWidth < 768) {
                    this.closeMenu();
                }
            });
        }
    }

    toggleMenu() {
        const sidebar = document.querySelector('#sidebar, #side-menu');
        const overlay = document.getElementById('menu-overlay');

        if (sidebar) {
            // Only toggle for mobile widths; keep sidebar fixed on desktop
            if (window.innerWidth < 768) {
                sidebar.classList.toggle('-translate-x-full');
                if (overlay) overlay.classList.toggle('hidden');
            }
        }
    }

    closeMenu() {
        const sidebar = document.querySelector('#sidebar, #side-menu');
        const overlay = document.getElementById('menu-overlay');

        if (sidebar) {
            if (window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }
        }
    }
}

// Tagging System Enhancements
console.log('🏷️ Loading LilacTags system...');
console.log('🏷️ Defining LilacTags object...');

window.LilacTags = {
    // Fetch all tags from a given API endpoint
    fetchTags: async function(api) {
        try {
            const res = await fetch(api + '?action=get_tags');
            const data = await res.json();
            return data.success ? data.tags : [];
        } catch (error) {
            console.error('Error fetching tags:', error);
            return [];
        }
    },
    
    // Render tag badges
    renderTags: function(tags) {
        if (!tags || tags.length === 0) return '';
        return tags.map(tag => `<span class="inline-block bg-purple-100 text-purple-700 text-xs px-2 py-1 rounded-full mr-1 mb-1">${tag}</span>`).join('');
    },
    
    // Attach tag input with autocomplete to a form
    attachTagInput: async function(formId, api) {
        console.log('🏷️ Attaching tag input to form:', formId);
        
        const form = document.getElementById(formId);
        if (!form) {
            console.error('Form not found:', formId);
            return;
        }
        
        let tagInput = form.querySelector('.lilac-tag-input');
        if (!tagInput) {
            tagInput = document.createElement('input');
            tagInput.type = 'text';
            tagInput.className = 'lilac-tag-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors';
            tagInput.placeholder = 'Add tags (comma or Enter to separate)';
            tagInput.autocomplete = 'off';
            
            // Add a label
            const label = document.createElement('label');
            label.className = 'block text-sm font-medium text-gray-700 mb-2 mt-4';
            label.textContent = 'Tags';
            
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                form.insertBefore(label, submitButton);
                form.insertBefore(tagInput, submitButton);
            }
        }
        
        let tagList = form.querySelector('.lilac-tag-list');
        if (!tagList) {
            tagList = document.createElement('div');
            tagList.className = 'lilac-tag-list flex flex-wrap gap-1 mt-2 mb-4';
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                form.insertBefore(tagList, submitButton);
            }
        }
        
        let tags = [];
        
        try {
            const allTags = await this.fetchTags(api);
            console.log('🏷️ Available tags:', allTags);
        } catch (error) {
            console.error('Error fetching existing tags:', error);
        }
        
        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const value = tagInput.value.trim();
                if (value && !tags.includes(value)) {
                    tags.push(value);
                    tagInput.value = '';
                    renderTagList();
                }
            } else if (e.key === 'Backspace' && tagInput.value === '' && tags.length > 0) {
                tags.pop();
                renderTagList();
            }
        });
        
        function renderTagList() {
            tagList.innerHTML = tags.map((tag, index) => `<span class='bg-purple-200 text-purple-800 px-2 py-1 rounded-full text-xs mr-1 mb-1 flex items-center'>${tag}<button type='button' class='ml-1 text-xs text-purple-600 hover:text-purple-900' data-tag-index='${index}'>&times;</button></span>`).join('');
            
            // Add event listeners to remove buttons
            tagList.querySelectorAll('button[data-tag-index]').forEach(button => {
                button.addEventListener('click', function() {
                    const tagIndex = parseInt(this.getAttribute('data-tag-index'));
                    tags.splice(tagIndex, 1);
                    renderTagList();
                });
            });
        }
        
        // Expose a method to get tags from the form
        form.getTags = () => tags;
        
        console.log('🏷️ Tag input attached successfully to', formId);
    },
    
    // Helper to add tags to FormData
    addTagsToFormData: function(form, formData) {
        if (form.getTags) {
            const tags = form.getTags();
            formData.append('tags', JSON.stringify(tags));
            console.log('🏷️ Added tags to form data:', tags);
        }
    }
};

console.log('🏷️ LilacTags object defined successfully:', !!window.LilacTags);

// Initialize all systems
document.addEventListener('DOMContentLoaded', function() {
    // Initialize enhancement systems
    window.lilacNotifications = new LILACNotifications();
    window.lilacValidator = new LILACFormValidator();
    window.lilacLoading = new LILACLoadingManager();
    window.lilacMobileNav = new LILACMobileNav();

    // Smooth page transitions (fade-out on navigation)
    try {
        const anchors = Array.from(document.querySelectorAll('a[href]'));
        anchors.forEach(a => {
            const href = a.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            a.addEventListener('click', function(e){
                // only intercept left-click, same tab
                if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                // Avoid double-handling if download or target set
                if (a.getAttribute('download') !== null || a.getAttribute('target') === '_blank') return;
                e.preventDefault();
                const main = document.getElementById('main-content');
                if (main) {
                    main.classList.add('transition-opacity','duration-200');
                    main.style.opacity = '0';
                    setTimeout(() => { window.location.href = href; }, 180);
                } else {
                    // No main content container; navigate immediately without fade to avoid sidebar flicker
                    window.location.href = href;
                }
            });
        });
    } catch(_) {}

    // Enhanced alert replacement
    window.showNotification = (message, type = 'info') => window.lilacNotifications.show(message, type);
    window.showSuccessMessage = (message) => window.lilacNotifications.success(message);
    window.showErrorMessage = (message) => window.lilacNotifications.error(message);
    
    const originalAlert = window.alert;
    window.alert = function(message) {
        if (message.toLowerCase().includes('error')) {
            window.lilacNotifications.error(message);
        } else if (message.toLowerCase().includes('success')) {
            window.lilacNotifications.success(message);
        } else {
            window.lilacNotifications.info(message);
        }
    };

    // Accessibility improvements
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id*="Modal"]:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    });

    // Enhanced focus management
    const style = document.createElement('style');
    style.textContent = `
        *:focus { outline: 2px solid #3B82F6; outline-offset: 2px; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .focus\\:not-sr-only:focus { position: static; width: auto; height: auto; padding: inherit; margin: inherit; overflow: visible; clip: auto; white-space: normal; }
    `;
    document.head.appendChild(style);

    console.log('🚀 LILAC Enhancement System initialized');
    console.log('🏷️ LilacTags available:', !!window.LilacTags);
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LILACNotifications,
        LILACFormValidator,
        LILACLoadingManager,
        LILACMobileNav
    };
} 