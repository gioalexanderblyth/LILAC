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
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.currentFilters.search = searchInput.value;
                this.currentFilters.page = 1;
                this.loadDocuments();
            }, 300));
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
        
        // Partner filter
        const partnerFilter = document.getElementById('partner-filter');
        if (partnerFilter) {
            partnerFilter.addEventListener('change', () => {
                this.currentFilters.partner = partnerFilter.value;
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
        const uploadForm = document.getElementById('upload-mou-form');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }
        
        // File input
        const fileInput = document.getElementById('mou-file');
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
                action: 'get_all',
                ...this.currentFilters
            });
            
            const response = await fetch(`${MouMoaConfig.api.list}?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.documents = data.mous || data.documents || [];
                this.renderDocuments();
                this.updateDocumentCounters();
            } else {
                throw new Error(data.message || 'Failed to load documents');
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
        const tbody = document.getElementById('documents-table-body');
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
        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-4 py-3 whitespace-nowrap">
                    <input type="checkbox" class="rounded border-gray-300" value="${doc.id}">
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                        ${doc.institution || 'Unknown Institution'}
                    </div>
                    <div class="text-xs text-gray-500">
                        ${doc.file_name ? doc.file_name : 'No file attached'}
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${doc.location || 'Not specified'}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${doc.term || 'Not specified'}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${doc.end_date ? this.formatDate(doc.end_date) : 'Not specified'}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(doc.upload_date)}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
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
        const total = this.documents.length;
        
        const activeEl = document.getElementById('active-mous');
        const expiringEl = document.getElementById('expiring-mous');
        const totalEl = document.getElementById('total-mous');
        
        if (activeEl) activeEl.textContent = active;
        if (expiringEl) expiringEl.textContent = expiring;
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
                    message = `⚠️ MOU with ${doc.institution} expires in ${monthsUntilExpiry} months (${endDate.toLocaleDateString()})`;
                } else if (daysUntilExpiry > 30) {
                    message = `⚠️ MOU with ${doc.institution} expires in ${Math.ceil(daysUntilExpiry / 30)} month (${endDate.toLocaleDateString()})`;
                } else {
                    message = `⚠️ MOU with ${doc.institution} expires in ${daysUntilExpiry} days (${endDate.toLocaleDateString()})`;
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
        const modal = document.getElementById('upload-modal');
        if (modal) {
            modal.classList.remove('hidden');
            this.resetUploadForm();
        }
    }
    
    /**
     * Hide upload modal
     */
    hideUploadModal() {
        const modal = document.getElementById('upload-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    /**
     * Reset upload form
     */
    resetUploadForm() {
        const form = document.getElementById('upload-mou-form');
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
        formData.append('action', 'add');
        
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
                this.showNotification('MOU/MOA uploaded successfully', 'success');
                this.hideUploadModal();
                this.loadDocuments();
                
                // Check for awards earned after successful MOU creation
                if (window.checkAwardCriteria) {
                    window.checkAwardCriteria('mou', result.mou?.id || result.data?.id);
                }
            } else {
                throw new Error(result.message || 'Upload failed');
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
        const institution = form.querySelector('#institution');
        if (!institution || !institution.value.trim()) {
            errors.push('Institution is required');
        }
        
        const location = form.querySelector('#location');
        if (!location || !location.value.trim()) {
            errors.push('Location is required');
        }
        
        const contactDetails = form.querySelector('#contact-details');
        if (!contactDetails || !contactDetails.value.trim()) {
            errors.push('Contact details are required');
        }
        
        const term = form.querySelector('#term');
        if (!term || !term.value.trim()) {
            errors.push('Term is required');
        }
        
        const signDate = form.querySelector('#sign-date');
        if (!signDate || !signDate.value) {
            errors.push('Sign date is required');
        }
        
        // Date validation
        const startDate = form.querySelector('#start-date');
        const endDate = form.querySelector('#end-date');
        if (startDate && endDate && startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            
            if (end <= start) {
                errors.push('End date must be after start date');
            }
        }
        
        // Email validation for contact details
        if (contactDetails && contactDetails.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(contactDetails.value)) {
                // Contact details contains email, validate it
            }
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
        
        // Show MOU details in a modal
        this.showMouDetailsModal(doc);
    }
    
    /**
     * Show MOU details modal
     */
    showMouDetailsModal(doc) {
        // Create modal HTML
        const modalHtml = `
            <div id="mou-details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[80] flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-900">MOU Details</h3>
                            <button id="close-mou-details-modal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Institution</label>
                                    <p class="mt-1 text-sm text-gray-900">${doc.institution || 'Not specified'}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Location</label>
                                    <p class="mt-1 text-sm text-gray-900">${doc.location || 'Not specified'}</p>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact Details</label>
                                <p class="mt-1 text-sm text-gray-900">${doc.contact_details || 'Not specified'}</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Term</label>
                                    <p class="mt-1 text-sm text-gray-900">${doc.term || 'Not specified'}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Type</label>
                                    <p class="mt-1 text-sm text-gray-900">${doc.type || 'MOU'}</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sign Date</label>
                                    <p class="mt-1 text-sm text-gray-900">${doc.sign_date ? this.formatDate(doc.sign_date) : 'Not specified'}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                                    <p class="mt-1 text-sm text-gray-900">${doc.end_date ? this.formatDate(doc.end_date) : 'Not specified'}</p>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    doc.status === 'Active' ? 'bg-green-100 text-green-800' : 
                                    doc.status === 'Expired' ? 'bg-red-100 text-red-800' : 
                                    'bg-yellow-100 text-yellow-800'
                                }">
                                    ${doc.status || 'Active'}
                                </span>
                            </div>
                            
                            ${doc.file_name ? `
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Attached File</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="text-sm text-gray-900">${doc.file_name}</span>
                                        <button onclick="mouMoaManager.downloadDocument('${doc.id}')" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            Download
                                        </button>
                                    </div>
                                </div>
                            ` : `
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Attached File</label>
                                    <p class="mt-1 text-sm text-gray-500">No file attached</p>
                                </div>
                            `}
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Upload Date</label>
                                <p class="mt-1 text-sm text-gray-900">${this.formatDate(doc.upload_date)}</p>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3 mt-6">
                            <button id="edit-mou-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Edit MOU
                            </button>
                            <button id="close-mou-details-modal-btn" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Add event listeners
        const modal = document.getElementById('mou-details-modal');
        const closeBtn = document.getElementById('close-mou-details-modal');
        const closeBtn2 = document.getElementById('close-mou-details-modal-btn');
        const editBtn = document.getElementById('edit-mou-btn');
        
        const closeModal = () => {
            modal.remove();
        };
        
        closeBtn.addEventListener('click', closeModal);
        closeBtn2.addEventListener('click', closeModal);
        editBtn.addEventListener('click', () => {
            closeModal();
            this.editDocument(doc.id);
        });
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    /**
     * Edit document
     */
    editDocument(documentId) {
        const doc = this.documents.find(d => d.id === documentId);
        if (!doc) return;
        
        // Show edit modal with pre-filled data
        this.showEditMouModal(doc);
    }
    
    /**
     * Show edit MOU modal
     */
    showEditMouModal(doc) {
        // Create edit modal HTML
        const modalHtml = `
            <div id="edit-mou-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[80] flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Edit MOU/MOA</h3>
                            <button id="close-edit-mou-modal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <form id="edit-mou-form" enctype="multipart/form-data">
                            <input type="hidden" name="mou_id" value="${doc.id}">
                            <div class="space-y-3">
                                <div>
                                    <label for="edit-institution" class="block text-sm font-medium text-gray-700 mb-2">Institution</label>
                                    <input type="text" id="edit-institution" name="institution" value="${doc.institution || ''}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                </div>
                                
                                <div>
                                    <label for="edit-location" class="block text-sm font-medium text-gray-700 mb-2">Location of Institution</label>
                                    <input type="text" id="edit-location" name="location" value="${doc.location || ''}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                </div>
                                
                                <div>
                                    <label for="edit-contact-details" class="block text-sm font-medium text-gray-700 mb-2">Contact Details</label>
                                    <textarea id="edit-contact-details" name="contact_details" rows="2"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>${doc.contact_details || ''}</textarea>
                                </div>
                                
                                <div>
                                    <label for="edit-term" class="block text-sm font-medium text-gray-700 mb-2">Term</label>
                                    <input type="text" id="edit-term" name="term" value="${doc.term || ''}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                </div>
                                
                                <div>
                                    <label for="edit-sign-date" class="block text-sm font-medium text-gray-700 mb-2">Date of Sign</label>
                                    <input type="date" id="edit-sign-date" name="sign_date" value="${doc.sign_date || ''}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                </div>
                                
                                <div>
                                    <label for="edit-start-date" class="block text-sm font-medium text-gray-700 mb-2">Start Date (Optional)</label>
                                    <input type="date" id="edit-start-date" name="start_date" value="${doc.start_date || ''}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label for="edit-end-date" class="block text-sm font-medium text-gray-700 mb-2">End Date (Optional)</label>
                                    <input type="date" id="edit-end-date" name="end_date" value="${doc.end_date || ''}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label for="edit-mou-file" class="block text-sm font-medium text-gray-700 mb-2">Update File (Optional)</label>
                                    <input type="file" id="edit-mou-file" name="mou-file" accept=".pdf,.doc,.docx" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current file</p>
                                </div>
                            </div>
                            
                            <div class="flex justify-end gap-3 mt-4">
                                <button type="button" id="cancel-edit-mou" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                    Update MOU/MOA
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Add event listeners
        const modal = document.getElementById('edit-mou-modal');
        const closeBtn = document.getElementById('close-edit-mou-modal');
        const cancelBtn = document.getElementById('cancel-edit-mou');
        const form = document.getElementById('edit-mou-form');
        
        const closeModal = () => {
            modal.remove();
        };
        
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Handle form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.updateDocument(doc.id, new FormData(form));
            closeModal();
        });
    }
    
    /**
     * Update document
     */
    async updateDocument(documentId, formData) {
        try {
            this.showLoading(true);
            
            formData.append('action', 'update');
            formData.append('id', documentId);
            
            const response = await fetch(MouMoaConfig.api.update, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('MOU/MOA updated successfully', 'success');
                this.loadDocuments();
            } else {
                throw new Error(result.message || 'Update failed');
            }
        } catch (error) {
            console.error('Update error:', error);
            this.showNotification('Failed to update MOU/MOA', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Download document
     */
    downloadDocument(documentId) {
        const doc = this.documents.find(d => d.id === documentId);
        if (!doc) return;
        
        if (!doc.file_name || !doc.file_path) {
            this.showNotification('No file attached to this MOU', 'info');
            return;
        }
        
        const link = document.createElement('a');
        const fileUrl = doc.file_path.startsWith('http') ? doc.file_path : `../${doc.file_path}`;
        link.href = fileUrl;
        link.download = doc.file_name;
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
        const loadingEl = document.getElementById('loading-indicator');
        if (loadingEl) {
            loadingEl.classList.toggle('hidden', !show);
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
