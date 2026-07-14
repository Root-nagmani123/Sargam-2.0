{{-- Choices.js searchable dropdowns for flat-field steps.
     Any field wrapper carrying the `choices-field` class becomes a searchable dropdown. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<style>
    .choices-field .choices { margin-bottom: 0; }
    .choices-field .choices__inner {
        min-height: calc(1.5em + .75rem + 2px);
        padding: .35rem .75rem; font-size: 1rem; line-height: 1.5;
        border-radius: .375rem; background: #fff; border: 1px solid #dee2e6;
    }
    .choices-field .choices__list--single { padding: 0; }
    .choices-field .choices__list--dropdown .choices__item { font-size: 1rem; }
    .choices-field .choices[data-type*="select-one"]::after { right: 11px; }
</style>
<script>
(function () {
    function initChoices() {
        if (typeof window.Choices === 'undefined') { return; }
        document.querySelectorAll('.choices-field select').forEach(function (sel) {
            if (sel._choices) { return; }
            try {
                sel._choices = new window.Choices(sel, {
                    searchEnabled: true,
                    shouldSort: false,
                    itemSelectText: '',
                    searchPlaceholderValue: 'Type to search…',
                    placeholderValue: '-- Select --',
                });
            } catch (e) {}
        });
    }
    window.fcInitChoices = initChoices;
    if (window.jQuery) { jQuery(initChoices); }
    else if (document.readyState !== 'loading') { initChoices(); }
    else { document.addEventListener('DOMContentLoaded', initChoices); }
})();
</script>
