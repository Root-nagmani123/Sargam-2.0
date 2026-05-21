/**
 * Central navigation state: tab persistence, sidebar sync, back/forward, breadcrumb stack.
 */
(function (global) {
    'use strict';

    const KEYS = {
        mainTab: 'activeMainTab',
        categoryId: 'sargam_active_category_id',
        groupId: 'sargam_active_group_id',
        navStack: 'sargam_breadcrumb_back_stack_v1',
        lastUrlPrefix: 'sargam_last_url_',
    };

    function lastUrlStorageKey(tabHash) {
        return KEYS.lastUrlPrefix + (tabHash || '#home');
    }

    function normalizeUrlForCompare(url) {
        try {
            var u = new URL(url, global.location.origin);
            return u.pathname.replace(/\/+$/, '') + (u.search || '');
        } catch (e) {
            return '';
        }
    }

    function getActiveTabHash() {
        return global.SARGAM_ACTIVE_NAV_TAB
            || (function () {
                try {
                    var saved = localStorage.getItem(KEYS.mainTab);
                    return saved && saved.charAt(0) === '#' ? saved : '#' + (saved || 'home');
                } catch (e) {
                    return '#home';
                }
            })()
            || '#home';
    }

    /** Remember full page URL for this header tab (Home, Setup, etc.) */
    function recordLastVisitedForTab(tabHash, url) {
        if (!tabHash || !url) return;
        try {
            if (!isSameOrigin(url)) return;
            sessionStorage.setItem(lastUrlStorageKey(tabHash), url.split('#')[0]);
        } catch (e) { /* ignore */ }
    }

    function getLastVisitedUrl(tabHash) {
        try {
            var url = sessionStorage.getItem(lastUrlStorageKey(tabHash));
            if (url && isSameOrigin(url)) {
                return url;
            }
        } catch (e) { /* ignore */ }
        return null;
    }

    /**
     * URL to open when switching to a tab whose pane is empty on this page.
     * Priority: last visited for tab → category landing → dashboard (home only).
     */
    function getNavigateUrlForTab(tabHash) {
        var last = getLastVisitedUrl(tabHash);
        if (last) {
            return last;
        }
        var landings = global.SARGAM_CATEGORY_LANDING_URLS || {};
        if (landings[tabHash]) {
            return landings[tabHash];
        }
        if (tabHash === '#home' && global.SARGAM_DASHBOARD_URL) {
            return global.SARGAM_DASHBOARD_URL;
        }
        return null;
    }

    function navigateToTabUrl(tabHash) {
        var url = getNavigateUrlForTab(tabHash);
        if (!url) {
            return false;
        }
        var cur = normalizeUrlForCompare(global.location.href);
        var dest = normalizeUrlForCompare(url);
        if (cur === dest) {
            return false;
        }
        global.location.assign(url);
        return true;
    }

    function resolveCategoryIdForTab(tabHash) {
        var link = document.querySelector(
            '.sidebar-category-link[href="' + tabHash + '"][data-id]'
        );
        return link ? link.getAttribute('data-id') : null;
    }

    /**
     * Header category tab click.
     * Home → always open Dashboard. Other tabs → sidebar only; main content unchanged.
     */
    function handleSargamCategoryTabClick(link) {
        var target = link.getAttribute('href');
        if (!target || target === '#') {
            return;
        }

        var fromTab = getActiveTabHash();
        recordLastVisitedForTab(fromTab, global.location.href);

        if (target === '#home') {
            var homeUrl = global.SARGAM_DASHBOARD_URL || getNavigateUrlForTab('#home');
            var categoryId = link.getAttribute('data-id') || resolveCategoryIdForTab(target);
            persistTabState('#home', categoryId, null);

            if (homeUrl) {
                var cur = normalizeUrlForCompare(global.location.href);
                var dest = normalizeUrlForCompare(homeUrl);
                if (cur !== dest) {
                    global.location.assign(homeUrl);
                    return;
                }
            }

            if (typeof global.showMainNavPane === 'function') {
                global.showMainNavPane('#home');
            }
            updateCategoryLinkActiveState('#home');
            if (categoryId && typeof global.loadSidebarGroupsForCategory === 'function') {
                global.loadSidebarGroupsForCategory(categoryId, loadLastVisitedGroupForTab.bind(null, '#home'));
            }
            return;
        }

        if (typeof global.showMainNavPane === 'function') {
            global.showMainNavPane(target, { keepPageContent: true });
        } else {
            ensureVisibleContentPane();
        }

        updateCategoryLinkActiveState(target);

        var categoryId = link.getAttribute('data-id') || resolveCategoryIdForTab(target);
        persistTabState(target, categoryId, null);

        if (categoryId && typeof global.loadSidebarGroupsForCategory === 'function') {
            global.loadSidebarGroupsForCategory(categoryId, function () {
                if (typeof global.clearSidebarGroupSelection === 'function') {
                    global.clearSidebarGroupSelection();
                }
            });
        }
    }

    function updateCategoryLinkActiveState(target) {
        document.querySelectorAll('.sidebar-category-link').forEach(function (l) {
            var href = l.getAttribute('href');
            if (href === target) {
                l.classList.add('active');
                l.setAttribute('aria-selected', 'true');
            } else {
                l.classList.remove('active');
                l.setAttribute('aria-selected', 'false');
            }
        });
    }

    function loadLastVisitedGroupForTab(tabHash) {
        var savedGroup = getLastVisitedGroupId(tabHash);
        if (!savedGroup || typeof global.loadSidebarMenusForGroup !== 'function') {
            return;
        }
        if (typeof global.jQuery !== 'undefined') {
            var $gl = global.jQuery('.sidebar-group-link[data-id="' + savedGroup + '"]');
            if ($gl.length) {
                $gl.trigger('click');
                return;
            }
        }
        global.loadSidebarMenusForGroup(savedGroup);
    }

    function initSidebarLinkMemory() {
        document.addEventListener('click', function (e) {
            var a = e.target.closest('#sidebarnav a.sidebar-link[href]');
            if (!a) {
                return;
            }
            var href = a.getAttribute('href') || '';
            if (!href || href.indexOf('javascript') === 0) {
                return;
            }
            if (a.getAttribute('data-bs-toggle') !== 'collapse') {
                document.querySelectorAll('#sidebarnav .sidebar-link.active').forEach(function (link) {
                    link.classList.remove('active');
                });
                a.classList.add('active');
            }
            recordLastVisitedForTab(getActiveTabHash(), global.location.href);
        }, true);
    }

    function ensureVisibleContentPane() {
        var panes = document.querySelectorAll('#mainNavbarContent .tab-pane');
        if (!panes.length) {
            return;
        }
        var visible = null;
        panes.forEach(function (p) {
            if (p.children.length > 0 && p.textContent.trim().length > 0) {
                visible = p;
            }
        });
        if (!visible) {
            visible = document.getElementById('home');
        }
        if (!visible) {
            return;
        }
        panes.forEach(function (p) {
            if (p === visible) {
                p.classList.add('show', 'active');
            } else {
                p.classList.remove('show', 'active');
            }
        });
    }

    function initCategoryTabClicks() {
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.sidebar-category-link');
            if (!link || !link.getAttribute('href') || link.getAttribute('href').charAt(0) !== '#') {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            handleSargamCategoryTabClick(link);
        }, true);
    }

    function recordCurrentPageForActiveTab() {
        var tab = getActiveTabHash();
        recordLastVisitedForTab(tab, global.location.href);
        if (global.SARGAM_ACTIVE_GROUP_ID != null) {
            persistTabState(tab, global.SARGAM_ACTIVE_CATEGORY_ID, global.SARGAM_ACTIVE_GROUP_ID);
        }
    }

    function normalizePath(path) {
        if (!path) return '';
        try {
            if (path.indexOf('http') === 0) {
                path = new URL(path).pathname;
            }
        } catch (e) { /* keep path */ }
        return path.replace(/^\/+/, '').replace(/\/+$/, '').toLowerCase();
    }

    function currentPagePath() {
        return normalizePath(window.location.pathname);
    }

    function currentRouteName() {
        return global.SARGAM_CURRENT_ROUTE_NAME || '';
    }

    function persistTabState(tabHash, categoryId, groupId) {
        try {
            if (tabHash) localStorage.setItem(KEYS.mainTab, tabHash);
            if (categoryId != null) localStorage.setItem(KEYS.categoryId, String(categoryId));
            if (groupId != null) {
                localStorage.setItem(KEYS.groupId, String(groupId));
                if (tabHash) {
                    sessionStorage.setItem(KEYS.lastUrlPrefix + 'group_' + tabHash, String(groupId));
                }
            }
        } catch (e) { /* storage blocked */ }
    }

    function getLastVisitedGroupId(tabHash) {
        try {
            return sessionStorage.getItem(KEYS.lastUrlPrefix + 'group_' + tabHash);
        } catch (e) {
            return null;
        }
    }

    function isSameOrigin(url) {
        try {
            return new URL(url).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function initBreadcrumbBackStack() {
        const currentUrl = window.location.href;
        try {
            if (!isSameOrigin(currentUrl)) return;
            const raw = sessionStorage.getItem(KEYS.navStack);
            let stack = [];
            try {
                stack = JSON.parse(raw || '[]');
            } catch (e) {
                stack = [];
            }
            if (!Array.isArray(stack)) stack = [];
            const last = stack[stack.length - 1];
            if (last !== currentUrl) {
                const deduped = stack.filter(function (u) { return u !== currentUrl; });
                deduped.push(currentUrl);
                sessionStorage.setItem(KEYS.navStack, JSON.stringify(deduped.slice(-20)));
            }
        } catch (e) { /* ignore */ }
    }

    function pathMatchesLink(menuPath, current) {
        if (!menuPath || !current) return false;
        return normalizePath(menuPath) === normalizePath(current);
    }

    function markActiveSidebarLinks() {
        if (typeof global.jQuery === 'undefined') return;
        var $ = global.jQuery;
        var currentFull = normalizeUrlForCompare(window.location.href);
        var currentPath = currentPagePath();

        $('#sidebarnav .sidebar-link').removeClass('active');

        var $best = null;
        var bestPathLen = -1;

        $('#sidebarnav .sidebar-link[href]').each(function () {
            if (this.getAttribute('data-bs-toggle') === 'collapse') {
                return;
            }
            var rawHref = (this.getAttribute('href') || '').trim();
            if (!rawHref || rawHref === '#' || rawHref.indexOf('javascript') === 0) {
                return;
            }

            var linkFull = '';
            var linkPath = '';
            try {
                var parsed = new URL(rawHref, global.location.origin);
                linkFull = normalizeUrlForCompare(parsed.href);
                linkPath = normalizePath(parsed.pathname);
            } catch (e) {
                linkFull = normalizeUrlForCompare(rawHref);
                linkPath = normalizePath(rawHref);
            }

            if (linkFull !== currentFull && !pathMatchesLink(linkPath, currentPath)) {
                return;
            }

            if (linkPath.length > bestPathLen) {
                bestPathLen = linkPath.length;
                $best = $(this);
            }
        });

        if ($best && $best.length) {
            $best.addClass('active');
            var $collapse = $best.closest('.collapse');
            if ($collapse.length) {
                $collapse.addClass('show');
                var toggle = document.querySelector('[href="#' + $collapse.attr('id') + '"]');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.classList.remove('collapsed');
                }
            }
        }

        var groupId = global.SARGAM_ACTIVE_GROUP_ID;
        if (groupId && typeof global.selectSidebarGroupVisual === 'function') {
            global.selectSidebarGroupVisual(groupId);
        }
    }

    function syncNavFromServer() {
        var routeTab = global.SARGAM_ACTIVE_NAV_TAB;
        if (routeTab && typeof global.showMainNavPane === 'function') {
            global.showMainNavPane(routeTab);
        }
        try {
            if (routeTab) localStorage.setItem(KEYS.mainTab, routeTab);
            if (global.SARGAM_ACTIVE_CATEGORY_ID != null) {
                localStorage.setItem(KEYS.categoryId, String(global.SARGAM_ACTIVE_CATEGORY_ID));
            }
            if (global.SARGAM_ACTIVE_GROUP_ID != null) {
                localStorage.setItem(KEYS.groupId, String(global.SARGAM_ACTIVE_GROUP_ID));
            }
        } catch (e) { /* ignore */ }
        markActiveSidebarLinks();
    }

    function initPageshowHandler() {
        window.addEventListener('pageshow', function (event) {
            initBreadcrumbBackStack();
            syncNavFromServer();
            markActiveSidebarLinks();

            if (event.persisted && typeof global.jQuery !== 'undefined') {
                var categoryId = global.SARGAM_ACTIVE_CATEGORY_ID;
                var groupId = global.SARGAM_ACTIVE_GROUP_ID;
                if (categoryId && typeof global.loadSidebarGroupsForCategory === 'function') {
                    global.loadSidebarGroupsForCategory(categoryId, function () {
                        if (groupId && typeof global.loadSidebarMenusForGroup === 'function') {
                            global.loadSidebarMenusForGroup(groupId);
                        }
                    });
                }
            }
        });
    }

    global.SargamNavState = {
        KEYS: KEYS,
        normalizePath: normalizePath,
        currentPagePath: currentPagePath,
        currentRouteName: currentRouteName,
        persistTabState: persistTabState,
        markActiveSidebarLinks: markActiveSidebarLinks,
        syncNavFromServer: syncNavFromServer,
        initBreadcrumbBackStack: initBreadcrumbBackStack,
        initPageshowHandler: initPageshowHandler,
        pathMatchesLink: pathMatchesLink,
        recordLastVisitedForTab: recordLastVisitedForTab,
        getLastVisitedUrl: getLastVisitedUrl,
        getNavigateUrlForTab: getNavigateUrlForTab,
        navigateToTabUrl: navigateToTabUrl,
        getActiveTabHash: getActiveTabHash,
        recordCurrentPageForActiveTab: recordCurrentPageForActiveTab,
        handleSargamCategoryTabClick: handleSargamCategoryTabClick,
        initCategoryTabClicks: initCategoryTabClicks,
        initSidebarLinkMemory: initSidebarLinkMemory,
        ensureVisibleContentPane: ensureVisibleContentPane,
        getLastVisitedGroupId: getLastVisitedGroupId,
    };

    initBreadcrumbBackStack();
    initPageshowHandler();
    recordCurrentPageForActiveTab();

    window.addEventListener('beforeunload', recordCurrentPageForActiveTab);

    document.addEventListener('DOMContentLoaded', function () {
        recordCurrentPageForActiveTab();
        initCategoryTabClicks();
        initSidebarLinkMemory();
        syncNavFromServer();
    });
})(typeof window !== 'undefined' ? window : this);
