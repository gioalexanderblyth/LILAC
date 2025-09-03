<div id="sidebar" class="sidebar fixed top-0 left-0 h-screen bg-gradient-to-b from-blue-500 to-purple-600 text-white transition-transform duration-300 ease-in-out z-[70] transform flex flex-col shadow-2xl rounded-r-2xl">
           <div class="h-20 px-6 py-4 flex items-center justify-between">
         <div class="flex items-center justify-center flex-1">
             <img src="img/cpu-logo.png" alt="CPU Logo" class="w-12 h-12 object-contain cursor-pointer hover:scale-105 transition-transform duration-200" onclick="location.reload()"/>
         </div>
         <button id="sidebar-close" class="absolute top-4 right-4 bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-lg p-1.5 transition-all duration-200 shadow-md" title="Close sidebar" onclick="try{window.dispatchEvent(new CustomEvent('sidebar:toggle'))}catch(e){}">
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
    <a href="registrar_files.php" class="nav-item modern-nav-item flex items-center px-4 py-3 rounded-xl hover:bg-white/20 active:bg-white/30 transition-all duration-200 text-white group" data-tooltip="Registrar Files">
      <svg class="w-5 h-5 mr-4 flex-shrink-0 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
      </svg>
      <span class="nav-text text-white font-semibold group-hover:font-bold transition-all duration-200">Registrar Files</span>
    </a>
  </nav>
  
  <!-- Section Divider -->
  <div class="px-6 py-2">
    <div class="border-t border-white/20"></div>
  </div>
  
  <!-- Connection Status -->
  <div class="px-6 py-4 mt-auto">
    <div id="connection-status" class="flex items-center justify-center space-x-2 text-sm">
      <div id="online-indicator" class="flex items-center space-x-2 text-green-300">
        <div class="w-2 h-2 bg-green-300 rounded-full animate-pulse"></div>
        <span class="font-medium">Online</span>
      </div>
      <div id="offline-indicator" class="flex items-center space-x-2 text-orange-300" style="display: none;">
        <div class="w-2 h-2 bg-orange-300 rounded-full"></div>
        <span class="font-medium">Offline</span>
      </div>
    </div>
  </div>
</div>
<script>
// Function to highlight the current page in navigation - DISABLED
// Now only hover effects will show highlights
function highlightCurrentPage() {
  // Remove any existing active classes to disable highlighting
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.classList.remove('active', 'bg-white/30', 'shadow-lg');
    const textSpan = item.querySelector('.nav-text');
    if (textSpan) {
      textSpan.classList.remove('font-bold');
    }
  });
  
  // No active highlighting - only hover effects will be visible
}

function logoutLILAC() {
  localStorage.clear();
  sessionStorage.clear();
  window.location.href = 'index.html';
}
document.addEventListener('DOMContentLoaded', function () {
  // Highlight current page in navigation
  highlightCurrentPage();
  
  var sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  var EDGE_OPEN_PX = 24; // open when cursor is within 24px of left edge
  var EDGE_CLOSE_PX = 300; // close when cursor moves beyond 300px from left edge
  var isOpen = true;
  function dispatchSidebarState(isOpen, mode) {
    try {
      window.dispatchEvent(new CustomEvent('sidebar:state', { detail: { open: !!isOpen, mode: mode } }));
      document.body.classList[isOpen ? 'add' : 'remove']('sidebar-open');
    } catch (err) { /* noop */ }
  }
  function adjustDashboardLayout(isOpen) {
    var main = document.getElementById('main-content');
    if (isOpen) {
        if (main) main.classList.add('ml-64');
    } else {
        if (main) main.classList.remove('ml-64');
    }
}
  function openSidebarDesktop() { sidebar.style.transform = ''; isOpen = true; dispatchSidebarState(true, 'desktop'); adjustDashboardLayout(true); }
  function closeSidebarDesktop() { sidebar.style.transform = 'translateX(-100%)'; isOpen = false; dispatchSidebarState(false, 'desktop'); adjustDashboardLayout(false); }

  // Listen for explicit toggle requests (from hamburger button)
  window.addEventListener('sidebar:toggle', function () {
    if (isOpen) {
      closeSidebarDesktop();
    } else {
      openSidebarDesktop();
    }
  });

  window.addEventListener('resize', function () {
    // Keep layout consistent; treat desktop as open spacing by default
    sidebar.classList.remove('-translate-x-full');
    sidebar.style.transform = '';
    dispatchSidebarState(true, 'desktop');
    adjustDashboardLayout(true);
  });

  // Broadcast initial state so layout adjusts immediately
  dispatchSidebarState(true, 'desktop');
  adjustDashboardLayout(true);

});
</script> 