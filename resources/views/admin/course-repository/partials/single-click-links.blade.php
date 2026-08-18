{{-- Reliable single-click navigation for Course Repository links.
     The links are plain <a href> anchors, but were observed to need a
     double-click to open because a bubble-phase handler swallows the first
     click. This handler runs in the CAPTURE phase (before the swallower) and
     calls stopPropagation(), so the swallower never runs and a single click is
     enough. For plain same-tab links we also force the navigation; for links the
     browser must handle itself (new tab / download / mail / tel) we deliberately
     do NOT preventDefault, so their native action fires on that same single
     click. Included ONLY from Course Repository views, so it is inherently scoped
     to the module; delegated on document, so AJAX-loaded rows are covered too. --}}
<script>
(function () {
    if (window.__cruSingleClickLinks) return;
    window.__cruSingleClickLinks = true;

    document.addEventListener('click', function (e) {
        // Only plain primary clicks (let Ctrl/Cmd/Shift open new tabs as usual).
        if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

        var link = e.target.closest ? e.target.closest('a[href]') : null;
        if (!link) return;

        var href = (link.getAttribute('href') || '').trim();
        var lower = href.toLowerCase();

        // Not a real navigation target — leave these entirely alone so their own
        // JS runs (in-page anchors, javascript: links, Bootstrap modal/tab/dropdown triggers).
        if (href === '' || href.charAt(0) === '#' ||
            lower.indexOf('javascript:') === 0) return;
        if (link.hasAttribute('data-bs-toggle')) return;

        // Block the bubble-phase handler that would otherwise swallow this first click.
        e.stopPropagation();

        // Links the browser must handle natively — new tab (View / external video),
        // download (file / video), mail, tel. Propagation is already stopped, so NOT
        // calling preventDefault lets the native action fire on this single click.
        if ((link.target && link.target !== '' && link.target !== '_self') ||
            link.hasAttribute('download') ||
            lower.indexOf('mailto:') === 0 ||
            lower.indexOf('tel:') === 0) {
            return;
        }

        // Plain same-tab navigation (and same-origin download routes that respond
        // with Content-Disposition: attachment): force it on this single click.
        e.preventDefault();
        window.location.href = link.href;
    }, true);
})();
</script>
