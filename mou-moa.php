<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC MOUs & MOAs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js"></script>
    <script>
        // Initialize MOUs functionality
        let currentDocuments = [];
        let expiringDocuments = [];
        let showExpiringOnly = false;
        const CATEGORY = 'MOUs & MOAs';

        document.addEventListener('DOMContentLoaded', function() {
            loadDocuments();
            loadStats();
            initializeEventListeners();
            setupFileUploadDetection();
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
            const dateElement = document.getElementById('current-date');
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
            // Form submission
            const form = document.getElementById('mou-form');
            if (form) {
                form.addEventListener('submit', handleFormSubmit);
            }

            // Search and filter functionality
            const searchInput = document.getElementById('search-mous');
            if (searchInput) {
                searchInput.addEventListener('input', filterDocuments);
            }

            const statusFilter = document.getElementById('status-filter');
            if (statusFilter) {
                statusFilter.addEventListener('change', filterDocuments);
            }
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'add');
            formData.append('category', CATEGORY);
            
            // Add partner_name as document_name for consistency
            const partnerName = formData.get('partner_name');
            formData.set('document_name', partnerName);
            
            // Add required file_name parameter
            formData.append('file_name', `mou_${Date.now()}.txt`);
            
            fetch('api/documents.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset form
                    e.target.reset();
                    
                    // Refresh display
                    loadDocuments();
                    loadStats();
                    showNotification('MOU/MOA created successfully!', 'success');
                } else {
                    showNotification(data.message || 'Error creating MOU/MOA', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
            });
        }

        function loadDocuments() {
            // Load documents 
            fetch(`api/documents.php?action=get_by_category&category=${encodeURIComponent(CATEGORY)}`)
            .then(response => response.json())
            .then(documentsData => {
                if (documentsData.success) {
                    // Transform document data to MOU format
                    currentDocuments = documentsData.documents.map(doc => {
                        return {
                            id: doc.id,
                            partner_name: doc.document_name || 'Unknown Partner',
                            type: extractMOUType(doc.document_name) || 'MOU',
                            status: doc.status || 'Active',
                            date_signed: doc.upload_date ? doc.upload_date.split(' ')[0] : new Date().toISOString().split('T')[0],
                            end_date: null, // No automatic date extraction
                            description: doc.description || '',
                            file_name: doc.filename || null,
                            file_size: doc.file_size || 0,
                            created_at: doc.upload_date || new Date().toISOString()
                        };
                    });
                    
                    displayDocuments(currentDocuments);
                    loadStats();
                } else {
                    console.error('Error loading MOUs:', documentsData.message);
                    showNotification('Error loading MOUs: ' + documentsData.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error loading documents:', error);
                showNotification('Network error. Please try again.', 'error');
            });
        }

        function extractMOUType(documentName) {
            if (!documentName) return 'MOU';
            
            const name = documentName.toUpperCase();
            if (name.includes('MOA')) return 'MOA';
            if (name.includes('ACADEMIC')) return 'MOU-Academic';
            if (name.includes('INTERNATIONAL')) return 'International-MOU';
            if (name.includes('PARTNERSHIP')) return 'Industry-Partnership';
            if (name.includes('RESEARCH')) return 'Research-Collaboration';
            if (name.includes('EXCHANGE')) return 'Student-Exchange';
            return 'MOU';
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
            const totalMOUsElement = document.getElementById('total-mous');
            const activeMOUsElement = document.getElementById('active-mous');
            const expiringMOUsElement = document.getElementById('expiring-mous');
            
            if (totalMOUsElement) {
                totalMOUsElement.textContent = stats.total;
            }
            if (activeMOUsElement) {
                // Count active MOUs from current documents
                const activeCount = currentDocuments.filter(doc => 
                    doc.description && doc.description.toLowerCase().includes('status: active')
                ).length;
                activeMOUsElement.textContent = activeCount;
            }
            if (expiringMOUsElement) {
                // Count expiring MOUs from current documents
                const expiringCount = currentDocuments.filter(doc => {
                    if (!doc.description) return false;
                    const desc = doc.description.toLowerCase();
                    return desc.includes('expiring') || desc.includes('expiry');
                }).length;
                expiringMOUsElement.textContent = expiringCount;
            }
        }

        function displayDocuments(documents) {
            const container = document.getElementById('mous-container');
            
            // --- TABLE LAYOUT ONLY ---
            let tableHTML = `<div class="overflow-x-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        Partner Organization
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Type
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Status
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Date Signed
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        End Date
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                        </svg>
                                        Actions
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">`;
            
            if (documents.length === 0) {
                tableHTML += `<tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No MOUs & MOAs yet</h3>
                            <p class="text-gray-500 mb-4">Create your first MOU/MOA to get started</p>
                            <button onclick="document.getElementById('mou-organization').focus()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                Create MOU/MOA
                            </button>
                        </div>
                    </td>
                </tr>`;
            } else {
                tableHTML += documents.map(doc => {
                    const signedDate = new Date(doc.date_signed);
                    const endDate = doc.end_date ? new Date(doc.end_date) : null;
                    const formattedSignedDate = signedDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                    const formattedEndDate = endDate ? endDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    }) : 'No end date';
                    
                    return `<tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${doc.partner_name || doc.document_name || 'Untitled MOU/MOA'}</div>
                                    <div class="text-sm text-gray-500">MOU ID: ${doc.id}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${doc.type || 'MOU'}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadgeColor(doc.status)}">${doc.status}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">${formattedSignedDate}</div>
                            <div class="text-sm text-gray-500">${getTimeAgo(signedDate)}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${formattedEndDate}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <button onclick="viewMOU(${doc.id})" class="text-blue-600 hover:text-blue-900 font-medium flex items-center" title="View Details">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </button>
                                <button onclick="editMOU(${doc.id})" class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center" title="Edit">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </button>
                                <button onclick="showDeleteModal(${doc.id}, '${(doc.partner_name || doc.document_name || 'Untitled MOU/MOA').replace(/'/g, "\\'")}')" class="text-red-600 hover:text-red-900 font-medium flex items-center" title="Delete">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            }
            tableHTML += `</tbody></table></div></div>`;

            container.innerHTML = tableHTML;
        }

        function filterDocuments() {
            const searchTerm = document.getElementById('search-mous').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;

            let filtered = currentDocuments.filter(doc => {
                const matchesSearch = doc.partner_name.toLowerCase().includes(searchTerm) ||
                                    (doc.description && doc.description.toLowerCase().includes(searchTerm));
                const matchesStatus = statusFilter === 'all' || doc.status.toLowerCase() === statusFilter.toLowerCase();
                return matchesSearch && matchesStatus;
            });

            displayDocuments(filtered);
        }

        function getStatusIcon(status) {
            const icons = {
                'Active': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                'Expired': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                'Pending': '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>'
            };
            return icons[status] || icons['Pending'];
        }

        function getStatusColor(status) {
            const colors = {
                'Active': 'bg-green-100 text-green-600',
                'Expired': 'bg-red-100 text-red-600',
                'Pending': 'bg-yellow-100 text-yellow-600'
            };
            return colors[status] || colors['Pending'];
        }

        function getStatusBadgeColor(status) {
            const colors = {
                'Active': 'bg-green-100 text-green-800',
                'Expired': 'bg-red-100 text-red-800',
                'Pending': 'bg-yellow-100 text-yellow-800'
            };
            return colors[status] || colors['Pending'];
        }

        function getTimeAgo(date) {
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'just now';
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
            if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)}d ago`;
            if (diffInSeconds < 31536000) return `${Math.floor(diffInSeconds / 2592000)}mo ago`;
            return `${Math.floor(diffInSeconds / 31536000)}y ago`;
        }

        function viewMOU(id) {
            const mou = currentDocuments.find(m => m.id == id);
            if (mou) {
                // Populate modal with MOU details
                document.getElementById('viewMOUTitle').textContent = `${mou.type} with ${mou.partner_name}`;
                document.getElementById('viewMOUPartner').textContent = mou.partner_name;
                document.getElementById('viewMOUType').textContent = mou.type;
                document.getElementById('viewMOUStatus').textContent = mou.status;
                document.getElementById('viewMOUDateSigned').textContent = new Date(mou.date_signed).toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric'
                });
                document.getElementById('viewMOUEndDate').textContent = mou.end_date ? 
                    new Date(mou.end_date).toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric'
                    }) : 'No end date specified';
                document.getElementById('viewMOUDescription').textContent = mou.description || 'No description provided.';
                
                // Handle document file
                const documentSection = document.getElementById('viewMOUDocument');
                if (mou.file_name) {
                    documentSection.innerHTML = `
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">${mou.file_name}</p>
                                <p class="text-sm text-gray-500">MOU/MOA document</p>
                            </div>
                            <button onclick="downloadMOUFile(${mou.id}, '${mou.file_name}')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                                Download
                            </button>
                        </div>
                    `;
                } else {
                    documentSection.innerHTML = `
                        <div class="text-center py-6 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <p>No document file uploaded</p>
                        </div>
                    `;
                }
                
                // Show modal
                document.getElementById('viewMOUModal').classList.remove('hidden');
            }
        }

        function closeViewMOUModal() {
            document.getElementById('viewMOUModal').classList.add('hidden');
        }

        function downloadMOUFile(id, fileName) {
            showNotification(`Downloading ${fileName}...`, 'info');
            // In a real implementation, this would trigger the actual file download
            // window.open(`api/mous.php?action=download&id=${id}`, '_blank');
        }

        function editMOU(id) {
            const mou = currentDocuments.find(m => m.id == id);
            if (mou) {
                // Pre-fill form with existing data
                document.getElementById('mou-organization').value = mou.partner_name;
                document.getElementById('mou-type').value = mou.type;
                document.getElementById('mou-status').value = mou.status;
                document.getElementById('signed-date').value = mou.date_signed;
                document.getElementById('expiry-date').value = mou.end_date || '';
                document.getElementById('mou-description').value = mou.description || '';
                
                // Scroll to form
                document.getElementById('mou-form').scrollIntoView({ behavior: 'smooth' });
                
                // Delete the MOU (will be replaced when form is submitted)
                deleteMOU(id);
                
                showNotification('MOU loaded for editing', 'info');
            }
        }

        // Delete modal functionality
        let mouToDelete = null;

        function showDeleteModal(id, organization) {
            mouToDelete = id;
            document.getElementById('mouToDeleteName').textContent = `MOU with "${organization}"`;
            document.getElementById('deleteConfirmModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            mouToDelete = null;
            document.getElementById('deleteConfirmModal').classList.add('hidden');
        }

        function confirmDelete() {
            if (mouToDelete !== null) {
                deleteMOU(mouToDelete);
                hideDeleteModal();
            }
        }

        function deleteMOU(id) {
            if (confirm('Are you sure you want to delete this MOU/MOA? This action cannot be undone.')) {
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
                        showNotification('MOU deleted successfully', 'success');
                    } else {
                        showNotification(data.message || 'Error deleting MOU', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error. Please try again.', 'error');
                });
            }
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

        function setupFileUploadDetection() {
            const fileInput = document.getElementById('mou-file');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        analyzeMOUFile(file);
                    }
                });
            }
        }

        function analyzeMOUFile(file) {
            const filename = file.name;
            const organizationInput = document.getElementById('mou-organization');
            const typeSelect = document.getElementById('mou-type');
            const statusSelect = document.getElementById('mou-status');
            const descriptionTextarea = document.getElementById('mou-description');
            const signedDateInput = document.getElementById('signed-date');
            const expiryDateInput = document.getElementById('expiry-date');
            
            // Auto-detect organization from filename
            if (!organizationInput.value.trim()) {
                const detectedOrg = extractOrganizationFromFilename(filename);
                if (detectedOrg) {
                    organizationInput.value = detectedOrg;
                    showNotification(`Auto-detected organization: ${detectedOrg}`, 'info');
                }
            }
            
            // Auto-detect type from filename (only MOU or MOA)
            if (!typeSelect.value) {
                const detectedType = extractMOUTypeFromFilename(filename);
                if (detectedType) {
                    typeSelect.value = detectedType;
                    showNotification(`Auto-detected type: ${detectedType}`, 'info');
                }
            }
            
            // Set default status if not set
            if (!statusSelect.value) {
                statusSelect.value = 'Active';
            }
            
            // Auto-detect dates from filename
            const dates = extractDatesFromFilename(filename);
            if (dates.startDate && !signedDateInput.value) {
                signedDateInput.value = dates.startDate;
                showNotification(`Auto-detected start date: ${dates.startDate}`, 'info');
            }
            if (dates.endDate && !expiryDateInput.value) {
                expiryDateInput.value = dates.endDate;
                showNotification(`Auto-detected end date: ${dates.endDate}`, 'info');
            }
            
            // Add file analysis to description
            if (!descriptionTextarea.value.trim()) {
                descriptionTextarea.value = `MOU/MOA document with ${extractOrganizationFromFilename(filename) || 'partner organization'}. File: ${filename}`;
            }
            
            // Auto-set signed date to today if not set and no date detected
            if (!signedDateInput.value) {
                signedDateInput.value = new Date().toISOString().split('T')[0];
            }
        }

        function extractOrganizationFromFilename(filename) {
            // Remove file extension
            let name = filename.replace(/\.[^/.]+$/, "");
            
            // Common patterns for organization names in MOU files
            const patterns = [
                /MOU[_\-\s]+(.*?)(?:[_\-\s]+\d{4}|[_\-\s]+CPU|$)/i,
                /MOA[_\-\s]+(.*?)(?:[_\-\s]+\d{4}|[_\-\s]+CPU|$)/i,
                /(.*?)[_\-\s]+MOU/i,
                /(.*?)[_\-\s]+MOA/i,
                /^([^_\-\d]+)/i
            ];
            
            for (const pattern of patterns) {
                const match = name.match(pattern);
                if (match && match[1]) {
                    let org = match[1].trim();
                    // Clean up common separators and artifacts
                    org = org.replace(/[_\-]+/g, ' ');
                    org = org.replace(/\s+/g, ' ');
                    org = org.trim();
                    
                    // Skip if it's too short or contains only numbers
                    if (org.length > 2 && !/^\d+$/.test(org)) {
                        // Capitalize properly
                        return org.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
                    }
                }
            }
            
            return null;
        }

        function extractMOUTypeFromFilename(filename) {
            const name = filename.toUpperCase();
            
            if (name.includes('MOA')) return 'MOA';
            return 'MOU';
        }

        function extractDatesFromFilename(filename) {
            const name = filename.toUpperCase();
            const dates = { startDate: null, endDate: null };
            
            // Common date patterns in filenames
            const datePatterns = [
                // YYYY-MM-DD or YYYY/MM/DD
                /(\d{4})[-/](\d{1,2})[-/](\d{1,2})/g,
                // DD-MM-YYYY or DD/MM/YYYY
                /(\d{1,2})[-/](\d{1,2})[-/](\d{4})/g,
                // MM-DD-YYYY or MM/DD/YYYY
                /(\d{1,2})[-/](\d{1,2})[-/](\d{4})/g,
                // YYYYMMDD
                /(\d{4})(\d{2})(\d{2})/g
            ];
            
            const foundDates = [];
            
            datePatterns.forEach(pattern => {
                let match;
                while ((match = pattern.exec(name)) !== null) {
                    let year, month, day;
                    
                    if (match[1].length === 4) {
                        // YYYY-MM-DD or YYYYMMDD format
                        year = parseInt(match[1]);
                        month = parseInt(match[2]);
                        day = parseInt(match[3]);
                    } else {
                        // DD-MM-YYYY or MM-DD-YYYY format
                        if (parseInt(match[3]) > 31) {
                            // DD-MM-YYYY
                            day = parseInt(match[1]);
                            month = parseInt(match[2]);
                            year = parseInt(match[3]);
                        } else {
                            // MM-DD-YYYY
                            month = parseInt(match[1]);
                            day = parseInt(match[2]);
                            year = parseInt(match[3]);
                        }
                    }
                    
                    // Validate date
                    const date = new Date(year, month - 1, day);
                    if (date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day) {
                        const dateString = date.toISOString().split('T')[0];
                        foundDates.push(dateString);
                    }
                }
            });
            
            // Sort dates chronologically
            foundDates.sort();
            
            if (foundDates.length >= 1) {
                dates.startDate = foundDates[0];
            }
            if (foundDates.length >= 2) {
                dates.endDate = foundDates[1];
            }
            
            return dates;
        }
    </script>
</head>

<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-[60] modern-nav p-4 h-16 flex items-center justify-between pl-64 relative transition-all duration-300 ease-in-out">
        <button id="hamburger-toggle" class="btn btn-secondary btn-sm absolute top-4 left-4 z-[70]" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        <div class="absolute left-1/2 transform -translate-x-1/2">
            <h1 class="text-xl font-bold text-gray-800">LILAC MOUs & MOAs</h1>
        </div>
        <div class="absolute right-4 top-4 z-[90] text-sm flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span id="current-date"></span>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var hamburger = document.getElementById('hamburger-toggle');
        if (hamburger) {
            hamburger.addEventListener('click', function() {
                try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (e) {}
            });
        }
        var desktopToggle = document.getElementById('desktop-menu-toggle');
        if (desktopToggle) {
            desktopToggle.addEventListener('click', function() {
                try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (e) {}
            });
        }

        // Update date in top-right
        function updateCurrentDate() {
            var el = document.getElementById('current-date');
            if (el) {
                var now = new Date();
                el.textContent = now.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
            }
        }
        updateCurrentDate();
        setInterval(updateCurrentDate, 60000);
    });
    </script>

    <!-- Main Content -->
    <div id="main-content" class="ml-64 p-6 pt-20 min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 transition-all duration-300 ease-in-out">
        <!-- Header Section -->
        <div class="mb-6">
            <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 text-white rounded-2xl p-4 shadow-xl">
                <div class="absolute inset-0 bg-black opacity-10"></div>
                <div class="absolute -top-2 -right-2 w-16 h-16 bg-white opacity-10 rounded-full"></div>
                <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-white opacity-5 rounded-full"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-black mb-2 bg-gradient-to-r from-white to-purple-100 bg-clip-text text-transparent">
                                Partnership Center
                            </h1>
                            <p class="text-purple-100 text-base font-medium">MOUs • MOAs • Partnerships</p>
                        </div>
                        <div class="hidden md:block">
                            <div class="w-16 h-16 bg-gradient-to-br from-white to-purple-200 rounded-full opacity-20 animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600">Total MOUs</p>
                <p id="total-mous" class="text-2xl font-bold text-gray-900">0</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600">Active MOUs</p>
                <p id="active-mous" class="text-2xl font-bold text-gray-900">0</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600">Expiring Soon</p>
                <p id="expiring-mous" class="text-2xl font-bold text-gray-900">0</p>
            </div>
        </div>

        <!-- Expiration Alerts Section -->
        <div id="expiration-alerts"></div>

        <!-- Enhanced Create MOU Section -->
        <div class="mb-8">
            <div class="group relative">
                <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-400 to-teal-600 rounded-3xl blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                <div class="relative bg-white bg-opacity-80 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-white border-opacity-30">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-3xl font-black text-gray-800">Create New MOU/MOA</h2>
                            <p class="text-gray-600 font-medium mt-2">Establish new partnership agreements</p>
                        </div>
                        <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                    </div>
            <form id="mou-form" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="mou-organization" class="block text-sm font-bold text-gray-700 mb-3">Organization *</label>
                        <input type="text" id="mou-organization" name="mou-organization" required
                               class="w-full px-6 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-emerald-500 focus:ring-0 text-gray-900 font-medium placeholder-gray-500 transition-all duration-300"
                               placeholder="Enter organization name">
                    </div>
                    <div>
                        <label for="mou-type" class="block text-sm font-bold text-gray-700 mb-3">Type *</label>
                        <select id="mou-type" name="mou-type" required
                                class="w-full px-6 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-emerald-500 focus:ring-0 text-gray-900 font-medium transition-all duration-300">
                            <option value="">Select Type</option>
                            <option value="MOU">MOU (Memorandum of Understanding)</option>
                            <option value="MOA">MOA (Memorandum of Agreement)</option>
                        </select>
                    </div>
                    <div>
                        <label for="mou-status" class="block text-sm font-bold text-gray-700 mb-3">Status *</label>
                        <select id="mou-status" name="mou-status" required
                                class="w-full px-6 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-emerald-500 focus:ring-0 text-gray-900 font-medium transition-all duration-300">
                            <option value="Active">Active</option>
                            <option value="Expired">Expired</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                    <div>
                        <label for="signed-date" class="block text-sm font-bold text-gray-700 mb-3">Date Signed *</label>
                        <input type="date" id="signed-date" name="signed-date" required
                               class="w-full px-6 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-emerald-500 focus:ring-0 text-gray-900 font-medium transition-all duration-300">
                    </div>
                    <div>
                        <label for="expiry-date" class="block text-sm font-bold text-gray-700 mb-3">End Date</label>
                        <input type="date" id="expiry-date" name="expiry-date"
                               class="w-full px-6 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-emerald-500 focus:ring-0 text-gray-900 font-medium transition-all duration-300">
                        <p class="mt-2 text-sm text-gray-600 font-medium">Required for active MOUs</p>
                    </div>
                </div>
                <div>
                    <label for="mou-description" class="block text-sm font-bold text-gray-700 mb-3">Description</label>
                    <textarea id="mou-description" name="mou-description" rows="4"
                              class="w-full px-6 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-emerald-500 focus:ring-0 text-gray-900 font-medium placeholder-gray-500 transition-all duration-300 resize-none"
                              placeholder="Enter MOU/MOA description and details"></textarea>
                </div>
                <div>
                    <label for="mou-file" class="block text-sm font-bold text-gray-700 mb-3">Upload Document</label>
                    <input type="file" id="mou-file" name="mou-file"
                           accept=".pdf,.doc,.docx"
                           class="w-full px-6 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-emerald-500 focus:ring-0 transition-all duration-300 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-gradient-to-r file:from-emerald-500 file:to-teal-600 file:text-white hover:file:from-emerald-600 hover:file:to-teal-700">
                    <p class="mt-3 text-sm text-gray-600 font-medium">Supported formats: PDF, DOC, DOCX (Max 10MB)</p>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 text-white px-10 py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300 font-bold text-lg">
                        <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <div class="relative flex items-center space-x-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>Create MOU/MOA</span>
                        </div>
                    </button>
                </div>
            </form>
                </div>
            </div>
        </div>

        <!-- Enhanced Search and Filter Section -->
        <div class="mb-8">
            <div class="group relative">
                <div class="absolute -inset-0.5 bg-gradient-to-r from-cyan-400 via-blue-500 to-indigo-600 rounded-3xl blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
                <div class="relative bg-white bg-opacity-90 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-white border-opacity-40">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-3xl font-black text-gray-800 bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">
                                Search & Filter MOUs
                            </h2>
                            <p class="text-gray-600 font-medium mt-2">Find and organize your partnership agreements</p>
                        </div>
                        <div class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="mb-6">
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                                <svg class="h-6 w-6 text-cyan-500 group-focus-within:text-cyan-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            <input type="text" id="search-mous" placeholder="Search MOUs by organization, type, description, or any keyword..."
                                   class="w-full pl-16 pr-12 py-5 bg-gradient-to-r from-gray-50 to-blue-50 border-2 border-gray-200 rounded-2xl focus:border-cyan-500 focus:bg-white focus:ring-0 text-gray-900 font-medium placeholder-gray-500 transition-all duration-300 shadow-inner">
                            <div class="absolute inset-y-0 right-0 pr-6 flex items-center pointer-events-none">
                                <div class="text-xs text-gray-400 font-semibold bg-gray-200 px-2 py-1 rounded-lg">
                                    Ctrl+K
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Filter Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Status</label>
                            <div class="relative">
                                <select id="status-filter" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-0 text-gray-900 font-medium transition-all duration-300 appearance-none cursor-pointer">
                                <option value="all">All Statuses</option>
                                    <option value="Active">🟢 Active</option>
                                    <option value="Expired">🔴 Expired</option>
                                    <option value="Pending">🟡 Pending</option>
                            </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Agreement Type</label>
                            <div class="relative">
                                <select id="type-filter" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-0 text-gray-900 font-medium transition-all duration-300 appearance-none cursor-pointer">
                                    <option value="all">All Types</option>
                                    <optgroup label="📚 Academic">
                                        <option value="MOU-Academic">Academic Partnership</option>
                                        <option value="Student-Exchange">Student Exchange</option>
                                        <option value="Research-Collaboration">Research</option>
                                    </optgroup>
                                    <optgroup label="🌍 International">
                                        <option value="International-MOU">International MOU</option>
                                        <option value="Study-Abroad">Study Abroad</option>
                                    </optgroup>
                                    <optgroup label="🏭 Industry">
                                        <option value="Industry-Partnership">Industry Partnership</option>
                                        <option value="Internship-Agreement">Internship</option>
                                    </optgroup>
                                    <optgroup label="📝 General">
                                        <option value="MOU">General MOU</option>
                                        <option value="MOA">General MOA</option>
                                    </optgroup>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Date Range Filter -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Date Range</label>
                            <div class="relative">
                                <select id="date-filter" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-0 text-gray-900 font-medium transition-all duration-300 appearance-none cursor-pointer">
                                    <option value="all">All Dates</option>
                                    <option value="this-year">📅 This Year</option>
                                    <option value="last-year">📅 Last Year</option>
                                    <option value="expiring-soon">⚠️ Expiring Soon</option>
                                    <option value="expired">❌ Expired</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Sort Options -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Sort By</label>
                            <div class="relative">
                                <select id="sort-filter" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-0 text-gray-900 font-medium transition-all duration-300 appearance-none cursor-pointer">
                                    <option value="date-desc">📅 Newest First</option>
                                    <option value="date-asc">📅 Oldest First</option>
                                    <option value="name-asc">🔤 A to Z</option>
                                    <option value="name-desc">🔤 Z to A</option>
                                    <option value="expiry-asc">⏰ Expiring Soon</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 justify-between items-center">
                        <div class="flex flex-wrap gap-3">
                            <button id="clear-filters" onclick="clearAllFilters()" 
                                    class="group flex items-center space-x-2 px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105">
                                <svg class="w-4 h-4 group-hover:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Clear Filters</span>
                            </button>
                            
                            <button id="export-results" onclick="exportResults()" 
                                    class="group flex items-center space-x-2 px-5 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                                <svg class="w-4 h-4 group-hover:translate-y-[-2px] transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Export Results</span>
                            </button>
                        </div>

                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-cyan-500 rounded-full animate-pulse"></div>
                                <span class="font-semibold">Results: <span id="results-count">0</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tag filter removed for now -->

        <!-- Enhanced MOUs Grid -->
        <div class="group relative">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-400 to-cyan-600 rounded-3xl blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
            <div class="relative bg-white bg-opacity-80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white border-opacity-30">
                <div class="p-8 border-b border-gray-200 border-opacity-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-3xl font-black text-gray-800">Your MOUs & MOAs</h2>
                            <p class="text-gray-600 font-medium mt-2">Manage your partnership agreements</p>
                        </div>
                        <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div id="mous-container" class="grid grid-cols-1 gap-4">
                        <!-- MOUs will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-xl shadow-xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Confirm Deletion</h2>
                <button onclick="hideDeleteModal()" class="text-gray-400 hover:text-gray-600 p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-6">
                <p class="text-gray-700 mb-3">Are you sure you want to delete this MOU/MOA? This action cannot be undone.</p>
                <p class="font-medium text-gray-900 bg-gray-50 p-3 rounded-lg" id="mouToDeleteName"></p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="hideDeleteModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="confirmDelete()"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Delete MOU/MOA
                </button>
            </div>
        </div>
    </div>

    <!-- View MOU Modal -->
    <div id="viewMOUModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="relative p-8 bg-white w-full max-w-2xl m-auto flex-col flex rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    MOU/MOA Details
                </h2>
                <button onclick="closeViewMOUModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Partner Organization</h3>
                    <p id="viewMOUPartner" class="text-lg font-semibold text-gray-900"></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Type</h3>
                    <p id="viewMOUType" class="text-lg text-gray-700"></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Status</h3>
                    <p id="viewMOUStatus" class="text-lg text-gray-700"></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Date Signed</h3>
                    <p id="viewMOUDateSigned" class="text-lg text-gray-700"></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">End Date</h3>
                    <p id="viewMOUEndDate" class="text-lg text-gray-700"></p>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Description</h3>
                <p id="viewMOUDescription" class="text-gray-700 leading-relaxed"></p>
            </div>
            
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Document</h3>
                <div id="viewMOUDocument">
                    <!-- Document content will be dynamically inserted here -->
                </div>
            </div>
            
            <div class="flex justify-end mt-6">
                <button onclick="closeViewMOUModal()" class="px-6 py-2 text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 rounded-lg">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Footer -->
    <footer class="ml-0 md:ml-64 bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Central Philippine University | LILAC System</p>
    </footer>

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
        });

        // Enhanced Search and Filter Functions
        function clearAllFilters() {
            document.getElementById('search-mous').value = '';
            document.getElementById('status-filter').value = 'all';
            document.getElementById('type-filter').value = 'all';
            document.getElementById('date-filter').value = 'all';
            document.getElementById('sort-filter').value = 'date-desc';
            
            // Trigger search to refresh results
            searchMOUs();
            
            showNotification('All filters cleared', 'info');
        }

        function exportResults() {
            // Get current filtered results
            const container = document.getElementById('mous-container');
            const mouCards = container.querySelectorAll('.mou-card:not([style*="display: none"])');
            
            if (mouCards.length === 0) {
                showNotification('No MOUs to export', 'warning');
                return;
            }

            // Create CSV content
            let csvContent = "Organization,Type,Status,Date Signed,End Date,Description\n";
            
            mouCards.forEach(card => {
                const org = card.querySelector('.mou-organization')?.textContent || '';
                const type = card.querySelector('.mou-type')?.textContent || '';
                const status = card.querySelector('.mou-status')?.textContent || '';
                const signed = card.querySelector('.signed-date')?.textContent || '';
                const end = card.querySelector('.end-date')?.textContent || '';
                const desc = card.querySelector('.mou-description')?.textContent || '';
                
                // Escape quotes and commas for CSV
                const escape = (str) => `"${str.replace(/"/g, '""')}"`;
                
                csvContent += `${escape(org)},${escape(type)},${escape(status)},${escape(signed)},${escape(end)},${escape(desc)}\n`;
            });

            // Download CSV file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `MOUs_Export_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showNotification(`Exported ${mouCards.length} MOUs`, 'success');
        }

        function updateResultsCount() {
            const container = document.getElementById('mous-container');
            const visibleCards = container.querySelectorAll('.mou-card:not([style*="display: none"])');
            const count = visibleCards.length;
            
            const resultsCountElement = document.getElementById('results-count');
            if (resultsCountElement) {
                resultsCountElement.textContent = count;
                resultsCountElement.parentElement.classList.toggle('animate-pulse', count === 0);
            }
        }

        function searchMOUs() {
            const searchTerm = document.getElementById('search-mous').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const typeFilter = document.getElementById('type-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            const sortFilter = document.getElementById('sort-filter').value;
            
            const container = document.getElementById('mous-container');
            const mouCards = Array.from(container.querySelectorAll('.mou-card'));
            
            // Filter cards
            mouCards.forEach(card => {
                let shouldShow = true;
                
                // Text search
                if (searchTerm) {
                    const cardText = card.textContent.toLowerCase();
                    shouldShow = shouldShow && cardText.includes(searchTerm);
                }
                
                // Status filter
                if (statusFilter !== 'all') {
                    const status = card.querySelector('.mou-status')?.textContent || '';
                    shouldShow = shouldShow && status === statusFilter;
                }
                
                // Type filter
                if (typeFilter !== 'all') {
                    const type = card.querySelector('.mou-type')?.textContent || '';
                    shouldShow = shouldShow && type === typeFilter;
                }
                
                // Date filter
                if (dateFilter !== 'all') {
                    const signedDate = card.querySelector('.signed-date')?.textContent || '';
                    const endDate = card.querySelector('.end-date')?.textContent || '';
                    const currentYear = new Date().getFullYear();
                    const lastYear = currentYear - 1;
                    const currentDate = new Date();
                    const threeMonthsFromNow = new Date();
                    threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);
                    
                    switch (dateFilter) {
                        case 'this-year':
                            shouldShow = shouldShow && signedDate.includes(currentYear.toString());
                            break;
                        case 'last-year':
                            shouldShow = shouldShow && signedDate.includes(lastYear.toString());
                            break;
                        case 'expiring-soon':
                            if (endDate) {
                                const endDateObj = new Date(endDate);
                                shouldShow = shouldShow && endDateObj > currentDate && endDateObj <= threeMonthsFromNow;
                            } else {
                                shouldShow = false;
                            }
                            break;
                        case 'expired':
                            if (endDate) {
                                const endDateObj = new Date(endDate);
                                shouldShow = shouldShow && endDateObj < currentDate;
                            } else {
                                shouldShow = false;
                            }
                            break;
                    }
                }
                
                card.style.display = shouldShow ? 'block' : 'none';
            });
            
            // Sort visible cards
            const visibleCards = mouCards.filter(card => card.style.display !== 'none');
            visibleCards.sort((a, b) => {
                switch (sortFilter) {
                    case 'date-desc':
                        const aDate = new Date(a.querySelector('.signed-date')?.textContent || '');
                        const bDate = new Date(b.querySelector('.signed-date')?.textContent || '');
                        return bDate - aDate;
                    case 'date-asc':
                        const aDateAsc = new Date(a.querySelector('.signed-date')?.textContent || '');
                        const bDateAsc = new Date(b.querySelector('.signed-date')?.textContent || '');
                        return aDateAsc - bDateAsc;
                    case 'name-asc':
                        const aName = a.querySelector('.mou-organization')?.textContent || '';
                        const bName = b.querySelector('.mou-organization')?.textContent || '';
                        return aName.localeCompare(bName);
                    case 'name-desc':
                        const aNameDesc = a.querySelector('.mou-organization')?.textContent || '';
                        const bNameDesc = b.querySelector('.mou-organization')?.textContent || '';
                        return bNameDesc.localeCompare(aNameDesc);
                    case 'expiry-asc':
                        const aExpiry = new Date(a.querySelector('.end-date')?.textContent || '9999-12-31');
                        const bExpiry = new Date(b.querySelector('.end-date')?.textContent || '9999-12-31');
                        return aExpiry - bExpiry;
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted cards
            visibleCards.forEach(card => container.appendChild(card));
            
            updateResultsCount();
        }

        // Add keyboard shortcut for search
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('search-mous').focus();
            }
        });

        // Add event listeners for filters
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-mous');
            const statusFilter = document.getElementById('status-filter');
            const typeFilter = document.getElementById('type-filter');
            const dateFilter = document.getElementById('date-filter');
            const sortFilter = document.getElementById('sort-filter');
            
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(searchMOUs, 300); // Debounce search
                });
            }
            
            [statusFilter, typeFilter, dateFilter, sortFilter].forEach(filter => {
                if (filter) {
                    filter.addEventListener('change', searchMOUs);
                }
            });
            
            // Initialize results count
            updateResultsCount();
        });

        // Tagging system removed for now
    </script>

</body>

</html>
