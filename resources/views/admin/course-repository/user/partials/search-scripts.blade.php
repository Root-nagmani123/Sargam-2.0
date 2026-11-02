@push('scripts')
<script>
(function () {
    'use strict';

    /* ── Sort / page-size selects ────────────────────────────────────────────
       Each <option> carries the full destination URL, so the current search is
       preserved without this script having to know any of the parameters. */
    document.querySelectorAll('select[data-cru-nav]').forEach(function (select) {
        select.addEventListener('change', function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });
    });

    /* ── Type-ahead suggestions ──────────────────────────────────────────────
       Suggests the values people search BY (topic, subject, author, batch,
       document title, category) rather than result rows, so choosing one runs a
       narrower search instead of jumping to a single file.

       The list items are <li>, not <a>: the module's capture-phase single-click
       handler (single-click-links.blade.php) forces navigation on every anchor,
       which would fight a click that is only meant to fill the input. */
    var input = document.getElementById('cruSearchInput');
    var list = document.getElementById('cruSuggestList');
    var form = document.getElementById('cruSearchForm');

    if (!input || !list || !form || typeof window.fetch !== 'function') return;

    var endpoint = input.getAttribute('data-cru-suggest-url');
    var minChars = 2;
    var debounceMs = 250;
    var timer = null;
    var controller = null;
    var activeIndex = -1;
    var items = [];

    function close() {
        list.hidden = true;
        list.innerHTML = '';
        items = [];
        activeIndex = -1;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
    }

    function highlight(text, term) {
        var frag = document.createDocumentFragment();
        var at = text.toLowerCase().indexOf(term.toLowerCase());

        if (!term || at === -1) {
            frag.appendChild(document.createTextNode(text));
            return frag;
        }

        frag.appendChild(document.createTextNode(text.slice(0, at)));
        var mark = document.createElement('mark');
        mark.className = 'cru-hl';
        mark.textContent = text.slice(at, at + term.length);
        frag.appendChild(mark);
        frag.appendChild(document.createTextNode(text.slice(at + term.length)));
        return frag;
    }

    function setActive(index) {
        if (!items.length) return;

        if (activeIndex >= 0 && items[activeIndex]) {
            items[activeIndex].classList.remove('active');
            items[activeIndex].setAttribute('aria-selected', 'false');
        }

        activeIndex = (index + items.length) % items.length;
        items[activeIndex].classList.add('active');
        items[activeIndex].setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', items[activeIndex].id);
        items[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    function choose(value) {
        input.value = value;
        close();
        form.submit();
    }

    function render(suggestions, term) {
        list.innerHTML = '';
        items = [];
        activeIndex = -1;

        if (!suggestions.length) {
            close();
            return;
        }

        suggestions.forEach(function (suggestion, i) {
            var li = document.createElement('li');
            li.className = 'cru-suggest-item';
            li.id = 'cruSuggest' + i;
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', 'false');

            var label = document.createElement('span');
            label.className = 'cru-suggest-label';
            label.appendChild(highlight(suggestion.label, term));

            var type = document.createElement('span');
            type.className = 'cru-suggest-type';
            type.textContent = suggestion.type;

            li.appendChild(label);
            li.appendChild(type);

            // mousedown, not click: the input's blur fires first on click and would
            // have already closed the list.
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                choose(suggestion.value);
            });
            li.addEventListener('mouseenter', function () { setActive(i); });

            list.appendChild(li);
            items.push(li);
        });

        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function request(term) {
        if (controller) controller.abort();
        controller = typeof AbortController === 'function' ? new AbortController() : null;

        fetch(endpoint + '?q=' + encodeURIComponent(term), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined
        })
            .then(function (res) { return res.ok ? res.json() : { suggestions: [] }; })
            .then(function (json) {
                // The box may have been cleared or retyped while this was in flight.
                if (input.value.trim() !== term) return;
                render((json && json.suggestions) || [], term);
            })
            .catch(function () { /* aborted or offline — leave the list as it is */ });
    }

    input.addEventListener('input', function () {
        var term = this.value.trim();
        window.clearTimeout(timer);

        if (term.length < minChars) {
            close();
            return;
        }

        timer = window.setTimeout(function () { request(term); }, debounceMs);
    });

    input.addEventListener('keydown', function (e) {
        if (list.hidden) {
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(activeIndex + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(activeIndex - 1);
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            items[activeIndex].dispatchEvent(new MouseEvent('mousedown'));
        } else if (e.key === 'Escape') {
            close();
        }
    });

    input.addEventListener('blur', function () {
        // Long enough for a mousedown on an item to land first.
        window.setTimeout(close, 150);
    });

    // Capture phase: the module's single-click handler stops propagation on link
    // clicks, so a bubble-phase listener here would never see them.
    document.addEventListener('click', function (e) {
        if (!e.target.closest || !e.target.closest('[data-cru-suggest-root]')) {
            close();
        }
    }, true);
})();
</script>
@endpush
