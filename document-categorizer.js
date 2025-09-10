/**
 * Document Categorization System
 * Automatically categorizes documents based on filename and content analysis
 */

class DocumentCategorizer {
    constructor() {
        this.rules = {
            'MOUs & MOAs': {
                keywords: ['mou', 'moa', 'agreement', 'memorandum', 'partnership', 'collaboration'],
                filePatterns: [/mou/i, /moa/i, /agreement/i, /partnership/i],
                priority: 1
            },
            'Events & Activities': {
                keywords: ['minutes', 'agenda', 'meeting', 'event', 'activity', 'conference', 'seminar', 'workshop'],
                filePatterns: [/minutes/i, /agenda/i, /meeting/i, /event/i, /activity/i, /conference/i, /seminar/i, /workshop/i],
                datePatterns: [/\d{1,2}-\d{1,2}-\d{2,4}/, /\d{4}-\d{1,2}-\d{1,2}/],
                priority: 2
            },
            'Awards Progress': {
                keywords: ['award', 'recognition', 'progress', 'achievement', 'certificate', 'honor', 'prize'],
                filePatterns: [/award/i, /recognition/i, /progress/i, /achievement/i, /certificate/i, /honor/i, /prize/i],
                priority: 3
            },
            'Templates': {
                keywords: ['template', 'form', 'blank', 'sample', 'example', 'draft'],
                filePatterns: [/template/i, /form/i, /blank/i, /sample/i, /example/i, /draft/i],
                priority: 4
            },
            'Registrar Files': {
                keywords: ['transcript', 'registrar', 'student records', 'enrollment', 'grade', 'academic record'],
                filePatterns: [/transcript/i, /registrar/i, /student.*record/i, /enrollment/i, /grade/i, /academic.*record/i],
                priority: 5
            }
        };
    }

    /**
     * Categorize a document based on filename and content
     * @param {File} file - The file to categorize
     * @param {string} content - Optional extracted content from the file
     * @returns {Promise<Object>} - Categorization result
     */
    async categorizeDocument(file, content = '') {
        const filename = file.name.toLowerCase();
        const fileExtension = this.getFileExtension(file.name);
        
        // Combine filename and content for analysis
        const textToAnalyze = `${filename} ${content}`.toLowerCase();
        
        let bestMatch = null;
        let highestScore = 0;
        
        // Check each category
        for (const [category, rule] of Object.entries(this.rules)) {
            let score = 0;
            
            // Check filename patterns
            for (const pattern of rule.filePatterns) {
                if (pattern.test(filename)) {
                    score += 10; // High score for filename matches
                }
            }
            
            // Check keywords in filename and content
            for (const keyword of rule.keywords) {
                const keywordRegex = new RegExp(`\\b${keyword}\\b`, 'i');
                if (keywordRegex.test(filename)) {
                    score += 8; // High score for filename keyword matches
                }
                if (content && keywordRegex.test(content)) {
                    score += 5; // Medium score for content keyword matches
                }
            }
            
            // Check for date patterns (especially for Events & Activities)
            if (rule.datePatterns) {
                for (const datePattern of rule.datePatterns) {
                    if (datePattern.test(filename)) {
                        score += 3; // Medium score for date patterns
                    }
                }
            }
            
            // Apply priority weighting
            score = score * (1 / rule.priority);
            
            if (score > highestScore) {
                highestScore = score;
                bestMatch = {
                    category: category,
                    score: score,
                    confidence: Math.min(score / 10, 1) // Normalize to 0-1
                };
            }
        }
        
        // If no good match found, use file extension as fallback
        if (!bestMatch || bestMatch.confidence < 0.3) {
            bestMatch = this.categorizeByExtension(fileExtension);
        }
        
        return bestMatch;
    }

    /**
     * Categorize by file extension as fallback
     * @param {string} extension - File extension
     * @returns {Object} - Categorization result
     */
    categorizeByExtension(extension) {
        const extensionMap = {
            'pdf': 'Templates',
            'doc': 'Templates',
            'docx': 'Templates',
            'xls': 'Templates',
            'xlsx': 'Templates',
            'ppt': 'Templates',
            'pptx': 'Templates'
        };
        
        const category = extensionMap[extension] || 'Templates';
        return {
            category: category,
            score: 1,
            confidence: 0.1
        };
    }

    /**
     * Extract text content from file for analysis
     * @param {File} file - The file to analyze
     * @returns {Promise<string>} - Extracted text content
     */
    async extractContent(file) {
        const extension = this.getFileExtension(file.name);
        
        try {
            if (extension === 'pdf') {
                return await this.extractFromPDF(file);
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                return await this.extractFromImage(file);
            } else if (['txt', 'md'].includes(extension)) {
                return await this.extractFromText(file);
            }
        } catch (error) {
            console.warn('Failed to extract content from file:', error);
        }
        
        return '';
    }

    /**
     * Extract text from PDF using PDF.js
     * @param {File} file - PDF file
     * @returns {Promise<string>} - Extracted text
     */
    async extractFromPDF(file) {
        if (!window.pdfjsLib) {
            throw new Error('PDF.js not available');
        }
        
        const arrayBuffer = await file.arrayBuffer();
        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        
        let fullText = '';
        const maxPages = Math.min(pdf.numPages, 5); // Limit to first 5 pages for performance
        
        for (let i = 1; i <= maxPages; i++) {
            const page = await pdf.getPage(i);
            const content = await page.getTextContent();
            const text = content.items.map(item => item.str).join(' ');
            fullText += text + ' ';
        }
        
        return fullText;
    }

    /**
     * Extract text from image using OCR
     * @param {File} file - Image file
     * @returns {Promise<string>} - Extracted text
     */
    async extractFromImage(file) {
        if (!window.Tesseract) {
            throw new Error('Tesseract.js not available');
        }
        
        const { data: { text } } = await Tesseract.recognize(file, 'eng');
        return text;
    }

    /**
     * Extract text from text file
     * @param {File} file - Text file
     * @returns {Promise<string>} - File content
     */
    async extractFromText(file) {
        return await file.text();
    }

    /**
     * Get file extension
     * @param {string} filename - File name
     * @returns {string} - File extension
     */
    getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    /**
     * Add custom rule to the categorizer
     * @param {string} category - Category name
     * @param {Object} rule - Rule configuration
     */
    addRule(category, rule) {
        this.rules[category] = {
            keywords: rule.keywords || [],
            filePatterns: rule.filePatterns || [],
            datePatterns: rule.datePatterns || [],
            priority: rule.priority || 10
        };
    }

    /**
     * Get all available categories
     * @returns {Array} - List of categories
     */
    getCategories() {
        return Object.keys(this.rules);
    }

    /**
     * Get rules for a specific category
     * @param {string} category - Category name
     * @returns {Object} - Category rules
     */
    getCategoryRules(category) {
        return this.rules[category] || null;
    }
}

// Create global instance
window.documentCategorizer = new DocumentCategorizer();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DocumentCategorizer;
}
