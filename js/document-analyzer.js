/**
 * Document and Event Analyzer for Award Classification
 * Handles text extraction, keyword analysis, and auto-classification for both documents and events
 */

class DocumentAnalyzer {
    constructor() {
        this.awardKeywords = {
            'leadership': {
                keywords: [
                    'leadership', 'internationalization', 'global', 'strategic', 'vision', 'innovation',
                    'partnership', 'collaboration', 'exchange', 'program', 'initiative', 'development',
                    'international', 'cross-cultural', 'cultural', 'diversity', 'inclusion', 'mentorship',
                    'faculty', 'student', 'research', 'academic', 'institutional', 'governance',
                    'policy', 'framework', 'strategy', 'planning', 'management', 'administration',
                    'award', 'recognition', 'excellence', 'achievement', 'impact', 'outcome',
                    'champion', 'bold', 'innovation', 'cultivate', 'global citizens', 'lifelong learning',
                    'purpose', 'ethical', 'inclusive leadership'
                ],
                phrases: [
                    'international leadership', 'global strategy', 'cross-cultural exchange',
                    'international partnership', 'global citizenship', 'cultural diversity',
                    'international collaboration', 'global education', 'international program',
                    'leadership development', 'strategic planning', 'international initiative',
                    'global perspective', 'international recognition', 'cultural exchange',
                    'international faculty', 'global student', 'international research',
                    'champion bold innovation', 'cultivate global citizens', 'nurture lifelong learning',
                    'lead with purpose', 'ethical and inclusive leadership'
                ],
                weight: 1.0
            },
            'education': {
                keywords: [
                    'education', 'program', 'curriculum', 'academic', 'course', 'learning',
                    'teaching', 'pedagogy', 'instruction', 'training', 'development', 'skill',
                    'knowledge', 'expertise', 'competency', 'qualification', 'certification',
                    'degree', 'diploma', 'certificate', 'study', 'research', 'scholarship',
                    'international', 'global', 'cross-cultural', 'multicultural', 'diverse',
                    'inclusive', 'accessible', 'opportunity', 'access', 'expand', 'foster',
                    'collaborative', 'innovation', 'beyond', 'inclusivity'
                ],
                phrases: [
                    'international education', 'global program', 'academic excellence',
                    'curriculum development', 'educational innovation', 'learning outcome',
                    'student success', 'academic achievement', 'educational partnership',
                    'international curriculum', 'global learning', 'educational exchange',
                    'academic collaboration', 'educational initiative', 'learning program',
                    'expand access to global opportunities', 'foster collaborative innovation',
                    'embrace inclusivity and beyond'
                ],
                weight: 1.0
            },
            'emerging': {
                keywords: [
                    'emerging', 'new', 'innovative', 'pioneering', 'cutting-edge', 'advanced',
                    'modern', 'contemporary', 'current', 'latest', 'recent', 'fresh',
                    'breakthrough', 'revolutionary', 'transformative', 'disruptive', 'creative',
                    'original', 'unique', 'novel', 'unprecedented', 'groundbreaking',
                    'strategic', 'growth', 'development', 'expansion', 'scaling', 'scalable',
                    'empowerment', 'empower', 'enable', 'facilitate', 'support', 'assist',
                    'mentor', 'guide', 'lead', 'direct', 'manage', 'coordinate'
                ],
                phrases: [
                    'emerging leadership', 'innovative approach', 'pioneering initiative',
                    'cutting-edge program', 'breakthrough innovation', 'transformative change',
                    'strategic growth', 'inclusive development', 'empowerment program',
                    'leadership development', 'innovative solution', 'emerging technology',
                    'strategic and inclusive growth', 'empowerment of others'
                ],
                weight: 1.0
            },
            'regional': {
                keywords: [
                    'regional', 'region', 'local', 'area', 'district', 'province', 'state',
                    'territory', 'zone', 'office', 'branch', 'center', 'centre', 'hub',
                    'headquarters', 'base', 'location', 'site', 'facility', 'institution',
                    'comprehensive', 'complete', 'full', 'total', 'entire', 'whole',
                    'cooperation', 'collaboration', 'partnership', 'alliance', 'network',
                    'coordination', 'coordinate', 'manage', 'administration', 'governance',
                    'impact', 'effect', 'result', 'outcome', 'achievement', 'success',
                    'measurable', 'quantifiable', 'assessable', 'evaluable'
                ],
                phrases: [
                    'regional office', 'local partnership', 'regional collaboration',
                    'comprehensive program', 'regional initiative', 'local development',
                    'regional coordination', 'local impact', 'regional success',
                    'comprehensive internationalization efforts', 'cooperation and collaboration',
                    'measurable impact'
                ],
                weight: 1.0
            },
            'citizenship': {
                keywords: [
                    'citizenship', 'citizen', 'community', 'society', 'social', 'civic',
                    'public', 'civil', 'democratic', 'participatory', 'engagement', 'involvement',
                    'participation', 'contribution', 'service', 'volunteer', 'activism',
                    'advocacy', 'awareness', 'consciousness', 'understanding', 'knowledge',
                    'cultural', 'intercultural', 'multicultural', 'diversity', 'inclusion',
                    'tolerance', 'respect', 'acceptance', 'appreciation', 'celebration',
                    'ignite', 'spark', 'inspire', 'motivate', 'encourage', 'stimulate',
                    'changemaker', 'change-maker', 'agent', 'catalyst', 'driver', 'force',
                    'cultivate', 'develop', 'foster', 'nurture', 'grow', 'build',
                    'active', 'engaged', 'involved', 'participatory', 'interactive'
                ],
                phrases: [
                    'global citizenship', 'cultural awareness', 'community engagement',
                    'social responsibility', 'civic participation', 'cultural exchange',
                    'intercultural understanding', 'global awareness', 'cultural diversity',
                    'community service', 'social impact', 'cultural celebration',
                    'ignite intercultural understanding', 'empower changemakers',
                    'cultivate active engagement'
                ],
                weight: 1.0
            }
        };

        this.awardNames = {
            'leadership': 'Internationalization (IZN) Leadership Award',
            'education': 'Outstanding International Education Program Award',
            'emerging': 'Emerging Leadership Award',
            'regional': 'Best Regional Office for Internationalization Award',
            'citizenship': 'Global Citizenship Award'
        };
    }

    /**
     * Extract text from various document types
     */
    async extractText(file) {
        const fileType = file.type.toLowerCase();
        const fileName = file.name.toLowerCase();

        try {
            if (fileType === 'text/plain' || fileName.endsWith('.txt')) {
                return await this.extractFromText(file);
            } else if (fileType === 'application/pdf' || fileName.endsWith('.pdf')) {
                return await this.extractFromPDF(file);
            } else if (fileType.includes('word') || fileName.endsWith('.docx') || fileName.endsWith('.doc')) {
                return await this.extractFromWord(file);
            } else {
                // For other file types, try to extract from filename and basic text
                return this.extractFromFilename(file.name);
            }
        } catch (error) {
            console.error('Error extracting text from document:', error);
            return this.extractFromFilename(file.name);
        }
    }

    /**
     * Extract text from plain text files
     */
    async extractFromText(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = reject;
            reader.readAsText(file);
        });
    }

    /**
     * Extract text from PDF files using PDF.js
     */
    async extractFromPDF(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = async (e) => {
                try {
                    const typedArray = new Uint8Array(e.target.result);
                    const pdf = await pdfjsLib.getDocument(typedArray).promise;
                    let fullText = '';

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
     * Extract text from Word documents (basic implementation)
     */
    async extractFromWord(file) {
        // For Word documents, we'll use a basic approach
        // In a production environment, you might want to use a library like mammoth.js
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
     * Extract meaningful text from filename
     */
    extractFromFilename(filename) {
        // Remove file extension and common separators
        const name = filename.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
        return name.toLowerCase();
    }

    /**
     * Analyze document content and classify it to an award
     */
    async analyzeDocument(file) {
        const text = await this.extractText(file);
        const analysis = this.performKeywordAnalysis(text);
        
        return {
            text: text,
            classification: analysis.bestMatch,
            confidence: analysis.confidence,
            scores: analysis.scores,
            matchedKeywords: analysis.matchedKeywords,
            matchedPhrases: analysis.matchedPhrases
        };
    }

    /**
     * Perform keyword analysis to determine award classification
     */
    performKeywordAnalysis(text) {
        const normalizedText = text.toLowerCase();
        const scores = {};
        const matchedKeywords = {};
        const matchedPhrases = {};

        // Calculate scores for each award
        for (const [awardKey, awardData] of Object.entries(this.awardKeywords)) {
            let score = 0;
            const keywords = [];
            const phrases = [];

            // Check for keyword matches
            for (const keyword of awardData.keywords) {
                const regex = new RegExp(`\\b${keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
                const matches = normalizedText.match(regex);
                if (matches) {
                    score += matches.length * awardData.weight;
                    keywords.push(keyword);
                }
            }

            // Check for phrase matches (higher weight)
            for (const phrase of awardData.phrases) {
                const regex = new RegExp(phrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
                const matches = normalizedText.match(regex);
                if (matches) {
                    score += matches.length * awardData.weight * 2; // Phrases get double weight
                    phrases.push(phrase);
                }
            }

            scores[awardKey] = score;
            matchedKeywords[awardKey] = keywords;
            matchedPhrases[awardKey] = phrases;
        }

        // Find the best match
        const bestMatch = Object.entries(scores).reduce((a, b) => scores[a[0]] > scores[b[0]] ? a : b);
        const maxScore = bestMatch[1];
        const totalPossibleScore = Object.values(this.awardKeywords).reduce((sum, award) => 
            sum + award.keywords.length + (award.phrases.length * 2), 0);
        
        const confidence = maxScore > 0 ? Math.min(maxScore / 10, 1.0) : 0; // Normalize confidence

        return {
            bestMatch: maxScore > 0 ? this.awardNames[bestMatch[0]] : null,
            confidence: confidence,
            scores: scores,
            matchedKeywords: matchedKeywords,
            matchedPhrases: matchedPhrases
        };
    }

    /**
     * Get award criteria for comparison
     */
    getAwardCriteria() {
        return {
            'leadership': [
                'Champion Bold Innovation',
                'Cultivate Global Citizens', 
                'Nurture Lifelong Learning',
                'Lead with Purpose',
                'Ethical and Inclusive Leadership'
            ],
            'education': [
                'Expand Access to Global Opportunities',
                'Foster Collaborative Innovation',
                'Embrace Inclusivity and Beyond'
            ],
            'emerging': [
                'Innovation',
                'Strategic and Inclusive Growth',
                'Empowerment of Others'
            ],
            'regional': [
                'Comprehensive Internationalization Efforts',
                'Cooperation and Collaboration',
                'Measurable Impact'
            ],
            'citizenship': [
                'Ignite Intercultural Understanding',
                'Empower Changemakers',
                'Cultivate Active Engagement'
            ]
        };
    }

    /**
     * Compare document against award criteria
     */
    compareAgainstCriteria(text, awardType) {
        const criteria = this.getAwardCriteria()[awardType];
        const normalizedText = text.toLowerCase();
        const satisfiedCriteria = [];
        const unsatisfiedCriteria = [];

        for (const criterion of criteria) {
            const criterionLower = criterion.toLowerCase();
            const keywords = criterionLower.split(' ');
            
            // Check if criterion keywords appear in the text
            const isSatisfied = keywords.some(keyword => 
                normalizedText.includes(keyword) || 
                normalizedText.includes(keyword.replace(/[^a-z0-9]/g, ''))
            );

            if (isSatisfied) {
                satisfiedCriteria.push(criterion);
            } else {
                unsatisfiedCriteria.push(criterion);
            }
        }

        return {
            satisfied: satisfiedCriteria,
            unsatisfied: unsatisfiedCriteria,
            satisfactionRate: satisfiedCriteria.length / criteria.length
        };
    }

    /**
     * Analyze event content and classify it to award(s)
     */
    async analyzeEvent(eventData) {
        let text = '';
        
        // Combine title and description
        if (eventData.title) {
            text += eventData.title + ' ';
        }
        if (eventData.description) {
            text += eventData.description + ' ';
        }
        
        // Extract text from image if provided
        if (eventData.image) {
            try {
                const imageText = await this.extractTextFromImage(eventData.image);
                text += imageText + ' ';
            } catch (error) {
                console.warn('Failed to extract text from event image:', error);
            }
        }
        
        const analysis = this.performKeywordAnalysis(text);
        
        return {
            text: text,
            classification: analysis.bestMatch,
            confidence: analysis.confidence,
            scores: analysis.scores,
            matchedKeywords: analysis.matchedKeywords,
            matchedPhrases: analysis.matchedPhrases,
            type: 'event'
        };
    }

    /**
     * Extract text from image using OCR
     */
    async extractTextFromImage(imageFile) {
        return new Promise((resolve, reject) => {
            if (typeof Tesseract === 'undefined') {
                console.warn('Tesseract.js not available for OCR');
                resolve('');
                return;
            }

            Tesseract.recognize(imageFile, 'eng', {
                logger: m => {
                    if (m.status === 'recognizing text') {
                        console.log(`OCR Progress: ${Math.round(m.progress * 100)}%`);
                    }
                }
            }).then(({ data: { text } }) => {
                resolve(text);
            }).catch(error => {
                console.error('OCR Error:', error);
                resolve('');
            });
        });
    }

    /**
     * Analyze both document and event content
     */
    async analyzeContent(contentData, type = 'document') {
        if (type === 'event') {
            return await this.analyzeEvent(contentData);
        } else {
            return await this.analyzeDocument(contentData);
        }
    }

    /**
     * Get multiple award classifications (for content that might support multiple awards)
     */
    getMultipleClassifications(analysis, threshold = 0.2) {
        const classifications = [];
        
        for (const [awardKey, score] of Object.entries(analysis.scores)) {
            const normalizedScore = score / 10; // Normalize score
            if (normalizedScore >= threshold) {
                classifications.push({
                    award: this.awardNames[awardKey],
                    score: normalizedScore,
                    confidence: Math.min(normalizedScore, 1.0)
                });
            }
        }
        
        // Sort by confidence
        return classifications.sort((a, b) => b.confidence - a.confidence);
    }

    /**
     * Generate recommendations for missing content
     */
    generateContentRecommendations(awardType, existingContent) {
        const criteria = this.getAwardCriteria()[awardType];
        const recommendations = [];
        
        for (const criterion of criteria) {
            const isSatisfied = existingContent.some(content => {
                const text = (content.title || '') + ' ' + (content.description || '') + ' ' + (content.ocr_text || '');
                return this.checkCriterionSatisfaction(text, criterion);
            });
            
            if (!isSatisfied) {
                recommendations.push({
                    criterion: criterion,
                    suggestion: this.generateContentSuggestion(criterion, awardType),
                    priority: this.getCriterionPriority(criterion, awardType)
                });
            }
        }
        
        return recommendations.sort((a, b) => b.priority - a.priority);
    }

    /**
     * Check if content satisfies a specific criterion
     */
    checkCriterionSatisfaction(text, criterion) {
        const normalizedText = text.toLowerCase();
        const criterionLower = criterion.toLowerCase();
        const keywords = criterionLower.split(' ').filter(word => word.length > 2);
        
        const matchedKeywords = keywords.filter(keyword => 
            normalizedText.includes(keyword) || 
            normalizedText.includes(keyword.replace(/[^a-z0-9]/g, ''))
        );
        
        return matchedKeywords.length >= (keywords.length * 0.5);
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
     * Get priority level for criterion
     */
    getCriterionPriority(criterion, awardType) {
        // Higher priority for core criteria
        const highPriorityCriteria = [
            'Champion Bold Innovation',
            'Expand Access to Global Opportunities', 
            'Innovation',
            'Comprehensive Internationalization Efforts',
            'Ignite Intercultural Understanding'
        ];
        
        return highPriorityCriteria.includes(criterion) ? 3 : 2;
    }
}

// Export for use in other modules
window.DocumentAnalyzer = DocumentAnalyzer;
