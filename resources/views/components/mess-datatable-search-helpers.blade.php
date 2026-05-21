<script>
(function() {
    if (typeof window.messDataTableBindSearchInputTrim !== 'undefined') {
        return;
    }

    var HL_CLASS = 'dt-search-highlight';

    function escapeRegExp(s) {
        return String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function unwrapSearchMarks(cell) {
        cell.querySelectorAll('mark.' + HL_CLASS).forEach(function(m) {
            var parent = m.parentNode;
            if (!parent) return;
            while (m.firstChild) parent.insertBefore(m.firstChild, m);
            parent.removeChild(m);
        });
        if (cell.normalize) cell.normalize();
    }

    function replaceMatchesInTextNode(textNode, regex) {
        var text = textNode.nodeValue;
        if (text === null || text === '') return;

        var r = new RegExp(regex.source, 'gi');
        if (!r.test(text)) return;
        r.lastIndex = 0;

        var lastIndex = 0;
        var frag = document.createDocumentFragment();
        var match;
        var emptyGuard = 0;
        while ((match = r.exec(text)) !== null) {
            if (match.index > lastIndex) {
                frag.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
            }
            var mk = document.createElement('mark');
            mk.className = HL_CLASS;
            mk.appendChild(document.createTextNode(match[0]));
            frag.appendChild(mk);
            lastIndex = match.index + match[0].length;
            if (match[0].length === 0) {
                r.lastIndex++;
                if (++emptyGuard > text.length + 50) break;
            }
            if (r.lastIndex >= text.length) break;
        }
        frag.appendChild(document.createTextNode(text.slice(lastIndex)));
        if (!frag.childNodes.length) return;
        textNode.parentNode.replaceChild(frag, textNode);
    }

    function highlightCellPlainTextRoots(cell, regex) {
        var nodes = [];
        var w = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT, null, false);
        var n;
        while ((n = w.nextNode())) {
            var anc = n.parentElement;
            if (!anc) continue;
            if (anc.closest && anc.closest('mark.' + HL_CLASS)) continue;
            var up = anc;
            var skipAncest = false;
            while (up) {
                var tag = String(up.tagName || '').toUpperCase();
                if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT') {
                    skipAncest = true;
                    break;
                }
                up = up.parentElement;
            }
            if (skipAncest) continue;
            nodes.push(n);
        }
        nodes.forEach(function(textNode) {
            if (!textNode.parentElement) return;
            replaceMatchesInTextNode(textNode, regex);
        });
    }

    window.messDataTableApplySearchHighlight = function(api, excludedColIndices) {
        if (!api) return;

        var raw = '';
        try {
            raw = (typeof api.search === 'function' ? api.search() : '') || '';
        } catch (e) {
            raw = '';
        }
        var search = String(raw).trim();

        var tableEl = api.table().node ? api.table().node() : api.table()[0];
        if (!tableEl) return;

        Array.prototype.slice.call(tableEl.querySelectorAll('tbody td')).forEach(function(td) {
            unwrapSearchMarks(td);
        });

        if (!search) return;

        var skip = {};
        (excludedColIndices || []).forEach(function(idx) {
            skip[Number(idx)] = true;
        });

        var terms = search.split(/\s+/).map(function(t) { return t.trim(); }).filter(Boolean);
        var seen = {};
        var uniq = [];
        terms.forEach(function(t) {
            var k = t.toLowerCase();
            if (!seen[k]) {
                seen[k] = true;
                uniq.push(t);
            }
        });
        uniq.sort(function(a, b) {
            return b.length - a.length;
        });

        var escaped = uniq.filter(function(t) {
            return t.length > 0;
        }).map(escapeRegExp);
        if (!escaped.length) return;

        var mergedRe = new RegExp('(?:' + escaped.join('|') + ')', 'gi');

        api.rows({ search: 'applied' }).every(function() {
            var rowEl = this.node();
            if (!rowEl) return;
            if (rowEl.querySelector && rowEl.querySelector('td[colspan]')) return;
            var cells = rowEl.cells;
            for (var c = 0; c < cells.length; c++) {
                if (skip[c]) continue;
                highlightCellPlainTextRoots(cells[c], mergedRe);
            }
        });
    };

    window.messDataTableNormalizeSearchValue = function(val) {
        return String(val || '')
            .replace(/\u00a0/g, ' ')
            .replace(/^\s+/, '')
            .replace(/[ \t]{2,}/g, ' ');
    };

    window.messDataTableSearchQueryValue = function(val) {
        return window.messDataTableNormalizeSearchValue(val).trim();
    };

    window.messDataTableStripHtmlForSearch = function(s) {
        if (typeof window.jQuery === 'undefined') {
            return String(s).replace(/<[^>]*>/g, '');
        }
        try {
            return window.jQuery('<div>').append(window.jQuery.parseHTML(String(s))).text();
        } catch (e) {
            return String(s).replace(/<[^>]*>/g, '');
        }
    };

    if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.dataTable && !window._messDtMultiWordSearchHooked) {
        window._messDtMultiWordSearchHooked = true;
        window.jQuery.fn.dataTable.ext.search.push(function(settings, data) {
            if (!settings._messCustomMultiWordSearch) {
                return true;
            }
            var api = new window.jQuery.fn.dataTable.Api(settings);
            var raw = window.messDataTableSearchQueryValue(
                typeof api.search === 'function' ? api.search() : ''
            );
            if (!raw) {
                return true;
            }
            var tokens = raw.split(/\s+/).filter(Boolean);
            if (!tokens.length) {
                return true;
            }
            var haystack = data.map(function(cell) {
                return window.messDataTableStripHtmlForSearch(cell).replace(/\s+/g, ' ').trim();
            }).join(' ').toLowerCase();
            return tokens.every(function(t) {
                return haystack.indexOf(String(t).toLowerCase()) !== -1;
            });
        });
    }

    window.messDataTableBindSearchInputTrim = function(api) {
        if (!api || !api.table) {
            return;
        }
        var container = api.table().container();
        if (!container) {
            return;
        }
        var $filter = window.jQuery(container).find('.dataTables_filter input');
        $filter.off('input.messDtSearchTrim').on('input.messDtSearchTrim', function() {
            var displayVal = window.messDataTableNormalizeSearchValue(this.value);
            if (this.value !== displayVal) {
                this.value = displayVal;
            }
            var queryVal = window.messDataTableSearchQueryValue(displayVal);
            if (typeof api.search === 'function' && api.search() !== queryVal) {
                api.search(queryVal).draw();
            }
        });
    };
})();
</script>
