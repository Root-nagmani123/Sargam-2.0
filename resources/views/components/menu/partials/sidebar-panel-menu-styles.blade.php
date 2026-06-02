<style>
/* Override legacy styles.css sidebar (blue panel, white text, pill hovers) */
#sidebar-home .side-mini-panel .sidebarmenu nav.sidebar-panel-menu,
#sidebar-setup .side-mini-panel .sidebarmenu nav.sidebar-panel-menu,
#sidebar-communications .side-mini-panel .sidebarmenu nav.sidebar-panel-menu,
.side-mini-panel.sidebar-google-style .sidebarmenu nav.sidebar-panel-menu {
    background: #ffffff !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin-top: 0 !important;
    top: auto !important;
    left: auto !important;
    width: 100% !important;
    height: 100% !important;
    z-index: 98;
}

.sidebar-panel-menu .sidebar-panel-menu__content {
    padding: 1rem 1rem 1.25rem 1.25rem !important;
    background: #ffffff !important;
}

.sidebar-panel-menu .sidebar-panel-menu__title {
    letter-spacing: 0.12em;
    font-size: 0.6875rem;
    font-weight: 600 !important;
    color: #9ca3af !important;
    margin-bottom: 0.875rem !important;
    padding-top: 0.25rem !important;
}

.sidebar-panel-menu .sidebar-menu {
    padding: 0;
    margin: 0;
}

.sidebar-panel-menu .sidebar-item {
    margin-bottom: 0.125rem;
}

/* Reset legacy link styles */
.sidebar-panel-menu .sidebar-nav ul .sidebar-item .sidebar-link,
.side-mini-panel .sidebarmenu nav.sidebar-panel-menu ul .sidebar-item .sidebar-link {
    display: flex !important;
    align-items: center !important;
    gap: 0.625rem !important;
    padding: 0.5rem 0.75rem !important;
    margin-bottom: 0 !important;
    font-size: 0.875rem !important;
    font-weight: 500 !important;
    line-height: 1.35 !important;
    white-space: nowrap !important;
    color: #1f2937 !important;
    background: transparent !important;
    border-radius: 0.25rem !important;
    box-shadow: none !important;
    max-width: none !important;
    width: 100% !important;
    transition: background-color 0.15s ease, color 0.15s ease !important;
}

.sidebar-panel-menu .sidebar-nav ul .sidebar-item .sidebar-link:hover,
.side-mini-panel .sidebarmenu nav.sidebar-panel-menu ul .sidebar-item .sidebar-link:hover {
    background-color: #f3f4f6 !important;
    color: #111827 !important;
    padding-left: 0.75rem !important;
    border-radius: 0.25rem !important;
    box-shadow: none !important;
}

.sidebar-panel-menu .sidebar-nav ul .sidebar-item > .sidebar-link.active,
.sidebar-panel-menu .sidebar-nav ul .sidebar-item.selected > .sidebar-link.active,
.side-mini-panel .sidebarmenu nav.sidebar-panel-menu ul .sidebar-item > .sidebar-link.active {
    background-color: #e7f1ff !important;
    color: #1d4ed8 !important;
    font-weight: 600 !important;
    border-radius: 0.25rem !important;
    box-shadow: none !important;
    padding: 0.5rem 0.75rem !important;
    max-width: none !important;
}

.sidebar-panel-menu .sidebar-link-collapse.active {
    background-color: transparent !important;
    color: #1f2937 !important;
    font-weight: 500 !important;
}

.sidebar-panel-menu .sidebar-panel-menu__icon,
.sidebar-panel-menu .sidebar-nav ul .sidebar-item .sidebar-link .material-icons.sidebar-panel-menu__icon {
    font-size: 20px !important;
    width: 20px !important;
    height: 20px !important;
    line-height: 1 !important;
    color: #6b7280 !important;
    opacity: 1 !important;
    flex-shrink: 0 !important;
    font-weight: normal !important;
}

.sidebar-panel-menu .sidebar-link.active .sidebar-panel-menu__icon,
.sidebar-panel-menu .sidebar-nav ul .sidebar-item > .sidebar-link.active .material-icons {
    color: #1d4ed8 !important;
}

.sidebar-panel-menu .sidebar-panel-menu__chevron {
    font-size: 1.25rem !important;
    color: #9ca3af !important;
    flex-shrink: 0 !important;
    margin-left: auto !important;
    transition: transform 0.2s ease, color 0.2s ease !important;
}

.sidebar-panel-menu .sidebar-link-collapse[aria-expanded="true"] .sidebar-panel-menu__chevron {
    transform: rotate(90deg);
    color: #1d4ed8 !important;
}

.sidebar-panel-menu .sidebar-link-collapse[aria-expanded="true"] {
    color: #1f2937 !important;
}

.sidebar-panel-menu .sidebar-link-collapse .hide-menu {
    font-weight: 500 !important;
    color: inherit !important;
}

/* Dashed submenu tree */
.sidebar-panel-menu .sidebar-panel-submenu-tree {
    position: relative;
    margin: 0.125rem 0 0.375rem 1.5rem !important;
    padding: 0.125rem 0 0.125rem 0.75rem !important;
    border-left: 1px dashed #cbd5e1 !important;
    list-style: none !important;
}

.sidebar-panel-menu .sidebar-panel-submenu-tree > .list-unstyled > .sidebar-item > .sidebar-link {
    padding: 0.4rem 0.65rem !important;
    font-size: 0.8125rem !important;
    font-weight: 400 !important;
    color: #374151 !important;
    border-radius: 0.25rem !important;
}

.sidebar-panel-menu .sidebar-panel-submenu-tree .sidebar-link:hover {
    background-color: #f3f4f6 !important;
    color: #111827 !important;
}

.sidebar-panel-menu .sidebar-panel-submenu-tree .sidebar-link.active {
    background-color: #e7f1ff !important;
    color: #1d4ed8 !important;
    font-weight: 600 !important;
}

.sidebar-panel-menu .collapse {
    background: transparent !important;
}

/* Kill legacy pseudo / selected decorations */
.sidebar-panel-menu .sidebar-item.selected > .sidebar-link::before,
.sidebar-panel-menu .sidebar-item > .sidebar-link::before {
    display: none !important;
    content: none !important;
}
</style>
