<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Events & Activities</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="dashboard-theme.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <!-- Tesseract.js for OCR functionality -->
    <script src="https://unpkg.com/tesseract.js@4.1.1/dist/tesseract.min.js"></script>
</head>

<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between relative transition-all duration-300 ease-in-out">
        <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        


    </nav>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Upload Modal -->
    <div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900">Upload Files</h3>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="p-3">
                <!-- Manual Entry Section -->
                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-900 mb-2">Enter Event Details</h4>
                    <div class="space-y-2">
                        <!-- First Row -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="event-name" class="block text-xs font-medium text-gray-700 mb-0.5">Name of Event</label>
                                <input type="text" id="event-name" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500" placeholder="Enter event name">
                            </div>
                            <div>
                                <label for="event-organizer" class="block text-xs font-medium text-gray-700 mb-0.5">Organizer</label>
                                <input type="text" id="event-organizer" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500" placeholder="Enter organizer name">
                            </div>
                        </div>

                        <!-- Second Row -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="event-place" class="block text-xs font-medium text-gray-700 mb-0.5">Place</label>
                                <input type="text" id="event-place" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500" placeholder="Enter venue/location">
                            </div>
                            <div>
                                <label for="event-date" class="block text-xs font-medium text-gray-700 mb-0.5">Date</label>
                                <input type="date" id="event-date" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500">
                            </div>
                        </div>

                        <!-- Third Row -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="event-status" class="block text-xs font-medium text-gray-700 mb-0.5">Status</label>
                                <select id="event-status" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500">
                                    <option value="">Select status</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div>
                                <label for="event-type" class="block text-xs font-medium text-gray-700 mb-0.5">Event Type</label>
                                <select id="event-type" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500">
                                    <option value="">Select type</option>
                                    <option value="events">Events</option>
                                    <option value="activities">Activities</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Section -->
                <div>
                    <h4 class="text-xs font-semibold text-gray-900 mb-2">Upload Files</h4>
                    <!-- Drag and Drop Area -->
                    <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded p-3 text-center hover:border-purple-400 transition-colors cursor-pointer">
                        <div class="flex flex-col items-center">
                            <svg class="w-6 h-6 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <h4 class="text-xs font-medium text-gray-900 mb-1">Drop files here or click to browse</h4>
                            <button id="browse-files" class="bg-purple-600 text-white px-2 py-1 text-xs rounded hover:bg-purple-700 transition-colors" onclick="event.stopPropagation(); document.getElementById('file-input').click(); console.log('Choose Files clicked!');">
                                Choose Files
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- File List -->
                <div id="file-list" class="mt-4 hidden">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Selected Files:</h4>
                    <div id="selected-files" class="space-y-2 max-h-32 overflow-y-auto">
                        <!-- Files will be listed here -->
                    </div>
                </div>
                
                <!-- OCR Progress -->
                <div id="ocr-progress" class="mt-4 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900">Scanning content...</span>
                        <span id="ocr-status" class="text-sm text-gray-500">Processing</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="ocr-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Upload Progress -->
                <div id="upload-progress" class="mt-4 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900">Uploading...</span>
                        <span id="progress-text" class="text-sm text-gray-500">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-purple-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <!-- OCR Results -->
                <div id="ocr-results" class="mt-4 hidden">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Content Analysis:</h4>
                    <div id="detected-content" class="bg-gray-50 rounded p-3 text-sm">
                        <!-- OCR results will be displayed here -->
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex items-center justify-between px-3 py-2 border-t border-gray-200">
                <button id="cancel-upload" class="px-2 py-1 text-xs text-gray-700 border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="add-manual-event" class="px-3 py-1.5 text-xs bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                    Add Event
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden File Input -->
    <input type="file" id="file-input" class="hidden" multiple accept="*/*" onchange="handleFileSelection(this.files)">

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-auto">
            <!-- Modal Header -->
            <div class="p-6 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Card</h3>
                <p class="text-gray-600 text-sm mb-6">Are you sure you want to delete this card? This action cannot be undone.</p>
                
                <!-- Modal Buttons -->
                <div class="flex gap-3 justify-center">
                    <button id="cancel-delete" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button id="confirm-delete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // GLOBAL FILE SELECTION HANDLER - MUST BE FIRST!
    let isProcessingFiles = false; // Flag to prevent multiple processing
    
    // Direct OCR processing function
    async function performDirectOCR(files) {
        console.log('performDirectOCR called with', files.length, 'files');
        
        // Show progress indicators
        const ocrProgress = document.getElementById('ocr-progress');
        const ocrBar = document.getElementById('ocr-bar');
        const ocrStatus = document.getElementById('ocr-status');
        
        if (ocrProgress) ocrProgress.classList.remove('hidden');
        if (ocrStatus) ocrStatus.textContent = 'Starting OCR processing...';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const progress = ((i + 1) / files.length) * 100;
            
            console.log(`Processing file ${i + 1}/${files.length}: ${file.name}`);
            
            // Update progress bar
            if (ocrBar) ocrBar.style.width = progress + '%';
            if (ocrStatus) ocrStatus.textContent = `Processing ${file.name}...`;
            
            try {
                // Check if it's an image file
                if (file.type.startsWith('image/')) {
                    console.log('Processing image file with Tesseract...');
                    
                    // Use Tesseract.js for OCR
                    if (window.Tesseract) {
                        const result = await Tesseract.recognize(file, 'eng', {
                            logger: m => {
                                if (m.status === 'recognizing text') {
                                    const progressPercent = Math.round(m.progress * 100);
                                    if (ocrBar) ocrBar.style.width = progressPercent + '%';
                                    if (ocrStatus) ocrStatus.textContent = `Scanning ${file.name}: ${progressPercent}%`;
                                }
                            }
                        });
                        
                        const extractedText = result.data.text;
                        console.log('OCR extracted text:', extractedText);
                        
                        if (extractedText && extractedText.trim().length > 0) {
                            // Extract event details from the text
                            const eventName = extractEventName(extractedText);
                            const eventDetails = extractEventDetails(extractedText);
                            const category = analyzeContent(extractedText);
                            
                            console.log('Extracted event name:', eventName);
                            console.log('Extracted event details:', eventDetails);
                            console.log('Detected category:', category);
                            
                            // Create event data
                            const eventData = {
                                name: eventName || file.name.replace(/\.[^/.]+$/, ""),
                                organizer: eventDetails.organizer,
                                place: eventDetails.place,
                                date: eventDetails.date,
                                status: 'upcoming'
                            };
                            
                            // Add to Events List table
                            if (window.addEventToTable) {
                                addEventToTable(eventData);
                                console.log('Added event to table:', eventData.name);
                            }
                            
                            // Show success message
                            if (ocrStatus) ocrStatus.textContent = `Completed: ${eventData.name}`;
                        } else {
                            console.log('No text extracted from image');
                            if (ocrStatus) ocrStatus.textContent = `No text found in ${file.name}`;
                        }
                    } else {
                        console.error('Tesseract.js not available');
                        if (ocrStatus) ocrStatus.textContent = 'OCR library not available';
                    }
                } else {
                    console.log('Non-image file, skipping OCR');
                    // For non-image files, just add basic info
                    const eventData = {
                        name: file.name.replace(/\.[^/.]+$/, ""),
                        organizer: 'File Upload',
                        place: 'Not specified',
                        date: new Date().toISOString().split('T')[0],
                        status: 'upcoming'
                    };
                    
                    if (window.addEventToTable) {
                        addEventToTable(eventData);
                        console.log('Added non-image file to table:', eventData.name);
                    }
                }
            } catch (error) {
                console.error('Error processing file:', file.name, error);
                if (ocrStatus) ocrStatus.textContent = `Error processing ${file.name}`;
            }
        }
        
        // Complete processing
        if (ocrBar) ocrBar.style.width = '100%';
        if (ocrStatus) ocrStatus.textContent = 'Processing complete!';
        
        // Hide progress after delay
        setTimeout(() => {
            if (ocrProgress) ocrProgress.classList.add('hidden');
        }, 2000);
        
        console.log('Direct OCR processing completed');
    }
    
    function handleFileSelection(files) {
        console.log('handleFileSelection called with', files.length, 'files');
        
        // Prevent multiple processing
        if (isProcessingFiles) {
            console.log('Already processing files, ignoring duplicate call');
            return;
        }
        
        isProcessingFiles = true;
        
        if (!files || files.length === 0) {
            console.log('No files selected');
            isProcessingFiles = false; // Reset flag
            return;
        }
        
        // Reset the file input value to allow selecting the same file again
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            setTimeout(() => {
                fileInput.value = '';
            }, 500);
        }
        
        const fileArray = Array.from(files);
        console.log('Processing files:', fileArray.map(f => f.name));
        
        // Show selected files in the modal
        const fileList = document.getElementById('file-list');
        const selectedFilesContainer = document.getElementById('selected-files');
        
        if (fileList && selectedFilesContainer) {
            console.log('Displaying files in modal');
            fileList.classList.remove('hidden');
            selectedFilesContainer.innerHTML = '';
            
            fileArray.forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded text-sm';
                fileItem.innerHTML = `
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-gray-900">${file.name}</span>
                    </div>
                    <span class="text-gray-500 text-xs">${(file.size / 1024).toFixed(1)} KB</span>
                `;
                selectedFilesContainer.appendChild(fileItem);
            });
        } else {
            console.log('File list elements not found');
        }
        
                // Start OCR processing with longer delay to ensure all functions are loaded
        setTimeout(function() {
            console.log('Attempting OCR processing...');
            console.log('typeof processFilesWithOCR:', typeof processFilesWithOCR);
            console.log('window.processFilesWithOCR:', typeof window.processFilesWithOCR);
            
            // Try multiple ways to access the OCR function
            let ocrFunction = null;
            
            if (typeof processFilesWithOCR === 'function') {
                ocrFunction = processFilesWithOCR;
                console.log('Using direct processFilesWithOCR function');
            } else if (window.processFilesWithOCR && typeof window.processFilesWithOCR === 'function') {
                ocrFunction = window.processFilesWithOCR;
                console.log('Using window.processFilesWithOCR function');
            } else {
                // Try to find it in the global scope by searching all functions
                for (let prop in window) {
                    if (typeof window[prop] === 'function' && prop.includes('processFiles')) {
                        ocrFunction = window[prop];
                        console.log('Found OCR function as:', prop);
                        break;
                    }
                }
            }
            
            if (ocrFunction) {
                console.log('Starting OCR processing with function:', ocrFunction.name || 'anonymous');
                try {
                    ocrFunction(fileArray);
                } catch (error) {
                    console.error('Error calling OCR function:', error);
                    // Fallback: try direct OCR processing
                    console.log('Attempting direct OCR processing as fallback...');
                    performDirectOCR(fileArray);
                }
            } else {
                console.error('OCR function not found, trying direct OCR processing...');
                performDirectOCR(fileArray);
            }
            
            // Reset processing flag after completion
            setTimeout(() => {
                isProcessingFiles = false;
                console.log('File processing completed, flag reset');
            }, 2000);
        }, 500); // Longer delay to ensure all functions are loaded
     }
    
    // IMMEDIATE CALENDAR FIX - RUN RIGHT NOW!
    (function() {
        const monthYearElement = document.getElementById('calendar-month-year');
        const calendarDays = document.getElementById('calendar-days');
        
        if (monthYearElement) {
            const now = new Date();
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"];
            monthYearElement.textContent = `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
            
            if (calendarDays) {
                const today = now.getDate();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).getDay();
                const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
                
                let html = '';
                
                // Add empty cells for days before the first day of the month
                for (let i = 0; i < firstDay; i++) {
                    html += '<div class="text-center py-1 text-gray-400"></div>';
                }
                
                // Add days of the current month
                for (let day = 1; day <= daysInMonth; day++) {
                    if (day === today) {
                        html += `<div class="text-center py-1 bg-purple-600 text-white rounded font-medium">${day}</div>`;
                    } else {
                        html += `<div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">${day}</div>`;
                    }
                }
                
                calendarDays.innerHTML = html;
            }
        }
    })();
    
    // MULTIPLE BACKUP ATTEMPTS
    setTimeout(function() {
        const monthYearElement = document.getElementById('calendar-month-year');
        if (monthYearElement && monthYearElement.textContent === 'Loading...') {
            const now = new Date();
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"];
            monthYearElement.textContent = `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
            
            const calendarDays = document.getElementById('calendar-days');
            if (calendarDays && calendarDays.innerHTML.trim() === '') {
                calendarDays.innerHTML = '<div class="text-center py-1 bg-purple-600 text-white rounded font-medium">12</div>';
            }
        }
    }, 100);
    
         setTimeout(function() {
         const monthYearElement = document.getElementById('calendar-month-year');
         const calendarDays = document.getElementById('calendar-days');
         
         // Force set the month/year
         if (monthYearElement) {
             monthYearElement.textContent = 'September 2025';
         }
         
         // Force add calendar days regardless
         if (calendarDays) {
             calendarDays.innerHTML = `
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">1</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">2</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">3</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">4</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">5</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">6</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">7</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">8</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">9</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">10</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">11</div>
                 <div class="text-center py-1 bg-purple-600 text-white rounded font-medium">12</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">13</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">14</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">15</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">16</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">17</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">18</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">19</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">20</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">21</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">22</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">23</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">24</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">25</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">26</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">27</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">28</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">29</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">30</div>
             `;
         }
     }, 1000);
     
     // FINAL AGGRESSIVE FIX - FORCE CALENDAR DAYS
     setTimeout(function() {
         const calendarDays = document.getElementById('calendar-days');
         if (calendarDays && (calendarDays.innerHTML.trim() === '' || calendarDays.children.length === 0)) {
             console.log('FORCING CALENDAR DAYS TO APPEAR!');
             calendarDays.innerHTML = `
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">1</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">2</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">3</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">4</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">5</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">6</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">7</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">8</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">9</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">10</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">11</div>
                 <div class="text-center py-1 bg-purple-600 text-white rounded font-medium">12</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">13</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">14</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">15</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">16</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">17</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">18</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">19</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">20</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">21</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">22</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">23</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">24</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">25</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">26</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">27</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">28</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">29</div>
                 <div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">30</div>
             `;
         }
     }, 2000);
     
     // IMMEDIATE UPLOAD BUTTON FIX - RUN RIGHT NOW!
     (function() {
         const uploadBtn = document.getElementById('events-upload-btn');
         const uploadModal = document.getElementById('upload-modal');
         
         if (uploadBtn && uploadModal) {
             uploadBtn.onclick = function() {
                 console.log('IMMEDIATE: Upload button clicked!');
                 uploadModal.classList.remove('hidden');
                 uploadModal.classList.add('flex');
             };
             console.log('IMMEDIATE: Upload button listener added');
         } else {
             console.error('IMMEDIATE: Upload elements not found:', {
                 button: !!uploadBtn,
                 modal: !!uploadModal
             });
         }
     })();
     
     // BACKUP UPLOAD BUTTON FIXES
     setTimeout(function() {
         const uploadBtn = document.getElementById('events-upload-btn');
         const uploadModal = document.getElementById('upload-modal');
         
         if (uploadBtn && uploadModal) {
             uploadBtn.onclick = function() {
                 console.log('BACKUP 100ms: Upload button clicked!');
                 uploadModal.classList.remove('hidden');
                 uploadModal.classList.add('flex');
             };
             console.log('BACKUP 100ms: Upload button listener added');
         }
     }, 100);
     
     setTimeout(function() {
         const uploadBtn = document.getElementById('events-upload-btn');
         const uploadModal = document.getElementById('upload-modal');
         
         if (uploadBtn && uploadModal) {
             uploadBtn.onclick = function() {
                 console.log('BACKUP 1000ms: Upload button clicked!');
                 uploadModal.classList.remove('hidden');
                 uploadModal.classList.add('flex');
             };
             console.log('BACKUP 1000ms: Upload button listener added');
         }
           }, 1000);
      
      // IMMEDIATE CLOSE MODAL FIX
      (function() {
          const closeModal = document.getElementById('close-modal');
          const cancelUpload = document.getElementById('cancel-upload');
          const uploadModal = document.getElementById('upload-modal');
          
          if (closeModal && uploadModal) {
              closeModal.onclick = function() {
                  console.log('Close modal clicked');
                  uploadModal.classList.add('hidden');
                  uploadModal.classList.remove('flex');
              };
          }
          
          if (cancelUpload && uploadModal) {
              cancelUpload.onclick = function() {
                  console.log('Cancel upload clicked');
                  uploadModal.classList.add('hidden');
                  uploadModal.classList.remove('flex');
              };
          }
          
          // Global close function
          window.closeUploadModal = function() {
              if (uploadModal) {
                  uploadModal.classList.add('hidden');
                  uploadModal.classList.remove('flex');
              }
          };
      })();
      
      // IMMEDIATE TAB SWITCHING FIX
      (function() {
          const fileUploadTab = document.getElementById('file-upload-tab');
          const manualEntryTab = document.getElementById('manual-entry-tab');
          const fileUploadContent = document.getElementById('file-upload-content');
          const manualEntryContent = document.getElementById('manual-entry-content');
          const startUpload = document.getElementById('start-upload');
          const addManualEvent = document.getElementById('add-manual-event');
          
          if (fileUploadTab && manualEntryTab) {
              fileUploadTab.onclick = function() {
                  console.log('File Upload tab clicked');
                  // Switch to file upload tab
                  fileUploadTab.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
                  fileUploadTab.classList.remove('text-gray-500');
                  manualEntryTab.classList.add('text-gray-500');
                  manualEntryTab.classList.remove('text-purple-600', 'border-b-2', 'border-purple-600');
                  
                  // Show/hide content
                  if (fileUploadContent) fileUploadContent.classList.remove('hidden');
                  if (manualEntryContent) manualEntryContent.classList.add('hidden');
                  
                  // Show/hide buttons
                  if (startUpload) startUpload.classList.remove('hidden');
                  if (addManualEvent) addManualEvent.classList.add('hidden');
              };
              
              manualEntryTab.onclick = function() {
                  console.log('Manual Entry tab clicked');
                  // Switch to manual entry tab
                  manualEntryTab.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
                  manualEntryTab.classList.remove('text-gray-500');
                  fileUploadTab.classList.add('text-gray-500');
                  fileUploadTab.classList.remove('text-purple-600', 'border-b-2', 'border-purple-600');
                  
                  // Show/hide content
                  if (manualEntryContent) manualEntryContent.classList.remove('hidden');
                  if (fileUploadContent) fileUploadContent.classList.add('hidden');
                  
                  // Show/hide buttons
                  if (addManualEvent) addManualEvent.classList.remove('hidden');
                  if (startUpload) startUpload.classList.add('hidden');
              };
          }
          
          // Manual entry form submission
          if (addManualEvent) {
              addManualEvent.onclick = function() {
                  console.log('Add Manual Event clicked');
                  try {
                      const eventName = document.getElementById('event-name')?.value || '';
                      const organizer = document.getElementById('event-organizer')?.value || '';
                      const place = document.getElementById('event-place')?.value || '';
                      const date = document.getElementById('event-date')?.value || '';
                      const status = document.getElementById('event-status')?.value || '';
                      const type = document.getElementById('event-type')?.value || '';
                      
                      console.log('Form values:', { eventName, organizer, place, date, status, type });
                      
                      // Validate required fields
                      if (!eventName || !organizer || !place || !date || !status || !type) {
                          alert('Please fill in all fields');
                          return;
                      }
                      
                      // Create event data object
                      const eventData = {
                          name: eventName,
                          organizer: organizer,
                          place: place,
                          date: date,
                          status: status,
                          type: type
                      };
                      
                      // Create event card
                      createManualEventCard(eventData);
                      
                      // Add event to table
                      addEventToTable(eventData);
                      
                      // Show success message
                      alert('Event added successfully!');
                      
                      // Close modal and reset
                      if (window.closeUploadModal) {
                          window.closeUploadModal();
                      }
                      
                      // Clear form
                      document.getElementById('event-name').value = '';
                      document.getElementById('event-organizer').value = '';
                      document.getElementById('event-place').value = '';
                      document.getElementById('event-date').value = '';
                      document.getElementById('event-status').value = '';
                      document.getElementById('event-type').value = '';
                      
                  } catch (error) {
                      console.error('Manual entry error:', error);
                      alert('An error occurred while adding the event');
                  }
              };
          }
      })();
     
    document.addEventListener('DOMContentLoaded', function() {
        updateCurrentDate();
        setInterval(updateCurrentDate, 60000);
        setTimeout(function() {
            if (window.lilacNotifications && window.lilacNotifications.container) {
                window.lilacNotifications.container.style.top = '80px';
                window.lilacNotifications.container.style.zIndex = '99999';
            }
        }, 500);
    });

    function updateCurrentDate() {
        var el = document.getElementById('current-date');
        if (el) {
            var now = new Date();
            el.textContent = now.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
        }
            }
        
        // Create manual event card
        function createManualEventCard(eventData) {
            const cardsContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-3.gap-3');
            if (!cardsContainer) return;
            
            // Create new card element
            const newCard = document.createElement('div');
            newCard.className = `event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-shadow duration-300`;
            newCard.setAttribute('data-type', eventData.type);
            
            // Generate random gradient colors
            const gradients = [
                'from-blue-400 to-purple-500',
                'from-purple-500 to-pink-500', 
                'from-green-400 to-blue-500',
                'from-yellow-400 to-orange-500',
                'from-red-400 to-pink-500',
                'from-indigo-400 to-purple-500'
            ];
            const randomGradient = gradients[Math.floor(Math.random() * gradients.length)];
            
            newCard.innerHTML = `
                <div class="h-32 bg-gradient-to-br ${randomGradient} relative">
                    <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                    <div class="absolute bottom-2 left-2 text-white">
                        <p class="text-xs font-medium">${eventData.name.toUpperCase()}</p>
                    </div>
                    <div class="absolute top-2 left-2">
                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full font-medium">NEW</span>
                    </div>
                    <!-- Delete Button -->
                    <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-purple-600 text-xs font-medium">${eventData.type.toUpperCase()}</span>
                        <div class="flex items-center gap-1 text-gray-500">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-xs">Manual Entry</span>
                        </div>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm mb-1">${eventData.name}</h3>
                    <p class="text-gray-600 text-xs mb-2">Organizer: ${eventData.organizer}</p>
                    <p class="text-gray-600 text-xs mb-2">Venue: ${eventData.place}</p>
                    <p class="text-gray-600 text-xs">${eventData.date}</p>
                    <div class="flex items-center justify-between mt-3">
                        <div class="flex items-center gap-1">
                            <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                            <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                            <div class="w-2 h-2 bg-purple-600 rounded-full"></div>
                        </div>
                        <button class="text-purple-600 hover:text-purple-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1a3 3 0 015.83 1M15 10h1a3 3 0 01-5.83 1M9 10V9a3 3 0 015.83-1M15 10V9a3 3 0 01-5.83 1"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            
            // Add the card to the container
            cardsContainer.appendChild(newCard);
            
            // Refresh delete listeners
            if (window.attachDeleteListeners) {
                window.attachDeleteListeners();
            }
        }
        
        // Add event to table
        function addEventToTable(eventData) {
            const tableBody = document.querySelector('tbody.divide-y.divide-gray-200');
            if (!tableBody) return;
            
            const newRow = document.createElement('tr');
            const statusClass = eventData.status === 'upcoming' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
            const statusText = eventData.status.charAt(0).toUpperCase() + eventData.status.slice(1);
            
            newRow.innerHTML = `
                <td class="px-3 py-2">
                    <p class="font-medium text-gray-900 text-sm">${eventData.name}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-900 text-sm">${eventData.organizer}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-900 text-sm">${eventData.place}</p>
                </td>
                <td class="px-3 py-2">
                    <p class="text-gray-600 text-sm">${eventData.date}</p>
                </td>
                <td class="px-3 py-2">
                    <span class="inline-block ${statusClass} text-xs px-2 py-1 rounded-full font-medium">${statusText}</span>
                </td>
            `;
            
            // Add the row to the table
            tableBody.appendChild(newRow);
        }
        
        // Make addEventToTable globally available
        window.addEventToTable = addEventToTable;
    </script>

    <!-- Main Content -->
    <div id="main-content" class="p-3 pt-2 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out overflow-x-hidden min-w-0">
        




        <div class="flex gap-4">
            <!-- Left Content -->
            <div class="flex-1">
                <!-- Event Counter Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <!-- Upcoming Events Counter -->
                    <div class="bg-white rounded-lg p-3 border border-gray-200 flex items-center justify-between shadow-md hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-700">Upcoming Events</p>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">12</div>
                    </div>
                    
                    <!-- Completed Events Counter -->
                    <div class="bg-white rounded-lg p-3 border border-gray-200 flex items-center justify-between shadow-md hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-700">Completed Events</p>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">27</div>
                    </div>
                </div>
                <!-- Continue Watching -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold text-gray-900">Your Events and Activities</h2>
                        <div class="flex items-center gap-2">
                            <!-- Filter Dropdown -->
                            <div class="relative">
                                <button id="filter-btn" class="flex items-center gap-1 px-3 py-1 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v4.586a1 1 0 01-.293.707l-2 2A1 1 0 0110 20.586V14.414a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                    </svg>
                                    <span id="filter-text">All</span>
                                    <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <!-- Filter Dropdown Menu -->
                                <div id="filter-menu" class="absolute top-full left-0 mt-1 w-32 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden">
                                    <div class="p-1">
                                        <button class="filter-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50 rounded" data-filter="all">All</button>
                                        <button class="filter-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50 rounded" data-filter="activities">Activities</button>
                                        <button class="filter-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50 rounded" data-filter="events">Events</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Navigation Arrows -->
                            <div class="flex gap-1">
                                <button class="w-6 h-6 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50">
                                    <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <button class="w-6 h-6 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50">
                                    <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <!-- Course Card 1 -->
                        <div class="event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-shadow duration-300" data-type="activities">
                            <div class="h-32 bg-gradient-to-br from-blue-400 to-purple-500 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">LEARN SOFTWARE DEVELOPMENT WITH US!</p>
                                </div>
                                <!-- Delete Button -->
                                <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="p-3">
                                <span class="text-purple-600 text-xs font-medium">ACTIVITIES</span>
                                <h3 class="font-semibold text-gray-900 mt-1 mb-2 text-sm">Beginner's Guide To Becoming A Professional Frontend Developer</h3>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <div class="flex -space-x-1">
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">+124</span>
                                    </div>
                                    <button class="w-6 h-6 bg-purple-600 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Course Card 2 -->
                        <div class="event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-shadow duration-300" data-type="events">
                            <div class="h-32 bg-gradient-to-br from-gray-800 to-gray-900 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">CODE EDITOR INTERFACE</p>
                                </div>
                                <!-- Delete Button -->
                                <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="p-3">
                                <span class="text-purple-600 text-xs font-medium">EVENTS</span>
                                <h3 class="font-semibold text-gray-900 mt-1 mb-2 text-sm">Beginner's Guide To Becoming A Professional Backend Developer</h3>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <div class="flex -space-x-1">
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">+27</span>
                                    </div>
                                    <button class="w-6 h-6 bg-purple-600 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Course Card 3 -->
                        <div class="event-card bg-white rounded-lg border border-gray-200 overflow-hidden relative group shadow-md hover:shadow-lg transition-shadow duration-300" data-type="activities">
                            <div class="h-32 bg-gradient-to-br from-blue-500 to-blue-600 relative">
                                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                                <div class="absolute bottom-2 left-2 text-white">
                                    <p class="text-xs font-medium">How To Create Your Online Course Step 3</p>
                                </div>
                                <!-- Delete Button -->
                                <button class="delete-card absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="Delete this card">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="p-3">
                                <span class="text-purple-600 text-xs font-medium">ACTIVITIES</span>
                                <h3 class="font-semibold text-gray-900 mt-1 mb-2 text-sm">Beginner's Guide To Becoming A Professional Frontend Developer</h3>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <div class="flex -space-x-1">
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                            <div class="w-4 h-4 bg-gray-300 rounded-full border border-white"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">+87</span>
                                    </div>
                                    <button class="w-6 h-6 bg-purple-600 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Events List Table -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="p-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-gray-900">Events List</h2>
                            <div class="flex items-center gap-3">
                                <!-- Search Bar -->
                                <div class="relative">
                                    <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <input type="text" placeholder="Search your events here..." class="w-48 pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                                <!-- Upload Button -->
                                <button id="events-upload-btn" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm">
                                    Upload
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">NAME OF EVENT</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">ORGANIZER</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">PLACE</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">DATE</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">STATUS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-gray-900 text-sm">Annual Tech Conference 2024</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">CPU IT Department</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">CPU Auditorium</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-600 text-sm">Jan 25, 2024</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">Upcoming</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-gray-900 text-sm">Student Orientation Program</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Student Affairs</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Main Campus</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-600 text-sm">Jan 12, 2024</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">Completed</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-gray-900 text-sm">Research Symposium</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Research Office</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-900 text-sm">Library Hall</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-gray-600 text-sm">Feb 15, 2024</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">Upcoming</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="w-64 space-y-4">
                <!-- Calendar -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold text-gray-900">Calendar</h2>
                        <div class="flex gap-1">
                            <button class="text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button class="text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Month/Year -->
                    <div class="text-center mb-3">
                        <h3 id="calendar-month-year" class="font-semibold text-gray-900">Loading...</h3>
                    </div>
                    
                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-7 gap-1 text-xs">
                        <!-- Days of week -->
                        <div class="text-center font-medium text-gray-500 py-1">S</div>
                        <div class="text-center font-medium text-gray-500 py-1">M</div>
                        <div class="text-center font-medium text-gray-500 py-1">T</div>
                        <div class="text-center font-medium text-gray-500 py-1">W</div>
                        <div class="text-center font-medium text-gray-500 py-1">T</div>
                        <div class="text-center font-medium text-gray-500 py-1">F</div>
                        <div class="text-center font-medium text-gray-500 py-1">S</div>
                        
                        <!-- Calendar days will be populated by JavaScript -->
                        <div id="calendar-days" class="contents">
                            <!-- Days will be inserted here by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-gray-900 text-sm">Upcoming Events</h3>
                        <button class="text-purple-600 text-xs font-medium hover:text-purple-700">View All</button>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-xs">Tech Conference 2024</p>
                                <p class="text-xs text-gray-500">Jan 25, 2024</p>
                                <p class="text-xs text-gray-500">CPU Auditorium</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-xs">Research Symposium</p>
                                <p class="text-xs text-gray-500">Feb 15, 2024</p>
                                <p class="text-xs text-gray-500">Library Hall</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-purple-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-xs">Student Activities Fair</p>
                                <p class="text-xs text-gray-500">Mar 10, 2024</p>
                                <p class="text-xs text-gray-500">Main Campus</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-orange-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-xs">Graduation Ceremony</p>
                                <p class="text-xs text-gray-500">Mar 25, 2024</p>
                                <p class="text-xs text-gray-500">Grand Auditorium</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <script>
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('menu-overlay');
            
            if (sidebar && overlay) {
                const isHidden = sidebar.classList.contains('-translate-x-full');
                
                if (isHidden) {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                } else {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                }
            }
        }

        function desktopToggleSidebar() {
            try {
                window.dispatchEvent(new CustomEvent('sidebar:toggle'));
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const overlay = document.getElementById('menu-overlay');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', toggleMenu);
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        sidebar.classList.add('-translate-x-full');
                        overlay.classList.add('hidden');
                    }
                });
            }
            
            // Direct file upload initialization
            console.log('Initializing file upload functionality...');
            
            // Get elements
            const browseFiles = document.getElementById('browse-files');
            const fileInput = document.getElementById('file-input');
            const dropZone = document.getElementById('drop-zone');
            const eventsUploadBtn = document.getElementById('events-upload-btn');
            const uploadModal = document.getElementById('upload-modal');
            const closeModal = document.getElementById('close-modal');
            const cancelUpload = document.getElementById('cancel-upload');
            
            console.log('Elements found:', {
                browseFiles: !!browseFiles,
                fileInput: !!fileInput,
                dropZone: !!dropZone,
                eventsUploadBtn: !!eventsUploadBtn,
                uploadModal: !!uploadModal
            });
            
            // Modal functionality
            if (eventsUploadBtn && uploadModal) {
                eventsUploadBtn.onclick = function() {
                    console.log('Upload button clicked, opening modal');
                    uploadModal.classList.remove('hidden');
                    uploadModal.classList.add('flex');
                };
            }
            
            window.closeUploadModal = function() {
                console.log('Closing modal');
                if (uploadModal) {
                    uploadModal.classList.add('hidden');
                    uploadModal.classList.remove('flex');
                }
            };
            
            if (closeModal) {
                closeModal.onclick = window.closeUploadModal;
            }
            
            if (cancelUpload) {
                cancelUpload.onclick = window.closeUploadModal;
            }
            
            // File upload functionality
            if (browseFiles && fileInput) {
                browseFiles.onclick = function(e) {
                    e.preventDefault();
                    console.log('Browse files clicked!');
                    fileInput.click();
                };
                console.log('Browse files handler attached');
            }
            
            if (dropZone && fileInput) {
                dropZone.onclick = function(e) {
                    e.preventDefault();
                    console.log('Drop zone clicked!');
                    fileInput.click();
                };
            }
            
            if (fileInput) {
                fileInput.onchange = function(e) {
                    console.log('File input changed!', e.target.files.length, 'files selected');
                    const files = Array.from(e.target.files);
                    if (files.length > 0) {
                        console.log('Processing files:', files.map(f => f.name));
                        
                        // Show selected files
                        const fileList = document.getElementById('file-list');
                        const selectedFilesContainer = document.getElementById('selected-files');
                        
                        if (fileList && selectedFilesContainer) {
                            fileList.classList.remove('hidden');
                            selectedFilesContainer.innerHTML = '';
                            
                            files.forEach(file => {
                                const fileItem = document.createElement('div');
                                fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded text-sm';
                                fileItem.innerHTML = `
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="text-gray-900">${file.name}</span>
                                    </div>
                                    <span class="text-gray-500 text-xs">${(file.size / 1024).toFixed(1)} KB</span>
                                `;
                                selectedFilesContainer.appendChild(fileItem);
                            });
                        }
                        
                        // Start OCR processing
                        if (window.processFilesWithOCR) {
                            console.log('Starting OCR processing...');
                            processFilesWithOCR(files);
                        } else {
                            console.error('processFilesWithOCR function not found');
                        }
                    }
                };
                console.log('File input change handler attached');
            }
            
            // Initialize filter functionality
            initializeFilter();
            
            // Initialize delete functionality
            initializeDeleteFunctionality();
            
            // Initialize calendar
            initializeCalendar();
        });

        let selectedFiles = [];
        
        function initializeUploadModal() {
            const eventsUploadBtn = document.getElementById('events-upload-btn');
            const uploadModal = document.getElementById('upload-modal');
            const closeModal = document.getElementById('close-modal');
            const cancelUpload = document.getElementById('cancel-upload');
            const fileInput = document.getElementById('file-input');
            const browseFiles = document.getElementById('browse-files');
            const dropZone = document.getElementById('drop-zone');
            const startUpload = document.getElementById('start-upload');
            
            // Tab elements
            const fileUploadTab = document.getElementById('file-upload-tab');
            const manualEntryTab = document.getElementById('manual-entry-tab');
            const fileUploadContent = document.getElementById('file-upload-content');
            const manualEntryContent = document.getElementById('manual-entry-content');
            const addManualEvent = document.getElementById('add-manual-event');
            
            // Open modal when upload button is clicked
            if (eventsUploadBtn && uploadModal) {
                eventsUploadBtn.addEventListener('click', function() {
                    uploadModal.classList.remove('hidden');
                    uploadModal.classList.add('flex');
                });
            }
            
            // Close modal functions
            window.closeUploadModal = function() {
                uploadModal.classList.add('hidden');
                resetModal();
            }
            
            if (closeModal) {
                closeModal.addEventListener('click', window.closeUploadModal);
            }
            
            if (cancelUpload) {
                cancelUpload.addEventListener('click', window.closeUploadModal);
            }
            
            // Close modal when clicking outside
            if (uploadModal) {
                uploadModal.addEventListener('click', function(e) {
                    if (e.target === uploadModal) {
                        window.closeUploadModal();
                    }
                });
            }
            
            // Browse files button
            if (browseFiles && fileInput) {
                browseFiles.addEventListener('click', function() {
                    console.log('Browse files clicked');
                    fileInput.click();
                });
            }
            
            // Drop zone click
            if (dropZone && fileInput) {
                dropZone.addEventListener('click', function() {
                    fileInput.click();
                });
            }
            
            // File input change
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files);
                    if (files.length > 0) {
                        selectedFiles = files;
                        displaySelectedFiles(files);
                        
                        // Automatically start OCR processing
                        processFilesWithOCR(files);
                    }
                });
            }
            
            // Drag and drop functionality
            if (dropZone) {
                setupModalDragAndDrop(dropZone);
            }
            
            // Start upload button
            if (startUpload) {
                startUpload.addEventListener('click', function() {
                    if (selectedFiles.length > 0) {
                        handleFileUpload(selectedFiles);
                    }
                });
            }
            
            // Tab switching functionality
            if (fileUploadTab && manualEntryTab) {
                fileUploadTab.addEventListener('click', function() {
                    // Switch to file upload tab
                    fileUploadTab.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
                    fileUploadTab.classList.remove('text-gray-500');
                    manualEntryTab.classList.add('text-gray-500');
                    manualEntryTab.classList.remove('text-purple-600', 'border-b-2', 'border-purple-600');
                    
                    // Show/hide content
                    fileUploadContent.classList.remove('hidden');
                    manualEntryContent.classList.add('hidden');
                    
                    // Show/hide buttons
                    startUpload.classList.remove('hidden');
                    addManualEvent.classList.add('hidden');
                });
                
                manualEntryTab.addEventListener('click', function() {
                    // Switch to manual entry tab
                    manualEntryTab.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
                    manualEntryTab.classList.remove('text-gray-500');
                    fileUploadTab.classList.add('text-gray-500');
                    fileUploadTab.classList.remove('text-purple-600', 'border-b-2', 'border-purple-600');
                    
                    // Show/hide content
                    manualEntryContent.classList.remove('hidden');
                    fileUploadContent.classList.add('hidden');
                    
                    // Show/hide buttons
                    addManualEvent.classList.remove('hidden');
                    startUpload.classList.add('hidden');
                });
            }
            
            // Manual entry form submission
            if (addManualEvent) {
                addManualEvent.addEventListener('click', function() {
                    const eventName = document.getElementById('event-name').value;
                    const organizer = document.getElementById('event-organizer').value;
                    const place = document.getElementById('event-place').value;
                    const date = document.getElementById('event-date').value;
                    const status = document.getElementById('event-status').value;
                    const type = document.getElementById('event-type').value;
                    
                    // Validate required fields
                    if (!eventName || !organizer || !place || !date || !status || !type) {
                        alert('Please fill in all fields');
                        return;
                    }
                    
                    // Create manual event entry
                    createManualEventCard({
                        name: eventName,
                        organizer: organizer,
                        place: place,
                        date: date,
                        status: status,
                        type: type
                    });
                    
                    // Add to events table
                    addEventToTable({
                        name: eventName,
                        organizer: organizer,
                        place: place,
                        date: date,
                        status: status
                    });
                    
                    // Show success message
                    showUploadSuccess('Event added successfully!');
                    
                    // Close modal and reset
                    window.closeUploadModal();
                });
            }
        }
        
        function setupModalDragAndDrop(dropZone) {
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            // Highlight drop area
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, function() {
                    dropZone.classList.add('border-purple-400', 'bg-purple-50');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, function() {
                    dropZone.classList.remove('border-purple-400', 'bg-purple-50');
                }, false);
            });
            
            // Handle dropped files
            dropZone.addEventListener('drop', function(e) {
                const files = Array.from(e.dataTransfer.files);
                if (files.length > 0) {
                    selectedFiles = files;
                    displaySelectedFiles(files);
                    
                    // Automatically start OCR processing
                    processFilesWithOCR(files);
                }
            }, false);
        }
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        function displaySelectedFiles(files) {
            const fileList = document.getElementById('file-list');
            const selectedFilesContainer = document.getElementById('selected-files');
            
            if (fileList && selectedFilesContainer) {
                fileList.classList.remove('hidden');
                selectedFilesContainer.innerHTML = '';
                
                files.forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded text-sm';
                    fileItem.innerHTML = `
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-gray-900">${file.name}</span>
                            <span class="text-gray-500">(${formatFileSize(file.size)})</span>
                        </div>
                        <button class="text-red-500 hover:text-red-700" onclick="removeFile(${index})">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    `;
                    selectedFilesContainer.appendChild(fileItem);
                });
            }
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            if (selectedFiles.length > 0) {
                displaySelectedFiles(selectedFiles);
            } else {
                document.getElementById('file-list').classList.add('hidden');
                disableUploadButton();
            }
        }
        
        function enableUploadButton() {
            const startUpload = document.getElementById('start-upload');
            if (startUpload) {
                startUpload.disabled = false;
            }
        }
        
        function disableUploadButton() {
            const startUpload = document.getElementById('start-upload');
            if (startUpload) {
                startUpload.disabled = true;
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function handleFileUpload(files) {
            const startUpload = document.getElementById('start-upload');
            
            // Disable upload button
            if (startUpload) {
                startUpload.disabled = true;
                startUpload.textContent = 'Processing...';
            }
            
            // Process files with OCR first
            processFilesWithOCR(files);
        }
        
        async function processFilesWithOCR(files) {
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
                        const ocrResult = await performOCR(file);
                        const category = analyzeContent(ocrResult.text);
                        
                        processedFiles.push({
                            file: file,
                            extractedText: ocrResult.text,
                            category: category,
                            confidence: ocrResult.confidence
                        });
                    } else {
                        // For non-image files, categorize based on filename
                        const category = categorizeByFilename(file.name);
                        processedFiles.push({
                            file: file,
                            extractedText: 'Non-image file',
                            category: category,
                            confidence: 50
                        });
                    }
                } catch (error) {
                    console.error('OCR processing failed:', error);
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
            
            // Event keywords
            const eventKeywords = [
                'meeting', 'conference', 'seminar', 'workshop', 'event', 'ceremony',
                'celebration', 'gathering', 'assembly', 'symposium', 'forum',
                'presentation', 'lecture', 'webinar', 'summit', 'convention'
            ];
            
            // Activity keywords
            const activityKeywords = [
                'assignment', 'homework', 'project', 'task', 'exercise', 'activity',
                'practice', 'drill', 'quiz', 'exam', 'test', 'assessment',
                'coursework', 'study', 'lesson', 'tutorial', 'lab', 'experiment'
            ];
            
            let eventScore = 0;
            let activityScore = 0;
            
            // Count keyword matches
            eventKeywords.forEach(keyword => {
                if (lowerText.includes(keyword)) {
                    eventScore++;
                }
            });
            
            activityKeywords.forEach(keyword => {
                if (lowerText.includes(keyword)) {
                    activityScore++;
                }
            });
            
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
        
        function extractEventName(text) {
            // Clean and split text into lines
            const lines = text.split('\n').map(line => line.trim()).filter(line => line.length > 0);
            
            // Common patterns for event names
            const eventPatterns = [
                // Direct event name patterns
                /^(.+?)\s*(?:event|conference|meeting|seminar|workshop|ceremony|celebration|gathering|symposium|forum|presentation|lecture|webinar|summit|convention)$/i,
                // Title case lines (likely event names)
                /^[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*(?:\s+\d{4})?$/,
                // Lines with event keywords
                /(.+?)\s*(?:event|conference|meeting|seminar|workshop|ceremony|celebration|gathering|symposium|forum|presentation|lecture|webinar|summit|convention)/i,
                // Lines that start with common event prefixes
                /^(?:annual|monthly|weekly|daily|special|grand|international|national|regional|local)?\s*(.+?)(?:\s*event|\s*conference|\s*meeting|\s*seminar|\s*workshop)?$/i
            ];
            
            // Look for event name in the first few lines (titles are usually at the top)
            for (let i = 0; i < Math.min(lines.length, 5); i++) {
                const line = lines[i];
                
                // Skip very short lines or lines with only numbers/symbols
                if (line.length < 3 || /^[\d\s\-_.,!@#$%^&*()]+$/.test(line)) {
                    continue;
                }
                
                // Try each pattern
                for (const pattern of eventPatterns) {
                    const match = line.match(pattern);
                    if (match && match[1]) {
                        let eventName = match[1].trim();
                        // Clean up the extracted name
                        eventName = eventName.replace(/[^\w\s]/g, ' ').replace(/\s+/g, ' ').trim();
                        if (eventName.length > 3 && eventName.length < 100) {
                            return eventName;
                        }
                    }
                }
                
                // If no pattern matches but the line looks like a title, use it
                if (line.length > 5 && line.length < 80 && /^[A-Z]/.test(line)) {
                    // Check if it contains mostly words (not numbers or symbols)
                    const wordCount = (line.match(/[a-zA-Z]+/g) || []).length;
                    const totalLength = line.length;
                    if (wordCount > 0 && (wordCount * 4) > totalLength) {
                        return line.replace(/[^\w\s]/g, ' ').replace(/\s+/g, ' ').trim();
                    }
                }
            }
            
            // Fallback: look for any line with event-related keywords
            for (const line of lines) {
                const lowerLine = line.toLowerCase();
                if ((lowerLine.includes('event') || lowerLine.includes('conference') || 
                     lowerLine.includes('meeting') || lowerLine.includes('seminar') || 
                     lowerLine.includes('workshop')) && line.length > 5 && line.length < 100) {
                    return line.replace(/[^\w\s]/g, ' ').replace(/\s+/g, ' ').trim();
                }
            }
            
            // Final fallback: use the first substantial line
            for (const line of lines) {
                if (line.length > 5 && line.length < 80) {
                    return line.replace(/[^\w\s]/g, ' ').replace(/\s+/g, ' ').trim();
                }
            }
            
            return 'Untitled Event';
        }
        
        function extractEventDetails(text) {
            const lines = text.split('\n').map(line => line.trim()).filter(line => line.length > 0);
            const lowerText = text.toLowerCase();
            
            let organizer = 'System Generated';
            let place = 'Not specified';
            let date = new Date().toISOString().split('T')[0]; // Default to today
            
            // Extract organizer
            const organizerPatterns = [
                /(?:organized by|organizer|host|hosted by|by)[\s:]*(.+?)(?:\n|$)/i,
                /(?:presented by|presenter|speaker)[\s:]*(.+?)(?:\n|$)/i,
                /(?:company|organization|institution|university|college)[\s:]*(.+?)(?:\n|$)/i
            ];
            
            for (const pattern of organizerPatterns) {
                const match = text.match(pattern);
                if (match && match[1]) {
                    organizer = match[1].trim().replace(/[^\w\s]/g, ' ').replace(/\s+/g, ' ').trim();
                    if (organizer.length > 3 && organizer.length < 50) {
                        break;
                    }
                }
            }
            
            // Extract place/venue
            const placePatterns = [
                /(?:venue|location|place|address|at|held at)[\s:]*(.+?)(?:\n|$)/i,
                /(?:room|hall|auditorium|center|building)[\s:]*(.+?)(?:\n|$)/i,
                /(?:campus|university|college|school)[\s:]*(.+?)(?:\n|$)/i
            ];
            
            for (const pattern of placePatterns) {
                const match = text.match(pattern);
                if (match && match[1]) {
                    place = match[1].trim().replace(/[^\w\s]/g, ' ').replace(/\s+/g, ' ').trim();
                    if (place.length > 3 && place.length < 50) {
                        break;
                    }
                }
            }
            
            // Extract date
            const datePatterns = [
                // Standard date formats
                /\b(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})\b/g,
                /\b(\d{2,4}[-\/]\d{1,2}[-\/]\d{1,2})\b/g,
                // Month day, year format
                /\b((?:january|february|march|april|may|june|july|august|september|october|november|december)\s+\d{1,2},?\s+\d{4})\b/gi,
                // Day month year format
                /\b(\d{1,2}\s+(?:january|february|march|april|may|june|july|august|september|october|november|december)\s+\d{4})\b/gi
            ];
            
            for (const pattern of datePatterns) {
                const matches = text.match(pattern);
                if (matches && matches[0]) {
                    try {
                        const parsedDate = new Date(matches[0]);
                        if (!isNaN(parsedDate.getTime())) {
                            date = parsedDate.toISOString().split('T')[0];
                            break;
                        }
                    } catch (e) {
                        // Continue to next pattern
                    }
                }
            }
            
            return { organizer, place, date };
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
        
        function displayOCRResults(processedFiles, container) {
            let resultsHTML = '';
            
            processedFiles.forEach(item => {
                const categoryBadge = item.category === 'events' 
                    ? '<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">EVENT</span>'
                    : '<span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">ACTIVITY</span>';
                
                resultsHTML += `
                    <div class="mb-3 p-2 border border-gray-200 rounded">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-sm">${item.file.name}</span>
                            ${categoryBadge}
                        </div>
                        <div class="text-xs text-gray-600">
                            ${item.extractedText ? item.extractedText.substring(0, 100) + '...' : 'No text detected'}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = resultsHTML;
        }
        
        function startUploadProcess(processedFiles) {
            const uploadProgress = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const startUpload = document.getElementById('start-upload');
            
            // Show upload progress
            if (uploadProgress) {
                uploadProgress.classList.remove('hidden');
            }
            
            // Update button text
            if (startUpload) {
                startUpload.textContent = 'Creating Cards...';
            }
            
            // Simulate upload progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 100) progress = 100;
                
                if (progressBar) {
                    progressBar.style.width = progress + '%';
                }
                if (progressText) {
                    progressText.textContent = Math.round(progress) + '%';
                }
                
                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        // Create cards from processed files
                        createCardsFromOCR(processedFiles);
                        showUploadSuccess(processedFiles.length);
                        setTimeout(window.closeUploadModal, 2000);
                    }, 500);
                }
            }, 200);
        }
        
        // Make processFilesWithOCR globally available
        window.processFilesWithOCR = processFilesWithOCR;
        
        function createCardsFromOCR(processedFiles) {
            // Skip card creation for now, focus only on Events List table
            
            processedFiles.forEach(item => {
                // Extract the actual event name from the OCR text
                const eventName = extractEventName(item.extractedText);
                const title = eventName || generateTitle(item.extractedText, item.file.name);
                
                // Extract additional event details from OCR text
                const eventDetails = extractEventDetails(item.extractedText);
                
                // Add to Events List table only
                const eventData = {
                    name: title,
                    organizer: eventDetails.organizer,
                    place: eventDetails.place,
                    date: eventDetails.date,
                    status: 'upcoming' // Default status
                };
                addEventToTable(eventData);
            });
        }
        
        function generateTitle(extractedText, filename) {
            // Try to extract a meaningful title from the text
            if (extractedText && extractedText.trim()) {
                const lines = extractedText.split('\n').filter(line => line.trim().length > 0);
                
                // Look for title-like text (usually the first few meaningful lines)
                for (let line of lines) {
                    const trimmedLine = line.trim();
                    if (trimmedLine.length > 10 && trimmedLine.length < 100) {
                        // Clean up the line and return as title
                        return trimmedLine.charAt(0).toUpperCase() + trimmedLine.slice(1);
                    }
                }
                
                // Fallback to first line if available
                if (lines.length > 0) {
                    return lines[0].trim().substring(0, 50) + '...';
                }
            }
            
            // Fallback to filename-based title
            const nameWithoutExt = filename.replace(/\.[^/.]+$/, '');
            return nameWithoutExt.charAt(0).toUpperCase() + nameWithoutExt.slice(1).replace(/[-_]/g, ' ');
        }
        
        function showUploadSuccess(fileCount) {
            const uploadProgress = document.getElementById('upload-progress');
            const startUpload = document.getElementById('start-upload');
            
            if (uploadProgress) {
                uploadProgress.innerHTML = `
                    <div class="flex items-center justify-center text-green-600">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        ${fileCount} file(s) uploaded successfully!
                    </div>
                `;
            }
            
            if (startUpload) {
                startUpload.textContent = 'Complete!';
                startUpload.classList.add('bg-green-600', 'hover:bg-green-700');
                startUpload.classList.remove('bg-purple-600', 'hover:bg-purple-700');
            }
        }
        
        function resetModal() {
            const fileList = document.getElementById('file-list');
            const uploadProgress = document.getElementById('upload-progress');
            const ocrProgress = document.getElementById('ocr-progress');
            const ocrResults = document.getElementById('ocr-results');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const startUpload = document.getElementById('start-upload');
            const fileInput = document.getElementById('file-input');
            
            selectedFiles = [];
            
            if (fileList) fileList.classList.add('hidden');
            if (ocrProgress) ocrProgress.classList.add('hidden');
            if (ocrResults) ocrResults.classList.add('hidden');
            if (uploadProgress) {
                uploadProgress.classList.add('hidden');
                uploadProgress.innerHTML = `
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900">Uploading...</span>
                        <span id="progress-text" class="text-sm text-gray-500">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-purple-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                `;
            }
            if (startUpload) {
                startUpload.disabled = true;
                startUpload.textContent = 'Upload Files';
                startUpload.classList.remove('bg-green-600', 'hover:bg-green-700');
                startUpload.classList.add('bg-purple-600', 'hover:bg-purple-700');
            }
            if (fileInput) {
                fileInput.value = '';
            }
            
            // Reset manual entry form
            const eventName = document.getElementById('event-name');
            const organizer = document.getElementById('event-organizer');
            const place = document.getElementById('event-place');
            const date = document.getElementById('event-date');
            const status = document.getElementById('event-status');
            const type = document.getElementById('event-type');
            
            if (eventName) eventName.value = '';
            if (organizer) organizer.value = '';
            if (place) place.value = '';
            if (date) date.value = '';
            if (status) status.value = '';
            if (type) type.value = '';
            
            // Reset to file upload tab
            const fileUploadTab = document.getElementById('file-upload-tab');
            const manualEntryTab = document.getElementById('manual-entry-tab');
            const fileUploadContent = document.getElementById('file-upload-content');
            const manualEntryContent = document.getElementById('manual-entry-content');
            const addManualEvent = document.getElementById('add-manual-event');
            const startUpload = document.getElementById('start-upload');
            
            if (fileUploadTab && manualEntryTab) {
                fileUploadTab.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
                fileUploadTab.classList.remove('text-gray-500');
                manualEntryTab.classList.add('text-gray-500');
                manualEntryTab.classList.remove('text-purple-600', 'border-b-2', 'border-purple-600');
                
                fileUploadContent.classList.remove('hidden');
                manualEntryContent.classList.add('hidden');
                
                if (startUpload) startUpload.classList.remove('hidden');
                if (addManualEvent) addManualEvent.classList.add('hidden');
            }
        }
        
        function initializeFilter() {
            const filterBtn = document.getElementById('filter-btn');
            const filterMenu = document.getElementById('filter-menu');
            const filterText = document.getElementById('filter-text');
            const filterOptions = document.querySelectorAll('.filter-option');
            
            // Toggle filter dropdown
            if (filterBtn && filterMenu) {
                filterBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    filterMenu.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    filterMenu.classList.add('hidden');
                });
                
                // Prevent dropdown from closing when clicking inside
                filterMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Handle filter option clicks
            filterOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const filterValue = this.getAttribute('data-filter');
                    const filterLabel = this.textContent;
                    
                    // Update filter button text
                    if (filterText) {
                        filterText.textContent = filterLabel;
                    }
                    
                    // Apply filter
                    applyFilter(filterValue);
                    
                    // Close dropdown
                    filterMenu.classList.add('hidden');
                });
            });
        }
        
        function applyFilter(filterValue) {
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                if (filterValue === 'all') {
                    card.style.display = 'block';
                } else {
                    const cardType = card.getAttribute('data-type');
                    if (cardType === filterValue) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }
        
        function initializeDeleteFunctionality() {
            let cardToDelete = null;
            const deleteModal = document.getElementById('delete-modal');
            const cancelDelete = document.getElementById('cancel-delete');
            const confirmDelete = document.getElementById('confirm-delete');
            
            // Add event listeners to existing delete buttons
            addDeleteListeners();
            
            // Cancel delete
            if (cancelDelete) {
                cancelDelete.addEventListener('click', function() {
                    closeDeleteModal();
                });
            }
            
            // Confirm delete
            if (confirmDelete) {
                confirmDelete.addEventListener('click', function() {
                    if (cardToDelete) {
                        deleteCard(cardToDelete);
                        closeDeleteModal();
                    }
                });
            }
            
            // Close modal when clicking outside
            if (deleteModal) {
                deleteModal.addEventListener('click', function(e) {
                    if (e.target === deleteModal) {
                        closeDeleteModal();
                    }
                });
            }
            
            function addDeleteListeners() {
                const deleteButtons = document.querySelectorAll('.delete-card');
                deleteButtons.forEach(button => {
                    // Remove existing listeners to prevent duplicates
                    button.removeEventListener('click', handleDeleteClick);
                    button.addEventListener('click', handleDeleteClick);
                });
            }
            
            function handleDeleteClick(e) {
                e.preventDefault();
                e.stopPropagation();
                cardToDelete = this.closest('.event-card');
                if (deleteModal) {
                    deleteModal.classList.remove('hidden');
                }
            }
            
            function closeDeleteModal() {
                if (deleteModal) {
                    deleteModal.classList.add('hidden');
                }
                cardToDelete = null;
            }
            
            function deleteCard(card) {
                // Add fade-out animation
                card.style.transition = 'all 0.3s ease-out';
                card.style.transform = 'scale(0.95)';
                card.style.opacity = '0';
                
                // Remove the card after animation
                setTimeout(() => {
                    if (card.parentNode) {
                        card.parentNode.removeChild(card);
                        showDeleteNotification();
                    }
                }, 300);
            }
            
            function showDeleteNotification() {
                const notification = document.createElement('div');
                notification.className = 'fixed top-20 right-4 z-[100] px-4 py-3 rounded-lg shadow-lg bg-red-500 text-white transition-all duration-300 transform translate-x-full';
                notification.textContent = 'Card deleted successfully';
                
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.classList.remove('translate-x-full');
                }, 100);
                
                // Auto remove after 3 seconds
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
            }
            
            // Make this function globally accessible for dynamically created cards
            window.refreshDeleteListeners = addDeleteListeners;
        }

        // Calendar initialization function
        function initializeCalendar() {
            try {
                const now = new Date();
                const currentYear = now.getFullYear();
                const currentMonth = now.getMonth();
                const currentDay = now.getDate();
                
                // Month names
                const monthNames = [
                    "January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ];
                
                // Update month/year display
                const monthYearElement = document.getElementById('calendar-month-year');
                if (monthYearElement) {
                    monthYearElement.textContent = `${monthNames[currentMonth]} ${currentYear}`;
                }
                
                // Generate calendar days
                generateCalendarDays(currentYear, currentMonth, currentDay);
            } catch (error) {
                console.error('Calendar error:', error);
            }
        }
        
        // EMERGENCY CALENDAR FIX - This will definitely work
        setTimeout(function() {
            const monthYearElement = document.getElementById('calendar-month-year');
            const calendarDays = document.getElementById('calendar-days');
            
            if (monthYearElement && monthYearElement.textContent === 'Loading...') {
                const now = new Date();
                const monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"];
                monthYearElement.textContent = `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
                
                if (calendarDays) {
                    const today = now.getDate();
                    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).getDay();
                    const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
                    
                    let html = '';
                    
                    // Add empty cells for days before the first day of the month
                    for (let i = 0; i < firstDay; i++) {
                        html += '<div class="text-center py-1 text-gray-400"></div>';
                    }
                    
                    // Add days of the current month
                    for (let day = 1; day <= daysInMonth; day++) {
                        if (day === today) {
                            html += `<div class="text-center py-1 bg-purple-600 text-white rounded font-medium">${day}</div>`;
                        } else {
                            html += `<div class="text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer">${day}</div>`;
                        }
                    }
                    
                    calendarDays.innerHTML = html;
                }
            }
        }, 500);
        
        function generateCalendarDays(year, month, today) {
            const calendarDays = document.getElementById('calendar-days');
            if (!calendarDays) return;
            calendarDays.innerHTML = '';
            
            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();
            
            // Add empty cells for days before the first day of the month
            for (let i = firstDay - 1; i >= 0; i--) {
                const dayElement = document.createElement('div');
                dayElement.className = 'text-center py-1 text-gray-400';
                dayElement.textContent = daysInPrevMonth - i;
                calendarDays.appendChild(dayElement);
            }
            
            // Add days of the current month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                dayElement.textContent = day;
                
                if (day === today) {
                    // Highlight today
                    dayElement.className = 'text-center py-1 bg-purple-600 text-white rounded font-medium';
                } else {
                    dayElement.className = 'text-center py-1 text-gray-900 hover:bg-gray-100 rounded cursor-pointer';
                }
                
                calendarDays.appendChild(dayElement);
            }
            
            // Add empty cells for remaining days to complete the grid
            const totalCells = calendarDays.children.length;
            const remainingCells = 42 - totalCells; // 6 rows × 7 days = 42 cells
            
            for (let i = 1; i <= remainingCells && i <= 14; i++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'text-center py-1 text-gray-400';
                dayElement.textContent = i;
                calendarDays.appendChild(dayElement);
            }
        }
        
        // Delete functionality for cards
        function initializeDeleteFunctionality() {
            console.log('Initializing delete functionality...');
            
            // Get modal elements
            const deleteModal = document.getElementById('delete-modal');
            const cancelDeleteBtn = document.getElementById('cancel-delete');
            const confirmDeleteBtn = document.getElementById('confirm-delete');
            
            let cardToDelete = null;
            
            // Add click listeners to all delete buttons
            function attachDeleteListeners() {
                const deleteButtons = document.querySelectorAll('.delete-card');
                console.log('Found', deleteButtons.length, 'delete buttons');
                
                deleteButtons.forEach(button => {
                    // Remove existing listeners to prevent duplicates
                    button.removeEventListener('click', handleDeleteClick);
                    // Add new listener
                    button.addEventListener('click', handleDeleteClick);
                });
            }
            
            function handleDeleteClick(event) {
                event.stopPropagation();
                event.preventDefault();
                
                console.log('Delete button clicked');
                
                // Find the parent card
                cardToDelete = event.target.closest('.event-card');
                
                if (cardToDelete) {
                    console.log('Card to delete:', cardToDelete);
                    // Show delete confirmation modal
                    if (deleteModal) {
                        deleteModal.classList.remove('hidden');
                        deleteModal.classList.add('flex');
                    }
                } else {
                    console.error('Could not find parent card');
                }
            }
            
            // Cancel delete
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', function() {
                    console.log('Delete cancelled');
                    if (deleteModal) {
                        deleteModal.classList.add('hidden');
                        deleteModal.classList.remove('flex');
                    }
                    cardToDelete = null;
                });
            }
            
            // Confirm delete
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    console.log('Delete confirmed');
                    
                    if (cardToDelete) {
                        // Add fade out animation
                        cardToDelete.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        cardToDelete.style.opacity = '0';
                        cardToDelete.style.transform = 'scale(0.95)';
                        
                        // Remove the card after animation
                        setTimeout(() => {
                            cardToDelete.remove();
                            console.log('Card deleted successfully');
                        }, 300);
                        
                        // Hide modal
                        if (deleteModal) {
                            deleteModal.classList.add('hidden');
                            deleteModal.classList.remove('flex');
                        }
                        
                        cardToDelete = null;
                    }
                });
            }
            
            // Close modal when clicking outside
            if (deleteModal) {
                deleteModal.addEventListener('click', function(event) {
                    if (event.target === deleteModal) {
                        deleteModal.classList.add('hidden');
                        deleteModal.classList.remove('flex');
                        cardToDelete = null;
                    }
                });
            }
            
            // Initial attachment
            attachDeleteListeners();
            
            // Re-attach listeners when new cards are added
            window.attachDeleteListeners = attachDeleteListeners;
            
            console.log('Delete functionality initialized');
        }
        
        // Initialize delete functionality when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeDeleteFunctionality);
        } else {
            initializeDeleteFunctionality();
        }
        
        // Also initialize immediately with timeout as backup
        setTimeout(initializeDeleteFunctionality, 100);
    </script>

    <style>
    .ml-64{ margin-left:16rem; }
    .pl-64{ padding-left:16rem; }
    </style>

</body>

</html>
