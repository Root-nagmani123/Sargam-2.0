
<!-- Tab Content Container -->

<!-- //admin tabs -->
    <div class="tab-content" id="sidebarTabContent">
        <div class="tab-pane fade show active" id="sidebar-home" role="tabpanel" aria-labelledby="home-tab">
           @include('admin.layouts.sidebar.home')
        </div>
        <div class="tab-pane fade" id="sidebar-setup" role="tabpanel" aria-labelledby="setup-tab">
            @include('admin.layouts.sidebar.setup')
        </div>
        <div class="tab-pane fade" id="sidebar-communications" role="tabpanel" aria-labelledby="communications-tab">
            @include('admin.layouts.sidebar.communication') 
        </div>
        <div class="tab-pane fade" id="sidebar-academics" role="tabpanel" aria-labelledby="academics-tab">
            @include('admin.layouts.sidebar.academics')
        </div>
        <div class="tab-pane fade" id="sidebar-purchase-order" role="tabpanel" aria-labelledby="purchase-order-tab">
            @include('admin.layouts.sidebar.material')
        </div>
    </div>


    <!-- //faculty & OTs tabs -->



<script>
const sidebar = document.querySelector('.sidebarmenu .simplebar-content-wrapper');

// Restore scroll position on load
document.addEventListener('DOMContentLoaded', function() {
    const scrollPos = localStorage.getItem('sidebar-scroll');
    if (scrollPos && sidebar) {
        sidebar.scrollTop = parseInt(scrollPos, 10);
    }
    
    // Sync sidebar tabs with main content tabs
    syncSidebarWithMainTabs();
});

// Function to sync sidebar tabs with main content tabs
function syncSidebarWithMainTabs() {
    const mainTabLinks = document.querySelectorAll('#mainNavbar .nav-link[data-bs-toggle="tab"]');
    const sidebarTabPanes = document.querySelectorAll('#sidebarTabContent .tab-pane');
    
    // Map main tab IDs to sidebar tab IDs
    const tabMapping = {
        '#home': '#sidebar-home',
        '#tab-setup': '#sidebar-setup',
        '#tab-communications': '#sidebar-communications',
        '#tab-academics': '#sidebar-academics',
        '#tab-purchase-order': '#sidebar-purchase-order'
    };
    
    // Function to activate sidebar tab based on main tab
    function activateSidebarTab(mainTabId) {
        const sidebarTabId = tabMapping[mainTabId];
        if (!sidebarTabId) return;
        
        // Deactivate all sidebar tabs
        sidebarTabPanes.forEach(pane => {
            pane.classList.remove('show', 'active');
        });
        
        // Activate corresponding sidebar tab
        const sidebarTab = document.querySelector(sidebarTabId);
        if (sidebarTab) {
            sidebarTab.classList.add('show', 'active');
        }
    }
    
    // Listen for main tab clicks
    mainTabLinks.forEach(link => {
        link.addEventListener('click', function() {
            const targetTab = this.getAttribute('href');
            activateSidebarTab(targetTab);
        });
        
        // Also listen for Bootstrap tab events
        link.addEventListener('shown.bs.tab', function() {
            const targetTab = this.getAttribute('href');
            activateSidebarTab(targetTab);
        });
    });
    
    // Activate sidebar tab based on initial active main tab
    const activeMainTab = document.querySelector('#mainNavbar .nav-link[data-bs-toggle="tab"].active');
    if (activeMainTab) {
        const targetTab = activeMainTab.getAttribute('href');
        activateSidebarTab(targetTab);
    }
    
    // Also check localStorage for persisted tab
    const savedTab = localStorage.getItem('activeMainTab');
    if (savedTab) {
        activateSidebarTab(savedTab);
    }
}

// Save scroll position before unload
window.addEventListener('beforeunload', function() {
    if (sidebar) {
        localStorage.setItem('sidebar-scroll', sidebar.scrollTop);
    }
});
// Add on click
document.querySelectorAll('.mini-nav-item').forEach(item => {
    item.addEventListener('click', function() {
        localStorage.setItem('active-mini-nav', this.id);
    });
});

// On load
document.addEventListener('DOMContentLoaded', () => {
    const activeId = localStorage.getItem('active-mini-nav');
    if (activeId) {
        document.querySelectorAll('.mini-nav-item').forEach(item => item.classList.remove('selected'));
        const activeEl = document.getElementById(activeId);
        if (activeEl) activeEl.classList.add('selected');
    }
});
</script>



<!--  Sidebar End -->