<?php
/**
 * Shared Document Viewer Component
 * 
 * A reusable PHP component for displaying document viewer modals.
 * This component can be included in multiple pages to ensure consistency
 * and reduce code duplication.
 */
?>

<!-- Document Viewer Modal -->
<div id="document-viewer-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[80] hidden">
    <div class="bg-white dark:bg-[#2a2f3a] rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-600">
            <h3 id="document-viewer-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                Document Viewer
            </h3>
            <button 
                data-modal-close="document-viewer-overlay"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl font-bold"
                aria-label="Close document viewer"
            >
                ×
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
            <!-- Document Content Container -->
            <div id="document-viewer-content" class="w-full">
                <!-- PDF Viewer -->
                <div id="pdf-viewer-container" class="hidden">
                    <canvas id="pdf-canvas" class="w-full border border-gray-300 dark:border-gray-600 rounded"></canvas>
                    <div class="flex justify-center mt-4 space-x-2">
                        <button 
                            id="pdf-prev-page" 
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                            disabled
                        >
                            Previous
                        </button>
                        <span id="pdf-page-info" class="px-4 py-2 text-gray-700 dark:text-gray-300">
                            Page 1 of 1
                        </span>
                        <button 
                            id="pdf-next-page" 
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                            disabled
                        >
                            Next
                        </button>
                    </div>
                </div>
                
                <!-- Image Viewer -->
                <div id="image-viewer-container" class="hidden">
                    <img id="image-viewer" class="w-full h-auto max-h-[70vh] object-contain border border-gray-300 dark:border-gray-600 rounded" alt="Document image">
                </div>
                
                <!-- Text Viewer -->
                <div id="text-viewer-container" class="hidden">
                    <div id="text-viewer" class="w-full p-4 bg-gray-50 dark:bg-gray-800 rounded border border-gray-300 dark:border-gray-600 whitespace-pre-wrap font-mono text-sm">
                        <!-- Text content will be loaded here -->
                    </div>
                </div>
                
                <!-- Error Message -->
                <div id="document-viewer-error" class="hidden text-center py-8">
                    <div id="document-viewer-error-message" class="text-gray-600 dark:text-gray-400">
                        Unable to load the document. Please try again.
                    </div>
                </div>
                
                <!-- Loading Spinner -->
                <div id="document-viewer-loading" class="hidden text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <div class="mt-2 text-gray-600 dark:text-gray-400">Loading document...</div>
                </div>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="flex items-center justify-between p-4 border-t border-gray-200 dark:border-gray-600">
            <div class="flex space-x-2">
                <button 
                    id="document-download-btn" 
                    class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 hidden"
                >
                    Download
                </button>
                <button 
                    id="document-print-btn" 
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 hidden"
                >
                    Print
                </button>
            </div>
            <button 
                data-modal-close="document-viewer-overlay"
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
            >
                Close
            </button>
        </div>
    </div>
</div>

<!-- Document Viewer JavaScript -->
<script>
/**
 * Document Viewer JavaScript
 * 
 * Handles the functionality of the shared document viewer modal.
 * This script should be included on pages that use the document viewer.
 */
class DocumentViewer {
    constructor() {
        this.currentDocument = null;
        this.pdfDoc = null;
        this.currentPage = 1;
        this.totalPages = 0;
        this.scale = 1.5;
        
        this.initializeEventListeners();
    }
    
    /**
     * Initialize event listeners for the document viewer
     */
    initializeEventListeners() {
        // PDF navigation buttons
        const prevBtn = document.getElementById('pdf-prev-page');
        const nextBtn = document.getElementById('pdf-next-page');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.previousPage());
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextPage());
        }
        
        // Download button
        const downloadBtn = document.getElementById('document-download-btn');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => this.downloadDocument());
        }
        
        // Print button
        const printBtn = document.getElementById('document-print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', () => this.printDocument());
        }
    }
    
    /**
     * Show the document viewer modal
     * 
     * @param {string} documentPath The path to the document
     * @param {string} documentType The type of document (pdf, image, text)
     * @param {string} documentTitle The title of the document
     */
    showDocument(documentPath, documentType, documentTitle = 'Document') {
        this.currentDocument = documentPath;
        
        // Update modal title
        const titleElement = document.getElementById('document-viewer-title');
        if (titleElement) {
            titleElement.textContent = documentTitle;
        }
        
        // Show loading state
        this.showLoading();
        
        // Show modal
        const modal = document.getElementById('document-viewer-overlay');
        if (modal) {
            modal.classList.remove('hidden');
        }
        
        // Load document based on type
        const docType = documentType ? documentType.toLowerCase() : 'unknown';
        switch (docType) {
            case 'pdf':
                this.loadPDF(documentPath);
                break;
            case 'image':
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                this.loadImage(documentPath);
                break;
            case 'text':
            case 'txt':
                this.loadText(documentPath);
                break;
            case 'unknown':
                this.showErrorWithDownload('This file type cannot be displayed in the viewer. Please download this file to view it.', documentPath);
                break;
            default:
                this.showError('Unsupported document type: ' + (documentType || 'unknown'));
        }
    }
    
    /**
     * Show loading state
     */
    showLoading() {
        this.hideAllViewers();
        const loading = document.getElementById('document-viewer-loading');
        if (loading) {
            loading.classList.remove('hidden');
        }
    }
    
    /**
     * Hide all viewer containers
     */
    hideAllViewers() {
        const containers = [
            'pdf-viewer-container',
            'image-viewer-container',
            'text-viewer-container',
            'document-viewer-error',
            'document-viewer-loading'
        ];
        
        containers.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.classList.add('hidden');
            }
        });
    }
    
    /**
     * Load and display a PDF document
     * 
     * @param {string} pdfPath The path to the PDF file
     */
    async loadPDF(pdfPath) {
        try {
            // Load PDF.js lazily if not already loaded
            if (typeof pdfjsLib === 'undefined') {
                if (window.lazyLoader && typeof window.lazyLoader.loadPDFJS === 'function') {
                    await window.lazyLoader.loadPDFJS();
                } else {
                    this.showError('PDF.js library is not available');
                    return;
                }
            }
            
            // Load PDF document
            const pdf = await pdfjsLib.getDocument(pdfPath).promise;
            this.pdfDoc = pdf;
            this.totalPages = pdf.numPages;
            this.currentPage = 1;
            
            // Show PDF viewer
            this.hideAllViewers();
            const container = document.getElementById('pdf-viewer-container');
            if (container) {
                container.classList.remove('hidden');
            }
            
            // Render first page
            await this.renderPage(1);
            
            // Update page info
            this.updatePageInfo();
            
            // Show download button
            this.showDownloadButton();
            
        } catch (error) {
            console.error('Error loading PDF:', error);
            this.showError('Failed to load PDF document: ' + error.message);
        }
    }
    
    /**
     * Render a specific page of the PDF
     * 
     * @param {number} pageNum The page number to render
     */
    async renderPage(pageNum) {
        if (!this.pdfDoc) return;
        
        try {
            const page = await this.pdfDoc.getPage(pageNum);
            const canvas = document.getElementById('pdf-canvas');
            const context = canvas.getContext('2d');
            
            const viewport = page.getViewport({ scale: this.scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };
            
            await page.render(renderContext).promise;
        } catch (error) {
            console.error('Error rendering PDF page:', error);
            this.showError('Failed to render PDF page: ' + error.message);
        }
    }
    
    /**
     * Go to previous page
     */
    async previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            await this.renderPage(this.currentPage);
            this.updatePageInfo();
        }
    }
    
    /**
     * Go to next page
     */
    async nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            await this.renderPage(this.currentPage);
            this.updatePageInfo();
        }
    }
    
    /**
     * Update page information display
     */
    updatePageInfo() {
        const pageInfo = document.getElementById('pdf-page-info');
        if (pageInfo) {
            pageInfo.textContent = `Page ${this.currentPage} of ${this.totalPages}`;
        }
        
        // Update navigation buttons
        const prevBtn = document.getElementById('pdf-prev-page');
        const nextBtn = document.getElementById('pdf-next-page');
        
        if (prevBtn) {
            prevBtn.disabled = this.currentPage <= 1;
        }
        
        if (nextBtn) {
            nextBtn.disabled = this.currentPage >= this.totalPages;
        }
    }
    
    /**
     * Load and display an image
     * 
     * @param {string} imagePath The path to the image file
     */
    loadImage(imagePath) {
        try {
            const img = document.getElementById('image-viewer');
            if (img) {
                img.onload = () => {
                    this.hideAllViewers();
                    const container = document.getElementById('image-viewer-container');
                    if (container) {
                        container.classList.remove('hidden');
                    }
                    this.showDownloadButton();
                };
                
                img.onerror = () => {
                    this.showError('Failed to load image');
                };
                
                img.src = imagePath;
            }
        } catch (error) {
            console.error('Error loading image:', error);
            this.showError('Failed to load image: ' + error.message);
        }
    }
    
    /**
     * Load and display text content
     * 
     * @param {string} textPath The path to the text file
     */
    async loadText(textPath) {
        try {
            const response = await fetch(textPath);
            if (!response.ok) {
                throw new Error('Failed to fetch text file');
            }
            
            const text = await response.text();
            
            this.hideAllViewers();
            const container = document.getElementById('text-viewer-container');
            const textElement = document.getElementById('text-viewer');
            
            if (container && textElement) {
                textElement.textContent = text;
                container.classList.remove('hidden');
            }
            
            this.showDownloadButton();
            
        } catch (error) {
            console.error('Error loading text:', error);
            this.showError('Failed to load text file: ' + error.message);
        }
    }
    
    /**
     * Show error message
     * 
     * @param {string} message The error message to display
     */
    showError(message) {
        this.hideAllViewers();
        const errorContainer = document.getElementById('document-viewer-error');
        const errorMessage = document.getElementById('document-viewer-error-message');
        
        if (errorContainer && errorMessage) {
            errorMessage.textContent = message;
            errorContainer.classList.remove('hidden');
        }
    }
    
    /**
     * Show error message with download button
     * 
     * @param {string} message The error message to display
     * @param {string} documentPath The path to the document for download
     */
    showErrorWithDownload(message, documentPath) {
        this.hideAllViewers();
        const errorContainer = document.getElementById('document-viewer-error');
        const errorMessage = document.getElementById('document-viewer-error-message');
        
        if (errorContainer && errorMessage) {
            errorMessage.innerHTML = `
                <div class="text-center">
                    <div class="text-gray-600 dark:text-gray-400 mb-4">${message}</div>
                    <button onclick="window.documentViewer.downloadDocument()" 
                            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                        Download File
                    </button>
                </div>
            `;
            errorContainer.classList.remove('hidden');
            
            // Store the document path for download
            this.currentDocument = documentPath;
        }
    }
    
    /**
     * Show download button
     */
    showDownloadButton() {
        const downloadBtn = document.getElementById('document-download-btn');
        if (downloadBtn) {
            downloadBtn.classList.remove('hidden');
        }
    }
    
    /**
     * Download the current document
     */
    downloadDocument() {
        if (this.currentDocument) {
            const link = document.createElement('a');
            link.href = this.currentDocument;
            link.download = this.currentDocument.split('/').pop();
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
    
    /**
     * Print the current document
     */
    printDocument() {
        if (this.currentDocument) {
            window.open(this.currentDocument, '_blank');
        }
    }
    
    /**
     * Open document in new tab (for unsupported types like DOCX)
     * 
     * @param {string} documentPath The path to the document
     */
    openInNewTab(documentPath) {
        try {
            // Show a message that the document will download
            this.hideAllViewers();
            const contentEl = document.getElementById('document-viewer-content');
            if (contentEl) {
                contentEl.innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-blue-500 text-lg font-semibold mb-2">Opening Document</div>
                        <div class="text-gray-600 dark:text-gray-400 mb-4">
                            This document type cannot be displayed inline.<br>
                            It will open in a new tab or download automatically.
                        </div>
                        <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                    </div>
                `;
            }
            
            // Open in new tab (will trigger download for DOCX files)
            window.open(documentPath, '_blank');
            
            // Close the modal after a short delay
            setTimeout(() => {
                this.close();
            }, 2000);
            
        } catch (error) {
            console.error('Error opening document in new tab:', error);
            this.showError('Failed to open document: ' + error.message);
        }
    }
    
    /**
     * Close the document viewer
     */
    close() {
        const modal = document.getElementById('document-viewer-overlay');
        if (modal) {
            modal.classList.add('hidden');
        }
        
        // Reset state
        this.currentDocument = null;
        this.pdfDoc = null;
        this.currentPage = 1;
        this.totalPages = 0;
    }
}

// Initialize document viewer when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.documentViewer = new DocumentViewer();
});

// Global function to show document (for backward compatibility)
function showDocumentViewer(documentPath, documentType, documentTitle) {
    if (window.documentViewer) {
        window.documentViewer.showDocument(documentPath, documentType, documentTitle);
    }
}
</script>