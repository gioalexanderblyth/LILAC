<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Templates</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script src="js/modal-handlers.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        if (window['pdfjsLib']) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>
    <script>
        // Initialize Templates functionality
        let currentDocuments = [];
        let showExpiringOnly = false;
        const CATEGORY = 'Templates';
        const TABS = ['Recommended', 'Reports', 'Meeting notes'];
        let activeTab = 'Recommended';
        let favorites = new Set();

        // Map UI tabs to existing category names in the system
        const TAB_TO_CATEGORY = {
            'Reports': 'Report',
            'Meeting notes': 'Meeting Minutes'
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Load favorites from localStorage
            try {
                favorites = new Set(JSON.parse(localStorage.getItem('templateFavorites') || '[]'));
            } catch (e) { favorites = new Set(); }
            renderTemplateTabs();
            loadDocuments();
            loadStats();
            initializeEventListeners();
            updateCurrentDate();
            
            // Update date every minute
            setInterval(updateCurrentDate, 60000);
            
            // Ensure LILAC notifications appear below navbar
            setTimeout(function() {
                if (window.lilacNotifications && window.lilacNotifications.container) {
                    // Force reposition the LILAC container itself - navbar is 64px tall
                    window.lilacNotifications.container.style.top = '80px'; // 64px navbar + 16px gap
                    window.lilacNotifications.container.style.zIndex = '99999';
                }
            }, 500);
        });

        function updateCurrentDate() {
            const now = new Date();
            if (dateElement) {
                dateElement.textContent = now.toLocaleDateString('en-US', {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }
        }



        function initializeEventListeners() {
            // Search and filter functionality
            const searchInput = document.getElementById('search-templates');
            if (searchInput) {
                searchInput.addEventListener('input', filterDocuments);
            }

            const statusFilter = document.getElementById('status-filter');
            if (statusFilter) {
                statusFilter.addEventListener('change', filterDocuments);
            }

            // Modal event listeners
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');
            const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            if (closeDeleteModalBtn) closeDeleteModalBtn.addEventListener('click', hideDeleteModal);
            if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', hideDeleteModal);
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', () => {
                    if (templateToDelete !== null) {
                        deleteTemplate(templateToDelete);
                    }
                    hideDeleteModal();
                });
            }
        }


        function filterDocuments() {
            const searchTerm = document.getElementById('search-templates').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;

            let filtered = currentDocuments.filter(doc => {
                const matchesSearch = doc.name.toLowerCase().includes(searchTerm) ||
                                    (doc.description && doc.description.toLowerCase().includes(searchTerm));
                const matchesStatus = statusFilter === 'all' || doc.status.toLowerCase() === statusFilter.toLowerCase();
                return matchesSearch && matchesStatus;
            });

            displayDocuments(filtered);
        }

        function loadDocuments() {
            fetch(`api/documents.php?action=get_by_category&category=${encodeURIComponent(CATEGORY)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentDocuments = data.documents;
                        displayDocuments(data.documents);
                    }
                })
                .catch(error => console.error('Error loading documents:', error));
        }

        function loadStats() {
            fetch(`api/documents.php?action=get_stats_by_category&category=${encodeURIComponent(CATEGORY)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStats(data.stats);
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        }

        function updateStats(stats) {
            const totalTemplatesElement = document.getElementById('total-templates');
            const activeTemplatesElement = document.getElementById('active-templates');
            const draftTemplatesElement = document.getElementById('draft-templates');
            
            if (totalTemplatesElement) {
                totalTemplatesElement.textContent = stats.total;
            }
            if (activeTemplatesElement) {
                // Count active templates from current documents
                const activeCount = currentDocuments.filter(doc => 
                    doc.status && doc.status.toLowerCase() === 'active'
                ).length;
                activeTemplatesElement.textContent = activeCount;
            }
            if (draftTemplatesElement) {
                // Count draft templates from current documents
                const draftCount = currentDocuments.filter(doc => 
                    doc.status && doc.status.toLowerCase() === 'draft'
                ).length;
                draftTemplatesElement.textContent = draftCount;
            }
        }

        function displayDocuments(documents) {
            const container = document.getElementById('templates-container');
            if (!container) return;

            // Search and sort controls
            const q = (document.getElementById('template-search')?.value || '').toLowerCase();
            const sortBy = (document.getElementById('template-sort')?.value || 'newest');

            // Filter by active tab
            let filtered = documents || [];
            if (activeTab !== 'Recommended') {
                const mapped = TAB_TO_CATEGORY[activeTab] || '';
                filtered = filtered.filter(doc => (doc.category || '').toLowerCase() === mapped.toLowerCase());
            } else {
                filtered = filtered.slice(0, 12);
            }

            // Text filter
            if (q) {
                filtered = filtered.filter(doc => {
                    const name = (doc.name || doc.document_name || '').toLowerCase();
                    const desc = (doc.description || '').toLowerCase();
                    return name.includes(q) || desc.includes(q);
                });
            }

            // Sort
            filtered.sort((a, b) => {
                if (sortBy === 'alpha') {
                    return (a.name || a.document_name || '').localeCompare(b.name || b.document_name || '');
                }
                if (sortBy === 'popular') {
                    return (b.file_size || 0) - (a.file_size || 0); // placeholder popularity
                }
                // newest
                return new Date(b.created_at || 0) - new Date(a.created_at || 0);
            });

            // Empty state
            if (filtered.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12 text-gray-500 border-2 border-dashed border-gray-200 rounded-xl">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="font-medium">No templates found for this category</p>
                        <p class="text-sm">Try another tab or create a new one</p>
                    </div>`;
                return;
            }

            const cards = filtered.map(doc => {
                const title = doc.name || doc.document_name || 'Untitled Template';
                const badge = `<span class="absolute top-3 left-3 text-[10px] px-2 py-0.5 rounded-full bg-white/90 text-gray-800 border">${doc.category || 'Template'}</span>`;
                const fav = favorites.has(String(doc.id));
                const app = getAppBadge(doc.category);
                return `
                    <div class="group relative rounded-2xl overflow-hidden bg-white shadow-sm border hover:shadow-md transition-all">
                        <button class="absolute top-3 right-3 z-10 p-2 rounded-full bg-white/90 hover:bg-white shadow" title="Favorite" onclick="event.stopPropagation(); toggleFavorite('${doc.id}')">
                            <svg class="w-4 h-4 ${fav ? 'text-yellow-500' : 'text-gray-400'}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118L10 13.347l-2.985 2.155c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L3.38 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </button>
                        <div class="h-56 bg-gradient-to-br from-gray-900 to-gray-700 relative cursor-pointer" onclick="viewTemplate(${doc.id})">
                            ${badge}
                            <div class="absolute bottom-3 left-3 right-3 flex items-end justify-start">
                                <div class="inline-block bg-yellow-400 text-black font-extrabold text-xs px-2 py-1 rounded">${(doc.category||'').toLowerCase().includes('report') ? 'ANNUAL REPORT' : 'TEMPLATE'}</div>
                                ${app}
                            </div>
                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <button class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm shadow hover:bg-blue-700" onclick="event.stopPropagation(); viewTemplate(${doc.id})">Preview</button>
                            </div>
                        </div>
                        <div class="p-3">
                            <p class="font-semibold text-gray-800 truncate" title="${title}">${title}</p>
                            <div class="mt-2 flex items-center justify-start text-xs text-gray-500">
                                <span>${formatFileSize(doc.file_size || 0)}</span>
                                <span>#${doc.id}</span>
                            </div>
                        </div>
                    </div>`;
            }).join('');

            container.innerHTML = `<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">${cards}</div>`;
        }

        function renderTemplateTabs() {
            const tabsEl = document.getElementById('template-tabs');
            if (!tabsEl) return;
            tabsEl.innerHTML = TABS.map(tab => {
                const isActive = tab === activeTab;
                return `<button data-tab="${tab}" class="px-4 py-2 rounded-full text-sm font-medium ${isActive ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'}">${tab}</button>`;
            }).join('');

            Array.from(tabsEl.querySelectorAll('button[data-tab]')).forEach(btn => {
                btn.addEventListener('click', () => {
                    activeTab = btn.getAttribute('data-tab');
                    renderTemplateTabs();
                    displayDocuments(currentDocuments);
                });
            });
        }


        function goToDocumentEditor() {
            window.location.href = 'document-editor.html';
        }

        function toggleFavorite(id) {
            const key = String(id);
            if (favorites.has(key)) favorites.delete(key); else favorites.add(key);
            try { localStorage.setItem('templateFavorites', JSON.stringify(Array.from(favorites))); } catch(e) {}
            displayDocuments(currentDocuments);
        }

        function getAppBadge(category) {
            const type = (category || '').toLowerCase();
            if (type.includes('report') || type.includes('meeting')) {
                return '<div class="w-8 h-8 rounded-md bg-white shadow flex items-center justify-center text-blue-600 font-bold text-sm">W</div>';
            }
            if (type.includes('flyer') || type.includes('presentation')) {
                return '<div class="w-8 h-8 rounded-md bg-white shadow flex items-center justify-center text-purple-600 font-bold text-sm">P</div>';
            }
            return '<div class="w-8 h-8 rounded-md bg-white shadow flex items-center justify-center text-emerald-600 font-bold text-sm">X</div>';
        }

        function getCategoryIcon(category) {
            const icons = {
                'Meeting Minutes': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a2 2 0 002 2h4a2 2 0 002-2V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"></path></svg>',
                'Report': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"></path></svg>',
                'MOU/MOA': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>',
                'Other': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm8 8v2h1v-2h-1zM6 7v1h1V7H6zm1 3v1h1v-1H7zm1 2H7v1h1v-1z" clip-rule="evenodd"></path></svg>'
            };
            return icons[category] || icons['Other'];
        }

        function getCategoryColor(category) {
            const colors = {
                'Meeting Minutes': 'bg-blue-100 text-blue-600',
                'Report': 'bg-green-100 text-green-600',
                'MOU/MOA': 'bg-purple-100 text-purple-600',
                'Other': 'bg-gray-100 text-gray-600'
            };
            return colors[category] || colors['Other'];
        }

        function getCategoryBadgeColor(category) {
            const colors = {
                'Meeting Minutes': 'bg-blue-100 text-blue-800',
                'Report': 'bg-green-100 text-green-800',
                'MOU/MOA': 'bg-purple-100 text-purple-800',
                'Other': 'bg-gray-100 text-gray-800'
            };
            return colors[category] || colors['Other'];
        }

        function getTypeBadgeColor(category) {
            const colors = {
                'Meeting Minutes': 'bg-blue-100 text-blue-800',
                'Report': 'bg-green-100 text-green-800',
                'MOU/MOA': 'bg-purple-100 text-purple-800',
                'Other': 'bg-gray-100 text-gray-800'
            };
            return colors[category] || colors['Other'];
        }

        function getTimeAgo(date) {
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            const diffInMinutes = Math.floor(diffInSeconds / 60);
            const diffInHours = Math.floor(diffInMinutes / 60);
            const diffInDays = Math.floor(diffInHours / 24);

            if (diffInSeconds < 60) return 'just now';
            if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
            if (diffInHours < 24) return `${diffInHours}h ago`;
            if (diffInDays < 7) return `${diffInDays}d ago`;
            return date.toLocaleDateString();
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function useTemplate(id) {
            const template = currentDocuments.find(t => t.id == id);
            if (template) {
                showNotification(`Opening template: ${template.document_name || template.name}`, 'info');
                
                if (template.filename || template.file_name) {
                    // Open the document editor with the template
                    openDocumentEditor(template);
                } else {
                    showNotification('Template file not found', 'error');
                }
            } else {
                showNotification('Template not found', 'error');
            }
        }

        function openDocumentEditor(template) {
            // Create a new document based on the template
            const templateData = {
                name: template.document_name || template.name,
                category: template.category,
                description: template.description,
                file_name: template.filename || template.file_name,
                content: null
            };
            
            // Store template data for the editor
            try {
                localStorage.setItem('lilac_template_data', JSON.stringify(templateData));
                localStorage.setItem('lilac_template_file_url', buildFileUrl(template, template.filename || template.file_name));
            } catch (e) {
                console.error('Error storing template data:', e);
            }
            
            // Open the document editor
            window.open('docs/document-editor.html', '_blank');
        }

        function viewTemplate(id) {
            const template = currentDocuments.find(t => t.id == id);
            if (template) {
                // Populate modal with template details
                document.getElementById('viewTemplateTitle').textContent = template.document_name || template.name || 'Untitled Template';
                document.getElementById('viewTemplateType').textContent = template.category || 'Template';
                document.getElementById('viewTemplateSize').textContent = formatFileSize(template.file_size || 0);
                document.getElementById('viewTemplateDateAdded').textContent = new Date(template.upload_date || template.created_at).toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric'
                });
                document.getElementById('viewTemplateDescription').textContent = template.description || 'No description provided.';
                
                // Handle file preview section
                const previewSection = document.getElementById('viewTemplatePreview');
                const fileName = template.filename || template.file_name;
                
                if (fileName) {
                    const fileExtension = getFileExtension(fileName);
                    const fileType = getFileType(fileExtension);
                    
                    // Generate preview based on file type
                    previewSection.innerHTML = generateFilePreview(template, fileName, fileExtension, fileType);
                    
                    // Trigger text preview loading if it's a text file
                    if (fileType === 'text') {
                        triggerTextPreview(template.id, fileName);
                    }
                } else {
                    previewSection.innerHTML = `
                        <div class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-lg font-medium">No file uploaded</p>
                            <p class="text-sm">This template doesn't have an associated file</p>
                        </div>
                    `;
                }
                
                // Show modal
                document.getElementById('viewTemplateModal').classList.remove('hidden');
            }
        }

        // File handling functions (enhanced from documents.php)
        function getFileExtension(fileName) {
            return fileName.split('.').pop().toLowerCase();
        }

        function getFileType(extension) {
            const imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            const documentTypes = ['pdf'];
            const textTypes = ['txt'];
            const unsupportedTypes = ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'zip', 'rar', '7z'];
            
            if (imageTypes.includes(extension)) return 'image';
            if (documentTypes.includes(extension)) return 'pdf';
            if (textTypes.includes(extension)) return 'text';
            if (unsupportedTypes.includes(extension)) return 'unsupported';
            return 'unknown';
        }

        function generateFilePreview(doc, fileName, extension, fileType) {
            const fileUrl = buildFileUrl(doc, fileName);
            
            switch (fileType) {
                case 'image':
                    return `
                        <div class="space-y-4">
                            <div class="flex items-center justify-start bg-gray-50 p-3 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900">${fileName}</p>
                                        <p class="text-sm text-gray-500">${formatFileSize(doc.file_size || 0)} • Image Preview</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="viewTemplateFile(${doc.id}, '${fileName}')" 
                                            class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                        View
                                    </button>
                                    <button onclick="downloadTemplateFile(${doc.id}, '${fileName}')" 
                                            class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-green-700 transition-colors">
                                        Download
                                    </button>
                                </div>
                            </div>
                            <div class="bg-gray-100 rounded-lg p-4">
                                <div class="text-center">
                                    <img src="${fileUrl}" alt="${fileName}" 
                                         class="max-w-full max-h-96 mx-auto rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                                         onclick="openImageLightbox('${fileUrl}', '${fileName}')"
                                         onerror="this.parentElement.innerHTML='<div class=\\'text-center py-8 text-red-50 border border-red-200 rounded-lg\\'><p class=\\'text-lg font-medium text-red-800\\'>Image preview unavailable</p><button onclick=\\'downloadTemplateFile(${doc.id}, &quot;${fileName}&quot;)\\'class=\\'mt-3 bg-green-600 text-white px-4 py-2 rounded-lg\\'>Download Image</button></div>'">
                                    <p class="text-sm text-gray-600 mt-2">Click image to view full size</p>
                                </div>
                            </div>
                        </div>
                    `;
                    
                case 'pdf':
                    return `
                        <div class="space-y-4">
                            <div class="flex items-center justify-start bg-gray-50 p-4 rounded-lg border">
                                <div class="flex items-center gap-3">
                                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-lg">${fileName}</p>
                                        <p class="text-sm text-gray-600">${formatFileSize(doc.file_size || 0)} • PDF Template</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="viewTemplateFile(${doc.id}, '${fileName}')" 
                                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                        View PDF
                                    </button>
                                    <button onclick="downloadTemplateFile(${doc.id}, '${fileName}')" 
                                            class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition-colors">
                                        Download
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                case 'text':
                    return `
                        <div class="space-y-4">
                            <div class="flex items-center justify-start bg-gray-50 p-3 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900">${fileName}</p>
                                        <p class="text-sm text-gray-500">${formatFileSize(doc.file_size || 0)} • Text Template</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="viewTemplateFile(${doc.id}, '${fileName}')" 
                                            class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-green-700 transition-colors">
                                        View
                                    </button>
                                    <button onclick="downloadTemplateFile(${doc.id}, '${fileName}')" 
                                            class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                        Download
                                    </button>
                                </div>
                            </div>
                            <div class="bg-gray-100 rounded-lg p-4">
                                <div id="text-preview-${doc.id}" class="bg-white border rounded p-4 max-h-64 overflow-y-auto">
                                    <div class="text-center py-4 text-gray-500">
                                        <div class="animate-spin inline-block w-6 h-6 border-[3px] border-current border-t-transparent text-blue-600 rounded-full"></div>
                                        <p class="mt-2">Loading template content...</p>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mt-2">Template content preview</p>
                            </div>
                        </div>
                    `;
                    
                case 'unsupported':
                    const iconColor = getUnsupportedFileColor(extension);
                    return `
                        <div class="space-y-4">
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.694-.833-3.464 0L3.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-amber-800">Preview not available</p>
                                        <p class="text-sm text-amber-700">This file type (${extension.toUpperCase()}) cannot be previewed inline</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-start bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 ${iconColor} rounded-lg">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-lg">${fileName}</p>
                                        <p class="text-gray-600">${formatFileSize(doc.file_size || 0)} • ${extension.toUpperCase()}</p>
                                    </div>
                                </div>
                                <button onclick="downloadTemplateFile(${doc.id}, '${fileName}')" 
                                        class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition-colors">
                                    Download Template
                                </button>
                            </div>
                        </div>
                    `;
                    
                default:
                    return `
                        <div class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.694-.833-3.464 0L3.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <p class="text-lg font-medium">Unknown file type</p>
                            <p class="text-sm">Unable to preview this template</p>
                            <button onclick="downloadTemplateFile(${doc.id}, '${fileName}')" 
                                    class="mt-3 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                Download Template
                            </button>
                        </div>
                    `;
            }
        }

        function getUnsupportedFileColor(extension) {
            const colorMap = {
                'docx': 'bg-blue-600', 'doc': 'bg-blue-600',
                'xlsx': 'bg-green-600', 'xls': 'bg-green-600',
                'pptx': 'bg-orange-600', 'ppt': 'bg-orange-600',
                'zip': 'bg-purple-600', 'rar': 'bg-purple-600', '7z': 'bg-purple-600'
            };
            return colorMap[extension] || 'bg-gray-600';
        }

        function openImageLightbox(imageUrl, fileName) {
            const lightboxDiv = document.createElement('div');
            lightboxDiv.id = 'image-lightbox';
            lightboxDiv.className = 'fixed inset-0 bg-black bg-opacity-90 z-[70] flex items-center justify-center p-4';
            lightboxDiv.onclick = closeImageLightbox;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'relative max-w-5xl max-h-full';
            contentDiv.onclick = function(e) { e.stopPropagation(); };
            
            const closeButton = document.createElement('button');
            closeButton.className = 'absolute -top-10 right-0 text-white hover:text-gray-300 text-xl font-bold p-2';
            closeButton.onclick = closeImageLightbox;
            closeButton.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            
            const imageElement = document.createElement('img');
            imageElement.src = imageUrl;
            imageElement.alt = fileName;
            imageElement.className = 'max-w-full max-h-full object-contain rounded-lg shadow-2xl';
            
            const captionDiv = document.createElement('div');
            captionDiv.className = 'absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-4 rounded-b-lg';
            const captionText = document.createElement('p');
            captionText.className = 'text-center font-medium';
            captionText.textContent = fileName;
            captionDiv.appendChild(captionText);
            
            contentDiv.appendChild(closeButton);
            contentDiv.appendChild(imageElement);
            contentDiv.appendChild(captionDiv);
            lightboxDiv.appendChild(contentDiv);
            
            document.body.appendChild(lightboxDiv);
            document.addEventListener('keydown', handleLightboxKeydown);
        }

        function handleLightboxKeydown(event) {
            if (event.key === 'Escape') {
                closeImageLightbox();
            }
        }

        function closeImageLightbox() {
            const lightbox = document.getElementById('image-lightbox');
            if (lightbox) {
                lightbox.remove();
                document.removeEventListener('keydown', handleLightboxKeydown);
            }
        }

        function triggerTextPreview(docId, fileName) {
            setTimeout(() => {
                loadTextPreview(docId, fileName);
            }, 100);
        }

        function loadTextPreview(docId, fileName) {
            const previewContainer = document.getElementById(`text-preview-${docId}`);
            if (!previewContainer) return;
            
            // Try to load the actual file content
            const template = currentDocuments.find(t => t.id == docId);
            if (template && (template.filename || template.file_name)) {
                const fileUrl = buildFileUrl(template, template.filename || template.file_name);
                
                fetch(fileUrl)
                    .then(response => {
                        if (response.ok) {
                            return response.text();
                        }
                        throw new Error('File not found');
                    })
                    .then(content => {
                        // Limit preview to first 1000 characters
                        const previewContent = content.length > 1000 ? 
                            content.substring(0, 1000) + '\n\n... [Content truncated for preview]' : 
                            content;
                        
                        previewContainer.innerHTML = `<pre class="whitespace-pre-wrap text-sm text-gray-800 font-mono leading-relaxed max-h-64 overflow-y-auto">${previewContent}</pre>`;
                    })
                    .catch(error => {
                        console.error('Error loading template content:', error);
                        // Fallback to sample content
                        showSampleTemplatePreview(previewContainer, fileName);
                    });
            } else {
                // Fallback to sample content
                showSampleTemplatePreview(previewContainer, fileName);
            }
        }

        function showSampleTemplatePreview(container, fileName) {
            const sampleText = `Template Preview: ${fileName}

[HEADER SECTION]
Organization: Central Philippine University
Department: _________________________
Date: _______________________________

[BODY SECTION]
This template provides a structured format for:

1. Meeting minutes
2. Report generation  
3. Document standardization
4. Professional formatting

[FOOTER SECTION]
Prepared by: _________________________
Reviewed by: _________________________
Approved by: _________________________

[Note: This is a preview. The actual template content may vary.]`;
            
            container.innerHTML = `<pre class="whitespace-pre-wrap text-sm text-gray-800 font-mono leading-relaxed">${sampleText}</pre>`;
        }

        // Document viewer functions
        function viewTemplateFile(id, fileName) {
            const template = currentDocuments.find(t => t.id == id);
            if (template) {
                showDocumentViewer(template);
            } else {
                showNotification('Template not found', 'error');
            }
        }

        function getFileExtension(filename) {
            return filename.split('.').pop().toLowerCase();
        }

        function showDocumentViewer(doc) {
            const title = doc.document_name || doc.title || 'Template Document';
            let filePath = doc.filename || doc.file_name || '';
            const ext = getFileExtension(filePath || '');

            if (filePath && !filePath.startsWith('uploads/') && !filePath.startsWith('/uploads/')) {
                filePath = `uploads/${filePath}`;
            }

            const overlay = document.getElementById('document-viewer-overlay');
            const titleEl = document.getElementById('document-viewer-title');
            const contentEl = document.getElementById('document-viewer-content');
            const downloadBtn = document.getElementById('document-viewer-download');
            const openBtn = document.getElementById('document-viewer-open');

            if (!overlay || !titleEl || !contentEl || !downloadBtn) return;

            titleEl.textContent = title;
            contentEl.innerHTML = '';

            if (ext === 'pdf') {
                const container = document.createElement('div');
                container.className = 'w-full h-full';
                contentEl.appendChild(container);
                try {
                    if (!window['pdfjsLib']) throw new Error('PDF.js not loaded');
                    pdfjsLib.getDocument(filePath).promise.then(pdf => {
                        const numPages = pdf.numPages;
                        const renderPage = (pageNum) => {
                            pdf.getPage(pageNum).then(page => {
                                const availableWidth = contentEl.clientWidth - 16; // account for padding
                                const viewport = page.getViewport({ scale: 1 });
                                const scale = Math.min(1.5, Math.max(0.6, availableWidth / viewport.width));
                                const scaledViewport = page.getViewport({ scale });
                                const canvas = document.createElement('canvas');
                                const ctx = canvas.getContext('2d');
                                canvas.height = scaledViewport.height;
                                canvas.width = scaledViewport.width;
                                canvas.className = 'mx-auto block shadow-lg mb-4';
                                container.appendChild(canvas);
                                page.render({ canvasContext: ctx, viewport: scaledViewport });
                            });
                        };
                        // Render first page
                        renderPage(1);
                        // Add page navigation if multiple pages
                        if (numPages > 1) {
                            const nav = document.createElement('div');
                            nav.className = 'flex justify-center items-center gap-2 mt-4';
                            const prevBtn = document.createElement('button');
                            prevBtn.textContent = '← Previous';
                            prevBtn.className = 'px-3 py-1 bg-gray-200 rounded text-sm';
                            const pageInfo = document.createElement('span');
                            pageInfo.textContent = `Page 1 of ${numPages}`;
                            pageInfo.className = 'px-3 py-1 text-sm';
                            const nextBtn = document.createElement('button');
                            nextBtn.textContent = 'Next →';
                            nextBtn.className = 'px-3 py-1 bg-gray-200 rounded text-sm';
                            let currentPage = 1;
                            prevBtn.onclick = () => {
                                if (currentPage > 1) {
                                    currentPage--;
                                    container.innerHTML = '';
                                    container.appendChild(nav);
                                    renderPage(currentPage);
                                    pageInfo.textContent = `Page ${currentPage} of ${numPages}`;
                                }
                            };
                            nextBtn.onclick = () => {
                                if (currentPage < numPages) {
                                    currentPage++;
                                    container.innerHTML = '';
                                    container.appendChild(nav);
                                    renderPage(currentPage);
                                    pageInfo.textContent = `Page ${currentPage} of ${numPages}`;
                                }
                            };
                            nav.appendChild(prevBtn);
                            nav.appendChild(pageInfo);
                            nav.appendChild(nextBtn);
                            container.appendChild(nav);
                        }
                    }).catch(error => {
                        console.error('PDF loading error:', error);
                        const fallback = document.createElement('div');
                        fallback.className = 'text-center text-gray-600 p-8';
                        fallback.innerHTML = `
                            <p class="mb-4">PDF preview failed to load</p>
                            <button onclick="window.open('${new URL(filePath, window.location.origin).href}', '_blank')" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                Open PDF in New Tab
                            </button>
                        `;
                        contentEl.innerHTML = '';
                        contentEl.appendChild(fallback);
                    });
                } catch (e) {
                    const fallback = document.createElement('iframe');
                    fallback.src = filePath;
                    fallback.className = 'w-full h-full rounded';
                    contentEl.appendChild(fallback);
                }
                
                openBtn.onclick = function() { window.open(new URL(filePath, window.location.origin).href, '_blank'); };
            } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)) {
                const imgUrl = new URL(filePath, window.location.origin).href;
                const img = document.createElement('img');
                img.src = imgUrl;
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100%';
                img.style.objectFit = 'contain';
                img.style.display = 'block';
                img.style.margin = '0 auto';
                contentEl.appendChild(img);
                
                openBtn.onclick = function() { window.open(imgUrl, '_blank'); };
            } else if (['doc','docx','ppt','pptx','xls','xlsx'].includes(ext)) {
                const isLocalhost = ['localhost','127.0.0.1','::1'].includes(location.hostname);
                if (isLocalhost) {
                    const info = document.createElement('div');
                    info.className = 'text-center text-gray-600';
                    info.textContent = 'Preview for Office files is not available on localhost. Please use Download to view the file.';
                    contentEl.appendChild(info);
                } else {
                    const absoluteUrl = new URL(filePath, window.location.origin).href;
                    const officeUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(absoluteUrl);
                    const iframe = document.createElement('iframe');
                    iframe.src = officeUrl;
                    iframe.style.width = '100%';
                    iframe.style.height = 'calc(100% - 0px)';
                    iframe.style.display = 'block';
                    contentEl.appendChild(iframe);
                }
                openBtn.onclick = function() { window.open(new URL(filePath, window.location.origin).href, '_blank'); };
            } else {
                contentEl.innerHTML = '<div class="text-center text-gray-600">Preview not supported for this file type. Please download to view.</div>';
                openBtn.onclick = function() { window.open(new URL(filePath, window.location.origin).href, '_blank'); };
            }

            downloadBtn.onclick = function() { downloadTemplateFile(doc.id, doc.filename || doc.file_name); };
            overlay.classList.remove('hidden');
        }

        function getApplicationBasePath() {
            const currentPath = window.location.pathname;
            const pathSegments = currentPath.split('/').filter(segment => segment !== '');
            
            if (pathSegments.length > 0 && pathSegments[0] !== 'index.php' && pathSegments[0] !== 'templates.php') {
                return '/' + pathSegments[0];
            }
            
            return '';
        }

        function buildFileUrl(doc, fileName) {
            if (doc.file_url && (doc.file_url.startsWith('http') || doc.file_url.startsWith('/'))) {
                return doc.file_url;
            }
            
            // Use the current origin (including port) to build the full URL
            const baseUrl = window.location.origin;
            return `${baseUrl}/uploads/${fileName}`;
        }

        function closeViewTemplateModal() {
            document.getElementById('viewTemplateModal').classList.add('hidden');
        }

        function downloadTemplateFile(id, fileName) {
            showNotification(`Downloading ${fileName}...`, 'info');
            // In a real implementation, this would trigger the actual file download
            // window.open(`api/templates.php?action=download&id=${id}`, '_blank');
        }

        function editTemplate(id) {
            const template = currentDocuments.find(t => t.id == id);
            if (template) {
                showNotification('Opening template for editing…', 'info');
                // Open the template in the document editor
                openDocumentEditor(template);
            }
        }

        // Delete modal functionality
        let templateToDelete = null;

        function showDeleteModal(id, templateName) {
            templateToDelete = id;
            document.getElementById('templateToDeleteName').textContent = `Template: "${templateName}"`;
            document.getElementById('deleteConfirmModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            templateToDelete = null;
            document.getElementById('deleteConfirmModal').classList.add('hidden');
        }

        function confirmDelete() {
            if (templateToDelete !== null) {
                deleteTemplate(templateToDelete);
                hideDeleteModal();
            }
        }

        function deleteTemplate(id) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadDocuments();
                    loadStats();
                    showNotification('Template deleted successfully', 'success');
                } else {
                    showNotification(data.message || 'Error deleting template', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
            });
        }

        function showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Slide in
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            
            // Slide out and remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</head>

<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-start relative transition-all duration-300 ease-in-out">
        <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        <div class="ml-16">
            <h1 id="templates-title" class="text-xl font-bold text-gray-800 cursor-pointer">Templates</h1>
        </div>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            </svg>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hamburger and desktop toggle buttons are now handled globally by LILACSidebar
    });
    
    // toggleSidebar function is now handled globally by LILACSidebar
    function toggleSidebar_DISABLED() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar) return;
        
        // Toggle hidden/visible by translating X
        sidebar.classList.toggle('-translate-x-full');
        
        // Toggle backdrop on mobile
        if (backdrop && window.innerWidth < 1024) {
            backdrop.classList.toggle('hidden');
        }
        
        // On mobile, adjust main content margin
        const mainContainer = document.getElementById('main-content');
        if (mainContainer) {
            // Only adjust margin on mobile (when sidebar is hidden by default)
            if (window.innerWidth < 1024) { // lg breakpoint
                mainContainer.classList.toggle('ml-0');
            }
        }
        
        // Adjust navbar left padding on desktop
        const nav = document.querySelector('nav.modern-nav');
        if (nav && window.innerWidth >= 1024) { // lg breakpoint
            nav.classList.toggle('pl-64');
        }
    }

        // Update date in top-right
        function updateCurrentDate() {
            if (el) {
                var now = new Date();
                el.textContent = now.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
            }
        }
        updateCurrentDate();
        setInterval(updateCurrentDate, 60000);
    </script>

    <!-- Main Content -->
    <div id="main-content" class="p-4 pt-3 min-h-screen bg-[#F8F8FF] transition-all duration-300 ease-in-out">
        <!-- Templates Display -->
        <div class="bg-white rounded-xl shadow-md p-4">
            <h2 class="text-xl font-bold mb-2"></h2>
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <div class="relative flex-1 min-w-[220px]">
                    <input id="template-search" type="search" placeholder="Search templates..." class="w-full border rounded-full px-4 py-2 pl-9 focus:outline-none focus:ring-2 focus:ring-blue-200" oninput="displayDocuments(currentDocuments)">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                </div>
                <select id="template-sort" class="border rounded-full px-3 py-2 text-sm" onchange="displayDocuments(currentDocuments)">
                    <option value="newest">Sort by: Newest</option>
                    <option value="alpha">Sort by: Name (A–Z)</option>
                    <option value="popular">Sort by: Most Popular</option>
                </select>
            </div>
            <div id="template-tabs" class="flex flex-wrap gap-2 mb-4"></div>
            <div id="templates-container" class="min-h-[120px]"></div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Footer -->
    <footer id="page-footer" class="bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-lg shadow-lg">
            <div class="flex justify-start items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Confirm Deletion</h2>
                <button onclick="hideDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-6">
                <p class="text-gray-700">Are you sure you want to delete this template? This action cannot be undone.</p>
                <p id="templateToDeleteName" class="font-semibold text-gray-700 mt-2"></p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="hideDeleteModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
                <button type="button" onclick="confirmDelete()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Delete Template</button>
            </div>
        </div>
    </div>

    <!-- View Template Modal -->
    <div id="viewTemplateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative p-4 bg-white w-full max-w-3xl m-auto flex-col flex rounded-lg shadow-xl max-h-[80vh] overflow-y-auto">
            <div class="flex justify-start items-center mb-6 sticky top-0 bg-white py-2">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                    </div>
                    Template Preview & Details
                </h2>
                <button onclick="closeViewTemplateModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Template Information Grid -->
            <div class="bg-gray-50 rounded-xl p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                            </svg>
                            Template Name
                        </h3>
                        <p id="viewTemplateTitle" class="text-lg font-semibold text-gray-900"></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            Category
                        </h3>
                        <p id="viewTemplateType" class="text-lg text-gray-700"></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            File Size
                        </h3>
                        <p id="viewTemplateSize" class="text-lg text-gray-700"></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            </svg>
                            Date Added
                        </h3>
                        <p id="viewTemplateDateAdded" class="text-lg text-gray-700"></p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                        Description
                    </h3>
                    <p id="viewTemplateDescription" class="text-gray-700 leading-relaxed bg-white p-4 rounded-lg border"></p>
                </div>
            </div>
            
            <!-- File Preview Section -->
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Template Preview
                </h3>
                <div id="viewTemplatePreview" class="bg-white border border-gray-200 rounded-xl">
                    <!-- File preview content will be dynamically inserted here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile navigation function
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('menu-overlay');
            
            if (sidebar && overlay) {
                const isHidden = sidebar.classList.contains('-translate-x-full');
                
                if (isHidden) {
                    // Show menu
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                } else {
                    // Hide menu
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                }
            }
        }

        // Setup mobile navigation when page loads
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

            // Reset filters and scroll to top when Browse All Templates button is clicked
            const browseTemplatesBtn = document.getElementById('browse-templates-btn');
            if (browseTemplatesBtn) {
                browseTemplatesBtn.addEventListener('click', function() {
                    document.getElementById('template-search').value = ''; // Clear search
                    document.getElementById('template-sort').value = 'newest'; // Reset sort
                    document.getElementById('template-tabs').innerHTML = ''; // Clear tabs
                    activeTab = 'Recommended'; // Reset active tab
                    renderTemplateTabs(); // Re-render tabs
                    displayDocuments(currentDocuments); // Display all documents
                    window.scrollTo({ top: 0, behavior: 'smooth' }); // Scroll to top
                });
            }
        });
    </script>

    <!-- Document Viewer Modal -->
    <div id="document-viewer-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden" onclick="this.classList.add('hidden')">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[80vh] flex flex-col" onclick="event.stopPropagation()">
                <div class="flex items-center justify-start px-4 py-3 border-b">
                    <h3 id="document-viewer-title" class="text-lg font-semibold text-gray-900"></h3>
                    <div class="flex items-center gap-2">
                        <button id="document-viewer-open" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Open in New Tab</button>
                    </div>
                </div>
                <div class="flex-1 bg-gray-50 p-2 overflow-y-auto overflow-x-hidden min-h-0">
                    <div id="document-viewer-content" class="w-full h-full overflow-y-auto overflow-x-hidden"></div>
                </div>
                <div class="flex items-center justify-end gap-2 px-4 py-3 border-t">
                    <button id="document-viewer-download" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Download</button>
                    <button onclick="closeModal('document-viewer-overlay')" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
