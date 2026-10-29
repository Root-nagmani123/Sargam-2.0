/**
 * COE question papers — read-only paper viewer (See Attachment / View … Paper).
 * Markup: resources/views/admin/coe/partials/paper_viewer_modal.blade.php.
 *
 *   CoePaperViewer.open({ url: '…/original', title: 'QP-3-015 · Counselling', subtitle: 'Original Paper · V1' });
 *   CoePaperViewer.print({ url: '…/original', title: '…' });   // administrator: print, no viewer
 *
 * The server answers { format: 'html' | 'docx', content, watermark }: an editor
 * paper as its cleaned HTML, a Word file as base64 that docx-preview lays out
 * (loaded on first use). The paper is never offered as a file.
 *
 * The same deterrents guard the paper editor (Add / Edit Original Paper,
 * Translate Question Paper) through CoePaperGuard:
 *
 *   CoePaperGuard.guard({ active: fn, shield: fn(on), copyKeys: bool });
 *   CoePaperGuard.keepClipboard(editableEl, function (html) { … insert … });
 *   el.style.backgroundImage = CoePaperGuard.watermark('Name · ID 12 · 29-10-2026 10:15');
 *
 * These are deterrents: no browser can stop a phone camera or a capture tool
 * outside it. What makes a leak traceable is the viewer's name, ID and time
 * stamped across the whole page.
 */
(function (window, document, $) {
    'use strict';

    var JSZIP = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js';
    var DOCX_PREVIEW = 'https://cdn.jsdelivr.net/npm/docx-preview@0.3.5/dist/docx-preview.min.js';

    // With Ctrl (Cmd on a Mac): save, print, view source; copy, cut, select all
    // where the paper is only shown; devtools with Shift.
    var SAVE_KEYS = ['s', 'p', 'u'];
    var COPY_KEYS = ['c', 'x', 'a'];
    var DEVTOOLS_KEYS = ['i', 'j', 'c'];

    // On <body>, printing gives a blank page (coe-admin.css).
    var NO_PRINT = 'coe-paper-guarded';

    // What the system clipboard holds after a copy inside the editor.
    var CLIPBOARD_MARK = '[Question paper text - it can only be pasted inside the paper]';

    var scripts = {};
    var el = null;
    var modal = null;
    var request = null;
    var isOpen = false;

    function loadScript(src) {
        if (!scripts[src]) {
            scripts[src] = $.Deferred(function (d) {
                var s = document.createElement('script');
                s.src = src;
                s.onload = function () { d.resolve(); };
                s.onerror = function () { delete scripts[src]; d.reject(); };
                document.head.appendChild(s);
            }).promise();
        }
        return scripts[src];
    }

    function docxPreview() {
        return (window.JSZip ? $.when() : loadScript(JSZIP)).then(function () {
            return window.docx ? $.when() : loadScript(DOCX_PREVIEW);
        });
    }

    function toBytes(base64) {
        var bin = window.atob(base64);
        var out = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) { out[i] = bin.charCodeAt(i); }
        return out.buffer;
    }

    function escapeXml(text) {
        return String(text).replace(/[<>&"']/g, function (c) {
            return { '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&apos;' }[c];
        });
    }

    // One tilted line, tiled over the whole paper (it scrolls with it).
    function watermark(text) {
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="440" height="240">' +
            '<text x="220" y="120" text-anchor="middle" dominant-baseline="middle" transform="rotate(-24 220 120)" ' +
            'font-family="Arial, sans-serif" font-size="15" font-weight="600" fill="#0b2a5b" fill-opacity="0.14">' +
            escapeXml(text) + '</text></svg>';
        return 'url("data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg) + '")';
    }

    function state(text) {
        el.state.textContent = text || '';
        el.state.hidden = !text;
    }

    function clear() {
        if (request) { request.abort(); request = null; }
        el.sheet.innerHTML = '';
        el.sheet.className = 'coe-viewer__sheet';
        el.mark.style.backgroundImage = '';
        el.stage.hidden = true;
    }

    function shield(on) {
        el.content.classList.toggle('is-shielded', on);
    }

    function render(res) {
        if (res.format === 'html') {
            el.sheet.classList.add('coe-paper-view');
            el.sheet.innerHTML = res.content;          // cleaned on the server (PaperHtmlSanitizer)
            return $.when();
        }
        return docxPreview().then(function () {
            return window.docx.renderAsync(toBytes(res.content), el.sheet, el.sheet, {
                className: 'docx',
                inWrapper: true,
                breakPages: true,
                useBase64URL: true,
                renderComments: false
            });
        });
    }

    function open(o) {
        setup();
        clear();
        shield(false);
        el.title.textContent = o.title || 'Question Paper';
        el.sub.textContent = o.subtitle || '';
        state('Loading the question paper…');
        modal.show();

        var current = request = $.ajax({ url: o.url, method: 'GET', dataType: 'json', headers: { Accept: 'application/json' } });
        current
            .then(function (res) {
                if (request !== current) { return $.Deferred().reject().promise(); }
                el.mark.style.backgroundImage = watermark(res.watermark || '');
                el.stage.hidden = false;
                return render(res);
            })
            .then(function () {
                if (request === current) { state(''); request = null; }
            }, function (xhr) {
                if (request !== current || (xhr && xhr.statusText === 'abort')) { return; }
                request = null;
                clear();
                state(xhr && xhr.status === 404
                    ? 'This question paper is not available.'
                    : 'The question paper could not be shown. Please try again.');
            });
    }

    // The page's coe-admin.css: the paper styles (.coe-paper-view) for the print copy.
    function paperCss() {
        var link = document.querySelector('link[href*="coe-admin.css"]');
        return link ? link.href : null;
    }

    /**
     * Print … Paper (COE administrator): the paper laid out in a hidden frame and
     * handed to the browser's print dialog - no file, and no watermark on the
     * printed exam paper. Resolves once the dialog has been opened.
     *
     *   CoePaperViewer.print({ url: '…/original', title: 'QP-3-015 · Counselling' });
     */
    function print(o) {
        var frame = null;

        function remove() {
            if (frame && frame.parentNode) { frame.parentNode.removeChild(frame); }
        }

        return $.ajax({ url: o.url, method: 'GET', dataType: 'json', headers: { Accept: 'application/json' } })
            .then(function (res) {
                frame = document.createElement('iframe');
                frame.setAttribute('aria-hidden', 'true');
                frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
                document.body.appendChild(frame);

                var doc = frame.contentDocument;
                doc.open();
                doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title></title>' +
                    '<style>@page{margin:14mm}body{margin:0;background:#fff;' +
                    '-webkit-print-color-adjust:exact;print-color-adjust:exact}</style></head><body></body></html>');
                doc.close();
                doc.title = o.title || 'Question Paper';    // the browser prints it in the page header

                if (res.format === 'html') {
                    var css = paperCss();
                    var styled = $.Deferred();
                    if (css) {
                        var link = doc.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = css;
                        link.onload = link.onerror = function () { styled.resolve(); };
                        doc.head.appendChild(link);
                    } else {
                        styled.resolve();
                    }
                    var sheet = doc.createElement('div');
                    sheet.className = 'coe-paper-view';
                    sheet.innerHTML = res.content;          // cleaned on the server (PaperHtmlSanitizer)
                    doc.body.appendChild(sheet);
                    return styled.promise();
                }
                return docxPreview().then(function () {
                    return window.docx.renderAsync(toBytes(res.content), doc.body, doc.head, {
                        className: 'docx',
                        inWrapper: false,
                        breakPages: true,
                        useBase64URL: true,
                        renderComments: false
                    });
                });
            })
            .then(function () {
                var win = frame.contentWindow;
                win.addEventListener('afterprint', remove);
                setTimeout(remove, 60000);                  // a browser that never reports afterprint
                win.focus();
                win.print();
            }, function (err) {
                remove();
                return $.Deferred().reject(err && err.status === 404
                    ? 'This question paper is not available.'
                    : 'The question paper could not be printed. Please try again.').promise();
            });
    }

    /**
     * Screen-capture deterrents for a paper on screen, while o.active() says so:
     * o.shield(true) covers it whenever this window is not the one in use
     * (Win+Shift+S, Snipping Tool, Alt+Tab, another tab) or PrintScreen is
     * pressed, and o.shield(false) uncovers it on return; save / print / source
     * / devtools keys are swallowed, and copy keys too unless o.copyKeys.
     */
    function guard(o) {
        function blocks(e) {
            var key = (e.key || '').toLowerCase();
            var ctrl = e.ctrlKey || e.metaKey;
            return key === 'printscreen' || key === 'f12'
                || (ctrl && e.shiftKey && DEVTOOLS_KEYS.indexOf(key) !== -1)
                || (ctrl && SAVE_KEYS.indexOf(key) !== -1)
                || (ctrl && !o.copyKeys && COPY_KEYS.indexOf(key) !== -1);
        }

        // Capture phase, so nothing on the page sees these first.
        document.addEventListener('keydown', function (e) {
            if (!o.active() || !blocks(e)) { return; }
            e.preventDefault();
            e.stopPropagation();
            if ((e.key || '').toLowerCase() === 'printscreen') { o.shield(true); }
        }, true);
        // Windows takes the PrintScreen shot on key down and only reports key up:
        // empty the clipboard and keep the paper covered until the user returns.
        document.addEventListener('keyup', function (e) {
            if (!o.active() || (e.key || '').toLowerCase() !== 'printscreen') { return; }
            o.shield(true);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('').catch(function () {});
            }
        }, true);

        window.addEventListener('blur', function () { if (o.active()) { o.shield(true); } });
        window.addEventListener('focus', function () { o.shield(false); });
        document.addEventListener('visibilitychange', function () {
            if (o.active() && document.hidden) { o.shield(true); }
        });
    }

    /**
     * Copy and cut inside `el` (the editor) stay inside the page: the system
     * clipboard only gets CLIPBOARD_MARK, and pasting that back into `el` calls
     * insert(html) with what was copied. Pasting from outside (Word) still works.
     * Dragging text out is blocked.
     */
    function keepClipboard(el, insert) {
        var held = null;

        function take(e, cut) {
            var sel = window.getSelection();
            if (!sel || sel.isCollapsed || !sel.rangeCount) { return; }
            var box = document.createElement('div');
            box.appendChild(sel.getRangeAt(0).cloneContents());
            held = box.innerHTML;
            e.preventDefault();
            if (e.clipboardData) { e.clipboardData.setData('text/plain', CLIPBOARD_MARK); }
            if (cut) { document.execCommand('delete'); }
        }

        el.addEventListener('copy', function (e) { take(e, false); });
        el.addEventListener('cut', function (e) { take(e, true); });
        el.addEventListener('paste', function (e) {
            var text = e.clipboardData ? e.clipboardData.getData('text/plain') : '';
            if (held !== null && text === CLIPBOARD_MARK) {
                e.preventDefault();
                insert(held);
            }
        }, true);
        el.addEventListener('dragstart', function (e) { e.preventDefault(); });
    }

    function setup() {
        if (el) { return; }
        var root = document.getElementById('coePaperViewer');
        el = {
            root: root,
            content: root.querySelector('.coe-viewer'),
            title: document.getElementById('coePaperViewerTitle'),
            sub: document.getElementById('coePaperViewerSub'),
            state: document.getElementById('coePaperViewerState'),
            stage: document.getElementById('coePaperViewerStage'),
            sheet: document.getElementById('coePaperViewerSheet'),
            mark: document.getElementById('coePaperViewerMark')
        };
        modal = bootstrap.Modal.getOrCreateInstance(root);

        root.addEventListener('show.bs.modal', function () {
            isOpen = true;
            document.body.classList.add(NO_PRINT);
        });
        // Nothing of the paper stays in the page once it is closed.
        root.addEventListener('hidden.bs.modal', function () {
            isOpen = false;
            document.body.classList.remove(NO_PRINT);
            clear();
            state('');
        });

        ['contextmenu', 'copy', 'cut', 'dragstart', 'selectstart'].forEach(function (type) {
            root.addEventListener(type, function (e) { e.preventDefault(); });
        });

        guard({ active: function () { return isOpen; }, shield: shield });
        el.content.addEventListener('click', function () { shield(false); });
    }

    window.CoePaperViewer = { open: open, print: print };
    window.CoePaperGuard = { guard: guard, keepClipboard: keepClipboard, watermark: watermark, NO_PRINT: NO_PRINT };
})(window, document, jQuery);
