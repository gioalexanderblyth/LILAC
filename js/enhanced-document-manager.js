/**
 * Enhanced Document Management System
 * Comprehensive system for automatic file content reading, award assignment, and readiness tracking
 */

class EnhancedDocumentManager {
    constructor() {
        this.awardTypes = {
            'leadership': {
                name: 'Internationalization (IZN) Leadership Award',
                criteria: [
                    'Champion Bold Innovation',
                    'Cultivate Global Citizens', 
                    'Nurture Lifelong Learning',
                    'Lead with Purpose',
                    'Ethical and Inclusive Leadership'
                ],
                keywords: [
                    'leadership', 'internationalization', 'global', 'strategic', 'vision', 'innovation',
                    'partnership', 'collaboration', 'exchange', 'program', 'initiative', 'development',
                    'international', 'cross-cultural', 'cultural', 'diversity', 'inclusion', 'mentorship',
                    'faculty', 'student', 'research', 'academic', 'institutional', 'governance',
                    'policy', 'framework', 'strategy', 'planning', 'management', 'administration',
                    'award', 'recognition', 'excellence', 'achievement', 'impact', 'outcome'
                ],
                threshold: 3 // Minimum documents/events needed
            },
            'education': {
                name: 'Outstanding International Education Program Award',
                criteria: [
                    'Expand Access to Global Opportunities',
                    'Foster Collaborative Innovation',
                    'Embrace Inclusivity and Beyond'
                ],
                keywords: [
                    'education', 'program', 'curriculum', 'academic', 'course', 'learning',
                    'teaching', 'pedagogy', 'instruction', 'training', 'development', 'skill',
                    'knowledge', 'expertise', 'competency', 'qualification', 'certification',
                    'degree', 'diploma', 'certificate', 'study', 'research', 'scholarship',
                    'international', 'global', 'cross-cultural', 'multicultural', 'diverse',
                    'inclusive', 'accessible', 'opportunity', 'access', 'expand', 'foster'
                ],
                threshold: 2
            },
            'emerging': {
                name: 'Emerging Leadership Award',
                criteria: [
                    'Innovation',
                    'Strategic and Inclusive Growth',
                    'Empowerment of Others'
                ],
                keywords: [
                    'emerging', 'new', 'innovative', 'pioneering', 'cutting-edge', 'advanced',
                    'modern', 'contemporary', 'current', 'latest', 'recent', 'fresh',
                    'breakthrough', 'revolutionary', 'transformative', 'disruptive', 'creative',
                    'original', 'unique', 'novel', 'unprecedented', 'groundbreaking',
                    'strategic', 'growth', 'development', 'expansion', 'scaling', 'scalable',
                    'empowerment', 'empower', 'enable', 'facilitate', 'support', 'assist'
                ],
                threshold: 2
            },
            'regional': {
                name: 'Best Regional Office for Internationalization Award',
                criteria: [
                    'Comprehensive Internationalization Efforts',
                    'Cooperation and Collaboration',
                    'Measurable Impact'
                ],
                keywords: [
                    'regional', 'region', 'local', 'area', 'district', 'province', 'state',
                    'territory', 'zone', 'office', 'branch', 'center', 'centre', 'hub',
                    'headquarters', 'base', 'location', 'site', 'facility', 'institution',
                    'comprehensive', 'complete', 'full', 'total', 'entire', 'whole',
                    'cooperation', 'collaboration', 'partnership', 'alliance', 'network',
                    'coordination', 'coordinate', 'manage', 'administration', 'governance',
                    'impact', 'effect', 'result', 'outcome', 'achievement', 'success'
                ],
                threshold: 2
            },
            'citizenship': {
                name: 'Global Citizenship Award',
                criteria: [
                    'Ignite Intercultural Understanding',
                    'Empower Changemakers',
                    'Cultivate Active Engagement'
                ],
                keywords: [
                    'citizenship', 'citizen', 'community', 'society', 'social', 'civic',
                    'public', 'civil', 'democratic', 'participatory', 'engagement', 'involvement',
                    'participation', 'contribution', 'service', 'volunteer', 'activism',
                    'advocacy', 'awareness', 'consciousness', 'understanding', 'knowledge',
                    'cultural', 'intercultural', 'multicultural', 'diversity', 'inclusion',
                    'tolerance', 'respect', 'acceptance', 'appreciation', 'celebration',
                    'ignite', 'spark', 'inspire', 'motivate', 'encourage', 'stimulate'
                ],
                threshold: 2
            }
        };

        this.documentCounters = {};
        this.readinessStatus = {};
        this.assignedDocuments = {};
        this.assignedEvents = {};
        
        this.initializeCounters();
    }

    /**
     * Initialize counters for all award types
     */
    initializeCounters() {
        for (const awardType in this.awardTypes) {
            this.documentCounters[awardType] = {
                documents: 0,
                events: 0,
                total: 0
            };
            this.readinessStatus[awardType] = {
                isReady: false,
                satisfiedCriteria: [],
                unsatisfiedCriteria: [],
                readinessPercentage: 0
            };
            this.assignedDocuments[awardType] = [];
            this.assignedEvents[awardType] = [];
        }
    }

    /**
     * Process uploaded file with comprehensive content analysis
     */
    async processUploadedFile(file, additionalData = {}) {
        try {
            // Extract content from file
            const extractedContent = await this.extractFileContent(file);
            
            // Analyze content for award assignment
            const analysis = await this.analyzeContentForAwards(extractedContent, file.name);
            
            // Determine award assignments
            const assignments = this.determineAwardAssignments(analysis);
            
            // Update counters and readiness
            this.updateCountersAndReadiness(assignments, 'document', {
                file: file,
                content: extractedContent,
                analysis: analysis,
                ...additionalData
            });

            return {
                success: true,
                content: extractedContent,
                analysis: analysis,
                assignments: assignments,
                counters: this.documentCounters,
                readiness: this.readinessStatus
            };
        } catch (error) {
            console.error('Error processing uploaded file:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Process uploaded event with comprehensive content analysis
     */
    async processUploadedEvent(eventData) {
        try {
            // Extract content from event data
            const extractedContent = await this.extractEventContent(eventData);
            
            // Analyze content for award assignment
            const analysis = await this.analyzeContentForAwards(extractedContent, eventData.title || 'Untitled Event');
            
            // Determine award assignments
            const assignments = this.determineAwardAssignments(analysis);
            
            // Update counters and readiness
            this.updateCountersAndReadiness(assignments, 'event', {
                eventData: eventData,
                content: extractedContent,
                analysis: analysis
            });

            return {
                success: true,
                content: extractedContent,
                analysis: analysis,
                assignments: assignments,
                counters: this.documentCounters,
                readiness: this.readinessStatus
            };
        } catch (error) {
            console.error('Error processing uploaded event:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Extract content from various file types
     */
    async extractFileContent(file) {
        const fileType = file.type.toLowerCase();
        const fileName = file.name.toLowerCase();
        const extension = fileName.split('.').pop();

        try {
            if (fileType === 'text/plain' || extension === 'txt') {
                return await this.extractFromTextFile(file);
            } else if (fileType === 'application/pdf' || extension === 'pdf') {
                return await this.extractFromPDF(file);
            } else if (fileType.includes('word') || extension === 'docx' || extension === 'doc') {
                return await this.extractFromWordDocument(file);
            } else if (fileType.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(extension)) {
                return await this.extractFromImage(file);
            } else {
                // Fallback to filename analysis
                return this.extractFromFilename(file.name);
            }
        } catch (error) {
            console.warn('Error extracting content from file:', error);
            return this.extractFromFilename(file.name);
        }
    }

    /**
     * Extract content from text files
     */
    async extractFromTextFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = reject;
            reader.readAsText(file);
        });
    }

    /**
     * Extract content from PDF files using PDF.js
     */
    async extractFromPDF(file) {
        if (!window.pdfjsLib) {
            throw new Error('PDF.js not available');
        }

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = async (e) => {
                try {
                    const typedArray = new Uint8Array(e.target.result);
                    const pdf = await pdfjsLib.getDocument(typedArray).promise;
                    let fullText = '';

                    // Extract text from all pages
                    for (let i = 1; i <= pdf.numPages; i++) {
                        const page = await pdf.getPage(i);
                        const textContent = await page.getTextContent();
                        const pageText = textContent.items.map(item => item.str).join(' ');
                        fullText += pageText + ' ';
                    }

                    resolve(fullText);
                } catch (error) {
                    reject(error);
                }
            };
            reader.onerror = reject;
            reader.readAsArrayBuffer(file);
        });
    }

    /**
     * Extract content from Word documents
     */
    async extractFromWordDocument(file) {
        // For Word documents, we'll use a basic approach
        // In production, you might want to use a library like mammoth.js
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    // Basic text extraction from filename for now
                    // In production, implement proper Word document parsing
                    const text = this.extractFromFilename(file.name);
                    resolve(text);
                } catch (error) {
                    reject(error);
                }
            };
            reader.onerror = reject;
            reader.readAsText(file);
        });
    }

    /**
     * Extract content from images using OCR
     */
    async extractFromImage(file) {
        if (!window.Tesseract) {
            console.warn('Tesseract.js not available for OCR');
            return this.extractFromFilename(file.name);
        }

        try {
            const { data: { text } } = await Tesseract.recognize(file, 'eng', {
                logger: m => {
                    if (m.status === 'recognizing text') {
                        console.log(`OCR Progress: ${Math.round(m.progress * 100)}%`);
                    }
                }
            });
            return text;
        } catch (error) {
            console.warn('OCR failed, falling back to filename:', error);
            return this.extractFromFilename(file.name);
        }
    }

    /**
     * Extract content from event data
     */
    async extractEventContent(eventData) {
        let content = '';
        
        // Combine title and description
        if (eventData.title) {
            content += eventData.title + ' ';
        }
        if (eventData.description) {
            content += eventData.description + ' ';
        }
        
        // Extract text from image if provided
        if (eventData.image) {
            try {
                const imageText = await this.extractFromImage(eventData.image);
                content += imageText + ' ';
            } catch (error) {
                console.warn('Failed to extract text from event image:', error);
            }
        }
        
        return content;
    }

    /**
     * Extract meaningful text from filename
     */
    extractFromFilename(filename) {
        // Remove file extension and common separators
        const name = filename.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
        return name.toLowerCase();
    }

    /**
     * Analyze content for award assignments
     */
    async analyzeContentForAwards(content, title = '') {
        const normalizedContent = (content + ' ' + title).toLowerCase();
        const analysis = {};

        for (const [awardType, awardData] of Object.entries(this.awardTypes)) {
            let score = 0;
            const matchedKeywords = [];
            const satisfiedCriteria = [];

            // Check keyword matches
            for (const keyword of awardData.keywords) {
                const regex = new RegExp(`\\b${keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
                const matches = normalizedContent.match(regex);
                if (matches) {
                    score += matches.length;
                    matchedKeywords.push(keyword);
                }
            }

            // Check criteria satisfaction
            for (const criterion of awardData.criteria) {
                const criterionLower = criterion.toLowerCase();
                const keywords = criterionLower.split(' ').filter(word => word.length > 2);
                
                const matchedCriterionKeywords = keywords.filter(keyword => 
                    normalizedContent.includes(keyword) || 
                    normalizedContent.includes(keyword.replace(/[^a-z0-9]/g, ''))
                );
                
                if (matchedCriterionKeywords.length >= (keywords.length * 0.5)) {
                    satisfiedCriteria.push(criterion);
                    score += 5; // Bonus points for satisfying criteria
                }
            }

            analysis[awardType] = {
                score: score,
                matchedKeywords: matchedKeywords,
                satisfiedCriteria: satisfiedCriteria,
                confidence: Math.min(score / 10, 1.0) // Normalize confidence
            };
        }

        return analysis;
    }

    /**
     * Determine award assignments based on analysis
     */
    determineAwardAssignments(analysis) {
        const assignments = [];
        const threshold = 0.2; // Minimum confidence threshold

        for (const [awardType, data] of Object.entries(analysis)) {
            if (data.confidence >= threshold) {
                assignments.push({
                    awardType: awardType,
                    awardName: this.awardTypes[awardType].name,
                    confidence: data.confidence,
                    score: data.score,
                    matchedKeywords: data.matchedKeywords,
                    satisfiedCriteria: data.satisfiedCriteria
                });
            }
        }

        // Sort by confidence
        return assignments.sort((a, b) => b.confidence - a.confidence);
    }

    /**
     * Update counters and readiness status
     */
    updateCountersAndReadiness(assignments, type, itemData) {
        for (const assignment of assignments) {
            const awardType = assignment.awardType;
            
            // Update counters
            if (type === 'document') {
                this.documentCounters[awardType].documents++;
                this.assignedDocuments[awardType].push(itemData);
            } else if (type === 'event') {
                this.documentCounters[awardType].events++;
                this.assignedEvents[awardType].push(itemData);
            }
            
            this.documentCounters[awardType].total = 
                this.documentCounters[awardType].documents + this.documentCounters[awardType].events;
            
            // Update readiness status
            this.updateReadinessStatus(awardType);
        }
    }

    /**
     * Update readiness status for a specific award
     */
    updateReadinessStatus(awardType) {
        const awardData = this.awardTypes[awardType];
        const counter = this.documentCounters[awardType];
        const allItems = [...this.assignedDocuments[awardType], ...this.assignedEvents[awardType]];
        
        const satisfiedCriteria = [];
        const unsatisfiedCriteria = [];
        
        // Check each criterion
        for (const criterion of awardData.criteria) {
            const isSatisfied = allItems.some(item => {
                const content = (item.content || '').toLowerCase();
                const criterionLower = criterion.toLowerCase();
                const keywords = criterionLower.split(' ').filter(word => word.length > 2);
                
                const matchedKeywords = keywords.filter(keyword => 
                    content.includes(keyword) || 
                    content.includes(keyword.replace(/[^a-z0-9]/g, ''))
                );
                
                return matchedKeywords.length >= (keywords.length * 0.5);
            });
            
            if (isSatisfied) {
                satisfiedCriteria.push(criterion);
            } else {
                unsatisfiedCriteria.push(criterion);
            }
        }
        
        const readinessPercentage = (satisfiedCriteria.length / awardData.criteria.length) * 100;
        const isReady = counter.total >= awardData.threshold && readinessPercentage >= 80;
        
        this.readinessStatus[awardType] = {
            isReady: isReady,
            satisfiedCriteria: satisfiedCriteria,
            unsatisfiedCriteria: unsatisfiedCriteria,
            readinessPercentage: readinessPercentage,
            totalItems: counter.total,
            threshold: awardData.threshold
        };
    }

    /**
     * Get comprehensive status report
     */
    getStatusReport() {
        const report = {
            summary: {
                totalDocuments: Object.values(this.documentCounters).reduce((sum, counter) => sum + counter.documents, 0),
                totalEvents: Object.values(this.documentCounters).reduce((sum, counter) => sum + counter.events, 0),
                totalItems: Object.values(this.documentCounters).reduce((sum, counter) => sum + counter.total, 0),
                readyAwards: Object.values(this.readinessStatus).filter(status => status.isReady).length,
                totalAwards: Object.keys(this.awardTypes).length
            },
            awards: {},
            recommendations: []
        };

        // Generate detailed report for each award
        for (const [awardType, awardData] of Object.entries(this.awardTypes)) {
            const counter = this.documentCounters[awardType];
            const readiness = this.readinessStatus[awardType];
            
            report.awards[awardType] = {
                name: awardData.name,
                counter: counter,
                readiness: readiness,
                assignedDocuments: this.assignedDocuments[awardType],
                assignedEvents: this.assignedEvents[awardType]
            };
            
            // Generate recommendations for missing content
            if (!readiness.isReady) {
                const recommendations = this.generateRecommendations(awardType, readiness);
                report.recommendations.push(...recommendations);
            }
        }

        return report;
    }

    /**
     * Generate recommendations for missing content
     */
    generateRecommendations(awardType, readiness) {
        const recommendations = [];
        const awardData = this.awardTypes[awardType];
        
        // Check if threshold is met
        if (readiness.totalItems < awardData.threshold) {
            recommendations.push({
                type: 'quantity',
                awardType: awardType,
                awardName: awardData.name,
                message: `Need ${awardData.threshold - readiness.totalItems} more document(s) or event(s) to meet minimum threshold`,
                priority: 'high'
            });
        }
        
        // Check unsatisfied criteria
        for (const criterion of readiness.unsatisfiedCriteria) {
            recommendations.push({
                type: 'criteria',
                awardType: awardType,
                awardName: awardData.name,
                criterion: criterion,
                message: `Missing content demonstrating: ${criterion}`,
                suggestion: this.generateContentSuggestion(criterion, awardType),
                priority: 'medium'
            });
        }
        
        return recommendations;
    }

    /**
     * Generate content suggestions for missing criteria
     */
    generateContentSuggestion(criterion, awardType) {
        const suggestions = {
            'Champion Bold Innovation': 'Create documents or events showcasing innovative international programs, cutting-edge research collaborations, or pioneering educational initiatives.',
            'Cultivate Global Citizens': 'Document student exchange programs, cultural immersion activities, or global citizenship education initiatives.',
            'Nurture Lifelong Learning': 'Showcase continuing education programs, professional development opportunities, or alumni engagement activities.',
            'Lead with Purpose': 'Document strategic planning initiatives, vision statements, or leadership development programs.',
            'Ethical and Inclusive Leadership': 'Showcase diversity and inclusion programs, ethical guidelines, or inclusive policy implementations.',
            'Expand Access to Global Opportunities': 'Document scholarship programs, international partnerships, or accessibility initiatives.',
            'Foster Collaborative Innovation': 'Showcase joint research projects, international collaborations, or innovative program partnerships.',
            'Embrace Inclusivity and Beyond': 'Document inclusive practices, diversity initiatives, or equity-focused programs.',
            'Innovation': 'Create content highlighting new approaches, creative solutions, or breakthrough initiatives.',
            'Strategic and Inclusive Growth': 'Document growth strategies, expansion plans, or inclusive development programs.',
            'Empowerment of Others': 'Showcase mentoring programs, capacity building initiatives, or empowerment-focused activities.',
            'Comprehensive Internationalization Efforts': 'Document holistic internationalization strategies, comprehensive program portfolios, or integrated approaches.',
            'Cooperation and Collaboration': 'Showcase partnership agreements, collaborative projects, or cooperative initiatives.',
            'Measurable Impact': 'Document outcomes, metrics, success stories, or quantifiable results.',
            'Ignite Intercultural Understanding': 'Showcase cultural exchange programs, intercultural dialogue initiatives, or cultural awareness activities.',
            'Empower Changemakers': 'Document leadership development programs, change initiatives, or empowerment-focused activities.',
            'Cultivate Active Engagement': 'Showcase community engagement programs, participatory initiatives, or active involvement activities.'
        };
        
        return suggestions[criterion] || `Create content that demonstrates ${criterion.toLowerCase()}.`;
    }

    /**
     * Reset all counters and assignments
     */
    reset() {
        this.initializeCounters();
    }

    /**
     * Load existing data from server
     */
    async loadExistingData() {
        try {
            // Load existing documents
            const documentsResponse = await fetch('api/documents.php?action=get_all');
            const documentsData = await documentsResponse.json();
            
            if (documentsData.success) {
                for (const doc of documentsData.documents) {
                    await this.processUploadedFile(
                        { name: doc.document_name, type: 'text/plain' }, // Mock file object
                        { id: doc.id, ...doc }
                    );
                }
            }
            
            // Load existing events
            const eventsResponse = await fetch('api/enhanced_management.php?action=get_all_events');
            const eventsData = await eventsResponse.json();
            
            if (eventsData.success) {
                for (const event of eventsData.events) {
                    await this.processUploadedEvent(event);
                }
            }
            
            return true;
        } catch (error) {
            console.error('Error loading existing data:', error);
            return false;
        }
    }
}

// Create global instance
window.enhancedDocumentManager = new EnhancedDocumentManager();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EnhancedDocumentManager;
}
