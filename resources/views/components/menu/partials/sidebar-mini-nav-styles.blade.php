<style>
/* Mini-nav rail — override legacy styles.css (#ECEDF8, left-aligned items, ::before blobs) */
#sidebar-home .sidebar-google-style.side-mini-panel,
#sidebar-setup .sidebar-google-style.side-mini-panel,
#sidebar-communications .sidebar-google-style.side-mini-panel {
    width: auto !important;
    background: transparent !important;
}

#sidebar-home .sidebar-google-style .mini-nav,
#sidebar-setup .sidebar-google-style .mini-nav,
#sidebar-communications .sidebar-google-style .mini-nav,
.side-mini-panel.sidebar-google-style .mini-nav {
    background: #ffffff !important;
    border-right: 1px solid #e5e7eb !important;
    border-radius: 0 !important;
    margin-top: 0 !important;
    padding: 0.65rem 0.25rem 1rem !important;
    width: 90px !important;
    min-width: 90px !important;
    height: calc(100vh - 72px) !important;
    z-index: 99 !important;
    position: relative !important;
    flex-shrink: 0;
}

#sidebar-home .sidebar-google-style .iconbar,
#sidebar-setup .sidebar-google-style .iconbar,
#sidebar-communications .sidebar-google-style .iconbar {
    background: #ffffff !important;
}

/* Toggle — gray rounded box + Close / Open label */
#sidebar-home .sidebar-google-hamburger,
#sidebar-setup .sidebar-google-hamburger,
#sidebar-communications .sidebar-google-hamburger {
    padding: 0 0.35rem 0.75rem !important;
    margin: 0 !important;
}

.sidebar-mini-toggle {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.35rem !important;
    width: 100% !important;
    text-decoration: none !important;
    color: inherit !important;
}

.sidebar-mini-toggle-box {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 42px !important;
    height: 42px !important;
    background: #f3f4f6 !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 0.25rem !important;
}

.sidebar-mini-toggle-box .material-icons {
    font-size: 22px !important;
    color: #4b5563 !important;
    transition: transform 0.25s ease !important;
}

.sidebar-mini-toggle-label {
    font-size: 0.6875rem !important;
    line-height: 1.1 !important;
    color: #6b7280 !important;
    font-weight: 500 !important;
}

body[data-sidebartype="mini-sidebar"] .sidebar-mini-toggle-text-close {
    display: none !important;
}

.sidebar-mini-toggle-text-open {
    display: none !important;
}

body[data-sidebartype="mini-sidebar"] .sidebar-mini-toggle-text-open {
    display: inline !important;
}

body[data-sidebartype="full"] .sidebar-mini-toggle-text-close {
    display: inline !important;
}

body[data-sidebartype="full"] .sidebar-mini-toggle-text-open {
    display: none !important;
}

/* Nav list */
#sidebar-home .sidebar-google-style .mini-nav ul.mini-nav-ul,
#sidebar-setup .sidebar-google-style .mini-nav ul.mini-nav-ul,
#sidebar-communications .sidebar-google-style .mini-nav ul.mini-nav-ul,
.side-mini-panel.sidebar-google-style .mini-nav ul.mini-nav-ul {
    padding: 0 !important;
    margin: 0 !important;
    list-style: none !important;
    height: auto !important;
}

#sidebar-home .sidebar-google-style .mini-nav .mini-nav-item,
#sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item,
#sidebar-communications .sidebar-google-style .mini-nav .mini-nav-item,
.side-mini-panel.sidebar-google-style .mini-nav .mini-nav-item {
    list-style: none !important;
    display: block !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

.side-mini-panel .mini-nav .mini-nav-item > a,
.side-mini-panel.sidebar-google-style .mini-nav .mini-nav-item > a {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.35rem !important;
    width: 100% !important;
    height: auto !important;
    min-height: 68px !important;
    padding: 0.45rem 0.25rem !important;
    margin: 0.1rem 0 !important;
    background: transparent !important;
    text-align: center !important;
    color: #374151 !important;
    position: relative !important;
    border: none !important;
    box-shadow: none !important;
    transition: background-color 0.15s ease, color 0.15s ease !important;
}

.side-mini-panel .mini-nav .mini-nav-item > a:hover {
    background: transparent !important;
}

.side-mini-panel .mini-nav .mini-nav-item.selected > a::before,
.side-mini-panel .mini-nav .mini-nav-item.selected > span::before {
    display: none !important;
    content: none !important;
}

.sidebar-google-icon-wrap {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    margin: 0 auto !important;
    border-radius: 0.25rem !important;
    background: transparent !important;
    transition: background-color 0.15s ease !important;
    flex-shrink: 0 !important;
}

.side-mini-panel .mini-nav .mini-nav-item > a .material-icons,
.side-mini-panel .mini-nav .mini-nav-item > a .menu-icon {
    font-size: 22px !important;
    color: #6b7280 !important;
    opacity: 1 !important;
    z-index: 2 !important;
    position: relative !important;
}

.sidebar-google-label,
.side-mini-panel .mini-nav .mini-nav-item .mini-nav-title {
    font-size: 0.6875rem !important;
    font-weight: 500 !important;
    color: #6b7280 !important;
    line-height: 1.15 !important;
    text-align: center !important;
    max-width: 82px;
    white-space: normal !important;
    word-break: break-word;
    display: block !important;
}

/* Active category */
.side-mini-panel .mini-nav .mini-nav-item.selected > a .sidebar-google-icon-wrap {
    background: #e7f1ff !important;
    border-radius: 0.25rem !important;
    padding: 0 !important;
    transform: none !important;
    box-shadow: none !important;
    width: 40px !important;
    height: 40px !important;
    margin: 0 auto !important;
}

.side-mini-panel .mini-nav .mini-nav-item.selected > a .material-icons {
    color: #1d4ed8 !important;
}

.side-mini-panel .mini-nav .mini-nav-item.selected > a .sidebar-google-label,
.side-mini-panel .mini-nav .mini-nav-item.selected > a .mini-nav-title {
    color: #1d4ed8 !important;
    font-weight: 600 !important;
    font-size: 0.6875rem !important;
}

.side-mini-panel .mini-nav .mini-nav-item > a:hover .sidebar-google-icon-wrap {
    background: #f3f4f6 !important;
}

.side-mini-panel .mini-nav .mini-nav-item.selected > a:hover .sidebar-google-icon-wrap {
    background: #e7f1ff !important;
}

/* Beat legacy styles.css (.mini-nav #ECEDF8, margin-top: 110px, left-aligned links) */
.side-mini-panel.sidebar-google-style .mini-nav {
    margin-top: 0 !important;
    background: #ffffff !important;
    border-radius: 0 !important;
    padding: 0.65rem 0.25rem 1rem !important;
}

.side-mini-panel.sidebar-google-style .mini-nav ul.mini-nav-ul {
    height: auto !important;
}

.side-mini-panel.sidebar-google-style .mini-nav .mini-nav-item > a {
    background: transparent !important;
    align-items: center !important;
    justify-content: center !important;
    padding-left: 0 !important;
    height: auto !important;
    margin-bottom: 0 !important;
    font-size: inherit !important;
    z-index: auto !important;
}

.side-mini-panel.sidebar-google-style .mini-nav .mini-nav-item.selected > a:before,
.side-mini-panel.sidebar-google-style .mini-nav .mini-nav-item.selected > span:before {
    display: none !important;
    content: none !important;
    width: 0 !important;
    height: 0 !important;
}

.side-mini-panel.sidebar-google-style .mini-nav .mini-nav-item.selected > a .material-icons {
    color: #1d4ed8 !important;
}

.side-mini-panel.sidebar-google-style .mini-nav .mini-nav-item.selected > a .mini-nav-title {
    font-size: 0.6875rem !important;
    font-weight: 600 !important;
    color: #1d4ed8 !important;
}

.side-mini-panel.sidebar-google-style .mini-nav .mini-nav-item > a:focus-visible {
    outline: 2px solid rgba(29, 78, 216, 0.35) !important;
    outline-offset: 2px !important;
}
</style>
