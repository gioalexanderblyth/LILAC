/**
 * PDF Text Extractor using PDF.js
 * Client-side PDF text extraction for better content analysis
 */

class PDFTextExtractor {
    constructor() {
        this.pdfjsLib = null;
        this.isInitialized = false;
    }
    
    /**
     * Initialize PDF.js library
     */
    async initialize() {
        if (this.isInitialized) return;
        
        try {
            // Load PDF.js from CDN
            if (typeof pdfjsLib === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                script.onload = () => {
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                    this.pdfjsLib = pdfjsLib;
                    this.isInitialized = true;
                };
                document.head.appendChild(script);
            } else {
                this.pdfjsLib = pdfjsLib;
                this.isInitialized = true;
            }
        } catch (error) {
            console.error('Failed to initialize PDF.js:', error);
        }
    }
    
    /**
     * Extract text from PDF file
     */
    async extractTextFromPDF(file) {
        await this.initialize();
        
        if (!this.isInitialized || !this.pdfjsLib) {
            throw new Error('PDF.js not initialized');
        }
        
        try {
            const arrayBuffer = await file.arrayBuffer();
            const pdf = await this.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            
            let fullText = '';
            
            // Extract text from all pages
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                const page = await pdf.getPage(pageNum);
                const textContent = await page.getTextContent();
                
                // Combine text items
                const pageText = textContent.items
                    .map(item => item.str)
                    .join(' ');
                
                fullText += pageText + '\n';
            }
            
            return fullText.trim();
            
        } catch (error) {
            console.error('PDF text extraction error:', error);
            throw error;
        }
    }
    
    /**
     * Extract text from multiple file types
     */
    async extractTextFromFile(file) {
        const fileType = file.type.toLowerCase();
        const fileName = file.name.toLowerCase();
        
        try {
            if (fileType === 'application/pdf' || fileName.endsWith('.pdf')) {
                return await this.extractTextFromPDF(file);
            } else if (fileType === 'text/plain' || fileName.endsWith('.txt')) {
                return await this.extractTextFromTextFile(file);
            } else {
                // For other file types, return filename-based content
                return this.extractFromFilename(file.name);
            }
        } catch (error) {
            console.warn('Text extraction failed, using filename fallback:', error);
            return this.extractFromFilename(file.name);
        }
    }
    
    /**
     * Extract text from text files
     */
    async extractTextFromTextFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = (e) => reject(e);
            reader.readAsText(file);
        });
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
}

// Global instance
window.pdfTextExtractor = new PDFTextExtractor();
