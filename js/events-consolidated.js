/**
 * Consolidated Events JavaScript
 * Combines multiple JS files for better performance
 */

// Error Handler (from js/error-handler.js)
window.ErrorHandler = {
    show: function(message, type = 'error') {
        console.error('Error:', message);
        if (window.showNotification) {
            showNotification(message, type);
        }
    },
    handle: function(error, context = '') {
        console.error(`Error in ${context}:`, error);
        this.show(error.message || 'An error occurred', 'error');
    }
};

// Security Utils (from js/security-utils.js)
window.SecurityUtils = {
    sanitizeInput: function(input) {
        if (typeof input !== 'string') return input;
        return input.replace(/[<>\"'&]/g, function(match) {
            const escape = {
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#x27;',
                '&': '&amp;'
            };
            return escape[match];
        });
    },
    validateCSRF: function(token) {
        return token && token.length === 64;
    }
};

// Awards Check (from js/awards-check.js)
window.checkAwardCriteria = function(type, id) {
    if (!window.awardsCheckEnabled) return;
    
    try {
        fetch('api/awards-check.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: type,
                id: id,
                timestamp: Date.now()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.awards) {
                data.awards.forEach(award => {
                    if (window.lilacNotifications) {
                        window.lilacNotifications.success(`🎉 New Award Earned: ${award.name}`, 5000);
                    }
                });
            }
        })
        .catch(error => {
            console.warn('Awards check failed:', error);
        });
    } catch (error) {
        console.warn('Awards check error:', error);
    }
};

// Events Config (from js/events-config.js)
window.EventsConfig = {
    categories: [
        'Academic Excellence',
        'Research & Innovation',
        'Community Service',
        'Leadership Development',
        'Cultural Activities',
        'Sports & Recreation',
        'International Programs',
        'Student Affairs',
        'Faculty Development',
        'Administrative'
    ],
    statuses: ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'],
    priorities: ['Low', 'Medium', 'High', 'Critical']
};

// Events Management (from js/events-management.js)
window.EventsManager = {
    currentEvents: [],
    
    loadEvents: function() {
        return fetch('api/central_events_api.php?action=get_events_by_status')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.currentEvents = data.events || [];
                    return this.currentEvents;
                }
                throw new Error(data.message || 'Failed to load events');
            });
    },
    
    createEvent: function(eventData) {
        return fetch('create_event.php', {
            method: 'POST',
            body: eventData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.currentEvents.unshift(data.event);
                return data;
            }
            throw new Error(data.message || 'Failed to create event');
        });
    },
    
    updateEvent: function(id, eventData) {
        return fetch(`api/central_events_api.php?action=update_event&id=${id}`, {
            method: 'POST',
            body: eventData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const index = this.currentEvents.findIndex(e => e.id === id);
                if (index !== -1) {
                    this.currentEvents[index] = data.event;
                }
                return data;
            }
            throw new Error(data.message || 'Failed to update event');
        });
    },
    
    deleteEvent: function(id) {
        return fetch('delete_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `event_id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.currentEvents = this.currentEvents.filter(e => e.id !== id);
                return data;
            }
            throw new Error(data.message || 'Failed to delete event');
        });
    }
};

// Modal Handlers (from js/modal-handlers.js)
window.ModalHandlers = {
    open: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    },
    
    close: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    },
    
    closeAll: function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.classList.add('hidden');
        });
        document.body.style.overflow = '';
    }
};

// Text Config (from js/text-config.js)
window.TextConfig = {
    placeholders: {
        eventTitle: 'Enter event title',
        eventDescription: 'Describe the event details',
        eventLocation: 'Enter event location',
        eventDate: 'Select event date'
    },
    labels: {
        eventTitle: 'Event Title',
        eventDescription: 'Description',
        eventLocation: 'Location',
        eventDate: 'Date',
        eventTime: 'Time'
    },
    messages: {
        success: 'Operation completed successfully',
        error: 'An error occurred',
        loading: 'Loading...',
        saving: 'Saving...'
    }
};

// Date Time Utility (from js/date-time-utility.js)
window.DateTimeUtility = {
    formatDate: function(date, format = 'short') {
        if (!date) return '';
        const d = new Date(date);
        const options = {
            short: { year: 'numeric', month: 'short', day: 'numeric' },
            long: { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' },
            time: { hour: '2-digit', minute: '2-digit' }
        };
        return d.toLocaleDateString('en-US', options[format] || options.short);
    },
    
    formatDateTime: function(date) {
        if (!date) return '';
        const d = new Date(date);
        return d.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    isToday: function(date) {
        if (!date) return false;
        const today = new Date();
        const d = new Date(date);
        return today.toDateString() === d.toDateString();
    },
    
    isPast: function(date) {
        if (!date) return false;
        const today = new Date();
        const d = new Date(date);
        return d < today;
    },
    
    isFuture: function(date) {
        if (!date) return false;
        const today = new Date();
        const d = new Date(date);
        return d > today;
    }
};

// Notification System
window.showNotification = function(message, type = 'info', duration = 3000) {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
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
    }, duration);
};

// Lazy Loader functionality (from js/lazy-loader.js)
window.LazyLoader = {
    loadedScripts: new Set(),
    loadedStyles: new Set(),
    
    loadScript: function(src, callback) {
        if (this.loadedScripts.has(src)) {
            if (callback) callback();
            return;
        }
        
        const script = document.createElement('script');
        script.src = src;
        script.onload = () => {
            this.loadedScripts.add(src);
            if (callback) callback();
        };
        script.onerror = () => {
            console.error('Failed to load script:', src);
        };
        document.head.appendChild(script);
    },
    
    loadStyle: function(href) {
        if (this.loadedStyles.has(href)) return;
        
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.onload = () => this.loadedStyles.add(href);
        link.onerror = () => console.error('Failed to load style:', href);
        document.head.appendChild(link);
    }
};

// Document Analyzer functionality (from js/document-analyzer.js)
window.DocumentAnalyzer = class {
    constructor() {
        this.keywords = {
            'leadership': ['leadership', 'leader', 'manage', 'direct', 'coordinate', 'supervise'],
            'research': ['research', 'study', 'investigation', 'analysis', 'findings'],
            'academic': ['academic', 'education', 'learning', 'teaching', 'curriculum'],
            'community': ['community', 'service', 'outreach', 'volunteer', 'social']
        };
    }
    
    async analyzeDocument(file) {
        try {
            const text = await this.extractText(file);
            const classification = this.classifyText(text);
            const confidence = this.calculateConfidence(text, classification);
            
            return {
                classification: classification,
                confidence: confidence,
                text: text.substring(0, 500) // First 500 chars
            };
        } catch (error) {
            console.error('Document analysis error:', error);
            return { classification: 'unknown', confidence: 0 };
        }
    }
    
    async extractText(file) {
        if (file.type === 'text/plain') {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = reject;
                reader.readAsText(file);
            });
        }
        // For other file types, return filename as text
        return file.name;
    }
    
    classifyText(text) {
        const lowerText = text.toLowerCase();
        let maxScore = 0;
        let bestCategory = 'unknown';
        
        for (const [category, keywords] of Object.entries(this.keywords)) {
            let score = 0;
            keywords.forEach(keyword => {
                const matches = (lowerText.match(new RegExp(keyword, 'g')) || []).length;
                score += matches;
            });
            
            if (score > maxScore) {
                maxScore = score;
                bestCategory = category;
            }
        }
        
        return maxScore > 0 ? bestCategory : 'unknown';
    }
    
    calculateConfidence(text, classification) {
        if (classification === 'unknown') return 0;
        
        const keywords = this.keywords[classification];
        const lowerText = text.toLowerCase();
        let totalMatches = 0;
        
        keywords.forEach(keyword => {
            const matches = (lowerText.match(new RegExp(keyword, 'g')) || []).length;
            totalMatches += matches;
        });
        
        // Simple confidence calculation based on keyword density
        return Math.min(totalMatches / 10, 1);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Events consolidated JavaScript loaded');
    
    // Initialize any global event listeners
    document.addEventListener('click', function(e) {
        // Handle modal close buttons
        if (e.target.matches('[data-modal-close]')) {
            const modalId = e.target.getAttribute('data-modal-close');
            window.ModalHandlers.close(modalId);
        }
    });
    
    // Handle escape key for modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.ModalHandlers.closeAll();
        }
    });
});
