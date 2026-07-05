{{--
  Choices.js 10.x styled like Bootstrap 5 form-select.
  Wrap page content in .choices-bs-scope and use select.form-select (optional data-placeholder, data-no-choices to skip).
  Options may use data-short or data-login for custom search (same pattern as FC activity departments setup).
--}}
@once('admin-choices-bootstrap5-assets')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css"/>
<style>
    .choices-bs-scope .choices { margin-bottom: 0; font-size: 1rem; max-width: 100%; }
    .choices-bs-scope .choices .choices__inner {
        display: inline-block;
        width: 100%;
        min-height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }
    .choices-bs-scope .choices.is-focused .choices__inner,
    .choices-bs-scope .choices.is-open .choices__inner {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    }
    .choices-bs-scope .choices[data-type*="select-one"] .choices__inner { padding-bottom: 0.375rem; }
    .choices-bs-scope .choices__list--single { padding: 0; }
    .choices-bs-scope .choices__list--single .choices__item { padding: 0; }
    .choices-bs-scope .choices[data-type*="select-one"] .choices__input {
        padding: 0.375rem 0.75rem;
        background-color: var(--bs-body-bg);
    }
    .choices-bs-scope .choices__list--dropdown .choices__item,
    .choices-bs-scope .choices__list[aria-expanded] .choices__item { padding: 0.375rem 0.75rem; }
    .choices-bs-scope .choices__list--dropdown .choices__item--selectable.is-highlighted,
    .choices-bs-scope .choices__list[aria-expanded] .choices__item--selectable.is-highlighted {
        background-color: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
    }
    .choices-bs-scope .choices__list--dropdown,
    .choices-bs-scope .choices__list[aria-expanded] {
        border-color: var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        box-shadow: var(--bs-box-shadow);
        z-index: 1060;
    }
    .choices-bs-scope .choices[data-type*="select-multiple"] .choices__button {
        border-left: 1px solid rgba(0, 0, 0, 0.12);
        margin-left: 0.35rem;
        padding-left: 0.35rem;
    }
    .choices-bs-scope .card.overflow-hidden { overflow: visible; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
(function () {
    function normalizeChoicesSearchText(text) {
        return String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function selectOptionByValue(selectEl, value) {
        if (!selectEl) return null;
        for (var i = 0; i < selectEl.options.length; i++) {
            if (String(selectEl.options[i].value) === String(value)) {
                return selectEl.options[i];
            }
        }
        return null;
    }

    function applyChoicesCustomSearchFilter(instance, rawQuery) {
        if (!instance || !instance.dropdown || !instance.dropdown.element) return;
        var selectEl = instance.passedElement && instance.passedElement.element ? instance.passedElement.element : null;
        if (!selectEl) return;
        var dropdownEl = instance.dropdown.element;
        var query = normalizeChoicesSearchText(rawQuery);
        var choiceItems = dropdownEl.querySelectorAll('.choices__item--choice');
        if (!choiceItems || !choiceItems.length) return;

        choiceItems.forEach(function (item) {
            if (item.classList.contains('choices__placeholder')) return;
            var label = normalizeChoicesSearchText(item.textContent || '');
            var value = normalizeChoicesSearchText(item.getAttribute('data-value') || '');
            var short = '';
            var login = '';
            var dv = item.getAttribute('data-value');
            if (dv) {
                var opt = selectOptionByValue(selectEl, dv);
                if (opt && opt.dataset) {
                    if (opt.dataset.short) short = normalizeChoicesSearchText(opt.dataset.short);
                    if (opt.dataset.login) login = normalizeChoicesSearchText(opt.dataset.login);
                }
            }
            var show = !query || label.indexOf(query) !== -1 || value.indexOf(query) !== -1
                || short.indexOf(query) !== -1 || login.indexOf(query) !== -1;
            item.style.display = show ? '' : 'none';
        });
    }

    function usesCustomChoicesSearch(el) {
        if (!el || el.getAttribute('data-search') === 'false') return false;
        if (el.getAttribute('data-search') === 'true') return true;
        for (var i = 0; i < el.options.length; i++) {
            var o = el.options[i];
            if (o.dataset && (o.dataset.short || o.dataset.login)) return true;
        }
        return false;
    }

    function wireChoicesCustomSearch(el, instance) {
        if (!usesCustomChoicesSearch(el)) return;

        function applySearchFilterAfterRender() {
            var typed = (instance.input && instance.input.element) ? (instance.input.element.value || '') : '';
            requestAnimationFrame(function () {
                applyChoicesCustomSearchFilter(instance, typed);
            });
        }

        el.addEventListener('showDropdown', applySearchFilterAfterRender);
        if (instance.input && instance.input.element) {
            instance.input.element.addEventListener('input', applySearchFilterAfterRender);
            instance.input.element.addEventListener('keyup', applySearchFilterAfterRender);
        }
    }

    function choicesBsPlaceholder(el) {
        var attr = el.getAttribute('data-placeholder');
        if (attr) return attr;
        var first = el.options[0];
        if (first && !first.value && first.textContent) return first.textContent.trim();
        return 'Choose…';
    }

    function choicesBsOptions(el) {
        var multiple = el.hasAttribute('multiple');
        var customSearch = usesCustomChoicesSearch(el);
        return {
            searchEnabled: true,
            searchChoices: !customSearch,
            shouldSort: false,
            allowHTML: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: choicesBsPlaceholder(el),
            searchPlaceholderValue: 'Search…',
            position: 'bottom',
            removeItemButton: multiple,
            closeDropdownOnSelect: !multiple
        };
    }

    function destroyChoicesBootstrap5(el) {
        if (!el || !el._choicesBs) return;
        try {
            el._choicesBs.destroy();
        } catch (e) {}
        el._choicesBs = null;
    }

    function initChoicesBootstrap5Element(el) {
        if (typeof Choices === 'undefined' || !el) return;
        if (el.getAttribute('data-no-choices') !== null) return;
        if (el.classList.contains('js-cru-filter-choice')) return;
        if (el.classList.contains('select2-hidden-accessible')) return;
        if (el.classList.contains('tomselected')) return;
        if (/select2/i.test(el.className || '')) return;
        if (el.parentElement && el.parentElement.classList.contains('choices')) return;

        destroyChoicesBootstrap5(el);
        try {
            var instance = new Choices(el, choicesBsOptions(el));
            el._choicesBs = instance;
            wireChoicesCustomSearch(el, instance);
        } catch (e) {
            console.warn('Choices init failed', e);
        }
    }

    function initChoicesBootstrap5In(root) {
        if (!root) return;
        root.querySelectorAll('select.form-select, select.form-control').forEach(initChoicesBootstrap5Element);
    }

    window.initChoicesBootstrap5In = initChoicesBootstrap5In;
    window.initChoicesBootstrap5Element = initChoicesBootstrap5Element;
    window.destroyChoicesBootstrap5 = destroyChoicesBootstrap5;
    window.reinitChoicesBootstrap5 = initChoicesBootstrap5Element;

    document.addEventListener('DOMContentLoaded', function () {
        var main = document.getElementById('main-content');
        if (main && main.classList.contains('choices-bs-scope')) {
            initChoicesBootstrap5In(main);
            return;
        }
        document.querySelectorAll('.choices-bs-scope').forEach(function (scope) {
            initChoicesBootstrap5In(scope);
        });
    });
})();
</script>
@endpush
@endonce
