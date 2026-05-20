{{-- Mess module: smooth scroll + reliable data refresh after long sessions (view-only). --}}
@once
<style>
@media screen {
    body.admin-mess-module #main-wrapper {
        max-height: none;
    }

    body.admin-mess-module .page-wrapper {
        overflow-x: hidden;
        overflow-y: visible;
    }

    /* Keep rounded cards; only clip on X so sticky/scroll chaining works vertically */
    body.admin-mess-module .card.overflow-hidden {
        overflow-x: hidden;
        overflow-y: visible;
    }

    /* Focused table viewport: enough rows visible without trapping page scroll */
    body.admin-mess-module .ssr-table-scroller,
    body.admin-mess-module .mess-items-report-scroll,
    body.admin-mess-module .stock-purchase-table-wrapper,
    body.admin-mess-module .psq-scroll-wrapper,
    body.admin-mess-module .stock-balance-table-body-scroll,
    body.admin-mess-module .print-slip-section .cw-slip-table-body-scroll,
    body.admin-mess-module .print-slip-section .cw-slip-table-scroll,
    body.admin-mess-module .table-responsive[tabindex="0"] {
        max-height: min(72vh, calc(100dvh - 12rem)) !important;
        overflow-x: auto !important;
        overflow-y: auto !important;
        overscroll-behavior-x: contain;
        overscroll-behavior-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    body.admin-mess-module .stock-balance-report .stock-balance-table-split {
        max-height: min(72vh, calc(100dvh - 12rem)) !important;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    body.admin-mess-module .stock-balance-report .stock-balance-table-body-scroll {
        flex: 1 1 auto;
        min-height: 0;
    }

    body.admin-mess-module .print-slip-section .cw-slip-table-split {
        max-height: min(68vh, calc(100dvh - 14rem)) !important;
        display: flex;
        flex-direction: column;
    }

    body.admin-mess-module .print-slip-section .cw-slip-table-head-wrap {
        flex: 0 0 auto;
        overflow: hidden;
    }

    body.admin-mess-module .print-slip-section .cw-slip-table-body-scroll {
        flex: 1 1 auto;
        min-height: 0;
    }

    body.admin-mess-module .mess-dt-stale-hint {
        display: none;
    }

    body.admin-mess-module.mess-dt-stale .mess-dt-stale-hint {
        display: flex !important;
    }

    body.admin-mess-module .mess-dt-stale-hint {
        position: sticky;
        top: calc(122px + 0.5rem);
        z-index: 1025;
    }
}
</style>
@endonce
<script>
(function () {
    'use strict';
    if (!document.body.classList.contains('admin-mess-module')) {
        return;
    }

    var lastHiddenAt = 0;
    var idleReloadMs = 3 * 60 * 1000;

    function unlockBodyScroll() {
        if (document.querySelector('.modal.show')) {
            return;
        }
        if (window.innerWidth < 992 && document.body.classList.contains('sidebar-open')) {
            return;
        }
        if (document.body.style.overflow === 'hidden') {
            document.body.style.overflow = '';
        }
        document.body.style.paddingRight = '';
    }

    function reloadMessDataTables() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
            return;
        }
        var $ = window.jQuery;
        var reloaded = false;
        (window.messMasterDataTableRegistry || []).forEach(function (api) {
            if (api && typeof api.ajax === 'function' && typeof api.ajax.reload === 'function') {
                api.ajax.reload(null, false);
                reloaded = true;
            }
        });
        $('.dataTable').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                return;
            }
            var api = $(this).DataTable();
            if (api && typeof api.ajax === 'function' && typeof api.ajax.reload === 'function') {
                api.ajax.reload(null, false);
                reloaded = true;
            }
        });
        if (reloaded) {
            document.body.classList.remove('mess-dt-stale');
        }
    }

    function showStaleHint() {
        document.body.classList.add('mess-dt-stale');
    }

    function maybeRefreshAfterIdle() {
        if (lastHiddenAt && Date.now() - lastHiddenAt >= idleReloadMs) {
            showStaleHint();
            reloadMessDataTables();
        }
        unlockBodyScroll();
    }

    document.addEventListener('hidden.bs.modal', unlockBodyScroll);
    window.addEventListener('resize', unlockBodyScroll);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            lastHiddenAt = Date.now();
            return;
        }
        maybeRefreshAfterIdle();
    });
    window.addEventListener('pageshow', function (e) {
        unlockBodyScroll();
        if (e.persisted) {
            showStaleHint();
            reloadMessDataTables();
        }
    });
    document.addEventListener('DOMContentLoaded', function () {
        unlockBodyScroll();
        var btn = document.getElementById('messDtStaleRefreshBtn');
        if (btn) {
            btn.addEventListener('click', function () {
                reloadMessDataTables();
                if (window.location.search.indexOf('refresh=') === -1) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('refresh', '1');
                    window.location.href = url.toString();
                    return;
                }
                window.location.reload();
            });
        }
    });

    function isVerticalScrollBox(el) {
        if (!el || el === document.body || el === document.documentElement) {
            return false;
        }
        if (el.closest('.modal')) {
            return false;
        }
        var style = window.getComputedStyle(el);
        if (!/(auto|scroll|overlay)/.test(style.overflowY)) {
            return false;
        }
        return el.scrollHeight > el.clientHeight + 2;
    }

    document.addEventListener('wheel', function (e) {
        var el = e.target;
        while (el && el !== document.documentElement) {
            if (el.classList && el.classList.contains('modal')) {
                return;
            }
            if (isVerticalScrollBox(el)) {
                var dy = e.deltaY;
                if (dy === 0) {
                    return;
                }
                var atTop = el.scrollTop <= 0;
                var atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 1;
                if ((dy < 0 && atTop) || (dy > 0 && atBottom)) {
                    return;
                }
                return;
            }
            el = el.parentElement;
        }
    }, { passive: true });
})();
</script>
