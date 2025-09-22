<?php
// Include required files first
require_once 'config/documents_config.php';
require_once 'classes/DateTimeUtility.php';

// Start session for authentication
session_start();

// Check if user is logged in (more permissive for demo)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    // Set default session for demo purposes
    $_SESSION['user_id'] = DocumentsConfig::$DEFAULT_SESSION['user_id'];
    $_SESSION['user_role'] = DocumentsConfig::$DEFAULT_SESSION['user_role'];
}

// Check user permissions for documents management (dynamic roles)
$allowed_roles = DocumentsConfig::getAllowedRoles();
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    // Set default role for demo
    $_SESSION['user_role'] = 'user';
}

// Validate session token for security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(DocumentsConfig::$SECURITY['csrf_token_length']));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/lazy-loader.js"></script>
    <script src="js/error-handler.js"></script>
    <script src="js/security-utils.js"></script>
    <script src="js/awards-check.js"></script>
    <script src="js/documents-management.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    
    <script src="js/document-analyzer.js"></script>
    <script src="js/modal-handlers.js"></script>
    <script src="js/text-config.js"></script>
    <script src="js/date-time-utility.js"></script>
    <script src="js/pdf-text-extractor.js"></script>
    <script>
        // Dynamic configuration from PHP - MUST be loaded before document-categorizer.js
        const DocumentsConfig = {
            categories: <?php echo json_encode(DocumentsConfig::getCategoryRulesForJS()); ?>,
            categoriesByPriority: <?php echo json_encode(DocumentsConfig::getCategoriesByPriority()); ?>,
            userRoles: <?php echo json_encode(DocumentsConfig::$USER_ROLES); ?>,
            fileTypes: <?php echo json_encode(DocumentsConfig::$FILE_TYPES); ?>,
            filters: <?php echo json_encode(DocumentsConfig::$FILTERS); ?>,
            pagination: <?php echo json_encode(DocumentsConfig::$PAGINATION); ?>,
            ui: <?php echo json_encode(DocumentsConfig::$UI); ?>,
            security: <?php echo json_encode(DocumentsConfig::$SECURITY); ?>,
            api: {
                upload: 'api/documents.php',
                list: 'api/documents.php',
                delete: 'api/documents.php',
                search: 'api/documents.php'
            }
        };
    </script>
    <script src="js/enhanced-document-upload.js"></script>
    <script src="document-categorizer.js"></script>
    <title>LILAC Documents</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <link rel="stylesheet" href="css/documents.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script>


        // Global variables for pagination and filtering
        let currentFilters = {
            page: 1,
            limit: DocumentsConfig.pagination.default_limit,
            search: '',
            category: '',
            sort_by: DocumentsConfig.ui.default_sort_by,
            sort_order: DocumentsConfig.ui.default_sort_order,
            view: DocumentsConfig.filters.views[0], // Use first view from config
            // Advanced filters
            file_group: DocumentsConfig.ui.default_file_group,
            file_types: [],
            date_from: '',
            date_to: '',
            size_min: '',
            size_max: '',
            categories: []
        };
        
        // Track file names to handle duplicates
        let existingFileNames = new Set();
        
        // View mode state (now using dynamic configuration)
        let currentViewMode = DocumentsConfig.ui.default_view_mode;
        
        // Local state for recent uploads (no API dependency)
        let recentUploads = [];
        
        // Bulk selection state
        let selectedDocuments = new Set();
        let selectAllChecked = false;
        
        // Document editor state
        let currentDocumentContent = '';
        let isEditorOpen = false;
        
        let debounceTimer;
        let availableCategories = DocumentsConfig.categoriesByPriority || [];
        let currentDocuments = [];

        // Initialize documents functionality
        document.addEventListener('DOMContentLoaded', function() {
            try { document.documentElement.style.fontSize = '14px'; } catch(_) {}
            console.log('🚀 DOM Content Loaded - Initializing documents page');
            initializeEventListeners();

            // Load initial data
            console.log('📋 Loading categories...');
            loadCategories();
            console.log('📋 Loading documents...');
            loadDocuments();
            console.log('📊 Loading stats...');
            loadStats();

            updateCurrentDate();
            
            // Initialize progress bars
            initializeProgressBars();
            
            // Update date every minute
            setInterval(updateCurrentDate, 60000);
            
            // Ensure LILAC notifications appear below navbar
            setTimeout(function() {
                if (window.lilacNotifications && window.lilacNotifications.container) {
                    window.lilacNotifications.container.style.top = '80px';
                    window.lilacNotifications.container.style.zIndex = '99999';
                }
            }, 500);
            
            // Disable all notifications permanently
            window.suppressNotifications();
            
            // Sidebar layout is now handled globally by LILACSidebar
            // No local sidebar management needed

            // New: Quick chips behavior
            document.querySelectorAll('.chip-filter').forEach(function(btn){
                btn.classList.add('chip-enter');
                btn.addEventListener('click', function(){
                    var group = this.getAttribute('data-group') || 'all';
                    currentFilters.file_group = group;
                    currentFilters.page = 1;
                    loadDocuments();
                    updateActiveFilterChips();
                    // visual state
                    document.querySelectorAll('.chip-filter').forEach(function(b){ b.classList.remove('chip-active'); b.setAttribute('aria-pressed','false'); });
                    this.classList.add('chip-active');
                    this.setAttribute('aria-pressed','true');
                    // subtle pop
                    this.style.transform = 'scale(0.98)';
                    setTimeout(()=>{ this.style.transform = 'scale(1)'; },120);
                });
            });



            // New: Filters panel
            var filtersTrigger = document.getElementById('filters-trigger');
            var filtersPanel = document.getElementById('filters-panel');
            var filtersApply = document.getElementById('filters-apply');
            var filtersReset = document.getElementById('filters-reset');
            var filtersClose = document.getElementById('filters-close');
            if (filtersTrigger && filtersPanel) {
                filtersTrigger.addEventListener('click', function(){ filtersPanel.classList.toggle('hidden'); });
                document.addEventListener('click', function(e){
                    if (!filtersPanel.contains(e.target) && !filtersTrigger.contains(e.target)) { filtersPanel.classList.add('hidden'); }
                });
            }
            if (filtersApply) {
                filtersApply.addEventListener('click', function(){
                    // categories from tag chips
                    var selectedCats = Array.from(document.querySelectorAll('#filter-categories .selected')).map(function(el){ return el.textContent; });
                    currentFilters.categories = selectedCats;
                    // file types
                    var types = [];
                    document.querySelectorAll('.filter-type:checked').forEach(function(cb){
                        var vals = (cb.value||'').split('|');
                        vals.forEach(function(v){ if (v) types.push(v); });
                    });
                    currentFilters.file_types = types;
                    // dates
                    var df = document.getElementById('filter-date-from');
                    var dt = document.getElementById('filter-date-to');
                    currentFilters.date_from = df ? df.value : '';
                    currentFilters.date_to = dt ? dt.value : '';
                    currentFilters.page = 1;
                    loadDocuments();
                    updateActiveFilterChips();
                    filtersPanel.classList.add('hidden');
                });
            }
            if (filtersReset) {
                filtersReset.addEventListener('click', function(){
                    currentFilters.categories = [];
                    currentFilters.file_types = [];
                    currentFilters.date_from = '';
                    currentFilters.date_to = '';
                    // clear UI
                    document.querySelectorAll('.filter-type').forEach(function(cb){ cb.checked = false; });
                    var df = document.getElementById('filter-date-from'); if (df) df.value = '';
                    var dt = document.getElementById('filter-date-to'); if (dt) dt.value = '';
                    updateActiveFilterChips();
                });
            }
            if (filtersClose) { filtersClose.addEventListener('click', function(){ filtersPanel.classList.add('hidden'); }); }

            // Populate category chips inside filters
            (function renderFilterCategories(){
                var wrap = document.getElementById('filter-categories');
                if (!wrap || !Array.isArray(availableCategories)) return;
                wrap.innerHTML = '';
                availableCategories.forEach(function(cat){
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'px-2 py-1 text-xs rounded-full border hover:bg-gray-50';
                    b.textContent = cat;
                    b.addEventListener('click', function(){ b.classList.toggle('selected'); });
                    wrap.appendChild(b);
                });
            })();

            // Reload categorizer rules from PHP config after page load
            if (window.documentCategorizer) {
                window.documentCategorizer.reloadFromConfig();
                console.log('DocumentCategorizer rules reloaded from PHP config');
            } else {
                console.error('DocumentCategorizer not found - document-categorizer.js may not be loaded');
            }

            // Debug: Show available categories
            console.log('Available categories:', window.documentCategorizer ? window.documentCategorizer.getCategories() : 'No categorizer');
        });

        function updateActiveFilterChips(){
            var container = document.getElementById('active-filter-chips');
            var badge = document.getElementById('filters-badge');
            if (!container) return;
            var chips = [];
            if (currentFilters.file_group && currentFilters.file_group !== 'all') { chips.push({k:'group', label: currentFilters.file_group}); }
            (currentFilters.categories||[]).forEach(function(c){ chips.push({k:'category', label:c}); });
            (currentFilters.file_types||[]).forEach(function(t){ chips.push({k:'type', label:t}); });
            if (currentFilters.date_from) { chips.push({k:'date_from', label:'from '+currentFilters.date_from}); }
            if (currentFilters.date_to) { chips.push({k:'date_to', label:'to '+currentFilters.date_to}); }

            container.innerHTML = '';
            if (chips.length === 0) {
                container.classList.add('hidden');
                if (badge) badge.classList.add('hidden');
                return;
            }
            container.classList.remove('hidden');
            if (badge){ badge.classList.remove('hidden'); badge.textContent = String(chips.length); }
            chips.forEach(function(ch){
                var el = document.createElement('button');
                el.className = 'px-2 py-1 text-xs rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center gap-1';
                el.innerHTML = '<span>'+ch.label+'</span><span aria-hidden="true">×</span>';
                el.addEventListener('click', function(){
                    // remove filter
                    if (ch.k==='group') { currentFilters.file_group='all'; }
                    if (ch.k==='category') { currentFilters.categories = currentFilters.categories.filter(function(c){ return c!==ch.label; }); }
                    if (ch.k==='type') { currentFilters.file_types = currentFilters.file_types.filter(function(t){ return t!==ch.label; }); }
                    if (ch.k==='date_from') { currentFilters.date_from=''; }
                    if (ch.k==='date_to') { currentFilters.date_to=''; }

                    currentFilters.page = 1;
                    loadDocuments();
                    updateActiveFilterChips();
                });
                container.appendChild(el);
            });
            // Clear all
            var clearBtn = document.createElement('button');
            clearBtn.className = 'px-2 py-1 text-xs rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700';
            clearBtn.textContent = 'Clear all';
            clearBtn.addEventListener('click', function(){
                currentFilters.file_group='all';
                currentFilters.categories=[];
                currentFilters.file_types=[];
                currentFilters.date_from='';
                currentFilters.date_to='';

                currentFilters.page = 1;
                loadDocuments();
                updateActiveFilterChips();
            });
            container.appendChild(clearBtn);
        }

        function updateCurrentDate() {
            const now = new Date();
            const dateElement = document.getElementById('current-date');
            if (dateElement) {
                dateElement.textContent = now.toLocaleDateString(DocumentsConfig.ui.date_format, DocumentsConfig.ui.date_options);
            }
        }

        function initializeProgressBars() {
            // Set up progress bars with data attributes
            const progressBars = document.querySelectorAll('[data-progress]');
            progressBars.forEach(bar => {
                const progress = bar.getAttribute('data-progress');
                if (progress !== null) {
                    bar.style.setProperty('--progress-width', progress + '%');
                    bar.style.width = progress + '%';
                }
            });
        }

        function initializeEventListeners() {
            // Tab navigation
            const tabButtons = document.querySelectorAll('[data-tab]');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tab = this.getAttribute('data-tab');
                    switchTab(tab);
                });
            });

            // View toggle buttons
            const viewButtons = document.querySelectorAll('[data-view]');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const view = this.getAttribute('data-view');
                    switchView(view);
                });
            });



            // Search functionality with debounce
            const searchInput = document.getElementById('search-documents');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    const searchValue = this.value.trim();
                    
                    // Show/hide clear button
                    const clearBtn = document.getElementById('clear-search');
                    if (searchValue) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }
                    
                    // Debounce search to avoid too many API calls
                    debounceTimer = setTimeout(() => {
                        currentFilters.search = searchValue;
                        currentFilters.page = 1;
                        filterAndDisplayDocuments(searchValue);
                    }, 300);
                });
            }

            // Clear search button
            const clearSearchBtn = document.getElementById('clear-search');
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    this.classList.add('hidden');
                    currentFilters.search = '';
                    currentFilters.page = 1;
                    filterAndDisplayDocuments('');
                });
            }

            // Filter dropdowns
            const filterDropdowns = document.querySelectorAll('[data-filter]');
            filterDropdowns.forEach(dropdown => {
                dropdown.addEventListener('change', function() {
                    const filterType = this.getAttribute('data-filter');
                    const value = this.value;
                    
                    switch(filterType) {
                        case 'group':
                            currentFilters.groupBy = value;
                            break;
                        case 'sort':
                            const [sortBy, sortOrder] = value.split('-');
                            currentFilters.sort_by = sortBy;
                            currentFilters.sort_order = sortOrder;
                            break;
                        case 'view':
                            currentFilters.viewType = value;
                            break;
                    }
                    
                    currentFilters.page = 1;
                    loadDocuments();
                });
            });

            // Initialize additional functionality
            initializeDropdownFilters();
            initializeDragAndDrop();
            initializeRecentUploads();
            initializeKeyboardNavigation();
        }

        function switchTab(tab) {
            // Update active tab
            document.querySelectorAll('[data-tab]').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('text-gray-600', 'hover:text-gray-900');
            });
            
            document.querySelector(`[data-tab="${tab}"]`).classList.add('bg-blue-600', 'text-white');
            document.querySelector(`[data-tab="${tab}"]`).classList.remove('text-gray-600', 'hover:text-gray-900');
            
            // Update current view
            currentFilters.view = tab;
            loadDocuments();
        }

        function switchView(view) {
            // Switching to view
            
            // Update active view button
            document.querySelectorAll('[data-view]').forEach(btn => {
                btn.classList.remove('bg-gray-200', 'text-gray-700');
                btn.classList.add('text-gray-400', 'hover:text-gray-600');
            });
            
            document.querySelector(`[data-view="${view}"]`).classList.add('bg-gray-200', 'text-gray-700');
            document.querySelector(`[data-view="${view}"]`).classList.remove('text-gray-400', 'hover:text-gray-600');
            
            // Update view mode based on the selected view
            currentViewMode = view;
            // View mode updated
            
            // Update view type
            currentFilters.viewType = view;
            
            // Preserve selection state when switching views
            const currentSelection = new Set(selectedDocuments);
            const currentSelectAllState = selectAllChecked;
            
            // Instead of reloading documents, just switch the view immediately
            displayDocumentsByTime(currentDocuments);
            
            // Restore selection state
            selectedDocuments = currentSelection;
            selectAllChecked = currentSelectAllState;
            updateBulkDeleteUI();
            updateSelectAllCheckbox();
        }

        function filterAndDisplayDocuments(searchTerm) {
            // If no search term, show all documents
            if (!searchTerm || searchTerm.trim() === '') {
                if (window.allDocuments) {
                    currentDocuments = window.allDocuments;
                    displayDocumentsByTime(window.allDocuments);
                    updateDocumentsCount({ total_documents: window.allDocuments.length, current_page: 1, limit: currentFilters.limit });
                }
                return;
            }

            // Filter documents by filename (case-insensitive)
            const filteredDocuments = window.allDocuments.filter(doc => {
                const filename = doc.document_name || doc.title || doc.filename || '';
                return filename.toLowerCase().includes(searchTerm.toLowerCase());
            });

            // Update current documents and display
            currentDocuments = filteredDocuments;
            displayDocumentsByTime(filteredDocuments);
            updateDocumentsCount({ total_documents: filteredDocuments.length, current_page: 1, limit: currentFilters.limit });
        }

        function loadCategories() {
            fetch('api/documents.php?action=get_categories')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(responseText => {
                    if (!responseText || responseText.trim() === '') {
                        console.log('⚠️ Empty response from categories API');
                        return;
                    }
                    
                    const data = JSON.parse(responseText);
                    if (data.success) {
                        availableCategories = data.categories;
                        // Populate filter categories if panel exists
                        const wrap = document.getElementById('filter-categories');
                        if (wrap) {
                            wrap.innerHTML = '';
                            availableCategories.forEach(function(cat){
                                const b = document.createElement('button');
                                b.type = 'button';
                                b.className = 'px-2 py-1 text-xs rounded-full border hover:bg-gray-50';
                                b.textContent = cat;
                                b.addEventListener('click', function(){ b.classList.toggle('selected'); });
                                wrap.appendChild(b);
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading categories:', error);
                    console.error('Error details:', {
                        name: error.name,
                        message: error.message,
                        stack: error.stack
                    });
                });
        }

        function loadDocuments() {
            showLoadingState();
            
            // Build query parameters
            const params = new URLSearchParams({
                action: 'get_all',
                page: currentFilters.page,
                limit: currentFilters.limit,
                sort_by: currentFilters.sort_by,
                sort_order: currentFilters.sort_order
            });
            
            if (currentFilters.search) {
                params.append('search', currentFilters.search);
            }
            
            if (currentFilters.category) {
                params.append('category', currentFilters.category);
            }
            
            // Advanced filters
            if (currentFilters.file_group && currentFilters.file_group !== 'all') {
                params.append('file_group', currentFilters.file_group);
            }
            if (Array.isArray(currentFilters.file_types)) {
                currentFilters.file_types.forEach(t => params.append('file_type[]', t));
            }
            if (Array.isArray(currentFilters.categories)) {
                currentFilters.categories.forEach(c => params.append('category[]', c));
            }
            if (currentFilters.date_from) { params.append('date_from', currentFilters.date_from); }
            if (currentFilters.date_to) { params.append('date_to', currentFilters.date_to); }
            
            console.log('🔗 Making API call to: api/documents.php?' + params.toString());
            fetch('api/documents.php?' + params.toString())
                .then(response => {
                    console.log('📡 Response status:', response.status, response.statusText);
                    console.log('📡 Response headers:', response.headers);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(responseText => {
                    if (!responseText || responseText.trim() === '') {
                        console.log('⚠️ Empty response from documents API');
                        hideLoadingState();
                        return;
                    }
                    
                    const data = JSON.parse(responseText);
                    hideLoadingState();
                    console.log('📥 API Response:', data);
                    if (data.success) {
                        currentDocuments = data.documents;
                        console.log('✅ Loaded', data.documents.length, 'documents');
                        // Store all documents for client-side filtering
                        window.allDocuments = data.documents;
                        
                        // Update existing file names set
                        existingFileNames.clear();
                        data.documents.forEach(doc => {
                            const fileName = doc.document_name || doc.title || 'Untitled Document';
                            existingFileNames.add(fileName.toLowerCase());
                        });
                        
                        displayDocumentsByTime(data.documents);
                        updateDocumentsCount(data.pagination);
                        
                        // Reset selection state when documents are loaded
                        selectedDocuments.clear();
                        selectAllChecked = false;
                        updateSelectAllCheckbox();
                        
                        // Load trash list
                        fetch('api/documents.php?action=get_trash')
                            .then(r => r.json())
                            .then(d => { if (d.success) renderTrash(d.trash || []); })
                            .catch(() => {});
                    } else {
                        showErrorMessage('Failed to load documents: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    hideLoadingState();
                    console.error('❌ Error loading documents:', error);
                    console.error('Error details:', {
                        name: error.name,
                        message: error.message,
                        stack: error.stack
                    });
                    showErrorMessage('Error loading documents. Please try again.');
                });
        }

        function showLoadingState() {
            const loadingElement = document.getElementById('loading-state');
            if (loadingElement) {
                loadingElement.classList.remove('hidden');
            } else {
                console.log('⚠️ Loading state element not found');
            }
        }

        function hideLoadingState() {
            const loadingElement = document.getElementById('loading-state');
            if (loadingElement) {
                loadingElement.classList.add('hidden');
            } else {
                console.log('⚠️ Loading state element not found');
            }
        }

        function loadStats() {
            console.log('🔄 Loading stats...');
            fetch('api/documents.php?action=get_stats')
                .then(response => {
                    console.log('📊 Stats API response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(responseText => {
                    console.log('📊 Stats API response text:', responseText);
                    if (!responseText || responseText.trim() === '') {
                        console.log('⚠️ Empty response from stats API');
                        return;
                    }
                    
                    const data = JSON.parse(responseText);
                    console.log('📊 Parsed stats data:', data);
                    
                    if (data.success && data.stats) {
                        const totalElement = document.getElementById('total-documents');
                        const recentElement = document.getElementById('recent-documents');
                        const typesElement = document.getElementById('document-types');
                        
                        console.log('📊 Updating counters:', {
                            total: data.stats.total,
                            recent: data.stats.recent,
                            totalElement: totalElement,
                            recentElement: recentElement,
                            typesElement: typesElement
                        });
                        
                        if (totalElement) {
                            totalElement.textContent = data.stats.total;
                            console.log('✅ Updated total documents to:', data.stats.total);
                        }
                        if (recentElement) {
                            recentElement.textContent = data.stats.recent;
                            console.log('✅ Updated recent documents to:', data.stats.recent);
                        }
                        if (typesElement) {
                            // Get categories count from API
                            fetch('api/documents.php?action=get_categories')
                                .then(response => response.json())
                                .then(catData => {
                                    if (catData.success) {
                                        typesElement.textContent = catData.categories.length;
                                        console.log('✅ Updated categories to:', catData.categories.length);
                                    }
                                })
                                .catch(error => console.error('Error loading categories:', error));
                        }
                    } else {
                        console.error('❌ Stats API returned error or no stats data:', data);
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading stats:', error);
                    console.error('Error details:', {
                        name: error.name,
                        message: error.message,
                        stack: error.stack
                    });
                });
        }
        
        // Make loadStats globally available
        window.loadStats = loadStats;
        
        // Add a test function to manually refresh stats
        function testStatsRefresh() {
            console.log(' Testing stats refresh...');
            loadStats();
        }
        
        // Make it available globally for testing
        window.testStatsRefresh = testStatsRefresh;

        function displayDocumentsByTime(documents) {
            console.log('📋 Displaying documents:', documents.length, 'documents');
            console.log('📋 Current view mode:', currentViewMode);
            const listContainer = document.getElementById('documents-table-body');
            const gridContainer = document.getElementById('documents-grid-body');
            const listView = document.getElementById('list-view');
            const gridView = document.getElementById('grid-view');
            
            // Debug: Check if elements exist
            if (!listContainer) {
                console.error('❌ documents-table-body element not found');
                return;
            }
            if (!gridContainer) {
                console.error('❌ documents-grid-body element not found');
                return;
            }
            if (!listView) {
                console.error('❌ list-view element not found');
                return;
            }
            if (!gridView) {
                console.error('❌ grid-view element not found');
                return;
            }
            
            // Show/hide appropriate view
            if (currentViewMode === 'list') {
                listView.classList.remove('hidden');
                gridView.classList.add('hidden');
            } else {
                listView.classList.add('hidden');
                gridView.classList.remove('hidden');
            }
            
            if (documents.length === 0) {
                if (currentViewMode === 'list') {
                    listContainer.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-20 h-20 text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <h3 class="text-xl font-medium text-gray-900 mb-3">No documents found</h3>
                                    <p class="text-gray-500 mb-6">${currentFilters.search ? 'Try adjusting your search criteria' : 'Upload your first document to get started'}</p>
                                    ${!currentFilters.search ? `
                                        <button onclick="showUploadModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                            Upload First Document
                                        </button>
                                    ` : `
                                        <button onclick="clearSearch()" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                                            Clear Search
                                        </button>
                                    `}
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    gridContainer.innerHTML = `
                        <div class="col-span-full px-6 py-24 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-20 h-20 text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="text-xl font-medium text-gray-900 mb-3">No documents found</h3>
                                <p class="text-gray-500 mb-6">${currentFilters.search ? 'Try adjusting your search criteria' : 'Upload your first document to get started'}</p>
                                ${!currentFilters.search ? `
                                    <button onclick="showUploadModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                        Upload First Document
                                    </button>
                                ` : `
                                    <button onclick="clearSearch()" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                                        Clear Search
                                    </button>
                                `}
                            </div>
                        </div>
                    `;
                }
                return;
            }

            // Display documents based on current view mode
            if (currentViewMode === 'list') {
                console.log('🧾 Rendering list rows for:', documents.map(function(d){ return (d && (d.document_name||d.title||d.filename)) || 'unknown'; }));
                const html = documents.map(function(doc){
                    try {
                        return createDocumentTableRow(doc);
                    } catch (e) {
                        console.error('Row render error for doc:', doc, e);
                        // Fallback minimal row to avoid dropping subsequent items
                        var name = (doc && (doc.document_name||doc.title||doc.filename)) || 'Untitled Document';
                        var ext = getFileExtension((doc && doc.filename) || '');
                        return `
                            <tr>
                                <td class="px-6 py-4"></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">${name}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">—</td>
                                <td class="px-6 py-4 text-sm text-gray-500">${(ext||'').toUpperCase()}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">—</td>
                                <td class="px-6 py-4 text-sm"></td>
                            </tr>`;
                    }
                }).join('');
                listContainer.innerHTML = html;
                
                // Update bulk selection UI
                updateBulkDeleteUI();
                updateSelectAllCheckbox();
            } else {
                const html = documents.map(function(doc){
                    try { return createDocumentGridCard(doc); }
                    catch(e){ console.error('Grid render error for doc:', doc, e); return ''; }
                }).join('');
                gridContainer.innerHTML = html;
            }
        }

        function createDocumentTableRow(doc, isSelected = false) {
            const fileExtension = getFileExtension(doc.filename || '');
            const fileIcon = getFileIcon(fileExtension);
            const fileColor = getFileColor(fileExtension);
            const addedDate = new Date(doc.upload_date || doc.date_added);
            const formattedDate = addedDate.toLocaleDateString('en-US', { 
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            }).replace(',', ' ·');

            // Generate random file size and shared members for demo
            const fileSize = Math.floor(Math.random() * 10000) + 100;
            const sharedMembers = Math.floor(Math.random() * 5) + 1;
            
            // Check if this document is selected
            const isDocSelected = selectedDocuments.has(doc.id);

            return `
                <tr class="${isDocSelected ? 'bg-blue-50' : 'hover:bg-gray-50'}" data-document-id="${doc.id}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center justify-center">
                            <input type="checkbox" 
                                   onclick="event.stopPropagation(); toggleDocumentSelection(${doc.id})" 
                                   ${isDocSelected ? 'checked' : ''}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap cursor-pointer" onclick="viewDocument(${doc.id})">
                        <div class="flex items-center">
                            <div class="w-8 h-8 ${fileColor} rounded-lg flex items-center justify-center mr-3 flex-shrink-0 shadow-sm ring-1 ring-black/5">
                                ${fileIcon.replace('w-4 h-4', 'w-5 h-5')}
                            </div>
                            <div class="text-sm font-medium text-gray-900">
                                ${doc.document_name || doc.title || 'Untitled Document'}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer w-[12%] text-center" onclick="viewDocument(${doc.id})">
                        ${fileSize.toLocaleString()} KB
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer w-[12%] text-center" onclick="viewDocument(${doc.id})">
                        ${fileExtension.toUpperCase()}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer w-[24%] text-center" onclick="viewDocument(${doc.id})">
                        ${formattedDate}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium w-[12%] text-center">
                        <button onclick="event.stopPropagation(); deleteDocumentWithConfirmation(${doc.id})" 
                                class="text-gray-400 hover:text-red-600 transition-colors inline-flex items-center justify-center" 
                                aria-label="Delete document">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
            `;
        }

        function createDocumentCard(doc) {
            const fileExtension = getFileExtension(doc.filename || '');
            const fileIcon = getFileIcon(fileExtension);
            const fileColor = getFileColor(fileExtension);
            const addedDate = new Date(doc.upload_date || doc.date_added);
            const formattedDate = addedDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });

            // Generate random colored circles for tags (in real app, these would be actual tags)
            const tagColors = ['bg-orange-400', 'bg-red-400', 'bg-yellow-400', 'bg-blue-400', 'bg-green-400', 'bg-purple-400', 'bg-pink-400'];
            const tags = tagColors.slice(0, 3).map(color => `<div class="w-2 h-2 ${color} rounded-full"></div>`).join('');

            return `
                <div class="bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow cursor-pointer group" onclick="viewDocument(${doc.id})">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-8 h-8 ${fileColor} rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm ring-1 ring-black/5">
                                ${fileIcon.replace('w-4 h-4', 'w-5 h-5')}
                            </div>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="event.stopPropagation(); showDocumentMenu(${doc.id})" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h4 class="font-medium text-gray-900 text-sm leading-tight mb-2" title="${doc.document_name || doc.title || 'Untitled Document'}">
                                ${(doc.document_name || doc.title || 'Untitled Document').length > 30 ? 
                                    (doc.document_name || doc.title || 'Untitled Document').substring(0, 30) + '...' : 
                                    (doc.document_name || doc.title || 'Untitled Document')}
                            </h4>
                            <div class="flex items-center gap-1">
                                ${tags}
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            ${formattedDate}
                        </div>
                    </div>
                </div>
            `;
        }

        function createDocumentGridCard(doc, isSelected = false) {
            const fileExtension = getFileExtension(doc.filename || '');
            const fileIcon = getFileIcon(fileExtension);
            const fileColor = getFileColor(fileExtension);
            const addedDate = new Date(doc.upload_date || doc.date_added);
            const formattedDate = addedDate.toLocaleDateString('en-US', { 
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            }).replace(',', ' ·');

            // Generate random file size and shared members for demo
            const fileSize = Math.floor(Math.random() * 10000) + 100;
            const sharedMembers = Math.floor(Math.random() * 5) + 1;
            
            // Check if this document is selected
            const isDocSelected = selectedDocuments.has(doc.id);

            return `
                <div class="bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-all duration-200 group ${isDocSelected ? 'ring-2 ring-blue-500 bg-blue-50' : ''}" 
                     data-document-id="${doc.id}">
                    <div class="p-4">
                        <!-- Checkbox and File Icon -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" 
                                       onclick="event.stopPropagation(); toggleDocumentSelection(${doc.id})" 
                                       ${isDocSelected ? 'checked' : ''}
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <div class="w-12 h-12 ${fileColor} rounded-lg flex items-center justify-center shadow-sm">
                                    ${fileIcon.replace('w-4 h-4', 'w-6 h-6')}
                                </div>
                            </div>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="event.stopPropagation(); deleteDocumentWithConfirmation(${doc.id})" 
                                        class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded-full hover:bg-red-50" 
                                        aria-label="Delete document">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- File Name -->
                        <div class="mb-3 cursor-pointer" onclick="viewDocument(${doc.id})">
                            <h4 class="font-semibold text-gray-900 text-sm leading-tight mb-1" title="${doc.document_name || doc.title || 'Untitled Document'}">
                                ${(doc.document_name || doc.title || 'Untitled Document').length > 25 ? 
                                    (doc.document_name || doc.title || 'Untitled Document').substring(0, 25) + '...' : 
                                    (doc.document_name || doc.title || 'Untitled Document')}
                            </h4>
                            <p class="text-xs text-gray-500">${fileExtension.toUpperCase()} File</p>
                        </div>
                        
                        <!-- Metadata -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Size</span>
                                <span class="font-medium">${fileSize.toLocaleString()} KB</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Shared</span>
                                <span class="font-medium">${sharedMembers} ${sharedMembers === 1 ? 'Member' : 'Members'}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Date</span>
                                <span class="font-medium">${formattedDate}</span>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mt-4 pt-3 border-t border-gray-100 opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="flex gap-2">
                                <button onclick="event.stopPropagation(); viewDocument(${doc.id})" 
                                        class="flex-1 bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-medium hover:bg-blue-100 transition-colors">
                                    View
                                </button>
                                <button onclick="event.stopPropagation(); downloadDocument(${doc.id})" 
                                        class="flex-1 bg-gray-50 text-gray-600 px-2 py-1 rounded text-xs font-medium hover:bg-gray-100 transition-colors">
                                    Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function updateDocumentsCount(pagination) {
            const countElement = document.getElementById('documents-count');
            if (countElement && pagination) {
                const start = (pagination.current_page - 1) * pagination.limit + 1;
                const end = Math.min(pagination.current_page * pagination.limit, pagination.total_documents);
                countElement.textContent = `Showing ${start}-${end} of ${pagination.total_documents} documents`;
            }
        }

        // Bulk selection functions
        function toggleSelectAll() {
            selectAllChecked = !selectAllChecked;
            
            if (selectAllChecked) {
                // Select all visible documents
                currentDocuments.forEach(doc => {
                    selectedDocuments.add(doc.id);
                });
            } else {
                // Unselect all documents
                selectedDocuments.clear();
            }
            
            // Update the display to reflect the new selection state
            displayDocumentsByTime(currentDocuments);
            updateBulkDeleteUI();
            updateSelectAllCheckbox();
        }

        function toggleDocumentSelection(docId) {
            if (selectedDocuments.has(docId)) {
                selectedDocuments.delete(docId);
            } else {
                selectedDocuments.add(docId);
            }
            
            // Update select all state based on current selection
            selectAllChecked = selectedDocuments.size === currentDocuments.length;
            
            // Update the display to reflect the new selection state
            displayDocumentsByTime(currentDocuments);
            updateBulkDeleteUI();
            updateSelectAllCheckbox();
        }

        function updateBulkDeleteUI() {
            const bulkActionsBar = document.getElementById('bulk-actions-bar');
            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
            const selectedCount = document.getElementById('selected-count');
            const selectedPlural = document.getElementById('selected-plural');
            const gridSelectedCount = document.getElementById('grid-selected-count');
            
            if (bulkActionsBar && bulkDeleteBtn && selectedCount && selectedPlural) {
                if (selectedDocuments.size > 0) {
                    bulkActionsBar.classList.remove('hidden');
                    selectedCount.textContent = selectedDocuments.size;
                    selectedPlural.textContent = selectedDocuments.size === 1 ? '' : 's';
                } else {
                    bulkActionsBar.classList.add('hidden');
                }
            }
            
            // Update grid view selected count
            if (gridSelectedCount) {
                gridSelectedCount.textContent = selectedDocuments.size;
            }
        }

        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const gridSelectAllCheckbox = document.getElementById('grid-select-all-checkbox');
            
            // Update list view checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = selectAllChecked;
                selectAllCheckbox.indeterminate = selectedDocuments.size > 0 && selectedDocuments.size < currentDocuments.length;
                
                // Ensure the checkbox state is properly reflected
                if (selectedDocuments.size === 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (selectedDocuments.size === currentDocuments.length) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                }
            }
            
            // Update grid view checkbox
            if (gridSelectAllCheckbox) {
                gridSelectAllCheckbox.checked = selectAllChecked;
                gridSelectAllCheckbox.indeterminate = selectedDocuments.size > 0 && selectedDocuments.size < currentDocuments.length;
                
                // Ensure the checkbox state is properly reflected
                if (selectedDocuments.size === 0) {
                    gridSelectAllCheckbox.checked = false;
                    gridSelectAllCheckbox.indeterminate = false;
                } else if (selectedDocuments.size === currentDocuments.length) {
                    gridSelectAllCheckbox.checked = true;
                    gridSelectAllCheckbox.indeterminate = false;
                } else {
                    gridSelectAllCheckbox.checked = false;
                    gridSelectAllCheckbox.indeterminate = true;
                }
                
                // Show/hide "Select All" text based on checkbox state
                const gridSelectAllText = document.getElementById('grid-select-all-text');
                if (gridSelectAllText) {
                    if (gridSelectAllCheckbox.checked) {
                        gridSelectAllText.classList.remove('hidden');
                    } else {
                        gridSelectAllText.classList.add('hidden');
                    }
                }
            }
        }

        function clearSelection() {
            selectedDocuments.clear();
            selectAllChecked = false;
            updateBulkDeleteUI();
            updateSelectAllCheckbox();
            displayDocumentsByTime(currentDocuments);
        }

        function bulkDeleteSelected() {
            if (selectedDocuments.size === 0) return;
            
            const selectedDocs = Array.from(selectedDocuments).map(id => 
                currentDocuments.find(doc => doc.id == id)
            ).filter(Boolean);
            
            if (selectedDocs.length === 0) return;
            
            // Show confirmation modal
            showBulkDeleteConfirmation(selectedDocs);
        }

        function showBulkDeleteConfirmation(docs) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Delete Selected Documents</h3>
                        <button type="button" onclick="closeBulkDeleteModal()" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                            Close
                        </button>
                    </div>
                    <div class="mb-4">
                        <p class="text-gray-700">Are you sure you want to delete ${docs.length} selected document${docs.length > 1 ? 's' : ''}?</p>
                        <p class="text-sm text-gray-500 mt-2">This action cannot be undone.</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="bulk-delete-confirm-btn" type="button" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                        <button type="button" onclick="closeBulkDeleteModal()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add event listeners
            const confirmBtn = document.getElementById('bulk-delete-confirm-btn');
            confirmBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                confirmBulkDelete(docs);
            });
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeBulkDeleteModal();
                }
            });
        }

        function closeBulkDeleteModal() {
            const modals = document.querySelectorAll('.fixed');
            modals.forEach(modal => {
                if (modal.innerHTML.includes('Delete Selected Documents')) {
                    modal.remove();
                }
            });
        }

        function confirmBulkDelete(docs) {
            const confirmBtn = document.getElementById('bulk-delete-confirm-btn');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Deleting...';
            }
            
            let deletedCount = 0;
            let failedCount = 0;
            
            // Delete each document
            const deletePromises = docs.map(doc => {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', doc.id);
                
                return fetch('api/documents.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        deletedCount++;
                        // Remove from current documents
                        const index = currentDocuments.findIndex(d => d.id == doc.id);
                        if (index !== -1) {
                            currentDocuments.splice(index, 1);
                        }
                        // Remove from selected documents
                        selectedDocuments.delete(doc.id);
                    } else {
                        failedCount++;
                    }
                })
                .catch(error => {
                    console.error('Error deleting document:', error);
                    failedCount++;
                });
            });
            
            Promise.all(deletePromises).then(() => {
                // Close modal
                closeBulkDeleteModal();
                
                // Reset selection
                selectedDocuments.clear();
                selectAllChecked = false;
                
                // Update UI
                displayDocumentsByTime(currentDocuments);
                updateBulkDeleteUI();
                updateSelectAllCheckbox();
                
                console.log(`Bulk delete completed: ${deletedCount} deleted, ${failedCount} failed`);
            });
        }

        // Document Editor Functions
        function openDocumentEditor() {
            if (isEditorOpen) return;
            
            isEditorOpen = true;
            currentDocumentContent = '';
            
            const modal = document.createElement('div');
            modal.id = 'document-editor-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl h-5/6 flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Create New Document</h2>
                        <button type="button" onclick="closeDocumentEditor()" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                            Close
                        </button>
                    </div>
                    
                    <!-- Editor Content -->
                    <div class="flex-1 p-6 overflow-hidden">
                        <div class="h-full flex flex-col">
                            <div class="mb-4">
                                <label for="document-title" class="block text-sm font-medium text-gray-700 mb-2">Document Title</label>
                                <input type="text" id="document-title" placeholder="Enter document title..." 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="flex-1">
                                <label for="document-content" class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                                <textarea id="document-content" placeholder="Start typing your document content here..." 
                                          class="w-full h-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none min-h-[300px]"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
                        <button type="button" onclick="closeDocumentEditor()" 
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="button" onclick="saveDocument()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Save Document
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Focus on title input
            setTimeout(() => {
                const titleInput = document.getElementById('document-title');
                if (titleInput) titleInput.focus();
            }, 100);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeDocumentEditor();
                }
            });
        }

        function closeDocumentEditor() {
            const modal = document.getElementById('document-editor-modal');
            if (modal) {
                modal.remove();
                isEditorOpen = false;
                currentDocumentContent = '';
            }
        }

        function saveDocument() {
            const titleInput = document.getElementById('document-title');
            const contentInput = document.getElementById('document-content');
            
            if (!titleInput || !contentInput) return;
            
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
            
            if (!title) {
                alert('Please enter a document title.');
                titleInput.focus();
                return;
            }
            
            if (!content) {
                alert('Please enter some content for your document.');
                contentInput.focus();
                return;
            }
        }
        
        function openDocumentUpload() {
            // Open the new advanced upload modal
            try {
                showUploadModal();
            } catch (e) {
                console.error('Failed to open upload modal:', e);
            }
        }
        function clearSearch() {
            const searchInput = document.getElementById('search-documents');
            if (searchInput) {
                searchInput.value = '';
                currentFilters.search = '';
                filterAndDisplayDocuments('');
            }
        }

        function showDocumentMenu(docId) {
            // This would show a context menu for document actions
            showNotification('Document menu coming soon!', 'info');
        }

        function showTrashModal() {
            const m = document.getElementById('trashModal');
            if (m) m.classList.remove('hidden');
            // Refresh trash list when opening
            fetch('api/documents.php?action=get_trash')
                .then(function(r){ return r.json(); })
                .then(function(d){ if (d && d.success) { renderTrash(d.trash || []); } })
                .catch(function(){});
        }

        function hideTrashModal() {
            const m = document.getElementById('trashModal');
            if (m) m.classList.add('hidden');
        }

        function showRuleManagementModal() {
            const m = document.getElementById('ruleManagementModal');
            if (m) {
                m.classList.remove('hidden');
                loadRuleCategories();
            }
        }

        function hideRuleManagementModal() {
            const m = document.getElementById('ruleManagementModal');
            if (m) m.classList.add('hidden');
        }

        function loadRuleCategories() {
            console.log('Loading rule categories...');
            const container = document.getElementById('rule-categories-container');
            if (!container) {
                console.error('Rule categories container not found');
                return;
            }

            if (!window.documentCategorizer) {
                console.error('DocumentCategorizer not available');
                container.innerHTML = '<p class="text-red-500">DocumentCategorizer not loaded. Please refresh the page.</p>';
                return;
            }

            const categories = window.documentCategorizer.getCategories();
            console.log('Found categories:', categories);
            let html = '';

            if (categories.length === 0) {
                container.innerHTML = '<p class="text-gray-500">No categories found. DocumentCategorizer may not have rules.</p>';
                return;
            }

            categories.forEach(category => {
                const rules = window.documentCategorizer.getCategoryRules(category);
                console.log('Category:', category, 'Rules:', rules);
                if (rules) {
                    html += `
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-medium text-gray-900">${category}</h4>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500">Priority: ${rules.priority}</span>
                                    <button onclick="deleteRule('${category}')" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">Keywords</h5>
                                    <div class="flex flex-wrap gap-1">
                                        ${rules.keywords.map(keyword => 
                                            `<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">${keyword}</span>`
                                        ).join('')}
                                    </div>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">File Patterns</h5>
                                    <div class="flex flex-wrap gap-1">
                                        ${rules.filePatterns.map(pattern =>
                                            `<span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">${pattern.toString()}</span>`
                                        ).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });

            container.innerHTML = html || '<p class="text-gray-500">No rules configured yet.</p>';
        }

        function addNewRule() {
            const category = document.getElementById('new-rule-category').value.trim();
            const priority = parseInt(document.getElementById('new-rule-priority').value);
            const keywords = document.getElementById('new-rule-keywords').value.split(',').map(k => k.trim()).filter(k => k);
            const patterns = document.getElementById('new-rule-patterns').value.split(',').map(p => p.trim()).filter(p => p);

            if (!category || keywords.length === 0) {
                showNotification('Please provide a category name and at least one keyword', 'error');
                return;
            }

            if (!window.documentCategorizer) {
                showNotification('Document categorizer not available', 'error');
                return;
            }

            // Convert patterns to regex
            const filePatterns = patterns.map(pattern => new RegExp(pattern, 'i'));

            // Add the rule
            window.documentCategorizer.addRule(category, {
                keywords: keywords,
                filePatterns: filePatterns,
                datePatterns: [],
                priority: priority
            });

            // Clear form
            document.getElementById('new-rule-category').value = '';
            document.getElementById('new-rule-keywords').value = '';
            document.getElementById('new-rule-patterns').value = '';
            document.getElementById('new-rule-priority').value = '5';

            // Reload categories
            loadRuleCategories();
            showNotification(`Rule for "${category}" added successfully`, 'success');
        }

        function deleteRule(category) {
            if (!confirm(`Are you sure you want to delete the rule for "${category}"?`)) {
                return;
            }

            if (!window.documentCategorizer) {
                showNotification('Document categorizer not available', 'error');
                return;
            }

            // Remove the rule (this would need to be implemented in the categorizer)
            delete window.documentCategorizer.rules[category];
            
            // Reload categories
            loadRuleCategories();
            showNotification(`Rule for "${category}" deleted successfully`, 'success');
        }

        // File type and icon functions
        function getFileExtension(filename) {
            return filename.split('.').pop().toLowerCase();
        }

        function getFileIcon(extension) {
            const icons = {
                'pdf': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm0 2h4v4H4V5zm6 0h6v4h-6V5zM4 11h4v4H4v-4zm6 0h6v4h-6v-4z"></path></svg>',
                'doc': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'docx': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'xls': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'xlsx': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'ppt': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'pptx': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'txt': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v2H6V5zm0 4h8v2H6V9zm0 4h6v2H6v-2z"></path></svg>',
                'jpg': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                'jpeg': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                'png': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                'gif': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                'zip': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>',
                'rar': '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 2h8v10H6V5z"></path></svg>'
            };
            return icons[extension] || '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm8 2v4H8V5h4zM6 7v2H4V7h2zm0 4v2H4v-2h2zm8-4v2h2V7h-2zm0 4v2h2v-2h-2z"></path></svg>';
        }

        function getFileColor(extension) {
            const colors = {
                'pdf': 'bg-red-500',
                'doc': 'bg-blue-500',
                'docx': 'bg-blue-500',
                'xls': 'bg-green-600',
                'xlsx': 'bg-green-600',
                'ppt': 'bg-orange-500',
                'pptx': 'bg-orange-500',
                'txt': 'bg-gray-500',
                'jpg': 'bg-green-500',
                'jpeg': 'bg-green-500',
                'png': 'bg-green-500',
                'gif': 'bg-purple-500',
                'zip': 'bg-yellow-500',
                'rar': 'bg-yellow-500'
            };
            return colors[extension] || 'bg-gray-400';
        }

        function viewDocument(id) {
            const doc = currentDocuments.find(d => d.id == id);
            if (doc) {
                // For now, just show a notification
                showNotification(`Viewing ${doc.document_name || doc.title || 'Untitled Document'}`, 'info');
                // In a real implementation, this would open a detailed view modal
            }
        }

        function showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300`;
            notification.style.top = '160px';
            notification.style.zIndex = '99999';
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function showErrorMessage(message) {
            // Don't show network error notifications
            if (message.includes('Network error') || message.includes('connection')) {
                console.log('Network error suppressed:', message);
                return;
            }
            showNotification(message, 'error');
        }

        // Recent uploads functionality - Local state based (no API dependency)
        function displayRecentUploads() {
            console.log('=== DISPLAY RECENT UPLOADS CALLED ===');
            console.log('Current recentUploads array:', recentUploads);
            console.log('Array length:', recentUploads.length);
            
            const container = document.getElementById('recent-uploads');
            if (!container) {
                console.error('Recent uploads container not found!');
                return;
            }
            
            console.log('Found container:', container);
            console.log('Container current HTML:', container.innerHTML.substring(0, 200) + '...');
            
            if (recentUploads.length === 0) {
                console.log('No recent uploads, showing empty state');
                container.innerHTML = `
                    <div class="text-center py-6 text-gray-500">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-600">No recent uploads yet</p>
                        <p class="text-xs text-gray-500 mt-1">Upload files to see them here</p>
                    </div>
                `;
                console.log('Empty state displayed');
                return;
            }
            
            console.log('Found', recentUploads.length, 'recent uploads to display');
            console.log('Clearing container...');
            container.innerHTML = '';
            
            // Display recent uploads (newest first)
            recentUploads.forEach((upload, index) => {
                console.log('Creating upload item', index + 1, ':', upload);
                try {
                    const uploadItem = createUploadItem(upload);
                    console.log('Created upload item HTML:', uploadItem.substring(0, 100) + '...');
                    container.appendChild(uploadItem);
                    console.log('Added upload item to container');
                } catch (error) {
                    console.error('Error creating upload item:', error);
                }
            });
            
            console.log('Final container HTML length:', container.innerHTML.length);
            console.log('=== DISPLAY RECENT UPLOADS COMPLETED ===');
        }

        function createUploadItem(upload) {
            try {
                console.log('Creating upload item for:', upload);
                
                const fileIcon = getFileIcon(upload.type);
                const fileColor = getFileColor(upload.type);
                const isCompleted = upload.progress === 100;
                
                // Format file size
                let fileSize = 'Unknown size';
                if (upload.fileSize && typeof formatFileSize === 'function') {
                    fileSize = formatFileSize(upload.fileSize);
                } else if (upload.fileSize) {
                    fileSize = Math.round(upload.fileSize / 1024) + ' KB';
                }
                
                // Format upload date
                let uploadDate = 'Just now';
                if (upload.uploadDate) {
                    try {
                        uploadDate = new Date(upload.uploadDate).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    } catch (e) {
                        console.log('Error formatting date:', e);
                        uploadDate = 'Just now';
                    }
                }

                const uploadItem = `
                    <div class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors cursor-pointer" onclick="viewDocumentByName('${upload.name}')">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center flex-1 min-w-0">
                                <div class="w-6 h-6 ${fileColor} rounded flex items-center justify-center mr-2 flex-shrink-0">
                                    ${fileIcon.replace('w-4 h-4', 'w-3 h-3')}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 truncate" title="${upload.name}">${upload.name}</div>
                                    <div class="text-xs text-gray-500">${fileSize} • ${upload.type.toUpperCase()}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1 ml-2">
                                <span class="text-xs text-gray-400">${uploadDate}</span>
                                <button onclick="event.stopPropagation(); removeFromRecentUploads('${upload.name}')" 
                                        class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded-full hover:bg-red-50" 
                                        aria-label="Remove from recent uploads">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        ${!isCompleted ? `
                            <div class="w-full bg-gray-200 rounded-full h-1">
                                <div class="bg-blue-600 h-1 rounded-full transition-all duration-300" data-progress="${upload.progress}"></div>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                console.log('Created upload item successfully');
                return uploadItem;
            } catch (error) {
                console.error('Error creating upload item:', error);
                // Return a simple fallback item
                return `
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-gray-400 rounded flex items-center justify-center mr-2">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm8 2v4H8V5h4zM6 7v2H4V7h2zm0 4v2H4v-2h2zm8-4v2h2V7h-2zm0 4v2h2v-2h-2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">${upload.name || 'Unknown File'}</div>
                                <div class="text-xs text-gray-500">File uploaded</div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        function showUploadModal() {
            // Remove any existing modal
            const existingModal = document.getElementById('upload-modal');
            if (existingModal) {
                existingModal.remove();
            }

            // Create modal with inline event handlers
            const modalHTML = `
                <div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4" onclick="if(event.target === this) this.remove()">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[80vh] overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">File Upload</h3>
                            <button type="button" onclick="document.getElementById('upload-modal').remove()" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                                Close
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-5 overflow-y-auto">
                            <div onclick="document.getElementById('single-file-input').click()" class="border-2 border-dashed rounded-xl p-8 text-center bg-gray-50 border-gray-300 cursor-pointer hover:bg-gray-100 transition-colors" style="cursor: pointer !important;">
                                                         <div class="w-12 h-12 rounded-full mx-auto mb-3 flex items-center justify-center bg-white shadow">
                                    <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8"/>
                                    </svg>
                             </div>
                                <p class="text-sm font-medium text-gray-800">Click to Upload</p>
                             <p class="text-xs text-gray-500">or drag and drop</p>
                             <p class="text-[11px] text-gray-400 mt-1">Supports any kinds of document</p>
                                <input id="single-file-input" type="file" multiple class="hidden" onchange="handleFileSelection(this.files)" />
                        </div>

                        <div id="upload-list" class="space-y-3"></div>
                        
                        <!-- Award Selection -->
                        <div class="space-y-2">
                            <label for="award-type-select" class="block text-sm font-medium text-gray-700">Select Award Type (Optional)</label>
                            <select id="award-type-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">No specific award</option>
                                <option value="Internationalization (IZN) Leadership Award">Internationalization (IZN) Leadership Award</option>
                                <option value="Outstanding International Education Program Award">Outstanding International Education Program Award</option>
                                <option value="Emerging Leadership Award">Emerging Leadership Award</option>
                                <option value="Best Regional Office for Internationalization Award">Best Regional Office for Internationalization Award</option>
                                <option value="Global Citizenship Award">Global Citizenship Award</option>
                            </select>
                        </div>
                        
                        <!-- Auto-categorization toggle -->
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="auto-categorize" checked class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label for="auto-categorize" class="text-sm font-medium text-gray-700">Auto-categorize documents</label>
                            </div>
                            <div class="text-xs text-gray-500">
                                <span id="categorization-status">Ready</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                            <button id="begin-upload-btn" onclick="handleUpload()" class="w-full bg-gray-800 text-white py-3 rounded-xl font-semibold disabled:opacity-50 hover:bg-gray-900 transition-colors" disabled>Upload</button>
                        </div>
                    </div>
                </div>
            `;

            // Insert modal
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Global variables for file handling
            window.selectedFiles = [];
            window.uploadList = document.getElementById('upload-list');
            window.uploadBtn = document.getElementById('begin-upload-btn');

            // Global function to handle file selection
            window.handleFileSelection = function(files) {
                console.log('Files selected:', files);
                if (files && files.length > 0) {
                    window.selectedFiles = Array.from(files);
                    displaySelectedFiles();
                    window.uploadBtn.disabled = false;
                }
            };

            // Global function to display selected files
            window.displaySelectedFiles = function() {
                window.uploadList.innerHTML = '';
                window.selectedFiles.forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'bg-gray-50 rounded-lg p-3 flex items-center justify-between';
                    fileItem.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                    </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">${file.name}</p>
                                <p class="text-xs text-gray-500">${Math.round(file.size / 1024)} KB</p>
                                    </div>
                                    </div>
                        <button type="button" onclick="removeFileFromList(${index})" class="text-red-500 hover:text-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                    `;
                    window.uploadList.appendChild(fileItem);
                });
            };

            // Global function to remove files
            window.removeFileFromList = function(index) {
                window.selectedFiles.splice(index, 1);
                window.displaySelectedFiles();
                if (window.selectedFiles.length === 0) {
                    window.uploadBtn.disabled = true;
                }
            };

            // Global function to handle upload
            window.handleUpload = function() {
                if (window.selectedFiles.length === 0) return;
                
                console.log('Starting upload for', window.selectedFiles.length, 'files');
                
                window.uploadBtn.disabled = true;
                window.uploadBtn.textContent = 'Uploading...';
                
                // Upload each file directly
                let completedCount = 0;
                let failedCount = 0;
                
                window.selectedFiles.forEach(async (file, index) => {
                    try {
                        console.log(`Uploading file ${index + 1}:`, file.name);
                        
                        // Create FormData
                        const formData = new FormData();
                        formData.append('action', 'add');
                        formData.append('file', file);
                        
                        // Generate document name
                        let documentName = file.name.replace(/\.[^/.]+$/, "");
                        formData.append('document_name', documentName);
                        
                        // Add award type if selected
                        const awardTypeSelect = document.getElementById('award-type-select');
                        if (awardTypeSelect && awardTypeSelect.value) {
                            formData.append('award_type', awardTypeSelect.value);
                        }
                        
                        // Add CSRF token
                        const csrfToken = document.getElementById('csrf-token');
                        if (csrfToken) {
                            formData.append('csrf_token', csrfToken.value);
                        }
                        
                        // Upload to server
                        const response = await fetch('api/documents.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const responseText = await response.text();
                        console.log('Upload response:', responseText);
                        
                        let data;
                        try {
                            data = JSON.parse(responseText);
                        } catch (e) {
                            console.error('Failed to parse response:', e);
                            throw new Error('Invalid response from server');
                        }
                        
                        if (data && data.success) {
                            completedCount++;
                            console.log(`File ${index + 1} uploaded successfully:`, file.name);
                        } else {
                            failedCount++;
                            console.log(`File ${index + 1} upload failed:`, data.message || 'Unknown error');
                        }
                        
                    } catch (error) {
                        failedCount++;
                        console.error(`Error uploading file ${index + 1}:`, error);
                    }
                    
                    // Check if all uploads are complete
                    if (completedCount + failedCount === window.selectedFiles.length) {
                        console.log(`All uploads complete: ${completedCount} success, ${failedCount} failed`);
                        
                        window.uploadBtn.textContent = 'Upload Complete';
                        
                                setTimeout(() => {
                    document.getElementById('upload-modal').remove();
                            
                            // Reload documents to show the new files
                            if (typeof loadDocuments === 'function') {
                                    loadDocuments();
                            }
                            if (typeof loadStats === 'function') {
                                    loadStats();
                            }
                            
                            // Show result message
                            if (typeof showNotification === 'function') {
                                if (failedCount === 0) {
                                    showNotification(`${completedCount} file(s) uploaded successfully!`, 'success');
                                } else {
                                    showNotification(`${completedCount} uploaded, ${failedCount} failed`, 'warning');
                                }
                            } else {
                                if (failedCount === 0) {
                                    alert(`${completedCount} file(s) uploaded successfully!`);
                                } else {
                                    alert(`${completedCount} uploaded, ${failedCount} failed`);
                                }
                                }
                            }, 1000);
                        }
                    });
            };
        }

        function displaySelectedFiles(files) {
            const filesList = document.getElementById('selected-files-list');
            const filesContainer = document.getElementById('files-container');
            
            if (files.length === 0) {
                filesList.classList.add('hidden');
                return;
            }
            
            filesList.classList.remove('hidden');
            filesContainer.innerHTML = '';
            
            Array.from(files).forEach((file, index) => {
                            // Check for duplicate file names (including versioned files)
            const fileName = file.name;
            const baseFileName = fileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers like "(1)", "(2)"
            
            const isDuplicate = currentDocuments.some(doc => {
                const docFileName = doc.filename || doc.document_name || doc.title || '';
                const docBaseFileName = docFileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers
                return docBaseFileName.toLowerCase() === baseFileName.toLowerCase();
            });
                
                const fileItem = document.createElement('div');
                fileItem.className = `flex items-center justify-between p-2 rounded-lg ${isDuplicate ? 'bg-red-50 border border-red-200' : 'bg-gray-50'}`;
                fileItem.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 ${isDuplicate ? 'text-red-500' : 'text-gray-500'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm ${isDuplicate ? 'text-red-700' : 'text-gray-700'} truncate">${file.name}</span>
                        <span class="text-xs ${isDuplicate ? 'text-red-500' : 'text-gray-500'}">(${formatFileSize(file.size)})</span>
                        ${isDuplicate ? '<span class="text-xs text-red-600 font-medium">(Duplicate)</span>' : ''}
                    </div>
                    <button onclick="removeFile(${index})" class="text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                `;
                filesContainer.appendChild(fileItem);
            });
        }

        function removeFile(index) {
            const fileInput = document.getElementById('file-upload');
            const dt = new DataTransfer();
            const files = Array.from(fileInput.files);
            
            files.splice(index, 1);
            files.forEach(file => dt.items.add(file));
            
            fileInput.files = dt.files;
            displaySelectedFiles(fileInput.files);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function uploadFiles() {
            const fileInput = document.getElementById('file-upload');
            const files = fileInput.files;
            
            if (files.length === 0) {
                showNotification('Please select files to upload', 'error');
                return;
            }
            
            // Check for duplicate files before uploading (including versioned files)
            const duplicateFiles = [];
            Array.from(files).forEach(file => {
                const fileName = file.name;
                const baseFileName = fileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers like "(1)", "(2)"
                
                const isDuplicate = currentDocuments.some(doc => {
                    const docFileName = doc.filename || doc.document_name || doc.title || '';
                    const docBaseFileName = docFileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers
                    return docBaseFileName.toLowerCase() === baseFileName.toLowerCase();
                });
                if (isDuplicate) {
                    duplicateFiles.push(fileName);
                }
            });
            
            if (duplicateFiles.length > 0) {
                const duplicateList = duplicateFiles.join(', ');
                showNotification(`Cannot upload duplicate files: ${duplicateList}. Please rename or remove them.`, 'error');
                return;
            }
            
            // Show upload progress modal
            showUploadProgressModal(files);
            
            // Upload each file
            let uploadedCount = 0;
            let failedCount = 0;
            
            Array.from(files).forEach((file, index) => {
                uploadSingleFile(file, index, () => {
                    uploadedCount++;
                    updateUploadProgress(index, true);
                    
                    // Check for awards earned after successful document upload
                    if (window.checkAwardCriteria) {
                        window.checkAwardCriteria('document', 'batch_upload');
                    }
                    
                    if (uploadedCount + failedCount === files.length) {
                        // All uploads completed
                        setTimeout(() => {
                            closeUploadProgressModal();
                            
                            // Remove upload modal
                            const uploadModal = document.getElementById('upload-modal');
                            if (uploadModal) {
                                uploadModal.remove();
                            }
                            
                            // Suppress all notifications during upload completion
                            window.suppressNotifications(1000);
                            
                            // Reload documents but preserve recent uploads
                            loadDocuments();
                            // Don't call displayRecentUploads() here as it might clear the recent uploads
                            console.log('Upload completion - recent uploads should already be updated');
                        }, 1000);
                    }
                }, () => {
                    failedCount++;
                    updateUploadProgress(index, false);
                    
                    if (uploadedCount + failedCount === files.length) {
                        // All uploads completed
                        setTimeout(() => {
                            closeUploadProgressModal();
                            
                            // Remove upload modal
                            const uploadModal = document.getElementById('upload-modal');
                            if (uploadModal) {
                                uploadModal.remove();
                            }
                            
                            // Suppress all notifications during upload completion
                            window.suppressNotifications(1000);
                            
                            // Reload documents but preserve recent uploads
                            loadDocuments();
                            // Don't call displayRecentUploads() here as it might clear the recent uploads
                            console.log('Upload completion - recent uploads should already be updated');
                        }, 1000);
                    }
                });
            });
        }

        function uploadSingleFile(file, index, onSuccess, onError) {
            // No file size limit - removed 10MB restriction
            // No file type restrictions - accept any file type
            
            // Check for duplicate file names (including versioned files)
            const fileName = file.name;
            const baseFileName = fileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers like "(1)", "(2)"
            
            const isDuplicate = currentDocuments.some(doc => {
                const docFileName = doc.filename || doc.document_name || doc.title || '';
                const docBaseFileName = docFileName.replace(/\(\d+\)$/, '').trim(); // Remove version numbers
                return docBaseFileName.toLowerCase() === baseFileName.toLowerCase();
            });
            
            if (isDuplicate) {
                // Allow upload by auto-versioning the name instead of blocking
                console.warn(`Duplicate base name detected for "${baseFileName}". Proceeding with versioned name.`);
            }
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('file', file);
            
            // Auto-generate document name from filename with duplicate handling
            let documentName = file.name.replace(/\.[^/.]+$/, ""); // Remove extension
            let counter = 1;
            const originalName = documentName;
            
            // Check for duplicates and add version number if needed
            while (existingFileNames.has(documentName.toLowerCase())) {
                documentName = `${originalName} (${counter})`;
                counter++;
            }
            
            // Add to existing file names set
            existingFileNames.add(documentName.toLowerCase());
            
            formData.append('document_name', documentName);
            
            // Add award type if selected
            const awardTypeSelect = document.getElementById('award-type-select');
            if (awardTypeSelect && awardTypeSelect.value) {
                formData.append('award_type', awardTypeSelect.value);
            }
            try {
                classifyFileClientSide(file).then(category => {
                    if (category) formData.append('category', category);
                }).catch(() => {});
            } catch(e) {}
            
            console.log('Starting upload for file:', file.name, 'Size:', file.size, 'Type:', file.type);
            console.log('Form data prepared:', {
                action: 'add',
                document_name: documentName,
                file_name: file.name
            });
            
            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON response:', e);
                        console.error('Raw response was:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                console.log('Upload response data:', data);
                console.log('Response type:', typeof data);
                console.log('Success property:', data.success);
                
                // ALWAYS add to recent uploads regardless of API response
                console.log('File upload attempt completed for:', file.name);
                
                // Force add to recent uploads immediately
                const uploadData = {
                    name: file.name,
                    type: file.name.split('.').pop().toLowerCase(),
                    progress: 100,
                    file: file,
                    fileSize: file.size,
                    uploadDate: new Date().toISOString()
                };
                
                console.log('About to add to recent uploads:', uploadData);
                
                // Add to recent uploads when file is successfully uploaded
                addToRecentUploads(uploadData);
                
                console.log('Successfully called addToRecentUploads for:', file.name);
                
                if (data && data.success) {
                    console.log('File uploaded successfully:', file.name);
                    onSuccess();
                } else {
                    console.log('Upload failed:', data ? data.message : 'Unknown error');
                    onError();
                }
            })
            .catch(error => {
                console.error('Upload error for file:', file.name, error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack
                });
                onError();
            });
        }

        function detectCategoryFromText(text) {
            try {
                if (!text || !DocumentsConfig.categories) return '';
                
                // Sort categories by priority for proper detection order
                const sortedCategories = DocumentsConfig.categoriesByPriority;
                
                for (const category of sortedCategories) {
                    const rules = DocumentsConfig.categories[category];
                    if (!rules) continue;
                    
                    // Check keywords first
                    const keywords = rules.keywords || [];
                    for (const keyword of keywords) {
                        const regex = new RegExp('\\b' + keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'i');
                        if (regex.test(text)) {
                            return category;
                        }
                    }
                    
                    // Check patterns
                    const patterns = rules.patterns || [];
                    for (const pattern of patterns) {
                        if (new RegExp(pattern.slice(1, -2), pattern.slice(-2)).test(text)) {
                            return category;
                        }
                    }
                }
            } catch(e) {
                console.error('Category detection error:', e);
            }
            return '';
        }

        async function classifyFileClientSide(file) {
            // Returns { category: string, ocr_excerpt: string }
            const result = { category: '', ocr_excerpt: '' };
            try {
                const name = (file.name || '').toLowerCase();
                if (/\b(mou|moa|memorandum|agreement|kuma-mou)\b/i.test(name)) result.category = 'MOUs & MOAs';
                if (/\b(template|form|admission|application|registration|checklist|request)\b/i.test(name)) result.category = result.category || 'Templates';
                if (/\b(registrar|transcript|tor|certificate|cor|gwa|grades|enrollment|student[-_\s]?record)\b/i.test(name)) result.category = result.category || 'Registrar Files';

                const isImage = /^image\//.test(file.type);
                const isPDF = /pdf$/i.test(file.name) || file.type === 'application/pdf';

                if (window.Tesseract) {
                    if (isImage && file.size < 6 * 1024 * 1024) {
                        const txt = await Tesseract.recognize(file, 'eng').then(r => (r && r.data && r.data.text) ? r.data.text : '').catch(() => '');
                        if (txt) {
                            result.ocr_excerpt = (txt || '').slice(0, 3000);
                            const cat = detectCategoryFromText(txt);
                            if (cat && !result.category) result.category = cat;
                        }
                    } else if (isPDF) {
                        try {
                            // Load PDF.js lazily if not already loaded
                            if (!window['pdfjsLib']) {
                                await window.lazyLoader.loadPDFJS();
                            }
                            
                            const buf = await file.arrayBuffer();
                            const pdf = await pdfjsLib.getDocument({ data: buf }).promise;
                            const maxPages = Math.min(2, pdf.numPages);
                            let aggregate = '';
                            for (let p = 1; p <= maxPages; p++) {
                                const page = await pdf.getPage(p);
                                const viewport = page.getViewport({ scale: 1.5 });
                                const canvas = document.createElement('canvas');
                                const ctx = canvas.getContext('2d');
                                canvas.width = viewport.width;
                                canvas.height = viewport.height;
                                await page.render({ canvasContext: ctx, viewport }).promise;
                                const txt = await Tesseract.recognize(canvas, 'eng').then(r => (r && r.data && r.data.text) ? r.data.text : '').catch(() => '');
                                if (txt) aggregate += '\n' + txt;
                            }
                            if (aggregate) {
                                result.ocr_excerpt = aggregate.slice(0, 3000);
                                const cat = detectCategoryFromText(aggregate);
                                if (cat && !result.category) result.category = cat;
                            }
                        } catch(e) { /* ignore OCR errors */ }
                    }
                }
            } catch(e) {}
            return result;
        }

        // Upload individual file (async to await classification)
        async function uploadSingleFile(file, index, onSuccess, onError) {
            // No file size/type restrictions
            try {
                const fileName = file.name;
                const baseFileName = fileName.replace(/\(\d+\)$/,'').trim();
                const isDuplicate = currentDocuments.some(doc => {
                    const docFileName = doc.filename || doc.document_name || doc.title || '';
                    const docBaseFileName = docFileName.replace(/\(\d+\)$/,'').trim();
                    return docBaseFileName.toLowerCase() === baseFileName.toLowerCase();
                });
                if (isDuplicate) {
                    showNotification(`File "${baseFileName}" already exists (including any versions). Please rename the file or choose a different one.`, 'error');
                    onError();
                    return;
                }

                const formData = new FormData();
                formData.append('action','add');
                formData.append('csrf_token', document.getElementById('csrf-token')?.value || '');
                formData.append('file', file);

                // Generate non-duplicating document name
                let documentName = file.name.replace(/\.[^/.]+$/, "");
                let counter = 1;
                const originalName = documentName;
                while (existingFileNames.has(documentName.toLowerCase())) {
                    documentName = `${originalName} (${counter})`;
                    counter++;
                }
                existingFileNames.add(documentName.toLowerCase());
                formData.append('document_name', documentName);

                // Add award type if selected
                const awardTypeSelect = document.getElementById('award-type-select');
                if (awardTypeSelect && awardTypeSelect.value) {
                    formData.append('award_type', awardTypeSelect.value);
                }

                // Use the new document categorizer
                let category = file._category || '';
                let confidence = file._confidence || 0;
                
                // If no category was determined during file selection, try to categorize now
                if (!category && window.documentCategorizer) {
                    try {
                        const content = await window.documentCategorizer.extractContent(file);
                        const result = await window.documentCategorizer.categorizeDocument(file, content);
                        category = result.category;
                        confidence = result.confidence;
                    } catch (e) {
                        console.warn('Failed to categorize file during upload:', e);
                    }
                }
                
                if (category) {
                    formData.append('category', category);
                    formData.append('category_confidence', confidence.toString());
                }

                // Auto-classify award type if not manually selected
                if (!awardTypeSelect.value && window.DocumentAnalyzer) {
                    try {
                        const analyzer = new DocumentAnalyzer();
                        const analysis = await analyzer.analyzeDocument(file);
                        if (analysis.classification && analysis.confidence > 0.3) {
                            formData.append('award_type', analysis.classification);
                            console.log(`Auto-classified document as: ${analysis.classification} (confidence: ${analysis.confidence})`);
                        }
                    } catch (e) {
                        console.warn('Failed to auto-classify award type:', e);
                    }
                }

                const res = await fetch('api/documents.php', { method:'POST', body: formData });
                const text = await res.text();
                let data;
                try { data = JSON.parse(text); } catch(e) { throw new Error('Invalid JSON response from server'); }

                // Add to recent uploads immediately
                const uploadData = { name: file.name, type: file.name.split('.').pop().toLowerCase(), progress: 100, file: file, fileSize: file.size, uploadDate: new Date().toISOString() };
                addToRecentUploads(uploadData);

                if (data && data.success) { onSuccess(); } else { onError(); }
            } catch (error) {
                onError();
            }
        }

        function showUploadProgressModal(files) {
            const modal = document.createElement('div');
            modal.id = 'upload-progress-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Uploading Files</h3>
                    </div>
                    <div class="space-y-3">
                        ${Array.from(files).map((file, index) => `
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-700 truncate">${file.name}</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin mr-2"></div>
                                    <span class="text-xs text-gray-500">Uploading...</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function updateUploadProgress(index, success) {
            const progressElements = document.querySelectorAll('#upload-progress-modal .animate-spin');
            if (progressElements[index]) {
                const parent = progressElements[index].parentElement;
                if (success) {
                    parent.innerHTML = `
                        <div class="w-4 h-4 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-xs text-green-600">Complete</span>
                    `;
                } else {
                    parent.innerHTML = `
                        <div class="w-4 h-4 bg-red-500 rounded-full mr-2"></div>
                        <span class="text-xs text-red-600">Failed</span>
                    `;
                }
            }
        }

        function closeUploadProgressModal() {
            const modal = document.getElementById('upload-progress-modal');
            if (modal) {
                modal.remove();
            }
        }

        // Initialize dropdown filters functionality
        function initializeDropdownFilters() {
            // All Documents dropdown
            const allDocumentsDropdown = document.querySelector('select:first-of-type');
            if (allDocumentsDropdown) {
                allDocumentsDropdown.addEventListener('change', function() {
                    const filterValue = this.value;
                    currentFilters.documentStatus = filterValue;
                    currentFilters.page = 1;
                    loadDocuments();
                });
            }

            // Documents Type dropdown
            const documentTypeDropdown = document.querySelector('select:last-of-type');
            if (documentTypeDropdown) {
                documentTypeDropdown.addEventListener('change', function() {
                    const filterValue = this.value;
                    currentFilters.fileType = filterValue;
                    currentFilters.page = 1;
                    loadDocuments();
                });
            }
        }

        // Initialize drag and drop functionality
        function initializeDragAndDrop() {
            const uploadArea = document.querySelector('.border-dashed');
            if (uploadArea) {
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('border-blue-500', 'bg-blue-50');
                });

                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-blue-500', 'bg-blue-50');
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-blue-500', 'bg-blue-50');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        handleFileUpload(files);
                    }
                });
            }
        }

        // Handle file upload
        function handleFileUpload(files) {
            const fileArray = Array.from(files);
            
            // Show upload progress
            showUploadProgress(fileArray);
            
            // Simulate upload process
            fileArray.forEach((file, index) => {
                setTimeout(() => {
                    uploadFile(file, index);
                }, index * 500);
            });
        }

        // Show upload progress
        function showUploadProgress(files) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Uploading Files</h3>
                    </div>
                    <div class="space-y-3">
                        ${files.map((file, index) => `
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">${file.name}</span>
                                <div class="flex items-center">
                                    <div class="w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin mr-2"></div>
                                    <span class="text-xs text-gray-500">Uploading...</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Upload individual file
        function uploadFile(file, index) {
            // Simulate upload progress
            const progress = Math.random() * 100;
            
            // Add to recent uploads
            addToRecentUploads({
                name: file.name,
                type: getFileExtension(file.name),
                progress: progress,
                file: file
            });
            
            // Update progress in modal
            updateUploadProgress(index, progress);
            
            if (progress >= 100) {
                setTimeout(() => {
                    document.querySelector('.fixed').remove();
                    
                    // Suppress all notifications during individual file upload completion
                    window.suppressNotifications(1000);
                    
                    loadDocuments();
                }, 1000);
            }
        }

        // Update upload progress
        function updateUploadProgress(index, progress) {
            const progressElements = document.querySelectorAll('.animate-spin');
            if (progressElements[index]) {
                const parent = progressElements[index].parentElement;
                parent.innerHTML = `
                    <div class="w-4 h-4 bg-green-500 rounded-full mr-2"></div>
                    <span class="text-xs text-green-600">Complete</span>
                `;
            }
        }

        // Initialize recent uploads interactions
        function initializeRecentUploads() {
            // Add event delegation for recent uploads
            document.addEventListener('click', function(e) {
                const uploadItem = e.target.closest('.bg-gray-50');
                if (uploadItem && uploadItem.closest('#recent-uploads')) {
                    const fileName = uploadItem.querySelector('span').textContent;
                    viewDocumentByName(fileName);
                }
                
                // Handle cancel/remove buttons
                const cancelBtn = e.target.closest('button[aria-label="Cancel upload"]');
                if (cancelBtn) {
                    const uploadItem = cancelBtn.closest('.bg-gray-50');
                    const fileName = uploadItem.querySelector('span').textContent;
                    removeFromRecentUploads(fileName);
                }
            });
        }

        // Add to recent uploads - Persistent storage based
        function addToRecentUploads(upload) {
            console.log('=== ADD TO RECENT UPLOADS CALLED ===');
            console.log('Upload data received:', upload);
            
            // Handle duplicate names in recent uploads
            let uploadName = upload.name;
            let counter = 1;
            const originalName = uploadName;
            
            // Check for duplicates in recent uploads
            while (recentUploads.some(u => u.name === uploadName)) {
                uploadName = `${originalName} (${counter})`;
                counter++;
            }
            
            // Enhance upload object with additional details
            const enhancedUpload = {
                ...upload,
                name: uploadName, // Use potentially modified name
                fileSize: upload.file ? upload.file.size : null,
                uploadDate: new Date().toISOString(),
                type: upload.type || (upload.file ? upload.file.name.split('.').pop().toLowerCase() : 'file')
            };
            
            console.log('Enhanced upload object:', enhancedUpload);
            
            // Add to local state (at the beginning for newest first)
            recentUploads.unshift(enhancedUpload);
            
            console.log('Updated recentUploads array:', recentUploads);
            console.log('Array length:', recentUploads.length);
            
            // Save to localStorage for persistence
            try {
                localStorage.setItem('lilac_recent_uploads', JSON.stringify(recentUploads));
                console.log('Saved to localStorage');
            } catch (error) {
                console.error('Failed to save to localStorage:', error);
            }
            
            console.log('About to call displayRecentUploads()...');
            
            // Update the display
            displayRecentUploads();
            
            console.log('displayRecentUploads() completed');
            
            console.log('=== ADD TO RECENT UPLOADS COMPLETED ===');
        }

        // Remove from recent uploads - Persistent storage based
        function removeFromRecentUploads(fileName) {
            // Remove from local state
            const index = recentUploads.findIndex(upload => upload.name === fileName);
            if (index !== -1) {
                recentUploads.splice(index, 1);
                
                // Save to localStorage
                try {
                    localStorage.setItem('lilac_recent_uploads', JSON.stringify(recentUploads));
                } catch (error) {
                    console.error('Failed to save to localStorage:', error);
                }
                
                // Update the display
                displayRecentUploads();
            }
        }
        
        // Load recent uploads from localStorage
        function loadRecentUploadsFromStorage() {
            try {
                const stored = localStorage.getItem('lilac_recent_uploads');
                if (stored) {
                    recentUploads = JSON.parse(stored);
                    console.log('Loaded recent uploads from localStorage:', recentUploads.length, 'items');
                }
            } catch (error) {
                console.error('Failed to load from localStorage:', error);
                recentUploads = [];
            }
        }

        // View document by name
        function viewDocumentByName(fileName) {
            const doc = currentDocuments.find(d => 
                (d.document_name || d.title || '').toLowerCase().includes(fileName.toLowerCase())
            );
            
            if (doc) {
                viewDocument(doc.id);
            } else {
                showNotification(`Opening ${fileName}`, 'info');
            }
        }

        // Initialize keyboard navigation
        function initializeKeyboardNavigation() {
            document.addEventListener('keydown', function(e) {
                // Tab navigation for dropdowns
                if (e.key === 'Tab') {
                    const dropdowns = document.querySelectorAll('select');
                    dropdowns.forEach(dropdown => {
                        if (document.activeElement === dropdown) {
                            dropdown.addEventListener('keydown', function(e) {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    this.focus();
                                }
                            });
                        }
                    });
                }

                // Escape key to close modals
                if (e.key === 'Escape') {
                    const modals = document.querySelectorAll('.fixed');
                    modals.forEach(modal => {
                        if (modal.style.display !== 'none') {
                            modal.remove();
                        }
                    });
                }
            });
        }

        // Helper function to get document type from extension
        function getDocumentTypeFromExtension(extension) {
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

        // Enhanced view document function
        function viewDocument(docId) {
            const doc = currentDocuments.find(d => d.id == docId);
            if (doc) {
                // Use the shared document viewer component
                if (window.documentViewer) {
                    const title = doc.document_name || doc.title || 'Untitled Document';
                    let filePath = doc.file_path || doc.filename;
                    const ext = getFileExtension(filePath || '');
                    
                    if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                        filePath = `uploads/${filePath}`;
                    }
                    
                    // Check if file exists before trying to view it
                    fetch(filePath, { method: 'HEAD' })
                        .then(response => {
                            if (response.ok) {
                                // File exists, proceed with viewing
                    const documentType = getDocumentTypeFromExtension(ext);
                    window.documentViewer.showDocument(filePath, documentType, title, doc.original_filename);
                            } else {
                                // File doesn't exist, show error and offer to remove from database
                                showNotification(`File not found: ${title}. The file may have been deleted.`, 'error');
                                console.error('File not found:', filePath);
                                
                                // Optionally, you could add a button to remove the database record
                                if (confirm('Would you like to remove this file from the database?')) {
                                    deleteDocument(docId);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error checking file existence:', error);
                            showNotification(`Error accessing file: ${title}`, 'error');
                        });
                } else {
                    showNotification('Document viewer not available', 'error');
                }
            } else {
                showNotification('Document not found', 'error');
            }
        }

		// Modal-based document viewer using shared component
		async function showDocumentViewer(doc) {
			const title = doc.document_name || doc.title || 'Untitled Document';
			const originalFilename = doc.original_filename || doc.document_name || doc.title || 'Untitled Document';
			let filePath = doc.file_path || doc.filename || '';
			const ext = getFileExtension(filePath || '');

			if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
				filePath = `uploads/${filePath}`;
			}

			let viewerType = 'unknown';
			if (ext === 'pdf') viewerType = 'pdf';
			else if (['png','jpg','jpeg','gif','webp','bmp','svg'].includes(ext)) viewerType = 'image';
			else if (['txt'].includes(ext)) viewerType = 'text';

			if (!filePath) {
				showNotification('File path not available.', 'error');
				return;
			}

			if (window.documentViewer && typeof window.documentViewer.showDocument === 'function') {
				window.documentViewer.showDocument(filePath, viewerType, title, originalFilename);
			} else {
				showNotification('Document viewer is not initialized.', 'error');
			}
		}

        // Download document
        function downloadDocument(docId) {
            const doc = currentDocuments.find(d => d.id == docId);
            if (doc) {
                const fileName = doc.original_filename || doc.document_name || doc.title || 'Untitled Document';
                // Ensure proper file path with uploads directory
                let filePath = doc.file_path || doc.filename || doc.filename;
                
                // If the path doesn't start with uploads/, add it
                if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                    filePath = `uploads/${filePath}`;
                }
                
                // If no filename is available, show error
                if (!filePath) {
                    showNotification('File path not available', 'error');
                    return;
                }
                
                console.log('Download attempt:', { doc, fileName, filePath });
                
                showNotification(`Downloading ${fileName}...`, 'info');
                
                // First check if the file exists
                fetch(filePath, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            // File exists, proceed with download
                            const link = document.createElement('a');
                            link.href = filePath;
                            link.download = fileName;
                            link.target = '_blank';
                            
                            // Add to DOM, click, and remove
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            showNotification(`${fileName} download started!`, 'success');
                        } else {
                            throw new Error(`File not found (${response.status})`);
                        }
                    })
                    .catch(error => {
                        console.error('Download error:', error);
                        showNotification(`Download failed: ${fileName} wasn't available on site`, 'error');
                        
                        // Show detailed error modal
                        showDownloadErrorModal(doc, fileName, filePath, error);
                    });
            } else {
                showNotification('Document not found', 'error');
            }
        }

        // Show download error modal with details
        function showDownloadErrorModal(doc, fileName, filePath, error) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Download Error</h3>
                        <button onclick="this.closest('.fixed').remove()" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                            Close
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-800 font-medium">${fileName} wasn't available on site</p>
                            <p class="text-xs text-red-600 mt-1">The file could not be found at the specified location.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File Details</label>
                            <div class="space-y-2 text-sm">
                                <div><strong>Name:</strong> ${fileName}</div>
                                <div><strong>Attempted Path:</strong> ${filePath}</div>
                                <div><strong>File Type:</strong> ${getFileExtension(doc.filename || '')}</div>
                                <div><strong>Document ID:</strong> ${doc.id}</div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Possible Solutions</label>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Check if the file exists in the uploads folder</li>
                                <li>• Verify the file path in the database</li>
                                <li>• Try re-uploading the file</li>
                                <li>• Contact administrator if issue persists</li>
                            </ul>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="retryDownload(${doc.id})" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Retry Download
                            </button>
                            <button onclick="this.closest('.fixed').remove()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Retry download function
        function retryDownload(docId) {
            // Close any existing error modals
            const modals = document.querySelectorAll('.fixed');
            modals.forEach(modal => {
                if (modal.innerHTML.includes('Download Error')) {
                    modal.remove();
                }
            });
            
            // Retry the download
            downloadDocument(docId);
        }

        // Share document
        function shareDocument(docId) {
            console.log('Share document called with ID:', docId);
            const doc = currentDocuments.find(d => d.id == docId);
            console.log('Found document:', doc);
            
            if (doc) {
                const fileName = doc.document_name || doc.title || 'Untitled Document';
                // Ensure proper file path with uploads directory
                let filePath = doc.file_path || doc.filename || doc.filename;
                
                // If the path doesn't start with uploads/, add it
                if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                    filePath = `uploads/${filePath}`;
                }
                
                // If no filename is available, show error
                if (!filePath) {
                    showNotification('File path not available', 'error');
                    return;
                }
                
                console.log('Sharing document:', { fileName, filePath });
                
                // Always show the custom share modal for now (more reliable)
                showShareModal(doc);
                
                // Optional: Try Web Share API first on mobile devices
                if (navigator.share && /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    navigator.share({
                        title: fileName,
                        text: `Check out this document: ${fileName}`,
                        url: window.location.origin + '/' + filePath
                    })
                    .then(() => {
                        showNotification(`${fileName} shared successfully!`, 'success');
                        // Close any open share modal
                        const shareModals = document.querySelectorAll('.fixed');
                        shareModals.forEach(modal => {
                            if (modal.innerHTML.includes('Share Document')) {
                                modal.remove();
                            }
                        });
                    })
                    .catch((error) => {
                        console.log('Web Share API failed, using custom modal:', error);
                        // Custom modal is already shown above
                    });
                }
            } else {
                showNotification('Document not found', 'error');
            }
        }

        // Show share modal for document
        function showShareModal(doc) {
            const fileName = doc.document_name || doc.title || 'Untitled Document';
            // Ensure proper file path with uploads directory
            let filePath = doc.file_path || doc.filename || doc.filename;
            
            // If the path doesn't start with uploads/, add it
            if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                filePath = `uploads/${filePath}`;
            }
            
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center';
            modal.addEventListener('click', function(e){ if (e.target === modal) modal.remove(); });
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Share Document</h3>
                        <button onclick="this.closest('.fixed').remove()" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                            Close
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document</label>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm font-medium text-gray-900">${fileName}</p>
                                <p class="text-xs text-gray-500">${filePath}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Share Link</label>
                            <div class="flex">
                                <input type="text" value="${window.location.origin}/${filePath}" 
                                       class="flex-1 border border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                       readonly>
                                <button onclick="copyToClipboard(this.previousElementSibling)" 
                                        class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700 transition-colors text-sm">
                                    Copy
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Share via Email</label>
                            <input type="email" placeholder="Enter email address" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="flex gap-2">
                            <button onclick="sendEmailShare('${fileName}', '${filePath}')" 
                                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Send Email
                            </button>
                            <button onclick="this.closest('.fixed').remove()" 
                                    class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Copy to clipboard function
        function copyToClipboard(inputElement) {
            inputElement.select();
            inputElement.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                document.execCommand('copy');
                showNotification('Link copied to clipboard!', 'success');
            } catch (err) {
                // Fallback for modern browsers
                navigator.clipboard.writeText(inputElement.value).then(() => {
                    showNotification('Link copied to clipboard!', 'success');
                }).catch(() => {
                    showNotification('Failed to copy link', 'error');
                });
            }
        }

        // Send email share
        function sendEmailShare(fileName, filePath) {
            const emailInput = document.querySelector('input[type="email"]');
            const email = emailInput ? emailInput.value.trim() : '';
            
            if (!email) {
                showNotification('Please enter an email address', 'error');
                return;
            }
            
            if (!email.includes('@')) {
                showNotification('Please enter a valid email address', 'error');
                return;
            }
            
            // Validate email format more thoroughly
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('Please enter a valid email address', 'error');
                return;
            }
            
            showNotification(`Sharing ${fileName} with ${email}...`, 'info');
            
            // Create form data for email sending
            const formData = new FormData();
            formData.append('action', 'send_email_share');
            formData.append('email', email);
            formData.append('fileName', fileName);
            formData.append('filePath', filePath);
            formData.append('subject', `Document Shared: ${fileName}`);
            formData.append('message', `Hello,\n\nA document has been shared with you: ${fileName}\n\nYou can access it at: ${window.location.origin}/${filePath}\n\nBest regards,\nLILAC System`);
            
            // Send email via API
            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(`${fileName} shared successfully with ${email}!`, 'success');
                    // Close the modal
                    const modal = document.querySelector('.fixed');
                    if (modal) {
                        modal.remove();
                    }
                } else {
                    showNotification(`Failed to send email: ${data.message || 'Unknown error'}`, 'error');
                }
            })
            .catch(error => {
                console.error('Email sending error:', error);
                showNotification('Failed to send email. Please try again.', 'error');
            });
        }

        // Enhanced delete document with confirmation
        function deleteDocumentWithConfirmation(docId) {
            const doc = currentDocuments.find(d => d.id == docId);
            if (!doc) return;
            
            // Create modal with form to prevent default submission
            const modal = document.createElement('div');
            modal.id = 'delete-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.addEventListener('click', function(e){ if (e.target === modal) modal.remove(); });
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4" role="dialog" aria-modal="true" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Delete Document</h3>
                    </div>
                    <div class="mb-4">
                        <p class="text-gray-700">Are you sure you want to delete "${doc.document_name || doc.title || 'Untitled Document'}"?</p>
                        <p class="text-sm text-gray-500 mt-2">This action cannot be undone.</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="delete-confirm-btn-${docId}" type="button" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 active:bg-gray-700 active:text-white transition-colors">
                            Delete
                        </button>
                        <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Get button references
            const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
            const cancelBtn = modal.querySelector('button:last-child');
            
            // Add event listeners
            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                confirmDeleteDocument(docId);
            });
            
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeDeleteModal();
            });
            
            // Keyboard event handling
            const handleKeyDown = function(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    e.stopPropagation();
                    closeDeleteModal();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    confirmDeleteDocument(docId);
                }
            };
            
            document.addEventListener('keydown', handleKeyDown);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeDeleteModal();
                }
            });
            
            // Store cleanup function on modal
            modal.cleanup = function() {
                document.removeEventListener('keydown', handleKeyDown);
            };
            
            // Focus the delete button for better accessibility
            setTimeout(() => {
                if (deleteBtn) {
                    deleteBtn.focus();
                }
            }, 100);
        }

        // Helper function to close delete modal
        function closeDeleteModal() {
            // Prefer targeted close by id, fallback to legacy content check
            const overlay = document.getElementById('delete-modal');
            if (overlay) {
                if (overlay.cleanup && typeof overlay.cleanup === 'function') {
                    overlay.cleanup();
                }
                overlay.remove();
                return;
            }
            const allModals = document.querySelectorAll('.fixed');
            allModals.forEach(modal => {
                if (modal.innerHTML.includes('Delete Document')) {
                    if (modal.cleanup && typeof modal.cleanup === 'function') {
                        modal.cleanup();
                    }
                    modal.remove();
                }
            });
        }

        // Global function to force close any stuck modals (can be called from browser console)
        window.forceCloseModals = function() {
            console.log('Force closing all modals...');
            const allModals = document.querySelectorAll('.fixed');
            console.log('Found', allModals.length, 'modals');
            allModals.forEach((modal, index) => {
                console.log('Removing modal', index, ':', modal.innerHTML.substring(0, 100) + '...');
                modal.remove();
            });
            console.log('All modals closed');
        };

        // Global function to reset any stuck delete buttons
        window.resetDeleteButtons = function() {
            console.log('Resetting all delete buttons...');
            const deleteButtons = document.querySelectorAll('button[id^="delete-confirm-btn-"]');
            deleteButtons.forEach(btn => {
                btn.disabled = false;
                btn.textContent = 'Delete';
            });
            console.log('All delete buttons reset');
        };

        // Global function to suppress all notifications temporarily
        window.suppressNotifications = function(duration = 1000) {
            // Completely disable all notifications
            if (window.lilacNotifications) {
                window.lilacNotifications.success = function() {};
                window.lilacNotifications.show = function() {};
                window.lilacNotifications.error = function() {};
                window.lilacNotifications.info = function() {};
            }
        };



        // Confirm delete document
        function confirmDeleteDocument(docId) {
            const doc = currentDocuments.find(d => d.id == docId);
            if (!doc) return;
            
            // Show loading state
            const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.textContent = 'Deleting...';
            }
            
            // Create form data for API call
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', docId);
            
            // Add timeout to prevent infinite loading
            const timeoutId = setTimeout(() => {
                console.log('Delete request timed out, forcing modal close');
                showNotification('Request timed out. Please try again.', 'error');
                
                // Force close modal
                const allModals = document.querySelectorAll('.fixed');
                allModals.forEach(modal => {
                    if (modal.innerHTML.includes('Delete Document')) {
                        modal.remove();
                    }
                });
                
                // Reset button state
                const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.textContent = 'Delete';
                }
            }, 10000); // 10 second timeout
            
            // Call the API to delete the document
            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                clearTimeout(timeoutId); // Clear timeout on response
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text().then(text => {
                    console.log('Response text:', text);
                    
                    if (!text || text.trim() === '') {
                        throw new Error('Empty response from server');
                    }
                    
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                console.log('Delete response data:', data);
                console.log('Response type:', typeof data);
                console.log('Success property:', data.success);
                
                if (data && data.success) {
                    console.log('Delete successful, closing modal...');
                    
                    // Force close the modal immediately
                    closeDeleteModal();
                    
                    // Remove the deleted document from selection if it was selected
                    selectedDocuments.delete(docId);
                    
                    // Update select all state
                    selectAllChecked = selectedDocuments.size === currentDocuments.length;
                    
                    // Reload documents to reflect changes (silently)
                    loadDocuments();
                    loadStats();
                    
                    // Completely suppress any notifications for delete operations
                    if (window.lilacNotifications) {
                        // Store original methods
                        const originalSuccess = window.lilacNotifications.success;
                        const originalShow = window.lilacNotifications.show;
                        
                        // Disable all notification methods
                        window.lilacNotifications.success = function() {};
                        window.lilacNotifications.show = function() {};
                        
                        // Re-enable after a longer delay to ensure no notifications show
                        setTimeout(() => {
                            window.lilacNotifications.success = originalSuccess;
                            window.lilacNotifications.show = originalShow;
                        }, 500);
                    }
                } else {
                    console.log('Delete failed:', data);
                    showNotification(data.message || 'Error deleting document', 'error');
                    
                    // Reset button state
                    const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
                    if (deleteBtn) {
                        deleteBtn.disabled = false;
                        deleteBtn.textContent = 'Delete';
                    }
                }
            })
            .catch(error => {
                clearTimeout(timeoutId); // Clear timeout on error
                console.error('Error deleting document:', error);
                showNotification('Network error. Please try again.', 'error');
                
                // Reset button state
                const deleteBtn = document.getElementById(`delete-confirm-btn-${docId}`);
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.textContent = 'Delete';
                }
            });
        }

        // Show share modal
        function showShareModal() {
            const modal = document.createElement('div');
            modal.id = 'share-documents-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center';
            modal.addEventListener('click', function(e){ if (e.target === modal) modal.remove(); });
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Share Documents</h3>
                        <button id="close-share-modal" class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                            Close
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Documents</label>
                            <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-2">
                                ${currentDocuments.slice(0, 5).map(doc => `
                                    <label class="flex items-center p-2 hover:bg-gray-50 rounded">
                                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="${doc.id}">
                                        <span class="ml-2 text-sm text-gray-700">${doc.document_name || doc.title || 'Untitled Document'}</span>
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Share with</label>
                            <input type="email" placeholder="Enter email addresses" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message (optional)</label>
                            <textarea rows="3" placeholder="Add a message..." class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button id="share-button" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Share
                            </button>
                            <button id="cancel-share-button" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add event listeners after modal is added to DOM
            setTimeout(() => {
                const shareButton = document.getElementById('share-button');
                const cancelButton = document.getElementById('cancel-share-button');
                const closeButton = document.getElementById('close-share-modal');
                
                if (shareButton) {
                    console.log('Adding click listener to share button');
                    shareButton.addEventListener('click', function(e) {
                        console.log('Share button clicked!');
                        e.preventDefault();
                        e.stopPropagation();
                        shareSelectedDocuments();
                    });
                } else {
                    console.error('Share button not found');
                }
                
                if (cancelButton) {
                    cancelButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        modal.remove();
                    });
                }
                
                if (closeButton) {
                    closeButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        modal.remove();
                    });
                }
            }, 100);
            
            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Share selected documents
        function shareSelectedDocuments() {
            console.log('=== SHARE SELECTED DOCUMENTS FUNCTION CALLED ===');
            
            const modal = document.getElementById('share-documents-modal');
            if (!modal) {
                console.error('Share modal not found');
                showNotification('Share modal not found', 'error');
                return;
            }
            
            const selectedCheckboxes = modal.querySelectorAll('input[type="checkbox"]:checked');
            const emailInput = modal.querySelector('input[type="email"]');
            const messageInput = modal.querySelector('textarea');
            const shareButton = document.getElementById('share-button');
            
            console.log('Found elements:', {
                modal: !!modal,
                selectedCheckboxes: selectedCheckboxes.length,
                emailInput: !!emailInput,
                messageInput: !!messageInput
            });
            
            // Validate that at least one document is selected
            if (selectedCheckboxes.length === 0) {
                showNotification('Please select at least one document to share', 'error');
                return;
            }
            
            // Validate email address
            const email = emailInput.value.trim();
            if (!email) {
                showNotification('Please enter an email address', 'error');
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('Please enter a valid email address', 'error');
                return;
            }
            
            // Get selected document IDs
            const selectedDocumentIds = Array.from(selectedCheckboxes).map(cb => cb.value);
            
            // Get optional message
            const message = messageInput.value.trim();
            
            // Show loading state
            if (shareButton) {
                shareButton.disabled = true;
                shareButton.textContent = 'Sending...';
                shareButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
            
            // Prepare request payload
            const payload = {
                documents: selectedDocumentIds,
                email: email,
                message: message
            };
            
            console.log('Sending share request with payload:', payload);
            
            // Call backend API
            fetch('api/documents.php?action=send_email_share', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                console.log('API response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('API response data:', data);
                
                // Reset button state
                if (shareButton) {
                    shareButton.disabled = false;
                    shareButton.textContent = 'Share';
                    shareButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                if (data.success) {
                    if (data.local_dev) {
                        showNotification(`Documents prepared for sharing! Email content saved to ${data.log_file} (local development mode)`, 'success');
                        modal.remove();
                    } else {
                        showNotification(`Documents shared successfully! Email sent to ${email}`, 'success');
                        modal.remove();
                    }
                } else {
                    showNotification(`Failed to send email: ${data.message || 'Unknown error'}`, 'error');
                }
            })
            .catch(error => {
                console.error('Share error:', error);
                
                // Reset button state
                if (shareButton) {
                    shareButton.disabled = false;
                    shareButton.textContent = 'Share';
                    shareButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                showNotification('Failed to send email. Please try again.', 'error');
            });
        }

        function permanentlyDeleteDocument(docId) {
            showDeleteConfirmModal(docId);
        }

        // Trash rendering and actions
        function renderTrash(items) {
            const container = document.getElementById('trash-container');
            if (!container) return;
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="text-sm text-gray-500">No deleted files.</div>';
                return;
            }
            container.innerHTML = items.map(function(item){ return (
                '<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">'
                + '<div class="min-w-0">'
                + '<div class="text-sm font-medium text-gray-900 truncate">' + (item.document_name || item.filename || 'Untitled') + '</div>'
                + '<div class="text-xs text-gray-500">Deleted ' + new Date(item.deleted_at||Date.now()).toLocaleString() + '</div>'
                + '</div>'
                + '<div class="flex items-center gap-2">'
                + '<button class="px-3 py-1.5 text-sm bg-gray-100 rounded-lg hover:bg-gray-200" onclick="restoreTrash(' + item.id + ')">Restore</button>'
                + '<button class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="permanentlyDeleteTrash(' + item.id + ')">Delete</button>'
                + '</div>'
                + '</div>'
            ); }).join('');
        }

        function restoreTrash(trashId) {
            const fd = new FormData();
            fd.append('action','restore');
            fd.append('trash_id', trashId);
            fetch('api/documents.php', { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(d){ loadDocuments(); if (d && d.message) { showNotification(d.message,'success'); } })
                .catch(function(){});
        }

        function permanentlyDeleteTrash(trashId) {
            const fd = new FormData();
            fd.append('action','permanently_delete');
            fd.append('trash_id', trashId);
            fetch('api/documents.php', { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(d){ loadDocuments(); if (d && d.message) { showNotification(d.message,'success'); } })
                .catch(function(){});
        }

        function emptyTrash() {
            const fd = new FormData();
            fd.append('action','empty_trash');
            fetch('api/documents.php', { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(d){ loadDocuments(); if (d && d.message) { showNotification(d.message,'success'); } })
                .catch(function(){});
        }

        // Add a simple test function to check if stats are working
        function testStatsAPI() {
            console.log(' Testing stats API...');
            fetch('api/documents.php?action=get_stats')
                .then(response => response.text())
                .then(text => {
                    console.log('Raw API response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        if (data.success && data.stats) {
                            console.log('Stats data:', data.stats);
                            console.log('Total documents:', data.stats.total);
                            console.log('Recent documents:', data.stats.recent);
                        } else {
                            console.error('API returned error:', data);
                        }
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                    }
                })
                .catch(error => {
                    console.error('API call failed:', error);
                });
        }
        
        // Make it available globally
        window.testStatsAPI = testStatsAPI;

        // FIXED: Real file selection handler that actually processes files
        async function handleFileSelection(files) {
            console.log('handleFileSelection called with', files.length, 'files');
            
            if (isProcessingFiles) {
                console.log('Already processing files, ignoring duplicate call');
                return;
            }
            
            isProcessingFiles = true;
            
            if (!files || files.length === 0) {
                console.log('No files selected');
                isProcessingFiles = false;
                return;
            }
            
            const fileArray = Array.from(files);
            console.log('Processing files:', fileArray.map(f => f.name));
            
            // Show upload progress
            const uploadProgress = document.getElementById('upload-progress');
            if (uploadProgress) {
                uploadProgress.classList.remove('hidden');
            }
            
            try {
                let successCount = 0;
                let errorCount = 0;
                
                // Process each file
                for (let i = 0; i < fileArray.length; i++) {
                    const file = fileArray[i];
                    console.log(`Processing file ${i + 1}/${fileArray.length}: ${file.name}`);
                    
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('action', 'create_event');
                    formData.append('title', file.name.replace(/\.[^/.]+$/, "").replace(/[-_]/g, ' '));
                    formData.append('description', 'Event created from uploaded file');
                    formData.append('event_date', new Date().toISOString().split('T')[0]);
                    formData.append('location', 'To be determined');
                    
                    // Upload to central events API
                    const response = await fetch('api/central_events_api.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        console.log(`✅ File ${file.name} uploaded and event created successfully`);
                        successCount++;
                    } else {
                        console.error(`❌ Failed to create event from ${file.name}:`, result.data?.message || result.message);
                        errorCount++;
                    }
                }
                
                // Hide progress after processing
                if (uploadProgress) {
                    uploadProgress.classList.add('hidden');
                }
                
                // Show appropriate success/error message
                if (successCount > 0 && errorCount === 0) {
                    showNotification(`Successfully uploaded ${successCount} file(s) and created events!`, 'success');
                } else if (successCount > 0 && errorCount > 0) {
                    showNotification(`Uploaded ${successCount} file(s) successfully, ${errorCount} failed.`, 'warning');
                } else {
                    showNotification(`Failed to upload files. Please try again.`, 'error');
                }
                
                // Refresh events list to show new events
                setTimeout(() => {
                    refreshEventsFromAPI();
                }, 1000);
                
            } catch (error) {
                console.error('Error processing files:', error);
                
                // Hide progress on error
                if (uploadProgress) {
                    uploadProgress.classList.add('hidden');
                }
                
                showNotification('Error uploading files: ' + error.message, 'error');
            } finally {
                isProcessingFiles = false;
            }
        }

        // FIXED: Update event counters based on calendar dates, not just status field
        function updateEventCounters(events) {
            try {
                const upcomingCountElement = document.getElementById('upcoming-count');
                const completedCountElement = document.getElementById('completed-count');
                
                if (!upcomingCountElement || !completedCountElement) {
                    console.warn('Counter elements not found');
                    return;
                }
                
                const now = new Date();
                now.setHours(0, 0, 0, 0); // Set to start of today for accurate comparison
                
                // Count events by actual calendar dates, not just status field
                let upcomingCount = 0;
                let completedCount = 0;
                
                events.forEach(event => {
                    const eventDate = new Date(event.start || event.event_date || event.date);
                    eventDate.setHours(0, 0, 0, 0); // Set to start of day for comparison
                    
                    if (eventDate >= now) {
                        upcomingCount++;
                    } else {
                        completedCount++;
                    }
                });
                
                // Update the counter displays
                upcomingCountElement.textContent = upcomingCount;
                completedCountElement.textContent = completedCount;
                
                console.log(`📊 Updated counters based on calendar dates - Upcoming: ${upcomingCount}, Completed: ${completedCount}`);
                
                // Update statuses in database to match calendar dates
                updateEventStatusesInDatabase();
                
            } catch (error) {
                console.error('Error updating event counters:', error);
            }
        }

    </script>
</head>

<body class="bg-[#F8F8FF]">
    <!-- CSRF Token -->
    <input type="hidden" id="csrf-token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    
    <!-- Shared Document Viewer Modal -->
    <?php include 'components/shared-document-viewer.php'; ?>

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between relative transition-all duration-300 ease-in-out">
        <div class="flex items-center space-x-4 pl-16">
            <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <h1 class="text-xl font-bold text-gray-800 cursor-pointer" onclick="location.reload()">Documents</h1>
            
            <a href="dashboard.php" class="flex items-center space-x-3 hover:opacity-80 transition-opacity cursor-pointer">
            </a>
        </div>

        <div class="text-sm flex items-center space-x-4">
            <!-- View Toggle Buttons -->
            <div class="flex items-center bg-gray-100 rounded-lg p-1">
                <button data-view="grid" class="p-2 rounded-md text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </button>
                <button data-view="list" class="p-2 rounded-md bg-gray-200 text-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-documents" name="search-documents"
                       class="block w-64 pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm"
                       placeholder="Search">
                <button type="button" id="clear-search" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 hidden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Filters Dropdown Trigger -->
            <div class="relative">
                <button id="filters-trigger" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M8 12h8m-6 8h4"></path></svg>
                    Filters
                    <span id="filters-badge" class="hidden ml-1 inline-flex items-center justify-center px-1.5 text-xs rounded-full bg-blue-600 text-white">0</span>
                </button>
                <div id="filters-panel" class="hidden absolute right-0 mt-2 w-96 bg-white border border-gray-200 rounded-lg shadow-xl p-4 z-[70]">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                            <div id="filter-categories" class="flex flex-wrap gap-2 max-h-28 overflow-y-auto"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">File type</label>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <label class="flex items-center gap-2"><input type="checkbox" class="filter-type" value="pdf"> PDF</label>
                                <label class="flex items-center gap-2"><input type="checkbox" class="filter-type" value="doc|docx"> Word</label>
                                <label class="flex items-center gap-2"><input type="checkbox" class="filter-type" value="jpg|jpeg|png"> Images</label>
                                <label class="flex items-center gap-2"><input type="checkbox" class="filter-type" value="txt"> Text</label>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Date from</label>
                                <input id="filter-date-from" type="date" class="w-full border border-gray-300 rounded-md px-2 py-1 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Date to</label>
                                <input id="filter-date-to" type="date" class="w-full border border-gray-300 rounded-md px-2 py-1 text-sm">
                            </div>
                        </div>

                        <div class="flex justify-between pt-2">
                            <button id="filters-reset" class="text-sm text-gray-600 hover:text-gray-800">Reset</button>
                            <div class="space-x-2">
                                <button id="filters-close" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Close</button>
                                <button id="filters-apply" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button onclick="showUploadModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 0l-4 4m4-4l4 4"/></svg>
                    Upload
                </button>
                <button onclick="showTrashModal()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors font-medium text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Trash
                </button>
                <button onclick="showRuleManagementModal()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Rules
                </button>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

        	<!-- Main Content -->
	<div id="main-content" class="p-4 pt-3 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out">
		<!-- Content Area -->
		<div class="">
            <!-- Stats Cards -->
            <div class="grid grid-cols-3 gap-3 mb-4">
                <!-- Total Documents Card -->
                <div class="bg-white bg-opacity-80 backdrop-blur-xl rounded-xl border border-white border-opacity-30 p-3 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-600">Total Documents</p>
                            <p id="total-documents" class="text-lg font-bold text-gray-900">0</p>
                        </div>
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Recent Documents Card -->
                <div class="bg-white bg-opacity-80 backdrop-blur-xl rounded-xl border border-white border-opacity-30 p-3 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-600">Recent (30 days)</p>
                            <p id="recent-documents" class="text-lg font-bold text-gray-900">0</p>
                        </div>
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Document Categories Card -->
                <div class="bg-white bg-opacity-80 backdrop-blur-xl rounded-xl border border-white border-opacity-30 p-3 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-600">Categories</p>
                            <p id="document-types" class="text-lg font-bold text-gray-900">0</p>
                        </div>
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Container -->
            <div id="documents-container" class="bg-white bg-opacity-80 backdrop-blur-xl rounded-3xl border border-white border-opacity-30 overflow-hidden shadow-2xl">
                <!-- Quick Filter Chips -->
                <div class="px-6 pt-4 pb-2 border-b border-gray-100 flex flex-wrap gap-2" role="group" aria-label="Quick filters">
                    <button class="chip-filter px-4 py-1.5 text-sm rounded-full border border-gray-200 bg-white/80 text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-all" data-group="all" aria-pressed="false">View all</button>
                    <button class="chip-filter px-4 py-1.5 text-sm rounded-full border border-gray-200 bg-white/80 text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-all" data-group="documents" aria-pressed="false">Documents</button>
                    <button class="chip-filter px-4 py-1.5 text-sm rounded-full border border-gray-200 bg-white/80 text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-all" data-group="spreadsheets" aria-pressed="false">Spreadsheets</button>
                    <button class="chip-filter px-4 py-1.5 text-sm rounded-full border border-gray-200 bg-white/80 text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-all" data-group="pdfs" aria-pressed="false">PDFs</button>
                    <button class="chip-filter px-4 py-1.5 text-sm rounded-full border border-gray-200 bg-white/80 text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-all" data-group="images" aria-pressed="false">Images</button>
                </div>
                <!-- Active Filter Chips -->
                <div id="active-filter-chips" class="px-6 pt-3 pb-1 hidden flex flex-wrap gap-2"></div>
                <!-- Bulk Actions Bar -->
                <div id="bulk-actions-bar" class="hidden bg-blue-50 border-b border-blue-200 px-6 py-3 rounded-t-3xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm text-blue-700 font-medium">
                                <span id="selected-count">0</span> document<span id="selected-plural">s</span> selected
                            </span>
                        </div>
                        <div class="flex items-center">
                            <button id="bulk-delete-btn" onclick="bulkDeleteSelected()" class="bg-red-600 text-white px-3 py-1.5 rounded-md hover:bg-red-700 transition-colors text-sm font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- List View -->
                                    <div id="list-view" class="overflow-x-auto min-h-[550px]">
                        <table class="w-full table-fixed">
                        <thead class="bg-gray-50 border-b border-gray-200 rounded-t-3xl">
                            <tr>
                                <th class="w-16 px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all-checkbox" onclick="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="w-[40%] px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="w-[12%] px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="w-[12%] px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="w-[24%] px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="w-[12%] px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="documents-table-body" class="bg-white divide-y divide-gray-200 min-h-[400px]">
                            <!-- Documents will be loaded here -->
                        </tbody>
                    </table>
                    
                    <!-- Loading State -->
                    <div id="loading-state" class="hidden absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center">
                        <div class="flex items-center space-x-2">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                            <span class="text-gray-600">Loading documents...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Grid View -->
                <div id="grid-view" class="hidden rounded-b-3xl min-h-[400px]">
                    <!-- Grid View Header with Select All -->
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="grid-select-all-checkbox" onclick="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span id="grid-select-all-text" class="text-sm font-medium text-gray-700 hidden">Select All</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span id="grid-selected-count">0</span> selected
                            </div>
                        </div>
                    </div>
                    <!-- Grid Content -->
                    <div class="p-6">
                        <div id="documents-grid-body" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <!-- Documents will be loaded here -->
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        
    </div>


    <!-- Trash Bin Modal -->
    <div id="trashModal" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden" onclick="hideTrashModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Trash Bin</h3>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="emptyTrash()">Empty Trash</button>
                    </div>
                </div>
                <div class="p-4 max-h-[60vh] overflow-y-auto">
                    <div id="trash-container" class="space-y-2">
                        <div class="text-sm text-gray-500">No deleted files.</div>
                    </div>
                </div>
                <div class="p-4 border-t flex justify-end">
                    <button class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="hideTrashModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rule Management Modal -->
    <div id="ruleManagementModal" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden" onclick="hideRuleManagementModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-900">Document Categorization Rules</h3>
                    <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="hideRuleManagementModal()">Close</button>
                </div>
                <div class="p-6 max-h-[70vh] overflow-y-auto">
                    <div class="space-y-6">
                        <!-- Rule Categories -->
                        <div id="rule-categories-container">
                            <!-- Categories will be loaded here -->
                        </div>
                        
                        <!-- Add New Rule Section -->
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Add New Rule</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                    <input type="text" id="new-rule-category" placeholder="e.g., Research Papers" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority (1-10)</label>
                                    <input type="number" id="new-rule-priority" min="1" max="10" value="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Keywords (comma-separated)</label>
                                    <input type="text" id="new-rule-keywords" placeholder="e.g., research, study, analysis, paper" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">File Name Patterns (comma-separated)</label>
                                    <input type="text" id="new-rule-patterns" placeholder="e.g., research, study, analysis" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                                <div class="md:col-span-2">
                                    <button onclick="addNewRule()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                        Add Rule
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hamburger button is now handled globally by LILACSidebar

            // Responsive floating button on scroll
            let lastScrollTop = 0;
            const floatingBtn = document.getElementById('view-switch-btn');
            const floatingBtnContainer = floatingBtn?.parentElement;
            
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (floatingBtnContainer) {
                    if (scrollTop > lastScrollTop && scrollTop > 100) {
                        // Scrolling down - move button up (current position above footer)
                        floatingBtnContainer.classList.remove('floating-btn-normal');
                        floatingBtnContainer.classList.add('floating-btn-scrolled');
                    } else {
                        // Scrolling up - move button down (old position at bottom)
                        floatingBtnContainer.classList.remove('floating-btn-scrolled');
                        floatingBtnContainer.classList.add('floating-btn-normal');
                    }
                }
                
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            });
        });
        
        // toggleSidebar function is now handled globally by LILACSidebar
    </script>

</body>

</html>
