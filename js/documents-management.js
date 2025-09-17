/**
 * Documents Management JavaScript
 * Handles all documents-related functionality
 */

class DocumentsManager {
    constructor() {
        this.currentFilters = {
            page: 1,
            limit: DocumentsConfig.pagination.defaultLimit,
            search: '',
            category: '',
            sortBy: 'upload_date',
            sortOrder: 'desc'
        };
        
        this.currentDocuments = [];
        this.recentUploads = [];
        this.isUploading = false;
        
        this.initializeEventListeners();
        this.loadRecentUploadsFromStorage();
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
        
        // Category filter
        const categorySelect = document.getElementById('category-filter');
        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                this.currentFilters.category = categorySelect.value;
                this.currentFilters.page = 1;
                this.loadDocuments();
            });
        }
        
        // Pagination limit
        const limitSelect = document.getElementById('pagination-limit');
        if (limitSelect) {
            limitSelect.addEventListener('change', () => {
                this.currentFilters.limit = parseInt(limitSelect.value);
                this.currentFilters.page = 1;
                this.loadDocuments();
            });
        }
        
        // Upload modal
        const uploadBtn = document.getElementById('upload-btn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', () => this.openDocumentUpload());
        }
        
        // File input
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFileSelection(e));
        }
        
        // Upload form
        const uploadForm = document.getElementById('upload-form');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }
    }
    
    /**
     * Load documents from API
     */
    async loadDocuments() {
        try {
            const params = new URLSearchParams(this.currentFilters);
            const response = await fetch(`${DocumentsConfig.api.list}?action=list&${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const responseText = await response.text();
            if (!responseText || responseText.trim() === '') {
                console.log('Empty response from documents API, using fallback data');
                this.currentDocuments = [];
                this.renderDocuments();
                this.updatePagination({});
                return;
            }
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', responseText);
                throw new Error('Invalid JSON response from documents API');
            }
            
            if (data.success) {
                this.currentDocuments = data.documents || [];
                this.renderDocuments();
                this.updatePagination(data.pagination || {});
            } else {
                console.log('Documents API returned error, using fallback data');
                this.currentDocuments = [];
                this.renderDocuments();
                this.updatePagination({});
            }
        } catch (error) {
            console.error('Error loading documents:', error);
            console.log('Using fallback data for documents');
            this.currentDocuments = [];
            this.renderDocuments();
            this.updatePagination({});
            this.showNotification('Using fallback data for documents', 'warning');
        }
    }
    
    /**
     * Render documents in the table
     */
    renderDocuments() {
        const tbody = document.getElementById('documents-table-body');
        if (!tbody) return;
        
        if (this.currentDocuments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-8 text-gray-500">
                        No documents found
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.currentDocuments.map(doc => this.createDocumentRow(doc)).join('');
    }
    
    /**
     * Create a document table row
     */
    createDocumentRow(doc) {
        const fileType = this.getFileType(doc.filename || doc.document_name);
        const fileIcon = DocumentsConfig.fileTypes[fileType] || DocumentsConfig.fileTypes.default;
        
        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">${fileIcon.icon}</span>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                ${doc.title || doc.document_name || 'Untitled'}
                            </div>
                            <div class="text-sm text-gray-500">
                                ${doc.filename || 'Unknown file'}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        ${doc.category || 'Uncategorized'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatFileSize(doc.file_size)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(doc.upload_date)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${doc.uploaded_by || 'Unknown'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="documentsManager.viewDocument('${doc.id}')" 
                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                        View
                    </button>
                    <button onclick="documentsManager.downloadDocument('${doc.id}')" 
                            class="text-green-600 hover:text-green-900 mr-3">
                        Download
                    </button>
                    <button onclick="documentsManager.deleteDocument('${doc.id}')" 
                            class="text-red-600 hover:text-red-900">
                        Delete
                    </button>
                </td>
            </tr>
        `;
    }
    
    /**
     * Update pagination controls
     */
    updatePagination(pagination) {
        const paginationContainer = document.getElementById('pagination-container');
        if (!paginationContainer || !pagination) return;
        
        const { currentPage, totalPages, totalItems } = pagination;
        
        paginationContainer.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing ${(currentPage - 1) * this.currentFilters.limit + 1} to 
                    ${Math.min(currentPage * this.currentFilters.limit, totalItems)} of ${totalItems} results
                </div>
                <div class="flex space-x-2">
                    <button onclick="documentsManager.goToPage(${currentPage - 1})" 
                            ${currentPage <= 1 ? 'disabled' : ''}
                            class="px-3 py-1 text-sm border rounded disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <span class="px-3 py-1 text-sm border rounded">
                        Page ${currentPage} of ${totalPages}
                    </span>
                    <button onclick="documentsManager.goToPage(${currentPage + 1})" 
                            ${currentPage >= totalPages ? 'disabled' : ''}
                            class="px-3 py-1 text-sm border rounded disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        `;
    }
    
    /**
     * Go to specific page
     */
    goToPage(page) {
        if (page < 1) return;
        this.currentFilters.page = page;
        this.loadDocuments();
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
        
        if (files.length > DocumentsConfig.upload.maxFiles) {
            errors.push(`Maximum ${DocumentsConfig.upload.maxFiles} files allowed`);
        }
        
        files.forEach(file => {
            if (file.size > DocumentsConfig.upload.maxFileSize) {
                errors.push(`${file.name} is too large (max ${this.formatFileSize(DocumentsConfig.upload.maxFileSize)})`);
            }
            
            if (!DocumentsConfig.upload.allowedTypes.includes(file.type)) {
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
     * Handle file upload
     */
    async handleUpload(event) {
        event.preventDefault();
        
        if (this.isUploading) return;
        
        const fileInput = document.getElementById('file-input');
        const files = Array.from(fileInput.files);
        
        if (files.length === 0) {
            this.showNotification('Please select files to upload', 'warning');
            return;
        }
        
        if (!this.validateFiles(files)) return;
        
        this.isUploading = true;
        this.updateUploadButton(true);
        
        try {
            for (let i = 0; i < files.length; i++) {
                await this.uploadSingleFile(files[i], i);
            }
            
            this.showNotification('Files uploaded successfully', 'success');
            this.loadDocuments();
            this.closeUploadModal();
            
            // Check for awards earned after successful document upload
            if (window.checkAwardCriteria) {
                window.checkAwardCriteria('document', 'batch_upload');
            }
            
        } catch (error) {
            console.error('Upload error:', error);
            this.showNotification('Upload failed', 'error');
        } finally {
            this.isUploading = false;
            this.updateUploadButton(false);
        }
    }
    
    /**
     * Upload single file
     */
    async uploadSingleFile(file, index) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'upload');
        
        // Add category if selected
        const categorySelect = document.getElementById('upload-category');
        if (categorySelect && categorySelect.value) {
            formData.append('category', categorySelect.value);
        }
        
        const response = await fetch(DocumentsConfig.api.upload, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`Upload failed: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Upload failed');
        }
        
        return result;
    }
    
    /**
     * View document
     */
    viewDocument(documentId) {
        const doc = this.currentDocuments.find(d => d.id === documentId);
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
        const doc = this.currentDocuments.find(d => d.id === documentId);
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
            const response = await fetch(DocumentsConfig.api.delete, {
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
                this.loadStats(); // Add this line to refresh stats after deletion
            } else {
                throw new Error(result.error || 'Delete failed');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Failed to delete document', 'error');
        }
    }
    
    /**
     * Open document upload modal
     */
    openDocumentUpload() {
        const modal = document.getElementById('upload-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }
    
    /**
     * Close upload modal
     */
    closeUploadModal() {
        const modal = document.getElementById('upload-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
        
        // Reset form
        const form = document.getElementById('upload-form');
        if (form) {
            form.reset();
        }
    }
    
    /**
     * Update upload button state
     */
    updateUploadButton(isUploading) {
        const button = document.getElementById('upload-submit-btn');
        if (button) {
            button.disabled = isUploading;
            button.textContent = isUploading ? 'Uploading...' : 'Upload Files';
        }
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        if (window.showNotification) {
            window.showNotification(message, type);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
    
    /**
     * Utility functions
     */
    getFileType(filename) {
        if (!filename) return 'default';
        const ext = filename.split('.').pop().toLowerCase();
        return DocumentsConfig.fileTypes[ext] ? ext : 'default';
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
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
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
    
    /**
     * Load recent uploads from localStorage
     */
    loadRecentUploadsFromStorage() {
        try {
            const stored = localStorage.getItem('lilac_recent_uploads');
            if (stored) {
                this.recentUploads = JSON.parse(stored);
            }
        } catch (error) {
            console.error('Failed to load recent uploads:', error);
            this.recentUploads = [];
        }
    }

    /**
     * Load stats from API
     */
    async loadStats() {
        try {
            const response = await fetch('api/documents.php?action=get_stats');
            const data = await response.json();
            
            if (data.success && data.stats) {
                this.updateStats(data.stats);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }
    
    /**
     * Update stats display
     */
    updateStats(stats) {
        const totalElement = document.getElementById('total-documents');
        const recentElement = document.getElementById('recent-documents');
        const typesElement = document.getElementById('document-types');
        
        if (totalElement) {
            totalElement.textContent = stats.total || 0;
        }
        if (recentElement) {
            recentElement.textContent = stats.recent || 0;
        }
        if (typesElement) {
            // Load categories count
            this.loadCategoriesCount();
        }
    }
    
    /**
     * Load categories count
     */
    async loadCategoriesCount() {
        try {
            const response = await fetch('api/documents.php?action=get_categories');
            const data = await response.json();
            
            if (data.success) {
                const typesElement = document.getElementById('document-types');
                if (typesElement) {
                    typesElement.textContent = data.categories.length;
                }
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.documentsManager = new DocumentsManager();
    window.documentsManager.loadDocuments();
});
