{{-- Force reliable single-click navigation for Course Repository links.
     The links are plain <a href> anchors, but were observed to need a
     double-click to open. Handling the click in the CAPTURE phase runs before
     anything that could swallow the first click, so a single click always
     navigates. Scoped to Course Repository content only (.cr-admin / .cru-page)
     and delegated, so AJAX-loaded rows are covered too. --}}
<script>
(function () {
    if (window.__cruSingleClickLinks) return;
    window.__cruSingleClickLinks = true;

    document.addEventListener('click', function (e) {
        // Only plain primary clicks (let Ctrl/Cmd/Shift open new tabs as usual).
        if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

        var link = e.target.closest ? e.target.closest('a[href]') : null;
        if (!link) return;

        // Scope to Course Repository pages only.
        if (!link.closest('.cr-admin, .cru-page')) return;

        var href = (link.getAttribute('href') || '').trim();
        var lower = href.toLowerCase();

        // Skip non-navigational / JS-handled anchors (edit, delete, modal triggers, anchors).
        if (href === '' || href.charAt(0) === '#' ||
            lower.indexOf('javascript:') === 0 ||
            lower.indexOf('mailto:') === 0 ||
            lower.indexOf('tel:') === 0) return;
        if (link.hasAttribute('data-bs-toggle')) return;

        // Respect new-tab and download links (View / Download / external video).
        if (link.target && link.target !== '' && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;

        // Force the navigation on this single click.
        e.preventDefault();
        e.stopPropagation();
        window.location.href = link.href;
    }, true);
})();
</script>
