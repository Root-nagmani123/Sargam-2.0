{{--
  Choices.js 10.x styled like Bootstrap 5 form-select.
  Wrap page content in .choices-bs-scope and use select.form-select (optional data-placeholder, data-no-choices to skip).
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
    .choices-bs-scope .card.overflow-hidden { overflow: visible; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
(function () {
    function choicesBsPlaceholder(el) {
        var attr = el.getAttribute('data-placeholder');
        if (attr) return attr;
        var first = el.options[0];
        if (first && !first.value && first.textContent) return first.textContent.trim();
        return 'Choose…';
    }

    function choicesBsOptions(el) {
        var multiple = el.hasAttribute('multiple');
        return {
            searchEnabled: true,
            shouldSort: false,
            allowHTML: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: choicesBsPlaceholder(el),
            searchPlaceholderValue: 'Search…',
            position: 'bottom',
            removeItemButton: multiple
        };
    }

    function initChoicesBootstrap5In(root) {
        if (typeof Choices === 'undefined' || !root) return;
        root.querySelectorAll('select.form-select, select.form-control').forEach(function (el) {
            if (el.getAttribute('data-no-choices') !== null) return;
            if (el.classList.contains('select2-hidden-accessible')) return;
            if (el.classList.contains('tomselected')) return;
            if (/select2/i.test(el.className || '')) return;
            if (el.parentElement && el.parentElement.classList.contains('choices')) return;
            try {
                el._choicesBs = new Choices(el, choicesBsOptions(el));
            } catch (e) {
                console.warn('Choices init failed', e);
            }
        });
    }

    window.initChoicesBootstrap5In = initChoicesBootstrap5In;

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
