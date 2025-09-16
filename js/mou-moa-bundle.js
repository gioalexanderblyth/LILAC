/**
 * MOU/MOA Consolidated JavaScript Bundle
 * Combines all MOU/MOA related functionality for better performance
 * Generated: 2024-12-19
 */

// Configuration
const MouMoaConfig = {
    api: {
        list: 'api/mous.php?action=get_all',
        add: 'api/mous.php?action=add',
        update: 'api/mous.php?action=update',
        delete: 'api/mous.php?action=delete',
        sync: 'api/mous.php?action=sync_from_documents'
    },
    pagination: {
        defaultLimit: 10,
        maxLimit: 100
    },
    fileTypes: [
        { value: 'pdf', label: 'PDF', color: 'text-red-600' },
        { value: 'doc', label: 'DOC', color: 'text-blue-600' },
        { value: 'docx', label: 'DOCX', color: 'text-blue-600' },
        { value: 'txt', label: 'TXT', color: 'text-gray-600' }
    ],
    statusTypes: [
        { value: 'Active', label: 'Active', color: 'text-green-600' },
        { value: 'Expired', label: 'Expired', color: 'text-red-600' },
        { value: 'Pending', label: 'Pending', color: 'text-yellow-600' },
        { value: 'Cancelled', label: 'Cancelled', color: 'text-gray-600' }
    ],
    partnerTypes: [
        { value: 'University', label: 'University', color: 'text-blue-600' },
        { value: 'Government', label: 'Government', color: 'text-green-600' },
        { value: 'NGO', label: 'NGO', color: 'text-purple-600' },
        { value: 'Private', label: 'Private', color: 'text-orange-600' }
    ]
};

// Main MOU/MOA Management Class
class MouMoaManager {
    constructor() {
        this.currentFilters = {
            page: 1,
            limit: MouMoaConfig.pagination.defaultLimit,
            search: '',
            status: '',
            partner_type: '',
            sort_by: 'end_date',
            sort_order: 'ASC'
        };
        this.currentDocuments = [];
        this.selectedDocuments = new Set();
        this.selectAllChecked = false;
        this.isLoading = false;
    }

    /**
     * Initialize the MOU/MOA manager
     */
    async initialize() {
        try {
            this.initializeEventListeners();
            await this.loadDocuments();
            await this.loadStats();
            this.setupExpirationMonitoring();
        } catch (error) {
            console.error('Failed to initialize MOU/MOA manager:', error);
            this.showNotification('Failed to initialize MOU/MOA system', 'error');
        }
    }

    /**
     * Initialize event listeners
     */
    initializeEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.currentFilters.search = e.target.value;
                this.currentFilters.page = 1;
                this.loadDocuments();
            }, 300));
        }

        // Filter functionality
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.currentFilters.status = e.target.value;
                this.currentFilters.page = 1;
                this.loadDocuments();
            });
        }

        const partnerFilter = document.getElementById('partner-filter');
        if (partnerFilter) {
            partnerFilter.addEventListener('change', (e) => {
                this.currentFilters.partner_type = e.target.value;
                this.currentFilters.page = 1;
                this.loadDocuments();
            });
        }

        // Upload functionality
        const uploadForm = document.getElementById('upload-form');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }

        // Bulk actions
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.toggleSelectAll(e.target.checked);
            });
        }

        const bulkDeleteBtn = document.getElementById('bulk-delete');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', () => this.handleBulkDelete());
        }

        // Sync functionality
        const syncBtn = document.getElementById('sync-mous-btn');
        if (syncBtn) {
            syncBtn.addEventListener('click', () => this.syncFromDocuments());
        }
    }

    /**
     * Load documents with current filters
     */
    async loadDocuments() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingState();

        try {
            const params = new URLSearchParams(this.currentFilters);
            const response = await fetch(`${MouMoaConfig.api.list}&${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.currentDocuments = data.mous || [];
                this.renderDocuments();
                this.updatePagination(data.pagination || {});
            } else {
                throw new Error(data.message || 'Failed to load documents');
            }
        } catch (error) {
            console.error('Error loading documents:', error);
            this.showNotification('Failed to load MOU/MOA documents', 'error');
            this.currentDocuments = [];
            this.renderDocuments();
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }

    /**
     * Render documents in the table
     */
    renderDocuments() {
        const tbody = document.getElementById('documents-table-body');
        if (!tbody) return;

        if (this.currentDocuments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No MOU/MOA documents found</td></tr>';
            return;
        }

        tbody.innerHTML = this.currentDocuments.map(doc => this.renderDocumentRow(doc)).join('');
        this.updateSelectAllCheckbox();
    }

    /**
     * Render a single document row
     */
    renderDocumentRow(doc) {
        const expirationStatus = this.getExpirationStatus(doc.end_date);
        const statusColor = MouMoaConfig.statusTypes.find(s => s.value === doc.status)?.color || 'text-gray-600';
        const partnerColor = MouMoaConfig.partnerTypes.find(p => p.value === doc.partner_type)?.color || 'text-gray-600';
        
        return `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <input type="checkbox" class="document-checkbox" value="${doc.id}" 
                           ${this.selectedDocuments.has(doc.id) ? 'checked' : ''}>
                </td>
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900">${this.escapeHtml(doc.title)}</div>
                    <div class="text-sm text-gray-500">${this.escapeHtml(doc.partner_name)}</div>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColor}">
                        ${this.escapeHtml(doc.status)}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${partnerColor}">
                        ${this.escapeHtml(doc.partner_type || 'Unknown')}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-900">
                    ${this.formatDate(doc.end_date)}
                    ${expirationStatus !== 'active' ? `<span class="ml-2 text-xs ${this.getExpirationColor(expirationStatus)}">(${expirationStatus})</span>` : ''}
                </td>
                <td class="px-4 py-3 text-sm text-gray-500">
                    ${this.formatDate(doc.upload_date)}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center space-x-2">
                        <button onclick="mouMoaManager.viewDocument(${doc.id})" 
                                class="text-blue-600 hover:text-blue-800 text-sm">View</button>
                        <button onclick="mouMoaManager.deleteDocument(${doc.id})" 
                                class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Get expiration status for a document
     */
    getExpirationStatus(endDate) {
        if (!endDate) return 'unknown';
        
        const today = new Date();
        const expiration = new Date(endDate);
        const daysUntilExpiration = Math.ceil((expiration - today) / (1000 * 60 * 60 * 24));
        
        if (daysUntilExpiration < 0) return 'expired';
        if (daysUntilExpiration <= 7) return 'critical';
        if (daysUntilExpiration <= 30) return 'warning';
        return 'active';
    }

    /**
     * Get expiration status color
     */
    getExpirationColor(status) {
        const colors = {
            'expired': 'text-red-600',
            'critical': 'text-red-500',
            'warning': 'text-yellow-600',
            'active': 'text-green-600',
            'unknown': 'text-gray-500'
        };
        return colors[status] || 'text-gray-500';
    }

    /**
     * Setup expiration monitoring
     */
    setupExpirationMonitoring() {
        // Check for expiring MOUs every hour
        setInterval(() => {
            this.checkExpiringMous();
        }, 60 * 60 * 1000);
        
        // Initial check
        this.checkExpiringMous();
    }

    /**
     * Check for expiring MOUs and show notifications
     */
    async checkExpiringMous() {
        try {
            const response = await fetch('api/mous.php?action=get_expiring');
            const data = await response.json();
            
            if (data.success && data.expiring_mous) {
                data.expiring_mous.forEach(mou => {
                    this.showExpirationNotification(mou);
                });
            }
        } catch (error) {
            console.error('Failed to check expiring MOUs:', error);
        }
    }

    /**
     * Show expiration notification
     */
    showExpirationNotification(mou) {
        const daysRemaining = mou.days_until_expiration;
        let message = '';
        let type = 'warning';
        
        if (daysRemaining <= 0) {
            message = `MOU "${mou.title}" has expired!`;
            type = 'error';
        } else if (daysRemaining <= 7) {
            message = `MOU "${mou.title}" expires in ${daysRemaining} days!`;
            type = 'error';
        } else if (daysRemaining <= 30) {
            message = `MOU "${mou.title}" expires in ${daysRemaining} days`;
            type = 'warning';
        }
        
        if (message) {
            this.showNotification(message, type);
        }
    }

    /**
     * Handle file upload
     */
    async handleUpload(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const fileInput = event.target.querySelector('input[type="file"]');
        
        if (!fileInput.files.length) {
            this.showNotification('Please select a file to upload', 'error');
            return;
        }
        
        this.showLoadingState();
        
        try {
            const response = await fetch(MouMoaConfig.api.add, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('MOU/MOA uploaded successfully', 'success');
                event.target.reset();
                await this.loadDocuments();
                await this.loadStats();
            } else {
                throw new Error(data.message || 'Upload failed');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showNotification('Failed to upload MOU/MOA', 'error');
        } finally {
            this.hideLoadingState();
        }
    }

    /**
     * Sync MOUs from documents
     */
    async syncFromDocuments() {
        const syncBtn = document.getElementById('sync-mous-btn');
        if (!syncBtn) return;
        
        const originalText = syncBtn.innerHTML;
        
        // Show loading state
        syncBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Syncing...';
        syncBtn.disabled = true;
        
        try {
            const response = await fetch(MouMoaConfig.api.sync);
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(`Synced ${data.synced_count || 0} MOUs from documents`, 'success');
                await this.loadDocuments();
                await this.loadStats();
            } else {
                throw new Error(data.message || 'Sync failed');
            }
        } catch (error) {
            console.error('Sync error:', error);
            this.showNotification('Failed to sync MOUs from documents', 'error');
        } finally {
            syncBtn.innerHTML = originalText;
            syncBtn.disabled = false;
        }
    }

    /**
     * Load statistics
     */
    async loadStats() {
        try {
            const response = await fetch('api/mous.php?action=get_stats');
            const data = await response.json();
            
            if (data.success) {
                this.updateStatsDisplay(data.stats);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    /**
     * Update statistics display
     */
    updateStatsDisplay(stats) {
        const elements = {
            'total-mous': stats.total_mous || 0,
            'active-mous': stats.active_mous || 0,
            'expiring-mous': stats.expiring_mous || 0,
            'expired-mous': stats.expired_mous || 0
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }

    /**
     * Utility functions
     */
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

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString();
    }

    showLoadingState() {
        const loadingElement = document.getElementById('loading-indicator');
        if (loadingElement) {
            loadingElement.style.display = 'block';
        }
    }

    hideLoadingState() {
        const loadingElement = document.getElementById('loading-indicator');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }

    showNotification(message, type = 'info') {
        if (window.lilacNotifications && window.lilacNotifications.show) {
            window.lilacNotifications.show(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    // Document action methods
    viewDocument(id) {
        const doc = this.currentDocuments.find(d => d.id == id);
        if (!doc) {
            this.showNotification('Document not found', 'error');
            return;
        }

        // Use the shared document viewer
        if (window.documentViewer) {
            let filePath = doc.file_path || doc.filename || doc.file_name;
            
            // Ensure file path includes uploads directory if not already present
            if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                filePath = `uploads/${filePath}`;
            }
            
            const fileExtension = this.getFileExtension(filePath);
            const documentType = this.getDocumentTypeFromExtension(fileExtension);
            const title = doc.partner_name || doc.document_name || 'MOU/MOA Document';
            
            console.log('MOU/MOA Document Viewer Debug:', {
                doc: doc,
                filePath: filePath,
                fileExtension: fileExtension,
                documentType: documentType,
                title: title
            });
            
            window.documentViewer.showDocument(filePath, documentType, title);
        } else {
            this.showNotification('Document viewer not available', 'error');
        }
    }

    deleteDocument(id) {
        const doc = this.currentDocuments.find(d => d.id == id);
        if (!doc) {
            this.showNotification('Document not found', 'error');
            return;
        }

        // Store the document ID for confirmation
        this.documentToDelete = id;
        
        // Show the delete confirmation modal
        const modal = document.getElementById('delete-mou-modal');
        if (modal) {
            modal.classList.remove('hidden');
        } else {
            // Fallback to browser confirm if modal not found
            if (confirm('Are you sure you want to delete this MOU/MOA?')) {
                this.confirmDeleteMou();
            }
        }
    }

    confirmDeleteMou() {
        if (!this.documentToDelete) return;

        const docId = this.documentToDelete;
        this.documentToDelete = null;

        // Close modal
        this.closeDeleteModal();

        // Perform delete via API
        this.performDelete(docId);
    }

    closeDeleteModal() {
        const modal = document.getElementById('delete-mou-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
        this.documentToDelete = null;
    }

    async performDelete(docId) {
        try {
            const response = await fetch(`api/mous.php?action=delete&id=${docId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('MOU/MOA deleted successfully', 'success');
                this.loadDocuments(); // Reload the list
            } else {
                throw new Error(data.message || 'Failed to delete document');
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            this.showNotification('Failed to delete MOU/MOA', 'error');
        }
    }

    getFileExtension(filename) {
        if (!filename) return '';
        return filename.split('.').pop().toLowerCase();
    }

    getDocumentTypeFromExtension(extension) {
        const typeMap = {
            'pdf': 'pdf',
            'jpg': 'image',
            'jpeg': 'image',
            'png': 'image',
            'gif': 'image',
            'webp': 'image',
            'bmp': 'image',
            'svg': 'image',
            'txt': 'text',
            'doc': 'unknown',
            'docx': 'unknown'
        };
        return typeMap[extension] || 'unknown';
    }

    toggleSelectAll(checked) {
        this.selectAllChecked = checked;
        this.selectedDocuments.clear();
        
        if (checked) {
            this.currentDocuments.forEach(doc => {
                this.selectedDocuments.add(doc.id);
            });
        }
        
        this.updateSelectAllCheckbox();
        this.renderDocuments();
    }

    updateSelectAllCheckbox() {
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = this.selectAllChecked;
            selectAllCheckbox.indeterminate = this.selectedDocuments.size > 0 && this.selectedDocuments.size < this.currentDocuments.length;
        }
    }

    handleBulkDelete() {
        if (this.selectedDocuments.size === 0) {
            this.showNotification('Please select documents to delete', 'warning');
            return;
        }
        
        if (confirm(`Are you sure you want to delete ${this.selectedDocuments.size} selected MOU/MOA documents?`)) {
            console.log('Bulk delete:', Array.from(this.selectedDocuments));
            this.showNotification('Bulk delete functionality coming soon', 'info');
        }
    }

    updatePagination(pagination) {
        // Update pagination controls
        const paginationElement = document.getElementById('pagination');
        if (paginationElement && pagination.total_pages > 1) {
            paginationElement.innerHTML = this.renderPagination(pagination);
        }
    }

    renderPagination(pagination) {
        let html = '<div class="flex items-center justify-between">';
        
        // Previous button
        if (pagination.current_page > 1) {
            html += `<button onclick="mouMoaManager.goToPage(${pagination.current_page - 1})" 
                     class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                     Previous</button>`;
        }
        
        // Page numbers
        html += '<div class="flex space-x-1">';
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === pagination.current_page) {
                html += `<span class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md">${i}</span>`;
            } else {
                html += `<button onclick="mouMoaManager.goToPage(${i})" 
                         class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                         ${i}</button>`;
            }
        }
        html += '</div>';
        
        // Next button
        if (pagination.current_page < pagination.total_pages) {
            html += `<button onclick="mouMoaManager.goToPage(${pagination.current_page + 1})" 
                     class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                     Next</button>`;
        }
        
        html += '</div>';
        return html;
    }

    goToPage(page) {
        this.currentFilters.page = page;
        this.loadDocuments();
    }
}

// Initialize the manager when DOM is loaded
let mouMoaManager;
document.addEventListener('DOMContentLoaded', function() {
    mouMoaManager = new MouMoaManager();
    mouMoaManager.initialize();
});

// Global functions for modal interactions
function confirmDeleteMou() {
    if (mouMoaManager) {
        mouMoaManager.confirmDeleteMou();
    }
}

function closeDeleteModal() {
    if (mouMoaManager) {
        mouMoaManager.closeDeleteModal();
    }
}
