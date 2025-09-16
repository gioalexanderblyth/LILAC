/**
 * Security Utilities
 * Client-side security functions and validation
 */

class SecurityUtils {
    constructor() {
        this.allowedFileTypes = {
            images: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            documents: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            spreadsheets: ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            text: ['text/plain', 'text/csv']
        };
        
        this.maxFileSizes = {
            images: 10 * 1024 * 1024, // 10MB
            documents: 20 * 1024 * 1024, // 20MB
            spreadsheets: 15 * 1024 * 1024, // 15MB
            text: 5 * 1024 * 1024 // 5MB
        };
    }
    
    /**
     * Validate file upload
     */
    validateFileUpload(file, options = {}) {
        const errors = [];
        
        // Check file size
        const maxSize = options.maxSize || this.getMaxFileSize(file.type);
        if (file.size > maxSize) {
            errors.push(`File size exceeds ${this.formatFileSize(maxSize)} limit`);
        }
        
        // Check file type
        const allowedTypes = options.allowedTypes || this.getAllowedFileTypes(file.type);
        if (!allowedTypes.includes(file.type)) {
            errors.push('File type not allowed');
        }
        
        // Check file name for malicious patterns
        if (this.hasMaliciousFileName(file.name)) {
            errors.push('File name contains invalid characters');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
    
    /**
     * Get maximum file size for file type
     */
    getMaxFileSize(fileType) {
        for (const [category, types] of Object.entries(this.allowedFileTypes)) {
            if (types.includes(fileType)) {
                return this.maxFileSizes[category];
            }
        }
        return 5 * 1024 * 1024; // Default 5MB
    }
    
    /**
     * Get allowed file types for category
     */
    getAllowedFileTypes(fileType) {
        for (const [category, types] of Object.entries(this.allowedFileTypes)) {
            if (types.includes(fileType)) {
                return types;
            }
        }
        return [];
    }
    
    /**
     * Check for malicious file names
     */
    hasMaliciousFileName(filename) {
        const maliciousPatterns = [
            /\.\./, // Directory traversal
            /[<>:"|?*]/, // Invalid characters
            /^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/i, // Reserved names
            /\.(exe|bat|cmd|com|pif|scr|vbs|js|jar|php|asp|aspx|jsp)$/i // Executable extensions
        ];
        
        return maliciousPatterns.some(pattern => pattern.test(filename));
    }
    
    /**
     * Sanitize input string
     */
    sanitizeInput(input) {
        if (typeof input !== 'string') return input;
        
        return input
            .trim()
            .replace(/[<>]/g, '') // Remove potential HTML tags
            .replace(/javascript:/gi, '') // Remove javascript: protocol
            .replace(/on\w+=/gi, '') // Remove event handlers
            .substring(0, 1000); // Limit length
    }
    
    /**
     * Validate email address
     */
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Validate phone number
     */
    validatePhone(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
    }
    
    /**
     * Validate URL
     */
    validateUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    /**
     * Validate date
     */
    validateDate(dateString) {
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date);
    }
    
    /**
     * Validate numeric input
     */
    validateNumber(value, options = {}) {
        const num = parseFloat(value);
        
        if (isNaN(num)) return false;
        
        if (options.min !== undefined && num < options.min) return false;
        if (options.max !== undefined && num > options.max) return false;
        if (options.integer && !Number.isInteger(num)) return false;
        
        return true;
    }
    
    /**
     * Validate required fields
     */
    validateRequired(data, requiredFields) {
        const errors = [];
        
        for (const field of requiredFields) {
            if (!data[field] || (typeof data[field] === 'string' && !data[field].trim())) {
                errors.push(`${this.formatFieldName(field)} is required`);
            }
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
    
    /**
     * Format field name for display
     */
    formatFieldName(fieldName) {
        return fieldName
            .replace(/_/g, ' ')
            .replace(/([A-Z])/g, ' $1')
            .replace(/^./, str => str.toUpperCase())
            .trim();
    }
    
    /**
     * Format file size for display
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    /**
     * Generate secure random string
     */
    generateSecureId(length = 16) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        return result;
    }
    
    /**
     * Hash string (simple hash for client-side)
     */
    hashString(str) {
        let hash = 0;
        if (str.length === 0) return hash;
        
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        
        return hash.toString();
    }
    
    /**
     * Check if string contains XSS patterns
     */
    hasXSSPatterns(input) {
        const xssPatterns = [
            /<script/i,
            /javascript:/i,
            /on\w+\s*=/i,
            /<iframe/i,
            /<object/i,
            /<embed/i,
            /<link/i,
            /<meta/i,
            /expression\s*\(/i,
            /url\s*\(/i
        ];
        
        return xssPatterns.some(pattern => pattern.test(input));
    }
    
    /**
     * Sanitize HTML content
     */
    sanitizeHtml(html) {
        const temp = document.createElement('div');
        temp.textContent = html;
        return temp.innerHTML;
    }
    
    /**
     * Validate form data
     */
    validateForm(formData, rules) {
        const errors = [];
        
        for (const [field, rule] of Object.entries(rules)) {
            const value = formData[field];
            
            // Required validation
            if (rule.required && (!value || (typeof value === 'string' && !value.trim()))) {
                errors.push(`${this.formatFieldName(field)} is required`);
                continue;
            }
            
            // Skip other validations if field is empty and not required
            if (!value || (typeof value === 'string' && !value.trim())) {
                continue;
            }
            
            // Type-specific validations
            if (rule.type === 'email' && !this.validateEmail(value)) {
                errors.push(`${this.formatFieldName(field)} must be a valid email address`);
            }
            
            if (rule.type === 'phone' && !this.validatePhone(value)) {
                errors.push(`${this.formatFieldName(field)} must be a valid phone number`);
            }
            
            if (rule.type === 'url' && !this.validateUrl(value)) {
                errors.push(`${this.formatFieldName(field)} must be a valid URL`);
            }
            
            if (rule.type === 'date' && !this.validateDate(value)) {
                errors.push(`${this.formatFieldName(field)} must be a valid date`);
            }
            
            if (rule.type === 'number' && !this.validateNumber(value, rule)) {
                errors.push(`${this.formatFieldName(field)} must be a valid number`);
            }
            
            // Length validation
            if (rule.minLength && value.length < rule.minLength) {
                errors.push(`${this.formatFieldName(field)} must be at least ${rule.minLength} characters`);
            }
            
            if (rule.maxLength && value.length > rule.maxLength) {
                errors.push(`${this.formatFieldName(field)} must be no more than ${rule.maxLength} characters`);
            }
            
            // Pattern validation
            if (rule.pattern && !rule.pattern.test(value)) {
                errors.push(`${this.formatFieldName(field)} format is invalid`);
            }
            
            // XSS validation
            if (rule.preventXSS && this.hasXSSPatterns(value)) {
                errors.push(`${this.formatFieldName(field)} contains invalid content`);
            }
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
}

// Initialize security utils
document.addEventListener('DOMContentLoaded', function() {
    window.securityUtils = new SecurityUtils();
});

// Global security functions for backward compatibility
window.validateFileUpload = function(file, options) {
    if (window.securityUtils) {
        return window.securityUtils.validateFileUpload(file, options);
    }
    return { valid: true, errors: [] };
};

window.sanitizeInput = function(input) {
    if (window.securityUtils) {
        return window.securityUtils.sanitizeInput(input);
    }
    return input;
};

window.validateEmail = function(email) {
    if (window.securityUtils) {
        return window.securityUtils.validateEmail(email);
    }
    return true;
};

window.validateForm = function(formData, rules) {
    if (window.securityUtils) {
        return window.securityUtils.validateForm(formData, rules);
    }
    return { valid: true, errors: [] };
};
