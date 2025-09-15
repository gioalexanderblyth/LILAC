<!-- Mobile backdrop/overlay -->
<div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden lg:hidden"></div>

<div id="sidebar" class="sidebar fixed top-0 left-0 w-64 h-screen bg-gradient-to-b from-blue-500 to-purple-600 text-white transition-transform duration-300 ease-in-out z-[70] flex flex-col shadow-2xl rounded-r-2xl -translate-x-full">
           <div class="h-20 px-6 py-4 flex items-center justify-between">
         <div class="flex items-center justify-center flex-1">
             <img src="img/cpu-logo.png" alt="CPU Logo" class="w-12 h-12 object-contain cursor-pointer hover:scale-105 transition-transform duration-200" onclick="location.reload()"/>
         </div>
         <button id="sidebar-close" class="absolute top-4 right-4 bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-lg p-1.5 transition-all duration-200 shadow-md" title="Close sidebar">
           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
           </svg>
         </button>
  </div>
  <nav class="px-6 py-4 space-y-3">
    <a href="dashboard.php" class="nav-item modern-nav-item flex items-center px-4 py-3 rounded-xl hover:bg-white/20 active:bg-white/30 transition-all duration-200 text-white group" data-tooltip="Dashboard">
      <svg class="w-5 h-5 mr-4 flex-shrink-0 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      <span class="nav-text text-white font-semibold group-hover:font-bold transition-all duration-200">Dashboard</span>
    </a>
    <a href="documents.php" class="nav-item modern-nav-item flex items-center px-4 py-3 rounded-xl hover:bg-white/20 active:bg-white/30 transition-all duration-200 text-white group" data-tooltip="Documents">
      <svg class="w-5 h-5 mr-4 flex-shrink-0 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      <span class="nav-text text-white font-semibold group-hover:font-bold transition-all duration-200">Documents</span>
    </a>
    <a href="scheduler.php" class="nav-item modern-nav-item flex items-center px-4 py-3 rounded-xl hover:bg-white/20 active:bg-white/30 transition-all duration-200 text-white group" data-tooltip="Scheduler">
      <svg class="w-5 h-5 mr-4 flex-shrink-0 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <span class="nav-text text-white font-semibold group-hover:font-bold transition-all duration-200">Scheduler</span>
    </a>
    <a href="events_activities.php" class="nav-item modern-nav-item flex items-center px-4 py-3 rounded-xl hover:bg-white/20 active:bg-white/30 transition-all duration-200 text-white group" data-tooltip="Events & Activities">
      <svg class="w-5 h-5 mr-4 flex-shrink-0 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="nav-text text-white font-semibold group-hover:font-bold transition-all duration-200">Events & Activities</span>
    </a>
    <a href="mou-moa.php" class="nav-item modern-nav-item flex items-center px-4 py-3 rounded-xl hover:bg-white/20 active:bg-white/30 transition-all duration-200 text-white group" data-tooltip="MOUs & MOAs">
      <svg class="w-5 h-5 mr-4 flex-shrink-0 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
      </svg>
      <span class="nav-text text-white font-semibold group-hover:font-bold transition-all duration-200">MOUs & MOAs</span>
    </a>
    <a href="awards.php" class="nav-item modern-nav-item flex items-center px-4 py-3 rounded-xl hover:bg-white/20 active:bg-white/30 transition-all duration-200 text-white group" data-tooltip="Awards Progress">
      <svg class="w-5 h-5 mr-4 flex-shrink-0 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
      </svg>
      <span class="nav-text text-white font-semibold group-hover:font-bold transition-all duration-200">Awards Progress</span>
    </a>
    <a href="templates.php" class="nav-item modern-nav-item flex items-center px-4 py-3 rounded-xl hover:bg-white/20 active:bg-white/30 transition-all duration-200 text-white group" data-tooltip="Templates">
      <svg class="w-5 h-5 mr-4 flex-shrink-0 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
      </svg>
      <span class="nav-text text-white font-semibold group-hover:font-bold transition-all duration-200">Templates</span>
    </a>
  </nav>
  
  <div class="px-6 py-2">
    <div class="border-t border-white/20"></div>
  </div>
  
  <div class="px-6 py-4 mt-auto">
  </div>
</div>

<script>
// Global Sidebar Management System
window.LILACSidebar = {
    initialized: false,
    isOpen: false,
    
    init: function() {
        if (this.initialized) return;
        this.initialized = true;
        
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        
        if (!sidebar) {
            console.warn('Sidebar element not found');
            return;
        }
        
        // Initialize saveTimeout property
        this.saveTimeout = null;
        
        // Load saved state from database and apply
        this.loadState().then(() => {
            this.applyState();
            this.setupEventListeners();
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            this.applyState();
        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (this.saveTimeout) {
                clearTimeout(this.saveTimeout);
                // Save immediately before leaving
                this.saveStateToDatabase();
            }
        });
    },
    
    loadState: async function() {
        try {
            const response = await fetch('api/sidebar_state.php?action=get_state');
            const data = await response.json();
            if (data.success) {
                this.isOpen = data.data.state === 'open';
            } else {
                // Default state based on screen size
                this.isOpen = window.innerWidth >= 1024;
            }
        } catch (error) {
            console.warn('Could not load sidebar state from database:', error);
            // Default state based on screen size
            this.isOpen = window.innerWidth >= 1024;
        }
    },
    
    saveState: function() {
        // Debounce database saves to avoid excessive API calls
        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => {
            this.saveStateToDatabase();
        }, 500); // Save to database after 500ms of inactivity
    },
    
    saveStateToDatabase: async function() {
        try {
            const formData = new FormData();
            formData.append('action', 'save_state');
            formData.append('state', this.isOpen ? 'open' : 'closed');
            
            const response = await fetch('api/sidebar_state.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (!data.success) {
                console.warn('Failed to save sidebar state to database:', data.message);
            }
        } catch (error) {
            console.warn('Could not save sidebar state to database:', error);
        }
    },
    
    applyState: function() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        
        const isDesktop = window.innerWidth >= 1024;
        
        // Use requestAnimationFrame for smooth animations
        requestAnimationFrame(() => {
            // Apply sidebar visibility - always respect the isOpen state
            if (this.isOpen) {
                sidebar.classList.remove('-translate-x-full');
            } else {
                sidebar.classList.add('-translate-x-full');
            }
            
            // Handle backdrop (only on mobile)
            const backdrop = document.getElementById('sidebar-backdrop');
            if (backdrop) {
                if (!isDesktop && this.isOpen) {
                    backdrop.classList.remove('hidden');
                } else {
                    backdrop.classList.add('hidden');
                }
            }
            
            // Handle main content margin (only on desktop when sidebar is open)
            const mainContainer = document.getElementById('main-content');
            if (mainContainer) {
                if (isDesktop && this.isOpen) {
                    mainContainer.classList.add('ml-64');
                } else {
                    mainContainer.classList.remove('ml-64');
                }
            }
            
            // Handle navigation padding (only on desktop when sidebar is open)
            const nav = document.querySelector('nav.modern-nav');
            if (nav) {
                if (isDesktop && this.isOpen) {
                    nav.classList.add('pl-64');
                } else {
                    nav.classList.remove('pl-64');
                }
            }
            
            // Dispatch custom event for other components
            window.dispatchEvent(new CustomEvent('sidebar:state', { 
                detail: { isOpen: this.isOpen, isDesktop: isDesktop } 
            }));
        });
    },
    
    toggle: function() {
        this.isOpen = !this.isOpen;
        this.applyState();
        
        // Save state to database (debounced)
        this.saveState();
        
        // On mobile, close sidebar automatically after navigation
        if (window.innerWidth < 1024 && this.isOpen) {
            // Auto-close after 5 seconds on mobile
            setTimeout(() => {
                if (window.innerWidth < 1024) {
                    this.close();
                }
            }, 5000);
        }
    },
    
    open: function() {
        if (!this.isOpen) {
            this.toggle();
        }
    },
    
    close: function() {
        if (this.isOpen) {
            this.toggle();
        }
    },
    
    setupEventListeners: function() {
        // Setup hamburger button if it exists
        const hamburger = document.getElementById('hamburger-toggle');
        if (hamburger) {
            hamburger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Immediate visual feedback
                hamburger.style.transform = 'scale(0.95)';
                
                // Toggle sidebar immediately
                this.toggle();
                
                // Reset animation after a short delay
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        hamburger.style.transform = '';
                    }, 100);
                });
            });
        }
        
        // Setup close button
        const closeBtn = document.getElementById('sidebar-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                // Add click animation
                closeBtn.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    closeBtn.style.transform = '';
                }, 120);
                this.close();
            });
        }
        
        // Setup backdrop click
        const backdrop = document.getElementById('sidebar-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', () => this.close());
        }
        
        // Auto-close on navigation for mobile
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (link && window.innerWidth < 1024 && this.isOpen) {
                // Small delay to allow navigation to start
                setTimeout(() => this.close(), 100);
            }
        });
    }
};

// Global toggle function for backward compatibility
window.toggleSidebar = function() {
    if (window.LILACSidebar) {
        window.LILACSidebar.toggle();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.LILACSidebar.init();
    
    // Add active class to current page navigation item
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
    const navItems = document.querySelectorAll('#sidebar .nav-item');
    
    // Remove active class from all items first
    navItems.forEach(item => item.classList.remove('active'));
    
    // Find and highlight the current page
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href) {
            const hrefPage = href.split('/').pop();
            if (hrefPage === currentPage) {
                item.classList.add('active');
            }
        }
        
        // Add click animation (zoom out then back up like documents page)
        item.addEventListener('click', function(e) {
            // Apply the scale down effect
            this.style.transform = 'translateX(2px) scale(0.98)';
            
            // Reset after animation
            setTimeout(() => {
                this.style.transform = '';
            }, 120);
        });
    });
});
</script>