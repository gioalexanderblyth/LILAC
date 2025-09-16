/**
 * MOU-MOA Management JavaScript
 * Handles all MOU-MOA related functionality
 */

class MouMoaManager {
    constructor() {
        this.documents = [];
        this.currentFilters = {
            type: 'all',
            status: 'all',
            search: '',
            page: 1,
            limit: MouMoaConfig.ui.itemsPerPage
        };
        
        this.initializeEventListeners();
        this.loadDocuments();
        this.checkExpiringDocuments();
    }
    
    /**
     * Initialize event listeners
     */
    initializeEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('mou-search');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.currentFilters.search = searchInput.value;
                this.currentFilters.page = 1;
                this.loadDocuments();
            }, 300));
        }
        
        // Type filter
        const typeFilter = document.getElementById('type-filter');
        if (typeFilter) {
            typeFilter.addEventListener('change', () => {
                this.currentFilters.type = typeFilter.value;
                this.currentFilters.page = 1;
                this.loadDocuments();
            });
        }
        
        // Status filter
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                this.currentFilters.status = statusFilter.value;
                this.currentFilters.page = 1;
                this.loadDocuments();
            });
        }
        
        // Upload button
        const uploadBtn = document.getElementById('upload-mou-btn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', () => this.showUploadModal());
        }
        
        // Upload form
        const uploadForm = document.getElementById('mou-upload-form');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }
        
        // File input
        const fileInput = document.getElementById('mou-file-input');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFileSelection(e));
        }
    }
    
    /**
     * Load documents from API
     */
    async loadDocuments() {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                action: 'list',
                ...this.currentFilters
            });
            
            const response = await fetch(`${MouMoaConfig.api.list}?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.documents = data.documents || [];
                this.renderDocuments();
                this.updateDocumentCounters();
            } else {
                throw new Error(data.error || 'Failed to load documents');
            }
        } catch (error) {
            console.error('Error loading documents:', error);
            this.showNotification('Failed to load documents', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Render documents in the table
     */
    renderDocuments() {
        const tbody = document.getElementById('mou-table-body');
        if (!tbody) return;
        
        if (this.documents.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-500">
                        No documents found
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.documents.map(doc => this.createDocumentRow(doc)).join('');
    }
    
    /**
     * Create a document table row
     */
    createDocumentRow(doc) {
        const docType = MouMoaConfig.documentTypes.find(t => t.value === doc.type) || MouMoaConfig.documentTypes[0];
        const status = this.getDocumentStatus(doc);
        const statusConfig = MouMoaConfig.statusOptions.find(s => s.value === status) || MouMoaConfig.statusOptions[0];
        
        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        ${doc.partner_name || 'Unknown Partner'}
                    </div>
                    <div class="text-sm text-gray-500">
                        ${doc.title || 'Untitled Document'}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${docType.color}">
                        ${docType.label}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(doc.start_date)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(doc.end_date)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusConfig.color}">
                        ${statusConfig.label}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.getDaysUntilExpiration(doc.end_date)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="mouMoaManager.viewDocument('${doc.id}')" 
                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                        View
                    </button>
                    <button onclick="mouMoaManager.downloadDocument('${doc.id}')" 
                            class="text-green-600 hover:text-green-900 mr-3">
                        Download
                    </button>
                    <button onclick="mouMoaManager.deleteDocument('${doc.id}')" 
                            class="text-red-600 hover:text-red-900">
                        Delete
                    </button>
                </td>
            </tr>
        `;
    }
    
    /**
     * Update document counters
     */
    updateDocumentCounters() {
        const active = this.documents.filter(d => this.getDocumentStatus(d) === 'active').length;
        const expiring = this.documents.filter(d => this.getDocumentStatus(d) === 'expiring').length;
        const expired = this.documents.filter(d => this.getDocumentStatus(d) === 'expired').length;
        const total = this.documents.length;
        
        const activeEl = document.getElementById('active-count');
        const expiringEl = document.getElementById('expiring-count');
        const expiredEl = document.getElementById('expired-count');
        const totalEl = document.getElementById('total-count');
        
        if (activeEl) activeEl.textContent = active;
        if (expiringEl) expiringEl.textContent = expiring;
        if (expiredEl) expiredEl.textContent = expired;
        if (totalEl) totalEl.textContent = total;
    }
    
    /**
     * Get document status based on end date
     */
    getDocumentStatus(doc) {
        if (!doc.end_date) return 'active';
        
        const endDate = new Date(doc.end_date);
        const today = new Date();
        const daysUntilExpiration = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
        
        if (daysUntilExpiration < 0) return 'expired';
        if (daysUntilExpiration <= MouMoaConfig.expiration.criticalDays) return 'expiring';
        return 'active';
    }
    
    /**
     * Get days until expiration
     */
    getDaysUntilExpiration(endDate) {
        if (!endDate) return 'N/A';
        
        const end = new Date(endDate);
        const today = new Date();
        const days = Math.ceil((end - today) / (1000 * 60 * 60 * 24));
        
        if (days < 0) return `Expired ${Math.abs(days)} days ago`;
        if (days === 0) return 'Expires today';
        if (days === 1) return 'Expires tomorrow';
        return `${days} days`;
    }
    
    /**
     * Check for expiring documents and show notifications
     */
    checkExpiringDocuments() {
        const expiringDocs = this.documents.filter(doc => {
            const status = this.getDocumentStatus(doc);
            return status === 'expiring' || status === 'expired';
        });
        
        if (expiringDocs.length > 0) {
            this.showExpiringDocumentsNotification(expiringDocs);
        }
        
        // Check for upcoming expirations (6 months before expiry)
        this.checkUpcomingExpirations();
    }
    
    /**
     * Check for upcoming MOU expirations (6 months before)
     */
    checkUpcomingExpirations() {
        const today = new Date();
        const sixMonthsFromNow = new Date();
        sixMonthsFromNow.setMonth(sixMonthsFromNow.getMonth() + 6);
        
        this.documents.forEach(doc => {
            if (!doc.end_date) return;
            
            const endDate = new Date(doc.end_date);
            const sixMonthsBefore = new Date(endDate);
            sixMonthsBefore.setMonth(sixMonthsBefore.getMonth() - 6);
            
            // Check if we're within the 6-month warning period
            if (today >= sixMonthsBefore && today < endDate) {
                const daysUntilExpiry = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
                const monthsUntilExpiry = Math.ceil(daysUntilExpiry / 30);
                
                let message = '';
                if (monthsUntilExpiry > 1) {
                    message = `⚠️ MOU with ${doc.partner_name} expires in ${monthsUntilExpiry} months (${endDate.toLocaleDateString()})`;
                } else if (daysUntilExpiry > 30) {
                    message = `⚠️ MOU with ${doc.partner_name} expires in ${Math.ceil(daysUntilExpiry / 30)} month (${endDate.toLocaleDateString()})`;
                } else {
                    message = `⚠️ MOU with ${doc.partner_name} expires in ${daysUntilExpiry} days (${endDate.toLocaleDateString()})`;
                }
                
                this.showNotification(message, 'warning');
            }
        });
    }
    
    /**
     * Show expiring documents notification
     */
    showExpiringDocumentsNotification(docs) {
        const expired = docs.filter(d => this.getDocumentStatus(d) === 'expired');
        const expiring = docs.filter(d => this.getDocumentStatus(d) === 'expiring');
        
        let message = '';
        if (expired.length > 0) {
            message += `${expired.length} document(s) have expired. `;
        }
        if (expiring.length > 0) {
            message += `${expiring.length} document(s) are expiring soon.`;
        }
        
        this.showNotification(message, 'warning');
    }
    
    /**
     * Show upload modal
     */
    showUploadModal() {
        const modal = document.getElementById('mou-upload-modal');
        if (modal) {
            modal.classList.remove('hidden');
            this.resetUploadForm();
        }
    }
    
    /**
     * Hide upload modal
     */
    hideUploadModal() {
        const modal = document.getElementById('mou-upload-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    /**
     * Reset upload form
     */
    resetUploadForm() {
        const form = document.getElementById('mou-upload-form');
        if (form) {
            form.reset();
        }
    }
    
    /**
     * Handle file selection
     */
    handleFileSelection(event) {
        const files = Array.from(event.target.files);
        this.validateFiles(files);
    }
    
    /**
     * Validate selected files
     */
    validateFiles(files) {
        const errors = [];
        
        if (files.length > MouMoaConfig.upload.maxFiles) {
            errors.push(`Maximum ${MouMoaConfig.upload.maxFiles} files allowed`);
        }
        
        files.forEach(file => {
            if (file.size > MouMoaConfig.upload.maxFileSize) {
                errors.push(`${file.name} is too large (max ${this.formatFileSize(MouMoaConfig.upload.maxFileSize)})`);
            }
            
            if (!MouMoaConfig.upload.allowedTypes.includes(file.type)) {
                errors.push(`${file.name} has an unsupported file type`);
            }
        });
        
        if (errors.length > 0) {
            this.showNotification(errors.join(', '), 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Handle form submission
     */
    async handleUpload(event) {
        event.preventDefault();
        
        // Validate form
        if (!this.validateForm()) return;
        
        const formData = new FormData(event.target);
        formData.append('action', 'upload');
        
        try {
            this.showLoading(true);
            
            const response = await fetch(MouMoaConfig.api.upload, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Document uploaded successfully', 'success');
                this.hideUploadModal();
                this.loadDocuments();
                
                // Check for awards earned after successful MOU creation
                if (window.checkAwardCriteria) {
                    window.checkAwardCriteria('mou', result.document_id || result.data?.document_id);
                }
            } else {
                throw new Error(result.error || 'Upload failed');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showNotification('Upload failed', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Validate form data
     */
    validateForm() {
        const form = document.getElementById('mou-upload-form');
        if (!form) return false;
        
        const errors = [];
        
        // Required fields
        const partnerName = form.querySelector('#partner-name');
        if (!partnerName || !partnerName.value.trim()) {
            errors.push(MouMoaConfig.validation.required.partnerName);
        }
        
        const documentType = form.querySelector('#document-type');
        if (!documentType || !documentType.value) {
            errors.push(MouMoaConfig.validation.required.documentType);
        }
        
        const startDate = form.querySelector('#start-date');
        if (!startDate || !startDate.value) {
            errors.push(MouMoaConfig.validation.required.startDate);
        }
        
        const endDate = form.querySelector('#end-date');
        if (!endDate || !endDate.value) {
            errors.push(MouMoaConfig.validation.required.endDate);
        }
        
        // Date validation
        if (startDate && endDate && startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            
            if (end <= start) {
                errors.push('End date must be after start date');
            }
        }
        
        // Email validation
        const contactEmail = form.querySelector('#contact-email');
        if (contactEmail && contactEmail.value && !MouMoaConfig.validation.patterns.email.test(contactEmail.value)) {
            errors.push('Please enter a valid email address');
        }
        
        // Phone validation
        const contactPhone = form.querySelector('#contact-phone');
        if (contactPhone && contactPhone.value && !MouMoaConfig.validation.patterns.phone.test(contactPhone.value)) {
            errors.push('Please enter a valid phone number');
        }
        
        // URL validation
        const website = form.querySelector('#website');
        if (website && website.value && !MouMoaConfig.validation.patterns.url.test(website.value)) {
            errors.push('Please enter a valid website URL');
        }
        
        if (errors.length > 0) {
            this.showNotification(errors.join(', '), 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * View document
     */
    viewDocument(documentId) {
        const doc = this.documents.find(d => d.id === documentId);
        if (!doc) return;
        
        if (window.documentViewer) {
            const filePath = doc.file_path || doc.document_path;
            const fileType = this.getFileType(doc.filename || doc.document_name);
            window.documentViewer.showDocument(filePath, fileType, doc.title || doc.document_name);
        }
    }
    
    /**
     * Download document
     */
    downloadDocument(documentId) {
        const doc = this.documents.find(d => d.id === documentId);
        if (!doc) return;
        
        const link = document.createElement('a');
        link.href = doc.file_path || doc.document_path;
        link.download = doc.filename || doc.document_name;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * Delete document
     */
    async deleteDocument(documentId) {
        if (!confirm('Are you sure you want to delete this document?')) return;
        
        try {
            this.showLoading(true);
            
            const response = await fetch(MouMoaConfig.api.delete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: documentId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Document deleted successfully', 'success');
                this.loadDocuments();
            } else {
                throw new Error(result.error || 'Delete failed');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Failed to delete document', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Show loading state
     */
    showLoading(show) {
        const loadingEl = document.getElementById('mou-loading');
        if (loadingEl) {
            loadingEl.style.display = show ? 'block' : 'none';
        }
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = MouMoaConfig.notifications[type] || MouMoaConfig.notifications.info;
        
        // Create notification element
        const notificationEl = document.createElement('div');
        notificationEl.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${notification.bgColor} ${notification.color}`;
        notificationEl.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">${notification.icon}</span>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notificationEl);
        
        // Remove after 5 seconds
        setTimeout(() => {
            if (notificationEl.parentNode) {
                notificationEl.parentNode.removeChild(notificationEl);
            }
        }, 5000);
    }
    
    /**
     * Utility functions
     */
    getFileType(filename) {
        if (!filename) return 'pdf';
        return filename.split('.').pop().toLowerCase();
    }
    
    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    formatDate(dateString) {
        if (!dateString) return 'Unknown';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.mouMoaManager = new MouMoaManager();
});
