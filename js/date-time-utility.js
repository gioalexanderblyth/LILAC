/**
 * DateTimeUtility JavaScript Class
 * 
 * Centralized date and time handling utility for the LILAC project frontend.
 * Provides consistent formatting, parsing, and manipulation across all JavaScript files.
 */
class DateTimeUtility {
    /**
     * Format a date to a consistent format
     * 
     * @param {Date|string} dateInput The date to format
     * @param {string} format The desired output format (default: 'YYYY-MM-DD')
     * @returns {string} Formatted date string
     */
    static formatDate(dateInput, format = 'YYYY-MM-DD') {
        try {
            const date = new Date(dateInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid date input');
            }
            
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            
            return format
                .replace('YYYY', year)
                .replace('MM', month)
                .replace('DD', day);
        } catch (error) {
            console.error('DateTimeUtility::formatDate error:', error);
            return String(dateInput);
        }
    }

    /**
     * Format a time to a consistent format
     * 
     * @param {Date|string} timeInput The time to format
     * @param {string} format The desired output format (default: 'HH:mm')
     * @returns {string} Formatted time string
     */
    static formatTime(timeInput, format = 'HH:mm') {
        try {
            const date = new Date(timeInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid time input');
            }
            
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            
            return format
                .replace('HH', hours)
                .replace('mm', minutes)
                .replace('ss', seconds);
        } catch (error) {
            console.error('DateTimeUtility::formatTime error:', error);
            return String(timeInput);
        }
    }

    /**
     * Format a datetime to a consistent format
     * 
     * @param {Date|string} datetimeInput The datetime to format
     * @param {string} format The desired output format (default: 'YYYY-MM-DD HH:mm')
     * @returns {string} Formatted datetime string
     */
    static formatDateTime(datetimeInput, format = 'YYYY-MM-DD HH:mm') {
        try {
            const date = new Date(datetimeInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid datetime input');
            }
            
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            
            return format
                .replace('YYYY', year)
                .replace('MM', month)
                .replace('DD', day)
                .replace('HH', hours)
                .replace('mm', minutes)
                .replace('ss', seconds);
        } catch (error) {
            console.error('DateTimeUtility::formatDateTime error:', error);
            return String(datetimeInput);
        }
    }

    /**
     * Get a human-readable relative time (e.g., "2 hours ago", "in 3 days")
     * 
     * @param {Date|string} datetimeInput The datetime
     * @returns {string} Human-readable relative time
     */
    static getRelativeTime(datetimeInput) {
        try {
            const date = new Date(datetimeInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid datetime input');
            }
            
            const now = new Date();
            const diffMs = date.getTime() - now.getTime();
            const diffSeconds = Math.floor(diffMs / 1000);
            const diffMinutes = Math.floor(diffSeconds / 60);
            const diffHours = Math.floor(diffMinutes / 60);
            const diffDays = Math.floor(diffHours / 24);
            
            if (diffMs < 0) {
                // Past time
                if (diffDays > 0) {
                    return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
                } else if (diffHours > 0) {
                    return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
                } else if (diffMinutes > 0) {
                    return `${diffMinutes} minute${diffMinutes > 1 ? 's' : ''} ago`;
                } else {
                    return 'Just now';
                }
            } else {
                // Future time
                if (diffDays > 0) {
                    return `in ${diffDays} day${diffDays > 1 ? 's' : ''}`;
                } else if (diffHours > 0) {
                    return `in ${diffHours} hour${diffHours > 1 ? 's' : ''}`;
                } else if (diffMinutes > 0) {
                    return `in ${diffMinutes} minute${diffMinutes > 1 ? 's' : ''}`;
                } else {
                    return 'Now';
                }
            }
        } catch (error) {
            console.error('DateTimeUtility::getRelativeTime error:', error);
            return String(datetimeInput);
        }
    }

    /**
     * Check if a date is today
     * 
     * @param {Date|string} dateInput The date to check
     * @returns {boolean} True if the date is today
     */
    static isToday(dateInput) {
        try {
            const date = new Date(dateInput);
            if (isNaN(date.getTime())) {
                return false;
            }
            
            const today = new Date();
            return date.toDateString() === today.toDateString();
        } catch (error) {
            console.error('DateTimeUtility::isToday error:', error);
            return false;
        }
    }

    /**
     * Check if a date is in the past
     * 
     * @param {Date|string} dateInput The date to check
     * @returns {boolean} True if the date is in the past
     */
    static isPast(dateInput) {
        try {
            const date = new Date(dateInput);
            if (isNaN(date.getTime())) {
                return false;
            }
            
            const now = new Date();
            return date < now;
        } catch (error) {
            console.error('DateTimeUtility::isPast error:', error);
            return false;
        }
    }

    /**
     * Check if a date is in the future
     * 
     * @param {Date|string} dateInput The date to check
     * @returns {boolean} True if the date is in the future
     */
    static isFuture(dateInput) {
        try {
            const date = new Date(dateInput);
            if (isNaN(date.getTime())) {
                return false;
            }
            
            const now = new Date();
            return date > now;
        } catch (error) {
            console.error('DateTimeUtility::isFuture error:', error);
            return false;
        }
    }

    /**
     * Get the start of the day for a given date
     * 
     * @param {Date|string} dateInput The date
     * @returns {Date} Start of day date object
     */
    static getStartOfDay(dateInput) {
        try {
            const date = new Date(dateInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid date input');
            }
            
            date.setHours(0, 0, 0, 0);
            return date;
        } catch (error) {
            console.error('DateTimeUtility::getStartOfDay error:', error);
            return new Date();
        }
    }

    /**
     * Get the end of the day for a given date
     * 
     * @param {Date|string} dateInput The date
     * @returns {Date} End of day date object
     */
    static getEndOfDay(dateInput) {
        try {
            const date = new Date(dateInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid date input');
            }
            
            date.setHours(23, 59, 59, 999);
            return date;
        } catch (error) {
            console.error('DateTimeUtility::getEndOfDay error:', error);
            return new Date();
        }
    }

    /**
     * Add days to a date
     * 
     * @param {Date|string} dateInput The date
     * @param {number} days Number of days to add (can be negative)
     * @returns {Date} New date object
     */
    static addDays(dateInput, days) {
        try {
            const date = new Date(dateInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid date input');
            }
            
            date.setDate(date.getDate() + days);
            return date;
        } catch (error) {
            console.error('DateTimeUtility::addDays error:', error);
            return new Date();
        }
    }

    /**
     * Get the current date in a consistent format
     * 
     * @param {string} format The desired format (default: 'YYYY-MM-DD')
     * @returns {string} Current date string
     */
    static getCurrentDate(format = 'YYYY-MM-DD') {
        return this.formatDate(new Date(), format);
    }

    /**
     * Get the current datetime in a consistent format
     * 
     * @param {string} format The desired format (default: 'YYYY-MM-DD HH:mm:ss')
     * @returns {string} Current datetime string
     */
    static getCurrentDateTime(format = 'YYYY-MM-DD HH:mm:ss') {
        return this.formatDateTime(new Date(), format);
    }

    /**
     * Parse a date string and return a Date object
     * 
     * @param {string} dateString The date string to parse
     * @returns {Date} Parsed date object
     */
    static parseDate(dateString) {
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid date string');
            }
            return date;
        } catch (error) {
            console.error('DateTimeUtility::parseDate error:', error);
            return new Date();
        }
    }

    /**
     * Get a formatted date for display (e.g., "January 15, 2024")
     * 
     * @param {Date|string} dateInput The date to format
     * @param {string} locale The locale (default: 'en-US')
     * @returns {string} Formatted date for display
     */
    static getDisplayDate(dateInput, locale = 'en-US') {
        try {
            const date = new Date(dateInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid date input');
            }
            
            return date.toLocaleDateString(locale, {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (error) {
            console.error('DateTimeUtility::getDisplayDate error:', error);
            return String(dateInput);
        }
    }

    /**
     * Get a formatted time for display (e.g., "2:30 PM")
     * 
     * @param {Date|string} timeInput The time to format
     * @param {string} locale The locale (default: 'en-US')
     * @returns {string} Formatted time for display
     */
    static getDisplayTime(timeInput, locale = 'en-US') {
        try {
            const date = new Date(timeInput);
            if (isNaN(date.getTime())) {
                throw new Error('Invalid time input');
            }
            
            return date.toLocaleTimeString(locale, {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        } catch (error) {
            console.error('DateTimeUtility::getDisplayTime error:', error);
            return String(timeInput);
        }
    }
}
