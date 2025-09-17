/**
 * Awards Management JavaScript
 * Handles all awards-related functionality
 */

class AwardsManager {
    constructor() {
        this.awardsData = [];
        this.statsData = {};
        this.currentFilters = {
            category: 'all',
            status: 'all',
            period: 'This Month',
            search: '',
            page: 1,
            limit: AwardsConfig.ui.itemsPerPage
        };
        
        this.initializeEventListeners();
        this.loadAwardsData();
        this.loadStatsData();
    }
    
    /**
     * Initialize event listeners
     */
    initializeEventListeners() {
        // Period filter for donut chart
        const periodSelect = document.getElementById('awards-breakdown-period');
        if (periodSelect) {
            periodSelect.addEventListener('change', () => {
                this.currentFilters.period = periodSelect.value;
                this.updateAwardsDonutForPeriod(periodSelect.value);
            });
        }
        
        // Search functionality
        const searchInput = document.getElementById('awards-search');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.currentFilters.search = searchInput.value;
                this.currentFilters.page = 1;
                this.loadAwardsData();
            }, 300));
        }
        
        // Category filter
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => {
                this.currentFilters.category = categoryFilter.value;
                this.currentFilters.page = 1;
                this.loadAwardsData();
            });
        }
        
        // Upload button
        const uploadBtn = document.getElementById('upload-award-btn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', () => this.showUploadModal());
        }
        
        // Upload form
        const uploadForm = document.getElementById('award-upload-form');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }
    }
    
    /**
     * Load awards data from API
     */
    async loadAwardsData() {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                action: 'get_awards',
                ...this.currentFilters
            });
            
            const response = await fetch(`${AwardsConfig.api.awards}?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.awardsData = data.awards || [];
                this.statsData = data; // Store the full API response including counts
                this.renderAwards();
                this.updateAwardCounters();
            } else {
                throw new Error(data.error || 'Failed to load awards');
            }
        } catch (error) {
            console.error('Error loading awards:', error);
            this.showNotification('Failed to load awards', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Load statistics data from API
     */
    async loadStatsData() {
        try {
            const response = await fetch(`${AwardsConfig.api.stats}?action=get_stats`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.statsData = data.stats || {};
                this.updateStats();
                this.updateAwardsDonutForPeriod(this.currentFilters.period);
            } else {
                // Fallback to default values
                this.statsData = {
                    total: 0,
                    academic: 0,
                    research: 0,
                    leadership: 0
                };
                this.updateStats();
                this.updateAwardsDonutForPeriod(this.currentFilters.period);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
            // Use fallback data
            this.statsData = {
                total: 0,
                academic: 0,
                research: 0,
                leadership: 0
            };
            this.updateStats();
            this.updateAwardsDonutForPeriod(this.currentFilters.period);
        }
    }
    
    /**
     * Update statistics display
     */
    updateStats() {
        const totalEl = document.getElementById('total-awards');
        const academicEl = document.getElementById('academic-count');
        const researchEl = document.getElementById('research-count');
        const leadershipEl = document.getElementById('leadership-count');
        
        if (totalEl) totalEl.textContent = this.statsData.total || 0;
        if (academicEl) academicEl.textContent = this.statsData.academic || 0;
        if (researchEl) researchEl.textContent = this.statsData.research || 0;
        if (leadershipEl) leadershipEl.textContent = this.statsData.leadership || 0;
    }
    
    /**
     * Update awards donut chart for specific period
     */
    async updateAwardsDonutForPeriod(period) {
        try {
            // Try to get real data from API
            const response = await fetch(`${AwardsConfig.api.awards}?action=get_awards_by_period&period=${encodeURIComponent(period)}`);
            
            if (response.ok) {
                const result = await response.json();
                if (result.success && result.data) {
                    // Use real API data
                    this.renderDonutChart(result.data);
                    return;
                }
            }
            
            // If API fails, show error state instead of fake zeros
            this.showDonutError('Unable to load chart data');
            
        } catch (error) {
            console.error('Error loading period data:', error);
            this.showDonutError('Network error loading chart data');
        }
    }
    
    /**
     * Show error state for donut chart
     */
    showDonutError(message) {
        const donut = document.getElementById('awardsDonut');
        if (donut) {
            donut.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-500">
                    <div class="text-center">
                        <div class="text-2xl mb-2">📊</div>
                        <div class="text-sm">${message}</div>
                    </div>
                </div>
            `;
        }
        
        // Update labels to show error
        const pctElems = document.querySelectorAll('#awards-breakdown-percentages .pct');
        if (pctElems.length >= 3) {
            pctElems[0].textContent = 'N/A';
            pctElems[1].textContent = 'N/A';
            pctElems[2].textContent = 'N/A';
        }
    }
    
    /**
     * Render donut chart with data
     */
    renderDonutChart(data) {
        const total = data.red + data.blue + data.pink;
        
        // Convert to degrees safely (avoid NaN when total is 0)
        const redDeg = total > 0 ? 360 * (data.red / total) : 0;
        const blueDeg = total > 0 ? 360 * (data.blue / total) : 0;
        const pinkDeg = total > 0 ? Math.max(0, 360 - (redDeg + blueDeg)) : 0;
        
        const donut = document.getElementById('awardsDonut');
        if (donut) {
            donut.style.setProperty('--deg-red', redDeg + 'deg');
            donut.style.setProperty('--deg-blue', blueDeg + 'deg');
        }
        
        // Update labels on right
        const pctElems = document.querySelectorAll('#awards-breakdown-percentages .pct');
        if (pctElems.length >= 3) {
            pctElems[0].textContent = total > 0 ? Math.round((data.red / total) * 100) + '%' : '0%';
            pctElems[1].textContent = total > 0 ? Math.round((data.blue / total) * 100) + '%' : '0%';
            pctElems[2].textContent = total > 0 ? Math.round((data.pink / total) * 100) + '%' : '0%';
        }
        
        // Update bar charts
        const barRed = document.querySelector('#awards-breakdown-percentages .bar-red');
        const barBlue = document.querySelector('#awards-breakdown-percentages .bar-blue');
        const barPink = document.querySelector('#awards-breakdown-percentages .bar-pink');
        
        if (barRed) barRed.style.width = total > 0 ? (data.red / total) * 100 + '%' : '0%';
        if (barBlue) barBlue.style.width = total > 0 ? (data.blue / total) * 100 + '%' : '0%';
        if (barPink) barPink.style.width = total > 0 ? (data.pink / total) * 100 + '%' : '0%';
    }
    
    /**
     * Load monthly trend data
     */
    async loadMonthlyTrendData() {
        try {
            const loadingElement = document.getElementById('monthlyTrendChartLoading');
            if (loadingElement) {
                loadingElement.classList.remove('hidden');
            }
            
            const response = await fetch(`${AwardsConfig.api.awards}?action=get_awards_by_month`);
            
            if (response.ok) {
                const result = await response.json();
                if (result.success && result.data) {
                    this.updateMonthlyTrendChart(result.data);
                } else {
                    this.updateMonthlyTrendChart();
                }
            } else {
                this.updateMonthlyTrendChart();
            }
        } catch (error) {
            console.error('Error loading monthly trend data:', error);
            this.updateMonthlyTrendChart();
        } finally {
            const loadingElement = document.getElementById('monthlyTrendChartLoading');
            if (loadingElement) {
                loadingElement.classList.add('hidden');
            }
        }
    }
    
    /**
     * Update monthly trend chart
     */
    updateMonthlyTrendChart(apiData = null) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        let thisYearData, lastYearData;
        
        if (apiData) {
            // Use real API data
            thisYearData = apiData.thisYear || new Array(12).fill(0);
            lastYearData = apiData.lastYear || new Array(12).fill(0);
        } else {
            // Default to zeros when no data is available
            thisYearData = new Array(12).fill(0);
            lastYearData = new Array(12).fill(0);
        }
        
        // Update the chart with new data
        if (window.monthlyTrendChart && typeof Chart !== 'undefined') {
            window.monthlyTrendChart.data.datasets[0].data = thisYearData;
            window.monthlyTrendChart.data.datasets[1].data = lastYearData;
            window.monthlyTrendChart.update('active');
        }
    }
    
    /**
     * Render awards in the table
     */
    renderAwards() {
        const tbody = document.getElementById('awards-table-body');
        if (!tbody) return;
        
        if (this.awardsData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-8 text-gray-500">
                        No awards found
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.awardsData.map(award => this.createAwardRow(award)).join('');
    }
    
    /**
     * Create an award table row
     */
    createAwardRow(award) {
        const category = AwardsConfig.categories.find(c => c.value === award.category) || AwardsConfig.categories[0];
        const status = AwardsConfig.statusOptions.find(s => s.value === award.status) || AwardsConfig.statusOptions[0];
        
        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        ${award.title}
                    </div>
                    <div class="text-sm text-gray-500">
                        ${award.description || 'No description'}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" style="color: ${category.color}">
                        ${category.label}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${award.recipient || 'Unknown'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(award.award_date)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${status.color}">
                        ${status.label}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="awardsManager.viewAward('${award.id}')" 
                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                        View
                    </button>
                    <button onclick="awardsManager.downloadAward('${award.id}')" 
                            class="text-green-600 hover:text-green-900 mr-3">
                        Download
                    </button>
                    <button onclick="awardsManager.deleteAward('${award.id}')" 
                            class="text-red-600 hover:text-red-900">
                        Delete
                    </button>
                </td>
            </tr>
        `;
    }
    
    /**
     * Update award counters
     */
    updateAwardCounters() {
        // Use actual awards received from API, not document counts
        const total = this.statsData.counts?.total || 0;
        const academic = this.statsData.counts?.education || 0;
        const research = this.statsData.counts?.emerging || 0;
        const leadership = this.statsData.counts?.leadership || 0;
        
        const totalEl = document.getElementById('total-awards');
        const academicEl = document.getElementById('academic-count');
        const researchEl = document.getElementById('research-count');
        const leadershipEl = document.getElementById('leadership-count');
        
        if (totalEl) totalEl.textContent = total;
        if (academicEl) academicEl.textContent = academic;
        if (researchEl) researchEl.textContent = research;
        if (leadershipEl) leadershipEl.textContent = leadership;
    }
    
    /**
     * Show upload modal
     */
    showUploadModal() {
        const modal = document.getElementById('award-upload-modal');
        if (modal) {
            modal.classList.remove('hidden');
            this.resetUploadForm();
        }
    }
    
    /**
     * Hide upload modal
     */
    hideUploadModal() {
        const modal = document.getElementById('award-upload-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    /**
     * Reset upload form
     */
    resetUploadForm() {
        const form = document.getElementById('award-upload-form');
        if (form) {
            form.reset();
        }
    }
    
    /**
     * Handle form submission
     */
    async handleUpload(event) {
        event.preventDefault();
        
        // Validate form
        if (!this.validateForm()) return;
        
        const formData = new FormData(event.target);
        formData.append('action', 'upload');
        
        try {
            this.showLoading(true);
            
            const response = await fetch(AwardsConfig.api.upload, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Award uploaded successfully', 'success');
                this.hideUploadModal();
                this.loadAwardsData();
                this.loadStatsData();
                
                // Check for awards earned after successful upload
                if (window.checkAwardCriteria) {
                    window.checkAwardCriteria('award', result.award_id || result.data?.award_id);
                }
            } else {
                throw new Error(result.error || 'Upload failed');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showNotification('Upload failed', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Validate form data
     */
    validateForm() {
        const form = document.getElementById('award-upload-form');
        if (!form) return false;
        
        const errors = [];
        
        // Required fields
        const title = form.querySelector('#award-title');
        if (!title || !title.value.trim()) {
            errors.push(AwardsConfig.validation.required.title);
        }
        
        const category = form.querySelector('#award-category');
        if (!category || !category.value) {
            errors.push(AwardsConfig.validation.required.category);
        }
        
        const recipient = form.querySelector('#award-recipient');
        if (!recipient || !recipient.value.trim()) {
            errors.push(AwardsConfig.validation.required.recipient);
        }
        
        const date = form.querySelector('#award-date');
        if (!date || !date.value) {
            errors.push(AwardsConfig.validation.required.date);
        }
        
        if (errors.length > 0) {
            this.showNotification(errors.join(', '), 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * View award details
     */
    viewAward(awardId) {
        const award = this.awardsData.find(a => a.id === awardId);
        if (!award) return;
        
        // Show award details in modal or navigate to details page
        this.showAwardDetailsModal(award);
    }
    
    /**
     * Show award details modal
     */
    showAwardDetailsModal(award) {
        const modal = document.getElementById('award-details-modal');
        if (!modal) return;
        
        // Populate modal with award data
        const titleEl = modal.querySelector('#award-details-title');
        const descriptionEl = modal.querySelector('#award-details-description');
        const categoryEl = modal.querySelector('#award-details-category');
        const recipientEl = modal.querySelector('#award-details-recipient');
        const dateEl = modal.querySelector('#award-details-date');
        const statusEl = modal.querySelector('#award-details-status');
        
        if (titleEl) titleEl.textContent = award.title;
        if (descriptionEl) descriptionEl.textContent = award.description || 'No description';
        if (categoryEl) {
            const category = AwardsConfig.categories.find(c => c.value === award.category);
            categoryEl.textContent = category ? category.label : award.category;
        }
        if (recipientEl) recipientEl.textContent = award.recipient || 'Unknown';
        if (dateEl) dateEl.textContent = this.formatDate(award.award_date);
        if (statusEl) {
            const status = AwardsConfig.statusOptions.find(s => s.value === award.status);
            statusEl.textContent = status ? status.label : award.status;
        }
        
        modal.classList.remove('hidden');
    }
    
    /**
     * Download award document
     */
    downloadAward(awardId) {
        const award = this.awardsData.find(a => a.id === awardId);
        if (!award) return;
        
        const link = document.createElement('a');
        link.href = award.file_path || award.document_path;
        link.download = award.filename || award.document_name;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * Delete award
     */
    async deleteAward(awardId) {
        if (!confirm('Are you sure you want to delete this award?')) return;
        
        try {
            this.showLoading(true);
            
            const response = await fetch(AwardsConfig.api.awards, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete_award',
                    id: awardId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Award deleted successfully', 'success');
                this.loadAwardsData();
                this.loadStatsData();
            } else {
                throw new Error(result.error || 'Failed to delete award');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Failed to delete award', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Show loading state
     */
    showLoading(show) {
        const loadingEl = document.getElementById('awards-loading');
        if (loadingEl) {
            loadingEl.style.display = show ? 'block' : 'none';
        }
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = AwardsConfig.notifications[type] || AwardsConfig.notifications.info;
        
        // Create notification element
        const notificationEl = document.createElement('div');
        notificationEl.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${notification.bgColor} ${notification.color}`;
        notificationEl.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">${notification.icon}</span>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notificationEl);
        
        // Remove after 3 seconds
        setTimeout(() => {
            if (notificationEl.parentNode) {
                notificationEl.parentNode.removeChild(notificationEl);
            }
        }, 3000);
    }
    
    /**
     * Format date for display
     */
    formatDate(dateString) {
        if (!dateString) return 'Unknown';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.awardsManager = new AwardsManager();
});
