{{-- Horizontal Navigation Bar - GIGW Compliant --}}
{{-- Displays navigation as a horizontal menu below the header --}}

<nav class="horizontal-nav-bar" id="horizontalNavBar" role="navigation" aria-label="Section navigation">
    <div class="horizontal-nav-inner">

        {{-- Pane: Home --}}
        <div class="horizontal-nav-pane {{ ($activeNavTab ?? '#home') === '#home' ? '' : 'd-none' }}" id="sidebar-home">
            @include('admin.layouts.sidebar.home-new')
        </div>

        {{-- Pane: Setup --}}
        <div class="horizontal-nav-pane {{ ($activeNavTab ?? '#home') === '#tab-setup' ? '' : 'd-none' }}" id="sidebar-setup">
            @include('admin.layouts.sidebar.setup-new')
        </div>

        {{-- Pane: Communications --}}
        <div class="horizontal-nav-pane {{ ($activeNavTab ?? '#home') === '#tab-communications' ? '' : 'd-none' }}" id="sidebar-communications">
            @include('admin.layouts.sidebar.communication-new')
        </div>

        {{-- Pane: Academics --}}
        <div class="horizontal-nav-pane {{ ($activeNavTab ?? '#home') === '#tab-academics' ? '' : 'd-none' }}" id="sidebar-academics">
            @include('admin.layouts.sidebar.academics-new')
        </div>

        {{-- Pane: Material (placeholder) --}}
        <div class="horizontal-nav-pane {{ ($activeNavTab ?? '#home') === '#tab-material-management' ? '' : 'd-none' }}" id="sidebar-purchase-order">
            @include('admin.layouts.sidebar.material')
        </div>

    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Sync horizontal nav panes with header tab clicks
    var allNavTabLinks = document.querySelectorAll(
        '#mainNavbar .nav-link[data-bs-toggle="tab"], .mobile-tabbar .nav-link[data-bs-toggle="tab"]'
    );
    var navPanes = document.querySelectorAll('#horizontalNavBar .horizontal-nav-pane');

    var tabMapping = {
        '#home': 'sidebar-home',
        '#tab-setup': 'sidebar-setup',
        '#tab-communications': 'sidebar-communications',
        '#tab-academics': 'sidebar-academics',
        '#tab-material-management': 'sidebar-purchase-order'
    };

    function activateNavPane(mainTabId) {
        var paneId = tabMapping[mainTabId];
        if (!paneId) return;

        navPanes.forEach(function(pane) {
            pane.classList.add('d-none');
        });

        var activePane = document.getElementById(paneId);
        if (activePane) {
            activePane.classList.remove('d-none');
        }
    }

    // Listen for header tab clicks
    allNavTabLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            var targetTab = this.getAttribute('href');
            if (targetTab && tabMapping[targetTab]) {
                activateNavPane(targetTab);
            }
        });
        link.addEventListener('shown.bs.tab', function() {
            var targetTab = this.getAttribute('href');
            activateNavPane(targetTab);
        });
    });

    // Position a fixed dropdown panel below its trigger button
    function positionHnDropdown(btn, panel) {
        var rect = btn.getBoundingClientRect();
        var panelWidth = Math.max(panel.offsetWidth || 280, 280);
        var top = rect.bottom + 4;
        var left = rect.left;

        // Prevent overflow off right edge
        if (left + panelWidth > window.innerWidth - 8) {
            left = window.innerWidth - panelWidth - 8;
        }
        // Prevent overflow off left edge
        if (left < 8) left = 8;

        panel.style.top  = top  + 'px';
        panel.style.left = left + 'px';
    }

    function closeAllHnDropdowns() {
        document.querySelectorAll('.hn-dropdown.open').forEach(function(dd) {
            dd.classList.remove('open');
            var b = dd.querySelector('.hn-section-btn');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
    }

    // Horizontal nav: close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.hn-dropdown')) {
            closeAllHnDropdowns();
        }
    });

    // Close on scroll / resize so fixed panel stays in sync
    window.addEventListener('scroll', closeAllHnDropdowns, { passive: true });
    window.addEventListener('resize', closeAllHnDropdowns, { passive: true });

    // Horizontal nav: toggle dropdown on section click
    document.querySelectorAll('.horizontal-nav-bar .hn-section-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var parentDropdown = this.closest('.hn-dropdown');
            var wasOpen = parentDropdown.classList.contains('open');

            // Close all open dropdowns first
            closeAllHnDropdowns();

            // Toggle current
            if (!wasOpen) {
                parentDropdown.classList.add('open');
                this.setAttribute('aria-expanded', 'true');
                // Position the panel using fixed coords
                var panel = parentDropdown.querySelector('.hn-dropdown-panel');
                if (panel) positionHnDropdown(this, panel);
            }
        });

        // Keyboard: Enter/Space toggle
        btn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
            // Escape to close
            if (e.key === 'Escape') {
                closeAllHnDropdowns();
            }
        });
    });

    // Mark parent .hn-dropdown as active if it contains an active link
    // (panel stays CLOSED — user must click to open)
    document.querySelectorAll('.horizontal-nav-bar .sidebar-link.active').forEach(function(activeLink) {
        // Mark the dropdown button as "has-active" so CSS can style it
        var dropdown = activeLink.closest('.hn-dropdown');
        if (dropdown) {
            dropdown.classList.add('has-active');
            var activeBtn = dropdown.querySelector('.hn-section-btn');
            if (activeBtn) activeBtn.classList.add('has-active');
        }
        // Expand inner Bootstrap collapse so the active item is visible when panel opens
        var innerCollapse = activeLink.closest('.collapse');
        if (innerCollapse && !innerCollapse.classList.contains('show')) {
            innerCollapse.classList.add('show');
            // Update toggle aria-expanded for the collapse trigger
            var collapseToggle = document.querySelector('[data-bs-target="#' + innerCollapse.id + '"]');
            if (collapseToggle) collapseToggle.setAttribute('aria-expanded', 'true');
        }
    });
});
</script>

<!--  Horizontal Nav End -->