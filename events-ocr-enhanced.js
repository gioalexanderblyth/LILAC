// Enhanced OCR Processing for Events & Activities Cards
// This script provides automatic card creation with intelligent title extraction and description generation

async function processFilesWithOCR(files) {
    console.log('Enhanced OCR processing started with', files.length, 'files');
    
    const ocrProgress = document.getElementById('ocr-progress');
    const ocrBar = document.getElementById('ocr-bar');
    const ocrStatus = document.getElementById('ocr-status');
    const ocrResults = document.getElementById('ocr-results');
    const detectedContent = document.getElementById('detected-content');
    
    // Show OCR progress
    if (ocrProgress) {
        ocrProgress.classList.remove('hidden');
    }
    
    const processedFiles = [];
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const progress = ((i + 1) / files.length) * 100;
        
        // Update progress
        if (ocrBar) ocrBar.style.width = progress + '%';
        if (ocrStatus) ocrStatus.textContent = `Processing ${file.name}...`;
        
        try {
            if (file.type.startsWith('image/')) {
                // Process image files with OCR
                console.log('Processing image:', file.name);
                const ocrResult = await performOCR(file);
                const category = analyzeContent(ocrResult.text);
                
                processedFiles.push({
                    file: file,
                    extractedText: ocrResult.text,
                    category: category,
                    confidence: ocrResult.confidence || 75
                });
            } else if (file.type.startsWith('video/')) {
                // For video files, extract frame for OCR (simplified approach)
                console.log('Processing video:', file.name);
                const category = categorizeByFilename(file.name);
                processedFiles.push({
                    file: file,
                    extractedText: `Video file: ${file.name}`,
                    category: category,
                    confidence: 60
                });
            } else {
                // For other files, categorize based on filename
                console.log('Processing document:', file.name);
                const category = categorizeByFilename(file.name);
                processedFiles.push({
                    file: file,
                    extractedText: `Document: ${file.name}`,
                    category: category,
                    confidence: 50
                });
            }
        } catch (error) {
            console.error('OCR processing failed for', file.name, ':', error);
            processedFiles.push({
                file: file,
                extractedText: 'Processing failed',
                category: 'activities',
                confidence: 0
            });
        }
    }
    
    // Show OCR results
    if (ocrResults && detectedContent) {
        ocrResults.classList.remove('hidden');
        displayOCRResults(processedFiles, detectedContent);
    }
    
    // Complete OCR processing
    if (ocrStatus) ocrStatus.textContent = 'Analysis complete!';
    if (ocrBar) ocrBar.style.width = '100%';
    
    // Start upload process
    setTimeout(() => {
        startUploadProcess(processedFiles);
    }, 1000);
}

async function performOCR(imageFile) {
    try {
        console.log('Starting OCR for:', imageFile.name);
        const result = await Tesseract.recognize(imageFile, 'eng', {
            logger: m => {
                if (m.status === 'recognizing text') {
                    const ocrBar = document.getElementById('ocr-bar');
                    if (ocrBar) {
                        ocrBar.style.width = (m.progress * 100) + '%';
                    }
                }
            }
        });
        
        console.log('OCR completed for:', imageFile.name, 'Text length:', result.data.text.length);
        return {
            text: result.data.text,
            confidence: result.data.confidence
        };
    } catch (error) {
        console.error('OCR Error:', error);
        return {
            text: '',
            confidence: 0
        };
    }
}

function analyzeContent(text) {
    const lowerText = text.toLowerCase();
    
    // Event keywords with weights
    const eventKeywords = [
        { word: 'conference', weight: 3 },
        { word: 'seminar', weight: 3 },
        { word: 'workshop', weight: 3 },
        { word: 'meeting', weight: 2 },
        { word: 'event', weight: 2 },
        { word: 'ceremony', weight: 2 },
        { word: 'celebration', weight: 2 },
        { word: 'gathering', weight: 2 },
        { word: 'assembly', weight: 2 },
        { word: 'symposium', weight: 3 },
        { word: 'forum', weight: 2 },
        { word: 'presentation', weight: 1 },
        { word: 'lecture', weight: 2 },
        { word: 'webinar', weight: 2 },
        { word: 'summit', weight: 3 },
        { word: 'convention', weight: 3 }
    ];
    
    // Activity keywords with weights
    const activityKeywords = [
        { word: 'assignment', weight: 3 },
        { word: 'homework', weight: 3 },
        { word: 'project', weight: 3 },
        { word: 'task', weight: 2 },
        { word: 'exercise', weight: 2 },
        { word: 'activity', weight: 2 },
        { word: 'practice', weight: 2 },
        { word: 'drill', weight: 2 },
        { word: 'quiz', weight: 3 },
        { word: 'exam', weight: 3 },
        { word: 'test', weight: 3 },
        { word: 'assessment', weight: 3 },
        { word: 'coursework', weight: 2 },
        { word: 'study', weight: 1 },
        { word: 'lesson', weight: 2 },
        { word: 'tutorial', weight: 2 },
        { word: 'lab', weight: 2 },
        { word: 'experiment', weight: 2 }
    ];
    
    let eventScore = 0;
    let activityScore = 0;
    
    // Count weighted keyword matches
    eventKeywords.forEach(item => {
        if (lowerText.includes(item.word)) {
            eventScore += item.weight;
        }
    });
    
    activityKeywords.forEach(item => {
        if (lowerText.includes(item.word)) {
            activityScore += item.weight;
        }
    });
    
    console.log('Content analysis scores - Events:', eventScore, 'Activities:', activityScore);
    
    // Return category based on higher score
    if (eventScore > activityScore) {
        return 'events';
    } else if (activityScore > eventScore) {
        return 'activities';
    } else {
        // Default to activities if no clear match
        return 'activities';
    }
}

// Enhanced function to create event cards from OCR processing
function createEventCardFromOCR(processedFile) {
    console.log('Creating card for:', processedFile.file.name, 'Category:', processedFile.category);
    
    const cardsContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-3.gap-3');
    if (!cardsContainer) {
        console.error('Cards container not found');
        return;
    }
    
    // Extract meaningful title and description
    const title = extractMeaningfulTitle(processedFile.extractedText, processedFile.file.name);
    const description = generateSmartDescription(processedFile.extractedText, processedFile.category);
    const eventDetails = extractEventDetails(processedFile.extractedText);
    
    console.log('Extracted title:', title);
    console.log('Generated description:', description);
    
    // Create new card element
    const newCard = document.createElement('div');
    newCard.className = `event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-shadow duration-300`;
    newCard.setAttribute('data-type', processedFile.category);
    
    // Generate gradient based on category
    const categoryGradients = {
        'events': [
            'from-blue-400 to-purple-500',
            'from-purple-500 to-pink-500',
            'from-indigo-400 to-purple-600'
        ],
        'activities': [
            'from-green-400 to-blue-500',
            'from-yellow-400 to-orange-500',
            'from-red-400 to-pink-500'
        ]
    };
    
    const gradients = categoryGradients[processedFile.category] || categoryGradients['activities'];
    const randomGradient = gradients[Math.floor(Math.random() * gradients.length)];
    
    // Create image preview if it's an image file
    let headerContent = '';
    if (processedFile.file.type.startsWith('image/')) {
        const imageUrl = URL.createObjectURL(processedFile.file);
        headerContent = `
            <div class="h-32 relative overflow-hidden">
                <img src="${imageUrl}" alt="${title}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                <div class="absolute inset-0 bg-black bg-opacity-40"></div>
                <!-- Delete Button -->
                <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
                <!-- Event Title in Header - Bottom Left like original cards -->
                <div class="absolute bottom-2 left-2 text-white">
                    <p class="text-xs font-medium uppercase" style="color: white !important; text-shadow: 0 1px 3px rgba(0,0,0,0.7);">${title}</p>
                </div>
            </div>
        `;
    } else {
        headerContent = `
            <div class="h-32 bg-gradient-to-br ${randomGradient} relative">
                <!-- Delete Button -->
                <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
                <!-- Event Title in Header - Bottom Left like original cards -->
                <div class="absolute bottom-2 left-2 text-white">
                    <p class="text-xs font-medium uppercase" style="color: white !important; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">${title}</p>
                </div>
            </div>
        `;
    }
    
    // Confidence color based on OCR accuracy
    const confidenceColor = processedFile.confidence > 80 ? 'bg-green-500' : 
                           processedFile.confidence > 60 ? 'bg-yellow-500' : 'bg-red-500';
    
    newCard.innerHTML = headerContent + `
        <div class="p-3 bg-white">
            <div class="mb-2">
                <span class="text-purple-600 text-xs font-medium uppercase">${processedFile.category}</span>
            </div>
            <h3 class="font-semibold text-gray-900 text-sm mb-2">${title}</h3>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1">
                    <div class="flex -space-x-1">
                        <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                        <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                        <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                    </div>
                    <span class="text-xs text-gray-600">+124</span>
                </div>
                <button class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    // Add the card to the container with animation
    newCard.style.opacity = '0';
    newCard.style.transform = 'scale(0.95)';
    cardsContainer.appendChild(newCard);
    
    // Animate card in
    setTimeout(() => {
        newCard.style.transition = 'all 0.3s ease-out';
        newCard.style.opacity = '1';
        newCard.style.transform = 'scale(1)';
    }, 100);
    
    // Refresh delete listeners
    if (window.attachCardDeleteListeners) {
        window.attachCardDeleteListeners();
    }
    
    console.log('Card created successfully for:', processedFile.file.name);
    return newCard;
}

// Enhanced title extraction with better algorithms
function extractMeaningfulTitle(text, filename) {
    if (!text || text.trim().length === 0) {
        return cleanFilename(filename);
    }
    
    const lines = text.split('\n').map(line => line.trim()).filter(line => line.length > 0);
    
    // Priority patterns for title detection
    const titlePatterns = [
        // Event announcement patterns
        /^(?:announcing|presenting|invitation to|welcome to)\s+(.+?)(?:\s*!|\s*$)/i,
        // Direct event titles
        /^(.+?)\s*(?:event|conference|meeting|seminar|workshop|ceremony|celebration|gathering|symposium|forum|presentation|lecture|webinar|summit|convention)\s*(?:20\d{2})?$/i,
        // Activity titles
        /^(.+?)\s*(?:activity|assignment|project|task|exercise|practice|drill|quiz|exam|test|assessment|coursework|study|lesson|tutorial|lab|experiment)$/i,
        // Title case lines (likely titles)
        /^[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*(?:\s+20\d{2})?$/,
        // Lines with common title words
        /^(.+?)\s*(?:program|training|course|class|session|module|unit)$/i
    ];
    
    // Look for titles in the first few lines
    for (let i = 0; i < Math.min(lines.length, 5); i++) {
        const line = lines[i];
        
        // Skip very short or number-only lines
        if (line.length < 5 || /^[\d\s\-_.,!@#$%^&*()]+$/.test(line)) {
            continue;
        }
        
        // Try each pattern
        for (const pattern of titlePatterns) {
            const match = line.match(pattern);
            if (match && match[1]) {
                let title = match[1].trim();
                title = cleanTitle(title);
                if (title.length > 5 && title.length < 100) {
                    return title;
                }
            }
        }
        
        // Check if line looks like a title
        if (isLikelyTitle(line)) {
            return cleanTitle(line);
        }
    }
    
    // Fallback: use first substantial line
    for (const line of lines) {
        if (line.length > 8 && line.length < 80 && isLikelyTitle(line)) {
            return cleanTitle(line);
        }
    }
    
    return cleanFilename(filename);
}

// Smart description generation based on OCR content and category
function generateSmartDescription(text, category) {
    if (!text || text.trim().length === 0) {
        return category === 'events' 
            ? 'An upcoming event with details to be confirmed.'
            : 'An educational activity designed to enhance learning.';
    }
    
    const lines = text.split('\n').map(line => line.trim()).filter(line => line.length > 5);
    const lowerText = text.toLowerCase();
    
    // Extract key information
    const eventDetails = extractEventDetails(text);
    const keyPhrases = extractKeyPhrases(text);
    
    let description = '';
    
    if (category === 'events') {
        // Generate event description
        if (lowerText.includes('conference') || lowerText.includes('seminar')) {
            description = `A professional ${lowerText.includes('conference') ? 'conference' : 'seminar'} bringing together experts and participants.`;
        } else if (lowerText.includes('meeting') || lowerText.includes('assembly')) {
            description = 'An organized meeting to discuss important matters and collaborate.';
        } else if (lowerText.includes('ceremony') || lowerText.includes('celebration')) {
            description = 'A special ceremony to commemorate and celebrate achievements.';
        } else if (lowerText.includes('workshop') || lowerText.includes('training')) {
            description = 'An interactive workshop designed to provide hands-on learning experience.';
        } else {
            description = 'An important event bringing people together for a meaningful purpose.';
        }
        
        // Add specific details if available
        if (eventDetails.organizer !== 'System Generated') {
            description += ` Organized by ${eventDetails.organizer}.`;
        }
        
        if (keyPhrases.length > 0) {
            description += ` Focus areas include ${keyPhrases.slice(0, 2).join(' and ')}.`;
        }
        
    } else {
        // Generate activity description
        if (lowerText.includes('assignment') || lowerText.includes('homework')) {
            description = 'A structured assignment designed to reinforce learning objectives.';
        } else if (lowerText.includes('project') || lowerText.includes('research')) {
            description = 'A comprehensive project requiring research and analytical thinking.';
        } else if (lowerText.includes('quiz') || lowerText.includes('exam') || lowerText.includes('test')) {
            description = 'An assessment activity to evaluate understanding and progress.';
        } else if (lowerText.includes('lab') || lowerText.includes('experiment')) {
            description = 'A practical laboratory activity for hands-on learning experience.';
        } else if (lowerText.includes('presentation') || lowerText.includes('report')) {
            description = 'A presentation activity to demonstrate knowledge and communication skills.';
        } else {
            description = 'An educational activity designed to enhance learning and skill development.';
        }
        
        if (keyPhrases.length > 0) {
            description += ` Topics covered include ${keyPhrases.slice(0, 3).join(', ')}.`;
        }
    }
    
    // Add completion note if it seems like a past event
    if (lowerText.includes('completed') || lowerText.includes('finished') || lowerText.includes('concluded')) {
        description += ' This activity has been completed.';
    }
    
    return description.length > 200 ? description.substring(0, 197) + '...' : description;
}

// Helper function to extract key phrases from text
function extractKeyPhrases(text) {
    const phrases = [];
    const lowerText = text.toLowerCase();
    
    // Common academic/event topics
    const topics = [
        'technology', 'computer science', 'programming', 'software development',
        'web development', 'mobile app', 'artificial intelligence', 'machine learning',
        'data science', 'cybersecurity', 'networking', 'database',
        'research', 'innovation', 'entrepreneurship', 'leadership',
        'communication', 'presentation', 'teamwork', 'collaboration',
        'mathematics', 'physics', 'chemistry', 'biology',
        'engineering', 'design', 'architecture', 'planning',
        'business', 'marketing', 'management', 'finance',
        'education', 'training', 'learning', 'development'
    ];
    
    topics.forEach(topic => {
        if (lowerText.includes(topic)) {
            phrases.push(topic);
        }
    });
    
    return phrases.slice(0, 5); // Limit to 5 key phrases
}

// Helper function to check if a line looks like a title
function isLikelyTitle(line) {
    // Check various title characteristics
    const hasCapitalization = /^[A-Z]/.test(line);
    const hasReasonableLength = line.length >= 5 && line.length <= 100;
    const hasWords = (line.match(/[a-zA-Z]+/g) || []).length >= 2;
    const notMostlyNumbers = !/^\d+[\s\d]*$/.test(line);
    const notMostlySymbols = (line.match(/[a-zA-Z\s]/g) || []).length > line.length * 0.6;
    
    return hasCapitalization && hasReasonableLength && hasWords && notMostlyNumbers && notMostlySymbols;
}

// Helper function to clean extracted titles
function cleanTitle(title) {
    return title
        .replace(/[^\w\s\-']/g, ' ')  // Remove special chars except hyphens and apostrophes
        .replace(/\s+/g, ' ')          // Replace multiple spaces with single space
        .trim()                        // Remove leading/trailing spaces
        .split(' ')                    // Split into words
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()) // Title case
        .join(' ');
}

// Helper function to clean filename for use as title
function cleanFilename(filename) {
    return filename
        .replace(/\.[^/.]+$/, '')      // Remove file extension
        .replace(/[-_]/g, ' ')         // Replace hyphens and underscores with spaces
        .replace(/\s+/g, ' ')          // Replace multiple spaces with single space
        .trim()                        // Remove leading/trailing spaces
        .split(' ')                    // Split into words
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()) // Title case
        .join(' ');
}

function categorizeByFilename(filename) {
    const lowerName = filename.toLowerCase();
    
    if (lowerName.includes('event') || lowerName.includes('meeting') || 
        lowerName.includes('conference') || lowerName.includes('seminar')) {
        return 'events';
    } else if (lowerName.includes('assignment') || lowerName.includes('homework') ||
               lowerName.includes('project') || lowerName.includes('activity')) {
        return 'activities';
    } else {
        return 'activities'; // Default
    }
}

// Start upload process and create cards
async function startUploadProcess(processedFiles) {
    console.log('Starting upload process for', processedFiles.length, 'files');
    
    // Create cards from OCR results
    await createCardsFromOCR(processedFiles);
    
    // Show success message
    showUploadSuccess(processedFiles.length);
    
    // Reset modal after a delay
    setTimeout(() => {
        resetModal();
    }, 2000);
}

async function createCardsFromOCR(processedFiles) {
    console.log('Creating cards from', processedFiles.length, 'processed files');
    
    for (const item of processedFiles) {
        // Extract the actual event name from the OCR text
        const eventName = extractEventName(item.extractedText);
        const title = eventName || generateTitle(item.extractedText, item.file.name);
        
        // Extract additional event details from OCR text
        const eventDetails = extractEventDetails(item.extractedText);
        
        // Prepare event data for API
        const eventData = {
            name: title,
            organizer: eventDetails.organizer,
            place: eventDetails.place,
            date: eventDetails.date,
            status: 'upcoming',
            type: item.category || 'activities',
            description: item.description || `${(item.category || 'activities').charAt(0).toUpperCase() + (item.category || 'activities').slice(1)} detected from uploaded image`,
            image_file: item.file.name,
            ocr_text: item.extractedText,
            confidence: item.confidence || 0
        };
        
        // Save to API first
        let savedEvent = null;
        if (window.saveEventToAPI) {
            try {
                savedEvent = await window.saveEventToAPI(eventData);
                console.log('OCR event saved to API:', savedEvent);
            } catch (error) {
                console.error('Failed to save OCR event to API:', error);
            }
        }
        
        // Create visual event card with OCR data
        createEventCardFromOCR(item, savedEvent);
        
        // Add to Events List table with saved data
        if (window.addEventToTable) {
            addEventToTable(savedEvent || eventData);
        }
    }
}

// Show upload success message
function showUploadSuccess(fileCount) {
    const ocrStatus = document.getElementById('ocr-status');
    if (ocrStatus) {
        ocrStatus.textContent = `Successfully processed ${fileCount} file${fileCount > 1 ? 's' : ''}!`;
        ocrStatus.className = 'text-green-600 font-medium';
    }
}

// Reset modal to initial state
function resetModal() {
    const ocrResults = document.getElementById('ocr-results');
    const ocrStatus = document.getElementById('ocr-status');
    const ocrBar = document.getElementById('ocr-bar');
    
    if (ocrResults) ocrResults.classList.add('hidden');
    if (ocrStatus) {
        ocrStatus.textContent = 'Ready to process files...';
        ocrStatus.className = 'text-gray-600';
    }
    if (ocrBar) ocrBar.style.width = '0%';
}

// Make functions globally available
window.processFilesWithOCR = processFilesWithOCR;
window.createEventCardFromOCR = createEventCardFromOCR;
window.createCardsFromOCR = createCardsFromOCR;
window.startUploadProcess = startUploadProcess;

console.log('Enhanced OCR processing module loaded successfully'); 