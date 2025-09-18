/**
 * Enhanced Document Upload with Client-Side Content Extraction
 * Handles PDF text extraction and improved content analysis
 */

class EnhancedDocumentUpload {
    constructor() {
        this.pdfExtractor = window.pdfTextExtractor;
        this.uploadEndpoint = 'api/documents.php';
    }
    
    /**
     * Handle file upload with enhanced content extraction
     */
    async handleFileUpload(file, formData) {
        try {
            // Extract content on client-side first
            const extractedContent = await this.extractFileContent(file);
            
            // Add extracted content to form data
            formData.append('extracted_content', extractedContent);
            formData.append('content_length', extractedContent.length);
            
            // Upload file with extracted content
            const response = await fetch(this.uploadEndpoint, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success with content info
                this.showUploadSuccess(result, extractedContent);
                return result;
            } else {
                throw new Error(result.message || 'Upload failed');
            }
            
        } catch (error) {
            console.error('Upload error:', error);
            this.showUploadError(error.message);
            throw error;
        }
    }
    
    /**
     * Extract content from various file types
     */
    async extractFileContent(file) {
        try {
            if (this.pdfExtractor) {
                return await this.pdfExtractor.extractTextFromFile(file);
            } else {
                // Fallback to filename-based extraction
                return this.extractFromFilename(file.name);
            }
        } catch (error) {
            console.warn('Content extraction failed:', error);
            return this.extractFromFilename(file.name);
        }
    }
    
    /**
     * Extract content from filename
     */
    extractFromFilename(filename) {
        const name = filename.replace(/\.[^/.]+$/, ""); // Remove extension
        const cleanName = name.replace(/[_\-]/g, ' '); // Replace underscores/hyphens with spaces
        
        // Add keywords based on filename patterns
        const keywords = [];
        const lowerName = cleanName.toLowerCase();
        
        if (lowerName.includes('mou')) keywords.push('memorandum of understanding');
        if (lowerName.includes('moa')) keywords.push('memorandum of agreement');
        if (lowerName.includes('agreement')) keywords.push('agreement');
        if (lowerName.includes('partnership')) keywords.push('partnership');
        if (lowerName.includes('international')) keywords.push('international');
        if (lowerName.includes('education')) keywords.push('education');
        if (lowerName.includes('research')) keywords.push('research');
        if (lowerName.includes('collaboration')) keywords.push('collaboration');
        
        let content = cleanName;
        if (keywords.length > 0) {
            content += ' ' + keywords.join(' ');
        }
        
        return content;
    }
    
    /**
     * Show upload success with content information
     */
    showUploadSuccess(result, extractedContent) {
        const contentLength = extractedContent.length;
        const preview = extractedContent.substring(0, 200);
        
        let message = `Document uploaded successfully!`;
        if (contentLength > 0) {
            message += `\n\nContent extracted: ${contentLength} characters`;
            if (contentLength > 200) {
                message += `\n\nPreview: ${preview}...`;
            } else {
                message += `\n\nContent: ${extractedContent}`;
            }
        }
        
        // Show notification
        if (window.showNotification) {
            window.showNotification(message, 'success');
        } else {
            alert(message);
        }
        
        // Log for debugging
        console.log('Upload successful:', {
            documentId: result.document_id,
            contentLength: contentLength,
            preview: preview
        });
    }
    
    /**
     * Show upload error
     */
    showUploadError(message) {
        if (window.showNotification) {
            window.showNotification(`Upload failed: ${message}`, 'error');
        } else {
            alert(`Upload failed: ${message}`);
        }
    }
    
    /**
     * Process multiple files
     */
    async processMultipleFiles(files) {
        const results = [];
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('file', file);
            
            try {
                const result = await this.handleFileUpload(file, formData);
                results.push({ file: file.name, success: true, result });
            } catch (error) {
                results.push({ file: file.name, success: false, error: error.message });
            }
        }
        
        return results;
    }
}

// Global instance
window.enhancedDocumentUpload = new EnhancedDocumentUpload();
