<style>
/* Dual sidebar shell — mini-nav + white panel side by side (reference layout) */
.side-mini-panel.sidebar-google-style {
    background: #ffffff !important;
    border-right: 1px solid #e5e7eb !important;
}

.side-mini-panel.sidebar-google-style .iconbar {
    min-height: 0 !important;
}

.side-mini-panel.sidebar-google-style .iconbar > .flex-fill.d-flex.flex-column {
    flex-direction: row !important;
    align-items: stretch !important;
    min-height: 0 !important;
    height: 100% !important;
}

.side-mini-panel.sidebar-google-style .iconbar .mini-nav {
    flex: 0 0 90px !important;
    width: 90px !important;
    min-width: 90px !important;
    max-width: 90px !important;
    height: 100% !important;
    border-right: 1px solid #e5e7eb !important;
}

.side-mini-panel.sidebar-google-style .iconbar .sidebarmenu {
    flex: 1 1 auto !important;
    min-width: 0 !important;
    width: auto !important;
    max-width: 300px !important;
    background: #ffffff !important;
    border-right: 1px solid #e5e7eb !important;
    overflow: hidden !important;
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    min-height: 0 !important;
}

/* Fly-out panels — static in-column, not absolute blue overlay */
.side-mini-panel.sidebar-google-style .sidebarmenu .sidebar-nav,
.side-mini-panel.sidebar-google-style .sidebarmenu nav.sidebar-panel-menu {
    position: relative !important;
    top: auto !important;
    left: auto !important;
    margin-top: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    height: 100% !important;
    min-height: 0 !important;
    background: #ffffff !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
    display: none !important;
    overflow: hidden !important;
}

.side-mini-panel.sidebar-google-style .sidebarmenu .sidebar-nav.d-block,
.side-mini-panel.sidebar-google-style .sidebarmenu nav.sidebar-panel-menu.d-block {
    display: flex !important;
    flex-direction: column !important;
}

.side-mini-panel.sidebar-google-style .sidebarmenu .sidebar-nav[style*="display: block"],
.side-mini-panel.sidebar-google-style .sidebarmenu nav.sidebar-panel-menu[style*="display: block"] {
    display: flex !important;
    flex-direction: column !important;
}

.side-mini-panel.sidebar-google-style .sidebarmenu .sidebar-panel-menu .simplebar-mask,
.side-mini-panel.sidebar-google-style .sidebarmenu .sidebar-panel-menu .simplebar-content-wrapper {
    height: 100% !important;
}

/* Collapsed: only mini rail visible */
@media (min-width: 992px) {
    body[data-sidebartype="mini-sidebar"] .side-mini-panel.sidebar-google-style .iconbar .sidebarmenu {
        display: none !important;
        flex: 0 0 0 !important;
        width: 0 !important;
        min-width: 0 !important;
        max-width: 0 !important;
        border: none !important;
        overflow: hidden !important;
    }

    html[data-layout="vertical"] body[data-sidebartype="full"] .side-mini-panel.sidebar-google-style {
        width: 370px !important;
        max-width: 370px !important;
    }

    html[data-layout="vertical"] body[data-sidebartype="mini-sidebar"] .side-mini-panel.sidebar-google-style {
        width: 90px !important;
        max-width: 90px !important;
    }

    html[data-layout="vertical"] body[data-sidebartype="full"] .page-wrapper {
        margin-left: 370px !important;
    }

    html[data-layout="vertical"] body[data-sidebartype="mini-sidebar"] .page-wrapper {
        margin-left: 90px !important;
    }
}

@media (min-width: 1300px) {
    html[data-layout="vertical"] body[data-sidebartype="full"] .page-wrapper {
        margin-left: 370px !important;
    }

    html[data-layout="vertical"] body[data-sidebartype="mini-sidebar"] .page-wrapper {
        margin-left: 90px !important;
    }
}
</style>
